<?php
/**
 * Plugin Name: آرامگاه مجازی — Core
 * Description: Core memorial content, authentication, search, front-end account/create flows and administrator settings.
 * Version: 1.11.6
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: avam
 */
if (!defined('ABSPATH')) exit;

final class AVAM_Core {
 const CPT='avam_memorial';

 public static function init(){
  add_action('init',[__CLASS__,'register_cpt']);
  add_action('init',[__CLASS__,'shortcodes']);
  add_action('admin_menu',[__CLASS__,'admin_menu']);
  add_action('admin_init',[__CLASS__,'settings']);
  add_action('add_meta_boxes',[__CLASS__,'meta_boxes']);
  add_action('save_post_'.self::CPT,[__CLASS__,'save_meta']);
  add_action('wp_enqueue_scripts',[__CLASS__,'assets']);
  add_action('admin_enqueue_scripts',[__CLASS__,'admin_assets']);
  add_action('admin_init',[__CLASS__,'protect_admin']);
  add_action('after_setup_theme',[__CLASS__,'hide_admin_bar']);
  add_action('template_redirect',[__CLASS__,'handle_auth']);
  add_action('wp_ajax_avam_save_memorial',[__CLASS__,'save_front_memorial']);
  add_action('wp_ajax_avam_delete_memorial',[__CLASS__,'delete_front_memorial']);
  add_action('wp_ajax_avam_client_log',[__CLASS__,'log_client_error']);
  add_action('wp_ajax_nopriv_avam_client_log',[__CLASS__,'log_client_error']);
 }

 public static function register_cpt(){
  register_post_type(self::CPT,[
   'labels'=>['name'=>'یادبودها','singular_name'=>'یادبود','add_new'=>'یادبود جدید','add_new_item'=>'افزودن یادبود','edit_item'=>'ویرایش یادبود','menu_name'=>'یادبودها'],
   'public'=>true,'exclude_from_search'=>true,'show_in_rest'=>true,'has_archive'=>'memorials','rewrite'=>['slug'=>'memorial','with_front'=>false],
   'supports'=>['title','editor','thumbnail','author','comments'],'menu_icon'=>'dashicons-heart','capability_type'=>'post','map_meta_cap'=>true
  ]);
 }

 public static function assets(){
  $version='1.11.6';
  $cc=get_option('avam_control_center',[]);
  $cache_busting=!is_array($cc)||($cc['cache_busting']??'1')==='1';
  $asset_version=static function($relative)use($version,$cache_busting){
   $path=plugin_dir_path(__FILE__).ltrim($relative,'/');
   return $cache_busting && is_file($path) ? (string)filemtime($path) : $version;
  };
  wp_enqueue_style('avam-plugin',plugins_url('assets/css/core.css',__FILE__),[], $asset_version('assets/css/core.css'));
  wp_enqueue_style('avam-unified-shell',plugins_url('assets/css/unified-shell.css',__FILE__),['avam-plugin'],$asset_version('assets/css/unified-shell.css'));
  wp_enqueue_style('avam-archive-mobile-fixes',plugins_url('assets/css/archive-mobile-fixes.css',__FILE__),['avam-unified-shell'],$asset_version('assets/css/archive-mobile-fixes.css'));
  wp_enqueue_script('avam-plugin',plugins_url('assets/js/core.js',__FILE__),['jquery'], $asset_version('assets/js/core.js'),true);
  wp_localize_script('avam-plugin','AVAM',['ajax'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('avam_front'),'account'=>avam_account_url(),'diagnostics'=>class_exists('AVAM_Logger')&&AVAM_Logger::enabled(),'clientLogNonce'=>wp_create_nonce('avam_client_log'),'confirmDelete'=>!is_array($cc)||($cc['advanced_confirm_delete']??'1')==='1']);
 }

