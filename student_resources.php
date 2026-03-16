<?php
session_start();
include "db.php";

// Auth Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? "Student";
$name = "Hello, " . $name;
$profile_pic = "uploads/default.png";

// Get profile picture
$result = $conn->query("SELECT profile_pic FROM users WHERE id='$user_id' LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    if (!empty($row['profile_pic'])) {
        $profile_pic = $row['profile_pic'];
    }
}

// --- Search & Filters ---
$search = trim($_GET['search'] ?? "");
$filter_course = $_GET['course'] ?? "";
$filter_standard = $_GET['standard'] ?? "";

// Function to highlight keywords
function highlight($text, $keyword) {
    if (!$keyword) return htmlspecialchars($text);
    return preg_replace("/(" . preg_quote($keyword, '/') . ")/i", "<mark>$1</mark>", htmlspecialchars($text));
}

// Get distinct courses & standards
$courses_res = $conn->query("SELECT DISTINCT course FROM student_resources ORDER BY course ASC");
$standards_res = $conn->query("SELECT DISTINCT standard FROM student_resources ORDER BY standard ASC");

// Base query
$sql = "SELECT * FROM student_resources WHERE 1=1";
$params = [];
$types = "";

// Apply filters
if ($search !== "") {
    $sql .= " AND (title LIKE ? OR description LIKE ? OR course LIKE ? OR standard LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= "ssss";
}
if ($filter_course !== "") {
    $sql .= " AND course = ?";
    $params[] = $filter_course;
    $types .= "s";
}
if ($filter_standard !== "") {
    $sql .= " AND standard = ?";
    $params[] = $filter_standard;
    $types .= "s";
}
$sql .= " ORDER BY course ASC, created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

