(() => {
  'use strict';
  const qs = (s, r = document) => r.querySelector(s);
  const isRoot = ['/', '/login', '/signup'].includes(location.pathname.replace(/\/$/, '') || '/');
  const addBack = () => {
    if (isRoot || qs('[data-global-back]')) return;
    const main = qs('main');
    if (!main) return;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'page-back-button';
    button.dataset.globalBack = '1';
    button.innerHTML = '<span aria-hidden="true">←</span><span>Back</span>';
    button.addEventListener('click', () => {
      if (window.history.length > 1) window.history.back();
      else window.location.href = '/';
    });
    const title = qs('.page-title', main);
    if (title) title.insertAdjacentElement('afterbegin', button);
    else {
      const heading = qs('h1', main);
      if (heading?.parentElement) heading.parentElement.insertBefore(button, heading);
      else main.insertBefore(button, main.firstChild);
    }
  };
  const init = () => addBack();
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
