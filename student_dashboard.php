<?php
session_start();
include "db.php";

// ----------------------
// Auth guard
// ----------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$uid  = (int) $_SESSION['user_id'];
$name = $_SESSION['name'] ?? "Student";

// ----------------------
// Profile picture (robust path + fallback)
// ----------------------
$profilePic = 'uploads/default.png';
if (!empty($_SESSION['profile_pic'])) {
    $pp = $_SESSION['profile_pic'];
    // If not already under uploads/, store only basename to avoid path traversal
    if (substr($pp, 0, 8) !== 'uploads/') {
        $pp = 'uploads/' . basename($pp);
    }
    if (file_exists(__DIR__ . '/' . $pp)) {
        $profilePic = $pp;
    }
}

// ----------------------
// Notifications
// ----------------------
$notes = $conn->query("SELECT * FROM notifications WHERE user_id IS NULL OR user_id={$uid} ORDER BY created_at DESC LIMIT 5");
if (!$notes) { $notes = new class { public $num_rows = 0; public function fetch_assoc(){ return null; } }; }

// ----------------------
// Status check
// ----------------------
$stmt = $conn->prepare("SELECT status FROM users WHERE id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result ? $result->fetch_assoc() : null;
if ($user && ($user['status'] ?? '') === 'blocked') {
    session_destroy();
    die("<h2 style='color:red; text-align:center;'>Your account has been blocked. Please contact admin.</h2>");
}

// ----------------------
// Books & categories
// Expecting table `books` with columns: id, title, author, category, pdf_file
// ----------------------
$books = $conn->query("SELECT * FROM books ORDER BY title ASC");
if (!$books) { die("Error loading books: " . $conn->error); }
$categories = $conn->query("SELECT DISTINCT category FROM books ORDER BY category ASC");
if (!$categories) { die("Error loading categories: " . $conn->error); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Student Dashboard</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
<style>
*{box-sizing:border-box;margin:0;padding:0}

/* Sidebar */
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 230px;
  height: 100%;
  background: rgba(20, 20, 20, 0.95);
  color: #fff;
  display: flex;
  flex-direction: column;
  padding: 20px 15px;
  box-shadow: 4px 0 15px rgba(0,0,0,0.4);
  z-index: 1000;
}

.sidebar h2 {
  font-size: 1.4rem;
  margin-bottom: 25px;
  color: #00c6ff;
}

.sidebar a {
  color: #fff;
  text-decoration: none;
  padding: 12px 15px;
  border-radius: 10px;
  margin: 4px 0;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: 0.3s;
}

.sidebar a:hover {
  background: linear-gradient(135deg,#00c6ff,#0072ff);
}

.main-content {
  margin-left: 250px; /* space for sidebar */
  padding: 20px;
}


body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#1f1c2c,#928dab);color:#fff;min-height:100vh}
.container{width:95%;max-width:1500px;margin:20px auto 80px}
/* Header */
header{display:flex;justify-content:space-between;align-items:center;padding:20px 30px;background:rgba(255,255,255,.1);backdrop-filter:blur(25px);border-radius:25px;margin-bottom:30px;box-shadow:0 10px 35px rgba(0,0,0,.4)}
header h2{margin:0;font-size:1.6rem;color:#fff}
header button{padding:10px 16px;border:none;border-radius:10px;cursor:pointer;background:linear-gradient(135deg,#00c6ff,#0072ff);color:#fff;font-weight:600;transition:.3s}
header button:hover{transform:scale(1.05);box-shadow:0 0 20px #00c6ff}
header img{width:65px;height:65px;border-radius:50%;object-fit:cover;border:3px solid #00c6ff}
/* Search */
.search-filter{display:flex;gap:15px;margin-bottom:25px}
.search-filter input,.search-filter select{flex:1;padding:14px;border-radius:12px;border:none;background:rgba(255,255,255,.15);color:black;font-size:15px;font-weight:900;outline:none}
/* Carousel */
/* Book Grid */
.book-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 25px;
  margin-top: 15px;
}

.carousel-container::-webkit-scrollbar{display:none}
.book-card{flex:0 0 220px;background:rgba(255,255,255,.15);border-radius:20px;padding:15px;text-align:center;box-shadow:0 8px 25px rgba(0,0,0,.3);transition:transform .3s,box-shadow .3s}
.book-card {
  background: rgba(255,255,255,.15);
  border-radius: 20px;
  padding: 15px;
  text-align: center;
  box-shadow: 0 8px 25px rgba(0,0,0,.3);
  transition: transform .3s, box-shadow .3s;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 380px;   /* ✅ fix uniform height */
}

.book-thumb {
  width: 100%;
  height: 220px;       /* ✅ same image height */
  object-fit: cover;
  border-radius: 12px;
  margin-bottom: 12px;
}

.book-card h3 {
  font-size: 16px;
  color: #fff;
  margin: 10px 0;
  flex-grow: 1;        /* ✅ title area grows to fill space */
  display: -webkit-box;
  -webkit-line-clamp: 2; /* ✅ show max 2 lines */
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.book-card p {
  font-size: 13px;
  color: #ddd;
  margin-bottom: 8px;
}

.book-card button {
  padding: 8px 14px;
  border: none;
  border-radius: 8px;
  background: linear-gradient(135deg,#ff416c,#ff4b2b);
  color: #fff;
  cursor: pointer;
  transition: .3s;
  margin-top: auto;     /* ✅ push button to bottom */
}

.book-card:hover{transform:translateY(-10px);box-shadow:0 15px 40px rgba(0,198,255,.6)}
.book-thumb{width:100%;height:280px;border-radius:15px;margin-bottom:12px;background:#000;object-fit:cover}
.book-card h3{font-size:16px;color:#fff}
.book-card p{font-size:13px;color:#ddd}
.book-card button{margin-top:8px;padding:8px 14px;border:none;border-radius:8px;background:linear-gradient(135deg,#ff416c,#ff4b2b);color:#fff;cursor:pointer;transition:.3s}
.book-card button:hover{background:linear-gradient(135deg,#ff4b2b,#ff416c)}
.active{border:2px solid #00c6ff}
/* Recently Read */
#recent-books{margin-top:20px}
.recent-card{flex:0 0 200px;background:rgba(0,0,0,.5);border-radius:18px;padding:12px;text-align:center}
.recent-card img{width:100%;height:260px;border-radius:12px;object-fit:cover}
/* PDF Reader */
.pdf-container{margin-top:40px;padding:25px;border-radius:25px;background:rgba(255,255,255,.1);text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.4)}
.pdf-controls{margin-top:20px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.pdf-controls button{padding:8px 14px;border:none;border-radius:10px;background:linear-gradient(135deg,#00c6ff,#0072ff);color:#fff;cursor:pointer}
canvas{max-width:100%;border-radius:15px}
.mode-switch {
    display: flex;
    align-items: center;
    justify-content: flex-end; /* place to the right */
    gap: 10px;
    margin: 15px 0;
    padding: 8px 12px;
    background: rgba(0, 198, 255, 0.1); /* soft highlight */
    border-radius: 12px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(0, 198, 255, 0.4);
    width: fit-content;
    font-family: 'Poppins', sans-serif;
}

.mode-switch label {
    font-size: 14px;
    color: #00c6ff;
    font-weight: 500;
}

.mode-switch select {
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
    font-size: 14px;
    cursor: pointer;
    transition: 0.3s all;
}

.mode-switch select:hover,
.mode-switch select:focus {
    border-color: #00c6ff;
    box-shadow: 0 0 8px rgba(0, 198, 255, 0.5);
    outline: none;
}

/* Scroll view container */
#pdf-scroll-container{max-height:85vh;overflow-y:auto;background:#fff;border-radius:12px;padding:10px}
#pdf-scroll-container canvas{display:block;margin:0 auto 16px;border-radius:8px}
/* Notifications */
.notif-wrapper{position:relative}
#notifBtn{background:none;border:none;font-size:26px;cursor:pointer;position:relative;color:#fff}
#notif-count{background:red;color:#fff;font-size:12px;padding:2px 6px;border-radius:50%;position:absolute;top:-5px;right:-10px}
#notifDropdown{display:none;position:absolute;right:0;top:35px;background:rgba(20,20,20,.95);padding:12px;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,.6);width:270px;z-index:999}
#notifDropdown p{margin:8px 0;font-size:14px;color:#fff}
/* Dark mode */
.dark-mode{background:#0d1117;color:#e6edf3}
.dark-mode header{background:rgba(20,20,20,.6)}
.dark-mode .book-card{background:rgba(20,20,20,.6)}
.dark-mode .pdf-container{background:rgba(20,20,20,.6)}
.mode-switch{margin-top:10px}


</style>
</head>
<body>
    <!-- Sidebar Menu -->
<div class="sidebar">
  <h2>📖 Menu</h2>
  <a href="#dashboard"><i class="fas fa-user"></i>  My Profile</a>
  <a href="home.php"><i class="fas fa-home" ></i>Home</a>
  <a href="#books"><i class="fas fa-book"></i>Books</a>
  <a href="#recent"><i class="fas fa-history"></i> Recently Read</a>
  <a href="student_resources.php"><i class="fas fa-user-graduate fa-1x"></i> Student Resources</a>
  <a href="#settings"><i class="fas fa-cog"></i> Settings</a>
  <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">

<div class="container">
  <header>
      <div style="display:flex;align-items:center;gap:15px;">
          <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile">
          <h2>Welcome, <?= htmlspecialchars($name)?>!</h2>
      </div>
      <div style="display:flex;align-items:center;gap:15px;">
          <div class="notif-wrapper">
              <button id="notifBtn">🔔<span id="notif-count"><?= (int)($notes->num_rows ?? 0) ?></span></button>
              <div id="notifDropdown">
                  <?php while($row = $notes->fetch_assoc()): ?>
                      <p><?= htmlspecialchars($row['message']) ?><br><small><?= htmlspecialchars($row['created_at']) ?></small></p>
                  <?php endwhile; ?>
              </div>
          </div>
          <button onclick="toggleDarkMode()">🌙</button>
          <!-- Keep your existing logout behavior; replace action to logout.php if you have it -->
          <form method="post" action="login.php"><button type="submit">Logout</button></form>
      </div>
  </header>

  <!-- Search & Filter -->
  <div class="search-filter">
      <input type="text" id="search-input" placeholder="🔍 Search by title or author...">
      <select id="category-filter">
          <option value="">All Categories</option>
          <?php while($cat=$categories->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($cat['category']) ?>"><?= htmlspecialchars($cat['category']) ?></option>
          <?php endwhile; ?>
      </select>
  </div>

  <!-- Book Carousel -->
<!-- Book Grid -->
<h2>📚 Available Books</h2>
<div class="book-grid" id="book-grid">

  <?php
  $books->data_seek(0);
  while($row=$books->fetch_assoc()):
      $pdf_path = $row['pdf_file'];
      if (!empty($pdf_path) && file_exists(__DIR__ . '/' . $pdf_path)):
          $bookId = (int)$row['id'];
  ?>
      <div class="book-card" data-id="<?= $bookId ?>" data-title="<?= htmlspecialchars(strtolower($row['title'])) ?>" data-author="<?= htmlspecialchars(strtolower($row['author'])) ?>" data-category="<?= htmlspecialchars($row['category']) ?>" id="book-<?= $bookId ?>">
          <!-- Thumb starts as placeholder; JS will replace with first page preview using PDF.js -->
          <img class="book-thumb" id="thumb-<?= $bookId ?>" src="uploads/default_thumb.jpg" alt="Book Cover" onerror="this.src='uploads/default_thumb.jpg'">
          <h3><?= htmlspecialchars($row['title']) ?></h3>
          <p><?= htmlspecialchars($row['author']) ?></p>
          <button onclick="openPDF('<?= htmlspecialchars($pdf_path) ?>', <?= $bookId ?>)">Read</button>
          <span class="sr-only" data-pdf="<?= htmlspecialchars($pdf_path) ?>"></span>
      </div>
  <?php endif; endwhile; ?>
  </div>

  <!-- Recently Read -->
<h2>📘 Recently Read</h2>
<div id="recent-books" class="book-grid"></div>



  <!-- PDF Reader -->
  <div class="pdf-container">
      <h2 id="pdf-title">📖 Read Book</h2>

      <!-- View mode switch -->
      <div class="mode-switch">
    <label for="view-mode">View mode:</label>
    <select id="view-mode">
        <option value="single" selected>Page-by-page</option>
        <option value="scroll">Scroll</option>
    </select>
</div>


      <!-- Single-page view -->
      <div id="single-view">
          <canvas id="pdf-render"></canvas>
          <div class="pdf-controls">
              <button onclick="prevPage()">⬅ Prev</button>
              <button onclick="nextPage()">Next ➡</button>
              <button onclick="zoomOut()">➖</button>
              <button onclick="zoomIn()">➕</button>
              <span>Page <span id="page-num"></span> / <span id="page-count"></span></span>
          </div>
      </div>

      <!-- Scroll view -->
      <div id="scroll-view" style="display:none;">
          <div id="pdf-scroll-container"></div>
          <div class="pdf-controls">
              <button onclick="zoomOut()">➖</button>
              <button onclick="zoomIn()">➕</button>
              <span id="scroll-hint">Scroll to read all pages</span>
          </div>
      </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
// Ensure worker is set for this version
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

let pdfDoc = null,
    pageNum = 1,
    pageIsRendering = false,
    pageNumPending = null,
    scale = 1.2,
    canvas = document.getElementById('pdf-render'),
    ctx = canvas.getContext('2d');
let viewMode = 'single'; // 'single' | 'scroll'

// ============ Notifications dropdown ============
document.getElementById('notifBtn').addEventListener('click', () => {
  const drop = document.getElementById('notifDropdown');
  drop.style.display = (drop.style.display === 'block' ? 'none' : 'block');
});
document.addEventListener('click', (e) => {
  const wrap = document.querySelector('.notif-wrapper');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('notifDropdown').style.display = 'none';
  }
});

// ============ Dark Mode ============
function toggleDarkMode(){
  document.body.classList.toggle('dark-mode');
  localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
}
if (localStorage.getItem('darkMode') === 'enabled') document.body.classList.add('dark-mode');

// ============ Search & Filter ============
const searchInput = document.getElementById('search-input');
const categoryFilter = document.getElementById('category-filter');
function filterBooks(){
  const query = (searchInput.value || '').toLowerCase();
  const category = categoryFilter.value || '';
  document.querySelectorAll('.book-card').forEach(card => {
    const title = card.dataset.title || '';
    const author = card.dataset.author || '';
    const cardCategory = card.dataset.category || '';
    const match = ((title.includes(query) || author.includes(query)) && (category === '' || cardCategory === category));
    card.style.display = match ? 'flex' : 'none';
  });
}
searchInput.addEventListener('input', filterBooks);
categoryFilter.addEventListener('change', filterBooks);

// ============ PDF Rendering (single page) ============
function renderPage(num){
  if (!pdfDoc) return;
  pageIsRendering = true;
  pdfDoc.getPage(num).then(page => {
    const viewport = page.getViewport({ scale });
    canvas.height = viewport.height;
    canvas.width  = viewport.width;
    const renderCtx = { canvasContext: ctx, viewport };
    return page.render(renderCtx).promise;
  }).then(() => {
    pageIsRendering = false;
    document.getElementById('page-num').textContent = num;
    if (pageNumPending !== null){
      renderPage(pageNumPending);
      pageNumPending = null;
    }
  });
}
function queueRenderPage(num){
  if (pageIsRendering) pageNumPending = num; else renderPage(num);
}
function prevPage(){ if (pageNum <= 1) return; pageNum--; queueRenderPage(pageNum); }
function nextPage(){ if (!pdfDoc || pageNum >= pdfDoc.numPages) return; pageNum++; queueRenderPage(pageNum); }

// ============ PDF Rendering (scroll mode) ============
function renderAllPages(){
  if (!pdfDoc) return;
  const container = document.getElementById('pdf-scroll-container');
  container.innerHTML = '';
  // Render pages (simple async loop)
  for (let p = 1; p <= pdfDoc.numPages; p++){
    pdfDoc.getPage(p).then(page => {
      const viewport = page.getViewport({ scale });
      const c = document.createElement('canvas');
      const cctx = c.getContext('2d');
      c.height = viewport.height;
      c.width  = viewport.width;
      container.appendChild(c);
      page.render({ canvasContext: cctx, viewport });
    });
  }
}

// ============ Open PDF (keeps both modes) ============
function openPDF(url, bookId){
  // highlight active card
  document.querySelectorAll('.book-card').forEach(c => c.classList.remove('active'));
  const active = document.getElementById('book-' + bookId);
  if (active) active.classList.add('active');

  // Encode spaces
  url = url.replace(/ /g, '%20');

  pdfjsLib.getDocument(url).promise.then(pdf => {
    pdfDoc = pdf; pageNum = 1;
    document.getElementById('page-count').textContent = pdfDoc.numPages;
    const titleEl = active ? active.querySelector('h3') : null;
    document.getElementById('pdf-title').textContent = '📖 ' + (titleEl ? titleEl.textContent : 'Read Book');

    if (viewMode === 'single'){
      document.getElementById('single-view').style.display = 'block';
      document.getElementById('scroll-view').style.display = 'none';
      renderPage(pageNum);
    } else {
      document.getElementById('single-view').style.display = 'none';
      document.getElementById('scroll-view').style.display = 'block';
      renderAllPages();
    }

    // Save to recent (with thumb if available in cache)
    saveRecentBook(bookId, titleEl ? titleEl.textContent : 'Book', url);
  });
}

// ============ Zoom (works for both modes) ============
function zoomIn(){ scale += 0.1; if (viewMode === 'single') renderPage(pageNum); else renderAllPages(); }
function zoomOut(){ if (scale > 0.2){ scale -= 0.1; if (viewMode === 'single') renderPage(pageNum); else renderAllPages(); } }

// ============ View mode switch ============
document.getElementById('view-mode').addEventListener('change', (e) => {
  viewMode = e.target.value;
  if (!pdfDoc) return;
  if (viewMode === 'single'){
    document.getElementById('single-view').style.display = 'block';
    document.getElementById('scroll-view').style.display = 'none';
    renderPage(pageNum);
  } else {
    document.getElementById('single-view').style.display = 'none';
    document.getElementById('scroll-view').style.display = 'block';
    renderAllPages();
  }
});

// ============ Recently Read (uses localStorage) ============
function renderRecentBooks(){
    const cont = document.getElementById("recent-books");
    cont.innerHTML = "";
    let recents = JSON.parse(localStorage.getItem("recentBooks") || "[]");

    recents.forEach(b => {
        let div = document.createElement("div");
        div.className = "book-card"; // ✅ same style as available books
        div.innerHTML = `
            <img class="book-thumb" src="${b.thumb}" alt="Book Cover">
            <h3>${b.title}</h3>
            <p>${b.author || "Unknown Author"}</p>
            <button onclick="openPDF('${b.pdf}',${b.id})">Continue</button>
        `;
        cont.appendChild(div);
    });
}

renderRecentBooks();

// ============ Generate thumbnails from first PDF page (client-side via PDF.js) ============
// This avoids Imagick/Ghostscript server dependency.
function generateThumbFromPDF(pdfUrl, bookId){
  // Use cache if present
  const cached = localStorage.getItem('thumb_'+bookId);
  if (cached) {
    const img = document.getElementById('thumb-'+bookId);
    if (img) img.src = cached;
    return;
  }
  // Load only first page with a small scale
  pdfjsLib.getDocument(pdfUrl.replace(/ /g, '%20')).promise.then(pdf => pdf.getPage(1)).then(page => {
    const viewport = page.getViewport({ scale: 0.5 }); // small preview
    const c = document.createElement('canvas');
    const cctx = c.getContext('2d');
    c.width = viewport.width; c.height = viewport.height;
    return page.render({ canvasContext: cctx, viewport }).promise.then(() => c.toDataURL('image/jpeg', 0.8));
  }).then(dataUrl => {
    try { localStorage.setItem('thumb_'+bookId, dataUrl); } catch(e) { /* storage full, ignore */ }
    const img = document.getElementById('thumb-'+bookId);
    if (img) img.src = dataUrl;
  }).catch(() => {
    const img = document.getElementById('thumb-'+bookId);
    if (img) img.src = 'uploads/default_thumb.jpg';
  });
}

// Kick off thumbnail generation for visible books
(function initThumbs(){
  document.querySelectorAll('.book-card').forEach(card => {
    const id = card.dataset.id;
    const pdfSpan = card.querySelector('.sr-only');
    const pdf = pdfSpan ? pdfSpan.getAttribute('data-pdf') : '';
    if (pdf) generateThumbFromPDF(pdf, id);
  });
})();
</script>
</body>
</html>
