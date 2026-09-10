/**
 * Ian Escalante Portfolio - Interactive Script
 * Logo ICE, Constellation Hero Particles Canvas, Animated Header Scroll, WCAG Modals, Navigation, & Filtering
 */

document.addEventListener('DOMContentLoaded', () => {
  initHeroParticles();
  initHeaderScroll();
  initHeroTextAnimation();
  initNavigation();
  initPortfolioFilters();
  initFeaturedWork();
  initCaseStudyModals();
  highlightActiveNav();
  initLoadMoreExperience();
  initPortraitPopup();
  initTechnicalSkills();
  initAboutSection();
});

function initPortraitPopup() {
  const popup = document.querySelector('.portrait-popup');
  const about = document.getElementById('about');
  if (!popup || !about) return;

  const cloudText = popup.querySelector('.portrait-cloud-text');
  const messages = {
    about: 'I build WordPress solutions around your business.',
    services: 'Need a website, custom plugin, or automation? I can help!',
    portfolio: 'Take a look at the projects I have brought to life.',
    capabilities: 'These are the tools I use to turn ideas into working solutions.',
    workflow: 'From discovery to launch, here is how I bring your project to life.',
    experience: 'Explore the experience behind my work.',
    contact: 'Have a project in mind? Let\'s talk!'
  };
  const sections = Array.from(document.querySelectorAll('section[id]'))
    .filter(section => messages[section.id]);
  let activeSection = '';
  let dismissed = false;
  const update = () => {
    const visible = !dismissed && about.getBoundingClientRect().top <= window.innerHeight * 0.75;
    popup.classList.toggle('is-visible', visible);
    popup.setAttribute('aria-hidden', String(!visible));
    popup.inert = !visible;
    if (!visible || !cloudText) return;

    let current = about.id;
    for (const section of sections) {
      if (section.getBoundingClientRect().top <= window.innerHeight * 0.5) {
        current = section.id;
      }
    }
    if (current !== activeSection) {
      cloudText.textContent = messages[current];
      activeSection = current;
    }
  };

  popup.querySelector('.portrait-popup-close').addEventListener('click', () => {
    dismissed = true;
    update();
  });
  window.addEventListener('scroll', update, { passive: true });
  window.addEventListener('resize', update);
  window.addEventListener('pageshow', update);
  update();
}

/* ==========================================================================
   Header Scroll & Mouse-Move Animation (.scrolled & .nav-visible states)
   ========================================================================== */
function initHeaderScroll() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  let mouseNearTop = false;

  function checkState() {
    const isScrolled = window.scrollY > 30;
    if (isScrolled || mouseNearTop) {
      header.classList.add('scrolled', 'nav-visible');
    } else {
      header.classList.remove('scrolled', 'nav-visible');
    }
  }

  window.addEventListener('scroll', checkState, { passive: true });

  document.addEventListener('mousemove', (e) => {
    if (e.clientY < 110) {
      mouseNearTop = true;
    } else {
      mouseNearTop = false;
    }
    checkState();
  });

  checkState();
}

/* ==========================================================================
   Interactive Hero Typewriter Text Animation
   ========================================================================== */
