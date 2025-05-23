<?php
session_start();
include 'db.php';

// Add debug logging
function debug_log($message) {
    $log_file = 'debug_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle reservation approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['reservation_id'])) {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action'];
    
    debug_log("Starting $action process for reservation_id: $reservation_id");
    $start_time = microtime(true);
    
    try {
        $conn->beginTransaction();
        
        if ($action === 'approve') {
            // Get reservation details first
            $stmt = $conn->prepare("SELECT r.*, s.student_id, s.sessions 
                                  FROM reservations r 
                                  JOIN students s ON r.student_id = s.student_id 
                                  WHERE r.reservation_id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            debug_log("Reservation details fetched in " . (microtime(true) - $start_time) . " seconds");
            
            if ($reservation && $reservation['sessions'] > 0) {
                // Update reservation status
                $stmt = $conn->prepare("UPDATE reservations SET status = 'approved' WHERE reservation_id = ?");
                $stmt->execute([$reservation_id]);
                
                // Create sit-in record
                if ($pc_number_exists && $reservation_id_exists) {
                    $stmt = $conn->prepare("INSERT INTO SitIn_Log (student_id, laboratory_number, pc_number, purpose, time_in, reservation_id) 
                                          VALUES (?, ?, ?, ?, NOW(), ?)");
                    $stmt->execute([
                        $reservation['student_id'],
                        $reservation['laboratory_number'],
                        $reservation['pc_number'],
                        $reservation['purpose'],
                        $reservation_id
                    ]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO SitIn_Log (student_id, laboratory_number, purpose, time_in) 
                                          VALUES (?, ?, ?, NOW())");
                    $stmt->execute([
                        $reservation['student_id'],
                        $reservation['laboratory_number'],
                        $reservation['purpose']
                    ]);
                }
                
                debug_log("Sit-in record created in " . (microtime(true) - $start_time) . " seconds");
                
                $conn->commit();
                debug_log("Transaction committed in " . (microtime(true) - $start_time) . " seconds");
                
                header("Location: admin_reservation.php?approved=1");
                exit();
            } else {
                $conn->rollBack();
                debug_log("Transaction rolled back: Student has no remaining sessions or reservation not found");
                echo "<script>alert('Error: Student has no remaining sessions or reservation not found.');</script>";
            }
        } else if ($action === 'disapprove') {
            $stmt = $conn->prepare("UPDATE reservations SET status = 'rejected' WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);
            
            debug_log("Reservation rejected in " . (microtime(true) - $start_time) . " seconds");
            
            $conn->commit();
            
            header("Location: admin_reservation.php?rejected=1");
            exit();
        }
    } catch (Exception $e) {
        $conn->rollBack();
        debug_log("Error: " . $e->getMessage());
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
    exit();
}

// Handle time out action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'timeout' && isset($_POST['pc_number'])) {
    $pc_number = $_POST['pc_number'];
    $lab_number = $_POST['lab_number'];
    
    debug_log("Starting timeout process for PC: $pc_number, Lab: $lab_number");
    $start_time = microtime(true);
    
    try {
        $conn->beginTransaction();
        
        // Find the active sit-in record for this lab
        $stmt = $conn->prepare("SELECT sitin_id FROM SitIn_Log 
                               WHERE laboratory_number = ? AND time_out IS NULL 
                               ORDER BY time_in DESC LIMIT 1");
        $stmt->execute([$lab_number]);
        $sitin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        debug_log("Active sit-in record found in " . (microtime(true) - $start_time) . " seconds");
        
        if ($sitin) {
            // Update the time_out field
            $stmt = $conn->prepare("UPDATE SitIn_Log SET time_out = NOW() WHERE sitin_id = ?");
            $stmt->execute([$sitin['sitin_id']]);
            
            debug_log("Time out updated in " . (microtime(true) - $start_time) . " seconds");
            
            $conn->commit();
            echo "<script>alert('PC marked as available.'); window.location.href='admin_reservation.php?lab=" . substr($lab_number, 4) . "';</script>";
        } else {
            $conn->rollBack();
            debug_log("No active session found for this laboratory");
            echo "<script>alert('Error: No active session found for this laboratory.');</script>";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        debug_log("Error: " . $e->getMessage());
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
    exit();
}

// Get selected lab filter
$selected_lab = isset($_GET['lab']) ? 'Lab ' . $_GET['lab'] : 'Lab 524'; // Default to lab 524

// Fetch pending reservations with lab filter
$start_time = microtime(true);
$pending_query = "SELECT r.*, s.student_number, s.firstname, s.lastname, s.sessions 
                 FROM reservations r 
                 JOIN students s ON r.student_id = s.student_id 
                 WHERE r.status = 'pending'";
if (!empty($selected_lab)) {
    $pending_query .= " AND r.laboratory_number = :lab";
}
$pending_query .= " ORDER BY r.reservation_date, r.reservation_time LIMIT 50"; // Add limit to prevent excessive data retrieval
$pending_stmt = $conn->prepare($pending_query);
if (!empty($selected_lab)) {
    $pending_stmt->bindParam(':lab', $selected_lab);
}
$pending_stmt->execute();
$pending_reservations = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
debug_log("Pending reservations fetched in " . (microtime(true) - $start_time) . " seconds");

// Check if pc_number and reservation_id columns exist in SitIn_Log table
$start_time = microtime(true);
$check_columns_query = "SHOW COLUMNS FROM SitIn_Log LIKE 'pc_number'";
$check_columns_stmt = $conn->prepare($check_columns_query);
$check_columns_stmt->execute();
$pc_number_exists = $check_columns_stmt->rowCount() > 0;

$check_columns_query = "SHOW COLUMNS FROM SitIn_Log LIKE 'reservation_id'";
$check_columns_stmt = $conn->prepare($check_columns_query);
$check_columns_stmt->execute();
$reservation_id_exists = $check_columns_stmt->rowCount() > 0;

debug_log("Column check completed in " . (microtime(true) - $start_time) . " seconds");
debug_log("pc_number exists: " . ($pc_number_exists ? "Yes" : "No"));
debug_log("reservation_id exists: " . ($reservation_id_exists ? "Yes" : "No"));

// Fetch PC usage status with student information - OPTIMIZED QUERY
$start_time = microtime(true);

if ($pc_number_exists && $reservation_id_exists) {
    // If both columns exist, use the optimized query
    $pc_status_query = "SELECT 
        sl.laboratory_number,
        sl.pc_number,
        CASE 
            WHEN sl.time_out IS NULL THEN 1
            ELSE 0
        END as is_used,
        CONCAT(st.firstname, ' ', st.lastname) as student_name,
        st.student_number,
        sl.purpose,
        sl.time_in
        FROM SitIn_Log sl
        INNER JOIN students st ON sl.student_id = st.student_id
        WHERE sl.time_out IS NULL
        AND sl.laboratory_number = :lab
        LIMIT 100"; // Add limit to prevent excessive data retrieval
} else if ($reservation_id_exists) {
    // If only reservation_id exists, join with reservations table
    $pc_status_query = "SELECT 
        sl.laboratory_number,
        r.pc_number,
        CASE 
            WHEN sl.time_out IS NULL THEN 1
            ELSE 0
        END as is_used,
        CONCAT(st.firstname, ' ', st.lastname) as student_name,
        st.student_number,
        sl.purpose,
        sl.time_in
        FROM SitIn_Log sl
        INNER JOIN students st ON sl.student_id = st.student_id
        INNER JOIN reservations r ON sl.reservation_id = r.reservation_id
        WHERE sl.time_out IS NULL
        AND sl.laboratory_number = :lab
        LIMIT 100"; // Add limit to prevent excessive data retrieval
} else {
    // If neither column exists, use the original query
    $pc_status_query = "SELECT 
        sl.laboratory_number,
        r.pc_number,
        CASE 
            WHEN sl.time_out IS NULL THEN 1
            ELSE 0
        END as is_used,
        CONCAT(st.firstname, ' ', st.lastname) as student_name,
        st.student_number,
        sl.purpose,
        sl.time_in
        FROM SitIn_Log sl
        INNER JOIN students st ON sl.student_id = st.student_id
        INNER JOIN reservations r ON sl.student_id = r.student_id 
            AND sl.laboratory_number = r.laboratory_number 
            AND r.status = 'approved'
            AND DATE(sl.time_in) = r.reservation_date
        WHERE sl.time_out IS NULL
        AND sl.laboratory_number = :lab
        LIMIT 100"; // Add limit to prevent excessive data retrieval
}

$pc_status_stmt = $conn->prepare($pc_status_query);
$pc_status_stmt->bindParam(':lab', $selected_lab);
$pc_status_stmt->execute();
$pc_status = $pc_status_stmt->fetchAll(PDO::FETCH_ASSOC);
debug_log("PC status fetched in " . (microtime(true) - $start_time) . " seconds");

// Create a map of used PCs for easier lookup
$used_pcs = [];
foreach ($pc_status as $status) {
    if ($status['is_used'] == 1) {
        $used_pcs[$status['pc_number']] = $status;
    }
}

// Debug information
echo "<!-- Debug Info: ";
echo "Selected Lab: " . $selected_lab . "\n";
echo "Number of PC Status Records: " . count($pc_status) . "\n";
foreach ($pc_status as $status) {
    echo "Lab: " . $status['laboratory_number'] . 
         ", PC: " . $status['pc_number'] . 
         ", Used: " . $status['is_used'] . 
         ", Student: " . $status['student_name'] . "\n";
}
echo " -->";

// After fetching PC status, add usage statistics
$total_labs = 5; // Total number of labs
$used_labs_count = count($used_pcs);
$available_labs_count = $total_labs - $used_labs_count;
$usage_percentage = ($used_labs_count / $total_labs) * 100;

// Check for success messages
$success_message = '';
if (isset($_GET['approved']) && $_GET['approved'] == 1) {
    $success_message = '<div class="w3-panel w3-green w3-display-container">
        <span onclick="this.parentElement.style.display=\'none\'" class="w3-button w3-green w3-large w3-display-topright">&times;</span>
        <h3>Success!</h3>
        <p>Reservation approved successfully.</p>
    </div>';
} else if (isset($_GET['rejected']) && $_GET['rejected'] == 1) {
    $success_message = '<div class="w3-panel w3-red w3-display-container">
        <span onclick="this.parentElement.style.display=\'none\'" class="w3-button w3-red w3-large w3-display-topright">&times;</span>
        <h3>Success!</h3>
        <p>Reservation rejected successfully.</p>
    </div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Reservation Management</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script>
        // Auto-refresh the page every 60 seconds
        setTimeout(function() {
            window.location.reload();
        }, 60000);
        
        function submitForm(action, formId) {
            const form = document.getElementById(formId);
            const button = event.target;
            
            // Disable the button and show loading state
            button.disabled = true;
            const originalText = button.textContent;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Create and append hidden input for action
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            // Submit the form
            form.submit();
        }
    </script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background-color: #004d99;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .computer-control {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .pc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .pc-item {
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 80px;
        }
        .pc-number {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .status-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 5px;
        }
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-dot.used {
            background-color: #ef5350;
        }
        .status-dot.available {
            background-color: #66bb6a;
        }
        .pc-item.used {
            background-color: #ffebee;
            border-color: #ef5350;
            position: relative;
            border-width: 2px;
            box-shadow: 0 0 5px rgba(239, 83, 80, 0.5);
        }
        .used-label {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #ef5350;
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 3px 6px;
            border-radius: 3px;
            z-index: 10;
        }
        .student-name {
            margin-top: 5px;
            font-size: 14px;
            font-weight: bold;
            color: #d32f2f;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 3px;
            border-radius: 3px;
        }
        .pc-item.available {
            background-color: #e8f5e9;
            border-color: #66bb6a;
        }
        .pc-info {
            font-size: 12px;
            margin-top: 5px;
        }
        .student-info {
            display: none;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            width: 200px;
            text-align: left;
        }
        .pc-item.used:hover .student-info {
            display: block;
        }
        .reservation-requests {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .request-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .request-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .action-buttons {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        .filter-section {
            margin-bottom: 15px;
        }
        .nav-links {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            background-color: #1a4c8b;
            transition: background-color 0.3s ease;
        }
        .nav-links a:hover {
            background-color: #0d3c7a;
        }
        .w3-button {
            transition: all 0.3s ease;
        }
        .w3-button:hover {
            opacity: 0.9;
        }
        .usage-summary {
            background: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .summary-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
        }
        .stat-item {
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            background-color: #f5f5f5;
            min-width: 120px;
        }
        .stat-label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
        }
        .stat-value.used {
            color: #ef5350;
        }
        .stat-value.available {
            color: #66bb6a;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Admin Module - Reservation Management</h2>
        <div class="nav-links">
            <a href="admin.php">Back to Dashboard</a>
        </div>
    </div>

    <?php echo $success_message; ?>

    <div class="container">
        <!-- Computer Control Section -->
        <div class="computer-control">
            <h3>Computer Control</h3>
            
            <div class="filter-section">
                <form method="GET" action="admin_reservation.php" class="w3-container">
                    <select class="w3-select" name="lab" onchange="this.form.submit()">
                        <option value="524" <?php echo $selected_lab === 'Lab 524' ? 'selected' : ''; ?>>Laboratory 524</option>
                        <option value="526" <?php echo $selected_lab === 'Lab 526' ? 'selected' : ''; ?>>Laboratory 526</option>
                        <option value="544" <?php echo $selected_lab === 'Lab 544' ? 'selected' : ''; ?>>Laboratory 544</option>
                        <option value="528" <?php echo $selected_lab === 'Lab 528' ? 'selected' : ''; ?>>Laboratory 528</option>
                        <option value="530" <?php echo $selected_lab === 'Lab 530' ? 'selected' : ''; ?>>Laboratory 530</option>
                    </select>
                    <button type="button" onclick="window.location.reload()" class="w3-button w3-blue w3-round" style="margin-top: 10px;">Refresh Status</button>
                </form>
            </div>

            <div class="pc-grid">
                <?php 
                $pcs_per_lab = 70; // 70 PCs per lab
                
                // If a specific lab is selected, show PCs 1-70 for that lab
                if (!empty($selected_lab)) {
                    $start_pc = 1;
                    $end_pc = 70;
                } else {
                    // If no lab is selected, show all labs with PCs 1-70
                    $start_pc = 1;
                    $end_pc = 70;
                }
                
                for($i = $start_pc; $i <= $end_pc; $i++): 
                    $pc_number = "PC $i";
                    $is_pc_used = isset($used_pcs[$pc_number]);
                    $pc_info = $is_pc_used ? $used_pcs[$pc_number] : null;
                ?>
                    <div class="pc-item <?php echo $is_pc_used ? 'used' : 'available'; ?>">
                        <?php if ($is_pc_used): ?>
                            <div class="used-label">USED</div>
                        <?php endif; ?>
                        <div class="pc-number"><?php echo $pc_number; ?></div>
                        <div class="status-indicator">
                            <span class="status-dot <?php echo $is_pc_used ? 'used' : 'available'; ?>"></span>
                            <span class="status-text"><?php echo $is_pc_used ? 'In Use' : 'Available'; ?></span>
                        </div>
                        <?php if ($is_pc_used && $pc_info): ?>
                            <div class="student-name">
                                <strong>Student:</strong> <?php echo htmlspecialchars($pc_info['student_name']); ?>
                            </div>
                            <div class="student-info">
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($pc_info['student_number']); ?></p>
                                <p><strong>Purpose:</strong> <?php echo htmlspecialchars($pc_info['purpose']); ?></p>
                                <p><strong>Time In:</strong> <?php echo htmlspecialchars(date('h:i A', strtotime($pc_info['time_in']))); ?></p>
                                <form method="POST" style="margin-top: 10px;">
                                    <input type="hidden" name="action" value="timeout">
                                    <input type="hidden" name="pc_number" value="<?php echo htmlspecialchars($pc_number); ?>">
                                    <input type="hidden" name="lab_number" value="<?php echo htmlspecialchars($selected_lab); ?>">
                                    <button type="submit" class="w3-button w3-red w3-round w3-small">Mark Lab as Available</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Reservation Requests Section -->
        <div class="reservation-requests">
            <h3>Reservation Requests</h3>
            
            <?php if(count($pending_reservations) > 0): ?>
                <?php foreach($pending_reservations as $reservation): ?>
                    <div class="request-item">
                        <p><strong>ID Number:</strong> <?php echo htmlspecialchars($reservation['student_number']); ?></p>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($reservation['firstname'] . ' ' . $reservation['lastname']); ?></p>
                        <p><strong>Laboratory:</strong> <?php echo htmlspecialchars($reservation['laboratory_number']); ?></p>
                        <p><strong>PC Number:</strong> <?php echo htmlspecialchars($reservation['pc_number']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($reservation['reservation_date']); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars(date('h:i A', strtotime($reservation['reservation_time']))); ?></p>
                        <p><strong>Purpose:</strong> <?php echo htmlspecialchars($reservation['purpose']); ?></p>
                        <p><strong>Remaining Sessions:</strong> <?php echo htmlspecialchars($reservation['sessions']); ?></p>
                        
                        <div class="action-buttons">
                            <form method="POST" style="display: inline;" id="approve-form-<?php echo $reservation['reservation_id']; ?>">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                <button type="button" onclick="submitForm('approve', 'approve-form-<?php echo $reservation['reservation_id']; ?>')" class="w3-button w3-green w3-round">Approve</button>
                                <button type="button" onclick="submitForm('disapprove', 'approve-form-<?php echo $reservation['reservation_id']; ?>')" class="w3-button w3-red w3-round">Disapprove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No pending reservation requests.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 