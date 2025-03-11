<?php
session_start();
include 'db.php';

// Ensure student is logged in
if (!isset($_SESSION['student_number'])) {
    header("Location: login.php");
    exit();
}

$student_number = $_SESSION['student_number'];

try {
    // Fetch student details
    $query = "SELECT * FROM students WHERE student_number = :student_number";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_number', $student_number, PDO::PARAM_STR);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die('Student not found.');
    }

    // Handle Profile Update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_profile'])) {
            $firstname = htmlspecialchars($_POST['firstname']);
            $lastname = htmlspecialchars($_POST['lastname']);
            $address = htmlspecialchars($_POST['address']);
            $course = htmlspecialchars($_POST['course']);
            $year_level = (int) $_POST['year_level'];
            $email = htmlspecialchars($_POST['email']);

            // Update student profile
            $updateQuery = "UPDATE students SET firstname = :firstname, lastname = :lastname, address = :address, 
                            course = :course, year_level = :year_level, email = :email WHERE student_number = :student_number";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute([
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':address' => $address,
                ':course' => $course,
                ':year_level' => $year_level,
                ':email' => $email,
                ':student_number' => $student_number
            ]);
        }

        // Handle Profile Picture Upload
        if (!empty($_FILES['profile_picture']['name'])) {
            $targetDir = "uploads/";
            $fileName = basename($_FILES['profile_picture']['name']);
            $targetFilePath = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFilePath)) {
                $updatePicQuery = "UPDATE students SET profile_picture = :profile_picture WHERE student_number = :student_number";
                $updatePicStmt = $conn->prepare($updatePicQuery);
                $updatePicStmt->execute([':profile_picture' => $targetFilePath, ':student_number' => $student_number]);
            }
        }

        // Handle Sit-In Entry
        if (isset($_POST['sit_in'])) {
            $laboratory_number = htmlspecialchars($_POST['laboratory_number']);
            $purpose = htmlspecialchars($_POST['purpose']);
            
            $sitInQuery = "INSERT INTO SitIn_Log (student_id, laboratory_number, purpose) VALUES (:student_id, :laboratory_number, :purpose)";
            $sitInStmt = $conn->prepare($sitInQuery);
            $sitInStmt->execute([
                ':student_id' => $student['student_id'],
                ':laboratory_number' => $laboratory_number,
                ':purpose' => $purpose
            ]);
        }
        
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$profile_picture_url = !empty($student['profile_picture']) ? $student['profile_picture'] : 'default-profile.png';
?>

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
    .class-container{
        display: flex;
    }

    .class-container > div {
  background-color: #f1f1f1;
  margin: 20px;
  padding: 50px;
  font-size: 30px;
}
  
</style>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile & Sit-In</title>
</head>
<body>
    <div class="class-container">
        <div class="w3-container w3-card-4 w3-light-grey"  style="padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); text-align: center;">
    <h2>Edit Profile</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>First Name:</label>
        <input type="text" name="firstname" value="<?= htmlspecialchars($student['firstname']); ?>" required><br>
        <label>Last Name:</label>
        <input type="text" name="lastname" value="<?= htmlspecialchars($student['lastname']); ?>" required><br>
        <label>Address:</label>
        <input type="text" name="address" value="<?= htmlspecialchars($student['address']); ?>" required><br>
        <label>Course:</label>
        <input type="text" name="course" value="<?= htmlspecialchars($student['course']); ?>" required><br>
        <label>Year Level:</label>
        <input type="number" name="year_level" value="<?= htmlspecialchars($student['year_level']); ?>" required><br>
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($student['email']); ?>" required><br>
        <label>Profile Picture:</label>
        <input type="file" name="profile_picture"><br>
        <img src="<?= $profile_picture_url; ?>" width="100" height="100"><br>
        <button type="submit" name="update_profile">Update Profile</button>
    </form>
            </div>
        <div class="w3-container w3-card-4 w3-light-grey"  style="padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); text-align: center;">
    <h2>Submit Sit-In</h2>
    <form method="POST">
        <label>Laboratory Number:</label>
        <input type="text" name="laboratory_number" required><br>
        <label>Purpose:</label>
        <input type="text" name="purpose" required><br>
        <label>Time In (Default):</label>
        <input type="text" value="<?= date('Y-m-d H:i:s'); ?>" disabled><br>
        <button type="submit" name="sit_in">Submit Sit-In</button>
    </form>
        </div>
</div>
</body>
</html>
