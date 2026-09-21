<?php
/**
 * Plugin Name: ردی کمپین | Ready Campaign
 * Description: مدیریت پیشرفته بنرهای تبلیغاتی + لینک‌ساز UTM + آنالیتیکس داخلی، زمان‌بندی و قوانین نمایش، پیش‌نمایش زنده و UI مدرن.
 * Version: 1.2.6
 * Author: Ready Studio | Fazel Ghaemi
 * Author URI: https://readystudio.ir/
 * Text Domain: ready-campaign
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) { exit; }

// Use unique constants to avoid collisions with older/other variants
if (!defined('RCP_VERSION')) define('RCP_VERSION', '1.2.6');
if (!defined('RCP_FILE'))    define('RCP_FILE', __FILE__);
if (!defined('RCP_PATH'))    define('RCP_PATH', plugin_dir_path(__FILE__));
if (!defined('RCP_URL'))     define('RCP_URL', plugin_dir_url(__FILE__));
if (!defined('RCP_ASSETS_URL')) define('RCP_ASSETS_URL', RCP_URL . 'assets/');

require_once RCP_PATH . 'includes/class-rcp-post-type.php';
require_once RCP_PATH . 'includes/class-rcp-admin.php';
require_once RCP_PATH . 'includes/class-rcp-frontend.php';
require_once RCP_PATH . 'includes/class-rcp-settings.php';
require_once RCP_PATH . 'includes/class-rcp-analytics.php';

class Ready_Campaign_Pro {
    public static function init() {
        load_plugin_textdomain('ready-campaign', false, dirname(plugin_basename(__FILE__)) . '/languages');

        RCP_Post_Type::init();
        RCP_Settings::init();
        RCP_Admin::init();
        RCP_Analytics::init();
        RCP_Frontend::init();

        register_activation_hook(__FILE__, [__CLASS__, 'activate']);
        register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);
    }

    public static function activate() {
        RCP_Post_Type::register_post_type();
        RCP_Analytics::maybe_create_tables();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}

Ready_Campaign_Pro::init();
