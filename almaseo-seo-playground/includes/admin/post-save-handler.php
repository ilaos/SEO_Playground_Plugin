<?php
/**
 * AlmaSEO Post Save Handler - Saves SEO metadata when posts are saved
 */

if (!defined('ABSPATH')) exit;

// Save SEO Playground data
function almaseo_save_seo_playground_meta($post_id) {
    // Check if nonce is valid
    if (!isset($_POST['almaseo_seo_playground_nonce']) ||
        !wp_verify_nonce($_POST['almaseo_seo_playground_nonce'], 'almaseo_seo_playground_nonce')) {
        return;
    }

    // Check if user has permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Check if not an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Skip revisions
    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Get old values for history tracking - check canonical keys first
    $old_title = get_post_meta($post_id, '_almaseo_title', true);
    if (empty($old_title)) {
        $old_title = get_post_meta($post_id, '_seo_playground_title', true);
    }

    $old_description = get_post_meta($post_id, '_almaseo_description', true);
    if (empty($old_description)) {
        $old_description = get_post_meta($post_id, '_seo_playground_description', true);
    }

    $old_keyword = get_post_meta($post_id, '_almaseo_focus_keyword', true);
    if (empty($old_keyword)) {
        $old_keyword = get_post_meta($post_id, '_seo_playground_focus_keyword', true);
    }

    // Get new values using dedicated sanitizers from security.php
    $new_title = isset($_POST['almaseo_seo_title']) ? almaseo_sanitize_seo_title($_POST['almaseo_seo_title']) : '';
    $new_description = isset($_POST['almaseo_seo_description']) ? almaseo_sanitize_meta_description($_POST['almaseo_seo_description']) : '';
    $new_keyword = isset($_POST['almaseo_focus_keyword']) ? sanitize_text_field($_POST['almaseo_focus_keyword']) : '';

    // Track metadata changes in history
    $has_changes = false;
    if ($old_title != $new_title || $old_description != $new_description || $old_keyword != $new_keyword) {
        $has_changes = true;

        // Get existing history
        $metadata_history = get_post_meta($post_id, '_almaseo_metadata_history', true);
        if (!is_array($metadata_history)) {
            $metadata_history = [];
        }

        // Add new history entry
        $current_user = wp_get_current_user();
        $history_entry = [
            'timestamp' => current_time('U'),
            'title' => $new_title,
            'description' => $new_description,
            'keyword' => $new_keyword,
            'user' => $current_user->display_name
        ];

        // Add to beginning of array (newest first)
        array_unshift($metadata_history, $history_entry);

        // Limit to 20 entries
        if (count($metadata_history) > 20) {
            $metadata_history = array_slice($metadata_history, 0, 20);
        }

        // Save history
        update_post_meta($post_id, '_almaseo_metadata_history', $metadata_history);
    }

    // Save to canonical meta keys
    update_post_meta($post_id, '_almaseo_title', $new_title);
    update_post_meta($post_id, '_almaseo_description', $new_description);
    update_post_meta($post_id, '_almaseo_focus_keyword', $new_keyword);

    // Delete legacy keys if they exist (migration complete)
    delete_post_meta($post_id, '_seo_playground_title');
    delete_post_meta($post_id, '_seo_playground_description');
    delete_post_meta($post_id, '_seo_playground_focus_keyword');

    // Save sticky notes
    if (isset($_POST['almaseo_seo_notes'])) {
        update_post_meta($post_id, '_seo_playground_notes', almaseo_sanitize_notes($_POST['almaseo_seo_notes']));
    }

    // Cornerstone Content
    if ( isset( $_POST['almaseo_is_cornerstone'] ) ) {
        update_post_meta( $post_id, '_almaseo_is_cornerstone', 1 );
    } else {
        delete_post_meta( $post_id, '_almaseo_is_cornerstone' );
    }

    // Save Schema & Meta tab fields

    // Meta Robots settings
    $robots_index = isset($_POST['almaseo_robots_index']) ? 'index' : 'noindex';
    $robots_follow = isset($_POST['almaseo_robots_follow']) ? 'follow' : 'nofollow';
    $robots_archive = isset($_POST['almaseo_robots_archive']) ? 'archive' : 'noarchive';
    $robots_snippet = isset($_POST['almaseo_robots_snippet']) ? 'snippet' : 'nosnippet';
    $robots_imageindex = isset($_POST['almaseo_robots_imageindex']) ? 'imageindex' : 'noimageindex';
    $robots_translate = isset($_POST['almaseo_robots_translate']) ? 'translate' : 'notranslate';

    update_post_meta($post_id, '_almaseo_robots_index', $robots_index);
    update_post_meta($post_id, '_almaseo_robots_follow', $robots_follow);
    update_post_meta($post_id, '_almaseo_robots_archive', $robots_archive);
    update_post_meta($post_id, '_almaseo_robots_snippet', $robots_snippet);
    update_post_meta($post_id, '_almaseo_robots_imageindex', $robots_imageindex);
    update_post_meta($post_id, '_almaseo_robots_translate', $robots_translate);

    // Canonical URL
    if (isset($_POST['almaseo_canonical_url'])) {
        update_post_meta($post_id, '_almaseo_canonical_url', esc_url_raw($_POST['almaseo_canonical_url']));
    }

    // Schema Type
    if (isset($_POST['almaseo_schema_type'])) {
        $schema_type = almaseo_sanitize_schema_type($_POST['almaseo_schema_type']);
        update_post_meta($post_id, '_almaseo_schema_type', $schema_type);
        update_post_meta($post_id, '_seo_playground_schema_type', $schema_type); // Legacy
    }

    // Advanced Schema - Primary Type (Pro)
    if (isset($_POST['almaseo_schema_primary_type'])) {
        update_post_meta($post_id, '_almaseo_schema_primary_type', sanitize_text_field($_POST['almaseo_schema_primary_type']));
    }

    // Advanced Schema - FAQPage toggle (Pro)
    if (isset($_POST['almaseo_schema_is_faqpage'])) {
        update_post_meta($post_id, '_almaseo_schema_is_faqpage', true);
    } else {
        update_post_meta($post_id, '_almaseo_schema_is_faqpage', false);
    }

    // Advanced Schema - HowTo toggle (Pro)
    if (isset($_POST['almaseo_schema_is_howto'])) {
        update_post_meta($post_id, '_almaseo_schema_is_howto', true);
    } else {
        update_post_meta($post_id, '_almaseo_schema_is_howto', false);
    }

    // Advanced Schema - Disable toggle (Pro)
    if (isset($_POST['almaseo_schema_disable'])) {
        update_post_meta($post_id, '_almaseo_schema_disable', true);
    } else {
        update_post_meta($post_id, '_almaseo_schema_disable', false);
    }

    // Article Author (for Article schema)
    if (isset($_POST['almaseo_article_author'])) {
        update_post_meta($post_id, '_almaseo_article_author', sanitize_text_field($_POST['almaseo_article_author']));
    }

    // Open Graph metadata
    if (isset($_POST['almaseo_og_title'])) {
        update_post_meta($post_id, '_almaseo_og_title', sanitize_text_field($_POST['almaseo_og_title']));
    }
    if (isset($_POST['almaseo_og_description'])) {
        update_post_meta($post_id, '_almaseo_og_description', sanitize_textarea_field($_POST['almaseo_og_description']));
    }
    if (isset($_POST['almaseo_og_image'])) {
        update_post_meta($post_id, '_almaseo_og_image', esc_url_raw($_POST['almaseo_og_image']));
    }

    // Twitter Card metadata
    if (isset($_POST['almaseo_twitter_card'])) {
        update_post_meta($post_id, '_almaseo_twitter_card', sanitize_text_field($_POST['almaseo_twitter_card']));
    }
    if (isset($_POST['almaseo_twitter_title'])) {
        update_post_meta($post_id, '_almaseo_twitter_title', sanitize_text_field($_POST['almaseo_twitter_title']));
    }
    if (isset($_POST['almaseo_twitter_description'])) {
        update_post_meta($post_id, '_almaseo_twitter_description', sanitize_textarea_field($_POST['almaseo_twitter_description']));
    }

    // Save update reminder settings
    if (isset($_POST['almaseo_update_reminder_enabled'])) {
        update_post_meta($post_id, '_almaseo_update_reminder_enabled', '1');

        // Save email preference
        if (isset($_POST['almaseo_update_reminder_email'])) {
            update_post_meta($post_id, '_almaseo_update_reminder_email', '1');
        } else {
            delete_post_meta($post_id, '_almaseo_update_reminder_email');
        }

        if (isset($_POST['almaseo_update_reminder_days'])) {
            $reminder_days = intval($_POST['almaseo_update_reminder_days']);
            if ($reminder_days > 0 && $reminder_days <= 365) {
                update_post_meta($post_id, '_almaseo_update_reminder_days', $reminder_days);

                // Schedule the reminder cron event
                $scheduled_time = current_time('U') + ($reminder_days * DAY_IN_SECONDS);

                // Clear any existing reminder for this post
                wp_clear_scheduled_hook('almaseo_content_refresh_reminder', array($post_id));

                // Schedule new reminder
                wp_schedule_single_event($scheduled_time, 'almaseo_content_refresh_reminder', array($post_id));

                // Save the scheduled time for display
                update_post_meta($post_id, '_almaseo_update_reminder_scheduled', $scheduled_time);
            }
        }
    } else {
        // Clear reminder if unchecked
        delete_post_meta($post_id, '_almaseo_update_reminder_enabled');
        delete_post_meta($post_id, '_almaseo_update_reminder_email');
        delete_post_meta($post_id, '_almaseo_update_reminder_scheduled');

        // Clear scheduled event
        wp_clear_scheduled_hook('almaseo_content_refresh_reminder', array($post_id));
    }
}
add_action('save_post', 'almaseo_save_seo_playground_meta');
