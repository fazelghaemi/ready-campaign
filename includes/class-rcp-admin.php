<?php
if (!defined('ABSPATH')) { exit; }

class RCP_Admin {
    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'add_metaboxes']);
        add_action('save_post', [__CLASS__, 'save_meta']);

        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
        add_action('admin_menu', [__CLASS__, 'utm_builder_page']);

        add_action('wp_ajax_rc_apply_utm_to_banner', [__CLASS__, 'ajax_apply_utm']);
    }

    public static function admin_assets($hook) {
        wp_enqueue_style('rc-admin', RCP_ASSETS_URL . 'css/ready-campaign.css', [], RCP_VERSION);
        wp_enqueue_script('rc-admin', RCP_ASSETS_URL . 'js/ready-campaign-admin.js', ['jquery'], RCP_VERSION, true);
        wp_localize_script('rc-admin', 'RCAdmin', [
            'nonce' => wp_create_nonce('rc_admin_nonce'),
            'applyUrl' => admin_url('admin-ajax.php?action=rc_apply_utm_to_banner')
        ]);
        wp_enqueue_media();
    }

    public static function add_metaboxes() {
        add_meta_box('rc_banner_settings', __('Banner Settings', 'ready-campaign'), [__CLASS__, 'metabox_render'], RCP_Post_Type::CPT, 'normal', 'high');
    }

    public static function field($name, $default='', $post_id=0) {
        $v = get_post_meta($post_id, $name, true);
        return ($v === '') ? $default : $v;
    }

    public static function metabox_render($post) {
        wp_nonce_field('rc_save_meta', 'rc_nonce');
        $id = $post->ID;

        $get = function($k,$d='') use ($id){ return esc_attr(self::field($k,$d,$id)); };
        $geti= function($k,$d=0) use ($id){ return (int)self::field($k,$d,$id); };
        $getb= function($k,$d=false) use ($id){ return (bool)self::field($k,$d,$id); };

        $imgD = (int)self::field('rc_image_desktop',0,$id);
        $imgM = (int)self::field('rc_image_mobile',0,$id);
        $imgDurl = $imgD ? wp_get_attachment_image_url($imgD,'full') : RCP_ASSETS_URL.'img/placeholder.svg';
        $imgMurl = $imgM ? wp_get_attachment_image_url($imgM,'full') : RCP_ASSETS_URL.'img/placeholder.svg';
?>
        <style>.rc-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.rc-row{margin-bottom:12px}</style>
        <div class="rc-grid">
            <div class="ui-card">
                <h2 class="rc-h">عمومی</h2>
                <p class="rc-row"><label><input type="checkbox" name="rc_active" value="1" <?php checked(self::field('rc_active',false,$id));?>> فعال</label></p>
                <p class="rc-row">
                    <label>دستگاه</label><br>
                    <select name="rc_device">
                        <option value="both" <?php selected(self::field('rc_device','both',$id),'both');?>>هردو</option>
                        <option value="desktop" <?php selected(self::field('rc_device','both',$id),'desktop');?>>دسکتاپ</option>
                        <option value="mobile" <?php selected(self::field('rc_device','both',$id),'mobile');?>>موبایل</option>
                    </select>
                </p>
                <p class="rc-row"><label>لینک مقصد</label><br>
                    <input type="url" name="rc_link" value="<?php echo esc_url(self::field('rc_link','',$id)); ?>" class="widefat" placeholder="https://example.com/?utm_source=..."></p>

                <h2 class="rc-h">زمان‌بندی</h2>
                <p class="rc-row"><label>شروع (Y-m-d H:i)</label><br>
                    <input type="text" name="rc_start" value="<?php echo $get('rc_start',''); ?>" placeholder="2025-08-18 08:00"></p>
                <p class="rc-row"><label>پایان (Y-m-d H:i)</label><br>
                    <input type="text" name="rc_end" value="<?php echo $get('rc_end',''); ?>" placeholder="2025-09-01 23:59"></p>
                <p class="rc-row"><label>روزهای هفته (0=Sun,...,6=Sat، مثل 0,1,2)</label><br>
                    <input type="text" name="rc_days" value="<?php echo $get('rc_days',''); ?>" placeholder="1,2,3,4,5"></p>
                <p class="rc-row"><label>بازه ساعت (HH:MM)</label><br>
                    <input type="text" name="rc_time_start" value="<?php echo $get('rc_time_start',''); ?>" placeholder="09:00">
                    <input type="text" name="rc_time_end" value="<?php echo $get('rc_time_end',''); ?>" placeholder="22:00">
                </p>

                <h2 class="rc-h">قوانین نمایش و فرکانس</h2>
                <p class="rc-row"><label>موقعیت</label><br>
                    <select name="rc_position">
                        <option value="tl" <?php selected($get('rc_position','br'),'tl');?>>بالا-چپ</option>
                        <option value="tr" <?php selected($get('rc_position','br'),'tr');?>>بالا-راست</option>
                        <option value="bl" <?php selected($get('rc_position','br'),'bl');?>>پایین-چپ</option>
                        <option value="br" <?php selected($get('rc_position','br'),'br');?>>پایین-راست</option>
                        <option value="center" <?php selected($get('rc_position','br'),'center');?>>مرکز</option>
                    </select>
                </p>
                <p class="rc-row"><label>وزن نمایش (برای چرخش تصادفی)</label><br>
                    <input type="number" name="rc_weight" min="1" value="<?php echo (int)$geti('rc_weight',1); ?>"></p>
                <p class="rc-row"><label>عدم نمایش پس از بستن (روز)</label><br>
                    <input type="number" name="rc_mute_days" min="0" value="<?php echo (int)$geti('rc_mute_days',0); ?>"></p>
                <p class="rc-row"><label>سقف نمایش روزانه برای هر کاربر</label><br>
                    <input type="number" name="rc_cap_user_day" min="0" value="<?php echo (int)$geti('rc_cap_user_day',0); ?>"></p>
                <p class="rc-row"><label>تأخیر نمایش (میلی‌ثانیه)</label><br>
                    <input type="number" name="rc_delay_ms" min="0" value="<?php echo (int)$geti('rc_delay_ms',0); ?>"></p>
                <p class="rc-row"><label>نمایش پس از اسکرول (%)</label><br>
                    <input type="number" name="rc_scroll_percent" min="0" max="100" value="<?php echo (int)$geti('rc_scroll_percent',0); ?>"></p>
                <p class="rc-row"><label><input type="checkbox" name="rc_exit_intent" value="1" <?php checked($getb('rc_exit_intent',false)); ?>> نمایش بر اساس خروج کاربر (Exit-Intent)</label></p>
            </div>

            <div class="ui-card">
                <h2 class="rc-h">رسانه و ظاهر</h2>
                <p class="rc-row"><label>تصویر دسکتاپ</label><br>
                    <img src="<?php echo esc_url($imgDurl);?>" class="rc-preview-img">
                    <input type="hidden" name="rc_image_desktop" value="<?php echo (int)$imgD;?>">
                    <button class="button rcp-media" data-target="rc_image_desktop">انتخاب/آپلود</button>
                </p>
                <p class="rc-row"><label>تصویر موبایل</label><br>
                    <img src="<?php echo esc_url($imgMurl);?>" class="rc-preview-img">
                    <input type="hidden" name="rc_image_mobile" value="<?php echo (int)$imgM;?>">
                    <button class="button rcp-media" data-target="rc_image_mobile">انتخاب/آپلود</button>
                </p>
                <p class="rc-row"><label>عرض (مثلاً 320px یا 80%)</label><br>
                    <input type="text" name="rc_width" value="<?php echo $get('rc_width','320px'); ?>" placeholder="320px"></p>
                <p class="rc-row"><label>فاصله X</label><br>
                    <input type="text" name="rc_offset_x" value="<?php echo $get('rc_offset_x','16px'); ?>" placeholder="16px"></p>
                <p class="rc-row"><label>فاصله Y</label><br>
                    <input type="text" name="rc_offset_y" value="<?php echo $get('rc_offset_y','16px'); ?>" placeholder="16px"></p>
                <p class="rc-row"><label>انحنای گوشه‌ها</label><br>
                    <input type="text" name="rc_radius" value="<?php echo $get('rc_radius','12px'); ?>" placeholder="12px"></p>
                <p class="rc-row"><label>انیمیشن ورود</label><br>
                    <select name="rc_anim_in">
                        <option value="fade" <?php selected($get('rc_anim_in','fade'),'fade');?>>Fade</option>
                        <option value="slide" <?php selected($get('rc_anim_in','fade'),'slide');?>>Slide</option>
                        <option value="zoom" <?php selected($get('rc_anim_in','fade'),'zoom');?>>Zoom</option>
                        <option value="bounce" <?php selected($get('rc_anim_in','fade'),'bounce');?>>Bounce</option>
                    </select>
                </p>
                <p class="rc-row"><label>انیمیشن خروج</label><br>
                    <select name="rc_anim_out">
                        <option value="fade" <?php selected($get('rc_anim_out','fade'),'fade');?>>Fade</option>
                        <option value="slide" <?php selected($get('rc_anim_out','fade'),'slide');?>>Slide</option>
                        <option value="zoom" <?php selected($get('rc_anim_out','fade'),'zoom');?>>Zoom</option>
                        <option value="bounce" <?php selected($get('rc_anim_out','fade'),'bounce');?>>Bounce</option>
                    </select>
                </p>

                <h2 class="rc-h">منبع ورودی و آدرس</h2>
                <p class="rc-row"><label>الزام UTM Source (لیست جدا با کاما)</label><br>
                    <input type="text" name="rc_require_utm_source" value="<?php echo $get('rc_require_utm_source',''); ?>" placeholder="google,instagram"></p>
                <p class="rc-row"><label>Referrer باید شامل (کاما/خط جدید)</label><br>
                    <textarea name="rc_referrer_contains" rows="2" class="widefat" placeholder="facebook.com, telegram.me"><?php echo esc_textarea(self::field('rc_referrer_contains','',$id)); ?></textarea></p>
                <p class="rc-row"><label>شامل URLها (هر خط یک عبارت)</label><br>
                    <textarea name="rc_include_urls" rows="2" class="widefat" placeholder="/blog/, /sale"><?php echo esc_textarea(self::field('rc_include_urls','',$id)); ?></textarea></p>
                <p class="rc-row"><label>حذف URLها (هر خط یک عبارت)</label><br>
                    <textarea name="rc_exclude_urls" rows="2" class="widefat" placeholder="/checkout"><?php echo esc_textarea(self::field('rc_exclude_urls','',$id)); ?></textarea></p>
            </div>
        </div>

        <div class="ui-card rc-live">
            <h2 class="rc-h">پیش‌نمایش زنده</h2>
            <div id="rc_live_stage">
                <div class="rc-banner rc-pos-br rc-in-fade" style="width:320px;border-radius:12px;--rc-ox:16px;--rc-oy:16px;">
                    <button class="rc-close" aria-label="Close">×</button>
                    <img id="rc_live_img" src="<?php echo esc_url($imgDurl); ?>" alt="preview">
                </div>
            </div>
            <p class="description">پیش‌نمایش صرفاً نمایهٔ ظاهری است و قوانین نمایش/تریگرها را شبیه‌سازی نمی‌کند.</p>
        </div>
<?php
    }

    public static function save_meta($post_id) {
        if (!isset($_POST['rc_nonce']) || !wp_verify_nonce($_POST['rc_nonce'], 'rc_save_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $b = function($k){ return isset($_POST[$k]) ? (bool)$_POST[$k] : false; };
        $s = function($k,$def=''){ return isset($_POST[$k]) ? sanitize_text_field($_POST[$k]) : $def; };
        $i = function($k){ return isset($_POST[$k]) ? (int)$_POST[$k] : 0; };
        $t = function($k){ return isset($_POST[$k]) ? wp_kses_post($_POST[$k]) : ''; };

        update_post_meta($post_id,'rc_active', $b('rc_active'));
        update_post_meta($post_id,'rc_device', in_array($s('rc_device','both'),['desktop','mobile','both'])? $s('rc_device','both'):'both');
        update_post_meta($post_id,'rc_image_desktop', $i('rc_image_desktop'));
        update_post_meta($post_id,'rc_image_mobile',  $i('rc_image_mobile'));
        update_post_meta($post_id,'rc_link', esc_url_raw($s('rc_link','')));

        update_post_meta($post_id,'rc_start', $s('rc_start',''));
        update_post_meta($post_id,'rc_end',   $s('rc_end',''));
        update_post_meta($post_id,'rc_days',  $s('rc_days',''));
        update_post_meta($post_id,'rc_time_start',$s('rc_time_start',''));
        update_post_meta($post_id,'rc_time_end',  $s('rc_time_end',''));

        update_post_meta($post_id,'rc_position', in_array($s('rc_position','br'),['tl','tr','bl','br','center'])? $s('rc_position','br'):'br');
        update_post_meta($post_id,'rc_width',  $s('rc_width','320px'));
        update_post_meta($post_id,'rc_offset_x',$s('rc_offset_x','16px'));
        update_post_meta($post_id,'rc_offset_y',$s('rc_offset_y','16px'));
        update_post_meta($post_id,'rc_radius', $s('rc_radius','12px'));
        update_post_meta($post_id,'rc_weight', $i('rc_weight'));

        update_post_meta($post_id,'rc_include_urls', $t('rc_include_urls'));
        update_post_meta($post_id,'rc_exclude_urls', $t('rc_exclude_urls'));
        update_post_meta($post_id,'rc_referrer_contains', $t('rc_referrer_contains'));
        update_post_meta($post_id,'rc_require_utm_source', $s('rc_require_utm_source',''));

        update_post_meta($post_id,'rc_cap_user_day', $i('rc_cap_user_day'));
        update_post_meta($post_id,'rc_mute_days',    $i('rc_mute_days'));
        update_post_meta($post_id,'rc_delay_ms',     $i('rc_delay_ms'));
        update_post_meta($post_id,'rc_scroll_percent',$i('rc_scroll_percent'));
        update_post_meta($post_id,'rc_exit_intent',  $b('rc_exit_intent'));

        update_post_meta($post_id,'rc_anim_in',  in_array($s('rc_anim_in','fade'),['fade','slide','zoom','bounce'])? $s('rc_anim_in','fade'):'fade');
        update_post_meta($post_id,'rc_anim_out', in_array($s('rc_anim_out','fade'),['fade','slide','zoom','bounce'])? $s('rc_anim_out','fade'):'fade');
    }

    public static function utm_builder_page() {
        add_submenu_page(
            'edit.php?post_type=' . RCP_Post_Type::CPT,
            __('UTM Builder', 'ready-campaign'),
            __('UTM Builder', 'ready-campaign'),
            'edit_posts',
            'rc-utm-builder',
            [__CLASS__, 'utm_builder_render']
        );
    }

    public static function utm_builder_render() {
        if (!current_user_can('edit_posts')) return;
        $nonce = wp_create_nonce('rc_admin_nonce');

        $banners = get_posts([
            'post_type' => RCP_Post_Type::CPT,
            'numberposts' => -1,
            'post_status' => 'any',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
?>
<div class="wrap">
    <h1>UTM Builder</h1>
    <div class="rc-utm ui-card">
        <div class="rc-utm-grid">
            <label>Base URL
                <input type="url" id="rc_base" placeholder="https://example.com/">
            </label>
            <label>utm_source <input type="text" id="rc_source" placeholder="google"></label>
            <label>utm_medium <input type="text" id="rc_medium" placeholder="cpc"></label>
            <label>utm_campaign <input type="text" id="rc_campaign" placeholder="summer_sale"></label>
            <label>utm_id <input type="text" id="rc_id" placeholder="id-123"></label>
            <label>utm_term <input type="text" id="rc_term" placeholder="keyword"></label>
            <label>utm_content <input type="text" id="rc_content" placeholder="banner_a"></label>
        </div>
        <div class="rc-utm-actions">
            <button class="button button-primary" id="rc_build">ساخت لینک</button>
            <input type="text" id="rc_result" readonly placeholder="Result URL will appear here" class="widefat">
            <button class="button" id="rc_copy">کپی لینک</button>
        </div>
        <hr>
        <div class="rc-utm-apply">
            <label>اعمال روی بنر:
                <select id="rc_banner">
                    <option value="">— انتخاب بنر —</option>
                    <?php foreach($banners as $b): ?>
                        <option value="<?php echo (int)$b->ID; ?>"><?php echo esc_html($b->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button button-secondary" id="rc_apply">اعمال لینک روی بنر انتخاب‌شده</button>
        </div>
    </div>
</div>
<script>window.RCAdmin = Object.assign(window.RCAdmin||{}, {nonce:'<?php echo esc_js($nonce); ?>'});</script>
<?php
    }

    public static function ajax_apply_utm() {
        check_ajax_referer('rc_admin_nonce','nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error(['message'=>'no perms'], 403);

        $banner_id = isset($_POST['banner_id']) ? (int)$_POST['banner_id'] : 0;
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (!$banner_id || empty($url)) wp_send_json_error(['message'=>'invalid']);

        update_post_meta($banner_id, 'rc_link', $url);
        wp_send_json_success(['message'=>'updated']);
    }
}