 public static function admin_assets($hook){
  if($hook!=='settings_page_avam-settings') return;
  wp_enqueue_media();
  $script = <<<'JS'
jQuery(function($){
  $('.avam-media-pick').on('click',function(e){
    e.preventDefault();
    var b=$(this),f=b.data('field');
    var t=wp.media({
      title:'انتخاب فایل',
      button:{text:'انتخاب'},
      multiple:false,
      library:{type:b.data('type')||''}
    });
    t.on('select',function(){
      var a=t.state().get('selection').first().toJSON();
      $('#'+f).val(a.id);
      $('#'+f+'-url').val(a.url);
    });
    t.open();
  });

  /* Admin settings accordion: keep the long options page scannable and keyboard-accessible. */
  var headings=$('.avam-admin-settings form h2');
  headings.each(function(index){
    var heading=$(this),group=heading.nextUntil('h2');
    heading.attr({role:'button',tabindex:'0','aria-expanded':index===0?'true':'false'})
      .css({display:'flex',alignItems:'center',justifyContent:'space-between',gap:'16px',padding:'16px 18px',margin:'18px 0 0',border:'1px solid #d7e0d9',borderRadius:'12px',background:'#f7f9f7',cursor:'pointer',fontWeight:'700'});
    var indicator=$('<span aria-hidden="true">').text(index===0?'−':'+')
      .css({display:'inline-flex',alignItems:'center',justifyContent:'center',width:'28px',height:'28px',borderRadius:'8px',background:'#e8eee9',color:'#526d5b',fontSize:'20px',lineHeight:'1',flex:'0 0 28px'});
    heading.append(indicator);
    group.css({marginInline:'0',paddingInline:'18px'});
    if(index!==0)group.hide();
    function toggle(){
      var open=heading.attr('aria-expanded')!=='true';
      heading.attr('aria-expanded',open?'true':'false');
      group.stop(true,true).slideToggle(160);
      indicator.text(open?'−':'+');
    }
    heading.on('click',toggle).on('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();toggle();}});
  });
});
JS;
  wp_add_inline_script('jquery-core',$script);
 }
 public static function shortcodes(){
  add_shortcode('avam_login',[__CLASS__,'login']);
  add_shortcode('avam_register',[__CLASS__,'register']);
  add_shortcode('avam_account',[__CLASS__,'account']);
  add_shortcode('avam_create_memorial',[__CLASS__,'create']);
  add_shortcode('avam_memorial_search',[__CLASS__,'search']);
 }

 public static function login(){
  if(is_user_logged_in()){ wp_safe_redirect(avam_account_url()); exit; }
  $err=isset($_GET['avam_error'])&&is_string($_GET['avam_error'])?sanitize_text_field(wp_unslash($_GET['avam_error'])):'';
  ob_start(); ?><div class="avam-card avam-auth-card"><h1>ورود به آرامگاه</h1><p class="avam-auth-lead">برای مدیریت یادبودها و روایت‌های شما.</p><?php if($err):?><div class="avam-notice avam-error"><?php echo esc_html($err);?></div><?php endif;?><form class="avam-form" method="post"><div class="avam-field"><label for="avam-login-name">نام کاربری یا ایمیل</label><input id="avam-login-name" name="log" autocomplete="username" required></div><div class="avam-field"><label for="avam-login-password">رمز عبور</label><input id="avam-login-password" type="password" name="pwd" autocomplete="current-password" required></div><?php wp_nonce_field('avam_login','avam_nonce');?><input type="hidden" name="avam_action" value="login"><input type="hidden" name="redirect_to" value="<?php echo esc_attr(isset($_GET['redirect_to']) && is_string($_GET['redirect_to']) ? wp_validate_redirect(wp_unslash($_GET['redirect_to']),avam_account_url()) : avam_account_url()); ?>"><button class="avam-btn avam-auth-btn" type="submit">ورود</button></form><p class="avam-auth-foot">حساب ندارید؟ <a href="<?php echo esc_url(avam_register_url());?>">ساخت حساب</a></p></div><?php return ob_get_clean();
 }

 public static function register(){
  if(is_user_logged_in()){ wp_safe_redirect(avam_account_url()); exit; }
  $avam_cc=get_option('avam_control_center',[]);
  if(is_array($avam_cc) && (($avam_cc['registration_enabled']??'1')!=='1' || ($avam_cc['public_registration']??'1')!=='1')) {
   return '<div class="avam-card avam-auth-card" dir="rtl"><h1>ثبت‌نام موقتاً بسته است</h1><p>در حال حاضر امکان ساخت حساب جدید وجود ندارد. اگر حساب دارید، می‌توانید وارد شوید.</p><a class="avam-btn" href="'.esc_url(avam_login_url()).'">ورود به حساب</a></div>';
  }
  $err=isset($_GET['avam_error'])&&is_string($_GET['avam_error'])?sanitize_text_field(wp_unslash($_GET['avam_error'])):'';
  ob_start(); ?><div class="avam-card avam-auth-card"><h1>ساخت حساب</h1><p class="avam-auth-lead">یک فضای شخصی برای نگه‌داری یادها بسازید.</p><?php if($err):?><div class="avam-notice avam-error"><?php echo esc_html($err);?></div><?php endif;?><form class="avam-form" method="post"><div class="avam-field"><label for="avam-register-name">نام نمایشی</label><input id="avam-register-name" name="display_name" maxlength="80" required></div><div class="avam-field"><label for="avam-register-email">ایمیل</label><input id="avam-register-email" type="email" name="email" autocomplete="email" required></div><div class="avam-field"><label for="avam-register-password">رمز عبور</label><input id="avam-register-password" type="password" name="password" minlength="10" autocomplete="new-password" required></div><?php wp_nonce_field('avam_register','avam_nonce');?><input type="hidden" name="avam_action" value="register"><input type="hidden" name="redirect_to" value="<?php echo esc_attr(isset($_GET['redirect_to']) && is_string($_GET['redirect_to']) ? wp_validate_redirect(wp_unslash($_GET['redirect_to']),avam_account_url()) : avam_account_url()); ?>"><button class="avam-btn avam-auth-btn" type="submit">ایجاد حساب</button></form><p class="avam-auth-foot">حساب دارید؟ <a href="<?php echo esc_url(avam_login_url());?>">ورود</a></p></div><?php return ob_get_clean();
 }

 public static function account(){
  if(!is_user_logged_in()){ wp_safe_redirect(add_query_arg('redirect_to',rawurlencode(avam_account_url()),avam_login_url())); exit; }
  $account_settings=get_option('avam_control_center',[]);
  if(is_array($account_settings) && ($account_settings['account_dashboard_enabled']??'1')!=='1' && !current_user_can('manage_options')) return '<section class="avam-card" dir="rtl"><h1>داشبورد موقتاً غیرفعال است</h1><p>مدیریت داشبورد در حال حاضر در دسترس نیست. برای ادامه از پیوندهای ناوبری استفاده کنید.</p></section>';
  $uid=get_current_user_id();
  $user=wp_get_current_user();
  $show_stats=($account_settings['account_stats_enabled']??'1')==='1';
  $show_recent=($account_settings['account_recent_enabled']??'1')==='1';
  $published=0; $draft_only=0; $pending=0;
  if($show_stats){
   $count_status=static function($status)use($uid){
    $query=new WP_Query(['post_type'=>self::CPT,'author'=>$uid,'post_status'=>$status,'posts_per_page'=>1,'fields'=>'ids','no_found_rows'=>false,'update_post_meta_cache'=>false,'update_post_term_cache'=>false]);
    return (int)$query->found_posts;
   };
   $published=$count_status('publish');
   $draft_only=$count_status('draft');
   $pending=$count_status('pending');
  }
  $drafts=$draft_only+$pending;
  $total=$published+$drafts;
  $recent=($show_recent||$show_stats) ? get_posts(['post_type'=>self::CPT,'author'=>$uid,'posts_per_page'=>5,'post_status'=>['publish','draft','pending'],'orderby'=>'date','order'=>'DESC']) : [];
  $create=avam_create_url();$memorials=avam_memorials_url();$logout=wp_logout_url(home_url('/'));
  $display_name=$user->display_name ?: $user->user_login;
  $initial=mb_substr($display_name,0,1);
  $max_status=max(1,$total,$published,$drafts);
  $pub_h=round(($published/$max_status)*132);
  $total_h=round(($total/$max_status)*132);
  $draft_only_h=round(($draft_only/$max_status)*132);
  $pending_h=round(($pending/$max_status)*132);
  $published_percent=$total>0?round(($published/$total)*100):0;
  $draft_percent=$total>0?round(($drafts/$total)*100):0;
  ob_start(); ?>
  <section class="avam-account-dashboard-content" dir="rtl">
    <header class="avam-ref-heading">
        <div><span>فضای شخصی</span><h1>داشبورد</h1></div>
        <a class="avam-dash-primary" href="<?php echo esc_url($create); ?>"><span><svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg></span> ساخت یادبود</a>
      </header>

      <section class="avam-ref-stats" aria-label="خلاصه حساب">
        <article><span class="avam-ref-stat-icon"><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 8.7c0 4.4-8.5 10-8.5 10s-8.5-5.6-8.5-10a4.4 4.4 0 0 1 8.5-1.2 4.4 4.4 0 0 1 8.5 1.2Z"/></svg></span><div><small>مجموع یادبودها</small><strong><?php echo esc_html($total); ?></strong></div><em>همه یادبودها</em></article>
        <article><span class="avam-ref-stat-icon is-green"><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.2 4.2L19 6.5"/></svg></span><div><small>یادبودهای منتشرشده</small><strong><?php echo esc_html($published); ?></strong></div><em>قابل مشاهده</em></article>
        <article><span class="avam-ref-stat-icon is-blue"><svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><div><small>در حال تکمیل</small><strong><?php echo esc_html($drafts); ?></strong></div><em>پیش‌نویس / بررسی</em></article>
        <article><span class="avam-ref-stat-icon is-terra"><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="m19 15 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"/></svg></span><div><small>آخرین فعالیت</small><strong><?php echo $recent ? esc_html(get_the_date('j M',$recent[0])) : '—'; ?></strong></div><em><?php echo $recent ? 'آخرین یادبود' : 'هنوز فعالیتی نیست'; ?></em></article>
      </section>

      <section class="avam-ref-chart-grid">
        <article class="avam-ref-panel">
          <div class="avam-ref-panel-head"><div><span>نمای کلی محتوا</span><h2>وضعیت یادبودها</h2></div><span class="avam-ref-filter">بر اساس وضعیت</span></div>
          <div class="avam-ref-linechart" aria-label="توزیع یادبودها بر اساس وضعیت">
            <div style="display:grid;gap:22px;padding:20px 4px">
              <div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:9px"><strong>یادبودهای منتشرشده</strong><span><?php echo esc_html($published); ?> (<?php echo esc_html($published_percent); ?>٪)</span></div>
                <div role="progressbar" aria-label="درصد یادبودهای منتشرشده" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr($published_percent); ?>" style="height:10px;border-radius:999px;background:rgba(127,127,127,.16);overflow:hidden"><span style="display:block;width:<?php echo esc_attr($published_percent); ?>%;height:100%;border-radius:inherit;background:#5d59df"></span></div>
              </div>
              <div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:9px"><strong>پیش‌نویس و در انتظار بررسی</strong><span><?php echo esc_html($drafts); ?> (<?php echo esc_html($draft_percent); ?>٪)</span></div>
                <div role="progressbar" aria-label="درصد پیش‌نویس‌ها و یادبودهای در انتظار بررسی" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr($draft_percent); ?>" style="height:10px;border-radius:999px;background:rgba(127,127,127,.16);overflow:hidden"><span style="display:block;width:<?php echo esc_attr($draft_percent); ?>%;height:100%;border-radius:inherit;background:#ad875c"></span></div>
              </div>
              <p style="margin:0;color:var(--c-text-secondary,#6b7280);font-size:13px">نمایش بر اساس وضعیت فعلی یادبودهای حساب شما؛ داده زمانی در این نمودار استفاده نمی‌شود.</p>
            </div>
          </div>
        </article>

        <article class="avam-ref-panel">
          <div class="avam-ref-panel-head"><div><span>وضعیت محتوا</span><h2>وضعیت یادبودها</h2></div><span class="avam-ref-filter">همه <b>⌄</b></span></div>
          <div class="avam-ref-barchart">
            <div class="avam-ref-bars">
              <div><strong><?php echo esc_html($total); ?></strong><i style="height:<?php echo esc_attr($total_h); ?>px"></i><small>همه</small></div>
              <div><strong><?php echo esc_html($published); ?></strong><i style="height:<?php echo esc_attr($pub_h); ?>px"></i><small>منتشر</small></div>
              <div><strong><?php echo esc_html($draft_only); ?></strong><i style="height:<?php echo esc_attr(max(4,$draft_only_h)); ?>px"></i><small>پیش‌نویس</small></div>
              <div><strong><?php echo esc_html($pending); ?></strong><i style="height:<?php echo esc_attr(max(4,$pending_h)); ?>px"></i><small>در انتظار</small></div>
            </div>
          </div>
        </article>
      </section>

      <section class="avam-ref-table-panel">
        <div class="avam-ref-panel-head"><div><span>مدیریت محتوا</span><h2>آخرین یادبودها</h2></div><a href="<?php echo esc_url($create); ?>">+ افزودن یادبود</a></div>
        <?php if($recent): ?>
        <div class="avam-ref-table-wrap">
          <table class="avam-ref-table">
            <thead><tr><th>یادبود</th><th>شهر</th><th>تاریخ</th><th>وضعیت</th><th>عملیات</th></tr></thead>
            <tbody>
            <?php foreach($recent as $p):
              $city=get_post_meta($p->ID,'avam_city',true);
              $status=$p->post_status==='publish'?'منتشر شده':($p->post_status==='draft'?'پیش‌نویس':'در انتظار بررسی');
              $edit=add_query_arg('edit',(int)$p->ID,$create);
              $view=$p->post_status==='publish'?get_permalink($p):get_preview_post_link($p);
            ?>
              <tr>
                <td><div class="avam-ref-person"><span class="avam-ref-person-photo"><?php $thumb=get_the_post_thumbnail_url($p->ID,'thumbnail'); if($thumb): ?><img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($p->post_title); ?>"><?php else: ?><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c.4-3.5 2.8-5.5 6.5-5.5s6.1 2 6.5 5.5"/><path d="M3 4v4M3 4h4M21 20v-4M21 20h-4"/></svg><?php endif; ?></span><strong><?php echo esc_html($p->post_title); ?></strong></div></td>
                <td><?php echo esc_html($city ?: '—'); ?></td>
                <td><?php echo esc_html(get_the_date('j F Y',$p)); ?></td>
                <td><span class="avam-ref-status <?php echo $p->post_status==='publish'?'is-published':''; ?>"><i></i><?php echo esc_html($status); ?></span></td>
                <td><div class="avam-ref-actions"><a href="<?php echo esc_url($view); ?>" aria-label="پیش‌نمایش یا مشاهده">◉</a><a href="<?php echo esc_url($edit); ?>" aria-label="ویرایش"><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m15 5 4 4"/><path d="m4 20 4.3-.9L19.5 7.9a2.1 2.1 0 0 0-3-3L5.3 16.1 4 20Z"/></svg></a><button type="button" class="avam-action-delete" data-id="<?php echo esc_attr($p->ID); ?>" aria-label="حذف"><svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="m5 7 1 14h12l1-14"/><path d="M9 7V4h6v3"/></svg></button></div></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <div class="avam-ref-empty"><div><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 8.7c0 4.4-8.5 10-8.5 10s-8.5-5.6-8.5-10a4.4 4.4 0 0 1 8.5-1.2 4.4 4.4 0 0 1 8.5 1.2Z"/></svg></div><h3>هنوز یادبودی نساخته‌اید</h3><p>اولین یادبود را بسازید و نام، تصویر و روایت عزیزتان را در یک صفحه ماندگار نگه دارید.</p><a class="avam-dash-primary" href="<?php echo esc_url($create); ?>">ساخت اولین یادبود</a></div>
        <?php endif; ?>
      </section>
    </section>
  <?php return ob_get_clean();
 }

 public static function create(){
  $avam_cc=get_option('avam_control_center',[]); $avam_private_enabled=!is_array($avam_cc)||($avam_cc['private_memorials_enabled']??'1')==='1'||current_user_can('manage_options'); if(is_array($avam_cc) && ($avam_cc['memorial_creation_enabled']??'1')!=='1' && !current_user_can('manage_options')) return '<div class="avam-card"><p>ساخت یادبود در حال حاضر غیرفعال است.</p></div>';
  if(!is_user_logged_in()) return '<div class="avam-card"><p>برای ساخت یادبود ابتدا وارد شوید.</p><a class="avam-btn" href="'.esc_url(avam_login_url()).'">ورود</a></div>';
  $edit_id=isset($_GET['edit'])?absint($_GET['edit']):0;$editing=false;$data=[];
  if($edit_id){$p=get_post($edit_id);if($p&&$p->post_type===self::CPT&&(int)$p->post_author===get_current_user_id()){$editing=true;$data=['title'=>$p->post_title,'content'=>$p->post_content,'image_url'=>get_the_post_thumbnail_url($edit_id,'medium')?:'','gallery_ids'=>get_post_meta($edit_id,'avam_gallery_ids',true)];foreach(['city','birth','death','visibility','timeline'] as $k)$data[$k]=get_post_meta($edit_id,'avam_'.$k,true);}}
  $val=function($k)use($data){return esc_attr($data[$k]??'');};
  ob_start(); ?>
  <div class="avam-card avam-create-card">
    <div class="avam-create-top"><div><span class="avam-dash-kicker"><?php echo $editing?'ویرایش یادبود':'ساخت یادبود'; ?></span><h1><?php echo $editing?'روایت را کامل‌تر کنید':'صفحه‌ای برای ماندن'; ?></h1><p class="avam-auth-lead"><?php echo $editing?'اطلاعات این یادبود را ویرایش کنید و جزئیات بیشتری به آن اضافه کنید.':'اطلاعاتی که دوست دارید برای همیشه کنار نام او بماند.'; ?></p></div><a class="avam-create-back" href="<?php echo esc_url(avam_account_url()); ?>">← بازگشت به حساب</a></div>
    <div id="avam-create-notice"></div>
    <form id="avam-create-form" class="avam-form avam-memorial-form" enctype="multipart/form-data">
      <input type="hidden" name="edit_id" value="<?php echo esc_attr($editing?$edit_id:0); ?>"><input type="hidden" name="gallery_management" value="1">
      <div class="avam-form-section"><div class="avam-form-section-title"><span>01</span><div><strong>اطلاعات اصلی</strong><small>مشخصات پایه عزیز شما</small></div></div>
        <div class="avam-form-grid"><div class="avam-field"><label for="avam-memorial-title">نام و نام خانوادگی متوفی</label><input id="avam-memorial-title" name="title" value="<?php echo $val('title'); ?>" required></div><div class="avam-field"><label for="avam-memorial-city">شهر</label><input id="avam-memorial-city" name="city" value="<?php echo $val('city'); ?>" placeholder="مثلاً تهران"></div><div class="avam-field"><label for="avam-memorial-birth">تاریخ تولد</label><input id="avam-memorial-birth" name="birth" value="<?php echo $val('birth'); ?>" placeholder="مثلاً ۱۳۳۵/۰۳/۱۰"></div><div class="avam-field"><label for="avam-memorial-death">تاریخ درگذشت</label><input id="avam-memorial-death" name="death" value="<?php echo $val('death'); ?>" placeholder="مثلاً ۱۴۰۴/۰۸/۲۱"></div></div>
      </div>
      <div class="avam-form-section"><div class="avam-form-section-title"><span>02</span><div><strong>روایت زندگی</strong><small>داستانی که باید ماندگار بماند</small></div></div><div class="avam-field"><label for="avam-memorial-content">روایت زندگی</label><textarea id="avam-memorial-content" name="content" required placeholder="از زندگی، شخصیت، لحظه‌های مهم و چیزهایی که دوست دارید دیگران بدانند بنویسید..."><?php echo esc_textarea($data['content']??''); ?></textarea></div></div>\n      <div class="avam-form-section"><div class="avam-form-section-title"><span>03</span><div><strong>خط زمانی زندگی</strong><small>هر رویداد را در یک خط با قالب «تاریخ | رویداد» بنویسید</small></div></div><div class="avam-field"><label for="avam-memorial-timeline">رویدادهای مهم زندگی (اختیاری)</label><textarea id="avam-memorial-timeline" name="timeline" placeholder="۱۳۳۵/۰۳/۱۰ | تولد\n۱۳۵۸/۰۲/۲۰ | آغاز زندگی مشترک\n۱۴۰۴/۰۸/۲۱ | درگذشت"><?php echo esc_textarea($data['timeline']??''); ?></textarea></div></div>
      
      <div class="avam-form-section"><div class="avam-form-section-title"><span>04</span><div><strong>تصویر و حریم خصوصی</strong><small>تصویر و دسترسی به یادبود را انتخاب کنید</small></div></div><div class="avam-media-manager"><div class="avam-field"><label for="avam-featured-image">تصویر اصلی یادبود</label><div class="avam-upload" data-upload-zone="featured"><input id="avam-featured-image" type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-describedby="avam-featured-help"><div><strong><?php echo $editing?'برای تغییر تصویر انتخاب کنید':'انتخاب تصویر اصلی'; ?></strong><small id="avam-featured-help">تصویر واضح و باکیفیت انتخاب کنید؛ JPG، PNG یا WebP تا ۸ مگابایت</small><span class="avam-upload-filename" data-upload-filename="featured"></span></div><span aria-hidden="true">＋</span></div><div class="avam-image-preview avam-featured-preview" data-preview="featured" <?php echo empty($data['image_url'])?'hidden':''; ?>><?php if(!empty($data['image_url'])): ?><img src="<?php echo esc_url($data['image_url']); ?>" alt="پیش‌نمایش تصویر اصلی یادبود"><span>تصویر فعلی</span><?php endif; ?></div></div><div class="avam-field"><label for="avam-gallery-images">گالری خاطرات</label><div class="avam-upload avam-upload-gallery" data-upload-zone="gallery"><input id="avam-gallery-images" type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple aria-describedby="avam-gallery-help" data-existing-count="<?php echo esc_attr(is_array($data['gallery_ids']??null)?count($data['gallery_ids']):0); ?>"><div><strong>افزودن عکس به گالری</strong><small id="avam-gallery-help">تا ۱۰ عکس در مجموع؛ هر عکس حداکثر ۸ مگابایت</small><span class="avam-upload-filename" data-upload-filename="gallery"></span></div><span aria-hidden="true">▧</span></div><div class="avam-image-preview avam-gallery-existing" data-preview="gallery-existing"><?php if(!empty($data['gallery_ids'])&&is_array($data['gallery_ids'])): foreach(array_slice($data['gallery_ids'],0,10) as $gallery_id): $gallery_id=absint($gallery_id); $gallery_thumb=wp_get_attachment_image_url($gallery_id,'thumbnail'); if(!$gallery_thumb)continue; ?><div class="avam-gallery-item" data-gallery-item><img src="<?php echo esc_url($gallery_thumb); ?>" alt="تصویر ثبت‌شده در گالری" loading="lazy"><input type="hidden" name="gallery_keep[]" value="<?php echo esc_attr($gallery_id); ?>"><button type="button" class="avam-gallery-remove" aria-label="حذف تصویر از گالری">×</button></div><?php endforeach; endif; ?></div><div class="avam-image-preview avam-gallery-preview" data-preview="gallery" hidden></div></div><p class="avam-upload-privacy-note">تصاویر برای ساخت صفحه یادبود استفاده می‌شوند. پیش از بارگذاری، مطمئن شوید حق اشتراک‌گذاری آن‌ها را دارید. توجه: خصوصی‌بودن صفحه، دسترسی مستقیم به URL فایل‌های رسانه‌ای وردپرس را لزوماً مسدود نمی‌کند.</p></div><div class="avam-field"><label for="avam-memorial-visibility">حریم خصوصی</label><select id="avam-memorial-visibility" name="visibility"><option value="public" <?php selected($data['visibility']??'public','public'); ?>>عمومی — در جستجو نمایش داده شود</option><?php if($avam_private_enabled || (($data['visibility']??'public')==='private')): ?><option value="private" <?php selected($data['visibility']??'public','private'); ?>>خصوصی — فقط مالک و مدیر سایت</option><?php endif; ?></select></div></div>
      <div class="avam-form-submit"><a class="avam-create-back" href="<?php echo esc_url(avam_account_url()); ?>">انصراف</a><button class="avam-dash-primary" type="button" data-save-status="draft">ذخیره پیش‌نویس</button><button class="avam-dash-primary" type="button" data-save-status="publish"><?php echo $editing?'ذخیره تغییرات':'انتشار یادبود'; ?><span>←</span></button></div>
    </form>
  </div>
  <?php return ob_get_clean();
 }

 public static function search(){
  $avam_cc=get_option('avam_control_center',[]); if(!is_array($avam_cc))$avam_cc=[];
  $q=isset($_GET['q'])&&is_string($_GET['q'])?sanitize_text_field(wp_unslash($_GET['q'])):'';
  $city_filter_enabled=($avam_cc['archive_city_filter']??'1')==='1';
  $sort_enabled=($avam_cc['archive_sort']??'1')==='1';
  $city=$city_filter_enabled&&isset($_GET['city'])&&is_string($_GET['city'])?sanitize_text_field(wp_unslash($_GET['city'])):'';
  $default_sort=sanitize_key($avam_cc['archive_default_sort']??'newest');
  $sort=$sort_enabled&&isset($_GET['sort'])&&is_string($_GET['sort'])?sanitize_key(wp_unslash($_GET['sort'])):$default_sort;
  $sorts=['newest'=>['date','DESC'],'oldest'=>['date','ASC'],'name'=>['title','ASC']];[$orderby,$order]=$sorts[$sort]??$sorts['newest'];
  $per_page=min(60,max(1,absint($avam_cc['archive_per_page']??12)));
  $args=['post_type'=>self::CPT,'post_status'=>'publish','posts_per_page'=>$per_page,'paged'=>max(1,get_query_var('paged')),'orderby'=>$orderby,'order'=>$order,'meta_query'=>['relation'=>'AND',['relation'=>'OR',['key'=>'avam_visibility','compare'=>'NOT EXISTS'],['key'=>'avam_visibility','value'=>'private','compare'=>'!=']]]];
  if($q) $args['s']=$q;
  if($city) $args['meta_query'][]=['key'=>'avam_city','value'=>$city,'compare'=>'LIKE'];
  $query=new WP_Query($args);
  ob_start();?><section class="avam-search-page"><div class="avam-search-hero"><span>آرامگاه مجازی</span><h1><?php echo esc_html($avam_cc['search_title']??'جستجوی یادبودها'); ?></h1><p><?php echo esc_html($avam_cc['search_intro']??'نام متوفی یا شهر را جستجو کنید.'); ?></p><form class="avam-search-form" method="get" action="<?php echo esc_url(avam_memorials_url()); ?>"><label><span>نام متوفی یا واژه‌ای از روایت</span><input type="search" name="q" value="<?php echo esc_attr($q); ?>" placeholder="نام یا عبارت موردنظر را بنویسید"></label><?php if($city_filter_enabled): ?><label><span>شهر</span><input name="city" value="<?php echo esc_attr($city); ?>" placeholder="فیلتر بر اساس شهر"></label><?php endif; ?><?php if($sort_enabled): ?><label><span>مرتب‌سازی</span><select name="sort"><option value="newest" <?php selected($sort,'newest'); ?>>جدیدترین</option><option value="oldest" <?php selected($sort,'oldest'); ?>>قدیمی‌ترین</option><option value="name" <?php selected($sort,'name'); ?>>نام</option></select></label><?php endif; ?><button type="submit">اعمال فیلترها</button></form></div><div class="avam-results"><?php if($query->have_posts()):while($query->have_posts()):$query->the_post();$cid=get_post_meta(get_the_ID(),'avam_city',true);?><a class="avam-result" href="<?php the_permalink();?>"><?php if(has_post_thumbnail()):?><img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(),'medium'));?>" alt="" loading="<?php echo (($avam_cc['lazy_images']??'1')==='1')?'lazy':'eager'; ?>"><?php endif;?><div><h2><?php the_title();?></h2><?php if($cid):?><span><?php echo esc_html($cid);?></span><?php endif;?></div></a><?php endwhile; echo '<nav class="avam-archive-pagination">'.wp_kses_post(paginate_links(['total'=>$query->max_num_pages,'current'=>max(1,get_query_var('paged')),'type'=>'list'])).'</nav>'; else:?><div class="avam-no-results">یادبودی با این مشخصات پیدا نشد.</div><?php endif;wp_reset_postdata();?></div></section><?php return ob_get_clean();
 }

 public static function handle_auth(){
  if(($_SERVER['REQUEST_METHOD']??'')!=='POST')return;
  $action_raw=$_POST['avam_action']??'';$nonce_raw=$_POST['avam_nonce']??'';
  if(!is_string($action_raw)||!is_string($nonce_raw)||$action_raw===''||$nonce_raw==='')return;
  $nonce=sanitize_text_field(wp_unslash($nonce_raw));$action=sanitize_key(wp_unslash($action_raw));
  if($action==='login'&&wp_verify_nonce($nonce,'avam_login')){
   $redirect_raw=$_POST['redirect_to']??'';$redirect=is_string($redirect_raw)?wp_validate_redirect(wp_unslash($redirect_raw),avam_account_url()):avam_account_url();
   $login_raw=$_POST['log']??'';$password_raw=$_POST['pwd']??'';
   if(!is_string($login_raw)||!is_string($password_raw)){wp_safe_redirect(add_query_arg('avam_error',rawurlencode('اطلاعات ورود معتبر نیست.'),avam_login_url()));exit;}
   $login=sanitize_text_field(wp_unslash($login_raw));$password=wp_unslash($password_raw);
   if(is_email($login)){$email_user=get_user_by('email',$login);if($email_user)$login=$email_user->user_login;}
   $ip=isset($_SERVER['REMOTE_ADDR'])&&is_string($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'unknown';
   $login_key='avam_login_rate_'.hash('sha256',strtolower($login).'|'.$ip);
   $ip_key='avam_login_ip_rate_'.hash('sha256',$ip);
   $attempts=(int)get_transient($login_key); $ip_attempts=(int)get_transient($ip_key);
   if($attempts>=10 || $ip_attempts>=50){wp_safe_redirect(add_query_arg('avam_error',rawurlencode('نام کاربری یا رمز عبور نادرست است.'),avam_login_url()));exit;}
   $u=wp_signon(['user_login'=>$login,'user_password'=>$password,'remember'=>true],is_ssl());
   if(is_wp_error($u)){
    set_transient($login_key,min(10,$attempts+1),15*MINUTE_IN_SECONDS);
    set_transient($ip_key,min(50,$ip_attempts+1),15*MINUTE_IN_SECONDS);
    wp_safe_redirect(add_query_arg('avam_error',rawurlencode('نام کاربری یا رمز عبور نادرست است.'),avam_login_url()));exit;
   }
   delete_transient($login_key);
   wp_safe_redirect($redirect);exit;
  }
  if($action==='register'&&wp_verify_nonce($nonce,'avam_register')){
   $avam_cc=get_option('avam_control_center',[]);
   if(is_array($avam_cc) && (($avam_cc['registration_enabled']??'1')!=='1' || ($avam_cc['public_registration']??'1')!=='1')){wp_safe_redirect(add_query_arg('avam_error',rawurlencode('ثبت‌نام کاربران در حال حاضر غیرفعال است.'),avam_register_url()));exit;}
   $email_raw=$_POST['email']??'';$name_raw=$_POST['display_name']??'';$pass_raw=$_POST['password']??'';
   if(!is_string($email_raw)||!is_string($name_raw)||!is_string($pass_raw)){wp_safe_redirect(add_query_arg('avam_error',rawurlencode('اطلاعات ثبت‌نام معتبر نیست.'),avam_register_url()));exit;}
   $email=sanitize_email(wp_unslash($email_raw));$name=sanitize_text_field(wp_unslash($name_raw));$pass=wp_unslash($pass_raw);
   if(!$name||mb_strlen($name)>80||!is_email($email)||email_exists($email)||strlen($pass)<10||strlen($pass)>4096){wp_safe_redirect(add_query_arg('avam_error',rawurlencode('نام، ایمیل یا رمز عبور معتبر نیست؛ رمز باید حداقل ۱۰ نویسه داشته باشد.'),avam_register_url()));exit;}
   $ip=isset($_SERVER['REMOTE_ADDR'])&&is_string($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'unknown';
   $register_rate_key='avam_register_rate_'.hash('sha256',$ip);
   $register_count=(int)get_transient($register_rate_key);
   if($register_count>=10){wp_safe_redirect(add_query_arg('avam_error',rawurlencode('تعداد ثبت‌نام از این شبکه بیش از حد مجاز است؛ کمی بعد دوباره تلاش کنید.'),avam_register_url()));exit;}
   $base=sanitize_user(strtok($email,'@'));$login=$base;$i=1;while(username_exists($login))$login=$base.$i++;
   $uid=wp_create_user($login,$pass,$email);if(is_wp_error($uid)){wp_safe_redirect(avam_register_url());exit;}
   set_transient($register_rate_key,$register_count+1,HOUR_IN_SECONDS);
   wp_update_user(['ID'=>$uid,'display_name'=>$name]);wp_set_auth_cookie($uid,true);
   $redirect_raw=$_POST['redirect_to']??'';$redirect=is_string($redirect_raw)?wp_validate_redirect(wp_unslash($redirect_raw),avam_account_url()):avam_account_url();
   wp_safe_redirect($redirect);exit;
  }
 }

 public static function save_front_memorial(){
  $content_length=isset($_SERVER['CONTENT_LENGTH'])&&is_string($_SERVER['CONTENT_LENGTH'])?(int)$_SERVER['CONTENT_LENGTH']:0;
  $post_max_size=wp_convert_hr_to_bytes((string)ini_get('post_max_size'));
  if($content_length>0 && $post_max_size>0 && $content_length>$post_max_size && empty($_POST))wp_send_json_error(['message'=>'حجم مجموع فایل‌های ارسالی از محدودیت هاست بیشتر است. حجم فایل‌ها را کاهش دهید یا post_max_size را در تنظیمات هاست افزایش دهید.'],413);
  $avam_cc=get_option('avam_control_center',[]); if(is_array($avam_cc) && ($avam_cc['memorial_creation_enabled']??'1')!=='1' && !current_user_can('manage_options')) wp_send_json_error(['message'=>'ساخت یادبود در حال حاضر غیرفعال است.'],403);
  $nonce_raw=$_POST['nonce']??'';
  if(!is_user_logged_in() || !is_string($nonce_raw) || !wp_verify_nonce(sanitize_text_field(wp_unslash($nonce_raw)),'avam_front'))wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  foreach(['title','content','save_status','visibility','city','birth','death','timeline'] as $field){
   if(isset($_POST[$field]) && !is_string($_POST[$field]))wp_send_json_error(['message'=>'یکی از فیلدهای فرم معتبر نیست.'],422);
  }
  $title=sanitize_text_field(wp_unslash($_POST['title']??''));if(!$title)wp_send_json_error(['message'=>'نام الزامی است.'],422);
  $edit_id=absint($_POST['edit_id']??0);$post_content=sanitize_textarea_field(wp_unslash($_POST['content']??''));
  $save_status=sanitize_key(wp_unslash($_POST['save_status']??'publish')); $save_status=$save_status==='draft'?'draft':'publish';
  $visibility=sanitize_key(wp_unslash($_POST['visibility']??'public'));
  if(!in_array($visibility,['public','private'],true))$visibility='public';
  $existing_private=$edit_id>0 && get_post_meta($edit_id,'avam_visibility',true)==='private';
  if($visibility==='private' && is_array($avam_cc) && ($avam_cc['private_memorials_enabled']??'1')!=='1' && !$existing_private && !current_user_can('manage_options'))wp_send_json_error(['message'=>'ساخت یادبود خصوصی در حال حاضر غیرفعال است.'],403);
  if(mb_strlen($title)>160)wp_send_json_error(['message'=>'نام یادبود بیش از حد طولانی است.'],422);
  if(mb_strlen($post_content)>50000)wp_send_json_error(['message'=>'متن روایت بیش از حد طولانی است.'],422);
  if($save_status==='publish' && trim($post_content)==='')wp_send_json_error(['message'=>'برای انتشار، روایت زندگی را وارد کنید یا پیش‌نویس ذخیره کنید.'],422);
  $timeline_check=sanitize_textarea_field(wp_unslash($_POST['timeline']??'')); if(mb_strlen($timeline_check)>12000)wp_send_json_error(['message'=>'خط زمانی بیش از حد طولانی است.'],422);
  if(!empty($_FILES['image']['name'])){
   if(!is_array($_FILES['image']) || !isset($_FILES['image']['name'],$_FILES['image']['tmp_name']) || !is_string($_FILES['image']['name']) || !is_string($_FILES['image']['tmp_name']) || $_FILES['image']['tmp_name']==='')wp_send_json_error(['message'=>'فایل تصویر معتبر نیست.'],422);
   if(!empty($_FILES['image']['error']) && (int)$_FILES['image']['error']!==UPLOAD_ERR_OK)wp_send_json_error(['message'=>'آپلود تصویر ناموفق بود.'],422);
   if((int)($_FILES['image']['size']??0)>8*1024*1024)wp_send_json_error(['message'=>'حجم تصویر باید کمتر از ۸ مگابایت باشد.'],422);
   $check=wp_check_filetype_and_ext($_FILES['image']['tmp_name'],$_FILES['image']['name']); if(empty($check['type'])||!in_array($check['type'],['image/jpeg','image/png','image/webp'],true))wp_send_json_error(['message'=>'فقط تصویر JPG، PNG یا WebP مجاز است.'],422);
  }
  if(!empty($_FILES['gallery']['name']) && is_array($_FILES['gallery']['name'])){
   $new_gallery_count=count(array_filter($_FILES['gallery']['name']));$existing_gallery=get_post_meta(absint($_POST['edit_id']??0),'avam_gallery_ids',true);if(!is_array($existing_gallery))$existing_gallery=[];$existing_gallery=array_values(array_filter(array_map('absint',$existing_gallery),'wp_attachment_is_image'));$keep_gallery=$existing_gallery;if(!empty($_POST['gallery_management'])){$requested_keep=isset($_POST['gallery_keep'])&&is_array($_POST['gallery_keep'])?array_map('absint',wp_unslash($_POST['gallery_keep'])):[];$keep_gallery=array_values(array_intersect($existing_gallery,$requested_keep));}if(count($keep_gallery)+$new_gallery_count>10)wp_send_json_error(['message'=>'گالری می‌تواند در مجموع حداکثر ۱۰ تصویر داشته باشد. تعداد تصاویر جدید را کاهش دهید.'],422);
   foreach($_FILES['gallery']['name'] as $i=>$name){if(!$name)continue; if(!is_string($name) || !isset($_FILES['gallery']['tmp_name'][$i]) || !is_string($_FILES['gallery']['tmp_name'][$i]) || $_FILES['gallery']['tmp_name'][$i]==='')wp_send_json_error(['message'=>'یکی از فایل‌های گالری معتبر نیست.'],422); if((int)($_FILES['gallery']['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)wp_send_json_error(['message'=>'آپلود یکی از تصاویر گالری ناموفق بود.'],422); if((int)($_FILES['gallery']['size'][$i]??0)>8*1024*1024)wp_send_json_error(['message'=>'حجم هر تصویر گالری باید کمتر از ۸ مگابایت باشد.'],422); $check=wp_check_filetype_and_ext($_FILES['gallery']['tmp_name'][$i],$name); if(empty($check['type'])||!in_array($check['type'],['image/jpeg','image/png','image/webp'],true))wp_send_json_error(['message'=>'گالری فقط تصویر JPG، PNG یا WebP می‌پذیرد.'],422); }
  }
  if($edit_id){$existing=get_post($edit_id);if(!$existing||$existing->post_type!==self::CPT||(int)$existing->post_author!==get_current_user_id())wp_send_json_error(['message'=>'دسترسی به این یادبود مجاز نیست.'],403);$id=wp_update_post(['ID'=>$edit_id,'post_title'=>$title,'post_content'=>$post_content,'post_status'=>$save_status],true);}else{$id=wp_insert_post(['post_type'=>self::CPT,'post_status'=>$save_status,'post_title'=>$title,'post_content'=>$post_content,'post_author'=>get_current_user_id()],true);}
  if(is_wp_error($id)){
   if(class_exists('AVAM_Logger')) AVAM_Logger::log('error','memorial_save_failed',['post_id'=>$edit_id,'code'=>$id->get_error_code(),'component'=>'memorial']);
   wp_send_json_error(['message'=>'ذخیره انجام نشد.'],500);
  }
  foreach(['city','birth','death'] as $k)update_post_meta($id,'avam_'.$k,sanitize_textarea_field(wp_unslash($_POST[$k]??'')));
  $timeline=$timeline_check; update_post_meta($id,'avam_timeline',$timeline);
  update_post_meta($id,'avam_visibility',$visibility);
  if(!empty($_FILES['image']['name'])){
      require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';
   $att=media_handle_upload('image',$id);
   if(is_wp_error($att)){
    if(class_exists('AVAM_Logger')) AVAM_Logger::log('error','featured_image_upload_failed',['post_id'=>$id,'code'=>$att->get_error_code(),'component'=>'media']);
    wp_send_json_error(['message'=>'تصویر قابل ذخیره‌سازی نبود.'],422);
   }
   set_post_thumbnail($id,$att);
  }
  if(!isset($keep_gallery)){$existing_gallery=get_post_meta($id,'avam_gallery_ids',true);if(!is_array($existing_gallery))$existing_gallery=[];$existing_gallery=array_values(array_filter(array_map('absint',$existing_gallery),'wp_attachment_is_image'));$keep_gallery=$existing_gallery;if(!empty($_POST['gallery_management'])){$requested_keep=isset($_POST['gallery_keep'])&&is_array($_POST['gallery_keep'])?array_map('absint',wp_unslash($_POST['gallery_keep'])):[];$keep_gallery=array_values(array_intersect($existing_gallery,$requested_keep));}}
  $gallery_ids=[];
  if(!empty($_FILES['gallery']['name']) && is_array($_FILES['gallery']['name'])){ $total_files=count($_FILES['gallery']['name']);
   require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';
   for($i=0;$i<$total_files;$i++){
    if(empty($_FILES['gallery']['name'][$i]))continue;
    $single=['name'=>$_FILES['gallery']['name'][$i],'type'=>$_FILES['gallery']['type'][$i],'tmp_name'=>$_FILES['gallery']['tmp_name'][$i],'error'=>$_FILES['gallery']['error'][$i],'size'=>$_FILES['gallery']['size'][$i]];
    $_FILES['avam_gallery_single']=$single; $gallery_att=media_handle_upload('avam_gallery_single',$id); unset($_FILES['avam_gallery_single']);
    if(is_wp_error($gallery_att)){
     if(class_exists('AVAM_Logger')) AVAM_Logger::log('error','gallery_upload_failed',['post_id'=>$id,'code'=>$gallery_att->get_error_code(),'component'=>'media']);
     wp_send_json_error(['message'=>'یکی از تصاویر گالری ذخیره نشد.'],422);
    }
    $gallery_ids[]=(int)$gallery_att;
   }
  }
  if($gallery_ids || !empty($_POST['gallery_management'])){$final_gallery=array_values(array_unique(array_merge($keep_gallery,$gallery_ids)));update_post_meta($id,'avam_gallery_ids',array_slice($final_gallery,0,10));}
  $url=$save_status==='draft'?get_preview_post_link($id):get_permalink($id);
  wp_send_json_success(['url'=>$url?:get_permalink($id),'id'=>(int)$id,'status'=>$save_status,'message'=>$save_status==='draft'?'پیش‌نویس ذخیره شد.':'یادبود منتشر شد.']);
 }

 public static function log_client_error(){
  if(!class_exists('AVAM_Logger')||!AVAM_Logger::enabled())wp_send_json_error(['message'=>'ثبت گزارش غیرفعال است.'],403);
  if(!isset($_POST['nonce'])||!is_string($_POST['nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])),'avam_client_log'))wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  $kind_raw=$_POST['kind']??'';
  if(!is_string($kind_raw))wp_send_json_error(['message'=>'نوع رویداد نامعتبر است.'],422);
  $kind=sanitize_key(wp_unslash($kind_raw));
  $allowed=['javascript_error','unhandled_promise_rejection','resource_load_error'];
  if(!in_array($kind,$allowed,true))wp_send_json_error(['message'=>'نوع رویداد نامعتبر است.'],422);
  $ip=isset($_SERVER['REMOTE_ADDR'])?(string)$_SERVER['REMOTE_ADDR']:'unknown';
  $rate_key='avam_client_log_rate_'.hash('sha256',$ip);
  $count=(int)get_transient($rate_key);
  $global_count=(int)get_transient('avam_client_log_global_rate');
  if($count>=20||$global_count>=300)wp_send_json_error(['message'=>'محدودیت ثبت گزارش اعمال شد.'],429);
  set_transient($rate_key,$count+1,MINUTE_IN_SECONDS);
  set_transient('avam_client_log_global_rate',$global_count+1,MINUTE_IN_SECONDS);
  $component_raw=$_POST['component']??'';
  $file=is_string($component_raw)?substr(sanitize_file_name(wp_unslash($component_raw)),0,100):'';
  $line=absint($_POST['line']??0);
  AVAM_Logger::log('error','client_'.$kind,['file'=>$file,'component'=>'browser','line'=>$line]);
  wp_send_json_success(['logged'=>true]);
 }

 public static function delete_front_memorial(){
  $nonce_raw=$_POST['nonce']??'';
  if(!is_user_logged_in() || !is_string($nonce_raw) || !wp_verify_nonce(sanitize_text_field(wp_unslash($nonce_raw)),'avam_front'))wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  $id=absint($_POST['id']??0);$p=get_post($id);if(!$p||$p->post_type!==self::CPT||(int)$p->post_author!==get_current_user_id())wp_send_json_error(['message'=>'دسترسی مجاز نیست.'],403);
  $deleted=wp_delete_post($id,true);
  if(!$deleted){if(class_exists('AVAM_Logger')) AVAM_Logger::log('error','memorial_delete_failed',['post_id'=>$id,'component'=>'memorial']);wp_send_json_error(['message'=>'حذف یادبود انجام نشد. لطفاً دوباره تلاش کنید.'],500);}
  wp_send_json_success(['url'=>avam_account_url()]);
 }

 public static function meta_boxes(){
  add_meta_box('avam_details','جزئیات یادبود',function($post){wp_nonce_field('avam_meta','avam_meta_nonce');foreach(['city'=>'شهر','birth'=>'تولد','death'=>'درگذشت'] as $k=>$label){$v=get_post_meta($post->ID,'avam_'.$k,true);echo '<p><label for="avam-meta-'.$k.'">'.esc_html($label).'</label><input id="avam-meta-'.$k.'" style="width:100%" name="avam_'.$k.'" value="'.esc_attr($v).'"></p>';}$visibility=get_post_meta($post->ID,'avam_visibility',true)?:'public';echo '<p><label for="avam_visibility"><strong>حریم خصوصی</strong></label><select id="avam_visibility" name="avam_visibility" style="width:100%"><option value="public" '.selected($visibility,'public',false).'>عمومی — قابل نمایش در جستجو</option><option value="private" '.selected($visibility,'private',false).'>خصوصی — فقط مالک و مدیر</option></select></p>';},self::CPT,'normal','high');
 }

 public static function save_meta($post_id){
  if(!isset($_POST['avam_meta_nonce']) || !is_string($_POST['avam_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['avam_meta_nonce'])),'avam_meta'))return;
  if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)return;if(!current_user_can('edit_post',$post_id))return;
  foreach(['city','birth','death'] as $k)if(isset($_POST['avam_'.$k]) && is_string($_POST['avam_'.$k]))update_post_meta($post_id,'avam_'.$k,sanitize_textarea_field(wp_unslash($_POST['avam_'.$k])));if(isset($_POST['avam_visibility']) && is_string($_POST['avam_visibility'])){$visibility=sanitize_key(wp_unslash($_POST['avam_visibility']));update_post_meta($post_id,'avam_visibility',in_array($visibility,['public','private'],true)?$visibility:'public');}
 }

 public static function protect_admin(){if(is_admin()&&!current_user_can('manage_options')&&!wp_doing_ajax()){wp_safe_redirect(home_url('/'));exit;}}
 public static function hide_admin_bar(){
  $settings=get_option('avam_control_center',[]);
  if(!current_user_can('manage_options') || (is_array($settings) && ($settings['admin_bar_frontend']??'1')!=='1')) show_admin_bar(false);
 }

 public static function admin_menu(){add_options_page('مرکز کنترل آرامگاه مجازی','آرامگاه مجازی','manage_options','avam-settings',['AVAM_Admin_Center','render']);}

 public static function settings(){
  $text_fields=[
   'avam_site_message','avam_home_eyebrow','avam_home_title','avam_home_subtitle',
   'avam_home_panel_title','avam_home_panel_text','avam_home_cta','avam_footer_text',
   'avam_single_kicker','avam_single_intro','avam_search_title','avam_search_intro'
  ];
  foreach($text_fields as $field) register_setting('avam_settings',$field,['sanitize_callback'=>'sanitize_textarea_field']);
  register_setting('avam_settings','avam_home_media_type',['sanitize_callback'=>'sanitize_key']);
  register_setting('avam_settings','avam_home_image',['sanitize_callback'=>'absint']);
  register_setting('avam_settings','avam_home_video',['sanitize_callback'=>'absint']);
  register_setting('avam_settings','avam_accent_color',['sanitize_callback'=>'sanitize_hex_color']);
  register_setting('avam_settings','avam_primary_color',['sanitize_callback'=>'sanitize_hex_color']);
  register_setting('avam_settings','avam_show_stats',['sanitize_callback'=>function($v){return $v?'1':'0';}]);
  register_setting('avam_settings','avam_show_home_panel',['sanitize_callback'=>function($v){return $v?'1':'0';}]);
  register_setting('avam_settings','avam_show_search',['sanitize_callback'=>function($v){return $v?'1':'0';}]);

  add_settings_section('avam_general','هویت و متن‌های عمومی',function(){echo '<p>متن‌های اصلی سایت را از اینجا مدیریت کنید. ساختار و عملکرد صفحات ثابت می‌ماند.</p>';},'avam-settings');
  foreach([
   ['avam_site_message','پیام پایانی سایت'],['avam_footer_text','متن فوتر'],['avam_search_title','عنوان جستجو'],['avam_search_intro','توضیح جستجو'],
   ['avam_single_kicker','برچسب بالای صفحه یادبود'],['avam_single_intro','متن معرفی صفحه یادبود']
  ] as $f) add_settings_field($f[0],$f[1],[__CLASS__,'text_setting_field'],'avam-settings','avam_general',['field'=>$f[0]]);

  add_settings_section('avam_home','صفحه اول — محتوای Hero',function(){echo '<p>عنوان، توضیح و اجزای اصلی Hero را کنترل کنید.</p>';},'avam-settings');
  foreach([
   ['avam_home_eyebrow','متن بالای عنوان'],['avam_home_title','عنوان اصلی'],['avam_home_subtitle','زیرعنوان'],['avam_home_panel_title','عنوان پنل شیشه‌ای'],['avam_home_panel_text','متن پنل'],['avam_home_cta','متن دکمه اصلی']
  ] as $f) add_settings_field($f[0],$f[1],[__CLASS__,'text_setting_field'],'avam-settings','avam_home',['field'=>$f[0]]);
  add_settings_field('avam_show_stats','نمایش آمار صفحه اول',[__CLASS__,'checkbox_field'],'avam-settings','avam_home',['field'=>'avam_show_stats']);
  add_settings_field('avam_show_home_panel','نمایش پنل معرفی',[__CLASS__,'checkbox_field'],'avam-settings','avam_home',['field'=>'avam_show_home_panel']);
  add_settings_field('avam_show_search','نمایش جستجوی Hero',[__CLASS__,'checkbox_field'],'avam-settings','avam_home',['field'=>'avam_show_search']);

  add_settings_section('avam_media','رسانه و ظاهر',function(){echo '<p>رسانه و رنگ‌های اصلی رابط را مدیریت کنید.</p>';},'avam-settings');
  add_settings_field('avam_home_media_type','نوع پس‌زمینه',[__CLASS__,'media_type_field'],'avam-settings','avam_media');
  add_settings_field('avam_home_image','تصویر صفحه اصلی',[__CLASS__,'image_field'],'avam-settings','avam_media');
  add_settings_field('avam_home_video','ویدیوی لوپ صفحه اصلی',[__CLASS__,'video_field'],'avam-settings','avam_media');
  add_settings_field('avam_primary_color','رنگ اصلی',[__CLASS__,'color_field'],'avam-settings','avam_media',['field'=>'avam_primary_color','default'=>'#17253d']);
  add_settings_field('avam_accent_color','رنگ Accent',[__CLASS__,'color_field'],'avam-settings','avam_media',['field'=>'avam_accent_color','default'=>'#a76652']);
 }
 public static function media_type_field(){
  $v=get_option('avam_home_media_type','video');
  echo '<select name="avam_home_media_type"><option value="video" '.selected($v,'video',false).'>ویدیو</option><option value="image" '.selected($v,'image',false).'>تصویر</option></select><p class="description">در حالت ویدیو، ویدیوی لوپ نمایش داده می‌شود و تصویر به‌عنوان پوستر/جایگزین استفاده خواهد شد.</p>';
 }
 public static function image_field(){
  $id=absint(get_option('avam_home_image',0));$url=$id?wp_get_attachment_url($id):'';
  echo '<input type="hidden" id="avam_home_image" name="avam_home_image" value="'.esc_attr($id).'"><input id="avam_home_image-url" class="regular-text" type="text" value="'.esc_attr($url).'" readonly> <button class="button avam-media-pick" data-field="avam_home_image" data-type="image">انتخاب تصویر</button>';
 }
 public static function video_field(){
  $id=absint(get_option('avam_home_video',0));$url=$id?wp_get_attachment_url($id):'';
  echo '<input type="hidden" id="avam_home_video" name="avam_home_video" value="'.esc_attr($id).'"><input id="avam_home_video-url" class="regular-text" type="text" value="'.esc_attr($url).'" readonly> <button class="button avam-media-pick" data-field="avam_home_video" data-type="video">انتخاب ویدیو</button>';
 }
 public static function text_setting_field($args){$field=$args['field'];$defaults=[
  'avam_home_eyebrow'=>'جایی برای نام، تصویر و روایت','avam_home_title'=>'یادها اینجا می‌مانند.','avam_home_subtitle'=>'برای کسانی که نمی‌خواهیم از یاد بروند.',
  'avam_home_panel_title'=>'یادبودهای دیجیتال','avam_home_panel_text'=>"نام، تصویر و روایت\nبا احترام نگه‌داری می‌شود.",'avam_home_cta'=>'ساخت یادبود',
  'avam_footer_text'=>'آرامگاه مجازی — جایی برای نگه‌داشتن یک روایت با احترام.','avam_site_message'=>'هر نام، یک روایت است.',
  'avam_single_kicker'=>'صفحه یادبود','avam_single_intro'=>'روایتی برای ماندن و به یاد آوردن.',
  'avam_search_title'=>'جستجوی یادبودها','avam_search_intro'=>'نام متوفی یا شهر را جستجو کنید.'
 ];$v=get_option($field,$defaults[$field]??'');echo '<textarea name="'.esc_attr($field).'" rows="3" class="large-text" style="max-width:760px">'.esc_textarea($v).'</textarea>'; }
 public static function checkbox_field($args){$field=$args['field'];$v=get_option($field,'1');echo '<label><input type="checkbox" name="'.esc_attr($field).'" value="1" '.checked($v,'1',false).'> فعال باشد</label>'; }
 public static function color_field($args){$field=$args['field'];$v=get_option($field,$args['default']??'#17253d');echo '<input type="color" name="'.esc_attr($field).'" value="'.esc_attr($v).'"> <code>'.esc_html($v).'</code>'; }
 public static function settings_page(){
  if(!current_user_can('manage_options'))return;
  echo '<div class="wrap avam-admin-settings" dir="rtl"><h1>تنظیمات آرامگاه مجازی</h1><p>مرکز مدیریت تجربه سایت: صفحه اول، جستجو، صفحه یادبود، رسانه و ظاهر.</p><form method="post" action="options.php">';settings_fields('avam_settings');do_settings_sections('avam-settings');submit_button('ذخیره همه تنظیمات');echo '</form></div>';
 }
}

function avam_account_url(){ $s=get_option('avam_control_center',[]); $id=is_array($s)?absint($s['account_page']??0):0; return $id&&get_post_type($id)==='page'?get_permalink($id):home_url('/account/'); }
function avam_login_url(){ $s=get_option('avam_control_center',[]); $id=is_array($s)?absint($s['login_page']??0):0; return $id&&get_post_type($id)==='page'?get_permalink($id):home_url('/login/'); }
function avam_register_url(){ $s=get_option('avam_control_center',[]); $id=is_array($s)?absint($s['register_page']??0):0; return $id&&get_post_type($id)==='page'?get_permalink($id):home_url('/register/'); }
function avam_create_url(){ $s=get_option('avam_control_center',[]); $id=is_array($s)?absint($s['create_page']??0):0; return $id&&get_post_type($id)==='page'?get_permalink($id):home_url('/create-memorial/'); }
function avam_memorials_url(){ $s=get_option('avam_control_center',[]); $id=is_array($s)?absint($s['memorials_page']??0):0; if($id&&get_post_type($id)==='page')return get_permalink($id); $url=get_post_type_archive_link('avam_memorial'); return $url ? $url : home_url('/memorials/'); }

register_activation_hook(__FILE__,function(){
 AVAM_Core::register_cpt();
 foreach([['login','ورود','[avam_login]'],['register','ثبت‌نام','[avam_register]'],['account','حساب من','[avam_account]'],['create-memorial','ساخت یادبود','[avam_create_memorial]'],['memorials','جستجوی یادبودها','[avam_memorial_search]']] as $p){
  if(!get_page_by_path($p[0]))wp_insert_post(['post_title'=>$p[1],'post_name'=>$p[0],'post_content'=>$p[2],'post_status'=>'publish','post_type'=>'page']);
 }
 flush_rewrite_rules();
});
register_deactivation_hook(__FILE__,function(){wp_clear_scheduled_hook('avam_daily_anniversary_check');flush_rewrite_rules();});
require_once __DIR__.'/includes/logger.php';
require_once __DIR__.'/includes/admin-center.php';
AVAM_Core::init();
add_action('init',function(){
 $visual_version=get_option('avam_visual_version','0');
 if(version_compare($visual_version,'2.1.0','<')){
  if(!get_option('avam_primary_color') || get_option('avam_primary_color')==='#17253d') update_option('avam_primary_color','#2c3531');
  if(!get_option('avam_accent_color') || get_option('avam_accent_color')==='#a76652') update_option('avam_accent_color','#ad875c');
  update_option('avam_visual_version','2.1.0');
 }
},98);
add_action('init',function(){
 if(get_option('avam_rewrite_version')!=='1.5.0'){
  $legacy=get_page_by_path('memorials');
  if($legacy && $legacy->post_type==='page' && trim($legacy->post_content)==='[avam_memorial_search]') wp_delete_post($legacy->ID,true);
  flush_rewrite_rules(false);
  update_option('avam_rewrite_version','1.5.0');
 }
},99);
require_once __DIR__.'/includes/features.php';
