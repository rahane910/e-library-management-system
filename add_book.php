<?php
session_start();
include "db.php";

// ------------------------
// Admin guard
// ------------------------
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

// ------------------------
// OPTIONAL: create tables if they don't exist
// (You can remove these if you manage schema separately)
// ------------------------
$createBooksSQL = "CREATE TABLE IF NOT EXISTS `books` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `author` VARCHAR(255),
  `category` VARCHAR(100),
  `available_qty` INT DEFAULT 1,
  `pdf_file` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($createBooksSQL);

$createResourcesSQL = "CREATE TABLE IF NOT EXISTS `student_resources` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `course` VARCHAR(150),
  `standard` VARCHAR(50),
  `file_path` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($createResourcesSQL);

// ------------------------
// Helper / messages
// ------------------------
$success = $error = "";

/**
 * Safe helper: create directory if not exists and return boolean
 */
function ensure_dir($dir) {
    if (!is_dir($dir)) {
        return mkdir($dir, 0777, true);
    }
    return true;
}

// ------------------------
// Handle Add Book POST
// ------------------------
if (isset($_POST['add_book'])) {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $qty = intval($_POST['available_qty'] ?? 1);

    if ($title === '') {
        $error = "Book title is required.";
    } elseif (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a PDF file to upload.";
    } else {
        // validate PDF
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['pdf_file']['tmp_name']);
        if ($mime !== 'application/pdf') {
            $error = "Uploaded file must be a PDF. Detected: {$mime}";
        } else {
            // create uploads dir
            $target_dir = __DIR__ . "/uploads/books/";
            if (!ensure_dir($target_dir)) {
                $error = "Failed to create uploads directory.";
            } else {
                $ext = '.pdf';
                $safeName = time() . '_' . bin2hex(random_bytes(6)) . $ext;
                $target_file = $target_dir . $safeName;
                if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_file)) {
                    $error = "Failed to move uploaded file.";
                } else {
                    // store relative path
                    $rel_path = 'uploads/books/' . $safeName;

                    // prepare statement and check
$stmt = $conn->prepare("INSERT INTO student_resources (title, description, course, standard, file_path) VALUES (?,?,?,?,?)");
                    if ($stmt === false) {
                        $error = "DB prepare failed: " . $conn->error;
                    } else {
                        // types: s s s i s
                        $stmt->bind_param("sssds", $title, $author, $category, $qty, $rel_path);
                        // Note: previous code used 'sssis' — using explicit types below
                        // but PHP's bind_param expects types string like "sssis" where i=int, s=string, d=double
                        // We'll use "sss i s" => "sss i s" combined into "sss is" not valid; so use correct: "sss i s" => "sss i s"
                        // To avoid confusion, call bind_param using "sss i s" collapsed to "sss i s" as "sss i s" is invalid.
                        // Instead do: correct order types => title(s), author(s), category(s), qty(i), rel_path(s) => "sss i s" => "sss i s" -> final string "sss i s" -> remove spaces: "sss i s" => "sssis"
                        // However earlier we accidentally used "sssds" to satisfy bind_param; adjust below to proper use.
                        // We'll close and rebind correctly:
                        $stmt->close();

                        $stmt = $conn->prepare("INSERT INTO books (title, author, category, available_qty, pdf_file) VALUES (?,?,?,?,?)");
                        if ($stmt === false) {
                            $error = "DB prepare failed: " . $conn->error;
                        } else {
                            $stmt->bind_param("sssis", $title, $author, $category, $qty, $rel_path);
                            if ($stmt->execute()) {
                                $success = "Book added successfully.";
                            } else {
                                $error = "DB execute failed: " . $stmt->error;
                                // optionally remove uploaded file on failure
                                @unlink($target_file);
                            }
                            $stmt->close();
                        }
                    }
                }
            }
        }
    }
}

