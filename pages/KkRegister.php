<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$popup = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name   = trim($_POST['first_name'] ?? '');
    $middle_name  = trim($_POST['middle_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $birth_date   = $_POST['birth_date'] ?? '';
    $gender       = $_POST['gender'] ?? '';
    $address      = $_POST['address'] ?? '';
    $barangay     = $_POST['barangay'] ?? '';
    $municipality = $_POST['municipality'] ?? '';
    $phone        = $_POST['phone'] ?? '';
    $email        = $_POST['email'] ?? '';
    $status       = $_POST['status'] ?? '';
    $educbackground = $_POST['educbackground'] ?? null;
    $school       = $_POST['school'] ?? null;
    $course_grade = $_POST['course_grade'] ?? null;
    $work         = $_POST['work'] ?? null;
    $password_raw = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $guardian_name = $_POST['guardian_name'] ?? null;
    $guardian_relationship = $_POST['guardian_relationship'] ?? null;
    $guardian_consent = isset($_POST['guardian_consent']) ? 1 : 0;

    $errors = [];
    if ($password_raw !== $confirm_pass) {
        $errors[] = 'Passwords do not match!';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format!';
    }
    if (!empty($phone) && !preg_match('/^09\d{9}$/', $phone)) {
        $errors[] = 'Invalid phone number format! Use 09XXXXXXXXX';
    }

    $checkStmt = $conn->prepare("SELECT id FROM kk_members WHERE email=? LIMIT 1");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        $errors[] = 'Email already registered!';
    }
    $checkStmt->close();

    if (!empty($errors)) {
        $errorText = implode('<br>', $errors);
        $popup = "Swal.fire({
            icon: 'error',
            title: 'Registration Failed',
            html: `$errorText`,
            confirmButtonText: 'OK'
        });";
    } else {
        $dob = new DateTime($birth_date);
        $today = new DateTime();
        $age = $dob->diff($today)->y;

        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        $insertStmt = $conn->prepare("INSERT INTO kk_members
            (first_name, middle_name, last_name, birth_date, age, gender, address, barangay, municipality, phone, email, status, school, course_grade, educbackground, work, password, login_status, guardian_name, guardian_relationship, guardian_consent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");

        $insertStmt->bind_param(
            "ssssissssssssssssiii",
            $first_name, $middle_name, $last_name, $birth_date, $age, $gender, $address,
            $barangay, $municipality, $phone, $email, $status, $school, $course_grade,
            $educbackground, $work, $password, $guardian_name, $guardian_relationship, $guardian_consent
        );

        if ($insertStmt->execute()) {
            $popup = "Swal.fire({
                icon: 'success',
                title: 'Registration Successful!',
                text: 'Youth registered successfully. Waiting for SK approval.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location = 'login.html';
            });";
        } else {
            $popup = "Swal.fire({
                icon: 'error',
                title: 'Database Error',
                text: 'An error occurred while saving your data: " . addslashes($conn->error) . "',
                confirmButtonText: 'OK'
            });";
        }
        $insertStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Katipunan ng Kabataan Registration</title>
