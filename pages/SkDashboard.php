  <?php 
    session_start();
    require_once __DIR__ . '/../config/db.php';


    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sk') {
        header("Location: ../pages/Login.html");
        exit();
    }

    $barangay   = $_SESSION['barangay'] ?? '';
    $email      = $_SESSION['email'] ?? '';
    $sk_initial = strtoupper(substr($email, 0, 1));
    $message    = "";


  $skRes = $conn->prepare("SELECT id, first_name, last_name, email, barangay, municipality, position, status, auth_file 
                          FROM sk_officials
                          WHERE municipality=? 
                          ORDER BY barangay");
  $municipality = $_SESSION['municipality'] ?? '';
  $skRes->bind_param("s", $municipality);
  $skRes->execute();
  $skList = $skRes->get_result();


  $section = $_GET['section'] ?? 'create';
  $selected_event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;

  $sk_barangay = $_SESSION['barangay'] ?? null;
  $sk_municipality = $_SESSION['municipality'] ?? null;
  $sk_id = $_SESSION['user_id'] ?? 0;

  if (empty($sk_barangay) || empty($sk_municipality)) {
      $tmp = $conn->prepare("SELECT barangay, municipality FROM sk_officials WHERE email=? OR id=? LIMIT 1");
      $tmp->bind_param("si", $email, $sk_id);
      $tmp->execute();
      $tmp->bind_result($b_from_sk, $m_from_sk);
      if ($tmp->fetch()) {
          if (empty($sk_barangay)) $sk_barangay = $b_from_sk;
          if (empty($sk_municipality)) $sk_municipality = $m_from_sk;
      }
      $tmp->close();
  }



  if ((empty($sk_barangay) || empty($sk_municipality)) && !empty($_SESSION['email'])) {
      $email = $_SESSION['email'];
      $tmp2 = $conn->prepare("SELECT barangay, municipality FROM sk_officials WHERE email = ? LIMIT 1");
      $tmp2->bind_param("s", $email);
      $tmp2->execute();
      $tmp2->bind_result($b_from_sk, $m_from_sk);
      if ($tmp2->fetch()) {
          if (empty($sk_barangay)) $sk_barangay = $b_from_sk;
          if (empty($sk_municipality)) $sk_municipality = $m_from_sk;
      }
      $tmp2->close();
  }


  $sk_barangay = $sk_barangay ?? '';
  $sk_municipality = $sk_municipality ?? '';
  $location_available = ($sk_barangay !== '' && $sk_municipality !== '');


  $sk_name = $_SESSION['fullname'] ?? 'SK Official';
  $sk_initial = strtoupper(substr($sk_name, 0, 1));


  $sk_barangay = $_SESSION['barangay'] ?? '';
  $sk_municipality = $_SESSION['municipality'] ?? '';


  $sk_barangay = $_SESSION['barangay'] ?? '';
  $sk_municipality = $_SESSION['municipality'] ?? '';

  $pending_count = 0;
  $approved_count = 0;
  $pending_res = null;
  $registered_res = null;

  if ($location_available) {

      $stmt = $conn->prepare("
          SELECT COUNT(*) AS total 
          FROM kk_members 
          WHERE barangay=? AND municipality=? 
          AND approval_status='pending'
      ");
      $stmt->bind_param("ss", $sk_barangay, $sk_municipality);
      $stmt->execute();
      $pending_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
      $stmt->close();
      $stmt = $conn->prepare("SELECT COUNT(*) AS total 
          FROM kk_members 
          WHERE barangay=? AND municipality=? 
          AND approval_status='approved'
      ");
      $stmt->bind_param("ss", $sk_barangay, $sk_municipality);
      $stmt->execute();
      $approved_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
      $stmt->close();
      $stmt = $conn->prepare("SELECT id, first_name, last_name, email, gender, barangay, municipality, registered_at 
          FROM kk_members 
          WHERE barangay=? AND municipality=? 
          AND approval_status='pending'
          ORDER BY registered_at DESC
      ");
      $stmt->bind_param("ss", $sk_barangay, $sk_municipality);
      $stmt->execute();
      $pending_res = $stmt->get_result();
      $stmt->close();
      $stmt = $conn->prepare("SELECT id, first_name, last_name, email, birth_date, age, gender, barangay, municipality, approved_at 
          FROM kk_members 
          WHERE barangay=? AND municipality=? 
          AND approval_status='approved'
          ORDER BY first_name ASC, last_name ASC
      ");
      $stmt->bind_param("ss", $sk_barangay, $sk_municipality);
      $stmt->execute();
      $registered_res = $stmt->get_result();
      $stmt->close();

    } else {
        $pending_res = null;
        $registered_res = null;
    }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="UTF-8">
  <title>SK Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/sk_sidebar.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
    background-color: #f8f9fa;
    margin: 0;
    font-family: 'Inter', sans-serif;
  }


  .main-content {
    margin-left: 250px;
    padding: 20px;
  }
  </style>
  </head>
  <body>
  <?php include __DIR__ . '/../includes/SkSidebar.php'; ?>
  <main class="main-content">
    <h2 class="mb-4">Pending Youth (<?= htmlspecialchars($sk_barangay . ($sk_municipality ? ', ' . $sk_municipality : '')) ?>)</h2>

    <?php if (!$location_available): ?>
      <div class="alert alert-warning">Your barangay/municipality is not set. Please contact admin or re-login so your location info can be loaded.</div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card shadow-sm text-center p-3 clickable" id="pendingCard">
          <h6 class="text-muted">Pending Youth</h6>
          <h2 class="text-warning"><?= intval($pending_count) ?></h2>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card shadow-sm text-center p-3 clickable" id="registeredCard">
          <h6 class="text-muted">View All Youth Registered</h6>
          <h2 class="text-success"><?= intval($approved_count) ?></h2>
        </div>
      </div>
    </div>

  <div class="card shadow-sm section" id="pendingSection">
      <div class="card-header bg-dark text-white"><h5 class="mb-0">Pending Youth Registrations</h5></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Gender</th>
                <th>Barangay</th>
                <th>Municipality</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($pending_res && $pending_res->num_rows > 0): ?>
                <?php while ($y = $pending_res->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($y['first_name'].' '.$y['last_name']) ?></td>
                  <td><?= htmlspecialchars($y['email']) ?></td>
                  <td><?= htmlspecialchars($y['gender']) ?></td>
                  <td><?= htmlspecialchars($y['barangay']) ?></td>
                  <td><?= htmlspecialchars($y['municipality']) ?></td>
                  <td>
                  <form method="post" action="../auth/ProcessYouth.php" style="display:inline">
                    <input type="hidden" name="id" value="<?= intval($y['id']) ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="button" class="btn btn-success btn-approve" data-id="<?= intval($y['id']) ?>">Approve</button>
                  </form>
                  <form method="post" action="../auth/ProcessYouth.php" style="display:inline">
                    <input type="hidden" name="id" value="<?= intval($y['id']) ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="button" class="btn btn-danger btn-reject" data-id="<?= intval($y['id']) ?>">Reject</button>
                  </form>
                </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="6" class="text-center">No pending youth found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="card shadow-sm hidden-section section" id="registeredSection">
      <div class="mb-2 text-end">
        <a href="export_youth_sk.php" class="btn btn-success btn-sm">Export Youth List (Excel)</a>
      </div>
      <div class="card-header bg-dark text-white"><h5 class="mb-0">All Registered Youth</h5></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Barangay</th>
                <th>Municipality</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($registered_res && $registered_res->num_rows > 0): ?>
                <?php while ($y = $registered_res->fetch_assoc()):
                  $fullname = htmlspecialchars(trim($y['first_name'].' '.$y['last_name'] ));
                  $age_display = !empty($y['age']) ? intval($y['age']) :
                      (!empty($y['birth_date']) ? ((new DateTime($y['birth_date']))->diff(new DateTime())->y) : 'N/A');
                ?>
                <tr>
                  <td><?= $fullname ?></td>
                  <td><?= htmlspecialchars($y['email']) ?></td>
                  <td><?= $age_display ?></td>
                  <td><?= htmlspecialchars($y['gender']) ?></td>
                  <td><?= htmlspecialchars($y['barangay']) ?></td>
                  <td><?= htmlspecialchars($y['municipality']) ?></td>
                  <td>
                  <form method="post" action="../auth/ProcessYouth.php" style="display:inline">
                    <input type="hidden" name="id" value="<?= intval($y['id']) ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="button" class="btn btn-danger btn-sm btn-delete">Delete</button>
                  </form>
                </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="7" class="text-center">No registered youth found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>

 <script>
  const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
  logoutBtn.addEventListener('click', function (e) {
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
}

document.getElementById("pendingCard").addEventListener("click", () => {
  document.getElementById("pendingSection").style.display = "block";
  document.getElementById("registeredSection").style.display = "none";    
});
document.getElementById("registeredCard").addEventListener("click", () => {
  document.getElementById("pendingSection").style.display = "none";
  document.getElementById("registeredSection").style.display = "block";
});

async function processYouth(action, id) {
  try {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', action);

    const res = await fetch('../auth/ProcessYouth.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.status === 'success') {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: data.message,
        timer: 1500,
        showConfirmButton: false
      }).then(() => window.location.reload(true));
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: data.message
      });
    }
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Server Error',
      text: err.message
    });
  }
}

document.querySelectorAll('.btn-approve').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;
    Swal.fire({
      title: 'Approve this youth?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Approve'
    }).then(r => {
      if (r.isConfirmed) processYouth('approve', id);
    });
  });
});

document.querySelectorAll('.btn-reject').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;
    Swal.fire({
      title: 'Reject this youth?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Reject'
    }).then(r => {
      if (r.isConfirmed) processYouth('reject', id);
    });
  });
});


document.querySelectorAll('.btn-delete').forEach(btn => {
  btn.addEventListener('click', e => {
    const id = btn.closest('form').querySelector('input[name="id"]').value;
    Swal.fire({
      title: 'Delete this youth?',
      icon: 'error',
      showCancelButton: true,
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel'
    }).then(result => {
      if (result.isConfirmed) processYouth('delete', id);
    });
  });
});
</script>
  </body>
  </html>
 