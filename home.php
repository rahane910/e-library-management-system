<?php
session_start();
include "db.php";

// Auth Guard: redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get session info
$role = $_SESSION['role'] ?? 'guest';
$name = $_SESSION['name'] ?? 'Guest';

$profile_pic = "default.png"; // fallback

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT profile_pic FROM users WHERE id = '$user_id' LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        if (!empty($row['profile_pic'])) {
            $profile_pic = $row['profile_pic']; // store filename or relative path
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library Home</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.10.100/pdf.min.js"></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif;}
body {background: linear-gradient(135deg,#1f1c2c,#928dab); color:#fff; line-height:1.6; scroll-behavior:smooth;}
a {text-decoration:none; color:inherit;}

/* Navbar */
nav {
  position: fixed;
  top:0; left:0; width:100%; display:flex; justify-content:space-between; align-items:center;
  padding:15px 30px; background:rgba(20,20,20,0.85); backdrop-filter:blur(10px);
  z-index:1000; border-bottom:1px solid rgba(0,198,255,0.3);
  box-shadow:0 5px 20px rgba(0,0,0,.5);
}
nav .logo {font-size:24px; font-weight:700; color:#00c6ff;}
nav ul {display:flex; list-style:none; gap:30px; margin-top:10px;}
nav ul li {position:relative;}
nav ul li a {padding:10px 12px; border-radius:8px; transition:.3s;}
nav ul li a:hover {background:linear-gradient(135deg,#00c6ff,#0072ff); transform:scale(1.05);}
nav .dark-mode-toggle {padding:8px 12px; border:none; border-radius:8px; cursor:pointer; background:linear-gradient(135deg,#00c6ff,#0072ff); transition:.3s;}
nav .dark-mode-toggle:hover {transform:scale(1.05); background:linear-gradient(135deg,#ff416c,#ff4b2b);}
nav ul li a.active {
    background: linear-gradient(135deg,#ff416c,#ff4b2b);
    transform: scale(1.05);
}

body.dark-mode {
    background: #121212 !important;
    color: #e0e0e0 !important;
}

body.dark-mode a {
    color: #00c6ff !important;
}

body.dark-mode nav {
    background: rgba(20,20,20,0.95) !important;
}

body.dark-mode .contact-form,
body.dark-mode .contact-info,
body.dark-mode section {
    background: rgba(30,30,30,0.9) !important;
    color: #e0e0e0 !important;
}

body.dark-mode .dark-mode-toggle {
    background: linear-gradient(135deg,#ff416c,#ff4b2b) !important;
}



/* Mobile Navbar */
.menu-toggle {display:none; font-size:24px; cursor:pointer; color:#00c6ff;}
@media(max-width:900px){
  nav ul {position:absolute; top:60px; left:0; width:100%; flex-direction:column; background:rgba(20,20,20,0.95); display:none;}
  nav ul li {text-align:center; padding:10px 0; border-bottom:1px solid rgba(0,198,255,0.2);}
  nav ul.show {display:flex;}
  .menu-toggle {display:block;}
}

/* Section separator */
section {
    padding-top: 130px; /* add extra top padding for fixed navbar */
    margin-top: -80px;  /* offset negative margin to maintain layout */
}

.section-separator {position: relative; margin:40px 0; text-align: center;}
.section-separator::before, .section-separator::after {
    content: "";
    position: absolute;
    top: 50%;
    width: 40%;
    height: 2px;
    background: linear-gradient(90deg, #00c6ff, #0072ff);
    transform: translateY(-50%);
    transition: 0.3s all;
}
.section-separator::before { left: 0; }
.section-separator::after { right: 0; }
.section-separator span {
    display: inline-block;
    padding: 0 20px;
    font-size: 20px;
    font-weight: 600;
    color: #00c6ff;
    background: linear-gradient(135deg,#1f1c2c,#928dab);
    border-radius: 20px;
    position: relative;
    z-index: 1;
}
.section-separator:hover::before,
.section-separator:hover::after { width: 45%; }

/* Sections */
section {padding:100px 30px; max-width:1200px; margin:0 auto;}
h1,h2 {margin-bottom:20px; color:#00c6ff;}
p {margin-bottom:15px; color:#ddd;}


.home-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 30px;
  max-width: 1200px;
  margin: 0 auto;
}

.home-cards .card {
  background: rgba(20,20,20,0.85);
  padding: 30px 20px;
  border-radius: 20px;
  text-align: center;
  box-shadow: 0 8px 30px rgba(0,0,0,.5);
  transition: transform 0.3s, box-shadow 0.3s;
}

.home-cards .card:hover {
  transform: translateY(-10px);
  box-shadow: 0 12px 40px rgba(0,198,255,.6);
}

.home-cards .card i {
  color: #00c6ff;
  margin-bottom: 15px;
}

.home-cards .card h3 {
  color: #ff416c;
  margin-bottom: 10px;
}

.home-cards .card p {
  color: #ddd;
  font-size: 1rem;
  line-height: 1.5;
}
.home-cards a.card {
  display: block;
  background: rgba(20,20,20,0.85);
  padding: 30px 20px;
  border-radius: 20px;
  text-align: center;
  box-shadow: 0 8px 30px rgba(0,0,0,.5);
  transition: transform 0.3s, box-shadow 0.3s;
  text-decoration: none; /* remove underline */
  color: inherit; /* keep text color */
}

.home-cards a.card:hover {
  transform: translateY(-10px);
  box-shadow: 0 12px 40px rgba(0,198,255,.6);
}



/* Contact Form Section */
.contact-section {display:flex; flex-wrap:wrap; gap:50px; justify-content:space-between;}
.contact-info, .contact-form {flex:1 1 450px; background: rgba(20,20,20,0.8); padding:30px; border-radius:20px; box-shadow:0 8px 30px rgba(0,0,0,.5); transition:0.3s;}
.contact-info:hover, .contact-form:hover {transform:translateY(-5px); box-shadow:0 12px 40px rgba(0,198,255,.6);}
.contact-info h2, .contact-form h2 {margin-bottom:15px;}
.info-item {margin-bottom:15px;}
.info-item i {margin-right:12px; color:#00c6ff; transition:.3s;}
.info-item i:hover {color:#ff4b2b; transform:scale(1.2);}
.contact-form form {display:flex; flex-direction:column; gap:15px;}
.contact-form select {
    padding:12px 15px;
    border-radius:10px;
    border:none;
    outline:none;
    font-size:14px;
    background: rgba(255,255,255,0.15);
    color:#fff;
    transition:0.3s;
    -webkit-appearance:none;
    -moz-appearance:none;
    appearance:none;
}
.contact-form select option { background: #141414; color:#fff; }
.contact-form input, .contact-form select, .contact-form textarea {
  padding:12px 15px; border-radius:10px; border:none; outline:none; font-size:14px;
  background: rgba(255,255,255,0.15); color:#fff; transition:0.3s;
}
.contact-form input:focus,.contact-form textarea:focus {background: rgba(255,255,255,0.25);}
.contact-form button {padding:12px 20px; border:none; border-radius:10px; background:linear-gradient(135deg,#00c6ff,#0072ff); color:#fff; font-weight:600; cursor:pointer; transition:0.3s;}
.contact-form button:hover {transform:scale(1.05); background:linear-gradient(135deg,#ff416c,#ff4b2b);}
.social-links {margin-top:20px; display:flex; gap:15px;}
.social-links a {color:#fff; font-size:20px; transition:0.3s;}
.social-links a:hover {color:#00c6ff; transform:translateY(-5px);}
@media(max-width:900px){ .contact-section {flex-direction:column;} }

/*About Section*/
#about h2, #about h3 { font-family: 'Poppins', sans-serif; }
#about p, #about li { font-family: 'Poppins', sans-serif; color: #e0e0e0; }
#about ul li::marker { color: #00c6ff; }
#about section:hover { box-shadow: 0 15px 40px rgba(0,198,255,0.4); transition: 0.4s; }
</style>
</head>
<body>

<!-- Navbar -->
<nav>
  <div class="logo">📚E-Library SSGM College,Kopargaon</div>
  <div class="menu-toggle"><i class="fas fa-bars"></i></div>
  <ul>
   <?php if(isset($_SESSION['user_id'])): ?>
  <li style="display:flex; align-items:center; gap:10px; margin-top:-10px;">
  <img src="<?php echo htmlspecialchars($profile_pic); ?>" 
       alt="Profile" 
       style="width:50px; height:50px; border-radius:50%; object-fit:cover; border:2px solid #00c6ff;">
  <span>Hello, <?php echo htmlspecialchars($name);?></span>
</li>
<?php endif; ?>
 <?php if(isset($_SESSION['user_id'])): ?>
      <!--Logout -->
      <li><a href="logout.php">Logout</a></li>
    <?php else: ?>
      <!-- Login/Register -->
      <li><a href="login.php">Login/Register</a></li>
    <?php endif; ?>

    <li><a href="#home">Home</a></li>
    <li><a href="#about">About</a></li>
    <li><a href="#contact">Contact</a></li>
    <li><a href="student_dashboard.php">Browse Library</a></li>
    <li>
  <input type="text" id="nav-search" placeholder="Search..." style="
    padding:6px 10px; 
    border-radius:8px; 
    border:none; 
    outline:none; 
    font-size:14px; 
    background: rgba(255,255,255,0.15); 
    color:#fff; 
    width:300px;
    transition:0.3s;
  ">
</li>
  </ul>
  <button class="dark-mode-toggle">🌙</button>
</nav>

<!-- Sections -->
<section id="home">
  <h1 style="font-size:3rem; text-align:center; margin-bottom:20px;">Welcome to Our Library</h1>
  <p style="font-size:1.2rem; text-align:center; margin-bottom:50px;">Explore books, resources, and more!</p>

  <!-- Static Home Cards -->
  <div class="home-cards">
    <a href="student_dashboard.php" class="card">
      <i class="fas fa-book-open fa-3x"></i>
      <h3>Browse Books</h3>
      <p>Explore a wide range of digital books from various categories.</p>
    </a>
    <a href="student_resources.php" class="card">
      <i class="fas fa-user-graduate fa-3x"></i>
      <h3>Student Resources</h3>
      <p>Access study materials, guides, and resources for students.</p>
    </a>
    <a href="librarian_help.php" class="card">
      <i class="fas fa-chalkboard-teacher fa-3x"></i>
      <h3>Librarian Help</h3>
      <p>Get assistance from librarians for research and book queries.</p>
    </a>
    <a href="global_access.php" class="card">
      <i class="fas fa-globe fa-3x"></i>
      <h3>Global Access</h3>
      <p>Read books anytime, anywhere, on any device.</p>
    </a>
  </div>

  <!-- Dynamic Books Section -->
<!-- Books Section -->





<!-- Resources Section -->
<h2 class="section-separator" style="margin-top:60px;"><span>Resources</span></h2>
<div class="home-cards">
<?php
$res_res = $conn->query("SELECT * FROM student_resources ORDER BY created_at DESC LIMIT 8");
if ($res_res && $res_res->num_rows > 0):
    while ($resource = $res_res->fetch_assoc()):
        $pdfPath = htmlspecialchars($resource['file_path']);
?>
  <a href="resource_preview.php?id=<?php echo $resource['id']; ?>" class="card">
      <!-- Canvas thumbnail -->
      <canvas class="pdf-thumb" 
              data-pdf="<?php echo $pdfPath; ?>" 
              style="width:100%; height:180px; border-radius:12px; background:#f4f4f4; margin-bottom:12px;">
      </canvas>
      <h3><?php echo htmlspecialchars($resource['title']); ?></h3>
      <p><?php echo htmlspecialchars(substr($resource['description'],0,60)); ?>...</p>
  </a>
<?php endwhile; else: ?>
  <p style="color:#fff; text-align:center;">No resources available.</p>
<?php endif; ?>
</div>

<!-- PDF.js Script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
// Render first page as thumbnail
document.querySelectorAll(".pdf-thumb").forEach(canvas => {
    const url = canvas.dataset.pdf;
    const ctx = canvas.getContext("2d");

    pdfjsLib.getDocument(url).promise.then(pdf => {
        pdf.getPage(1).then(page => {
            const viewport = page.getViewport({ scale: 0.2 }); // small for thumbnail
            canvas.width = viewport.width;
            canvas.height = viewport.height;

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            page.render(renderContext);
        });
    }).catch(err => {
        console.error("PDF load error:", err);
        ctx.fillStyle = "red";
        ctx.fillText("Error loading PDF", 10, 50);
    });
});
</script>

</section>




<section id="about" style="padding:60px 20px; background:linear-gradient(135deg,#1f1c2c,#928dab); color:#fff; border-radius:20px; margin:50px auto; max-width:1200px;">
    <div class="section-separator"><span>About Us</span></div> 
  <h2 style="font-size:3rem; color:#00c6ff; margin-bottom:20px;">🌱 Welcome to SSGM College's E-Library</h2>
  <p style="font-size:1.4rem; line-height:1.8; margin-bottom:30px;">
    Welcome to <strong>SSGM College's E-Library</strong>, your gateway to a world of knowledge, imagination, and lifelong learning.
  </p>
  <p style="font-size:1.4rem; line-height:1.8; margin-bottom:30px;">
    We believe that access to books and educational resources should never be limited by geography, time, or circumstance. That’s why we created this digital library—to empower readers, students, educators, and curious minds with instant access to thousands of titles, all from the comfort of their own devices.
  </p>
  <h3 style="color:#ff416c; margin-bottom:15px;">🌱 Our Mission</h3>
  <p style="font-size:1.4rem; line-height:1.8; margin-bottom:30px;">
    To democratize learning by providing free, easy, and inclusive access to digital books and educational content for everyone, everywhere.
  </p>
  <h3 style="color:#ff4b2b; margin-bottom:15px;">📚 What We Offer</h3>
  <ul style="font-size:1.4rem; line-height:1.8; margin-bottom:30px; list-style:disc; padding-left:25px;">
    <li>A growing catalog of digital books and multimedia resources</li>
    <li>Reading progress tracking and bookmarks</li>
    <li>Role-based access for students, librarians, and administrators</li>
  </ul>
  <h3 style="color:#00c6ff; margin-bottom:15px;">💬 Join Us</h3>
  <p style="font-size:1.4rem; line-height:1.8;">
    Explore. Learn. Grow. We invite you to browse our collection, sign up, and become part of a community that values curiosity and lifelong learning.
  </p>
</section>




<section id="contact">
    <div class="section-separator"><span>Contact Us</span></div>
  <p>Have a question or need help? Fill out the form or reach us directly.</p>
  <div class="contact-section">
    <div class="contact-info">
      <h2>Direct Inquiries</h2>
      <div class="info-item"><i class="fas fa-envelope"></i> support@ssgmlibrary.com</div>
      <div class="info-item"><i class="fas fa-phone"></i> +91-7499587873</div>
      <h2>Location & Hours</h2>
      <div class="info-item"><i class="fas fa-map-marker-alt"></i> 123 Library Street, Knowledge City</div>
      <div class="info-item"><i class="fas fa-clock"></i> Mon-Sat: 9:00 AM - 6:00 PM</div>
      <div class="info-item"><i class="fas fa-clock"></i> Sun: Holiday</div>
      <h2>Connect with Us</h2>
      <div class="social-links">
        <a href="#" target="_blank"><i class="fab fa-facebook"></i></a>
        <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
        <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
      </div>
    </div>
    <div class="contact-form">
      <h2>Send a Message</h2>
      <form action="send_contact.php" method="post">
        <input type="text" name="name" placeholder="Name*" required>
        <input type="email" name="email" placeholder="Email*" required>
        <input type="text" name="phone" placeholder="Phone (Optional)">
        <select name="user_type">
          <option value="">Select User Type</option>
          <option value="Student">Student</option>
          <option value="Librarian">Librarian</option>
          <option value="Guest">Guest</option>
        </select>
        <input type="text" name="subject" placeholder="Subject*" required>
        <textarea name="message" rows="6" placeholder="Message*" required></textarea>
        <button type="submit">Send Message</button>
      </form>
    </div>
  </div>
</section>

<script>
  const darkBtn = document.querySelector('.dark-mode-toggle');
  darkBtn.addEventListener('click', ()=>document.body.classList.toggle('dark-mode'));
  const toggle = document.querySelector('.menu-toggle');
  const menu = document.querySelector('nav ul');
  toggle.addEventListener('click', ()=> menu.classList.toggle('show'));

// Highlight active menu item on scroll
const sections = document.querySelectorAll('section');
const navLinks = document.querySelectorAll('nav ul li a');
const navbarHeight = document.querySelector('nav').offsetHeight;

function activateMenu() {
    let scrollPos = window.scrollY + navbarHeight + 20; // add padding

    sections.forEach(section => {
        const top = section.offsetTop;
        const bottom = top + section.offsetHeight;

        if (scrollPos >= top && scrollPos < bottom) {
            navLinks.forEach(link => link.classList.remove('active'));
            const activeLink = document.querySelector('nav ul li a[href="#' + section.id + '"]');
            if (activeLink) activeLink.classList.add('active');
        }
    });
}

window.addEventListener('scroll', activateMenu);
window.addEventListener('load', activateMenu);


// Simple search filter for nav links
const searchInput = document.getElementById('nav-search');

searchInput.addEventListener('input', function() {
    const query = this.value.toLowerCase();
    const sections = document.querySelectorAll('section');
    
    sections.forEach(section => {
        if(section.innerText.toLowerCase().includes(query)){
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }
    });
});

</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.10.100/pdf.min.js"></script>
</body>
</html>
