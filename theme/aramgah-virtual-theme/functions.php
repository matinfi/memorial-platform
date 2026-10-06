<?php
if (!defined('ABSPATH')) exit;
function avam_theme_setup(){
 add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('html5',['search-form','gallery','caption','style','script']);
 register_nav_menus(['primary'=>'منوی اصلی']);
}
add_action('after_setup_theme','avam_theme_setup');
function avam_theme_assets(){
 wp_enqueue_style('avam-fonts','https://fonts.googleapis.com/css2?family=Noto+Serif+Arabic:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&display=swap',[],null);
 wp_enqueue_style('avam-style',get_stylesheet_uri(),[], '1.6.0');
 $primary=sanitize_hex_color(get_option('avam_primary_color','#17253d')) ?: '#17253d';
 $accent=sanitize_hex_color(get_option('avam_accent_color','#a76652')) ?: '#a76652';
 wp_add_inline_style('avam-style',':root{--ink:'.$primary.';--cta:'.$primary.';--accent:'.$accent.';--c-primary:'.$primary.';--c-accent:'.$accent.';}');
 wp_enqueue_script('avam-main',get_template_directory_uri().'/assets/js/main.js',[], '2.0.0', true);
}
add_action('wp_enqueue_scripts','avam_theme_assets');
function avam_body_classes($classes){$classes[]='avam-shell';return $classes;} add_filter('body_class','avam_body_classes');

add_action('admin_menu',function(){
 add_theme_page('تنظیمات آرامگاه مجازی','تنظیمات آرامگاه مجازی','manage_options','avam-theme-settings',function(){
  if(!current_user_can('manage_options')) return;
  echo '<div class="wrap" dir="rtl"><h1>تنظیمات قالب آرامگاه مجازی</h1><p>مدیریت تمام جزئیات محتوایی و ظاهری سایت در یک مرکز تنظیمات.</p><p><a class="button button-primary" href="'.esc_url(admin_url('options-general.php?page=avam-settings')).'">باز کردن مرکز تنظیمات آرامگاه مجازی</a></p></div>';
 });
});
