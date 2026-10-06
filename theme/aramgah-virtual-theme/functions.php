<?php
if (!defined('ABSPATH')) exit;
function avam_theme_setup(){
 add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('html5',['search-form','gallery','caption','style','script']);
 register_nav_menus(['primary'=>'منوی اصلی']);
}
add_action('after_setup_theme','avam_theme_setup');
function avam_theme_assets(){
 wp_enqueue_style('avam-fonts','https://fonts.googleapis.com/css2?family=Noto+Serif+Arabic:wght@400;500;600;700&family=Vazirmatn:wght@400;500;600;700&display=swap',[],null);
 wp_enqueue_style('avam-style',get_stylesheet_uri(),[], '1.4.0');
 wp_enqueue_script('avam-main',get_template_directory_uri().'/assets/js/main.js',[], '2.0.0', true);
}
add_action('wp_enqueue_scripts','avam_theme_assets');
function avam_body_classes($classes){$classes[]='avam-shell';return $classes;} add_filter('body_class','avam_body_classes');
