<?php
session_start();
include "db.php";

// Auth check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? "Student";
$profile_pic = "uploads/default.png";

// Get profile picture
$result = $conn->query("SELECT profile_pic FROM users WHERE id='$user_id' LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    if (!empty($row['profile_pic'])) {
        $profile_pic = $row['profile_pic'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $attachment = "";

    if (!empty($_FILES['attachment']['name'])) {
        $uploadDir = "uploads/helpdesk/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES['attachment']['name']);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
            $attachment = $targetFile;
        }
    }

    $stmt = $conn->prepare("INSERT INTO helpdesk_tickets (user_id, subject, message, attachment, status, created_at) VALUES (?, ?, ?, ?, 'Open', NOW())");
    $stmt->bind_param("isss", $user_id, $subject, $message, $attachment);
    $stmt->execute();
    $success = "Your ticket has been submitted successfully!";
}

// Fetch user tickets
$tickets = $conn->query("SELECT * FROM helpdesk_tickets WHERE user_id=$user_id ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Help Desk</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:linear-gradient(135deg,#1f1c2c,#928dab);color:#fff;min-height:100vh;}

/* Navbar */
.navbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:14px 40px;
    background:linear-gradient(90deg,#141E30,#243B55);
    box-shadow:0 5px 15px rgba(0,0,0,.6);
    position:sticky;
    top:0;
    z-index:1000;
}
.nav-left {display:flex;align-items:center;gap:15px;}
.logo {font-size:1.3rem;font-weight:700;color:#00c6ff;display:flex;align-items:center;gap:8px;}
.nav-left img {width:45px;height:45px;border-radius:50%;border:2px solid #00c6ff;object-fit:cover;}
.nav-right {display:flex;align-items:center;gap:25px;}
.nav-right ul {display:flex;list-style:none;gap:20px;}
.nav-right ul li a {text-decoration:none;color:#fff;font-weight:500;position:relative;padding:6px 10px;transition:0.3s;}
.nav-right ul li a::after {content:'';position:absolute;width:0;height:2px;bottom:0;left:0;background:#00c6ff;transition:width 0.3s;}
.nav-right ul li a:hover::after {width:100%;}
.nav-right ul li a:hover {color:#00c6ff;}
.search-bar {display:flex;align-items:center;background:rgba(255,255,255,0.1);padding:4px 8px;border-radius:50px;gap:6px;}
.search-bar input {background:transparent;border:none;outline:none;color:#fff;padding:8px 10px;width:200px;font-size:14px;}
.search-bar input::placeholder {color:#bbb;}
.search-bar input:focus {box-shadow:0 0 10px #00c6ff;border-radius:50px;}
.search-bar button {background:#00c6ff;border:none;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:0.3s;color:#fff;}
.search-bar button:hover {background:#ff416c;transform:scale(1.1);}

/* Container */
.container {max-width:1200px;margin:30px auto;padding:0 15px;}

/* Form Card */
.form-card {
    background:rgba(20,20,20,0.9);
    border-radius:15px;
    padding:25px;
    box-shadow:0 5px 20px rgba(0,0,0,0.5);
    margin-bottom:30px;
}
.form-card h2 {color:#00c6ff;margin-bottom:15px;text-align:center;}
form input, form textarea {
    width:100%;padding:10px;margin-bottom:15px;border:none;border-radius:8px;font-size:14px;
}
form input[type="file"] {padding:5px;}
form button {
    background:linear-gradient(135deg,#00c6ff,#0072ff);
    color:#fff;padding:10px 20px;border:none;border-radius:8px;font-weight:600;cursor:pointer;
    transition:0.3s;
}
form button:hover {background:linear-gradient(135deg,#ff416c,#ff4b2b);transform:scale(1.05);}

/* Tickets Grid */
.ticket-list {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:25px;
}
.ticket-card {
    background:rgba(20,20,20,0.9);
    border-radius:15px;
    padding:20px;
    box-shadow:0 5px 15px rgba(0,0,0,0.5);
    transition:0.3s;
}
.ticket-card:hover {
    transform:translateY(-6px) scale(1.02);
    box-shadow:0 12px 25px rgba(0,198,255,0.6);
}
.ticket-card h3 {color:#ff416c;margin-bottom:8px;}
.ticket-card p {font-size:14px;margin-bottom:10px;color:#ddd;word-break:break-word;}
.ticket-card a {
    color:#00c6ff;text-decoration:none;
}
.ticket-card a:hover {text-decoration:underline;color:#ff416c;}
.status{font-weight:bold;}
.status.Open{color:#ff9800;}
.status.Closed{color:#4caf50;}

/* Success Message */
.success {
    background:rgba(76,175,80,0.3);
    color:#c8e6c9;
    padding:10px;
    border-radius:8px;
    margin-bottom:20px;
    text-align:center;
}
</style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar">
    <div class="nav-left">
        <h2 class="logo"><i class="fas fa-headset"></i> Help Desk</h2>
        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile">
        <span><?php echo htmlspecialchars($name); ?></span>
    </div>
    <div class="nav-right">
        <ul>
            <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="student_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <?php if(!empty($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="form-card">
        <h2>Submit a Ticket</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="subject" placeholder="Subject" required>
            <textarea name="message" rows="4" placeholder="Describe your issue..." required></textarea>
            <input type="file" name="attachment">
            <button type="submit"><i class="fas fa-paper-plane"></i> Submit</button>
        </form>
    </div>

    <h2 style="margin-bottom:15px;color:#00c6ff;">Your Tickets</h2>
    <div class="ticket-list">
        <?php if ($tickets->num_rows > 0): ?>
            <?php while($t = $tickets->fetch_assoc()): ?>
                <div class="ticket-card">
                    <h3><?php echo htmlspecialchars($t['subject']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($t['message'])); ?></p>
                    <?php if (!empty($t['attachment'])): ?>
                        <p><a href="<?php echo $t['attachment']; ?>" target="_blank"><i class="fas fa-paperclip"></i> View Attachment</a></p>
                    <?php endif; ?>
                    <p class="status <?php echo $t['status']; ?>">Status: <?php echo $t['status']; ?></p>
                    <small>Created: <?php echo $t['created_at']; ?></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No tickets submitted yet.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
