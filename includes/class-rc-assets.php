<?php
// فایل: includes/class-rc-assets.php
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
            RC_PLUGIN_URL . 'public/css/rc-style.css',
            array(),
            RC_VERSION
        );
        wp_enqueue_script(
            'rc-public-script',
            RC_PLUGIN_URL . 'public/js/rc-banner-script.js',
            array( 'jquery' ),
            RC_VERSION,
            true
        );
    }

    public function enqueue_admin_assets( $hook_suffix ) {
        if ( strpos( $hook_suffix, 'rc-main-settings' ) !== false ||
             (isset($_GET['post_type']) && $_GET['post_type'] == 'rc_banner') ||
             (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'rc_banner') ) {

            wp_enqueue_media();

            wp_enqueue_style(
                'rc-admin-style',
                RC_PLUGIN_URL . 'admin/css/rc-admin-style.css',
                array(),
                RC_VERSION
            );
            wp_enqueue_script(
                'rc-media-uploader',
                RC_PLUGIN_URL . 'admin/js/rc-media-uploader.js',
                array( 'jquery' ),
                RC_VERSION, // اطمینان از وجود این پارامتر
                true        // و این پارامتر
            );
            // اگر اسکریپت بعدی وجود دارد، آن را هم کامل کنید:
            wp_enqueue_script(
                'rc-admin-script',
                RC_PLUGIN_URL . 'admin/js/rc-admin-script.js',
                array( 'jquery' ),
                RC_VERSION,
                true
            );
        }
    }
}
?>
