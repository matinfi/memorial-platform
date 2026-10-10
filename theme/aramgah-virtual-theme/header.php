<!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#f6f8f7">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="avam-skip" href="#main">پرش به محتوا</a>
<?php
$avam_memorials_url = function_exists('avam_memorials_url') ? avam_memorials_url() : home_url('/memorials/');
$avam_login_url = function_exists('avam_login_url') ? avam_login_url() : wp_login_url();
$avam_register_url = function_exists('avam_register_url') ? avam_register_url() : wp_registration_url();
$avam_account_url = function_exists('avam_account_url') ? avam_account_url() : home_url('/account/');
$avam_create_url = function_exists('avam_create_url') ? avam_create_url() : $avam_register_url;
$avam_user = wp_get_current_user();
$avam_display_name = is_user_logged_in() ? ($avam_user->display_name ?: $avam_user->user_login) : 'مهمان گرامی';
$avam_initial = function_exists('mb_substr') ? mb_substr($avam_display_name, 0, 1) : substr($avam_display_name, 0, 1);
$avam_search_query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
?>
<div class="avam-dashboard avam-dashboard-reference avam-unified-shell" dir="rtl">
  <button class="avam-mobile-menu-toggle" type="button" aria-expanded="false" aria-controls="avam-account-sidebar" aria-label="باز کردن منوی اصلی"><span aria-hidden="true">☰</span><b>منو</b></button>
  <button class="avam-mobile-menu-backdrop" type="button" aria-label="بستن منو" hidden></button>
  <aside class="avam-dash-sidebar" id="avam-account-sidebar">
    <a class="avam-dash-brand" href="<?php echo esc_url(home_url('/')); ?>">
      <span class="avam-dash-brand-mark">آ</span>
      <span><b>آرامگاه مجازی</b><small><?php echo is_user_logged_in() ? 'فضای شخصی شما' : 'یادها اینجا می‌مانند'; ?></small></span>
    </a>
    <nav class="avam-dash-nav" aria-label="ناوبری اصلی">
      <a href="<?php echo esc_url(home_url('/')); ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>><span class="avam-nav-icon" aria-hidden="true">⌂</span>خانه</a>
      <a href="<?php echo esc_url($avam_memorials_url); ?>"<?php echo is_page() && get_post_field('post_name',get_queried_object_id())==='memorials' ? ' aria-current="page"' : ''; ?>><span class="avam-nav-icon" aria-hidden="true">⌕</span>جست‌وجوی یادبودها</a>
      <?php if (is_user_logged_in()): ?>
        <a href="<?php echo esc_url($avam_account_url); ?>"><span class="avam-nav-icon" aria-hidden="true">▦</span>داشبورد من</a>
        <a href="<?php echo esc_url($avam_create_url); ?>"><span class="avam-nav-icon" aria-hidden="true">＋</span>ساخت یادبود</a>
        <a href="<?php echo esc_url(home_url('/profile/')); ?>"><span class="avam-nav-icon" aria-hidden="true">⚙</span>تنظیمات حساب</a>
      <?php else: ?>
        <a href="<?php echo esc_url($avam_login_url); ?>"><span class="avam-nav-icon" aria-hidden="true">↪</span>ورود</a>
        <a href="<?php echo esc_url($avam_register_url); ?>"><span class="avam-nav-icon" aria-hidden="true">＋</span>ثبت‌نام</a>
      <?php endif; ?>
    </nav>
    <div class="avam-dash-sidebar-foot">
      <?php if (is_user_logged_in()): ?>
        <a class="avam-dash-logout" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"><span aria-hidden="true">↪</span>خروج از حساب</a>
      <?php else: ?>
        <a class="avam-dash-logout" href="<?php echo esc_url($avam_register_url); ?>"><span aria-hidden="true">＋</span>ایجاد حساب رایگان</a>
      <?php endif; ?>
    </div>
  </aside>
  <div class="avam-dash-main avam-unified-main">
    <header class="avam-ref-topbar">
      <form class="avam-ref-search avam-unified-search" action="<?php echo esc_url($avam_memorials_url); ?>" method="get" role="search">
        <span aria-hidden="true">⌕</span>
        <input type="search" name="q" value="<?php echo esc_attr($avam_search_query); ?>" placeholder="جست‌وجو در یادبودها" aria-label="جست‌وجو در یادبودها">
        <button type="submit" aria-label="جست‌وجو">جست‌وجو</button>
      </form>
      <div class="avam-ref-user">
        <span class="avam-ref-avatar"><?php echo esc_html($avam_initial); ?></span>
        <div><strong><?php echo esc_html($avam_display_name); ?></strong><small><?php echo is_user_logged_in() ? 'حساب شخصی' : 'دسترسی مهمان'; ?></small></div>
      </div>
    </header>
    <main id="main" class="avam-unified-content">
