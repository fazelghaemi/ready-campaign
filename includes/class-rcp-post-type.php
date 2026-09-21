<?php
if (!defined('ABSPATH')) { exit; }

class RCP_Post_Type {
    const CPT = 'rc_banner';

    public static function banner_defaults() {
        return [
            'rc_active' => false, 'rc_device' => 'both',
            'rc_image_desktop' => 0, 'rc_image_mobile' => 0,
            'rc_link' => '', 'rc_link_desktop' => '', 'rc_link_mobile' => '',
            'rc_start' => '', 'rc_end' => '', 'rc_days' => '',
            'rc_time_start' => '', 'rc_time_end' => '',
            'rc_position' => 'br', 'rc_width' => '320px',
            'rc_offset_x' => '16px', 'rc_offset_y' => '16px',
            'rc_radius' => '12px', 'rc_weight' => 1,
            'rc_include_urls' => '', 'rc_exclude_urls' => '',
            'rc_referrer_contains' => '', 'rc_require_utm_source' => '',
            'rc_cap_user_day' => 0, 'rc_mute_days' => 0, 'rc_delay_ms' => 0,
            'rc_scroll_percent' => 0, 'rc_exit_intent' => false,
            'rc_anim_in' => 'fade', 'rc_anim_out' => 'fade',
        ];
    }

    public static function seed_banner_defaults($post_id) {
        if (!$post_id || get_post_type($post_id) !== self::CPT) return;
        foreach (self::banner_defaults() as $key => $value) {
            if (!metadata_exists('post', $post_id, $key)) add_post_meta($post_id, $key, $value, true);
        }
    }

    public static function init() {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('init', [__CLASS__, 'register_meta']);
    }

    public static function register_post_type() {
        $labels = [
            'name' => __('Campaign Banners', 'ready-campaign'),
            'singular_name' => __('Campaign Banner', 'ready-campaign'),
            'add_new' => __('Add New Banner', 'ready-campaign'),
            'add_new_item' => __('Add New Campaign Banner', 'ready-campaign'),
            'edit_item' => __('Edit Campaign Banner', 'ready-campaign'),
            'new_item' => __('New Campaign Banner', 'ready-campaign'),
            'all_items' => __('Banners', 'ready-campaign'),
            'menu_name' => __('Ready Campaign', 'ready-campaign'),
        ];

        register_post_type(self::CPT, [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-megaphone',
            'supports' => ['title'],
        ]);
    }

    public static function register_meta() {
        $metas = [
            'rc_active'      => ['type' => 'boolean', 'default' => false],
            'rc_device'      => ['type' => 'string',  'default' => 'both'],
            'rc_image_desktop' => ['type' => 'integer', 'default' => 0],
            'rc_image_mobile'  => ['type' => 'integer', 'default' => 0],
            'rc_link'          => ['type' => 'string',  'default' => ''],
            'rc_link_desktop'  => ['type' => 'string',  'default' => ''],
            'rc_link_mobile'   => ['type' => 'string',  'default' => ''],

            'rc_start' => ['type' => 'string', 'default' => ''],
            'rc_end'   => ['type' => 'string', 'default' => ''],
            'rc_days'  => ['type' => 'string', 'default' => ''],
            'rc_time_start' => ['type' => 'string', 'default' => ''],
            'rc_time_end'   => ['type' => 'string', 'default' => ''],

            'rc_position' => ['type' => 'string', 'default' => 'br'],
            'rc_width'    => ['type' => 'string', 'default' => '320px'],
            'rc_offset_x' => ['type' => 'string', 'default' => '16px'],
            'rc_offset_y' => ['type' => 'string', 'default' => '16px'],
            'rc_radius'   => ['type' => 'string', 'default' => '12px'],
            'rc_weight'   => ['type' => 'integer','default' => 1],

            'rc_include_urls' => ['type' => 'string', 'default' => ''],
            'rc_exclude_urls' => ['type' => 'string', 'default' => ''],
            'rc_referrer_contains' => ['type' => 'string', 'default' => ''],
            'rc_require_utm_source' => ['type' => 'string', 'default' => ''],

            'rc_cap_user_day' => ['type' => 'integer', 'default' => 0],
            'rc_mute_days'    => ['type' => 'integer', 'default' => 0],
            'rc_delay_ms'     => ['type' => 'integer', 'default' => 0],
            'rc_scroll_percent'=>['type' => 'integer', 'default' => 0],
            'rc_exit_intent'  => ['type' => 'boolean', 'default' => false],

            'rc_anim_in'  => ['type' => 'string', 'default' => 'fade'],
            'rc_anim_out' => ['type' => 'string', 'default' => 'fade'],
        ];

        foreach ($metas as $key => $args) {
            register_post_meta(self::CPT, $key, array_merge([
                'show_in_rest' => true,
                'single'       => true,
                'auth_callback'=> function(){ return current_user_can('edit_posts'); }
            ], $args));
        }
    }
}
