<?php
/**
 * Tabbed administration center for آرامگاه مجازی.
 * Persistent settings are sanitized and applied only where the theme/plugin exposes a matching feature.
 */
if (!defined('ABSPATH')) exit;

final class AVAM_Admin_Center {
    const OPTION = 'avam_control_center';
    const PAGE = 'avam-settings';

    public static function init() {
        add_action('admin_post_avam_save_control_center', [__CLASS__, 'save']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'assets']);
        add_action('wp_head', [__CLASS__, 'frontend_css'], 99);
        add_action('wp_body_open', [__CLASS__, 'announcement']);
        add_filter('body_class', [__CLASS__, 'body_classes']);
        add_filter('wp_robots', [__CLASS__, 'robots']);
    }

    private static function tabs() {
        return [
            'overview' => ['داشبورد تنظیمات', 'نمای کلی', [
                ['type'=>'notice','label'=>'مرکز کنترل آرامگاه مجازی','description'=>'تنظیمات در این صفحه ذخیره می‌شوند. تغییرات ظاهری و گزینه‌های متصل به قالب بلافاصله در خروجی سایت اعمال می‌شوند؛ گزینه‌های مدیریتی فقط در محدوده‌ای اثر دارند که وردپرس یا افزونه پشتیبانی می‌کند.'],
                ['type'=>'checkbox','key'=>'maintenance_notice','label'=>'نمایش پیام اطلاع‌رسانی در پنل','description'=>'این گزینه فقط پیام راهنمای همین مرکز تنظیمات را نمایش می‌دهد.']
            ]],
            'header' => ['هدر و سربرگ', 'هدر', [
                ['type'=>'text','key'=>'site_title','label'=>'عنوان نمایشی سایت','default'=>'آرامگاه مجازی'],
                ['type'=>'text','key'=>'site_tagline','label'=>'زیرعنوان سایت','default'=>'یادها اینجا می‌مانند'],
                ['type'=>'checkbox','key'=>'header_title_enabled','label'=>'نمایش عنوان سایت در نوار بالایی','default'=>'1'],
                ['type'=>'checkbox','key'=>'header_search_enabled','label'=>'نمایش جست‌وجوی سراسری هدر','default'=>'1'],
                ['type'=>'menu','key'=>'header_menu','label'=>'منوی هدر / منوی اصلی'],
                ['type'=>'select','key'=>'header_width','label'=>'عرض هدر','default'=>'wide','options'=>['wide'=>'تمام عرض محتوا','contained'=>'محدود و جمع‌وجور']],
                ['type'=>'checkbox','key'=>'sticky_header','label'=>'هدر چسبان','description'=>'در حال حاضر فقط ذخیره می‌شود؛ فعال‌سازی آن نیازمند رفتار اسکرول در قالب است.']
            ]],
            'navigation' => ['منوها و ناوبری', 'منوها', [
                ['type'=>'menu','key'=>'primary_menu','label'=>'منوی اصلی'],
                ['type'=>'menu','key'=>'footer_menu','label'=>'منوی فوتر'],
                ['type'=>'page','key'=>'home_page','label'=>'صفحه خانه'],
                ['type'=>'page','key'=>'login_page','label'=>'صفحه ورود'],
                ['type'=>'page','key'=>'register_page','label'=>'صفحه ثبت‌نام'],
                ['type'=>'page','key'=>'memorials_page','label'=>'صفحه فهرست یادبودها'],
                ['type'=>'page','key'=>'account_page','label'=>'صفحه حساب کاربری'],
                ['type'=>'page','key'=>'create_page','label'=>'صفحه ساخت یادبود'],
                ['type'=>'textarea','key'=>'custom_nav_note','label'=>'یادداشت مدیر درباره ناوبری','description'=>'برای مستندسازی ساختار منو؛ این متن در سایت عمومی نمایش داده نمی‌شود.']
            ]],
            'pages' => ['مدیریت صفحات', 'صفحات', [
                ['type'=>'checkbox','key'=>'page_titles_enabled','label'=>'نمایش عنوان صفحات به‌صورت پیش‌فرض','default'=>'1'],
                ['type'=>'checkbox','key'=>'page_breadcrumbs','label'=>'نمایش مسیر راهنما (Breadcrumb)','description'=>'تا زمانی که خروجی breadcrumb در قالب یا افزونه فعال نباشد، این گزینه به‌تنهایی عنصر جدیدی ایجاد نمی‌کند.'],
                ['type'=>'select','key'=>'default_page_width','label'=>'عرض پیش‌فرض صفحات','default'=>'normal','options'=>['narrow'=>'باریک','normal'=>'معمولی','wide'=>'عریض']],
                ['type'=>'textarea','key'=>'pages_admin_note','label'=>'یادداشت مدیریت صفحات']
            ]],
            'home' => ['صفحه اصلی و بخش‌ها', 'خانه', [
                ['type'=>'checkbox','key'=>'home_hero_enabled','label'=>'نمایش بخش معرفی اصلی','default'=>'1'],
                ['type'=>'checkbox','key'=>'home_stats_enabled','label'=>'نمایش آمار صفحه اصلی','default'=>'1'],
                ['type'=>'checkbox','key'=>'home_recent_enabled','label'=>'نمایش یادبودهای تازه','default'=>'1'],
                ['type'=>'checkbox','key'=>'home_intro_panel_enabled','label'=>'نمایش پنل معرفی','default'=>'1'],
                ['type'=>'text','key'=>'home_eyebrow','label'=>'متن بالای عنوان','default'=>'آرامگاه مجازی'],
                ['type'=>'text','key'=>'home_title','label'=>'عنوان اصلی صفحه','default'=>'یادها اینجا می‌مانند.'],
                ['type'=>'textarea','key'=>'home_subtitle','label'=>'توضیح صفحه اصلی','default'=>'فضایی محترمانه برای زنده نگه‌داشتن نام، تصویر و روایت عزیزان.'],
                ['type'=>'text','key'=>'home_cta','label'=>'متن دکمه اصلی','default'=>'ساخت یادبود'],
                ['type'=>'select','key'=>'home_media_type','label'=>'رسانه پس‌زمینه','default'=>'image','options'=>['none'=>'بدون رسانه','image'=>'تصویر','video'=>'ویدیو']],
                ['type'=>'attachment','key'=>'home_image','label'=>'شناسه تصویر صفحه اصلی'],
                ['type'=>'attachment','key'=>'home_video','label'=>'شناسه ویدیوی صفحه اصلی']
            ]],
            'archive' => ['فهرست و جست‌وجوی یادبودها', 'آرشیو', [
                ['type'=>'text','key'=>'search_title','label'=>'عنوان آرشیو','default'=>'یادبودها'],
                ['type'=>'textarea','key'=>'search_intro','label'=>'توضیح آرشیو','default'=>'نام متوفی یا شهر را جستجو کنید.'],
                ['type'=>'number','key'=>'archive_per_page','label'=>'تعداد یادبود در هر صفحه','default'=>'12','min'=>1,'max'=>60],
                ['type'=>'checkbox','key'=>'archive_city_filter','label'=>'فعال بودن فیلتر شهر','default'=>'1'],
                ['type'=>'checkbox','key'=>'archive_sort','label'=>'فعال بودن مرتب‌سازی','default'=>'1'],
                ['type'=>'checkbox','key'=>'archive_show_dates','label'=>'نمایش تاریخ در کارت یادبود','default'=>'1'],
                ['type'=>'checkbox','key'=>'archive_show_city','label'=>'نمایش شهر در کارت یادبود','default'=>'1'],
                ['type'=>'select','key'=>'archive_default_sort','label'=>'مرتب‌سازی پیش‌فرض','default'=>'newest','options'=>['newest'=>'جدیدترین','oldest'=>'قدیمی‌ترین','name'=>'نام الفبایی']]
            ]],
            'single' => ['صفحه جزئیات یادبود', 'جزئیات یادبود', [
                ['type'=>'checkbox','key'=>'single_image_enabled','label'=>'نمایش تصویر شاخص','default'=>'1'],
                ['type'=>'checkbox','key'=>'single_timeline_enabled','label'=>'نمایش خط زمانی زندگی','default'=>'1'],
                ['type'=>'checkbox','key'=>'single_comments_enabled','label'=>'نمایش بخش دعا و خاطره','default'=>'1'],
                ['type'=>'checkbox','key'=>'single_share_enabled','label'=>'نمایش ابزار اشتراک‌گذاری','default'=>'1'],
                ['type'=>'text','key'=>'single_kicker','label'=>'برچسب بالای صفحه','default'=>'صفحه یادبود'],
                ['type'=>'textarea','key'=>'single_intro','label'=>'متن معرفی','default'=>'روایتی برای ماندن و به یاد آوردن.']
            ]],
            'account' => ['حساب کاربری و داشبورد', 'حساب کاربری', [
                ['type'=>'checkbox','key'=>'account_dashboard_enabled','label'=>'فعال بودن داشبورد حساب','default'=>'1'],
                ['type'=>'checkbox','key'=>'account_recent_enabled','label'=>'نمایش آخرین یادبودها در داشبورد','default'=>'1'],
                ['type'=>'checkbox','key'=>'account_stats_enabled','label'=>'نمایش آمار حساب','default'=>'1'],
                ['type'=>'checkbox','key'=>'account_comments_link','label'=>'نمایش پیوند دیدگاه‌های من','default'=>'1'],
                ['type'=>'page','key'=>'profile_page','label'=>'صفحه تنظیمات حساب'],
                ['type'=>'page','key'=>'comments_page','label'=>'صفحه دیدگاه‌های من']
            ]],
            'forms' => ['فرم‌ها و فیلدها', 'فرم‌ها', [
                ['type'=>'checkbox','key'=>'registration_enabled','label'=>'اجازه ثبت‌نام کاربران','default'=>'1'],
                ['type'=>'checkbox','key'=>'memorial_creation_enabled','label'=>'اجازه ساخت یادبود برای کاربران واردشده','default'=>'1'],
                ['type'=>'checkbox','key'=>'comments_enabled','label'=>'فعال بودن دعا و خاطره','default'=>'1'],
                ['type'=>'checkbox','key'=>'comments_moderation','label'=>'تأیید دیدگاه‌ها پیش از انتشار','default'=>'1'],
                ['type'=>'number','key'=>'comment_max_length','label'=>'حداکثر طول پیام','default'=>'5000','min'=>1,'max'=>20000],
                ['type'=>'textarea','key'=>'form_admin_note','label'=>'یادداشت فرم‌ها','description'=>'این یادداشت داخلی است؛ برای افزودن/حذف فیلدهای واقعی فرم باید کد فرم مربوطه نیز به این تنظیم متصل شود.']
            ]],
            'design' => ['طراحی و هویت بصری', 'طراحی', [
                ['type'=>'color','key'=>'primary_color','label'=>'رنگ اصلی','default'=>'#2c3531'],
                ['type'=>'color','key'=>'accent_color','label'=>'رنگ تأکیدی','default'=>'#ad875c'],
                ['type'=>'color','key'=>'surface_color','label'=>'رنگ پس‌زمینه','default'=>'#f5f7f6'],
                ['type'=>'color','key'=>'text_color','label'=>'رنگ متن','default'=>'#26382f'],
                ['type'=>'select','key'=>'font_family','label'=>'فونت رابط','default'=>'vazirmatn','options'=>['vazirmatn'=>'Vazirmatn','system'=>'فونت سیستم']],
                ['type'=>'select','key'=>'corner_style','label'=>'گردی گوشه‌ها','default'=>'medium','options'=>['sharp'=>'کم','medium'=>'متوسط','round'=>'زیاد']],
                ['type'=>'select','key'=>'shadow_style','label'=>'شدت سایه','default'=>'soft','options'=>['none'=>'بدون سایه','soft'=>'ملایم','strong'=>'پررنگ']],
                ['type'=>'number','key'=>'content_max_width','label'=>'حداکثر عرض محتوا (پیکسل)','default'=>'1200','min'=>720,'max'=>1800]
            ]],
            'footer' => ['فوتر و انتهای صفحات', 'فوتر', [
                ['type'=>'checkbox','key'=>'footer_enabled','label'=>'نمایش فوتر','default'=>'1'],
                ['type'=>'text','key'=>'footer_text','label'=>'متن فوتر','default'=>'آرامگاه مجازی — جایی برای نگه‌داشتن یک روایت با احترام.'],
                ['type'=>'menu','key'=>'footer_menu','label'=>'منوی فوتر'],
                ['type'=>'checkbox','key'=>'footer_copyright','label'=>'نمایش متن حق نشر','default'=>'1'],
                ['type'=>'text','key'=>'footer_copyright_text','label'=>'متن حق نشر','default'=>'تمام حقوق محفوظ است.']
            ]],
            'content' => ['محتوا و پیام‌ها', 'محتوا', [
                ['type'=>'textarea','key'=>'site_message','label'=>'پیام عمومی سایت','default'=>'هر نام، یک روایت است.'],
                ['type'=>'checkbox','key'=>'announcement_enabled','label'=>'نمایش اعلان عمومی','default'=>'0'],
                ['type'=>'text','key'=>'announcement_text','label'=>'متن اعلان'],
                ['type'=>'select','key'=>'announcement_type','label'=>'نوع اعلان','default'=>'info','options'=>['info'=>'اطلاع‌رسانی','success'=>'موفقیت','warning'=>'هشدار']]
            ]],
            'access' => ['نقش‌ها و دسترسی‌ها', 'دسترسی', [
                ['type'=>'checkbox','key'=>'public_registration','label'=>'ثبت‌نام عمومی فعال باشد','default'=>'1'],
                ['type'=>'checkbox','key'=>'private_memorials_enabled','label'=>'پشتیبانی از یادبود خصوصی','default'=>'1'],
                ['type'=>'checkbox','key'=>'admin_bar_frontend','label'=>'نمایش نوار مدیریت وردپرس برای مدیران','default'=>'1'],
                ['type'=>'textarea','key'=>'access_note','label'=>'یادداشت سیاست دسترسی','description'=>'این گزینه‌ها جایگزین مدیریت نقش‌ها و قابلیت‌های وردپرس نیستند.']
            ]],
            'seo' => ['سئو و اشتراک‌گذاری', 'سئو', [
                ['type'=>'text','key'=>'seo_default_title','label'=>'عنوان پیش‌فرض سایت','default'=>'آرامگاه مجازی'],
                ['type'=>'textarea','key'=>'seo_default_description','label'=>'توضیح پیش‌فرض سایت'],
                ['type'=>'text','key'=>'seo_og_image','label'=>'نشانی تصویر اشتراک‌گذاری'],
                ['type'=>'checkbox','key'=>'seo_search_index','label'=>'اجازه ایندکس شدن سایت','default'=>'1'],
                ['type'=>'checkbox','key'=>'seo_social_links','label'=>'نمایش ابزارهای اشتراک‌گذاری','default'=>'1']
            ]],
            'performance' => ['کارایی و کش', 'کارایی', [
                ['type'=>'checkbox','key'=>'lazy_images','label'=>'بارگذاری تنبل تصاویر','default'=>'1'],
                ['type'=>'checkbox','key'=>'disable_emojis','label'=>'غیرفعال‌سازی اسکریپت ایموجی وردپرس','default'=>'0'],
                ['type'=>'checkbox','key'=>'cache_busting','label'=>'بارگذاری فایل‌ها با نسخه مشخص','default'=>'1'],
                ['type'=>'textarea','key'=>'performance_note','label'=>'یادداشت کارایی','description'=>'تنظیمات کش سرور و CDN باید در همان سرویس مدیریت شوند.']
            ]],
            'responsive' => ['موبایل و واکنش‌گرایی', 'موبایل', [
                ['type'=>'checkbox','key'=>'mobile_search_enabled','label'=>'نمایش جستجو در موبایل','default'=>'1'],
                ['type'=>'checkbox','key'=>'mobile_menu_enabled','label'=>'نمایش منوی موبایل','default'=>'1'],
                ['type'=>'select','key'=>'mobile_breakpoint','label'=>'نقطه شکست موبایل','default'=>'760','options'=>['640'=>'۶۴۰ پیکسل','760'=>'۷۶۰ پیکسل','900'=>'۹۰۰ پیکسل']],
                ['type'=>'checkbox','key'=>'reduce_motion','label'=>'کاهش حرکت‌های تزئینی','default'=>'0']
            ]],
            'accessibility' => ['دسترس‌پذیری', 'دسترس‌پذیری', [
                ['type'=>'checkbox','key'=>'a11y_skip_link','label'=>'نمایش پیوند پرش به محتوا','default'=>'1'],
                ['type'=>'checkbox','key'=>'a11y_focus_outline','label'=>'نمایش واضح فوکوس صفحه‌کلید','default'=>'1'],
                ['type'=>'checkbox','key'=>'a11y_contrast','label'=>'افزایش کنتراست رابط','default'=>'0']
            ]],
            'localization' => ['زبان، تاریخ و زمان', 'زبان و تاریخ', [
                ['type'=>'select','key'=>'date_display','label'=>'شیوه نمایش تاریخ','default'=>'wordpress','options'=>['wordpress'=>'تنظیم وردپرس','jdate'=>'شمسی (در صورت نصب افزونه سازگار)']],
                ['type'=>'select','key'=>'number_display','label'=>'نمایش اعداد','default'=>'locale','options'=>['locale'=>'بر اساس زبان سایت','latin'=>'لاتین']],
                ['type'=>'text','key'=>'timezone_note','label'=>'یادداشت منطقه زمانی','description'=>'منطقه زمانی اصلی را از تنظیمات عمومی وردپرس تغییر دهید.']
            ]],
            'integrations' => ['اتصال‌ها و توسعه', 'اتصال‌ها', [
                ['type'=>'text','key'=>'analytics_id','label'=>'شناسه ابزار آمارگیری'],
                ['type'=>'url','key'=>'privacy_policy_url','label'=>'پیوند سیاست حریم خصوصی'],
                ['type'=>'textarea','key'=>'custom_head_note','label'=>'یادداشت کدهای سفارشی','description'=>'برای امنیت، اجرای کد دلخواه در هدر از این فیلد انجام نمی‌شود. از افزونه معتبر یا Child Theme استفاده کنید.']
            ]],
            'advanced' => ['پشتیبان‌گیری و پیشرفته', 'پیشرفته', [
                ['type'=>'checkbox','key'=>'advanced_debug','label'=>'ثبت یادداشت عیب‌یابی تنظیمات','default'=>'0'],
                ['type'=>'checkbox','key'=>'advanced_confirm_delete','label'=>'تأیید مضاعف عملیات حذف','default'=>'1'],
                ['type'=>'textarea','key'=>'advanced_notes','label'=>'یادداشت نسخه و نگهداری'],
                ['type'=>'notice','label'=>'پشتیبان‌گیری و بازیابی','description'=>'برای جلوگیری از واردکردن داده نامعتبر، در این نسخه خروجی/ورودی فایل تنظیمات ارائه نشده است. از ابزار پشتیبان‌گیری هاست یا افزونه معتبر استفاده کنید.']
            ]],
            'overrides' => ['تنظیمات اختصاصی صفحه', 'اختصاصی صفحه', [
                ['type'=>'page','key'=>'override_page','label'=>'صفحه موردنظر'],
                ['type'=>'select','key'=>'override_width','label'=>'عرض محتوا برای صفحه انتخابی','default'=>'inherit','options'=>['inherit'=>'پیش‌فرض','narrow'=>'باریک','normal'=>'معمولی','wide'=>'عریض']],
                ['type'=>'color','key'=>'override_accent','label'=>'رنگ تأکیدی صفحه','default'=>'#ad875c'],
                ['type'=>'textarea','key'=>'override_note','label'=>'یادداشت تغییرات صفحه','description'=>'این مقادیر به‌عنوان پیکربندی ذخیره می‌شوند؛ قالب فعلی هنوز برای همه صفحات override مستقل ندارد.']
            ]],
            'blocks' => ['تنظیمات استاندارد بلوک‌ها', 'بلوک‌ها', [
                ['type'=>'select','key'=>'block_spacing','label'=>'فاصله بین بخش‌ها','default'=>'normal','options'=>['compact'=>'فشرده','normal'=>'معمولی','relaxed'=>'باز']],
                ['type'=>'select','key'=>'block_heading_style','label'=>'سبک عنوان بخش','default'=>'line','options'=>['plain'=>'ساده','line'=>'همراه خط','badge'=>'برچسب‌دار']],
                ['type'=>'checkbox','key'=>'block_icons','label'=>'نمایش آیکون‌های تزئینی','default'=>'1'],
                ['type'=>'checkbox','key'=>'block_animations','label'=>'انیمیشن بلوک‌ها','default'=>'0']
            ]]
        ];
    }

    public static function get($key, $default = '') {
        $values = get_option(self::OPTION, []);
        return is_array($values) && array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public static function render() {
        if (!current_user_can('manage_options')) return;
        $tabs = self::tabs();
        $active = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'overview';
        if (!isset($tabs[$active])) $active = 'overview';
        $values = get_option(self::OPTION, []);
        if (!is_array($values)) $values = [];
        echo '<div class="wrap avam-control-center" dir="rtl"><div class="avam-cc-heading"><div><span class="avam-cc-eyebrow">ARAMGAH VIRTUAL · CONTROL CENTER</span><h1>مرکز کنترل آرامگاه مجازی</h1><p>تنظیمات ساختاریافته قالب و تجربه کاربری، با ذخیره‌سازی مرکزی و تب‌های مستقل.</p></div><span class="avam-cc-version">نسخه مدیریت ۱.۰</span></div>';
        if (isset($_GET['saved']) && $_GET['saved'] === '1') echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
        echo '<div class="avam-cc-layout"><nav class="avam-cc-tabs" aria-label="گروه‌های تنظیمات">';
        foreach ($tabs as $key=>$tab) {
            $url = add_query_arg(['page'=>self::PAGE,'tab'=>$key], admin_url('options-general.php'));
            echo '<a class="avam-cc-tab'.($active===$key?' is-active':'').'" href="'.esc_url($url).'"'.($active===$key?' aria-current="page"':'').'><span>'.esc_html($tab[1]).'</span><small>'.esc_html($tab[0]).'</small></a>';
        }
        echo '</nav><section class="avam-cc-panel"><div class="avam-cc-panel-head"><div><span>گروه تنظیمات</span><h2>'.esc_html($tabs[$active][0]).'</h2></div><span class="avam-cc-count">'.count($tabs[$active][2]).' گزینه</span></div>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="avam_save_control_center"><input type="hidden" name="return_tab" value="'.esc_attr($active).'">';
        wp_nonce_field('avam_save_control_center','avam_control_nonce');
        foreach ($tabs[$active][2] as $field) self::render_field($field, $values);
        echo '<div class="avam-cc-actions"><button class="button button-primary button-hero" type="submit">ذخیره تنظیمات این تب</button><span>تنظیمات هر گروه جداگانه ذخیره می‌شوند.</span></div></form></section></div></div>';
    }

    private static function render_field($f, $values) {
        if ($f['type']==='notice') {
            echo '<div class="avam-cc-notice"><strong>'.esc_html($f['label']).'</strong><p>'.esc_html($f['description']??'').'</p></div>';
            return;
        }
        $key = $f['key'];
        $default = $f['default'] ?? '';
        $value = array_key_exists($key,$values) ? $values[$key] : $default;
        echo '<div class="avam-cc-field"><div class="avam-cc-label"><label for="avam-cc-'.esc_attr($key).'">'.esc_html($f['label']).'</label>';
        if (!empty($f['description'])) echo '<p>'.esc_html($f['description']).'</p>';
        echo '</div><div class="avam-cc-control">';
        $id = 'avam-cc-'.$key;
        switch ($f['type']) {
            case 'checkbox':
                echo '<input type="hidden" name="settings['.esc_attr($key).']" value="0"><label class="avam-cc-switch"><input id="'.esc_attr($id).'" type="checkbox" name="settings['.esc_attr($key).']" value="1" '.checked((string)$value,'1',false).'><span aria-hidden="true"></span><b>'.((string)$value==='1'?'فعال':'غیرفعال').'</b></label>';
                break;
            case 'textarea':
                echo '<textarea id="'.esc_attr($id).'" name="settings['.esc_attr($key).']" rows="3" class="large-text">'.esc_textarea($value).'</textarea>';
                break;
            case 'select':
                echo '<select id="'.esc_attr($id).'" name="settings['.esc_attr($key).']">';
                foreach (($f['options']??[]) as $v=>$label) echo '<option value="'.esc_attr($v).'" '.selected((string)$value,(string)$v,false).'>'.esc_html($label).'</option>';
                echo '</select>';
                break;
            case 'page':
                echo '<select id="'.esc_attr($id).'" name="settings['.esc_attr($key).']"><option value="0">— انتخاب صفحه —</option>';
                foreach (get_pages(['sort_column'=>'post_title','sort_order'=>'ASC']) as $page) echo '<option value="'.(int)$page->ID.'" '.selected((string)$value,(string)$page->ID,false).'>'.esc_html($page->post_title).'</option>';
                echo '</select>';
                break;
            case 'menu':
                echo '<select id="'.esc_attr($id).'" name="settings['.esc_attr($key).']"><option value="0">— انتخاب منو —</option>';
                foreach (wp_get_nav_menus() as $menu) echo '<option value="'.(int)$menu->term_id.'" '.selected((string)$value,(string)$menu->term_id,false).'>'.esc_html($menu->name).'</option>';
                echo '</select> <a href="'.esc_url(admin_url('nav-menus.php')).'">مدیریت ساختار و زیرمنوها</a>';
                break;
            case 'color':
                echo '<input id="'.esc_attr($id).'" type="color" name="settings['.esc_attr($key).']" value="'.esc_attr(sanitize_hex_color($value)?:($default?:'#2c3531')).'"> <code>'.esc_html($value).'</code>';
                break;
            case 'number':
                echo '<input id="'.esc_attr($id).'" type="number" class="small-text" name="settings['.esc_attr($key).']" min="'.esc_attr($f['min']??0).'" max="'.esc_attr($f['max']??999999).'" value="'.esc_attr($value).'">';
                break;
            case 'url':
                echo '<input id="'.esc_attr($id).'" type="url" class="large-text" name="settings['.esc_attr($key).']" value="'.esc_attr($value).'">';
                break;
            case 'attachment':
                echo '<input id="'.esc_attr($id).'" type="number" min="0" class="small-text" name="settings['.esc_attr($key).']" value="'.esc_attr($value).'"> <button type="button" class="button avam-cc-media-pick" data-target="'.esc_attr($id).'" data-type="'.($key==='home_video'?'video':'image').'">انتخاب از کتابخانه رسانه</button> <span class="description">فایل از کتابخانه وردپرس انتخاب می‌شود.</span>';
                break;
            default:
                echo '<input id="'.esc_attr($id).'" type="text" class="regular-text" name="settings['.esc_attr($key).']" value="'.esc_attr($value).'">';
        }
        echo '</div></div>';
    }

    public static function save() {
        if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز', '', ['response'=>403]);
        if (!isset($_POST['avam_control_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['avam_control_nonce'])),'avam_save_control_center')) wp_die('درخواست معتبر نیست.', '', ['response'=>403]);
        $posted = isset($_POST['settings']) && is_array($_POST['settings']) ? wp_unslash($_POST['settings']) : [];
        $tabs = self::tabs();
        $schema = [];
        foreach ($tabs as $tab) foreach ($tab[2] as $field) if (!empty($field['key'])) $schema[$field['key']] = $field;
        $old = get_option(self::OPTION, []);
        if (!is_array($old)) $old = [];
        foreach ($schema as $key=>$field) {
            if (!array_key_exists($key,$posted)) continue;
            $v = $posted[$key];
            switch ($field['type']) {
                case 'checkbox': $v = $v === '1' ? '1' : '0'; break;
                case 'color': $v = sanitize_hex_color($v) ?: ($field['default'] ?? '#2c3531'); break;
                case 'number': $v = min((int)($field['max']??999999),max((int)($field['min']??0),absint($v))); break;
                case 'url': $v = esc_url_raw($v); break;
                case 'page': $v = absint($v); if ($v && get_post_type($v)!=='page') $v=0; break;
                case 'menu': $v = absint($v); if ($v && !wp_get_nav_menu_object($v)) $v=0; break;
                case 'attachment': $v = absint($v); if ($v && !wp_attachment_is_image($v) && $key==='home_image') $v=0; break;
                case 'select': $v = sanitize_key($v); if (!array_key_exists($v,$field['options']??[])) $v=$field['default']??''; break;
                case 'textarea': $v = sanitize_textarea_field($v); break;
                default: $v = sanitize_text_field($v); break;
            }
            $old[$key] = $v;
        }
        update_option(self::OPTION,$old,false);
        if (!empty($old['home_page']) && get_post_type(absint($old['home_page'])) === 'page') {
            update_option('show_on_front', 'page');
            update_option('page_on_front', absint($old['home_page']));
        }
        // Keep existing public-facing options in sync for legacy template compatibility.
        $sync = ['primary_color'=>'avam_primary_color','accent_color'=>'avam_accent_color','footer_text'=>'avam_footer_text','site_message'=>'avam_site_message','search_title'=>'avam_search_title','search_intro'=>'avam_search_intro','single_kicker'=>'avam_single_kicker','single_intro'=>'avam_single_intro','comments_enabled'=>'avam_comments_enabled','comments_moderation'=>'avam_comments_require_moderation','home_eyebrow'=>'avam_home_eyebrow','home_title'=>'avam_home_title','home_subtitle'=>'avam_home_subtitle','home_cta'=>'avam_home_cta','home_image'=>'avam_home_image','home_video'=>'avam_home_video','home_media_type'=>'avam_home_media_type'];
        foreach ($sync as $key=>$option) if (array_key_exists($key,$old)) update_option($option,$old[$key],false);
        if (isset($_POST['return_tab'])) $tab=sanitize_key(wp_unslash($_POST['return_tab'])); else $tab='overview';
        if (!isset($tabs[$tab])) $tab='overview';
        wp_safe_redirect(add_query_arg(['page'=>self::PAGE,'tab'=>$tab,'saved'=>'1'],admin_url('options-general.php')));
        exit;
    }

    public static function body_classes($classes) {
        $settings = get_option(self::OPTION, []);
        if (is_array($settings)) {
            if (($settings['home_hero_enabled']??'1')==='0') $classes[]='avam-setting-home-hero-off';
            if (($settings['home_stats_enabled']??'1')==='0') $classes[]='avam-setting-home-stats-off';
            if (($settings['home_recent_enabled']??'1')==='0') $classes[]='avam-setting-home-recent-off';
            if (($settings['home_intro_panel_enabled']??'1')==='0') $classes[]='avam-setting-home-panel-off';
            if (($settings['header_title_enabled']??'1')==='0') $classes[]='avam-setting-header-title-off';
            if (($settings['header_search_enabled']??'1')==='0') $classes[]='avam-setting-header-search-off';
            if (($settings['footer_enabled']??'1')==='0') $classes[]='avam-setting-footer-off';
            if (($settings['mobile_search_enabled']??'1')==='0') $classes[]='avam-setting-mobile-search-off';
            if (($settings['mobile_menu_enabled']??'1')==='0') $classes[]='avam-setting-mobile-menu-off';
            if (($settings['a11y_contrast']??'0')==='1') $classes[]='avam-setting-high-contrast';
            if (($settings['reduce_motion']??'0')==='1') $classes[]='avam-setting-reduce-motion';
            if (($settings['sticky_header']??'0')==='1') $classes[]='avam-setting-sticky-header';
            if (($settings['account_dashboard_enabled']??'1')==='0') $classes[]='avam-setting-account-dashboard-off';
            if (($settings['account_stats_enabled']??'1')==='0') $classes[]='avam-setting-account-stats-off';
            if (($settings['account_recent_enabled']??'1')==='0') $classes[]='avam-setting-account-recent-off';
            if (($settings['account_comments_link']??'1')==='0') $classes[]='avam-setting-account-comments-link-off';
            if (($settings['single_image_enabled']??'1')==='0') $classes[]='avam-setting-single-image-off';
            if (($settings['single_timeline_enabled']??'1')==='0') $classes[]='avam-setting-single-timeline-off';
            if (($settings['single_comments_enabled']??'1')==='0') $classes[]='avam-setting-single-comments-off';
            if (($settings['single_share_enabled']??'1')==='0') $classes[]='avam-setting-single-share-off';
            if (($settings['a11y_skip_link']??'1')==='0') $classes[]='avam-setting-skip-link-off';
            if (($settings['seo_social_links']??'1')==='0') $classes[]='avam-setting-social-off';
        }
        return $classes;
    }

    public static function robots($robots) {
        $s = get_option(self::OPTION, []);
        if (is_array($s) && ($s['seo_search_index'] ?? '1') === '0') {
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
        }
        return $robots;
    }

    public static function announcement() {
        $s = get_option(self::OPTION, []);
        if (!is_array($s) || ($s['announcement_enabled'] ?? '0') !== '1' || empty($s['announcement_text'])) return;
        $type = sanitize_key($s['announcement_type'] ?? 'info');
        $colors = ['info'=>'#edf4f8','success'=>'#edf7ef','warning'=>'#fff6e5'];
        $color = $colors[$type] ?? $colors['info'];
        echo '<div class="avam-cc-announcement" role="status" style="padding:10px 18px;background:'.esc_attr($color).';color:#26382f;text-align:center;font-size:13px">'.esc_html($s['announcement_text']).'</div>';
    }

    public static function frontend_css() {
        $s=get_option(self::OPTION,[]);
        if (!is_array($s)) $s=[];
        $primary=sanitize_hex_color($s['primary_color']??get_option('avam_primary_color','#2c3531'))?:'#2c3531';
        $accent=sanitize_hex_color($s['accent_color']??get_option('avam_accent_color','#ad875c'))?:'#ad875c';
        $surface=sanitize_hex_color($s['surface_color']??'#f5f7f6')?:'#f5f7f6';
        $text=sanitize_hex_color($s['text_color']??'#26382f')?:'#26382f';
        $width=min(1800,max(720,absint($s['content_max_width']??1200)));
        $radius=['sharp'=>'4px','medium'=>'12px','round'=>'22px'][$s['corner_style']??'medium']??'12px';
        $shadow=['none'=>'none','soft'=>'0 8px 24px rgba(35,50,42,.06)','strong'=>'0 12px 32px rgba(20,30,25,.16)'][$s['shadow_style']??'soft']??'0 8px 24px rgba(35,50,42,.06)';
        echo '<style id="avam-control-center-css">:root{--ink:'.$primary.';--cta:'.$primary.';--accent:'.$accent.';--c-primary:'.$primary.';--c-accent:'.$accent.';--avam-surface:'.$surface.';--avam-text:'.$text.';--avam-content-max:'.$width.'px;--avam-radius:'.$radius.';--avam-shadow:'.$shadow.'}';
        echo 'body.avam-setting-home-hero-off .avam-unified-welcome,body.avam-setting-home-stats-off .avam-unified-home-stats,body.avam-setting-home-recent-off .avam-unified-recent,body.avam-setting-home-panel-off .avam-unified-welcome-mark,body.avam-setting-header-title-off .avam-site-title,body.avam-setting-header-search-off .avam-unified-search,body.avam-setting-footer-off footer,body.avam-setting-mobile-menu-off .avam-mobile-menu-toggle{display:none!important}';
        echo 'body{background:var(--avam-surface);color:var(--avam-text)}.avam-unified-welcome-mark img,.avam-unified-welcome-mark video{display:block;width:100%;height:100%;object-fit:cover;border-radius:inherit}.avam-footer-copyright{display:block;margin-top:12px;color:#7a877d;font-size:11px}.avam-setting-sticky-header .avam-site-identity-bar{position:sticky;top:32px;z-index:40}.avam-setting-skip-link-off .avam-skip{display:none!important}.avam-setting-account-dashboard-off .avam-account-dashboard-content{display:none!important}.avam-setting-account-stats-off .avam-ref-stats{display:none!important}.avam-setting-account-recent-off .avam-ref-table-panel{display:none!important}.avam-setting-account-comments-link-off .avam-dash-nav a[href*="my-comments"]{display:none!important}.avam-setting-single-image-off .avam-reading-portrait-wrap,.avam-setting-single-timeline-off .avam-reading-timeline,.avam-setting-single-comments-off .avam-reading-comments,.avam-setting-single-share-off [data-share],.avam-setting-social-off [data-share]{display:none!important}';
        echo '@media(max-width:760px){body.avam-setting-mobile-search-off .avam-unified-search{display:none!important}}';
        echo 'body.avam-setting-high-contrast{filter:contrast(1.12)}'body.avam-setting-reduce-motion *,body.avam-setting-reduce-motion *:before,body.avam-setting-reduce-motion *:after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important;scroll-behavior:auto!important}';
        echo '.avam-unified-content,.avam-container,.avam-archive-results{max-width:var(--avam-content-max)}.avam-unified-memorial-card,.avam-memorial-card,.avam-card,.avam-ref-panel{border-radius:var(--avam-radius);box-shadow:var(--avam-shadow)}';
        if (($s['a11y_focus_outline']??'1')==='1') echo ':focus-visible{outline:3px solid '.$accent.'!important;outline-offset:3px}';
        if (($s['font_family']??'vazirmatn')==='system') echo 'body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif!important}';
        echo '</style>';
        if (!is_admin() && !is_singular() && !empty($s['seo_default_description']) && !defined('WPSEO_VERSION') && !class_exists('RankMath\\RankMath')) {
            echo '<meta name="description" content="'.esc_attr(wp_strip_all_tags($s['seo_default_description'])).'">';
        }
    }

    public static function assets($hook) {
        if ($hook !== 'settings_page_avam-settings') return;
        wp_enqueue_media();
        wp_enqueue_style('avam-control-center', plugins_url('assets/css/admin-control-center.css', dirname(__FILE__)), [], '1.0.0');
        wp_add_inline_script('jquery-core', "jQuery(function($){$(document).on('click','.avam-cc-media-pick',function(e){e.preventDefault();var b=$(this),target=$('#'+b.data('target')),type=b.data('type');var frame=wp.media({title:'انتخاب رسانه',button:{text:'استفاده از این فایل'},multiple:false,library:{type:type}});frame.on('select',function(){var item=frame.state().get('selection').first().toJSON();target.val(item.id).trigger('change');});frame.open();});});");
    }
}
AVAM_Admin_Center::init();
