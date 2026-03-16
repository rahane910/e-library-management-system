<?php
session_start();
include "db.php";

if(isset($_POST['reset'])){
    $email = $_POST['email'];
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if($new_pass !== $confirm_pass){
        $error = "❌ New password and confirm password do not match!";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT password FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            $row = $result->fetch_assoc();

            // Verify old password
            if(password_verify($old_pass, $row['password'])){
                $hashed_new = password_hash($new_pass, PASSWORD_BCRYPT);

                $update = $conn->prepare("UPDATE users SET password=? WHERE email=?");
                $update->bind_param("ss", $hashed_new, $email);

                if($update->execute()){
                    $success = "✅ Password reset successfully!";
                } else {
                    $error = "❌ Database error!";
                }
            } else {
                $error = "❌ Old password is incorrect!";
            }
        } else {
            $error = "❌ Email not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <style>
    body {
        margin: 0;
        font-family: "Segoe UI", sans-serif;
        background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }
    .glass-card {
        background: rgba(255,255,255,0.1);
        border-radius: 20px;
        padding: 40px;
        width: 420px;
        backdrop-filter: blur(14px);
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        color: #fff;
        animation: fadeIn 0.6s ease-in-out;
    }
    .glass-card h2 {
        text-align: center;
        margin-bottom: 25px;
        font-size: 26px;
        letter-spacing: 1px;
    }
    .message {
        text-align: center;
        font-weight: bold;
        margin-bottom: 15px;
    }
    .success { color: #4caf50; }
    .error { color: #ff5252; }
    label {
        font-weight: bold;
        font-size: 14px;
        display: block;
        margin-bottom: 6px;
    }
    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 12px;
        margin-bottom: 18px;
        border: none;
        border-radius: 12px;
        outline: none;
        font-size: 14px;
        background: rgba(255,255,255,0.2);
        color: #fff;
        transition: 0.3s;
    }
    input::placeholder {
        color: #ddd;
    }
    input:focus {
        background: rgba(255,255,255,0.3);
        box-shadow: 0 0 10px #6c63ff;
    }
    input[type="submit"] {
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: bold;
        cursor: pointer;
        background: linear-gradient(90deg,#6c63ff,#48c6ef);
        color: #fff;
        transition: 0.3s;
    }
    input[type="submit"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(72,198,239,0.4);
    }
    .back-link {
        display: block;
        text-align: center;
        margin-top: 15px;
        text-decoration: none;
        font-weight: bold;
        color: #48c6ef;
    }
    .back-link:hover {
        color: #6c63ff;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="glass-card">
    <h2>🔐 Reset Password</h2>

    <?php if(isset($success)) echo "<p class='message success'>$success</p>"; ?>
    <?php if(isset($error)) echo "<p class='message error'>$error</p>"; ?>

    <form method="POST">
      <label>Email:</label>
      <input type="email" name="email" placeholder="Enter your email" required>

      <label>Old Password:</label>
      <input type="password" name="old_password" placeholder="Enter old password" required>

      <label>New Password:</label>
      <input type="password" name="new_password" placeholder="Enter new password" required>

      <label>Confirm New Password:</label>
      <input type="password" name="confirm_password" placeholder="Re-enter new password" required>

      <input type="submit" name="reset" value="Reset Password">
    </form>

    <a class="back-link" href="login.php">⬅ Back to Login</a>
  </div>
</body>
</html>
