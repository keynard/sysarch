<?php
session_start();
include 'db.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch feedback from the database
$feedbackLogs = [];
try {
    $feedbackQuery = "SELECT f.feedback_id, s.student_number, s.firstname, s.lastname, f.feedback_text, f.created_at
                      FROM feedback f
                      JOIN students s ON f.student_id = s.student_id
                      ORDER BY f.created_at DESC";
    $feedbackStmt = $conn->prepare($feedbackQuery);
    $feedbackStmt->execute();
    $feedbackLogs = $feedbackStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching feedback: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Reports</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
        font-family: Arial, sans-serif;
        margin: 0; /* Remove default margin from the body */
        padding: 0; /* Remove default padding from the body */
        background-color: #f5f5f5;
        }
        .page-title {
        text-align: center;
        margin-bottom: 20px;
        font-size: 24px;
        font-weight: bold;
       }
        table {
            width: 98%;
            border-collapse: collapse;
            margin: 20px auto;
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
        .header {
        background-color: #004d99;
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 0; /* Ensure no margin around the header */
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
            margin-left: 15px;
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
<div class="header">
        <h1>College of Computer Studies Admin</h1>
        <div class="nav-links">
            <a href="admin.php">Home</a>
            
           
            
            <a  style="cursor: pointer;" onclick="document.getElementById('searchModal').style.display='block'">Search</a>
            <a href="#">Navigate</a>
            <a href="reservation_handler.php">Sit-in</a>
            <a href="sitin_records.php">Sit-in Records</a>
            <a href="#">Sit-in Reports</a>
            <a href="feedback_report.php">Feedback Reports</a>
            <a href="#">Reservation</a>
            <a class="logout-btn" href="dashboard_main.php">Log Out</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Feedback ID</th>
                <th>Student Number</th>
                <th>Name</th>
                <th>Feedback</th>
                <th>Submitted At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($feedbackLogs) > 0): ?>
                <?php foreach ($feedbackLogs as $feedback): ?>
                    <tr>
                        <td><?= htmlspecialchars($feedback['feedback_id']) ?></td>
                        <td><?= htmlspecialchars($feedback['student_number']) ?></td>
                        <td><?= htmlspecialchars($feedback['firstname'] . ' ' . $feedback['lastname']) ?></td>
                        <td><?= htmlspecialchars($feedback['feedback_text']) ?></td>
                        <td><?= htmlspecialchars($feedback['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No feedback available.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>