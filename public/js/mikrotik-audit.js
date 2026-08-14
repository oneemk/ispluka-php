(() => {
  const page = document.querySelector('[data-page="mikrotik-audit"]');
  if (!page) return;
  const list = page.querySelector('[data-audit-list]');
  const selects = page.querySelectorAll('select');
  const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  const params = () => {
    const q = new URLSearchParams();
    const status = page.querySelector('[name="status"]')?.value;
    const severity = page.querySelector('[name="severity"]')?.value;
    const router = page.querySelector('[name="router"]')?.value;
    if (status) q.set('status', status);
    if (severity) q.set('severity', severity);
    if (router) q.set('router_id', router);
    return q.toString();
  };
  const render = (rows) => {
    const counts = {critical:0,high:0,warning:0,open:0};
    rows.forEach(r => { if (counts[r.severity] !== undefined) counts[r.severity]++; if (r.status === 'open') counts.open++; });
    Object.entries(counts).forEach(([k,v]) => { const el = page.querySelector(`[data-stat="${k}"]`); if (el) el.textContent = v; });
    list.innerHTML = rows.map(r => `<article class="audit-card"><div><b>${escapeHtml(r.username)}</b><span>${escapeHtml(r.finding_type)}</span></div><strong>${escapeHtml(r.severity)}</strong><p>${escapeHtml(r.message)}</p><small>Router #${escapeHtml(r.router_id)} · ${escapeHtml(r.status)} · ${escapeHtml(r.last_seen_at)}</small></article>`).join('') || '<p>No audit issues found.</p>';
  };
  const load = async () => {
    list.innerHTML = '<p>Loading audit…</p>';
    try {
      const response = await fetch(`/api/networking/mikrotik/audit?${params()}`, {headers:{Accept:'application/json'}, credentials:'same-origin'});
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      render(Array.isArray(data) ? data : (data.data || []));
    } catch (e) { list.innerHTML = '<p>Unable to load audit data.</p>'; }
  };
  selects.forEach(s => s.addEventListener('change', load));
  load();
})();
