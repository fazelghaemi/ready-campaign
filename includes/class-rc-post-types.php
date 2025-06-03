<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RC_Post_Types {

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
    }

    public function register_post_types() {
        $labels_banner = array(
            'name'                  => _x( 'بنرها', 'Post Type General Name', 'ready-campaign' ),
            'singular_name'         => _x( 'بنر', 'Post Type Singular Name', 'ready-campaign' ),
            'menu_name'             => __( 'بنرهای ردی کمپین', 'ready-campaign' ),
            'name_admin_bar'        => __( 'بنر', 'ready-campaign' ),
            'archives'              => __( 'آرشیو بنرها', 'ready-campaign' ),
            'attributes'            => __( 'ویژگی‌های بنر', 'ready-campaign' ),
            'parent_item_colon'     => __( 'بنر مادر:', 'ready-campaign' ),
            'all_items'             => __( 'همه بنرها', 'ready-campaign' ),
            'add_new_item'          => __( 'افزودن بنر جدید', 'ready-campaign' ),
            'add_new'               => __( 'افزودن جدید', 'ready-campaign' ),
            'new_item'              => __( 'بنر جدید', 'ready-campaign' ),
            'edit_item'             => __( 'ویرایش بنر', 'ready-campaign' ),
            'update_item'           => __( 'به‌روزرسانی بنر', 'ready-campaign' ),
            'view_item'             => __( 'مشاهده بنر', 'ready-campaign' ),
            'view_items'            => __( 'مشاهده بنرها', 'ready-campaign' ),
            'search_items'          => __( 'جستجوی بنر', 'ready-campaign' ),
            // ... سایر لیبل‌ها
        );
        $args_banner = array(
            'label'                 => __( 'بنر', 'ready-campaign' ),
            'description'           => __( 'پست تایپ برای مدیریت بنرهای تبلیغاتی', 'ready-campaign' ),
            'labels'                => $labels_banner,
            'supports'              => array( 'title' ), // فقط عنوان، بقیه موارد با متاباکس اضافه می‌شوند
            'hierarchical'          => false,
            'public'                => false, // معمولاً بنرها به صورت مستقیم قابل مشاهده نیستند
            'show_ui'               => true,
            'show_in_menu'          => 'rc-campaign-settings', // نمایش زیرمنوی "ردی کمپین" یا یک منوی جدید
            // 'menu_icon'          => 'dashicons-megaphone', // اگر منوی سطح بالا باشد
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'rewrite'               => false,
            'capability_type'       => 'post',
            'show_in_rest'          => true, // برای استفاده از ویرایشگر گوتنبرگ یا REST API
        );
        register_post_type( 'rc_banner', $args_banner );

        // می‌توانید CPT دیگری برای "کمپین‌ها" (Campaign Hub) نیز به همین ترتیب ثبت کنید
        // register_post_type( 'rc_campaign', $args_campaign );
    }

    public function register_taxonomies() {
        // مثال: دسته‌بندی برای بنرها (اختیاری)
        $labels_group = array(
            'name'              => _x( 'گروه‌های بنر', 'taxonomy general name', 'ready-campaign' ),
            'singular_name'     => _x( 'گروه بنر', 'taxonomy singular name', 'ready-campaign' ),
            // ...
        );
        $args_group = array(
            'hierarchical'      => true,
            'labels'            => $labels_group,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'banner-group' ),
            'show_in_rest'      => true,
        );
        register_taxonomy( 'rc_banner_group', array( 'rc_banner' ), $args_group );
    }
}
?>