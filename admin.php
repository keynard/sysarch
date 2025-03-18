<?php
session_start();
include 'db.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}



// Fetch pending reservations with optional search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$reservationQuery = "SELECT r.reservation_id, s.student_number, s.firstname, s.lastname, r.laboratory_number, r.purpose, r.status, r.created_at 
                     FROM reservations r
                     JOIN students s ON r.student_id = s.student_id
                     WHERE r.status = 'pending'";

if (!empty($search)) {
    $reservationQuery .= " AND (s.student_number LIKE :search OR s.firstname LIKE :search OR s.lastname LIKE :search)";
}

$reservationStmt = $conn->prepare($reservationQuery);

if (!empty($search)) {
    $searchParam = "%$search%";
    $reservationStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
}

$reservationStmt->execute();
$pendingReservations = $reservationStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch sit-in logs
$sitInQuery = "SELECT l.sitin_id, s.student_number, s.firstname, s.lastname, l.laboratory_number, l.purpose, l.time_in, l.time_out
               FROM SitIn_Log l
               JOIN students s ON l.student_id = s.student_id
               ORDER BY l.time_in DESC";
$sitInStmt = $conn->prepare($sitInQuery);
$sitInStmt->execute();
$sitInLogs = $sitInStmt->fetchAll(PDO::FETCH_ASSOC);

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
            margin-bottom: 30px;
        }
        .chart-wrapper {
            width: 45%;
            max-width: 400px;
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
            <div>
    <form method="GET" action="admin.php" style="display: flex; align-items: center;">
        <input type="text" name="search" placeholder="Search by Student Number, Name, or Last Name..." class="search-box" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        <button type="submit" class="w3-button w3-blue" style="margin-left: 10px;">Search</button>
    </form>
</div>
            <a href="#">Navigate</a>
            <a href="#">Sit-in Records</a>
            <a href="#">Sit-in Reports</a>
            <a href="#">Feedback Reports</a>
            <a href="#">Reservation</a>
            <a class="logout-btn" href="dashboard_main.php">Log Out</a>
        </div>
    </div>

    <div class="main-content">
        <h2 class="page-title">Current Sit-in Records</h2>

        <div class="charts-container">
            <div class="chart-wrapper">
                <canvas id="programChart"></canvas>
            </div>
            <div class="chart-wrapper">
                <canvas id="labChart"></canvas>
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
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                            <button type="submit" name="action" value="approve" class="w3-button w3-green">Approve</button>
                            <button type="submit" name="action" value="reject" class="w3-button w3-red">Reject</button>
                        </form>
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

    <script>
        // Program distribution chart
        const programCtx = document.getElementById('programChart').getContext('2d');
        const programChart = new Chart(programCtx, {
            type: 'doughnut',
            data: {
                labels: ['CS', 'IT', 'ISTE', 'APP Dev'],
                datasets: [{
                    data: [70, 10, 15, 5],
                    backgroundColor: ['#20c997', '#dc3545', '#fd7e14', '#6f42c1'],
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
        const labCtx = document.getElementById('labChart').getContext('2d');
        const labChart = new Chart(labCtx, {
            type: 'doughnut',
            data: {
                labels: ['G1', 'G2B', 'G3', 'G1D'],
                datasets: [{
                    data: [40, 30, 20, 10],
                    backgroundColor: ['#ff9cbb', '#6cb2eb', '#6ee7b7', '#ffd54f'],
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
    </script>
</body>
</html>