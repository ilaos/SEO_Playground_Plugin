<?php
/**
 * AlmaSEO Evergreen — AI Freshness Dashboard REST API
 *
 * Push endpoint for the AlmaSEO dashboard to send LLM-based semantic staleness
 * analysis for posts. The plugin stores it as post meta; the local heuristic in
 * almaseo_eg_compute_ai_freshness_score() overlays this when it is available
 * and the post content has not drifted since analysis.
 *
 * The `score` field is a staleness score (0-100) — higher means the content is
 * more out of date and a stronger refresh candidate — matching the orientation
 * of the local heuristic it overlays.
 *
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.16.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_Evergreen_Freshness_REST {

    const NS = 'almaseo/v1';

    /**
     * Register the push route.
     *
     * Called directly by the evergreen loader from inside rest_api_init, so it
     * registers immediately rather than re-hooking rest_api_init.
     */
    public static function register_routes() {
        register_rest_route(self::NS, '/evergreen-freshness/push', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array(__CLASS__, 'push_analysis'),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'items' => array('type' => 'array', 'required' => true),
            ),
        ));
    }

    /**
     * Handle a batch push of AI freshness analyses.
     *
     * @param WP_REST_Request $request Request with an `items` array.
     * @return WP_REST_Response|WP_Error
     */
    public static function push_analysis(WP_REST_Request $request) {
        $items = $request->get_param('items');
        if (!is_array($items)) {
            return new WP_Error('invalid_payload', 'Expected an "items" array.', array('status' => 400));
        }

        $valid_severities = array('high', 'medium', 'low');
        $stored  = 0;
        $skipped = array();

        // Hard cap the batch so a runaway payload can't exhaust memory.
        foreach (array_slice($items, 0, 200) as $item) {
            if (!is_array($item) || empty($item['post_id'])) {
                $skipped[] = array('reason' => 'missing_post_id');
                continue;
            }

            $post_id = absint($item['post_id']);
            $post    = get_post($post_id);
            if (!$post) {
                $skipped[] = array('post_id' => $post_id, 'reason' => 'post_not_found');
                continue;
            }

            $findings = array();
            if (isset($item['findings']) && is_array($item['findings'])) {
                foreach (array_slice($item['findings'], 0, 30) as $finding) {
                    if (!is_array($finding)) {
                        continue;
                    }
                    $severity = isset($finding['severity']) && in_array($finding['severity'], $valid_severities, true)
                        ? $finding['severity']
                        : 'medium';
                    $findings[] = array(
                        'severity'   => $severity,
                        'excerpt'    => isset($finding['excerpt']) ? sanitize_text_field($finding['excerpt']) : '',
                        'issue'      => isset($finding['issue']) ? sanitize_text_field($finding['issue']) : '',
                        'suggestion' => isset($finding['suggestion']) ? sanitize_text_field($finding['suggestion']) : '',
                    );
                }
            }

            // Prefer the hash the dashboard analyzed; otherwise hash the current
            // content so drift detection still works for the next edit.
            $content_hash = (isset($item['content_hash']) && $item['content_hash'] !== '')
                ? sanitize_text_field($item['content_hash'])
                : md5((string) $post->post_content);

            $data = array(
                'score'        => max(0, min(100, (int) (isset($item['score']) ? $item['score'] : 0))),
                'summary'      => isset($item['summary']) ? sanitize_textarea_field($item['summary']) : '',
                'findings'     => $findings,
                'content_hash' => $content_hash,
                'analyzed_at'  => current_time('mysql', true),
            );

            update_post_meta($post_id, ALMASEO_EG_META_AI_FRESHNESS, wp_json_encode($data));
            $stored++;
        }

        return rest_ensure_response(array(
            'success' => true,
            'stored'  => $stored,
            'skipped' => $skipped,
        ));
    }
}
