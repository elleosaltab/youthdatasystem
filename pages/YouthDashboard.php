<?php   
        declare(strict_types=1);
        session_start();
        require_once __DIR__ . '/../config/db.php';
        require_once __DIR__ . '/../auth/events_helper.php';


        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'youth') {
            header("Location: ../pages/Login.html");
            exit();
        }

        $user_id     = (int)$_SESSION['user_id'];
        $email       = $_SESSION['email'] ?? '';
        $barangay    = $_SESSION['barangay'] ?? '';
        $municipality= $_SESSION['municipality'] ?? '';
        $today       = date('Y-m-d');


        $profile = [];
        if ($email !== '') {
            $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, barangay, municipality, 
                    birth_date, phone, gender
                FROM kk_members 
                WHERE email = ? 
                LIMIT 1
            ");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $profile = $stmt->get_result()->fetch_assoc() ?? [];
            $stmt->close();
        }


        if (!empty($profile['barangay'])) {
            $barangay = $profile['barangay'];
        }
        if (!empty($profile['municipality'])) {
            $municipality = $profile['municipality'];
        }

        $searchRaw = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        $search    = '%' . $searchRaw . '%';
        $eventType = $_GET['event_type'] ?? 'all'; 
        $status    = $_GET['status'] ?? 'all';


        $activities = [];

       if ($barangay && $municipality) {

    $query = "
        SELECT 
            id, title, description, start_date, end_date, start_time, end_time,
            venue, level, barangay, municipality, category, max_participants, image
        FROM events
        WHERE municipality = ?
    ";

    $params = [$municipality];
    $types  = "s";

    // EVENT TYPE FILTER
    if ($eventType === 'barangay') {
        $query .= " AND barangay = ?";
        $params[] = $barangay;
        $types   .= "s";

    } elseif ($eventType === 'municipal') {
        $query .= " AND (barangay IS NULL OR barangay = '')";

    } else {
        $query .= " AND (
            barangay = ?
            OR barangay IS NULL
            OR barangay = ''
        )";
        $params[] = $barangay;
        $types   .= "s";
    }

    if ($status === 'pending') {
        $query .= " AND CONCAT(start_date,' ',start_time) > NOW()";

    } elseif ($status === 'ongoing') {
        $query .= " AND NOW() BETWEEN CONCAT(start_date,' ',start_time)
                             AND CONCAT(end_date,' ',end_time)";

    } elseif ($status === 'closed') {
        $query .= " AND CONCAT(end_date,' ',end_time) < NOW()";
    }

  
    if (!empty($searchRaw)) {
        $query .= " AND (
            title LIKE ?
            OR description LIKE ?
            OR venue LIKE ?
            OR category LIKE ?
        )";
        $params = array_merge($params, [$search, $search, $search, $search]);
        $types .= "ssss";
    }

    $query .= " ORDER BY start_date ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}


?>
<!DOCTYPE html>
    <html lang="en">
                <head>
                    <meta charset="UTF-8">
                        <title>Youth Dashboard</title>
                            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
                            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <style>
<?= file_get_contents(__DIR__ . '/../assets/css/dashboard.css') ?>
                    .card {
                    border-radius: 15px;
                    overflow: hidden;
                    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                    margin-bottom: 20px;
            }
                    .card-img {
                    max-height: 220px;
                    object-fit: cover;
                    width: 100%;
                    display: block;
            }
                    .card-body h3 {
                        font-size: 1.3rem;
                        font-weight: 700;
            }
                    .card-body p {
                        margin-bottom: 8px;
            }
                    .badge {
                        font-size: 0.9rem;
            }
        .cards-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    padding: 1rem;
}

