<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College of Computer Studies Sit-in Monitoring System</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
         body {
        margin: 0;
        padding-top: 80px; /* Adjusted padding so content doesn't overlap navbar */
        background: url('uc-campus.png') no-repeat center center fixed;
        background-size: cover;
    }
        .navbar {
        position: fixed; /* Keeps the navbar at the top */
        top: 0;
        left: 0;
        width: 100%;
        background-color: #0d4a8f;
        color: white;
        padding: 1.4rem; /* Increased padding for a bigger navbar */
        z-index: 1000; /* Ensures navbar is above other elements */
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .navbar-title {
        font-size: 1.3rem; /* Increased font size */
        font-weight: bold;
        margin-left: 15px;
    }
    .navbar-links a {
        color: white;
        text-decoration: none;
        font-size: 1rem; /* Make links bigger */
        padding: 0.8rem 1.2rem;
    }
        .navbar-links {
        display: flex;
        gap: 15px; /* More space between links */
        margin-right: 20px;
    }

        .announcement-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .announcement-header {
            background-color: #0d4a8f;
            color: white;
            padding: 1rem;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .announcement-content {
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
        }
        .announcement-item {
            margin-bottom: 1.5rem;
        }
        .announcement-info {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .announcement-message {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<nav class="navbar">
        <div class="navbar-title">College of Computer Studies Sit-in Monitoring System</div>
        <div class="navbar-links">
            <a href="dashboard_main.php" class="w3-bar-item w3-button">Home</a>
            <a href="#" class="w3-bar-item w3-button">Community ▼</a>
            <a href="#" class="w3-bar-item w3-button">About</a>
            <a href="login.php" class="w3-bar-item w3-button">Login</a>
            <a href="#" class="w3-bar-item w3-button">Register</a>
        </div>
    </nav>

    
    <div class="w3-container w3-margin-top">
        <div class="w3-card announcement-box">
            <div class="w3-container announcement-header">Announcement</div>
            <div class="w3-container announcement-content">
                <div class="announcement-item">
                    <div class="announcement-info">CCS Admin | 2025-Mar-05</div>
                    <div class="announcement-message">
                        dasdasd
                    </div>
                </div>
                
                <div class="announcement-item">
                    <div class="announcement-info">CCS Admin | 2025-Mar-05</div>
                    <div class="announcement-message">
                        dasdasd
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>