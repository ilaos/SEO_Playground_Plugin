<?php
/**
 * AlmaSEO Redirects Trash Handler
 *
 * Detects when posts/pages are trashed and prompts the user
 * to create a 301 redirect from the old URL.
 *
 * Works from both:
 *  - Single post edit screen ("Move to Trash" button)
 *  - All Posts/Pages list screen (hover → Trash link)
 *
 * Both paths land on the list screen with ?trashed=1&ids=<post_id>,
 * so one admin_notices hook covers both cases.
 *
 * @package AlmaSEO
 * @subpackage Redirects
 * @since 8.10.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_Redirects_Trash_Handler {

    /**
     * Initialize hooks
     */
    public static function init() {
        // Capture permalink BEFORE the post is trashed
        add_action('wp_trash_post', array(__CLASS__, 'capture_trashed_post'), 10, 1);

        // Show the redirect banner on the next page load
        add_action('admin_notices', array(__CLASS__, 'display_redirect_banner'));

        // AJAX: create the redirect
        add_action('wp_ajax_almaseo_create_trash_redirect', array(__CLASS__, 'ajax_create_redirect'));

        // AJAX: dismiss the banner
        add_action('wp_ajax_almaseo_dismiss_trash_redirect', array(__CLASS__, 'ajax_dismiss_banner'));
    }

    /**
     * Capture post data before it is trashed.
     *
     * Stores the post's permalink, title, and ID in a short-lived transient
     * keyed to the current user so it survives the redirect to the list screen.
     *
     * @param int $post_id
     */
    public static function capture_trashed_post($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        // Only handle public post types (pages, posts, CPTs with public URLs)
        $post_type_obj = get_post_type_object($post->post_type);
        if (!$post_type_obj || !$post_type_obj->public) {
            return;
        }

        // Get the permalink while the post is still published
        $permalink = get_permalink($post_id);
        if (!$permalink) {
            return;
        }

        // Extract the path portion only
        $path = wp_parse_url($permalink, PHP_URL_PATH);
        if (!$path) {
            return;
        }

        $user_id = get_current_user_id();

        // Retrieve any existing trashed posts for this page load (bulk trash)
        $trashed = get_transient('almaseo_trashed_posts_' . $user_id);
        if (!is_array($trashed)) {
            $trashed = array();
        }

        $trashed[$post_id] = array(
            'post_id'    => $post_id,
            'title'      => $post->post_title,
            'path'       => $path,
            'post_type'  => $post->post_type,
            'trashed_at' => current_time('mysql'),
        );

        // Store for 5 minutes — plenty of time for the page redirect
        set_transient('almaseo_trashed_posts_' . $user_id, $trashed, 5 * MINUTE_IN_SECONDS);
    }

    /**
     * Display the redirect creation banner after a post is trashed.
     *
     * Checks for the ?trashed query param (set by WordPress on both
     * single-trash and list-trash actions) and our stored transient.
     */
    public static function display_redirect_banner() {
        // Must have the trashed query param
        if (empty($_GET['trashed'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $user_id = get_current_user_id();
        $trashed = get_transient('almaseo_trashed_posts_' . $user_id);

        if (empty($trashed) || !is_array($trashed)) {
            return;
        }

        // Check if redirects already exist for these paths
        if (!class_exists('AlmaSEO_Redirects_Model')) {
            require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        }

        // Filter out any posts that already have a redirect
        $actionable = array();
        foreach ($trashed as $post_id => $data) {
            $existing = AlmaSEO_Redirects_Model::get_redirect_by_source($data['path']);
            if (!$existing) {
                $actionable[$post_id] = $data;
            }
        }

        if (empty($actionable)) {
            // Nothing to show — clean up transient
            delete_transient('almaseo_trashed_posts_' . $user_id);
            return;
        }

        $nonce = wp_create_nonce('almaseo_trash_redirect');
        $home  = home_url();

        ?>
        <div id="almaseo-trash-redirect-banner" class="notice notice-warning" style="position: relative; padding: 0; border-left: 4px solid #f0ad4e; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin: 15px 0;">
            <div style="display: flex; align-items: flex-start; padding: 16px 20px;">
                <div style="margin-right: 15px; flex-shrink: 0; padding-top: 2px;">
                    <img src="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'almaseo-logo.png'); ?>" alt="AlmaSEO" style="height: 36px; width: auto;">
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 8px 0; color: #856404; font-size: 15px; font-weight: 600;">
                        Protect Your SEO — Create a Redirect
                    </h3>
                    <p style="margin: 0 0 14px 0; font-size: 13px; color: #555; line-height: 1.5;">
                        <?php if (count($actionable) === 1): ?>
                            The page below was just moved to Trash. Any inbound links or bookmarks to it will now return a 404 error. Create a 301 redirect to preserve link equity and send visitors to the right place.
                        <?php else: ?>
                            The pages below were just moved to Trash. Any inbound links or bookmarks to them will now return 404 errors. Create 301 redirects to preserve link equity and send visitors to the right place.
                        <?php endif; ?>
                    </p>

                    <?php foreach ($actionable as $post_id => $data): ?>
                    <div class="almaseo-trash-redirect-row" data-post-id="<?php echo esc_attr($post_id); ?>" style="background: #fefcf5; border: 1px solid #f0e6c8; border-radius: 6px; padding: 12px 16px; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <div style="flex-shrink: 0;">
                                <span style="display: inline-block; background: #856404; color: #fff; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 3px; text-transform: uppercase;">
                                    <?php echo esc_html($data['post_type']); ?>
                                </span>
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <strong style="font-size: 13px; color: #333;"><?php echo esc_html($data['title']); ?></strong>
                                <div style="font-size: 12px; color: #888; margin-top: 2px;">
                                    <?php echo esc_html($home . $data['path']); ?>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                <span style="font-size: 13px; color: #666;">→</span>
                                <input type="text"
                                    class="almaseo-redirect-target"
                                    placeholder="<?php echo esc_attr(home_url('/')); ?>"
                                    value="<?php echo esc_attr(home_url('/')); ?>"
                                    style="width: 280px; padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px;"
                                >
                                <button type="button"
                                    class="button button-primary almaseo-create-redirect-btn"
                                    data-path="<?php echo esc_attr($data['path']); ?>"
                                    data-nonce="<?php echo esc_attr($nonce); ?>"
                                    style="background: #2271b1; border-color: #2271b1; white-space: nowrap;">
                                    Create Redirect
                                </button>
                            </div>
                        </div>
                        <div class="almaseo-redirect-status" style="margin-top: 8px; display: none;"></div>
                    </div>
                    <?php endforeach; ?>

                    <div style="margin-top: 6px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 12px; color: #999;">
                            Redirects are managed in <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-redirects')); ?>">SEO Playground → Redirects</a>
                        </span>
                        <button type="button" class="button-link almaseo-dismiss-trash-banner" data-nonce="<?php echo esc_attr($nonce); ?>" style="font-size: 12px; color: #999; text-decoration: none;">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Create redirect
            $('#almaseo-trash-redirect-banner').on('click', '.almaseo-create-redirect-btn', function() {
                var $btn   = $(this);
                var $row   = $btn.closest('.almaseo-trash-redirect-row');
                var $status = $row.find('.almaseo-redirect-status');
                var source = $btn.data('path');
                var target = $row.find('.almaseo-redirect-target').val().trim();
                var nonce  = $btn.data('nonce');
                var postId = $row.data('post-id');

                if (!target) {
                    $status.html('<span style="color:#dc3232;">Please enter a target URL.</span>').show();
                    return;
                }

                $btn.prop('disabled', true).text('Creating...');

                $.post(ajaxurl, {
                    action: 'almaseo_create_trash_redirect',
                    nonce: nonce,
                    source: source,
                    target: target,
                    post_id: postId
                }, function(response) {
                    if (response.success) {
                        $row.css({ background: '#f0fdf4', borderColor: '#bbf7d0' });
                        $status.html(
                            '<span style="color:#16a34a; font-weight: 500;">&#10003; Redirect created! ' +
                            '<a href="' + response.data.redirects_url + '">View in Redirect Manager</a></span>'
                        ).show();
                        $btn.hide();
                        $row.find('.almaseo-redirect-target').prop('readonly', true)
                            .css({ background: '#f0fdf4', borderColor: '#bbf7d0' });
                    } else {
                        $status.html('<span style="color:#dc3232;">' + (response.data || 'Error creating redirect.') + '</span>').show();
                        $btn.prop('disabled', false).text('Create Redirect');
                    }
                }).fail(function() {
                    $status.html('<span style="color:#dc3232;">Network error. Please try again.</span>').show();
                    $btn.prop('disabled', false).text('Create Redirect');
                });
            });

            // Dismiss banner
            $('#almaseo-trash-redirect-banner').on('click', '.almaseo-dismiss-trash-banner', function() {
                var nonce = $(this).data('nonce');
                $.post(ajaxurl, {
                    action: 'almaseo_dismiss_trash_redirect',
                    nonce: nonce
                });
                $('#almaseo-trash-redirect-banner').fadeOut(300);
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Create a redirect from a trashed post's old URL.
     */
    public static function ajax_create_redirect() {
        check_ajax_referer('almaseo_trash_redirect', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions.');
        }

        $source  = isset($_POST['source']) ? sanitize_text_field(wp_unslash($_POST['source'])) : '';
        $target  = isset($_POST['target']) ? esc_url_raw(wp_unslash($_POST['target'])) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (empty($source) || empty($target)) {
            wp_send_json_error('Source and target are required.');
        }

        if (!class_exists('AlmaSEO_Redirects_Model')) {
            require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        }

        // Check if redirect already exists
        $existing = AlmaSEO_Redirects_Model::get_redirect_by_source($source);
        if ($existing) {
            wp_send_json_error('A redirect for this URL already exists.');
        }

        $redirect_id = AlmaSEO_Redirects_Model::create_redirect(array(
            'source'     => $source,
            'target'     => $target,
            'status'     => 301,
            'is_enabled' => 1,
        ));

        if (!$redirect_id) {
            wp_send_json_error('Failed to create redirect. Please check the source and target URLs.');
        }

        // Remove this post from the transient
        $user_id = get_current_user_id();
        $trashed = get_transient('almaseo_trashed_posts_' . $user_id);
        if (is_array($trashed) && isset($trashed[$post_id])) {
            unset($trashed[$post_id]);
            if (empty($trashed)) {
                delete_transient('almaseo_trashed_posts_' . $user_id);
            } else {
                set_transient('almaseo_trashed_posts_' . $user_id, $trashed, 5 * MINUTE_IN_SECONDS);
            }
        }

        wp_send_json_success(array(
            'redirect_id'   => $redirect_id,
            'redirects_url' => admin_url('admin.php?page=almaseo-redirects'),
            'message'       => 'Redirect created successfully.',
        ));
    }

    /**
     * AJAX: Dismiss the trash redirect banner (clears the transient).
     */
    public static function ajax_dismiss_banner() {
        check_ajax_referer('almaseo_trash_redirect', 'nonce');

        $user_id = get_current_user_id();
        delete_transient('almaseo_trashed_posts_' . $user_id);

        wp_send_json_success();
    }
}
