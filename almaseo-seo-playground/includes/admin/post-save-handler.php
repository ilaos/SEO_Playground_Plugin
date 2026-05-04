<?php
/**
 * AlmaSEO Post Save Handler - Saves SEO metadata when posts are saved
 */

if (!defined('ABSPATH')) exit;

// Save SEO Playground data
if (!function_exists('almaseo_save_seo_playground_meta')) {
function almaseo_save_seo_playground_meta($post_id) {
    // Check if nonce is valid
    if (!isset($_POST['almaseo_seo_playground_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['almaseo_seo_playground_nonce'])), 'almaseo_seo_playground_nonce')) {
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
    $new_title = isset($_POST['almaseo_seo_title']) ? almaseo_sanitize_seo_title(wp_unslash($_POST['almaseo_seo_title'])) : '';
    $new_description = isset($_POST['almaseo_seo_description']) ? almaseo_sanitize_meta_description(wp_unslash($_POST['almaseo_seo_description'])) : '';
    $new_keyword = isset($_POST['almaseo_focus_keyword']) ? sanitize_text_field(wp_unslash($_POST['almaseo_focus_keyword'])) : '';

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
        update_post_meta($post_id, '_seo_playground_notes', almaseo_sanitize_notes(wp_unslash($_POST['almaseo_seo_notes'])));
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
        update_post_meta($post_id, '_almaseo_canonical_url', esc_url_raw(wp_unslash($_POST['almaseo_canonical_url'])));
    }

    // Schema Type — single merged dropdown writes to BOTH legacy + advanced meta
    // keys so all consumers (meta-tags-renderer, schema-advanced-output, refresh-queue,
    // eeat-engine, schema-clean, llm-rest, etc.) read a consistent value regardless
    // of which key they query.
    if (isset($_POST['almaseo_schema_type'])) {
        $schema_type = almaseo_sanitize_schema_type(wp_unslash($_POST['almaseo_schema_type']));
        update_post_meta($post_id, '_almaseo_schema_type', $schema_type);
        update_post_meta($post_id, '_seo_playground_schema_type', $schema_type); // Legacy
        update_post_meta($post_id, '_almaseo_schema_primary_type', $schema_type); // Advanced output reads this
    }

    // Backwards compatibility: legacy POST field from older form versions
    if (isset($_POST['almaseo_schema_primary_type']) && !isset($_POST['almaseo_schema_type'])) {
        $primary = almaseo_sanitize_schema_type(wp_unslash($_POST['almaseo_schema_primary_type']));
        update_post_meta($post_id, '_almaseo_schema_primary_type', $primary);
        update_post_meta($post_id, '_almaseo_schema_type', $primary);
    }

    // Secondary schema types — multi-schema. Whitelist-validated against the
    // dispatcher's known types so a tampered POST can't inject arbitrary @types.
    // Always write (even when empty) so unticking everything actually clears.
    if (current_user_can('edit_post', $post_id)) {
        $allowed_secondary = array(
            'FAQPage', 'HowTo', 'LocalBusiness', 'MusicGroup',
            'Person', 'Organization', 'Product', 'Event', 'Recipe',
        );
        $primary_for_dedup = isset($_POST['almaseo_schema_type'])
            ? almaseo_sanitize_schema_type(wp_unslash($_POST['almaseo_schema_type']))
            : '';
        $submitted = isset($_POST['almaseo_schema_secondary_types']) && is_array($_POST['almaseo_schema_secondary_types'])
            ? array_map('sanitize_text_field', wp_unslash($_POST['almaseo_schema_secondary_types']))
            : array();
        $clean_secondary = array();
        foreach ($submitted as $t) {
            if ($t && $t !== $primary_for_dedup && in_array($t, $allowed_secondary, true) && !in_array($t, $clean_secondary, true)) {
                $clean_secondary[] = $t;
            }
        }
        update_post_meta($post_id, '_almaseo_schema_secondary_types', wp_json_encode($clean_secondary));
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
        update_post_meta($post_id, '_almaseo_article_author', sanitize_text_field(wp_unslash($_POST['almaseo_article_author'])));
    }

    // LocalBusiness fields (v8.5.0)
    if (isset($_POST['almaseo_lb_subtype'])) {
        $lb_subtype = sanitize_text_field(wp_unslash($_POST['almaseo_lb_subtype']));
        if (function_exists('almaseo_sanitize_localbusiness_type')) {
            $lb_subtype = almaseo_sanitize_localbusiness_type($lb_subtype);
        }
        update_post_meta($post_id, '_almaseo_lb_subtype', $lb_subtype);
    }
    $lb_text_fields = array(
        'almaseo_lb_street'      => '_almaseo_lb_street',
        'almaseo_lb_city'        => '_almaseo_lb_city',
        'almaseo_lb_state'       => '_almaseo_lb_state',
        'almaseo_lb_zip'         => '_almaseo_lb_zip',
        'almaseo_lb_country'     => '_almaseo_lb_country',
        'almaseo_lb_phone'       => '_almaseo_lb_phone',
        'almaseo_lb_price_range' => '_almaseo_lb_price_range',
        'almaseo_lb_area_served' => '_almaseo_lb_area_served',
        'almaseo_lb_payment'     => '_almaseo_lb_payment',
    );
    foreach ($lb_text_fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$post_key])));
        }
    }
    if (isset($_POST['almaseo_lb_email'])) {
        update_post_meta($post_id, '_almaseo_lb_email', sanitize_email(wp_unslash($_POST['almaseo_lb_email'])));
    }
    if (isset($_POST['almaseo_lb_lat'])) {
        update_post_meta($post_id, '_almaseo_lb_lat', sanitize_text_field(wp_unslash($_POST['almaseo_lb_lat'])));
    }
    if (isset($_POST['almaseo_lb_lng'])) {
        update_post_meta($post_id, '_almaseo_lb_lng', sanitize_text_field(wp_unslash($_POST['almaseo_lb_lng'])));
    }
    if (isset($_POST['almaseo_lb_hours']) && is_array($_POST['almaseo_lb_hours'])) {
        $hours = array();
        $valid_days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
        foreach ($valid_days as $day) {
            if (isset($_POST['almaseo_lb_hours'][$day])) {
                $lb_hours_raw = wp_unslash($_POST['almaseo_lb_hours']);
                $hours[$day] = array(
                    'open'  => sanitize_text_field($lb_hours_raw[$day]['open'] ?? ''),
                    'close' => sanitize_text_field($lb_hours_raw[$day]['close'] ?? ''),
                );
            }
        }
        update_post_meta($post_id, '_almaseo_lb_hours', wp_json_encode($hours));
    }

    // MusicGroup fields — band/artist/ensemble schema
    if (isset($_POST['almaseo_mg_genre'])) {
        update_post_meta($post_id, '_almaseo_mg_genre', sanitize_text_field(wp_unslash($_POST['almaseo_mg_genre'])));
    }
    if (isset($_POST['almaseo_mg_founding_date'])) {
        update_post_meta($post_id, '_almaseo_mg_founding_date', sanitize_text_field(wp_unslash($_POST['almaseo_mg_founding_date'])));
    }
    if (isset($_POST['almaseo_mg_founding_location'])) {
        update_post_meta($post_id, '_almaseo_mg_founding_location', sanitize_text_field(wp_unslash($_POST['almaseo_mg_founding_location'])));
    }
    if (isset($_POST['almaseo_mg_members'])) {
        update_post_meta($post_id, '_almaseo_mg_members', sanitize_textarea_field(wp_unslash($_POST['almaseo_mg_members'])));
    }
    if (isset($_POST['almaseo_mg_image'])) {
        update_post_meta($post_id, '_almaseo_mg_image', esc_url_raw(wp_unslash($_POST['almaseo_mg_image'])));
    }
    if (isset($_POST['almaseo_mg_same_as'])) {
        update_post_meta($post_id, '_almaseo_mg_same_as', sanitize_textarea_field(wp_unslash($_POST['almaseo_mg_same_as'])));
    }
    $mg_address_fields = array(
        'almaseo_mg_area_served' => '_almaseo_mg_area_served',
        'almaseo_mg_street'      => '_almaseo_mg_street',
        'almaseo_mg_city'        => '_almaseo_mg_city',
        'almaseo_mg_state'       => '_almaseo_mg_state',
        'almaseo_mg_zip'         => '_almaseo_mg_zip',
        'almaseo_mg_country'     => '_almaseo_mg_country',
    );
    foreach ($mg_address_fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$post_key])));
        }
    }

    // Person fields — author/profile/public-figure schema
    $person_text_fields = array(
        'almaseo_person_job_title'    => '_almaseo_person_job_title',
        'almaseo_person_works_for'    => '_almaseo_person_works_for',
        'almaseo_person_telephone'    => '_almaseo_person_telephone',
        'almaseo_person_given_name'   => '_almaseo_person_given_name',
        'almaseo_person_family_name'  => '_almaseo_person_family_name',
        'almaseo_person_birth_date'   => '_almaseo_person_birth_date',
        'almaseo_person_knows_about'  => '_almaseo_person_knows_about',
    );
    foreach ($person_text_fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$post_key])));
        }
    }
    if (isset($_POST['almaseo_person_email'])) {
        update_post_meta($post_id, '_almaseo_person_email', sanitize_email(wp_unslash($_POST['almaseo_person_email'])));
    }
    if (isset($_POST['almaseo_person_image'])) {
        update_post_meta($post_id, '_almaseo_person_image', esc_url_raw(wp_unslash($_POST['almaseo_person_image'])));
    }
    if (isset($_POST['almaseo_person_same_as'])) {
        update_post_meta($post_id, '_almaseo_person_same_as', sanitize_textarea_field(wp_unslash($_POST['almaseo_person_same_as'])));
    }

    // Organization fields — company/NGO/non-physical-org schema
    $org_text_fields = array(
        'almaseo_org_legal_name'    => '_almaseo_org_legal_name',
        'almaseo_org_founding_date' => '_almaseo_org_founding_date',
        'almaseo_org_founder'       => '_almaseo_org_founder',
        'almaseo_org_industry'      => '_almaseo_org_industry',
        'almaseo_org_telephone'     => '_almaseo_org_telephone',
    );
    foreach ($org_text_fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$post_key])));
        }
    }
    if (isset($_POST['almaseo_org_employees'])) {
        $employees_raw = wp_unslash($_POST['almaseo_org_employees']);
        update_post_meta($post_id, '_almaseo_org_employees', $employees_raw === '' ? '' : absint($employees_raw));
    }
    if (isset($_POST['almaseo_org_email'])) {
        update_post_meta($post_id, '_almaseo_org_email', sanitize_email(wp_unslash($_POST['almaseo_org_email'])));
    }
    if (isset($_POST['almaseo_org_logo'])) {
        update_post_meta($post_id, '_almaseo_org_logo', esc_url_raw(wp_unslash($_POST['almaseo_org_logo'])));
    }
    if (isset($_POST['almaseo_org_same_as'])) {
        update_post_meta($post_id, '_almaseo_org_same_as', sanitize_textarea_field(wp_unslash($_POST['almaseo_org_same_as'])));
    }

    // Product fields — e-commerce schema
    $product_text_fields = array(
        'almaseo_product_brand'        => '_almaseo_product_brand',
        'almaseo_product_sku'          => '_almaseo_product_sku',
        'almaseo_product_gtin'         => '_almaseo_product_gtin',
        'almaseo_product_mpn'          => '_almaseo_product_mpn',
        'almaseo_product_price'        => '_almaseo_product_price',
        'almaseo_product_rating_value' => '_almaseo_product_rating_value',
    );
    foreach ($product_text_fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$post_key])));
        }
    }
    if (isset($_POST['almaseo_product_currency'])) {
        $currency = strtoupper(sanitize_text_field(wp_unslash($_POST['almaseo_product_currency'])));
        // ISO 4217 is 3 letters; trim to that length to be safe
        update_post_meta($post_id, '_almaseo_product_currency', substr($currency, 0, 3));
    }
    // Whitelist for availability — schema.org expects an exact ItemAvailability value
    $availability_whitelist = array('InStock', 'OutOfStock', 'PreOrder', 'BackOrder', 'Discontinued', 'LimitedAvailability', 'SoldOut');
    if (isset($_POST['almaseo_product_availability'])) {
        $val = sanitize_text_field(wp_unslash($_POST['almaseo_product_availability']));
        if (in_array($val, $availability_whitelist, true)) {
            update_post_meta($post_id, '_almaseo_product_availability', $val);
        }
    }
    // Whitelist for condition — schema.org expects an exact OfferItemCondition value
    $condition_whitelist = array('NewCondition', 'UsedCondition', 'RefurbishedCondition', 'DamagedCondition');
    if (isset($_POST['almaseo_product_condition'])) {
        $val = sanitize_text_field(wp_unslash($_POST['almaseo_product_condition']));
        if (in_array($val, $condition_whitelist, true)) {
            update_post_meta($post_id, '_almaseo_product_condition', $val);
        }
    }
    if (isset($_POST['almaseo_product_review_count'])) {
        $count_raw = wp_unslash($_POST['almaseo_product_review_count']);
        update_post_meta($post_id, '_almaseo_product_review_count', $count_raw === '' ? '' : absint($count_raw));
    }
    if (isset($_POST['almaseo_product_image'])) {
        update_post_meta($post_id, '_almaseo_product_image', esc_url_raw(wp_unslash($_POST['almaseo_product_image'])));
    }

    // Event fields — concert/conference/webinar schema
    $event_text_fields = array(
        'almaseo_event_start_date'       => '_almaseo_event_start_date',
        'almaseo_event_end_date'         => '_almaseo_event_end_date',
        'almaseo_event_location_name'    => '_almaseo_event_location_name',
        'almaseo_event_location_address' => '_almaseo_event_location_address',
        'almaseo_event_performer'        => '_almaseo_event_performer',
        'almaseo_event_organizer'        => '_almaseo_event_organizer',
        'almaseo_event_ticket_price'     => '_almaseo_event_ticket_price',
    );
    foreach ($event_text_fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$post_key])));
        }
    }
    if (isset($_POST['almaseo_event_ticket_currency'])) {
        $currency = strtoupper(sanitize_text_field(wp_unslash($_POST['almaseo_event_ticket_currency'])));
        update_post_meta($post_id, '_almaseo_event_ticket_currency', substr($currency, 0, 3));
    }
    // Whitelist event status — schema.org expects an exact EventStatusType value
    $event_status_whitelist = array('EventScheduled', 'EventCancelled', 'EventPostponed', 'EventRescheduled', 'EventMovedOnline');
    if (isset($_POST['almaseo_event_status'])) {
        $val = sanitize_text_field(wp_unslash($_POST['almaseo_event_status']));
        if (in_array($val, $event_status_whitelist, true)) {
            update_post_meta($post_id, '_almaseo_event_status', $val);
        }
    }
    // Whitelist attendance mode
    $attendance_whitelist = array('OfflineEventAttendanceMode', 'OnlineEventAttendanceMode', 'MixedEventAttendanceMode');
    if (isset($_POST['almaseo_event_attendance_mode'])) {
        $val = sanitize_text_field(wp_unslash($_POST['almaseo_event_attendance_mode']));
        if (in_array($val, $attendance_whitelist, true)) {
            update_post_meta($post_id, '_almaseo_event_attendance_mode', $val);
        }
    }
    if (isset($_POST['almaseo_event_location_url'])) {
        update_post_meta($post_id, '_almaseo_event_location_url', esc_url_raw(wp_unslash($_POST['almaseo_event_location_url'])));
    }
    if (isset($_POST['almaseo_event_ticket_url'])) {
        update_post_meta($post_id, '_almaseo_event_ticket_url', esc_url_raw(wp_unslash($_POST['almaseo_event_ticket_url'])));
    }
    if (isset($_POST['almaseo_event_image'])) {
        update_post_meta($post_id, '_almaseo_event_image', esc_url_raw(wp_unslash($_POST['almaseo_event_image'])));
    }

    // Recipe fields — food/cooking schema
    $recipe_text_fields = array(
        'almaseo_recipe_cuisine'      => '_almaseo_recipe_cuisine',
        'almaseo_recipe_category'     => '_almaseo_recipe_category',
        'almaseo_recipe_yield'        => '_almaseo_recipe_yield',
        'almaseo_recipe_rating_value' => '_almaseo_recipe_rating_value',
        'almaseo_recipe_keywords'     => '_almaseo_recipe_keywords',
    );
    foreach ($recipe_text_fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field(wp_unslash($_POST[$post_key])));
        }
    }
    $recipe_int_fields = array(
        'almaseo_recipe_prep_minutes' => '_almaseo_recipe_prep_minutes',
        'almaseo_recipe_cook_minutes' => '_almaseo_recipe_cook_minutes',
        'almaseo_recipe_calories'     => '_almaseo_recipe_calories',
        'almaseo_recipe_review_count' => '_almaseo_recipe_review_count',
    );
    foreach ($recipe_int_fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            $raw = wp_unslash($_POST[$post_key]);
            update_post_meta($post_id, $meta_key, $raw === '' ? '' : absint($raw));
        }
    }
    if (isset($_POST['almaseo_recipe_ingredients'])) {
        update_post_meta($post_id, '_almaseo_recipe_ingredients', sanitize_textarea_field(wp_unslash($_POST['almaseo_recipe_ingredients'])));
    }
    if (isset($_POST['almaseo_recipe_instructions'])) {
        update_post_meta($post_id, '_almaseo_recipe_instructions', sanitize_textarea_field(wp_unslash($_POST['almaseo_recipe_instructions'])));
    }
    if (isset($_POST['almaseo_recipe_image'])) {
        update_post_meta($post_id, '_almaseo_recipe_image', esc_url_raw(wp_unslash($_POST['almaseo_recipe_image'])));
    }

    // Open Graph metadata
    if (isset($_POST['almaseo_og_title'])) {
        update_post_meta($post_id, '_almaseo_og_title', sanitize_text_field(wp_unslash($_POST['almaseo_og_title'])));
    }
    if (isset($_POST['almaseo_og_description'])) {
        update_post_meta($post_id, '_almaseo_og_description', sanitize_textarea_field(wp_unslash($_POST['almaseo_og_description'])));
    }
    if (isset($_POST['almaseo_og_image'])) {
        update_post_meta($post_id, '_almaseo_og_image', esc_url_raw(wp_unslash($_POST['almaseo_og_image'])));
    }

    // Twitter Card metadata
    if (isset($_POST['almaseo_twitter_card'])) {
        update_post_meta($post_id, '_almaseo_twitter_card', sanitize_text_field(wp_unslash($_POST['almaseo_twitter_card'])));
    }
    if (isset($_POST['almaseo_twitter_title'])) {
        update_post_meta($post_id, '_almaseo_twitter_title', sanitize_text_field(wp_unslash($_POST['almaseo_twitter_title'])));
    }
    if (isset($_POST['almaseo_twitter_description'])) {
        update_post_meta($post_id, '_almaseo_twitter_description', sanitize_textarea_field(wp_unslash($_POST['almaseo_twitter_description'])));
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
            $reminder_days = intval(wp_unslash($_POST['almaseo_update_reminder_days']));
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
} // end function_exists guard: almaseo_save_seo_playground_meta
add_action('save_post', 'almaseo_save_seo_playground_meta');
