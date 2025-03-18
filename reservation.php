<?php
session_start();
include 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['student_number']) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle reservation submission by students
if (isset($_SESSION['student_number']) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $student_number = $_SESSION['student_number'];
    $laboratory_number = trim($_POST['laboratory_number']);
    $purpose = trim($_POST['purpose']);

    // Ensure all fields are filled
    if (!$laboratory_number || !$purpose) {
        echo "<script>alert('Error: All fields are required!'); window.history.back();</script>";
        exit();
    }

    // Insert reservation into the database with pending status
    $query = "INSERT INTO reservations (student_id, laboratory_number, purpose, status) 
              VALUES ((SELECT student_id FROM students WHERE student_number = :student_number), :laboratory_number, :purpose, 'pending')";
    $stmt = $conn->prepare($query);

    try {
        $stmt->execute([
            ':student_number' => $student_number,
            ':laboratory_number' => $laboratory_number,
            ':purpose' => $purpose
        ]);

        echo "<script>alert('Reservation submitted successfully! Pending approval.'); window.location.href='dashboard.php';</script>";
        exit();
    } catch (PDOException $e) {
        echo "<script>alert('Error submitting reservation: " . $e->getMessage() . "'); window.history.back();</script>";
        exit();
    }
}

// Handle approval or rejection by admin
if (isset($_SESSION['admin_id']) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $reservation_id = intval($_POST['reservation_id']);
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action === 'approve') {
        $query = "UPDATE reservations SET status = 'approved' WHERE reservation_id = :reservation_id";
    } elseif ($action === 'reject') {
        $query = "UPDATE reservations SET status = 'rejected' WHERE reservation_id = :reservation_id";
    } else {
        echo "<script>alert('Invalid action.'); window.history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare($query);

    try {
        $stmt->execute([':reservation_id' => $reservation_id]);
        echo "<script>alert('Reservation has been " . ($action === 'approve' ? "approved" : "rejected") . ".'); window.location.href='admin.php';</script>";
        exit();
    } catch (PDOException $e) {
        echo "<script>alert('Error updating reservation: " . $e->getMessage() . "'); window.history.back();</script>";
        exit();
    }
}

$conn = null; // Close the database connection
?>