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
$query = "SELECT student_number, lastname, firstname, middlename, course,address, year_level, email FROM students WHERE student_number = ?";
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
        background:rgb(9, 32, 160);
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
        padding: 10px 20px;
        font-size: 16px;
        background-color: #f44336;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .logout:hover {
        background-color: #d32f2f;
    }
    .sidebar {
        height: 100%;
        width: 0;
        position: fixed;
        top: 0;
        left: 0;
        background-color:  #f0f0f0;
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
    .hhh {
        text-align: center;
    }
    .section-header {
        background-color: #3f51b5;
        color: white;
        padding: 10px;
    }
    
</style>
</head>
<body>

<div class="header">
    <span class="menu-icon" onclick="openNav()">&#9776;</span>
    DASHBOARD
    <a href="login.php" class="w3-button w3-round-large w3-white logout">Logout</a>
</div>
<!-- Announcements Section -->
<div class="w3-container w3-padding-16">
   
    <div class="w3-card-4 w3-margin w3-white">
    <div class="section-header ">
        <h2 class="hhh">Announcements</h2>
    </div>
        <div class="w3-container">
            <h3 class="hhh">WAY KLASE <br> BIRTHDAY NAKO</h3>
            <h5>Subtitle, <span class="w3-opacity">Date</span></h5>
        </div>
        <div class="w3-container">
            <p>Details about the announcement...</p>
        </div>
    </div>
</div>

<!-- Rules and Regulations Section -->
<div class="w3-container w3-padding-16">
    
    <div class="w3-card-4 w3-margin w3-white">
    <div class="section-header ">
        <h2 class="hhh">Rules and Regulation</h2>
    </div>
        <div class="w3-container">
            <h3 class="hhh">University of Cebu <br>
            COLLEGE OF INFORMATION & COMPUTER STUDIES</h3>
        </div>
        <div class="w3-container">
            <p>Details about the rule...</p>
        </div>
    </div>
</div>
<!-- Sidebar (Hamburger Menu) -->
<div id="sidebar" class="sidebar">
    <span class="close-btn" onclick="closeNav()">&times;</span>

    <div class="profile-info">
        <img src="images.jfif" alt="Profile Picture">
        <p><strong>Name:</strong> <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($student['email']); ?></p>
        <p><strong>Year:</strong> <?= htmlspecialchars($student['year_level']); ?></p>
        <p><strong>Course:</strong> <?= htmlspecialchars($student['course']); ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($student['address']); ?></p>
        <p>Duration:30 min</p>
    </div>

    <a href="javascript:void(0)" onclick="document.getElementById('editModal').style.display='block'" >Edit Profile</a>
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

                <label>Duration</label>
                <select name="duration" class="w3-select" required>
                    <option value="Partial Time" <?= $student['duration'] == 'Partial Time' ? 'selected' : ''; ?>>Partial Time</option>
                    <option value="Temporary Time" <?= $student['duration'] == 'Temporary Time' ? 'selected' : ''; ?>>Temporary Time</option>
                </select>

                <button type="submit" class="w3-button w3-green w3-margin-top">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<script>
function openNav() {
    document.getElementById("sidebar").style.width = "300px"; // Adjusted width
}

function closeNav() {
    document.getElementById("sidebar").style.width = "0";
}
</script>

</body>
</html>