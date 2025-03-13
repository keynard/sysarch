<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College of Computer Studies Sit-in Monitoring System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        .navbar {
            background-color: #0d4a8f;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-title {
            font-size: 1.3rem;
            font-weight: bold;
        }
        
        .navbar-links {
            display: flex;
            gap: 1rem;
        }
        
        .navbar-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem;
        }
        
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
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
        
        .scrollbar {
            width: 20px;
            background-color: #f0f0f0;
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-title">College of Computer Studies Sit-in Monitoring System</div>
        <div class="navbar-links">
            <a href="#">Home</a>
            <a href="#">Community ▼</a>
            <a href="#">About</a>
            <a href="#">Login</a>
            <a href="#">Register</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="announcement-box">
            <div class="announcement-header">Announcement</div>
            <div class="announcement-content">
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