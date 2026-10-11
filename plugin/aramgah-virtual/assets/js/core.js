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

/* Two-theme switcher: dark graphite is the default; the user's choice is persisted locally. */
jQuery(function($){
 const body=document.body;
 const key='avam-panel-theme';
 let saved='dark';
 try{saved=localStorage.getItem(key)||'dark';}catch(e){}
 function applyTheme(theme){
  const light=theme==='light';
  body.classList.toggle('avam-theme-light',light);
  body.classList.toggle('avam-theme-dark',!light);
  $('[data-avam-theme-toggle]').attr('aria-pressed',light?'true':'false');
  $('[data-avam-theme-label]').text(light?'حالت تیره':'حالت روشن');
  $('[data-avam-theme-toggle] [aria-hidden="true"]').html(light ? '<svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"/></svg>' : '<svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"/></svg>');
  try{localStorage.setItem(key,light?'light':'dark');}catch(e){}
 }
 applyTheme(saved);
 $(document).on('click','[data-avam-theme-toggle]',function(){
  applyTheme(body.classList.contains('avam-theme-light')?'dark':'light');
 });
});

/* Single global sidebar navigation — only the outer application shell owns the mobile menu. */
jQuery(function($){
 const dashboard=$('.avam-unified-shell').first();
 if(!dashboard.length)return;
 const toggle=dashboard.children('.avam-mobile-menu-toggle');
 const sidebar=dashboard.children('.avam-dash-sidebar');
 const backdrop=dashboard.children('.avam-mobile-menu-backdrop');
 const closeMenu=function(){
   dashboard.removeClass('mobile-menu-open');
   toggle.attr('aria-expanded','false');
   backdrop.prop('hidden',true);
   $('body').removeClass('avam-mobile-nav-open');
 };
 toggle.on('click',function(){
   const open=!dashboard.hasClass('mobile-menu-open');
   dashboard.toggleClass('mobile-menu-open',open);
   toggle.attr('aria-expanded',open?'true':'false');
   backdrop.prop('hidden',!open);
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


/* Profile avatar preview also works in the AJAX-loaded account settings page. */
jQuery(function($){
 $(document).on('change','#avam-profile-avatar',function(){
  const input=this,form=input.closest('.avam-profile-form'),file=input.files&&input.files[0];
  if(!form||!file)return;
  if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>8*1024*1024){
   input.value='';
   const notice=form.closest('.avam-profile-settings')?.querySelector('.avam-notice');
   if(notice){notice.textContent='فقط JPG، PNG یا WebP با حجم حداکثر ۸ مگابایت مجاز است.';notice.classList.add('avam-error');}
   return;
  }
  const preview=form.querySelector('[data-profile-avatar-preview]');
  if(preview){const img=document.createElement('img');const url=URL.createObjectURL(file);img.src=url;img.alt='پیش‌نمایش تصویر پروفایل';img.onload=()=>URL.revokeObjectURL(url);preview.replaceChildren(img);}
 });
});
/* Professional image selection: delegated handlers also work after in-app navigation. */
jQuery(function($){
 const maxBytes=8*1024*1024;
 const allowed=['image/jpeg','image/png','image/webp'];
 function showError(form,message){
  const notice=form.querySelector('#avam-create-notice');
  if(notice){notice.textContent=message;notice.classList.add('avam-notice','avam-error');}
 }
 function previewImage(file,container,alt){
  const url=URL.createObjectURL(file);
  const img=document.createElement('img');
  img.src=url;img.alt=alt;img.onload=()=>URL.revokeObjectURL(url);
  container.appendChild(img);
 }
 $(document).on('click','#avam-create-form .avam-gallery-remove',function(){const form=this.closest('#avam-create-form');const item=this.closest('[data-gallery-item]');if(item)item.remove();const galleryInput=form&&form.querySelector('input[name="gallery[]"]');const remaining=form?form.querySelectorAll('[data-gallery-item]').length:0;if(galleryInput)galleryInput.dataset.existingCount=String(remaining);});
 $(document).on('change','#avam-create-form input[type="file"][name="image"],#avam-create-form input[type="file"][name="gallery[]"]',function(){
  const input=this,form=input.closest('#avam-create-form');
  if(!form)return;
  const gallery=input.name==='gallery[]';
  const files=Array.from(input.files||[]);
  const existingCount=gallery?Number(input.dataset.existingCount||0):0;
  if(gallery&&existingCount+files.length>10){showError(form,'گالری می‌تواند در مجموع حداکثر ۱۰ تصویر داشته باشد. تعداد عکس‌های انتخاب‌شده را کاهش دهید.');input.value='';return;}
  for(const file of files){
   if(!allowed.includes(file.type)){showError(form,'فرمت «'+file.name+'» پشتیبانی نمی‌شود. فقط JPG، PNG و WebP مجاز است.');input.value='';return;}
   if(file.size>maxBytes){showError(form,'حجم «'+file.name+'» بیشتر از ۸ مگابایت است. لطفاً تصویر کوچک‌تری انتخاب کنید.');input.value='';return;}
  }
  const kind=gallery?'gallery':'featured';
  const preview=form.querySelector('[data-preview="'+kind+'"]');
  const filename=form.querySelector('[data-upload-filename="'+kind+'"]');
  if(!preview)return;
  preview.replaceChildren();preview.hidden=!files.length;
  if(gallery){files.forEach(file=>previewImage(file,preview,file.name));if(filename)filename.textContent=files.length?files.length+' تصویر انتخاب شد':'';}
  else if(files[0]){previewImage(files[0],preview,'پیش‌نمایش تصویر اصلی انتخاب‌شده');const caption=document.createElement('span');caption.textContent=files[0].name;preview.appendChild(caption);if(filename)filename.textContent=files[0].name;}
  const notice=form.querySelector('#avam-create-notice');
  if(notice){notice.textContent='';notice.classList.remove('avam-notice','avam-error');}
 });
});

/* AJAX navigation inside the account app shell. */
(function($){
 let busy=false;
 function targetContent(doc){
  const root=doc.querySelector('#main')||doc.body;
  const unified=doc.querySelector('.avam-unified-shell #main.avam-unified-content');
  if(unified)return unified.innerHTML;
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
  busy=true;
  const shell=document.querySelector('.avam-unified-shell');
  const main=shell&&(shell.classList.contains('avam-unified-shell')?shell.querySelector('.avam-unified-content'):shell.querySelector('.avam-dash-main'));
  if(!main){busy=false;window.location.href=url;return;}
  shell.classList.add('avam-panel-loading');
  const old=main.innerHTML;
  fetch(url,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
   .then(res=>{if(!res.ok)throw new Error('request failed');return res.text();})
   .then(html=>{
    const doc=new DOMParser().parseFromString(html,'text/html');
    doc.querySelectorAll('link[rel="stylesheet"]').forEach(link=>{if(link.href.includes('memorial-view.css')&&!document.querySelector('link[href="'+link.href+'"]'))document.head.appendChild(link.cloneNode(true));});
    const content=targetContent(doc);
    if(!content.trim())throw new Error('empty response');
    main.innerHTML=content;
    const title=doc.querySelector('title');if(title)document.title=title.textContent;
    const currentUrl=new URL(url,location.href);
    const globalSearch=document.querySelector('.avam-unified-search input[name="q"]');
    if(globalSearch)globalSearch.value=currentUrl.searchParams.get('q')||'';
    if(replace)history.replaceState({avamPanel:true},'',url);else history.pushState({avamPanel:true},'',url);
    window.scrollTo({top:0,behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});
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
 window.addEventListener('popstate',function(){if(document.querySelector('.avam-unified-shell'))window.avamPanelNavigate(location.href,true);});
})(jQuery);
