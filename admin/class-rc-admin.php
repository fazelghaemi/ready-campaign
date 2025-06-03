<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RC_Admin {

    private $active_tab; // برای مدیریت تب فعال در صفحه تنظیمات

    public function __construct() {
        $this->active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'utm_builder'; // تب پیش‌فرض

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) ); // برای تنظیمات عمومی و UTM
        // هوک‌های مربوط به متاباکس‌های CPT بنر در اینجا یا کلاس جداگانه اضافه می‌شوند
        // add_action( 'add_meta_boxes_rc_banner', array( $this, 'add_banner_metaboxes' ) );
        // add_action( 'save_post_rc_banner', array( $this, 'save_banner_metaboxes' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'ردی کمپین', 'ready-campaign' ),          // عنوان صفحه
            __( 'ردی کمپین', 'ready-campaign' ),          // عنوان منو
            'manage_options',                           // سطح دسترسی
            'rc-main-settings',                         // اسلاگ (slug) منو - صفحه اصلی تنظیمات شما
            array( $this, 'render_main_settings_page' ), // تابع برای نمایش محتوای صفحه
            RC_PLUGIN_URL . 'assets/ready-campaign-banner.png', // <-- *** این خط تغییر می‌کند *** آدرس آیکون شما
            90                                          // موقعیت منو
        );

        // زیر منو برای UTM Builder (اگر بخواهید جدا باشد یا در تب بماند)
        // add_submenu_page(
        // 'rc-main-settings',
        //     __( 'لینک ساز کمپین', 'ready-campaign' ),
        //     __( 'لینک ساز UTM', 'ready-campaign' ),
        // 'manage_options',
        // 'rc-utm-builder',
        // array( $this, 'render_utm_builder_page' )
        // );

        // CPT بنرها به صورت خودکار زیرمنویی برای "همه بنرها" و "افزودن جدید" ایجاد می‌کند
        // اگر show_in_menu => 'rc-main-settings' را در ثبت CPT تنظیم کرده باشید.
    }

    public function render_main_settings_page() {
        // در اینجا از فایل view برای نمایش صفحه تنظیمات استفاده می‌کنیم
        // فایل admin/views/main-settings-page.php شامل ساختار تب‌ها خواهد بود
        // و هر تب می‌تواند محتوای خود را از فایل view دیگری بارگذاری کند.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        require_once RC_PLUGIN_DIR . 'admin/views/main-settings-page.php';
    }

    public function register_settings() {
        // ثبت تنظیمات عمومی افزونه و تنظیمات UTM Builder
        // این بخش مشابه بخش register_setting و add_settings_section/field در کد فعلی شما
        // برای تب UTM خواهد بود. فیلدهای مربوط به یک بنر خاص دیگر اینجا نیستند.

        register_setting(
            'rc_utm_option_group', // گروه جدید یا همان قبلی اگر فقط شامل UTM است
            'rc_utm_options',      // نام جدید برای optionهای UTM
            array( $this, 'sanitize_utm_options' ) // تابع sanitize جدید
        );

        // بخش تنظیمات کمپین UTM
        add_settings_section(
            'rc_campaign_builder_section',
            __( 'ساخت لینک کمپین UTM', 'ready-campaign' ),
            array( $this, 'utm_section_callback' ), // از کد فعلی شما
            'rc-utm-settings-admin' // شناسه جدید برای do_settings_sections
        );

        // فیلدهای UTM (مشابه کد فعلی شما اما با ارجاع به rc_utm_options)
        $utm_fields = array(
            'website_url'      => array('label' => __( 'آدرس وب سایت*', 'ready-campaign' ), 'callback' => 'utm_website_url_callback'),
            // ... سایر فیلدهای UTM
            'utm_result'       => array('label' => __( 'لینک نهایی کمپین', 'ready-campaign' ), 'callback' => 'utm_result_callback')
        );
        
        // دقت کنید که نام option در فیلدها به rc_utm_options[field_name] تغییر می‌کند
        // و تابع callback ها باید از rc_utm_options بخوانند.

        foreach ( $utm_fields as $field_id => $field_data ) {
            add_settings_field(
                $field_id,
                $field_data['label'],
                array( $this, $field_data['callback'] ),
                'rc-utm-settings-admin',
                'rc_campaign_builder_section'
            );
        }
    }
    
    // توابع callback برای فیلدهای UTM (مشابه کد فعلی، اما باید از RC_Core::get_options() یا مستقیما از get_option('rc_utm_options') بخوانند)
    // مثال:
    public function utm_website_url_callback() {
        $options = get_option('rc_utm_options');
        $website_url = isset( $options['website_url'] ) ? esc_url( $options['website_url'] ) : '';
        printf(
            '<input type="url" id="website_url" name="rc_utm_options[website_url]" value="%s" class="regular-text" style="width:70%%;" placeholder="https://www.example.com" />',
            $website_url
        );
        echo '<p class="description">' . __('آدرس کامل وب‌سایت مانند https://www.example.com', 'ready-campaign') . '</p>';
    }
    // ... سایر توابع callback برای UTM باید به همین شکل اصلاح شوند.

    public function utm_section_callback() { // از کد فعلی
        echo '<p>' . __('با پر کردن فیلدهای زیر، لینک UTM شما به صورت خودکار ساخته می‌شود. فیلدهای ستاره‌دار (*) اجباری هستند.', 'ready-campaign') . '</p>';
    }

    public function sanitize_utm_options( $input ) { // تابع sanitize جدید برای آپشن‌های UTM
        $new_input = array();
        // پاکسازی فیلدهای UTM مشابه کد فعلی شما در تابع sanitize برای بخش کمپین
        $new_input['website_url'] = isset( $input['website_url'] ) ? esc_url_raw( $input['website_url'] ) : '';
        // ...
        return $new_input;
    }
    
    // متدها برای افزودن و ذخیره متاباکس‌های CPT بنر در اینجا یا کلاس جداگانه اضافه می‌شوند.
}
?>
