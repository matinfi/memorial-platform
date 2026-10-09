jQuery(function($){
 $(document).on('click','[data-avam-react]',function(){
  const b=$(this); if(b.prop('disabled'))return; b.prop('disabled',true);
  $.post(AVAM_FEATURES.ajax,{action:'avam_react',nonce:AVAM_FEATURES.nonce,id:b.data('id'),kind:b.data('avam-react')})
   .done(r=>{if(r.success){b.find('[data-count]').text(r.data.count);b.addClass('is-active');}else alert(r.data?.message||'ثبت انجام نشد.');})
   .fail(()=>alert('ارتباط با سرور برقرار نشد.')).always(()=>b.prop('disabled',false));
 });
 $(document).on('click','[data-avam-report]',function(){
  const id=$(this).data('id'); const reason=window.prompt('دلیل گزارش این یادبود را بنویسید (۵ تا ۱۰۰۰ نویسه):'); if(!reason)return;
  $.post(AVAM_FEATURES.ajax,{action:'avam_report',nonce:AVAM_FEATURES.nonce,id:id,reason:reason})
   .done(r=>alert(r.success?r.data.message:(r.data?.message||'گزارش ارسال نشد.')))
   .fail(()=>alert('ارتباط با سرور برقرار نشد.'));
 });
});