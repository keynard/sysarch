<?php
session_start();
include 'db.php'; // Include your database connection file



// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure the student is logged in
    if (!isset($_SESSION['student_id'])) {
        echo "<script>alert('You must be logged in to submit feedback.'); window.location.href='dashboard.php';</script>";
        exit();
    }

    // Get the student ID and feedback text
    $studentId = $_SESSION['student_id'];
    $feedbackText = $_POST['feedback_text'];

    try {
        // Insert the feedback into the database
        $insertFeedbackQuery = "INSERT INTO feedback (student_id, feedback_text) VALUES (:student_id, :feedback_text)";
        $stmt = $conn->prepare($insertFeedbackQuery);
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindParam(':feedback_text', $feedbackText, PDO::PARAM_STR);
        $stmt->execute();

        // Redirect back to the dashboard with a success message
        echo "<script>alert('Feedback submitted successfully!'); window.location.href='dashboard.php';</script>";
    } catch (PDOException $e) {
        // Log the error and show an error message
        error_log("Error submitting feedback: " . $e->getMessage());
        echo "<script>alert('Failed to submit feedback. Please try again.'); window.location.href='dashboard.php';</script>";
    }
} else {
    // Redirect to the dashboard if the file is accessed directly
    header("Location: dashboard.php");
    exit();
}
?>