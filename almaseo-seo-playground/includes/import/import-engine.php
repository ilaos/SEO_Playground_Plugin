<?php
/**
 * AlmaSEO Import Engine
 *
 * Batch-processes SEO data import from competitor plugins.
 * Each batch processes BATCH_SIZE posts to avoid timeouts.
 *
 * @package AlmaSEO
 * @since   8.1.0
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

        $done = count( $rows ) < self::BATCH_SIZE;

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
}
