<?php
/**
 * AlmaSEO Import Engine
 *
 * Batch-processes SEO data import from competitor plugins.
 * Each batch processes BATCH_SIZE posts to avoid timeouts.
 *
 * @package AlmaSEO
 * @since   8.1.0
 * @updated 8.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_Engine {

    const BATCH_SIZE = 50;

    /**
     * Process one batch of imports.
     *
     * @param string $source    'yoast', 'rankmath', or 'aioseo'.
     * @param int    $offset    Current offset.
     * @param bool   $overwrite Whether to overwrite existing AlmaSEO data.
     * @return array|WP_Error Result with keys: processed, imported, skipped, offset, done.
     */
    public static function process_batch( $source, $offset = 0, $overwrite = false ) {
        $mapper = self::get_mapper( $source );
        if ( ! $mapper ) {
            return new WP_Error( 'invalid_source', __( 'Unknown import source.', 'almaseo' ) );
        }

        $rows = $mapper::get_batch( $offset, self::BATCH_SIZE );

        $imported  = 0;
        $skipped   = 0;
        $processed = 0;

        // Suspend third-party SEO plugin hooks during import to prevent
        // fatal errors (e.g., AIOSEO schema rebuild crash on update_post_meta).
        self::suspend_seo_hooks();

        foreach ( $rows as $row ) {
            $post_id = (int) $row['post_id'];
            $mapped  = $mapper::map_row( $row );
            $processed++;

            foreach ( $mapped as $meta_key => $meta_value ) {
                if ( empty( $meta_value ) ) {
                    continue;
                }

                // Check if AlmaSEO already has data for this key.
                if ( ! $overwrite ) {
                    $existing = get_post_meta( $post_id, $meta_key, true );
                    if ( ! empty( $existing ) ) {
                        $skipped++;
                        continue;
                    }
                }

                update_post_meta( $post_id, $meta_key, $meta_value );
                $imported++;
            }
        }

        // Restore hooks after import batch completes.
        self::restore_seo_hooks();

        $done = count( $rows ) < self::BATCH_SIZE;

        // Accumulate batch totals in a transient so the final save is accurate.
        $status = get_option( 'almaseo_import_status', array() );
        $prev   = ( $offset === 0 ) ? array() : ( isset( $status['posts'] ) ? $status['posts'] : array() );
        $running_imported = ( isset( $prev['_running_imported'] ) ? $prev['_running_imported'] : 0 ) + $imported;
        $running_skipped  = ( isset( $prev['_running_skipped'] ) ? $prev['_running_skipped'] : 0 ) + $skipped;

        if ( $done ) {
            $status['posts'] = array(
                'completed' => true,
                'source'    => $source,
                'imported'  => $running_imported,
                'skipped'   => $running_skipped,
                'date'      => current_time( 'mysql' ),
            );
        } else {
            // Store running totals (not yet completed).
            $status['posts'] = array(
                'completed'         => false,
                'source'            => $source,
                '_running_imported' => $running_imported,
                '_running_skipped'  => $running_skipped,
            );
        }
        update_option( 'almaseo_import_status', $status, false );

        return array(
            'processed' => $processed,
            'imported'  => $imported,
            'skipped'   => $skipped,
            'offset'    => $offset + $processed,
            'done'      => $done,
        );
    }

    /**
     * Get a preview of what would be imported.
     *
     * @param string $source Source slug.
     * @param int    $limit  Max records to preview.
     * @return array Preview data.
     */
    public static function preview( $source, $limit = 5 ) {
        $mapper = self::get_mapper( $source );
        if ( ! $mapper ) {
            return array();
        }

        $rows    = $mapper::get_batch( 0, $limit );
        $preview = array();

        foreach ( $rows as $row ) {
            $post_id = (int) $row['post_id'];
            $mapped  = $mapper::map_row( $row );
            $post    = get_post( $post_id );

            $preview[] = array(
                'post_id'          => $post_id,
                'post_title'       => $post ? $post->post_title : '(deleted)',
                'source_title'     => isset( $mapped['_almaseo_title'] ) ? $mapped['_almaseo_title'] : '',
                'source_desc'      => isset( $mapped['_almaseo_description'] ) ? $mapped['_almaseo_description'] : '',
                'current_title'    => get_post_meta( $post_id, '_almaseo_title', true ),
                'current_desc'     => get_post_meta( $post_id, '_almaseo_description', true ),
                'has_existing'     => ! empty( get_post_meta( $post_id, '_almaseo_title', true ) ),
            );
        }

        return $preview;
    }

    /**
     * Get the mapper class for a source.
     *
     * @param string $source Source slug.
     * @return string|null Mapper class name or null.
     */
    private static function get_mapper( $source ) {
        $mappers = array(
            'yoast'    => 'AlmaSEO_Import_Mapper_Yoast',
            'rankmath' => 'AlmaSEO_Import_Mapper_RankMath',
            'aioseo'   => 'AlmaSEO_Import_Mapper_AIOSEO',
        );

        return isset( $mappers[ $source ] ) ? $mappers[ $source ] : null;
    }

    /* ------------------------------------------------------------------
     *  Hook suspension: prevent third-party SEO plugins from interfering
     *  with meta updates during import. Specifically fixes AIOSEO Pro
     *  fatal error in Schema/Helpers.php usort() on update_post_meta.
     * ----------------------------------------------------------------*/

    private static $suspended_hooks = array();

    /**
     * Temporarily remove third-party SEO plugin hooks that fire on
     * post meta changes and can cause fatal errors during bulk import.
     */
    private static function suspend_seo_hooks() {
        self::$suspended_hooks = array();

        // Hooks to suspend: wp_head is not relevant in REST context,
        // but updated_post_meta and added_post_meta are.
        $hook_names = array(
            'updated_post_meta',
            'added_post_meta',
            'update_post_meta',
            'add_post_meta',
            'save_post',
            'wp_insert_post',
        );

        // Patterns that match third-party SEO plugin callbacks.
        $patterns = array( 'AIOSEO', 'aioseo', 'WPSEO', 'wpseo', 'RankMath', 'rank_math' );

        foreach ( $hook_names as $hook_name ) {
            global $wp_filter;
            if ( ! isset( $wp_filter[ $hook_name ] ) ) {
                continue;
            }

            foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
                foreach ( $callbacks as $id => $callback_data ) {
                    $func = $callback_data['function'];
                    $match = false;

                    // Check if callback belongs to a third-party SEO plugin.
                    if ( is_string( $func ) ) {
                        foreach ( $patterns as $p ) {
                            if ( stripos( $func, $p ) !== false ) { $match = true; break; }
                        }
                    } elseif ( is_array( $func ) && count( $func ) === 2 ) {
                        $class = is_object( $func[0] ) ? get_class( $func[0] ) : (string) $func[0];
                        foreach ( $patterns as $p ) {
                            if ( stripos( $class, $p ) !== false ) { $match = true; break; }
                        }
                    } elseif ( $func instanceof Closure ) {
                        // Can't inspect closures — skip.
                        continue;
                    }

                    if ( $match ) {
                        self::$suspended_hooks[] = array(
                            'hook'     => $hook_name,
                            'callback' => $func,
                            'priority' => $priority,
                            'args'     => $callback_data['accepted_args'],
                        );
                        remove_filter( $hook_name, $func, $priority );
                    }
                }
            }
        }
    }

    /**
     * Restore previously suspended hooks.
     */
    private static function restore_seo_hooks() {
        foreach ( self::$suspended_hooks as $h ) {
            add_filter( $h['hook'], $h['callback'], $h['priority'], $h['args'] );
        }
        self::$suspended_hooks = array();
    }
}
