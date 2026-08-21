(()=>{
const router=document.querySelector('#pppoe-router');
const load=document.querySelector('#pppoe-load');
const table=document.querySelector('#pppoe-sessions');
const live=document.querySelector('#live-status');
if(!router||!load)return;
const style=document.createElement('style');
style.textContent=`
.pppoe-card .router-table{table-layout:fixed;min-width:980px}
.pppoe-card .router-table th,.pppoe-card .router-table td{box-sizing:border-box;vertical-align:middle}
.pppoe-card .router-table th:nth-child(1),.pppoe-card .router-table td:nth-child(1){width:54px;text-align:center}
.pppoe-card .router-table th:nth-child(2),.pppoe-card .router-table td:nth-child(2){width:190px}
.pppoe-card .router-table th:nth-child(3),.pppoe-card .router-table td:nth-child(3){width:100px}
.pppoe-card .router-table th:nth-child(4),.pppoe-card .router-table td:nth-child(4){width:150px}
.pppoe-card .router-table th:nth-child(5),.pppoe-card .router-table td:nth-child(5){width:180px}
.pppoe-card .router-table th:nth-child(6),.pppoe-card .router-table td:nth-child(6){width:130px;white-space:nowrap}
.pppoe-card .router-table th:nth-child(7),.pppoe-card .router-table td:nth-child(7){width:140px;text-align:center}
.pppoe-card .router-table td:nth-child(2),.pppoe-card .router-table td:nth-child(3),.pppoe-card .router-table td:nth-child(4),.pppoe-card .router-table td:nth-child(5){overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pppoe-card .router-table td:nth-child(7) button{display:inline-flex!important;align-items:center;justify-content:center;min-width:105px!important;white-space:nowrap}
.pppoe-card #pppoe-uptime-sort{cursor:pointer;user-select:none}
`;
document.head.appendChild(style);
let timer=null;
const loadNow=()=>{if(router.value)load.click();};
router.addEventListener('change',()=>{clearTimeout(timer);loadNow();});
const autoSelect=()=>{
 if(router.options.length<=1)return false;
 if(!router.value){
   const first=[...router.options].find(o=>o.value);
   if(first)router.value=first.value;
 }
 loadNow();
 return true;
};
const refresh=()=>{if(router.value)loadNow();};
const observer=new MutationObserver(()=>{
 if(router.options.length>1){
   observer.disconnect();
   autoSelect();
   if(live)live.textContent='Live RouterOS snapshot · PPPoE session data';
 }
});
observer.observe(router,{childList:true});
setTimeout(autoSelect,350);
timer=setInterval(refresh,30000);
window.addEventListener('beforeunload',()=>{clearInterval(timer);observer.disconnect();});
})();
