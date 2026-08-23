(() => {
  'use strict';

  const qsa = (selector, root = document) => [...root.querySelectorAll(selector)];
  const dict = {
    'Dashboard':'ড্যাশবোর্ড','Operations':'অপারেশন','Customers & Billing':'গ্রাহক ও বিলিং','Customers':'গ্রাহক','Add Customer':'গ্রাহক যোগ করুন','Collection':'কালেকশন','Collection Report':'কালেকশন রিপোর্ট','Network':'নেটওয়ার্ক','Networking':'নেটওয়ার্কিং','MikroTik Routers':'MikroTik রাউটার','Customer Networking':'গ্রাহক নেটওয়ার্কিং','Hotspot':'হটস্পট','OLT':'OLT','Enforcement Audit':'এনফোর্সমেন্ট অডিট','Management':'ম্যানেজমেন্ট','Subscription':'সাবস্ক্রিপশন','Tenants / Admins':'টেন্যান্ট / অ্যাডমিন','Platform Billing':'প্ল্যাটফর্ম বিলিং','Logout':'লগআউট','Back':'পেছনে','Network Health':'নেটওয়ার্ক স্বাস্থ্য','Router Status':'রাউটার স্ট্যাটাস','Online':'অনলাইন','Offline':'অফলাইন','Active':'সক্রিয়','Suspended':'স্থগিত','Overdue':'বকেয়া','Refresh':'রিফ্রেশ','Refresh Status':'স্ট্যাটাস রিফ্রেশ','Search':'খুঁজুন','Save':'সংরক্ষণ','Cancel':'বাতিল','Create':'তৈরি করুন','Update':'আপডেট','Delete':'ডিলিট','Edit':'এডিট','View':'দেখুন','Action':'অ্যাকশন','Actions':'অ্যাকশন','Status':'স্ট্যাটাস','Name':'নাম','Phone':'ফোন','Address':'ঠিকানা','Username':'ইউজারনেম','Service':'সার্ভিস','Uptime':'আপটাইম','Caller ID':'কলার আইডি','Total':'মোট','Total Customers':'মোট গ্রাহক','Active Customers':'সক্রিয় গ্রাহক','Active PPPoE Customers':'সক্রিয় PPPoE গ্রাহক','Recent active customers':'সাম্প্রতিক সক্রিয় গ্রাহক','Total Routers':'মোট রাউটার','Online Routers':'অনলাইন রাউটার','Offline Routers':'অফলাইন রাউটার','Live RouterOS snapshot':'লাইভ RouterOS স্ন্যাপশট','Recent Payments':'সাম্প্রতিক পেমেন্ট','Recent Customers':'সাম্প্রতিক গ্রাহক','Quick Actions':'দ্রুত কাজ','Daily Operations':'দৈনন্দিন অপারেশন','Good Morning':'শুভ সকাল','New Customer':'নতুন গ্রাহক','Today':'আজ','Monthly Collection':'মাসিক কালেকশন','Today\'s Collection':'আজকের কালেকশন','Outstanding':'বকেয়া','Due / Outstanding':'বকেয়া / পাওনা','Overdue Invoices':'বকেয়া ইনভয়েস','Service Health':'সার্ভিস স্বাস্থ্য','Current month':'চলতি মাস','Currently active':'বর্তমানে সক্রিয়','Needs attention':'মনোযোগ প্রয়োজন','Current receivable':'বর্তমান পাওনা','Completed today':'আজ সম্পন্ন','Search customer, phone or code…':'গ্রাহক, ফোন বা কোড খুঁজুন…','Search customer, phone or code...':'গ্রাহক, ফোন বা কোড খুঁজুন...','View live status →':'লাইভ স্ট্যাটাস দেখুন →','Manage Network →':'নেটওয়ার্ক পরিচালনা করুন →','Router management & PPPoE':'রাউটার ম্যানেজমেন্ট ও PPPoE','Optical line terminal management':'অপটিক্যাল লাইন টার্মিনাল ম্যানেজমেন্ট','Users, profiles & sessions':'ইউজার, প্রোফাইল ও সেশন','PPPoE reconciliation & audit':'PPPoE রিকনসিলিয়েশন ও অডিট','Collection Overview':'কালেকশন সারাংশ','Completed payments · last 6 months':'সম্পন্ন পেমেন্ট · শেষ ৬ মাস','View report →':'রিপোর্ট দেখুন →','Service status':'সার্ভিস স্ট্যাটাস','Subscriber service status':'গ্রাহক সার্ভিস স্ট্যাটাস','Collect customer payment':'গ্রাহকের পেমেন্ট গ্রহণ করুন','Create a new subscriber':'নতুন গ্রাহক তৈরি করুন','Your ISP operational overview at a glance.':'আপনার ISP-এর অপারেশনাল সারাংশ এক নজরে।','ISP CONTROL CENTER':'ISP কন্ট্রোল সেন্টার','Network infrastructure management.':'নেটওয়ার্ক অবকাঠামো পরিচালনা।','No data found.':'কোনো ডাটা পাওয়া যায়নি।','Loading…':'লোড হচ্ছে…','Loading...':'লোড হচ্ছে...','No records found.':'কোনো রেকর্ড পাওয়া যায়নি।','No customers found.':'কোনো গ্রাহক পাওয়া যায়নি।','No routers found.':'কোনো রাউটার পাওয়া যায়নি।','Live RouterOS Snapshot':'লাইভ RouterOS স্ন্যাপশট','Load Session':'সেশন লোড করুন','Disconnect':'ডিসকানেক্ট','Profile':'প্রোফাইল','Profiles':'প্রোফাইলসমূহ','Users':'ইউজার','Sessions':'সেশন','Traffic':'ট্রাফিক','Logs':'লগ','Settings':'সেটিংস','Reports':'রিপোর্ট','Billing':'বিলিং','Payments':'পেমেন্ট','Packages':'প্যাকেজ','Resellers':'রিসেলার','Inventory':'ইনভেন্টরি','POS':'POS','BTRC':'BTRC','Audit Logs':'অডিট লগ','Login':'লগইন','Sign Up':'সাইন আপ','Password':'পাসওয়ার্ড','Email':'ইমেইল','Submit':'সাবমিট','Confirm':'নিশ্চিত করুন','Close':'বন্ধ করুন','Open':'খুলুন','Details':'বিস্তারিত','Date':'তারিখ','Time':'সময়','Reference':'রেফারেন্স','Amount':'পরিমাণ','Customer':'গ্রাহক','Router':'রাউটার','MikroTik':'MikroTik','PPPoE':'PPPoE','Import':'ইমপোর্ট','Export':'এক্সপোর্ট','Test Connection':'কানেকশন টেস্ট','Edit Router':'রাউটার এডিট','Add Router':'রাউটার যোগ করুন','Router Status':'রাউটার স্ট্যাটাস','Connection':'কানেকশন','Connected':'সংযুক্ত','Disconnected':'সংযোগ বিচ্ছিন্ন','Failed':'ব্যর্থ','Success':'সফল','Mismatch':'মিল নেই','Warning':'সতর্কতা','Error':'ত্রুটি','Yes':'হ্যাঁ','No':'না'
  };
  const reverse = Object.fromEntries(Object.entries(dict).map(([en, bn]) => [bn, en]));
  const originals = new WeakMap();
  let current = 'en';

  const ignoredTextNode = node => {
    const parent = node.parentElement;
    return !parent || ['SCRIPT','STYLE','NOSCRIPT','INPUT','TEXTAREA','OPTION'].includes(parent.tagName) || parent.closest('[data-no-translate],.global-language-switch');
  };

  const translateText = (root, lang) => {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(node => {
      if (ignoredTextNode(node) || !node.nodeValue.trim()) return;
      let original = originals.get(node);
      if (original === undefined) {
        const text = node.nodeValue.trim();
        original = reverse[text] || text;
        originals.set(node, original);
      }
      const mapped = lang === 'bn' ? (dict[original] || original) : original;
      const raw = node.nodeValue;
      const lead = raw.match(/^\s*/)?.[0] || '';
      const trail = raw.match(/\s*$/)?.[0] || '';
      node.nodeValue = lead + mapped + trail;
    });
  };

  const translateAttributes = lang => {
    qsa('[data-i18n-placeholder],[data-i18n-title],[data-i18n-aria-label]').forEach(el => {
      const mapping = [['data-i18n-placeholder','placeholder'],['data-i18n-title','title'],['data-i18n-aria-label','aria-label']];
      mapping.forEach(([dataAttr, attr]) => {
        const key = el.getAttribute(dataAttr);
        if (!key) return;
        const original = reverse[key] || key;
        el.setAttribute(attr, lang === 'bn' ? (dict[original] || original) : original);
      });
    });
  };

  const updateButtons = () => {
    qsa('[data-language]').forEach(button => {
      const active = button.dataset.language === current;
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    qsa('[data-global-lang]').forEach(button => button.classList.toggle('active', button.dataset.globalLang === current));
  };

  const apply = lang => {
    current = lang === 'bn' ? 'bn' : 'en';
    document.documentElement.lang = current;
    document.documentElement.dataset.lang = current;
    try {
      localStorage.setItem('ispluka.language', current);
      document.cookie = `ispluka_language=${current}; Path=/; Max-Age=31536000; SameSite=Lax`;
    } catch (_) {}
    translateText(document.body, current);
    translateAttributes(current);
    updateButtons();
  };

  const installTheme = () => {
    if (document.getElementById('global-ui-style')) return;
    const style = document.createElement('style');
    style.id = 'global-ui-style';
    style.textContent = `
      :root{--bg:#f5f7fa;--surface:#fff;--surface-2:#f8fafc;--text:#172033;--muted:#667085;--border:#e3e7ee;--primary:#2563eb;--primary-dark:#1d4ed8;--radius:10px}
      body{background:var(--bg);color:var(--text)}
      .card,.content-card,.table-card,.form-section,.panel,.kpi-card,.network-main-card,.network-module,.quick-card{border-color:var(--border)!important;border-radius:var(--radius)!important;box-shadow:0 1px 3px rgba(15,23,42,.045)!important}
      .app-header{background:rgba(255,255,255,.98)!important;border-bottom-color:var(--border)!important}
      .sidebar{background:#111827!important;border-right:1px solid #273142!important}
      .nav-mark{display:none!important}
      .nav a,.sidebar-logout-form button{gap:0!important}
      .nav a{padding:.66rem .78rem!important}
      .nav-section{padding-left:.78rem!important}
      .dashboard-page .hero-row{display:none!important}
      .dashboard-page .dashboard-main{padding-top:1.35rem!important}
      .dashboard-page .nav-brand-mini{padding:.35rem .75rem .8rem!important;margin-bottom:.15rem!important}
      .dashboard-page .nav-brand-mini>span{display:none!important}
      .dashboard-page .nav-brand-mini div{margin:0!important}
      .dashboard-page .quick-card>span,.dashboard-page .network-module .module-icon{display:none!important}
      .dashboard-page .quick-card{gap:.75rem!important}
      .global-language-switch{display:flex;gap:2px;align-items:center;position:fixed;z-index:99999;top:10px;right:12px;padding:3px;background:#fff;border:1px solid var(--border);border-radius:8px;box-shadow:0 4px 16px rgba(15,23,42,.08)}
      .global-language-switch button{min-height:30px;padding:3px 9px;border:0;border-radius:6px;background:transparent;color:#667085;font-size:12px;font-weight:700;cursor:pointer}
      .global-language-switch button.active{background:var(--primary);color:#fff}
      .global-language-switch button:hover{background:#eff6ff;color:var(--primary-dark)}
      .language-switch{display:flex;gap:2px;padding:3px;background:#f8fafc;border:1px solid var(--border);border-radius:8px}
      .language-switch .language-btn{min-height:30px;padding:3px 9px;background:transparent!important;color:#667085!important;border:0!important;box-shadow:none!important}
      .language-switch .language-btn.active{background:var(--primary)!important;color:#fff!important}
      @media(max-width:767px){.global-language-switch{top:8px;right:8px}.global-language-switch button{min-height:28px;padding:2px 7px}.dashboard-page .header-tools .language-switch{display:none!important}}
    `;
    document.head.appendChild(style);
  };

  const addSwitcher = () => {
    const existing = document.querySelector('.language-switch');
    if (existing) return;
    const wrap = document.createElement('div');
    wrap.className = 'global-language-switch';
    wrap.innerHTML = '<button type="button" data-global-lang="en" aria-pressed="false">EN</button><button type="button" data-global-lang="bn" aria-pressed="false">বাংলা</button>';
    document.body.appendChild(wrap);
  };

  const init = () => {
    installTheme();
    addSwitcher();
    let lang = 'en';
    try {
      lang = localStorage.getItem('ispluka.language') || decodeURIComponent((document.cookie.match(/(?:^|;\s*)ispluka_language=([^;]+)/) || [])[1] || '') || document.documentElement.dataset.lang || 'en';
    } catch (_) {}
    apply(lang);
  };

  document.addEventListener('click', event => {
    const button = event.target.closest('[data-global-lang],[data-language]');
    if (!button) return;
    event.preventDefault();
    event.stopPropagation();
    apply(button.dataset.globalLang || button.dataset.language);
  }, true);

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once:true });
  else init();
})();
