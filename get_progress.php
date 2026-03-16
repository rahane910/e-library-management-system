<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])) exit;

$user_id = $_SESSION['user_id'];
$book_id = intval($_GET['book_id']);

$stmt = $conn->prepare("SELECT last_page FROM reading_progress WHERE user_id=? AND book_id=?");
$stmt->bind_param("ii",$user_id,$book_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode(['last_page' => $result['last_page'] ?? 1]);
?>
