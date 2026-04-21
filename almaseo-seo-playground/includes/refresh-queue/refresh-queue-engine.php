<?php
/**
 * Refresh Queue – Scoring Engine
 *
 * Calculates priority scores for content refresh using four signals:
 * business value, traffic decline, conversion intent, opportunity size.
 *
 * Hybrid approach: calculates locally using evergreen/health data,
 * with optional dashboard-pushed overrides stored in post meta.
 *
 * @package AlmaSEO
 * @since   7.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Refresh_Queue_Engine {

    /**
     * Default signal weights (must sum to 100).
     */
    const DEFAULT_WEIGHTS = array(
        'business_value'    => 25,
        'traffic_decline'   => 30,
        'conversion_intent' => 20,
        'opportunity_size'  => 25,
    );

    /* ──────────────────────── Settings ── */

    /**
     * Get configured weights (or defaults).
     *
     * @return array
     */
    public static function get_weights() {
        $saved = get_option( 'almaseo_rq_settings', array() );
        $weights = wp_parse_args( $saved, self::DEFAULT_WEIGHTS );

        // Normalize so they sum to 100.
        $total = array_sum( $weights );
        if ( $total <= 0 ) {
            return self::DEFAULT_WEIGHTS;
        }

        foreach ( $weights as $key => &$val ) {
            $val = round( ( $val / $total ) * 100, 2 );
        }

        return $weights;
    }

    /* ──────────────────────── Signal: Business Value ── */

    /**
     * Calculate business value score (0–100).
     *
     * Dashboard override: _almaseo_rq_business_value post meta.
     * Local fallback: post type + schema type bonus.
     *
     * @param int $post_id
     * @return float
     */
    public static function calculate_business_value( $post_id ) {
        // Dashboard override.
        $pushed = get_post_meta( $post_id, '_almaseo_rq_business_value', true );
        if ( $pushed !== '' && $pushed !== false ) {
            return min( 100, max( 0, (float) $pushed ) );
        }

        // Local fallback: post type.
        $post_type = get_post_type( $post_id );
        $type_scores = array(
            'product'    => 80,
            'page'       => 60,
            'post'       => 40,
        );
        $score = isset( $type_scores[ $post_type ] ) ? $type_scores[ $post_type ] : 30;

        // Schema type bonus.
        $schema = get_post_meta( $post_id, '_almaseo_schema_type', true );
        if ( $schema && in_array( $schema, array( 'Product', 'FAQ', 'LocalBusiness', 'Service' ), true ) ) {
            $score += 15;
        }

        return min( 100, (float) $score );
    }

    /* ──────────────────────── Signal: Traffic Decline ── */

    /**
     * Calculate traffic decline score (0–100).
     *
     * Higher score = more severe decline = higher refresh priority.
     * Reuses evergreen traffic data from GSC.
     *
     * @param int $post_id
     * @return float
     */
    public static function calculate_traffic_decline( $post_id ) {
        // Dashboard override.
        $pushed = get_post_meta( $post_id, '_almaseo_rq_traffic_decline', true );
        if ( $pushed !== '' && $pushed !== false ) {
            return min( 100, max( 0, (float) $pushed ) );
        }

        // Local fallback: evergreen traffic data.
        if ( ! function_exists( 'almaseo_eg_get_clicks' ) || ! function_exists( 'almaseo_compute_trend' ) ) {
            return 50; // Neutral when no traffic data available.
        }

        $clicks = almaseo_eg_get_clicks( $post_id );

        if ( $clicks['clicks_90d'] <= 0 && $clicks['clicks_prev90d'] <= 0 ) {
            return 50; // No data — neutral.
        }

        $trend = almaseo_compute_trend( $clicks['clicks_90d'], $clicks['clicks_prev90d'] );

        // Same mapping as evergreen scoring.php:535-547.
        if ( $trend < -50 ) {
            return 100; // Severe decline.
        } elseif ( $trend < -30 ) {
            return 80;
        } elseif ( $trend < -15 ) {
            return 60;
        } elseif ( $trend < 0 ) {
            return 40;
        } elseif ( $trend < 15 ) {
            return 20; // Stable.
        }

        return 0; // Growing.
    }

    /* ──────────────────────── Signal: Conversion Intent ── */

    /**
     * Calculate conversion intent score (0–100).
     *
     * @param int $post_id
     * @return float
     */
    public static function calculate_conversion_intent( $post_id ) {
        // Dashboard override.
        $pushed = get_post_meta( $post_id, '_almaseo_rq_conversion_intent', true );
        if ( $pushed !== '' && $pushed !== false ) {
            return min( 100, max( 0, (float) $pushed ) );
        }

        // Local fallback: post type.
        $post_type = get_post_type( $post_id );
        $type_scores = array(
            'product' => 90,
            'page'    => 50,
            'post'    => 30,
        );
        $score = isset( $type_scores[ $post_type ] ) ? $type_scores[ $post_type ] : 25;

        // URL path bonus for conversion-oriented slugs.
        $permalink = get_permalink( $post_id );
        if ( $permalink ) {
            $path = wp_parse_url( $permalink, PHP_URL_PATH );
            if ( $path && preg_match( '/\b(pricing|buy|shop|checkout|contact|demo|quote|order|subscribe)\b/i', $path ) ) {
                $score += 20;
            }
        }

        return min( 100, (float) $score );
    }

    /* ──────────────────────── Signal: Opportunity Size ── */

    /**
     * Calculate opportunity size score (0–100).
     *
     * Higher score = bigger potential gain from refreshing.
     *
     * @param int $post_id
     * @return float
     */
    public static function calculate_opportunity_size( $post_id ) {
        // Dashboard override.
        $pushed = get_post_meta( $post_id, '_almaseo_rq_opportunity_size', true );
        if ( $pushed !== '' && $pushed !== false ) {
            return min( 100, max( 0, (float) $pushed ) );
        }

        $score = 0;

        // Inverted health score: low health = high opportunity.
        $health = get_post_meta( $post_id, '_almaseo_health_score', true );
        if ( $health !== '' && $health !== false ) {
            $score += max( 0, 100 - (int) $health );
        } else {
            $score += 50; // No health data — neutral.
        }

        // Evergreen status bonus.
        $eg_status = get_post_meta( $post_id, '_almaseo_eg_status', true );
        if ( $eg_status === 'stale' ) {
            $score += 20;
        } elseif ( $eg_status === 'watch' ) {
            $score += 10;
        }

        // Age bonus: older content has more room for improvement.
        if ( function_exists( 'almaseo_get_post_ages' ) ) {
            $ages = almaseo_get_post_ages( $post_id );
            if ( $ages['updated_days'] > 365 ) {
                $score += 15;
            } elseif ( $ages['updated_days'] > 180 ) {
                $score += 8;
            }
        }

        return min( 100, (float) $score );
    }

    /* ──────────────────────── Composite Priority ── */

    /**
     * Calculate full priority for a post.
     *
     * @param int $post_id
     * @return array Score breakdown ready for model upsert.
     */
    public static function calculate_priority( $post_id ) {
        $bv = self::calculate_business_value( $post_id );
        $td = self::calculate_traffic_decline( $post_id );
        $ci = self::calculate_conversion_intent( $post_id );
        $os = self::calculate_opportunity_size( $post_id );

        $weights = self::get_weights();

        $priority = (
            ( $bv * $weights['business_value'] / 100 ) +
            ( $td * $weights['traffic_decline'] / 100 ) +
            ( $ci * $weights['conversion_intent'] / 100 ) +
            ( $os * $weights['opportunity_size'] / 100 )
        );

        $priority = round( min( 100, max( 0, $priority ) ), 2 );

        // Determine tier.
        if ( $priority >= 70 ) {
            $tier = 'high';
        } elseif ( $priority >= 50 ) {
            $tier = 'medium';
        } else {
            $tier = 'low';
        }

        // Reason: identify the top contributing signal.
        $signals = array(
            'business_value'    => $bv,
            'traffic_decline'   => $td,
            'conversion_intent' => $ci,
            'opportunity_size'  => $os,
        );
        arsort( $signals );
        $top_signal = key( $signals );

        $reason_labels = array(
            'business_value'    => 'High business value',
            'traffic_decline'   => 'Traffic declining',
            'conversion_intent' => 'High conversion potential',
            'opportunity_size'  => 'Large improvement opportunity',
        );
        $reason = isset( $reason_labels[ $top_signal ] ) ? $reason_labels[ $top_signal ] : '';

        return array(
            'post_id'           => $post_id,
            'priority_score'    => $priority,
            'business_value'    => $bv,
            'traffic_decline'   => $td,
            'conversion_intent' => $ci,
            'opportunity_size'  => $os,
            'priority_tier'     => $tier,
            'reason'            => $reason,
            'source'            => 'auto',
        );
    }

    /* ──────────────────────── Batch Recalculation ── */

    /**
     * Recalculate all published posts.
     *
     * Processes in batches to avoid memory issues.
     * Preserves 'skipped' status for existing entries.
     *
     * @return int Number of posts scored.
     */
    public static function recalculate_all() {
        global $wpdb;

        $excluded_types = array( 'attachment', 'revision', 'nav_menu_item', 'wp_template', 'wp_template_part', 'wp_navigation' );
        $placeholders   = implode( ', ', array_fill( 0, count( $excluded_types ), '%s' ) );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type NOT IN ({$placeholders})",
            $excluded_types
        ) );

        $batch_size = 50;
        $scored     = 0;

        for ( $offset = 0; $offset < $total; $offset += $batch_size ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
            $post_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type NOT IN ({$placeholders}) ORDER BY ID LIMIT %d OFFSET %d",
                array_merge( $excluded_types, array( $batch_size, $offset ) )
            ) );

            foreach ( $post_ids as $pid ) {
                $data = self::calculate_priority( (int) $pid );
                AlmaSEO_Refresh_Queue_Model::upsert( $data );
                $scored++;
            }
        }

        // Prune orphaned entries (deleted/trashed posts).
        AlmaSEO_Refresh_Queue_Model::prune_orphaned();

        return $scored;
    }

    /**
     * Recalculate a single post.
     *
     * @param int $post_id
     * @return array|false Score breakdown or false on failure.
     */
    public static function recalculate_single( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_status !== 'publish' ) {
            return false;
        }

        $data = self::calculate_priority( $post_id );
        AlmaSEO_Refresh_Queue_Model::upsert( $data );
        return $data;
    }
}
