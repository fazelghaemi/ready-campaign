<?php
if (!defined('ABSPATH')) { exit; }

class RCP_Settings {
    const OPTION = 'rc_settings';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'register']);
    }

    public static function defaults() {
        return [
            'safe_top' => '0px',
            'safe_bottom' => '0px',
            'safe_left' => '0px',
            'safe_right' => '0px',
            'max_per_request' => 5,
            'cache_ttl' => 120,
            'retention_days' => 180,
        ];
    }

    public static function get() {
        $opt = get_option(self::OPTION, []);
        return wp_parse_args($opt, self::defaults());
    }

    public static function menu() {
        add_submenu_page(
            'edit.php?post_type=' . RCP_Post_Type::CPT,
            __('Display Settings', 'ready-campaign'),
            __('Display Settings', 'ready-campaign'),
            'manage_options',
            'rc-settings',
            [__CLASS__, 'render']
        );
    }

    public static function register() {
        register_setting(self::OPTION, self::OPTION);
    }

    public static function render() {
        if (!current_user_can('manage_options')) return;
        $opt = self::get();
        ?>
        <div class="wrap">
            <h1><?php _e('Display & Performance Settings', 'ready-campaign'); ?></h1>
            <form method="post" action="options.php" class="ui-card">
                <?php settings_fields(self::OPTION); ?>
                <table class="form-table">
                    <tr><th><?php _e('Safe Areas', 'ready-campaign'); ?></th>
                        <td>
                            <label>Top <input type="text" name="<?php echo esc_attr(self::OPTION); ?>[safe_top]" value="<?php echo esc_attr($opt['safe_top']); ?>" placeholder="0px"></label>
                            <label>Right <input type="text" name="<?php echo esc_attr(self::OPTION); ?>[safe_right]" value="<?php echo esc_attr($opt['safe_right']); ?>" placeholder="0px"></label>
                            <label>Bottom <input type="text" name="<?php echo esc_attr(self::OPTION); ?>[safe_bottom]" value="<?php echo esc_attr($opt['safe_bottom']); ?>" placeholder="0px"></label>
                            <label>Left <input type="text" name="<?php echo esc_attr(self::OPTION); ?>[safe_left]" value="<?php echo esc_attr($opt['safe_left']); ?>" placeholder="0px"></label>
                            <p class="description"><?php _e('Prevents overlap with cookie bars, chat widgets, etc.', 'ready-campaign'); ?></p>
                        </td>
                    </tr>
                    <tr><th><?php _e('Max Banners Per Page', 'ready-campaign'); ?></th>
                        <td><input type="number" min="1" max="10" name="<?php echo esc_attr(self::OPTION); ?>[max_per_request]" value="<?php echo esc_attr($opt['max_per_request']); ?>"></td>
                    </tr>
                    <tr><th><?php _e('Candidates Cache TTL (sec)', 'ready-campaign'); ?></th>
                        <td><input type="number" min="0" name="<?php echo esc_attr(self::OPTION); ?>[cache_ttl]" value="<?php echo esc_attr($opt['cache_ttl']); ?>"></td>
                    </tr>
                    <tr><th><?php _e('Analytics Retention (days)', 'ready-campaign'); ?></th>
                        <td><input type="number" min="30" name="<?php echo esc_attr(self::OPTION); ?>[retention_days]" value="<?php echo esc_attr($opt['retention_days']); ?>">
                        <p class="description"><?php _e('Older rows may be purged to keep DB small.', 'ready-campaign'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
