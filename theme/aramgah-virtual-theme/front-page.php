<?php
get_header();

$user_count = count_users();
$total_users = isset($user_count['total_users']) ? (int) $user_count['total_users'] : 0;
$memorial_count = wp_count_posts('avam_memorial');
$total_memorials = isset($memorial_count->publish) ? (int) $memorial_count->publish : 0;
$image_id = (int) get_option('avam_home_image', 0);
$video_id = (int) get_option('avam_home_video', 0);
$poster = $image_id ? wp_get_attachment_url($image_id) : 'https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260912_105822_bf7c2d53-9957-4521-bbbf-7c1ab7a70130.png';
$video = $video_id ? wp_get_attachment_url($video_id) : 'https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260912_105953_21ad8049-9088-4a00-bad3-aee6b5575a2b.mp4';
$media_type = get_option('avam_home_media_type', 'video');
$search_url = function_exists('avam_memorials_url') ? avam_memorials_url() : home_url('/memorials/');
$register_url = function_exists('avam_register_url') ? avam_register_url() : wp_registration_url();
$login_url = function_exists('avam_login_url') ? avam_login_url() : wp_login_url();
?>
<div class="avam-home-page">
  <div class="avam-home-card">
    <?php if($media_type === 'image' || !$video): ?>
      <img class="avam-home-bg avam-home-bg-image" src="<?php echo esc_url($poster); ?>" alt="" aria-hidden="true">
    <?php else: ?>
      <video class="avam-home-bg" autoplay muted loop playsinline preload="auto" disablepictureinpicture aria-hidden="true"
        poster="<?php echo esc_url($poster); ?>" src="<?php echo esc_url($video); ?>"></video>
    <?php endif; ?>
    <div class="avam-home-tint" aria-hidden="true"></div>

    <div class="avam-home-stack">
      <header class="avam-home-header">
        <a class="avam-home-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="آرامگاه مجازی">
          <svg class="avam-home-mark" viewBox="0 0 40 40" fill="none" aria-hidden="true">
            <defs><clipPath id="avam-globe-clip"><circle cx="20" cy="20" r="18.2"/></clipPath></defs>
            <circle cx="20" cy="20" r="18.4" stroke="#0d1b30" stroke-width="1.1"/>
            <g clip-path="url(#avam-globe-clip)" stroke="#0d1b30" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path d="M13.2 4.6c3-.9 6.2-1 9.2-.1" stroke-width="1.7"/>
              <path d="M5.6 9.2c3.8-1.9 8-2.4 11.7-1.2 2.5.8 4.3 2.1 6.6 2.3 2 .2 4.2-.4 6.4-1.5" stroke-width="2.3"/>
              <path d="M2.6 13.9c4.4-2.4 9.4-3 13.6-1.5 2.4.9 4.1 2.3 6.4 2.4 2.3.1 4.7-1 7.1-2.4 1.6-.9 3.4-1.4 5.3-1.4" stroke-width="2.7"/>
              <path d="M1.6 18.7c4.8-2.7 10.1-3.2 14.4-1.6 1.8.7 3.2 1.6 4.7 2.1-1.7 1.3-3.4 2.2-5.1 2.6 2.9.5 5.9-.1 8.8-1.5 1.5-.7 2.9-1.6 4.4-2.3 1.9-.9 3.9-1.3 5.9-1.1" stroke-width="2.9"/>
              <path d="M1.9 24.1c4.5-2.4 9.6-3 13.9-1.6 2.3.7 4 1.9 6.2 2 2.4.1 5-.9 7.5-2.3 1.6-.9 3.3-1.4 5-1.4" stroke-width="2.8"/>
              <path d="M3.7 28.8c4.1-2 8.7-2.5 12.6-1.3 2.2.7 3.8 1.8 5.9 1.8 2.3.1 4.8-.8 7.1-2.1 1.2-.7 2.5-1.1 3.8-1.2" stroke-width="2.4"/>
              <path d="M7.6 32.9c3.5-1.5 7.4-1.9 10.6-.9 1.9.6 3.3 1.4 5 1.5 1.6.1 3.3-.3 5-1.1" stroke-width="1.9"/>
              <path d="M13.6 35.8c2.8-.9 5.8-1 8.6-.2" stroke-width="1.5"/>
            </g>
          </svg>
          <b>آرامگاه مجازی</b>
        </a>

        <button class="avam-home-burger" type="button" aria-label="باز کردن منو" aria-expanded="false" aria-controls="avam-home-menu"><i></i><i></i></button>

        <div class="avam-home-menu" id="avam-home-menu">
          <nav class="avam-home-nav" aria-label="اصلی">
            <a href="<?php echo esc_url(home_url('/')); ?>">
              <svg viewBox="0 0 20 21" aria-hidden="true"><path d="M2 8.4 10 2l8 6.4V18a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z" stroke="#202940" stroke-width="1.7" stroke-linejoin="round"/></svg>
              <span>خانه</span>
            </a>
            <a href="#avam-home-stats"><svg viewBox="0 0 20 20" aria-hidden="true"><rect x="1" y="1" width="7.4" height="7.4" rx="1.7" stroke="#202940" stroke-width="1.7"/><rect x="11.6" y="1" width="7.4" height="7.4" rx="1.7" stroke="#202940" stroke-width="1.7"/><rect x="1" y="11.6" width="7.4" height="7.4" rx="1.7" stroke="#202940" stroke-width="1.7"/><rect x="11.6" y="11.6" width="7.4" height="7.4" rx="1.7" stroke="#202940" stroke-width="1.7"/></svg><span>یادبودها</span></a>
            <span class="avam-home-divider" aria-hidden="true"></span>
            <a class="avam-home-login-mobile" href="<?php echo esc_url($login_url); ?>">ورود</a>
          </nav>
          <a class="avam-home-cta" href="<?php echo esc_url($register_url); ?>"><span>ساخت یادبود</span><i class="avam-home-knob"><svg viewBox="0 0 18 18" aria-hidden="true"><path d="m6.6 3.6 6 5.4-6 5.4" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></i></a>
        </div>
      </header>

      <main class="avam-home-hero">
        <p class="avam-home-eyebrow">جایی برای نام، تصویر و روایت</p>
        <h1><span>یادها</span><br><span>اینجا <em>می‌مانند.</em></span></h1>
        <form class="avam-home-search" action="<?php echo esc_url($search_url); ?>" method="get" role="search">
          <span class="avam-home-search-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 5 5"></path></svg></span>
          <input name="q" type="search" placeholder="جستجوی نام متوفی" aria-label="جستجوی نام متوفی">
          <span class="avam-home-search-sep"></span>
          <input name="city" type="search" placeholder="شهر" aria-label="شهر">
          <button type="submit">جستجو</button>
        </form>
        <div class="avam-home-tagrow">
          <a class="avam-home-play" href="#avam-home-stats" aria-label="دیدن یادبودها"><svg viewBox="0 0 13 14" aria-hidden="true"><path d="M1.4 1.3 11.6 7 1.4 12.7z" fill="#0b1526"/></svg></a>
          <span>برای کسانی که نمی‌خواهیم از یاد بروند.</span>
        </div>
      </main>

      <aside class="avam-home-panel" aria-label="آرامگاه مجازی">
        <div class="avam-panel-title">یادبودهای دیجیتال</div>
        <span class="avam-panel-dot"></span>
        <div class="avam-panel-shield" aria-hidden="true">
          <svg viewBox="0 0 30 39" fill="none"><path d="M15 1.2 1.6 6.6v13.1c0 6.6 5.1 12.6 13.4 17.9 8.3-5.3 13.4-11.3 13.4-17.9V6.6z" stroke="#101c33" stroke-width="2" stroke-linejoin="round"/><path d="M2.1 18.9c4.6-1.1 8.9-1.6 12.9-1.6s8.3.5 12.9 1.6" stroke="#101c33" stroke-width="2" stroke-linecap="round"/></svg>
        </div>
        <p>نام، تصویر و روایت<br>با احترام نگه‌داری می‌شود.</p>
        <div class="avam-panel-scale"><span>نام</span><span>تصویر</span><span>روایت</span><span>یاد</span></div>
        <div class="avam-panel-track"><i></i></div>
      </aside>

      <div class="avam-home-stats" id="avam-home-stats">
        <div class="avam-home-stat">
          <span class="avam-home-num"><?php echo esc_html(number_format_i18n($total_memorials)); ?>+</span>
          <span class="avam-home-label">یادبود<br>ثبت‌شده</span>
        </div>
        <span class="avam-home-slash" aria-hidden="true"></span>
        <div class="avam-home-stat">
          <span class="avam-home-num"><?php echo esc_html(number_format_i18n($total_users)); ?>+</span>
          <span class="avam-home-label">کاربر<br>همراه</span>
        </div>
      </div>

      <a class="avam-home-meet" href="<?php echo esc_url($login_url); ?>">
        <span class="avam-home-thumb"><img src="<?php echo esc_url($poster); ?>" alt=""></span>
        <b>ورود به آرامگاه</b>
        <i class="avam-home-knob"><svg viewBox="0 0 18 18" aria-hidden="true"><path d="m6.6 3.6 6 5.4-6 5.4" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></i>
      </a>
    </div>
  </div>
