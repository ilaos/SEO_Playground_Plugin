<?php
/**
 * GSC Monitor – Engine
 *
 * Handles validation, dedup upsert logic, auto-dismiss, and labels.
 * No local scanning — all data comes from the AlmaSEO dashboard.
 *
 * @package AlmaSEO
 * @since   7.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_GSC_Monitor_Engine {

    const DEFAULT_SETTINGS = array(
        'alert_threshold_indexation' => 5,
        'alert_threshold_snippet'   => 100,
        'auto_dismiss_days'         => 0,
    );

    const VALID_TYPES = array(
        'indexation_drift' => array( 'not_indexed', 'excluded_spike', 'coverage_drop' ),
        'rich_result_loss' => array( 'lost', 'gained', 'degraded' ),
        'snippet_rewrite'  => array( 'title_rewrite', 'description_rewrite' ),
    );

    /* ──────────────────────── Settings ── */

    public static function get_settings() {
        $saved = get_option( 'almaseo_gsc_settings', array() );
        return wp_parse_args( $saved, self::DEFAULT_SETTINGS );
    }

    /* ──────────────────────── Validation ── */

    /**
     * Validate a single finding from a push payload.
     *
     * @param array $entry Raw finding data.
     * @return array|false Sanitized data or false on validation failure.
     */
    public static function validate_finding( $entry ) {
        if ( empty( $entry['url'] ) || empty( $entry['finding_type'] ) || empty( $entry['subtype'] ) ) {
            return false;
        }

        $type    = sanitize_key( $entry['finding_type'] );
        $subtype = sanitize_key( $entry['subtype'] );

        // Validate type/subtype.
        if ( ! isset( self::VALID_TYPES[ $type ] ) ) {
            return false;
        }
        if ( ! in_array( $subtype, self::VALID_TYPES[ $type ], true ) ) {
            return false;
        }

        // Resolve post_id if not provided.
        $post_id = ! empty( $entry['post_id'] ) ? absint( $entry['post_id'] ) : null;
        if ( ! $post_id ) {
            $post_id = self::resolve_post_url( $entry['url'] );
        }

        return array(
            'url'            => esc_url_raw( $entry['url'] ),
            'post_id'        => $post_id,
            'finding_type'   => $type,
            'subtype'        => $subtype,
            'severity'       => isset( $entry['severity'] ) ? sanitize_key( $entry['severity'] ) : 'medium',
            'detected_value' => isset( $entry['detected_value'] ) ? wp_kses_post( $entry['detected_value'] ) : '',
            'expected_value' => isset( $entry['expected_value'] ) ? wp_kses_post( $entry['expected_value'] ) : null,
            'context_data'   => isset( $entry['context_data'] ) ? $entry['context_data'] : null,
            'suggestion'     => isset( $entry['suggestion'] ) ? sanitize_text_field( $entry['suggestion'] ) : null,
        );
    }

    /* ──────────────────────── Dedup Upsert ── */

    /**
     * Insert or update a finding with dedup logic.
     *
     * If an open match exists → update last_seen + detected_value.
     * If resolved/dismissed match → create new.
     * If no match → insert new.
     *
     * @param array $data Validated finding data.
     * @return string 'inserted', 'updated', or 'skipped'.
     */
    public static function upsert_finding( $data ) {
        $existing = AlmaSEO_GSC_Monitor_Model::find_existing(
            $data['url'],
            $data['finding_type'],
            $data['subtype']
        );

        if ( $existing ) {
            // Update existing open finding.
            $update = array(
                'last_seen'      => current_time( 'mysql', true ),
                'detected_value' => $data['detected_value'],
                'severity'       => $data['severity'],
            );
            if ( $data['expected_value'] !== null ) {
                $update['expected_value'] = $data['expected_value'];
            }
            if ( $data['context_data'] !== null ) {
                $update['context_data'] = wp_json_encode( $data['context_data'] );
            }

            AlmaSEO_GSC_Monitor_Model::update_finding( $existing->id, $update );
            return 'updated';
        }

        // No open match — insert new.
        if ( AlmaSEO_GSC_Monitor_Model::insert_finding( $data ) ) {
            return 'inserted';
        }

        return 'skipped';
    }

    /* ──────────────────────── Batch Push ── */

    /**
     * Process a batch of findings from dashboard push.
     *
     * @param array $findings Array of raw finding entries.
     * @return array { inserted: int, updated: int, skipped: int }
     */
    public static function process_push_batch( $findings ) {
        $counts = array( 'inserted' => 0, 'updated' => 0, 'skipped' => 0 );

        foreach ( $findings as $entry ) {
            $validated = self::validate_finding( $entry );
            if ( ! $validated ) {
                $counts['skipped']++;
                continue;
            }

            $result = self::upsert_finding( $validated );
            $counts[ $result ]++;
        }

        return $counts;
    }

    /* ──────────────────────── Auto-dismiss ── */

    /**
     * Run auto-dismiss if configured.
     */
    public static function maybe_auto_dismiss() {
        $settings = self::get_settings();
        $days     = (int) $settings['auto_dismiss_days'];

        if ( $days > 0 ) {
            AlmaSEO_GSC_Monitor_Model::auto_dismiss_old( $days );
        }
    }

    /* ──────────────────────── URL Resolution ── */

    /**
     * Attempt to find a WordPress post_id for a URL.
     *
     * @param string $url
     * @return int|null
     */
    public static function resolve_post_url( $url ) {
        $post_id = url_to_postid( $url );
        return $post_id > 0 ? $post_id : null;
    }

    /* ──────────────────────── Labels ── */

    /**
     * Human-readable finding type labels.
     */
    public static function get_type_label( $finding_type ) {
        $labels = array(
            'indexation_drift' => 'Indexation Drift',
            'rich_result_loss' => 'Rich Results',
            'snippet_rewrite'  => 'Snippet Rewrite',
        );
        return isset( $labels[ $finding_type ] ) ? $labels[ $finding_type ] : $finding_type;
    }

    /**
     * Human-readable subtype labels.
     */
    public static function get_subtype_label( $subtype ) {
        $labels = array(
            'not_indexed'         => 'Not Indexed',
            'excluded_spike'      => 'Excluded Spike',
            'coverage_drop'       => 'Coverage Drop',
            'lost'                => 'Lost',
            'gained'              => 'Gained',
            'degraded'            => 'Degraded',
            'title_rewrite'       => 'Title Rewrite',
            'description_rewrite' => 'Description Rewrite',
        );
        return isset( $labels[ $subtype ] ) ? $labels[ $subtype ] : $subtype;
    }
}
