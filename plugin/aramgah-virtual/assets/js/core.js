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
   if(r.success){b.closest('.avam-dash-memorial').slideUp(220,function(){$(this).remove();});}
   else{alert(r.data?.message||'حذف انجام نشد.');b.prop('disabled',false).text('حذف');}
  }).fail(function(){alert('ارتباط با سرور برقرار نشد.');b.prop('disabled',false).text('حذف');});
 });
});