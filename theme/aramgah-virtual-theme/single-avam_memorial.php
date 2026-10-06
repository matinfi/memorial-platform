<?php
get_header();
if(have_posts()): while(have_posts()): the_post();
$birth=get_post_meta(get_the_ID(),'avam_birth',true); $death=get_post_meta(get_the_ID(),'avam_death',true);
$city=get_post_meta(get_the_ID(),'avam_city',true); $will=get_post_meta(get_the_ID(),'avam_will',true);
$letter=get_post_meta(get_the_ID(),'avam_letter',true); $prayer=get_post_meta(get_the_ID(),'avam_prayer',true);
$memory=get_post_meta(get_the_ID(),'avam_memory',true); $image=get_the_post_thumbnail_url(get_the_ID(),'full');
$kicker=get_option('avam_single_kicker','صفحه یادبود'); $intro=get_option('avam_single_intro','روایتی برای ماندن و به یاد آوردن.');
?>
<article class="avam-memorial-page" dir="rtl">
  <section class="avam-memorial-cover">
    <div class="avam-container avam-memorial-cover-grid">
      <div class="avam-memorial-cover-copy">
        <span class="avam-memorial-kicker"><?php echo esc_html($kicker); ?></span>
        <h1><?php the_title(); ?></h1>
        <p><?php echo esc_html($intro); ?></p>
        <div class="avam-memorial-dates">
          <?php if($city): ?><span><?php echo esc_html($city); ?></span><?php endif; ?>
          <?php if($birth): ?><span><?php echo esc_html($birth); ?></span><?php endif; ?>
          <?php if($death): ?><span><?php echo esc_html($death); ?></span><?php endif; ?>
        </div>
        <div class="avam-memorial-actions">
          <a class="avam-memorial-btn" href="<?php echo esc_url(avam_memorials_url()); ?>">جستجوی یادبودها</a>
          <button class="avam-memorial-share" type="button" data-share>اشتراک‌گذاری</button>
        </div>
      </div>
      <div class="avam-memorial-cover-photo">
        <?php if($image): ?><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>"><?php else: ?><div class="avam-memorial-photo-empty">♡</div><?php endif; ?>
      </div>
    </div>
  </section>

  <div class="avam-container avam-memorial-content">
    <div class="avam-memorial-main">
      <section class="avam-memory-section avam-memory-story">
        <span class="avam-memory-label">01 · روایت</span>
        <h2>روایت زندگی</h2>
        <div class="avam-richtext"><?php the_content(); ?></div>
      </section>
      <?php foreach([['02','وصیت',$will],['03','نامه',$letter],['04','خاطره',$memory],['05','دعا',$prayer]] as $item): if($item[2]): ?>
      <section class="avam-memory-section">
        <span class="avam-memory-label"><?php echo esc_html($item[0]); ?> · یاد</span>
        <h2><?php echo esc_html($item[1]); ?></h2>
        <div class="avam-richtext"><?php echo wpautop(esc_html($item[2])); ?></div>
      </section>
      <?php endif; endforeach; ?>
    </div>
    <aside class="avam-memorial-aside">
      <div class="avam-memorial-aside-card">
        <span>این صفحه برای یادآوری ساخته شده است.</span>
        <strong>نام‌ها می‌مانند؛<br>وقتی روایت‌ها را نگه می‌داریم.</strong>
      </div>
      <a class="avam-memorial-back" href="<?php echo esc_url(avam_memorials_url()); ?>">← بازگشت به یادبودها</a>
    </aside>
  </div>
</article>
<script>
document.addEventListener('click',function(e){
 const b=e.target.closest('[data-share]'); if(!b)return;
 const data={title:document.title,url:location.href};
 if(navigator.share){navigator.share(data).catch(()=>{});}
 else if(navigator.clipboard){navigator.clipboard.writeText(location.href).then(()=>{b.textContent='لینک کپی شد';setTimeout(()=>b.textContent='اشتراک‌گذاری',1800);});}
});
</script>
<?php endwhile; endif; get_footer();