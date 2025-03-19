<?php
session_start();
include 'db.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle reservation approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $reservationId = $_POST['reservation_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    $updateQuery = "UPDATE reservations SET status = :status WHERE reservation_id = :reservation_id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':status', $action, PDO::PARAM_STR);
    $updateStmt->bindParam(':reservation_id', $reservationId, PDO::PARAM_INT);
    $updateStmt->execute();

    if ($action === 'approve') {
        // Insert into SitIn_Log if approved
        $insertQuery = "INSERT INTO SitIn_Log (student_id, laboratory_number, purpose) 
                        SELECT student_id, laboratory_number, purpose 
                        FROM reservations 
                        WHERE reservation_id = :reservation_id";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bindParam(':reservation_id', $reservationId, PDO::PARAM_INT);
        $insertStmt->execute();
    }

    echo "<script>alert('Reservation has been " . ($action === 'approve' ? 'approved' : 'rejected') . ".'); window.location.href='reservation_handler.php';</script>";
    exit();
}

// Handle logout of sit-in records
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout_sitin_id'])) {
    $sitinId = $_POST['logout_sitin_id'];

    $logoutQuery = "UPDATE SitIn_Log SET time_out = NOW() WHERE sitin_id = :sitin_id";
    $logoutStmt = $conn->prepare($logoutQuery);
    $logoutStmt->bindParam(':sitin_id', $sitinId, PDO::PARAM_INT);
    $logoutStmt->execute();

    echo "<script>alert('Student has been logged out.'); window.location.href='reservation_handler.php';</script>";
    exit();
}

// Fetch approved records from SitIn_Log and remaining sessions
$sitInQuery = "SELECT l.sitin_id, s.student_number, s.firstname, s.lastname, s.sessions, l.laboratory_number, l.purpose, l.time_in
               FROM SitIn_Log l
               JOIN students s ON l.student_id = s.student_id
               WHERE l.time_out IS NULL
               ORDER BY l.time_in DESC";
$sitInStmt = $conn->prepare($sitInQuery);
$sitInStmt->execute();
$sitInLogs = $sitInStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Records</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .navbar {
            background: rgb(9, 32, 160);
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 24px;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .menu-icon {
            font-size: 30px;
            cursor: pointer;
            margin-left: 20px;
        }
        .nav-links {
            display: flex;
            gap: 20px;
            margin-right: 20px;
        }
        .nav-links a {
            font-size: 16px;
            color: white;
            cursor: pointer;
            transition: color 0.3s;
            text-decoration: none;
        }
        .nav-links a:hover {
            color: rgb(5, 2, 2);
        }
        .header {
            background-color: #004d99;
            color: white;
            padding: 15px 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
        }
        .nav-links {
            display: flex;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
          
            font-size: 14px;
        }
        .logout-btn {
            background-color: #ffc107;
            color: black;
            border: none;
            padding: 5px 15px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="header">
        <h1>College of Computer Studies Admin</h1>
        <div class="nav-links">
            <a href="admin.php">Home</a>
            
           
            
            <a  style="cursor: pointer;" onclick="document.getElementById('searchModal').style.display='block'">Search</a>
            <a href="#">Navigate</a>
            <a href="reservation_handler.php">Sit-in</a>
            <a href="sitin_records.php">Sit-in Records</a>
            <a href="#">Sit-in Reports</a>
            <a href="#">Feedback Reports</a>
            <a href="#">Reservation</a>
            <a class="logout-btn" href="dashboard_main.php">Log Out</a>
        </div>
    </div>

    <div class="w3-container">
        <h2>Approved Sit-in Records</h2>
        <table>
            <thead>
                <tr>
                    <th>Sit-in ID</th>
                    <th>Student Number</th>
                    <th>Name</th>
                    <th>Remaining Sessions</th>
                    <th>Laboratory Number</th>
                    <th>Purpose</th>
                    <th>Time In</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    <?php if (count($sitInLogs) > 0): ?>
        <?php foreach ($sitInLogs as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['sitin_id']) ?></td>
                <td><?= htmlspecialchars($log['student_number']) ?></td>
                <td><?= htmlspecialchars($log['firstname'] . ' ' . $log['lastname']) ?></td>
                <td><?= htmlspecialchars($log['sessions']) ?></td>
                <td><?= htmlspecialchars($log['laboratory_number']) ?></td>
                <td><?= htmlspecialchars($log['purpose']) ?></td>
                <td><?= htmlspecialchars($log['time_in']) ?></td>
                <td>
                    <form method="POST" action="reservation_handler.php" style="display: inline;">
                        <input type="hidden" name="logout_sitin_id" value="<?= $log['sitin_id'] ?>">
                        <button type="submit" class="w3-button w3-red">Logout</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" style="text-align: center;">No active sit-in records found.</td>
        </tr>
    <?php endif; ?>
</tbody>
        </table>
    </div>
</body>
</html>