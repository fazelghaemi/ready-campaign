<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RC_Public {

    public function __construct() {
        // هوک برای نمایش بنرها (می‌تواند wp_footer یا جای دیگری باشد)
        add_action( 'wp_footer', array( $this, 'maybe_display_banners' ) );
    }

    public function maybe_display_banners() {
        // 1. دریافت تمام بنرهای "فعال" از CPT 'rc_banner'
        $args = array(
            'post_type' => 'rc_banner',
            'post_status' => 'publish', // یا هر وضعیتی که برای بنرهای فعال در نظر می‌گیرید
            'posts_per_page' => -1, // دریافت همه بنرها برای بررسی شرایط
            'meta_query' => array(
                array(
                    'key' => '_rc_banner_enabled', // یک متادیتا برای فعال/غیرفعال بودن هر بنر
                    'value' => '1',
                    'compare' => '='
                )
            )
        );
        $active_banners = get_posts( $args );

        if ( empty( $active_banners ) ) {
            return; // هیچ بنر فعالی وجود ندارد
        }

        $banners_to_display = array();

        foreach ( $active_banners as $banner_post ) {
            // 2. برای هر بنر، شرایط نمایش آن را بررسی کنید
            if ( $this->check_banner_conditions( $banner_post->ID ) ) {
                $banners_to_display[] = $banner_post;
            }
        }

        if ( empty( $banners_to_display ) ) {
            return; // هیچ بنری شرایط نمایش را ندارد
        }

        // 3. اگر چندین بنر شرایط نمایش برای یک موقعیت را دارند، منطق A/B تست یا نمایش چرخشی را پیاده‌سازی کنید.
        // فعلا ساده‌ترین حالت: اولین بنری که شرایط را دارد یا همه بنرهایی که شرایط را دارند (اگر طراحی اجازه دهد).
        // برای سادگی، فرض می‌کنیم هر بنر موقعیت منحصر به فرد خود را دارد یا در JS مدیریت می‌شود.

        // 4. HTML بنرها را تولید و نمایش دهید.
        foreach ( $banners_to_display as $banner_to_display ) {
            $this->render_banner( $banner_to_display );
        }
    }

    /**
     * بررسی شرایط نمایش برای یک بنر خاص.
     * @param int $banner_id شناسه پست بنر
     * @return bool True اگر بنر باید نمایش داده شود، در غیر این صورت False.
     */
    private function check_banner_conditions( $banner_id ) {
        // دریافت متادیتای بنر
        $is_enabled = get_post_meta( $banner_id, '_rc_banner_enabled', true );
        if ( ! $is_enabled ) {
            return false;
        }

        // الف. بررسی تاریخ شروع و پایان (مشابه کد فعلی شما اما با get_post_meta)
        $start_date_meta = get_post_meta( $banner_id, '_rc_banner_start_date', true );
        $end_date_meta = get_post_meta( $banner_id, '_rc_banner_end_date', true );
        $today = date( 'Y-m-d' );

        if ( ! empty( $start_date_meta ) && $today < $start_date_meta ) {
            return false;
        }
        if ( ! empty( $end_date_meta ) && $today > $end_date_meta ) {
            return false;
        }

        // ب. بررسی نوع دستگاه (دسکتاپ/موبایل)
        // این متادیتا باید در متاباکس CPT بنر ذخیره شود (مثلاً _rc_banner_device_target)
        // مقادیر می‌تواند 'all', 'desktop_only', 'mobile_only' باشد.
        $device_target = get_post_meta( $banner_id, '_rc_banner_device_target', true );
        if ( empty( $device_target ) ) $device_target = 'all'; // پیش‌فرض

        $is_mobile = wp_is_mobile(); // تابع وردپرسی برای تشخیص موبایل

        if ( $device_target === 'desktop_only' && $is_mobile ) {
            return false;
        }
        if ( $device_target === 'mobile_only' && ! $is_mobile ) {
            return false;
        }

        // ج. بررسی هدف‌گذاری صفحات (URL ها، پست تایپ‌ها، دسته‌بندی‌ها و...)
        // این بخش پیچیده‌تر است و نیاز به ذخیره قوانین هدف‌گذاری در متادیتای بنر دارد.
        // به عنوان مثال:
        // $display_on_all_pages = get_post_meta( $banner_id, '_rc_banner_display_all', true );
        // $specific_pages = get_post_meta( $banner_id, '_rc_banner_specific_pages', true ); // آرایه‌ای از ID صفحات
        // if ( !$display_on_all_pages && !empty($specific_pages) && !is_singular($specific_pages) ) {
        // return false;
        // }
        // ... (این بخش نیاز به طراحی دقیق‌تر دارد)

        // د. سایر شرایط (نقش کاربر، تعداد نمایش و ...)

        return true; // اگر تمام شرایط برقرار باشند
    }

    /**
     * رندر کردن HTML یک بنر.
     * @param WP_Post $banner_post آبجکت پست بنر
     */
    private function render_banner( $banner_post ) {
        $banner_id = $banner_post->ID;

        // دریافت تمام متادیتای لازم برای نمایش بنر
        $image_url_desktop = get_post_meta( $banner_id, '_rc_banner_image_desktop', true ); // URL تصویر دسکتاپ
        $image_url_mobile = get_post_meta( $banner_id, '_rc_banner_image_mobile', true ); // URL تصویر موبایل
        $link_url = get_post_meta( $banner_id, '_rc_banner_link_url', true );
        $position = get_post_meta( $banner_id, '_rc_banner_position', true ); // مثلا 'bottom-right', 'top-left', 'center'
        $width_desktop = get_post_meta( $banner_id, '_rc_banner_width_desktop', true );
        $width_mobile = get_post_meta( $banner_id, '_rc_banner_width_mobile', true );
        $margin = get_post_meta( $banner_id, '_rc_banner_margin', true ); // فاصله از لبه‌ها
        $radius = get_post_meta( $banner_id, '_rc_banner_radius', true );
        $animation_in = get_post_meta( $banner_id, '_rc_banner_animation_in', true );
        $animation_out = get_post_meta( $banner_id, '_rc_banner_animation_out', true );
        $device_target = get_post_meta( $banner_id, '_rc_banner_device_target', true ); // برای کلاس CSS

        // انتخاب تصویر مناسب بر اساس دستگاه (این منطق می‌تواند در JS هم باشد)
        $image_to_use = ( wp_is_mobile() && !empty($image_url_mobile) ) ? $image_url_mobile : $image_url_desktop;
        if ( empty($image_to_use) && wp_is_mobile() && !empty($image_url_desktop) ) $image_to_use = $image_url_desktop; // Fallback to desktop if mobile not set
        if ( empty($image_to_use) && !wp_is_mobile() && !empty($image_url_mobile) ) $image_to_use = $image_url_mobile; // Fallback to mobile if desktop not set

        if ( empty( $image_to_use ) ) return; // اگر هیچ تصویری برای نمایش وجود ندارد

        // ساخت استایل‌های inline یا data attributes برای JS
        // در کد فعلی شما، متغیرهای CSS (--desktop-width و ...) در rc-style.css استفاده شده‌اند.
        // این رویکرد خوبی است. مقادیر را می‌توان از طریق data attributes به کانتینر بنر داد.
        $container_data_attrs = sprintf(
            'data-id="rc-banner-%1$s" data-position="%2$s" data-desktop-width="%3$spx" data-mobile-width="%4$spx" data-margin="%5$spx" data-radius="%6$spx" data-anim-in="%7$s" data-anim-out="%8$s" data-device-target="%9$s"',
            esc_attr( $banner_id ),
            esc_attr( $position ?: 'bottom-right' ),
            esc_attr( $width_desktop ?: '350' ),
            esc_attr( $width_mobile ?: '300' ),
            esc_attr( $margin ?: '20' ),
            esc_attr( $radius ?: '0' ),
            esc_attr( $animation_in ?: 'fadeIn' ),
            esc_attr( $animation_out ?: 'fadeOut' ),
            esc_attr( $device_target ?: 'all' )
        );

        // استفاده از یک فایل قالب برای رندر HTML (روش بهتر)
        // include RC_PLUGIN_DIR . 'templates/single-banner.php';
        // یا تولید HTML به صورت مستقیم:

        ?>
        <div id="rc-banner-<?php echo esc_attr( $banner_id ); ?>" 
             class="rc-banner-container <?php echo ($device_target === 'desktop_only') ? 'rc-desktop-only' : (($device_target === 'mobile_only') ? 'rc-mobile-only' : ''); ?>"
             <?php echo $container_data_attrs; ?>
             style="display:none;" <?php // JS نمایش را مدیریت می‌کند ?>>
            <div class="rc-banner-close">&times;</div>
            <div class="rc-banner-content" style="border-radius: <?php echo esc_attr( $radius ?: '0' ); ?>px;">
                <a href="<?php echo esc_url( $link_url ?: '#' ); ?>" target="_blank" rel="noopener noreferrer">
                    <img src="<?php echo esc_url( $image_to_use ); ?>" alt="<?php esc_attr_e( 'بنر تبلیغاتی', 'ready-campaign' ); ?>" />
                </a>
            </div>
        </div>
        <?php
    }
}
?>