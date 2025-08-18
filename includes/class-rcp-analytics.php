<?php
if (!defined('ABSPATH')) { exit; }

class RCP_Analytics {
    private static $table = '';

    public static function init() {
        global $wpdb;
        self::$table = $wpdb->prefix . 'rc_stats';

        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('wp_ajax_nopriv_rc_track', [__CLASS__, 'ajax_track']);
        add_action('wp_ajax_rc_track', [__CLASS__, 'ajax_track']);
        add_action('admin_post_rc_export_csv', [__CLASS__, 'export_csv']);
        add_action('rc_daily_cleanup', [__CLASS__, 'cleanup']);
        if (!wp_next_scheduled('rc_daily_cleanup')) {
            wp_schedule_event(time()+3600, 'daily', 'rc_daily_cleanup');
        }
    }

    public static function maybe_create_tables() {
        global $wpdb;
        self::$table = $wpdb->prefix . 'rc_stats';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS `".self::$table."` (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            banner_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(16) NOT NULL,
            ts DATETIME NOT NULL,
            device VARCHAR(16) NULL,
            page VARCHAR(255) NULL,
            referrer VARCHAR(255) NULL,
            utm_source VARCHAR(64) NULL,
            session VARCHAR(64) NULL,
            PRIMARY KEY (id),
            KEY banner_id (banner_id),
            KEY event_type (event_type),
            KEY ts (ts)
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function menu() {
        add_submenu_page(
            'edit.php?post_type=' . RCP_Post_Type::CPT,
            __('Analytics', 'ready-campaign'),
            __('Analytics', 'ready-campaign'),
            'edit_posts',
            'rc-analytics',
            [__CLASS__, 'render']
        );
    }

    public static function ajax_track() {
        $banner_id = isset($_POST['banner_id']) ? (int)$_POST['banner_id'] : 0;
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        if (!$banner_id || !in_array($type, ['impression','click'])) wp_send_json_error();

        $device = wp_is_mobile() ? 'mobile' : 'desktop';
        $page = isset($_POST['page']) ? sanitize_text_field(wp_unslash($_POST['page'])) : '';
        $ref = isset($_POST['ref']) ? sanitize_text_field(wp_unslash($_POST['ref'])) : '';
        $utm = isset($_POST['utm']) ? sanitize_text_field(wp_unslash($_POST['utm'])) : '';
        $sess= isset($_POST['sess']) ? sanitize_text_field(wp_unslash($_POST['sess'])) : '';

        global $wpdb;
        $wpdb->insert(self::$table, [
            'banner_id' => $banner_id,
            'event_type'=> $type,
            'ts'        => current_time('mysql', 1),
            'device'    => $device,
            'page'      => mb_substr($page, 0, 255),
            'referrer'  => mb_substr($ref, 0, 255),
            'utm_source'=> mb_substr($utm, 0, 64),
            'session'   => mb_substr($sess, 0, 64),
        ], ['%d','%s','%s','%s','%s','%s','%s','%s']);

        wp_send_json_success();
    }

    public static function render() {
        if (!current_user_can('edit_posts')) return;
        global $wpdb;
        $table = self::$table;

        $from = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : date('Y-m-d', strtotime('-7 days'));
        $to   = isset($_GET['to']) ? sanitize_text_field($_GET['to']) : date('Y-m-d');
        $from_dt = $from . ' 00:00:00';
        $to_dt   = $to   . ' 23:59:59';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT banner_id,
                    SUM(event_type='impression') AS imps,
                    SUM(event_type='click') AS clicks
             FROM $table
             WHERE ts BETWEEN %s AND %s
             GROUP BY banner_id
             ORDER BY clicks DESC, imps DESC
            ", $from_dt, $to_dt
        ), ARRAY_A);

        $totals = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(event_type='impression') AS imps,
                    SUM(event_type='click') AS clicks
             FROM $table WHERE ts BETWEEN %s AND %s
            ", $from_dt, $to_dt
        ), ARRAY_A);
        $imps = intval($totals['imps']); $clicks=intval($totals['clicks']);
        $ctr = $imps ? round(($clicks/$imps)*100, 2) : 0;
        ?>
        <div class="wrap">
            <h1><?php _e('Analytics', 'ready-campaign'); ?></h1>
            <form method="get" class="rc-analytics-filters ui-card">
                <input type="hidden" name="post_type" value="<?php echo RCP_Post_Type::CPT; ?>">
                <input type="hidden" name="page" value="rc-analytics">
                <label>From <input type="date" name="from" value="<?php echo esc_attr($from); ?>"></label>
                <label>To <input type="date" name="to" value="<?php echo esc_attr($to); ?>"></label>
                <button class="button button-primary">اعمال</button>
                <a class="button" href="<?php echo esc_url(admin_url('admin-post.php?action=rc_export_csv&from='.$from.'&to='.$to)); ?>">خروجی CSV</a>
            </form>

            <div class="ui-card rc-kpis">
                <div class="kpi"><div class="kpi-title">Impressions</div><div class="kpi-value"><?php echo number_format_i18n($imps); ?></div></div>
                <div class="kpi"><div class="kpi-title">Clicks</div><div class="kpi-value"><?php echo number_format_i18n($clicks); ?></div></div>
                <div class="kpi"><div class="kpi-title">CTR</div><div class="kpi-value"><?php echo esc_html($ctr); ?>%</div></div>
            </div>

            <div class="ui-card">
                <table class="widefat striped">
                    <thead><tr><th>Banner</th><th>Imps</th><th>Clicks</th><th>CTR</th></tr></thead>
                    <tbody>
                        <?php if ($rows) :
                            foreach($rows as $r):
                                $ctr = $r['imps'] ? round(($r['clicks']/$r['imps'])*100, 2) : 0;
                                $title = get_the_title($r['banner_id']);
                        ?>
                        <tr>
                            <td><?php echo esc_html($title ?: ('#'.$r['banner_id'])); ?></td>
                            <td><?php echo number_format_i18n($r['imps']); ?></td>
                            <td><?php echo number_format_i18n($r['clicks']); ?></td>
                            <td><?php echo esc_html($ctr); ?>%</td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="4">داده‌ای برای بازه انتخابی وجود ندارد.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public static function export_csv() {
        if (!current_user_can('edit_posts')) wp_die('forbidden');
        global $wpdb;
        $table = self::$table;
        $from = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : date('Y-m-d', strtotime('-7 days'));
        $to   = isset($_GET['to']) ? sanitize_text_field($_GET['to']) : date('Y-m-d');
        $from_dt = $from . ' 00:00:00';
        $to_dt   = $to   . ' 23:59:59';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE ts BETWEEN %s AND %s ORDER BY ts DESC",
            $from_dt, $to_dt
        ), ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=rc-analytics-'.$from.'_to_'.$to.'.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','banner_id','event_type','ts','device','page','referrer','utm_source','session']);
        foreach($rows as $r) {
            fputcsv($out, $r);
        }
        fclose($out);
        exit;
    }

    public static function cleanup() {
        $opt = RCP_Settings::get();
        $days = isset($opt['retention_days']) ? intval($opt['retention_days']) : 180;
        if ($days < 30) $days = 30;
        global $wpdb;
        $table = self::$table;
        $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE ts < (NOW() - INTERVAL %d DAY)", $days));
    }
}