.cards-row::-webkit-scrollbar {
    display: none;
}
        .fancy-card-row {
            flex:0 0 300px;
            background:#fff;
            border-radius:15px;
            overflow:hidden;
            box-shadow:0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display:flex;
            flex-direction:column;
        }
        .fancy-card-row:hover {
            transform: translateY(-5px);
            box-shadow:0 12px 25px rgba(0,0,0,0.15);
        }
        .card-image {
            position:relative;
            height:180px;
            overflow:hidden;
        }
        .card-image img {
            width:100%;
            height:100%;
            object-fit:cover;
            transition: transform 0.5s;
        }
        .fancy-card-row:hover .card-image img {
            transform: scale(1.05);
        }
        .card-overlay {
            position:absolute;
            bottom:0;
            width:100%;
            background: linear-gradient(180deg, transparent, rgba(0,0,0,0.7));
            color:#fff;
            padding:0.6rem;
        }
        .card-overlay h4 {
            margin:0;
            font-size:1rem;
            font-weight:bold;
        }
        .card-overlay p {
            margin:0;
            font-size:0.75rem;
        }
        .card-content {
            padding:0.8rem;
            flex:1;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
        }
        .card-content p {
            font-size:0.85rem;
            color:#555;
        }
        .card-meta {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:0.5rem;
        }
        .card-meta span {
            font-size:0.75rem;
            color:#777;
        }
        .btn-primary {
            background:#007bff;
            border:none;
            border-radius:8px;
            padding:0.4rem 0;
            font-weight:bold;
            transition: background 0.3s;
            font-size:0.85rem;
        }
        .btn-primary:hover {
            background:#0056b3;
        }
                </style>
            </head>
        <body>


           <?php include __DIR__ . '/../includes/YouthSidebar.php'; ?>

            <div class="main">
            <div class="topbar">
  <form method="GET" class="search-bar d-flex gap-2 align-items-center">

    <input type="text"
           name="search"
           placeholder="Search activities..."
           value="<?= htmlspecialchars($searchRaw) ?>"
           class="form-control">

    <select name="status" class="form-select" style="max-width:160px;">
    <option value="all" <?= ($_GET['status'] ?? 'all') === 'all' ? 'selected' : '' ?>>
      All Status
    </option>
    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>
      Pending
    </option>
    <option value="ongoing" <?= ($_GET['status'] ?? '') === 'ongoing' ? 'selected' : '' ?>>
      Ongoing
    </option>
    <option value="closed" <?= ($_GET['status'] ?? '') === 'closed' ? 'selected' : '' ?>>
      Closed
    </option>
  </select>

    <select name="event_type" class="form-select" style="max-width:180px;">
      <option value="all" <?= ($_GET['event_type'] ?? 'all') === 'all' ? 'selected' : '' ?>>
        All Events
      </option>
      <option value="barangay" <?= ($_GET['event_type'] ?? '') === 'barangay' ? 'selected' : '' ?>>
        Barangay Events
      </option>
      <option value="municipal" <?= ($_GET['event_type'] ?? '') === 'municipal' ? 'selected' : '' ?>>
        Municipal Events
      </option>
    </select>

    <button type="submit" class="btn btn-outline-primary">
      Search
    </button>
  </form>
