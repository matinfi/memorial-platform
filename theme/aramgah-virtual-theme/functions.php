<?php
if (!defined('ABSPATH')) exit;
function avam_theme_setup(){
 add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('html5',['search-form','gallery','caption','style','script']);
 register_nav_menus(['primary'=>'منوی اصلی']);
}
add_action('after_setup_theme','avam_theme_setup');
function avam_theme_assets(){
 $settings=get_option('avam_control_center',[]);
 if(!is_array($settings))$settings=[];
 $cache_busting=($settings['cache_busting']??'1')==='1';
 $theme_asset_version=static function($relative,$fallback)use($cache_busting){
  $path=get_template_directory().'/'.ltrim($relative,'/');
  return $cache_busting && is_file($path) ? (string)filemtime($path) : $fallback;
 };
 if (($settings['font_family']??'vazirmatn')!=='system') wp_enqueue_style('avam-fonts','https://fonts.googleapis.com/css2?family=Noto+Serif+Arabic:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&display=swap',[],null);
 wp_enqueue_style('avam-style',get_stylesheet_uri(),[], $theme_asset_version('style.css','2.2.2'));
 $primary=sanitize_hex_color(get_option('avam_primary_color','#2c3531')) ?: '#2c3531';
 $accent=sanitize_hex_color(get_option('avam_accent_color','#a67c50')) ?: '#a67c50';
 wp_add_inline_style('avam-style',':root{--ink:'.$primary.';--cta:'.$primary.';--accent:'.$accent.';--c-primary:'.$primary.';--c-accent:'.$accent.';}');
 wp_enqueue_script('avam-main',get_template_directory_uri().'/assets/js/main.js',[], $theme_asset_version('assets/js/main.js','2.0.0'), true);
 if(is_singular('avam_memorial')) wp_enqueue_style('avam-memorial-view',get_template_directory_uri().'/assets/css/memorial-view.css',['avam-style'],$theme_asset_version('assets/css/memorial-view.css','2.4.0'));
}
function avam_filter_controlled_menu_items($items,$args){
 $settings=get_option('avam_control_center',[]);
 if(!is_array($settings)) return $items;
 $registration_open=(($settings['registration_enabled']??'1')==='1') && (($settings['public_registration']??'1')==='1');
 $comments_link_enabled=($settings['account_comments_link']??'1')==='1';
 if($registration_open && $comments_link_enabled) return $items;
 $register_id=absint($settings['register_page']??0);
 $register_url=function_exists('avam_register_url')?avam_register_url():wp_registration_url();
 $register_path=untrailingslashit((string)wp_parse_url($register_url,PHP_URL_PATH));
 $comments_id=absint($settings['comments_page']??0);
 $comments_url=$comments_id && get_post_type($comments_id)==='page' ? get_permalink($comments_id) : home_url('/my-comments/');
 $comments_path=untrailingslashit((string)wp_parse_url($comments_url,PHP_URL_PATH));
 foreach($items as $index=>$item){
  $item_path=untrailingslashit((string)wp_parse_url($item->url??'',PHP_URL_PATH));
  if(!$registration_open && (($register_id && isset($item->object_id) && absint($item->object_id)===$register_id) || ($register_path!=='' && $item_path===$register_path))) unset($items[$index]);
  elseif(!$comments_link_enabled && (($comments_id && isset($item->object_id) && absint($item->object_id)===$comments_id) || ($comments_path!=='' && $item_path===$comments_path))) unset($items[$index]);
 }
 return array_values($items);
}
add_filter('wp_nav_menu_objects','avam_filter_controlled_menu_items',10,2);

add_action('wp_enqueue_scripts','avam_theme_assets');
function avam_body_classes($classes){$classes[]='avam-shell';return $classes;} add_filter('body_class','avam_body_classes');

add_action('admin_menu',function(){
 add_theme_page('تنظیمات آرامگاه مجازی','تنظیمات آرامگاه مجازی','manage_options','avam-theme-settings',function(){
  if(!current_user_can('manage_options')) return;
  echo '<div class="wrap" dir="rtl"><h1>تنظیمات قالب آرامگاه مجازی</h1><p>مدیریت تمام جزئیات محتوایی و ظاهری سایت در یک مرکز تنظیمات.</p><p><a class="button button-primary" href="'.esc_url(admin_url('options-general.php?page=avam-settings')).'">باز کردن مرکز تنظیمات آرامگاه مجازی</a></p></div>';
 });
});
