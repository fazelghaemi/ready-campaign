<?php
// فایل: public/class-rc-public.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class RC_Public {

    public function __construct() {
        add_action( 'wp_footer', array( $this, 'maybe_display_banners' ), 999 ); // اولویت بالا برای اطمینان از اجرای دیرهنگام
    }

    /**
     * بررسی و نمایش بنرهای واجد شرایط.
     */
    public function maybe_display_banners() {
        $args = array(
            'post_type'      => 'rc_banner',
            'post_status'    => 'publish',
            'posts_per_page' => -1, // دریافت همه بنرها برای بررسی شرایط
            'meta_query'     => array(
                array(
                    'key'     => '_rc_banner_enabled', // متادیتا برای فعال/غیرفعال بودن
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
        );
        $active_banners_query = new WP_Query( $args );

        if ( ! $active_banners_query->have_posts() ) {
            return; // هیچ بنر فعالی وجود ندارد
        }

        $banners_to_display = array();

        while ( $active_banners_query->have_posts() ) {
            $active_banners_query->the_post();
            $banner_id = get_the_ID();
            if ( $this->check_banner_conditions( $banner_id ) ) {
                // اگر نیاز به مرتب‌سازی یا منطق A/B تست دارید، می‌توانید آبجکت پست کامل را اضافه کنید
                // یا فقط اطلاعات لازم برای رندر را استخراج کنید.
                $banners_to_display[] = get_post( $banner_id );
            }
        }
        wp_reset_postdata(); // بازنشانی کوئری اصلی وردپرس

        if ( empty( $banners_to_display ) ) {
            return; // هیچ بنری شرایط نمایش را ندارد
        }

        // در اینجا می‌توانید منطق بیشتری برای انتخاب بنرها پیاده‌سازی کنید
        // مثلاً اگر چندین بنر برای یک موقعیت واجد شرایط هستند، یکی را به صورت تصادفی انتخاب کنید
        // یا بر اساس اولویت/وزن. فعلاً همه بنرهای واجد شرایط را رندر می‌کنیم.

        foreach ( $banners_to_display as $banner_to_render ) {
            $this->render_banner( $banner_to_render );
        }
    }

    /**
     * بررسی شرایط نمایش برای یک بنر خاص.
     * @param int $banner_id شناسه پست بنر.
     * @return bool True اگر بنر باید نمایش داده شود، در غیر این صورت False.
     */
    private function check_banner_conditions( $banner_id ) {
        // متادیتای '_rc_banner_enabled' قبلاً در کوئری اصلی چک شده است.

        // الف. بررسی تاریخ شروع و پایان
        $start_date = get_post_meta( $banner_id, '_rc_banner_start_date', true );
        $end_date   = get_post_meta( $banner_id, '_rc_banner_end_date', true );
        $today      = date( 'Y-m-d' );

        if ( ! empty( $start_date ) && $today < $start_date ) {
            return false;
        }
        if ( ! empty( $end_date ) && $today > $end_date ) {
            return false;
        }

        // ب. بررسی هدف‌گذاری دستگاه
        $device_target = get_post_meta( $banner_id, '_rc_banner_device_target', true );
        $device_target = ! empty( $device_target ) ? $device_target : 'all'; // پیش‌فرض 'all'
        $is_mobile     = wp_is_mobile();

        if ( ( $device_target === 'desktop_only' && $is_mobile ) ||
             ( $device_target === 'mobile_only' && ! $is_mobile ) ) {
            return false;
        }

        // ج. بررسی هدف‌گذاری صفحات خاص (این بخش برای پیاده‌سازی پیشرفته‌تر است)
        // $target_pages = get_post_meta( $banner_id, '_rc_banner_target_pages', true ); // آرایه‌ای از IDها یا شرایط
        // if ( ! empty( $target_pages ) ) {
        //     global $post; // آبجکت پست فعلی در صفحه
        //     $current_page_id = isset($post->ID) ? $post->ID : 0;
        //     $is_front = is_front_page();
        //     $is_blog = is_home();
        //     // ... منطق بررسی اینکه آیا صفحه فعلی در $target_pages هست یا خیر ...
        //     // if ( ! $condition_met ) return false;
        // }

        // د. سایر شرایط (نقش کاربر، تعداد نمایش به کاربر و ...) در آینده قابل افزودن است.

        return true; // اگر تمام شرایط برقرار باشند
    }

    /**
     * رندر کردن HTML یک بنر.
     * @param WP_Post $banner_post آبجکت پست بنر.
     */
    private function render_banner( $banner_post ) {
        $banner_id = $banner_post->ID;

        // دریافت تمام متادیتای لازم برای نمایش بنر
        $image_desktop     = get_post_meta( $banner_id, '_rc_banner_image_desktop', true );
        $image_mobile      = get_post_meta( $banner_id, '_rc_banner_image_mobile', true );
        $link_url          = get_post_meta( $banner_id, '_rc_banner_link_url', true );
        $position          = get_post_meta( $banner_id, '_rc_banner_position', true ) ?: 'bottom-right';
        $device_target     = get_post_meta( $banner_id, '_rc_banner_device_target', true ) ?: 'all';
        $width_desktop     = get_post_meta( $banner_id, '_rc_banner_width_desktop', true ) ?: '350';
        $width_mobile      = get_post_meta( $banner_id, '_rc_banner_width_mobile', true ) ?: '300';
        $margin_vertical   = get_post_meta( $banner_id, '_rc_banner_margin_vertical', true ) ?: '20';
        $margin_horizontal = get_post_meta( $banner_id, '_rc_banner_margin_horizontal', true ) ?: '20'; // اضافه شد
        $radius            = get_post_meta( $banner_id, '_rc_banner_radius', true ) ?: '0';
        $animation_in      = get_post_meta( $banner_id, '_rc_banner_animation_in', true ) ?: 'fadeIn';
        $animation_out     = get_post_meta( $banner_id, '_rc_banner_animation_out', true ) ?: 'fadeOut';

        // انتخاب تصویر مناسب بر اساس دستگاه
        $image_to_use = ( wp_is_mobile() && !empty($image_mobile) ) ? $image_mobile : $image_desktop;
        // اگر تصویر موبایل خالی بود و در حالت موبایل هستیم، از تصویر دسکتاپ استفاده کن (اگر وجود دارد)
        if ( wp_is_mobile() && empty($image_to_use) && !empty($image_desktop) ) {
            $image_to_use = $image_desktop;
        }
        // اگر تصویر دسکتاپ خالی بود و در حالت دسکتاپ هستیم، از تصویر موبایل استفاده کن (اگر وجود دارد و این منطق مطلوب است)
        // معمولاً اگر تصویر اصلی (دسکتاپ) نباشد، کلاً نمایش داده نمی‌شود.
        
        if ( empty( $image_to_use ) ) {
            return; // اگر هیچ تصویری برای نمایش وجود ندارد
        }

        // ساخت data attributes برای استفاده توسط JavaScript
        $container_data_attrs = sprintf(
            'data-id="%1$s" data-position="%2$s" data-desktop-width="%3$s" data-mobile-width="%4$s" data-margin="%5$s" data-side-margin="%6$s" data-radius="%7$s" data-anim-in="%8$s" data-anim-out="%9$s" data-device-target="%10$s"',
            esc_attr( 'rc-banner-' . $banner_id ), // ID منحصر به فرد برای هر کانتینر
            esc_attr( $position ),
            esc_attr( $width_desktop ),
            esc_attr( $width_mobile ),
            esc_attr( $margin_vertical ),
            esc_attr( $margin_horizontal ), // اضافه شد
            esc_attr( $radius ),
            esc_attr( $animation_in ),
            esc_attr( $animation_out ),
            esc_attr( $device_target )
        );

        $link_target = '_blank'; // یا از یک متادیتای دیگر برای کنترل target بخوانید

        ?>
        <div id="rc-banner-<?php echo esc_attr( $banner_id ); ?>" 
             class="rc-banner-container" <?php /* کلاس‌های rc-desktop-only/rc-mobile-only دیگر اینجا لازم نیستند اگر JS نمایش را بر اساس data-device-target کنترل می‌کند یا CSS آن‌ها را دارد */ ?>
             <?php echo $container_data_attrs; ?>
             style="display:none;" <?php // JavaScript نمایش را مدیریت می‌کند ?>>
            <div class="rc-banner-close" role="button" tabindex="0" aria-label="<?php esc_attr_e('بستن بنر', 'ready-campaign'); ?>">&times;</div>
            <div class="rc-banner-content" style="border-radius: <?php echo esc_attr( $radius ); ?>px;">
                <?php if ( !empty( $link_url ) ): ?>
                    <a href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr($link_target); ?>" rel="noopener noreferrer">
                        <img src="<?php echo esc_url( $image_to_use ); ?>" alt="<?php echo esc_attr( $banner_post->post_title ); // استفاده از عنوان بنر به عنوان متن alt ?>" />
                    </a>
                <?php else: ?>
                    <img src="<?php echo esc_url( $image_to_use ); ?>" alt="<?php echo esc_attr( $banner_post->post_title ); ?>" />
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
?>
