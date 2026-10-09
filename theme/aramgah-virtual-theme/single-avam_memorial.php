<?php
get_header();
if(have_posts()): while(have_posts()): the_post();
$birth=get_post_meta(get_the_ID(),'avam_birth',true);
$death=get_post_meta(get_the_ID(),'avam_death',true);
$city=get_post_meta(get_the_ID(),'avam_city',true);
$image=get_the_post_thumbnail_url(get_the_ID(),'full');
$kicker=get_option('avam_single_kicker','صفحه یادبود');
$intro=get_option('avam_single_intro','روایتی برای ماندن و به یاد آوردن.');
?>
<article class="avam-memorial-content-view" dir="rtl">

  <header class="avam-memorial-reading-header">
    <a href="<?php echo esc_url(avam_memorials_url()); ?>" aria-label="بازگشت به یادبودها">بازگشت به یادبودها</a>
    <a class="avam-reading-brand" href="<?php echo esc_url(home_url('/')); ?>">آرامگاه مجازی</a>
    <button type="button" data-share aria-label="اشتراک‌گذاری">اشتراک‌گذاری</button>
  </header>

  <section class="avam-memorial-reading-hero">
    <div class="avam-reading-ambient"></div>

    <div class="avam-memorial-reading-hero-inner">
      <div class="avam-memorial-reading-identity">
        <span class="avam-reading-kicker"><?php echo esc_html($kicker); ?></span>
        <h1><?php the_title(); ?></h1>

        <?php if($birth || $death): ?>
          <p class="avam-reading-dates">
            <?php echo esc_html(trim($birth.' — '.$death,' —')); ?>
          </p>
        <?php endif; ?>

        <?php if($city): ?>
          <p class="avam-reading-place">
            <span aria-hidden="true">⌖</span>
            <?php echo esc_html($city); ?>
          </p>
        <?php endif; ?>

        <p class="avam-reading-intro"><?php echo esc_html($intro); ?></p>
      </div>

      <div class="avam-reading-portrait-wrap">
        <div class="avam-reading-portrait">
          <?php if($image): ?>
            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
          <?php else: ?>
            <div class="avam-reading-empty">♡</div>
          <?php endif; ?>
        </div>
        <span class="avam-reading-portrait-label">یادبود</span>
      </div>
    </div>

    <div class="avam-reading-scroll">برای خواندن روایت <i></i></div>
  </section>

  <div class="avam-reading-layout">
  <main class="avam-reading-body">

    <section class="avam-reading-story">
      <span class="avam-reading-index">۰۱ / روایت</span>
      <h2 class="avam-accordion-title">روایت زندگی</h2>
      <div class="avam-reading-richtext"><?php the_content(); ?></div>
    </section>

    <?php
      $timeline_raw=get_post_meta(get_the_ID(),'avam_timeline',true);
      $timeline_lines=array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',$timeline_raw)));
      $gallery_ids=get_post_meta(get_the_ID(),'avam_gallery_ids',true);
      if(!is_array($gallery_ids))$gallery_ids=[];
    ?>
    <?php if($timeline_lines): ?>
      <section class="avam-reading-timeline">
        <span class="avam-reading-index">لحظه‌های ماندگار</span>
        <h2>خط زمانی زندگی</h2>
        <ol>
          <?php foreach($timeline_lines as $line): $parts=array_map('trim',explode('|',$line,2)); ?>
            <li><time><?php echo esc_html($parts[0]??''); ?></time><p><?php echo esc_html($parts[1]??$parts[0]??''); ?></p></li>
          <?php endforeach; ?>
        </ol>
      </section>
    <?php endif; ?>
    <?php if($gallery_ids): ?>
      <section class="avam-reading-gallery">
        <span class="avam-reading-index">تصاویر</span>
        <h2>گالری یادها</h2>
        <div class="avam-reading-gallery-grid">
          <?php foreach(array_slice($gallery_ids,0,10) as $attachment_id): $gallery_url=wp_get_attachment_image_url(absint($attachment_id),'large'); if(!$gallery_url)continue; ?>
            <a href="<?php echo esc_url(wp_get_attachment_url(absint($attachment_id))); ?>" target="_blank" rel="noopener"><img loading="lazy" src="<?php echo esc_url($gallery_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>"></a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <div class="avam-memorial-actions" aria-label="همراهی با یادبود">
      <button type="button" data-avam-react="candle" data-id="<?php echo esc_attr(get_the_ID()); ?>">🕯️ روشن کردن شمع <span data-count><?php echo esc_html((int)get_post_meta(get_the_ID(),'avam_candle_count',true)); ?></span></button>
      <button type="button" data-avam-react="flower" data-id="<?php echo esc_attr(get_the_ID()); ?>">🌷 تقدیم گل <span data-count><?php echo esc_html((int)get_post_meta(get_the_ID(),'avam_flower_count',true)); ?></span></button>
      <button type="button" data-avam-report data-id="<?php echo esc_attr(get_the_ID()); ?>">گزارش محتوا</button>
    </div>

    <section class="avam-reading-comments">
      <span class="avam-reading-index">پیام‌های یادبود</span>
      <h2>دعا و خاطره</h2>
      <?php if(comments_open() || get_comments_number()): comments_template(); else: ?>
        <div class="avam-social-comments-empty">هنوز پیامی ثبت نشده است. اولین دعا یا خاطره را شما بنویسید.</div>
      <?php endif; ?>
    </section>

    <footer class="avam-reading-end">
      <span>✦</span>
      <p>نام‌ها می‌مانند؛<br>وقتی روایت‌ها را نگه می‌داریم.</p>
      <a href="<?php echo esc_url(avam_memorials_url()); ?>">بازگشت به یادبودها</a>
    </footer>

  </main>
</div>
</article>

<script>
document.addEventListener('click',function(e){
  const share=e.target.closest('[data-share]');
  if(share){
    const original=share.textContent;
    const data={title:document.title,url:location.href};
    if(navigator.share){
      navigator.share(data).catch(()=>{});
    }else if(navigator.clipboard){
      navigator.clipboard.writeText(location.href).then(()=>{
        share.textContent='لینک کپی شد';
        setTimeout(()=>share.textContent=original,1800);
      });
    }
  }

  const copy=e.target.closest('[data-copy]');
  if(copy && navigator.clipboard){
    navigator.clipboard.writeText(location.href).then(()=>{
      const original=copy.innerHTML;
      copy.innerHTML='<span>✓</span> لینک کپی شد';
      setTimeout(()=>copy.innerHTML=original,1800);
    });
  }
});
</script>
<?php endwhile; endif; get_footer();