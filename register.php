<?php
session_start();
include "db.php";

$error = $success = "";

// Helper function for ordinal
function ordinal($n){ return $n.((($n>3 && $n<21) || $n%10>3)?'th':[ 'st','nd','rd'][$n%10] ); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'];
    $m_id = trim($_POST['m_id']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = $first_name . " " . $middle_name . " " . $surname;
    $profilePicPath = "uploads/default.png";

    // Password check
    if($password !== $confirm_password){
        $error = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Profile picture upload
        if(!empty($_FILES['profile_pic']['name'])){
            $target_dir = "uploads/";
            if(!is_dir($target_dir)) mkdir($target_dir,0777,true);
            $file_name = time() . "_" . basename($_FILES['profile_pic']['name']);
            $target_file = $target_dir . str_replace(" ","_",$file_name);
            if(move_uploaded_file($_FILES['profile_pic']['tmp_name'],$target_file)){
                $profilePicPath = $target_file;
            }
        }

        if($role=="student"){
            $class_grade = $_POST['class_grade'];
            $admission_year = $_POST['admission_year'];
            $batch = $_POST['batch_course'];
            $phone = $_POST['phone'];
            $gender = $_POST['gender'];
            $dob = $_POST['dob'];
            $address = $_POST['address'];
            $city = $_POST['city'];
            $state = $_POST['state'];
            $zip = $_POST['zip'];

           $stmt = $conn->prepare("INSERT INTO users 
(m_id, first_name, middle_name, surname, name, email, password, role, profile_pic, class_grade, admission_year, batch_course, phone, gender, dob, address, city, state, zip, status) 
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'active')");

            $stmt->bind_param("sssssssssssssssssss", $m_id, $first_name, $middle_name, $surname, $full_name, $email, $hashed_password, $role, $profilePicPath, $class_grade, $admission_year, $batch, $phone, $gender, $dob, $address, $city, $state, $zip);
        } else {
            $department = $_POST['department'];
            $access_level = $_POST['access_level'];

            $stmt = $conn->prepare("INSERT INTO users 
            (m_id, first_name, middle_name, surname, name, email, password, role, profile_pic, department, access_level, status) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?, 'active')");
            $stmt->bind_param("sssssssssss", $m_id, $first_name, $middle_name, $surname, $full_name, $email, $hashed_password, $role, $profilePicPath, $department, $access_level);
        }

        if($stmt->execute()){
            $success = "Registration successful! <a href='login.php'>Login Here</a>.";
        } else {
            $error = strpos($stmt->error,"Duplicate")!==false ? "M.ID or Email already exists!" : "DB Error: ".$stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
body {font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#1f1c2c,#928dab);color:#fff;display:flex;justify-content:center;align-items:center;min-height:100vh;}
.container {background: rgba(0,0,0,.6); padding:25px; border-radius:20px; width:600px; max-width:95%; box-shadow:0 10px 30px rgba(0,0,0,.7); overflow-y:auto; max-height:90vh;}
h2 {text-align:center;margin-bottom:20px;color:#00c6ff;}
input, select, button, textarea {width:100%;padding:12px;margin:8px 0;border-radius:10px;border:none;outline:none;font-size:14px;}
input, select, textarea {background: rgba(255,255,255,.15); color:#fff;}
select option {background:#222;color:#fff;}
button {background: linear-gradient(135deg,#00c6ff,#0072ff);color:#fff;font-weight:bold;cursor:pointer;transition:0.3s;}
button:hover {transform: scale(1.05);}
label {font-size:14px;margin-top:10px;display:block;}
.error {color:#ff4b2b;text-align:center;}
.success {color:#00ff99;text-align:center;}
.section {margin-top:15px;padding:15px;border-radius:12px;background: rgba(255,255,255,0.05);}
.section h3 {margin-bottom:10px;color:#00c6ff;font-size:16px;}
#profile-preview {width:100px;height:100px;border-radius:50%;object-fit:cover;display:block;margin:10px auto;}
::-webkit-scrollbar {width:8px;}
::-webkit-scrollbar-track {background:rgba(255,255,255,0.1);border-radius:8px;}
::-webkit-scrollbar-thumb {background:rgba(0,198,255,0.6);border-radius:8px;}
</style>
</head>
<body>
<div class="container">
<h2>Register</h2>
<?php if($error) echo "<p class='error'>$error</p>"; ?>
<?php if($success) echo "<p class='success'>$success</p>"; ?>

<form method="POST" enctype="multipart/form-data" id="regForm">
<div class="section">
<h3><i class="fas fa-graduation-cap"></i> Academic / Role Info</h3>
<label>Role</label>
<select name="role" id="role-select" required>
<option value="">Select Role</option>
<option value="student">Student</option>
<option value="admin">Admin</option>
</select>

<div id="student-section" style="display:none;">
<label>Class / Standard</label>
<select name="class_grade" id="class-grade">
<option value="">Select Standard</option>
<option value="1st Year Bachelors">1st Year Bachelors</option>
<option value="2nd Year Bachelors">2nd Year Bachelors</option>
<option value="3rd Year Bachelors">3rd Year Bachelors</option>
<option value="4th Year Bachelors">4th Year Bachelors</option>
<option value="1st Year PG">1st Year PG</option>
<option value="2nd Year PG">2nd Year PG</option>
<option value="9th">9th</option>
<option value="10th">10th</option>
<option value="11th">11th</option>
<option value="12th">12th</option>
</select>
<label>Batch / Course</label>
<select name="batch_course" id="batch-course">
<option value="">Select Batch / Course</option>
</select>
<label>Admission Year</label>
<input type="text" name="admission_year" placeholder="Admission Year">
<label>Phone Number</label>
<input type="text" name="phone" placeholder="Phone Number">
<label>Gender</label>
<select name="gender">
<option value="">Select Gender</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Other">Other</option>
</select>
<label>Date of Birth</label>
<input type="date" name="dob">
<label>Address</label>
<textarea name="address" placeholder="Street / City / State"></textarea>
<input type="text" name="city" placeholder="City">
<input type="text" name="state" placeholder="State / Province">
<input type="text" name="zip" placeholder="ZIP / Postal Code">
</div>

<div id="admin-section" style="display:none;">
<label>Department</label>
<input type="text" name="department" placeholder="Department">
<label>Access Level</label>
<select name="access_level">
<option value="">Select Level</option>
<option value="Super Admin">Super Admin</option>
<option value="Librarian">Librarian</option>
<option value="Staff">Staff</option>
</select>
</div>
</div>

<div class="section">
<h3>Basic Info</h3>
<input type="text" name="m_id" placeholder="M.ID" required>
<input type="text" name="first_name" placeholder="First Name" required>
<input type="text" name="middle_name" placeholder="Middle Name">
<input type="text" name="surname" placeholder="Surname" required>
<input type="email" name="email" placeholder="Email" required>
<label>Profile Picture</label>
<input type="file" name="profile_pic" accept="image/*" onchange="previewImage(event)">
<img id="profile-preview" src="uploads/default.png" alt="Preview">
</div>

<div class="section">
<h3>Security</h3>
<input type="password" name="password" placeholder="Password" required>
<input type="password" name="confirm_password" placeholder="Confirm Password" required>
</div>

<button type="submit">Register</button>
<div style="text-align:center; margin-top:20px;">
    <p style="margin-bottom:8px; font-size:14px;">Already have an account?</p>
    <a href="login.php" style="
        display:inline-block;
        padding:10px 25px;
        background: linear-gradient(135deg,#00c6ff,#0072ff);
        color: #fff;
        border-radius: 12px;
        text-decoration: none;
        font-weight: bold;
        transition: 0.3s;
    " 
    onmouseover="this.style.transform='scale(1.05)';" 
    onmouseout="this.style.transform='scale(1)';">Login Here</a>
</div>
</form>
</div>



<script>
const roleSelect = document.getElementById('role-select');
const studentSection = document.getElementById('student-section');
const adminSection = document.getElementById('admin-section');
const classGrade = document.getElementById('class-grade');
const batchCourse = document.getElementById('batch-course');

const ugCourses = ['B.Sc Physics','B.Sc Chemistry','B.Sc Biology','B.Sc Mathematics','BCA','BBA','B.Com','B.Com (Hons)','B.Tech Computer Science','B.Tech Mechanical','B.Tech Civil','B.Tech Electrical','B.Sc Computer Science','B.Ed'];
const pgCourses = ['M.Sc Physics','M.Sc Chemistry','M.Sc Mathematics','M.Tech Computer Science','M.Tech Mechanical','MBA'];

roleSelect.addEventListener('change', function(){
    if(this.value === 'student'){
        studentSection.style.display = 'block';
        adminSection.style.display = 'none';
    } else if(this.value === 'admin'){
        studentSection.style.display = 'none';
        adminSection.style.display = 'block';
    } else {
        studentSection.style.display = 'none';
        adminSection.style.display = 'none';
    }
});

classGrade.addEventListener('change', function(){
    const selected = this.value;
    batchCourse.innerHTML = '<option value="">Select Batch / Course</option>';

    if(selected.includes('Bachelors')){
        ugCourses.forEach(course=>{
            const opt = document.createElement('option');
            opt.value = course;
            opt.textContent = course;
            batchCourse.appendChild(opt);
        });
    } else if(selected.includes('PG')){
        pgCourses.forEach(course=>{
            const opt = document.createElement('option');
            opt.value = course;
            opt.textContent = course;
            batchCourse.appendChild(opt);
        });
    } else {
        let schoolStreams = [];
        if(selected.includes('9th') || selected.includes('10th') || selected.includes('11th') || selected.includes('12th')){
            schoolStreams = ['Science','Commerce','Arts'];
        } else {
            schoolStreams = ['General Stream'];
        }
        schoolStreams.forEach(course=>{
            const opt = document.createElement('option');
            opt.value = course;
            opt.textContent = course;
            batchCourse.appendChild(opt);
        });
    }
});

function previewImage(event){
    const output = document.getElementById('profile-preview');
    output.src = URL.createObjectURL(event.target.files[0]);
}
</script>
</body>
</html>
