<?php
session_start();
include 'db.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_sitin'])) {
    $reservationId = $_POST['reservation_id'];
    $labNumber = trim($_POST['lab_number']);
    $purpose = trim($_POST['purpose']);

    // Fetch the student ID from the reservation
    $fetchStudentQuery = "SELECT student_id FROM reservations WHERE reservation_id = :reservation_id";
    $fetchStudentStmt = $conn->prepare($fetchStudentQuery);
    $fetchStudentStmt->bindParam(':reservation_id', $reservationId, PDO::PARAM_INT);
    $fetchStudentStmt->execute();
    $student = $fetchStudentStmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $studentId = $student['student_id'];

        // Insert the sit-in record
        $insertSitInQuery = "INSERT INTO SitIn_Log (student_id, laboratory_number, purpose, time_in) 
                             VALUES (:student_id, :lab_number, :purpose, NOW())";
        $insertSitInStmt = $conn->prepare($insertSitInQuery);
        $insertSitInStmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $insertSitInStmt->bindParam(':lab_number', $labNumber, PDO::PARAM_STR);
        $insertSitInStmt->bindParam(':purpose', $purpose, PDO::PARAM_STR);
        $insertSitInStmt->execute();

        // Update the reservation status to "completed"
        $updateReservationQuery = "UPDATE reservations SET status = 'completed' WHERE reservation_id = :reservation_id";
        $updateReservationStmt = $conn->prepare($updateReservationQuery);
        $updateReservationStmt->bindParam(':reservation_id', $reservationId, PDO::PARAM_INT);
        $updateReservationStmt->execute();

        echo "<script>alert('Sit-in record added successfully.'); window.location.href='admin.php';</script>";
        exit();
    } else {
        echo "<script>alert('Invalid reservation ID.'); window.location.href='admin.php';</script>";
        exit();
    }
}
// Fetch pending reservations
$pendingReservationsQuery = "SELECT r.reservation_id, s.student_number, s.firstname, s.lastname, 
                             r.laboratory_number, r.purpose, r.status, r.created_at 
                             FROM reservations r
                             JOIN students s ON r.student_id = s.student_id
                             WHERE r.status = 'pending'";
$pendingReservationsStmt = $conn->prepare($pendingReservationsQuery);
$pendingReservationsStmt->execute();
$pendingReservations = $pendingReservationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending reservations with optional search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$studentQuery = "SELECT student_id, student_number, firstname, lastname, course, year_level 
                 FROM students";

if (!empty($search)) {
    $studentQuery .= " WHERE student_number LIKE :search OR firstname LIKE :search OR lastname LIKE :search";
}

$studentStmt = $conn->prepare($studentQuery);

if (!empty($search)) {
    $searchParam = "%$search%";
    $studentStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}

$studentStmt->execute();
$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch sit-in logs
$sitInQuery = "SELECT l.sitin_id, s.student_number, s.firstname, s.lastname, l.laboratory_number, l.purpose, l.time_in, l.time_out
               FROM SitIn_Log l
               JOIN students s ON l.student_id = s.student_id
               ORDER BY l.time_in DESC";
$sitInStmt = $conn->prepare($sitInQuery);
$sitInStmt->execute();
$sitInLogs = $sitInStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_announcement'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $adminId = $_SESSION['admin_id']; // Assuming admin_id is stored in the session

    $addQuery = "INSERT INTO announcement (admin_id, title, content) VALUES (:admin_id, :title, :content)";
    $addStmt = $conn->prepare($addQuery);
    $addStmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
    $addStmt->bindParam(':title', $title, PDO::PARAM_STR);
    $addStmt->bindParam(':content', $content, PDO::PARAM_STR);
    $addStmt->execute();

    echo "<script>alert('Announcement added successfully.'); window.location.href='admin.php';</script>";
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

    echo "<script>alert('Reservation has been " . ($action === 'approve' ? 'approved' : 'rejected') . ".'); window.location.href='admin.php';</script>";
    exit();
}
// Fetch program distribution data
$programQuery = "SELECT course, COUNT(*) as count FROM students GROUP BY course";
$programStmt = $conn->prepare($programQuery);
$programStmt->execute();
$programData = $programStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch laboratory usage data
$labQuery = "SELECT laboratory_number, COUNT(*) as count FROM SitIn_Log GROUP BY laboratory_number";
$labStmt = $conn->prepare($labQuery);
$labStmt->execute();
$labData = $labStmt->fetchAll(PDO::FETCH_ASSOC);