</div>
        <div class="cards-row" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem; padding:1rem;">
        <?php if (!empty($activities)): ?>
        <?php foreach ($activities as $activity): ?>
            <?php
            $now = new DateTime();
            $startDateTime = new DateTime($activity['start_date'] . ' ' . $activity['start_time']);
            $endDateTime   = new DateTime($activity['end_date'] . ' ' . $activity['end_time']);

        
            $stmtCheck = $conn->prepare("SELECT 1 FROM event_participants WHERE event_id=? AND kk_member_id=? LIMIT 1");
            $stmtCheck->bind_param("ii", $activity['id'], $user_id);
            $stmtCheck->execute();
            $isRegistered = $stmtCheck->get_result()->num_rows > 0; 
            $stmtCheck->close();

            
            $stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM event_participants WHERE event_id=?");
            $stmtCount->bind_param("i", $activity['id']);
            $stmtCount->execute();
            $countResult = $stmtCount->get_result()->fetch_assoc();
            $participantCount = $countResult['total'] ?? 0;
            $stmtCount->close();


            $stmtRating = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_ratings 
                                        FROM event_ratings WHERE event_id=?");
            $stmtRating->bind_param("i", $activity['id']);
            $stmtRating->execute();
            $ratingData = $stmtRating->get_result()->fetch_assoc();
            $avgRating = round((float)($ratingData['avg_rating'] ?? 0), 1);
            $totalRatings = $ratingData['total_ratings'] ?? 0;
            $stmtRating->close();

            $stmtRated = $conn->prepare("SELECT 1 FROM event_ratings WHERE event_id=? AND kk_member_id=? LIMIT 1");
            $stmtRated->bind_param("ii", $activity['id'], $user_id);
            $stmtRated->execute();
            $alreadyRated = $stmtRated->get_result()->num_rows > 0;
            $stmtRated->close();

            $maxParticipants = $activity['max_participants'] ?? '∞';
            $imageFile = $activity['image'] ?? '';
            $isClosed = $now > $endDateTime;
            $isOngoing = $now >= $startDateTime && $now <= $endDateTime;
            ?>
  

                <div class="fancy-card-row">
                    <?php if (!empty($imageFile)): ?>
                    <div class="card-image">
                        <img src="../<?= htmlspecialchars($imageFile) ?>" alt="<?= htmlspecialchars($activity['title']) ?>">
                        <div class="card-overlay">
                            <h4><?= htmlspecialchars($activity['title']) ?></h4>
                            <p>📅 <?= htmlspecialchars($activity['start_date']) ?> - <?= htmlspecialchars($activity['end_date']) ?></p>
                            🕒 <?= htmlspecialchars($activity['start_time']) ?> - <?= htmlspecialchars($activity['end_time']) ?><br>
                        </div>
 
                   </div>
                    <?php endif; ?>

                    <div class="card-content" 
                        data-start-datetime="<?= htmlspecialchars($activity['start_date'].'T'.$activity['start_time']) ?>"
                        data-end-datetime="<?= htmlspecialchars($activity['end_date'].'T'.$activity['end_time']) ?>"
                        data-activity-id="<?= (int)$activity['id'] ?>"
                        data-already-rated="<?= $alreadyRated ? '1' : '0' ?>"
                        data-is-registered="<?= $isRegistered ? '1' : '0' ?>">


                        <p><?= nl2br(htmlspecialchars($activity['description'])) ?></p>
                        <div class="card-meta">
                            <span class="badge bg-warning"><?= htmlspecialchars((string)$activity['category']) ?></span>
                            <span>📍 <?= htmlspecialchars($activity['venue']) ?></span>
                        </div>
                    <div class="mt-2">
                            <small class="text-muted">Participants: <?= $participantCount ?> / <?= $maxParticipants ?></small><br>
                            <small class="star-rating">⭐ <?= $avgRating ?>/5 (<?= $totalRatings ?>)</small>
                        </div>

                        <?php if ($isClosed && $isRegistered): ?>
                            <button class="btn btn-secondary w-100 mt-2" disabled>Closed</button>
                            <?php if (!$alreadyRated): ?>
                                <button class="btn btn-warning w-100 mt-2 rate-btn"
                                        data-activity-id="<?= (int)$activity['id'] ?>"
                                        data-bs-toggle="modal" data-bs-target="#rateModal"
                                        onclick="setRateActivityId(<?= (int)$activity['id'] ?>)">
                                    Rate Event
                                </button>
                            <?php else: ?>
                                <button class="btn btn-success w-100 mt-2" disabled>Rated</button>
                            <?php endif; ?>

                        <?php elseif ($isOngoing && $isRegistered): ?>
                            <form method="GET" action="../actions/show_qr.php" class="mt-2">
                                <input type="hidden" name="event_id" value="<?= (int)$activity['id'] ?>">
                                <button type="submit" class="btn btn-info w-100">Show QR</button>
                            </form>

                        <?php elseif ($isRegistered): ?>
                            <button class="btn btn-success w-100 mt-2" disabled>Registered</button>

                        <?php else: ?>
                          
                            <form method="POST" action="../actions/JoinActivity.php" class="mt-2">
                                <input type="hidden" name="activity_id" value="<?= (int)$activity['id'] ?>">
                                <button type="button" class="btn btn-primary w-100 join-btn"
                                        data-activity-id="<?= (int)$activity['id'] ?>"
                                        data-bs-toggle="modal" data-bs-target="#joinModal">
                                    Pre Register
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty" style="text-align:center; color:#777;">No activities found.</div>
        <?php endif; ?>
        </div>


        <div class="modal fade" id="joinModal" tabindex="-1" aria-labelledby="joinModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="joinForm" method="POST" action="../actions/JoinActivity.php">
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title" id="joinModalLabel">Join Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                <input type="hidden" name="activity_id" id="modal_activity_id">

            
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" 
                        class="form-control" 
                        name="full_name" 
                        value="<?= htmlspecialchars(trim(($profile['first_name'] ?? '') . ' ' . ($profile['middle_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''))) ?>" 
                        readonly>
                </div>

            
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" 
                        name="email" 
                        class="form-control" 
                        value="<?= htmlspecialchars($profile['email'] ?? '') ?>" 
                        readonly>
                </div>

        
                <div class="mb-3">
                    <label class="form-label">Barangay</label>
                    <input type="text" 
                        class="form-control" 
                        name="barangay" 
                        value="<?= htmlspecialchars($profile['barangay'] ?? '') ?>" 
                        readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Municipality</label>
                    <input type="text" 
                        class="form-control" 
                        name="municipality" 
                        value="<?= htmlspecialchars($profile['municipality'] ?? '') ?>" 
                        readonly>
                </div>


                <div class="mb-3">
                    <label class="form-label">Birth Date</label>
                    <input type="date" 
                        class="form-control" 
                        name="birth_date" 
                        value="<?= htmlspecialchars($profile['birth_date'] ?? '') ?>" 
                        readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" 
                        class="form-control" 
                        name="phone" 
                        value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" 
                        readonly>
                </div>  

                <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <input type="text" 
                        class="form-control" 
                        name="gender" 
                        value="<?= htmlspecialchars($profile['gender'] ?? '') ?>" 
                        readonly>
                </div>
                </div>

                <div class="modal-footer">
                <small class="text-muted me-auto">Please review your info before joining</small>
                <button type="submit" class="btn btn-primary">Confirm Join</button>
                </div>
            </div>
            </form>
        </div>
        </div>
        <div class="modal fade" id="rateModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form id="rateForm" method="POST" action="../actions/SubmitRating.php">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Rate Event</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="activity_id" id="rate_activity_id"> 
                            <div class="mb-3">
                        <label class="form-label">Rating (1–5)</label>
                        <select name="rating" id="ratingNumber" class="form-select" required>
                          <option value="">Select rating</option>
                          <option value="1">1</option>
                          <option value="2">2</option>
                          <option value="3">3</option>
                          <option value="4">4</option>
                          <option value="5">5</option>
                        </select>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">Star Preview</label>
                        <div id="starPreview" style="font-size:1.5rem; color:#f1c40f;">
                          ☆☆☆☆☆
                        </div>
                      </div>
                            <div class="mb-3">
                                <label class="form-label">Feedback (optional)</label>
                                <textarea name="feedback" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-warning">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

