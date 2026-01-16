<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['role'], $_SESSION['user_id'])) {
    header("Location: Login.html");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$barangay = $_SESSION['barangay'] ?? '';
$municipality = $_SESSION['municipality'] ?? '';

$edit_mode = false;
$event_id = $_GET['id'] ?? null;
$error = "";

if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($role === 'sk') {
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND sk_id = ?");
        $stmt->bind_param("ii", $del_id, $user_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND admin_id = ?");
        $stmt->bind_param("ii", $del_id, $user_id);
    }
    $stmt->execute();
    $_SESSION['flash'] = "Event deleted successfully!";
    header("Location: CreateActivity.php");
    exit();
}

if ($event_id) {
    $edit_mode = true;
    if ($role === 'sk') {
        $stmt = $conn->prepare("SELECT * FROM events WHERE id=? AND sk_id=?");
        $stmt->bind_param("ii", $event_id, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT * FROM events WHERE id=? AND admin_id=?");
        $stmt->bind_param("ii", $event_id, $user_id);
    }
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    if (!$event) {
        $_SESSION['flash'] = "Event not found or unauthorized.";
        header("Location: CreateActivity.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $details = trim($_POST['details'] ?? $description);
    $start_date = $_POST['start_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $category = $_POST['category'] ?? '';
    $max_participants = (int)($_POST['max_participants'] ?? 0);
    $registration_deadline = $_POST['registration_deadline'] ?? null;
    $cutoff_policy = $registration_deadline;
    $imagePath = $event['image'] ?? null;

    // Handle Image Upload
    if (!empty($_FILES['image']['name'] ?? '')) {
        $uploadDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileTmp = $_FILES['image']['tmp_name'];
        $fileNameOriginal = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($fileNameOriginal, PATHINFO_EXTENSION));
        $fileName = uniqid('event_', true) . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmp, $targetPath)) {
            $imagePath = 'uploads/' . $fileName;
        } else {
            $error = "Failed to upload image.";
        }
    }

    // Validation
    if (!$title) $error = "Title is required.";
    if (!$start_date || !$end_date) $error = $error ?: "Start and End dates are required.";
    if ($start_date && $end_date && $end_date < $start_date) $error = "End Date cannot be earlier than Start Date.";
    if ($registration_deadline && $end_date && $registration_deadline > $end_date) $error = "Registration deadline cannot be after the End Date!";

    if (!$error) {
        $level = $role;

        if ($edit_mode) {
            if ($role === 'sk') {
                $stmt = $conn->prepare("UPDATE events SET title=?, description=?, details=?, start_date=?, start_time=?, end_date=?, end_time=?, venue=?, category=?, max_participants=?, image=?, cutoff_policy=?, registration_deadline=? WHERE id=? AND sk_id=?");
                $stmt->bind_param("sssssssssssssii",
                    $title, $description, $details, $start_date, $start_time, $end_date, $end_time,
                    $venue, $category, $max_participants, $imagePath, $cutoff_policy, $registration_deadline,
                    $event_id, $user_id
                );
            } else {
                $stmt = $conn->prepare("UPDATE events SET title=?, description=?, details=?, start_date=?, start_time=?, end_date=?, end_time=?, venue=?, category=?, max_participants=?, image=?, cutoff_policy=?, registration_deadline=? WHERE id=? AND admin_id=?");
                $stmt->bind_param("sssssssssssssii",
                    $title, $description, $details, $start_date, $start_time, $end_date, $end_time,
                    $venue, $category, $max_participants, $imagePath, $cutoff_policy, $registration_deadline,
                    $event_id, $user_id
                );
            }
        } else {
            if ($role === 'sk') {
                $stmt = $conn->prepare("INSERT INTO events (title, description, details, start_date, start_time, end_date, end_time, venue, category, max_participants, image, cutoff_policy, level, municipality, barangay, sk_id, registration_deadline) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssssssssis",
                    $title, $description, $details, $start_date, $start_time, $end_date, $end_time,
                    $venue, $category, $max_participants, $imagePath, $cutoff_policy,
                    $level, $municipality, $barangay, $user_id, $registration_deadline
                );
            } else {
                $stmt = $conn->prepare("INSERT INTO events (title, description, details, start_date, start_time, end_date, end_time, venue, category, max_participants, image, cutoff_policy, level, municipality, admin_id, registration_deadline) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssssssssis",
                    $title, $description, $details, $start_date, $start_time, $end_date, $end_time,
                    $venue, $category, $max_participants, $imagePath, $cutoff_policy,
                    $level, $municipality, $user_id, $registration_deadline
                );
            }
        }

        if ($stmt->execute()) {
            $_SESSION['flash'] = $edit_mode ? "Event updated successfully!" : "Event created successfully!";
            header("Location: CreateActivity.php");
            exit();
        } else {
            $error = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

if ($role === 'sk' || $role === 'admin') {
    $stmt = $conn->prepare("
        SELECT * FROM events
        WHERE 
            (level = 'sk' AND sk_id = ?)
            OR (level = 'barangay' AND barangay = ?)
            OR (level = 'municipal' AND municipality = ?)
            OR (level = 'admin' AND admin_id = ?)
        ORDER BY start_date DESC
    ");
    $stmt->bind_param("isss", $user_id, $barangay, $municipality, $user_id);
    $stmt->execute();
    $events = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $edit_mode ? "Edit Activity" : "Create Activity" ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { background-color: #f8f9fa; }
.main-wrapper { display: flex; min-height: 100vh; }
.sidebar { width: 250px; flex-shrink: 0; }
.main-content { flex-grow: 1; padding: 30px; }
.card { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
#imagePreview { display:none; max-height:200px; margin-top:10px; }
</style>
</head>
<body>
<div class="container py-4">
    <?php if ($role === 'admin'): ?>
        <link rel="stylesheet" href="../assets/css/admin_sidebar.css">
    <?php elseif ($role === 'sk'): ?>
        <link rel="stylesheet" href="../assets/css/sk_sidebar.css">
    <?php endif; ?>

    <div class="main-wrapper">
        <div class="sidebar">
            <?php if ($role === 'sk'): ?>
                 <?php include __DIR__ . '/../includes/SkSidebar.php'; ?>
            <?php else: ?>
               <?php include __DIR__ . '/../includes/AdminSidebar.php'; ?>
            <?php endif; ?>
        </div>

        <div class="main-content">
            <div class="container-fluid">
                
                <h2 class="mb-4"><?= $edit_mode ? "Edit Activity" : "Create New Activity" ?></h2>
                <?php if (!empty($error)): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            Swal.fire({
                                title: "Error",
                                text: <?= json_encode($error) ?>,
                                icon: "error"
                            });
                        });
                    </script>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="card p-4" id="activityForm">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($event_id) ?>">

                    <div class="mb-3">
                        <label class="form-label">Activity Title:</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($event['title'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description:</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Details:</label>
                        <textarea name="details" class="form-control" rows="2"><?= htmlspecialchars($event['details'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Start Date:</label>
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($event['start_date'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Start Time:</label>
                            <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars($event['start_time'] ?? '08:00') ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>End Date:</label>
                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($event['end_date'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>End Time:</label>
                            <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars($event['end_time'] ?? '17:00') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Venue:</label>
                        <input type="text" name="venue" class="form-control" value="<?= htmlspecialchars($event['venue'] ?? '') ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Max Participants:</label>
                            <input type="number" name="max_participants" class="form-control" min="1" value="<?= htmlspecialchars($event['max_participants'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Registration Deadline:</label>
                            <input type="date" name="registration_deadline" class="form-control" value="<?= htmlspecialchars($event['registration_deadline'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Category</label>
                        <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($event['category'] ?? '') ?>" placeholder="Enter category" required>
                    </div>

                    <div class="mb-3">
                        <label>Event Image:</label>
                        <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">
                        <?php if (!empty($event['image'])): ?>
                            <img src="<?= htmlspecialchars($event['image']) ?>" id="imagePreview" class="img-fluid rounded shadow" style="display:block; max-height:200px; margin-top:10px;">
                        <?php else: ?>
                            <img id="imagePreview" class="img-fluid rounded shadow" style="display:none;">
                        <?php endif; ?>
                    </div>

                    <?php if ($role==='sk'): ?>
                        <div class="alert alert-info">
                            Barangay-level activity for <?= htmlspecialchars($barangay) ?>, <?= htmlspecialchars($municipality) ?>.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Municipal-level activity for <?= htmlspecialchars($municipality) ?> (all barangays).
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="submitBtn"><?= $edit_mode ? "Update Activity" : "Create Activity" ?></button>
                        <?php if ($edit_mode): ?>
                            <a href="CreateActivity.php" class="btn btn-secondary" id="cancelEditBtn">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>

                <hr class="my-5">
                <h3>Manage Your Events</h3>
                <?php if (!empty($_SESSION['flash'])): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            Swal.fire({
                                title: "Success",
                                text: <?= json_encode($_SESSION['flash']) ?>,
                                icon: "success",
                                timer: 2200,
                                showConfirmButton: false
                            });
                        });
                    </script>
                    <?php unset($_SESSION['flash']); ?>
                <?php endif; ?>

                <table class="table table-bordered table-striped mt-3">
                    <thead>
                        <tr><th>Title</th><th>Start</th><th>End</th><th>Venue</th><th>Category</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($events->num_rows > 0): ?>
                            <?php while ($row = $events->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['start_date']) ?></td>
                                    <td><?= htmlspecialchars($row['end_date']) ?></td>
                                    <td><?= htmlspecialchars($row['venue']) ?></td>
                                    <td><?= htmlspecialchars($row['category']) ?></td>
                                    <td>
                                        <a href="CreateActivity.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Update</a>
                                        <a href="CreateActivity.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger delete-btn" data-id="<?= $row['id'] ?>">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No events created yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

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
    if (result.isConfirmed) {
      window.location.href = "../auth/Logout.php";
    }
  });
});
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');
    const deadline = document.querySelector('input[name="registration_deadline"]');
    const form = document.getElementById('activityForm');
    const submitBtn = document.getElementById('submitBtn');
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const cancelEditBtn = document.getElementById('cancelEditBtn');

    imageInput?.addEventListener('change', () => {
        const file = imageInput.files[0];
        if (file) {
            imagePreview.src = URL.createObjectURL(file);
            imagePreview.style.display = 'block';
        } else imagePreview.style.display = 'none';
    });

    function showError(title, text) {
        Swal.fire({
            title: title || 'Error',
            text: text || '',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
    }
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Cancel edit?',
                text: 'Your unsaved changes will be lost.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, cancel',
                cancelButtonText: 'Continue editing'
            }).then((res) => {
                if (res.isConfirmed) {
                    window.location.href = this.href;
                }
            });
        });
    }

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.href;
            Swal.fire({
                title: 'Delete this event?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const startVal = startDate.value;
        const endVal = endDate.value;
        const deadlineVal = deadline.value;

        if (!startVal || !endVal) {
            showError('Validation', 'Start and End dates are required.');
            return;
        }

        if (endVal < startVal) {
            showError('Validation', 'End Date cannot be earlier than Start Date!');
            return;
        }

        if (deadlineVal && (deadlineVal > endVal)) {
            showError('Validation', 'Registration deadline cannot be after the End Date!');
            return;
        }

        Swal.fire({
            title: <?= json_encode($edit_mode ? 'Update this event?' : 'Create this event?') ?>,
            text: <?= json_encode($edit_mode ? 'Changes will be saved.' : 'This will create a new activity.') ?>,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: <?= json_encode($edit_mode ? 'Yes, update' : 'Yes, create') ?>,
            cancelButtonText: 'Cancel'
        }).then((res) => {
            if (res.isConfirmed) {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                        form.submit();
                    }
                });
            }
        });
    });
</script>

</body>
</html>
