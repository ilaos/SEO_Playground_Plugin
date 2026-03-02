<?php
/**
 * AlmaSEO Internal Links Model - CRUD Operations
 *
 * Handles all database operations for internal link rules.
 *
 * @package AlmaSEO
 * @subpackage InternalLinks
 * @since 6.6.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Internal_Links_Model {

    /**
     * Get table name
     *
     * @return string
     */
    private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'almaseo_internal_links';
    }

    /**
     * Get all link rules with optional filters
     *
     * @param array $args Query arguments.
     * @return array {
     *     @type array  $items  Array of link rule rows.
     *     @type int    $total  Total matching rows.
     *     @type int    $pages  Total pages.
     * }
     */
    public static function get_links( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'per_page'   => 20,
            'page'       => 1,
            'orderby'    => 'priority',
            'order'      => 'ASC',
            'search'     => '',
            'is_enabled' => null,
        );

        $args  = wp_parse_args( $args, $defaults );
        $table = self::get_table_name();

        // Build WHERE clause
        $where          = array( '1=1' );
        $prepare_values = array();

        if ( ! empty( $args['search'] ) ) {
            $where[]          = '(keyword LIKE %s OR target_url LIKE %s)';
            $search_term      = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        if ( $args['is_enabled'] !== null ) {
            $where[]          = 'is_enabled = %d';
            $prepare_values[] = intval( $args['is_enabled'] );
        }

        $where_clause = implode( ' AND ', $where );

        // Get total count
        $count_query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
        if ( ! empty( $prepare_values ) ) {
            $count_query = $wpdb->prepare( $count_query, $prepare_values );
        }
        $total = $wpdb->get_var( $count_query );

        // Build main query
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        // Whitelist orderby column and direction
        $allowed_orderby = array( 'id', 'keyword', 'target_url', 'priority', 'hits', 'is_enabled', 'created_at', 'updated_at' );
        $allowed_order   = array( 'ASC', 'DESC' );
        $orderby_col     = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'priority';
        $order_dir       = in_array( strtoupper( $args['order'] ), $allowed_order, true ) ? strtoupper( $args['order'] ) : 'ASC';
        $orderby         = "$orderby_col $order_dir";

        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";

        $prepare_values[] = intval( $args['per_page'] );
        $prepare_values[] = intval( $offset );

        if ( ! empty( $prepare_values ) ) {
            $query = $wpdb->prepare( $query, $prepare_values );
        }

        $results = $wpdb->get_results( $query, ARRAY_A );

        return array(
            'items' => $results ? $results : array(),
            'total' => (int) $total,
            'pages' => $args['per_page'] > 0 ? (int) ceil( $total / $args['per_page'] ) : 1,
        );
    }

    /**
     * Get a single link rule by ID
     *
     * @param int $id Link rule ID.
     * @return array|null
     */
    public static function get_link( $id ) {
        global $wpdb;

        $table = self::get_table_name();
        $query = $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id );

        return $wpdb->get_row( $query, ARRAY_A );
    }

    /**
     * Get all enabled link rules (for front-end engine)
     *
     * Results are ordered by priority (lower = higher priority) so that
     * more important rules are applied first.
     *
     * @return array
     */
    public static function get_enabled_links() {
        global $wpdb;

        // Try cache first
        $cached = get_transient( 'almaseo_internal_links_cache' );
        if ( false !== $cached ) {
            return $cached;
        }

        $table   = self::get_table_name();
        $results = $wpdb->get_results(
            "SELECT * FROM $table WHERE is_enabled = 1 ORDER BY priority ASC, id ASC LIMIT 500",
            ARRAY_A
        );

        if ( ! $results ) {
            $results = array();
        }

        // Cache for 5 minutes
        set_transient( 'almaseo_internal_links_cache', $results, 5 * MINUTE_IN_SECONDS );

        return $results;
    }

    /**
     * Create a new link rule
     *
     * @param array $data Link rule data.
     * @return int|false Insert ID or false on failure.
     */
    public static function create_link( $data ) {
        global $wpdb;

        $table = self::get_table_name();

        $insert_data = array(
            'keyword'        => sanitize_text_field( $data['keyword'] ),
            'target_url'     => esc_url_raw( $data['target_url'] ),
            'target_post_id' => isset( $data['target_post_id'] ) ? absint( $data['target_post_id'] ) : null,
            'match_type'     => isset( $data['match_type'] ) && in_array( $data['match_type'], array( 'exact', 'partial', 'regex' ), true ) ? $data['match_type'] : 'exact',
            'case_sensitive' => isset( $data['case_sensitive'] ) ? intval( (bool) $data['case_sensitive'] ) : 0,
            'max_per_post'   => isset( $data['max_per_post'] ) ? absint( $data['max_per_post'] ) : 1,
            'max_per_page'   => isset( $data['max_per_page'] ) ? absint( $data['max_per_page'] ) : 3,
            'nofollow'       => isset( $data['nofollow'] ) ? intval( (bool) $data['nofollow'] ) : 0,
            'new_tab'        => isset( $data['new_tab'] ) ? intval( (bool) $data['new_tab'] ) : 0,
            'is_enabled'     => isset( $data['is_enabled'] ) ? intval( (bool) $data['is_enabled'] ) : 1,
            'post_types'     => isset( $data['post_types'] ) ? sanitize_text_field( $data['post_types'] ) : 'post,page',
            'exclude_ids'    => isset( $data['exclude_ids'] ) ? sanitize_text_field( $data['exclude_ids'] ) : null,
            'priority'       => isset( $data['priority'] ) ? absint( $data['priority'] ) : 10,
            'hits'           => 0,
            'created_at'     => current_time( 'mysql' ),
            'updated_at'     => current_time( 'mysql' ),
        );

        $format = array( '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s' );

        $result = $wpdb->insert( $table, $insert_data, $format );

        if ( $result ) {
            self::clear_cache();
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update a link rule
     *
     * @param int   $id   Link rule ID.
     * @param array $data Fields to update.
     * @return bool
     */
    public static function update_link( $id, $data ) {
        global $wpdb;

        $table       = self::get_table_name();
        $update_data = array();
        $format      = array();

        $field_map = array(
            'keyword'        => '%s',
            'target_url'     => '%s',
            'target_post_id' => '%d',
            'match_type'     => '%s',
            'case_sensitive' => '%d',
            'max_per_post'   => '%d',
            'max_per_page'   => '%d',
            'nofollow'       => '%d',
            'new_tab'        => '%d',
            'is_enabled'     => '%d',
            'post_types'     => '%s',
            'exclude_ids'    => '%s',
            'priority'       => '%d',
        );

        foreach ( $field_map as $field => $fmt ) {
            if ( isset( $data[ $field ] ) ) {
                if ( $field === 'keyword' ) {
                    $update_data[ $field ] = sanitize_text_field( $data[ $field ] );
                } elseif ( $field === 'target_url' ) {
                    $update_data[ $field ] = esc_url_raw( $data[ $field ] );
                } elseif ( $field === 'match_type' ) {
                    $update_data[ $field ] = in_array( $data[ $field ], array( 'exact', 'partial', 'regex' ), true ) ? $data[ $field ] : 'exact';
                } else {
                    $update_data[ $field ] = $data[ $field ];
                }
                $format[] = $fmt;
            }
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        // Always update timestamp
        $update_data['updated_at'] = current_time( 'mysql' );
        $format[]                  = '%s';

        $result = $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $id ),
            $format,
            array( '%d' )
        );

        if ( false !== $result ) {
            self::clear_cache();
            return true;
        }

        return false;
    }

    /**
     * Delete a link rule
     *
     * @param int $id Link rule ID.
     * @return bool
     */
    public static function delete_link( $id ) {
        global $wpdb;

        $table  = self::get_table_name();
        $result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

        if ( $result ) {
            self::clear_cache();
            return true;
        }

        return false;
    }

    /**
     * Toggle link rule enabled status
     *
     * @param int $id Link rule ID.
     * @return bool
     */
    public static function toggle_link( $id ) {
        $link = self::get_link( $id );
        if ( ! $link ) {
            return false;
        }

        $new_status = $link['is_enabled'] ? 0 : 1;
        return self::update_link( $id, array( 'is_enabled' => $new_status ) );
    }

    /**
     * Increment the hit counter for a link rule
     *
     * @param int $id Link rule ID.
     * @return bool
     */
    public static function record_hit( $id ) {
        global $wpdb;

        $table = self::get_table_name();
        $query = $wpdb->prepare(
            "UPDATE $table SET hits = hits + 1 WHERE id = %d",
            $id
        );

        return $wpdb->query( $query ) !== false;
    }

    /**
     * Get summary statistics
     *
     * @return array {
     *     @type int $total_rules   Total link rules.
     *     @type int $active_rules  Enabled rules.
     *     @type int $total_hits    Sum of all hits.
     *     @type int $unique_targets Number of distinct target URLs.
     * }
     */
    public static function get_stats() {
        global $wpdb;

        $table = self::get_table_name();

        // Check if table exists first
        $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        if ( ! $table_exists ) {
            return array(
                'total_rules'    => 0,
                'active_rules'   => 0,
                'total_hits'     => 0,
                'unique_targets' => 0,
            );
        }

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*)                       AS total_rules,
                SUM( CASE WHEN is_enabled = 1 THEN 1 ELSE 0 END ) AS active_rules,
                COALESCE( SUM( hits ), 0 )     AS total_hits,
                COUNT( DISTINCT target_url )   AS unique_targets
            FROM $table",
            ARRAY_A
        );

        return $stats ? $stats : array(
            'total_rules'    => 0,
            'active_rules'   => 0,
            'total_hits'     => 0,
            'unique_targets' => 0,
        );
    }

    /**
     * Check if a keyword already exists (for duplicate prevention)
     *
     * @param string   $keyword    Keyword to check.
     * @param int|null $exclude_id Optional ID to exclude from check.
     * @return bool
     */
    public static function keyword_exists( $keyword, $exclude_id = null ) {
        global $wpdb;

        $table = self::get_table_name();

        if ( $exclude_id ) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE keyword = %s AND id != %d",
                $keyword,
                $exclude_id
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE keyword = %s",
                $keyword
            );
        }

        return $wpdb->get_var( $query ) > 0;
    }

    /**
     * Clear the link rules cache
     */
    public static function clear_cache() {
        delete_transient( 'almaseo_internal_links_cache' );
    }
}
