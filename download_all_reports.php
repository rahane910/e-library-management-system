<?php
require_once('tcpdf/tcpdf.php');
include "db.php";

$pdf = new TCPDF();
$pdf->SetCreator('Library System');
$pdf->SetTitle('All Student Reports');
$pdf->SetAutoPageBreak(TRUE, 15);

$charts = json_decode($_POST['charts'], true);

$students = $conn->query("SELECT id, first_name, email, last_login FROM users WHERE role='student'");
while($student = $students->fetch_assoc()){
    $pdf->AddPage();
    $pdf->SetFont('helvetica','B',16);
    $pdf->Cell(0,10,"Student Report: ".$student['first_name'],0,1);
    $pdf->SetFont('helvetica','',12);
    $pdf->Cell(0,8,"Email: ".$student['email'],0,1);
    $pdf->Cell(0,8,"Last Login: ".($student['last_login'] ?? 'Never'),0,1);

    // Books completed
    $booksCompleted = $conn->query("SELECT b.title FROM issued_books ib JOIN books b ON ib.book_id=b.id WHERE ib.user_id=".$student['id']." AND ib.status='returned'");
    $pdf->Ln(5);
    $pdf->SetFont('helvetica','B',14);
    $pdf->Cell(0,8,"Books Completed:",0,1);
    $pdf->SetFont('helvetica','',12);
    if($booksCompleted->num_rows>0){
        while($book = $booksCompleted->fetch_assoc()){
            $pdf->Cell(0,6,"- ".$book['title'],0,1);
        }
    } else { $pdf->Cell(0,6,"No books completed yet.",0,1); }

    // Add Chart image
    if(isset($charts[$student['id']])){
        $imgdata = $charts[$student['id']];
        $pdf->Ln(5);
        $pdf->Image('@'.base64_decode(preg_replace('#^data:image/\w+;base64,#i','',$imgdata)), '', '', 100, 60, '', '', '', false, 300);
    }
}

$pdf->Output('all_student_reports.pdf', 'D');
?>
