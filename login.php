<?php
session_start();
include 'db.php'; // Include PDO connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        // Prepare query using PDO
        $stmt = $conn->prepare("SELECT student_number, password FROM students WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the password
        if ($user && password_verify($password, $user['password'])) {
            // Store session variables
            $_SESSION['student_number'] = $user['student_number'];
            $_SESSION['username'] = $username;
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid username or password.');</script>";
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage()); // Better error handling
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Sit-in Monitoring System</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
          body {
        margin: 0;
        padding-top: 60px; /* Push content below navbar */
        background: url('uc-campus.png') no-repeat center center fixed;
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
        .container {
            background-color: #e0e0e0;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .w3-input {
            width: 80%;
            margin: 10px auto;
        }
        .w3-button {
            width: 40%;
            margin: 10px auto;
        }
        .navbar-links {
        display: flex;
        gap: 20px; /* Space between links */
        margin-right: 20px; /* Space from the right edge */
    }
        .navbar {
        position: fixed;  /* Fixes navbar at the top */
        top: 0;
        left: 0;
        width: 100%;
        background-color: #0d4a8f;
        color: white;
        padding: 1rem;
        z-index: 1000; /* Keeps navbar above everything else */
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .navbar-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-right: 550px;
        }
      .navbar-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem;
        }

    </style>
    
</head>
<body>
<nav class="w3-bar navbar">
        <div class="navbar-title">College of Computer Studies Sit-in Monitoring System</div>
        <div class="navbar-links">
            <a href="dashboard_main.php" class="w3-bar-item w3-button">Home</a>
            <a href="#" class="w3-bar-item w3-button">Community ▼</a>
            <a href="#" class="w3-bar-item w3-button">About</a>
            <a href="#" class="w3-bar-item w3-button">Login</a>
            <a href="register.php" class="w3-bar-item w3-button">Register</a>
        </div>
    </nav>

    <div class="w3-container w3-card-4 w3-light-grey container">
        <h2>CCS SIT-IN MONITORING SYSTEM</h2>
        <img src="ccslogo-removebg-preview.png" alt="Logo" class="w3-image" style="width: 50px; height: 50px;">
        <img src="uclogo-removebg-preview.png" alt="Logo" class="w3-image" style="width: 50px; height: 50px;">
        
        <form action="login.php" method="post">
            <input type="text" name="username" placeholder="USERNAME" required class="w3-input">
            <input type="password" name="password" placeholder="PASSWORD" required class="w3-input">
            <button type="submit" class="w3-button w3-blue">LOGIN</button>
            
        </form>
    </div>
</body>
</html>