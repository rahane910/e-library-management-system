<?php
session_start();
include "db.php";

// ================= LOGIN LOGIC =================
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $m_id = $_POST['m_id'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND m_id=? LIMIT 1");
    $stmt->bind_param("ss", $email, $m_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['status'] == 'blocked') {
                $error = "Your account is blocked. Contact Admin.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['first_name'] . " " . $user['last_name'];
                $_SESSION['profile_pic'] = $user['profile_pic'];

                if ($user['role'] == 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: student_dashboard.php");
                }
                exit;
            }
        } else {
            $error = "Invalid Password!";
        }
    } else {
        $error = "Invalid Email or M.ID!";
    }
}

// ================= REGISTER LOGIC =================
if (isset($_POST['register'])) {
    $m_id = $_POST['m_id'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Upload profile picture
    $profile_pic = "";
    if (!empty($_FILES['profile_pic']['name'])) {
        $target_dir = "uploads/profiles/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $file_name = time() . "_" . basename($_FILES['profile_pic']['name']);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
            $profile_pic = $target_file;
        }
    }

    $stmt = $conn->prepare("INSERT INTO users (m_id, first_name, middle_name, last_name, email, password, role, profile_pic, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'student', ?, 'active')");
    $stmt->bind_param("sssssss", $m_id, $first_name, $middle_name, $last_name, $email, $password, $profile_pic);

    if ($stmt->execute()) {
        $success = "Registration successful! Please login.";
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Library Auth</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef2f3; margin:0; padding:0; }
        .container { width: 450px; margin: 60px auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0px 4px 10px rgba(0,0,0,0.1); }
        .tabs { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .tab { flex: 1; text-align: center; padding: 12px; cursor: pointer; background: #f0f0f0; border-radius: 8px 8px 0 0; }
        .tab.active { background: #4CAF50; color: white; font-weight: bold; }
        form { display: none; }
        form.active { display: block; }
        input, button { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 6px; }
        button { background: #4CAF50; color: white; font-size: 16px; border: none; cursor: pointer; }
        button:hover { background: #45a049; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<div class="container">
    <div class="tabs">
        <div class="tab active" onclick="showTab('login')">Login</div>
        <div class="tab" onclick="showTab('register')">Register</div>
    </div>

    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>

    <!-- LOGIN FORM -->
    <form method="POST" class="active" id="loginForm">
        <input type="text" name="m_id" placeholder="Enter 5-digit M.ID" pattern="\d{5}" maxlength="5" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <!-- REGISTER FORM -->
    <form method="POST" enctype="multipart/form-data" id="registerForm">
        <input type="text" name="m_id" placeholder="5-digit M.ID" pattern="\d{5}" maxlength="5" required>
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="middle_name" placeholder="Middle/Father Name">
        <input type="text" name="last_name" placeholder="Surname" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <label>Upload Profile Picture:</label>
        <input type="file" name="profile_pic" accept="image/*">
        <button type="submit" name="register">Register</button>
    </form>
</div>

<script>
function showTab(tab) {
    document.getElementById("loginForm").classList.remove("active");
    document.getElementById("registerForm").classList.remove("active");
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));

    if (tab === "login") {
        document.getElementById("loginForm").classList.add("active");
        document.querySelectorAll(".tab")[0].classList.add("active");
    } else {
        document.getElementById("registerForm").classList.add("active");
        document.querySelectorAll(".tab")[1].classList.add("active");
    }
}
</script>
</body>
</html>
