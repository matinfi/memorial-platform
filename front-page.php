<?php
get_header();
$memorials = get_posts([
  'post_type' => 'avam_memorial',
  'post_status' => 'publish',
  'posts_per_page' => 3
]);
?>

<div class="avam-experience">
  <div class="avam-progress" aria-hidden="true"><span></span></div>

  <nav class="avam-rail" aria-label="بخش‌های صفحه">
    <span class="avam-rail-line" aria-hidden="true"></span>
    <a class="avam-rail-item is-active" data-chapter="arrival" href="#chapter-arrival"><span class="avam-rail-label">آغاز</span></a>
    <a class="avam-rail-item" data-chapter="person" href="#chapter-person"><span class="avam-rail-label">انسان</span></a>
    <a class="avam-rail-item" data-chapter="story" href="#chapter-story"><span class="avam-rail-label">روایت</span></a>
    <a class="avam-rail-item" data-chapter="words" href="#chapter-words"><span class="avam-rail-label">کلمات</span></a>
    <a class="avam-rail-item" data-chapter="ending" href="#chapter-ending"><span class="avam-rail-label">پایان</span></a>
  </nav>

  <section id="chapter-arrival" class="avam-chapter avam-chapter--hero" data-chapter="arrival">
    <div class="avam-chapter-bg" aria-hidden="true"></div>
    <div class="avam-orb avam-orb--one" aria-hidden="true"></div>
    <div class="avam-orb avam-orb--two" aria-hidden="true"></div>
    <div class="avam-grain" aria-hidden="true"></div>
    <div class="avam-chapter-frame" aria-hidden="true"></div>

    <div class="avam-chapter-content">
      <div class="avam-eyebrow"><i></i><span>آرامگاه مجازی</span></div>
      <h1>هر آدمی،<br><em>روایتی دارد.</em></h1>
      <p>فضایی آرام برای نگه‌داشتن نام، تصویر، خاطره و کلماتی که نمی‌خواهیم از میان ما محو شوند.</p>
      <div class="avam-hero-actions">
        <?php if (is_user_logged_in()): ?>
          <a class="avam-cinematic-btn" href="<?php echo esc_url(avam_create_url()); ?>"><b>ساخت صفحه یادبود</b><span>←</span></a>
        <?php else: ?>
          <a class="avam-cinematic-btn" href="<?php echo esc_url(avam_register_url()); ?>"><b>ساخت صفحه یادبود</b><span>←</span></a>
          <a class="avam-text-link" href="<?php echo esc_url(avam_login_url()); ?>">ورود به حساب</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="avam-hero-visual" aria-hidden="true">
      <div class="avam-visual-ring"></div>
      <div class="avam-visual-photo"><span>یاد<br>می‌ماند</span></div>
      <div class="avam-visual-caption">A PLACE FOR MEMORY<br>AND HUMAN STORIES</div>
    </div>

    <div class="avam-scroll-cue"><span>برای ادامه اسکرول کنید</span><b>↓</b></div>
  </section>

  <section id="chapter-person" class="avam-chapter avam-chapter--light" data-chapter="person">
    <div class="avam-chapter-bg avam-chapter-bg--light" aria-hidden="true"></div>
    <div class="avam-line-art" aria-hidden="true"></div>
    <div class="avam-chapter-frame" aria-hidden="true"></div>

    <div class="avam-chapter-content">
      <div class="avam-eyebrow"><i></i><span>02 / 05 — انسان</span></div>
      <h2>اول،<br><em>خودِ او.</em></h2>
      <p>هر یادبود با یک انسان آغاز می‌شود؛ با نام، تصویر و نشانه‌هایی که حضور او را برای خانواده و دوستان ملموس نگه می‌دارند.</p>
    </div>

    <div class="avam-editorial-photo" aria-hidden="true">
      <div class="avam-photo-placeholder"><span>تصویر<br>او</span><small>PORTRAIT / MEMORY</small></div>
      <div class="avam-photo-meta"><span>نام و تصویر</span><span>01</span></div>
    </div>
  </section>

  <section id="chapter-story" class="avam-chapter avam-chapter--dark" data-chapter="story">
    <div class="avam-chapter-bg" aria-hidden="true"></div>
    <div class="avam-grid-glow" aria-hidden="true"></div>
    <div class="avam-chapter-frame" aria-hidden="true"></div>

    <div class="avam-chapter-content">
      <div class="avam-eyebrow"><i></i><span>03 / 05 — روایت</span></div>
      <h2>زندگی،<br><em>خط مستقیم نیست.</em></h2>
      <p>از سال‌های نخست تا لحظه‌هایی که چیزی از او برای دیگران ساختند؛ روایت را می‌شود آرام، انسانی و با احترام کنار هم نشاند.</p>
    </div>

    <div class="avam-timeline-editorial" aria-hidden="true">
      <div class="avam-time-line"></div>
      <div class="avam-time-node"><strong>01</strong><span>آغاز</span><small>سال‌های نخست</small></div>
      <div class="avam-time-node"><strong>02</strong><span>ساختن</span><small>مسیر زندگی</small></div>
      <div class="avam-time-node"><strong>03</strong><span>ماندن</span><small>اثری که باقی ماند</small></div>
    </div>
  </section>

  <section id="chapter-words" class="avam-chapter avam-chapter--paper" data-chapter="words">
    <div class="avam-chapter-frame" aria-hidden="true"></div>
    <div class="avam-chapter-content">
      <div class="avam-eyebrow"><i></i><span>04 / 05 — کلمات</span></div>
      <h2>بعضی کلمات،<br><em>باید بمانند.</em></h2>
      <p>نامه، وصیت، خاطره و دعا؛ هرکدام می‌توانند بخشی از یک صفحه باشند که برای همیشه با نام او باقی می‌ماند.</p>
    </div>

    <div class="avam-quote-wall" aria-hidden="true">
      <div class="avam-quote avam-quote--large">«آنچه از آدم‌ها می‌ماند، فقط تصویرشان نیست؛ کلماتی‌ست که در ما گذاشته‌اند.»</div>
      <div class="avam-quote avam-quote--small">LETTER · WILL · MEMORY · PRAYER</div>
    </div>
  </section>

  <section id="chapter-ending" class="avam-chapter avam-chapter--ending" data-chapter="ending">
    <div class="avam-ending-glow" aria-hidden="true"></div>
    <div class="avam-chapter-frame" aria-hidden="true"></div>
    <div class="avam-ending-content">
      <div class="avam-eyebrow"><i></i><span>05 / 05 — پایان آرام</span><i></i></div>
      <h2>یاد، وقتی زیبا روایت شود،<br><em>آرام‌تر می‌ماند.</em></h2>
      <p>این‌جا پایان یک زندگی نیست؛ جایی‌ست برای ادامه‌ی روایت آن در خاطره‌ی آدم‌ها.</p>
      <?php if (is_user_logged_in()): ?>
        <a class="avam-cinematic-btn avam-cinematic-btn--cream" href="<?php echo esc_url(avam_create_url()); ?>"><b>ساخت یک یادبود</b><span>←</span></a>
      <?php else: ?>
        <a class="avam-cinematic-btn avam-cinematic-btn--cream" href="<?php echo esc_url(avam_register_url()); ?>"><b>شروع یک یادبود</b><span>←</span></a>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($memorials): ?>
    <section class="avam-memorial-index">
      <div class="avam-index-head">
        <span>یادبودهای عمومی</span>
        <h2>روایت‌هایی که خانواده‌ها<br>خواسته‌اند بمانند.</h2>
      </div>
      <div class="avam-memorial-links">
        <?php foreach ($memorials as $m): ?>
          <a href="<?php echo esc_url(get_permalink($m)); ?>">
            <span><?php echo esc_html($m->post_title); ?></span>
            <b>مشاهده روایت ←</b>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<?php get_footer(); ?>