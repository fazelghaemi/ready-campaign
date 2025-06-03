<?php
// فایل: admin/class-rc-admin.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RC_Admin {

    private $active_tab;

    public function __construct() {
        $this->active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'utm_builder';
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    // ... (متدهای add_admin_menu و render_main_settings_page شما) ...

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
            'rc-utm-settings-admin' // این شناسه برای do_settings_sections در فایل view استفاده می‌شود
        );

        // تعریف کامل فیلدهای UTM و callback های آنها
        $utm_fields = array(
            'website_url'      => array('label' => __( 'آدرس وب سایت*', 'ready-campaign' ), 'callback' => 'utm_website_url_callback'),
            'campaign_id'      => array('label' => __( 'شناسه کمپین', 'ready-campaign' ), 'callback' => 'utm_campaign_id_callback'), // اطمینان از وجود این callback
            'campaign_source'  => array('label' => __( 'منبع کمپین*', 'ready-campaign' ), 'callback' => 'utm_campaign_source_callback'), // اطمینان از وجود این callback
            'campaign_medium'  => array('label' => __( 'رسانه کمپین*', 'ready-campaign' ), 'callback' => 'utm_campaign_medium_callback'), // اطمینان از وجود این callback
            'campaign_name'    => array('label' => __( 'نام کمپین', 'ready-campaign' ), 'callback' => 'utm_campaign_name_callback'), // اطمینان از وجود این callback
            'campaign_term'    => array('label' => __( 'عبارت کمپین', 'ready-campaign' ), 'callback' => 'utm_campaign_term_callback'), // اطمینان از وجود این callback
            'campaign_content' => array('label' => __( 'محتوای کمپین', 'ready-campaign' ), 'callback' => 'utm_campaign_content_callback'), // اطمینان از وجود این callback
            'utm_result'       => array('label' => __( 'لینک نهایی کمپین', 'ready-campaign' ), 'callback' => 'utm_result_callback') // این callback را اضافه می‌کنیم
        );

        foreach ( $utm_fields as $field_id => $field_data ) {
            add_settings_field(
                $field_id, // شناسه فیلد، که باید با کلید در $utm_fields یکی باشد
                $field_data['label'],
                array( $this, $field_data['callback'] ),
                'rc-utm-settings-admin', // شناسه صفحه (باید با do_settings_sections یکی باشد)
                'rc_campaign_builder_section' // شناسه بخش
            );
        }
    }

    public function utm_section_callback() {
        echo '<p>' . __('با پر کردن فیلدهای زیر، لینک UTM شما به صورت خودکار ساخته می‌شود. فیلدهای ستاره‌دار (*) اجباری هستند.', 'ready-campaign') . '</p>';
    }

    public function sanitize_utm_options( $input ) {
        $new_input = array();
        $new_input['website_url'] = isset( $input['website_url'] ) ? esc_url_raw( $input['website_url'] ) : '';
        $new_input['campaign_id'] = isset( $input['campaign_id'] ) ? sanitize_text_field( $input['campaign_id'] ) : '';
        $new_input['campaign_source'] = isset( $input['campaign_source'] ) ? sanitize_text_field( $input['campaign_source'] ) : '';
        $new_input['campaign_medium'] = isset( $input['campaign_medium'] ) ? sanitize_text_field( $input['campaign_medium'] ) : '';
        $new_input['campaign_name'] = isset( $input['campaign_name'] ) ? sanitize_text_field( $input['campaign_name'] ) : '';
        $new_input['campaign_term'] = isset( $input['campaign_term'] ) ? sanitize_text_field( $input['campaign_term'] ) : '';
        $new_input['campaign_content'] = isset( $input['campaign_content'] ) ? sanitize_text_field( $input['campaign_content'] ) : '';
        return $new_input;
    }

    // --- توابع Callback برای فیلدهای UTM ---
    // (شما فقط utm_website_url_callback را در کد ارسالی داشتید، بقیه را هم باید مشابه آن ایجاد کنید)

    public function utm_website_url_callback() {
        $options = get_option('rc_utm_options');
        // ID فیلد باید با شناسه فیلد در add_settings_field یکی باشد
        // همچنین نام فیلد در rc_utm_options باید درست باشد
        printf(
            '<input type="url" id="rc_utm_website_url" name="rc_utm_options[website_url]" value="%s" class="regular-text" style="width:70%%;" placeholder="https://www.example.com" />',
            isset( $options['website_url'] ) ? esc_url( $options['website_url'] ) : ''
        );
        echo '<p class="description">' . __('آدرس کامل وب‌سایت مانند https://www.example.com', 'ready-campaign') . '</p>';
    }

    public function utm_campaign_id_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_id" name="rc_utm_options[campaign_id]" value="%s" class="regular-text" style="width:70%%;" />',
            isset( $options['campaign_id'] ) ? esc_attr( $options['campaign_id'] ) : ''
        );
        echo '<p class="description">' . __('شناسه کمپین تبلیغاتی (اختیاری)', 'ready-campaign') . '</p>';
    }

    public function utm_campaign_source_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_source" name="rc_utm_options[campaign_source]" value="%s" class="regular-text" style="width:70%%;" placeholder="google, newsletter" />',
            isset( $options['campaign_source'] ) ? esc_attr( $options['campaign_source'] ) : ''
        );
        echo '<p class="description">' . __('منبع ارجاع دهنده (مثلاً: google، instagram، newsletter)*', 'ready-campaign') . '</p>';
    }

    public function utm_campaign_medium_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_medium" name="rc_utm_options[campaign_medium]" value="%s" class="regular-text" style="width:70%%;" placeholder="cpc, banner, email" />',
            isset( $options['campaign_medium'] ) ? esc_attr( $options['campaign_medium'] ) : ''
        );
        echo '<p class="description">' . __('رسانه تبلیغات (مثلاً: cpc، banner، email)*', 'ready-campaign') . '</p>';
    }

    public function utm_campaign_name_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_name" name="rc_utm_options[campaign_name]" value="%s" class="regular-text" style="width:70%%;" placeholder="summer_sale" />',
            isset( $options['campaign_name'] ) ? esc_attr( $options['campaign_name'] ) : ''
        );
        echo '<p class="description">' . __('نام محصول، کد تخفیف یا شعار تبلیغات (اختیاری)', 'ready-campaign') . '</p>';
    }

    public function utm_campaign_term_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_term" name="rc_utm_options[campaign_term]" value="%s" class="regular-text" style="width:70%%;" />',
            isset( $options['campaign_term'] ) ? esc_attr( $options['campaign_term'] ) : ''
        );
        echo '<p class="description">' . __('کلمات کلیدی که برای کمپین خریداری شده‌اند (اختیاری)', 'ready-campaign') . '</p>';
    }

    public function utm_campaign_content_callback() {
        $options = get_option('rc_utm_options');
        printf(
            '<input type="text" id="rc_utm_campaign_content" name="rc_utm_options[campaign_content]" value="%s" class="regular-text" style="width:70%%;" />',
            isset( $options['campaign_content'] ) ? esc_attr( $options['campaign_content'] ) : ''
        );
        echo '<p class="description">' . __('برای تفکیک تبلیغات مشابه (مانند: logolink، textlink) (اختیاری)', 'ready-campaign') . '</p>';
    }

    /**
     * تابع Callback برای نمایش فیلد لینک نهایی کمپین (UTM Result).
     * این متد باید در کلاس RC_Admin تعریف شود.
     */
    public function utm_result_callback() {
        $options = get_option('rc_utm_options'); // خواندن گزینه‌های ذخیره شده UTM

        $website_url = isset( $options['website_url'] ) ? $options['website_url'] : '';
        $campaign_id_val = isset( $options['campaign_id'] ) ? $options['campaign_id'] : ''; // تغییر نام متغیر برای جلوگیری از تداخل
        $campaign_source_val = isset( $options['campaign_source'] ) ? $options['campaign_source'] : '';
        $campaign_medium_val = isset( $options['campaign_medium'] ) ? $options['campaign_medium'] : '';
        $campaign_name_val = isset( $options['campaign_name'] ) ? $options['campaign_name'] : '';
        $campaign_term_val = isset( $options['campaign_term'] ) ? $options['campaign_term'] : '';
        $campaign_content_val = isset( $options['campaign_content'] ) ? $options['campaign_content'] : '';

        $utm_url = '';
        if (!empty($website_url) && !empty($campaign_source_val) && !empty($campaign_medium_val)) {
            // اطمینان از وجود http یا https در ابتدای آدرس
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
            <textarea id="utm_result" readonly style="width:70%; height:100px; direction:ltr; text-align:left;"><?php echo esc_textarea($utm_url); // استفاده از esc_textarea برای textarea ?></textarea>
            <p>
                <button type="button" class="button" id="copy-utm-url"><?php _e('کپی لینک', 'ready-campaign'); ?></button>
                <?php /* دکمه‌های اعمال به بنر فعلا حذف شده‌اند، چون منطق آنها با CPT تغییر می‌کند
                <button type="button" class="button" id="apply-to-desktop">استفاده در بنر دسکتاپ</button>
                <button type="button" class="button" id="apply-to-mobile">استفاده در بنر موبایل</button>
                */?>
            </p>
        </div>
        <p class="description"><?php _e('لینک نهایی UTM شما. برای استفاده، آن را کپی کنید. (تولید لینک به صورت خودکار با پر کردن فیلدهای بالا انجام می‌شود)', 'ready-campaign'); ?></p>
        <?php
    }

    // ... (متدهای add_banner_metaboxes و save_banner_metaboxes در آینده اضافه خواهند شد) ...
}
?>
