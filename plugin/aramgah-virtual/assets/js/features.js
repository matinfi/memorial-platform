jQuery(function($){
 const showNotice=(form,message,type)=>form.find('.avam-comment-ajax-notice').text(message).removeClass('is-error is-success').addClass(type==='error'?'is-error':'is-success');
 $(document).on('click','[data-avam-react]',function(e){
  e.preventDefault();
  const b=$(this); if(b.prop('disabled'))return; b.prop('disabled',true).addClass('is-loading');
  $.post(AVAM_FEATURES.ajax,{action:'avam_react',nonce:AVAM_FEATURES.nonce,id:b.data('id'),kind:b.data('avam-react')})
   .done(r=>{if(r.success){b.find('[data-count]').text(r.data.count);b.addClass('is-active');}else alert(r.data?.message||'ثبت انجام نشد.');})
   .fail(xhr=>alert(xhr.responseJSON?.data?.message||'ارتباط با سرور برقرار نشد.')).always(()=>b.prop('disabled',false).removeClass('is-loading'));
 });
 $(document).on('click','[data-avam-report]',function(e){
  e.preventDefault();
  const b=$(this); const id=b.data('id'); const reason=window.prompt('دلیل گزارش این یادبود را بنویسید (۵ تا ۱۰۰۰ نویسه):'); if(!reason)return;
  b.prop('disabled',true);
  $.post(AVAM_FEATURES.ajax,{action:'avam_report',nonce:AVAM_FEATURES.nonce,id:id,reason:reason})
   .done(r=>alert(r.success?r.data.message:(r.data?.message||'گزارش ارسال نشد.')))
   .fail(xhr=>alert(xhr.responseJSON?.data?.message||'ارتباط با سرور برقرار نشد.')).always(()=>b.prop('disabled',false));
 });
 $(document).on('submit','#commentform',function(e){
  const form=$(this);
  if(!form.closest('.avam-social-comments').length)return;
  e.preventDefault();
  const submit=form.find(':submit').first();
  if(submit.prop('disabled'))return;
  submit.prop('disabled',true);
  showNotice(form,'در حال ثبت پیام…','success');
  const data=form.serializeArray();
  data.push({name:'action',value:'avam_submit_comment'});
  $.ajax({url:AVAM_FEATURES.ajax,method:'POST',data:data,dataType:'json'})
   .done(r=>{
    if(!r.success){showNotice(form,r.data?.message||'ثبت پیام انجام نشد.','error');return;}
    showNotice(form,r.data.message,'success');
    form.find('textarea[name="comment"]').val('');
    if(r.data.html){
      let list=form.closest('.avam-social-comments').find('.comment-list').first();
      if(!list.length){list=$('<ol class="comment-list"></ol>');form.closest('.avam-social-comments').prepend(list);}
      list.append(r.data.html);
    }
   })
   .fail(xhr=>showNotice(form,xhr.responseJSON?.data?.message||'ارتباط با سرور برقرار نشد.','error'))
   .always(()=>submit.prop('disabled',false));
 });
});
