    </main>
  </div>
</div>
<footer class="avam-footer avam-unified-footer">
  <div class="avam-container">
    <div><?php echo esc_html(get_option('avam_footer_text','آرامگاه مجازی — جایی برای نگه‌داشتن یک روایت با احترام.')); ?></div>
    <nav aria-label="پیوندهای پایانی">
      <a href="<?php echo esc_url(home_url('/')); ?>">خانه</a>
      <a href="<?php echo esc_url(avam_memorials_url()); ?>">یادبودها</a>
      <?php if (is_user_logged_in()): ?><a href="<?php echo esc_url(avam_account_url()); ?>">حساب من</a><?php else: ?><a href="<?php echo esc_url(avam_login_url()); ?>">ورود</a><a href="<?php echo esc_url(avam_register_url()); ?>">ثبت‌نام</a><?php endif; ?>
    </nav>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
