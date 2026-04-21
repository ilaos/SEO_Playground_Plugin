<?php
/**
 * AlmaSEO Import Verifier
 *
 * Scans imported data for potential issues: unresolved template variables,
 * empty critical fields, duplicate titles, and produces a migration health report.
 *
 * @package AlmaSEO
 * @since   8.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_Verifier {

    /**
     * Run a full verification scan on all published posts with AlmaSEO meta.
     *
     * @param int $limit Max posts to scan (0 = all).
     * @return array Verification report.
     */
    public static function verify( $limit = 0 ) {
        global $wpdb;

        $report = array(
            'total_scanned'        => 0,
            'posts_with_title'     => 0,
            'posts_with_desc'      => 0,
            'posts_with_keyword'   => 0,
            'issues'               => array(),
            'unresolved_templates' => array(),
            'empty_descriptions'   => array(),
            'duplicate_titles'     => array(),
        );

        // Get all published posts that have at least one AlmaSEO meta key.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $query = "SELECT DISTINCT p.ID, p.post_title
                  FROM {$wpdb->posts} p
                  INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                  WHERE p.post_status = 'publish'
                  AND pm.meta_key LIKE '_almaseo_%'
                  AND pm.meta_value != ''
                  ORDER BY p.ID ASC";

        if ( $limit > 0 ) {
            $query .= $wpdb->prepare( " LIMIT %d", $limit );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dynamically built with safe placeholders
        $posts = $wpdb->get_results( $query );

        $title_map = array(); // Track titles for duplicate detection.

        // Use the centralised validator patterns for foreign token detection.
        // We still need the raw patterns + valid list for the detailed report
        // (which specific tag was found, from which source).
        $foreign_patterns = array(
            'yoast'    => '/%%[a-z_]+%%/',
            'rankmath' => '/%[a-z_]+%/',
            'aioseo'   => '/#[a-z_]+/',
        );

        // AlmaSEO's own valid tags (these are fine).  Kept in sync with
        // AlmaSEO_Tag_Validator::$valid_almaseo_tags.
        $valid_almaseo_tags = array(
            '%%title%%', '%%sitename%%', '%%sitetagline%%', '%%sep%%',
            '%%excerpt%%', '%%date%%', '%%modified%%', '%%author%%',
            '%%author_first_name%%', '%%author_last_name%%',
            '%%category%%', '%%tag%%', '%%primary_category%%',
            '%%term_title%%', '%%term_description%%',
            '%%searchphrase%%', '%%page%%', '%%pagetotal%%', '%%pagenumber%%',
            '%%id%%', '%%year%%', '%%currentyear%%', '%%currentdate%%',
            '%%currentmonth%%', '%%currentday%%',
            '%%focuskeyword%%', '%%pt_single%%', '%%pt_plural%%',
        );

        foreach ( $posts as $post ) {
            $report['total_scanned']++;

            $seo_title   = get_post_meta( $post->ID, '_almaseo_title', true );
            $seo_desc    = get_post_meta( $post->ID, '_almaseo_description', true );
            $focus_kw    = get_post_meta( $post->ID, '_almaseo_focus_keyword', true );

            if ( ! empty( $seo_title ) ) {
                $report['posts_with_title']++;
            }
            if ( ! empty( $seo_desc ) ) {
                $report['posts_with_desc']++;
            }
            if ( ! empty( $focus_kw ) ) {
                $report['posts_with_keyword']++;
            }

            // Check for unresolved template variables in title.
            if ( ! empty( $seo_title ) ) {
                $unresolved = self::find_unresolved_tags( $seo_title, $foreign_patterns, $valid_almaseo_tags );
                if ( ! empty( $unresolved ) ) {
                    $report['unresolved_templates'][] = array(
                        'post_id'    => $post->ID,
                        'post_title' => $post->post_title,
                        'field'      => 'title',
                        'value'      => $seo_title,
                        'tags'       => $unresolved,
                    );
                }
            }

            // Check for unresolved template variables in description.
            if ( ! empty( $seo_desc ) ) {
                $unresolved = self::find_unresolved_tags( $seo_desc, $foreign_patterns, $valid_almaseo_tags );
                if ( ! empty( $unresolved ) ) {
                    $report['unresolved_templates'][] = array(
                        'post_id'    => $post->ID,
                        'post_title' => $post->post_title,
                        'field'      => 'description',
                        'value'      => $seo_desc,
                        'tags'       => $unresolved,
                    );
                }
            }

            // Flag posts with title but no description.
            if ( ! empty( $seo_title ) && empty( $seo_desc ) ) {
                $report['empty_descriptions'][] = array(
                    'post_id'    => $post->ID,
                    'post_title' => $post->post_title,
                );
            }

            // Track titles for duplicate detection.
            if ( ! empty( $seo_title ) ) {
                $normalized = strtolower( trim( $seo_title ) );
                if ( ! isset( $title_map[ $normalized ] ) ) {
                    $title_map[ $normalized ] = array();
                }
                $title_map[ $normalized ][] = $post->ID;
            }
        }

        // Find duplicate titles (same SEO title on multiple posts).
        foreach ( $title_map as $title => $post_ids ) {
            if ( count( $post_ids ) > 1 ) {
                $report['duplicate_titles'][] = array(
                    'title'    => $title,
                    'post_ids' => $post_ids,
                    'count'    => count( $post_ids ),
                );
            }
        }

        // Summary counts.
        $report['issues'] = array(
            'unresolved_templates' => count( $report['unresolved_templates'] ),
            'empty_descriptions'   => count( $report['empty_descriptions'] ),
            'duplicate_titles'     => count( $report['duplicate_titles'] ),
        );

        $report['issues']['total'] = array_sum( $report['issues'] );

        return $report;
    }

    /**
     * Find unresolved template tags that don't belong to AlmaSEO.
     *
     * @param string $text           Text to scan.
     * @param array  $patterns       Foreign tag patterns.
     * @param array  $valid_tags     Known valid AlmaSEO tags.
     * @return array Unresolved tags found.
     */
    private static function find_unresolved_tags( $text, $patterns, $valid_tags ) {
        $found = array();

        foreach ( $patterns as $source => $pattern ) {
            if ( preg_match_all( $pattern, $text, $matches ) ) {
                foreach ( $matches[0] as $tag ) {
                    // Skip if it's a valid AlmaSEO tag.
                    if ( in_array( $tag, $valid_tags, true ) ) {
                        continue;
                    }
                    $found[] = array(
                        'tag'    => $tag,
                        'source' => $source,
                    );
                }
            }
        }

        return $found;
    }
}
