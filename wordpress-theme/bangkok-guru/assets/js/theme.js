(()=>{
  const root=document.documentElement;
  try {const saved=localStorage.getItem('bg-color-scheme');root.dataset.bgTheme=(saved==='dark'||saved==='light')?saved:(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');}catch(_){root.dataset.bgTheme='light';}
  const button=document.querySelector('.bg-theme-toggle');
  if(button){
    const reflect=()=>{button.setAttribute('aria-pressed',String(root.dataset.bgTheme==='dark'));button.setAttribute('aria-label',root.dataset.bgTheme==='dark'?'Switch to light mode':'Switch to dark mode');};
    reflect();button.addEventListener('click',()=>{const next=root.dataset.bgTheme==='dark'?'light':'dark';root.dataset.bgTheme=next;reflect();try{localStorage.setItem('bg-color-scheme',next);}catch(_){}});
  }
  // Price preview is informational only: PHP remains the source of truth for price and inventory.
  document.querySelectorAll('.bg-form-booking').forEach(form=>{
    const select=form.querySelector('select[name="departure"]');
    const quantity=form.querySelector('input[name="travellers"]');
    const target=form.querySelector('[data-bg-estimate]');
    if(!select||!quantity||!target)return;
    const update=()=>{
      const option=select.selectedOptions?.[0];if(!option)return;
      const price=Math.max(0,Number(option.dataset.price)||0);
      const available=Math.max(0,Number(option.dataset.left)||0);
      const count=Math.max(1,Math.min(20,Number(quantity.value)||1));
      const total=price*count;
      let fmt;
      try{fmt=new Intl.NumberFormat(document.documentElement.lang||'en-US',{style:'currency',currency:form.dataset.currency||'THB'}).format(total);}
      catch(_){fmt=total.toFixed(2);}
      const deposit=Math.round(total*Math.max(0,Math.min(100,Number(form.dataset.deposit)||0)))/100;
      let depositStr=deposit.toFixed(2);
      try{depositStr=new Intl.NumberFormat(document.documentElement.lang||'en-US',{style:'currency',currency:form.dataset.currency||'THB'}).format(deposit);}catch(_){}
      target.textContent=(available<count?'Waitlist request · ':'')+fmt+' estimated total · '+depositStr+' estimated deposit · '+available+' seats currently available.';
    };
    select.addEventListener('change',update);quantity.addEventListener('input',update);update();
  });
  const menu=document.querySelector('.bg-mobile-menu');
  if(menu){
    const nav=document.querySelector('.bg-menu');
    const close=()=>{menu.setAttribute('aria-expanded','false');nav?.classList.remove('is-open');};
    menu.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')==='true';menu.setAttribute('aria-expanded',String(!open));nav?.classList.toggle('is-open',!open);});
    nav?.querySelectorAll('a').forEach(link=>link.addEventListener('click',close));
    document.addEventListener('keydown',event=>{if(event.key==='Escape')close();});
    document.addEventListener('click',event=>{if(!menu.contains(event.target)&&!nav?.contains(event.target))close();});
  }
})();
