<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access denied!");
}

include "db.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT pdf_file FROM books WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();

    if ($book && file_exists($book['pdf_file'])) {
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($book['pdf_file']) . '"');
        readfile($book['pdf_file']);
    } else {
        echo "PDF not found!";
    }
}
?>
