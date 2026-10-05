<?php
/**
 * Plugin Name: آرامگاه مجازی — Core
 * Description: Core memorial content, authentication, search, front-end account/create flows and administrator settings.
 * Version: 1.1.0
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
  add_action('wp_ajax_nopriv_avam_save_memorial',[__CLASS__,'save_front_memorial']);
  add_action('wp_ajax_avam_delete_memorial',[__CLASS__,'delete_front_memorial']);
 }

 public static function register_cpt(){
  register_post_type(self::CPT,[
   'labels'=>['name'=>'یادبودها','singular_name'=>'یادبود','add_new'=>'یادبود جدید','add_new_item'=>'افزودن یادبود','edit_item'=>'ویرایش یادبود','menu_name'=>'یادبودها'],
   'public'=>true,'show_in_rest'=>true,'has_archive'=>false,'rewrite'=>['slug'=>'memorial','with_front'=>false],
   'supports'=>['title','editor','thumbnail','author'],'menu_icon'=>'dashicons-heart','capability_type'=>'post','map_meta_cap'=>true
  ]);
 }

 public static function assets(){
  wp_enqueue_style('avam-plugin',plugins_url('assets/css/core.css',__FILE__),[],'1.1.0');
  wp_enqueue_script('avam-plugin',plugins_url('assets/js/core.js',__FILE__),['jquery'],'1.1.0',true);
  wp_localize_script('avam-plugin','AVAM',['ajax'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('avam_front'),'account'=>avam_account_url()]);
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
  if(is_user_logged_in()) return '<div class="avam-card"><p>شما وارد شده‌اید.</p><a class="avam-btn" href="'.esc_url(avam_account_url()).'">حساب من</a></div>';
  $err=isset($_GET['avam_error'])?sanitize_text_field(wp_unslash($_GET['avam_error'])):'';
  ob_start(); ?><div class="avam-card avam-auth-card"><h1>ورود به آرامگاه</h1><p class="avam-auth-lead">برای مدیریت یادبودها و روایت‌های شما.</p><?php if($err):?><div class="avam-notice avam-error"><?php echo esc_html($err);?></div><?php endif;?><form class="avam-form" method="post"><div class="avam-field"><label>نام کاربری یا ایمیل</label><input name="log" autocomplete="username" required></div><div class="avam-field"><label>رمز عبور</label><input type="password" name="pwd" autocomplete="current-password" required></div><?php wp_nonce_field('avam_login','avam_nonce');?><input type="hidden" name="avam_action" value="login"><button class="avam-btn avam-auth-btn" type="submit">ورود</button></form><p class="avam-auth-foot">حساب ندارید؟ <a href="<?php echo esc_url(avam_register_url());?>">ساخت حساب</a></p></div><?php return ob_get_clean();
 }

 public static function register(){
  if(is_user_logged_in()) return '<div class="avam-card"><p>حساب شما فعال است.</p><a class="avam-btn" href="'.esc_url(avam_account_url()).'">حساب من</a></div>';
  $err=isset($_GET['avam_error'])?sanitize_text_field(wp_unslash($_GET['avam_error'])):'';
  ob_start(); ?><div class="avam-card avam-auth-card"><h1>ساخت حساب</h1><p class="avam-auth-lead">یک فضای شخصی برای نگه‌داری یادها بسازید.</p><?php if($err):?><div class="avam-notice avam-error"><?php echo esc_html($err);?></div><?php endif;?><form class="avam-form" method="post"><div class="avam-field"><label>نام نمایشی</label><input name="display_name" required></div><div class="avam-field"><label>ایمیل</label><input type="email" name="email" autocomplete="email" required></div><div class="avam-field"><label>رمز عبور</label><input type="password" name="password" minlength="8" autocomplete="new-password" required></div><?php wp_nonce_field('avam_register','avam_nonce');?><input type="hidden" name="avam_action" value="register"><button class="avam-btn avam-auth-btn" type="submit">ایجاد حساب</button></form><p class="avam-auth-foot">حساب دارید؟ <a href="<?php echo esc_url(avam_login_url());?>">ورود</a></p></div><?php return ob_get_clean();
 }

 public static function account(){
  if(!is_user_logged_in()) return '<div class="avam-card"><p>برای مشاهده حساب خود ابتدا وارد شوید.</p><a class="avam-btn" href="'.esc_url(avam_login_url()).'">ورود</a></div>';
  $uid=get_current_user_id();
  $user=wp_get_current_user();
  $posts=get_posts(['post_type'=>self::CPT,'author'=>$uid,'posts_per_page'=>50,'post_status'=>['publish','draft','pending'],'orderby'=>'date','order'=>'DESC']);
  $total=count($posts);$published=0;$drafts=0;
  foreach($posts as $p){if($p->post_status==='publish')$published++;else$drafts++;}
  $recent=array_slice($posts,0,3);
  $create=avam_create_url();$memorials=avam_memorials_url();$logout=wp_logout_url(home_url('/'));
  ob_start(); ?>
  <section class="avam-dashboard" dir="rtl">
    <aside class="avam-dash-sidebar">
      <a class="avam-dash-brand" href="<?php echo esc_url(home_url('/')); ?>">
        <span class="avam-dash-brand-mark">آ</span><span><b>آرامگاه مجازی</b><small>فضای شخصی شما</small></span>
      </a>
      <nav class="avam-dash-nav" aria-label="ناوبری حساب">
        <a class="is-active" href="<?php echo esc_url(avam_account_url()); ?>"><span class="avam-nav-icon">⌂</span>نمای کلی</a>
        <a href="<?php echo esc_url($create); ?>"><span class="avam-nav-icon">＋</span>ساخت یادبود</a>
        <a href="<?php echo esc_url($memorials); ?>"><span class="avam-nav-icon">⌕</span>جستجوی یادبودها</a>
        <a href="<?php echo esc_url(home_url('/')); ?>"><span class="avam-nav-icon">↗</span>مشاهده سایت</a>
      </nav>
      <div class="avam-dash-sidebar-foot">
        <div class="avam-dash-user-mini"><span><?php echo esc_html(mb_substr($user->display_name ?: $user->user_login,0,1)); ?></span><div><strong><?php echo esc_html($user->display_name ?: $user->user_login); ?></strong><small><?php echo esc_html($user->user_email); ?></small></div></div>
        <a class="avam-dash-logout" href="<?php echo esc_url($logout); ?>">خروج از حساب <span>↪</span></a>
      </div>
    </aside>

    <main class="avam-dash-main">
      <header class="avam-dash-topbar">
        <div>
          <span class="avam-dash-kicker">فضای شخصی</span>
          <h1>سلام، <?php echo esc_html($user->display_name ?: $user->user_login); ?></h1>
          <p>اینجا می‌توانید یادبودها را با آرامش مدیریت و روایت هر عزیز را کامل‌تر کنید.</p>
        </div>
        <a class="avam-dash-primary" href="<?php echo esc_url($create); ?>"><span>＋</span> ساخت یادبود جدید</a>
      </header>

      <div class="avam-dash-mobile-nav">
        <a class="is-active" href="<?php echo esc_url(avam_account_url()); ?>">نمای کلی</a>
        <a href="<?php echo esc_url($create); ?>">ساخت یادبود</a>
        <a href="<?php echo esc_url($memorials); ?>">جستجو</a>
      </div>

      <section class="avam-dash-stats" aria-label="آمار حساب">
        <article><span class="avam-stat-icon">♡</span><div><small>همه یادبودها</small><strong><?php echo esc_html($total); ?></strong></div><em>مجموع</em></article>
        <article><span class="avam-stat-icon is-green">✓</span><div><small>منتشر شده</small><strong><?php echo esc_html($published); ?></strong></div><em>قابل مشاهده</em></article>
        <article><span class="avam-stat-icon is-blue">◷</span><div><small>در حال تکمیل</small><strong><?php echo esc_html($drafts); ?></strong></div><em>پیش‌نویس / بررسی</em></article>
      </section>

      <section class="avam-dash-section">
        <div class="avam-section-head"><div><span>مدیریت یادبودها</span><h2>آخرین یادبودها</h2></div><a href="<?php echo esc_url($create); ?>">+ افزودن یادبود</a></div>
        <?php if($recent): ?>
        <div class="avam-dash-memorials">
        <?php foreach($recent as $p):
          $city=get_post_meta($p->ID,'avam_city',true);$death=get_post_meta($p->ID,'avam_death',true);$thumb=get_the_post_thumbnail_url($p->ID,'medium');$status=$p->post_status==='publish'?'منتشر شده':($p->post_status==='draft'?'پیش‌نویس':'در انتظار بررسی');
          $edit=add_query_arg('edit',(int)$p->ID,$create);
        ?>
          <article class="avam-dash-memorial">
            <div class="avam-dash-memorial-photo"><?php if($thumb): ?><img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($p->post_title); ?>"><?php else: ?><span>♡</span><?php endif; ?></div>
            <div class="avam-dash-memorial-body">
              <div class="avam-dash-status <?php echo $p->post_status==='publish'?'is-published':''; ?>"><i></i><?php echo esc_html($status); ?></div>
              <h3><?php echo esc_html($p->post_title); ?></h3>
              <p><?php echo esc_html($city ?: 'شهر ثبت نشده'); ?><?php if($death): ?><span>•</span><?php echo esc_html($death); ?><?php endif; ?></p>
              <small>ایجاد شده در <?php echo esc_html(get_the_date('j F Y',$p)); ?></small>
            </div>
            <div class="avam-dash-memorial-actions">
              <a class="avam-action-view" href="<?php echo esc_url(get_permalink($p)); ?>">مشاهده</a>
              <a class="avam-action-edit" href="<?php echo esc_url($edit); ?>">ویرایش</a>
              <button type="button" class="avam-action-delete" data-id="<?php echo esc_attr($p->ID); ?>">حذف</button>
            </div>
          </article>
        <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="avam-dash-empty"><div class="avam-empty-mark">♡</div><h3>هنوز یادبودی نساخته‌اید</h3><p>اولین یادبود را بسازید و نام، تصویر و روایت عزیزتان را در یک صفحه ماندگار نگه دارید.</p><a class="avam-dash-primary" href="<?php echo esc_url($create); ?>">ساخت اولین یادبود</a></div>
        <?php endif; ?>
      </section>

      <section class="avam-dash-bottom">
        <article class="avam-dash-info-card">
          <div class="avam-info-head"><span class="avam-info-icon">✦</span><div><small>پیشنهاد</small><h3>یادبود را کامل‌تر کنید</h3></div></div>
          <p>تصویر، روایت زندگی، خاطره، نامه و دعا را اضافه کنید تا صفحه یادبود فقط یک نام نباشد؛ یک روایت زنده باشد.</p>
          <a href="<?php echo esc_url($create); ?>">مدیریت یادبودها <span>←</span></a>
        </article>
        <article class="avam-dash-profile">
          <div class="avam-profile-avatar"><?php echo esc_html(mb_substr($user->display_name ?: $user->user_login,0,1)); ?></div>
          <div><small>حساب شما</small><h3><?php echo esc_html($user->display_name ?: $user->user_login); ?></h3><p><?php echo esc_html($user->user_email); ?></p></div>
          <a href="<?php echo esc_url($logout); ?>" aria-label="خروج">↪</a>
        </article>
      </section>
    </main>
  </section>
  <?php return ob_get_clean();
 }

 public static function create(){
  if(!is_user_logged_in()) return '<div class="avam-card"><p>برای ساخت یادبود ابتدا وارد شوید.</p><a class="avam-btn" href="'.esc_url(avam_login_url()).'">ورود</a></div>';
  $edit_id=isset($_GET['edit'])?absint($_GET['edit']):0;$editing=false;$data=[];
  if($edit_id){$p=get_post($edit_id);if($p&&$p->post_type===self::CPT&&(int)$p->post_author===get_current_user_id()){$editing=true;$data=['title'=>$p->post_title,'content'=>$p->post_content];foreach(['city','birth','death','will','letter','memory','prayer'] as $k)$data[$k]=get_post_meta($edit_id,'avam_'.$k,true);}}
  $val=function($k)use($data){return esc_textarea($data[$k]??'');};
  ob_start(); ?>
  <div class="avam-card avam-create-card">
    <div class="avam-create-top"><div><span class="avam-dash-kicker"><?php echo $editing?'ویرایش یادبود':'ساخت یادبود'; ?></span><h1><?php echo $editing?'روایت را کامل‌تر کنید':'صفحه‌ای برای ماندن'; ?></h1><p class="avam-auth-lead"><?php echo $editing?'اطلاعات این یادبود را ویرایش کنید و جزئیات بیشتری به آن اضافه کنید.':'اطلاعاتی که دوست دارید برای همیشه کنار نام او بماند.'; ?></p></div><a class="avam-create-back" href="<?php echo esc_url(avam_account_url()); ?>">← بازگشت به حساب</a></div>
    <div id="avam-create-notice"></div>
    <form id="avam-create-form" class="avam-form avam-memorial-form" enctype="multipart/form-data">
      <input type="hidden" name="edit_id" value="<?php echo esc_attr($editing?$edit_id:0); ?>">
      <div class="avam-form-section"><div class="avam-form-section-title"><span>01</span><div><strong>اطلاعات اصلی</strong><small>مشخصات پایه عزیز شما</small></div></div>
        <div class="avam-form-grid"><div class="avam-field"><label>نام و نام خانوادگی متوفی</label><input name="title" value="<?php echo $val('title'); ?>" required></div><div class="avam-field"><label>شهر</label><input name="city" value="<?php echo $val('city'); ?>" placeholder="مثلاً تهران"></div><div class="avam-field"><label>تاریخ تولد</label><input name="birth" value="<?php echo $val('birth'); ?>" placeholder="مثلاً ۱۳۳۵/۰۳/۱۰"></div><div class="avam-field"><label>تاریخ درگذشت</label><input name="death" value="<?php echo $val('death'); ?>" placeholder="مثلاً ۱۴۰۴/۰۸/۲۱"></div></div>
      </div>
      <div class="avam-form-section"><div class="avam-form-section-title"><span>02</span><div><strong>روایت زندگی</strong><small>داستانی که باید ماندگار بماند</small></div></div><div class="avam-field"><label>روایت زندگی</label><textarea name="content" required placeholder="از زندگی، شخصیت، لحظه‌های مهم و چیزهایی که دوست دارید دیگران بدانند بنویسید..."><?php echo $val('content'); ?></textarea></div></div>
      <div class="avam-form-section"><div class="avam-form-section-title"><span>03</span><div><strong>یادها و کلمات</strong><small>وصیت، نامه، خاطره و دعا</small></div></div><div class="avam-form-grid avam-form-grid-2"><div class="avam-field"><label>وصیت</label><textarea name="will" placeholder="اگر نوشته‌ای از او باقی مانده است..."><?php echo $val('will'); ?></textarea></div><div class="avam-field"><label>نامه</label><textarea name="letter" placeholder="نامه یا کلماتی که می‌خواهید نگه داشته شوند..."><?php echo $val('letter'); ?></textarea></div><div class="avam-field"><label>خاطره</label><textarea name="memory" placeholder="خاطره‌ای که هرگز نمی‌خواهید فراموش شود..."><?php echo $val('memory'); ?></textarea></div><div class="avam-field"><label>دعا</label><textarea name="prayer" placeholder="دعایی برای او..."><?php echo $val('prayer'); ?></textarea></div></div></div>
      <div class="avam-form-section"><div class="avam-form-section-title"><span>04</span><div><strong>تصویر یادبود</strong><small>یک تصویر آرام و ماندگار انتخاب کنید</small></div></div><div class="avam-upload"><input type="file" name="image" accept="image/jpeg,image/png,image/webp"><div><strong><?php echo $editing?'تغییر تصویر یادبود':'افزودن تصویر'; ?></strong><small>JPG، PNG یا WebP — ترجیحاً تصویر باکیفیت و روشن</small></div><span>↑</span></div></div>
      <div class="avam-form-submit"><a class="avam-create-back" href="<?php echo esc_url(avam_account_url()); ?>">انصراف</a><button class="avam-dash-primary" type="submit"><?php echo $editing?'ذخیره تغییرات':'ذخیره و ساخت یادبود'; ?><span>←</span></button></div>
    </form>
  </div>
  <?php return ob_get_clean();
 }

 public static function search(){
  $q=isset($_GET['q'])?sanitize_text_field(wp_unslash($_GET['q'])):'';
  $city=isset($_GET['city'])?sanitize_text_field(wp_unslash($_GET['city'])):'';
  $args=['post_type'=>self::CPT,'post_status'=>'publish','posts_per_page'=>24,'orderby'=>'date','order'=>'DESC'];
  if($q) $args['s']=$q;
  if($city) $args['meta_query']=[['key'=>'avam_city','value'=>$city,'compare'=>'LIKE']];
  $query=new WP_Query($args);
  ob_start();?><section class="avam-search-page"><div class="avam-search-hero"><span>آرامگاه مجازی</span><h1>جستجوی یادبودها</h1><p>نام متوفی یا شهر را جستجو کنید.</p><form class="avam-search-form" method="get" action="<?php echo esc_url(avam_memorials_url());?>"><input name="q" value="<?php echo esc_attr($q);?>" placeholder="نام متوفی"><input name="city" value="<?php echo esc_attr($city);?>" placeholder="شهر"><button type="submit">جستجو</button></form></div><div class="avam-results"><?php if($query->have_posts()):while($query->have_posts()):$query->the_post();$cid=get_post_meta(get_the_ID(),'avam_city',true);?><a class="avam-result" href="<?php the_permalink();?>"><?php if(has_post_thumbnail()):?><img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(),'medium'));?>" alt=""><?php endif;?><div><h2><?php the_title();?></h2><?php if($cid):?><span><?php echo esc_html($cid);?></span><?php endif;?></div></a><?php endwhile;else:?><div class="avam-no-results">یادبودی با این مشخصات پیدا نشد.</div><?php endif;wp_reset_postdata();?></div></section><?php return ob_get_clean();
 }

 public static function handle_auth(){
  if('POST'!==$_SERVER['REQUEST_METHOD']||empty($_POST['avam_action']))return;
  if(!isset($_POST['avam_nonce']))return;
  $nonce=sanitize_text_field(wp_unslash($_POST['avam_nonce']));$action=sanitize_key($_POST['avam_action']);
  if($action==='login'&&wp_verify_nonce($nonce,'avam_login')){
   $u=wp_signon(['user_login'=>sanitize_text_field(wp_unslash($_POST['log']??'')),'user_password'=>$_POST['pwd']??'','remember'=>true],is_ssl());
   if(is_wp_error($u)){wp_safe_redirect(add_query_arg('avam_error',rawurlencode('نام کاربری یا رمز عبور نادرست است.'),avam_login_url()));exit;}
   wp_safe_redirect(avam_account_url());exit;
  }
  if($action==='register'&&wp_verify_nonce($nonce,'avam_register')){
   $email=sanitize_email(wp_unslash($_POST['email']??''));$name=sanitize_text_field(wp_unslash($_POST['display_name']??''));$pass=$_POST['password']??'';
   if(!is_email($email)||email_exists($email)||strlen($pass)<8){wp_safe_redirect(add_query_arg('avam_error',rawurlencode('اطلاعات ثبت‌نام معتبر نیست.'),avam_register_url()));exit;}
   $base=sanitize_user(strtok($email,'@'));$login=$base;$i=1;while(username_exists($login))$login=$base.$i++;
   $uid=wp_create_user($login,$pass,$email);if(is_wp_error($uid)){wp_safe_redirect(avam_register_url());exit;}
   wp_update_user(['ID'=>$uid,'display_name'=>$name]);wp_set_auth_cookie($uid,true);wp_safe_redirect(avam_account_url());exit;
  }
 }

 public static function save_front_memorial(){
  if(!is_user_logged_in()||!check_ajax_referer('avam_front','nonce',false))wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  $title=sanitize_text_field(wp_unslash($_POST['title']??''));if(!$title)wp_send_json_error(['message'=>'نام الزامی است.'],422);
  $edit_id=absint($_POST['edit_id']??0);$post_content=sanitize_textarea_field(wp_unslash($_POST['content']??''));
  if($edit_id){$existing=get_post($edit_id);if(!$existing||$existing->post_type!==self::CPT||(int)$existing->post_author!==get_current_user_id())wp_send_json_error(['message'=>'دسترسی به این یادبود مجاز نیست.'],403);$id=wp_update_post(['ID'=>$edit_id,'post_title'=>$title,'post_content'=>$post_content],true);}else{$id=wp_insert_post(['post_type'=>self::CPT,'post_status'=>'publish','post_title'=>$title,'post_content'=>$post_content,'post_author'=>get_current_user_id()],true);}
  if(is_wp_error($id))wp_send_json_error(['message'=>'ذخیره انجام نشد.'],500);
  foreach(['city','birth','death','will','letter','memory','prayer'] as $k)update_post_meta($id,'avam_'.$k,sanitize_textarea_field(wp_unslash($_POST[$k]??'')));
  if(!empty($_FILES['image']['name'])){require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';$att=media_handle_upload('image',$id);if(!is_wp_error($att))set_post_thumbnail($id,$att);}
  wp_send_json_success(['url'=>get_permalink($id)]);
 }

 public static function delete_front_memorial(){
  if(!is_user_logged_in()||!check_ajax_referer('avam_front','nonce',false))wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  $id=absint($_POST['id']??0);$p=get_post($id);if(!$p||$p->post_type!==self::CPT||(int)$p->post_author!==get_current_user_id())wp_send_json_error(['message'=>'دسترسی مجاز نیست.'],403);
  wp_delete_post($id,true);wp_send_json_success(['url'=>avam_account_url()]);
 }

 public static function meta_boxes(){
  add_meta_box('avam_details','جزئیات یادبود',function($post){wp_nonce_field('avam_meta','avam_meta_nonce');foreach(['city'=>'شهر','birth'=>'تولد','death'=>'درگذشت','will'=>'وصیت','letter'=>'نامه','memory'=>'خاطره','prayer'=>'دعا'] as $k=>$label){$v=get_post_meta($post->ID,'avam_'.$k,true);if(in_array($k,['will','letter','memory','prayer'],true))echo '<p><label>'.$label.'</label><textarea style="width:100%;min-height:120px" name="avam_'.$k.'">'.esc_textarea($v).'</textarea></p>';else echo '<p><label>'.$label.'</label><input style="width:100%" name="avam_'.$k.'" value="'.esc_attr($v).'"></p>';}} ,self::CPT,'normal','high');
 }

 public static function save_meta($post_id){
  if(!isset($_POST['avam_meta_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['avam_meta_nonce'])),'avam_meta'))return;
  if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)return;if(!current_user_can('edit_post',$post_id))return;
  foreach(['city','birth','death','will','letter','memory','prayer'] as $k)if(isset($_POST['avam_'.$k]))update_post_meta($post_id,'avam_'.$k,sanitize_textarea_field(wp_unslash($_POST['avam_'.$k])));
 }

 public static function protect_admin(){if(is_admin()&&!current_user_can('manage_options')&&!wp_doing_ajax()){wp_safe_redirect(home_url('/'));exit;}}
 public static function hide_admin_bar(){if(!current_user_can('manage_options'))show_admin_bar(false);}

 public static function admin_menu(){add_options_page('تنظیمات آرامگاه مجازی','آرامگاه مجازی','manage_options','avam-settings',[__CLASS__,'settings_page']);}

 public static function settings(){
  register_setting('avam_settings','avam_site_message',['sanitize_callback'=>'sanitize_textarea_field']);
  register_setting('avam_settings','avam_home_media_type',['sanitize_callback'=>'sanitize_key']);
  register_setting('avam_settings','avam_home_image',['sanitize_callback'=>'absint']);
  register_setting('avam_settings','avam_home_video',['sanitize_callback'=>'absint']);
  add_settings_section('avam_general','تنظیمات صفحه اصلی',function(){echo '<p>تصویر یا ویدیوی پس‌زمینه صفحه اول را از کتابخانه رسانه انتخاب کنید.</p>';},'avam-settings');
  add_settings_field('avam_home_media_type','نوع پس‌زمینه',[__CLASS__,'media_type_field'],'avam-settings','avam_general');
  add_settings_field('avam_home_image','تصویر صفحه اصلی',[__CLASS__,'image_field'],'avam-settings','avam_general');
  add_settings_field('avam_home_video','ویدیوی لوپ صفحه اصلی',[__CLASS__,'video_field'],'avam-settings','avam_general');
  add_settings_field('avam_site_message','پیام پایانی',[__CLASS__,'settings_field'],'avam-settings','avam_general');
 }

 public static function media_type_field(){ $v=get_option('avam_home_media_type','video');?><label><input type="radio" name="avam_home_media_type" value="video" <?php checked($v,'video');?> > ویدیوی لوپ</label> &nbsp; <label><input type="radio" name="avam_home_media_type" value="image" <?php checked($v,'image');?> > تصویر ثابت</label><?php }
 public static function image_field(){self::media_picker('avam_home_image','image','تصویر را انتخاب کنید','image');}
 public static function video_field(){self::media_picker('avam_home_video','video','ویدیوی MP4/WebM را انتخاب کنید','video');}
 private static function media_picker($field,$type,$button,$library){
  $id=(int)get_option($field,0);$url=$id?wp_get_attachment_url($id):'';echo '<input type="hidden" id="'.esc_attr($field).'" name="'.esc_attr($field).'" value="'.esc_attr($id).'"><input type="text" readonly id="'.esc_attr($field).'-url" class="regular-text" value="'.esc_attr($url).'"> <button type="button" class="button avam-media-pick" data-field="'.esc_attr($field).'" data-type="'.esc_attr($library).'">'.esc_html($button).'</button>';
 }

 public static function settings_field(){echo '<textarea name="avam_site_message" id="avam_site_message" rows="5" class="large-text">'.esc_textarea(get_option('avam_site_message','')).'</textarea>';}

 public static function settings_page(){
  if(!current_user_can('manage_options'))return;
  echo '<div class="wrap" dir="rtl"><h1>تنظیمات آرامگاه مجازی</h1><form method="post" action="options.php">';settings_fields('avam_settings');do_settings_sections('avam-settings');submit_button('ذخیره تنظیمات');echo '</form></div>';
 }
}

function avam_account_url(){return home_url('/account/');}
function avam_login_url(){return home_url('/login/');}
function avam_register_url(){return home_url('/register/');}
function avam_create_url(){return home_url('/create-memorial/');}
function avam_memorials_url(){return home_url('/memorials/');}

register_activation_hook(__FILE__,function(){
 AVAM_Core::register_cpt();
 foreach([['login','ورود','[avam_login]'],['register','ثبت‌نام','[avam_register]'],['account','حساب من','[avam_account]'],['create-memorial','ساخت یادبود','[avam_create_memorial]'],['memorials','جستجوی یادبودها','[avam_memorial_search]']] as $p){
  if(!get_page_by_path($p[0]))wp_insert_post(['post_title'=>$p[1],'post_name'=>$p[0],'post_content'=>$p[2],'post_status'=>'publish','post_type'=>'page']);
 }
 flush_rewrite_rules();
});
register_deactivation_hook(__FILE__,function(){flush_rewrite_rules();});
AVAM_Core::init();
