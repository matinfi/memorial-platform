    </main>
  </div>
</div>
<footer class="avam-footer avam-unified-footer">
  <div class="avam-container">
    <div><?php echo esc_html(get_option('avam_footer_text','آرامگاه مجازی — جایی برای نگه‌داشتن یک روایت با احترام.')); ?></div>
    <nav aria-label="پیوندهای پایانی">
      <?php
      $avam_cc_footer = get_option('avam_control_center', []);
      $avam_footer_menu = is_array($avam_cc_footer) ? absint($avam_cc_footer['footer_menu'] ?? 0) : 0;
      if ($avam_footer_menu && wp_get_nav_menu_object($avam_footer_menu)) {
        wp_nav_menu(['menu'=>$avam_footer_menu,'container'=>false,'items_wrap'=>'%3$s','fallback_cb'=>false,'depth'=>1]);
      } else {
        ?>
        <a href="<?php echo esc_url(home_url('/')); ?>">خانه</a>
        <a href="<?php echo esc_url(avam_memorials_url()); ?>">یادبودها</a>
        <?php if (is_user_logged_in()): ?><a href="<?php echo esc_url(avam_account_url()); ?>">حساب من</a><?php else: ?><a href="<?php echo esc_url(avam_login_url()); ?>">ورود</a><a href="<?php echo esc_url(avam_register_url()); ?>">ثبت‌نام</a><?php endif; ?>
        <?php
      }
      ?>
      <?php if (!empty($avam_cc_footer['privacy_policy_url'])): ?><a href="<?php echo esc_url($avam_cc_footer['privacy_policy_url']); ?>">حریم خصوصی</a><?php endif; ?>
    </nav>
    <?php if (!is_array($avam_cc_footer) || (($avam_cc_footer['footer_copyright'] ?? '1') === '1')): ?>
      <small class="avam-footer-copyright"><?php echo esc_html(is_array($avam_cc_footer) ? ($avam_cc_footer['footer_copyright_text'] ?? 'تمام حقوق محفوظ است.') : 'تمام حقوق محفوظ است.'); ?></small>
    <?php endif; ?>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
