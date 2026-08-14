(() => {
 const root=document.querySelector('[data-pppoe-usage-report]'); if(!root)return;
 const body=root.querySelector('[data-usage-body]');
 const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 const bytes=n=>{n=Number(n||0);const u=['B','KB','MB','GB','TB'];let i=0;while(n>=1024&&i<4){n/=1024;i++;}return `${n.toFixed(i?2:0)} ${u[i]}`;};
 const load=async()=>{body.innerHTML='<tr><td colspan="5">Loading…</td></tr>';const q=new URLSearchParams({router_id:root.dataset.routerId||'',username:root.dataset.username||'',from:root.dataset.from||'',to:root.dataset.to||''});try{const r=await fetch(`/api/networking/mikrotik/pppoe/usage?${q}`,{credentials:'same-origin',headers:{Accept:'application/json'}});if(!r.ok)throw new Error();const j=await r.json(),rows=j.data||[];body.innerHTML=rows.map(x=>`<tr><td>${esc(x.month_start)}</td><td>${bytes(x.rx_bytes)}</td><td>${bytes(x.tx_bytes)}</td><td>${Math.round(Number(x.online_seconds||0)/3600)} h</td><td>${esc(x.samples)}</td></tr>`).join('')||'<tr><td colspan="5">No usage history.</td></tr>';}catch(e){body.innerHTML='<tr><td colspan="5">Unable to load usage history.</td></tr>';}};
 load();
})();
