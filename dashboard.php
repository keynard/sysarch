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
$query = "SELECT student_number, lastname, firstname, middlename, course, address, year_level, duration_value, duration_unit, email, profile_picture, purpose_of_sitin, laboratory_number, time_in FROM students WHERE student_number = ?";
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
        background: url('uc-campus.png') no-repeat center center fixed;
        background-size: cover;
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
        border-radius: 20px;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }
    .w3-card-4 {
        border-radius: 20px;
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
        <div class="section-header">
            <h2 class="hhh">Announcements</h2>
        </div>
        <div class="w3-container">
            <h3 class="hhh">CCS Admin</h3>
            <h5>CCS Admin <span class="w3-opacity">2025-Feb-25</span></h5>
        </div>
        <div class="w3-container">
            <p>UC did it again.</p>
        </div>
    </div>
</div>

<!-- Rules and Regulations Section -->
<div class="w3-container w3-padding-16">
    <div class="w3-card-4 w3-margin w3-white">
        <div class="section-header">
            <h2 class="hhh">Rules and Regulation</h2>
        </div>
        <div class="w3-container">
            <h3 class="hhh">University of Cebu <br> COLLEGE OF INFORMATION & COMPUTER STUDIES</h3>
        </div>
        <div class="w3-container">
            <b>LABORATORY RULES AND REGULATIONS</b>

             <p>   To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:<br>

                1. Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.<br>

                2. Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.<br>

                3. Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.<br>

                4. Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.<br>

                5. Deleting computer files and changing the set-up of the computer is a major offense.<br>

                6. Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".<br>

                7. Observe proper decorum while inside the laboratory.<br>

                <ul> Do not get inside the lab unless the instructor is present.<br>
                All bags, knapsacks, and the likes must be deposited at the counter.<br>
                Follow the seating arrangement of your instructor.<br>
                At the end of class, all software programs must be closed.<br>
                Return all chairs to their proper places after using.</ul><br>
                8. Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.<br>

                9. Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the members of the community, including public display of physical intimacy, are not tolerated.<br>

                10. Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be asked to leave the lab.<br>

                11. For serious offense, the lab personnel may call the Civil Security Office (CSU) for assistance.<br>

                12. Any technical problem or difficulty must be addressed to the laboratory supervisor, student assistant or instructor immediately.<br><br>
                <hr>


                <b>DISCIPLINARY ACTION</b> <br><br>

                First Offense - The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.<br>
                Second and Subsequent Offenses - A recommendation for a heavier sanction will be endorsed to the Guidance Center.</p>
        </div>
    </div>
</div>

<!-- Sidebar (Hamburger Menu) -->
<div id="sidebar" class="sidebar">
    <span class="close-btn" onclick="closeNav()">&times;</span>
    <div class="profile-info">
        <?php
        $profile_picture_url = htmlspecialchars($student['profile_picture']) . '?' . time();
        echo "<img src='$profile_picture_url' alt='Profile Picture'>";
        ?>
        <p><strong>Name:</strong> <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($student['email']); ?></p>
        <p><strong>Year:</strong> <?= htmlspecialchars($student['year_level']); ?></p>
        <p><strong>Course:</strong> <?= htmlspecialchars($student['course']); ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($student['address']); ?></p>
        <p><strong>Duration:</strong> <?= htmlspecialchars($student['duration_value'] . ' ' . $student['duration_unit']); ?></p>
        <p><strong>Purpose of Sit-in:</strong> <?= htmlspecialchars($student['purpose_of_sitin']); ?></p>
        <p><strong>Laboratory Number:</strong> <?= htmlspecialchars($student['laboratory_number']); ?></p>
        <p><strong>Time In:</strong> <?= htmlspecialchars($student['time_in']); ?></p>
        <p><strong>Remaining Time:</strong> <span id="remaining-time"></span></p>
    </div>
    <a href="javascript:void(0)" onclick="document.getElementById('editModal').style.display='block'">Edit Profile</a>
</div>

<!-- Edit Modal -->
<div id="editModal" class="w3-modal">
    <div class="w3-modal-content w3-padding">
        <div class="w3-container">
            <span onclick="document.getElementById('editModal').style.display='none'" class="w3-button w3-display-topright">&times;</span>
            <h3>Edit Information</h3>
            <form action="update_profile.php" method="post" enctype="multipart/form-data">
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
                <input type="number" name="duration_value" value="<?= htmlspecialchars($student['duration_value']); ?>" class="w3-input" required>
                <select name="duration_unit" class="w3-select" required>
                    <option value="minutes" <?= $student['duration_unit'] == 'minutes' ? 'selected' : ''; ?>>Minutes</option>
                    <option value="hours" <?= $student['duration_unit'] == 'hours' ? 'selected' : ''; ?>>Hours</option>
                </select>

                <label>Purpose of Sit-in</label>
                <input type="text" name="purpose_of_sitin" value="<?= htmlspecialchars($student['purpose_of_sitin']); ?>" class="w3-input" required>

                <label>Laboratory Number</label>
                <input type="text" name="laboratory_number" value="<?= htmlspecialchars($student['laboratory_number']); ?>" class="w3-input" required>

                <label>Time In</label>
                <input type="time" name="time_in" value="<?= htmlspecialchars($student['time_in']); ?>" class="w3-input" required>

                <label>Profile Picture</label>
                <input type="file" name="profile_picture" class="w3-input">
                <input type="hidden" name="existing_profile_picture" value="<?= htmlspecialchars($student['profile_picture']); ?>">

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