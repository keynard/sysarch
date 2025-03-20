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
    .boxes-container {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 15px;
}

/* Modify these existing styles */
.announcement-box,
.rules-box {
    width: 100%; /* This makes each box take equal space */
    max-width: none; /* Remove the max-width constraint */
    margin: 0; /* Remove margin as it's handled by the container */
}
.announcement-header,
.rules-header {
    background-color: #1a4c8b;
    color: white;
    padding: 12px 15px;
    font-size: 18px;
    font-weight: bold;
}

.announcement-header h3,
.rules-header h3 {
    margin: 0;
}

.announcement-content,
.rules-content {
    background-color: white;
    padding: 15px;
    max-height: 400px;
    overflow-y: auto;
}

.announcement-item {
    margin-bottom: 15px;
    padding-bottom: 12px;
    border-bottom: 1px solid #ddd;
}
.announcement-item {
    margin-bottom: 15px;
    padding-bottom: 12px;
    border-bottom: 1px solid #ddd;
}

.university-heading {
    text-align: center;
    margin-bottom: 15px;
}

.university-heading h4 {
    font-size: 18px;
    margin: 5px 0;
    color: #000;
}

.university-heading h5 {
    font-size: 16px;
    margin: 5px 0;
    color: #000;
}

.university-heading h6 {
    font-size: 15px;
    margin: 5px 0;
    font-weight: bold;
    color: #000;
}

.rules-intro {
    margin-bottom: 15px;
}

.rules-list {
    padding-left: 25px;
}

.rules-list li {
    margin-bottom: 12px;
    line-height: 1.4;
}
@media (max-width: 768px) {
    .boxes-container {
        flex-direction: column;
    }
    
    .announcement-box,
    .rules-box {
        margin-bottom: 20px;
    }
}
.announcement-info {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>

<div class="navbar">
    <span class="menu-icon" onclick="openNav()">&#9776;</span>
    <span>DASHBOARD</span>
    <div class="nav-links">
        <a>Notification</a>
        <a href="update_profile.php" class="edit-prof">Edit Profile</a>
        <a  onclick="document.getElementById('reservationModal').style.display='block'">Reservation</a>
        <a href="login.php" class="logout">Logout</a>
    </div>
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
<!-- Reservation Modal -->
<div class="w3-modal" id="reservationModal" style="display:none;">
    <div class="w3-modal-content w3-animate-top w3-card-4" style="max-width: 500px;">
        <header class="w3-container w3-blue">
            <span onclick="document.getElementById('reservationModal').style.display='none'" 
                  class="w3-button w3-display-topright">&times;</span>
            <h2>Make a Reservation</h2>
        </header>
        <form action="reservation.php" method="POST" class="w3-container">
            <div class="w3-section">
                <label for="laboratory_number"><b>Laboratory Number</b></label>
                <input type="text" id="laboratory_number" name="laboratory_number" class="w3-input w3-border" required>

                <label for="purpose"><b>Purpose</b></label>
                <textarea id="purpose" name="purpose" class="w3-input w3-border" rows="4" required></textarea>
            </div>
            <footer class="w3-container w3-light-grey">
                <button type="button" class="w3-button w3-red" onclick="document.getElementById('reservationModal').style.display='none'">Cancel</button>
                <button type="submit" class="w3-button w3-blue">Submit</button>
            </footer>
        </form>
    </div>
</div>

<div class="boxes-container">
    <div class="announcement-box">
        <div class="announcement-header">
            <h3>Announcements</h3>
        </div>
        <div class="announcement-content">
            <?php if (count($announcements) > 0): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-item">
                    <div class="announcement-info">
                            CCS Admin | <?php echo htmlspecialchars(date("Y-M-d", strtotime($announcement['created_at']))); ?>
                        </div>
                        <h4><?= htmlspecialchars($announcement['title']) ?></h4>
                        <p><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                        <small>Posted on <?= htmlspecialchars($announcement['created_at']) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No announcements available.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="rules-box">
    <div class="rules-header">
        <h3>Rules and Regulations</h3>
    </div>
    <div class="rules-content">
        <div class="university-heading">
            <h4>University of Cebu</h4>
            <h5>COLLEGE OF INFORMATION & COMPUTER STUDIES</h5>
            <h6>LABORATORY RULES AND REGULATIONS</h6>
        </div>
        <div class="rules-intro">
            <p>To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
        </div>
        <ol class="rules-list">
            <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans, and other personal equipment must be switched off.</li>
            <li>Games are not allowed inside the lab. This includes computer-related games, card games, and other games that may disturb the operation of the lab.</li>
            <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing software are strictly prohibited.</li>
            <li>Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.</li>
            <li>Deleting computer files and changing the set-up of the computer is a major offense.</li>
            <li>Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".</li>
            <li>Observe proper decorum while inside the laboratory.
                <ul>
                    <li>Do not enter the lab unless the instructor is present.</li>
                    <li>All bags, knapsacks, and the like must be deposited at the counter.</li>
                    <li>Follow the seating arrangement of your instructor.</li>
                    <li>At the end of class, all software programs must be closed.</li>
                    <li>Return all chairs to their proper places after use.</li>
                </ul>
            </li>
            <li>Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.</li>
            <li>Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the community, including public displays of physical intimacy, are not tolerated.</li>
            <li>Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be asked to leave the lab.</li>
            <li>For serious offenses, lab personnel may call the Civil Security Office (CSU) for assistance.</li>
            <li>Any technical problem or difficulty must be reported to the laboratory supervisor, student assistant, or instructor immediately.</li>
        </ol>
        <div class="disciplinary-actions">
            <h6>DISCIPLINARY ACTION</h6>
            <p><strong>First Offense:</strong> The Head, Dean, or OIC recommends to the Guidance Center a suspension from classes for each offender.</p>
            <p><strong>Second and Subsequent Offenses:</strong> A recommendation for a heavier sanction will be endorsed to the Guidance Center.</p>
        </div>
    </div>
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