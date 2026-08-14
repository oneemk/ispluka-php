<?php declare(strict_types=1); ?>
<section class="networking-audit" data-page="mikrotik-audit">
  <header class="audit-header">
    <div><h1>MikroTik Sync Audit</h1><p>ERP ↔ MikroTik mismatch and inactivity findings.</p></div>
    <div class="audit-filters">
      <select name="status"><option value="">All status</option><option value="open">Open</option><option value="resolved">Resolved</option></select>
      <select name="severity"><option value="">All severity</option><option value="critical">Critical</option><option value="high">High</option><option value="warning">Warning</option><option value="info">Info</option></select>
      <select name="router"><option value="">All routers</option></select>
    </div>
  </header>
  <div class="audit-summary" aria-label="Audit summary">
    <article><strong data-stat="critical">0</strong><span>Critical</span></article>
    <article><strong data-stat="high">0</strong><span>High</span></article>
    <article><strong data-stat="warning">0</strong><span>Warning</span></article>
    <article><strong data-stat="open">0</strong><span>Open Issues</span></article>
  </div>
  <div class="audit-list" data-audit-list></div>
</section>
<script>
(() => {
 const root=document.querySelector('[data-page="mikrotik-audit"]'); if(!root)return;
 const list=root.querySelector('[data-audit-list]');
 const escapeHtml=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 const render=rows=>{list.innerHTML=rows.map(r=>`<article class="audit-card"><div><b>${escapeHtml(r.username)}</b><span>${escapeHtml(r.finding_type)}</span></div><strong>${escapeHtml(r.severity)}</strong><p>${escapeHtml(r.message)}</p><small>Router #${escapeHtml(r.router_id)} · ${escapeHtml(r.status)} · ${escapeHtml(r.last_seen_at)}</small></article>`).join('')||'<p>No audit issues found.</p>';};
 root.querySelectorAll('select').forEach(s=>s.addEventListener('change',()=>window.dispatchEvent(new CustomEvent('mikrotik-audit-filter-change'))));
 window.mikrotikAuditRender=render;
})();
</script>