</div>

<script>
(function(){
  const d=document.documentElement;
  if(!('animate' in Element.prototype)) return;
  if(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  d.classList.add('avam-home-pre');
  setTimeout(()=>d.classList.remove('avam-home-pre'),4000);
})();
(function(){
  const b=document.querySelector('.avam-home-burger'),m=document.querySelector('#avam-home-menu');
  if(!b||!m)return;
  const close=()=>{b.setAttribute('aria-expanded','false');m.removeAttribute('data-open');};
  b.addEventListener('click',e=>{e.stopPropagation();const open=b.getAttribute('aria-expanded')==='true';b.setAttribute('aria-expanded',String(!open));if(!open)m.setAttribute('data-open','');else m.removeAttribute('data-open');});
  m.querySelectorAll('a').forEach(a=>a.addEventListener('click',close));
  document.addEventListener('click',e=>{if(!m.contains(e.target)&&e.target!==b)close();});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){close();b.focus();}});
  const v=document.querySelector('.avam-home-bg'),q=matchMedia('(prefers-reduced-motion: reduce)');
  if(v){const sync=()=>{if(q.matches){v.pause();v.currentTime=0;}else v.play().catch(()=>{});};q.addEventListener('change',sync);document.addEventListener('visibilitychange',()=>{if(!document.hidden)sync();});v.addEventListener('canplay',sync);sync();}
})();
</script>
<?php get_footer();