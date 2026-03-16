<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role']!='admin') {
    header("Location: auth.php");
    exit;
}
include "db.php";

// Fetch books & categories
$books = $conn->query("SELECT * FROM books ORDER BY title ASC");
$categories = $conn->query("SELECT DISTINCT category FROM books ORDER BY category ASC");

// Summary stats
$result = $conn->query("SELECT COUNT(*) AS cnt FROM books");
if(!$result){
    die("SQL Error: ".$conn->error);
}
$totalBooks = $result->fetch_assoc()['cnt'];
$totalStudents = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='student'")->fetch_assoc()['cnt'];
$booksIssuedToday = $conn->query("SELECT COUNT(*) AS cnt FROM issued_books WHERE DATE(issue_date)=CURDATE()")->fetch_assoc()['cnt'];
$pendingRequests = $conn->query("SELECT COUNT(*) AS cnt FROM book_requests WHERE status='pending'")->fetch_assoc()['cnt'];

// Popular books data
$popularBooks = $conn->query("SELECT b.title, COUNT(ib.id) AS issued_count 
                              FROM books b LEFT JOIN issued_books ib ON b.id=ib.book_id 
                              GROUP BY b.id ORDER BY issued_count DESC")->fetch_all(MYSQLI_ASSOC);

// Active vs inactive students
$activeStudents = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='student' AND status='active'")->fetch_assoc()['cnt'];
$inactiveStudents = $totalStudents - $activeStudents;

// Fetch students & progress
$students = $conn->query("SELECT id, first_name, last_name, last_login FROM users WHERE role='student'");
$issued_books_all = $conn->query("SELECT user_id, book_id, status FROM issued_books");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<style>
/* ===== Previous styles ===== */
* {margin:0; padding:0; box-sizing:border-box;}
body {font-family: 'Segoe UI', sans-serif; background:#f4f6f9; color:#333;}
.sidebar {width: 240px; height: 100vh; position: fixed; left:0; top:0; background:#1e293b; padding:20px 0; color:#fff;}
.sidebar h2 {text-align:center; margin-bottom:30px; font-size:20px; letter-spacing:1px;}
.sidebar .nav-link {display:flex; align-items:center; gap:10px; padding:12px 20px; color:#fff; text-decoration:none; margin:5px 0; transition:0.3s; border-left:4px solid transparent;}
.sidebar .nav-link:hover {background:#0f172a; border-left:4px solid #38bdf8; padding-left:24px;}
.sidebar .nav-link.active {background:#0f172a; border-left:4px solid #3b82f6;}
.main {margin-left:240px; padding:20px;}
header {background:#fff; padding:15px 20px; box-shadow:0 2px 4px rgba(0,0,0,0.1); margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;}
header h1 {font-size:22px;}
header a {background:#ef4444; color:#fff; padding:8px 15px; text-decoration:none; border-radius:6px; font-size:14px;}
header a:hover {background:#dc2626;}
.card {background:#fff; padding:20px; margin-bottom:20px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.05);}
.card h2 {margin-bottom:15px; font-size:18px; color:#1e293b;}
.card input, .card select {width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:6px; font-size:14px;}
.card input[type=submit] {background:#3b82f6; color:#fff; border:none; cursor:pointer; transition:0.3s;}
.card input[type=submit]:hover {background:#2563eb;}
table {width:100%; border-collapse:collapse; margin-top:10px;}
th, td {padding:12px; text-align:left; border-bottom:1px solid #ddd;}
th {background:#1e293b; color:#fff;}
tr:hover {background:#f1f5f9;}
.view-btn, .edit-btn, .delete-btn {padding:5px 10px; border-radius:4px; color:#fff; text-decoration:none; font-size:13px; margin-right:3px;}
.view-btn {background:#10b981;} .view-btn:hover {background:#059669;}
.edit-btn {background:#f59e0b;} .edit-btn:hover {background:#d97706;}
.delete-btn {background:#ef4444;} .delete-btn:hover {background:#dc2626;}
.carousel-container{display:flex; overflow-x:auto; gap:25px; padding:15px 5px; scroll-behavior:smooth; margin-bottom:20px;}
.book-card{flex:0 0 180px; background:#fff; border-radius:12px; padding:10px; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.1); transition:0.3s;}
.book-card:hover{transform:translateY(-5px);}
.book-card canvas{width:100%; border-radius:8px; margin-bottom:8px;}
.book-card h3{ font-size:1rem; margin:5px 0; }
.book-card p{ font-size:0.85rem; color:#444; margin-bottom:8px; }
.book-card button{ background:#3b82f6; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:0.85rem; }
.book-card button:hover{ background:#2563eb; }
.search-filter{display:flex; gap:15px; margin-bottom:15px;}
.search-filter input, .search-filter select{flex:1; padding:8px; border-radius:6px; border:1px solid #ccc;}

/* Charts */
.chart-container {width:100%; display:flex; gap:20px; flex-wrap:wrap; margin-top:20px;}
.chart-box {flex:1; min-width:300px; max-width:500px; height:300px; background:#fff; padding:15px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.05);}
</style>
</head>
<body>

<div class="sidebar">
    <h2>📚 Admin Panel</h2>
    <a href="admin_dashboard.php" class="nav-link active">🏠 Dashboard</a>
    <a href="add_book.php" class="nav-link">➕ Add Book</a>
    <a href="manage_books.php" class="nav-link">📖 Manage Books</a>
    <a href="manage_users.php" class="nav-link">👥 Manage Users</a>
    <a href="logout.php" class="nav-link">🚪 Logout</a>
</div>

<div class="main">
<header>
    <h1>Welcome, Admin</h1>
    <a href="logout.php">Logout</a>
</header>

<!-- Summary cards -->
<div class="card" style="display:flex; gap:15px; flex-wrap:wrap;">
    <div style="flex:1; padding:15px; background:#3b82f6; color:#fff; border-radius:10px; text-align:center;">
        <h3>Total Books</h3><p><?= $totalBooks ?></p>
    </div>
    <div style="flex:1; padding:15px; background:#10b981; color:#fff; border-radius:10px; text-align:center;">
        <h3>Total Students</h3><p><?= $totalStudents ?></p>
    </div>
    <div style="flex:1; padding:15px; background:#f59e0b; color:#fff; border-radius:10px; text-align:center;">
        <h3>Books Issued Today</h3><p><?= $booksIssuedToday ?></p>
    </div>
    <div style="flex:1; padding:15px; background:#ef4444; color:#fff; border-radius:10px; text-align:center;">
        <h3>Pending Requests</h3><p><?= $pendingRequests ?></p>
    </div>
</div>

<!-- Search / Filter -->
<div class="search-filter">
    <input type="text" id="search-input" placeholder="Search by title or author...">
    <select id="category-filter">
        <option value="">All Categories</option>
        <?php while($cat=$categories->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($cat['category']) ?>"><?= htmlspecialchars($cat['category']) ?></option>
        <?php endwhile; ?>
    </select>
</div>

<!-- Book Carousel -->
<div class="carousel-container" id="book-carousel">
<?php
$books->data_seek(0);
while($row=$books->fetch_assoc()):
    $pdf_path = $row['pdf_file'];
    if(!empty($pdf_path) && file_exists($pdf_path)):
?>
<div class="book-card" data-title="<?= strtolower($row['title']) ?>" data-author="<?= strtolower($row['author']) ?>" data-category="<?= htmlspecialchars($row['category']) ?>" id="book-<?= $row['id'] ?>">
    <canvas id="thumb-<?= $row['id'] ?>"></canvas>
    <h3><?= htmlspecialchars($row['title']) ?></h3>
    <p>by <?= htmlspecialchars($row['author']) ?></p>
    <button onclick="window.open('<?= $pdf_path ?>','_blank')">View PDF</button>
</div>
<?php endif; endwhile; ?>
</div>

<!-- Books Table -->
<div class="card">
<h2>All Books</h2>
<table>
<tr><th>Title</th><th>Author</th><th>Category</th><th>Quantity</th><th>PDF</th><th>Actions</th></tr>
<?php
$books->data_seek(0);
while($row=$books->fetch_assoc()):
    $pdf_link = file_exists($row['pdf_file']) ? $row['pdf_file'] : '';
?>
<tr>
    <td><?= htmlspecialchars($row['title']); ?></td>
    <td><?= htmlspecialchars($row['author']); ?></td>
    <td><?= htmlspecialchars($row['category']); ?></td>
    <td><?= $row['available_qty']; ?></td>
    <td><?= $pdf_link ? "<a class='view-btn' href='$pdf_link' target='_blank'>View</a>" : "No PDF"; ?></td>
    <td>
        <a href="edit_book.php?id=<?= $row['id'] ?>" class="edit-btn">Edit</a>
        <a href="delete_book.php?id=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- Charts -->
<div class="chart-container">
    <div class="chart-box">
        <canvas id="popularBooksChart"></canvas>
    </div>
    <div class="chart-box">
        <canvas id="activeStudentsChart"></canvas>
    </div>
</div>

<!-- Hidden canvas for PDF -->
<canvas id="pdfChartCanvas" style="display:none;"></canvas>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
// PDF Thumbnails
<?php
$books->data_seek(0);
while($row=$books->fetch_assoc()):
    $pdf_path=$row['pdf_file'];
    if(!empty($pdf_path)&&file_exists($pdf_path)):
?>
pdfjsLib.getDocument("<?= $pdf_path ?>".replace(/ /g,"%20")).promise.then(pdf=>{
    pdf.getPage(1).then(page=>{
        const c=document.getElementById("thumb-<?= $row['id'] ?>");
        if(c){
            const ctx=c.getContext("2d");
            const viewport=page.getViewport({scale:0.25});
            c.width=viewport.width; c.height=viewport.height;
            page.render({canvasContext:ctx, viewport});
        }
    });
});
<?php endif; endwhile; ?>

// Search & Filter
const searchInput=document.getElementById('search-input');
const categoryFilter=document.getElementById('category-filter');
const bookCards=document.querySelectorAll('.book-card');
function filterBooks(){
    const query=searchInput.value.toLowerCase();
    const category=categoryFilter.value;
    bookCards.forEach(card=>{
        const title=card.dataset.title;
        const author=card.dataset.author;
        const cardCategory=card.dataset.category;
        card.style.display=((title.includes(query)||author.includes(query)) && (category==""||cardCategory==category))?"flex":"none";
    });
}
searchInput.addEventListener('input',filterBooks);
categoryFilter.addEventListener('change',filterBooks);

// Highlight active sidebar link
document.querySelectorAll(".sidebar .nav-link").forEach(link=>{
    link.addEventListener("click",function(){
        document.querySelectorAll(".sidebar .nav-link").forEach(l=>l.classList.remove("active"));
        this.classList.add("active");
    });
});

// Charts
const popularBooksChart=new Chart(document.getElementById('popularBooksChart'),{
    type:'pie',
    data:{
        labels:[<?php foreach($popularBooks as $b) echo "'".addslashes($b['title'])."',"; ?>],
        datasets:[{label:'Books Issued Count', data:[<?php foreach($popularBooks as $b) echo $b['issued_count'].','; ?>], backgroundColor:['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#f43f5e','#14b8a6']}]
    },
    options:{responsive:true, maintainAspectRatio:false}
});

const activeStudentsChart=new Chart(document.getElementById('activeStudentsChart'),{
    type:'doughnut',
    data:{
        labels:['Active Students','Inactive Students'],
        datasets:[{data:[<?= $activeStudents ?>,<?= $inactiveStudents ?>], backgroundColor:['#10b981','#ef4444']}]
    },
    options:{responsive:true, maintainAspectRatio:false}
});
</script>
</body>
</html>
