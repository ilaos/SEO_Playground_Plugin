<?php
/**
 * AlmaSEO Reload Debug Tracer
 *
 * DROP THIS FILE into wp-content/mu-plugins/ to trace the infinite reload bug.
 * It logs all redirects, output, and errors on post.php/post-new.php.
 *
 * REMOVE THIS FILE after debugging is complete.
 *
 * Logs are written to: wp-content/almaseo-reload-debug.log
 */

if (!defined('ABSPATH')) exit;

// Only activate on admin post edit screens
if (!is_admin()) return;

$log_file = WP_CONTENT_DIR . '/almaseo-reload-debug.log';

/**
 * Log helper
 */
function almaseo_debug_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $request_id = substr(md5(uniqid()), 0, 8);

    if (!isset($GLOBALS['almaseo_debug_request_id'])) {
        $GLOBALS['almaseo_debug_request_id'] = $request_id;
        // Log request start
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown';
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'unknown';
        file_put_contents($log_file, "\n[{$timestamp}] === NEW REQUEST [{$request_id}] {$method} {$uri} ===\n", FILE_APPEND);
    }

    $rid = $GLOBALS['almaseo_debug_request_id'];
    file_put_contents($log_file, "[{$timestamp}] [{$rid}] {$message}\n", FILE_APPEND);
}

/**
 * 1. Intercept ALL wp_redirect calls
 */
add_filter('wp_redirect', function($location, $status) {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
    $callers = array();
    foreach ($trace as $frame) {
        if (isset($frame['file']) && isset($frame['line'])) {
            $file = basename($frame['file']);
            $callers[] = "{$file}:{$frame['line']}";
        }
    }
    $caller_str = implode(' → ', array_slice($callers, 0, 5));
    almaseo_debug_log("wp_redirect → {$location} (status {$status}) | Called from: {$caller_str}");
    return $location;
}, 1, 2);

/**
 * 2. Log if headers have already been sent
 */
add_action('admin_init', function() {
    if (headers_sent($file, $line)) {
        almaseo_debug_log("WARNING: Headers already sent in {$file}:{$line}");
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen) {
        almaseo_debug_log("Screen: {$screen->id} | post_type: {$screen->post_type}");
    }
}, 999);

/**
 * 3. Log PHP errors/warnings on post edit screens
 */
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (strpos($uri, 'post.php') !== false || strpos($uri, 'post-new.php') !== false) {
        $type = $errno === E_WARNING ? 'WARNING' : ($errno === E_NOTICE ? 'NOTICE' : "ERROR({$errno})");
        $file = basename($errfile);
        almaseo_debug_log("PHP {$type}: {$errstr} in {$file}:{$errline}");
    }
    return false; // Let PHP's default handler also run
}, E_ALL);

/**
 * 4. Check for output buffering issues
 */
add_action('admin_init', function() {
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (strpos($uri, 'post.php') === false && strpos($uri, 'post-new.php') === false) {
        return;
    }

    $ob_level = ob_get_level();
    $ob_length = ob_get_length();
    almaseo_debug_log("Output buffer: level={$ob_level}, length=" . ($ob_length !== false ? $ob_length : 'N/A'));
}, 1);

/**
 * 5. Log all enqueued scripts on post edit screens
 */
add_action('admin_print_footer_scripts', function() {
    global $wp_scripts;
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (strpos($uri, 'post.php') === false && strpos($uri, 'post-new.php') === false) {
        return;
    }

    $almaseo_scripts = array();
    foreach ($wp_scripts->done as $handle) {
        if (stripos($handle, 'almaseo-seo-playground') !== false || stripos($handle, 'alma') !== false) {
            $almaseo_scripts[] = $handle;
        }
    }
    almaseo_debug_log("AlmaSEO scripts loaded: " . implode(', ', $almaseo_scripts));
}, 999);

/**
 * 6. Inject client-side reload detection script
 */