function setRateActivityId(id) {
  document.getElementById('rate_activity_id').value = id;
}

function showAlert(message, icon = "success") {
  Swal.fire({
    title: icon === "success" ? "Success!" : "Notice",
    text: message,
    icon: icon,
    confirmButtonColor: "#3085d6"
  });
}

function hideModal(id) {
  const modalEl = document.getElementById(id);
  const modal = bootstrap.Modal.getInstance(modalEl);
  if (modal) modal.hide();
}

document.getElementById('rateForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');

  submitBtn.disabled = true;
  submitBtn.textContent = "Submitting...";

  try {
    const response = await fetch(form.action, { method: 'POST', body: formData });
    const data = await response.json();

    if (data.status === 'success') {
      hideModal('rateModal');

      const activityId = formData.get('activity_id');
      const cardContent = document.querySelector(`.card-content[data-activity-id="${activityId}"]`);
      const rateBtn = cardContent?.querySelector('.rate-btn');

      if (rateBtn) {
        rateBtn.textContent = 'Rated';
        rateBtn.classList.remove('btn-warning');
        rateBtn.classList.add('btn-success');
        rateBtn.disabled = true;
      }

      Swal.fire({
        title: "Thank you!",
        text: "Your rating has been successfully submitted.",
        icon: "success",
        timer: 2000,
        showConfirmButton: false
      });
    } else {
      showAlert(data.message || "Failed to submit rating.", "error");
    }
  } catch (error) {
    console.error(error);
    showAlert("Something went wrong. Please try again.", "error");
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = "Submit";
  }
});

document.querySelectorAll('.join-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('modal_activity_id').value = btn.dataset.activityId;
  });
});

