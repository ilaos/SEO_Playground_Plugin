<?php
/**
 * Advanced Schema Output
 *
 * Generates advanced schema markup including Knowledge Graph, BreadcrumbList,
 * and advanced schema types (FAQPage, HowTo, Service, LocalBusiness, etc.)
 *
 * @package AlmaSEO
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main entry point for advanced schema output
 */
if (!function_exists('almaseo_output_advanced_schema')) {
function almaseo_output_advanced_schema() {
    // Guard: Only Pro users
    if (!almaseo_feature_available('schema_advanced')) {
        return;
    }

    // Only run on singular pages (not admin, archives, etc.)
    if (is_admin() || !is_singular()) {
        return;
    }

    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return;
    }

    // Check if advanced schema is disabled for this post
    if (get_post_meta($post->ID, '_almaseo_schema_disable', true)) {
        return;
    }

    // Get advanced schema settings
    $settings = get_option('almaseo_schema_advanced_settings', array(
        'enabled' => false,
        'site_represents' => 'organization',
        'site_name' => '',
        'site_logo_url' => '',
        'site_social_profiles' => array(),
        'default_schema_by_post_type' => array()
    ));

    // Check if advanced schema is enabled globally
    if (!$settings['enabled']) {
        return;
    }

    // Build the @graph array
    $graph = array();

    // 1. Knowledge Graph node
    $kg_node = almaseo_build_knowledge_graph_node($settings);
    if ($kg_node) {
        $graph[] = $kg_node;
    }

    // 2. Primary schema type node
    $primary_node = almaseo_build_primary_schema_node($post, $settings);
    if ($primary_node) {
        $graph[] = $primary_node;
    }

    // 3. Secondary schema type nodes — user can describe a single page as
    //    multiple things (e.g. MusicGroup + LocalBusiness for a venue band).
    //    Stored as JSON array of type strings; deduped against primary.
    //    Pro-gated (schema_multi) — even with data in post meta from a prior
    //    Pro session, free-tier sites must not emit additional graph nodes.
    $secondary_raw = get_post_meta($post->ID, '_almaseo_schema_secondary_types', true);
    $secondary_types = array();
    if ($secondary_raw && function_exists('almaseo_feature_available') && almaseo_feature_available('schema_multi')) {
        $decoded = is_array($secondary_raw) ? $secondary_raw : json_decode($secondary_raw, true);
        if (is_array($decoded)) {
            $secondary_types = array_values(array_unique(array_filter($decoded)));
        }
    }
    if (!empty($secondary_types)) {
        $primary_type_str = is_array($primary_node) && isset($primary_node['@type']) ? $primary_node['@type'] : '';
        foreach ($secondary_types as $sec_type) {
            if ($sec_type === $primary_type_str) continue;
            $sec_node = almaseo_build_schema_node_by_type($post, $sec_type);
            if ($sec_node) {
                $graph[] = $sec_node;
            }
        }
    }

    // Output JSON-LD if we have any nodes
    if (!empty($graph)) {
        $data = array(
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        );

        echo '<script type="application/ld+json">';
        echo wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional JSON-LD output
        echo '</script>' . "\n";
    }
}
} // end function_exists guard: almaseo_output_advanced_schema

/**
 * Build Knowledge Graph node (Organization or Person)
 *
 * @param array $settings Advanced schema settings
 * @return array|null Knowledge Graph node or null if not configured
 */
if (!function_exists('almaseo_build_knowledge_graph_node')) {
function almaseo_build_knowledge_graph_node($settings) {
    // Require at least a name
    if (empty($settings['site_name'])) {
        return null;
    }

    $type = ($settings['site_represents'] === 'person') ? 'Person' : 'Organization';

    $node = array(
        '@type' => $type,
        '@id' => home_url('/#identity'),
        'name' => $settings['site_name'],
        'url' => home_url('/'),
    );

    // Add logo if provided
    if (!empty($settings['site_logo_url'])) {
        $node['logo'] = array(
            '@type' => 'ImageObject',
            'url' => $settings['site_logo_url'],
        );

        // For Organization, also add image property
        if ($type === 'Organization') {
            $node['image'] = $settings['site_logo_url'];
        }
    }

    // Add social profiles if provided
    if (!empty($settings['site_social_profiles']) && is_array($settings['site_social_profiles'])) {
        $node['sameAs'] = $settings['site_social_profiles'];
    }

    return $node;
}
} // end function_exists guard: almaseo_build_knowledge_graph_node

/**
 * Build primary schema type node
 *
 * @param WP_Post $post Current post
 * @param array $settings Advanced schema settings
 * @return array|null Primary schema node or null if not applicable
 */
if (!function_exists('almaseo_build_primary_schema_node')) {
function almaseo_build_primary_schema_node($post, $settings) {
    $schema_type = almaseo_determine_schema_type($post, $settings);
    if (!$schema_type) {
        return null;
    }
    return almaseo_build_schema_node_by_type($post, $schema_type);
}
} // end function_exists guard: almaseo_build_primary_schema_node