function initHeroTextAnimation() {
  const dynamicText = document.getElementById('heroDynamicText');
  if (!dynamicText) return;

  const words = [
    'Real Business Needs',
    'High Performance',
    'Custom WP Plugins',
    'API & CRM Automations',
    'Scalable Growth'
  ];

  let wordIndex = 0;
  let charIndex = 0;
  let isDeleting = false;
  let typeSpeed = 100;

  function type() {
    const currentWord = words[wordIndex];

    if (isDeleting) {
      dynamicText.textContent = currentWord.substring(0, charIndex - 1);
      charIndex--;
      typeSpeed = 45;
    } else {
      dynamicText.textContent = currentWord.substring(0, charIndex + 1);
      charIndex++;
      typeSpeed = 85;
    }

    if (!isDeleting && charIndex === currentWord.length) {
      typeSpeed = 2200; // Pause at end of word
      isDeleting = true;
    } else if (isDeleting && charIndex === 0) {
      isDeleting = false;
      wordIndex = (wordIndex + 1) % words.length;
      typeSpeed = 400; // Pause before next word
    }

    setTimeout(type, typeSpeed);
  }

  setTimeout(type, 1000);
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

/* ==========================================================================
   Load More Work Experience Toggle
   ========================================================================== */
function initLoadMoreExperience() {
  const loadBtn = document.getElementById('loadMoreExperienceBtn');
  const hiddenContainer = document.getElementById('experienceHiddenRoles');

  if (!loadBtn || !hiddenContainer) return;

  loadBtn.addEventListener('click', () => {
    const isHidden = hiddenContainer.style.display === 'none' || hiddenContainer.style.display === '';

    if (isHidden) {
      hiddenContainer.style.display = 'block';
      loadBtn.innerHTML = '<i class="fas fa-chevron-up" style="margin-right: 8px;"></i> Show Less Work Experience';
    } else {
      hiddenContainer.style.display = 'none';
      loadBtn.innerHTML = '<i class="fas fa-chevron-down" style="margin-right: 8px;"></i> Load More Work Experience';
      
      const expSection = document.getElementById('experience');
      if (expSection) {
        expSection.scrollIntoView({ behavior: 'smooth' });
      }
    }
  });
}

function initTechnicalSkills() {
  const section = document.getElementById("capabilities");
  if (!section || !section.querySelector(".pie-chart")) return;

  const skills = {
    wordpress: { title: "WordPress Engineering", level: "Expert" },
    backend: { title: "Backend & Data Layer", level: "Advanced" },
    frontend: { title: "Frontend & UI", level: "Advanced" },
    integrations: { title: "Integrations & Automations", level: "Expert" },
    infrastructure: { title: "Infrastructure & Support", level: "Proficient" }
  };

  const slices = [...section.querySelectorAll(".skill-slice")];
  const cards = [...section.querySelectorAll(".skill-card")];
  const activeTitle = section.querySelector("#active-title");
  const activeLevel = section.querySelector("#active-level");

  function activateSkill(skillId) {
    const selected = skills[skillId];
    if (!selected) return;

    activeTitle.textContent = selected.title;
    activeLevel.textContent = selected.level;

    slices.forEach(slice => {
      const isSelected = slice.dataset.skill === skillId;
      slice.classList.toggle("is-active", isSelected);
      slice.setAttribute("aria-pressed", String(isSelected));
    });

    cards.forEach(card => {
      const isSelected = card.dataset.skill === skillId;
      card.classList.toggle("is-active", isSelected);
      card.setAttribute("aria-pressed", String(isSelected));
    });
  }

  [...slices, ...cards].forEach(control => {
    const select = () => activateSkill(control.dataset.skill);

    control.addEventListener("mouseenter", select);
    control.addEventListener("focus", select);
    control.addEventListener("click", select);
    control.addEventListener("keydown", event => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        select();
      }
    });
  });

  activateSkill("wordpress");

}

