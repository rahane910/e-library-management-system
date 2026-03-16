<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

include "db.php";

// Delete Book
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT pdf_file FROM books WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if($result && file_exists($result['pdf_file'])){
        unlink($result['pdf_file']); // delete file from server
    }

    $del = $conn->prepare("DELETE FROM books WHERE id=?");
    $del->bind_param("i",$id);
    $del->execute();
    echo "<script>alert('Book deleted successfully!');window.location='manage_books.php';</script>";
}

// Update Book
if(isset($_POST['update'])){
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $category = trim($_POST['category']);
    $qty = intval($_POST['available_qty']);

    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, category=?, available_qty=? WHERE id=?");
    $stmt->bind_param("sssii",$title,$author,$category,$qty,$id);
    if($stmt->execute()){
        echo "<script>alert('Book updated successfully!');window.location='manage_books.php';</script>";
    } else {
        echo "<p style='color:red;'>Update failed: ".$stmt->error."</p>";
    }
}

// Fetch Books
$books = $conn->query("SELECT * FROM books ORDER BY title ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Books</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f7f9; margin:0; padding:20px; }
        h1 { text-align:center; color:#333; }
        table { width:100%; border-collapse:collapse; margin-top:20px; background:#fff; border-radius:8px; overflow:hidden; }
        th, td { padding:12px; border:1px solid #ddd; text-align:left; }
        th { background:#2196F3; color:white; }
        tr:nth-child(even) { background:#f9f9f9; }
        a.btn { padding:6px 12px; border-radius:4px; text-decoration:none; color:#fff; }
        .edit { background:#4CAF50; }
        .delete { background:#f44336; }
        .back { background:#555; float:right; margin-bottom:10px; }
        form { margin:20px auto; padding:15px; background:#fff; border-radius:8px; width:400px; display:none; }
        input, select { width:100%; padding:8px; margin:6px 0; border:1px solid #ccc; border-radius:4px; }
        input[type=submit] { background:#2196F3; color:#fff; border:none; cursor:pointer; }
        input[type=submit]:hover { background:#1976D2; }
    </style>
    <script>
        function editBook(id, title, author, category, qty){
            document.getElementById('editForm').style.display = 'block';
            document.getElementById('bookId').value = id;
            document.getElementById('title').value = title;
            document.getElementById('author').value = author;
            document.getElementById('category').value = category;
            document.getElementById('qty').value = qty;
            window.scrollTo(0,0);
        }
    </script>
</head>
<body>
    <h1>Manage Books</h1>
    <a href="admin_dashboard.php" class="btn back">⬅ Back to Dashboard</a>

    <!-- Edit Form -->
    <form method="POST" id="editForm">
        <input type="hidden" name="id" id="bookId">
        Title: <input type="text" name="title" id="title" required>
        Author: <input type="text" name="author" id="author" required>
        Category: <input type="text" name="category" id="category" required>
        Quantity: <input type="number" name="available_qty" id="qty" required>
        <input type="submit" name="update" value="Update Book">
    </form>

    <!-- Books Table -->
    <table>
        <tr><th>Title</th><th>Author</th><th>Category</th><th>Quantity</th><th>PDF</th><th>Action</th></tr>
        <?php while($row=$books->fetch_assoc()){ ?>
        <tr>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><?php echo htmlspecialchars($row['author']); ?></td>
            <td><?php echo htmlspecialchars($row['category']); ?></td>
            <td><?php echo $row['available_qty']; ?></td>
            <td><?php echo (!empty($row['pdf_file']) && file_exists($row['pdf_file'])) ? "<a href='".$row['pdf_file']."' target='_blank'>View</a>" : "No PDF"; ?></td>
            <td>
                <a href="javascript:void(0);" class="btn edit"
                   onclick="editBook('<?php echo $row['id']; ?>',
                                     '<?php echo htmlspecialchars($row['title']); ?>',
                                     '<?php echo htmlspecialchars($row['author']); ?>',
                                     '<?php echo htmlspecialchars($row['category']); ?>',
                                     '<?php echo $row['available_qty']; ?>')">Edit</a>
                <a href="?delete=<?php echo $row['id']; ?>" class="btn delete" onclick="return confirm('Delete this book?')">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>
