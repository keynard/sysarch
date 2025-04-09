<?php
session_start();
include 'db.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch sit-in history
$sitInLogs = [];
try {
    $sitInQuery = "SELECT l.sitin_id, s.student_number, s.firstname, s.lastname, l.laboratory_number, l.purpose, l.time_in, l.time_out
               FROM SitIn_Log l
               JOIN students s ON l.student_id = s.student_id
               ORDER BY l.time_in DESC";
    $sitInStmt = $conn->prepare($sitInQuery);
    $sitInStmt->execute();
    $sitInLogs = $sitInStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching sit-in logs: " . $e->getMessage());
}

// Fetch laboratory usage data for the chart
$labChartData = [];
try {
    $labChartQuery = "SELECT laboratory_number, COUNT(*) as count FROM SitIn_Log GROUP BY laboratory_number";
    $labChartStmt = $conn->prepare($labChartQuery);
    $labChartStmt->execute();
    $labChartData = $labChartStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching lab chart data: " . $e->getMessage());
}

// Fetch program distribution data for the chart
$programChartData = [];
try {
    $programChartQuery = "SELECT course, COUNT(*) as count FROM students GROUP BY course";
    $programChartStmt = $conn->prepare($programChartQuery);
    $programChartStmt->execute();
    $programChartData = $programChartStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching program chart data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Records</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   <!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<!-- jQuery and DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
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
        .charts-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin: 20px 0;
        }
        .chart-box {
            width: 45%;
            max-width: 500px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .chart-box h3 {
            margin-bottom: 10px;
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
        .table-controls {
            display: flex;
            justify-content: space-between; /* Align to the left */
            align-items: center;
            margin-bottom: 10px; /* Add spacing between controls and table */
            width: 100%; /* Ensure it spans the full width */
}
        .entries-selector {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .entries-selector label {
            margin-right: 5px;
        }
        .search-box-container {
            display: flex;
            align-items: center;
        }

        .search-box {
            padding: 5px;
            border: 1px solid #ddd;
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
            <a href="#">Feedback Reports</a>
            <a href="#">Reservation</a>
            <a class="logout-btn" href="dashboard_main.php">Log Out</a>
        </div>
    </div>
    <div class="w3-container">
    <h2>Sitin Records</h2>

    <!-- Charts Container -->
    <div class="charts-container">
        <!-- Laboratory Usage Chart -->
        <div class="chart-box">
            <h3>Laboratory Usage</h3>
            <canvas id="labUsageChart"></canvas>
        </div>

        <!-- Program Distribution Chart -->
        <div class="chart-box">
            <h3>Program Distribution</h3>
            <canvas id="programChart"></canvas>
        </div>
    </div>

  
    <!-- Table Controls -->
    <div class="table-controls">
    <div class="entries-selector">
        <label for="entries">Show</label>
        <select id="entries">
            <option>10</option>
            <option>25</option>
            <option>50</option>
            <option>100</option>
        </select>
        <span>entries per page</span>
    </div>
    
</div>

<!-- Table with Export Buttons -->
<div id="sitInTableWrapper">
    <table id="sitInTable">
        <thead>
            <tr>
                <th>Sit-in ID</th>
                <th>Student Number</th>
                <th>Name</th>
                <th>Laboratory Number</th>
                <th>Purpose</th>
                <th>Time In</th>
                <th>Time Out</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($sitInLogs) > 0): ?>
                <?php foreach ($sitInLogs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['sitin_id']) ?></td>
                        <td><?= htmlspecialchars($log['student_number']) ?></td>
                        <td><?= htmlspecialchars($log['firstname'] . ' ' . $log['lastname']) ?></td>
                        <td><?= htmlspecialchars($log['laboratory_number']) ?></td>
                        <td><?= htmlspecialchars($log['purpose']) ?></td>
                        <td><?= htmlspecialchars($log['time_in']) ?></td>
                        <td><?= htmlspecialchars($log['time_out'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No sit-in records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
    <script>
        // Pass PHP data to JavaScript
        const labChartData = <?= json_encode($labChartData) ?>;
        const programChartData = <?= json_encode($programChartData) ?>;

        // Prepare data for the Laboratory Usage Chart
        const labLabels = labChartData.map(item => item.laboratory_number);
        const labCounts = labChartData.map(item => item.count);

        // Create the Laboratory Usage Chart
        const labCtx = document.getElementById('labUsageChart').getContext('2d');
        const labUsageChart = new Chart(labCtx, {
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

        // Prepare data for the Program Distribution Chart
        const programLabels = programChartData.map(item => item.course);
        const programCounts = programChartData.map(item => item.count);

        // Create the Program Distribution Chart
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
    </script>
    
    <script>
    $(document).ready(function () {
        const base64Logo = 'data:image/png;base64,REPLACE_THIS_WITH_YOUR_BASE64_STRING'; // Replace with your valid Base64 string

        $('#sitInTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csvHtml5',
                    title: 'Sit-in Records',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'excelHtml5',
                    title: 'Sit-in Records',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdfHtml5',
                    title: 'Sit-in Records',
                    customize: function (doc) {
                        // Add logo to the top of the PDF
                        doc.content.splice(0, 0, {
                            alignment: 'center',
                            image: uclogo-removebg-preview.png, // Use the static Base64 logo
                            width: 100
                        });

                        // Add university and department headers
                        doc.content.splice(1, 0, {
                            alignment: 'center',
                            text: [
                                { text: 'University Name\n', fontSize: 18, bold: true },
                                { text: 'Department of Computer Studies\n', fontSize: 16 }
                            ]
                        });
                    },
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    title: '',
                    customize: function (win) {
                        $(win.document.body)
                            .css('text-align', 'center')
                            .prepend(
                                '<img src="uclogo-removebg-preview.png" style="width:100px; height:auto; margin-bottom:20px;">' +
                                '<h2>University Name</h2>' +
                                '<h2>Department of Computer Studies</h2>'
                            );
                    },
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ]
        });
    });

    // Helper function to convert an image to Base64
    function getBase64Image(img) {
        const canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        return canvas.toDataURL('image/png').replace(/^data:image\/(png|jpg);base64,/, '');
    }
</script>
</body>
</html>