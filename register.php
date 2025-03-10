<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $student_number = filter_input(INPUT_POST, 'student_number', FILTER_SANITIZE_STRING);
    $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
    $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
    $midname = !empty($_POST['midname']) ? filter_input(INPUT_POST, 'midname', FILTER_SANITIZE_STRING) : NULL;
    $course = filter_input(INPUT_POST, 'course', FILTER_SANITIZE_STRING);
    $year_level = filter_input(INPUT_POST, 'year_level', FILTER_VALIDATE_INT);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password']; // Will be hashed later
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);

    // Check required fields
    if (!$student_number || !$lastname || !$firstname || !$course || !$year_level || !$email || !$username || !$password || !$address) {
        die("Error: All fields are required!");
    }

    // Check password strength
    if (strlen($password) < 6) {
        die("Error: Password must be at least 6 characters long!");
    }

    // Check for duplicate student number, email, or username in a single query
    $check_stmt = $conn->prepare("SELECT student_number, email, username FROM students WHERE student_number = ? OR email = ? OR username = ?");
    $check_stmt->execute([$student_number, $email, $username]);

    if ($check_stmt->fetch()) {
        die("Error: Student Number, Email, or Username already exists!");
    }
    $check_stmt->closeCursor(); // Proper way to free the statement in PDO

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user data into the database (Added address column & default sessions)
    $stmt = $conn->prepare("INSERT INTO students (student_number, lastname, firstname, middlename, course, year_level, email, address, username, password, sessions) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 30)");
    if ($stmt->execute([$student_number, $lastname, $firstname, $midname, $course, $year_level, $email, $address, $username, $hashed_password])) {
        echo "Registration successful! Redirecting to login...";
        header("refresh:2;url=login.php");
        exit();
    } else {
        die("Error: " . $stmt->errorInfo()[2]);
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS SIT IN MAINTAINING SYSTEM - Register</title>
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
    .w3-input, .w3-select {
        width: 80%;
        margin: 10px auto;
    }
    .button-container {
        display: flex;
        justify-content: space-between;
        width: 80%;
        margin: 10px auto;
    }
    .w3-button {
        width: 48%;
    }
</style>

    <script>
        function redirectToLogin() {
            window.location.href = 'login.php';
        }
    </script>
</head>
<body>
    <div class="w3-container w3-card-4 w3-light-grey" style="padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); text-align: center;">
        <h2>CCS SIT IN MONITORING SYSTEM</h2>
        <form action="register.php" method="post">
    <input type="text" name="student_number" placeholder="STUDENT NUMBER" required class="w3-input">
    <input type="email" name="email" placeholder="EMAIL" required class="w3-input">
    
    <input type="text" name="lastname" placeholder="LASTNAME" required class="w3-input">
    <input type="text" name="firstname" placeholder="FIRSTNAME" required class="w3-input">
    <input type="text" name="midname" placeholder="MIDNAME" class="w3-input">
    
    <select name="course" required class="w3-select">
        <option value="" disabled selected>Select your course</option>
        <option value="Computer Science">Computer Science</option>
        <option value="Information Technology">Information Technology</option>
        <option value="Software Engineering">Software Engineering</option>
    </select>
    
    <select name="year_level" required class="w3-select">
        <option value="" disabled selected>Select your year level</option>
        <option value="1">1st Year</option>
        <option value="2">2nd Year</option>
        <option value="3">3rd Year</option>
        <option value="4">4th Year</option>
    </select>
    
    <input type="text" name="address" placeholder="ADDRESS" required class="w3-input"> <!-- New Address Field -->
    
    <input type="text" name="username" placeholder="USERNAME" required class="w3-input">
    <input type="password" name="password" placeholder="PASSWORD" required class="w3-input">
    
    <div class="button-container">
        <button type="submit" class="w3-button w3-blue">SAVE</button>
        <button type="button" onclick="redirectToLogin()" class="w3-button w3-green">LOGIN</button>
    </div>
</form>


    </div>
</body>
</html>