<link id="themeStylesheet" rel="stylesheet" href="../assets/css/reg.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <button id="themeToggle" class="theme-toggle">🌙</button>
<div class="login-box">
<h1>Youth Profiling Form</h1>
<form id="registrationForm" method="post" action="">
  <div class="input-row">
    <input type="text" name="first_name" placeholder="First Name" required>
    <input type="text" name="middle_name" placeholder="Middle Name">
    <input type="text" name="last_name" placeholder="Last Name" required>
  </div>

  <div class="input-row">
    <input type="date" name="birth_date" id="birth_date" required>
  </div>

  <div class="input-row">
    <input type="text" name="gender" placeholder="Gender" required>
  </div>

  <div class="input-row">
    <select id="municipality" name="municipality" required>
      <option value="">Select Municipality</option>
      <option value="Bagamanoc">Bagamanoc</option>
      <option value="Baras">Baras</option>
      <option value="Bato">Bato</option>
      <option value="Caramoran">Caramoran</option>
      <option value="Gigmoto">Gigmoto</option>
      <option value="Pandan">Pandan</option>
      <option value="Panganiban">Panganiban</option>
      <option value="San Andres">San Andres</option>
      <option value="San Miguel">San Miguel</option>
      <option value="Viga">Viga</option>
      <option value="Virac">Virac</option>
    </select>

    <select id="barangay" name="barangay" required>
      <option value="">Select Barangay</option>
    </select>
  </div>  

  <div class="input-row">
    <input type="text" name="phone" placeholder="Contact Number (PH Format 09XXXXXXXXX)" pattern="^09\d{9}$" maxlength="11" required>
    <input type="email" name="email" placeholder="Email" required>
  </div>

  <div class="input-row">
    <select name="status" required>
      <option value="">Civil Status</option>
      <option value="Single">Single</option>
      <option value="Married">Married</option>
      <option value="Widowed">Widowed</option>
    </select>
  </div>

  <div class="input-row" id="work-field" style="display:none;">
    <select id="work" name="work">
      <option value="">Work Status</option>
      <option>Unemployed</option>
      <option>Self-Employed</option>
      <option>Employed</option>
      <option>Student</option>
    </select>
  </div>

  <div class="input-row" id="schoolRow" style="display:none;">
    <select name="educbackground" id="educbackground">
      <option value="">Educational Background</option>
      <option value="Highschool">Highschool</option>
      <option value="Senior Highschool">Senior Highschool</option>
      <option value="College">College Level</option>
      <option value="Graduate">College Grad</option>
    </select>
    <input type="text" name="course_grade" id="course_grade" placeholder="Course/Grade">
    <input type="text" name="school" id="school" placeholder="School Name">
  </div>

  <div id="consent-field" style="display:none;">
    <div class="input-row">
      <input type="text" name="guardian_name" placeholder="Parent/Guardian Name">
    </div>
    <div class="input-row">
      <input type="text" name="guardian_relationship" placeholder="Relationship to Minor">
    </div>
    <div class="input-row">
      <label>
        <input type="checkbox" name="guardian_consent" value="yes">
        I, the parent/guardian, consent to the participation of this youth.
      </label>
    </div>
  </div>

  <div class="input-row">
    <div class="passwordfield">
      <input type="password" id="password" name="password" placeholder="Password" required>
      <button type="button" class="btn" onclick="togglePassword('password', this)">Show</button>
    </div>
    <div class="passwordfield">
      <input type="password" id="confirm" name="confirm_password" placeholder="Confirm Password" required>
      <button type="button" class="btn" onclick="togglePassword('confirm', this)">Show</button>
    </div>
  </div>

  <div class="terms">
    <input type="checkbox" id="terms" required>
    I agree to the <a href="terms.html" target="_blank">Terms and Conditions</a>
  </div>

  <button type="submit" class="login-btn">Submit</button>
</form>

<div class="register">
  Are you SK Official? <a href="SkRegister.html">Register Here</a>
</div>
</div>

<script src="../assets/js/regitration.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const birthInput = document.getElementById("birth_date");
    const workField = document.getElementById("work-field");
    const consentField = document.getElementById("consent-field");
    const schoolRow = document.getElementById("schoolRow");
    const workSelect = document.getElementById("work");
    const municipalitySelect = document.getElementById("municipality");
    const barangaySelect = document.getElementById("barangay");
    const addressInput = document.getElementById("address");
    const form = document.getElementById("registrationForm");

    window.togglePassword = function(id, btn) {
        const field = document.getElementById(id);
        if (!field) return;
        if (field.type === "password") {
            field.type = "text";
            btn.textContent = "Hide";
        } else {
            field.type = "password";
            btn.textContent = "Show";
        }
    };

    function handleAgeChange() {
        const dob = new Date(birthInput.value);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) age--;

        if (age >= 18) {
            workField.style.display = "block";
            consentField.style.display = "none";
            schoolRow.style.display = "none";
        } else {
            workField.style.display = "none";
            consentField.style.display = "block";
            schoolRow.style.display = "flex";
        }
    }

    function handleWorkChange() {
        if (workSelect.value === "Student") {
            schoolRow.style.display = "flex";
        } else {
            schoolRow.style.display = "none";
        }
    }

    function updateAddress() {
        const municipality = municipalitySelect.value;
        const barangay = barangaySelect.value;
        if (addressInput) {
            if (barangay && municipality) addressInput.value = `${barangay}, ${municipality}, Catanduanes`;
            else if (municipality) addressInput.value = `${municipality}, Catanduanes`;
        }
    }

    form.addEventListener("submit", (e) => {
        const pass = document.getElementById("password")?.value;
        const confirm = document.getElementById("confirm")?.value;
        if (!document.getElementById("terms")?.checked) {
            e.preventDefault();
            Swal.fire({icon:'warning', title:'Error', text:'You must agree to Terms and Conditions', confirmButtonText:'OK'});
            return;
        }
        if (pass !== confirm) {
            e.preventDefault();
            Swal.fire({icon:'error', title:'Error', text:'Passwords do not match!', confirmButtonText:'OK'});
            return;
        }
    });

    birthInput?.addEventListener("change", handleAgeChange);
    workSelect?.addEventListener("change", handleWorkChange);
    municipalitySelect?.addEventListener("change", updateAddress);
    barangaySelect?.addEventListener("change", updateAddress);
});
</script>

<?php if (!empty($popup)): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    <?= $popup ?>
});
</script>
<?php endif; ?>

</body>
</html>
