
document.addEventListener("DOMContentLoaded", () => {
  const savedEmail = localStorage.getItem("savedEmail");
  if (savedEmail) {
    const emailInput = document.getElementById("loginEmail");
    if (emailInput) emailInput.value = savedEmail;
  }
});

document.getElementById("loginForm").addEventListener("submit", (e) => {
  const email = document.getElementById("loginEmail").value;
  localStorage.setItem("savedEmail", email);
});

    function togglePassword(id, btn) {
      const input = document.getElementById(id);
      if (input.type === "password") {
        input.type = "text";
        btn.textContent = "Hide";
      } else {
        input.type = "password";
        btn.textContent = "Show";
      }
    }

     document.addEventListener("DOMContentLoaded", () => {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get("registered") === "1") {
        const notif = document.getElementById("notification");
        notif.textContent = "✅ Registration successful! Please check your email to verify before logging in.";
        notif.style.display = "block";
        notif.style.background = "#2ecc71";
        notif.style.color = "#fff";
      }
     });

    window.togglePassword = function(id, btn) {
      const field = document.getElementById(id);
      if (field.type === "password") {
        field.type = "text";
        btn.textContent = "Hide";
      } else {
        field.type = "password";
        btn.textContent = "Show";
      }
    };
const toggleBtn = document.getElementById("themeToggle");
const themeLink = document.getElementById("themeStyle");

let isLight = false;

if (localStorage.getItem("theme") === "light") {
  themeLink.href = "../assets/css/log.light.css";
  toggleBtn.textContent = "☀️";
  isLight = true;
}

toggleBtn.addEventListener("click", () => {
  if (isLight) {
    themeLink.href = "../assets/css/log.css";
    toggleBtn.textContent = "🌙";
    localStorage.setItem("theme", "dark");
  } else {
    themeLink.href = "../assets/css/log.light.css";
    toggleBtn.textContent = "☀️";
    localStorage.setItem("theme", "light");
  }
  isLight = !isLight;
});


