<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'youth') {
    header("Location: ../pages/Login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$email = $_SESSION['email'] ?? '';

$profile = [];
if ($email !== '') {
    $stmt = $conn->prepare("
        SELECT first_name, middle_name, last_name, birth_date, age, gender, address,
               barangay, municipality, phone, email, status, school, course_grade, work
        FROM kk_members
        WHERE email = ? LIMIT 1
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc() ?? [];
    $stmt->close();
    $profile = array_map(fn($v) => (string)$v, $profile);
}

$fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['middle_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
<?= file_get_contents(__DIR__ . '/../assets/css/dashboard.css') ?>
:root { --sidebar-blue:  #1e293b; }
body { font-family: 'Inter', sans-serif; }
.profile-container {
    max-width: 900px; margin: 2rem auto; background: #fff;
    border-radius: 15px; border: 2px solid var(--sidebar-blue);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 2rem;
}
.profile-header { text-align: center; margin-bottom: 1.5rem; }
.avatar-circle {
    width: 90px; height: 90px; background: var(--sidebar-blue);
    color: #fff; font-size: 2.2rem; font-weight: 700; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;
}
.profile-header h2 { font-weight: 700; color: #333; }
.table-profile { width: 100%; border-collapse: collapse; border-radius: 10px; overflow: hidden; border: 1.5px solid var(--sidebar-blue);}
.table-profile th { width: 30%; background-color: var(--sidebar-blue); color: #fff; font-weight: 600; padding: 12px 15px; border-bottom: 1px solid #ddd;}
.table-profile td { padding: 12px 15px; border-bottom: 1px solid #ddd; color: #333;}
.table-profile tr:hover td { background-color: #f0f4ff;}
.topbar h3 { font-weight: 700;}
input.editable-input { width: 100%; border: none; background: transparent; color: #333;}
input.editable-input:disabled { color: #333; }
.edit-btn { float: right; margin-bottom: 1rem; }
</style>
</head>
<body>

<?php include __DIR__ . '/../includes/YouthSidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h3 style="margin-left:1rem; color:#333;">My Profile</h3>
    </div>

    <div class="profile-container">
        <div class="profile-header">
            <div class="avatar-circle"><?= strtoupper(substr($profile['first_name'] ?? 'U',0,1)) ?></div>
            <h2><?= htmlspecialchars($fullName ?: 'No Name') ?></h2>
        </div>

        

        <table class="table-profile">
            <?php
            foreach ($profile as $key => $value) {

    if (trim($value) === '') {
        continue; 
    }

    $label = ucwords(str_replace('_',' ',$key));
    echo "<tr>
            <th>{$label}</th>
            <td>
                <input class='editable-input'
                       type='text'
                       name='{$key}'
                       value='".htmlspecialchars($value)."'
                       disabled>
            </td>
            
          </tr>";
}

            ?>
        </table>
        <button id="editBtn" class="btn btn-primary edit-btn">Edit</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('logoutBtn').addEventListener('click', function (e) {
    e.preventDefault();
    Swal.fire({
        title: "Log Out?",
        text: "Are you sure you want to log out of your account?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Log out",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) window.location.href = "../auth/Logout.php";
    });
});

const editBtn = document.getElementById('editBtn');
let editing = false;
editBtn.addEventListener('click', () => {
    const inputs = document.querySelectorAll('.editable-input');
    editing = !editing;
    inputs.forEach(input => input.disabled = !editing);
    editBtn.textContent = editing ? 'Save' : 'Edit';

    if (!editing) {
        // Save via AJAX
        const formData = new FormData();
        inputs.forEach(input => formData.append(input.name, input.value));

        fetch('YouthProfileSave.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json())
        .then(data => {
            if(data.success){
                Swal.fire('Saved!','Your profile has been updated.','success');
            } else {
                Swal.fire('Error','Failed to save profile.','error');
            }
        }).catch(err => {
            Swal.fire('Error','Something went wrong.','error');
        });
    }
});
</script>
</body>
</html>
