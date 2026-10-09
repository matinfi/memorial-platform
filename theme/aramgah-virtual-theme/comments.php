<?php
if (!defined('ABSPATH')) exit;
if (post_password_required()) return;
?>
<div id="comments" class="avam-social-comments">
 <?php if (have_comments()): ?>
  <h3 class="comments-title"><?php echo esc_html(number_format_i18n(get_comments_number()).' پیام'); ?></h3>
  <ol class="comment-list">
   <?php wp_list_comments(['style'=>'ol','short_ping'=>true,'avatar_size'=>40,'max_depth'=>4]); ?>
  </ol>
  <?php the_comments_navigation(); ?>
 <?php elseif (comments_open()): ?>
  <p class="avam-social-comments-empty">هنوز پیامی ثبت نشده است. اولین دعا یا خاطره را شما بنویسید.</p>
 <?php else: ?>
  <p class="no-comments">ثبت پیام برای این یادبود بسته است.</p>
 <?php endif; ?>
 <?php if (comments_open()) comment_form(); ?>
</div>
