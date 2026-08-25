(() => {
  'use strict';
  const qsa = (s, r = document) => [...r.querySelectorAll(s)];
  const dicts = {
    en: {
      dashboard:'Dashboard', overview:'Overview', total_customers:'Total Customers', active_services:'Active Services', suspended_services:'Suspended Services', overdue_invoices:'Overdue Invoices', outstanding:'Outstanding', today_collection:"Today's Collection", monthly_collection:'Monthly Collection', new_customers_today:'New Customers Today', network_health:'Network Health', total_mikrotik_routers:'Total MikroTik Routers', online_routers:'Online Routers', offline_routers:'Offline Routers', network_audit:'Network Audit', collection_overview:'Collection Overview', full_report:'Full Report', recent_collections:'Recent Collections', recent_customers:'Recent Customers', quick_actions:'Quick Actions', add_customer:'Add Customer', add_customer_subtitle:'Create a new subscriber', collection:'Collection', collection_subtitle:'Collect customer payment', mikrotik_routers:'MikroTik Routers', mikrotik_subtitle:'Manage and test routers', customer_networking:'Customer Networking', customer_networking_subtitle:'Live PPPoE and usage', customers_billing:'Customers & Billing', customers:'Customers', collection_report:'Collection Report', network:'Network', enforcement_audit:'Enforcement Audit', hotspot:'Hotspot', olt:'OLT', management:'Management', subscription:'Subscription', logout:'Logout', customer_search:'Search customer, phone or code…', no_collection_history:'No collection history available.', no_today_collection:'No bill collection recorded today.', no_customers:'No customers added this month.', open:'Open'
    },
    bn: {
      dashboard:'ড্যাশবোর্ড', overview:'সারসংক্ষেপ', total_customers:'মোট গ্রাহক', active_services:'সক্রিয় সার্ভিস', suspended_services:'স্থগিত সার্ভিস', overdue_invoices:'বকেয়া ইনভয়েস', outstanding:'বকেয়া টাকা', today_collection:'আজকের সংগ্রহ', monthly_collection:'মাসিক সংগ্রহ', new_customers_today:'আজকের নতুন গ্রাহক', network_health:'নেটওয়ার্ক স্বাস্থ্য', total_mikrotik_routers:'মোট MikroTik রাউটার', online_routers:'অনলাইন রাউটার', offline_routers:'অফলাইন রাউটার', network_audit:'নেটওয়ার্ক অডিট', collection_overview:'সংগ্রহের সারসংক্ষেপ', full_report:'পূর্ণ রিপোর্ট', recent_collections:'সাম্প্রতিক সংগ্রহ', recent_customers:'সাম্প্রতিক গ্রাহক', quick_actions:'দ্রুত কাজ', add_customer:'গ্রাহক যোগ করুন', add_customer_subtitle:'নতুন সাবস্ক্রাইবার তৈরি করুন', collection:'সংগ্রহ', collection_subtitle:'গ্রাহকের পেমেন্ট গ্রহণ করুন', mikrotik_routers:'MikroTik রাউটার', mikrotik_subtitle:'রাউটার পরিচালনা ও পরীক্ষা করুন', customer_networking:'গ্রাহক নেটওয়ার্কিং', customer_networking_subtitle:'লাইভ PPPoE ও ব্যবহার', customers_billing:'গ্রাহক ও বিলিং', customers:'গ্রাহক', collection_report:'সংগ্রহ রিপোর্ট', network:'নেটওয়ার্ক', enforcement_audit:'এনফোর্সমেন্ট অডিট', hotspot:'হটস্পট', olt:'OLT', management:'ম্যানেজমেন্ট', subscription:'সাবস্ক্রিপশন', logout:'লগআউট', customer_search:'গ্রাহক, ফোন বা কোড খুঁজুন…', no_collection_history:'কোনো সংগ্রহের ইতিহাস নেই।', no_today_collection:'আজ কোনো বিল সংগ্রহ রেকর্ড হয়নি।', no_customers:'এই মাসে কোনো গ্রাহক যোগ হয়নি।', open:'খুলুন'
    }
  };

  const apply = (lang) => {
    const normalized = lang === 'bn' ? 'bn' : 'en';
    const d = dicts[normalized];
    qsa('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (Object.prototype.hasOwnProperty.call(d, key)) el.textContent = d[key];
    });
    qsa('[data-i18n-placeholder]').forEach(el => {
      const key = el.getAttribute('data-i18n-placeholder');
      if (Object.prototype.hasOwnProperty.call(d, key)) el.setAttribute('placeholder', d[key]);
    });
    qsa('[data-language]').forEach(button => {
      const active = button.getAttribute('data-language') === normalized;
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    document.documentElement.lang = normalized;
    try { localStorage.setItem('ispluka.language', normalized); } catch (_) {}
  };

  const bind = () => {
    qsa('[data-language]').forEach(button => button.addEventListener('click', () => apply(button.getAttribute('data-language'))));
    let lang = 'en';
    try { lang = localStorage.getItem('ispluka.language') || 'en'; } catch (_) {}
    apply(lang);
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind, {once:true}); else bind();
})();
