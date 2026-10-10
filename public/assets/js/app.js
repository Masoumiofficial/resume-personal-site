/* Minimal progressive enhancement. The site is fully usable without JS.
   (Theme is applied before first paint by a tiny inline script in <head>.) */
(function () {
  'use strict';
  var root = document.documentElement;

  // ---- Theme toggle: light <-> dark, persisted; default follows OS ----
  function currentTheme() {
    var t = root.getAttribute('data-theme');
    if (t) return t;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  function syncToggle(btn) {
    var dark = currentTheme() === 'dark';
    btn.setAttribute('aria-pressed', String(dark));
    btn.setAttribute('aria-label', dark ? 'تغییر به حالت روشن' : 'تغییر به حالت تیره');
  }
  document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
    syncToggle(btn);
    btn.addEventListener('click', function () {
      var next = currentTheme() === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('theme', next); } catch (e) { /* storage blocked */ }
      document.querySelectorAll('[data-theme-toggle]').forEach(syncToggle);
    });
  });

  // ---- Mobile navigation (disclosure pattern) ----
  document.querySelectorAll('[data-nav-toggle]').forEach(function (btn) {
    var nav = document.getElementById(btn.getAttribute('aria-controls'));
    if (!nav) return;
    btn.addEventListener('click', function () {
      var open = btn.getAttribute('aria-expanded') !== 'true';
      btn.setAttribute('aria-expanded', String(open));
      nav.setAttribute('data-open', String(open));
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') {
        btn.setAttribute('aria-expanded', 'false');
        nav.setAttribute('data-open', 'false');
        btn.focus();
      }
    });
  });

  // ---- Print button (resume) ----
  document.querySelectorAll('[data-print]').forEach(function (btn) {
    btn.addEventListener('click', function () { window.print(); });
  });

  // ---- Confirm destructive actions (admin) ----
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });
})();