/**
 * Generic dispatcher: build a schema node for any supported type.
 *
 * Used by the primary node builder AND by the secondary-types loop in
 * almaseo_output_advanced_schema. Returns null for unknown types so callers
 * can safely skip without emitting a malformed node.
 *
 * @param WP_Post $post
 * @param string $type Schema.org type name (e.g. "MusicGroup", "LocalBusiness")
 * @return array|null
 */
if (!function_exists('almaseo_build_schema_node_by_type')) {
function almaseo_build_schema_node_by_type($post, $type) {
    switch ($type) {
        case 'FAQPage':       return almaseo_build_faqpage_node($post);
        case 'HowTo':         return almaseo_build_howto_node($post);
        case 'Service':       return almaseo_build_service_node($post);
        case 'LocalBusiness': return almaseo_build_localbusiness_node($post);
        case 'MusicGroup':    return almaseo_build_musicgroup_node($post);
        case 'Person':        return almaseo_build_person_node($post);
        case 'Organization':  return almaseo_build_organization_node($post);
        case 'Product':       return almaseo_build_product_node($post);
        case 'Event':         return almaseo_build_event_node($post);
        case 'Recipe':        return almaseo_build_recipe_node($post);
        case 'Article':
        case 'BlogPosting':
        case 'NewsArticle':
            return almaseo_build_article_node($post, $type);
        default:
            return null;
    }
}
} // end function_exists guard: almaseo_build_schema_node_by_type

/**
 * Determine the schema type for a post
 *
 * @param WP_Post $post Current post
 * @param array $settings Advanced schema settings
 * @return string|null Schema type or null
 */
if (!function_exists('almaseo_determine_schema_type')) {
function almaseo_determine_schema_type($post, $settings) {
    // 1. Check per-post primary type
    $primary_type = get_post_meta($post->ID, '_almaseo_schema_primary_type', true);
    if (!empty($primary_type)) {
        return $primary_type;
    }

    // 2. Check per-post toggles
    if (get_post_meta($post->ID, '_almaseo_schema_is_faqpage', true)) {
        return 'FAQPage';
    }
    if (get_post_meta($post->ID, '_almaseo_schema_is_howto', true)) {
        return 'HowTo';
    }

    // 3. Check default for this post type
    if (isset($settings['default_schema_by_post_type'][$post->post_type])) {
        $default_type = $settings['default_schema_by_post_type'][$post->post_type];
        if (!empty($default_type)) {
            return $default_type;
        }
    }

    // 4. Fall back to Article
    return 'Article';
}
} // end function_exists guard: almaseo_determine_schema_type

/**
 * Build Article/BlogPosting/NewsArticle node
 *
 * @param WP_Post $post Current post
 * @param string $type Schema type (Article, BlogPosting, NewsArticle)
 * @return array Article node
 */
if (!function_exists('almaseo_build_article_node')) {
function almaseo_build_article_node($post, $type = 'Article') {
    $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
    $seo_description = get_post_meta($post->ID, '_almaseo_description', true);
    $headline = $seo_title ?: get_the_title($post->ID);
    $description = $seo_description ?: wp_trim_words(wp_strip_all_tags($post->post_content), 30);

    $node = array(
        '@type' => $type,
        '@id' => get_permalink($post->ID) . '#article',
        'headline' => $headline,
        'description' => $description,
        'url' => get_permalink($post->ID),
        'datePublished' => get_the_date('c', $post->ID),
        'dateModified' => get_the_modified_date('c', $post->ID),
    );

    // Author
    $author_name = get_the_author_meta('display_name', $post->post_author);
    $node['author'] = array(
        '@type' => 'Person',
        'name' => $author_name,
    );

    // Publisher (reference Knowledge Graph if exists)
    $site_name = get_bloginfo('name');
    $node['publisher'] = array(
        '@type' => 'Organization',
        'name' => $site_name,
        '@id' => home_url('/#identity'),
    );

    // Image (featured image or OG image)
    $og_image = get_post_meta($post->ID, '_almaseo_og_image', true);
    $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
    $image_url = $og_image ?: $featured_image;

    if ($image_url) {
        $node['image'] = array(
            '@type' => 'ImageObject',
            'url' => $image_url,
        );
    }

    return $node;
}
} // end function_exists guard: almaseo_build_article_node

/**
 * Build FAQPage node
 *
 * @param WP_Post $post Current post
 * @return array FAQPage node
 */
if (!function_exists('almaseo_build_faqpage_node')) {
function almaseo_build_faqpage_node($post) {
    $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
    $headline = $seo_title ?: get_the_title($post->ID);

    $node = array(
        '@type' => 'FAQPage',
        '@id' => get_permalink($post->ID) . '#faqpage',
        'headline' => $headline,
        'url' => get_permalink($post->ID),
    );

    // Try to extract Q&A pairs from content
    $qa_pairs = almaseo_extract_qa_pairs($post->post_content);

    if (!empty($qa_pairs)) {
        $node['mainEntity'] = $qa_pairs;
    }

    return $node;
}
} // end function_exists guard: almaseo_build_faqpage_node

/**
 * Extract Q&A pairs from content
 *
 * @param string $content Post content (HTML)
 * @return array Array of Question nodes
 */
