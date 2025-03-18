<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College of Computer Studies Admin</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .header {
            background-color: #0055a4;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h2 {
            margin: 0;
        }
        
        .nav-links {
            display: flex;
            gap: 10px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-right: 10px;
        }
        
        .logout-btn {
            background-color: #ffc107;
            color: #000;
            border: none;
            padding: 5px 15px;
            border-radius: 3px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .main-title {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .charts-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }
        
        .chart-wrapper {
            width: 45%;
            max-width: 400px;
            position: relative;
        }
        
        .legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
            margin-bottom: 5px;
        }
        
        .legend-color {
            width: 15px;
            height: 15px;
            margin-right: 5px;
        }
        
        .records-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .records-table th, .records-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .records-table th {
            background-color: #f2f2f2;
            cursor: pointer;
        }
        
        .records-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .records-per-page {
            margin-top: 10px;
        }
        
        .search-bar {
            text-align: right;
            margin-bottom: 10px;
        }
        
        .search-bar input {
            padding: 5px;
            width: 200px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>College of Computer Studies Admin</h2>
        <div class="nav-links">
            <a href="#">Home</a>
            <a href="#">Search</a>
            <a href="#">Navigate</a>
            <a href="#">Sit-in Records</a>
            <a href="#">Sit-in Reports</a>
            <a href="#">Feedback Reports</a>
            <a href="#">Reservation</a>
        </div>
        <button class="logout-btn">Log Out</button>
    </div>
    
    <div class="main-content">
        <h1 class="main-title">Current Sit-in Records</h1>
        
        <div class="charts-container">
            <div class="chart-wrapper">
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #20c997;"></div>
                        <span>CS</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #dc3545;"></div>
                        <span>IT</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #fd7e14;"></div>
                        <span>ISTE</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #6f42c1;"></div>
                        <span>APP Dev</span>
                    </div>
                </div>
                <canvas id="leftChart"></canvas>
            </div>
            <div class="chart-wrapper">
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #ff9cbb;"></div>
                        <span>G1</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #6cb2eb;"></div>
                        <span>G2B</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #6ee7b7;"></div>
                        <span>G3</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #ffd54f;"></div>
                        <span>G1D</span>
                    </div>
                </div>
                <canvas id="rightChart"></canvas>
            </div>
        </div>
        
        <div class="records-per-page">
            <select id="recordsPerPage">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>entries per page</span>
        </div>
        
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search...">
        </div>
        
        <table class="records-table">
            <thead>
                <tr>
                    <th>Sit-in Number</th>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Login</th>
                    <th>Logout</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="recordsTableBody">
                <!-- Table rows will be added here dynamically -->
            </tbody>
        </table>
    </div>

    <script>
        // Sample data for charts
        const programData = {
            labels: ['CS', 'IT', 'ISTE', 'APP Dev'],
            datasets: [{
                data: [70, 10, 15, 5],
                backgroundColor: ['#20c997', '#dc3545', '#fd7e14', '#6f42c1'],
                borderWidth: 0
            }]
        };
        
        const labData = {
            labels: ['G1', 'G2B', 'G3', 'G1D'],
            datasets: [{
                data: [40, 30, 20, 10],
                backgroundColor: ['#ff9cbb', '#6cb2eb', '#6ee7b7', '#ffd54f'],
                borderWidth: 0
            }]
        };
        
        // Sample data for table
        const sampleRecords = [
            { sitinNumber: '82', idNumber: '90/1234', name: 'Jeff Salingalgan', purpose: 'PHP Programming', lab: 'J24', login: '11:10:45am', logout: '11:32:22am', date: '2025-02-13' },
            { sitinNumber: '83', idNumber: '92/4567', name: 'Maria Santos', purpose: 'Java Programming', lab: 'G1', login: '09:15:30am', logout: '10:45:10am', date: '2025-02-13' },
            { sitinNumber: '84', idNumber: '93/7891', name: 'John Doe', purpose: 'Database Design', lab: 'G2B', login: '01:20:15pm', logout: '03:05:45pm', date: '2025-02-13' },
            { sitinNumber: '85', idNumber: '94/2345', name: 'Lisa Johnson', purpose: 'Web Development', lab: 'G3', login: '10:30:00am', logout: '12:15:30pm', date: '2025-02-13' },
            { sitinNumber: '86', idNumber: '95/6789', name: 'David Chen', purpose: 'Mobile App Dev', lab: 'J24', login: '02:45:10pm', logout: '04:30:22pm', date: '2025-02-13' }
        ];
        
        // Initialize charts
        window.onload = function() {
            // Left chart
            const leftCtx = document.getElementById('leftChart').getContext('2d');
            const leftChart = new Chart(leftCtx, {
                type: 'doughnut',
                data: programData,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '70%'
                }
            });
            
            // Right chart
            const rightCtx = document.getElementById('rightChart').getContext('2d');
            const rightChart = new Chart(rightCtx, {
                type: 'doughnut',
                data: labData,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '70%'
                }
            });
            
            // Populate table
            populateTable(sampleRecords);
        };
        
        // Function to populate table with data
        function populateTable(records) {
            const tableBody = document.getElementById('recordsTableBody');
            tableBody.innerHTML = '';
            
            records.forEach(record => {
                const row = document.createElement('tr');
                
                row.innerHTML = `
                    <td>${record.sitinNumber}</td>
                    <td>${record.idNumber}</td>
                    <td>${record.name}</td>
                    <td>${record.purpose}</td>
                    <td>${record.lab}</td>
                    <td>${record.login}</td>
                    <td>${record.logout}</td>
                    <td>${record.date}</td>
                `;
                
                tableBody.appendChild(row);
            });
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            
            const filteredRecords = sampleRecords.filter(record => {
                return Object.values(record).some(value => 
                    value.toString().toLowerCase().includes(searchValue)
                );
            });
            
            populateTable(filteredRecords);
        });
    </script>
</body>
</html>