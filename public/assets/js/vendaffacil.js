/**
 * Vendaffacil — UI leve (sem frameworks JS)
 */
(function () {
  'use strict';

  function initSidebar() {
    var toggle = document.querySelector('[data-vf-sidebar-toggle]');
    var sidebar = document.querySelector('[data-vf-sidebar]');
    var backdrop = document.querySelector('[data-vf-sidebar-backdrop]');
    if (!toggle || !sidebar) return;

    function open() {
      sidebar.classList.add('vf-sidebar-open');
      if (backdrop) backdrop.classList.add('show');
      document.body.style.overflow = 'hidden';
    }

    function close() {
      sidebar.classList.remove('vf-sidebar-open');
      if (backdrop) backdrop.classList.remove('show');
      document.body.style.overflow = '';
    }

    toggle.addEventListener('click', function () {
      if (sidebar.classList.contains('vf-sidebar-open')) close();
      else open();
    });

    if (backdrop) {
      backdrop.addEventListener('click', close);
    }

    window.addEventListener('resize', function () {
      if (window.innerWidth >= 992) close();
    });
  }

  function initSubmenus() {
    document.querySelectorAll('[data-vf-submenu-toggle]').forEach(function (btn) {
      var content = btn.nextElementSibling;
      if (!content || !content.classList.contains('vf-submenu-content')) return;

      // Estado inicial (server-side)
      var expanded = btn.getAttribute('aria-expanded') === 'true';
      if (!expanded) content.classList.add('d-none');

      btn.addEventListener('click', function () {
        var isOpen = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        content.classList.toggle('d-none', isOpen);
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initSidebar();
      initSubmenus();
    });
  } else {
    initSidebar();
    initSubmenus();
  }
})();