document.getElementById('joinForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');

  submitBtn.disabled = true;
  submitBtn.textContent = "Joining...";

  try {
    const response = await fetch(form.action, { method: 'POST', body: formData });
    const data = await response.json();

    if (data.status === 'success') {
      hideModal('joinModal');

      const activityId = formData.get('activity_id');
      const cardContent = document.querySelector(`.card-content[data-activity-id="${activityId}"]`);
      const joinBtn = cardContent?.querySelector('.join-btn');

      if (joinBtn) {
        joinBtn.textContent = 'Registered';
        joinBtn.classList.replace('btn-primary', 'btn-success');
        joinBtn.disabled = true;
      }

      Swal.fire({
        title: "Joined Successfully!",
        text: "You have registered for this event.",
        icon: "success",
        timer: 2000,
        showConfirmButton: false
      });
    } else {
      showAlert(data.message || "Failed to join.", "error");
    }
  } catch (error) {
    console.error(error);
    showAlert("Something went wrong. Please try again.", "error");
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = "Confirm Join";
  }
});

function updateActivityButtons() {
  const now = new Date();

  document.querySelectorAll('.card-content').forEach(card => {
    const start = new Date(card.dataset.startDatetime);
    const end = new Date(card.dataset.endDatetime);
    const activityId = card.dataset.activityId;
    const existingBtn = card.querySelector('button');

    if (!existingBtn) return;

    if (now > end) {
      existingBtn.textContent = 'Closed';
      existingBtn.className = 'btn btn-secondary w-100 mt-2';
      existingBtn.disabled = true;

      const isRegistered = card.textContent.includes('Registered') || card.textContent.includes('Show QR') || card.dataset.isRegistered === '1';
      const isRated = card.dataset.alreadyRated === '1' || !!card.querySelector('.btn-success');
      const hasRateBtn = !!card.querySelector('.rate-btn');

      if (isRegistered && !isRated && !hasRateBtn) {
        const rateButton = document.createElement('button');
        rateButton.className = 'btn btn-warning w-100 mt-2 rate-btn';
        rateButton.textContent = 'Rate Event';
        rateButton.dataset.activityId = activityId;
        rateButton.setAttribute('data-bs-toggle', 'modal');
        rateButton.setAttribute('data-bs-target', '#rateModal');
        rateButton.addEventListener('click', () => setRateActivityId(activityId));
        card.appendChild(rateButton);
      } else if (isRegistered && isRated && !card.querySelector('.btn-success')) {
        const ratedBtn = document.createElement('button');
        ratedBtn.className = 'btn btn-success w-100 mt-2';
        ratedBtn.textContent = 'Rated';
        ratedBtn.disabled = true;
        card.appendChild(ratedBtn);
      }
    } else if (now >= start && now <= end) {
      const isRegistered = existingBtn.textContent.includes('Registered');
      if (isRegistered && !card.querySelector('.qr-btn')) {
        const qrBtn = document.createElement('button');
        qrBtn.type = 'button';
        qrBtn.className = 'btn btn-info w-100 mt-2 qr-btn';
        qrBtn.textContent = 'Show QR';
        qrBtn.addEventListener('click', () => showQrPopup(activityId));
        card.appendChild(qrBtn);
      }
    }
  });
}

const ratingSelect = document.getElementById('ratingNumber');
const starPreview = document.getElementById('starPreview');

if (ratingSelect) {
  ratingSelect.addEventListener('change', () => {
    const value = parseInt(ratingSelect.value || 0);
    let stars = '';

    for (let i = 1; i <= 5; i++) {
      stars += i <= value ? '★' : '☆';
    }

    starPreview.textContent = stars;
  });
}


async function showQrPopup(activityId) {
  try {
    const response = await fetch(`../actions/show_qr.php?event_id=${activityId}`);
    if (!response.ok) throw new Error('QR not found');
    const blob = await response.blob();
    const qrUrl = URL.createObjectURL(blob);

    Swal.fire({
      title: "Your Event QR",
      html: `
        <img src="${qrUrl}" alt="QR Code" style="width:220px;height:220px;margin-bottom:15px;">
        <br>
        <a href="${qrUrl}" download="EventQR_${activityId}.png" class="btn btn-success">
          Download QR
        </a>
      `,
      showConfirmButton: false,
      showCloseButton: true,
      width: 350
    });
  } catch (error) {
    Swal.fire("Error", "Unable to load QR code.", "error");
  }
}

updateActivityButtons();
setInterval(updateActivityButtons, 5000);
</script>
 </body>
 </html>