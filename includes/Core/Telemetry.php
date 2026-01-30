<?php
/**
 * Telemetry Engine
 * Collects server and WordPress environment data.
 */
declare(strict_types=1);

namespace SystemDeck\Core;

if (!defined('ABSPATH')) { exit; }

class Telemetry
{
    /**
     * Get All System Diagnostics (Unified)
     * Philosophy: Safe execution on any host environment.
     */
    public static function get_all_metrics(): array
    {
        global $wpdb, $wp_version;

        // --- 1. Timing & Metrics ---
        $start_micro = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $times = self::get_time_diagnostics();

        $wp_time = number_format((float)self::safe_timer_stop(), 3);
        $db_query_time = (defined('SAVEQUERIES') && SAVEQUERIES) ? self::safe_timer_stop() : '';
        $db_time_str = $db_query_time ? ' (' . $db_query_time . 's)' : '';

        $srv_time = isset($_SERVER['REQUEST_TIME_FLOAT']) ? number_format(microtime(true) - $start_micro, 3) : '0.000';
        $memory_raw = memory_get_peak_usage(true);
        $memory_fmt = size_format($memory_raw);
        $limit_str = ini_get('memory_limit');
        $mem_mb = $memory_raw / 1048576;
        $queries = get_num_queries();

        // --- 2. WP-Specific Counts ---
        if (!function_exists('get_plugins')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        $inactive_plugins = count($all_plugins) - count($active_plugins);
        $all_themes = wp_get_themes();
        $inactive_themes = count($all_themes) - 1;
        $wp_install_date = get_option('install_date') ? date('Y-m-d', get_option('install_date')) : 'Unknown';
        $uploads_dir_size = self::get_uploads_size();

        // --- 3. DATABASE & CACHE ---
        $table_count = $wpdb->get_var("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
        $db_size = $wpdb->get_var("SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
        $db_size_fmt = size_format((int)$db_size, 2);

        // Safe check for autoload query
        $autoload_bytes = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb->options'") === $wpdb->options) {
             $autoload_bytes = (int) $wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM $wpdb->options WHERE autoload = 'yes'");
        }
        $autoload_fmt = size_format($autoload_bytes, 2);

        $object_cache = wp_using_ext_object_cache() ? 'Persistent' : 'File/None';
        $mysql_ver = $wpdb->db_version() ?? 'Unknown';
        $cache_path = self::get_cache_path();

        // --- 4. HARDWARE & NETWORK ---
        $hw = self::get_server_hardware();
        $disk_usage = self::get_disk_usage();
        $host_name = gethostname() ?: 'N/A';
        $user_ip = self::get_real_ip();
        $geo_loc = self::get_geo_location();
        // gethostbyname can be slow, use with caution or cache? Keeping it simple for now.
        $host_ip = gethostbyname($_SERVER['SERVER_NAME'] ?? 'localhost');
        $ip_list = gethostbynamel($_SERVER['SERVER_NAME'] ?? 'localhost');
        $ip_count = is_array($ip_list) ? count($ip_list) : 1;

        // --- 5. CONFIG & LIMITS ---
        $upload_max = size_format(wp_max_upload_size());
        $max_exec = ini_get('max_execution_time') . 's';
        $debug_mode = (defined('WP_DEBUG') && WP_DEBUG) ? 'Enabled' : 'Disabled';

        $auto_up_core = 'Enabled';
        if (defined('WP_AUTO_UPDATE_CORE')) {
            $auto_up_core = (is_bool(WP_AUTO_UPDATE_CORE) || WP_AUTO_UPDATE_CORE === 'true') ? 'Enabled (Const)' : 'Disabled (Const)';
        } elseif (has_filter('automatic_updater_disabled')) {
            $auto_up_core = 'Disabled (Filter)';
        }

        $display_errors = ini_get('display_errors');
        $errors_status = ($display_errors && strtolower($display_errors) !== 'off') ? 'ON' : 'Off';

        // --- 6. ASSETS ---
        $scripts_raw = self::get_assets_data('scripts');
        $styles_raw  = self::get_assets_data('styles');
        $script_str  = $scripts_raw['counts']['enqueued'] . ' / ' . $scripts_raw['counts']['registered'];
        $style_str   = $styles_raw['counts']['enqueued'] . ' / ' . $styles_raw['counts']['registered'];

        // --- COLORS (Thresholds) ---


        $t_color = 'var(--wp--preset--color--vivid-green-cyan)';
        if ((float)$wp_time > 2) $t_color = 'var(--wp--preset--color--vivid-red)';
        elseif ((float)$wp_time > 1) $t_color = 'var(--wp--preset--color--luminous-vivid-amber)';

        $m_color = 'var(--wp--preset--color--vivid-green-cyan)';
        if ($mem_mb > 128) $m_color = 'var(--wp--preset--color--vivid-red)';
        elseif ($mem_mb > 96) $m_color = 'var(--wp--preset--color--luminous-vivid-amber)';

        $q_color = 'var(--wp--preset--color--vivid-green-cyan)';
        if ($queries > 150) $q_color = 'var(--wp--preset--color--vivid-red)';
        elseif ($queries > 80) $q_color = 'var(--wp--preset--color--luminous-vivid-amber)';

        $d_color = ($debug_mode === 'Enabled') ? 'var(--wp--preset--color--luminous-vivid-amber)' : 'inherit';
        $errors_color = ($errors_status === 'ON') ? 'var(--wp--preset--color--luminous-vivid-amber)' : 'inherit';

        $al_color = 'var(--wp--preset--color--vivid-green-cyan)';
        if ($autoload_bytes > 1048576) $al_color = 'var(--wp--preset--color--vivid-red)';
        elseif ($autoload_bytes > 800000) $al_color = 'var(--wp--preset--color--luminous-vivid-amber)';

        // Helper closure for quick consistency
        $style = function($v, $bold = false, $color = null) {
            $s = [];
            if ($bold) $s[] = 'font-weight:700;';
            if ($color) $s[] = "color:{$color};";
            if (empty($s)) return $v;
            return '<span style="' . implode(' ', $s) . '">' . $v . '</span>';
        };

        return [
            // GROUP 1: VITALS (Performance) - Already styled manually above
            'load' => ['label' => 'WP Load Time', 'value' => "<span style='color:{$t_color}; font-weight:700;'>{$wp_time}s</span> / {$srv_time}s", 'icon' => 'dashicons-dashboard'],
            'queries' => ['label' => 'DB Queries', 'value' => "<span style='color:{$q_color}; font-weight:700;'>{$queries}</span>" . $db_time_str, 'icon' => 'dashicons-search'],
            'memory' => ['label' => 'Memory Usage', 'value' => "<span style='color:{$m_color}; font-weight:700;'>{$memory_fmt}</span> / {$limit_str}", 'icon' => 'dashicons-info'],

            // GROUP 2: TIME & CORE
            'time_srv' => ['label' => 'Server Time', 'value' => $times['wp_ts'] ? date('H:i:s', time()) . ' (UTC)' : 'Unknown', 'icon' => 'dashicons-clock'],
            'uptime' => ['label' => 'Server Uptime', 'value' => $times['uptime'], 'icon' => 'dashicons-backup'],
            'time_wp' => ['label' => 'WP Time Zone', 'value' => date('H:i:s', $times['wp_ts']) . ' (' . $times['tz_wp'] . ')', 'icon' => 'dashicons-wordpress'],
            'time_db' => ['label' => 'MySQL Time', 'value' => explode(' ', $times['db_time'])[1] ?? 'Error', 'icon' => 'dashicons-database'],
            'wp_install' => ['label' => 'Install Date', 'value' => $wp_install_date, 'icon' => 'dashicons-calendar-alt'],

            // GROUP 3: DATABASE & CACHE
            'dbsize' => ['label' => 'DB Size', 'value' => $db_size_fmt . ' (' . $table_count . ' tables)', 'icon' => 'dashicons-category'],
            'autoload' => ['label' => 'Autoload Options', 'value' => "<span style='color:{$al_color}; font-weight:700;'>{$autoload_fmt}</span>", 'icon' => 'dashicons-archive'],
            'objcache' => ['label' => 'Object Cache', 'value' => $object_cache, 'icon' => 'dashicons-cloud'],
            'cache_path' => ['label' => 'Cache Path Hint', 'value' => $cache_path, 'icon' => 'dashicons-admin-settings'],

            // GROUP 4: CONFIG & PLUGINS
            'plugins' => ['label' => 'Plugins (Act/Total)', 'value' => $style(count($active_plugins) . ' / ' . count($all_plugins), true), 'icon'  => 'dashicons-admin-plugins'],
            'themes' => ['label' => 'Themes (Inac/Total)', 'value' => $style($inactive_themes . ' / ' . count($all_themes), true), 'icon'  => 'dashicons-admin-appearance'],
            'debug' => ['label' => 'WP Debug', 'value' => $style($debug_mode, true, $d_color !== 'inherit' ? $d_color : null), 'icon'  => 'dashicons-buddicons-replies'],
            'display_errors' => ['label' => 'PHP Errors', 'value' => $style($errors_status, true, $errors_color !== 'inherit' ? $errors_color : null), 'icon' => 'dashicons-warning'],
            'updates' => ['label' => 'Auto Updates', 'value' => $auto_up_core, 'icon'  => 'dashicons-update'],
            'upload' => ['label' => 'Max Upload', 'value' => $style($upload_max, true), 'icon'  => 'dashicons-upload'],
            'uploads_size' => ['label' => 'Uploads Dir Size', 'value' => $style($uploads_dir_size, true), 'icon' => 'dashicons-category'],
            'exec' => ['label' => 'Max Exec', 'value' => $style($max_exec, true), 'icon'  => 'dashicons-clock'],

            // GROUP 5: ASSETS
            'scripts' => ['label' => 'Scripts (On/Reg)', 'value' => $style($script_str, true), 'icon' => 'dashicons-media-code'],
            'styles' => ['label' => 'Styles (On/Reg)', 'value' => $style($style_str, true), 'icon' => 'dashicons-admin-appearance'],

            // GROUP 6: HARDWARE & STACK
            'cpu' => ['label' => 'Processor', 'value' => esc_html($hw['cpu']) . " ({$hw['cores']} cores)", 'icon' => 'dashicons-desktop'],
            'temp' => ['label' => 'Temp', 'value' => $hw['temp'], 'icon' => 'dashicons-editor-video'],
            'disk_space' => ['label' => 'Disk Space', 'value' => $style($disk_usage, true), 'icon' => 'dashicons-media-default'],
            'server' => ['label' => 'Software', 'value' => esc_html($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'), 'icon'  => 'dashicons-cloud'],
            'php' => ['label' => 'PHP', 'value' => $style(phpversion(), true), 'icon' => 'dashicons-editor-code'],
            'mysql' => ['label' => 'MySQL', 'value' => $style($mysql_ver, true), 'icon'  => 'dashicons-networking'],
            'ip_hostname' => ['label' => 'Server Hostname', 'value' => $style(esc_html($host_name), true), 'icon' => 'dashicons-info-outline'],
            'ip_srv' => ['label' => 'Server Host IP', 'value' => $style($host_ip, true), 'icon' => 'dashicons-admin-site'],
            'ips' => ['label' => 'Server IPs Count', 'value' => $style($ip_count . ' Assigned', true), 'icon' => 'dashicons-networking'],
            'ip_user' => ['label' => 'Your IP', 'value' => $style($user_ip, true), 'icon' => 'dashicons-admin-users'],
            'geo' => ['label' => 'Location', 'value' => $style($geo_loc, true), 'icon' => 'dashicons-location'],
            'wp_version' => ['label' => 'WP Version', 'value' => $style($wp_version, true), 'icon' => 'dashicons-wordpress'],
        ];
    }

    /**
     * Get Raw System Metrics (Full Spectrum)
     * Returns unformatted data for JS visualization (Graphs, Clocks, Status Bars).
     * * @return array Full system telemetry in raw JSON-ready format.
     */
    public static function get_raw_metrics(): array
    {
        global $wpdb, $wp_version;

        // 1. TIMING & LOAD
        $start_micro = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $times       = self::get_time_diagnostics(); // Uses internal helper
        $wp_load     = function_exists('timer_stop') ? timer_stop(0, 4) : 0;

        // 2. MEMORY
        $mem_bytes   = memory_get_peak_usage(true);
        $mem_limit   = ini_get('memory_limit');

        // 3. DATABASE (Raw Counts)
        $queries     = get_num_queries();
        $table_count = $wpdb->get_var("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
        $db_size     = $wpdb->get_var("SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");

        // Autoload size check
        $autoload_bytes = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb->options'") === $wpdb->options) {
             $autoload_bytes = (int) $wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM $wpdb->options WHERE autoload = 'yes'");
        }

        // 4. WP INTERNALS (Counts)
        if (!function_exists('get_plugins')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $all_plugins    = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        $all_themes     = wp_get_themes();

        // 5. ASSETS (Real-time Counts)
        $scripts = self::get_assets_data('scripts');
        $styles  = self::get_assets_data('styles');

        // 6. HARDWARE & DISK
        $hw = self::get_server_hardware();
        $disk_total = @disk_total_space(ABSPATH) ?: 0;
        $disk_free  = @disk_free_space(ABSPATH) ?: 0;
        $uploads_dir = wp_upload_dir();

        // 7. ENVIRONMENT
        $debug_mode = (defined('WP_DEBUG') && WP_DEBUG);
        $ssl = is_ssl();

        return [
            // --- TIME ---
            'timestamp'      => time(),
            'server_time'    => $times['server_ts'] ?? time(), // Fallback if helper changes
            'wp_time'        => $times['wp_ts'] ?? current_time('timestamp'),
            'uptime'         => $times['uptime'] ?? 'N/A',
            'load_time_wp'   => (float)$wp_load,
            'load_time_srv'  => (float)number_format(microtime(true) - $start_micro, 4),

            // --- RESOURCES ---
            'memory_bytes'   => $mem_bytes,
            'memory_limit'   => $mem_limit,
            'cpu_model'      => $hw['cpu'] ?? 'Unknown',
            'cpu_cores'      => $hw['cores'] ?? 1,
            'cpu_temp'       => $hw['temp'] ?? 'N/A',
            'disk_total'     => $disk_total,
            'disk_used'      => $disk_total - $disk_free,
            'disk_free'      => $disk_free,

            // --- DATABASE ---
            'db_queries'     => $queries,
            'db_tables'      => (int)$table_count,
            'db_size_bytes'  => (int)$db_size,
            'db_autoload_bytes' => $autoload_bytes,
            'db_version'     => $wpdb->db_version(),

            // --- WORDPRESS ---
            'wp_version'     => $wp_version,
            'wp_debug'       => $debug_mode,
            'is_ssl'         => $ssl,
            'php_version'    => phpversion(),
            'server_software'=> $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',

            // --- COUNTS ---
            'plugins_total'  => count($all_plugins),
            'plugins_active' => count($active_plugins),
            'themes_total'   => count($all_themes),
            'scripts_total'  => $scripts['counts']['registered'],
            'scripts_enqueued' => $scripts['counts']['enqueued'],
            'styles_total'   => $styles['counts']['registered'],
            'styles_enqueued' => $styles['counts']['enqueued'],

            // --- NETWORK ---
            'ip_server'      => gethostbyname($_SERVER['SERVER_NAME'] ?? 'localhost'),
            'ip_user'        => self::get_real_ip(),
            'geo_location'   => self::get_geo_location(),
            'host_name'      => gethostname(),
        ];
    }


    private static function get_time_diagnostics(): array
    {
        global $wpdb;
        $now_server = time();
        $now_wp     = (int) current_time('timestamp');
        $db_time = 'Unknown';
        $latency = 0;
        $uptime_readable = 'N/A';

        if ($wpdb) {
            $start = microtime(true);
            $db_res = $wpdb->get_row("SELECT NOW() as t");
            $end = microtime(true);
            $latency = round(($end - $start) * 1000, 2);
            $db_time = $db_res ? $db_res->t : 'Error';
        }

        if (@is_readable('/proc/uptime')) {
            $u = @file_get_contents('/proc/uptime');
            if ($u) {
                $parts = explode(' ', trim($u));
                $uptime_sec = (int)$parts[0];
                $dtF = new \DateTime('@0');
                $dtT = new \DateTime("@$uptime_sec");
                $uptime_readable = $dtF->diff($dtT)->format('%ad %hh %im');
            }
        }

        return [
            'db_time'   => $db_time,
            'tz_server' => date_default_timezone_get(),
            'tz_wp'     => get_option('timezone_string') ?: 'WP Offset',
            'uptime'    => $uptime_readable,
            'wp_ts'     => $now_wp,
        ];
    }

    private static function get_real_ip(): string
    {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $list = explode(',', $_SERVER[$key]);
                $ip = trim(end($list));
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }

    private static function get_geo_location(): string
    {
        $headers = ['HTTP_CF_IPCOUNTRY', 'GEOIP_COUNTRY_CODE', 'HTTP_X_COUNTRY_CODE', 'HTTP_X_GEOIP_COUNTRY'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return strtoupper(sanitize_text_field($_SERVER[$header]));
            }
        }
        return 'Local/Unknown';
    }

    private static function get_uploads_size(): string
    {
        $uploads_dir = wp_upload_dir()['basedir'] ?? ABSPATH . 'wp-content/uploads';
        if (!is_dir($uploads_dir) || !function_exists('exec') || ini_get('safe_mode')) {
            return 'N/A';
        }
        $size = trim(@exec("du -sh " . escapeshellarg($uploads_dir) . " 2>/dev/null | awk '{print $1}'"));
        return !empty($size) ? esc_html($size) : 'N/A';
    }

    private static function get_disk_usage(): string
    {
        $path = ABSPATH;
        $total = @disk_total_space($path);
        $free  = @disk_free_space($path);
        if ($total === false || $free === false) return 'Unknown';
        $used = $total - $free;
        $percent = $total > 0 ? round(($used / $total) * 100) : 0;
        return size_format((int)$used) . ' / ' . size_format((int)$total) . " ({$percent}%)";
    }

    private static function get_server_hardware(): array
    {
        $cpu = 'Unknown';
        $cores = 0;
        $temp = 'N/A';
        if (@is_readable('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            preg_match_all('/^model name\s+:\s+(.*)$/m', $cpuinfo, $matches);
            if (!empty($matches[1][0])) {
                $cpu = $matches[1][0];
                $cores = count($matches[1]);
            }
        }
        if (@is_readable('/sys/class/thermal/thermal_zone0/temp')) {
            $t = @file_get_contents('/sys/class/thermal/thermal_zone0/temp');
            $temp = round((int)$t / 1000) . '&deg;C';
        }
        return ['cpu' => $cpu, 'cores' => $cores, 'temp' => $temp];
    }

    private static function get_cache_path(): string
    {
        if (defined('WP_CACHE') && WP_CACHE) {
            if (defined('WPFC_CACHE_DIR')) return 'WPFC: ' . WPFC_CACHE_DIR;
            if (defined('WPSC_CACHE_DIR')) return 'WP Super Cache: ' . WPSC_CACHE_DIR;
            if (class_exists('\WpLscIsu') || defined('LSCWP_DIR')) return 'LiteSpeed: .../cache/litespeed';
            if (defined('W3TC')) return 'W3TC: wp-content/cache/';
            return 'Advanced Caching Detected';
        }
        return 'None/Unknown';
    }

    private static function safe_timer_stop(): string
    {
        if (!function_exists('timer_stop')) return '0.000';
        return (string)timer_stop(0, 3);
    }

    private static function get_assets_data(string $type = 'scripts'): array
    {
        $wp_obj = ($type === 'scripts') ? wp_scripts() : wp_styles();
        // Force dependency resolution
        if (!empty($wp_obj->queue)) {
            $wp_obj->all_deps($wp_obj->queue);
        }
        $done = $wp_obj->done ?? [];
        $todo = $wp_obj->to_do ?? [];
        $active_handles = array_unique(array_merge($done, $todo));
        $registered_list = $wp_obj->registered;
        $enqueued_count = 0;
        foreach ($registered_list as $handle => $data) {
             if (in_array($handle, $active_handles, true)) $enqueued_count++;
        }
        return [
            'counts' => [
                'registered' => count($registered_list),
                'enqueued'   => $enqueued_count
            ]
        ];
    }
}
