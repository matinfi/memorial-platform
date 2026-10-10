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
    <article><span class="avam-unified-stat-icon"><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 8.7c0 4.4-8.5 10-8.5 10s-8.5-5.6-8.5-10a4.4 4.4 0 0 1 8.5-1.2 4.4 4.4 0 0 1 8.5 1.2Z"/></svg></span><div><strong><?php echo esc_html(number_format_i18n($avam_total)); ?></strong><span>یادبود عمومی</span></div></article>
    <article><span class="avam-unified-stat-icon"><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="m19 15 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"/></svg></span><div><strong>یاد و خاطره</strong><span>برای همیشه محفوظ</span></div></article>
    <article><span class="avam-unified-stat-icon"><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 4c-8.5 0-14 3.4-14 9a6 6 0 0 0 6 6c5.6 0 8-6.5 8-15Z"/><path d="M4 21c3.3-5.4 7.2-8.8 12-11"/></svg></span><div><strong><?php echo is_user_logged_in() ? 'فضای شخصی شما' : 'رایگان و در دسترس'; ?></strong><span><?php echo is_user_logged_in() ? 'یادبودهایتان را مدیریت کنید' : 'برای ساخت یادبود حساب بسازید'; ?></span></div></article>
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
            <div class="avam-unified-memorial-image"><?php if (has_post_thumbnail()): ?><?php the_post_thumbnail('medium_large',['loading'=>'lazy']); ?><?php else: ?><span><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c.4-3.5 2.8-5.5 6.5-5.5s6.1 2 6.5 5.5"/><path d="M3 4v4M3 4h4M21 20v-4M21 20h-4"/></svg></span><?php endif; ?></div>
            <div class="avam-unified-memorial-info"><small>یادبود</small><h3><?php the_title(); ?></h3><p><?php echo esc_html(get_post_meta(get_the_ID(),'avam_city',true)); ?></p></div>
          </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php else: ?>
      <div class="avam-unified-empty"><span><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 8.7c0 4.4-8.5 10-8.5 10s-8.5-5.6-8.5-10a4.4 4.4 0 0 1 8.5-1.2 4.4 4.4 0 0 1 8.5 1.2Z"/></svg></span><h3>هنوز یادبود عمومی ثبت نشده است</h3><p>می‌توانید نخستین روایت را با احترام ثبت کنید.</p><a href="<?php echo esc_url($avam_create_url); ?>"><?php echo is_user_logged_in() ? 'ساخت اولین یادبود' : 'ساخت حساب و ثبت یادبود'; ?></a></div>
    <?php endif; ?>
  </section>
</section>
<?php get_footer();