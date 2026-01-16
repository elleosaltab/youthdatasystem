
document.addEventListener("DOMContentLoaded", () => {
  const logoutBtn = document.getElementById("logoutBtn");
  const modal = document.getElementById("logoutModal");
  const confirmBtn = document.getElementById("confirmLogout");
  const cancelBtn = document.getElementById("cancelLogout");

  logoutBtn.addEventListener("click", (e) => {
    e.preventDefault();
    modal.style.display = "flex";
  });

  cancelBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  confirmBtn.addEventListener("click", () => {
    window.location.href = "../auth/Logout.php";
  });
});