if (!function_exists('almaseo_extract_qa_pairs')) {
function almaseo_extract_qa_pairs($content) {
    $qa_pairs = array();

    // Find headings that end with ? (questions)
    preg_match_all('/<h([2-6])[^>]*>(.*?\?[^<]*)<\/h\1>\s*<p[^>]*>(.*?)<\/p>/is', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $question = wp_strip_all_tags($match[2]);
        $answer = wp_strip_all_tags($match[3]);

        $qa_pairs[] = array(
            '@type' => 'Question',
            'name' => trim($question),
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => trim($answer),
            ),
        );

        // Limit to 10 Q&A pairs
        if (count($qa_pairs) >= 10) {
            break;
        }
    }

    return $qa_pairs;
}
} // end function_exists guard: almaseo_extract_qa_pairs

/**
 * Build HowTo node
 *
 * @param WP_Post $post Current post
 * @return array HowTo node
 */
if (!function_exists('almaseo_build_howto_node')) {
function almaseo_build_howto_node($post) {
    $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
    $seo_description = get_post_meta($post->ID, '_almaseo_description', true);
    $name = $seo_title ?: get_the_title($post->ID);
    $description = $seo_description ?: wp_trim_words(wp_strip_all_tags($post->post_content), 30);

    $node = array(
        '@type' => 'HowTo',
        '@id' => get_permalink($post->ID) . '#howto',
        'name' => $name,
        'description' => $description,
        'url' => get_permalink($post->ID),
    );

    // Try to extract steps from content (ordered/unordered lists or numbered headings)
    $steps = almaseo_extract_howto_steps($post->post_content);

    if (!empty($steps)) {
        $node['step'] = $steps;
    }

    return $node;
}
} // end function_exists guard: almaseo_build_howto_node

/**
 * Extract HowTo steps from content
 *
 * @param string $content Post content (HTML)
 * @return array Array of HowToStep nodes
 */
if (!function_exists('almaseo_extract_howto_steps')) {
function almaseo_extract_howto_steps($content) {
    $steps = array();

    // Try to find ordered/unordered lists
    preg_match('/<[ou]l[^>]*>(.*?)<\/[ou]l>/is', $content, $list_match);

    if (!empty($list_match[1])) {
        preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $list_match[1], $items);

        foreach ($items[1] as $item) {
            $text = wp_strip_all_tags($item);
            if (!empty(trim($text))) {
                $steps[] = array(
                    '@type' => 'HowToStep',
                    'text' => trim($text),
                );
            }

            // Limit to 20 steps
            if (count($steps) >= 20) {
                break;
            }
        }
    }

    return $steps;
}
} // end function_exists guard: almaseo_extract_howto_steps

/**
 * Build Service node
 *
 * @param WP_Post $post Current post
 * @return array Service node
 */
if (!function_exists('almaseo_build_service_node')) {
function almaseo_build_service_node($post) {
    $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
    $seo_description = get_post_meta($post->ID, '_almaseo_description', true);
    $name = $seo_title ?: get_the_title($post->ID);
    $description = $seo_description ?: wp_trim_words(wp_strip_all_tags($post->post_content), 30);

    $node = array(
        '@type' => 'Service',
        '@id' => get_permalink($post->ID) . '#service',
        'name' => $name,
        'description' => $description,
        'url' => get_permalink($post->ID),
    );

    // Link to provider (Knowledge Graph if exists)
    $node['provider'] = array(
        '@type' => 'Organization',
        '@id' => home_url('/#identity'),
    );

    return $node;
}
} // end function_exists guard: almaseo_build_service_node

/**
 * Build LocalBusiness node
 *
 * @param WP_Post $post Current post
 * @return array LocalBusiness node
 */
