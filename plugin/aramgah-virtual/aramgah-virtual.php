<?php
/**
 * Plugin Name: آرامگاه مجازی — Core
 * Description: Core memorial content, authentication, search, front-end account/create flows and administrator settings.
 * Version: 1.9.6
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
 }

 public static function register_cpt(){
  register_post_type(self::CPT,[
   'labels'=>['name'=>'یادبودها','singular_name'=>'یادبود','add_new'=>'یادبود جدید','add_new_item'=>'افزودن یادبود','edit_item'=>'ویرایش یادبود','menu_name'=>'یادبودها'],
   'public'=>true,'exclude_from_search'=>true,'show_in_rest'=>true,'has_archive'=>'memorials','rewrite'=>['slug'=>'memorial','with_front'=>false],
   'supports'=>['title','editor','thumbnail','author','comments'],'menu_icon'=>'dashicons-heart','capability_type'=>'post','map_meta_cap'=>true
  ]);
 }

 public static function assets(){
  wp_enqueue_style('avam-plugin',plugins_url('assets/css/core.css',__FILE__),[], '1.9.6');
  wp_enqueue_style('avam-unified-shell',plugins_url('assets/css/unified-shell.css',__FILE__),['avam-plugin'],'1.9.6');
  wp_enqueue_script('avam-plugin',plugins_url('assets/js/core.js',__FILE__),['jquery'], '1.9.6',true);
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
  if(is_user_logged_in()){ wp_safe_redirect(avam_account_url()); exit; }
  $err=isset($_GET['avam_error'])?sanitize_text_field(wp_unslash($_GET['avam_error'])):'';
  ob_start(); ?><div class="avam-card avam-auth-card"><h1>ورود به آرامگاه</h1><p class="avam-auth-lead">برای مدیریت یادبودها و روایت‌های شما.</p><?php if($err):?><div class="avam-notice avam-error"><?php echo esc_html($err);?></div><?php endif;?><form class="avam-form" method="post"><div class="avam-field"><label>نام کاربری یا ایمیل</label><input name="log" autocomplete="username" required></div><div class="avam-field"><label>رمز عبور</label><input type="password" name="pwd" autocomplete="current-password" required></div><?php wp_nonce_field('avam_login','avam_nonce');?><input type="hidden" name="avam_action" value="login"><input type="hidden" name="redirect_to" value="<?php echo esc_attr(isset($_GET['redirect_to']) ? wp_validate_redirect(wp_unslash($_GET['redirect_to']),avam_account_url()) : avam_account_url()); ?>"><button class="avam-btn avam-auth-btn" type="submit">ورود</button></form><p class="avam-auth-foot">حساب ندارید؟ <a href="<?php echo esc_url(avam_register_url());?>">ساخت حساب</a></p></div><?php return ob_get_clean();
 }

 public static function register(){
  if(is_user_logged_in()){ wp_safe_redirect(avam_account_url()); exit; }
  $err=isset($_GET['avam_error'])?sanitize_text_field(wp_unslash($_GET['avam_error'])):'';
  ob_start(); ?><div class="avam-card avam-auth-card"><h1>ساخت حساب</h1><p class="avam-auth-lead">یک فضای شخصی برای نگه‌داری یادها بسازید.</p><?php if($err):?><div class="avam-notice avam-error"><?php echo esc_html($err);?></div><?php endif;?><form class="avam-form" method="post"><div class="avam-field"><label>نام نمایشی</label><input name="display_name" required></div><div class="avam-field"><label>ایمیل</label><input type="email" name="email" autocomplete="email" required></div><div class="avam-field"><label>رمز عبور</label><input type="password" name="password" minlength="8" autocomplete="new-password" required></div><?php wp_nonce_field('avam_register','avam_nonce');?><input type="hidden" name="avam_action" value="register"><input type="hidden" name="redirect_to" value="<?php echo esc_attr(isset($_GET['redirect_to']) ? wp_validate_redirect(wp_unslash($_GET['redirect_to']),avam_account_url()) : avam_account_url()); ?>"><button class="avam-btn avam-auth-btn" type="submit">ایجاد حساب</button></form><p class="avam-auth-foot">حساب دارید؟ <a href="<?php echo esc_url(avam_login_url());?>">ورود</a></p></div><?php return ob_get_clean();
 }

 public static function account(){
  if(!is_user_logged_in()){ wp_safe_redirect(add_query_arg('redirect_to',rawurlencode(avam_account_url()),avam_login_url())); exit; }
  $uid=get_current_user_id();
  $user=wp_get_current_user();
  $posts=get_posts(['post_type'=>self::CPT,'author'=>$uid,'posts_per_page'=>50,'post_status'=>['publish','draft','pending'],'orderby'=>'date','order'=>'DESC']);
  $total=count($posts);$published=0;$drafts=0;
  foreach($posts as $p){if($p->post_status==='publish')$published++;else$drafts++;}
  $recent=array_slice($posts,0,5);
  $create=avam_create_url();$memorials=avam_memorials_url();$logout=wp_logout_url(home_url('/'));
  $display_name=$user->display_name ?: $user->user_login;
  $initial=mb_substr($display_name,0,1);
  $max_status=max(1,$total,$published,$drafts);
  $pub_h=round(($published/$max_status)*132);
  $draft_h=round(($drafts/$max_status)*132);
  $total_h=round(($total/$max_status)*132);
  ob_start(); ?>
  <section class="avam-account-dashboard-content" dir="rtl">
    <header class="avam-ref-heading">
        <div><span>فضای شخصی</span><h1>داشبورد</h1></div>
        <a class="avam-dash-primary" href="<?php echo esc_url($create); ?>"><span>＋</span> ساخت یادبود</a>
      </header>

      <section class="avam-ref-stats" aria-label="خلاصه حساب">
        <article><span class="avam-ref-stat-icon">♡</span><div><small>مجموع یادبودها</small><strong><?php echo esc_html($total); ?></strong></div><em>همه یادبودها</em></article>
        <article><span class="avam-ref-stat-icon is-green">✓</span><div><small>یادبودهای منتشرشده</small><strong><?php echo esc_html($published); ?></strong></div><em>قابل مشاهده</em></article>
        <article><span class="avam-ref-stat-icon is-blue">◷</span><div><small>در حال تکمیل</small><strong><?php echo esc_html($drafts); ?></strong></div><em>پیش‌نویس / بررسی</em></article>
        <article><span class="avam-ref-stat-icon is-terra">✦</span><div><small>آخرین فعالیت</small><strong><?php echo $recent ? esc_html(get_the_date('j M',$recent[0])) : '—'; ?></strong></div><em><?php echo $recent ? 'آخرین یادبود' : 'هنوز فعالیتی نیست'; ?></em></article>
      </section>

      <section class="avam-ref-chart-grid">
        <article class="avam-ref-panel">
          <div class="avam-ref-panel-head"><div><span>روند فعالیت</span><h2>روند یادبودها</h2></div><span class="avam-ref-filter">امسال <b>⌄</b></span></div>
          <div class="avam-ref-linechart">
            <div class="avam-ref-ylabels"><span><?php echo esc_html($max_status); ?></span><span><?php echo esc_html(max(1,round($max_status*.66))); ?></span><span><?php echo esc_html(max(0,round($max_status*.33))); ?></span><span>۰</span></div>
            <svg viewBox="0 0 620 210" preserveAspectRatio="none" aria-label="نمودار روند یادبودها">
              <defs><linearGradient id="avamLineFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#5d59df" stop-opacity=".22"/><stop offset="1" stop-color="#5d59df" stop-opacity="0"/></linearGradient></defs>
              <path d="M10 184 C70 174 90 140 145 146 S215 176 258 138 S330 122 372 142 S430 92 470 104 S535 74 610 26 L610 184 L10 184 Z" fill="url(#avamLineFill)"/>
              <path d="M10 184 C70 174 90 140 145 146 S215 176 258 138 S330 122 372 142 S430 92 470 104 S535 74 610 26" fill="none" stroke="#5d59df" stroke-width="3" stroke-linecap="round"/>
              <circle cx="470" cy="104" r="5" fill="#fff" stroke="#5d59df" stroke-width="3"/>
            </svg>
            <div class="avam-ref-xlabels"><span>فروردین</span><span>اردیبهشت</span><span>خرداد</span><span>تیر</span><span>مرداد</span><span>شهریور</span><span>مهر</span><span>آبان</span><span>آذر</span></div>
          </div>
        </article>

        <article class="avam-ref-panel">
          <div class="avam-ref-panel-head"><div><span>وضعیت محتوا</span><h2>وضعیت یادبودها</h2></div><span class="avam-ref-filter">همه <b>⌄</b></span></div>
          <div class="avam-ref-barchart">
            <div class="avam-ref-bars">
              <div><strong><?php echo esc_html($total); ?></strong><i style="height:<?php echo esc_attr($total_h); ?>px"></i><small>همه</small></div>
              <div><strong><?php echo esc_html($published); ?></strong><i style="height:<?php echo esc_attr($pub_h); ?>px"></i><small>منتشر</small></div>
              <div><strong><?php echo esc_html($drafts); ?></strong><i style="height:<?php echo esc_attr($draft_h); ?>px"></i><small>تکمیل</small></div>
              <div><strong><?php echo esc_html($published); ?></strong><i style="height:<?php echo esc_attr(max(12,$pub_h-8)); ?>px"></i><small>فعال</small></div>
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
                <td><div class="avam-ref-person"><span class="avam-ref-person-photo"><?php $thumb=get_the_post_thumbnail_url($p->ID,'thumbnail'); if($thumb): ?><img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($p->post_title); ?>"><?php else: ?>♡<?php endif; ?></span><strong><?php echo esc_html($p->post_title); ?></strong></div></td>
                <td><?php echo esc_html($city ?: '—'); ?></td>
                <td><?php echo esc_html(get_the_date('j F Y',$p)); ?></td>
                <td><span class="avam-ref-status <?php echo $p->post_status==='publish'?'is-published':''; ?>"><i></i><?php echo esc_html($status); ?></span></td>
                <td><div class="avam-ref-actions"><a href="<?php echo esc_url($view); ?>" aria-label="پیش‌نمایش یا مشاهده">◉</a><a href="<?php echo esc_url($edit); ?>" aria-label="ویرایش">✎</a><button type="button" class="avam-action-delete" data-id="<?php echo esc_attr($p->ID); ?>" aria-label="حذف">×</button></div></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
          <div class="avam-ref-empty"><div>♡</div><h3>هنوز یادبودی نساخته‌اید</h3><p>اولین یادبود را بسازید و نام، تصویر و روایت عزیزتان را در یک صفحه ماندگار نگه دارید.</p><a class="avam-dash-primary" href="<?php echo esc_url($create); ?>">ساخت اولین یادبود</a></div>
        <?php endif; ?>
      </section>
    </section>
  <?php return ob_get_clean();
 }

 public static function create(){
  if(!is_user_logged_in()) return '<div class="avam-card"><p>برای ساخت یادبود ابتدا وارد شوید.</p><a class="avam-btn" href="'.esc_url(avam_login_url()).'">ورود</a></div>';
  $edit_id=isset($_GET['edit'])?absint($_GET['edit']):0;$editing=false;$data=[];
  if($edit_id){$p=get_post($edit_id);if($p&&$p->post_type===self::CPT&&(int)$p->post_author===get_current_user_id()){$editing=true;$data=['title'=>$p->post_title,'content'=>$p->post_content];foreach(['city','birth','death','visibility','timeline'] as $k)$data[$k]=get_post_meta($edit_id,'avam_'.$k,true);}}
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
      <div class="avam-form-section"><div class="avam-form-section-title"><span>02</span><div><strong>روایت زندگی</strong><small>داستانی که باید ماندگار بماند</small></div></div><div class="avam-field"><label>روایت زندگی</label><textarea name="content" required placeholder="از زندگی، شخصیت، لحظه‌های مهم و چیزهایی که دوست دارید دیگران بدانند بنویسید..."><?php echo $val('content'); ?></textarea></div></div>\n      <div class="avam-form-section"><div class="avam-form-section-title"><span>03</span><div><strong>خط زمانی زندگی</strong><small>هر رویداد را در یک خط با قالب «تاریخ | رویداد» بنویسید</small></div></div><div class="avam-field"><label>رویدادهای مهم زندگی (اختیاری)</label><textarea name="timeline" placeholder="۱۳۳۵/۰۳/۱۰ | تولد\n۱۳۵۸/۰۲/۲۰ | آغاز زندگی مشترک\n۱۴۰۴/۰۸/۲۱ | درگذشت"><?php echo $val('timeline'); ?></textarea></div></div>
      
      <div class="avam-form-section"><div class="avam-form-section-title"><span>04</span><div><strong>تصویر و حریم خصوصی</strong><small>تصویر و دسترسی به یادبود را انتخاب کنید</small></div></div><div class="avam-upload"><input type="file" name="image" accept="image/jpeg,image/png,image/webp"><div><strong><?php echo $editing?'تغییر تصویر یادبود':'افزودن تصویر'; ?></strong><small>JPG، PNG یا WebP — حداکثر ۸ مگابایت</small></div><span>↑</span></div><div class="avam-field"><label>گالری تصاویر (اختیاری)</label><input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple><small>تا ۱۰ تصویر، هر تصویر حداکثر ۸ مگابایت</small></div><div class="avam-field"><label>حریم خصوصی</label><select name="visibility"><option value="public" <?php selected($data['visibility']??'public','public'); ?>>عمومی — در جستجو نمایش داده شود</option><option value="private" <?php selected($data['visibility']??'public','private'); ?>>خصوصی — فقط مالک و مدیر سایت</option></select></div></div>
      <div class="avam-form-submit"><a class="avam-create-back" href="<?php echo esc_url(avam_account_url()); ?>">انصراف</a><button class="avam-dash-primary" type="button" data-save-status="draft">ذخیره پیش‌نویس</button><button class="avam-dash-primary" type="button" data-save-status="publish"><?php echo $editing?'ذخیره تغییرات':'انتشار یادبود'; ?><span>←</span></button></div>
    </form>
  </div>
  <?php return ob_get_clean();
 }

 public static function search(){
  $q=isset($_GET['q'])?sanitize_text_field(wp_unslash($_GET['q'])):'';
  $city=isset($_GET['city'])?sanitize_text_field(wp_unslash($_GET['city'])):'';
  $sort=isset($_GET['sort'])?sanitize_key(wp_unslash($_GET['sort'])):'newest';$sorts=['newest'=>['date','DESC'],'oldest'=>['date','ASC'],'name'=>['title','ASC']];[$orderby,$order]=$sorts[$sort]??$sorts['newest'];
  $args=['post_type'=>self::CPT,'post_status'=>'publish','posts_per_page'=>24,'paged'=>max(1,get_query_var('paged')),'orderby'=>$orderby,'order'=>$order,'meta_query'=>['relation'=>'AND',['relation'=>'OR',['key'=>'avam_visibility','compare'=>'NOT EXISTS'],['key'=>'avam_visibility','value'=>'private','compare'=>'!=']]]];
  if($q) $args['s']=$q;
  if($city) $args['meta_query'][]=['key'=>'avam_city','value'=>$city,'compare'=>'LIKE'];
  $query=new WP_Query($args);
  ob_start();?><section class="avam-search-page"><div class="avam-search-hero"><span>آرامگاه مجازی</span><h1>جستجوی یادبودها</h1><p>نام متوفی یا شهر را جستجو کنید.</p><form class="avam-search-form" method="get" action="<?php echo esc_url(avam_memorials_url()); ?>"><input type="hidden" name="q" value="<?php echo esc_attr($q); ?>"><label><span>شهر</span><input name="city" value="<?php echo esc_attr($city); ?>" placeholder="فیلتر بر اساس شهر"></label><label><span>مرتب‌سازی</span><select name="sort"><option value="newest" <?php selected($sort,'newest'); ?>>جدیدترین</option><option value="oldest" <?php selected($sort,'oldest'); ?>>قدیمی‌ترین</option><option value="name" <?php selected($sort,'name'); ?>>نام</option></select></label><button type="submit">اعمال فیلترها</button></form></div><div class="avam-results"><?php if($query->have_posts()):while($query->have_posts()):$query->the_post();$cid=get_post_meta(get_the_ID(),'avam_city',true);?><a class="avam-result" href="<?php the_permalink();?>"><?php if(has_post_thumbnail()):?><img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(),'medium'));?>" alt=""><?php endif;?><div><h2><?php the_title();?></h2><?php if($cid):?><span><?php echo esc_html($cid);?></span><?php endif;?></div></a><?php endwhile; echo '<nav class="avam-archive-pagination">'.wp_kses_post(paginate_links(['total'=>$query->max_num_pages,'current'=>max(1,get_query_var('paged')),'type'=>'list'])).'</nav>'; else:?><div class="avam-no-results">یادبودی با این مشخصات پیدا نشد.</div><?php endif;wp_reset_postdata();?></div></section><?php return ob_get_clean();
 }

 public static function handle_auth(){
  if('POST'!==$_SERVER['REQUEST_METHOD']||empty($_POST['avam_action']))return;
  if(!isset($_POST['avam_nonce']))return;
  $nonce=sanitize_text_field(wp_unslash($_POST['avam_nonce']));$action=sanitize_key($_POST['avam_action']);
  if($action==='login'&&wp_verify_nonce($nonce,'avam_login')){
   $redirect=isset($_POST['redirect_to']) ? wp_validate_redirect(wp_unslash($_POST['redirect_to']),avam_account_url()) : avam_account_url();
   $login=sanitize_text_field(wp_unslash($_POST['log']??''));
   if(is_email($login)){$email_user=get_user_by('email',$login);if($email_user)$login=$email_user->user_login;}
   $u=wp_signon(['user_login'=>$login,'user_password'=>$_POST['pwd']??'','remember'=>true],is_ssl());
   if(is_wp_error($u)){wp_safe_redirect(add_query_arg('avam_error',rawurlencode('نام کاربری یا رمز عبور نادرست است.'),avam_login_url()));exit;}
   wp_safe_redirect($redirect);exit;
  }
  if($action==='register'&&wp_verify_nonce($nonce,'avam_register')){
   $email=sanitize_email(wp_unslash($_POST['email']??''));$name=sanitize_text_field(wp_unslash($_POST['display_name']??''));$pass=$_POST['password']??'';
   if(!is_email($email)||email_exists($email)||strlen($pass)<8){wp_safe_redirect(add_query_arg('avam_error',rawurlencode('اطلاعات ثبت‌نام معتبر نیست.'),avam_register_url()));exit;}
   $base=sanitize_user(strtok($email,'@'));$login=$base;$i=1;while(username_exists($login))$login=$base.$i++;
   $uid=wp_create_user($login,$pass,$email);if(is_wp_error($uid)){wp_safe_redirect(avam_register_url());exit;}
   wp_update_user(['ID'=>$uid,'display_name'=>$name]);wp_set_auth_cookie($uid,true);$redirect=isset($_POST['redirect_to']) ? wp_validate_redirect(wp_unslash($_POST['redirect_to']),avam_account_url()) : avam_account_url();wp_safe_redirect($redirect);exit;
  }
 }

 public static function save_front_memorial(){
  if(!is_user_logged_in()||!check_ajax_referer('avam_front','nonce',false))wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  $title=sanitize_text_field(wp_unslash($_POST['title']??''));if(!$title)wp_send_json_error(['message'=>'نام الزامی است.'],422);
  $edit_id=absint($_POST['edit_id']??0);$post_content=sanitize_textarea_field(wp_unslash($_POST['content']??''));
  $save_status=sanitize_key(wp_unslash($_POST['save_status']??'publish')); $save_status=$save_status==='draft'?'draft':'publish';
  if(mb_strlen($title)>160)wp_send_json_error(['message'=>'نام یادبود بیش از حد طولانی است.'],422);
  if(mb_strlen($post_content)>50000)wp_send_json_error(['message'=>'متن روایت بیش از حد طولانی است.'],422);
  if($save_status==='publish' && trim($post_content)==='')wp_send_json_error(['message'=>'برای انتشار، روایت زندگی را وارد کنید یا پیش‌نویس ذخیره کنید.'],422);
  $timeline_check=sanitize_textarea_field(wp_unslash($_POST['timeline']??'')); if(mb_strlen($timeline_check)>12000)wp_send_json_error(['message'=>'خط زمانی بیش از حد طولانی است.'],422);
  if(!empty($_FILES['image']['name'])){
   if(!empty($_FILES['image']['error']) && (int)$_FILES['image']['error']!==UPLOAD_ERR_OK)wp_send_json_error(['message'=>'آپلود تصویر ناموفق بود.'],422);
   if((int)($_FILES['image']['size']??0)>8*1024*1024)wp_send_json_error(['message'=>'حجم تصویر باید کمتر از ۸ مگابایت باشد.'],422);
   $check=wp_check_filetype_and_ext($_FILES['image']['tmp_name'],$_FILES['image']['name']); if(empty($check['type'])||!in_array($check['type'],['image/jpeg','image/png','image/webp'],true))wp_send_json_error(['message'=>'فقط تصویر JPG، PNG یا WebP مجاز است.'],422);
  }
  if(!empty($_FILES['gallery']['name']) && is_array($_FILES['gallery']['name'])){
   if(count($_FILES['gallery']['name'])>10)wp_send_json_error(['message'=>'حداکثر ۱۰ تصویر برای گالری مجاز است.'],422);
   foreach($_FILES['gallery']['name'] as $i=>$name){if(!$name)continue; if((int)($_FILES['gallery']['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)wp_send_json_error(['message'=>'آپلود یکی از تصاویر گالری ناموفق بود.'],422); if((int)($_FILES['gallery']['size'][$i]??0)>8*1024*1024)wp_send_json_error(['message'=>'حجم هر تصویر گالری باید کمتر از ۸ مگابایت باشد.'],422); $check=wp_check_filetype_and_ext($_FILES['gallery']['tmp_name'][$i],$name); if(empty($check['type'])||!in_array($check['type'],['image/jpeg','image/png','image/webp'],true))wp_send_json_error(['message'=>'گالری فقط تصویر JPG، PNG یا WebP می‌پذیرد.'],422); }
  }
  if($edit_id){$existing=get_post($edit_id);if(!$existing||$existing->post_type!==self::CPT||(int)$existing->post_author!==get_current_user_id())wp_send_json_error(['message'=>'دسترسی به این یادبود مجاز نیست.'],403);$id=wp_update_post(['ID'=>$edit_id,'post_title'=>$title,'post_content'=>$post_content,'post_status'=>$save_status],true);}else{$id=wp_insert_post(['post_type'=>self::CPT,'post_status'=>$save_status,'post_title'=>$title,'post_content'=>$post_content,'post_author'=>get_current_user_id()],true);}
  if(is_wp_error($id))wp_send_json_error(['message'=>'ذخیره انجام نشد.'],500);
  foreach(['city','birth','death'] as $k)update_post_meta($id,'avam_'.$k,sanitize_textarea_field(wp_unslash($_POST[$k]??'')));
  $timeline=$timeline_check; update_post_meta($id,'avam_timeline',$timeline);
  $visibility=sanitize_key(wp_unslash($_POST['visibility']??'public')); update_post_meta($id,'avam_visibility',in_array($visibility,['public','private'],true)?$visibility:'public');
  if(!empty($_FILES['image']['name'])){
      require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';
   $att=media_handle_upload('image',$id);
   if(is_wp_error($att))wp_send_json_error(['message'=>'تصویر قابل ذخیره‌سازی نبود.'],422);
   set_post_thumbnail($id,$att);
  }
  if(!empty($_FILES['gallery']['name']) && is_array($_FILES['gallery']['name'])){
   $gallery_ids=[]; $total_files=count($_FILES['gallery']['name']);
   require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';
   for($i=0;$i<$total_files;$i++){
    if(empty($_FILES['gallery']['name'][$i]))continue;
    $single=['name'=>$_FILES['gallery']['name'][$i],'type'=>$_FILES['gallery']['type'][$i],'tmp_name'=>$_FILES['gallery']['tmp_name'][$i],'error'=>$_FILES['gallery']['error'][$i],'size'=>$_FILES['gallery']['size'][$i]];
    $_FILES['avam_gallery_single']=$single; $gallery_att=media_handle_upload('avam_gallery_single',$id); unset($_FILES['avam_gallery_single']);
    if(is_wp_error($gallery_att))wp_send_json_error(['message'=>'یکی از تصاویر گالری ذخیره نشد.'],422); $gallery_ids[]=(int)$gallery_att;
   }
   if($gallery_ids){$old=get_post_meta($id,'avam_gallery_ids',true);if(!is_array($old))$old=[];update_post_meta($id,'avam_gallery_ids',array_values(array_unique(array_merge($old,$gallery_ids))));}
  }
  $url=$save_status==='draft'?get_preview_post_link($id):get_permalink($id);
  wp_send_json_success(['url'=>$url?:get_permalink($id),'id'=>(int)$id,'status'=>$save_status,'message'=>$save_status==='draft'?'پیش‌نویس ذخیره شد.':'یادبود منتشر شد.']);
 }

 public static function delete_front_memorial(){
  if(!is_user_logged_in()||!check_ajax_referer('avam_front','nonce',false))wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  $id=absint($_POST['id']??0);$p=get_post($id);if(!$p||$p->post_type!==self::CPT||(int)$p->post_author!==get_current_user_id())wp_send_json_error(['message'=>'دسترسی مجاز نیست.'],403);
  wp_delete_post($id,true);wp_send_json_success(['url'=>avam_account_url()]);
 }

 public static function meta_boxes(){
  add_meta_box('avam_details','جزئیات یادبود',function($post){wp_nonce_field('avam_meta','avam_meta_nonce');foreach(['city'=>'شهر','birth'=>'تولد','death'=>'درگذشت'] as $k=>$label){$v=get_post_meta($post->ID,'avam_'.$k,true);echo '<p><label>'.$label.'</label><input style="width:100%" name="avam_'.$k.'" value="'.esc_attr($v).'"></p>';}$visibility=get_post_meta($post->ID,'avam_visibility',true)?:'public';echo '<p><label for="avam_visibility"><strong>حریم خصوصی</strong></label><select id="avam_visibility" name="avam_visibility" style="width:100%"><option value="public" '.selected($visibility,'public',false).'>عمومی — قابل نمایش در جستجو</option><option value="private" '.selected($visibility,'private',false).'>خصوصی — فقط مالک و مدیر</option></select></p>';},self::CPT,'normal','high');
 }

 public static function save_meta($post_id){
  if(!isset($_POST['avam_meta_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['avam_meta_nonce'])),'avam_meta'))return;
  if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)return;if(!current_user_can('edit_post',$post_id))return;
  foreach(['city','birth','death'] as $k)if(isset($_POST['avam_'.$k]))update_post_meta($post_id,'avam_'.$k,sanitize_textarea_field(wp_unslash($_POST['avam_'.$k])));if(isset($_POST['avam_visibility']))update_post_meta($post_id,'avam_visibility',in_array(sanitize_key(wp_unslash($_POST['avam_visibility'])),['public','private'],true)?sanitize_key(wp_unslash($_POST['avam_visibility'])):'public');
 }

 public static function protect_admin(){if(is_admin()&&!current_user_can('manage_options')&&!wp_doing_ajax()){wp_safe_redirect(home_url('/'));exit;}}
 public static function hide_admin_bar(){if(!current_user_can('manage_options'))show_admin_bar(false);}

 public static function admin_menu(){add_options_page('تنظیمات آرامگاه مجازی','آرامگاه مجازی','manage_options','avam-settings',[__CLASS__,'settings_page']);}

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

function avam_account_url(){return home_url('/account/');}
function avam_login_url(){return home_url('/login/');}
function avam_register_url(){return home_url('/register/');}
function avam_create_url(){return home_url('/create-memorial/');}
function avam_memorials_url(){ $url=get_post_type_archive_link('avam_memorial'); return $url ? $url : home_url('/memorials/'); }

register_activation_hook(__FILE__,function(){
 AVAM_Core::register_cpt();
 foreach([['login','ورود','[avam_login]'],['register','ثبت‌نام','[avam_register]'],['account','حساب من','[avam_account]'],['create-memorial','ساخت یادبود','[avam_create_memorial]'],['memorials','جستجوی یادبودها','[avam_memorial_search]']] as $p){
  if(!get_page_by_path($p[0]))wp_insert_post(['post_title'=>$p[1],'post_name'=>$p[0],'post_content'=>$p[2],'post_status'=>'publish','post_type'=>'page']);
 }
 flush_rewrite_rules();
});
register_deactivation_hook(__FILE__,function(){wp_clear_scheduled_hook('avam_daily_anniversary_check');flush_rewrite_rules();});
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
