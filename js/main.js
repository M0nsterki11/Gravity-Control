let currentUser = null;

document.addEventListener("DOMContentLoaded", () => {
  // Init AOS
  if (window.AOS) {
    AOS.init({
      duration: 700,
      once: true,
      offset: 80
    });
  }

  const yearSpan = document.getElementById("year");
  if (yearSpan) {
    yearSpan.textContent = new Date().getFullYear();
  }

  // Učitaj korisnika iz localStorage (ako postoji)
  const savedUser = localStorage.getItem("gravityUser");
  if (savedUser) {
    try {
      currentUser = JSON.parse(savedUser);
    } catch {
      currentUser = null;
    }
  }

  setupNav();
  setupHeaderScroll();
  setupAuthModal();
  populateSchedule();
  setupProfileAndLogout();
  updateAuthUI();
  setupParticles();
  setupPasswordToggles();
  setupImageLightbox();
});

let lastScrollY = window.scrollY;
let lastShowY = window.scrollY; // gdje je zadnji put header bio vidljiv

function setupHeaderScroll() {
  const header = document.querySelector(".main-header");
  if (!header) return;

  window.addEventListener("scroll", () => {
    const currentY = window.scrollY;
    const delta = currentY - lastScrollY; // >0 = skrolaš dolje, <0 = gore

    if (Math.abs(delta) < 5) {
      // premali pomak – ignoriraj
      return;
    }

    // blizu vrha stranice – header uvijek vidljiv i resetiramo referencu
    if (currentY < 80) {
      header.classList.remove("header-hidden");
      lastShowY = currentY;
    } else {
      if (delta > 0) {
        // skrolaš dolje
        const distanceSinceShown = currentY - lastShowY;

        // sakrij tek kad si se spustio npr. 250px od zadnjeg "show"
        if (distanceSinceShown > 250) {
          header.classList.add("header-hidden");
        }
      } else {
        // skrolaš gore → odmah pokaži i resetiraj referencu
        header.classList.remove("header-hidden");
        lastShowY = currentY;
      }
    }

    lastScrollY = currentY;
  });
}

/* ---------- Toast helper ---------- */

function showToast(message, type = "info") {
  const toast = document.getElementById("toast");
  if (!toast) return;

  toast.textContent = message;
  toast.className = "toast"; // reset klase
  if (type === "success") toast.classList.add("success");
  if (type === "error") toast.classList.add("error");


  requestAnimationFrame(() => {
    toast.classList.add("visible");
  });

  // sakrij nakon ... sekunde
  setTimeout(() => {
    toast.classList.remove("visible");
  }, 5000);
}

/* ---------- Nav ---------- */

function setupNav() {
  const navToggle = document.getElementById("nav-toggle");
  const navLinks = document.getElementById("nav-links");

  if (!navToggle || !navLinks) return;

  navToggle.addEventListener("click", () => {
    navToggle.classList.toggle("active");
    navLinks.classList.toggle("open");
  });

  navLinks.addEventListener("click", (e) => {
    if (e.target.tagName.toLowerCase() === "a") {
      navToggle.classList.remove("active");
      navLinks.classList.remove("open");
    }
  });
}

/* ---------- Auth modal + login/register ---------- */

function setupAuthModal() {
  const modal = document.getElementById("auth-modal");
  const loginBtn = document.getElementById("login-btn");
  const registerBtn = document.getElementById("register-btn");
  const heroRegister = document.getElementById("hero-register");
  const modalClose = document.getElementById("modal-close");
  const tabButtons = document.querySelectorAll(".tab-btn");
  const forms = document.querySelectorAll(".auth-form");

  function openModal(defaultTab = "register-form") {
    if (!modal) return;
    modal.classList.add("active");
    switchTab(defaultTab);
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove("active");
  }

  function switchTab(tabId) {
    tabButtons.forEach(btn => {
      btn.classList.toggle("active", btn.dataset.tab === tabId);
    });
    forms.forEach(form => {
      form.classList.toggle("active", form.id === tabId);
    });
  }

  loginBtn?.addEventListener("click", () => openModal("login-form"));
  registerBtn?.addEventListener("click", () => openModal("register-form"));
  heroRegister?.addEventListener("click", () => openModal("register-form"));
  modalClose?.addEventListener("click", closeModal);

  tabButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      switchTab(btn.dataset.tab);
    });
  });


  // LOGIN submit
  const loginForm = document.getElementById("login-form");
  loginForm?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = loginForm.email.value.trim();
    const password = loginForm.password.value;

    try {
      const res = await fetch("backend/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ email, password })
      });

      const data = await res.json();

      if (!data.success) {
        showToast(data.message || "Login greška.", "error");
        return;
      }

      currentUser = data.user;
      localStorage.setItem("gravityUser", JSON.stringify(currentUser));
      updateAuthUI();
      showToast("Prijavljen si kao: " + currentUser.full_name, "success");
      closeModal();
    } catch (err) {
      console.error(err);
      showToast("Došlo je do greške kod login-a.", "error");
    }
  });

  // REGISTER submit
  const registerForm = document.getElementById("register-form");
  registerForm?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const fullName        = registerForm.fullName.value.trim();
    const email           = registerForm.email.value.trim();
    const password        = registerForm.password.value;
    const confirmPassword = registerForm.confirmPassword.value;

    try {
      const res = await fetch("backend/register.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({ fullName, email, password, confirmPassword })
      });

      const data = await res.json();

      if (!data.success) {
        showToast(data.message || "Greška kod registracije.", "error");
        return;
      }

      currentUser = data.user;
      localStorage.setItem("gravityUser", JSON.stringify(currentUser));
      updateAuthUI();
      showToast("Registracija uspješna. Prijavljen si kao: " + currentUser.full_name, "success");
      closeModal();
    } catch (err) {
      console.error(err);
      showToast("Došlo je do greške kod registracije.", "error");
    }
  });
}

