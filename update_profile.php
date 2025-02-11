<?php
session_start();
include 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['student_number'])) {
    header("Location: login.php");
    exit();
}

$student_number = $_SESSION['student_number'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $course = trim($_POST['course']);
    $year_level = (int)$_POST['year_level'];
    $email = trim($_POST['email']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Error: Invalid email format!");
    }

    // Ensure all fields are filled
    if (!$firstname || !$lastname || !$course || !$year_level || !$email) {
        die("Error: All fields are required!");
    }

    // Update student profile in the database
    $query = "UPDATE students SET firstname=?, lastname=?, course=?, year_level=?, email=? WHERE student_number=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssiss", $firstname, $lastname, $course, $year_level, $email, $student_number);

    if ($stmt->execute()) {
        // Fetch the updated email from the database
        $query = "SELECT email FROM students WHERE student_number=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $student_number);
        $stmt->execute();
        $stmt->bind_result($updated_email);
        $stmt->fetch();
        $stmt->close();

        // Update session email to reflect the changes
        $_SESSION['email'] = $updated_email;

        echo "<script>alert('Profile updated successfully!'); window.location.href='dashboard.php';</script>";
        exit();
    } else {
        die("Error updating profile: " . $stmt->error);
    }
}

$conn->close();
?>