// Fetch announcements
$announcementQuery = "SELECT announcement_id, title, content, created_at FROM announcement ORDER BY created_at DESC";
$announcementStmt = $conn->prepare($announcementQuery);
$announcementStmt->execute();
$announcements = $announcementStmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College of Computer Studies Admin</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .header {
            background-color: #004d99;
            color: white;
            padding: 15px 20px;
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
        .main-content {
            padding: 20px;
        }
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: bold;
        }
        .charts-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .chart-box {
            width: 45%;
            max-width: 400px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .announcement-box {
            width: 45%;
            max-width: 400px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .announcement-box h3 {
            margin-bottom: 15px;
            font-size: 18px;
            color: #333;
        }

        .announcement-content {
            max-height: 300px;
            overflow-y: auto;
        }

        .announcement-item {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .announcement-item h4 {
            margin: 0;
            font-size: 16px;
            color: #004d99;
        }

        .announcement-item p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }

        .announcement-item small {
            font-size: 12px;
            color: #888;
        }
        .announcement-input-box {
            width: 45%;
            max-width: 400px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .announcement-input-box h3 {
            margin-bottom: 15px;
            font-size: 18px;
            color: #333;
        }
        .chart-wrapper {
            width: 45%;
            max-width: 400px;
        }
        .chart-box h3 {
            margin-bottom: 15px;
            font-size: 18px;
            color: #333;
        }
        .table-controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .entries-selector {
            display: flex;
            align-items: center;
        }
        .entries-selector label {
            margin-right: 5px;
        }
        .search-box {
            padding: 5px;
            border: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>College of Computer Studies Admin</h1>
        <div class="nav-links">
            <a href="#">Home</a>
            
           
            
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

    <div class="main-content">
        <h2 class="page-title">ADMIN DASHBOARD</h2>

        <div class="charts-container">
    <!-- Program Distribution Chart -->
    <div class="chart-box">
        <h3>Program Distribution</h3>
        <canvas id="programChart"></canvas>
    </div>

    <!-- Laboratory Usage Chart -->
    <div class="chart-box">
        <h3>Laboratory Usage</h3>
        <canvas id="labChart"></canvas>
    </div>

    <div class="announcement-input-box">
    <h3>Add Announcement</h3>
    <form method="POST" action="admin.php" class="w3-container w3-card-4" style="padding: 20px;">
        <label for="title"><b>Title</b></label>
        <input type="text" id="title" name="title" class="w3-input w3-border" required>

        <label for="content" style="margin-top: 10px;"><b>Content</b></label>
        <textarea id="content" name="content" class="w3-input w3-border" rows="5" required></textarea>

        <button type="submit" name="add_announcement" class="w3-button w3-blue" style="margin-top: 10px;">Add Announcement</button>
    </form>
</div>
    <div class="announcement-box">
        <h3>Announcements</h3>
        <div class="announcement-content">
            <?php if (count($announcements) > 0): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-item">
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
</div>

        <div class="table-controls">
            <div class="entries-selector">
                <select>
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                </select>
                <span> entries per page</span>
            </div>
            <div>
                <input type="text" placeholder="Search..." class="search-box">
            </div>
        </div>

        <table>
    <thead>
        <tr>
            <th>Reservation ID</th>
            <th>Student Number</th>
            <th>Name</th>
            <th>Laboratory Number</th>
            <th>Purpose</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($pendingReservations) > 0): ?>
            <?php foreach ($pendingReservations as $reservation): ?>
                <tr>
                    <td><?= htmlspecialchars($reservation['reservation_id']) ?></td>
                    <td><?= htmlspecialchars($reservation['student_number']) ?></td>
                    <td><?= htmlspecialchars($reservation['firstname'] . ' ' . $reservation['lastname']) ?></td>
                    <td><?= htmlspecialchars($reservation['laboratory_number']) ?></td>
                    <td><?= htmlspecialchars($reservation['purpose']) ?></td>
                    <td><?= htmlspecialchars($reservation['status']) ?></td>
                    <td><?= htmlspecialchars($reservation['created_at']) ?></td>
                    <td>
                        <button type="button" class="w3-button w3-blue" 
                                onclick="openSitInModal(<?= $reservation['reservation_id'] ?>)">Sit-in</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center;">No pending reservations found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
    </div>

    <?php if (!empty($search)): ?>
    <h3>Search Results</h3>
    <table>
        <thead>
            <tr>
                <th>Student Number</th>
                <th>Name</th>
                <th>Course</th>
                <th>Year Level</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($students) > 0): ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['student_number']) ?></td>
                        <td><?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) ?></td>
                        <td><?= htmlspecialchars($student['course']) ?></td>
                        <td><?= htmlspecialchars($student['year_level']) ?></td>
                        <td>
                        <button type="button" class="w3-button w3-blue" 
                        onclick="openSitInModal(<?= $student['student_id'] ?>)">Set Sit-in</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No students found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>

    
    <!-- Search Modal -->