add_action('admin_footer', function() {
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (strpos($uri, 'post.php') === false && strpos($uri, 'post-new.php') === false) {
        return;
    }
    ?>
    <script>
    (function() {
        'use strict';
        var debugKey = 'almaseo_reload_debug';
        var loadCount = parseInt(sessionStorage.getItem(debugKey) || '0') + 1;
        sessionStorage.setItem(debugKey, loadCount);

        console.log('[AlmaSEO Debug] Page load #' + loadCount + ' at ' + new Date().toISOString());

        var shouldBlock = loadCount > 2;
        if (shouldBlock) {
            console.error('[AlmaSEO Debug] RELOAD LOOP DETECTED! Load count: ' + loadCount + '. Will BLOCK form submissions to catch the culprit.');
        }

        // CRITICAL: Intercept ALL form submissions (including programmatic .submit() calls)
        var origSubmit = HTMLFormElement.prototype.submit;
        HTMLFormElement.prototype.submit = function() {
            var formId = this.id || this.name || 'unnamed';
            var formAction = this.action || 'default';
            console.error('[AlmaSEO Debug] FORM.SUBMIT() called on form: #' + formId + ' action: ' + formAction);
            console.trace('[AlmaSEO Debug] Form submit stack trace:');

            // Log form data keys
            var formData = new FormData(this);
            var keys = [];
            formData.forEach(function(val, key) { keys.push(key); });
            console.log('[AlmaSEO Debug] Form fields:', keys.join(', '));

            if (shouldBlock) {
                console.error('[AlmaSEO Debug] >>> BLOCKED form submission to break the loop! Check the stack trace above. <<<');
                sessionStorage.removeItem(debugKey);
                return; // DON'T submit
            }
            origSubmit.call(this);
        };

        // Also catch submit events (from button clicks / requestSubmit)
        document.addEventListener('submit', function(e) {
            var formId = e.target.id || e.target.name || 'unnamed';
            console.error('[AlmaSEO Debug] SUBMIT EVENT on form: #' + formId);
            console.trace('[AlmaSEO Debug] Submit event stack trace:');
        }, true); // capture phase to catch early

        // Intercept click on #publish and #save-post buttons
        document.addEventListener('click', function(e) {
            var el = e.target;
            if (el.id === 'publish' || el.id === 'save-post' || (el.type === 'submit' && el.closest('#post'))) {
                console.error('[AlmaSEO Debug] SAVE BUTTON CLICKED: #' + el.id);
                console.trace('[AlmaSEO Debug] Save button click trace:');
            }
        }, true);

        // Monitor for meta refresh tags
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                m.addedNodes.forEach(function(node) {
                    if (node.tagName === 'META' && node.httpEquiv && node.httpEquiv.toLowerCase() === 'refresh') {
                        console.error('[AlmaSEO Debug] META REFRESH detected:', node.content);
                    }
                });
            });
        });
        observer.observe(document.head || document.documentElement, { childList: true, subtree: true });

        // Log navigation events
        window.addEventListener('beforeunload', function(e) {
            console.error('[AlmaSEO Debug] beforeunload fired at ' + new Date().toISOString());
            console.trace('[AlmaSEO Debug] beforeunload trace:');
        });

        // Monitor for form submissions that might cause reload
        document.addEventListener('submit', function(e) {
            console.log('[AlmaSEO Debug] Form submitted:', e.target.id || e.target.action);
        });

        // Monitor WordPress heartbeat
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('heartbeat-tick', function(e, data) {
                console.log('[AlmaSEO Debug] Heartbeat tick received');
            });
            jQuery(document).on('heartbeat-error', function(e, jqXHR, textStatus, error) {
                console.error('[AlmaSEO Debug] Heartbeat error:', textStatus, error);
            });
        }

        // Clear counter after 10 seconds of no reload (page is stable)
        setTimeout(function() {
            sessionStorage.removeItem(debugKey);
            console.log('[AlmaSEO Debug] Page stable for 10s, counter cleared');
        }, 10000);
    })();
    </script>
    <?php
}, 999);

/**
 * 7. Log save_post hook activity
 */
add_action('save_post', function($post_id, $post, $update) {
    $is_autosave = defined('DOING_AUTOSAVE') && DOING_AUTOSAVE;
    $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
    $is_revision = wp_is_post_revision($post_id);

    almaseo_debug_log("save_post fired: post_id={$post_id}, type={$post->post_type}, " .
        "update=" . ($update ? 'yes' : 'no') . ", " .
        "autosave=" . ($is_autosave ? 'yes' : 'no') . ", " .
        "ajax=" . ($is_ajax ? 'yes' : 'no') . ", " .
        "revision=" . ($is_revision ? 'yes' : 'no'));
}, 1, 3);

almaseo_debug_log("Debug tracer loaded");
