<?php
// فایل: includes/deactivation.php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'RC_Deactivation' ) ) {
    class RC_Deactivation {
        public static function deactivate() {
            // کدهای شما برای زمان غیرفعال‌سازی
            // مثال: delete_option('rc_some_option');
        }
    }
}
?>
