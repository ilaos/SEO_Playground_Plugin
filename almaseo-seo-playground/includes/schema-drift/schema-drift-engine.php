<?php
/**
 * Schema Drift Monitor – Scanning Engine
 *
 * Captures schema baselines from rendered pages and detects drift
 * by comparing current schema against stored baselines.
 *
 * @package AlmaSEO
 * @since   7.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Schema_Drift_Engine {

    /**
     * Default settings.
     */
    const DEFAULT_SETTINGS = array(
        'auto_scan_on_update'    => true,
        'monitored_post_types'   => array( 'post', 'page', 'product' ),
        'scan_sample_size'       => 3,
    );

    /* ──────────────────────── Settings ── */

    /**
     * Get scan settings.
     *
     * @return array
     */
    public static function get_settings() {
        $saved = get_option( 'almaseo_sd_settings', array() );
        return wp_parse_args( $saved, self::DEFAULT_SETTINGS );
    }

    /* ──────────────────────── Schema Extraction ── */

    /**
     * Fetch a URL and extract JSON-LD schema blocks.
     *
     * @param string $url The URL to fetch.
     * @return array Array of decoded schema objects, keyed by @type.
     */
    public static function extract_schemas_from_url( $url ) {
        $response = wp_remote_get( $url, array(
            'timeout'   => 15,
            'sslverify' => false,
        ) );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            return array();
        }

        return self::parse_jsonld( $body );
    }

    /**
     * Parse JSON-LD blocks from HTML.
     *
     * @param string $html Raw HTML.
     * @return array Keyed by @type value.
     */
    public static function parse_jsonld( $html ) {
        $schemas = array();

        if ( ! preg_match_all( '/<script\s+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches ) ) {
            return $schemas;
        }

        foreach ( $matches[1] as $json_str ) {
            $decoded = json_decode( trim( $json_str ), true );
            if ( ! $decoded || ! is_array( $decoded ) ) {
                continue;
            }

            // Handle @graph arrays.
            if ( isset( $decoded['@graph'] ) && is_array( $decoded['@graph'] ) ) {
                foreach ( $decoded['@graph'] as $item ) {
                    if ( isset( $item['@type'] ) ) {
                        $type = is_array( $item['@type'] ) ? implode( '+', $item['@type'] ) : $item['@type'];
                        $schemas[ $type ] = $item;
                    }
                }
            } elseif ( isset( $decoded['@type'] ) ) {
                $type = is_array( $decoded['@type'] ) ? implode( '+', $decoded['@type'] ) : $decoded['@type'];
                $schemas[ $type ] = $decoded;
            }
        }

        return $schemas;
    }

    /* ──────────────────────── Baseline Capture ── */

    /**
     * Capture baseline schemas for a single post.
     *
     * @param int $post_id
     * @return int Number of schema types captured.
     */
    public static function capture_baseline_for_post( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_status !== 'publish' ) {
            return 0;
        }

        $url     = get_permalink( $post_id );
        $schemas = self::extract_schemas_from_url( $url );
        $count   = 0;

        foreach ( $schemas as $type => $data ) {
            AlmaSEO_Schema_Drift_Model::upsert_baseline( array(
                'post_id'     => $post_id,
                'url'         => $url,
                'schema_type' => $type,
                'schema_json' => wp_json_encode( $data ),
            ) );
            $count++;
        }

        return $count;
    }

    /**
     * Capture baselines for a sample of published posts across monitored post types.
     *
     * @return array { posts_scanned: int, schemas_captured: int }
     */
    public static function capture_all_baselines() {
        global $wpdb;

        $settings    = self::get_settings();
        $post_types  = $settings['monitored_post_types'];
        $sample_size = max( 1, (int) $settings['scan_sample_size'] );

        if ( empty( $post_types ) ) {
            $post_types = array( 'post', 'page' );
        }

        $posts_scanned    = 0;
        $schemas_captured = 0;

        foreach ( $post_types as $pt ) {
            $pt = sanitize_key( $pt );

            $post_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_status = 'publish' AND post_type = %s
                 ORDER BY RAND() LIMIT %d",
                $pt,
                $sample_size
            ) );

            foreach ( $post_ids as $pid ) {
                $captured = self::capture_baseline_for_post( (int) $pid );
                $schemas_captured += $captured;
                $posts_scanned++;
            }
        }

        update_option( 'almaseo_sd_last_baseline', current_time( 'mysql', true ) );

        return array(
            'posts_scanned'    => $posts_scanned,
            'schemas_captured' => $schemas_captured,
        );
    }

    /* ──────────────────────── Drift Scan ── */

    /**
     * Scan for drift by re-fetching pages with baselines and comparing.
     *
     * @return array { posts_scanned: int, findings_count: int }
     */
    public static function scan_for_drift() {
        $baselines = AlmaSEO_Schema_Drift_Model::get_all_baselines();

        // Group baselines by post_id.
        $by_post = array();
        foreach ( $baselines as $bl ) {
            $by_post[ $bl->post_id ][] = $bl;
        }

        // Clear existing open findings before re-scan.
        AlmaSEO_Schema_Drift_Model::clear_open_findings();

        $posts_scanned  = 0;
        $findings_count = 0;

        foreach ( $by_post as $post_id => $post_baselines ) {
            $url            = get_permalink( $post_id );
            $current_schemas = self::extract_schemas_from_url( $url );
            $posts_scanned++;

            // Check each baseline against current.
            $baseline_types = array();
            foreach ( $post_baselines as $bl ) {
                $baseline_types[] = $bl->schema_type;
                $baseline_data    = json_decode( $bl->schema_json, true );

                if ( ! isset( $current_schemas[ $bl->schema_type ] ) ) {
                    // Schema removed.
                    $finding = array(
                        'post_id'        => $post_id,
                        'url'            => $url,
                        'drift_type'     => 'schema_removed',
                        'schema_type'    => $bl->schema_type,
                        'severity'       => 'high',
                        'baseline_value' => $bl->schema_json,
                        'current_value'  => null,
                        'diff_summary'   => $bl->schema_type . ' schema was present in baseline but is now missing.',
                        'suggestion'     => 'The ' . $bl->schema_type . ' schema has been removed. Check if a plugin update or theme change removed it.',
                    );
                    if ( AlmaSEO_Schema_Drift_Model::insert_finding( $finding ) ) {
                        $findings_count++;
                    }
                    continue;
                }

                // Schema exists — check for modifications.
                $current_data = $current_schemas[ $bl->schema_type ];
                $diff = self::compare_schemas( $baseline_data, $current_data );

                if ( ! empty( $diff ) ) {
                    $finding = array(
                        'post_id'        => $post_id,
                        'url'            => $url,
                        'drift_type'     => 'schema_modified',
                        'schema_type'    => $bl->schema_type,
                        'severity'       => 'medium',
                        'baseline_value' => $bl->schema_json,
                        'current_value'  => wp_json_encode( $current_data ),
                        'diff_summary'   => implode( '; ', array_slice( $diff, 0, 5 ) ),
                        'suggestion'     => 'The ' . $bl->schema_type . ' schema has been modified. Review the changes to ensure they are intentional.',
                    );
                    if ( AlmaSEO_Schema_Drift_Model::insert_finding( $finding ) ) {
                        $findings_count++;
                    }
                }
            }

            // Check for new schemas not in baseline.
            foreach ( $current_schemas as $type => $data ) {
                if ( ! in_array( $type, $baseline_types, true ) ) {
                    $finding = array(
                        'post_id'        => $post_id,
                        'url'            => $url,
                        'drift_type'     => 'schema_added',
                        'schema_type'    => $type,
                        'severity'       => 'low',
                        'baseline_value' => null,
                        'current_value'  => wp_json_encode( $data ),
                        'diff_summary'   => $type . ' schema was not in the baseline but is now present.',
                        'suggestion'     => 'A new ' . $type . ' schema has appeared. Verify it was intentionally added.',
                    );
                    if ( AlmaSEO_Schema_Drift_Model::insert_finding( $finding ) ) {
                        $findings_count++;
                    }
                }
            }

            // Check for JSON-LD parse errors (empty response but had baselines).
            if ( empty( $current_schemas ) && ! empty( $post_baselines ) ) {
                $post = get_post( $post_id );
                if ( $post && $post->post_status === 'publish' ) {
                    $finding = array(
                        'post_id'      => $post_id,
                        'url'          => $url,
                        'drift_type'   => 'schema_error',
                        'schema_type'  => '',
                        'severity'     => 'medium',
                        'diff_summary' => 'No JSON-LD schema found on page that previously had ' . count( $post_baselines ) . ' schema type(s).',
                        'suggestion'   => 'The page returned no JSON-LD. This could indicate a rendering error or plugin conflict.',
                    );
                    if ( AlmaSEO_Schema_Drift_Model::insert_finding( $finding ) ) {
                        $findings_count++;
                    }
                }
            }
        }

        update_option( 'almaseo_sd_last_scan', current_time( 'mysql', true ) );

        return array(
            'posts_scanned'  => $posts_scanned,
            'findings_count' => $findings_count,
        );
    }

    /* ──────────────────────── Schema Comparison ── */

    /**
     * Compare two decoded schema arrays and return a list of differences.
     *
     * @param array $baseline The baseline schema data.
     * @param array $current  The current schema data.
     * @param string $path    Dot-notation path (for recursion).
     * @return array List of human-readable difference descriptions.
     */
    public static function compare_schemas( $baseline, $current, $path = '' ) {
        $diffs = array();

        if ( ! is_array( $baseline ) || ! is_array( $current ) ) {
            if ( $baseline !== $current ) {
                $diffs[] = ( $path ? $path : 'value' ) . ' changed';
            }
            return $diffs;
        }

        // Keys in baseline but not in current.
        foreach ( $baseline as $key => $val ) {
            $key_path = $path ? $path . '.' . $key : $key;
            if ( ! array_key_exists( $key, $current ) ) {
                $diffs[] = $key_path . ' removed';
            } elseif ( is_array( $val ) ) {
                $diffs = array_merge( $diffs, self::compare_schemas( $val, $current[ $key ], $key_path ) );
            } elseif ( (string) $val !== (string) $current[ $key ] ) {
                $diffs[] = $key_path . ' changed';
            }
        }

        // Keys in current but not in baseline.
        foreach ( $current as $key => $val ) {
            $key_path = $path ? $path . '.' . $key : $key;
            if ( ! array_key_exists( $key, $baseline ) ) {
                $diffs[] = $key_path . ' added';
            }
        }

        return $diffs;
    }

    /* ──────────────────────── Auto-Scan Triggers ── */

    /**
     * Schedule an auto-scan 30 seconds after a plugin/theme update.
     */
    public static function schedule_auto_scan() {
        $settings = self::get_settings();
        if ( empty( $settings['auto_scan_on_update'] ) ) {
            return;
        }

        if ( ! wp_next_scheduled( 'almaseo_schema_drift_auto_scan' ) ) {
            wp_schedule_single_event( time() + 30, 'almaseo_schema_drift_auto_scan' );
        }
    }

    /**
     * Run the auto-scan (called by cron event).
     */
    public static function run_auto_scan() {
        // Only run if baselines exist.
        if ( AlmaSEO_Schema_Drift_Model::count_baselines() === 0 ) {
            return;
        }

        // Check Pro gate.
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'schema_drift' ) ) {
            return;
        }

        self::scan_for_drift();
    }
}
