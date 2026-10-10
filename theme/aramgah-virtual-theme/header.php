<!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#f5f7f6">
<?php wp_head(); ?>
<style>
/* Minimal identity header: one site name, using the exact same surface as the application shell. */
.avam-unified-main>.avam-ref-topbar.avam-site-identity-bar{box-sizing:border-box;width:100%;min-height:76px;margin:0 0 8px!important;padding:18px 0!important;background:#f2f5f3;border:0;border-bottom:1px solid #dce4de;border-radius:0;color:#26382f;box-shadow:none}
.avam-site-identity-bar .avam-site-title{display:block;margin:0;color:#26382f;font-family:"Vazirmatn","Noto Sans Arabic",Tahoma,sans-serif;font-size:20px;font-weight:750;line-height:1.6}
@media(max-width:760px){.avam-unified-main>.avam-ref-topbar.avam-site-identity-bar{min-height:64px;padding:14px 0!important}.avam-site-identity-bar .avam-site-title{font-size:17px}}
</style>
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
$avam_display_name = is_user_logged_in() ? ($avam_user->user_login ?: $avam_user->display_name) : 'مهمان گرامی';
$avam_initial = function_exists('mb_substr') ? mb_substr($avam_display_name, 0, 1) : substr($avam_display_name, 0, 1);
?>
<div class="avam-dashboard avam-dashboard-reference avam-unified-shell" dir="rtl">
  <button class="avam-mobile-menu-toggle" type="button" aria-expanded="false" aria-controls="avam-account-sidebar" aria-label="باز کردن منوی اصلی"><span aria-hidden="true">☰</span><b>منو</b></button>
  <button class="avam-mobile-menu-backdrop" type="button" aria-label="بستن منو" hidden></button>
  <aside class="avam-dash-sidebar" id="avam-account-sidebar">
    <a class="avam-dash-brand" href="<?php echo esc_url(home_url('/')); ?>">
      <span class="avam-dash-brand-mark">آ</span>
      <span><b><?php echo esc_html(is_user_logged_in() ? $avam_display_name : 'آرامگاه مجازی'); ?></b><small><?php echo is_user_logged_in() ? 'فضای شخصی شما' : 'یادها اینجا می‌مانند'; ?></small></span>
    </a>
    <nav class="avam-dash-nav" aria-label="ناوبری اصلی">
      <a href="<?php echo esc_url(home_url('/')); ?>"<?php echo is_front_page() ? ' aria-current="page"' : ''; ?>><span class="avam-nav-icon" aria-hidden="true">⌂</span>خانه</a>
      <a href="<?php echo esc_url($avam_memorials_url); ?>"<?php echo is_page() && get_post_field('post_name',get_queried_object_id())==='memorials' ? ' aria-current="page"' : ''; ?>><span class="avam-nav-icon" aria-hidden="true">⌕</span>جست‌وجوی یادبودها</a>
      <?php if (is_user_logged_in()): ?>
        <a href="<?php echo esc_url($avam_account_url); ?>"><span class="avam-nav-icon" aria-hidden="true">▦</span>داشبورد من</a>
        <a href="<?php echo esc_url(home_url('/my-comments/')); ?>"><span class="avam-nav-icon" aria-hidden="true">☷</span>دیدگاه‌های من</a>
        <a href="<?php echo esc_url($avam_create_url); ?>"><span class="avam-nav-icon" aria-hidden="true">＋</span>ساخت یادبود</a>
        <a href="<?php echo esc_url(home_url('/profile/')); ?>"><span class="avam-nav-icon" aria-hidden="true">⚙</span>تنظیمات حساب</a>
      <?php else: ?>
        <a href="<?php echo esc_url($avam_login_url); ?>"><span class="avam-nav-icon" aria-hidden="true">↪</span>ورود</a>
        <a href="<?php echo esc_url($avam_register_url); ?>"><span class="avam-nav-icon" aria-hidden="true">＋</span>ثبت‌نام</a>
      <?php endif; ?>
    </nav>
    <div class="avam-dash-sidebar-foot">
      <button class="avam-theme-toggle" type="button" data-avam-theme-toggle aria-pressed="false"><span aria-hidden="true">☼</span><span data-avam-theme-label>حالت روشن</span></button>
      <?php if (is_user_logged_in()): ?>
        <a class="avam-dash-logout" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"><span aria-hidden="true">↪</span>خروج از حساب</a>
      <?php else: ?>
        <a class="avam-dash-logout" href="<?php echo esc_url($avam_register_url); ?>"><span aria-hidden="true">＋</span>ایجاد حساب رایگان</a>
      <?php endif; ?>
    </div>
  </aside>
  <div class="avam-dash-main avam-unified-main">
    <header class="avam-ref-topbar avam-site-identity-bar">
      <span class="avam-site-title">آرامگاه مجازی</span>
    </header>
    <main id="main" class="avam-unified-content">
