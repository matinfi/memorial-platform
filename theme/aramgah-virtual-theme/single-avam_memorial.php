<?php
get_header();
if(have_posts()): while(have_posts()): the_post();
$birth=get_post_meta(get_the_ID(),'avam_birth',true); $death=get_post_meta(get_the_ID(),'avam_death',true);
$city=get_post_meta(get_the_ID(),'avam_city',true); $will=get_post_meta(get_the_ID(),'avam_will',true);
$letter=get_post_meta(get_the_ID(),'avam_letter',true); $prayer=get_post_meta(get_the_ID(),'avam_prayer',true);
$memory=get_post_meta(get_the_ID(),'avam_memory',true); $image=get_the_post_thumbnail_url(get_the_ID(),'full');
$kicker=get_option('avam_single_kicker','صفحه یادبود'); $intro=get_option('avam_single_intro','روایتی برای ماندن و به یاد آوردن.');
$published=get_the_date('Y/m/d');
?>
<article class="avam-memorial-content-view" dir="rtl">
  <div class="avam-memorial-view-shell">

    <header class="avam-memorial-view-header">
      <a class="avam-memorial-view-icon" href="<?php echo esc_url(avam_memorials_url()); ?>" aria-label="بازگشت به یادبودها">←</a>
      <h1>آرامگاه مجازی</h1>
      <button class="avam-memorial-view-icon" type="button" data-share aria-label="اشتراک‌گذاری">↗</button>
    </header>

    <section class="avam-memorial-view-hero">
      <div class="avam-memorial-view-avatar">
        <?php if($image): ?>
          <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
        <?php else: ?>
          <div class="avam-memorial-view-avatar-empty">♡</div>
        <?php endif; ?>
      </div>

      <span class="avam-memorial-view-kicker"><?php echo esc_html($kicker); ?></span>
      <h2><?php the_title(); ?></h2>

      <?php if($birth || $death): ?>
        <div class="avam-memorial-view-dates">
          <?php echo esc_html(trim($birth.' - '.$death,' -')); ?>
        </div>
      <?php endif; ?>

      <?php if($city): ?>
        <div class="avam-memorial-view-location">⌖ <?php echo esc_html($city); ?></div>
      <?php endif; ?>

      <div class="avam-memorial-view-meta">
        <?php if($birth): ?><span>تولد: <?php echo esc_html($birth); ?></span><?php endif; ?>
        <?php if($death): ?><span>درگذشت: <?php echo esc_html($death); ?></span><?php endif; ?>
      </div>
    </section>

    <div class="avam-memorial-view-content">
      <section class="avam-memorial-view-story">
        <div class="avam-memorial-view-richtext"><?php the_content(); ?></div>
        <div class="avam-memorial-view-date"><?php echo esc_html($published); ?></div>
      </section>

      <?php
      $extra_sections=[
        ['وصیت',$will,'وصیت‌نامه و آنچه برای ماندن به یادگار گذاشته شد.'],
        ['نامه',$letter,'کلماتی که برای همیشه می‌توان دوباره خواند.'],
        ['خاطره',$memory,'یادی کوتاه از لحظه‌ها و روزهای ماندگار.'],
        ['دعا',$prayer,'دعایی برای آرامش و یاد نیک.']
      ];
      $section_no=2;
      $has_extra=false;
      foreach($extra_sections as $item):
        if(!$item[1]) continue;
        $has_extra=true;
      ?>
        <section class="avam-memorial-view-section">
          <span class="avam-memorial-view-section-label"><?php echo esc_html(str_pad((string)$section_no,2,'0',STR_PAD_LEFT).' · یاد'); ?></span>
          <h3><?php echo esc_html($item[0]); ?></h3>
          <div class="avam-memorial-view-richtext"><?php echo wpautop(esc_html($item[1])); ?></div>
        </section>
      <?php $section_no++; endforeach; ?>

      <div class="avam-memorial-view-actions">
        <button class="avam-memorial-view-action is-primary" type="button" data-share>اشتراک‌گذاری یادبود</button>
        <a class="avam-memorial-view-action" href="<?php echo esc_url(avam_memorials_url()); ?>">بازگشت به یادبودها</a>
      </div>

      <div class="avam-memorial-view-note">
        <span><?php echo esc_html($intro); ?></span>
        <strong>نام‌ها می‌مانند؛ وقتی روایت‌ها را نگه می‌داریم.</strong>
      </div>
    </div>

    <footer class="avam-memorial-view-footer">
      <a href="<?php echo esc_url(home_url('/')); ?>">آرامگاه مجازی</a>
    </footer>
  </div>
</article>

<script>
document.addEventListener('click',function(e){
  const b=e.target.closest('[data-share]');
  if(!b)return;
  const original=b.textContent;
  const data={title:document.title,url:location.href};
  if(navigator.share){
    navigator.share(data).catch(()=>{});
  }else if(navigator.clipboard){
    navigator.clipboard.writeText(location.href).then(()=>{
      b.textContent='لینک کپی شد';
      setTimeout(()=>b.textContent=original,1800);
    });
  }
});
</script>
<?php endwhile; endif; get_footer();