// Group resources by course
$resources_by_course = [];
while ($row = $res->fetch_assoc()) {
    $resources_by_course[$row['course']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Resources</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:linear-gradient(135deg,#1f1c2c,#928dab);color:#fff;min-height:100vh;}

/* Navbar */
.navbar {display:flex;justify-content:space-between;align-items:center;padding:14px 40px;
    background:linear-gradient(90deg,#141E30,#243B55);box-shadow:0 5px 15px rgba(0,0,0,.6);
    position:sticky;top:0;z-index:1000;}
.nav-left {display:flex;align-items:center;gap:15px;}
.logo {font-size:1.3rem;font-weight:700;color:#00c6ff;display:flex;align-items:center;gap:8px;}
.nav-left img {width:45px;height:45px;border-radius:50%;border:2px solid #00c6ff;object-fit:cover;}
.nav-right {display:flex;align-items:center;gap:25px;}
.nav-right ul {display:flex;list-style:none;gap:20px;}
.nav-right ul li a {text-decoration:none;color:#fff;font-weight:500;position:relative;padding:6px 10px;transition:0.3s;}
.nav-right ul li a::after {content:'';position:absolute;width:0;height:2px;bottom:0;left:0;background:#00c6ff;transition:width 0.3s;}
.nav-right ul li a:hover::after {width:100%;}
.nav-right ul li a:hover {color:#00c6ff;}

/* Search + Filters */
.filters {padding:20px;text-align:center;}
.filters form {display:flex;flex-wrap:wrap;justify-content:center;gap:15px;}
.filters input, .filters select {padding:8px 12px;border-radius:6px;border:none;outline:none;}
.filters button {background:#00c6ff;color:#fff;border:none;padding:8px 15px;border-radius:6px;cursor:pointer;transition:0.3s;}
.filters button:hover {background:#ff416c;}

/* Dashboard-style Resource Grid */
.container {padding:20px;max-width:1300px;margin:auto;}
.course-title {margin:20px 0 10px;font-size:20px;color:#00c6ff;border-bottom:2px solid #00c6ff;padding-bottom:5px;}
.resources {display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:25px;}
.resource-card {
    background:rgba(20,20,20,0.9);border-radius:15px;padding:20px;
    display:flex;flex-direction:column;align-items:center;justify-content:space-between;
    transition:0.3s;box-shadow:0 5px 15px rgba(0,0,0,.5);text-align:center;height:380px;
}
.resource-card:hover {transform:translateY(-6px) scale(1.02);box-shadow:0 12px 25px rgba(0,198,255,.6);}
.resource-thumb {width:100%;height:200px;margin-bottom:15px;background:rgba(255,255,255,0.1);border-radius:10px;overflow:hidden;display:flex;align-items:center;justify-content:center;}
.resource-thumb canvas {width:100%;height:100%;object-fit:contain;}
.resource-body h3 {color:#ff416c;margin-bottom:6px;font-size:16px;min-height:20px;}
.resource-body p {font-size:13px;color:#ddd;margin-bottom:12px;min-height:40px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
.resource-body a {display:inline-block;padding:8px 15px;border-radius:8px;background:linear-gradient(135deg,#00c6ff,#0072ff);color:#fff;font-weight:600;text-decoration:none;transition:0.3s;}
.resource-body a:hover {background:linear-gradient(135deg,#ff416c,#ff4b2b);transform:scale(1.05);}
mark {background:#ff416c;color:#fff;padding:0 2px;border-radius:3px;}
</style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar">
    <div class="nav-left">
        <h2 class="logo"><i class="fas fa-book-open"></i> Student Resources</h2>
        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile">
        <span><?php echo htmlspecialchars($name); ?></span>
    </div>
    <div class="nav-right">
        <ul>
            <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="student_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="book_requests.php"><i class="fas fa-book"></i> Request a Book</a></li>
            <li><a href="helpdesk.php"><i class="fas fa-headset"></i> Help Desk</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<!-- Filters -->
<div class="filters">
    <form method="GET">
        <input type="text" name="search" placeholder="Search by title, description, course, standard..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="course">
            <option value="">All Courses</option>
            <?php if ($courses_res) while($c = $courses_res->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($c['course']); ?>" <?php if ($filter_course==$c['course']) echo "selected"; ?>>
                    <?php echo htmlspecialchars($c['course']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <select name="standard">
            <option value="">All Standards</option>
            <?php if ($standards_res) while($s = $standards_res->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($s['standard']); ?>" <?php if ($filter_standard==$s['standard']) echo "selected"; ?>>
                    <?php echo htmlspecialchars($s['standard']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit"><i class="fas fa-search"></i> Filter</button>
    </form>
</div>

<!-- Resources -->
<div class="container">
    <?php if (!empty($resources_by_course)): ?>
        <?php foreach ($resources_by_course as $course => $resources): ?>
            <h2 class="course-title">📘 <?php echo htmlspecialchars($course); ?></h2>
            <div class="resources">
                <?php foreach ($resources as $row): ?>
                    <div class="resource-card">
                        <div class="resource-thumb">
                            <canvas id="thumb-<?php echo $row['id']; ?>" data-url="<?php echo $row['file_path']; ?>"></canvas>
                        </div>
                        <div class="resource-body">
                            <h3><?php echo highlight($row['title'], $search); ?></h3>
                            <p><?php echo highlight($row['description'], $search); ?></p>
                            <a href="resource_preview.php?id=<?php echo $row['id']; ?>"><i class="fas fa-eye"></i> Preview</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align:center;">No resources found.</p>
    <?php endif; ?>
</div>

<script>
// Render first page of PDFs as thumbnails
document.querySelectorAll("canvas[data-url]").forEach(canvas => {
    const url = canvas.dataset.url;
    const ctx = canvas.getContext("2d");
    const THUMB_WIDTH = canvas.parentElement.clientWidth;
    const THUMB_HEIGHT = canvas.parentElement.clientHeight;

    pdfjsLib.getDocument(url).promise.then(pdf => pdf.getPage(1)).then(page => {
        const viewport = page.getViewport({ scale: 1 });
        const scale = Math.min(THUMB_WIDTH / viewport.width, THUMB_HEIGHT / viewport.height);
        const scaledViewport = page.getViewport({ scale });

        canvas.width = scaledViewport.width;
        canvas.height = scaledViewport.height;

        page.render({ canvasContext: ctx, viewport: scaledViewport });
    }).catch(err => {
        console.error("PDF load error:", err);
        ctx.fillStyle = "#444";
        ctx.fillRect(0, 0, THUMB_WIDTH, THUMB_HEIGHT);
    });
});
</script>
</body>
</html>
