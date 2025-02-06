<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Fix: Ensure correct input field name
    if (!isset($_POST['student_number'])) {
        die("Error: Student number is missing from the form!");
    }

    // Sanitize and validate input
    $student_number = trim($_POST['student_number']);
    $lastname = trim($_POST['lastname']);
    $firstname = trim($_POST['firstname']);
    $midname = !empty($_POST['midname']) ? trim($_POST['midname']) : NULL;
    $course = trim($_POST['course']);
    $year_level = (int) $_POST['year_level'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Check if required fields are filled
    if (empty($student_number) || empty($lastname) || empty($firstname) || empty($course) || empty($year_level) || empty($username) || empty($password)) {
        die("Error: All fields are required!");
    }

    // Check if student number already exists
    $check_stmt = $conn->prepare("SELECT student_number FROM students WHERE student_number = ?");
    $check_stmt->bind_param("s", $student_number);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        die("Error: Student number already exists!");
    }
    $check_stmt->close();

    // Check if username already exists
    $check_stmt = $conn->prepare("SELECT username FROM students WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        die("Error: Username already exists!");
    }
    $check_stmt->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user data into the database
    $stmt = $conn->prepare("INSERT INTO students (student_number, lastname, firstname, middlename, course, year_level, username, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $student_number, $lastname, $firstname, $midname, $course, $year_level, $username, $hashed_password);

    if ($stmt->execute()) {
        echo "Registration successful! Redirecting to login...";
        header("refresh:2;url=login.php");
        exit();
    } else {
        die("Error: " . $stmt->error);
    }

    $stmt->close();
}

$conn->close();
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
            background: url('campus.jfif') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
        }
        .w3-input, .w3-select {
            width: 80%;
            margin: 10px auto;
        }
        .w3-button {
            width: 40%;
            margin: 10px auto;
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
        <h2>CCS SIT IN MAINTAINING SYSTEM</h2>
        <form action="register.php" method="post">
    <input type="text" name="student_number" placeholder="STUDENT NUMBER" required class="w3-input">
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
    <input type="text" name="username" placeholder="USERNAME" required class="w3-input">
    <input type="password" name="password" placeholder="PASSWORD" required class="w3-input">
    <button type="submit" class="w3-button w3-blue">SAVE</button>
</form>

    </div>
</body>
</html>
