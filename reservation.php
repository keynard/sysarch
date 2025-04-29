<?php
session_start();
include 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['student_number'])) {
    header("Location: login.php");
    exit();
}

// Fetch student details
$student_number = $_SESSION['student_number'];
$stmt = $conn->prepare("SELECT * FROM students WHERE student_number = ?");
$stmt->execute([$student_number]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle reservation submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $laboratory_number = trim($_POST['laboratory_number']);
    $pc_number = trim($_POST['pc_number']);
    $purpose = trim($_POST['purpose']);
    
    // Use current date and time
    $reservation_date = date('Y-m-d');
    $reservation_time = date('H:i:s');

    // Validate inputs
    if (!$laboratory_number || !$pc_number || !$purpose) {
        echo "<script>alert('All fields are required!'); window.history.back();</script>";
        exit();
    }

    // Check remaining sessions
    if ($student['sessions'] <= 0) {
        echo "<script>alert('No remaining sessions available!'); window.history.back();</script>";
        exit();
    }

    // Check if PC is available
    $check_pc = $conn->prepare("SELECT COUNT(*) FROM reservations 
                               WHERE laboratory_number = ? 
                               AND pc_number = ? 
                               AND reservation_date = ? 
                               AND reservation_time = ? 
                               AND status = 'approved'");
    $check_pc->execute([$laboratory_number, $pc_number, $reservation_date, $reservation_time]);
    if ($check_pc->fetchColumn() > 0) {
        echo "<script>alert('This PC is already reserved for the selected time!'); window.history.back();</script>";
        exit();
    }

    try {
        // Insert reservation
        $query = "INSERT INTO reservations (student_id, laboratory_number, pc_number, purpose, 
                                          reservation_date, reservation_time, status) 
                 VALUES ((SELECT student_id FROM students WHERE student_number = ?), 
                         ?, ?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($query);
        $stmt->execute([$student_number, $laboratory_number, $pc_number, $purpose, 
                       $reservation_date, $reservation_time]);

        echo "<script>alert('Reservation submitted successfully!'); window.location.href='dashboard.php';</script>";
        exit();
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>";
        exit();
    }
}

// Handle approval or rejection by admin
if (isset($_SESSION['admin_id']) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $reservation_id = intval($_POST['reservation_id']);
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action === 'approve') {
        $query = "UPDATE reservations SET status = 'approved' WHERE reservation_id = :reservation_id";
    } elseif ($action === 'reject') {
        $query = "UPDATE reservations SET status = 'rejected' WHERE reservation_id = :reservation_id";
    } else {
        echo "<script>alert('Invalid action.'); window.history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare($query);

    try {
        $stmt->execute([':reservation_id' => $reservation_id]);
        echo "<script>alert('Reservation has been " . ($action === 'approve' ? "approved" : "rejected") . ".'); window.location.href='admin.php';</script>";
        exit();
    } catch (PDOException $e) {
        echo "<script>alert('Error updating reservation: " . $e->getMessage() . "'); window.history.back();</script>";
        exit();
    }
}

$conn = null; // Close the database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Module - Laboratory Reservation</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: url('uc-campus.png') no-repeat center center fixed;
            background-size: cover;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #004d99;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .sessions-display {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .button-container {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Student Module - Laboratory Reservation</h2>
        </div>

        <div class="sessions-display">
            <h4>Remaining Sessions: <?php echo htmlspecialchars($student['sessions']); ?></h4>
        </div>

        <form method="POST" action="reservation.php">
            <div class="form-group">
                <label>ID Number:</label>
                <input type="text" class="w3-input" value="<?php echo htmlspecialchars($student['student_number']); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Student Name:</label>
                <input type="text" class="w3-input" value="<?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Laboratory:</label>
                <select name="laboratory_number" class="w3-select" required>
                    <option value="" disabled selected>Select Laboratory</option>
                    <option value="Lab 524">Laboratory 524</option>
                    <option value="Lab 526">Laboratory 526</option>
                    <option value="Lab 544">Laboratory 544</option>
                    <option value="Lab 528">Laboratory 528</option>
                    <option value="Lab 530">Laboratory 530</option>
                </select>
            </div>

            <div class="form-group">
                <label>PC Number:</label>
                <select name="pc_number" class="w3-select" required>
                    <option value="" disabled selected>Select PC Number</option>
                    <?php for($i = 1; $i <= 80; $i++): ?>
                        <option value="PC <?php echo $i; ?>">PC <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Date:</label>
                <input type="text" class="w3-input" value="<?php echo date('Y-m-d'); ?>" readonly>
                <input type="hidden" name="reservation_date" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label>Time:</label>
                <input type="text" class="w3-input" value="<?php echo date('h:i A'); ?>" readonly>
                <input type="hidden" name="reservation_time" value="<?php echo date('H:i:s'); ?>">
            </div>

            <div class="form-group">
                <label>Purpose:</label>
                <textarea name="purpose" class="w3-input" rows="4" required placeholder="Enter the purpose of your reservation"></textarea>
            </div>

            <div class="button-container">
                <button type="submit" class="w3-button w3-blue w3-round">Reserve</button>
                <button type="button" onclick="window.location.href='dashboard.php'" class="w3-button w3-red w3-round">Cancel</button>
            </div>
        </form>
    </div>

    <script>
        // Remove the date picker script since we're using current date/time
    </script>
</body>
</html>