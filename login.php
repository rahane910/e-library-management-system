<?php
session_start();
include "db.php";

$error = "";

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $m_id = isset($_POST['m_id']) ? trim($_POST['m_id']) : '';

    if ($email && $password && $m_id) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND m_id=? LIMIT 1");
        if (!$stmt) die("SQL Error: " . $conn->error);
        $stmt->bind_param("ss", $email, $m_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = !empty($user['first_name']) ? $user['first_name'] : "User";
                $_SESSION['profile_pic'] = !empty($user['profile_pic']) ? $user['profile_pic'] : "uploads/default.png";
                if ($user['role'] == 'student') header("Location: home.php");
                elseif ($user['role'] == 'admin') header("Location: admin_dashboard.php");
                exit;
            } else $error = "Invalid password!";
        } else $error = "User not found!";
    } else $error = "Please fill all fields!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

* {margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body {
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(135deg, #1f1c2c, #928dab);
    color:#fff;
}
.login-box {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(25px);
    padding: 35px 30px;
    border-radius: 25px;
    width: 380px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.5);
    text-align: center;
    position: relative;
}
.login-box h2 {
    margin-bottom: 25px;
    font-size: 2rem;
    color: #00c6ff;
}
.login-box input {
    width: 100%;
    padding: 12px 15px;
    margin: 10px 0;
    border-radius: 12px;
    border: none;
    background: rgba(255,255,255,0.15);
    color: #fff;
    font-size: 15px;
    outline: none;
    transition: background 0.3s, transform 0.2s;
}
.login-box input::placeholder { color: #eee; }
.login-box input:hover { background: rgba(255,255,255,0.25); transform: scale(1.02);}
.login-box button {
    width: 100%;
    padding: 12px 15px;
    margin-top: 10px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg,#00c6ff,#0072ff);
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.3s;
}
.login-box button:hover {
    transform: scale(1.05);
    box-shadow: 0 0 20px #00c6ff;
}
.msg {margin: 12px 0; font-weight: 600;}
.msg.error {color: #ff416c;}
.register-link {
    margin-top: 15px;
    font-size: 14px;
}
.register-link a {
    color: #00c6ff;
    font-weight: 600;
    text-decoration: none;
    transition: 0.3s;
}
.register-link a:hover { text-decoration: underline; color: #ff4b2b; }

/* Add subtle floating circles */
body::before, body::after {
    content: '';
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    z-index:0;
}
body::before { width:300px;height:300px;background:#00c6ff;top:-50px;left:-50px;opacity:0.3;}
body::after { width:400px;height:400px;background:#ff416c;bottom:-100px;right:-100px;opacity:0.2;}
</style>
</head>
<body>
<div class="login-box">
    <h2>Login</h2>
    <?php if($error) echo "<p class='msg error'>$error</p>"; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="text" name="m_id" placeholder="5-Digit M.ID (SXXXX or AXXXX)" required>
        <button type="submit" name="login">Login</button>
    </form>
    <div class="register-link">
        <p>Don't have an account? <a href="register.php">Register Here</a></p>
    </div>
</div>
</body>
</html>
