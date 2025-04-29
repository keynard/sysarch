<?php
session_start();
include 'db.php';
require('fpdf/fpdf.php');

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get date range from request
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch sit-in records
$query = "SELECT l.sitin_id, s.student_number, s.firstname, s.lastname, 
          l.laboratory_number, l.purpose, l.time_in, l.time_out
          FROM SitIn_Log l
          JOIN students s ON l.student_id = s.student_id
          WHERE DATE(l.time_in) BETWEEN :start_date AND :end_date
          ORDER BY l.time_in DESC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':start_date', $startDate);
$stmt->bindParam(':end_date', $endDate);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create PDF
class PDF extends FPDF {
    function Header() {
        // Logo - using a smaller size
        $this->Image('ccs_logo.png', 10, 10, 20);
        
        // Header text
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'College of Computer Studies', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 10, 'Sit-in Records', 0, 1, 'C');
        
        // Date range
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 10, 'Date Range: ' . $GLOBALS['startDate'] . ' to ' . $GLOBALS['endDate'], 0, 1, 'C');
        
        // Table header
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(25, 7, 'Student No.', 1, 0, 'C', true);
        $this->Cell(45, 7, 'Name', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Lab No.', 1, 0, 'C', true);
        $this->Cell(35, 7, 'Time In', 1, 0, 'C', true);
        $this->Cell(35, 7, 'Time Out', 1, 1, 'C', true);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Initialize PDF
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);

// Add data
$pdf->SetFont('Arial', '', 7);
foreach ($records as $record) {
    $pdf->Cell(25, 6, $record['student_number'], 1, 0, 'C');
    $pdf->Cell(45, 6, $record['lastname'] . ', ' . $record['firstname'], 1, 0, 'L');
    $pdf->Cell(25, 6, $record['laboratory_number'], 1, 0, 'C');
    $pdf->Cell(35, 6, $record['time_in'], 1, 0, 'C');
    $pdf->Cell(35, 6, $record['time_out'], 1, 1, 'C');
}

// Add summary
$pdf->Ln(6);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 7, 'Summary', 0, 1, 'L');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(0, 6, 'Total Records: ' . count($records), 0, 1, 'L');

// Output PDF
$pdf->Output('D', 'Sit-in_Records.pdf');
?> 