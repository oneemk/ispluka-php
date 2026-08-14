(() => {
 const root=document.querySelector('[data-page="mikrotik-audit"]'); if(!root)return;
 const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 window.pppoeLiveMetrics=(row,metrics)=>{const box=row.querySelector('[data-live-metrics]');if(!box)return;box.innerHTML=`<b>Live</b> · IP: ${esc(metrics?.active_ip||'—')} · Rx: ${esc(metrics?.rx_rate_bps??'—')} bps · Tx: ${esc(metrics?.tx_rate_bps??'—')} bps`};
 window.pppoeOpenRouter=(ip)=>{if(!ip)return;const url=`http://${ip}:8080/`;window.open(url,'_blank','noopener,noreferrer');};
})();
