<?php

/**
 * SystemDeck – Dashboard Tunnel (Universal Drop-In)
 *
 * A clean-room iframe environment that can render ANY WordPress
 * dashboard widget (PHP or React-based) without admin chrome,
 * padding artifacts, or lifecycle breakage.
 */

declare(strict_types=1);

namespace SystemDeck\Modules;

if (!defined('ABSPATH')) {
    exit;
}

final class DashboardTunnel
{
    /* =========================================================
     * BOOTSTRAP
     * ======================================================= */

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'register_page'], 999);
        add_action('admin_init', [self::class, 'prepare_context'], 1);
        // Added: Server-side body class filter for guaranteed Context
        add_filter('admin_body_class', [self::class, 'force_dashboard_classes']);
        add_action('admin_enqueue_scripts', [self::class, 'asset_firewall'], 9999);
    }

    /* =========================================================
     * PAGE REGISTRATION
     * ======================================================= */

    public static function register_page(): void
    {
        add_submenu_page(
            'options.php',
            'SystemDeck Widget Tunnel',
            'SystemDeck Widget Tunnel',
            'manage_options',
            'sd-dashboard-tunnel',
            [self::class, 'render']
        );
    }

    /* =========================================================
     * DASHBOARD CONTEXT (NON-NEGOTIABLE)
     * ======================================================= */

    public static function force_dashboard_classes($classes)
    {
        if (($_GET['page'] ?? '') !== 'sd-dashboard-tunnel') {
            return $classes;
        }
        return "$classes index-php dashboard wp-core-ui";
    }

    public static function prepare_context(): void
    {
        if (($_GET['page'] ?? '') !== 'sd-dashboard-tunnel') {
            return;
        }

        if (!defined('IFRAME_REQUEST')) {
            define('IFRAME_REQUEST', true);
        }

        global $pagenow, $typenow, $title, $current_screen;

        $pagenow = 'index.php';
        $typenow = '';
        $title   = 'Dashboard';

        if (function_exists('set_current_screen')) {
            set_current_screen('dashboard');
        }

        if (is_object($current_screen)) {
            $current_screen->id   = 'dashboard';
            $current_screen->base = 'dashboard';
        }

        // 🔑 Required for React dashboards to register stores & widgets
        do_action('load-index.php');
    }

    /* =========================================================
     * ASSET FIREWALL + CLEAN ROOM + REACT SHIM
     * ======================================================= */

    public static function asset_firewall(): void
    {
        if (($_GET['page'] ?? '') !== 'sd-dashboard-tunnel') {
            return;
        }

        // Prevent SystemDeck recursion
        $sd_scripts = ['sd-deck-js', 'sd-workspace-react', 'sd-system-js', 'sd-scanner-js', 'sd-toolbox-toggle-js'];
        foreach ($sd_scripts as $handle) {
            wp_deregister_script($handle);
            wp_dequeue_script($handle);
        }

        $sd_styles = ['sd-core', 'sd-common', 'sd-grid', 'sd-screen-meta', 'sd-wpcolors'];
        foreach ($sd_styles as $handle) {
            wp_deregister_style($handle);
            wp_dequeue_style($handle);
        }

        // Kill admin chrome
        wp_deregister_script('admin-bar');
        wp_dequeue_script('admin-bar');
        wp_deregister_style('admin-bar');
        wp_dequeue_style('admin-bar');
        remove_action('wp_head', '_admin_bar_bump_cb');

        // Ensure React/API Core is present for the Shim
        wp_enqueue_script('wp-element');
        wp_enqueue_script('wp-api-fetch');
        wp_enqueue_script('wp-data');
        wp_enqueue_script('jquery');

        // TARGETED ASSETS (If load-index.php misses them)
        $widget_id = sanitize_text_field($_GET['widget'] ?? '');
        if (strpos($widget_id, 'wpseo') !== false) {
            wp_enqueue_script('yoast-seo-admin-global');
            wp_enqueue_script('yoast-seo-dashboard-widget');
            wp_enqueue_script('yoast-seo-wincher-dashboard-widget');
            wp_enqueue_style('yoast-seo-admin-global');
            wp_enqueue_style('yoast-seo-wp-dashboard');
        }

        // Required globals & Shim (Yoast, AIOSEO, Jetpack, Woo)
        add_action('admin_head', static function () {
            $rest_nonce = wp_create_nonce('wp_rest');
            $api_root = esc_url_raw(rest_url());
            $user_id = get_current_user_id();
        ?>
            <script>
                // 1. GLOBAL ENVIRONMENT SHIM
                window.ajaxurl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";

                // Explicitly set nonce on the global that legacy widgets check
                window.wpApiSettings = {
                    root: "<?php echo $api_root; ?>",
                    nonce: "<?php echo $rest_nonce; ?>"
                };

                // Polyfill userSettings
                window.userSettings = { "url": "/", "uid": "<?php echo $user_id; ?>", "time": "<?php echo time(); ?>" };

                // 2. DATA FALLBACKS (Prevents "Undefined" Crashes)
                if (typeof wpseoDashboardWidgetL10n === 'undefined') {
                    window.wpseoDashboardWidgetL10n = { "feed_header": "Latest", "feed_footer": "", "wp_version": "6.0", "php_version": "8.0" };
                }
                if (typeof wpseoAdminGlobalL10n === 'undefined') {
                    window.wpseoAdminGlobalL10n = { "isRtl": "0", "wincher_is_logged_in": "0" };
                }
                if (typeof wpseoWincherDashboardWidgetL10n === 'undefined') {
                    window.wpseoWincherDashboardWidgetL10n = { "wincher_is_logged_in": "0", "wincher_website_id": "" };
                }

                // 3. UNIVERSAL REACT LIFECYCLE BRIDGE (Enhanced)
                (function() {
                    var reactReady = false;
                    var debug = true; // Set to false to disable logging

                    function log(msg) {
                        if (debug) console.log('[SD Tunnel] ' + msg);
                    }

                    // 1. Setup API Fetch immediately
                    function setupApiFetch() {
                        if (window.wp && window.wp.apiFetch) {
                            try {
                                window.wp.apiFetch.use(window.wp.apiFetch.createRootURLMiddleware("<?php echo $api_root; ?>"));
                                window.wp.apiFetch.use(window.wp.apiFetch.createNonceMiddleware("<?php echo $rest_nonce; ?>"));
                                log('API Fetch configured');
                                return true;
                            } catch(e) {
                                log('API Fetch setup failed: ' + e.message);
                                return false;
                            }
                        }
                        return false;
                    }

                    // 2. Initialize WordPress data stores
                    function initDataStores() {
                        if (!window.wp || !window.wp.data) {
                            log('wp.data not available yet');
                            return false;
                        }

                        try {
                            var store = wp.data.select('core');
                            var dispatch = wp.data.dispatch('core');

                            // Get or set current user
                            var user = store.getCurrentUser();
                            if (user && user.id) {
                                log('User already loaded: ' + user.id);
                            } else {
                                // Inject user data
                                dispatch.receiveCurrentUser({
                                    id: <?php echo $user_id; ?>,
                                    name: '<?php echo esc_js(wp_get_current_user()->display_name); ?>'
                                });
                                log('User data injected');
                            }

                            // Set permissions
                            dispatch.receiveUserPermission('read', true);
                            dispatch.receiveUserPermission('edit', true);

                            return true;
                        } catch(e) {
                            log('Data store init failed: ' + e.message);
                            return false;
                        }
                    }

                    // 3. Trigger React mount
                    function triggerReactMount() {
                        if (reactReady) return;

                        log('Triggering React mount...');

                        // Dispatch readiness events
                        window.dispatchEvent(new Event('wp-dashboard-ready'));
                        window.dispatchEvent(new Event('yoast:ready'));

                        // Trigger jQuery ready for legacy widgets
                        if (window.jQuery) {
                            try {
                                jQuery(document).trigger('ready');
                            } catch(e) {}
                        }

                        reactReady = true;
                        log('React mount complete');
                    }

                    // 4. Main initialization
                    function initialize() {
                        log('Initializing...');

                        // Setup API
                        setupApiFetch();

                        // Init data stores
                        initDataStores();

                        // Trigger mount
                        setTimeout(triggerReactMount, 100);
                    }

                    // 5. Watch for DOM changes and React mount points
                    var observer = new MutationObserver(function(mutations) {
                        // Try to init data stores on any DOM change
                        if (!reactReady && window.wp && window.wp.data) {
                            initDataStores();
                        }

                        // Look for React mount points
                        mutations.forEach(function(mutation) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1 && node.id) {
                                    var id = node.id.toLowerCase();
                                    if (id.indexOf('dashboard') !== -1 ||
                                        id.indexOf('widget') !== -1 ||
                                        id.indexOf('seo') !== -1) {
                                        log('Mount point detected: ' + node.id);
                                        if (!reactReady) {
                                            setTimeout(initialize, 50);
                                        }
                                    }
                                }
                            });
                        });
                    });

                    // 6. Start observing
                    function startObserver() {
                        if (document.body) {
                            observer.observe(document.body, { childList: true, subtree: true });
                            log('Observer started');
                        }
                    }

                    // 7. Initialize on DOM ready
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function() {
                            log('DOM Ready');
                            startObserver();
                            initialize();
                        });
                    } else {
                        log('DOM already ready');
                        startObserver();
                        initialize();
                    }

                    // 8. Fallback: Force init after window load
                    window.addEventListener('load', function() {
                        log('Window loaded');
                        if (!reactReady) {
                            setTimeout(initialize, 200);
                        }
                    });

                    // 9. Final fallback after 2 seconds
                    setTimeout(function() {
                        if (!reactReady) {
                            log('Fallback initialization');
                            initialize();
                        }
                    }, 2000);
                })();
            </script>

            <style>
