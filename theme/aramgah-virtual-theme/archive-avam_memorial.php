<?php
get_header();
$q=isset($_GET['q'])?sanitize_text_field(wp_unslash($_GET['q'])):'';
$city=isset($_GET['city'])?sanitize_text_field(wp_unslash($_GET['city'])):'';
$args=['post_type'=>'avam_memorial','post_status'=>'publish','posts_per_page'=>12,'paged'=>max(1,get_query_var('paged')),'orderby'=>'date','order'=>'DESC'];
if($q)$args['s']=$q;
if($city)$args['meta_query']=[['key'=>'avam_city','value'=>$city,'compare'=>'LIKE']];
$memorials=new WP_Query($args);
?>
<main class="avam-archive-page" dir="rtl">
  <section class="avam-archive-hero">
    <div class="avam-container">
      <span>آرامگاه مجازی</span>
      <h1><?php echo esc_html(get_option('avam_search_title','یادبودها')); ?></h1>
      <p><?php echo esc_html(get_option('avam_search_intro','نام متوفی یا شهر را جستجو کنید.')); ?></p>
      <form class="avam-archive-search" method="get">
        <label><span>نام متوفی</span><input name="q" value="<?php echo esc_attr($q); ?>" placeholder="جستجوی نام"></label>
        <label><span>شهر</span><input name="city" value="<?php echo esc_attr($city); ?>" placeholder="مثلاً تهران"></label>
        <button type="submit">جستجو</button>
      </form>
    </div>
  </section>
  <section class="avam-container avam-archive-results">
    <div class="avam-archive-head"><strong><?php echo esc_html(number_format_i18n($memorials->found_posts)); ?> یادبود</strong><span><?php echo $q||$city?'نتایج جستجو':'آخرین یادبودهای ثبت‌شده'; ?></span></div>
    <?php if($memorials->have_posts()): ?>
      <div class="avam-memorial-grid">
      <?php while($memorials->have_posts()):$memorials->the_post(); $city_value=get_post_meta(get_the_ID(),'avam_city',true);$death=get_post_meta(get_the_ID(),'avam_death',true); ?>
        <a class="avam-memorial-card" href="<?php the_permalink(); ?>">
          <div class="avam-memorial-card-image"><?php if(has_post_thumbnail()): ?><img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(),'large')); ?>" alt="<?php echo esc_attr(get_the_title()); ?>"><?php else: ?><span>♡</span><?php endif; ?></div>
          <div class="avam-memorial-card-body">
            <span>یادبود</span><h2><?php the_title(); ?></h2>
            <p><?php echo esc_html($city_value ?: ''); ?><?php if($death && $city_value): ?> · <?php endif; ?><?php echo esc_html($death); ?></p>
          </div>
        </a>
      <?php endwhile; wp_reset_postdata(); ?>
      </div>
      <nav class="avam-archive-pagination"><?php echo wp_kses_post(paginate_links(['total'=>$memorials->max_num_pages,'current'=>max(1,get_query_var('paged')),'type'=>'list','prev_text'=>'←','next_text'=>'→'])); ?></nav>
    <?php else: ?>
      <div class="avam-archive-empty"><div>♡</div><h2>یادبودی پیدا نشد</h2><p>نام یا شهر دیگری را امتحان کنید.</p><a href="<?php echo esc_url(get_post_type_archive_link('avam_memorial')); ?>">مشاهده همه یادبودها</a></div>
    <?php endif; ?>
  </section>
</main>
<?php get_footer();