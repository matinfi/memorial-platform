jQuery(function($){
 $(document).on('submit','#avam-create-form',function(e){
  e.preventDefault();
  const f=$(this),fd=new FormData(this);
  fd.append('action','avam_save_memorial');fd.append('nonce',AVAM.nonce);fd.append('save_status',f.data('saveStatus')||'publish');
  const b=f.find('button[type=submit]'),notice=$('#avam-create-notice');
  b.prop('disabled',true).addClass('is-loading');notice.removeClass('avam-error avam-notice').text('');
  $.ajax({url:AVAM.ajax,type:'POST',data:fd,processData:false,contentType:false}).done(function(r){
   if(r.success){
    notice.text(r.data?.message||'ذخیره شد.').addClass('avam-notice');
    if(typeof window.avamPanelNavigate==='function')window.avamPanelNavigate(AVAM.account,true);
    else window.location.href=r.data.url;
   }else notice.text(r.data?.message||'خطا در ذخیره اطلاعات').addClass('avam-notice avam-error');
  }).fail(function(xhr){notice.text(xhr.responseJSON?.data?.message||'ارتباط با سرور برقرار نشد.').addClass('avam-notice avam-error');}).always(function(){b.prop('disabled',false).removeClass('is-loading');});
 });
 $(document).on('click','.avam-action-delete',function(){
  const b=$(this),id=b.data('id');
  if(!id||!window.confirm('این یادبود برای همیشه حذف شود؟ این عمل قابل بازگشت نیست.'))return;
  b.prop('disabled',true).text('در حال حذف...');
  $.post(AVAM.ajax,{action:'avam_delete_memorial',nonce:AVAM.nonce,id:id}).done(function(r){
   if(r.success){const row=b.closest('.avam-ref-table tr,.avam-dash-memorial');row.slideUp(220,function(){$(this).remove();});}
   else{alert(r.data?.message||'حذف انجام نشد.');b.prop('disabled',false).text('حذف');}
  }).fail(function(){alert('ارتباط با سرور برقرار نشد.');b.prop('disabled',false).text('حذف');});
 });
});

/* Mobile account dashboard navigation — v1.6.4 */
jQuery(function($){
 const dashboard=$('.avam-dashboard-reference');
 if(!dashboard.length)return;
 const toggle=dashboard.find('.avam-mobile-menu-toggle');
 const sidebar=dashboard.find('.avam-dash-sidebar');
 const backdrop=dashboard.find('.avam-mobile-menu-backdrop');
 const closeMenu=function(){
   dashboard.removeClass('mobile-menu-open');
   toggle.attr('aria-expanded','false');
   backdrop.attr('hidden',true);
   $('body').removeClass('avam-mobile-nav-open');
 };
 toggle.on('click',function(){
   const open=!dashboard.hasClass('mobile-menu-open');
   dashboard.toggleClass('mobile-menu-open',open);
   toggle.attr('aria-expanded',open?'true':'false');
   backdrop.attr('hidden',!open);
   $('body').toggleClass('avam-mobile-nav-open',open);
 });
 backdrop.on('click',closeMenu);
 sidebar.on('click','a',function(){closeMenu();});
 $(document).on('keydown',function(e){
   if(e.key==='Escape' && dashboard.hasClass('mobile-menu-open'))closeMenu();
 });
 $(window).on('resize',function(){
   if(window.innerWidth>760 && dashboard.hasClass('mobile-menu-open'))closeMenu();
 });
});

/* Save draft/publish intent without relying on submit-button serialization. */
jQuery(function($){
 $(document).on('click','#avam-create-form [data-save-status]',function(){
  const form=$('#avam-create-form');
  form.data('saveStatus',$(this).data('save-status'));
  form.trigger('submit');
 });
});

/* Accessible four-step memorial creation wizard */
jQuery(function($){
 const form=$('#avam-create-form'); if(!form.length)return;
 const sections=form.find('.avam-form-section'); if(sections.length<2)return;
 let active=0;
 form.addClass('avam-wizard-enabled');
 const progress=$('<div class="avam-wizard-progress" role="status" aria-live="polite"></div>');
 const nav=$('<div class="avam-wizard-nav"><button type="button" class="avam-wizard-prev">مرحله قبل</button><span class="avam-wizard-count"></span><button type="button" class="avam-wizard-next">مرحله بعد</button></div>');
 form.prepend(progress); form.find('.avam-form-submit').before(nav);
 function render(){
  sections.each(function(i){$(this).toggle(i===active);});
  progress.empty();
  sections.each(function(i){const label=$(this).find('.avam-form-section-title strong').first().text()||('مرحله '+(i+1));progress.append($('<span>').toggleClass('is-current',i===active).text((i+1)+' · '+label));});
  nav.find('.avam-wizard-prev').prop('disabled',active===0);
  nav.find('.avam-wizard-next').toggle(active<sections.length-1);
  nav.find('.avam-wizard-count').text('مرحله '+(active+1)+' از '+sections.length);
  form.find('.avam-form-submit').toggle(active===sections.length-1);
 }
 nav.on('click','.avam-wizard-prev',function(){if(active>0){active--;render();form[0].scrollIntoView({behavior:'smooth',block:'start'});}});
 nav.on('click','.avam-wizard-next',function(){
  const fields=sections.eq(active).find('input[required],textarea[required],select[required]');
  let valid=true; fields.each(function(){if(!this.checkValidity()){this.reportValidity();valid=false;return false;}});
  if(valid&&active<sections.length-1){active++;render();form[0].scrollIntoView({behavior:'smooth',block:'start'});}
 });
 render();
});


