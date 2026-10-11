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
  add_filter('rest_avam_memorial_query',[__CLASS__,'exclude_private_rest_query'],10,2);
  add_filter('rest_prepare_attachment',[__CLASS__,'filter_private_rest_media'],10,3);
  add_filter('posts_clauses',[__CLASS__,'exclude_private_rest_media_clauses'],10,2);
  add_filter('comments_clauses',[__CLASS__,'exclude_private_rest_comments'],10,2);
  add_filter('rest_prepare_comment',[__CLASS__,'filter_private_rest_comment'],10,3);
  add_filter('rest_prepare_avam_memorial',[__CLASS__,'filter_private_rest_memorial'],10,3);
  add_filter('get_avatar_url',[__CLASS__,'filter_user_avatar_url'],10,3);
  add_action('admin_menu',[__CLASS__,'admin_menu'],30);
  add_action('admin_init',[__CLASS__,'register_settings']);
  add_filter('pre_comment_approved',[__CLASS__,'moderate_comments'],20,2);
  add_filter('comment_form_defaults',[__CLASS__,'comment_form_defaults']);
  add_filter('comments_open',[__CLASS__,'comments_open_for_memorial'],20,2);
  add_action('wp_ajax_avam_submit_comment',[__CLASS__,'submit_comment']);
  add_action('wp_ajax_nopriv_avam_submit_comment',[__CLASS__,'submit_comment']);
  add_action('wp_ajax_avam_react',[__CLASS__,'react']);
  add_action('wp_ajax_nopriv_avam_react',[__CLASS__,'react']);
  add_action('wp_ajax_avam_reaction_list',[__CLASS__,'reaction_list']);
  add_action('wp_ajax_nopriv_avam_reaction_list',[__CLASS__,'reaction_list']);
  add_action('wp_ajax_avam_report',[__CLASS__,'report']);
  add_action('wp_ajax_nopriv_avam_report',[__CLASS__,'report']);
  add_shortcode('avam_profile_settings',[__CLASS__,'profile']);
  add_shortcode('avam_my_comments',[__CLASS__,'my_comments']);
  add_shortcode('avam_memorial_comment_moderation',[__CLASS__,'memorial_comment_moderation']);
  add_action('template_redirect',[__CLASS__,'protect_private_memorial']);
  add_action('wp_enqueue_scripts',[__CLASS__,'assets']);
  add_action('avam_daily_anniversary_check',[__CLASS__,'send_anniversary_reminders']);
  if (!wp_next_scheduled('avam_daily_anniversary_check')) wp_schedule_event(time()+300,'daily','avam_daily_anniversary_check');
 }
 public static function ensure_pages() {
  foreach ([['profile','تنظیمات حساب','[avam_profile_settings]'],['my-comments','دیدگاه‌های من','[avam_my_comments]'],['manage-memorial-comments','مدیریت دیدگاه‌های یادبود','[avam_memorial_comment_moderation]']] as $page) {
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
  if (get_option('avam_comments_enabled','1')!=='1' || get_post_status($post_id)!=='publish') return false;
  // Let a memorial's owner comment on their own post even if the post-level
  // WordPress discussion flag was closed. Other visitors still respect that flag.
  $is_owner=is_user_logged_in() && (int)get_post_field('post_author',$post_id)===get_current_user_id();
  return $open || $is_owner;
 }
 public static function comment_form_defaults($defaults) {
  $avam_cc=get_option('avam_control_center',[]); $max_length=is_array($avam_cc)?min(20000,max(1,absint($avam_cc['comment_max_length']??5000))):5000;
  $defaults['title_reply']='دعایی یا خاطره‌ای بنویسید';
  $defaults['label_submit']='ثبت پیام';
  $defaults['comment_notes_before']='';
  $defaults['comment_notes_after']='';
  $defaults['logged_in_as']='';
  $defaults['comment_field']='<p class="comment-form-comment"><label for="comment">پیام شما</label><textarea id="comment" name="comment" rows="5" maxlength="'.esc_attr($max_length).'" required placeholder="با احترام، خاطره یا دعایی از خود به یادگار بگذارید…"></textarea></p><input type="hidden" name="avam_comment_nonce" value="'.esc_attr(wp_create_nonce('avam_submit_comment')).'"><div class="avam-comment-ajax-notice" role="status" aria-live="polite"></div>';
  return $defaults;
 }
 public static function submit_comment() {
  $nonce_raw=$_POST['avam_comment_nonce']??'';
  if (!is_string($nonce_raw) || !wp_verify_nonce(sanitize_text_field(wp_unslash($nonce_raw)),'avam_submit_comment')) wp_send_json_error(['message'=>'نشست شما منقضی شده است؛ صفحه را تازه‌سازی کنید.'],403);
  foreach(['comment','author','email','url'] as $field) if(isset($_POST[$field]) && !is_string($_POST[$field])) wp_send_json_error(['message'=>'یکی از فیلدهای پیام معتبر نیست.'],422);
  $post_id=absint($_POST['comment_post_ID']??0);
  $post=get_post($post_id);
  if (!$post || $post->post_type!=='avam_memorial' || $post->post_status!=='publish' || !comments_open($post_id)) wp_send_json_error(['message'=>'ثبت پیام برای این یادبود ممکن نیست.'],422);
  if (get_post_meta($post_id,'avam_visibility',true)==='private' && !current_user_can('manage_options') && (int)$post->post_author!==get_current_user_id()) wp_send_json_error(['message'=>'دسترسی به این یادبود مجاز نیست.'],404);
  $content=trim(wp_unslash($_POST['comment']??''));
  $avam_cc=get_option('avam_control_center',[]); $max_length=is_array($avam_cc)?min(20000,max(1,absint($avam_cc['comment_max_length']??5000))):5000;
  if ($content==='' || mb_strlen($content)>$max_length) wp_send_json_error(['message'=>'متن پیام باید بین ۱ تا '.number_format_i18n($max_length).' نویسه باشد.'],422);
  $user=wp_get_current_user();
  $author=is_user_logged_in()?$user->display_name:sanitize_text_field(wp_unslash($_POST['author']??''));
  $email=is_user_logged_in()?$user->user_email:sanitize_email(wp_unslash($_POST['email']??''));
  if (!$author || !is_email($email)) wp_send_json_error(['message'=>'لطفاً نام و ایمیل معتبر وارد کنید.'],422);
  // Reject rapid repeat submissions before WordPress inserts a comment. The versioned
  // key intentionally ignores stale transients created by the previous spam-based limiter.
  $ip=sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']??''));
  $rate_key='avam_comment_submit_rate_v2_'.hash('sha256',$ip.'|'.$post_id);
  $rate_seconds=max(10,min(300,absint(get_option('avam_rate_limit_seconds',30))));
  if (get_transient($rate_key)) {
   wp_send_json_error(['message'=>'برای جلوگیری از ارسال پیام‌های پیاپی، لطفاً حدود '.number_format_i18n($rate_seconds).' ثانیه بعد دوباره تلاش کنید.'],429);
  }
  $commentdata=[
   'comment_post_ID'=>$post_id,
   'comment_content'=>sanitize_textarea_field($content),
   'comment_parent'=>absint($_POST['comment_parent']??0),
   'comment_author'=>$author,
   'comment_author_email'=>$email,
   'comment_author_url'=>esc_url_raw(wp_unslash($_POST['url']??'')),
   'user_id'=>get_current_user_id(),
   'comment_type'=>'comment',
   'comment_agent'=>sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']??'')),
   'comment_author_IP'=>sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']??'')),
  ];
  $comment_id=wp_new_comment(wp_slash($commentdata),true);
  if (is_wp_error($comment_id)) {
   if (class_exists('AVAM_Logger')) AVAM_Logger::log('error','comment_creation_failed',['post_id'=>$post_id,'code'=>$comment_id->get_error_code(),'component'=>'comments']);
   wp_send_json_error(['message'=>$comment_id->get_error_message()?:'ثبت پیام انجام نشد.'],422);
  }
  $comment=get_comment($comment_id);
  if (!$comment || (string)$comment->comment_approved==='spam') {
   wp_send_json_error(['message'=>'پیام توسط فیلتر هرزنامه پذیرفته نشد. لطفاً متن را بررسی کنید و در صورت تکرار، با مدیر سایت تماس بگیرید.'],422);
  }
  // Start the cooldown only after a comment was actually created and not marked as spam.
  set_transient($rate_key,1,$rate_seconds);
  $pending=(string)$comment->comment_approved!=='1';
  $html='';
  if (!$pending) {
   $html='<li id="comment-'.(int)$comment_id.'" class="comment"><article class="comment-body"><footer class="comment-meta"><b class="fn">'.esc_html(get_comment_author($comment)).'</b></footer><div class="comment-content"><p>'.nl2br(esc_html($comment->comment_content)).'</p></div></article></li>';
  }
  wp_send_json_success(['message'=>$pending?'پیام شما ثبت شد و پس از تأیید نمایش داده می‌شود.':'پیام شما با موفقیت منتشر شد.','pending'=>$pending,'html'=>$html,'id'=>(int)$comment_id]);
 }
 public static function moderate_comments($approved,$commentdata) {
  if (empty($commentdata['comment_post_ID']) || get_post_type((int)$commentdata['comment_post_ID'])!=='avam_memorial') return $approved;
  // Apply moderation here only. Rate limiting belongs in submit_comment(), where it can
  // return a clear 429 response instead of silently converting a valid comment to spam.
  if (get_option('avam_comments_require_moderation','1')==='1' && !current_user_can('moderate_comments')) return 0;
  return $approved;
 }
 public static function exclude_private_from_public_queries($query) {
  if (is_admin() || !$query->is_main_query()) return;
  if (!$query->is_post_type_archive('avam_memorial') && !$query->is_search() && !$query->is_home() && !$query->is_feed()) return;
  $types=$query->get('post_type');
  if ($query->is_search() && $types && !in_array('avam_memorial',(array)$types,true)) return;
  if (!$types && $query->is_search()) { $types=['post','page','avam_memorial']; $query->set('post_type',$types); }
  if ($types && !in_array('avam_memorial',(array)$types,true)) return;
  $existing=$query->get('meta_query'); if (!is_array($existing)) $existing=[];
  $existing[]=['relation'=>'OR',['key'=>'avam_visibility','compare'=>'NOT EXISTS'],['key'=>'avam_visibility','value'=>'private','compare'=>'!=']];
  $query->set('meta_query',$existing);
 }
 public static function exclude_private_rest_query($args,$request) {
  if (!is_array($args)) return $args;
  $existing=isset($args['meta_query']) && is_array($args['meta_query']) ? $args['meta_query'] : [];
  $existing[]=['relation'=>'OR',['key'=>'avam_visibility','compare'=>'NOT EXISTS'],['key'=>'avam_visibility','value'=>'private','compare'=>'!=']];
  $args['meta_query']=$existing;
  return $args;
 }
 public static function exclude_private_rest_media_clauses($clauses,$query) {
  if (!defined('REST_REQUEST') || !REST_REQUEST || current_user_can('manage_options') || !is_object($query) || $query->get('post_type')!=='attachment' || !is_array($clauses)) return $clauses;
  global $wpdb;
  $clauses['where'] .= $wpdb->prepare(
   " AND NOT EXISTS (
      SELECT 1 FROM {$wpdb->posts} avam_private_media_parent
      INNER JOIN {$wpdb->postmeta} avam_private_media_meta
       ON avam_private_media_meta.post_id=avam_private_media_parent.ID
       AND avam_private_media_meta.meta_key=%s AND avam_private_media_meta.meta_value=%s
      WHERE avam_private_media_parent.ID={$wpdb->posts}.post_parent
       AND avam_private_media_parent.post_type=%s
    )",
   'avam_visibility','private','avam_memorial'
  );
  return $clauses;
 }
 public static function filter_private_rest_media($response,$post,$request) {
  if (is_wp_error($response) || empty($post->post_parent) || get_post_type((int)$post->post_parent)!=='avam_memorial') return $response;
  if (get_post_meta((int)$post->post_parent,'avam_visibility',true)!=='private') return $response;
  if (current_user_can('manage_options') || (int)get_post_field('post_author',(int)$post->post_parent)===get_current_user_id() && is_user_logged_in()) return $response;
  return new WP_Error('rest_post_invalid_id','رسانه پیدا نشد.',['status'=>404]);
 }
 public static function filter_private_rest_comment($response,$comment,$request) {
  if (is_wp_error($response)) return $response;
  $post=get_post((int)$comment->comment_post_ID);
  if (!$post || $post->post_type!=='avam_memorial' || get_post_meta($post->ID,'avam_visibility',true)!=='private') return $response;
  if (current_user_can('manage_options') || (is_user_logged_in() && (int)$post->post_author===get_current_user_id())) return $response;
  return new WP_Error('rest_comment_invalid_id','دیدگاه پیدا نشد.',['status'=>404]);
 }
 public static function exclude_private_rest_comments($clauses,$query) {
  if (!defined('REST_REQUEST') || !REST_REQUEST || current_user_can('manage_options') || !is_array($clauses)) return $clauses;
  global $wpdb;
  $clauses['where'] .= $wpdb->prepare(
   " AND NOT EXISTS (
      SELECT 1 FROM {$wpdb->posts} avam_private_post
      INNER JOIN {$wpdb->postmeta} avam_private_meta
       ON avam_private_meta.post_id=avam_private_post.ID
       AND avam_private_meta.meta_key=%s AND avam_private_meta.meta_value=%s
      WHERE avam_private_post.ID={$wpdb->comments}.comment_post_ID
       AND avam_private_post.post_type=%s
    )",
   'avam_visibility','private','avam_memorial'
  );
  return $clauses;
 }
 public static function filter_private_rest_memorial($response,$post,$request) {
  if (is_wp_error($response) || get_post_meta($post->ID,'avam_visibility',true)!=='private') return $response;
  if (current_user_can('manage_options') || ((int)$post->post_author===get_current_user_id() && is_user_logged_in())) return $response;
  return new WP_Error('rest_post_invalid_id','یادبود پیدا نشد.',['status'=>404]);
 }
 public static function protect_private_memorial() {
  if (!is_singular('avam_memorial')) return;
  $id=get_queried_object_id(); $visibility=get_post_meta($id,'avam_visibility',true);
  if ($visibility==='private' && !current_user_can('manage_options') && (int)get_post_field('post_author',$id)!==get_current_user_id()) {
   status_header(404); nocache_headers(); include get_404_template(); exit;
  }
 }
 public static function assets() {
  $settings=get_option('avam_control_center',[]);
  $cache_busting=!is_array($settings)||($settings['cache_busting']??'1')==='1';
  $script_path=dirname(__DIR__).'/assets/js/features.js';
  $script_version=$cache_busting && is_file($script_path)?(string)filemtime($script_path):'1.11.8';
  wp_enqueue_script('avam-features',plugins_url('assets/js/features.js',dirname(__DIR__).'/aramgah-virtual.php'),['jquery'],$script_version,true);
  wp_localize_script('avam-features','AVAM_FEATURES',['ajax'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('avam_features')]);
 }
 private static function is_public_memorial($id) {
  $post=get_post($id);
  return $post instanceof WP_Post
   && $post->post_type==='avam_memorial'
   && $post->post_status==='publish'
   && get_post_meta($id,'avam_visibility',true)!=='private';
 }
 public static function react() {
  $nonce_raw=$_POST['nonce']??''; $kind_raw=$_POST['kind']??'';
  if (!is_string($nonce_raw) || !wp_verify_nonce(sanitize_text_field(wp_unslash($nonce_raw)),'avam_features')) wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  if (!is_string($kind_raw)) wp_send_json_error(['message'=>'نوع همراهی نامعتبر است.'],422);
  $id=absint($_POST['id']??0); $kind=sanitize_key(wp_unslash($kind_raw));
  if (!$id || get_post_type($id)!=='avam_memorial' || !in_array($kind,['candle','flower'],true)) wp_send_json_error(['message'=>'درخواست نامعتبر است.'],422);
  if (!self::is_public_memorial($id)) wp_send_json_error(['message'=>'یادبود پیدا نشد.'],404);
  $ip=$_SERVER['REMOTE_ADDR']??'unknown'; $key='avam_react_'.hash('sha256',$ip.'|'.$id.'|'.$kind);
  if (get_transient($key)) wp_send_json_error(['message'=>'از همراهی شما سپاسگزاریم؛ این یادبود را قبلاً همراهی کرده‌اید.'],429);
  $user_id=get_current_user_id();
  $log=get_post_meta($id,'avam_reaction_log',true);
  if (!is_array($log)) $log=[];
  if ($user_id) {
   foreach ($log as $entry) {
    if (is_array($entry) && (int)($entry['user_id']??0)===$user_id && ($entry['kind']??'')===$kind) {
     wp_send_json_error(['message'=>'از همراهی شما سپاسگزاریم؛ قبلاً این کار را با حساب خود انجام داده‌اید.'],429);
    }
   }
  }
  set_transient($key,1,DAY_IN_SECONDS);
  $user=$user_id?get_userdata($user_id):false;
  $name=$user?($user->display_name?:$user->user_login):'کاربر مهمان';
  $log[]=['kind'=>$kind,'user_id'=>$user_id,'name'=>sanitize_text_field($name),'time'=>current_time('mysql')];
  update_post_meta($id,'avam_reaction_log',$log);
  $meta='avam_'.$kind.'_count'; $count=max(0,(int)get_post_meta($id,$meta,true))+1; update_post_meta($id,$meta,$count);
  wp_send_json_success(['count'=>$count]);
 }
 public static function reaction_list() {
  $nonce_raw=$_POST['nonce']??'';
  if (!is_string($nonce_raw) || !wp_verify_nonce(sanitize_text_field(wp_unslash($nonce_raw)),'avam_features')) wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  $id=absint($_POST['id']??0); $kind_raw=$_POST['kind']??'';
  if (!$id || !is_string($kind_raw)) wp_send_json_error(['message'=>'درخواست نامعتبر است.'],422);
  $kind=sanitize_key(wp_unslash($kind_raw));
  if (!in_array($kind,['candle','flower'],true)) wp_send_json_error(['message'=>'نوع همراهی نامعتبر است.'],422);
  if (!self::is_public_memorial($id)) wp_send_json_error(['message'=>'یادبود پیدا نشد.'],404);
  $log=get_post_meta($id,'avam_reaction_log',true);
  if (!is_array($log)) $log=[];
  $items=[];
  foreach ($log as $entry) {
   if (!is_array($entry) || ($entry['kind']??'')!==$kind) continue;
   $name=isset($entry['name']) && is_string($entry['name']) ? sanitize_text_field($entry['name']) : '';
   if ($name!=='') $items[]=['name'=>$name];
  }
  $total=max(0,(int)get_post_meta($id,'avam_'.$kind.'_count',true));
  wp_send_json_success(['items'=>$items,'total'=>$total,'unlisted'=>max(0,$total-count($items))]);
 }
 public static function report() {
  $nonce_raw=$_POST['nonce']??''; $reason_raw=$_POST['reason']??'';
  if (!is_string($nonce_raw) || !wp_verify_nonce(sanitize_text_field(wp_unslash($nonce_raw)),'avam_features')) wp_send_json_error(['message'=>'درخواست نامعتبر است.'],403);
  if (!is_string($reason_raw)) wp_send_json_error(['message'=>'دلیل گزارش معتبر نیست.'],422);
  $id=absint($_POST['id']??0); if (!$id || get_post_type($id)!=='avam_memorial') wp_send_json_error(['message'=>'یادبود پیدا نشد.'],404);
  if (!self::is_public_memorial($id)) wp_send_json_error(['message'=>'یادبود پیدا نشد.'],404);
  $reason=sanitize_textarea_field(wp_unslash($reason_raw)); if (mb_strlen($reason)<5 || mb_strlen($reason)>1000) wp_send_json_error(['message'=>'لطفاً دلیل گزارش را در ۵ تا ۱۰۰۰ نویسه بنویسید.'],422);
  $ip=$_SERVER['REMOTE_ADDR']??'unknown'; $key='avam_report_'.hash('sha256',$ip.'|'.$id);
  if (get_transient($key)) wp_send_json_error(['message'=>'برای این یادبود قبلاً گزارشی از شما ثبت شده است.'],429);
  set_transient($key,1,DAY_IN_SECONDS);
  $count=(int)get_post_meta($id,'avam_reports_count',true)+1; update_post_meta($id,'avam_reports_count',$count); update_post_meta($id,'avam_last_report',current_time('mysql'));
  $email=sanitize_email(get_option('avam_report_email',get_option('admin_email')));
  if (is_email($email)) wp_mail($email,'گزارش یادبود: '.get_the_title($id),"یادبود: ".get_permalink($id)."\nدلیل: ".$reason."\nتعداد گزارش‌ها: ".$count);
  wp_send_json_success(['message'=>'گزارش شما ثبت شد و برای بررسی در پنل ذخیره شد.']);
 }
 public static function filter_user_avatar_url($url,$id_or_email,$args=[]) {
  $user=null;
  if($id_or_email instanceof WP_User)$user=$id_or_email;
  elseif($id_or_email instanceof WP_Comment){$user=$id_or_email->user_id?get_userdata((int)$id_or_email->user_id):null;if(!$user&&$id_or_email->comment_author_email)$user=get_user_by('email',$id_or_email->comment_author_email);}
  elseif(is_numeric($id_or_email))$user=get_userdata((int)$id_or_email);
  elseif(is_string($id_or_email)&&is_email($id_or_email))$user=get_user_by('email',$id_or_email);
  if(!$user)return $url;
  $attachment_id=absint(get_user_meta($user->ID,'avam_avatar_id',true));
  $custom=$attachment_id?wp_get_attachment_image_url($attachment_id,'thumbnail'):false;
  return $custom?:$url;
 }
 public static function profile() {
  if (!is_user_logged_in()) return '<div class="avam-card"><p>برای تنظیمات حساب وارد شوید.</p><a class="avam-btn" href="'.esc_url(avam_login_url()).'">ورود</a></div>';
  $uid=get_current_user_id(); $user=get_userdata($uid); $notice='';
  if ('POST'===($_SERVER['REQUEST_METHOD']??'') && isset($_POST['avam_profile_action'])) {
   $nonce_raw=$_POST['avam_profile_nonce']??'';
   $action_raw=$_POST['avam_profile_action']??'';
   if (!is_string($nonce_raw) || !wp_verify_nonce(sanitize_text_field(wp_unslash($nonce_raw)),'avam_profile_'.$uid) || !is_string($action_raw)) $notice='<div class="avam-notice avam-error">درخواست نامعتبر است.</div>';
   else {
    $action=sanitize_key(wp_unslash($action_raw));
    if ($action==='profile') {
     $name_raw=$_POST['display_name']??''; $email_raw=$_POST['email']??'';
     $name=is_string($name_raw)?sanitize_text_field(wp_unslash($name_raw)):'';
     $email=is_string($email_raw)?sanitize_email(wp_unslash($email_raw)):'';
     if (!$name || mb_strlen($name)>80 || !is_email($email) || (email_exists($email) && strtolower($email)!==strtolower($user->user_email))) $notice='<div class="avam-notice avam-error">نام یا ایمیل معتبر نیست یا قبلاً استفاده شده است.</div>';
     else {
      $res=wp_update_user(['ID'=>$uid,'display_name'=>$name,'user_email'=>$email]);
      if(is_wp_error($res)) $notice='<div class="avam-notice avam-error">ذخیره اطلاعات انجام نشد.</div>';
      else {
       update_user_meta($uid,'avam_anniversary_reminders',isset($_POST['anniversary_reminders'])?'1':'0');
       if(isset($_FILES['avatar']) && is_array($_FILES['avatar']) && isset($_FILES['avatar']['name']) && is_string($_FILES['avatar']['name']) && $_FILES['avatar']['name']!==''){
        if(!empty($_FILES['avatar']['error'])&&(int)$_FILES['avatar']['error']!==UPLOAD_ERR_OK) $notice='<div class="avam-notice avam-error">بارگذاری تصویر پروفایل ناموفق بود.</div>';
        elseif((int)($_FILES['avatar']['size']??0)>8*1024*1024) $notice='<div class="avam-notice avam-error">حجم تصویر پروفایل باید حداکثر ۸ مگابایت باشد.</div>';
        else {
         $avatar_tmp=$_FILES['avatar']['tmp_name']??''; $avatar_name=$_FILES['avatar']['name']??'';
         $check=(is_string($avatar_tmp) && $avatar_tmp!=='' && is_string($avatar_name)) ? wp_check_filetype_and_ext($avatar_tmp,$avatar_name) : [];
         if(empty($check['type'])||!in_array($check['type'],['image/jpeg','image/png','image/webp'],true)) $notice='<div class="avam-notice avam-error">فقط تصویر JPG، PNG یا WebP مجاز است.</div>';
         else {
          require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';
          $avatar_id=media_handle_upload('avatar',0);
          if(is_wp_error($avatar_id)) $notice='<div class="avam-notice avam-error">ذخیره تصویر پروفایل انجام نشد.</div>';
          else {update_user_meta($uid,'avam_avatar_id',(int)$avatar_id);$notice='<div class="avam-notice">اطلاعات و تصویر پروفایل ذخیره شد.</div>';}
         }
        }
       } else $notice='<div class="avam-notice">اطلاعات حساب ذخیره شد.</div>';
       $user=get_userdata($uid);
      }
     }
    } elseif ($action==='password') {
     $current_raw=$_POST['current_password']??''; $new_raw=$_POST['new_password']??''; $confirm_raw=$_POST['confirm_password']??'';
     if (!is_string($current_raw) || !is_string($new_raw) || !is_string($confirm_raw)) {
      $notice='<div class="avam-notice avam-error">اطلاعات رمز عبور معتبر نیست.</div>';
     } else {
      $current=wp_unslash($current_raw); $new=wp_unslash($new_raw); $confirm=wp_unslash($confirm_raw);
      if (!wp_check_password($current,$user->user_pass,$uid) || strlen($new)<10 || $new!==$confirm) $notice='<div class="avam-notice avam-error">رمز فعلی نادرست است یا رمز جدید (حداقل ۱۰ نویسه) مطابقت ندارد.</div>';
      else { wp_set_password($new,$uid); wp_set_auth_cookie($uid,true,is_ssl()); $user=get_userdata($uid); $notice='<div class="avam-notice">رمز عبور تغییر کرد.</div>'; }
     }
    }
   }
  }
  ob_start(); ?><section class="avam-card avam-profile-settings" dir="rtl"><h1>تنظیمات حساب</h1><p>اطلاعات نمایشی و امنیت حساب خود را مدیریت کنید.</p><?php echo $notice; ?><form method="post" enctype="multipart/form-data" class="avam-form avam-profile-form"><input type="hidden" name="avam_profile_action" value="profile"><?php wp_nonce_field('avam_profile_'.$uid,'avam_profile_nonce'); ?><div class="avam-profile-avatar-field"><div class="avam-profile-avatar-preview" data-profile-avatar-preview><?php $avatar_id=absint(get_user_meta($uid,'avam_avatar_id',true)); $avatar_url=$avatar_id?wp_get_attachment_image_url($avatar_id,'thumbnail'):get_avatar_url($uid); ?><img src="<?php echo esc_url($avatar_url); ?>" alt="تصویر پروفایل فعلی"></div><div class="avam-field"><label for="avam-profile-avatar">تصویر پروفایل</label><input id="avam-profile-avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" aria-describedby="avam-profile-avatar-help"><small id="avam-profile-avatar-help">JPG، PNG یا WebP؛ حداکثر ۸ مگابایت</small></div></div><div class="avam-field"><label for="avam-profile-display-name">نام نمایشی</label><input id="avam-profile-display-name" name="display_name" required maxlength="80" value="<?php echo esc_attr($user->display_name); ?>"></div><div class="avam-field"><label for="avam-profile-email">ایمیل</label><input id="avam-profile-email" type="email" name="email" required value="<?php echo esc_attr($user->user_email); ?>"></div><label class="avam-reminder-optin"><input type="checkbox" name="anniversary_reminders" value="1" <?php checked(get_user_meta($uid,'avam_anniversary_reminders',true),'1'); ?>> یادآوری سالانه سالگرد درگذشت از طریق ایمیل</label><button class="avam-btn" type="submit">ذخیره پروفایل</button></form><hr><h2>تغییر رمز عبور</h2><form method="post" class="avam-form"><input type="hidden" name="avam_profile_action" value="password"><?php wp_nonce_field('avam_profile_'.$uid,'avam_profile_nonce'); ?><div class="avam-field"><label for="avam-current-password">رمز فعلی</label><input id="avam-current-password" type="password" name="current_password" autocomplete="current-password" required></div><div class="avam-field"><label for="avam-new-password">رمز جدید</label><input id="avam-new-password" type="password" name="new_password" minlength="10" autocomplete="new-password" required></div><div class="avam-field"><label for="avam-confirm-password">تکرار رمز جدید</label><input id="avam-confirm-password" type="password" name="confirm_password" minlength="10" autocomplete="new-password" required></div><button class="avam-btn" type="submit">تغییر رمز</button></form></section><?php return ob_get_clean();
 }
 public static function memorial_comment_moderation() {
  if (!is_user_logged_in()) return '<section class="avam-card" dir="rtl"><h1>مدیریت دیدگاه‌های یادبود</h1><p>برای مدیریت دیدگاه‌ها ابتدا وارد حساب شوید.</p><a class="avam-btn" href="'.esc_url(avam_login_url()).'">ورود به حساب</a></section>';
  $uid=get_current_user_id(); $notice='';
  if (($_SERVER['REQUEST_METHOD']??'')==='POST' && isset($_POST['avam_owner_comment_action'])) {
   $comment_id=absint($_POST['comment_id']??0); $action=sanitize_key(wp_unslash($_POST['avam_owner_comment_action']));
   $nonce=$_POST['avam_owner_comment_nonce']??'';
   $comment=get_comment($comment_id); $post=$comment?get_post((int)$comment->comment_post_ID):null;
   if (!is_string($nonce) || !wp_verify_nonce(sanitize_text_field(wp_unslash($nonce)),'avam_owner_moderate_comment_'.$comment_id)) $notice='<div class="avam-notice avam-error">درخواست منقضی یا نامعتبر است؛ صفحه را تازه کنید.</div>';
   elseif (!$comment || !$post || $post->post_type!=='avam_memorial' || (int)$post->post_author!==$uid || $post->post_status!=='publish' || (string)$comment->comment_approved!=='0' || !in_array($action,['approve','reject'],true)) $notice='<div class="avam-notice avam-error">این دیدگاه قابل مدیریت نیست یا به یادبود شما تعلق ندارد.</div>';
   else {
    $updated=wp_set_comment_status($comment_id,$action==='approve'?'approve':'trash');
    $notice=$updated?'<div class="avam-notice">'.($action==='approve'?'دیدگاه تأیید و منتشر شد.':'دیدگاه رد و به زباله‌دان منتقل شد.').'</div>':'<div class="avam-notice avam-error">تغییر وضعیت دیدگاه انجام نشد.</div>';
   }
  }
  $memorial_ids=get_posts(['post_type'=>'avam_memorial','post_status'=>'publish','author'=>$uid,'numberposts'=>-1,'fields'=>'ids','no_found_rows'=>true]);
  $pending=$memorial_ids?get_comments(['post__in'=>array_map('absint',$memorial_ids),'status'=>'hold','number'=>200,'orderby'=>'comment_date_gmt','order'=>'DESC']):[];
  ob_start(); ?>
  <section class="avam-card avam-my-comments" dir="rtl">
   <header class="avam-my-comments-head"><div><span>ویژهٔ صاحب یادبود</span><h1>مدیریت دیدگاه‌ها</h1><p>دیدگاه‌های در انتظار تأیید روی یادبودهای شما. با تأیید، پیام برای بازدیدکنندگان هم نمایش داده می‌شود؛ با رد، به زباله‌دان منتقل می‌شود.</p></div><strong><?php echo esc_html(count($pending)); ?> در انتظار</strong></header>
   <?php echo $notice; ?>
   <?php if (!$pending): ?><div class="avam-my-comments-empty"><h2>دیدگاه در انتظاری وجود ندارد</h2><p>دیدگاه‌های تازه‌ای که نیاز به بررسی داشته باشند در این صفحه ظاهر می‌شوند.</p><a class="avam-btn" href="<?php echo esc_url(avam_memorials_url()); ?>">مشاهده یادبودها</a></div>
   <?php else: ?><ol class="avam-my-comments-list">
    <?php foreach($pending as $comment): $post=get_post((int)$comment->comment_post_ID); if(!$post || (int)$post->post_author!==$uid) continue; ?>
     <li class="avam-my-comment"><div class="avam-my-comment-meta"><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a><time datetime="<?php echo esc_attr(get_comment_date('c',$comment)); ?>"><?php echo esc_html(get_comment_date('Y/m/d',$comment)); ?></time><span class="avam-comment-status is-pending">در انتظار تأیید</span></div>
      <p><strong><?php echo esc_html(get_comment_author($comment)); ?>:</strong> <?php echo esc_html(wp_trim_words(wp_strip_all_tags($comment->comment_content),70,'…')); ?></p>
      <div class="avam-owner-comment-actions" style="display:flex;gap:10px;flex-wrap:wrap">
       <form method="post"><?php wp_nonce_field('avam_owner_moderate_comment_'.(int)$comment->comment_ID,'avam_owner_comment_nonce'); ?><input type="hidden" name="comment_id" value="<?php echo (int)$comment->comment_ID; ?>"><button class="avam-btn" type="submit" name="avam_owner_comment_action" value="approve">تأیید و انتشار</button></form>
       <form method="post" onsubmit="return confirm('این دیدگاه رد و به زباله‌دان منتقل شود؟')"><?php wp_nonce_field('avam_owner_moderate_comment_'.(int)$comment->comment_ID,'avam_owner_comment_nonce'); ?><input type="hidden" name="comment_id" value="<?php echo (int)$comment->comment_ID; ?>"><button class="avam-btn" type="submit" name="avam_owner_comment_action" value="reject">رد دیدگاه</button></form>
      </div>
     </li>
    <?php endforeach; ?>
   </ol><?php endif; ?>
  </section>
  <?php return ob_get_clean();
 }

 public static function my_comments() {
  if (!is_user_logged_in()) {
   return '<section class="avam-card avam-my-comments" dir="rtl"><h1>دیدگاه‌های من</h1><p>برای مشاهده پیام‌هایی که نوشته‌اید، ابتدا وارد حساب شوید.</p><a class="avam-btn" href="'.esc_url(avam_login_url()).'">ورود به حساب</a></section>';
  }
  $uid=get_current_user_id();
  $approved=get_comments(['user_id'=>$uid,'status'=>'approve','number'=>100,'orderby'=>'comment_date_gmt','order'=>'DESC']);
  $pending=get_comments(['user_id'=>$uid,'status'=>'hold','number'=>100,'orderby'=>'comment_date_gmt','order'=>'DESC']);
  $comments=array_merge($approved,$pending);
  usort($comments,function($a,$b){return strcmp($b->comment_date_gmt,$a->comment_date_gmt);});
  $seen=[]; $visible=[];
  foreach($comments as $comment) {
   if(isset($seen[$comment->comment_ID]))continue;
   $seen[$comment->comment_ID]=true;
   $post=get_post((int)$comment->comment_post_ID);
   if(!$post || $post->post_type!=='avam_memorial' || $post->post_status!=='publish')continue;
   $private=get_post_meta($post->ID,'avam_visibility',true)==='private';
   if($private && !current_user_can('manage_options') && (int)$post->post_author!==$uid)continue;
   $visible[]=$comment;
  }
  ob_start(); ?>
  <section class="avam-card avam-my-comments" dir="rtl">
   <header class="avam-my-comments-head"><div><span>یادگارهای شما</span><h1>دیدگاه‌های من</h1><p>پیام‌ها و دعاهایی که در یادبودها نوشته‌اید؛ پیام‌های در انتظار تأیید فقط برای شما نمایش داده می‌شوند.</p></div><strong><?php echo esc_html(count($visible)); ?> پیام</strong></header>
   <?php if(!$visible): ?>
    <div class="avam-my-comments-empty"><span aria-hidden="true"><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 8.7c0 4.4-8.5 10-8.5 10s-8.5-5.6-8.5-10a4.4 4.4 0 0 1 8.5-1.2 4.4 4.4 0 0 1 8.5 1.2Z"/></svg></span><h2>هنوز پیامی ننوشته‌اید</h2><p>می‌توانید وارد صفحه یک یادبود شوید و دعایی یا خاطره‌ای به یادگار بگذارید.</p><a class="avam-btn" href="<?php echo esc_url(avam_memorials_url()); ?>">مشاهده یادبودها</a></div>
   <?php else: ?>
    <ol class="avam-my-comments-list">
    <?php foreach($visible as $comment):
      $post=get_post((int)$comment->comment_post_ID);
      $is_pending=(string)$comment->comment_approved==='0';
    ?>
     <li class="avam-my-comment">
      <div class="avam-my-comment-meta"><a href="<?php echo esc_url(get_permalink($post).'#comment-'.(int)$comment->comment_ID); ?>"><?php echo esc_html(get_the_title($post)); ?></a><time datetime="<?php echo esc_attr(get_comment_date('c',$comment)); ?>"><?php echo esc_html(get_comment_date('Y/m/d',$comment)); ?></time><span class="avam-comment-status <?php echo $is_pending?'is-pending':'is-published'; ?>"><?php echo $is_pending?'در انتظار تأیید':'منتشر شده'; ?></span></div>
      <p><?php echo esc_html(wp_trim_words(wp_strip_all_tags($comment->comment_content),45,'…')); ?></p>
      <a class="avam-my-comment-open" href="<?php echo esc_url(get_permalink($post).'#comment-'.(int)$comment->comment_ID); ?>">مشاهده در یادبود ←</a>
     </li>
    <?php endforeach; ?>
    </ol>
   <?php endif; ?>
  </section>
  <?php return ob_get_clean();
 }
 public static function send_anniversary_reminders() {
  $today=time(); $jalali=self::gregorian_to_jalali((int)wp_date('Y',$today),(int)wp_date('n',$today),(int)wp_date('j',$today));
  // Process bounded batches so the daily cron does not load every memorial into memory.
  $offset=0;
  do {
   $posts=get_posts([
    'post_type'=>'avam_memorial','post_status'=>'publish','numberposts'=>100,'offset'=>$offset,
    'orderby'=>'ID','order'=>'ASC','no_found_rows'=>true,
    'meta_query'=>[['key'=>'avam_death','compare'=>'EXISTS']]
   ]);
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
   $offset+=count($posts);
  } while (count($posts)===100);
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
