<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class RC_Core {

    private static $_instance = null;
    public $admin;
    public $public;
    public $post_types;
    public $assets;
    // سایر ماژول‌ها مانند utm_handler, banner_handler و ...

    /**
     * Singleton instance.
     */
    public static function get_instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_modules();
    }

    private function load_dependencies() {
        // بارگذاری کلاس‌های اصلی و کمکی
        require_once RC_PLUGIN_DIR . 'includes/class-rc-assets.php';
        require_once RC_PLUGIN_DIR . 'includes/class-rc-post-types.php';
        // require_once RC_PLUGIN_DIR . 'includes/class-rc-banner-handler.php'; // بعدا اضافه می‌شود
        // require_once RC_PLUGIN_DIR . 'includes/class-rc-utm-handler.php'; // بعدا اضافه می‌شود
        require_once RC_PLUGIN_DIR . 'admin/class-rc-admin.php';
        require_once RC_PLUGIN_DIR . 'public/class-rc-public.php';
        // ... سایر فایل‌های لازم
    }

    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    }

    private function init_modules() {
        $this->assets = new RC_Assets();
        $this->post_types = new RC_Post_Types();
        $this->admin = new RC_Admin();
        $this->public = new RC_Public();
        // ... مقداردهی اولیه سایر ماژول‌ها
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'ready-campaign',
            false,
            dirname( plugin_basename( RC_PLUGIN_DIR ) ) . '/languages/'
        );
    }

    /**
     * دریافت تنظیمات ذخیره شده افزونه (مشابه کد فعلی شما اما می‌تواند متمرکزتر باشد)
     * این تابع می‌تواند در RC_Admin یا یک کلاس مجزا برای تنظیمات قرار گیرد.
     * فعلا برای حفظ سادگی از فراخوانی مستقیم get_option استفاده می‌کنیم.
     */
    public static function get_options() {
        return get_option( 'rc_options', array() ); // نام option فعلی شما
    }
}
?>