<?php
session_start();
include 'db.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle reservation approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action'];
    
    try {
        $conn->beginTransaction();
        
        if ($action === 'approve') {
            // Update reservation status
            $stmt = $conn->prepare("UPDATE reservations SET status = 'approved' WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);
            
            // Deduct one session from student
            $stmt = $conn->prepare("UPDATE students s 
                                  JOIN reservations r ON s.student_id = r.student_id 
                                  SET s.sessions = s.sessions - 1 
                                  WHERE r.reservation_id = ?");
            $stmt->execute([$reservation_id]);
        } else if ($action === 'disapprove') {
            $stmt = $conn->prepare("UPDATE reservations SET status = 'rejected' WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);
        }
        
        $conn->commit();
        echo "<script>alert('Reservation " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully!');</script>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}

// Fetch pending reservations
$pending_query = "SELECT r.*, s.student_number, s.firstname, s.lastname, s.sessions 
                 FROM reservations r 
                 JOIN students s ON r.student_id = s.student_id 
                 WHERE r.status = 'pending' 
                 ORDER BY r.reservation_date, r.reservation_time";
$pending_stmt = $conn->prepare($pending_query);
$pending_stmt->execute();
$pending_reservations = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch PC usage status
$pc_status_query = "SELECT r.laboratory_number, r.pc_number, 
                           COUNT(CASE WHEN r.status = 'approved' AND 
                                         r.reservation_date = CURDATE() AND 
                                         r.reservation_time <= CURTIME() AND 
                                         NOT EXISTS (
                                             SELECT 1 FROM SitIn_Log s 
                                             WHERE s.student_id = r.student_id 
                                             AND s.laboratory_number = r.laboratory_number
                                             AND s.time_out IS NULL
                                         ) 
                                    THEN 1 END) as is_used
                    FROM reservations r
                    GROUP BY r.laboratory_number, r.pc_number";
$pc_status_stmt = $conn->prepare($pc_status_query);
$pc_status_stmt->execute();
$pc_status = $pc_status_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Reservation Management</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
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
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .pc-item {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .pc-item.used {
            background-color: #ffebee;
            border-color: #ef5350;
        }
        .pc-item.available {
            background-color: #e8f5e9;
            border-color: #66bb6a;
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
        }
        .action-buttons {
            margin-top: 10px;
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
        }
        .nav-links a:hover {
            background-color: #0d3c7a;
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

    <div class="container">
        <!-- Computer Control Section -->
        <div class="computer-control">
            <h3>Computer Control</h3>
            
            <div class="filter-section">
                <select class="w3-select" onchange="filterLab(this.value)">
                    <option value="">All Laboratories</option>
                    <option value="Lab 524">Laboratory 524</option>
                    <option value="Lab 526">Laboratory 526</option>
                    <option value="Lab 544">Laboratory 544</option>
                    <option value="Lab 528">Laboratory 528</option>
                    <option value="Lab 530">Laboratory 530</option>
                </select>
            </div>

            <div class="pc-grid">
                <?php for($i = 1; $i <= 80; $i++): 
                    $is_used = false;
                    foreach($pc_status as $status) {
                        if($status['pc_number'] == "PC $i" && $status['is_used'] > 0) {
                            $is_used = true;
                            break;
                        }
                    }
                ?>
                    <div class="pc-item <?php echo $is_used ? 'used' : 'available'; ?>">
                        PC <?php echo $i; ?>
                        <div class="status"><?php echo $is_used ? 'Used' : 'Available'; ?></div>
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
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                <button type="submit" name="action" value="approve" class="w3-button w3-green w3-round">Approve</button>
                                <button type="submit" name="action" value="disapprove" class="w3-button w3-red w3-round">Disapprove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No pending reservation requests.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterLab(lab) {
            // Add lab filtering functionality here
            console.log('Filtering for lab:', lab);
        }
    </script>
</body>
</html> 