<?php
/**
 * The main template file for آرامگاه مجازی.
 *
 * @package avam
 */

get_header();
?>

<main class="avam-page">
  <div class="avam-container">
    <?php if ( have_posts() ) : ?>
      <?php while ( have_posts() ) : the_post(); ?>
        <article class="avam-card">
          <h1 class="avam-section-title"><?php the_title(); ?></h1>
          <div class="avam-richtext">
            <?php the_content(); ?>
          </div>
        </article>
      <?php endwhile; ?>

      <?php the_posts_pagination(); ?>
    <?php else : ?>
      <article class="avam-card">
        <h1 class="avam-section-title"><?php esc_html_e( 'محتوایی پیدا نشد.', 'avam' ); ?></h1>
        <p><?php esc_html_e( 'در حال حاضر محتوایی برای نمایش وجود ندارد.', 'avam' ); ?></p>
      </article>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
