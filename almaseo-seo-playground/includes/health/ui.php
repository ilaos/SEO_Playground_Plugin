<?php
/**
 * AlmaSEO Health Score Feature - UI Components
 *
 * @package AlmaSEO
 * @subpackage Health
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Truncate text for SERP display
 */
function almaseo_truncate_for_serp($text, $max_length) {
    if (strlen($text) <= $max_length) {
        return $text;
    }

    // Truncate at word boundary
    $truncated = substr($text, 0, $max_length - 3);
    $last_space = strrpos($truncated, ' ');

    if ($last_space !== false) {
        $truncated = substr($truncated, 0, $last_space);
    }

    return $truncated . '...';
}