<div id="searchModal" class="w3-modal" style="display:none;">
    <div class="w3-modal-content w3-animate-top w3-card-4" style="max-width: 500px;">
        <header class="w3-container w3-blue">
            <span onclick="document.getElementById('searchModal').style.display='none'" 
                  class="w3-button w3-display-topright">&times;</span>
            <h2>Search Reservations</h2>
        </header>
        <form method="GET" action="admin.php" class="w3-container">
            <div class="w3-section">
                <label for="search"><b>Search by Student Number, Name, or Last Name</b></label>
                <input type="text" id="search" name="search" class="w3-input w3-border" 
                       placeholder="Enter search term..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" required>
            </div>
            <footer class="w3-container w3-light-grey">
                <button type="button" class="w3-button w3-red" onclick="document.getElementById('searchModal').style.display='none'">Cancel</button>
                <button type="submit" class="w3-button w3-blue">Search</button>
            </footer>
        </form>
    </div>
</div>
<!-- Sit-in Modal -->
<div id="sitinModal" class="w3-modal" style="display:none;">
    <div class="w3-modal-content w3-animate-top w3-card-4" style="max-width: 500px;">
        <header class="w3-container w3-blue">
            <span onclick="document.getElementById('sitinModal').style.display='none'" 
                  class="w3-button w3-display-topright">&times;</span>
            <h2>Set Sit-in Details</h2>
        </header>
        <form method="POST" action="reservation_handler.php" class="w3-container">
            <div class="w3-section">
                <input type="hidden" id="student_id" name="student_id"> <!-- Hidden input for student ID -->
                <label for="lab-number"><b>Laboratory Number</b></label>
                <input type="text" id="lab-number" name="lab_number" class="w3-input w3-border" required>

                <label for="purpose" style="margin-top: 10px;"><b>Purpose</b></label>
                <textarea id="purpose" name="purpose" class="w3-input w3-border" rows="5" required></textarea>
            </div>
            <footer class="w3-container w3-light-grey">
                <button type="button" class="w3-button w3-red" onclick="document.getElementById('sitinModal').style.display='none'">Cancel</button>
                <button type="submit" name="set_sitin" class="w3-button w3-blue">Submit</button>
            </footer>
        </form>
    </div>
</div>
<script>
    // Pass PHP data to JavaScript
    const programData = <?= json_encode($programData) ?>;
    const labData = <?= json_encode($labData) ?>;

    // Program distribution chart
    const programLabels = programData.map(item => item.course);
    const programCounts = programData.map(item => item.count);

    const programCtx = document.getElementById('programChart').getContext('2d');
    const programChart = new Chart(programCtx, {
        type: 'doughnut',
        data: {
            labels: programLabels,
            datasets: [{
                data: programCounts,
                backgroundColor: ['#20c997', '#dc3545', '#fd7e14', '#6f42c1', '#ffc107', '#17a2b8', '#28a745'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    display: true
                }
            },
            cutout: '60%'
        }
    });

    // Laboratory usage chart
    const labLabels = labData.map(item => item.laboratory_number);
    const labCounts = labData.map(item => item.count);

    const labCtx = document.getElementById('labChart').getContext('2d');
    const labChart = new Chart(labCtx, {
        type: 'doughnut',
        data: {
            labels: labLabels,
            datasets: [{
                data: labCounts,
                backgroundColor: ['#ff9cbb', '#6cb2eb', '#6ee7b7', '#ffd54f', '#ff6f61', '#9c27b0', '#3f51b5'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    display: true
                }
            },
            cutout: '60%'
        }
    });
    function openSitInModal(studentId) {
    document.getElementById('student_id').value = studentId; // Set the student ID in the hidden input
    document.getElementById('sitinModal').style.display = 'block'; // Show the modal
}

   
</script>
</body>
</html>