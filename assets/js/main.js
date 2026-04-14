// FOCUS AGENCY — Main JS

// ── NAVBAR SCROLL ──
const navbar = document.querySelector('.navbar');
if (navbar) {
  window.addEventListener('scroll', () => {
    if (window.scrollY > 40) navbar.classList.add('scrolled');
    else navbar.classList.remove('scrolled');
  });
}

// ── MOBILE MENU ──
const hamburger = document.querySelector('.hamburger-btn');
const mobileNav = document.querySelector('.mobile-nav');
const mobileClose = document.querySelector('.mobile-close');

if (hamburger && mobileNav) {
  hamburger.addEventListener('click', () => {
    mobileNav.classList.add('open');
    document.body.style.overflow = 'hidden';
  });
}

if (mobileClose && mobileNav) {
  mobileClose.addEventListener('click', () => {
    mobileNav.classList.remove('open');
    document.body.style.overflow = '';
  });
}

// Close mobile nav on link click
document.querySelectorAll('.mobile-nav a').forEach(link => {
  link.addEventListener('click', () => {
    if (mobileNav) mobileNav.classList.remove('open');
    document.body.style.overflow = '';
  });
});

// ── SCROLL REVEAL ──
const revealElements = document.querySelectorAll('.reveal');
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      const baseDelay = Number(entry.target.dataset.delay || 0);
      const quickStagger = i * 30;
      setTimeout(() => {
        entry.target.classList.add('revealed');
      }, baseDelay + quickStagger);
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.06, rootMargin: '0px 0px 70px 0px' });

revealElements.forEach((el) => {
  if (!el.dataset.delay) {
    el.dataset.delay = '0';
  }
  revealObserver.observe(el);
});

// ── ACCORDION ──
document.querySelectorAll('.accordion-trigger').forEach(trigger => {
  trigger.addEventListener('click', () => {
    const content = trigger.nextElementSibling;
    const icon = trigger.querySelector('.accordion-icon svg');
    const isOpen = content.classList.contains('open');

    // Close all
    document.querySelectorAll('.accordion-content').forEach(c => c.classList.remove('open'));
    document.querySelectorAll('.accordion-trigger').forEach(t => {
      const svg = t.querySelector('.accordion-icon svg');
      if (svg) svg.style.transform = 'rotate(0deg)';
    });

    if (!isOpen) {
      content.classList.add('open');
      if (icon) icon.style.transform = 'rotate(45deg)';
    }
  });
});

// ── COUNTER ANIMATION ──
function animateCounter(el) {
  const target = parseInt(el.dataset.target, 10);
  const duration = 1800;
  const start = performance.now();
  const startVal = 0;

  function update(currentTime) {
    const elapsed = currentTime - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    const value = Math.round(startVal + (target - startVal) * eased);
    el.textContent = value + (el.dataset.suffix || '');
    if (progress < 1) requestAnimationFrame(update);
  }
  requestAnimationFrame(update);
}

const counterObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      animateCounter(entry.target);
      counterObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.5 });

document.querySelectorAll('[data-target]').forEach(el => counterObserver.observe(el));

// ── ACTIVE NAV LINK ──
const currentPage = window.location.pathname.split('/').pop() || 'index.html';
document.querySelectorAll('.nav-link').forEach(link => {
  const href = link.getAttribute('href');
  if (href === currentPage || (currentPage === '' && href === 'index.html')) {
    link.classList.add('active');
  }
});
