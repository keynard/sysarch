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
    $duration_value = (int)$_POST['duration_value'];
    $duration_unit = trim($_POST['duration_unit']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Error: Invalid email format!");
    }

    // Ensure all fields are filled
    if (!$firstname || !$lastname || !$course || !$year_level || !$email || !$duration_value || !$duration_unit) {
        die("Error: All fields are required!");
    }

    // Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file);
        $profile_picture = $target_file;
    } else {
        $profile_picture = isset($_POST['existing_profile_picture']) ? $_POST['existing_profile_picture'] : '';
    }

    // Update student profile in the database
    $query = "UPDATE students SET firstname=?, lastname=?, course=?, year_level=?, email=?, duration_value=?, duration_unit=?, profile_picture=? WHERE student_number=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssisssss", $firstname, $lastname, $course, $year_level, $email, $duration_value, $duration_unit, $profile_picture, $student_number);

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