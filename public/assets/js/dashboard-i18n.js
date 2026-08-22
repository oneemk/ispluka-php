(() => {
  'use strict';
  const qs = (s, r = document) => r.querySelector(s);
  const qsa = (s, r = document) => [...r.querySelectorAll(s)];
  const dicts = {
    en: {
      customers_billing:'Customers & Billing', customers:'Customers', new_customer:'New Customer', router_status:'Router Status', refresh_status:'View live status →', olt_subtitle:'Optical line terminal management', hotspot_subtitle:'Users, profiles & sessions', audit_subtitle:'PPPoE reconciliation & audit', quick_actions:'QUICK ACTIONS', daily_operations:'Daily Operations', customer_networking_subtitle:'Live customer network information', dashboard_footer:'Billing · Customers · Network · Operations', manage:'Manage Network →'
    },
    bn: {
      customers_billing:'গ্রাহক ও বিলিং', customers:'গ্রাহক', new_customer:'নতুন গ্রাহক', router_status:'রাউটার স্ট্যাটাস', refresh_status:'লাইভ স্ট্যাটাস দেখুন →', olt_subtitle:'অপটিক্যাল লাইন টার্মিনাল পরিচালনা', hotspot_subtitle:'ইউজার, প্রোফাইল ও সেশন', audit_subtitle:'PPPoE রিকনসিলিয়েশন ও অডিট', quick_actions:'দ্রুত কাজ', daily_operations:'দৈনন্দিন অপারেশন', customer_networking_subtitle:'গ্রাহকের লাইভ নেটওয়ার্ক তথ্য', dashboard_footer:'বিলিং · গ্রাহক · নেটওয়ার্ক · অপারেশন', manage:'নেটওয়ার্ক পরিচালনা →'
    }
  };
  const apply = (lang) => { const d = dicts[lang === 'bn' ? 'bn' : 'en']; qsa('[data-i18n]').forEach(el => { const k = el.getAttribute('data-i18n'); if (Object.prototype.hasOwnProperty.call(d, k)) el.textContent = d[k]; }); };
  const bind = () => { qsa('[data-language]').forEach(b => b.addEventListener('click', () => apply(b.getAttribute('data-language')))); let lang = 'en'; try { lang = localStorage.getItem('ispluka.language') || 'en'; } catch (_) {} apply(lang); };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind, {once:true}); else bind();
})();