/* =========================================================
   SYSTEMDECK DASHBOARD TUNNEL – FULL WP ADMIN STYLE FIX
   ========================================================= */

/* 1. FULL CLEAN ROOM */
html, body {
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
    height: auto !important;
    overflow: hidden !important;
}

/* 2. REMOVE ALL DASHBOARD SPACING */
#wpcontent, #wpbody, #wpbody-content, .wrap, #wpwrap, .metabox-holder,
.postbox-container, .postbox, .inside {
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    text-align: left !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 auto !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans,
    Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
    line-height: 1.4em !important;
    font-size: 13px !important;

}

/* 3. KILL ADMIN UI */
#wpadminbar, #adminmenuwrap, #adminmenuback, #wpfooter,
.notice, .update-nag, .handlediv, .hndle {
    display: none !important;
}

/* 4. TUNNEL CONTENT ROOT */
.sd-tunnel-content {
    width: 100%;
    box-sizing: border-box;
    padding: 0;
    margin: 0 auto;
}

/* 5. WIDGET PADDING FIX - Remove double padding */
.sd-tunnel-content .postbox {
    margin: 0;
    border: 0;
    box-shadow: none;
    background: transparent;
}

.sd-tunnel-content .postbox .inside {
    padding: 12px;
    margin: 0;
}


