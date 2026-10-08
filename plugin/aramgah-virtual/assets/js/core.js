jQuery(function($){
 const f=$('#avam-create-form');
 if(f.length){
  f.on('submit',function(e){
   e.preventDefault();
   const fd=new FormData(this);fd.append('action','avam_save_memorial');fd.append('nonce',AVAM.nonce);
   const b=f.find('button[type=submit]');b.prop('disabled',true).addClass('is-loading');
   $.ajax({url:AVAM.ajax,type:'POST',data:fd,processData:false,contentType:false}).done(function(r){
    if(r.success){$('#avam-create-notice').html('<div class="avam-notice">یادبود با موفقیت ذخیره شد. در حال انتقال...</div>');window.location.href=r.data.url;}
    else $('#avam-create-notice').html('<div class="avam-notice avam-error">'+(r.data?.message||'خطا در ذخیره اطلاعات')+'</div>');
   }).fail(function(){$('#avam-create-notice').html('<div class="avam-notice avam-error">ارتباط با سرور برقرار نشد.</div>');}).always(function(){b.prop('disabled',false).removeClass('is-loading');});
  });
 }
 $('.avam-action-delete').on('click',function(){
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
