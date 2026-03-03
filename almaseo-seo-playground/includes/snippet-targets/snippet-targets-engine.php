<?php
/**
 * Featured Snippet Targeting – Engine
 *
 * Handles format-specific prompt building, content formatting,
 * insertion into post content, and undo operations.
 *
 * @package AlmaSEO
 * @since   7.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Snippet_Targets_Engine {

    /**
     * Valid snippet formats.
     */
    const FORMATS = array( 'paragraph', 'list', 'table', 'definition' );

    /* ================================================================
     *  CONTENT APPLICATION
     * ================================================================ */

    /**
     * Apply draft content to the target post.
     *
     * Inserts or replaces the relevant section in the post content.
     * Saves original section for undo, stores content hash for drift detection.
     * Auto-creates a WordPress revision.
     *
     * @param int $target_id The snippet target ID.
     * @return array|WP_Error Result array or error.
     */
    public static function apply( $target_id ) {
        $target = AlmaSEO_Snippet_Targets_Model::get_target( $target_id );
        if ( ! $target ) {
            return new WP_Error( 'not_found', 'Snippet target not found.', array( 'status' => 404 ) );
        }

        if ( empty( $target->draft_content ) ) {
            return new WP_Error( 'no_draft', 'No draft content to apply.', array( 'status' => 400 ) );
        }

        if ( ! in_array( $target->status, array( 'approved', 'draft' ), true ) ) {
            return new WP_Error( 'invalid_status', 'Target must be in draft or approved status to apply.', array( 'status' => 400 ) );
        }

        $post = get_post( $target->post_id );
        if ( ! $post ) {
            return new WP_Error( 'post_not_found', 'Target post not found.', array( 'status' => 404 ) );
        }

        $current_content = $post->post_content;
        $content_hash    = md5( $current_content );

        // Build the snippet block with a marker comment for easy undo.
        $marker_start = '<!-- almaseo-snippet-target-' . $target->id . ' -->';
        $marker_end   = '<!-- /almaseo-snippet-target-' . $target->id . ' -->';
        $snippet_block = $marker_start . "\n" . $target->draft_content . "\n" . $marker_end;

        // Check if a previous snippet block exists (re-application).
        $pattern = '/' . preg_quote( $marker_start, '/' ) . '.*?' . preg_quote( $marker_end, '/' ) . '/s';
        if ( preg_match( $pattern, $current_content ) ) {
            // Replace existing block.
            $new_content      = preg_replace( $pattern, $snippet_block, $current_content );
            $original_section = null; // Already stored from first apply.
        } else {
            // Insert after the first heading or at the top.
            $insert_result    = self::insert_after_first_heading( $current_content, $snippet_block );
            $new_content      = $insert_result['content'];
            $original_section = $insert_result['replaced_section'];
        }

        // Update the post (creates WordPress revision automatically).
        wp_update_post( array(
            'ID'           => $post->ID,
            'post_content' => $new_content,
        ) );

        // Fire action for other modules.
        do_action( 'almaseo_post_content_modified', $post->ID, 'snippet_target', $target->id );

        // Update target record.
        $update_data = array(
            'status'       => 'applied',
            'content_hash' => $content_hash,
            'applied_at'   => current_time( 'mysql', true ),
            'applied_by'   => get_current_user_id(),
        );

        if ( $original_section !== null ) {
            $update_data['original_section'] = $original_section;
        }

        AlmaSEO_Snippet_Targets_Model::update_target( $target->id, $update_data );

        return array( 'applied' => true, 'post_id' => $post->ID );
    }

    /**
     * Undo an applied snippet — remove the inserted block.
     *
     * @param int $target_id The snippet target ID.
     * @return array|WP_Error
     */
    public static function undo( $target_id ) {
        $target = AlmaSEO_Snippet_Targets_Model::get_target( $target_id );
        if ( ! $target ) {
            return new WP_Error( 'not_found', 'Snippet target not found.', array( 'status' => 404 ) );
        }

        if ( $target->status !== 'applied' ) {
            return new WP_Error( 'not_applied', 'Target is not in applied status.', array( 'status' => 400 ) );
        }

        $post = get_post( $target->post_id );
        if ( ! $post ) {
            return new WP_Error( 'post_not_found', 'Target post not found.', array( 'status' => 404 ) );
        }

        $current_content = $post->post_content;
        $marker_start    = '<!-- almaseo-snippet-target-' . $target->id . ' -->';
        $marker_end      = '<!-- /almaseo-snippet-target-' . $target->id . ' -->';

        $pattern = '/' . preg_quote( $marker_start, '/' ) . '.*?' . preg_quote( $marker_end, '/' ) . '\s*/s';

        if ( ! preg_match( $pattern, $current_content ) ) {
            return new WP_Error( 'marker_not_found', 'Snippet marker not found in post content. The content may have been edited manually.', array( 'status' => 400 ) );
        }

        // Remove the snippet block.
        $new_content = preg_replace( $pattern, '', $current_content );

        wp_update_post( array(
            'ID'           => $post->ID,
            'post_content' => $new_content,
        ) );

        do_action( 'almaseo_post_content_modified', $post->ID, 'snippet_target_undo', $target->id );

        AlmaSEO_Snippet_Targets_Model::update_target( $target->id, array(
            'status'       => 'approved',
            'applied_at'   => null,
            'applied_by'   => null,
            'content_hash' => null,
        ) );

        return array( 'undone' => true, 'post_id' => $post->ID );
    }

    /* ================================================================
     *  CONTENT INSERTION HELPERS
     * ================================================================ */

    /**
     * Insert a snippet block after the first heading in the content.
     *
     * If no heading is found, prepends to the content.
     *
     * @param string $content       The post content.
     * @param string $snippet_block The formatted snippet block.
     * @return array { content: string, replaced_section: string|null }
     */
    private static function insert_after_first_heading( $content, $snippet_block ) {
        // Try to insert after the first paragraph following the first heading.
        $pattern = '/(<h[1-6][^>]*>.*?<\/h[1-6]>\s*)(<p>.*?<\/p>)?/is';

        if ( preg_match( $pattern, $content, $match, PREG_OFFSET_CAPTURE ) ) {
            $insert_pos = $match[0][1] + strlen( $match[0][0] );
            $replaced   = isset( $match[2] ) ? $match[2][0] : null;

            $new_content = substr( $content, 0, $insert_pos ) . "\n" . $snippet_block . "\n" . substr( $content, $insert_pos );

            return array(
                'content'          => $new_content,
                'replaced_section' => $replaced,
            );
        }

        // No heading found — prepend.
        return array(
            'content'          => $snippet_block . "\n" . $content,
            'replaced_section' => null,
        );
    }

    /* ================================================================
     *  FORMAT HELPERS
     * ================================================================ */

    /**
     * Build a format-specific prompt hint for LLM generation.
     *
     * @param string $format One of: paragraph, list, table, definition.
     * @param string $query  The target search query.
     * @return string Prompt instructions.
     */
    public static function build_format_prompt( $format, $query ) {
        $base = 'Write content optimized to win a Google featured snippet for the query "' . $query . '".';

        switch ( $format ) {
            case 'paragraph':
                return $base . ' Write a direct, concise answer in 40-60 words as a single paragraph. Start with a clear definition or direct answer.';

            case 'list':
                return $base . ' Write an ordered or unordered list of 5-8 items. Use <ol> or <ul> HTML tags. Each item should be a brief, scannable step or point.';

            case 'table':
                return $base . ' Write a comparison table with 3-5 rows and 2-4 columns using <table> HTML. Include a header row. Keep cell content brief.';

            case 'definition':
                return $base . ' Write a clear definition in 30-50 words. Start with "X is..." or "X refers to..." format. Be factual and concise.';

            default:
                return $base;
        }
    }

    /**
     * Check for content drift since application.
     *
     * @param int $target_id
     * @return bool True if content has drifted.
     */
    public static function has_content_drifted( $target_id ) {
        $target = AlmaSEO_Snippet_Targets_Model::get_target( $target_id );
        if ( ! $target || ! $target->content_hash ) {
            return false;
        }

        $post = get_post( $target->post_id );
        if ( ! $post ) {
            return true;
        }

        // Remove the snippet markers to compare the "base" content.
        $marker_start = '<!-- almaseo-snippet-target-' . $target->id . ' -->';
        $marker_end   = '<!-- /almaseo-snippet-target-' . $target->id . ' -->';
        $pattern      = '/' . preg_quote( $marker_start, '/' ) . '.*?' . preg_quote( $marker_end, '/' ) . '\s*/s';

        $base_content = preg_replace( $pattern, '', $post->post_content );
        return md5( $base_content ) !== $target->content_hash;
    }
}
