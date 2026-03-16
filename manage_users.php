<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role']!='admin'){
    header("Location: login.php");
    exit;
}

// ✅ Add new user
if(isset($_POST['add_user'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = password_hash("123456", PASSWORD_DEFAULT); // Default password

    $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,status) VALUES (?,?,?,?, 'active')");
    $stmt->bind_param("ssss",$name,$email,$password,$role);
    $stmt->execute();
    $success = "User added successfully! Default password: 123456";
}

// ✅ Update user
if(isset($_POST['edit_user'])){
    $id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
    $stmt->bind_param("sssi",$name,$email,$role,$id);
    $stmt->execute();
    $success = "User updated successfully!";
}

// ✅ Block/Unblock user
if(isset($_GET['toggle'])){
    $id = $_GET['toggle'];
    $result = $conn->query("SELECT status FROM users WHERE id=$id");
    $user = $result->fetch_assoc();
    $new_status = ($user['status']=='active') ? 'blocked' : 'active';
    $conn->query("UPDATE users SET status='$new_status' WHERE id=$id");
    $success = "User status updated!";
}

// ✅ Delete user
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id");
    $success = "User deleted!";
}

// ✅ Reset password
if(isset($_GET['reset'])){
    $id = $_GET['reset'];
    $new_password = password_hash("123456", PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password='$new_password' WHERE id=$id");
    $success = "Password reset to 123456!";
}

// ✅ Fetch users
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f4f6f9; }
        h2 { text-align:center; padding:20px; margin:0; background:#2196F3; color:white; }
        .container { width:90%; margin:20px auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th,td { padding:12px; border:1px solid #ddd; text-align:center; }
        th { background:#2196F3; color:white; }
        tr:nth-child(even){ background:#f9f9f9; }
        a.btn { padding:6px 12px; margin:2px; text-decoration:none; border-radius:5px; font-size:14px; }
        .edit { background:#ffc107; color:white; }
        .delete { background:#f44336; color:white; }
        .toggle { background:#9c27b0; color:white; }
        .reset { background:#009688; color:white; }
        .add-btn { display:inline-block; margin:10px 0; padding:10px 15px; background:#4CAF50; color:white; border-radius:5px; text-decoration:none; }
        .form-popup { display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.3); }
        .form-popup input, select { width:100%; padding:10px; margin:8px 0; border:1px solid #ddd; border-radius:5px; }
        .form-popup button { padding:10px 15px; background:#2196F3; color:white; border:none; border-radius:5px; cursor:pointer; }
        .close-btn { background:#f44336; float:right; cursor:pointer; padding:5px 10px; border-radius:50%; }
        .msg { text-align:center; font-weight:bold; margin:10px; color:green; }
    </style>
    <script>
        function openForm(id){
            document.getElementById(id).style.display='block';
        }
        function closeForm(id){
            document.getElementById(id).style.display='none';
        }
    </script>
</head>
<body>
    <h2>Manage Users</h2>
    <div class="container">

        <?php if(isset($success)) echo "<p class='msg'>$success</p>"; ?>

        <a href="#" class="add-btn" onclick="openForm('addForm')">+ Add New User</a>

        <table>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th>
            </tr>
            <?php while($u=$users->fetch_assoc()){ ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= $u['name'] ?></td>
                <td><?= $u['email'] ?></td>
                <td><?= ucfirst($u['role']) ?></td>
                <td><?= ucfirst($u['status']) ?></td>
                <td>
                    <a href="#" class="btn edit" onclick="document.getElementById('editForm<?= $u['id'] ?>').style.display='block'">Edit</a>
                    <a href="?delete=<?= $u['id'] ?>" class="btn delete" onclick="return confirm('Delete user?')">Delete</a>
                    <a href="?toggle=<?= $u['id'] ?>" class="btn toggle"><?= ($u['status']=='active'?'Block':'Unblock') ?></a>
                    <a href="?reset=<?= $u['id'] ?>" class="btn reset" onclick="return confirm('Reset password to 123456?')">Reset</a>
                </td>
            </tr>

            <!-- Edit Form -->
            <div class="form-popup" id="editForm<?= $u['id'] ?>">
                <span class="close-btn" onclick="closeForm('editForm<?= $u['id'] ?>')">×</span>
                <h3>Edit User</h3>
                <form method="POST">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <input type="text" name="name" value="<?= $u['name'] ?>" required>
                    <input type="email" name="email" value="<?= $u['email'] ?>" required>
                    <select name="role" required>
                        <option <?= ($u['role']=='admin'?'selected':'') ?> value="admin">Admin</option>
                        <option <?= ($u['role']=='student'?'selected':'') ?> value="student">Student</option>
                        <option <?= ($u['role']=='teacher'?'selected':'') ?> value="teacher">Teacher</option>
                    </select>
                    <button type="submit" name="edit_user">Update</button>
                </form>
            </div>
            <?php } ?>
        </table>
    </div>

    <!-- Add User Form -->
    <div class="form-popup" id="addForm">
        <span class="close-btn" onclick="closeForm('addForm')">×</span>
        <h3>Add User</h3>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <select name="role" required>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit" name="add_user">Add User</button>
        </form>
    </div>

</body>
</html>