if (!function_exists('almaseo_build_localbusiness_node')) {
function almaseo_build_localbusiness_node($post) {
    $seo_title       = get_post_meta($post->ID, '_almaseo_title', true);
    $seo_description = get_post_meta($post->ID, '_almaseo_description', true);
    $name            = $seo_title ?: get_the_title($post->ID);
    $description     = $seo_description ?: wp_trim_words(wp_strip_all_tags($post->post_content), 30);

    // Determine subtype
    $subtype = get_post_meta($post->ID, '_almaseo_lb_subtype', true);
    $type    = ( $subtype && $subtype !== 'LocalBusiness' ) ? $subtype : 'LocalBusiness';

    $node = array(
        '@type'       => $type,
        '@id'         => get_permalink($post->ID) . '#business',
        'name'        => $name,
        'description' => $description,
        'url'         => get_permalink($post->ID),
    );

    // Image
    $image = get_post_meta($post->ID, '_almaseo_og_image', true);
    if ( ! $image ) {
        $thumb_id = get_post_thumbnail_id($post->ID);
        if ( $thumb_id ) {
            $image = wp_get_attachment_url($thumb_id);
        }
    }
    if ( $image ) {
        $node['image'] = $image;
    }

    // Address
    $street  = get_post_meta($post->ID, '_almaseo_lb_street', true);
    $city    = get_post_meta($post->ID, '_almaseo_lb_city', true);
    $state   = get_post_meta($post->ID, '_almaseo_lb_state', true);
    $zip     = get_post_meta($post->ID, '_almaseo_lb_zip', true);
    $country = get_post_meta($post->ID, '_almaseo_lb_country', true);

    if ( $street || $city ) {
        $address = array( '@type' => 'PostalAddress' );
        if ( $street )  $address['streetAddress']   = $street;
        if ( $city )    $address['addressLocality']  = $city;
        if ( $state )   $address['addressRegion']    = $state;
        if ( $zip )     $address['postalCode']       = $zip;
        if ( $country ) $address['addressCountry']   = $country;
        $node['address'] = $address;
    }

    // Contact info
    $phone = get_post_meta($post->ID, '_almaseo_lb_phone', true);
    $email = get_post_meta($post->ID, '_almaseo_lb_email', true);
    if ( $phone ) $node['telephone'] = $phone;
    if ( $email ) $node['email']     = $email;

    // Price range
    $price_range = get_post_meta($post->ID, '_almaseo_lb_price_range', true);
    if ( $price_range ) $node['priceRange'] = $price_range;

    // Geo coordinates
    $lat = get_post_meta($post->ID, '_almaseo_lb_lat', true);
    $lng = get_post_meta($post->ID, '_almaseo_lb_lng', true);
    if ( $lat && $lng ) {
        $node['geo'] = array(
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $lat,
            'longitude' => (float) $lng,
        );
    }

    // Opening hours
    $hours_json = get_post_meta($post->ID, '_almaseo_lb_hours', true);
    if ( $hours_json ) {
        $hours = is_array($hours_json) ? $hours_json : json_decode($hours_json, true);
        if ( is_array($hours) && ! empty($hours) ) {
            $specs = array();
            $day_map = array(
                'monday'    => 'Mo', 'tuesday'  => 'Tu', 'wednesday' => 'We',
                'thursday'  => 'Th', 'friday'   => 'Fr', 'saturday'  => 'Sa',
                'sunday'    => 'Su',
            );
            foreach ( $hours as $day => $times ) {
                if ( empty($times['open']) || empty($times['close']) ) continue;
                $abbr = isset($day_map[$day]) ? $day_map[$day] : ucfirst(substr($day, 0, 2));
                $specs[] = array(
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => $abbr,
                    'opens'     => $times['open'],
                    'closes'    => $times['close'],
                );
            }
            if ( ! empty($specs) ) {
                $node['openingHoursSpecification'] = $specs;
            }
        }
    }

    // Area served
    $area = get_post_meta($post->ID, '_almaseo_lb_area_served', true);
    if ( $area ) $node['areaServed'] = $area;

    // Payment accepted
    $payment = get_post_meta($post->ID, '_almaseo_lb_payment', true);
    if ( $payment ) $node['paymentAccepted'] = $payment;

    return $node;
}
} // end function_exists guard: almaseo_build_localbusiness_node

/**
 * Build a MusicGroup node for bands, musicians, and music ensembles.
 *
 * Members textarea is parsed as one entry per line in "Name | Role" format.
 * Genre is comma-separated. sameAs textarea is one URL per line.
 *
 * @param WP_Post $post
 * @return array MusicGroup node
 */
if (!function_exists('almaseo_build_musicgroup_node')) {
function almaseo_build_musicgroup_node($post) {
    $node = array(
        '@type' => 'MusicGroup',
        '@id'   => get_permalink($post->ID) . '#musicgroup',
        'name'  => get_the_title($post->ID),
        'url'   => get_permalink($post->ID),
    );

    // Genre — comma-separated string → string (one) or array (multiple)
    $genre_raw = get_post_meta($post->ID, '_almaseo_mg_genre', true);
    if ($genre_raw) {
        $genres = array_values(array_filter(array_map('trim', explode(',', $genre_raw))));
        if (!empty($genres)) {
            $node['genre'] = count($genres) === 1 ? $genres[0] : $genres;
        }
    }

    // Founding date and location
    $founding_date = get_post_meta($post->ID, '_almaseo_mg_founding_date', true);
    if ($founding_date) {
        $node['foundingDate'] = $founding_date;
    }
    $founding_location = get_post_meta($post->ID, '_almaseo_mg_founding_location', true);
    if ($founding_location) {
        $node['foundingLocation'] = array(
            '@type' => 'Place',
            'name'  => $founding_location,
        );
    }

    $area_served = get_post_meta($post->ID, '_almaseo_mg_area_served', true);
    if ($area_served) {
        $node['areaServed'] = array(
            '@type' => 'Place',
            'name'  => $area_served,
        );
    }

    $street  = get_post_meta($post->ID, '_almaseo_mg_street', true);
    $city    = get_post_meta($post->ID, '_almaseo_mg_city', true);
    $state   = get_post_meta($post->ID, '_almaseo_mg_state', true);
    $zip     = get_post_meta($post->ID, '_almaseo_mg_zip', true);
    $country = get_post_meta($post->ID, '_almaseo_mg_country', true);
    if ($street || $city || $state || $zip || $country) {
        $address = array('@type' => 'PostalAddress');
        if ($street)  $address['streetAddress']   = $street;
        if ($city)    $address['addressLocality'] = $city;
        if ($state)   $address['addressRegion']   = $state;
        if ($zip)     $address['postalCode']      = $zip;
        if ($country) $address['addressCountry']  = $country;
        $node['address'] = $address;
    }

    // Members — "Name | Role" per line, becomes Person nodes with roleName
    $members_raw = get_post_meta($post->ID, '_almaseo_mg_members', true);
    if ($members_raw) {
        $member_nodes = array();
        $lines = preg_split('/\r\n|\r|\n/', $members_raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line, 2));
            $name  = $parts[0] ?? '';
            $role  = $parts[1] ?? '';
            if ($name === '') {
                continue;
            }
            $person = array(
                '@type' => 'Person',
                'name'  => $name,
            );
            if ($role !== '') {
                $person['roleName'] = $role;
            }
            $member_nodes[] = $person;
        }
        if (!empty($member_nodes)) {
            $node['member'] = $member_nodes;
        }
    }

    // Image — explicit MusicGroup image, then fall back to featured image
    $image_url = get_post_meta($post->ID, '_almaseo_mg_image', true);
    if (!$image_url && has_post_thumbnail($post->ID)) {
        $image_url = get_the_post_thumbnail_url($post->ID, 'large');
    }
    if ($image_url) {
        $node['image'] = $image_url;
    }

    // sameAs — one URL per line, validated and deduped
    $same_as_raw = get_post_meta($post->ID, '_almaseo_mg_same_as', true);
    if ($same_as_raw) {
        $urls = array();
        $lines = preg_split('/\r\n|\r|\n/', $same_as_raw);
        foreach ($lines as $line) {
            $url = esc_url_raw(trim($line));
            if ($url !== '' && !in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }
        if (!empty($urls)) {
            $node['sameAs'] = $urls;
        }
    }

    // Description fallback to post excerpt or trimmed content
    $description = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words($post->post_content, 30);
    if ($description) {
        $node['description'] = $description;
    }

    return $node;
}
} // end function_exists guard: almaseo_build_musicgroup_node

