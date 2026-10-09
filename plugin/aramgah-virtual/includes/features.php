<?php
/**
 * Optional feature layer for آرامگاه مجازی.
 * Adds moderation, privacy, profile management, memorial reactions and reporting.
 */
if (!defined('ABSPATH')) exit;

final class AVAM_Features {
 public static function init() {
  add_action('init',[__CLASS__,'ensure_pages'],20);
  add_action('pre_get_posts',[__CLASS__,'exclude_private_from_public_queries']);
  add_action('admin_menu',[__CLASS__,'admin_menu'],30);
  add_action('admin_init',[__CLASS__,'register_settings']);
  add_filter('pre_comment_approved',[__CLASS__,'moderate_comments'],20,2);
  add_filter('comment_form_defaults',[__CLASS__,'comment_form_defaults']);
  add_filter('comments_open',[__CLASS__,'comments_open_for_memorial'],20,2);
  add_action('wp_ajax_avam_react',[__CLASS__,'react']);
  add_action('wp_ajax_nopriv_avam_react',[__CLASS__,'react']);
  add_action('wp_ajax_avam_report',[__CLASS__,'report']);
  add_action('wp_ajax_nopriv_avam_report',[__CLASS__,'report']);
  add_shortcode('avam_profile_settings',[__CLASS__,'profile']);
  add_action('template_redirect',[__CLASS__,'protect_private_memorial']);
  add_action('wp_enqueue_scripts',[__CLASS__,'assets']);
  add_action('avam_daily_anniversary_check',[__CLASS__,'send_anniversary_reminders']);
  if (!wp_next_scheduled('avam_daily_anniversary_check')) wp_schedule_event(time()+300,'daily','avam_daily_anniversary_check');
 }
 public static function ensure_pages() {
  foreach ([['profile','تنظیمات حساب','[avam_profile_settings]']] as $page) {
   if (!get_page_by_path($page[0])) wp_insert_post(['post_title'=>$page[1],'post_name'=>$page[0],'post_content'=>$page[2],'post_status'=>'publish','post_type'=>'page']);
  }
 }
 public static function register_settings() {
  register_setting('avam_feature_settings','avam_comments_require_moderation',['sanitize_callback'=>function($v){return $v?'1':'0';}]);
  register_setting('avam_feature_settings','avam_comments_enabled',['sanitize_callback'=>function($v){return $v?'1':'0';}]);
  register_setting('avam_feature_settings','avam_report_email',['sanitize_callback'=>'sanitize_email']);
  register_setting('avam_feature_settings','avam_rate_limit_seconds',['sanitize_callback'=>function($v){return min(300,max(10,absint($v)));}]);
 }
 public static function admin_menu() {
  add_submenu_page('edit.php?post_type=avam_memorial','تنظیمات یادبودها','تنظیمات و گزارش‌ها','manage_options','avam-feature-settings',[__CLASS__,'settings_page']);
 }
 public static function settings_page() {
  if (!current_user_can('manage_options')) return;
  echo '<div class="wrap" dir="rtl"><h1>مدیریت تجربه یادبود</h1><p>تنظیمات دعا و خاطره، کنترل هرزنامه و گزارش‌های کاربران.</p><form method="post" action="options.php">';
  settings_fields('avam_feature_settings');
  self::setting_checkbox('avam_comments_enabled','فعال بودن بخش دعا و خاطره');
  self::setting_checkbox('avam_comments_require_moderation','تأیید دیدگاه‌ها پیش از انتشار');
  echo '<p><label>ایمیل دریافت گزارش‌ها <input type="email" class="regular-text" name="avam_report_email" value="'.esc_attr(get_option('avam_report_email',get_option('admin_email'))).'"></label></p>';
  echo '<p><label>فاصله مجاز بین دیدگاه‌ها (ثانیه) <input type="number" min="10" max="300" name="avam_rate_limit_seconds" value="'.esc_attr(get_option('avam_rate_limit_seconds',30)).'"></label></p>';
  submit_button('ذخیره تنظیمات'); echo '</form><hr><h2>گزارش‌های اخیر</h2>';
  $reports=get_posts(['post_type'=>'avam_memorial','post_status'=>['publish','draft','pending'],'numberposts'=>20,'meta_key'=>'avam_reports_count','orderby'=>'meta_value_num','order'=>'DESC']);
  if (!$reports) echo '<p>گزارشی ثبت نشده است.</p>';
  else { echo '<table class="widefat striped"><thead><tr><th>یادبود</th><th>تعداد گزارش</th><th>آخرین گزارش</th><th>پیوند</th></tr></thead><tbody>'; foreach($reports as $p) echo '<tr><td>'.esc_html($p->post_title).'</td><td>'.esc_html((int)get_post_meta($p->ID,'avam_reports_count',true)).'</td><td>'.esc_html(get_post_meta($p->ID,'avam_last_report',true)).'</td><td><a href="'.esc_url(get_edit_post_link($p->ID)).'">مدیریت</a></td></tr>'; echo '</tbody></table>'; }
  echo '</div>';
 }
 private static function setting_checkbox($key,$label) {
  $default=($key==='avam_comments_enabled')?'1':'1';
  echo '<p><label><input type="hidden" name="'.esc_attr($key).'" value="0"><input type="checkbox" name="'.esc_attr($key).'" value="1" '.checked(get_option($key,$default),'1',false).'> '.esc_html($label).'</label></p>';
 }
 public static function comments_open_for_memorial($open,$post_id) {
  if (get_post_type($post_id)!=='avam_memorial') return $open;
  return get_option('avam_comments_enabled','1')==='1' ? true : false;
 }
 public static function comment_form_defaults($defaults) {
  $defaults['title_reply']='دعایی یا خاطره‌ای بنویسید';
  $defaults['label_submit']='ثبت پیام';
  $defaults['comment_notes_before']='';
  $defaults['comment_notes_after']='';
  $defaults['logged_in_as']='';
  $defaults['comment_field']='<p class="comment-form-comment"><label for="comment">پیام شما</label><textarea id="comment" name="comment" rows="5" maxlength="5000" required placeholder="با احترام، خاطره یا دعایی از خود به یادگار بگذارید…"></textarea></p>';
  return $defaults;
 }
 public static function moderate_comments($approved,$commentdata) {
  if (empty($commentdata['comment_post_ID']) || get_post_type((int)$commentdata['comment_post_ID'])!=='avam_memorial') return $approved;
  $ip=isset($commentdata['comment_author_IP'])?$commentdata['comment_author_IP']:'';
  $key='avam_comment_rate_'.hash('sha256',$ip.'|'.(int)$commentdata['comment_post_ID']);
  if (get_transient($key)) return 'spam';
  set_transient($key,1,max(10,min(300,absint(get_option('avam_rate_limit_seconds',30)))));
  if (get_option('avam_comments_require_moderation','1')==='1' && !current_user_can('moderate_comments')) return 0;
  return $approved;
 }
 public static function exclude_private_from_public_queries($query) {
  if (is_admin() || !$query->is_main_query() || !$query->is_post_type_archive('avam_memorial')) return;
  $existing=$query->get('meta_query'); if (!is_array($existing)) $existing=[];
  $existing[]=['relation'=>'OR',['key'=>'avam_visibility','compare'=>'NOT EXISTS'],['key'=>'avam_visibility','value'=>'private','compare'=>'!=']];
  $query->set('meta_query',$existing);
 }
 public static function protect_private_memorial() {
  if (!is_singular('avam_memorial')) return;
  $id=get_queried_object_id(); $visibility=get_post_meta($id,'avam_visibility',true);
  if ($visibility==='private' && !current_user_can('manage_options') && (int)get_post_field('post_author',$id)!==get_current_user_id()) {
   status_header(404); nocache_headers(); include get_404_template(); exit;
  }
 }
 public static function assets() {
  if (!is_singular('avam_memorial')) return;
  wp_enqueue_script('avam-features',plugins_url('../assets/js/features.js',__FILE__),['jquery'],'1.0.0',true);
  wp_localize_script('avam-features','AVAM_FEATURES',['ajax'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('avam_features')]);
 }
 public static function react() {
  if (!check_ajax_referer('avam_features','nonce',false)) wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  $id=absint($_POST['id']??0); $kind=sanitize_key($_POST['kind']??'');
  if (!$id || get_post_type($id)!=='avam_memorial' || !in_array($kind,['candle','flower'],true)) wp_send_json_error(['message'=>'درخواست نامعتبر است.'],422);
  $ip=$_SERVER['REMOTE_ADDR']??'unknown'; $key='avam_react_'.hash('sha256',$ip.'|'.$id.'|'.$kind);
  if (get_transient($key)) wp_send_json_error(['message'=>'از همراهی شما سپاسگزاریم؛ این یادبود را قبلاً همراهی کرده‌اید.'],429);
  set_transient($key,1,DAY_IN_SECONDS);
  $meta='avam_'.$kind.'_count'; $count=max(0,(int)get_post_meta($id,$meta,true))+1; update_post_meta($id,$meta,$count);
  wp_send_json_success(['count'=>$count]);
 }
 public static function report() {
  if (!check_ajax_referer('avam_features','nonce',false)) wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  $id=absint($_POST['id']??0); if (!$id || get_post_type($id)!=='avam_memorial') wp_send_json_error(['message'=>'یادبود پیدا نشد.'],404);
  $reason=sanitize_textarea_field(wp_unslash($_POST['reason']??'')); if (mb_strlen($reason)<5 || mb_strlen($reason)>1000) wp_send_json_error(['message'=>'لطفاً دلیل گزارش را در ۵ تا ۱۰۰۰ نویسه بنویسید.'],422);
  $ip=$_SERVER['REMOTE_ADDR']??'unknown'; $key='avam_report_'.hash('sha256',$ip.'|'.$id);
  if (get_transient($key)) wp_send_json_error(['message'=>'برای این یادبود قبلاً گزارشی از شما ثبت شده است.'],429);
  set_transient($key,1,DAY_IN_SECONDS);
  $count=(int)get_post_meta($id,'avam_reports_count',true)+1; update_post_meta($id,'avam_reports_count',$count); update_post_meta($id,'avam_last_report',current_time('mysql'));
  $email=sanitize_email(get_option('avam_report_email',get_option('admin_email')));
  if (is_email($email)) wp_mail($email,'گزارش یادبود: '.get_the_title($id),"یادبود: ".get_permalink($id)."\nدلیل: ".$reason."\nتعداد گزارش‌ها: ".$count);
  wp_send_json_success(['message'=>'گزارش شما برای بررسی ارسال شد.']);
 }
 public static function profile() {
  if (!is_user_logged_in()) return '<div class="avam-card"><p>برای تنظیمات حساب وارد شوید.</p><a class="avam-btn" href="'.esc_url(avam_login_url()).'">ورود</a></div>';
  $uid=get_current_user_id(); $user=get_userdata($uid); $notice='';
  if ('POST'===($_SERVER['REQUEST_METHOD']??'') && isset($_POST['avam_profile_action'])) {
   if (!isset($_POST['avam_profile_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['avam_profile_nonce'])),'avam_profile_'.$uid)) $notice='<div class="avam-notice avam-error">درخواست نامعتبر است.</div>';
   else {
    $action=sanitize_key($_POST['avam_profile_action']);
    if ($action==='profile') {
     $name=sanitize_text_field(wp_unslash($_POST['display_name']??'')); $email=sanitize_email(wp_unslash($_POST['email']??''));
     if (!$name || !is_email($email) || email_exists($email) && strtolower($email)!==strtolower($user->user_email)) $notice='<div class="avam-notice avam-error">نام یا ایمیل معتبر نیست یا قبلاً استفاده شده است.</div>';
     else { $res=wp_update_user(['ID'=>$uid,'display_name'=>$name,'user_email'=>$email]); $notice=is_wp_error($res)?'<div class="avam-notice avam-error">ذخیره اطلاعات انجام نشد.</div>':'<div class="avam-notice">اطلاعات حساب ذخیره شد.</div>'; $user=get_userdata($uid); }
    } elseif ($action==='password') {
     $current=(string)($_POST['current_password']??''); $new=(string)($_POST['new_password']??''); $confirm=(string)($_POST['confirm_password']??'');
     if (!wp_check_password($current,$user->user_pass,$uid) || strlen($new)<10 || $new!==$confirm) $notice='<div class="avam-notice avam-error">رمز فعلی نادرست است یا رمز جدید (حداقل ۱۰ نویسه) مطابقت ندارد.</div>';
     else { wp_set_password($new,$uid); wp_set_auth_cookie($uid,true,is_ssl()); $user=get_userdata($uid); $notice='<div class="avam-notice">رمز عبور تغییر کرد.</div>'; }
    }
   }
  }
  ob_start(); ?><section class="avam-card avam-profile-settings" dir="rtl"><h1>تنظیمات حساب</h1><p>اطلاعات نمایشی و امنیت حساب خود را مدیریت کنید.</p><?php echo $notice; ?><form method="post" class="avam-form"><input type="hidden" name="avam_profile_action" value="profile"><?php wp_nonce_field('avam_profile_'.$uid,'avam_profile_nonce'); ?><div class="avam-field"><label>نام نمایشی</label><input name="display_name" required maxlength="80" value="<?php echo esc_attr($user->display_name); ?>"></div><div class="avam-field"><label>ایمیل</label><input type="email" name="email" required value="<?php echo esc_attr($user->user_email); ?>"></div><label class="avam-reminder-optin"><input type="checkbox" name="anniversary_reminders" value="1" <?php checked(get_user_meta($uid,'avam_anniversary_reminders',true),'1'); ?>> یادآوری سالانه سالگرد درگذشت از طریق ایمیل</label><button class="avam-btn" type="submit">ذخیره پروفایل</button></form><hr><h2>تغییر رمز عبور</h2><form method="post" class="avam-form"><input type="hidden" name="avam_profile_action" value="password"><?php wp_nonce_field('avam_profile_'.$uid,'avam_profile_nonce'); ?><div class="avam-field"><label>رمز فعلی</label><input type="password" name="current_password" autocomplete="current-password" required></div><div class="avam-field"><label>رمز جدید</label><input type="password" name="new_password" minlength="10" autocomplete="new-password" required></div><div class="avam-field"><label>تکرار رمز جدید</label><input type="password" name="confirm_password" minlength="10" autocomplete="new-password" required></div><button class="avam-btn" type="submit">تغییر رمز</button></form></section><?php return ob_get_clean();
 }
 public static function send_anniversary_reminders() {
  $today=time(); $jalali=self::gregorian_to_jalali((int)wp_date('Y',$today),(int)wp_date('n',$today),(int)wp_date('j',$today));
  $posts=get_posts(['post_type'=>'avam_memorial','post_status'=>'publish','numberposts'=>-1,'meta_query'=>['relation'=>'OR',['key'=>'avam_visibility','compare'=>'NOT EXISTS'],['key'=>'avam_visibility','value'=>'private','compare'=>'!=']]]);
  foreach($posts as $post) {
   $owner=(int)$post->post_author; if(get_user_meta($owner,'avam_anniversary_reminders',true)!=='1')continue;
   $raw=(string)get_post_meta($post->ID,'avam_death',true); $raw=strtr($raw,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9','-'=>'/','.'=>'/']);
   if(!preg_match('/^(13|14|15)\d{2}\/(\d{1,2})\/(\d{1,2})$/',$raw,$m))continue;
   if((int)$m[2]!==$jalali[1]||(int)$m[3]!==$jalali[2])continue;
   $year=(int)$jalali[0]; if((int)get_post_meta($post->ID,'avam_last_anniversary_email_year',true)===$year)continue;
   $user=get_userdata($owner); if(!$user||!is_email($user->user_email))continue;
   $subject='یادآوری سالگرد درگذشت — '.get_the_title($post->ID);
   $message="امروز سالگرد درگذشت عزیزتان است.\n\nبرای مرور روایت و یادهای ثبت‌شده می‌توانید به صفحه یادبود سر بزنید:\n".get_permalink($post->ID)."\n\nبرای توقف این یادآوری‌ها، از بخش تنظیمات حساب آن را غیرفعال کنید.";
   if(wp_mail($user->user_email,$subject,$message))update_post_meta($post->ID,'avam_last_anniversary_email_year',$year);
  }
 }
 private static function gregorian_to_jalali($gy,$gm,$gd) {
  $gdm=[0,31,59,90,120,151,181,212,243,273,304,334];
  if($gy>1600){$jy=979;$gy-=1600;}else{$jy=0;$gy-=621;}
  $gy2=$gm>2?$gy+1:$gy;
  $days=365*$gy+intdiv($gy2+3,4)-intdiv($gy2+99,100)+intdiv($gy2+399,400)-80+$gd+$gdm[$gm-1];
  $jy+=33*intdiv($days,12053);$days%=12053;
  $jy+=4*intdiv($days,1461);$days%=1461;
  if($days>365){$jy+=intdiv($days-1,365);$days=($days-1)%365;}
  if($days<186){$jm=1+intdiv($days,31);$jd=1+$days%31;}
  else{$jm=7+intdiv($days-186,30);$jd=1+($days-186)%30;}
  return [$jy,$jm,$jd];
 }

}
AVAM_Features::init();
