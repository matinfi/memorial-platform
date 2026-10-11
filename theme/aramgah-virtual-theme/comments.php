<?php
if (!defined('ABSPATH')) exit;
if (post_password_required()) return;

$avam_comments_post_id = (int) get_queried_object_id();
if (!$avam_comments_post_id) $avam_comments_post_id = (int) get_the_ID();
$avam_visible_comments = get_comments([
 'post_id' => $avam_comments_post_id,
 'status' => 'approve',
 'orderby' => 'comment_date_gmt',
 'order' => 'ASC',
 'hierarchical' => 'threaded',
 'number' => 0,
]);
$avam_pending_comments = [];
if (is_user_logged_in()) {
 $avam_pending_comments = get_comments([
  'post_id' => $avam_comments_post_id,
  'status' => 'hold',
  'user_id' => get_current_user_id(),
  'orderby' => 'comment_date_gmt',
  'order' => 'ASC',
  'number' => 0,
 ]);
} else {
 $avam_current_commenter = wp_get_current_commenter();
 if (!empty($avam_current_commenter['comment_author_email']) && is_email($avam_current_commenter['comment_author_email'])) {
  $avam_pending_comments = get_comments([
   'post_id' => $avam_comments_post_id,
   'status' => 'hold',
   'author_email' => $avam_current_commenter['comment_author_email'],
   'orderby' => 'comment_date_gmt',
   'order' => 'ASC',
   'number' => 0,
  ]);
 }
}
?>
<div id="comments" class="avam-social-comments">
 <?php if ($avam_visible_comments): ?>
  <h3 class="comments-title"><?php echo esc_html(number_format_i18n(count($avam_visible_comments)) . ' پیام'); ?></h3>
  <?php echo wp_list_comments(['style'=>'ol','short_ping'=>true,'avatar_size'=>40,'max_depth'=>4,'echo'=>false], $avam_visible_comments); ?>
 <?php else: ?>
  <p class="avam-social-comments-empty"><?php echo comments_open() ? 'هنوز پیامی ثبت نشده است. اولین دعا یا خاطره را شما بنویسید.' : 'هنوز پیام تأییدشده‌ای برای این یادبود وجود ندارد.'; ?></p>
 <?php endif; ?>

 <?php if ($avam_pending_comments): ?>
  <p class="avam-social-comments-pending" role="status">پیام در انتظار تأیید شما ثبت شده است و فقط برای شما نمایش داده می‌شود.</p>
 <?php endif; ?>

 <?php if (comments_open()) comment_form(); ?>
</div>
