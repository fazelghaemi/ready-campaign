<?php
if (!defined('ABSPATH')) { exit; }

class RCP_Frontend {
    private static $candidates = null;

    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets']);
        add_action('wp_footer', [__CLASS__, 'render_banners'], 100);
        add_shortcode('ready_campaign', [__CLASS__, 'shortcode']);
    }

    public static function assets() {
        $opt = RCP_Settings::get();
        wp_enqueue_style('ready-campaign', RCP_ASSETS_URL . 'css/ready-campaign.css', [], RCP_VERSION);
        wp_enqueue_script('ready-campaign', RCP_ASSETS_URL . 'js/ready-campaign.js', [], RCP_VERSION, true);
        wp_localize_script('ready-campaign', 'RCVars', [
            'ajax' => admin_url('admin-ajax.php'),
            'safe' => [
                'top' => $opt['safe_top'],
                'right' => $opt['safe_right'],
                'bottom' => $opt['safe_bottom'],
                'left' => $opt['safe_left'],
            ],
        ]);
    }

    public static function now_ts() {
        // Keep a real Unix timestamp; wp_date() applies the site timezone below.
        return current_time('timestamp', true);
    }

    private static function get_candidates_base() {
        $opt = RCP_Settings::get();
        $cache_key = 'rc_candidates_base';
        $ttl = max(0, intval($opt['cache_ttl']));
        $cached = $ttl ? get_transient($cache_key) : false;
        if ($cached !== false) return $cached;

        $q = new WP_Query([
            'post_type' => RCP_Post_Type::CPT,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ]);

        // Do not rely on one serialized representation of a boolean meta value.
        // Older versions may have stored true/1/on differently.
        $ids = array_values(array_filter($q->posts, function($id) {
            return (bool)get_post_meta($id, 'rc_active', true);
        }));
        if ($ttl) set_transient($cache_key, $ids, $ttl);
        return $ids;
    }

    private static function match_rules($id) {
        if (!(int)get_post_meta($id, 'rc_image_desktop', true) && !(int)get_post_meta($id, 'rc_image_mobile', true)) return false;
        $now = self::now_ts();
        $device = wp_is_mobile() ? 'mobile' : 'desktop';
        $start = get_post_meta($id,'rc_start',true);
        $end   = get_post_meta($id,'rc_end',true);
        $start_ts = $start ? self::local_datetime_to_timestamp($start) : 0;
        $end_ts   = $end   ? self::local_datetime_to_timestamp($end, true) : 0;
        if ($start_ts && $now < $start_ts) return false;
        if ($end_ts && $now > $end_ts) return false;

        $dev = get_post_meta($id,'rc_device',true) ?: 'both';
        if (!in_array($dev,['both','desktop','mobile'])) $dev='both';
        if ($dev !== 'both' && $dev !== $device) return false;

        $days = self::rule_values(get_post_meta($id,'rc_days',true));
        if ($days) {
            $list = array_values(array_filter($days, function($value) { return in_array($value, ['0','1','2','3','4','5','6'], true); }));
            if ($list) {
                $dow = intval(wp_date('w', $now));
                if (!in_array((string)$dow, $list, true)) return false;
            }
        }
        $hs = trim((string)get_post_meta($id,'rc_time_start',true));
        $he = trim((string)get_post_meta($id,'rc_time_end',true));
        $start_min = self::time_to_minutes($hs);
        $end_min = self::time_to_minutes($he);
        if ($start_min !== null || $end_min !== null) {
            $hnow = intval(current_time('H'))*60 + intval(current_time('i'));
            if ($start_min !== null && $end_min !== null && $start_min > $end_min) {
                if (!($hnow >= $start_min || $hnow <= $end_min)) return false;
            } else {
                if ($start_min !== null && $hnow < $start_min) return false;
                if ($end_min !== null && $hnow > $end_min) return false;
            }
        }

        $cur = home_url(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
        $inc = trim((string)get_post_meta($id,'rc_include_urls',true));
        if ($inc !== '') {
            $ok = false;
            foreach (self::rule_values($inc) as $line) {
                if ($line && strpos($cur, $line) !== false) { $ok = true; break; }
            }
            if (!$ok) return false;
        }
        $exc = trim((string)get_post_meta($id,'rc_exclude_urls',true));
        if ($exc !== '') {
            foreach (self::rule_values($exc) as $line) {
                if ($line && strpos($cur, $line) !== false) { return false; }
            }
        }

        $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $refs = trim((string)get_post_meta($id,'rc_referrer_contains',true));
        if ($refs !== '') {
            $ok = false;
            foreach (self::rule_values($refs) as $token) {
                if ($token && strpos($ref, $token) !== false) { $ok = true; break; }
            }
            if (!$ok) return false;
        }

        $req = trim((string)get_post_meta($id,'rc_require_utm_source',true));
        if ($req !== '') {
            $allowed = self::rule_values($req);
            $utm = isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : '';
            if (!in_array($utm, $allowed, true)) return false;
        }

        return true;
    }

    private static function rule_values($value) {
        $value = trim((string)$value);
        if ($value === '') return [];
        $value = strtr($value, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
        return array_values(array_filter(array_map('trim', preg_split('/[,،\r\n]+/u', $value))));
    }

    private static function time_to_minutes($value) {
        if (!preg_match('/^(?:[01]?\d|2[0-3]):[0-5]\d$/', trim((string)$value))) return null;
        list($hour, $minute) = array_map('intval', explode(':', $value));
        return ($hour * 60) + $minute;
    }

    private static function local_datetime_to_timestamp($value, $end_of_day = false) {
        try {
            $timezone = wp_timezone();
            $date = new DateTime($value, $timezone);
            if ($end_of_day && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) $date->setTime(23, 59, 59);
            return $date->getTimestamp();
        } catch (Exception $e) {
            return 0;
        }
    }

    private static function css_value($value, $default) {
        $value = trim((string)$value);
        return preg_match('/^(?:0|(?:\d+(?:\.\d+)?)(?:px|rem|em|vw|vh|%))$/', $value) ? $value : $default;
    }

    private static function get_candidates() {
        if (self::$candidates !== null) return self::$candidates;
        $ids = self::get_candidates_base();
        $out = [];
        foreach($ids as $id) {
            if (self::match_rules($id)) $out[] = $id;
        }
        self::$candidates = $out;
        return $out;
    }

    private static function weighted_pick($ids) {
        $bag = [];
        foreach($ids as $id) {
            $w = intval(get_post_meta($id,'rc_weight',true));
            if ($w < 1) $w = 1;
            for ($i=0; $i<$w; $i++) $bag[] = $id;
        }
        if (empty($bag)) return 0;
        return $bag[array_rand($bag)];
    }

    private static function group_by_position($ids) {
        $groups = ['tl'=>[],'tr'=>[],'bl'=>[],'br'=>[],'center'=>[]];
        foreach($ids as $id) {
            $pos = get_post_meta($id,'rc_position',true) ?: 'br';
            if (!isset($groups[$pos])) $groups[$pos] = [];
            $groups[$pos][] = $id;
        }
        return $groups;
    }

    private static function render_banner_html($id, $device = null) {
        if (!$id) return '';
        $device = $device ?: (wp_is_mobile() ? 'mobile' : 'desktop');

        $pos     = get_post_meta($id,'rc_position',true) ?: 'br';
        $width   = self::css_value(get_post_meta($id,'rc_width',true), '320px');
        $ox      = self::css_value(get_post_meta($id,'rc_offset_x',true), '16px');
        $oy      = self::css_value(get_post_meta($id,'rc_offset_y',true), '16px');
        $radius  = self::css_value(get_post_meta($id,'rc_radius',true), '12px');
        $animIn  = get_post_meta($id,'rc_anim_in',true) ?: 'fade';
        $animOut = get_post_meta($id,'rc_anim_out',true) ?: 'fade';
        $link_desktop = get_post_meta($id, 'rc_link_desktop', true) ?: get_post_meta($id,'rc_link',true);
        $link_mobile = get_post_meta($id, 'rc_link_mobile', true) ?: $link_desktop;
        $link = ($device === 'mobile') ? $link_mobile : $link_desktop;
        $img_desktop_id = (int)get_post_meta($id,'rc_image_desktop',true);
        $img_mobile_id  = (int)get_post_meta($id,'rc_image_mobile',true);
        $img_id  = ($device==='mobile') ? ($img_mobile_id ?: $img_desktop_id) : ($img_desktop_id ?: $img_mobile_id);

        $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'full') : RCP_ASSETS_URL . 'img/placeholder.svg';
        if (!$img_url) $img_url = RCP_ASSETS_URL . 'img/placeholder.svg';
        $mobile_url = $img_mobile_id ? wp_get_attachment_image_url($img_mobile_id, 'full') : $img_url;
        $desktop_url = $img_desktop_id ? wp_get_attachment_image_url($img_desktop_id, 'full') : $mobile_url;
        if (!$mobile_url) $mobile_url = $desktop_url;
        if (!$desktop_url) $desktop_url = RCP_ASSETS_URL . 'img/placeholder.svg';
        $img_alt = esc_attr(get_the_title($id));
        $classes = 'rc-banner rc-pos-'.$pos.' rc-in-'.$animIn.' rc-out-'.$animOut;
        $style = sprintf('width:%s;border-radius:%s;--rc-ox:%s;--rc-oy:%s;', esc_attr($width), esc_attr($radius), esc_attr($ox), esc_attr($oy));
        $delay   = intval(get_post_meta($id,'rc_delay_ms',true));
        $scrollP = intval(get_post_meta($id,'rc_scroll_percent',true));
        $exitInt = get_post_meta($id,'rc_exit_intent',true) ? 1 : 0;
        $capDay  = intval(get_post_meta($id,'rc_cap_user_day',true));
        $muteDays= intval(get_post_meta($id,'rc_mute_days',true));

        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>"
             style="<?php echo esc_attr($style); ?>"
             data-banner-id="<?php echo (int)$id; ?>"
             data-delay="<?php echo esc_attr($delay); ?>"
             data-scroll="<?php echo esc_attr($scrollP); ?>"
             data-exit="<?php echo esc_attr($exitInt); ?>"
             data-capday="<?php echo esc_attr($capDay); ?>"
             data-mutedays="<?php echo esc_attr($muteDays); ?>">
            <button class="rc-close" aria-label="بستن بنر">×</button>
            <?php if (!$device): ?>
                <?php if ($link_desktop): ?><a class="rc-link rc-link-desktop" href="<?php echo esc_url($link_desktop); ?>" target="_blank" rel="noopener nofollow sponsored"><img src="<?php echo esc_url($desktop_url); ?>" alt="<?php echo $img_alt; ?>" loading="lazy"/></a><?php else: ?><img class="rc-link-desktop" src="<?php echo esc_url($desktop_url); ?>" alt="<?php echo $img_alt; ?>" loading="lazy"/><?php endif; ?>
                <?php if ($link_mobile): ?><a class="rc-link rc-link-mobile" href="<?php echo esc_url($link_mobile); ?>" target="_blank" rel="noopener nofollow sponsored"><img src="<?php echo esc_url($mobile_url); ?>" alt="<?php echo $img_alt; ?>" loading="lazy"/></a><?php else: ?><img class="rc-link-mobile" src="<?php echo esc_url($mobile_url); ?>" alt="<?php echo $img_alt; ?>" loading="lazy"/><?php endif; ?>
            <?php elseif ($link): ?><a class="rc-link" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener nofollow sponsored"><img src="<?php echo esc_url($img_url); ?>" alt="<?php echo $img_alt; ?>" loading="lazy"/></a>
            <?php else: ?><img src="<?php echo esc_url($img_url); ?>" alt="<?php echo $img_alt; ?>" loading="lazy"/><?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_banners() {
        $opt = RCP_Settings::get();
        $ids = self::get_candidates();
        if (empty($ids)) return;
        $groups = self::group_by_position($ids);

        $count = 0; $max = intval($opt['max_per_request']); if ($max < 1) $max = 1;
        $html = '';
        foreach($groups as $pos => $list) {
            if (empty($list)) continue;
            if ($count >= $max) break;
            $id = self::weighted_pick($list);
            if (!$id) continue;
            $html .= self::render_banner_html($id);
            $count++;
        }
        if ($html) {
            $safe = sprintf('--rc-safe-top:%s;--rc-safe-right:%s;--rc-safe-bottom:%s;--rc-safe-left:%s;',
                esc_attr($opt['safe_top']), esc_attr($opt['safe_right']), esc_attr($opt['safe_bottom']), esc_attr($opt['safe_left']));
            echo '<div class="rc-root" style="'.$safe.'">'.$html.'</div>';
        }
    }

    public static function shortcode($atts) {
        $atts = shortcode_atts(['id'=>0,'device'=>''], $atts, 'ready_campaign');
        $id = (int)$atts['id'];
        $device = $atts['device'] ?: null;
        if ($id) return self::render_banner_html($id, $device);
        ob_start();
        self::render_banners();
        return ob_get_clean();
    }
}
