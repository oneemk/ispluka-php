(() => {
  'use strict';
  const qsa = (s, r = document) => [...r.querySelectorAll(s)];
  const dicts = {
    en: { Dashboard:'Dashboard', Overview:'Overview', 'Total Customers':'Total Customers', 'Active Services':'Active Services', 'Suspended Services':'Suspended Services', 'Overdue Invoices':'Overdue Invoices', Outstanding:'Outstanding', "Today's Collection":"Today's Collection", 'Monthly Collection':'Monthly Collection', 'New Customers Today':'New Customers Today', 'Network Health':'Network Health', 'Total MikroTik Routers':'Total MikroTik Routers', 'Online Routers':'Online Routers', 'Offline Routers':'Offline Routers', 'Network Audit':'Network Audit', 'Collection Overview':'Collection Overview', 'Full Report':'Full Report', 'Recent Collections':'Recent Collections', 'Recent Customers':'Recent Customers', 'Quick Actions':'Quick Actions', 'Add Customer':'Add Customer', 'Create a new subscriber':'Create a new subscriber', Collection:'Collection', 'Collect customer payment':'Collect customer payment', 'MikroTik Routers':'MikroTik Routers', 'Manage and test routers':'Manage and test routers', 'Customer Networking':'Customer Networking', 'Live PPPoE and usage':'Live PPPoE and usage', 'Customers & Billing':'Customers & Billing', Customers:'Customers', 'Collection Report':'Collection Report', Network:'Network', 'Enforcement Audit':'Enforcement Audit', Hotspot:'Hotspot', OLT:'OLT', Management:'Management', Subscription:'Subscription', Logout:'Logout', 'No collection history available.':'No collection history available.', 'No bill collection recorded today.':'No bill collection recorded today.', 'No customers added this month.':'No customers added this month.', Open:'Open' },
    bn: { Dashboard:'ড্যাশবোর্ড', Overview:'সারসংক্ষেপ', 'Total Customers':'মোট গ্রাহক', 'Active Services':'সক্রিয় সার্ভিস', 'Suspended Services':'স্থগিত সার্ভিস', 'Overdue Invoices':'বকেয়া ইনভয়েস', Outstanding:'বকেয়া টাকা', "Today's Collection":'আজকের সংগ্রহ', 'Monthly Collection':'মাসিক সংগ্রহ', 'New Customers Today':'আজকের নতুন গ্রাহক', 'Network Health':'নেটওয়ার্ক স্বাস্থ্য', 'Total MikroTik Routers':'মোট MikroTik রাউটার', 'Online Routers':'অনলাইন রাউটার', 'Offline Routers':'অফলাইন রাউটার', 'Network Audit':'নেটওয়ার্ক অডিট', 'Collection Overview':'সংগ্রহের সারসংক্ষেপ', 'Full Report':'পূর্ণ রিপোর্ট', 'Recent Collections':'সাম্প্রতিক সংগ্রহ', 'Recent Customers':'সাম্প্রতিক গ্রাহক', 'Quick Actions':'দ্রুত কাজ', 'Add Customer':'গ্রাহক যোগ করুন', 'Create a new subscriber':'নতুন সাবস্ক্রাইবার তৈরি করুন', Collection:'সংগ্রহ', 'Collect customer payment':'গ্রাহকের পেমেন্ট গ্রহণ করুন', 'MikroTik Routers':'MikroTik রাউটার', 'Manage and test routers':'রাউটার পরিচালনা ও পরীক্ষা করুন', 'Customer Networking':'গ্রাহক নেটওয়ার্কিং', 'Live PPPoE and usage':'লাইভ PPPoE ও ব্যবহার', 'Customers & Billing':'গ্রাহক ও বিলিং', Customers:'গ্রাহক', 'Collection Report':'সংগ্রহ রিপোর্ট', Network:'নেটওয়ার্ক', 'Enforcement Audit':'এনফোর্সমেন্ট অডিট', Hotspot:'হটস্পট', OLT:'OLT', Management:'ম্যানেজমেন্ট', Subscription:'সাবস্ক্রিপশন', Logout:'লগআউট', 'No collection history available.':'কোনো সংগ্রহের ইতিহাস নেই।', 'No bill collection recorded today.':'আজ কোনো বিল সংগ্রহ রেকর্ড হয়নি।', 'No customers added this month.':'এই মাসে কোনো গ্রাহক যোগ হয়নি।', Open:'খুলুন' }
  };

  const translateText = (root, dict) => {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(node => {
      const value = node.nodeValue?.trim();
      if (!value || !Object.prototype.hasOwnProperty.call(dict, value)) return;
      node.nodeValue = node.nodeValue.replace(value, dict[value]);
    });
  };

  const apply = (lang) => {
    const normalized = lang === 'bn' ? 'bn' : 'en';
    const d = dicts[normalized];
    qsa('[data-i18n]').forEach(el => { const key = el.getAttribute('data-i18n'); if (Object.prototype.hasOwnProperty.call(d, key)) el.textContent = d[key]; });
    qsa('[data-i18n-placeholder]').forEach(el => { const key = el.getAttribute('data-i18n-placeholder'); if (Object.prototype.hasOwnProperty.call(d, key)) el.setAttribute('placeholder', d[key]); });
    translateText(document.body, d);
    qsa('[data-language]').forEach(button => { const active = button.getAttribute('data-language') === normalized; button.classList.toggle('active', active); button.setAttribute('aria-pressed', active ? 'true' : 'false'); });
    document.documentElement.lang = normalized;
    try { localStorage.setItem('ispluka.language', normalized); } catch (_) {}
  };

  const bind = () => { qsa('[data-language]').forEach(button => button.addEventListener('click', () => apply(button.getAttribute('data-language')))); let lang = 'en'; try { lang = localStorage.getItem('ispluka.language') || 'en'; } catch (_) {} apply(lang); };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind, {once:true}); else bind();
})();
