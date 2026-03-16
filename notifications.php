<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch notifications for this user (or global ones)
if($role == 'admin') {
    $result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
} else {
    $result = $conn->query("SELECT * FROM notifications WHERE user_id IS NULL OR user_id=$user_id ORDER BY created_at DESC");
}

// Mark all as seen for this user
$conn->query("UPDATE notifications SET seen=1 WHERE user_id IS NULL OR user_id=$user_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Notifications</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f6f9;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 800px;
      margin: 50px auto;
      background: #fff;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 20px;
    }
    .notification {
      padding: 15px;
      margin: 10px 0;
      border-radius: 10px;
      background: #f9f9f9;
      border-left: 5px solid #007bff;
      transition: transform 0.2s ease-in-out;
    }
    .notification:hover {
      transform: translateX(5px);
      background: #eef6ff;
    }
    .notification small {
      display: block;
      color: #777;
      font-size: 12px;
      margin-top: 5px;
    }
    .back {
      display: block;
      text-align: center;
      margin-top: 20px;
      text-decoration: none;
      color: #fff;
      background: #007bff;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: bold;
    }
    .back:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>🔔 Notifications</h2>
    <?php if($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): ?>
        <div class="notification">
          <?= htmlspecialchars($row['message']) ?>
          <small>📅 <?= $row['created_at'] ?></small>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center; color:#666;">No notifications yet.</p>
    <?php endif; ?>

    <?php if($role == 'admin'): ?>
      <a href="admin_dashboard.php" class="back">⬅ Back to Dashboard</a>
    <?php else: ?>
      <a href="student_dashboard.php" class="back">⬅ Back to Dashboard</a>
    <?php endif; ?>
  </div>
</body>
</html>