/* AJAX navigation inside the account app shell. */
(function($){
 let busy=false;
 function targetContent(doc){
  const root=doc.querySelector('#main')||doc.body;
  if(doc.querySelector('.avam-unified-shell')){
   const unified=doc.querySelector('#main.avam-unified-content');
   if(unified)return unified.innerHTML;
  }
  const dash=root.querySelector('.avam-dashboard-reference:not(.avam-unified-shell) .avam-dash-main');
  if(dash)return dash.innerHTML;
  const memorial=root.querySelector('.avam-memorial-content-view');if(memorial)return memorial.outerHTML;
  const selectors=['.avam-create-card','.avam-profile-settings','.avam-archive-page','.avam-auth-card','.avam-card','.avam-page'];
  for(const selector of selectors){const el=root.querySelector(selector);if(el)return el.outerHTML;}
  return root.innerHTML;
 }
 window.avamPanelNavigate=function(url,replace){
  if(busy)return;
  const destination=new URL(url,location.href);
  if(typeof AVAM!=='undefined' && AVAM.account && destination.pathname===new URL(AVAM.account,location.href).pathname){window.location.href=url;return;}
  busy=true;
  const shell=document.querySelector('.avam-dashboard-reference');
  const main=shell&&(shell.classList.contains('avam-unified-shell')?shell.querySelector('.avam-unified-content'):shell.querySelector('.avam-dash-main'));
  if(!main){busy=false;window.location.href=url;return;}
  shell.classList.add('avam-panel-loading');
  const old=main.innerHTML;
  fetch(url,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
   .then(res=>{if(!res.ok)throw new Error('request failed');return res.text();})
   .then(html=>{
    const doc=new DOMParser().parseFromString(html,'text/html');
    if(doc.querySelector('.avam-dashboard-reference:not(.avam-unified-shell)'))throw new Error('switch to account layout');
    doc.querySelectorAll('link[rel="stylesheet"]').forEach(link=>{if(link.href.includes('memorial-view.css')&&!document.querySelector('link[href="'+link.href+'"]'))document.head.appendChild(link.cloneNode(true));});
    const content=targetContent(doc);
    if(!content.trim())throw new Error('empty response');
    main.innerHTML=content;
    const title=doc.querySelector('title');if(title)document.title=title.textContent;
    if(replace)history.replaceState({avamPanel:true},'',url);else history.pushState({avamPanel:true},'',url);
    main.scrollIntoView({behavior:'smooth',block:'start'});
    shell.classList.remove('avam-panel-loading');
    busy=false;
   }).catch(()=>{
    main.innerHTML=old;shell.classList.remove('avam-panel-loading');busy=false;window.location.href=url;
   });
 };
 $(document).on('click','.avam-dashboard-reference .avam-dash-sidebar a, .avam-dashboard-reference .avam-ref-topbar a, .avam-dashboard-reference .avam-ref-table-panel a, .avam-dashboard-reference .avam-archive-page a, .avam-dashboard-reference .avam-dash-main .avam-create-back, .avam-dashboard-reference .avam-dash-main .avam-profile-settings a, .avam-unified-shell .avam-unified-content a',function(e){
  const a=this,href=a.href;
  if(!href||a.target||a.hasAttribute('download')||a.classList.contains('avam-dash-logout')||a.getAttribute('href')==='#')return;
  const u=new URL(href,location.href);
  if(u.origin!==location.origin)return;
  e.preventDefault();window.avamPanelNavigate(href,false);
 });
 $(document).on('submit','.avam-dashboard-reference .avam-ref-search, .avam-dashboard-reference .avam-archive-search',function(e){
  e.preventDefault();const data=new FormData(this),url=new URL(this.action||location.href,location.href);
  for(const [k,v] of data.entries()){if(String(v).trim())url.searchParams.set(k,v);else url.searchParams.delete(k);}
  window.avamPanelNavigate(url.href,false);
 });
 window.addEventListener('popstate',function(){if(document.querySelector('.avam-dashboard-reference'))window.avamPanelNavigate(location.href,true);});
})(jQuery);
