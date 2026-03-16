<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])) exit;

$user_id = $_SESSION['user_id'];
$book_id = intval($_POST['book_id']);
$last_page = intval($_POST['page_num']);

// Upsert last page
$stmt = $conn->prepare("
    INSERT INTO reading_progress (user_id, book_id, last_page) 
    VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE last_page = ?
");
$stmt->bind_param("iiii", $user_id, $book_id, $last_page, $last_page);
$stmt->execute();
?>