/**
 * Build a Person node for author profiles, public figures, team members.
 *
 * knowsAbout is comma-separated. sameAs textarea is one URL per line.
 * Image falls back to the post's featured image if no override is set.
 *
 * @param WP_Post $post
 * @return array Person node
 */
if (!function_exists('almaseo_build_person_node')) {
function almaseo_build_person_node($post) {
    $node = array(
        '@type' => 'Person',
        '@id'   => get_permalink($post->ID) . '#person',
        'name'  => get_the_title($post->ID),
        'url'   => get_permalink($post->ID),
    );

    // Simple scalar fields
    $given_name  = get_post_meta($post->ID, '_almaseo_person_given_name', true);
    $family_name = get_post_meta($post->ID, '_almaseo_person_family_name', true);
    $job_title   = get_post_meta($post->ID, '_almaseo_person_job_title', true);
    $email       = get_post_meta($post->ID, '_almaseo_person_email', true);
    $telephone   = get_post_meta($post->ID, '_almaseo_person_telephone', true);
    $birth_date  = get_post_meta($post->ID, '_almaseo_person_birth_date', true);
    if ($given_name)  $node['givenName']  = $given_name;
    if ($family_name) $node['familyName'] = $family_name;
    if ($job_title)   $node['jobTitle']   = $job_title;
    if ($email)       $node['email']      = $email;
    if ($telephone)   $node['telephone']  = $telephone;
    if ($birth_date)  $node['birthDate']  = $birth_date;

    // worksFor — Organization node
    $works_for = get_post_meta($post->ID, '_almaseo_person_works_for', true);
    if ($works_for) {
        $node['worksFor'] = array(
            '@type' => 'Organization',
            'name'  => $works_for,
        );
    }

    // knowsAbout — comma-separated string → string (one) or array (multiple)
    $knows_raw = get_post_meta($post->ID, '_almaseo_person_knows_about', true);
    if ($knows_raw) {
        $knows = array_values(array_filter(array_map('trim', explode(',', $knows_raw))));
        if (!empty($knows)) {
            $node['knowsAbout'] = count($knows) === 1 ? $knows[0] : $knows;
        }
    }

    // Image — explicit Person image, then fall back to featured image
    $image_url = get_post_meta($post->ID, '_almaseo_person_image', true);
    if (!$image_url && has_post_thumbnail($post->ID)) {
        $image_url = get_the_post_thumbnail_url($post->ID, 'large');
    }
    if ($image_url) {
        $node['image'] = $image_url;
    }

    // sameAs — one URL per line, validated and deduped
    $same_as_raw = get_post_meta($post->ID, '_almaseo_person_same_as', true);
    if ($same_as_raw) {
        $urls = array();
        $lines = preg_split('/\r\n|\r|\n/', $same_as_raw);
        foreach ($lines as $line) {
            $url = esc_url_raw(trim($line));
            if ($url !== '' && !in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }
        if (!empty($urls)) {
            $node['sameAs'] = $urls;
        }
    }

    // Description fallback to post excerpt or trimmed content
    $description = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words($post->post_content, 30);
    if ($description) {
        $node['description'] = $description;
    }

    return $node;
}
} // end function_exists guard: almaseo_build_person_node

/**
 * Build an Organization node for companies, NGOs, schools, non-physical orgs.
 *
 * For brick-and-mortar businesses, use LocalBusiness instead. Logo is emitted
 * as both an ImageObject (logo property) and as a flat image string.
 *
 * @param WP_Post $post
 * @return array Organization node
 */
if (!function_exists('almaseo_build_organization_node')) {
function almaseo_build_organization_node($post) {
    $node = array(
        '@type' => 'Organization',
        '@id'   => get_permalink($post->ID) . '#organization',
        'name'  => get_the_title($post->ID),
        'url'   => get_permalink($post->ID),
    );

    // Simple scalar fields
    $legal_name    = get_post_meta($post->ID, '_almaseo_org_legal_name', true);
    $founding_date = get_post_meta($post->ID, '_almaseo_org_founding_date', true);
    $industry      = get_post_meta($post->ID, '_almaseo_org_industry', true);
    $email         = get_post_meta($post->ID, '_almaseo_org_email', true);
    $telephone     = get_post_meta($post->ID, '_almaseo_org_telephone', true);
    $employees     = get_post_meta($post->ID, '_almaseo_org_employees', true);
    if ($legal_name)    $node['legalName']        = $legal_name;
    if ($founding_date) $node['foundingDate']     = $founding_date;
    if ($industry)      $node['industry']         = $industry;
    if ($email)         $node['email']            = $email;
    if ($telephone)     $node['telephone']        = $telephone;
    if ($employees !== '' && $employees !== false) {
        $node['numberOfEmployees'] = (int) $employees;
    }

    // Founder — Person node
    $founder = get_post_meta($post->ID, '_almaseo_org_founder', true);
    if ($founder) {
        $node['founder'] = array(
            '@type' => 'Person',
            'name'  => $founder,
        );
    }

    // Logo — ImageObject + flat image fallback (Google likes both)
    $logo_url = get_post_meta($post->ID, '_almaseo_org_logo', true);
    if (!$logo_url && has_post_thumbnail($post->ID)) {
        $logo_url = get_the_post_thumbnail_url($post->ID, 'large');
    }
    if ($logo_url) {
        $node['logo'] = array(
            '@type' => 'ImageObject',
            'url'   => $logo_url,
        );
        $node['image'] = $logo_url;
    }

    // sameAs — one URL per line, validated and deduped
    $same_as_raw = get_post_meta($post->ID, '_almaseo_org_same_as', true);
    if ($same_as_raw) {
        $urls = array();
        $lines = preg_split('/\r\n|\r|\n/', $same_as_raw);
        foreach ($lines as $line) {
            $url = esc_url_raw(trim($line));
            if ($url !== '' && !in_array($url, $urls, true)) {
                $urls[] = $url;
            }
        }
        if (!empty($urls)) {
            $node['sameAs'] = $urls;
        }
    }

    // Description fallback to post excerpt or trimmed content
    $description = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words($post->post_content, 30);
    if ($description) {
        $node['description'] = $description;
    }

    return $node;
}
} // end function_exists guard: almaseo_build_organization_node

/**
 * Build a Product node for e-commerce items.
 *
 * Emits an Offer node when price + currency are set. Emits an AggregateRating
 * node only when BOTH ratingValue and reviewCount are set (schema.org rejects
 * incomplete ratings). Image falls back to the post's featured image.
 *
 * @param WP_Post $post
 * @return array Product node
 */
if (!function_exists('almaseo_build_product_node')) {
function almaseo_build_product_node($post) {
    $node = array(
        '@type' => 'Product',
        '@id'   => get_permalink($post->ID) . '#product',
        'name'  => get_the_title($post->ID),
        'url'   => get_permalink($post->ID),
    );

    // Identifiers
    $sku  = get_post_meta($post->ID, '_almaseo_product_sku', true);
    $gtin = get_post_meta($post->ID, '_almaseo_product_gtin', true);
    $mpn  = get_post_meta($post->ID, '_almaseo_product_mpn', true);
    if ($sku)  $node['sku']  = $sku;
    if ($gtin) $node['gtin'] = $gtin;
    if ($mpn)  $node['mpn']  = $mpn;

    // Brand — Brand node
    $brand = get_post_meta($post->ID, '_almaseo_product_brand', true);
    if ($brand) {
        $node['brand'] = array(
            '@type' => 'Brand',
            'name'  => $brand,
        );
    }

    // Image — explicit image first, then featured-image fallback
    $image_url = get_post_meta($post->ID, '_almaseo_product_image', true);
    if (!$image_url && has_post_thumbnail($post->ID)) {
        $image_url = get_the_post_thumbnail_url($post->ID, 'large');
    }
    if ($image_url) {
        $node['image'] = $image_url;
    }

    // Offer — only emit if we have a price (Google requires price + currency for the rich result)
    $price        = get_post_meta($post->ID, '_almaseo_product_price', true);
    $currency     = get_post_meta($post->ID, '_almaseo_product_currency', true);
    $availability = get_post_meta($post->ID, '_almaseo_product_availability', true);
    $condition    = get_post_meta($post->ID, '_almaseo_product_condition', true);
    if ($price !== '' && $price !== false) {
        $offer = array(
            '@type' => 'Offer',
            'price' => (string) $price,
            'url'   => get_permalink($post->ID),
        );
        if ($currency) {
            $offer['priceCurrency'] = $currency;
        }
        if ($availability) {
            $offer['availability'] = 'https://schema.org/' . $availability;
        }
        if ($condition) {
            $offer['itemCondition'] = 'https://schema.org/' . $condition;
        }
        $node['offers'] = $offer;
    }

    // AggregateRating — both ratingValue AND reviewCount required by Google
    $rating_value = get_post_meta($post->ID, '_almaseo_product_rating_value', true);
    $review_count = get_post_meta($post->ID, '_almaseo_product_review_count', true);
    if ($rating_value !== '' && $rating_value !== false && $review_count !== '' && $review_count !== false) {
        $node['aggregateRating'] = array(
            '@type'       => 'AggregateRating',
            'ratingValue' => (string) $rating_value,
            'reviewCount' => (int) $review_count,
        );
    }

    // Description fallback to post excerpt or trimmed content
    $description = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words($post->post_content, 30);
    if ($description) {
        $node['description'] = $description;
    }

    return $node;
}
} // end function_exists guard: almaseo_build_product_node

/**
 * Build an Event node for concerts, conferences, webinars, festivals.
 *
 * Location resolves to a Place node when a physical address is set, OR a
 * VirtualLocation when only a URL is set, OR a hybrid pair (array of both)
 * when the attendance mode is MixedEventAttendanceMode and both are present.
 * Offer is only emitted when a price is set; AttendanceMode and EventStatus
 * use full schema.org URI form per Google's spec.
 *
 * @param WP_Post $post
 * @return array Event node
 */
if (!function_exists('almaseo_build_event_node')) {
function almaseo_build_event_node($post) {
    $node = array(
        '@type' => 'Event',
        '@id'   => get_permalink($post->ID) . '#event',
        'name'  => get_the_title($post->ID),
        'url'   => get_permalink($post->ID),
    );

    // Dates
    $start_date = get_post_meta($post->ID, '_almaseo_event_start_date', true);
    $end_date   = get_post_meta($post->ID, '_almaseo_event_end_date', true);
    if ($start_date) $node['startDate'] = $start_date;
    if ($end_date)   $node['endDate']   = $end_date;

    // Status + Attendance Mode (full URI form per Google's Event spec)
    $status     = get_post_meta($post->ID, '_almaseo_event_status', true);
    $attendance = get_post_meta($post->ID, '_almaseo_event_attendance_mode', true);
    if ($status)     $node['eventStatus']         = 'https://schema.org/' . $status;
    if ($attendance) $node['eventAttendanceMode'] = 'https://schema.org/' . $attendance;

    // Location — Place (physical) and/or VirtualLocation (online)
    $loc_name    = get_post_meta($post->ID, '_almaseo_event_location_name', true);
    $loc_address = get_post_meta($post->ID, '_almaseo_event_location_address', true);
    $loc_url     = get_post_meta($post->ID, '_almaseo_event_location_url', true);
    $physical = null;
    $virtual  = null;
    if ($loc_name || $loc_address) {
        $physical = array(
            '@type' => 'Place',
            'name'  => $loc_name ?: get_the_title($post->ID),
        );
        if ($loc_address) {
            $physical['address'] = $loc_address;
        }
    }
    if ($loc_url) {
        $virtual = array(
            '@type' => 'VirtualLocation',
            'url'   => $loc_url,
        );
    }
    if ($physical && $virtual) {
        $node['location'] = array($physical, $virtual);
    } elseif ($physical) {
        $node['location'] = $physical;
    } elseif ($virtual) {
        $node['location'] = $virtual;
    }

    // Performer + Organizer
    $performer = get_post_meta($post->ID, '_almaseo_event_performer', true);
    if ($performer) {
        $node['performer'] = array(
            '@type' => 'PerformingGroup',
            'name'  => $performer,
        );
    }
    $organizer = get_post_meta($post->ID, '_almaseo_event_organizer', true);
    if ($organizer) {
        $node['organizer'] = array(
            '@type' => 'Organization',
            'name'  => $organizer,
        );
    }

    // Offer (tickets) — only emit when price is set
    $price        = get_post_meta($post->ID, '_almaseo_event_ticket_price', true);
    $currency     = get_post_meta($post->ID, '_almaseo_event_ticket_currency', true);
    $ticket_url   = get_post_meta($post->ID, '_almaseo_event_ticket_url', true);
    if ($price !== '' && $price !== false) {
        $offer = array(
            '@type'         => 'Offer',
            'price'         => (string) $price,
            'availability'  => 'https://schema.org/InStock',
        );
        if ($currency)   $offer['priceCurrency'] = $currency;
        if ($ticket_url) $offer['url']           = $ticket_url;
        $node['offers'] = $offer;
    }

    // Image — explicit, then featured-image fallback
    $image_url = get_post_meta($post->ID, '_almaseo_event_image', true);
    if (!$image_url && has_post_thumbnail($post->ID)) {
        $image_url = get_the_post_thumbnail_url($post->ID, 'large');
    }
    if ($image_url) {
        $node['image'] = $image_url;
    }

    // Description fallback to post excerpt or trimmed content
    $description = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words($post->post_content, 30);
    if ($description) {
        $node['description'] = $description;
    }

    return $node;
}
} // end function_exists guard: almaseo_build_event_node

/**
 * Build a Recipe node for cooking/food posts.
 *
 * Times are stored in raw minutes and converted to ISO 8601 duration here
 * (e.g. 45 → "PT45M") because Google requires ISO 8601 for prepTime/cookTime
 * but raw minutes is much friendlier in the editor. Author defaults to the
 * post author. AggregateRating only emits when both ratingValue and
 * reviewCount are set.
 *
 * @param WP_Post $post
 * @return array Recipe node
 */
if (!function_exists('almaseo_build_recipe_node')) {
function almaseo_build_recipe_node($post) {
    $node = array(
        '@type' => 'Recipe',
        '@id'   => get_permalink($post->ID) . '#recipe',
        'name'  => get_the_title($post->ID),
        'url'   => get_permalink($post->ID),
    );

    // Author — default to the post author so the Recipe rich result has a name
    $author_id = (int) $post->post_author;
    if ($author_id) {
        $node['author'] = array(
            '@type' => 'Person',
            'name'  => get_the_author_meta('display_name', $author_id),
        );
    }

    // datePublished — ISO 8601, from the post itself
    $node['datePublished'] = mysql2date('c', $post->post_date_gmt, false);

    // Classification
    $cuisine  = get_post_meta($post->ID, '_almaseo_recipe_cuisine', true);
    $category = get_post_meta($post->ID, '_almaseo_recipe_category', true);
    if ($cuisine)  $node['recipeCuisine']  = $cuisine;
    if ($category) $node['recipeCategory'] = $category;

    // Yield + times — convert minutes to ISO 8601 duration ("PT45M")
    $yield = get_post_meta($post->ID, '_almaseo_recipe_yield', true);
    if ($yield) $node['recipeYield'] = $yield;

    $prep_min = get_post_meta($post->ID, '_almaseo_recipe_prep_minutes', true);
    $cook_min = get_post_meta($post->ID, '_almaseo_recipe_cook_minutes', true);
    if ($prep_min !== '' && $prep_min !== false && (int) $prep_min > 0) {
        $node['prepTime'] = 'PT' . (int) $prep_min . 'M';
    }
    if ($cook_min !== '' && $cook_min !== false && (int) $cook_min > 0) {
        $node['cookTime'] = 'PT' . (int) $cook_min . 'M';
    }
    if (isset($node['prepTime']) && isset($node['cookTime'])) {
        $node['totalTime'] = 'PT' . ((int) $prep_min + (int) $cook_min) . 'M';
    }

    // Ingredients — one per line → array of strings
    $ingredients_raw = get_post_meta($post->ID, '_almaseo_recipe_ingredients', true);
    if ($ingredients_raw) {
        $items = array();
        $lines = preg_split('/\r\n|\r|\n/', $ingredients_raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $items[] = $line;
            }
        }
        if (!empty($items)) {
            $node['recipeIngredient'] = $items;
        }
    }

    // Instructions — one step per line → HowToStep nodes
    $instructions_raw = get_post_meta($post->ID, '_almaseo_recipe_instructions', true);
    if ($instructions_raw) {
        $steps = array();
        $lines = preg_split('/\r\n|\r|\n/', $instructions_raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $steps[] = array(
                    '@type' => 'HowToStep',
                    'text'  => $line,
                );
            }
        }
        if (!empty($steps)) {
            $node['recipeInstructions'] = $steps;
        }
    }

    // Nutrition — calories per serving
    $calories = get_post_meta($post->ID, '_almaseo_recipe_calories', true);
    if ($calories !== '' && $calories !== false && (int) $calories > 0) {
        $node['nutrition'] = array(
            '@type'    => 'NutritionInformation',
            'calories' => (int) $calories . ' calories',
        );
    }

    // AggregateRating — both fields required
    $rating_value = get_post_meta($post->ID, '_almaseo_recipe_rating_value', true);
    $review_count = get_post_meta($post->ID, '_almaseo_recipe_review_count', true);
    if ($rating_value !== '' && $rating_value !== false && $review_count !== '' && $review_count !== false) {
        $node['aggregateRating'] = array(
            '@type'       => 'AggregateRating',
            'ratingValue' => (string) $rating_value,
            'reviewCount' => (int) $review_count,
        );
    }

    // Image — explicit, then featured-image fallback
    $image_url = get_post_meta($post->ID, '_almaseo_recipe_image', true);
    if (!$image_url && has_post_thumbnail($post->ID)) {
        $image_url = get_the_post_thumbnail_url($post->ID, 'large');
    }
    if ($image_url) {
        $node['image'] = $image_url;
    }

    // Keywords — comma-separated string passes through as-is per Google's spec
    $keywords = get_post_meta($post->ID, '_almaseo_recipe_keywords', true);
    if ($keywords) {
        $node['keywords'] = $keywords;
    }

    // Description fallback to post excerpt or trimmed content
    $description = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words($post->post_content, 30);
    if ($description) {
        $node['description'] = $description;
    }

    return $node;
}
} // end function_exists guard: almaseo_build_recipe_node
