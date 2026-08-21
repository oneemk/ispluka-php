(() => {
  'use strict';
  const qs = (s, r = document) => r.querySelector(s);
  const qsa = (s, r = document) => [...r.querySelectorAll(s)];
  const toggle = qs('[data-menu-toggle]');
  const sidebar = qs('[data-sidebar]');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => { const open = sidebar.classList.toggle('is-open'); toggle.setAttribute('aria-expanded', String(open)); });
    qsa('[data-sidebar] a').forEach(a => a.addEventListener('click', () => { if (window.innerWidth < 768) sidebar.classList.remove('is-open'); }));
  }
  qsa('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!window.confirm(f.dataset.confirm || 'Are you sure?')) e.preventDefault(); }));
  qsa('[data-password-toggle]').forEach(btn => btn.addEventListener('click', () => { const input = qs(btn.dataset.passwordToggle); if (!input) return; input.type = input.type === 'password' ? 'text' : 'password'; btn.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password'); }));

  const translations = {
    en: {
      dashboard:'Dashboard', operations:'Operations', collection:'Collection', collection_report:'Collection Report', add_customer:'Add Customer', search_customer:'Search Customer',
      network:'Network', mikrotik_routers:'MikroTik Routers', customer_networking:'Customer Networking', hotspot:'Hotspot', olt:'OLT', network_audit:'Network Audit', management:'Management', subscription:'Subscription', tenants_admins:'Tenants / Admins', platform_billing:'Platform Billing',
      logout:'Logout', control_center:'ISP CONTROL CENTER', good_morning:'Good Morning', overview_subtitle:'Your ISP operational overview at a glance.', collect_payment:'Collect customer payment', create_subscriber:'Create a new subscriber', find_customer:'Find by name, phone or code', manage_network:'Manage network infrastructure',
      open:'Open →', overview:'OVERVIEW', business_snapshot:'Business Snapshot', total_customers:'Total Customers', today:'today', active_services:'Active Services', currently_active:'Currently active', suspended_services:'Suspended Services', service_status:'Service status', overdue_invoices_count:'Overdue Invoices', needs_attention:'Needs attention', outstanding:'Due / Outstanding', receivable:'Current receivable', today_collection:"Today's Collection", completed_today:'Completed today', monthly_collected:'Monthly Collection', current_month:'Current month', routers:'MikroTik Routers', online:'online', offline:'offline', network_health:'Network Health', online_routers:'Online Routers', offline_routers:'Offline Routers', total_routers:'Total Routers', financial:'FINANCIAL', collection_overview:'Collection Overview', last_six_months:'Completed payments · last 6 months', view_report:'View report →', no_collection:'Collection history will appear here after completed payments are recorded.', services:'SERVICES', service_health:'Service Health', subscriber_status:'Subscriber service status', active:'Active', suspended:'Suspended', overdue:'Overdue', payments:'PAYMENTS', recent_collections:'Recent Collections', latest_payments:'Latest completed payments', view_all:'View all →', no_payments:'No completed collections yet.', customers:'CUSTOMERS', recent_customers:'Recent Customers', newest_accounts:'Newest subscriber accounts', no_customers:'No customers found.', network_module_subtitle:'Network infrastructure management.'
    },
    bn: {
      dashboard:'ড্যাশবোর্ড', operations:'অপারেশন', collection:'কালেকশন', collection_report:'কালেকশন রিপোর্ট', add_customer:'গ্রাহক যোগ করুন', search_customer:'গ্রাহক খুঁজুন',
      network:'নেটওয়ার্ক', mikrotik_routers:'MikroTik রাউটার', customer_networking:'গ্রাহক নেটওয়ার্কিং', hotspot:'হটস্পট', olt:'OLT', network_audit:'নেটওয়ার্ক অডিট', management:'ম্যানেজমেন্ট', subscription:'সাবস্ক্রিপশন', tenants_admins:'টেন্যান্ট / অ্যাডমিন', platform_billing:'প্ল্যাটফর্ম বিলিং',
      logout:'লগআউট', control_center:'ISP কন্ট্রোল সেন্টার', good_morning:'শুভ সকাল', overview_subtitle:'আপনার ISP-এর অপারেশনাল সারাংশ এক নজরে।', collect_payment:'গ্রাহকের পেমেন্ট গ্রহণ করুন', create_subscriber:'নতুন গ্রাহক তৈরি করুন', find_customer:'নাম, ফোন বা কোড দিয়ে খুঁজুন', manage_network:'নেটওয়ার্ক অবকাঠামো পরিচালনা',
      open:'খুলুন →', overview:'ওভারভিউ', business_snapshot:'ব্যবসার সারাংশ', total_customers:'মোট গ্রাহক', today:'আজ', active_services:'সক্রিয় সার্ভিস', currently_active:'বর্তমানে সক্রিয়', suspended_services:'স্থগিত সার্ভিস', service_status:'সার্ভিসের অবস্থা', overdue_invoices_count:'বকেয়া ইনভয়েস', needs_attention:'মনোযোগ প্রয়োজন', outstanding:'বকেয়া / পাওনা', receivable:'বর্তমান পাওনা', today_collection:'আজকের কালেকশন', completed_today:'আজ সম্পন্ন', monthly_collected:'মাসিক কালেকশন', current_month:'চলতি মাস', routers:'MikroTik রাউটার', online:'অনলাইন', offline:'অফলাইন', network_health:'নেটওয়ার্ক স্বাস্থ্য', online_routers:'অনলাইন রাউটার', offline_routers:'অফলাইন রাউটার', total_routers:'মোট রাউটার', financial:'আর্থিক', collection_overview:'কালেকশন ওভারভিউ', last_six_months:'সম্পন্ন পেমেন্ট · শেষ ৬ মাস', view_report:'রিপোর্ট দেখুন →', no_collection:'সম্পন্ন পেমেন্ট যোগ হলে এখানে কালেকশন ইতিহাস দেখা যাবে।', services:'সার্ভিস', service_health:'সার্ভিস স্বাস্থ্য', subscriber_status:'গ্রাহক সার্ভিসের অবস্থা', active:'সক্রিয়', suspended:'স্থগিত', overdue:'বকেয়া', payments:'পেমেন্ট', recent_collections:'সাম্প্রতিক কালেকশন', latest_payments:'সর্বশেষ সম্পন্ন পেমেন্ট', view_all:'সব দেখুন →', no_payments:'এখনও কোনো সম্পন্ন কালেকশন নেই।', customers:'গ্রাহক', recent_customers:'সাম্প্রতিক গ্রাহক', newest_accounts:'সর্বশেষ তৈরি গ্রাহক অ্যাকাউন্ট', no_customers:'কোনো গ্রাহক পাওয়া যায়নি।', network_module_subtitle:'নেটওয়ার্ক অবকাঠামো পরিচালনা।'
    }
  };

  const ensureNetworkLinks = () => {
    const nav = qs('.dashboard-nav'); if (!nav) return;
    const section = qsa('.nav-section', nav).find(el => el.getAttribute('data-i18n') === 'network'); if (!section) return;
    const add = (href, key) => { if (qs('a[href="'+href+'"]', nav)) return; const a=document.createElement('a'); a.href=href; const s=document.createElement('span'); s.setAttribute('data-i18n',key); a.appendChild(s); section.insertAdjacentElement('afterend',a); };
    add('/networking/olt','olt');
    add('/networking/hotspot','hotspot');
  };

  const applyLanguage = (requested) => {
    const lang = requested === 'bn' ? 'bn' : 'en'; const dict = translations[lang];
    document.documentElement.lang = lang; document.documentElement.dataset.lang = lang;
    ensureNetworkLinks();
    qsa('[data-i18n]').forEach(el => { const key=el.getAttribute('data-i18n'); if(key && Object.prototype.hasOwnProperty.call(dict,key)) el.textContent=dict[key]; });
    qsa('[data-language]').forEach(btn => { const active=btn.getAttribute('data-language')===lang; btn.classList.toggle('active',active); btn.setAttribute('aria-pressed',active?'true':'false'); });
    try { localStorage.setItem('ispluka.language',lang); } catch (_) {}
    return lang;
  };
  window.applyIsplukaLanguage = applyLanguage;
  const initLanguage = () => { let saved='en'; try { saved=localStorage.getItem('ispluka.language')||'en'; } catch (_) {} applyLanguage(saved); qsa('[data-language]').forEach(btn => btn.addEventListener('click',()=>applyLanguage(btn.getAttribute('data-language')))); };
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initLanguage,{once:true}); else initLanguage();
  if('serviceWorker' in navigator) window.addEventListener('load',()=>{navigator.serviceWorker.register('/sw.js?v=6').catch(()=>{});});
})();
