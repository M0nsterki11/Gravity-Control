import { loginRequest, logoutRequest, registerRequest } from "./api.js";
import { clearStoredUser, getCsrfToken, getCurrentUser, setCurrentUser } from "./state/session.js";
import { createTabSwitcher, openModal, closeModal } from "./ui/modal.js";
import { showToast } from "./ui/toast.js";

let authModalEl = null;
let switchTab = () => {};

export function openLoginModal() {
  openAuthModal("login-form");
}

export function openRegisterModal() {
  openAuthModal("register-form");
}

export function openAuthModal(defaultTab = "register-form") {
  if (!authModalEl) return;
  openModal(authModalEl);
  switchTab(defaultTab);
}

function closeAuthModal() {
  closeModal(authModalEl);
}

export function updateAuthUI() {
  const guestContainer = document.getElementById("auth-guest");
  const userContainer = document.getElementById("auth-user");
  const profileBtn = document.getElementById("profile-btn");
  const currentUser = getCurrentUser();

  if (!guestContainer || !userContainer) return;

  if (currentUser) {
    guestContainer.style.display = "none";
    userContainer.style.display = "flex";
    if (profileBtn) {
      const name = currentUser.full_name || currentUser.email || "Profile";
      profileBtn.textContent = name.split(" ")[0];
    }
  } else {
    guestContainer.style.display = "flex";
    userContainer.style.display = "none";
    if (profileBtn) {
      profileBtn.textContent = "Profile";
    }
  }
}

export function clearLocalAuthState() {
  clearStoredUser();
  updateAuthUI();
}

export function setupAuthModal() {
  authModalEl = document.getElementById("auth-modal");
  const loginBtn = document.getElementById("login-btn");
  const registerBtn = document.getElementById("register-btn");
  const heroRegister = document.getElementById("hero-register");
  const modalClose = document.getElementById("modal-close");
  const tabButtons = document.querySelectorAll(".tab-btn");
  const forms = document.querySelectorAll(".auth-form");
  const loginForm = document.getElementById("login-form");
  const registerForm = document.getElementById("register-form");

  switchTab = createTabSwitcher(tabButtons, forms);

  loginBtn?.addEventListener("click", () => openLoginModal());
  registerBtn?.addEventListener("click", () => openRegisterModal());
  heroRegister?.addEventListener("click", () => openRegisterModal());
  modalClose?.addEventListener("click", closeAuthModal);

  tabButtons.forEach((btn) => {
    btn.addEventListener("click", () => switchTab(btn.dataset.tab));
  });

  loginForm?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const email = loginForm.email.value.trim();
    const password = loginForm.password.value;

    try {
      const data = await loginRequest(email, password);
      if (!data.success) {
        showToast(data.message || "Login greska.", "error");
        return;
      }

      setCurrentUser(data.user);
      if (!getCsrfToken()) {
        clearLocalAuthState();
        showToast("Sigurnosna sesija nije valjana. Prijavi se ponovno.", "error");
        return;
      }

      updateAuthUI();
      showToast(`Prijavljen si kao: ${data.user.full_name}`, "success");
      closeAuthModal();
    } catch (error) {
      console.error(error);
      showToast("Doslo je do greske kod login-a.", "error");
    }
  });

  registerForm?.addEventListener("submit", async (event) => {
    event.preventDefault();
    const fullName = registerForm.fullName.value.trim();
    const email = registerForm.email.value.trim();
    const password = registerForm.password.value;
    const confirmPassword = registerForm.confirmPassword.value;

    try {
      const data = await registerRequest(fullName, email, password, confirmPassword);
      if (!data.success) {
        showToast(data.message || "Greska kod registracije.", "error");
        return;
      }

      setCurrentUser(data.user);
      if (!getCsrfToken()) {
        clearLocalAuthState();
        showToast("Sigurnosna sesija nije valjana. Prijavi se ponovno.", "error");
        return;
      }

      updateAuthUI();
      showToast(`Registracija uspjesna. Prijavljen si kao: ${data.user.full_name}`, "success");
      closeAuthModal();
    } catch (error) {
      console.error(error);
      showToast("Doslo je do greske kod registracije.", "error");
    }
  });
}

export function setupPasswordToggles() {
  const toggles = document.querySelectorAll(".password-toggle");

  toggles.forEach((btn) => {
    btn.addEventListener("click", () => {
      const targetId = btn.dataset.target;
      const input = document.getElementById(targetId);
      if (!input) return;

      const isPassword = input.type === "password";
      input.type = isPassword ? "text" : "password";
      btn.classList.toggle("active", isPassword);

      const icon = btn.querySelector("i");
      if (!icon) return;
      icon.classList.toggle("fa-eye", !isPassword);
      icon.classList.toggle("fa-eye-slash", isPassword);
    });
  });
}

export function setupProfileAndLogout() {
  const profileBtn = document.getElementById("profile-btn");
  const logoutBtn = document.getElementById("logout-btn");

  profileBtn?.addEventListener("click", () => {
    const currentUser = getCurrentUser();
    if (!currentUser) {
      showToast("Nisi prijavljen.", "error");
      return;
    }

    const isAdmin = Number(currentUser.is_admin) === 1;
    if (isAdmin) {
      window.open("backend/admin_reservations.php", "_blank");
      return;
    }

    const name = currentUser.full_name || currentUser.email;
    showToast(`Profil: ${name}`, "info");
  });

  logoutBtn?.addEventListener("click", async () => {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
      clearLocalAuthState();
      showToast("Sesija je istekla. Prijavi se ponovno.", "error");
      return;
    }

    try {
      const data = await logoutRequest(csrfToken);
      if (!data.success) {
        if (/csrf|prijavljen/i.test(data.message || "")) {
          clearLocalAuthState();
        }
        showToast(data.message || "Greska kod odjave.", "error");
        return;
      }

      clearLocalAuthState();
      showToast(data.message || "Uspjesno si se odjavio.", "success");
    } catch (error) {
      console.error(error);
      clearLocalAuthState();
      showToast("Odjava lokalno zavrsena, ali server logout nije uspio.", "error");
    }
  });
}
