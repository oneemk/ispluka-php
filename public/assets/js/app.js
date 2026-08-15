(() => {
  'use strict';

  const qs = (s, r = document) => r.querySelector(s);
  const qsa = (s, r = document) => [...r.querySelectorAll(s)];

  const toggle = qs('[data-menu-toggle]');
  const sidebar = qs('[data-sidebar]');

  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      const open = sidebar.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });

    qsa('[data-sidebar] a').forEach(a => a.addEventListener('click', () => {
      if (window.innerWidth < 768) sidebar.classList.remove('is-open');
    }));
  }

  qsa('form[data-confirm]').forEach(f => f.addEventListener('submit', e => {
    if (!window.confirm(f.dataset.confirm || 'Are you sure?')) e.preventDefault();
  }));

  qsa('[data-password-toggle]').forEach(btn => btn.addEventListener('click', () => {
    const input = qs(btn.dataset.passwordToggle);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
  }));

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js?v=2').catch(() => {});
    });
  }
})();