/* 6. YOAST SPECIFIC FIXES *
#yoast-seo-dashboard-widget::before {
    display: none !important; /* Hide spinning loader *
}
*/

/* 6. CLUSTER HELPER HIDE (For complex widgets) */
.sd-cluster-helper {
    display: none !important;
}

/* 7. TYPOGRAPHY & LINKS */
body {

    color: #3c434a;
}
a {
    color: #2271b1;
    text-decoration: none;
}
a:hover {
    color: #135e96;
}

/* 8. COMMON DASHBOARD LISTS */
ul {
    margin: 0;
    padding: 0;
    list-style: none;
}
.inside ul li {
    margin-bottom: 6px;
    border-bottom: 1px solid #f0f0f1;
    padding-bottom: 6px;
}
.inside ul li:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

/* 9. WP TABLES */
table.widefat {
    border-spacing: 0;
    width: 100%;
    clear: both;
    margin: 0;
    border: none;
    box-shadow: none;
}
table.widefat td,
table.widefat th {
    padding: 8px 10px;
    text-align: left;
    vertical-align: top;
}

/* 10. ACTIVITY WIDGET */
#dashboard_activity .sub {
    color: #646970;
    font-size: 12px;
}

/* 11. RIGHT NOW WIDGET GRID */
.main {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

/* 12. INLINE IFRAAMES & PROXY WIDGETS */
.sd-widget-proxy iframe {
    width: 100%;
    height: auto;
    border: none;
    display: block;
    box-sizing: border-box;
    background: #fff;
    padding: 12px;
}

/* 13. OPTIONAL – FIX FOR REACT OR THIRD-PARTY WIDGETS */
.yoast-container,
.wpseo-dashboard-widget,
.sd-react-widget {
    max-width: 100% !important;
    box-sizing: border-box;
}
</style>
<?php
        }, 1);

        // Universal iframe auto-resize (content-aware)
        wp_add_inline_script('common', "
            (function () {
                var target = document.querySelector('.sd-tunnel-content') || document.body;
                if (!target || !window.ResizeObserver) return;
                var ro = new ResizeObserver(function () {
                    try {
                        if (window.frameElement) {
                            window.frameElement.style.height = target.scrollHeight + 'px';
                        }
                    } catch(e){}
                });
                ro.observe(target);
            })();
        ");

        // Force all links to open in parent window (not in iframe)
        wp_add_inline_script('common', "
            (function () {
                // Set target on existing links
                function setLinkTargets() {
                    document.querySelectorAll('a').forEach(function(link) {
                        // Skip anchors (same-page links)
                        var href = link.getAttribute('href');
                        if (!href || href.charAt(0) === '#') return;

                        // Force open in parent window
                        link.setAttribute('target', '_top');
                    });
                }

                // Run on load
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', setLinkTargets);
                } else {
                    setLinkTargets();
                }

                // Watch for dynamically added links (React widgets)
                if (window.MutationObserver) {
                    var observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) { // Element node
                                    if (node.tagName === 'A') {
                                        var href = node.getAttribute('href');
                                        if (href && href.charAt(0) !== '#') {
                                            node.setAttribute('target', '_top');
                                        }
                                    }
                                    // Check children
                                    node.querySelectorAll && node.querySelectorAll('a').forEach(function(link) {
                                        var href = link.getAttribute('href');
                                        if (href && href.charAt(0) !== '#') {
                                            link.setAttribute('target', '_top');
                                        }
                                    });
                                }
                            });
                        });
                    });
                    observer.observe(document.body, { childList: true, subtree: true });
                }
            })();
        ");
    }

    /* =========================================================
     * RENDER ANY DASHBOARD WIDGET
     * ======================================================= */

    public static function render(): void
    {
        $widget_id = sanitize_text_field($_GET['widget'] ?? '');
        $nonce     = $_GET['nonce'] ?? '';

        // --- FIX: NONCE FLEXIBILITY ---
        // Allow Specific Tunnel Nonce OR Global Shell Nonce (for JS hydration)
        $valid_specific = wp_verify_nonce($nonce, 'sd_tunnel_' . $widget_id);
        $valid_global   = wp_verify_nonce($nonce, 'sd_load_shell');

        if (!$widget_id || (!$valid_specific && !$valid_global)) {
            wp_die('Invalid widget request');
        }

        if (!function_exists('wp_dashboard_setup')) {
            require_once ABSPATH . 'wp-admin/includes/dashboard.php';
        }

        wp_dashboard_setup();

        // --- ID IMPERSONATION ---
        // Wrapper must match Widget ID for React mounting
        echo '<div class="sd-tunnel-content">';
        echo '<div id="' . esc_attr($widget_id) . '" class="postbox ' . esc_attr($widget_id) . '">';
        echo '<div class="inside">';

        if (!self::render_widget($widget_id)) {
            echo '<div style="padding:16px;text-align:center;color:#777">';
            echo esc_html__('Widget unavailable', 'systemdeck');
            echo '</div>';
        }

        echo '</div></div></div>'; // Close inside, postbox, tunnel
    }

    private static function render_widget(string $widget_id): bool
    {
        global $wp_meta_boxes;
        $rendered = false;

        // 1. MAIN RENDER
        foreach ((array) $wp_meta_boxes as $contexts) {
            foreach ((array) $contexts as $priorities) {
                foreach ((array) $priorities as $widgets) {
                    if (!isset($widgets[$widget_id])) continue;

                    $callback = $widgets[$widget_id]['callback'] ?? null;
                    $args     = $widgets[$widget_id]['args'] ?? [];

                    ob_start();
                    if (is_callable($callback)) {
                        call_user_func($callback, null, ['id' => $widget_id, 'args' => $args]);
                        $rendered = true;
                    }
                    $output = ob_get_clean();

                    // UNIVERSAL REACT MOUNT POINT DETECTION
                    // Check if output is empty or minimal (likely React widget)
                    $trimmed = trim(strip_tags($output));
                    $is_likely_react = empty($trimmed) || strlen($trimmed) < 50;

                    // Create mount point if needed
                    if ($is_likely_react) {
                        // Generate a mount point ID based on widget ID
                        $mount_id = $widget_id;

                        // Check if output already has this ID
                        if (strpos($output, 'id="' . $mount_id . '"') === false) {
                            // Add mount point div
                            echo '<div id="' . esc_attr($mount_id) . '" class="react-mount-point"></div>';
                        }
                    }

                    // Output the widget content
                    echo $output;

                    // Break out of loops once found
                    break 3;
                }
            }
        }

        // 2. CLUSTER RENDER (Siblings for Complex Widgets)
        // This helps widgets that depend on multiple dashboard widgets being present
        if ($rendered) {
            // Check if this is a plugin that uses multiple widgets
            $cluster_prefixes = ['wpseo', 'aioseo', 'jetpack'];
            $needs_cluster = false;

            foreach ($cluster_prefixes as $prefix) {
                if (strpos($widget_id, $prefix) === 0) {
                    $needs_cluster = true;
                    break;
                }
            }

            if ($needs_cluster) {
                foreach ((array) $wp_meta_boxes as $contexts) {
                    foreach ((array) $contexts as $priorities) {
                        foreach ((array) $priorities as $id => $box) {
                            // Render sibling widgets from same plugin
                            if ($id !== $widget_id && strpos($id, $prefix) === 0) {
                                $callback = $box['callback'] ?? null;
                                $args     = $box['args'] ?? [];
                                if (is_callable($callback)) {
                                    echo '<div class="sd-cluster-helper" style="display:none;">';
                                    call_user_func($callback, null, ['id' => $id, 'args' => $args]);
                                    echo '</div>';
                                }
                            }
                        }
                    }
                }
            }
        }

        return $rendered;
    }

    /* =========================================================
     * PUBLIC HELPER – IFRAME SHELL
     * ======================================================= */

    public static function iframe(string $widget_id): void
    {
        $url = add_query_arg([
            'page'   => 'sd-dashboard-tunnel',
            'widget' => $widget_id,
            'nonce'  => wp_create_nonce('sd_tunnel_' . $widget_id),
        ], admin_url('admin.php'));

        echo '<div class="sd-widget-proxy" style="width:100%;">';
        echo '<iframe
            src="' . esc_url($url) . '"
            frameborder="0"
            scrolling="no"
            loading="lazy"
            style="
                width:100%;
                min-height:12px;
                height: auto !important;
                border:0;
                background:transparent;
                overflow:hidden;
                margin: 0 auto;
            "
        ></iframe>';
        echo '</div>';
    }
}