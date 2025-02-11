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
$query = "SELECT student_number, lastname, firstname, middlename, course, year_level, email FROM students WHERE student_number = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_number);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();
$conn->close();
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
        }
        .header {
            background: #3f51b5;
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
        .logout {
            position: absolute;
            right: 20px;
            top: 10px;
        }
        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #222;
            overflow-x: hidden;
            transition: 0.3s;
            padding-top: 60px;
        }
        .sidebar a {
            padding: 10px 20px;
            text-decoration: none;
            font-size: 18px;
            color: white;
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
            color: white;
            text-align: center;
            padding: 20px;
        }
        .profile-info img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
        }
    </style>
</head>
<body>

<div class="header">
    <span class="menu-icon" onclick="openNav()">&#9776;</span>
    DASHBOARD
    <a href="login.php" class="w3-button w3-red logout">Logout</a>
</div>

<!-- Sidebar (Hamburger Menu) -->
<div id="sidebar" class="sidebar">
    <span class="close-btn" onclick="closeNav()">&times;</span>

    <div class="profile-info">
        <img src="images.jfif" alt="Profile Picture">
        <h3><?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></h3>
        <p><?= htmlspecialchars($student['course']); ?> - Year <?= htmlspecialchars($student['year_level']); ?></p>
        <p><?= htmlspecialchars($student['email']); ?></p>
    </div>

    <a href="javascript:void(0)" onclick="document.getElementById('editModal').style.display='block'">Edit Profile</a>
</div>

<!-- Edit Modal -->
<div id="editModal" class="w3-modal">
    <div class="w3-modal-content w3-padding">
        <div class="w3-container">
            <span onclick="document.getElementById('editModal').style.display='none'" class="w3-button w3-display-topright">&times;</span>
            <h3>Edit Information</h3>
            <form action="update_profile.php" method="post">
                <label>First Name</label>
                <input type="text" name="firstname" value="<?= htmlspecialchars($student['firstname']); ?>" class="w3-input" required>
                
                <label>Last Name</label>
                <input type="text" name="lastname" value="<?= htmlspecialchars($student['lastname']); ?>" class="w3-input" required>
                
                <label>Course</label>
                <input type="text" name="course" value="<?= htmlspecialchars($student['course']); ?>" class="w3-input" required>
                
                <label>Year Level</label>
                <input type="number" name="year_level" value="<?= htmlspecialchars($student['year_level']); ?>" class="w3-input" required>
                
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($student['email']); ?>" class="w3-input" required>

                <button type="submit" class="w3-button w3-green w3-margin-top">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<script>
function openNav() {
    document.getElementById("sidebar").style.width = "250px";
}

function closeNav() {
    document.getElementById("sidebar").style.width = "0";
}
</script>

</body>
</html>
