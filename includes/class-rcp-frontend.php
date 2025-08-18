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
        return current_time('timestamp');
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
            'meta_key' => 'rc_active',
            'meta_value' => 1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ]);

        $ids = $q->posts;
        if ($ttl) set_transient($cache_key, $ids, $ttl);
        return $ids;
    }

    private static function match_rules($id) {
        $now = self::now_ts();
        $device = wp_is_mobile() ? 'mobile' : 'desktop';
        $start = get_post_meta($id,'rc_start',true);
        $end   = get_post_meta($id,'rc_end',true);
        $start_ts = $start ? strtotime($start) : 0;
        $end_ts   = $end   ? strtotime($end)   : 0;
        if ($start_ts && $now < $start_ts) return false;
        if ($end_ts && $now > $end_ts) return false;

        $dev = get_post_meta($id,'rc_device',true) ?: 'both';
        if (!in_array($dev,['both','desktop','mobile'])) $dev='both';
        if ($dev !== 'both' && $dev !== $device) return false;

        $days = trim((string)get_post_meta($id,'rc_days',true));
        if ($days !== '') {
            $list = array_map('trim', explode(',', $days));
            $dow = intval(gmdate('w', $now + (get_option('gmt_offset')*3600)));
            if (!in_array((string)$dow, $list, true)) return false;
        }
        $hs = trim((string)get_post_meta($id,'rc_time_start',true));
        $he = trim((string)get_post_meta($id,'rc_time_end',true));
        if ($hs !== '' && $he !== '') {
            $hnow = intval(current_time('H'))*60 + intval(current_time('i'));
            list($h1,$m1) = array_map('intval', explode(':', $hs));
            list($h2,$m2) = array_map('intval', explode(':', $he));
            $smin = $h1*60 + $m1; $emin = $h2*60 + $m2;
            if ($smin <= $emin) {
                if ($hnow < $smin || $hnow > $emin) return false;
            } else {
                if (!($hnow >= $smin || $hnow <= $emin)) return false;
            }
        }

        $cur = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $inc = trim((string)get_post_meta($id,'rc_include_urls',true));
        if ($inc !== '') {
            $ok = false;
            foreach (preg_split('/\r\n|\r|\n/', $inc) as $line) {
                $line = trim($line);
                if ($line && strpos($cur, $line) !== false) { $ok = true; break; }
            }
            if (!$ok) return false;
        }
        $exc = trim((string)get_post_meta($id,'rc_exclude_urls',true));
        if ($exc !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $exc) as $line) {
                $line = trim($line);
                if ($line && strpos($cur, $line) !== false) { return false; }
            }
        }

        $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $refs = trim((string)get_post_meta($id,'rc_referrer_contains',true));
        if ($refs !== '') {
            $ok = false;
            foreach (preg_split('/,|\r\n|\r|\n/', $refs) as $token) {
                $token = trim($token);
                if ($token && strpos($ref, $token) !== false) { $ok = true; break; }
            }
            if (!$ok) return false;
        }

        $req = trim((string)get_post_meta($id,'rc_require_utm_source',true));
        if ($req !== '') {
            $allowed = array_map('trim', explode(',', $req));
            $utm = isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : '';
            if (!in_array($utm, $allowed, true)) return false;
        }

        return true;
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
        $width   = get_post_meta($id,'rc_width',true) ?: '320px';
        $ox      = get_post_meta($id,'rc_offset_x',true) ?: '16px';
        $oy      = get_post_meta($id,'rc_offset_y',true) ?: '16px';
        $radius  = get_post_meta($id,'rc_radius',true) ?: '12px';
        $animIn  = get_post_meta($id,'rc_anim_in',true) ?: 'fade';
        $animOut = get_post_meta($id,'rc_anim_out',true) ?: 'fade';
        $link    = get_post_meta($id,'rc_link',true) ?: '';
        $img_id  = ($device==='mobile') ? (int)get_post_meta($id,'rc_image_mobile',true) : (int)get_post_meta($id,'rc_image_desktop',true);

        $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'full') : RCP_ASSETS_URL . 'img/placeholder.svg';
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
            <button class="rc-close" aria-label="Close">×</button>
            <?php if ($link): ?><a class="rc-link" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener nofollow sponsored"><?php endif; ?>
                <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo $img_alt; ?>" loading="lazy"/>
            <?php if ($link): ?></a><?php endif; ?>
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
