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
 $(document).on('click','[data-avam-reaction-list]',function(e){
  e.preventDefault();
  const button=$(this), modal=$('#avam-reaction-modal'), kind=String(button.data('avam-reaction-list')||''), id=button.data('id');
  const title=kind==='candle'?'کسانی که شمع روشن کرده‌اند':'کسانی که گل داده‌اند';
  modal.find('#avam-reaction-modal-title').text(title);
  modal.find('[data-avam-reaction-status]').text('در حال بارگذاری…').show();
  modal.find('[data-avam-reaction-items]').empty();
  modal.find('[data-avam-reaction-note]').empty();
  modal.prop('hidden',false).attr('aria-hidden','false');
  $('body').addClass('avam-reaction-modal-open');
  $.post(AVAM_FEATURES.ajax,{action:'avam_reaction_list',nonce:AVAM_FEATURES.nonce,id:id,kind:kind})
   .done(r=>{
    const list=modal.find('[data-avam-reaction-items]').empty();
    if(!r.success){modal.find('[data-avam-reaction-status]').text(r.data?.message||'فهرست بارگذاری نشد.');return;}
    const items=Array.isArray(r.data.items)?r.data.items:[];
    items.forEach(item=>$('<li>').text(item.name||'کاربر').appendTo(list));
    modal.find('[data-avam-reaction-status]').text(items.length?'نام همراهان ثبت‌شده: '+items.length:'هنوز نامی برای این همراهی ثبت نشده است.');
    if(Number(r.data.unlisted)>0) modal.find('[data-avam-reaction-note]').text('تعداد کل: '+r.data.total+'؛ برخی همراهی‌های قدیمی پیش از فعال‌شدن ثبت نام‌ها انجام شده‌اند و نامشان ذخیره نشده است.');
    else if(!items.length) modal.find('[data-avam-reaction-note]').text('با ثبت گل یا روشن‌کردن شمع، نام حساب کاربری در این فهرست دیده می‌شود. همراهان مهمان با عنوان «کاربر مهمان» نمایش داده می‌شوند.');
   })
   .fail(xhr=>modal.find('[data-avam-reaction-status]').text(xhr.responseJSON?.data?.message||'ارتباط با سرور برقرار نشد.'))
   .always(()=>modal.find('[data-avam-reaction-status]').show());
 });
 $(document).on('click','[data-avam-reaction-close]',function() {
  const modal=$('#avam-reaction-modal'); modal.prop('hidden',true).attr('aria-hidden','true'); $('body').removeClass('avam-reaction-modal-open');
 });
 $(document).on('click','#avam-reaction-modal',function(e) {
  if(e.target===this){$(this).prop('hidden',true).attr('aria-hidden','true');$('body').removeClass('avam-reaction-modal-open');}
 });
 $(document).on('keydown',function(e) {
  if(e.key==='Escape'){const modal=$('#avam-reaction-modal');if(modal.length&&!modal.prop('hidden')){modal.prop('hidden',true).attr('aria-hidden','true');$('body').removeClass('avam-reaction-modal-open');}}
 });
});
