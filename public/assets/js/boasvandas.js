/**
 * Boas Vendas — UI leve (sem frameworks JS)
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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
  } else {
    initSidebar();
  }
})();
