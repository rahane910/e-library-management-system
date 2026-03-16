<?php
session_start();
include "db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Student submits new book request
if ($role == 'student' && isset($_POST['request'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];

    $stmt = $conn->prepare("INSERT INTO book_requests (user_id, title, author) VALUES (?,?,?)");
    $stmt->bind_param("iss", $user_id, $title, $author);
    if ($stmt->execute()) {
        $success = "✅ Request submitted successfully!";
    } else {
        $error = "❌ Failed to submit request!";
    }
}

// Admin approves/denies requests
if ($role == 'admin' && isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $action = ($_GET['action'] == 'approve') ? 'approved' : 'denied';

    $stmt = $conn->prepare("UPDATE book_requests SET status=? WHERE id=?");
    $stmt->bind_param("si", $action, $id);
    $stmt->execute();
    header("Location: book_requests.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Requests</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
        body {
            background: linear-gradient(135deg, #1f1c2c, #928dab);
            color: #fff;
            min-height: 100vh;
        }
        .container {
            width: 90%;
            max-width: 1100px;
            margin: 40px auto;
            background: rgba(20,20,20,0.9);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        }
        h2 {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 20px;
            color: #00c6ff;
        }
        form, table {
            background: rgba(30,30,30,0.95);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.4);
        }
        form h3 {
            margin-bottom: 15px;
            color: #ff416c;
        }
        input, button {
            padding: 10px;
            width: 100%;
            margin: 10px 0;
            border: none;
            border-radius: 8px;
            font-size: 14px;
        }
        input {
            background: #f8f9fa;
            color: #333;
        }
        button {
            background: linear-gradient(135deg,#00c6ff,#0072ff);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: linear-gradient(135deg,#ff416c,#ff4b2b);
            transform: scale(1.05);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background: #00c6ff;
            color: white;
        }
        tr:nth-child(even) { background: rgba(255,255,255,0.05); }
        tr:hover { background: rgba(0,198,255,0.1); }
        .status-pending { color: orange; font-weight: bold; }
        .status-approved { color: lightgreen; font-weight: bold; }
        .status-denied { color: #ff4b2b; font-weight: bold; }
        .actions a {
            margin-right: 10px;
            text-decoration: none;
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 6px;
            transition: 0.3s;
        }
        .approve { background: rgba(0,198,0,0.2); color: lightgreen; }
        .deny { background: rgba(255,65,108,0.2); color: #ff4b2b; }
        .actions a:hover {
            transform: scale(1.1);
            background: rgba(255,255,255,0.1);
        }
        /* Go Back Button */
        .go-back {
            display:inline-block;
            margin-bottom:20px;
            background:linear-gradient(135deg,#ff416c,#ff4b2b);
            padding:10px 20px;
            border-radius:8px;
            color:#fff;
            text-decoration:none;
            font-weight:600;
            transition:0.3s;
        }
        .go-back:hover {
            background:linear-gradient(135deg,#00c6ff,#0072ff);
            transform:scale(1.05);
        }
    </style>
</head>
<body>
<div class="container">
    <h2><i class="fas fa-book"></i> Book Requests</h2>

    <!-- Go Back Button -->
    <a href="student_resources.php" class="go-back"><i class="fas fa-arrow-left"></i> Go Back</a>

    <?php if (isset($success)) echo "<p style='color:lightgreen;font-weight:bold;'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p style='color:#ff4b2b;font-weight:bold;'>$error</p>"; ?>

    <!-- Student request form -->
    <?php if ($role == 'student') { ?>
        <form method="POST">
            <h3><i class="fas fa-plus-circle"></i> Request a New Book</h3>
            <input type="text" name="title" placeholder="📖 Book Title" required>
            <input type="text" name="author" placeholder="✍️ Author Name" required>
            <button type="submit" name="request"><i class="fas fa-paper-plane"></i> Submit Request</button>
        </form>
    <?php } ?>

    <!-- Admin requests management -->
    <?php if ($role == 'admin') { 
        $requests = $conn->query("SELECT br.*, u.name FROM book_requests br JOIN users u ON br.user_id=u.id ORDER BY br.requested_at DESC");
    ?>
        <h3 style="color:#ffcc00;"><i class="fas fa-tasks"></i> Manage Book Requests</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Title</th>
                <th>Author</th>
                <th>Status</th>
                <th>Requested At</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $requests->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['author']) ?></td>
                    <td class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
                    <td><?= $row['requested_at'] ?></td>
                    <td class="actions">
                        <?php if ($row['status'] == 'pending') { ?>
                            <a class="approve" href="?action=approve&id=<?= $row['id'] ?>"><i class="fas fa-check-circle"></i> Approve</a>
                            <a class="deny" href="?action=deny&id=<?= $row['id'] ?>"><i class="fas fa-times-circle"></i> Deny</a>
                        <?php } else { echo "-"; } ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>
</div>
</body>
</html>