function initFeaturedWork() {
  const section = document.getElementById("portfolio");
  if (!section || !section.querySelector(".project-console")) return;

  const projects = [
    {
      title: "Vaze Realty", logo: "VΛZE", type: "Real Estate Platform", status: "Live",
      categories: ["WordPress", "Integrations", "Real Estate"],
      tags: ["WordPress", "RESO API", "Custom Plugin"],
      summary: "A custom real-estate platform with property search, interactive maps, client dashboards, lead capture, and CRM integrations.",
      details: "Built as a connected property platform rather than a standard brochure website. The system brings listing search, saved properties, user portals, agent workflows, mapping, and lead capture into one experience.",
      focus: "Platform architecture", delivery: "Custom development"
    },
    {
      title: "SalesCreator", logo: "SalesCreator", type: "PropTech & Automation", status: "Active",
      categories: ["WordPress", "Integrations", "Real Estate"],
      tags: ["REST API", "Stripe", "Slack Webhooks"],
      summary: "Custom WordPress tools for property data, subscriptions, Stripe events, Slack notifications, and responsive customer experiences.",
      details: "A collection of production tools connecting WordPress interfaces to property feeds, subscription events, lead channels, and internal notification workflows.",
      focus: "Connected workflows", delivery: "Plugins & integrations"
    },
    {
      title: "Cruise Brisbane River", logo: "CRUISE", type: "Tourism & Booking", status: "In build",
      categories: ["WordPress", "Integrations"],
      tags: ["Custom Blocks", "Rezdy API", "Responsive UI"],
      summary: "A tourism experience website with editable cruise content, interactive filters, booking integration, and responsive layouts.",
      details: "Designed to make cruise discovery and booking easier while giving the site team flexible control over experience content through custom WordPress blocks.",
      focus: "Booking experience", delivery: "Custom WordPress"
    },
    {
      title: "Clark Partners", logo: "Clark Partners", type: "Property Agency", status: "In build",
      categories: ["WordPress", "Real Estate"],
      tags: ["ACF Blocks", "Gutenberg", "Calculators"],
      summary: "A modern real-estate website with editable Gutenberg blocks, finance calculators, appraisal flows, and responsive layouts.",
      details: "A flexible agency website system using reusable ACF-powered blocks, interactive finance tools, and streamlined property appraisal journeys.",
      focus: "Editable content system", delivery: "Theme engineering"
    },
    {
      title: "The Fresh Collective", logo: "FRESH COLLECTIVE", type: "Catering & Events", status: "Live",
      categories: ["WordPress"],
      tags: ["Custom Theme", "PHP", "Content UX"],
      summary: "A premium Australian catering and events platform built with custom WordPress components and curated content experiences.",
      details: "Custom development supporting a content-rich hospitality brand, with reusable components that keep menus, venues, and event experiences easy to browse.",
      focus: "Hospitality content", delivery: "Custom theme"
    },
    {
      title: "Scott Kim Real Estate", logo: "SCOTT KIM", type: "Real Estate Brokerage", status: "Live",
      categories: ["WordPress", "Real Estate"],
      tags: ["WordPress", "Listings", "Custom Layouts"],
      summary: "A modern real-estate agency website showcasing property listings, market appraisals, and client search tools.",
      details: "A polished brokerage experience focused on listing discovery, local market authority, and clear lead pathways for buyers and sellers.",
      focus: "Property marketing", delivery: "Website development"
    },
    {
      title: "Riverlife", logo: "Riverlife", type: "Outdoor Experiences", status: "Live",
      categories: ["WordPress", "Integrations"],
      tags: ["Divi", "Mailchimp", "Rezdy"],
      summary: "A WordPress experience platform connecting outdoor activities, enquiries, campaigns, and booking workflows.",
      details: "Ongoing development and support for a high-traffic experiences brand, including booking integration, campaign forms, page improvements, and content updates.",
      focus: "Experience bookings", delivery: "Development & support"
    },
    {
      title: "Omada Real Estate", logo: "OMADA", type: "Property Agency", status: "Live",
      categories: ["WordPress", "Integrations", "Real Estate"],
      tags: ["Property Feeds", "Custom Theme", "WordPress"],
      summary: "A real-estate platform for residential and commercial sales, auctions, rentals, and property management.",
      details: "A comprehensive property website connecting multiple service lines with property data and a unified, responsive browsing experience.",
      focus: "Property discovery", delivery: "Integrated platform"
    },
    {
      title: "ICON Elect", logo: "ICON ELECT", type: "Portal & Workflows", status: "Active",
      categories: ["WordPress", "Integrations"],
      tags: ["Airtable API", "Secure Portal", "Automation"],
      summary: "A secure citizenship application and reviewer portal connected to Airtable and automated operational workflows.",
      details: "A token-secured portal enabling controlled application review, decision write-back, audit data, and downstream document workflows without exposing the underlying database.",
      focus: "Secure operations", delivery: "Portal engineering"
    },
    {
      title: "Raviv Casuals", logo: "RAVIV", type: "E-commerce & Brand", status: "Live",
      categories: ["WordPress", "E-commerce"],
      tags: ["WooCommerce", "Custom Plugin", "Brand UX"],
      summary: "A responsive premium-footwear store combining e-commerce functionality with a focused brand experience.",
      details: "An online shop supported by custom WordPress and WooCommerce development, designed around product storytelling and a straightforward purchase journey.",
      focus: "Commerce experience", delivery: "WooCommerce"
    },
    {
      title: "L.L. Harrell", logo: "LLH", type: "Real Estate Brokerage", status: "Live",
      categories: ["WordPress", "Integrations", "Real Estate"],
      tags: ["PHP", "MySQL", "Property Search"],
      summary: "A brokerage website with WordPress content management, property search, and listing-data integrations.",
      details: "A professional real-estate presence supported by custom property-search functionality and structured listing integrations.",
      focus: "Listing search", delivery: "Custom development"
    },
    {
      title: "Harrell Melts", logo: "Harrell's", type: "E-commerce", status: "Live",
      categories: ["WordPress", "E-commerce"],
      tags: ["WordPress", "PHP", "Storefront"],
      summary: "An e-commerce and brand website created to present warm, engaging products through a focused customer journey.",
      details: "A custom storefront bringing product presentation, brand personality, and customer purchasing into a cohesive WordPress experience.",
      focus: "Product storefront", delivery: "E-commerce website"
    },
    {
      title: "Taxhaus Business Logics", logo: "TAXHAUS", type: "Corporate Identity", status: "Complete",
      categories: ["Branding"],
      tags: ["Brand Identity", "Visual System", "Vector Design"],
      summary: "A professional brand identity designed around clarity, trust, and confident business expertise.",
      details: "A flexible visual identity system created to feel credible across digital and business applications while retaining a distinctive corporate character.",
      focus: "Corporate identity", delivery: "Brand system"
    }
  ];

  const projectAssets = [
  {
  "title": "Vaze Realty",
  "image": "assets/images/logos/VAZE Realty logo.PNG",
  "url": "https://vazerealty.com/",
  "modal": "modal-vaze",
  "company": "Vaze Realty"
  },
  {
  "title": "SalesCreator",
  "image": "assets/images/logos/navbar-brand.png",
  "url": "",
  "modal": "modal-salescreator",
  "company": "SalesCreators"
  },
  {
  "title": "Cruise Brisbane River",
  "image": "assets/images/logos/cruise brisbane river.png",
  "url": "",
  "modal": "modal-cruisebrisbane",
  "company": "SalesCreators"
  },
  {
  "title": "Clark Partners",
  "image": "assets/images/logos/Clark Partners RGB.png",
  "url": "",
  "modal": "modal-clarkpartners",
  "company": "SalesCreators"
  },
  {
  "title": "The Fresh Collective",
  "image": "assets/images/logos/the fresh collective.png",
  "url": "https://thefreshcollective.com.au/",
  "modal": "",
  "company": "SalesCreators"
  },
  {
  "title": "Scott Kim Real Estate",
  "image": "assets/images/logos/scott kim.png",
  "url": "https://scottkim.com.au/",
  "modal": "",
  "company": "SalesCreators"
  },
  {
  "title": "Riverlife",
  "image": "assets/images/logos/River-Life-Logo-Landscape-White.webp",
  "url": "https://riverlife.com.au/",
  "modal": "modal-riverlife",
  "company": "SalesCreators"
  },
  {
  "title": "Omada Real Estate",
  "image": "assets/images/logos/omadarealestate.png",
  "url": "https://omadarealestate.com.au/",
  "modal": "",
  "company": "SalesCreators"
  },
  {
  "title": "ICON Elect",
  "image": "assets/images/logos/IconElect.png",
  "url": "https://iconelect.org",
  "modal": "modal-iconelect",
  "company": "TAXHAUS BUSINESS GROUP LLC"
  },
  {
  "title": "Raviv Casuals",
  "image": "assets/images/logos/RavivCasuals.png",
  "url": "https://ravivcasuals.com/",
  "modal": "",
  "company": "TAXHAUS BUSINESS GROUP LLC"
  },
  {
  "title": "L.L. Harrell",
  "image": "assets/images/logos/LLHarrell.png",
  "url": "https://llharrell.com/",
  "modal": "",
  "company": "TAXHAUS BUSINESS GROUP LLC"
  },
  {
  "title": "Harrell Melts",
  "image": "assets/images/logos/HarrellBCB.png",
  "url": "https://harrellmelts.com/",
  "modal": "",
  "company": "TAXHAUS BUSINESS GROUP LLC"
  },
  {
  "title": "Taxhaus Business Logics",
  "image": "assets/images/logos/TBL.png",
  "url": "",
  "modal": "",
  "company": "TAXHAUS BUSINESS GROUP LLC"
  }
];
  projects.forEach(project => Object.assign(project, projectAssets.find(asset => asset.title === project.title)));

  const state = { filter: "All", visible: [...projects], activeIndex: 0 };
  const rail = section.querySelector("#projectRail");
  const emptyState = section.querySelector("#emptyState");
  const logo = section.querySelector("#markLogo");
  const dialog = section.querySelector("#projectDialog");

  const fields = {
    consoleCategory: section.querySelector("#consoleCategory"),
    markIndex: section.querySelector("#markIndex"),
    projectType: section.querySelector("#projectType"),
    dataNumber: section.querySelector("#dataNumber"),
    dataStatus: section.querySelector("#dataStatus"),
    projectTitle: section.querySelector("#projectTitle"),
    projectSummary: section.querySelector("#projectSummary"),
    tagList: section.querySelector("#tagList")
  };

  function projectNumber(project) {
    return String(projects.indexOf(project) + 1).padStart(2, "0");
  }

  function renderRail() {
    rail.innerHTML = "";
    emptyState.style.display = state.visible.length ? "none" : "grid";

    state.visible.forEach((project, index) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "rail-item" + (index === state.activeIndex ? " is-active" : "");
      button.setAttribute("aria-label", `Show ${project.title}`);
      button.setAttribute("aria-pressed", String(index === state.activeIndex));
      button.innerHTML = `<span class="rail-number">${projectNumber(project)} // ${project.type}</span><span class="rail-title">${project.title}</span>`;
      button.addEventListener("click", () => selectProject(index));
      rail.appendChild(button);
    });
  }

  function selectProject(index, announce = true) {
    if (!state.visible.length) return;
    state.activeIndex = (index + state.visible.length) % state.visible.length;
    const project = state.visible[state.activeIndex];
    const number = projectNumber(project);

    logo.src = project.image;
    logo.alt = `${project.title} logo`;
    section.querySelector("#projectCompany").textContent = project.company;
    const liveLink = section.querySelector("#liveProjectLink");
    liveLink.hidden = !project.url;
    if (project.url) liveLink.href = project.url;
    else liveLink.removeAttribute("href");

    fields.consoleCategory.textContent = project.categories.slice(0, 2).join(" / ");
    fields.markIndex.textContent = `PROJECT // ${number}`;
    fields.projectType.textContent = project.type;
    fields.dataNumber.textContent = `CASE FILE ${number} / ${String(projects.length).padStart(2, "0")}`;
    fields.dataStatus.textContent = project.status;
    fields.projectTitle.textContent = project.title;
    fields.projectSummary.textContent = project.summary;
    fields.tagList.innerHTML = project.tags.map(tag => `<li>${tag}</li>`).join("");

    [...rail.children].forEach((button, i) => {
      button.classList.toggle("is-active", i === state.activeIndex);
      button.setAttribute("aria-pressed", String(i === state.activeIndex));
    });
    if (announce) {
      section.querySelector("#workAnnouncement").textContent = `${project.title}, ${state.activeIndex + 1} of ${state.visible.length} projects`;
      const selected = rail.children[state.activeIndex];
      rail.scrollTo({
        left: selected.offsetLeft - rail.offsetLeft - (rail.clientWidth - selected.offsetWidth) / 2,
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
      });
    }
  }

  function changeProject(direction) {
    selectProject(state.activeIndex + direction);
  }

  function applyFilter(filter) {
    state.filter = filter;
    state.visible = filter === "All" ? [...projects] : projects.filter(project => project.categories.includes(filter));
    state.activeIndex = 0;

    section.querySelectorAll(".filter-button").forEach(button => {
      const active = button.dataset.filter === filter;
      button.classList.toggle("is-active", active);
      button.setAttribute("aria-pressed", String(active));
    });

    renderRail();
    if (state.visible.length) selectProject(0);
  }

  function openDetails() {
    const project = state.visible[state.activeIndex];
    if (!project) return;
    if (project.modal) {
      openModal(document.getElementById(project.modal), section.querySelector('#detailsButton'));
      return;
    }
    section.querySelector("#dialogLabel").textContent = project.type;
    section.querySelector("#dialogTitle").textContent = project.title;
    section.querySelector("#dialogText").textContent = project.details;
    section.querySelector("#dialogFocus").textContent = project.focus;
    section.querySelector("#dialogDelivery").textContent = project.delivery;
    dialog.showModal();
  }

  section.querySelectorAll(".filter-button").forEach(button => {
    button.addEventListener("click", () => applyFilter(button.dataset.filter));
  });

  section.querySelector("#previousButton").addEventListener("click", () => changeProject(-1));
  section.querySelector("#nextButton").addEventListener("click", () => changeProject(1));
  section.querySelector("#nextProjectButton").addEventListener("click", () => changeProject(1));
  section.querySelector("#detailsButton").addEventListener("click", openDetails);

  rail.addEventListener("keydown", event => {
    if (!["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)) return;
    event.preventDefault();
    if (event.key === "Home") selectProject(0);
    else if (event.key === "End") selectProject(state.visible.length - 1);
    else changeProject(event.key === "ArrowLeft" ? -1 : 1);
    rail.children[state.activeIndex]?.focus({ preventScroll: true });
  });

  dialog.addEventListener("click", event => {
    const box = dialog.getBoundingClientRect();
    const outside = event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom;
    if (outside) dialog.close();
  });

  renderRail();
  selectProject(0, false);

}

function initAboutSection() {
  const section = document.getElementById("about");
  if (!section || !section.querySelector(".orbit-control")) return;

  const modes = {
    build: {
      title: "Build custom solutions",
      copy: "Websites, portals, dashboards, plugins, and interfaces shaped around the real business workflow."
    },
    connect: {
      title: "Connect the ecosystem",
      copy: "APIs, CRM platforms, payments, property data, booking tools, and automation working as one reliable system."
    },
    support: {
      title: "Improve and support",
      copy: "Careful debugging, maintenance, performance work, and practical improvements after the initial launch."
    }
  };

  const controls = [...section.querySelectorAll(".orbit-control")];
  const detailTitle = section.querySelector("#aboutDetailTitle");
  const detailCopy = section.querySelector("#aboutDetailCopy");

  function activateMode(modeName) {
    const mode = modes[modeName];
    if (!mode) return;

    detailTitle.textContent = mode.title;
    detailCopy.textContent = mode.copy;

    controls.forEach(control => {
      const active = control.dataset.mode === modeName;
      control.classList.toggle("is-active", active);
      control.setAttribute("aria-pressed", String(active));
    });
  }

  controls.forEach(control => {
    control.addEventListener("click", () => activateMode(control.dataset.mode));
    control.addEventListener("mouseenter", () => activateMode(control.dataset.mode));
    control.addEventListener("focus", () => activateMode(control.dataset.mode));
  });

  activateMode("build");
}
