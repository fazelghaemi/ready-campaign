<?php
// فایل: includes/activation.php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'RC_Activation' ) ) {
    class RC_Activation {
        public static function activate() {
            // کدهای شما برای زمان فعال‌سازی
            // مثال: flush_rewrite_rules();
            // مثال: set_option('rc_plugin_version', RC_VERSION);
        }
    }
}
?>
