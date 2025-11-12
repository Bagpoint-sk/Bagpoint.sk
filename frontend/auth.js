// auth.js – správa prihlásenia, navigácie a odhlásenia

window.addEventListener("DOMContentLoaded", () => {
  const loginBtn = document.querySelector(".login-btn");
  const accountLink = document.querySelector(".account-link");
  const logoutLink = document.querySelector(".logout-link");

  // 🔹 Získať uloženého používateľa
  const userData = localStorage.getItem("user");
  const user = userData ? JSON.parse(userData) : null;

  if (user) {
    // ✅ Používateľ je prihlásený
    if (loginBtn) loginBtn.style.display = "none";
    if (accountLink) {
      accountLink.style.display = "inline-block";
      accountLink.textContent = "Môj účet";
    }
    if (logoutLink) logoutLink.style.display = "inline-block";
  } else {
    // ❌ Nie je prihlásený
    if (loginBtn) loginBtn.style.display = "inline-block";
    if (accountLink) accountLink.style.display = "none";
    if (logoutLink) logoutLink.style.display = "none";
  }

  // 🔹 Odhlásenie
  if (logoutLink) {
    logoutLink.addEventListener("click", async (e) => {
      e.preventDefault();
      try {
        const res = await fetch("../backend/logout.php", {
          method: "POST",
          credentials: "include",
        });
        const data = await res.json();

        if (data.success) {
          localStorage.removeItem("user");
          if (loginBtn) loginBtn.style.display = "inline-block";
          if (accountLink) accountLink.style.display = "none";
          if (logoutLink) logoutLink.style.display = "none";

          window.location.href = "index.html";
        }
      } catch (err) {
        console.error("Chyba pri odhlásení:", err);
      }
    });
  }

  // 🔹 Efekt pri scrollovaní (z tvojho pôvodného auth.js)
  window.addEventListener("scroll", () => {
    const header = document.querySelector("header");
    header?.classList.toggle("scrolled", window.scrollY > 30);
  });
});
