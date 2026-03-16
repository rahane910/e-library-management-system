<?php
session_start();
include "db.php";

// Auth Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
$res = $conn->query("SELECT * FROM student_resources WHERE id=$id LIMIT 1");

if (!$res || $res->num_rows == 0) {
    echo "Resource not found.";
    exit;
}
$row = $res->fetch_assoc();
$pdfFile = htmlspecialchars($row['file_path']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($row['title']); ?> - Preview</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #1f1c2c;
    color: #fff;
}
.header {
    padding: 15px;
    text-align: center;
    background: #243B55;
    color: #00c6ff;
    font-size: 1.5rem;
    font-weight: bold;
}
.viewer {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px;
}
.controls {
    margin: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
    background: rgba(20,20,20,0.8);
    padding: 8px 15px;
    border-radius: 8px;
    position: sticky;
    top: 0;
    z-index: 100;
}
.controls button,
.controls input {
    background: #00c6ff;
    border: none;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: 0.3s;
}
.controls button:hover {
    background: #ff416c;
}
.controls input {
    width: 60px;
    text-align: center;
    color: #000;
    background: #fff;
}
.page-info {
    font-weight: bold;
    font-size: 14px;
    color: #fff;
}
#pdf-container {
    max-height: calc(100vh - 100px);
    overflow-y: auto;
    scroll-behavior: smooth;
    padding: 10px;
}
canvas {
    border: 2px solid #00c6ff;
    border-radius: 10px;
    margin: 15px auto;
    display: block;
    background: #fff;
    transition: transform 0.2s ease-in-out;
}
canvas:hover {
    transform: scale(1.01);
}
</style>
</head>
<body>
<div class="header">📖 Preview - <?php echo htmlspecialchars($row['title']); ?></div>

<div class="viewer">
    <div class="controls">
        <button id="zoom_in">🔍 +</button>
        <button id="zoom_out">🔍 -</button>
        <span class="page-info">Page <span id="current_page">1</span> / <span id="page_count"></span></span>
        <input type="number" id="page_input" placeholder="Go to" min="1">
        <button id="go_page">Go</button>
    </div>
    <div id="pdf-container"></div>
</div>

<script>
const url = "<?php echo $pdfFile; ?>";
let pdfDoc = null,
    scale = 1.2;

const container = document.getElementById('pdf-container');

// Render page (lazy)
const renderPage = (num, canvas) => {
    pdfDoc.getPage(num).then(page => {
        const viewport = page.getViewport({ scale });
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        const ctx = canvas.getContext("2d");
        const renderCtx = { canvasContext: ctx, viewport };
        page.render(renderCtx);
    });
};

// Load document
pdfjsLib.getDocument(url).promise.then(pdfDoc_ => {
    pdfDoc = pdfDoc_;
    document.getElementById("page_count").textContent = pdfDoc.numPages;

    // Create empty canvases
    for (let i = 1; i <= pdfDoc.numPages; i++) {
        const canvas = document.createElement("canvas");
        canvas.dataset.pageNumber = i;
        canvas.height = 200; // placeholder height
        canvas.style.background = "#eee";
        container.appendChild(canvas);
    }

    // Observe canvases when visible
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const canvas = entry.target;
                const pageNum = parseInt(canvas.dataset.pageNumber);
                if (!canvas.dataset.rendered) {
                    renderPage(pageNum, canvas);
                    canvas.dataset.rendered = true;
                }
            }
        });
    }, { root: container, threshold: 0.1 });

    document.querySelectorAll("#pdf-container canvas").forEach(c => observer.observe(c));
});

// Zoom controls
function reRenderAll() {
    document.querySelectorAll("#pdf-container canvas").forEach(c => {
        c.removeAttribute("data-rendered");
        c.getContext("2d").clearRect(0, 0, c.width, c.height);
    });
    document.querySelectorAll("#pdf-container canvas").forEach(c => {
        const pageNum = parseInt(c.dataset.pageNumber);
        renderPage(pageNum, c);
        c.dataset.rendered = true;
    });
}

document.getElementById("zoom_in").addEventListener("click", () => {
    scale += 0.2;
    reRenderAll();
});

document.getElementById("zoom_out").addEventListener("click", () => {
    if (scale <= 0.6) return;
    scale -= 0.2;
    reRenderAll();
});

// Go to page
document.getElementById("go_page").addEventListener("click", () => {
    const input = document.getElementById("page_input").value;
    const pageNum = parseInt(input);
    if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= pdfDoc.numPages) {
        const targetCanvas = container.querySelector(`canvas[data-page-number='${pageNum}']`);
        if (targetCanvas) {
            targetCanvas.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }
});

// Track current page
container.addEventListener("scroll", () => {
    const canvases = container.querySelectorAll("canvas");
    let currentPage = 1;
    let containerRect = container.getBoundingClientRect();

    canvases.forEach(canvas => {
        const rect = canvas.getBoundingClientRect();
        if (rect.top >= containerRect.top && rect.top < containerRect.top + container.clientHeight/2) {
            currentPage = parseInt(canvas.dataset.pageNumber);
        }
    });

    document.getElementById("current_page").textContent = currentPage;
});
</script>
</body>
</html>
