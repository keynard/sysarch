<?php
session_start();
include 'db.php';

// Ensure student is logged in
if (!isset($_SESSION['student_number'])) {
    header("Location: login.php");
    exit();
}

// Fetch student details based on student_number
$student_number = $_SESSION['student_number'];

try {
    $query = "SELECT student_id, student_number, lastname, firstname, middlename, 
                     course, address, year_level, email, profile_picture, sessions
              FROM students WHERE student_number = :student_number";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_number', $student_number, PDO::PARAM_STR);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die('Student not found.');
    }

    $student_id = $student['student_id'];

    // Fetch latest sit-in session details
    $sitInQuery = "SELECT laboratory_number, purpose, time_in 
                   FROM SitIn_Log 
                   WHERE student_id = :student_id 
                   ORDER BY time_in DESC LIMIT 1";

    $sitInStmt = $conn->prepare($sitInQuery);
    $sitInStmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $sitInStmt->execute();
    $sitIn = $sitInStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch announcements from the database
    $announcementQuery = "SELECT title, content, created_at 
                          FROM announcement 
                          ORDER BY created_at DESC";

    $announcementStmt = $conn->prepare($announcementQuery);
    $announcementStmt->execute();
    $announcements = $announcementStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Default profile picture if none is set
$profile_picture_url = !empty($student['profile_picture']) ? htmlspecialchars($student['profile_picture']) . '?' . time() : 'default-profile.png';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        background: url('uc-campus.png') no-repeat center center fixed;
        background-size: cover;
    }
    .header {
        background: rgb(9, 32, 160);
        color: white;
        padding: 15px;
        text-align: center;
        font-size: 24px;
        position: relative;
    }
    .menu-icon {
        font-size: 30px;
        cursor: pointer;
        position: absolute;
        left: 20px;
        top: 10px;
    }
    .logout, .sit-in {
        position: absolute;
        top: 17px;
        font-size: 16px;
        color: white;
        cursor: pointer;
        transition: color 0.3s;
        text-decoration: none;
    }
    .logout {
        right: 20px;
    }
    .sit-in {
        right: 100px;
    }
    .logout:hover, .sit-in:hover {
        color:rgb(5, 2, 2);
    }
    .sidebar {
        height: 100%;
        width: 0;
        position: fixed;
        top: 0;
        left: 0;
        background-color: #f0f0f0;
        overflow-x: hidden;
        transition: 0.3s;
        padding-top: 60px;
    }
    .sidebar a {
        padding: 10px 20px;
        text-decoration: none;
        font-size: 18px;
        color: black;
        display: block;
        transition: 0.3s;
    }
    .sidebar a:hover {
        background-color: #575757;
    }
    .sidebar .close-btn {
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 30px;
        cursor: pointer;
    }
    .profile-info {
        color: black;
        padding: 20px;
    }
    .profile-info img {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        display: block;
        margin: 0 auto 10px auto;
    }
    .profile-info p {
        margin: 10px 0;
    }
    .announcement-container {
        margin: 20px;
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
    }
    .announcement-title {
        font-size: 20px;
        font-weight: bold;
    }
    .announcement-content {
        margin-top: 10px;
    }
    .announcement-date {
        font-size: 12px;
        color: gray;
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgb(0,0,0);
        background-color: rgba(0,0,0,0.4);
        padding-top: 60px;
    }
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
    }
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }
    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    </style>
</head>
<body>

<div class="header">
    <span class="menu-icon" onclick="openNav()">&#9776;</span>
    DASHBOARD
    <a href="login.php" class="logout">Logout</a>
    <a href="#" class="sit-in" onclick="document.getElementById('sitInModal').style.display='block'">Sit-in</a>
</div>

<!-- Sidebar (Hamburger Menu) -->
<div id="sidebar" class="sidebar">
    <span class="close-btn" onclick="closeNav()">&times;</span>
    <div class="profile-info">
        <img src="<?= $profile_picture_url ?>" alt="Profile Picture">
        <p><strong>Name:</strong> <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($student['email']); ?></p>
        <p><strong>Year:</strong> <?= htmlspecialchars($student['year_level']); ?></p>
        <p><strong>Course:</strong> <?= htmlspecialchars($student['course']); ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($student['address']); ?></p>
        <p><strong>Session:</strong> <?= htmlspecialchars($student['sessions']); ?></p>
        <?php if ($sitIn): ?>
            <p><strong>Purpose of Sit-in:</strong> <?= htmlspecialchars($sitIn['purpose']); ?></p>
            <p><strong>Laboratory Number:</strong> <?= htmlspecialchars($sitIn['laboratory_number']); ?></p>
            <p><strong>Time In:</strong> <?= htmlspecialchars($sitIn['time_in']); ?></p>
        <?php else: ?>
            <p><strong>Purpose of Sit-in:</strong> Not available</p>
            <p><strong>Laboratory Number:</strong> Not available</p>
            <p><strong>Time In:</strong> Not available</p>
        <?php endif; ?>
    </div>
</div>

<!-- Announcements Section -->
<div class="announcement-container">
    <h2>Announcements</h2>
    <?php if (empty($announcements)): ?>
        <p>No announcements available.</p>
    <?php else: ?>
        <?php foreach ($announcements as $announcement): ?>
            <div class="announcement">
                <p class="announcement-title"><?= htmlspecialchars($announcement['title']); ?></p>
                <p class="announcement-content"><?= nl2br(htmlspecialchars($announcement['content'])); ?></p>
                <p class="announcement-date">Posted on: <?= htmlspecialchars($announcement['created_at']); ?></p>
                <hr>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


<script>
function openNav() {
    document.getElementById("sidebar").style.width = "300px";
}

function closeNav() {
    document.getElementById("sidebar").style.width = "0";
}
</script>

</body>
</html>