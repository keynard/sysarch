<?php
session_start();
include 'db.php';

// Ensure student is logged in
if (!isset($_SESSION['student_number'])) {
    header("Location: login.php");
    exit();
}

$student_number = $_SESSION['student_number'];
$error = "";
$success = "";

// Fetch student details
$query = "SELECT * FROM students WHERE student_number = ?";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $student_number, PDO::PARAM_STR);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$profile_picture_url = !empty($student['profile_picture']) ? $student['profile_picture'] . '?' . time() : 'default-profile.png';

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $middlename = trim($_POST['middlename']);
    $address = trim($_POST['address']);
    $course = trim($_POST['course']);
    $email = trim($_POST['email']);
    $year_level = (int)$_POST['year_level'];

    // Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES["profile_picture"]["name"]);
        $targetFilePath = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowedTypes = ["jpg", "jpeg", "png"];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
                $profile_picture_url = $targetFilePath;
            } else {
                $error = "Error uploading the file.";
            }
        } else {
            $error = "Invalid file type. Only JPG, JPEG, and PNG allowed.";
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        $update_query = "UPDATE students SET firstname=?, lastname=?, middlename=?, address=?, course=?, email=?, year_level=?, profile_picture=? WHERE student_number=?";
        $stmt = $conn->prepare($update_query);
        $stmt->bindParam(1, $firstname, PDO::PARAM_STR);
        $stmt->bindParam(2, $lastname, PDO::PARAM_STR);
        $stmt->bindParam(3, $middlename, PDO::PARAM_STR);
        $stmt->bindParam(4, $address, PDO::PARAM_STR);
        $stmt->bindParam(5, $course, PDO::PARAM_STR);
        $stmt->bindParam(6, $email, PDO::PARAM_STR);
        $stmt->bindParam(7, $year_level, PDO::PARAM_INT);
        $stmt->bindParam(8, $profile_picture_url, PDO::PARAM_STR);
        $stmt->bindParam(9, $student_number, PDO::PARAM_STR);

        if ($stmt->execute()) {
            // Redirect to dashboard after successful update
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Error updating profile: " . $stmt->errorInfo()[2];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: url('uc-campus.png') no-repeat center center fixed;
            background-size: cover;
        }
        .container {
            margin: 50px auto;
            max-width: 600px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .w3-input {
            margin-bottom: 15px;
        }
        .w3-button {
            width: 100%;
        }
        .profile-picture {
            display: block;
            margin: 0 auto 20px auto;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="w3-container container">
        <h2>Edit Profile</h2>
        <?php if ($error): ?>
            <div class="w3-panel w3-red">
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>
        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            <img src="<?= htmlspecialchars($profile_picture_url) ?>" alt="Profile Picture" class="profile-picture">
            <label>Upload New Profile Picture:</label>
            <input type="file" name="profile_picture" class="w3-input w3-border">
            <label>First Name:</label>
            <input type="text" name="firstname" value="<?= htmlspecialchars($student['firstname']) ?>" required class="w3-input w3-border">
            <label>Last Name:</label>
            <input type="text" name="lastname" value="<?= htmlspecialchars($student['lastname']) ?>" required class="w3-input w3-border">
            <label>Middle Name:</label>
            <input type="text" name="middlename" value="<?= htmlspecialchars($student['middlename']) ?>" class="w3-input w3-border">
            <label>Address:</label>
            <input type="text" name="address" value="<?= htmlspecialchars($student['address']) ?>" required class="w3-input w3-border">
            <label>Course:</label>
            <input type="text" name="course" value="<?= htmlspecialchars($student['course']) ?>" required class="w3-input w3-border">
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required class="w3-input w3-border">
            <label>Year Level:</label>
            <input type="number" name="year_level" value="<?= htmlspecialchars($student['year_level']) ?>" required class="w3-input w3-border">
            <button type="submit" class="w3-button w3-blue">Update Profile</button>
        </form>
    </div>
</body>
</html>