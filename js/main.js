import {
  clearLocalAuthState,
  openLoginModal,
  setupAuthModal,
  setupPasswordToggles,
  setupProfileAndLogout,
  updateAuthUI,
} from "./auth.js";
import { populateSchedule } from "./schedule.js";
import { loadUserFromStorage } from "./state/session.js";

document.addEventListener("DOMContentLoaded", () => {
  if (window.AOS) {
    AOS.init({
      duration: 700,
      once: true,
      offset: 80,
    });
  }

  const yearSpan = document.getElementById("year");
  if (yearSpan) {
    yearSpan.textContent = new Date().getFullYear();
  }

  loadUserFromStorage();

  setupNav();
  setupHeaderScroll();
  setupAuthModal();
  setupProfileAndLogout();
  updateAuthUI();

  populateSchedule({
    onRequireLogin: () => openLoginModal(),
    onSessionInvalid: () => {
      clearLocalAuthState();
      openLoginModal();
    },
  });

  setupParticles();
  setupPasswordToggles();
  setupImageLightbox();
});

let lastScrollY = window.scrollY;
let lastShowY = window.scrollY;

function setupHeaderScroll() {
  const header = document.querySelector(".main-header");
  if (!header) return;

  window.addEventListener("scroll", () => {
    const currentY = window.scrollY;
    const delta = currentY - lastScrollY;

    if (Math.abs(delta) < 5) {
      return;
    }

    if (currentY < 80) {
      header.classList.remove("header-hidden");
      lastShowY = currentY;
    } else if (delta > 0) {
      const distanceSinceShown = currentY - lastShowY;
      if (distanceSinceShown > 250) {
        header.classList.add("header-hidden");
      }
    } else {
      header.classList.remove("header-hidden");
      lastShowY = currentY;
    }

    lastScrollY = currentY;
  });
}

function setupNav() {
  const navToggle = document.getElementById("nav-toggle");
  const navLinks = document.getElementById("nav-links");

  if (!navToggle || !navLinks) return;

  navToggle.addEventListener("click", () => {
    navToggle.classList.toggle("active");
    navLinks.classList.toggle("open");
  });

  navLinks.addEventListener("click", (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    if (target.tagName.toLowerCase() === "a") {
      navToggle.classList.remove("active");
      navLinks.classList.remove("open");
    }
  });
}

function setupParticles() {
  const canvas = document.getElementById("bg-particles");
  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  if (!ctx) return;

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
  const spawnInterval = 140;
  const topLimit = height * 0.3;

  function createParticle() {
    const x = Math.random() * width;
    const y = height + Math.random() * 40;
    const speedY = -(70 + Math.random() * 90);
    const driftX = (Math.random() - 0.4) * 85;
    const radius = 1 + Math.random() * 2.2;
    const maxLife = 6200 + Math.random() * 1800;

    return {
      x,
      y,
      vy: speedY,
      vx: driftX,
      radius,
      life: 0,
      maxLife,
    };
  }

  let lastTime = performance.now();

  function loop(now) {
    const dt = now - lastTime;
    lastTime = now;

    spawnAccumulator += dt;
    while (spawnAccumulator >= spawnInterval && particles.length < maxParticles) {
      particles.push(createParticle());
      spawnAccumulator -= spawnInterval;
    }

    ctx.clearRect(0, 0, width, height);

    for (let i = particles.length - 1; i >= 0; i -= 1) {
      const particle = particles[i];
      particle.life += dt;

      const time = dt / 1000;
      particle.y += particle.vy * time;
      particle.x += particle.vx * time * 0.15;

      const lifeRatio = particle.life / particle.maxLife;
      if (lifeRatio >= 1 || particle.y < topLimit) {
        particles.splice(i, 1);
        continue;
      }

      const alpha = (1 - lifeRatio) * 0.9;
      ctx.beginPath();
      ctx.fillStyle = `rgba(255, 140, 40, ${alpha})`;
      ctx.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
      ctx.fill();
    }

    requestAnimationFrame(loop);
  }

  requestAnimationFrame(loop);
}

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

  lightbox.addEventListener("click", (event) => {
    if (event.target === lightbox) {
      closeLightbox();
    }
  });

  window.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && lightbox.classList.contains("open")) {
      closeLightbox();
    }
  });
}
