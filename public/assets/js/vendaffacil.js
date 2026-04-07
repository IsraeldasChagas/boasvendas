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

  function initSidebarCollapse() {
    var wrapper = document.querySelector('.vf-app-wrapper');
    if (!wrapper) return;

    var btn = document.querySelector('[data-vf-sidebar-collapse-toggle]');
    if (!btn) return;

    function apply(collapsed) {
      wrapper.classList.toggle('vf-sidebar-collapsed', collapsed);
      var icon = btn.querySelector('i');
      if (icon) {
        icon.className = collapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
      }
      btn.setAttribute('aria-label', collapsed ? 'Expandir menu' : 'Recolher menu');
      btn.setAttribute('title', collapsed ? 'Expandir menu' : 'Recolher menu');
    }

    var saved = null;
    try {
      saved = window.localStorage.getItem('vf_sidebar_collapsed');
    } catch (e) {}

    apply(saved === '1');

    btn.addEventListener('click', function () {
      var collapsed = wrapper.classList.contains('vf-sidebar-collapsed');
      var next = !collapsed;
      apply(next);
      try {
        window.localStorage.setItem('vf_sidebar_collapsed', next ? '1' : '0');
      } catch (e) {}
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
      initSidebarCollapse();
      initSubmenus();
    });
  } else {
    initSidebar();
    initSidebarCollapse();
    initSubmenus();
  }
})();
