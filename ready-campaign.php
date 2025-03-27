<?php
/*
Plugin Name: ردی کمپین | افزونه پیاده سازی کمپین ها
Plugin URI: https://readystudio.ir/
Description: ردی کمپین، افزونه‌ای تخصصی برای برگزاری کمپین در وب‌سایت های وردپرسی است. آپلود و نمایش بنر (دسکتاپ و موبایل) به همراه لینک‌های جداگانه، تفکیک تنظیمات در دو تب مجزا و قابلیت Campaign URL Builder و چندین قابلیت جذاب و کاربردی دیگر
Version: 1.5.0
Author: ردی استودیو | فاضل قائمی
Author URI: https://fazelghaemi.ir/
License: GPL2
Text Domain: ready-campaign
Tags: افزونه وردپرسی تبلیغات و برگزاری کمپین
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Ready_Campaign' ) ) :

class Ready_Campaign {

    const VERSION = '1.5.0';
    private $options;
    private $active_tab;

    public function __construct() {
        // دریافت تنظیمات ذخیره‌شده به صورت آرایه
        $this->options = get_option( 'rc_options', array() );
        $this->active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'desktop';

        // افزودن صفحه تنظیمات به منوی مدیریت
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );

        // نمایش بنر در فرانت‌اند (فقط در سایت)
        if ( ! is_admin() ) {
            add_action( 'wp_footer', array( $this, 'display_banner' ) );
        }

        // بارگذاری فایل‌های CSS و JS
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    // افزودن منوی تنظیمات در بخش مدیریت
    public function add_plugin_page() {
        add_menu_page(
            'تنظیمات ردی کمپین',
            'ردی کمپین',
            'manage_options',
            'rc-campaign-settings',
            array( $this, 'create_admin_page' ),
            'dashicons-megaphone',
            90
        );
    }

    // صفحه تنظیمات افزونه
    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>تنظیمات ردی کمپین</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=rc-campaign-settings&tab=desktop" class="nav-tab <?php echo $this->active_tab == 'desktop' ? 'nav-tab-active' : ''; ?>">تنظیمات دسکتاپ</a>
                <a href="?page=rc-campaign-settings&tab=mobile" class="nav-tab <?php echo $this->active_tab == 'mobile' ? 'nav-tab-active' : ''; ?>">تنظیمات موبایل</a>
                <a href="?page=rc-campaign-settings&tab=campaign" class="nav-tab <?php echo $this->active_tab == 'campaign' ? 'nav-tab-active' : ''; ?>">لینک ساز کمپین</a>
            </h2>
            
            <form method="post" action="options.php">
                <?php
                    settings_fields( 'rc_option_group' );
                    
                    if( $this->active_tab == 'desktop' ) {
                        do_settings_sections( 'rc-campaign-desktop-admin' );
                    } elseif( $this->active_tab == 'mobile' ) {
                        do_settings_sections( 'rc-campaign-mobile-admin' );
                    } else {
                        do_settings_sections( 'rc-campaign-utm-admin' );
                    }
                    
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // ثبت تنظیمات و فیلدهای مورد نیاز
    public function page_init() {
        register_setting(
            'rc_option_group',
            'rc_options',
            array( $this, 'sanitize' )
        );

        // بخش تنظیمات دسکتاپ
        add_settings_section(
            'rc_desktop_section',
            'تنظیمات بنر دسکتاپ',
            null,
            'rc-campaign-desktop-admin'
        );

        $desktop_fields = array(
            'desktop_enabled'  => array('label' => 'فعال/غیرفعال', 'callback' => 'desktop_enabled_callback'),
            'desktop_banner'   => array('label' => 'بنر دسکتاپ', 'callback' => 'desktop_banner_callback'),
            'desktop_link'     => array('label' => 'لینک بنر دسکتاپ', 'callback' => 'desktop_link_callback'),
            'desktop_position' => array('label' => 'موقعیت بنر', 'callback' => 'desktop_position_callback'),
            'desktop_radius'   => array('label' => 'انحنای گوشه‌ها (px)', 'callback' => 'desktop_radius_callback'),
            'desktop_margin'   => array('label' => 'فاصله بنر از پایین (px)', 'callback' => 'desktop_margin_callback'),
            'desktop_width'    => array('label' => 'عرض بنر (px)', 'callback' => 'desktop_width_callback'),
            'desktop_start_date' => array('label' => 'تاریخ شروع نمایش', 'callback' => 'desktop_start_date_callback'),
            'desktop_end_date'   => array('label' => 'تاریخ پایان نمایش', 'callback' => 'desktop_end_date_callback'),
            'desktop_animation_in' => array('label' => 'انیمیشن ورود', 'callback' => 'desktop_animation_in_callback'),
            'desktop_animation_out' => array('label' => 'انیمیشن خروج', 'callback' => 'desktop_animation_out_callback')
        );

        foreach ( $desktop_fields as $field_id => $field ) {
            add_settings_field(
                $field_id,
                $field['label'],
                array( $this, $field['callback'] ),
                'rc-campaign-desktop-admin',
                'rc_desktop_section'
            );
        }

        // بخش تنظیمات موبایل
        add_settings_section(
            'rc_mobile_section',
            'تنظیمات بنر موبایل',
            null,
            'rc-campaign-mobile-admin'
        );

        $mobile_fields = array(
            'mobile_enabled'  => array('label' => 'فعال/غیرفعال', 'callback' => 'mobile_enabled_callback'),
            'mobile_banner'   => array('label' => 'بنر موبایل', 'callback' => 'mobile_banner_callback'),
            'mobile_link'     => array('label' => 'لینک بنر موبایل', 'callback' => 'mobile_link_callback'),
            'mobile_position' => array('label' => 'موقعیت بنر', 'callback' => 'mobile_position_callback'),
            'mobile_radius'   => array('label' => 'انحنای گوشه‌ها (px)', 'callback' => 'mobile_radius_callback'),
            'mobile_margin'   => array('label' => 'فاصله بنر از پایین (px)', 'callback' => 'mobile_margin_callback'),
            'mobile_width'    => array('label' => 'عرض بنر (px)', 'callback' => 'mobile_width_callback'),
            'mobile_start_date' => array('label' => 'تاریخ شروع نمایش', 'callback' => 'mobile_start_date_callback'),
            'mobile_end_date'   => array('label' => 'تاریخ پایان نمایش', 'callback' => 'mobile_end_date_callback'),
            'mobile_animation_in' => array('label' => 'انیمیشن ورود', 'callback' => 'mobile_animation_in_callback'),
            'mobile_animation_out' => array('label' => 'انیمیشن خروج', 'callback' => 'mobile_animation_out_callback')
        );

        foreach ( $mobile_fields as $field_id => $field ) {
            add_settings_field(
                $field_id,
                $field['label'],
                array( $this, $field['callback'] ),
                'rc-campaign-mobile-admin',
                'rc_mobile_section'
            );
        }

        // بخش تنظیمات کمپین UTM
        add_settings_section(
            'rc_campaign_builder_section',
            'ساخت لینک کمپین UTM',
            array( $this, 'campaign_builder_section_callback' ),
            'rc-campaign-utm-admin'
        );

        $utm_fields = array(
            'website_url'      => array('label' => 'آدرس وب سایت*', 'callback' => 'website_url_callback'),
            'campaign_id'      => array('label' => 'شناسه کمپین', 'callback' => 'campaign_id_callback'),
            'campaign_source'  => array('label' => 'منبع کمپین*', 'callback' => 'campaign_source_callback'),
            'campaign_medium'  => array('label' => 'رسانه کمپین*', 'callback' => 'campaign_medium_callback'),
            'campaign_name'    => array('label' => 'نام کمپین', 'callback' => 'campaign_name_callback'),
            'campaign_term'    => array('label' => 'عبارت کمپین', 'callback' => 'campaign_term_callback'),
            'campaign_content' => array('label' => 'محتوای کمپین', 'callback' => 'campaign_content_callback'),
            'utm_result'       => array('label' => 'لینک نهایی کمپین', 'callback' => 'utm_result_callback')
        );

        foreach ( $utm_fields as $field_id => $field ) {
            add_settings_field(
                $field_id,
                $field['label'],
                array( $this, $field['callback'] ),
                'rc-campaign-utm-admin',
                'rc_campaign_builder_section'
            );
        }
    }

    // توضیحات بخش ساخت لینک کمپین
    public function campaign_builder_section_callback() {
        echo '<p>با پر کردن فیلدهای زیر، لینک UTM شما به صورت خودکار ساخته می‌شود. فیلدهای ستاره‌دار (*) اجباری هستند.</p>';
    }

    // تابع sanitize برای پاکسازی ورودی‌ها
    public function sanitize( $input ) {
        $new_input = array();
        
        // Desktop settings
        $new_input['desktop_enabled'] = isset( $input['desktop_enabled'] ) ? 1 : 0;
        $new_input['desktop_banner'] = isset( $input['desktop_banner'] ) ? esc_url_raw( $input['desktop_banner'] ) : '';
        $new_input['desktop_link'] = isset( $input['desktop_link'] ) ? esc_url_raw( $input['desktop_link'] ) : '';
        $new_input['desktop_position'] = isset( $input['desktop_position'] ) ? sanitize_text_field( $input['desktop_position'] ) : 'bottom-right';
        $new_input['desktop_radius'] = isset( $input['desktop_radius'] ) ? absint( $input['desktop_radius'] ) : 0;
        $new_input['desktop_margin'] = isset( $input['desktop_margin'] ) ? absint( $input['desktop_margin'] ) : 20;
        $new_input['desktop_width'] = isset( $input['desktop_width'] ) ? absint( $input['desktop_width'] ) : 350;
        $new_input['desktop_start_date'] = isset( $input['desktop_start_date'] ) ? sanitize_text_field( $input['desktop_start_date'] ) : '';
        $new_input['desktop_end_date'] = isset( $input['desktop_end_date'] ) ? sanitize_text_field( $input['desktop_end_date'] ) : '';
        $new_input['desktop_animation_in'] = isset( $input['desktop_animation_in'] ) ? sanitize_text_field( $input['desktop_animation_in'] ) : 'fadeIn';
        $new_input['desktop_animation_out'] = isset( $input['desktop_animation_out'] ) ? sanitize_text_field( $input['desktop_animation_out'] ) : 'fadeOut';
        
        // Mobile settings
        $new_input['mobile_enabled'] = isset( $input['mobile_enabled'] ) ? 1 : 0;
        $new_input['mobile_banner'] = isset( $input['mobile_banner'] ) ? esc_url_raw( $input['mobile_banner'] ) : '';
        $new_input['mobile_link'] = isset( $input['mobile_link'] ) ? esc_url_raw( $input['mobile_link'] ) : '';
        $new_input['mobile_position'] = isset( $input['mobile_position'] ) ? sanitize_text_field( $input['mobile_position'] ) : 'bottom-right';
        $new_input['mobile_radius'] = isset( $input['mobile_radius'] ) ? absint( $input['mobile_radius'] ) : 0;
        $new_input['mobile_margin'] = isset( $input['mobile_margin'] ) ? absint( $input['mobile_margin'] ) : 20;
        $new_input['mobile_width'] = isset( $input['mobile_width'] ) ? absint( $input['mobile_width'] ) : 300;
        $new_input['mobile_start_date'] = isset( $input['mobile_start_date'] ) ? sanitize_text_field( $input['mobile_start_date'] ) : '';
        $new_input['mobile_end_date'] = isset( $input['mobile_end_date'] ) ? sanitize_text_field( $input['mobile_end_date'] ) : '';
        $new_input['mobile_animation_in'] = isset( $input['mobile_animation_in'] ) ? sanitize_text_field( $input['mobile_animation_in'] ) : 'fadeIn';
        $new_input['mobile_animation_out'] = isset( $input['mobile_animation_out'] ) ? sanitize_text_field( $input['mobile_animation_out'] ) : 'fadeOut';
        
        // Campaign UTM settings
        $new_input['website_url'] = isset( $input['website_url'] ) ? esc_url_raw( $input['website_url'] ) : '';
        $new_input['campaign_id'] = isset( $input['campaign_id'] ) ? sanitize_text_field( $input['campaign_id'] ) : '';
        $new_input['campaign_source'] = isset( $input['campaign_source'] ) ? sanitize_text_field( $input['campaign_source'] ) : '';
        $new_input['campaign_medium'] = isset( $input['campaign_medium'] ) ? sanitize_text_field( $input['campaign_medium'] ) : '';
        $new_input['campaign_name'] = isset( $input['campaign_name'] ) ? sanitize_text_field( $input['campaign_name'] ) : '';
        $new_input['campaign_term'] = isset( $input['campaign_term'] ) ? sanitize_text_field( $input['campaign_term'] ) : '';
        $new_input['campaign_content'] = isset( $input['campaign_content'] ) ? sanitize_text_field( $input['campaign_content'] ) : '';
        
        return $new_input;
    }

    // Desktop Settings Callbacks
    public function desktop_enabled_callback() {
        $checked = isset( $this->options['desktop_enabled'] ) ? 1 : 0;
        ?>
        <input type="checkbox" id="desktop_enabled" name="rc_options[desktop_enabled]" value="1" <?php checked( $checked, 1 ); ?> />
        <label for="desktop_enabled">فعال کردن نمایش بنر دسکتاپ</label>
        <?php
    }
    
    public function desktop_banner_callback() {
        $desktop_banner = isset( $this->options['desktop_banner'] ) ? esc_url( $this->options['desktop_banner'] ) : '';
        ?>
        <div class="rc-media-uploader-wrapper">
            <input type="text" id="desktop_banner" name="rc_options[desktop_banner]" value="<?php echo $desktop_banner; ?>" style="width:70%;" placeholder="آدرس بنر دسکتاپ" />
            <input type="button" class="button rc-media-upload-button" data-target="#desktop_banner" value="انتخاب بنر دسکتاپ" />
            <div class="rc-media-preview" style="margin-top:10px;">
                <?php if ( $desktop_banner ) : ?>
                    <img src="<?php echo $desktop_banner; ?>" style="max-width:200px; height:auto;" />
                <?php endif; ?>
            </div>
        </div>
        <p class="description">می‌توانید چند آدرس تصویر را با کاما از هم جدا کنید تا به صورت چرخشی نمایش داده شوند.</p>
        <?php
    }

    public function desktop_link_callback() {
        $desktop_link = isset( $this->options['desktop_link'] ) ? esc_url( $this->options['desktop_link'] ) : '';
        ?>
        <input type="url" id="desktop_link" name="rc_options[desktop_link]" value="<?php echo $desktop_link; ?>" style="width:70%;" placeholder="لینک بنر دسکتاپ" />
        <p class="description">می‌توانید از ابزار ساخت لینک کمپین UTM در تب "لینک ساز کمپین" استفاده کنید.</p>
        <?php
    }

    public function desktop_position_callback() {
        $position = isset( $this->options['desktop_position'] ) ? $this->options['desktop_position'] : 'bottom-right';
        ?>
        <select id="desktop_position" name="rc_options[desktop_position]" style="width:180px;">
            <option value="bottom-right" <?php selected( $position, 'bottom-right' ); ?>>سمت راست پایین</option>
            <option value="bottom-left" <?php selected( $position, 'bottom-left' ); ?>>سمت چپ پایین</option>
            <option value="top-right" <?php selected( $position, 'top-right' ); ?>>سمت راست بالا</option>
            <option value="top-left" <?php selected( $position, 'top-left' ); ?>>سمت چپ بالا</option>
            <option value="center" <?php selected( $position, 'center' ); ?>>وسط صفحه</option>
        </select>
        <?php
    }

    public function desktop_radius_callback() {
        $radius = isset( $this->options['desktop_radius'] ) ? absint( $this->options['desktop_radius'] ) : 0;
        ?>
        <input type="number" id="desktop_radius" name="rc_options[desktop_radius]" value="<?php echo $radius; ?>" style="width:150px;" placeholder="px" />
        <?php
    }

    public function desktop_margin_callback() {
        $margin = isset( $this->options['desktop_margin'] ) ? absint( $this->options['desktop_margin'] ) : 20;
        ?>
        <input type="number" id="desktop_margin" name="rc_options[desktop_margin]" value="<?php echo $margin; ?>" style="width:150px;" placeholder="px" />
        <?php
    }

    public function desktop_width_callback() {
        $width = isset( $this->options['desktop_width'] ) ? absint( $this->options['desktop_width'] ) : 350;
        ?>
        <input type="number" id="desktop_width" name="rc_options[desktop_width]" value="<?php echo $width; ?>" style="width:150px;" placeholder="px" />
        <?php
    }

    public function desktop_start_date_callback() {
        $start_date = isset( $this->options['desktop_start_date'] ) ? $this->options['desktop_start_date'] : '';
        ?>
        <input type="date" id="desktop_start_date" name="rc_options[desktop_start_date]" value="<?php echo $start_date; ?>" style="width:150px;" />
        <?php
    }

    public function desktop_end_date_callback() {
        $end_date = isset( $this->options['desktop_end_date'] ) ? $this->options['desktop_end_date'] : '';
        ?>
        <input type="date" id="desktop_end_date" name="rc_options[desktop_end_date]" value="<?php echo $end_date; ?>" style="width:150px;" />
        <?php
    }

    public function desktop_animation_in_callback() {
        $animation = isset( $this->options['desktop_animation_in'] ) ? $this->options['desktop_animation_in'] : 'fadeIn';
        ?>
        <select id="desktop_animation_in" name="rc_options[desktop_animation_in]" style="width:180px;">
            <option value="fadeIn" <?php selected( $animation, 'fadeIn' ); ?>>محو شدن</option>
            <option value="slideIn" <?php selected( $animation, 'slideIn' ); ?>>کشویی</option>
            <option value="bounceIn" <?php selected( $animation, 'bounceIn' ); ?>>جهشی</option>
            <option value="flipIn" <?php selected( $animation, 'flipIn' ); ?>>چرخشی</option>
            <option value="none" <?php selected( $animation, 'none' ); ?>>بدون انیمیشن</option>
        </select>
        <?php
    }

    public function desktop_animation_out_callback() {
        $animation = isset( $this->options['desktop_animation_out'] ) ? $this->options['desktop_animation_out'] : 'fadeOut';
        ?>
        <select id="desktop_animation_out" name="rc_options[desktop_animation_out]" style="width:180px;">
            <option value="fadeOut" <?php selected( $animation, 'fadeOut' ); ?>>محو شدن</option>
            <option value="slideOut" <?php selected( $animation, 'slideOut' ); ?>>کشویی</option>
            <option value="bounceOut" <?php selected( $animation, 'bounceOut' ); ?>>جهشی</option>
            <option value="flipOut" <?php selected( $animation, 'flipOut' ); ?>>چرخشی</option>
            <option value="none" <?php selected( $animation, 'none' ); ?>>بدون انیمیشن</option>
        </select>
        <?php
    }

    // Mobile Settings Callbacks
    public function mobile_enabled_callback() {
        $checked = isset( $this->options['mobile_enabled'] ) ? 1 : 0;
        ?>
        <input type="checkbox" id="mobile_enabled" name="rc_options[mobile_enabled]" value="1" <?php checked( $checked, 1 ); ?> />
        <label for="mobile_enabled">فعال کردن نمایش بنر موبایل</label>
        <?php
    }
    
    public function mobile_banner_callback() {
        $mobile_banner = isset( $this->options['mobile_banner'] ) ? esc_url( $this->options['mobile_banner'] ) : '';
        ?>
        <div class="rc-media-uploader-wrapper">
            <input type="text" id="mobile_banner" name="rc_options[mobile_banner]" value="<?php echo $mobile_banner; ?>" style="width:70%;" placeholder="آدرس بنر موبایل" />
            <input type="button" class="button rc-media-upload-button" data-target="#mobile_banner" value="انتخاب بنر موبایل" />
            <div class="rc-media-preview" style="margin-top:10px;">
                <?php if ( $mobile_banner ) : ?>
                    <img src="<?php echo $mobile_banner; ?>" style="max-width:200px; height:auto;" />
                <?php endif; ?>
            </div>
        </div>
        <p class="description">می‌توانید چند آدرس تصویر را با کاما از هم جدا کنید تا به صورت چرخشی نمایش داده شوند.</p>
        <?php
    }

    public function mobile_link_callback() {
        $mobile_link = isset( $this->options['mobile_link'] ) ? esc_url( $this->options['mobile_link'] ) : '';
        ?>
        <input type="url" id="mobile_link" name="rc_options[mobile_link]" value="<?php echo $mobile_link; ?>" style="width:70%;" placeholder="لینک بنر موبایل" />
        <p class="description">می‌توانید از ابزار ساخت لینک کمپین UTM در تب "لینک ساز کمپین" استفاده کنید.</p>
        <?php
    }

    public function mobile_position_callback() {
        $position = isset( $this->options['mobile_position'] ) ? $this->options['mobile_position'] : 'bottom-right';
        ?>
        <select id="mobile_position" name="rc_options[mobile_position]" style="width:180px;">
            <option value="bottom-right" <?php selected( $position, 'bottom-right' ); ?>>سمت راست پایین</option>
            <option value="bottom-left" <?php selected( $position, 'bottom-left' ); ?>>سمت چپ پایین</option>
            <option value="top-right" <?php selected( $position, 'top-right' ); ?>>سمت راست بالا</option>
            <option value="top-left" <?php selected( $position, 'top-left' ); ?>>سمت چپ بالا</option>
            <option value="center" <?php selected( $position, 'center' ); ?>>وسط صفحه</option>
        </select>
        <?php
    }

    public function mobile_radius_callback() {
        $radius = isset( $this->options['mobile_radius'] ) ? absint( $this->options['mobile_radius'] ) : 0;
        ?>
        <input type="number" id="mobile_radius" name="rc_options[mobile_radius]" value="<?php echo $radius; ?>" style="width:150px;" placeholder="px" />
        <?php
    }

    public function mobile_margin_callback() {
        $margin = isset( $this->options['mobile_margin'] ) ? absint( $this->options['mobile_margin'] ) : 20;
        ?>
        <input type="number" id="mobile_margin" name="rc_options[mobile_margin]" value="<?php echo $margin; ?>" style="width:150px;" placeholder="px" />
        <?php
    }

    public function mobile_width_callback() {
        $width = isset( $this->options['mobile_width'] ) ? absint( $this->options['mobile_width'] ) : 300;
        ?>
        <input type="number" id="mobile_width" name="rc_options[mobile_width]" value="<?php echo $width; ?>" style="width:150px;" placeholder="px" />
        <?php
    }

    public function mobile_start_date_callback() {
        $start_date = isset( $this->options['mobile_start_date'] ) ? $this->options['mobile_start_date'] : '';
        ?>
        <input type="date" id="mobile_start_date" name="rc_options[mobile_start_date]" value="<?php echo $start_date; ?>" style="width:150px;" />
        <?php
    }

    public function mobile_end_date_callback() {
        $end_date = isset( $this->options['mobile_end_date'] ) ? $this->options['mobile_end_date'] : '';
        ?>
        <input type="date" id="mobile_end_date" name="rc_options[mobile_end_date]" value="<?php echo $end_date; ?>" style="width:150px;" />
        <?php
    }

    public function mobile_animation_in_callback() {
        $animation = isset( $this->options['mobile_animation_in'] ) ? $this->options['mobile_animation_in'] : 'fadeIn';
        ?>
        <select id="mobile_animation_in" name="rc_options[mobile_animation_in]" style="width:180px;">
            <option value="fadeIn" <?php selected( $animation, 'fadeIn' ); ?>>محو شدن</option>
            <option value="slideIn" <?php selected( $animation, 'slideIn' ); ?>>کشویی</option>
            <option value="bounceIn" <?php selected( $animation, 'bounceIn' ); ?>>جهشی</option>
            <option value="flipIn" <?php selected( $animation, 'flipIn' ); ?>>چرخشی</option>
            <option value="none" <?php selected( $animation, 'none' ); ?>>بدون انیمیشن</option>
        </select>
        <?php
    }

    public function mobile_animation_out_callback() {
        $animation = isset( $this->options['mobile_animation_out'] ) ? $this->options['mobile_animation_out'] : 'fadeOut';
        ?>
        <select id="mobile_animation_out" name="rc_options[mobile_animation_out]" style="width:180px;">
            <option value="fadeOut" <?php selected( $animation, 'fadeOut' ); ?>>محو شدن</option>
            <option value="slideOut" <?php selected( $animation, 'slideOut' ); ?>>کشویی</option>
            <option value="bounceOut" <?php selected( $animation, 'bounceOut' ); ?>>جهشی</option>
            <option value="flipOut" <?php selected( $animation, 'flipOut' ); ?>>چرخشی</option>
            <option value="none" <?php selected( $animation, 'none' ); ?>>بدون انیمیشن</option>
        </select>
        <?php
    }

    // Campaign UTM Callbacks
    public function website_url_callback() {
        $website_url = isset( $this->options['website_url'] ) ? esc_url( $this->options['website_url'] ) : '';
        ?>
        <input type="url" id="website_url" name="rc_options[website_url]" value="<?php echo $website_url; ?>" class="regular-text" style="width:70%;" placeholder="https://www.example.com" />
        <p class="description">آدرس کامل وب‌سایت مانند https://www.example.com</p>
        <?php
    }

    public function campaign_id_callback() {
        $campaign_id = isset( $this->options['campaign_id'] ) ? sanitize_text_field( $this->options['campaign_id'] ) : '';
        ?>
        <input type="text" id="campaign_id" name="rc_options[campaign_id]" value="<?php echo $campaign_id; ?>" class="regular-text" style="width:70%;" />
        <p class="description">شناسه کمپین تبلیغاتی</p>
        <?php
    }

    public function campaign_source_callback() {
        $campaign_source = isset( $this->options['campaign_source'] ) ? sanitize_text_field( $this->options['campaign_source'] ) : '';
        ?>
        <input type="text" id="campaign_source" name="rc_options[campaign_source]" value="<?php echo $campaign_source; ?>" class="regular-text" style="width:70%;" placeholder="google, newsletter" />
        <p class="description">منبع ارجاع دهنده (مثلاً: google، instagram، newsletter)</p>
        <?php
    }

    public function campaign_medium_callback() {
        $campaign_medium = isset( $this->options['campaign_medium'] ) ? sanitize_text_field( $this->options['campaign_medium'] ) : '';
        ?>
        <input type="text" id="campaign_medium" name="rc_options[campaign_medium]" value="<?php echo $campaign_medium; ?>" class="regular-text" style="width:70%;" placeholder="cpc, banner, email" />
        <p class="description">رسانه تبلیغات (مثلاً: cpc، banner، email)</p>
        <?php
    }

    public function campaign_name_callback() {
        $campaign_name = isset( $this->options['campaign_name'] ) ? sanitize_text_field( $this->options['campaign_name'] ) : '';
        ?>
        <input type="text" id="campaign_name" name="rc_options[campaign_name]" value="<?php echo $campaign_name; ?>" class="regular-text" style="width:70%;" placeholder="summer_sale" />
        <p class="description">نام محصول، کد تخفیف یا شعار تبلیغات</p>
        <?php
    }

    public function campaign_term_callback() {
        $campaign_term = isset( $this->options['campaign_term'] ) ? sanitize_text_field( $this->options['campaign_term'] ) : '';
        ?>
        <input type="text" id="campaign_term" name="rc_options[campaign_term]" value="<?php echo $campaign_term; ?>" class="regular-text" style="width:70%;" />
        <p class="description">کلمات کلیدی که برای کمپین خریداری شده‌اند</p>
        <?php
    }

    public function campaign_content_callback() {
        $campaign_content = isset( $this->options['campaign_content'] ) ? sanitize_text_field( $this->options['campaign_content'] ) : '';
        ?>
        <input type="text" id="campaign_content" name="rc_options[campaign_content]" value="<?php echo $campaign_content; ?>" class="regular-text" style="width:70%;" />
        <p class="description">برای تفکیک تبلیغات مشابه (مانند: logolink، textlink)</p>
        <?php
    }

    public function utm_result_callback() {
        $website_url = isset( $this->options['website_url'] ) ? esc_url( $this->options['website_url'] ) : '';
        $campaign_id = isset( $this->options['campaign_id'] ) ? sanitize_text_field( $this->options['campaign_id'] ) : '';
        $campaign_source = isset( $this->options['campaign_source'] ) ? sanitize_text_field( $this->options['campaign_source'] ) : '';
        $campaign_medium = isset( $this->options['campaign_medium'] ) ? sanitize_text_field( $this->options['campaign_medium'] ) : '';
        $campaign_name = isset( $this->options['campaign_name'] ) ? sanitize_text_field( $this->options['campaign_name'] ) : '';
        $campaign_term = isset( $this->options['campaign_term'] ) ? sanitize_text_field( $this->options['campaign_term'] ) : '';
        $campaign_content = isset( $this->options['campaign_content'] ) ? sanitize_text_field( $this->options['campaign_content'] ) : '';
        
        // ساخت URL کمپین
        $utm_url = '';
        if (!empty($website_url) && !empty($campaign_source) && !empty($campaign_medium)) {
            $utm_url = trailingslashit($website_url);
            $utm_url .= '?utm_source=' . urlencode($campaign_source);
            $utm_url .= '&utm_medium=' . urlencode($campaign_medium);
            
            if (!empty($campaign_id)) {
                $utm_url .= '&utm_id=' . urlencode($campaign_id);
            }
            
            if (!empty($campaign_name)) {
                $utm_url .= '&utm_campaign=' . urlencode($campaign_name);
            }
            
            if (!empty($campaign_term)) {
                $utm_url .= '&utm_term=' . urlencode($campaign_term);
            }
            
            if (!empty($campaign_content)) {
                $utm_url .= '&utm_content=' . urlencode($campaign_content);
            }
        }
        ?>
        <div class="utm-result-container">
            <textarea id="utm_result" readonly style="width:70%; height:80px;"><?php echo esc_url($utm_url); ?></textarea>
            <p><button type="button" class="button" id="copy-utm-url">کپی لینک</button>
            <button type="button" class="button" id="apply-to-desktop">استفاده در بنر دسکتاپ</button>
            <button type="button" class="button" id="apply-to-mobile">استفاده در بنر موبایل</button></p>
        </div>
        <p class="description">برای کپی لینک نهایی، روی دکمه کپی کلیک کنید یا آن را در بنر مورد نظر استفاده کنید.</p>
        <?php
    }

    // نمایش بنر در فرانت‌اند
    public function display_banner() {
        $options = $this->options;
        
        // بررسی تاریخ نمایش دسکتاپ
        $desktop_show = isset($options['desktop_enabled']) && $options['desktop_enabled'] == 1;
        if ($desktop_show && !empty($options['desktop_start_date'])) {
            $today = date('Y-m-d');
            $start_date = $options['desktop_start_date'];
            if ($today < $start_date) {
                $desktop_show = false;
            }
        }
        if ($desktop_show && !empty($options['desktop_end_date'])) {
            $today = date('Y-m-d');
            $end_date = $options['desktop_end_date'];
            if ($today > $end_date) {
                $desktop_show = false;
            }
        }
        
        // بررسی تاریخ نمایش موبایل
        $mobile_show = isset($options['mobile_enabled']) && $options['mobile_enabled'] == 1;
        if ($mobile_show && !empty($options['mobile_start_date'])) {
            $today = date('Y-m-d');
            $start_date = $options['mobile_start_date'];
            if ($today < $start_date) {
                $mobile_show = false;
            }
        }
        if ($mobile_show && !empty($options['mobile_end_date'])) {
            $today = date('Y-m-d');
            $end_date = $options['mobile_end_date'];
            if ($today > $end_date) {
                $mobile_show = false;
            }
        }
        
        // اگر هر دو بنر غیرفعال باشند، خروجی ندهید
        if (!$desktop_show && !$mobile_show) {
            return;
        }
        
        $desktop_banner = isset($options['desktop_banner']) ? $options['desktop_banner'] : '';
        $desktop_link = isset($options['desktop_link']) ? $options['desktop_link'] : '#';
        $mobile_banner = isset($options['mobile_banner']) ? $options['mobile_banner'] : '';
        $mobile_link = isset($options['mobile_link']) ? $options['mobile_link'] : '#';
        
        // بررسی بنرهای چرخشی
        $desktop_banners = $desktop_banner ? explode(',', $desktop_banner) : array();
        $mobile_banners = $mobile_banner ? explode(',', $mobile_banner) : array();
        
        // انتخاب تصادفی در صورت وجود چند بنر
        $desktop_banner = !empty($desktop_banners) ? trim($desktop_banners[array_rand($desktop_banners)]) : '';
        $mobile_banner = !empty($mobile_banners) ? trim($mobile_banners[array_rand($mobile_banners)]) : '';
        
        $desktop_position = isset($options['desktop_position']) ? $options['desktop_position'] : 'bottom-right';
        $mobile_position = isset($options['mobile_position']) ? $options['mobile_position'] : 'bottom-right';
        $desktop_radius = isset($options['desktop_radius']) ? absint($options['desktop_radius']) : 0;
        $mobile_radius = isset($options['mobile_radius']) ? absint($options['mobile_radius']) : 0;
        $desktop_margin = isset($options['desktop_margin']) ? absint($options['desktop_margin']) : 20;
        $mobile_margin = isset($options['mobile_margin']) ? absint($options['mobile_margin']) : 20;
        $desktop_width = isset($options['desktop_width']) ? absint($options['desktop_width']) : 350;
        $mobile_width = isset($options['mobile_width']) ? absint($options['mobile_width']) : 300;
        $desktop_animation_in = isset($options['desktop_animation_in']) ? $options['desktop_animation_in'] : 'fadeIn';
        $desktop_animation_out = isset($options['desktop_animation_out']) ? $options['desktop_animation_out'] : 'fadeOut';
        $mobile_animation_in = isset($options['mobile_animation_in']) ? $options['mobile_animation_in'] : 'fadeIn';
        $mobile_animation_out = isset($options['mobile_animation_out']) ? $options['mobile_animation_out'] : 'fadeOut';

        // تنظیم CSS custom properties برای کانتینر بنر
        $container_styles = array(
            'position' => 'fixed',
            'z-index' => '9999',
            '--desktop-width' => $desktop_width . 'px',
            '--mobile-width' => $mobile_width . 'px',
            '--desktop-margin' => $desktop_margin . 'px',
            '--mobile-margin' => $mobile_margin . 'px',
            '--desktop-animation-in' => $desktop_animation_in,
            '--desktop-animation-out' => $desktop_animation_out,
            '--mobile-animation-in' => $mobile_animation_in,
            '--mobile-animation-out' => $mobile_animation_out,
            '--desktop-position' => $desktop_position,
            '--mobile-position' => $mobile_position
        );
        
        // تبدیل آرایه استایل به رشته
        $container_style = '';
        foreach ($container_styles as $property => $value) {
            $container_style .= $property . ':' . $value . ';';
        }

        ?>
        <div id="rc-banner-container" class="rc-banner-container" style="<?php echo $container_style; ?>" data-desktop-position="<?php echo $desktop_position; ?>" data-mobile-position="<?php echo $mobile_position; ?>">
            <div class="rc-banner-close">&times;</div>
            <?php if ($desktop_show && $desktop_banner) : ?>
                <div class="rc-banner desktop-banner" style="border-radius:<?php echo $desktop_radius; ?>px;">
                    <a href="<?php echo esc_url($desktop_link); ?>" target="_blank">
                        <img src="<?php echo esc_url($desktop_banner); ?>" alt="بنر" style="border-radius:<?php echo $desktop_radius; ?>px;">
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($mobile_show && $mobile_banner) : ?>
                <div class="rc-banner mobile-banner" style="border-radius:<?php echo $mobile_radius; ?>px;">
                    <a href="<?php echo esc_url($mobile_link); ?>" target="_blank">
                        <img src="<?php echo esc_url($mobile_banner); ?>" alt="بنر" style="border-radius:<?php echo $mobile_radius; ?>px;">
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // بارگذاری استایل‌های فرانت‌اند
    public function enqueue_scripts() {
        wp_enqueue_style('rc-style', plugin_dir_url(__FILE__) . 'css/rc-style.css', array(), self::VERSION);
        wp_enqueue_script('rc-banner-script', plugin_dir_url(__FILE__) . 'js/rc-banner-script.js', array('jquery'), self::VERSION, true);
    }

    // بارگذاری فایل‌های مدیریت
    public function enqueue_admin_scripts($hook_suffix) {
        if (strpos($hook_suffix, 'rc-campaign-settings') !== false) {
            wp_enqueue_media();
            wp_enqueue_style('rc-admin-style', plugin_dir_url(__FILE__) . 'css/rc-admin-style.css', array(), self::VERSION);
            wp_enqueue_script('rc-media-uploader', plugin_dir_url(__FILE__) . 'js/rc-media-uploader.js', array('jquery'), self::VERSION, true);
            wp_enqueue_script('rc-admin-script', plugin_dir_url(__FILE__) . 'js/rc-admin-script.js', array('jquery'), self::VERSION, true);
        }
    }
}

new Ready_Campaign();

endif;
