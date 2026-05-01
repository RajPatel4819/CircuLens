/**
 * CircuLens Main JavaScript
 */
(function () {
  'use strict';

  // Auto-dismiss flash messages after 4 seconds
  function initFlashMessages() {
    const alerts = document.querySelectorAll('[role="alert"]');
    alerts.forEach(function (alert) {
      setTimeout(function () {
        alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-8px)';
        setTimeout(function () {
          if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
          }
        }, 400);
      }, 4000);
    });
  }

  // Highlight active nav link
  function initActiveNav() {
    const currentPath = window.location.pathname;
    document.querySelectorAll('nav a').forEach(function (link) {
      const href = link.getAttribute('href');
      if (href && currentPath.endsWith(href.split('/').pop())) {
        link.classList.add('active');
      }
    });
  }

  // Add loading spinner to forms on submit
  function initFormLoading() {
    document.querySelectorAll('form').forEach(function (form) {
      form.addEventListener('submit', function () {
        const btn = form.querySelector('button[type="submit"]');
        if (btn && !btn.dataset.noSpinner) {
          btn.disabled = true;
          const originalText = btn.innerHTML;
          btn.innerHTML = '<span class="spinner"></span>';
          // Re-enable after 10s as fallback
          setTimeout(function () {
            btn.disabled = false;
            btn.innerHTML = originalText;
          }, 10000);
        }
      });
    });
  }

  // Checkbox card styling toggle
  function initCheckboxCards() {
    document.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
      const card = cb.closest('label');
      if (!card) return;

      function updateStyle() {
        if (cb.checked) {
          card.classList.remove('border-gray-200');
          card.classList.add('border-blue-500', 'bg-blue-50');
        } else {
          card.classList.remove('border-blue-500', 'bg-blue-50');
          card.classList.add('border-gray-200');
        }
      }

      cb.addEventListener('change', updateStyle);
    });
  }

  // Confirm delete dialogs (for any data-confirm attribute)
  function initConfirmDialogs() {
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        const msg = el.dataset.confirm || 'Are you sure?';
        if (!window.confirm(msg)) {
          e.preventDefault();
          e.stopPropagation();
        }
      });
    });
  }

  // Simple search filter for tables (client-side)
  function initTableSearch() {
    const filterInput = document.getElementById('table-filter');
    if (!filterInput) return;

    filterInput.addEventListener('input', function () {
      const query = this.value.toLowerCase().trim();
      const rows  = document.querySelectorAll('tbody tr');
      rows.forEach(function (row) {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
      });
    });
  }

  // Notification badge update via API
  function initNotificationBadge() {
    const badge = document.getElementById('notif-count');
    if (!badge) return;

    fetch('/api/notifications.php')
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.unread > 0) {
          badge.textContent = data.unread;
          badge.style.display = 'inline-flex';
        } else {
          badge.style.display = 'none';
        }
      })
      .catch(function () {
        badge.style.display = 'none';
      });
  }

  // Initialize on DOM ready
  document.addEventListener('DOMContentLoaded', function () {
    initFlashMessages();
    initActiveNav();
    initFormLoading();
    initCheckboxCards();
    initConfirmDialogs();
    initTableSearch();
    initNotificationBadge();
  });
})();
