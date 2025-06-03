<?php
// فایل: admin/class-rc-admin.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class RC_Admin {

    private $active_tab;

    public function __construct() {
        $this->active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'utm_builder';

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        // هوک‌ها برای متاباکس‌های CPT 'rc_banner'
        add_action( 'add_meta_boxes_rc_banner', array( $this, 'add_banner_meta_boxes' ) ); // توجه به نام هوک خاص CPT
        add_action( 'save_post_rc_banner', array( $this, 'save_banner_meta_data' ) );    // توجه به نام هوک خاص CPT
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'ردی کمپین', 'ready-campaign' ),
            __( 'ردی کمپین', 'ready-campaign' ),
            'manage_options',
            'rc-main-settings',
            array( $this, 'render_main_settings_page' ),
            RC_PLUGIN_URL . 'assets/ready-campaign-banner.png',
            90
        );
    }

    public function render_main_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        require_once RC_PLUGIN_DIR . 'admin/views/main-settings-page.php';
    }

    public function register_settings() {
        register_setting(
            'rc_utm_option_group',
            'rc_utm_options',
            array( $this, 'sanitize_utm_options' )
        );

        add_settings_section(
            'rc_campaign_builder_section',
            __( 'ساخت لینک کمپین UTM', 'ready-campaign' ),
            array( $this, 'utm_section_callback' ),
            'rc-utm-settings-admin'
        );

        $utm_fields = array(
            'website_url'      => array('label' => __( 'آدرس وب سایت*', 'ready-campaign' ), 'callback' => 'utm_website_url_callback'),
            'campaign_id'      => array('label' => __( 'شناسه کمپین', 'ready-campaign' ), 'callback' => 'utm_campaign_id_callback'),
            'campaign_source'  => array('label' => __( 'منبع کمپین*', 'ready-campaign' ), 'callback' => 'utm_campaign_source_callback'),
            'campaign_medium'  => array('label' => __( 'رسانه کمپین*', 'ready-campaign' ), 'callback' => 'utm_campaign_medium_callback'),
            'campaign_name'    => array('label' => __( 'نام کمپین', 'ready-campaign' ), 'callback' => 'utm_campaign_name_callback'),
            'campaign_term'    => array('label' => __( 'عبارت کمپین', 'ready-campaign' ), 'callback' => 'utm_campaign_term_callback'),
            'campaign_content' => array('label' => __( 'محتوای کمپین', 'ready-campaign' ), 'callback' => 'utm_campaign_content_callback'),
            'utm_result'       => array('label' => __( 'لینک نهایی کمپین', 'ready-campaign' ), 'callback' => 'utm_result_callback')
        );

        foreach ( $utm_fields as $field_id => $field_data ) {
            add_settings_field(
                'rc_utm_' . $field_id, // اضافه کردن پیشوند برای یکتایی ID در سطح وردپرس
                $field_data['label'],
                array( $this, $field_data['callback'] ),
                'rc-utm-settings-admin',
                'rc_campaign_builder_section'
            );
        }
    }

    public function sanitize_utm_options( $input ) {
        $new_input = array();
        $new_input['website_url'] = isset( $input['website_url'] ) ? esc_url_raw( trim($input['website_url']) ) : '';
        $new_input['campaign_id'] = isset( $input['campaign_id'] ) ? sanitize_text_field( trim($input['campaign_id']) ) : '';
        $new_input['campaign_source'] = isset( $input['campaign_source'] ) ? sanitize_text_field( trim($input['campaign_source']) ) : '';
        $new_input['campaign_medium'] = isset( $input['campaign_medium'] ) ? sanitize_text_field( trim($input['campaign_medium']) ) : '';
        $new_input['campaign_name'] = isset( $input['campaign_name'] ) ? sanitize_text_field( trim($input['campaign_name']) ) : '';
        $new_input['campaign_term'] = isset( $input['campaign_term'] ) ? sanitize_text_field( trim($input['campaign_term']) ) : '';
        $new_input['campaign_content'] = isset( $input['campaign_content'] ) ? sanitize_text_field( trim($input['campaign_content']) ) : '';
        return $new_input;
    }

    public function utm_section_callback() {
        echo '<p>' . __('با پر کردن فیلدهای زیر، لینک UTM شما به صورت خودکار ساخته می‌شود. فیلدهای ستاره‌دار (*) اجباری هستند.', 'ready-campaign') . '</p>';
    }

    // --- توابع Callback برای فیلدهای UTM (همانطور که قبلاً اصلاح شد) ---
    public function utm_website_url_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="url" id="rc_utm_website_url" name="rc_utm_options[website_url]" value="%s" class="regular-text" style="width:70%%; direction:ltr; text-align:left;" placeholder="https://www.example.com" />',
            isset( $options['website_url'] ) ? esc_url( $options['website_url'] ) : ''
        );
        echo '<p class="description">' . __('آدرس کامل وب‌سایت مانند https://www.example.com', 'ready-campaign') . '</p>';
    }
    public function utm_campaign_id_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_id" name="rc_utm_options[campaign_id]" value="%s" class="regular-text" style="width:70%%; direction:ltr; text-align:left;" />',
            isset( $options['campaign_id'] ) ? esc_attr( $options['campaign_id'] ) : ''
        );
        echo '<p class="description">' . __('شناسه کمپین تبلیغاتی (اختیاری)', 'ready-campaign') . '</p>';
    }
    public function utm_campaign_source_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_source" name="rc_utm_options[campaign_source]" value="%s" class="regular-text" style="width:70%%; direction:ltr; text-align:left;" placeholder="google, newsletter" />',
            isset( $options['campaign_source'] ) ? esc_attr( $options['campaign_source'] ) : ''
        );
        echo '<p class="description">' . __('منبع ارجاع دهنده (مثلاً: google، instagram، newsletter)*', 'ready-campaign') . '</p>';
    }
    public function utm_campaign_medium_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_medium" name="rc_utm_options[campaign_medium]" value="%s" class="regular-text" style="width:70%%; direction:ltr; text-align:left;" placeholder="cpc, banner, email" />',
            isset( $options['campaign_medium'] ) ? esc_attr( $options['campaign_medium'] ) : ''
        );
        echo '<p class="description">' . __('رسانه تبلیغات (مثلاً: cpc، banner، email)*', 'ready-campaign') . '</p>';
    }
    public function utm_campaign_name_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_name" name="rc_utm_options[campaign_name]" value="%s" class="regular-text" style="width:70%%; direction:ltr; text-align:left;" placeholder="summer_sale" />',
            isset( $options['campaign_name'] ) ? esc_attr( $options['campaign_name'] ) : ''
        );
        echo '<p class="description">' . __('نام محصول، کد تخفیف یا شعار تبلیغات (اختیاری)', 'ready-campaign') . '</p>';
    }
    public function utm_campaign_term_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_term" name="rc_utm_options[campaign_term]" value="%s" class="regular-text" style="width:70%%; direction:ltr; text-align:left;" />',
            isset( $options['campaign_term'] ) ? esc_attr( $options['campaign_term'] ) : ''
        );
        echo '<p class="description">' . __('کلمات کلیدی که برای کمپین خریداری شده‌اند (اختیاری)', 'ready-campaign') . '</p>';
    }
    public function utm_campaign_content_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_content" name="rc_utm_options[campaign_content]" value="%s" class="regular-text" style="width:70%%; direction:ltr; text-align:left;" />',
            isset( $options['campaign_content'] ) ? esc_attr( $options['campaign_content'] ) : ''
        );
        echo '<p class="description">' . __('برای تفکیک تبلیغات مشابه (مانند: logolink، textlink) (اختیاری)', 'ready-campaign') . '</p>';
    }
    public function utm_result_callback() {
        $options = get_option('rc_utm_options');
        $website_url = isset( $options['website_url'] ) ? $options['website_url'] : '';
        $campaign_id_val = isset( $options['campaign_id'] ) ? $options['campaign_id'] : '';
        $campaign_source_val = isset( $options['campaign_source'] ) ? $options['campaign_source'] : '';
        $campaign_medium_val = isset( $options['campaign_medium'] ) ? $options['campaign_medium'] : '';
        $campaign_name_val = isset( $options['campaign_name'] ) ? $options['campaign_name'] : '';
        $campaign_term_val = isset( $options['campaign_term'] ) ? $options['campaign_term'] : '';
        $campaign_content_val = isset( $options['campaign_content'] ) ? $options['campaign_content'] : '';

        $utm_url = '';
        if (!empty($website_url) && !empty($campaign_source_val) && !empty($campaign_medium_val)) {
            if (strpos($website_url, 'http://') !== 0 && strpos($website_url, 'https://') !== 0) {
                $website_url = 'http://' . $website_url;
            }
            $base_url = $website_url;
            $query_params = [];
            $query_params['utm_source'] = $campaign_source_val;
            $query_params['utm_medium'] = $campaign_medium_val;
            if (!empty($campaign_name_val)) $query_params['utm_campaign'] = $campaign_name_val;
            if (!empty($campaign_id_val)) $query_params['utm_id'] = $campaign_id_val;
            if (!empty($campaign_term_val)) $query_params['utm_term'] = $campaign_term_val;
            if (!empty($campaign_content_val)) $query_params['utm_content'] = $campaign_content_val;
            $utm_url = add_query_arg( $query_params, $base_url );
        }
        ?>
        <div class="utm-result-container">
            <textarea id="utm_result" readonly style="width:70%; height:100px; direction:ltr; text-align:left;"><?php echo esc_textarea($utm_url); ?></textarea>
            <p><button type="button" class="button" id="copy-utm-url"><?php _e('کپی لینک', 'ready-campaign'); ?></button></p>
        </div>
        <p class="description"><?php _e('لینک نهایی UTM شما. برای استفاده، آن را کپی کنید. (تولید لینک به صورت خودکار با پر کردن فیلدهای بالا انجام می‌شود)', 'ready-campaign'); ?></p>
        <?php
    }

    // --- متاباکس‌ها برای CPT بنر ---

    /**
     * ثبت متاباکس(های) بنر.
     */
    public function add_banner_meta_boxes() {
        add_meta_box(
            'rc_banner_settings_metabox', // ID متاباکس
            __( 'تنظیمات بنر ردی کمپین', 'ready-campaign' ), // عنوان متاباکس
            array( $this, 'render_banner_settings_metabox' ), // تابع callback برای نمایش محتوا
            'rc_banner', // نام CPT
            'normal', // موقعیت (normal, side, advanced)
            'high' // اولویت (high, core, default, low)
        );
        // می‌توانید متاباکس‌های دیگری نیز اضافه کنید (مثلاً برای هدف‌گذاری پیشرفته یا آمار)
    }

    /**
     * نمایش محتوای متاباکس تنظیمات بنر.
     * @param WP_Post $post آبجکت پست فعلی.
     */
    public function render_banner_settings_metabox( $post ) {
        // افزودن Nonce field برای امنیت
        wp_nonce_field( 'rc_banner_save_meta_data', 'rc_banner_meta_nonce' );

        // دریافت مقادیر ذخیره شده متادیتا (اگر وجود دارد)
        $is_enabled = get_post_meta( $post->ID, '_rc_banner_enabled', true );
        $image_desktop = get_post_meta( $post->ID, '_rc_banner_image_desktop', true );
        $image_mobile = get_post_meta( $post->ID, '_rc_banner_image_mobile', true );
        $link_url = get_post_meta( $post->ID, '_rc_banner_link_url', true );
        $position = get_post_meta( $post->ID, '_rc_banner_position', true );
        $device_target = get_post_meta( $post->ID, '_rc_banner_device_target', true );
        $start_date = get_post_meta( $post->ID, '_rc_banner_start_date', true );
        $end_date = get_post_meta( $post->ID, '_rc_banner_end_date', true );
        $width_desktop = get_post_meta( $post->ID, '_rc_banner_width_desktop', true );
        $width_mobile = get_post_meta( $post->ID, '_rc_banner_width_mobile', true );
        $margin_vertical = get_post_meta( $post->ID, '_rc_banner_margin_vertical', true );
        $margin_horizontal = get_post_meta( $post->ID, '_rc_banner_margin_horizontal', true );
        $radius = get_post_meta( $post->ID, '_rc_banner_radius', true );
        $animation_in = get_post_meta( $post->ID, '_rc_banner_animation_in', true );
        $animation_out = get_post_meta( $post->ID, '_rc_banner_animation_out', true );

        // مقادیر پیش‌فرض
        $position = $position ?: 'bottom-right';
        $device_target = $device_target ?: 'all';
        $width_desktop = $width_desktop ?: '350';
        $width_mobile = $width_mobile ?: '300';
        $margin_vertical = $margin_vertical ?: '20';
        $margin_horizontal = $margin_horizontal ?: '20';
        $radius = $radius ?: '0';
        $animation_in = $animation_in ?: 'fadeIn';
        $animation_out = $animation_out ?: 'fadeOut';

        // آرایه‌ها برای گزینه‌های select
        $positions = array(
            'bottom-right' => __('پایین-راست', 'ready-campaign'),
            'bottom-left'  => __('پایین-چپ', 'ready-campaign'),
            'top-right'    => __('بالا-راست', 'ready-campaign'),
            'top-left'     => __('بالا-چپ', 'ready-campaign'),
            'center'       => __('وسط صفحه', 'ready-campaign'),
            'sticky-top'   => __('نوار چسبان بالا', 'ready-campaign'),
            'sticky-bottom'=> __('نوار چسبان پایین', 'ready-campaign'),
        );
        $animations = array(
            'none'    => __('بدون انیمیشن', 'ready-campaign'),
            'fadeIn'  => __('محو شدن (ورود)', 'ready-campaign'), 'fadeOut' => __('محو شدن (خروج)', 'ready-campaign'),
            'slideInFromBottom' => __('کشویی از پایین (ورود)', 'ready-campaign'), 'slideOutToBottom' => __('کشویی به پایین (خروج)', 'ready-campaign'),
            'slideInFromTop'    => __('کشویی از بالا (ورود)', 'ready-campaign'),    'slideOutToTop'    => __('کشویی به بالا (خروج)', 'ready-campaign'),
            'slideInFromLeft'   => __('کشویی از چپ (ورود)', 'ready-campaign'),   'slideOutToLeft'   => __('کشویی به چپ (خروج)', 'ready-campaign'),
            'slideInFromRight'  => __('کشویی از راست (ورود)', 'ready-campaign'),  'slideOutToRight'  => __('کشویی به راست (خروج)', 'ready-campaign'),
            'bounceIn'=> __('جهشی (ورود)', 'ready-campaign'), 'bounceOut' => __('جهشی (خروج)', 'ready-campaign'),
            'flipInX' => __('چرخش افقی (ورود)', 'ready-campaign'), 'flipOutX' => __('چرخش افقی (خروج)', 'ready-campaign'),
            'zoomIn'  => __('بزرگ‌نمایی (ورود)', 'ready-campaign'), 'zoomOut' => __('کوچک‌نمایی (خروج)', 'ready-campaign'),
        );

        ?>
        <table class="form-table rc-metabox-table">
            <tbody>
                <tr>
                    <th><label for="rc_banner_enabled"><?php _e('وضعیت بنر', 'ready-campaign'); ?></label></th>
                    <td>
                        <input type="checkbox" id="rc_banner_enabled" name="rc_banner_enabled" value="1" <?php checked($is_enabled, '1'); ?> />
                        <label for="rc_banner_enabled"><?php _e('فعال باشد', 'ready-campaign'); ?></label>
                    </td>
                </tr>

                <tr>
                    <th><label for="rc_banner_image_desktop"><?php _e('تصویر بنر دسکتاپ', 'ready-campaign'); ?></label></th>
                    <td>
                        <div class="rc-media-uploader-wrapper">
                            <input type="text" id="rc_banner_image_desktop" name="rc_banner_image_desktop" value="<?php echo esc_url($image_desktop); ?>" style="width:70%; direction:ltr; text-align:left;" />
                            <button type="button" class="button rc-media-upload-button" data-target="#rc_banner_image_desktop" data-uploader-title="<?php esc_attr_e('انتخاب تصویر دسکتاپ', 'ready-campaign'); ?>" data-uploader-button-text="<?php esc_attr_e('انتخاب تصویر', 'ready-campaign'); ?>">
                                <?php _e('انتخاب تصویر', 'ready-campaign'); ?>
                            </button>
                            <div class="rc-media-preview">
                                <?php if ($image_desktop): ?><img src="<?php echo esc_url($image_desktop); ?>" style="max-width:200px; height:auto; display:block; margin-top:10px;" /><?php endif; ?>
                            </div>
                        </div>
                        <p class="description"><?php _e('تصویری که در دستگاه‌های دسکتاپ نمایش داده می‌شود.', 'ready-campaign'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label for="rc_banner_image_mobile"><?php _e('تصویر بنر موبایل', 'ready-campaign'); ?></label></th>
                    <td>
                        <div class="rc-media-uploader-wrapper">
                            <input type="text" id="rc_banner_image_mobile" name="rc_banner_image_mobile" value="<?php echo esc_url($image_mobile); ?>" style="width:70%; direction:ltr; text-align:left;" />
                            <button type="button" class="button rc-media-upload-button" data-target="#rc_banner_image_mobile" data-uploader-title="<?php esc_attr_e('انتخاب تصویر موبایل', 'ready-campaign'); ?>" data-uploader-button-text="<?php esc_attr_e('انتخاب تصویر', 'ready-campaign'); ?>">
                                <?php _e('انتخاب تصویر', 'ready-campaign'); ?>
                            </button>
                            <div class="rc-media-preview">
                                <?php if ($image_mobile): ?><img src="<?php echo esc_url($image_mobile); ?>" style="max-width:200px; height:auto; display:block; margin-top:10px;" /><?php endif; ?>
                            </div>
                        </div>
                        <p class="description"><?php _e('تصویری که در دستگاه‌های موبایل نمایش داده می‌شود. اگر خالی باشد، از تصویر دسکتاپ استفاده می‌شود.', 'ready-campaign'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label for="rc_banner_link_url"><?php _e('لینک مقصد بنر', 'ready-campaign'); ?></label></th>
                    <td>
                        <input type="url" id="rc_banner_link_url" name="rc_banner_link_url" value="<?php echo esc_url($link_url); ?>" class="large-text" style="direction:ltr; text-align:left;" placeholder="https://example.com" />
                        <p class="description"><?php _e('آدرسی که کاربر پس از کلیک روی بنر به آن هدایت می‌شود. برای لینک UTM از تب لینک‌ساز استفاده کنید و اینجا کپی کنید.', 'ready-campaign'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><label for="rc_banner_device_target"><?php _e('هدف‌گذاری دستگاه', 'ready-campaign'); ?></label></th>
                    <td>
                        <label><input type="radio" name="rc_banner_device_target" value="all" <?php checked($device_target, 'all'); ?>> <?php _e('همه دستگاه‌ها', 'ready-campaign'); ?></label><br>
                        <label><input type="radio" name="rc_banner_device_target" value="desktop_only" <?php checked($device_target, 'desktop_only'); ?>> <?php _e('فقط دسکتاپ', 'ready-campaign'); ?></label><br>
                        <label><input type="radio" name="rc_banner_device_target" value="mobile_only" <?php checked($device_target, 'mobile_only'); ?>> <?php _e('فقط موبایل', 'ready-campaign'); ?></label>
                    </td>
                </tr>

                <tr>
                    <th><label for="rc_banner_position"><?php _e('موقعیت بنر', 'ready-campaign'); ?></label></th>
                    <td>
                        <select id="rc_banner_position" name="rc_banner_position">
                            <?php foreach ($positions as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($position, $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label for="rc_banner_start_date"><?php _e('زمان‌بندی نمایش', 'ready-campaign'); ?></label></th>
                    <td>
                        <label for="rc_banner_start_date"><?php _e('تاریخ شروع:', 'ready-campaign'); ?></label>
                        <input type="date" id="rc_banner_start_date" name="rc_banner_start_date" value="<?php echo esc_attr($start_date); ?>" />
                        <br>
                        <label for="rc_banner_end_date" style="margin-top:10px; display:inline-block;"><?php _e('تاریخ پایان:', 'ready-campaign'); ?></label>
                        <input type="date" id="rc_banner_end_date" name="rc_banner_end_date" value="<?php echo esc_attr($end_date); ?>" />
                        <p class="description"><?php _e('اگر تاریخ‌ها خالی باشند، بنر همیشه (در صورت فعال بودن) نمایش داده می‌شود.', 'ready-campaign'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th><?php _e('تنظیمات ظاهری', 'ready-campaign'); ?></th>
                    <td>
                        <label for="rc_banner_width_desktop"><?php _e('عرض دسکتاپ (px):', 'ready-campaign'); ?></label>
                        <input type="number" id="rc_banner_width_desktop" name="rc_banner_width_desktop" value="<?php echo esc_attr($width_desktop); ?>" class="small-text" />
                        <br>
                        <label for="rc_banner_width_mobile" style="margin-top:10px; display:inline-block;"><?php _e('عرض موبایل (px):', 'ready-campaign'); ?></label>
                        <input type="number" id="rc_banner_width_mobile" name="rc_banner_width_mobile" value="<?php echo esc_attr($width_mobile); ?>" class="small-text" />
                        <hr>
                        <label for="rc_banner_margin_vertical"><?php _e('فاصله عمودی (px):', 'ready-campaign'); ?></label>
                        <input type="number" id="rc_banner_margin_vertical" name="rc_banner_margin_vertical" value="<?php echo esc_attr($margin_vertical); ?>" class="small-text" />
                        <p class="description"><?php _e('فاصله بنر از بالا یا پایین صفحه (بسته به موقعیت).', 'ready-campaign'); ?></p>
                        <br>
                        <label for="rc_banner_margin_horizontal" style="margin-top:10px; display:inline-block;"><?php _e('فاصله افقی (px):', 'ready-campaign'); ?></label>
                        <input type="number" id="rc_banner_margin_horizontal" name="rc_banner_margin_horizontal" value="<?php echo esc_attr($margin_horizontal); ?>" class="small-text" />
                        <p class="description"><?php _e('فاصله بنر از چپ یا راست صفحه (بسته به موقعیت).', 'ready-campaign'); ?></p>
                        <hr>
                        <label for="rc_banner_radius"><?php _e('انحنای گوشه‌ها (px):', 'ready-campaign'); ?></label>
                        <input type="number" id="rc_banner_radius" name="rc_banner_radius" value="<?php echo esc_attr($radius); ?>" class="small-text" />
                    </td>
                </tr>

                 <tr>
                    <th><?php _e('تنظیمات انیمیشن', 'ready-campaign'); ?></th>
                    <td>
                        <label for="rc_banner_animation_in"><?php _e('انیمیشن ورود:', 'ready-campaign'); ?></label>
                        <select id="rc_banner_animation_in" name="rc_banner_animation_in">
                            <?php foreach ($animations as $value => $label): if (strpos(strtolower($value), 'in') !== false || $value === 'none'): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($animation_in, $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                        <br>
                        <label for="rc_banner_animation_out" style="margin-top:10px; display:inline-block;"><?php _e('انیمیشن خروج:', 'ready-campaign'); ?></label>
                        <select id="rc_banner_animation_out" name="rc_banner_animation_out">
                             <?php foreach ($animations as $value => $label): if (strpos(strtolower($value), 'out') !== false || $value === 'none'): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($animation_out, $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </td>
                </tr>
                </tbody>
        </table>
        <style>
            .rc-metabox-table th { width: 25%; vertical-align: top; padding-top: 15px;}
            .rc-metabox-table td { width: 75%; }
            .rc-metabox-table .small-text { width: 80px; }
            .rc-metabox-table .description { margin-top: 5px; }
        </style>
        <?php
    }

    /**
     * ذخیره‌سازی اطلاعات متای بنر هنگام ذخیره پست.
     * @param int $post_id شناسه پست فعلی.
     */
    public function save_banner_meta_data( $post_id ) {
        // بررسی Nonce برای امنیت
        if ( ! isset( $_POST['rc_banner_meta_nonce'] ) || ! wp_verify_nonce( $_POST['rc_banner_meta_nonce'], 'rc_banner_save_meta_data' ) ) {
            return;
        }

        // بررسی اینکه آیا در حال ذخیره خودکار هستیم یا خیر
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // بررسی سطح دسترسی کاربر
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // اطمینان از اینکه برای CPT 'rc_banner' هستیم (اختیاری، چون هوک save_post_rc_banner خاص این CPT است)
        if ( ! isset($_POST['post_type']) || 'rc_banner' != $_POST['post_type'] ) {
            // return; // این شرط با هوک save_post_rc_banner معمولا لازم نیست
        }

        // ذخیره یا حذف متادیتاها
        $meta_fields = array(
            '_rc_banner_enabled' => 'sanitize_checkbox', // برای چک‌باکس، تابع sanitize خاصی نداریم، بودنش را چک می‌کنیم
            '_rc_banner_image_desktop' => 'esc_url_raw',
            '_rc_banner_image_mobile' => 'esc_url_raw',
            '_rc_banner_link_url' => 'esc_url_raw',
            '_rc_banner_position' => 'sanitize_text_field',
            '_rc_banner_device_target' => 'sanitize_text_field',
            '_rc_banner_start_date' => 'sanitize_text_field', // یا یک تابع sanitize تاریخ خاص
            '_rc_banner_end_date' => 'sanitize_text_field',   // یا یک تابع sanitize تاریخ خاص
            '_rc_banner_width_desktop' => 'absint',
            '_rc_banner_width_mobile' => 'absint',
            '_rc_banner_margin_vertical' => 'absint',
            '_rc_banner_margin_horizontal' => 'absint',
            '_rc_banner_radius' => 'absint',
            '_rc_banner_animation_in' => 'sanitize_text_field',
            '_rc_banner_animation_out' => 'sanitize_text_field',
        );

        foreach ( $meta_fields as $meta_key => $sanitize_callback ) {
            $form_field_name = ltrim($meta_key, '_'); // نام فیلد در فرم معمولا بدون آندرلاین اولیه است

            if ( isset( $_POST[$form_field_name] ) ) {
                $value = $_POST[$form_field_name];
                if ($sanitize_callback === 'sanitize_checkbox') {
                    // برای چک‌باکس، اگر ارسال شده یعنی 1، وگرنه ذخیره نمی‌شود یا باید 0 ذخیره کنیم
                    $sanitized_value = '1';
                } elseif (function_exists($sanitize_callback)) {
                    $sanitized_value = call_user_func( $sanitize_callback, $value );
                } else {
                    $sanitized_value = sanitize_text_field( $value ); // پیش‌فرض
                }
                update_post_meta( $post_id, $meta_key, $sanitized_value );
            } else {
                // اگر فیلد ارسال نشده (مثلاً چک‌باکس تیک نخورده)، متای آن را حذف می‌کنیم یا مقدار پیش‌فرض (مثلا 0 برای چک‌باکس) ذخیره می‌کنیم
                if ($sanitize_callback === 'sanitize_checkbox') {
                     update_post_meta( $post_id, $meta_key, '0' ); // یا delete_post_meta($post_id, $meta_key);
                } else {
                    delete_post_meta( $post_id, $meta_key ); // یا مقدار خالی ذخیره کنید اگر لازم است
                }
            }
        }
    }
}
?>
