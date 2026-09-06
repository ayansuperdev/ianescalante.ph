/**
 * Ian Escalante Portfolio - Interactive Script
 * Logo ICE, Constellation Hero Particles Canvas, Animated Header Scroll, WCAG Modals, Navigation, & Filtering
 */

document.addEventListener('DOMContentLoaded', () => {
  initHeroParticles();
  initHeaderScroll();
  initNavigation();
  initPortfolioFilters();
  initCaseStudyModals();
  highlightActiveNav();
});

/* ==========================================================================
   Header Scroll Animation (.scrolled state)
   ========================================================================== */
function initHeaderScroll() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  function checkScroll() {
    if (window.scrollY > 40) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  }

  window.addEventListener('scroll', checkScroll, { passive: true });
  checkScroll();
}

/* ==========================================================================
   Hero Constellation Particle Canvas (Connected Lines)
   ========================================================================== */
function initHeroParticles() {
  const heroSection = document.querySelector('.hero-section');
  if (!heroSection) return;

  let canvas = document.getElementById('heroParticlesCanvas');
  if (!canvas) {
    canvas = document.createElement('canvas');
    canvas.id = 'heroParticlesCanvas';
    heroSection.insertBefore(canvas, heroSection.firstChild);
  }

  const ctx = canvas.getContext('2d');
  let width, height;
  let particles = [];
  let mouse = { x: null, y: null, radius: 140 };

  function resize() {
    width = canvas.width = heroSection.offsetWidth;
    height = canvas.height = heroSection.offsetHeight;
    createParticles();
  }

  function createParticles() {
    particles = [];
    const particleCount = Math.floor((width * height) / 12000);
    const count = Math.min(Math.max(particleCount, 40), 90);

    for (let i = 0; i < count; i++) {
      particles.push({
        x: Math.random() * width,
        y: Math.random() * height,
        vx: (Math.random() - 0.5) * 0.75,
        vy: (Math.random() - 0.5) * 0.75,
        radius: Math.random() * 2.2 + 1.2,
        alpha: Math.random() * 0.6 + 0.3
      });
    }
  }

  window.addEventListener('resize', resize);
  heroSection.addEventListener('mousemove', (e) => {
    const rect = heroSection.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
  });

  heroSection.addEventListener('mouseleave', () => {
    mouse.x = null;
    mouse.y = null;
  });

  resize();

  function animate() {
    ctx.clearRect(0, 0, width, height);

    for (let i = 0; i < particles.length; i++) {
      let p = particles[i];

      p.x += p.vx;
      p.y += p.vy;

      if (p.x < 0 || p.x > width) p.vx *= -1;
      if (p.y < 0 || p.y > height) p.vy *= -1;

      ctx.beginPath();
      ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(212, 175, 55, ${p.alpha})`;
      ctx.shadowBlur = 8;
      ctx.shadowColor = '#D4AF37';
      ctx.fill();

      for (let j = i + 1; j < particles.length; j++) {
        let p2 = particles[j];
        let dx = p.x - p2.x;
        let dy = p.y - p2.y;
        let dist = Math.sqrt(dx * dx + dy * dy);

        if (dist < 110) {
          let lineAlpha = (1 - dist / 110) * 0.35;
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(p2.x, p2.y);
          ctx.strokeStyle = `rgba(212, 175, 55, ${lineAlpha})`;
          ctx.lineWidth = 0.85;
          ctx.shadowBlur = 0;
          ctx.stroke();
        }
      }

      if (mouse.x !== null && mouse.y !== null) {
        let mdx = p.x - mouse.x;
        let mdy = p.y - mouse.y;
        let mdist = Math.sqrt(mdx * mdx + mdy * mdy);

        if (mdist < mouse.radius) {
          let mAlpha = (1 - mdist / mouse.radius) * 0.45;
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(mouse.x, mouse.y);
          ctx.strokeStyle = `rgba(245, 230, 200, ${mAlpha})`;
          ctx.lineWidth = 1;
          ctx.stroke();
        }
      }
    }

    requestAnimationFrame(animate);
  }

  animate();
}

/* ==========================================================================
   Navigation & Dropdowns
   ========================================================================== */
function initNavigation() {
  const toggleBtn = document.querySelector('.mobile-nav-toggle');
  const navMenu = document.querySelector('.nav-menu');
  const dropdownTrigger = document.querySelector('.has-dropdown > a');
  const dropdownParent = document.querySelector('.has-dropdown');

  if (toggleBtn && navMenu) {
    toggleBtn.addEventListener('click', () => {
      const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
      toggleBtn.setAttribute('aria-expanded', !isExpanded);
      navMenu.classList.toggle('show');
    });
  }

  if (dropdownTrigger && dropdownParent) {
    dropdownTrigger.addEventListener('click', (e) => {
      if (window.innerWidth <= 768) {
        e.preventDefault();
        dropdownParent.classList.toggle('active');
      }
    });
  }
}

function highlightActiveNav() {
  const currentPath = window.location.pathname.split('/').pop() || 'index.html';
  const navLinks = document.querySelectorAll('.nav-link, .dropdown-item');

  navLinks.forEach(link => {
    const linkHref = link.getAttribute('href');
    if (linkHref === currentPath || (currentPath === '' && linkHref === 'index.html')) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }
  });
}

/* ==========================================================================
   Portfolio Filtering
   ========================================================================== */
function initPortfolioFilters() {
  const filterBtns = document.querySelectorAll('.filter-btn');
  const projectCards = document.querySelectorAll('.project-card');

  if (!filterBtns.length || !projectCards.length) return;

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filterValue = btn.getAttribute('data-filter');

      projectCards.forEach(card => {
        const categories = (card.getAttribute('data-category') || '').split(' ');
        if (filterValue === 'all' || categories.includes(filterValue)) {
          card.style.display = 'flex';
          card.style.opacity = '1';
        } else {
          card.style.display = 'none';
          card.style.opacity = '0';
        }
      });
    });
  });
}

/* ==========================================================================
   Accessible Case Study Modal Engine
   ========================================================================== */
let previousActiveElement = null;

function initCaseStudyModals() {
  const modalTriggers = document.querySelectorAll('[data-modal-target]');
  const modalCloseBtns = document.querySelectorAll('.modal-close, [data-modal-close]');
  const modalOverlays = document.querySelectorAll('.modal-overlay');

  modalTriggers.forEach(trigger => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const targetId = trigger.getAttribute('data-modal-target');
      const targetModal = document.getElementById(targetId);
      if (targetModal) {
        openModal(targetModal, trigger);
      }
    });
  });

  modalCloseBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const activeModal = btn.closest('.modal-overlay');
      if (activeModal) {
        closeModal(activeModal);
      }
    });
  });

  modalOverlays.forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        closeModal(overlay);
      }
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const activeModal = document.querySelector('.modal-overlay.active');
      if (activeModal) {
        closeModal(activeModal);
      }
    }
  });
}

function openModal(modalElement, triggerElement) {
  previousActiveElement = triggerElement || document.activeElement;
  modalElement.classList.add('active');
  modalElement.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';

  const focusableElements = modalElement.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
  if (focusableElements.length) {
    focusableElements[0].focus();
    trapFocus(modalElement, focusableElements);
  }
}

function closeModal(modalElement) {
  modalElement.classList.remove('active');
  modalElement.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';

  if (previousActiveElement) {
    previousActiveElement.focus();
  }
}

function trapFocus(modalElement, focusableElements) {
  const firstElement = focusableElements[0];
  const lastElement = focusableElements[focusableElements.length - 1];

  modalElement.addEventListener('keydown', function(e) {
    if (e.key !== 'Tab') return;

    if (e.shiftKey) {
      if (document.activeElement === firstElement) {
        lastElement.focus();
        e.preventDefault();
      }
    } else {
      if (document.activeElement === lastElement) {
        firstElement.focus();
        e.preventDefault();
      }
    }
  });
}
