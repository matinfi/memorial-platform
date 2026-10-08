<?php get_header(); ?>
<?php if(is_page('account')): ?>
  <?php while(have_posts()): the_post(); the_content(); endwhile; ?>
<?php else: ?>
  <section class="avam-page"><div class="avam-container avam-card"><?php while(have_posts()): the_post(); ?><h1 class="avam-section-title"><?php the_title(); ?></h1><div><?php the_content(); ?></div><?php endwhile; ?></div></section>
<?php endif; ?>
<?php get_footer(); ?>
