<?php
get_header();
if(have_posts()): while(have_posts()): the_post();
$birth=get_post_meta(get_the_ID(),'avam_birth',true);
$death=get_post_meta(get_the_ID(),'avam_death',true);
$city=get_post_meta(get_the_ID(),'avam_city',true);
$will=get_post_meta(get_the_ID(),'avam_will',true);
$letter=get_post_meta(get_the_ID(),'avam_letter',true);
$prayer=get_post_meta(get_the_ID(),'avam_prayer',true);
$memory=get_post_meta(get_the_ID(),'avam_memory',true);
$image=get_the_post_thumbnail_url(get_the_ID(),'full');
$kicker=get_option('avam_single_kicker','صفحه یادبود');
$intro=get_option('avam_single_intro','روایتی برای ماندن و به یاد آوردن.');
$published=get_the_date('Y/m/d');
$extras=[
  ['وصیت',$will,'وصیت‌نامه و آنچه برای ماندن به یادگار گذاشته شد.'],
  ['نامه',$letter,'کلماتی که برای همیشه می‌توان دوباره خواند.'],
  ['خاطره',$memory,'یادی کوتاه از لحظه‌ها و روزهای ماندگار.'],
  ['دعا',$prayer,'دعایی برای آرامش و یاد نیک.']
];
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

    <aside class="avam-reading-rail">
      <div class="avam-reading-rail-card">
        <span>این صفحه برای</span>
        <strong>یادآوری و روایت</strong>
        <small><?php echo esc_html($published); ?></small>
      </div>

      <div class="avam-reading-quick-actions">
        <button type="button" data-share>
          <span>↗</span> اشتراک‌گذاری
        </button>
        <button type="button" data-copy>
          <span>⌁</span> کپی لینک
        </button>
        <button type="button" onclick="window.print()">
          <span>⌁</span> چاپ یادبود
        </button>
      </div>
    </aside>

    <main class="avam-reading-body">

      <section class="avam-reading-story">
        <span class="avam-reading-index">۰۱ / روایت</span>
        <h2>روایت زندگی</h2>
        <div class="avam-reading-richtext"><?php the_content(); ?></div>
      </section>

      <?php $i=2; foreach($extras as $item): if(!$item[1]) continue; ?>
        <section class="avam-reading-section">
          <span class="avam-reading-index">
            <?php echo esc_html(str_pad((string)$i,2,'0',STR_PAD_LEFT).' / یاد'); ?>
          </span>
          <h2><?php echo esc_html($item[0]); ?></h2>
          <p class="avam-reading-section-intro"><?php echo esc_html($item[2]); ?></p>
          <div class="avam-reading-richtext"><?php echo wpautop(esc_html($item[1])); ?></div>
        </section>
      <?php $i++; endforeach; ?>

      <?php if(comments_open() || get_comments_number()): ?>
        <section class="avam-reading-comments">
          <span class="avam-reading-index">۰<?php echo esc_html($i); ?> / پیام‌ها</span>
          <h2>پیام‌های یادبود</h2>
          <?php comments_template(); ?>
        </section>
      <?php endif; ?>

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