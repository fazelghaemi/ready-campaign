<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RC_Assets {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function enqueue_public_assets() {
        wp_enqueue_style(
            'rc-public-style',
            RC_PLUGIN_URL . 'public/css/rc-style.css', // مسیر جدید
            array(),
            RC_VERSION
        );
        wp_enqueue_script(
            'rc-public-script',
            RC_PLUGIN_URL . 'public/js/rc-banner-script.js', // مسیر جدید
            array( 'jquery' ),
            RC_VERSION,
            true
        );
        // می‌توانید با wp_localize_script داده‌هایی را از PHP به JS بفرستید (مثلاً تنظیمات انیمیشن)
    }

    public function enqueue_admin_assets( $hook_suffix ) {
        // بهتر است اسکریپت‌ها فقط در صفحات مربوط به افزونه بارگذاری شوند
        // $current_screen = get_current_screen();
        // if (strpos($current_screen->id, 'rc-') === false && $current_screen->post_type !== 'rc_banner') {
        // return;
        // }
        
        // مثال ساده‌تر بر اساس hook_suffix برای صفحه تنظیمات اصلی
        if ( strpos( $hook_suffix, 'rc-main-settings' ) !== false || 
             (isset($_GET['post_type']) && $_GET['post_type'] == 'rc_banner') || // برای صفحات ویرایش CPT بنر
             (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'rc_banner') ) {
            
            wp_enqueue_media(); // برای آپلودر تصویر در متاباکس‌ها
            
            wp_enqueue_style(
                'rc-admin-style',
                RC_PLUGIN_URL . 'admin/css/rc-admin-style.css', // مسیر جدید
                array(),
                RC_VERSION
            );
            wp_enqueue_script(
                'rc-media-uploader',
                RC_PLUGIN_URL . 'admin/js/rc-media-uploader.js',
                array( 'jquery' ),
                RC_VERSION, // <<<<<<< اضافه کردن ثابت نسخه
                true        // <<<<<<< اضافه کردن پارامETER برای بارگذاری در فوتر
            );
?>
