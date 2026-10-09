<?php
get_header();

$avam_user = wp_get_current_user();
$avam_name = is_user_logged_in() ? ($avam_user->display_name ?: $avam_user->user_login) : '';
$avam_memorials_url = function_exists('avam_memorials_url') ? avam_memorials_url() : home_url('/memorials/');
$avam_create_url = is_user_logged_in() ? (function_exists('avam_create_url') ? avam_create_url() : home_url('/create-memorial/')) : (function_exists('avam_register_url') ? avam_register_url() : wp_registration_url());
$avam_count = wp_count_posts('avam_memorial');
$avam_total = isset($avam_count->publish) ? (int) $avam_count->publish : 0;
$avam_recent = new WP_Query(['post_type'=>'avam_memorial','post_status'=>'publish','posts_per_page'=>6,'orderby'=>'date','order'=>'DESC','no_found_rows'=>true,'meta_query'=>['relation'=>'OR',['key'=>'avam_visibility','compare'=>'NOT EXISTS'],['key'=>'avam_visibility','value'=>'private','compare'=>'!=']]]);
?>
<section class="avam-unified-home" dir="rtl">
  <div class="avam-unified-welcome">
    <div class="avam-unified-welcome-copy">
      <span class="avam-unified-eyebrow">آرامگاه مجازی</span>
      <h1><?php echo is_user_logged_in() ? 'خوش آمدید، '.esc_html($avam_name) : 'یادها اینجا می‌مانند.'; ?></h1>
      <p>فضایی محترمانه برای زنده نگه‌داشتن نام، تصویر و روایت عزیزانی که فراموش نمی‌شوند.</p>
      <div class="avam-unified-actions">
        <a class="avam-dash-primary" href="<?php echo esc_url($avam_create_url); ?>"><?php echo is_user_logged_in() ? '＋ ساخت یادبود' : '＋ ثبت‌نام و ساخت یادبود'; ?></a>
        <a class="avam-unified-secondary" href="<?php echo esc_url($avam_memorials_url); ?>">⌕ جست‌وجوی یادبودها</a>
      </div>
    </div>
    <div class="avam-unified-welcome-mark" aria-hidden="true"><span>آ</span><i>یاد، پیوندی ماندگار است</i></div>
  </div>

  <div class="avam-unified-home-stats">
    <article><span class="avam-unified-stat-icon">♡</span><div><strong><?php echo esc_html(number_format_i18n($avam_total)); ?></strong><span>یادبود عمومی</span></div></article>
    <article><span class="avam-unified-stat-icon">✧</span><div><strong>یاد و خاطره</strong><span>برای همیشه محفوظ</span></div></article>
    <article><span class="avam-unified-stat-icon">♧</span><div><strong><?php echo is_user_logged_in() ? 'فضای شخصی شما' : 'رایگان و در دسترس'; ?></strong><span><?php echo is_user_logged_in() ? 'یادبودهایتان را مدیریت کنید' : 'برای ساخت یادبود حساب بسازید'; ?></span></div></article>
  </div>

  <section class="avam-unified-recent">
    <div class="avam-unified-section-head">
      <div><span>از میان روایت‌ها</span><h2>یادبودهای تازه</h2></div>
      <a href="<?php echo esc_url($avam_memorials_url); ?>">مشاهده همه <span aria-hidden="true">←</span></a>
    </div>
    <?php if ($avam_recent->have_posts()): ?>
      <div class="avam-unified-memorial-grid">
        <?php while ($avam_recent->have_posts()): $avam_recent->the_post(); ?>
          <a class="avam-unified-memorial-card" href="<?php the_permalink(); ?>">
            <div class="avam-unified-memorial-image"><?php if (has_post_thumbnail()): ?><?php the_post_thumbnail('medium_large',['loading'=>'lazy']); ?><?php else: ?><span>♡</span><?php endif; ?></div>
            <div class="avam-unified-memorial-info"><small>یادبود</small><h3><?php the_title(); ?></h3><p><?php echo esc_html(get_post_meta(get_the_ID(),'avam_city',true)); ?></p></div>
          </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php else: ?>
      <div class="avam-unified-empty"><span>♡</span><h3>هنوز یادبود عمومی ثبت نشده است</h3><p>می‌توانید نخستین روایت را با احترام ثبت کنید.</p><a href="<?php echo esc_url($avam_create_url); ?>"><?php echo is_user_logged_in() ? 'ساخت اولین یادبود' : 'ساخت حساب و ثبت یادبود'; ?></a></div>
    <?php endif; ?>
  </section>
</section>
<?php get_footer();