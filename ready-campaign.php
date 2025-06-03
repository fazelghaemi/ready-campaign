<?php
/*
Plugin Name: ردی کمپین | افزونه پیاده سازی کمپین ها
Plugin URI: https://readystudio.ir/
Description: ردی کمپین، افزونه‌ای تخصصی برای برگزاری کمپین در وب‌سایت های وردپرسی است. (توضیحات جدید با توجه به امکانات گسترده‌تر اضافه شود)
Version: 2.0.0 (نسخه جدید پس از بازسازی)
Author: ردی استودیو | فاضل قائمی
Author URI: https://fazelghaemi.ir/
License: GPL2
Text Domain: ready-campaign
Tags: افزونه وردپرسی تبلیغات و برگزاری کمپین, بنر, UTM, مدیریت کمپین
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'RC_VERSION', '2.0.0' );
define( 'RC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RC_MIN_PHP_VERSION', '7.2' ); // مثال: حداقل نسخه PHP مورد نیاز

// بررسی نسخه PHP
if ( version_compare( PHP_VERSION, RC_MIN_PHP_VERSION, '<' ) ) {
    add_action( 'admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php printf( 'افزونه ردی کمپین برای اجرا به نسخه %s یا بالاتر PHP نیاز دارد. نسخه PHP شما %s است.', RC_MIN_PHP_VERSION, PHP_VERSION ); ?></p>
        </div>
        <?php
    } );
    return;
}

// بارگذاری فایل‌های هسته
require_once RC_PLUGIN_DIR . 'includes/class-rc-core.php';
require_once RC_PLUGIN_DIR . 'includes/activation.php';
require_once RC_PLUGIN_DIR . 'includes/deactivation.php';

// ثبت هوک‌های فعال‌سازی و غیرفعال‌سازی
register_activation_hook( __FILE__, array( 'RC_Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RC_Deactivation', 'deactivate' ) );

/**
 * آغازگر اصلی افزونه ردی کمپین.
 *
 * @return RC_Core
 */
function rc_run_plugin() {
    return RC_Core::get_instance();
}
// اجرای افزونه
rc_run_plugin();

?>
