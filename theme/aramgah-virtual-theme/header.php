<!doctype html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#f5f7f6">
<?php wp_head(); ?>
<style>
/* Shared full-width identity bar; keep its palette aligned with the application panel. */
.avam-unified-main>.avam-ref-topbar.avam-site-identity-bar{
  box-sizing:border-box;
  width:calc(100% + 60px);
  min-height:88px;
  margin:0 -30px 8px!important;
  padding:18px 30px!important;
  background:#f5f7f6;
  border-bottom:1px solid #e0e7e3;
  color:#26382f;
}
.avam-site-identity-brand{display:flex;align-items:center;gap:13px;min-width:0;color:inherit;text-decoration:none}
.avam-site-identity-mark{display:flex;align-items:center;justify-content:center;flex:0 0 44px;width:44px;height:44px;border:1px solid #dce5de;border-radius:14px;background:#e8eee9;color:#526d5b;font-family:inherit;font-size:24px;font-weight:700}
.avam-site-identity-copy{display:flex;flex-direction:column;gap:3px;min-width:0}
.avam-site-identity-copy strong{color:#26382f;font-size:17px;font-weight:750;line-height:1.5}
.avam-site-identity-copy small{color:#718076;font-size:12px;line-height:1.5}
.avam-site-identity-bar .avam-ref-user{display:flex;align-items:center;gap:10px;flex:0 0 auto;margin-inline-start:auto}
.avam-site-identity-bar .avam-ref-user>div{display:flex;flex-direction:column;gap:3px}
.avam-site-identity-bar .avam-ref-user strong{color:#26382f;font-size:13px;font-weight:700}
.avam-site-identity-bar .avam-ref-user small{color:#718076;font-size:11px}
@media(max-width:760px){
 .avam-unified-main>.avam-ref-topbar.avam-site-identity-bar{width:calc(100% + 24px);min-height:72px;margin:0 -12px 8px!important;padding:12px!important;gap:12px}
 .avam-site-identity-mark{flex-basis:38px;width:38px;height:38px;border-radius:12px;font-size:21px}
 .avam-site-identity-copy strong{font-size:14px}
 .avam-site-identity-copy small{font-size:10px}
 .avam-site-identity-bar .avam-ref-user{gap:6px}
 .avam-site-identity-bar .avam-ref-user>div{display:flex}
 .avam-site-identity-bar .avam-ref-user strong{max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px}
 .avam-site-identity-bar .avam-ref-user small{font-size:10px}
}
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
    <header class="avam-ref-topbar avam-site-identity-bar">
      <a class="avam-site-identity-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="آرامگاه مجازی، صفحه اصلی">
        <span class="avam-site-identity-mark" aria-hidden="true">آ</span>
        <span class="avam-site-identity-copy"><strong>آرامگاه مجازی</strong><small><?php echo is_user_logged_in() ? 'فضای شخصی شما' : 'یادها اینجا می‌مانند'; ?></small></span>
      </a>
      <div class="avam-ref-user">
        <span class="avam-ref-avatar"><?php echo esc_html($avam_initial); ?></span>
        <div><strong><?php echo esc_html($avam_display_name); ?></strong><small><?php echo is_user_logged_in() ? 'نام کاربری' : 'دسترسی مهمان'; ?></small></div>
      </div>
    </header>
    <main id="main" class="avam-unified-content">