// ------------------------
// Handle Add Student Resource POST
// ------------------------
if (isset($_POST['add_resource'])) {
    $r_title = trim($_POST['res_title'] ?? '');
    $r_desc = trim($_POST['res_desc'] ?? '');
    $r_course = trim($_POST['course'] ?? '');
    $r_standard = trim($_POST['standard'] ?? '');

    if ($r_title === '') {
        $error = "Resource title required.";
    } elseif (!isset($_FILES['res_file']) || $_FILES['res_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a file to upload for the resource.";
    } else {
        $allowed = ['application/pdf','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['res_file']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            $error = "Unsupported resource type: {$mime}. Allowed: PDF, PPT, PPTX, DOC, DOCX.";
        } else {
            $target_dir = __DIR__ . "/uploads/resources/";
            if (!ensure_dir($target_dir)) {
                $error = "Failed to create resource uploads directory.";
            } else {
                $orig = $_FILES['res_file']['name'];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                $safeName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $target_file = $target_dir . $safeName;
                if (!move_uploaded_file($_FILES['res_file']['tmp_name'], $target_file)) {
                    $error = "Failed to move resource file.";
                } else {
                    $rel_path = 'uploads/resources/' . $safeName;
                    $stmt = $conn->prepare("INSERT INTO student_resources (title, description, course, standard, file_path, created_at) VALUES (?,?,?,?,?,NOW())");
                    if ($stmt === false) {
                        $error = "DB prepare failed: " . $conn->error;
                    } else {
                        $stmt->bind_param("sssss", $r_title, $r_desc, $r_course, $r_standard, $rel_path);
                        if ($stmt->execute()) {
                            $success = $success ? $success . " | Resource uploaded." : "Resource uploaded successfully.";
                        } else {
                            $error = "DB execute failed: " . $stmt->error;
                            @unlink($target_file);
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Add Book & Resource — Admin</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
  *{box-sizing:border-box;font-family:'Poppins',sans-serif}
  body{margin:0;background:linear-gradient(135deg,#1f1c2c,#928dab);color:#fff;padding:30px}
  .wrap{max-width:1100px;margin:0 auto}
  .topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
  .topbar h1{color:#00c6ff;font-size:20px;margin:0}
  .messages{margin-bottom:18px}
  .msg{padding:12px 16px;border-radius:10px;margin-bottom:10px}
  .success{background:linear-gradient(135deg,#0f9d58,#4caf50);color:#042b11}
  .error{background:linear-gradient(135deg,#ff8a80,#ff5252);color:#2b0000}

  .grid{display:grid;grid-template-columns:1fr 1fr;gap:22px}
  @media(max-width:860px){ .grid{grid-template-columns:1fr} }

  .card{background:rgba(20,20,20,0.85);padding:22px;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,0.5);transition:0.25s}
  .card:hover{transform:translateY(-6px);box-shadow:0 18px 40px rgba(0,198,255,0.15)}
  .card h2{margin:0 0 12px;color:#00c6ff;font-size:18px}
  label{display:block;font-weight:600;margin-bottom:6px;color:#ddd}
  input[type="text"],input[type="number"],select,textarea,input[type="file"]{
    width:100%;padding:10px 12px;border-radius:10px;border:none;margin-bottom:12px;background:#f4f6f9;color:#222;outline:none
  }
  textarea{min-height:120px;resize:vertical}
  .btn{display:inline-block;padding:12px 16px;border-radius:10px;border:none;background:linear-gradient(135deg,#00c6ff,#0072ff);color:#fff;font-weight:700;cursor:pointer}
  .btn:hover{transform:scale(1.03);box-shadow:0 8px 20px rgba(0,198,255,0.2)}
  .back{display:inline-block;margin-top:14px;color:#00c6ff;text-decoration:none}
  .hint{font-size:13px;color:#bbb;margin-bottom:8px}
  .small{font-size:13px;color:#bbb}
</style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <h1><i class="fas fa-book"></i> Admin — Add Book & Resources</h1>
      <div class="small">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></strong></div>
    </div>

    <div class="messages">
      <?php if ($success): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
    </div>

    <div class="grid">
      <!-- Add Book -->
      <div class="card">
        <h2><i class="fas fa-file-pdf"></i> Add New Book (PDF)</h2>
        <form method="post" enctype="multipart/form-data">
          <label>Title</label>
          <input type="text" name="title" required>

          <label>Author</label>
          <input type="text" name="author">

          <label>Category</label>
          <input type="text" name="category">

          <label>Available Quantity</label>
          <input type="number" name="available_qty" value="1" min="0">

          <label>PDF File</label>
          <input type="file" name="pdf_file" accept="application/pdf" required>

          <button class="btn" type="submit" name="add_book"><i class="fas fa-plus"></i> Add Book</button>
        </form>
      </div>

      <!-- Add Resource -->
      <div class="card">
        <h2><i class="fas fa-folder-plus"></i> Add Student Resource</h2>
        <form method="post" enctype="multipart/form-data">
          <label>Resource Title</label>
          <input type="text" name="res_title" required>

          <label>Description</label>
          <textarea name="res_desc" required></textarea>

          <label>Standard</label>
          <select name="standard" required>
            <option value="">-- Select Standard --</option>
            <option value="1st Standard">1st Standard</option>
            <option value="2nd Standard">2nd Standard</option>
            <option value="3rd Standard">3rd Standard</option>
            <option value="4th Standard">4th Standard</option>
            <option value="5th Standard">5th Standard</option>
            <option value="6th Standard">6th Standard</option>
            <option value="7th Standard">7th Standard</option>
            <option value="8th Standard">8th Standard</option>
            <option value="9th Standard">9th Standard</option>
            <option value="10th Standard">10th Standard</option>
            <option value="11th Standard">11th Standard</option>
            <option value="12th Standard">12th Standard</option>
            <option value="FY">FY (First Year UG)</option>
            <option value="SY">SY (Second Year UG)</option>
            <option value="TY">TY (Third Year UG)</option>
            <option value="Fourth Year">Fourth Year UG</option>
            <option value="PG">PG (Post Graduate)</option>
          </select>

          <label>Course</label>
          <select name="course" required>
            <option value="">-- Select Course --</option>
            <!-- Add/modify as per the university -->
            <option>B.Sc Computer Science</option>
            <option>B.Sc Physics</option>
            <option>B.Sc Chemistry</option>
            <option>B.Sc Mathematics</option>
            <option>B.A English</option>
            <option>B.A Economics</option>
            <option>B.Com</option>
            <option>BBA</option>
            <option>BCA</option>
            <option>M.Sc Computer Science</option>
            <option>M.Sc Physics</option>
            <option>M.Sc Chemistry</option>
            <option>M.Com</option>
            <option>M.A English</option>
            <option>MBA</option>
          </select>

          <label>Upload File (pdf,ppt,docx)</label>
          <input type="file" name="res_file" accept=".pdf,.ppt,.pptx,.doc,.docx" required>

          <button class="btn" type="submit" name="add_resource"><i class="fas fa-upload"></i> Upload Resource</button>
        </form>
      </div>
    </div>

    <a class="back" href="admin_dashboard.php">⬅ Back to Dashboard</a>
  </div>
</body>
</html>
