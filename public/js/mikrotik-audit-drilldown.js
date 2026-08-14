(() => {
 const root=document.querySelector('[data-page="mikrotik-audit"]'); if(!root)return;
 const list=root.querySelector('[data-audit-list]');
 const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 const detail=r=>{const d=r.details||{};return `<div class="audit-detail"><b>Details</b><dl><dt>Finding</dt><dd>${esc(r.finding_type)}</dd><dt>Router</dt><dd>#${esc(r.router_id)}</dd><dt>First seen</dt><dd>${esc(r.first_seen_at)}</dd><dt>Last seen</dt><dd>${esc(r.last_seen_at)}</dd><dt>Resolved</dt><dd>${esc(r.resolved_at||'—')}</dd>${Object.entries(d).slice(0,8).map(([k,v])=>`<dt>${esc(k)}</dt><dd>${esc(typeof v==='object'?JSON.stringify(v):v)}</dd>`).join('')}</dl></div>`};
 window.mikrotikAuditToggleDetail=(el)=>{const card=el.closest('.audit-card');const detail=card?.querySelector('.audit-detail');if(detail)detail.hidden=!detail.hidden;};
 window.mikrotikAuditRenderDrilldown=(rows)=>{list.innerHTML=rows.map(r=>`<article class="audit-card"><div><b>${esc(r.username)}</b><span>${esc(r.finding_type)}</span></div><strong>${esc(r.severity)}</strong><p>${esc(r.message)}</p><small>Router #${esc(r.router_id)} · ${esc(r.status)} · ${esc(r.last_seen_at)}</small><button type="button" class="btn btn-secondary" onclick="mikrotikAuditToggleDetail(this)">View details</button>${detail(r)}</article>`).join('')||'<p>No audit issues found.</p>';};
})();
