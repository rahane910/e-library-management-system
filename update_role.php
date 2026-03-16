<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!='admin') {
    header("Location: login.php");
    exit;
}

include "db.php";

if(isset($_POST['id']) && isset($_POST['role'])){
    $id = intval($_POST['id']);
    $role = $_POST['role'];
    $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->bind_param("si",$role,$id);
    $stmt->execute();
}
header("Location: manage_users.php");
exit;
?>
