/* ============================================================
   ADSWIBE® ENHANCEMENTS JS v5.0 — PRODUCTION READY
   Navbar Auto-Hide · Share Float · Scroll Reveal · FAQ
   Ripple · Modal Fix · Smooth Scroll · Mobile Menu
   ============================================================ */
(function() {
  'use strict';

  /* ══════════════════════════════════════════════
     0. CUSTOM CURSOR — Blue default, Orange on hover/click
     ══════════════════════════════════════════════ */
  (function() {
    /* Skip on touch devices */
    if (window.matchMedia('(hover: none) and (pointer: coarse)').matches) return;

    var dot  = document.getElementById('cursor-dot');
    var ring = document.getElementById('cursor-ring');
    if (!dot || !ring) return;

    /* Make sure they're visible */
    dot.style.display  = 'block';
    ring.style.display = 'block';

    var mouseX = -100, mouseY = -100;
    var ringX  = -100, ringY  = -100;
    var rafId  = null;

    /* Clickable selectors */
    var HOVER_SELECTORS = 'a, button, [role="button"], .btn-main, .btn-wa-service, ' +
      '.service-card, .faq-question, .tool-card, .pricing-card, ' +
      'input[type="submit"], input[type="button"], label, ' +
      '.slider-arrow, .sf-circle, #menu-btn, select, .de-item';

    /* Track mouse position */
    document.addEventListener('mousemove', function(e) {
      mouseX = e.clientX;
      mouseY = e.clientY;
      if (!rafId) rafId = requestAnimationFrame(moveCursor);
    }, { passive: true });

    function moveCursor() {
      rafId = null;
      /* Dot snaps to cursor instantly */
      dot.style.left = mouseX + 'px';
      dot.style.top  = mouseY + 'px';
      dot.style.transform = 'translate(-50%, -50%)';

      /* Ring follows with slight lag for premium feel — 0.28 = snappy but smooth */
      ringX += (mouseX - ringX) * 0.28;
      ringY += (mouseY - ringY) * 0.28;
      ring.style.left = Math.round(ringX * 10) / 10 + 'px';
      ring.style.top  = Math.round(ringY * 10) / 10 + 'px';

      /* Keep animating ring until it catches up */
      var dist = Math.abs(mouseX - ringX) + Math.abs(mouseY - ringY);
      if (dist > 0.3) rafId = requestAnimationFrame(moveCursor);
    }

    /* Hover detection */
    document.addEventListener('mouseover', function(e) {
      if (e.target.closest(HOVER_SELECTORS)) {
        document.body.classList.add('cursor-hover');
      }
    }, { passive: true });

    document.addEventListener('mouseout', function(e) {
      if (e.target.closest(HOVER_SELECTORS)) {
        document.body.classList.remove('cursor-hover');
      }
    }, { passive: true });

    /* Click detection */
    document.addEventListener('mousedown', function() {
      document.body.classList.add('cursor-click');
    }, { passive: true });

    document.addEventListener('mouseup', function() {
      document.body.classList.remove('cursor-click');
    }, { passive: true });

    /* Hide when cursor leaves window */
    document.addEventListener('mouseleave', function() {
      dot.style.opacity  = '0';
      ring.style.opacity = '0';
    }, { passive: true });

    document.addEventListener('mouseenter', function() {
      dot.style.opacity  = '1';
      ring.style.opacity = '0.75';
    }, { passive: true });
  })();

  /* ══════════════════════════════════════════════
     1. PAGE PRELOADER
     ══════════════════════════════════════════════ */
  window.addEventListener('load', function() {
    var pre = document.getElementById('page-preloader');
    if (pre) setTimeout(function() { pre.classList.add('hidden'); }, 400);
  });

  /* ══════════════════════════════════════════════
     2. NAVBAR AUTO-HIDE / SHOW ON SCROLL
     - Scrolling DOWN: navbar slides up (hidden)
     - Scrolling UP: navbar slides down (visible)
     - Always visible when near top (< 80px)
     ══════════════════════════════════════════════ */
  (function() {
    var header = document.querySelector('header');
    if (!header) return;

    var lastScrollY  = window.scrollY;
    var ticking      = false;
    var THRESHOLD    = 80;  /* px from top — always show above this */
    var MIN_DELTA    = 4;   /* px — minimum scroll change to trigger */

    function isModalOpen() {
      /* Check both body class and any active overlay */
      if (document.body.classList.contains('modal-open')) return true;
      var overlays = document.querySelectorAll('.service-modal-overlay');
      for (var i = 0; i < overlays.length; i++) {
        if (overlays[i].classList.contains('active')) return true;
      }
      return false;
    }

    function hideNavbar() {
      header.classList.add('nav-hidden');
      header.classList.remove('nav-visible');
      header.style.marginTop = '0px';
    }

    function showNavbar() {
      header.classList.remove('nav-hidden');
      header.classList.add('nav-visible');
      header.style.marginTop = '0px';
    }

    function updateNavbar() {
      /* CRITICAL: If any modal is open — always keep navbar hidden, no exceptions */
      if (isModalOpen()) {
        hideNavbar();
        lastScrollY = window.scrollY;
        ticking = false;
        return;
      }

      var currentY = window.scrollY;
      var delta    = currentY - lastScrollY;

      if (currentY <= THRESHOLD) {
        showNavbar();
      } else if (delta > MIN_DELTA) {
        hideNavbar();
      } else if (delta < -MIN_DELTA) {
        showNavbar();
      }

      lastScrollY = currentY;
      ticking = false;
    }

    window.addEventListener('scroll', function() {
      if (!ticking) {
        requestAnimationFrame(updateNavbar);
        ticking = true;
      }
    }, { passive: true });

    /* Override designesia.js margin-top — prevent flicker */
    window.addEventListener('scroll', function() {
      if (header) header.style.marginTop = '0px';
    }, { passive: true });

    /* Modal opened — immediately hide navbar */
    document.addEventListener('modalOpened', function() {
      hideNavbar();
    });

    /* Modal closed — restore navbar */
    document.addEventListener('modalClosed', function() {
      lastScrollY = window.scrollY; /* reset so no ghost delta */
      showNavbar();
    });
  })();

  /* ══════════════════════════════════════════════
     3. HEADER SCROLL SHADOW
     ══════════════════════════════════════════════ */
  (function() {
    var header = document.querySelector('header');
    if (!header) return;
    var ticking = false;
    function updateShadow() {
      if (window.scrollY > 50) header.classList.add('scrolled-shadow');
      else header.classList.remove('scrolled-shadow');
      ticking = false;
    }
    window.addEventListener('scroll', function() {
      if (!ticking) { requestAnimationFrame(updateShadow); ticking = true; }
    }, { passive: true });
  })();

  /* ══════════════════════════════════════════════
     4. MOBILE SIDE DRAWER
     ══════════════════════════════════════════════ */
  (function() {
    var menuBtn  = document.getElementById('menu-btn');
    var drawer   = document.getElementById('mob-nav-drawer');
    var overlay  = document.getElementById('mob-nav-overlay');
    var closeBtn = document.getElementById('mob-nav-close');
    if (!menuBtn || !drawer || !overlay) return;

    function preventScroll(e) { e.preventDefault(); }

    function resetHeader() {
      var hdr = document.querySelector('header');
      if (hdr) {
        hdr.style.height = 'auto';
        hdr.style.overflow = '';
        hdr.classList.remove('menu-open');
      }
    }

    function openDrawer() {
      drawer.classList.add('open');
      overlay.classList.add('open');
      resetHeader();
      overlay.addEventListener('touchmove', preventScroll, { passive: false });
    }

    function closeDrawer() {
      drawer.classList.remove('open');
      overlay.classList.remove('open');
      resetHeader();
      overlay.removeEventListener('touchmove', preventScroll);
    }

    function handleMenuBtn(e) {
      e.stopPropagation();
      drawer.classList.contains('open') ? closeDrawer() : openDrawer();
    }

    /* Wait for window load so designesia.js $(document).ready has run and
       registered its #menu-btn handler. Then replace it with ours. */
    window.addEventListener('load', function() {
      if (window.jQuery) jQuery('#menu-btn').off('click');
      menuBtn.addEventListener('click', handleMenuBtn);
    });

    overlay.addEventListener('click', closeDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

    drawer.querySelectorAll('a').forEach(function(link) {
      link.addEventListener('click', closeDrawer);
    });
  })();

  /* ══════════════════════════════════════════════
     5. SHARE FLOAT TOGGLE
     ══════════════════════════════════════════════ */
  var sfBtn     = document.getElementById('share-float-btn');
  var sfCircles = document.getElementById('share-float-circles');
  if (sfBtn && sfCircles) {
    var sfTimer = null;
    sfBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      var isOpen = sfCircles.classList.contains('sf-visible');
      sfCircles.classList.toggle('sf-visible', !isOpen);
      sfBtn.classList.toggle('sf-open', !isOpen);
      clearTimeout(sfTimer);
      if (!isOpen) {
        sfTimer = setTimeout(function() {
          sfCircles.classList.remove('sf-visible');
          sfBtn.classList.remove('sf-open');
        }, 7000);
      }
    });
    document.addEventListener('click', function(e) {
      var wrap = document.getElementById('share-float-wrap');
      if (wrap && !wrap.contains(e.target)) {
        sfCircles.classList.remove('sf-visible');
        sfBtn.classList.remove('sf-open');
      }
    });
    var copyCircle = document.getElementById('sf-copy-link');
    if (copyCircle) {
      copyCircle.addEventListener('click', function(e) {
        e.preventDefault();
        try {
          navigator.clipboard.writeText(window.location.href).then(function() {
            var orig = copyCircle.innerHTML;
            copyCircle.innerHTML = '<i class="fa-solid fa-check"></i>';
            setTimeout(function() { copyCircle.innerHTML = orig; }, 1800);
          });
        } catch(err) {
          var ta = document.createElement('textarea');
          ta.value = window.location.href;
          document.body.appendChild(ta);
          ta.select();
          document.execCommand('copy');
          document.body.removeChild(ta);
        }
      });
    }
  }

  /* ══════════════════════════════════════════════
     6. SCROLL REVEAL
     ══════════════════════════════════════════════ */
  (function() {
    var items = document.querySelectorAll('.scroll-reveal, .scroll-reveal-left, .scroll-reveal-right');
    if (!items.length || !window.IntersectionObserver) return;
    var obs = new IntersectionObserver(function(entries) {
      entries.forEach(function(e) {
        if (e.isIntersecting) { e.target.classList.add('revealed'); obs.unobserve(e.target); }
      });
    }, { threshold: 0.12 });
    items.forEach(function(el) { obs.observe(el); });
  })();

  /* ══════════════════════════════════════════════
     7. FAQ ACCORDION
     ══════════════════════════════════════════════ */
  document.querySelectorAll('.faq-question').forEach(function(q) {
    q.addEventListener('click', function() {
      var item   = this.closest('.faq-item');
      var isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item.open').forEach(function(i) { i.classList.remove('open'); });
      if (!isOpen) item.classList.add('open');
    });
  });

  /* ══════════════════════════════════════════════
     8. ACTIVE MENU HIGHLIGHT — robust page detection
     ══════════════════════════════════════════════ */
  (function() {
    var path = window.location.pathname;
    var currentPage = path.split('/').pop() || 'index.html';
    if (!currentPage || currentPage === '') currentPage = 'index.html';

    document.querySelectorAll('#mainmenu a.menu-item').forEach(function(link) {
      var href = (link.getAttribute('href') || '').split('?')[0].split('#')[0];
      var hPage = href.split('/').pop();

      var isMatch = false;

      // Exact filename match
      if (hPage === currentPage) isMatch = true;

      // Home page edge cases
      if ((currentPage === '' || currentPage === 'index.html') && (hPage === 'index.html' || href === '/' || href === './')) isMatch = true;

      // Partial path match (e.g. href="services.html" on /adswibe/services.html)
      if (!isMatch && path.endsWith('/' + hPage)) isMatch = true;

      /* Active highlight disabled — navbar stays uniform on all pages */
      link.classList.remove('active');
    });
  })();

  /* ══════════════════════════════════════════════
     9. SMOOTH ANCHOR SCROLL (with header offset)
     ══════════════════════════════════════════════ */
  document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
      var targetId = this.getAttribute('href');
      if (targetId === '#' || targetId === '#!') return;
      var target = document.querySelector(targetId);
      if (!target) return;
      e.preventDefault();
      var headerH = (document.querySelector('header') || { offsetHeight: 80 }).offsetHeight;
      var targetPos = target.getBoundingClientRect().top + window.pageYOffset - headerH - 10;
      window.scrollTo({ top: Math.max(0, targetPos), behavior: 'smooth' });
    });
  });

  /* ══════════════════════════════════════════════
     10. EXTRA-WRAP PANEL — keep hidden
     ══════════════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', function() {
    var ew = document.getElementById('extra-wrap');
    if (ew) { ew.classList.remove('open'); }
    document.addEventListener('click', function(e) {
      if (!ew) return;
      if (!ew.contains(e.target) && e.target.id !== 'btn-extra') {
        ew.classList.remove('open');
      }
    });
  });

  /* ══════════════════════════════════════════════
     11. SERVICE MODAL — body class for z-index
     ══════════════════════════════════════════════ */
  (function() {
    var overlays = document.querySelectorAll('.service-modal-overlay');
    overlays.forEach(function(overlay) {
      var mo = new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
          if (m.attributeName === 'class') {
            if (overlay.classList.contains('active')) {
              document.body.classList.add('modal-open');
              /* Fire event so navbar hides immediately */
              document.dispatchEvent(new CustomEvent('modalOpened'));
            } else {
              document.body.classList.remove('modal-open');
              /* Fire event so navbar can re-show */
              document.dispatchEvent(new CustomEvent('modalClosed'));
            }
          }
        });
      });
      mo.observe(overlay, { attributes: true });
    });
  })();

  /* ══════════════════════════════════════════════
     12. RIPPLE EFFECT on buttons
     ══════════════════════════════════════════════ */
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.btn-main, a.btn-main, .btn-wa-service, a.btn-wa-service');
    if (!btn) return;
    var rect   = btn.getBoundingClientRect();
    var size   = Math.max(rect.width, rect.height);
    var x      = e.clientX - rect.left - size / 2;
    var y      = e.clientY - rect.top  - size / 2;
    var ripple = document.createElement('span');
    ripple.className = 'btn-ripple';
    ripple.style.cssText = 'width:' + size + 'px;height:' + size + 'px;left:' + x + 'px;top:' + y + 'px;';
    btn.appendChild(ripple);
    setTimeout(function() { ripple.remove(); }, 600);
  });

  /* ══════════════════════════════════════════════
     13. FORM VALIDATION
     ══════════════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form').forEach(function(form) {
      form.querySelectorAll('input[required], textarea[required]').forEach(function(field) {
        field.addEventListener('blur', function() {
          if (!this.value.trim()) this.classList.add('is-invalid');
          else this.classList.remove('is-invalid');
        });
        if (field.type === 'email') {
          field.addEventListener('blur', function() {
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim())) this.classList.add('is-invalid');
            else this.classList.remove('is-invalid');
          });
        }
      });
    });
  });

  /* ══════════════════════════════════════════════
     14. BACK-TO-TOP — keep hidden
     ══════════════════════════════════════════════ */
  var btt = document.getElementById('back-to-top');
  if (btt) { btt.style.display = 'none'; btt.style.visibility = 'hidden'; }

})();
