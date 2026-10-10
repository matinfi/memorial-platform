<?php
get_header();
$q=isset($_GET['q'])?sanitize_text_field(wp_unslash($_GET['q'])):'';
$city=isset($_GET['city'])?sanitize_text_field(wp_unslash($_GET['city'])):'';
$sort=isset($_GET['sort'])?sanitize_key(wp_unslash($_GET['sort'])):'newest';
$order_map=['newest'=>['date','DESC'],'oldest'=>['date','ASC'],'name'=>['title','ASC']];
[$orderby,$order]=$order_map[$sort]??$order_map['newest'];
$args=['post_type'=>'avam_memorial','post_status'=>'publish','posts_per_page'=>12,'paged'=>max(1,get_query_var('paged')),'orderby'=>$orderby,'order'=>$order,'meta_query'=>['relation'=>'AND',['relation'=>'OR',['key'=>'avam_visibility','compare'=>'NOT EXISTS'],['key'=>'avam_visibility','value'=>'private','compare'=>'!=']]]];
if($q)$args['s']=$q;
if($city)$args['meta_query'][]=['key'=>'avam_city','value'=>$city,'compare'=>'LIKE'];
$memorials=new WP_Query($args);
?>
<div class="avam-archive-page" dir="rtl">
  <section class="avam-archive-hero">
    <div class="avam-container">
      <span>آرامگاه مجازی</span>
      <h1><?php echo esc_html(get_option('avam_search_title','یادبودها')); ?></h1>
      <p><?php echo esc_html(get_option('avam_search_intro','نام متوفی یا شهر را جستجو کنید.')); ?></p>
      <form class="avam-archive-search" method="get">
        <input type="hidden" name="q" value="<?php echo esc_attr($q); ?>">
        <label><span>شهر</span><input name="city" value="<?php echo esc_attr($city); ?>" placeholder="مثلاً تهران"></label>
        <label><span>مرتب‌سازی</span><select name="sort"><option value="newest" <?php selected($sort,'newest'); ?>>جدیدترین</option><option value="oldest" <?php selected($sort,'oldest'); ?>>قدیمی‌ترین</option><option value="name" <?php selected($sort,'name'); ?>>نام (الفبا)</option></select></label>
        <button type="submit">اعمال فیلترها</button>
      </form>
    </div>
  </section>
  <section class="avam-container avam-archive-results">
    <div class="avam-archive-head"><strong><?php echo esc_html(number_format_i18n($memorials->found_posts)); ?> یادبود</strong><span><?php echo $q||$city?'نتایج جستجو':'آخرین یادبودهای ثبت‌شده'; ?></span></div>
    <?php if($memorials->have_posts()): ?>
      <div class="avam-memorial-grid">
      <?php while($memorials->have_posts()):$memorials->the_post(); $city_value=get_post_meta(get_the_ID(),'avam_city',true);$death=get_post_meta(get_the_ID(),'avam_death',true); ?>
        <a class="avam-memorial-card" href="<?php the_permalink(); ?>">
          <div class="avam-memorial-card-image"><?php if(has_post_thumbnail()): ?><img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(),'large')); ?>" alt="<?php echo esc_attr(get_the_title()); ?>"><?php else: ?><span><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c.4-3.5 2.8-5.5 6.5-5.5s6.1 2 6.5 5.5"/><path d="M3 4v4M3 4h4M21 20v-4M21 20h-4"/></svg></span><?php endif; ?></div>
          <div class="avam-memorial-card-body">
            <span>یادبود</span><h2><?php the_title(); ?></h2>
            <p><?php echo esc_html($city_value ?: ''); ?><?php if($death && $city_value): ?> · <?php endif; ?><?php echo esc_html($death); ?></p>
          </div>
        </a>
      <?php endwhile; wp_reset_postdata(); ?>
      </div>
      <nav class="avam-archive-pagination"><?php echo wp_kses_post(paginate_links(['total'=>$memorials->max_num_pages,'current'=>max(1,get_query_var('paged')),'type'=>'list','prev_text'=>'←','next_text'=>'→'])); ?></nav>
    <?php else: ?>
      <div class="avam-archive-empty"><div><svg aria-hidden="true" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 8.7c0 4.4-8.5 10-8.5 10s-8.5-5.6-8.5-10a4.4 4.4 0 0 1 8.5-1.2 4.4 4.4 0 0 1 8.5 1.2Z"/></svg></div><h2>یادبودی پیدا نشد</h2><p>نام یا شهر دیگری را امتحان کنید.</p><a href="<?php echo esc_url(get_post_type_archive_link('avam_memorial')); ?>">مشاهده همه یادبودها</a></div>
    <?php endif; ?>
  </section>
</div>
<?php get_footer();