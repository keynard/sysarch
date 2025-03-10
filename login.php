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
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: url('uc-campus.png') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
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
    </style>
    <script>
        function redirectToRegister() {
            window.location.href = 'register.php';
        }
    </script>
</head>
<body>
    <div class="w3-container w3-card-4 w3-light-grey container">
        <h2>CCS SIT-IN MONITORING SYSTEM</h2>
        <img src="ccslogo-removebg-preview.png" alt="Logo" class="w3-image" style="width: 50px; height: 50px;">
        <img src="uclogo-removebg-preview.png" alt="Logo" class="w3-image" style="width: 50px; height: 50px;">
        
        <form action="login.php" method="post">
            <input type="text" name="username" placeholder="USERNAME" required class="w3-input">
            <input type="password" name="password" placeholder="PASSWORD" required class="w3-input">
            <button type="submit" class="w3-button w3-blue">LOGIN</button>
            <button type="button" class="w3-button w3-green" onclick="redirectToRegister()">REGISTER</button>
        </form>
    </div>
</body>
</html>