/* ---------- Show/hide lozinke ---------- */

function setupPasswordToggles() {
  const toggles = document.querySelectorAll(".password-toggle");

  toggles.forEach(btn => {
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

/* ---------- Profile / Logout UI ---------- */

function setupProfileAndLogout() {
  const profileBtn = document.getElementById("profile-btn");
  const logoutBtn = document.getElementById("logout-btn");

  if (profileBtn) {
    profileBtn.addEventListener("click", () => {
      if (!currentUser) {
        showToast("Nisi prijavljen.", "error");
        return;
      }
      const isAdmin = Number(currentUser.is_admin) === 1;

      if (isAdmin) {
        // ADMIN → otvori admin panel u novom tabu
        window.open("backend/admin_reservations.php", "_blank");
      } else {
        // običan user → za sada samo info toast
        const name = currentUser.full_name || currentUser.email;
        showToast("Profil: " + name, "info");
      }
    });
  }

  if (logoutBtn) {
    logoutBtn.addEventListener("click", () => {
      currentUser = null;
      localStorage.removeItem("gravityUser");
      updateAuthUI();
      showToast("Uspješno si se odjavio.", "success");
    });
  }
}

function updateAuthUI() {
  const guestContainer = document.getElementById("auth-guest");
  const userContainer = document.getElementById("auth-user");
  const profileBtn = document.getElementById("profile-btn");

  if (!guestContainer || !userContainer) return;

  if (currentUser) {
    guestContainer.style.display = "none";
    userContainer.style.display = "flex";

    if (profileBtn) {
      const name = currentUser.full_name || currentUser.email || "Profile";
      profileBtn.textContent = name.split(" ")[0]; // samo ime
    }
  } else {
    guestContainer.style.display = "flex";
    userContainer.style.display = "none";

    if (profileBtn) {
      profileBtn.textContent = "Profile";
    }
  }
}

/* ---------- Background ember particles ---------- */

function setupParticles() {
  const canvas = document.getElementById("bg-particles");
  if (!canvas) return;

  const ctx = canvas.getContext("2d");

  let width = window.innerWidth;
  let height = window.innerHeight;

  function resize() {
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = width;
    canvas.height = height;
  }

  window.addEventListener("resize", resize);
  resize();

  const particles = [];
  const maxParticles = 41;
  let spawnAccumulator = 0;
  const spawnInterval = 140;          // svakih ... nova čestica
  const topLimit = height * 0.3;      // lete do ~70% ekrana (od dole)

  function createParticle() {
    const x = Math.random() * width;
    const y = height + Math.random() * 40;        // malo ispod dna
    const speedY = -(70 + Math.random() * 90);    // 70–160 px/s prema gore
    const driftX = (Math.random() - 0.4) * 85;     // blagi drift lijevo/desno
    const radius = 1 + Math.random() * 2.2;
    const maxLife = 6200 + Math.random() * 1800;  // 6.0 – 7.8 sek
    return {
      x,
      y,
      vy: speedY,
      vx: driftX,
      radius,
      life: 0,
      maxLife
    };
  }

  let lastTime = performance.now();

  function loop(now) {
    const dt = now - lastTime; // ms
    lastTime = now;

    // ravnomjerni spawn – nema burstova
    spawnAccumulator += dt;
    while (spawnAccumulator >= spawnInterval && particles.length < maxParticles) {
      particles.push(createParticle());
      spawnAccumulator -= spawnInterval;
    }

    ctx.clearRect(0, 0, width, height);

    for (let i = particles.length - 1; i >= 0; i--) {
      const p = particles[i];
      p.life += dt;

      const t = dt / 1000; // sekunde
      p.y += p.vy * t;
      p.x += p.vx * t * 0.15;

      const lifeRatio = p.life / p.maxLife;

      // izbaci česticu ako je završila život ili došla previsoko (iznad 30% visine)
      if (lifeRatio >= 1 || p.y < topLimit) {
        particles.splice(i, 1);
        continue;
      }

      const alpha = (1 - lifeRatio) * 0.9; // fade out

      ctx.beginPath();
      ctx.fillStyle = `rgba(255, 140, 40, ${alpha})`;
      ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
      ctx.fill();
    }

    requestAnimationFrame(loop);
  }

  requestAnimationFrame(loop);
}
/* ---------- Termini / rezervacije ---------- */

async function populateSchedule() {
  const scheduleBody = document.getElementById("schedule-body");
  if (!scheduleBody) return;

  scheduleBody.innerHTML = "";

  try {
    const res = await fetch("backend/get_sessions.php");
    const data = await res.json();

    if (!data.success) {
      showToast(data.message || "Ne mogu učitati termine.", "error");
      return;
    }

    const sessions = data.sessions || [];

    sessions.forEach((session) => {
      const tr = document.createElement("tr");

      const timeLabel = `${session.time_from.slice(0, 5)} - ${session.time_to.slice(0, 5)}`;
      const sessionLabel = `${session.day} ${timeLabel}`;

      tr.innerHTML = `
        <td>${session.day}</td>
        <td>${timeLabel}</td>
        <td>${session.type}</td>
        <td>${session.coach}</td>
        <td>
          <button 
            class="btn btn-outline btn-small" 
            data-session="${sessionLabel}"
            data-session-id="${session.id}"
          >
            Rezerviraj
          </button>
        </td>
      `;

      scheduleBody.appendChild(tr);
    });

    // Event delegacija za klik na Rezerviraj
    scheduleBody.addEventListener("click", async (e) => {
      const target = e.target;
      if (target.matches("button[data-session]")) {
        if (!currentUser) {
          showToast("Za rezervaciju se moraš prvo ulogirati.", "error");
          const loginBtn = document.getElementById("login-btn");
          loginBtn?.click();
          return;
        }

        const sessionInfo = target.getAttribute("data-session");
        const sessionId = parseInt(target.getAttribute("data-session-id"), 10) || null;

        try {
          const res = await fetch("backend/reserve.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json"
            },
            body: JSON.stringify({
              userId: currentUser.id,
              sessionId,
              sessionInfo
            })
          });

          const data = await res.json();

          if (!data.success) {
            showToast(data.message || "Greška kod rezervacije.", "error");
            return;
          }

          showToast("Rezervacija potvrđena za: " + sessionInfo, "success");
        } catch (err) {
          console.error(err);
          showToast("Došlo je do greške kod rezervacije.", "error");
        }
      }
    });

  } catch (err) {
    console.error(err);
    showToast("Ne mogu se spojiti na server za termine.", "error");
  }
}

/* ---------- Image lightbox (klik na sliku) ---------- */

function setupImageLightbox() {
  const lightbox = document.getElementById("image-lightbox");
  const lightboxImg = document.getElementById("image-lightbox-img");
  if (!lightbox || !lightboxImg) return;

  const closeBtn = lightbox.querySelector(".image-lightbox-close");
  const images = document.querySelectorAll(".lightbox-image");

  function openLightbox(src, alt) {
    lightboxImg.src = src;
    lightboxImg.alt = alt || "";
    lightbox.classList.add("open");
    document.body.classList.add("no-scroll");
  }

  function closeLightbox() {
    lightbox.classList.remove("open");
    document.body.classList.remove("no-scroll");
    lightboxImg.src = "";
    lightboxImg.alt = "";
  }

  images.forEach((img) => {
    img.addEventListener("click", () => {
      openLightbox(img.src, img.alt);
    });
  });

  closeBtn?.addEventListener("click", closeLightbox);

  // klik po tamnoj pozadini zatvara
  lightbox.addEventListener("click", (e) => {
    if (e.target === lightbox) {
      closeLightbox();
    }
  });

  // ESC zatvara
  window.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && lightbox.classList.contains("open")) {
      closeLightbox();
    }
  });
}
