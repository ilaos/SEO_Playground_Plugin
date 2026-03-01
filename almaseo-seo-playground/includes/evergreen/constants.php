<?php
/**
 * AlmaSEO Evergreen Feature - Constants
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Status constants
if (!defined('ALMASEO_EG_STATUS_EVERGREEN')) {
    define('ALMASEO_EG_STATUS_EVERGREEN', 'evergreen');
}
if (!defined('ALMASEO_EG_STATUS_WATCH')) {
    define('ALMASEO_EG_STATUS_WATCH', 'watch');
}
if (!defined('ALMASEO_EG_STATUS_STALE')) {
    define('ALMASEO_EG_STATUS_STALE', 'stale');
}

// Meta keys
if (!defined('ALMASEO_EG_META_STATUS')) {
    define('ALMASEO_EG_META_STATUS', '_almaseo_eg_status');
}
if (!defined('ALMASEO_EG_META_LAST_CHECKED')) {
    define('ALMASEO_EG_META_LAST_CHECKED', '_almaseo_eg_last_checked');
}
if (!defined('ALMASEO_EG_META_CLICKS_90D')) {
    define('ALMASEO_EG_META_CLICKS_90D', '_almaseo_eg_clicks_90d');
}
if (!defined('ALMASEO_EG_META_CLICKS_PREV90D')) {
    define('ALMASEO_EG_META_CLICKS_PREV90D', '_almaseo_eg_clicks_prev90d');
}
if (!defined('ALMASEO_EG_META_NOTES')) {
    define('ALMASEO_EG_META_NOTES', '_almaseo_eg_notes');
}

// Default thresholds
if (!defined('ALMASEO_EG_DEFAULT_WATCH_DAYS')) {
    define('ALMASEO_EG_DEFAULT_WATCH_DAYS', 180);
}
if (!defined('ALMASEO_EG_DEFAULT_STALE_DAYS')) {
    define('ALMASEO_EG_DEFAULT_STALE_DAYS', 365);
}
if (!defined('ALMASEO_EG_DEFAULT_WATCH_TRAFFIC_DROP')) {
    define('ALMASEO_EG_DEFAULT_WATCH_TRAFFIC_DROP', 20);
}
if (!defined('ALMASEO_EG_DEFAULT_STALE_TRAFFIC_DROP')) {
    define('ALMASEO_EG_DEFAULT_STALE_TRAFFIC_DROP', 40);
}
if (!defined('ALMASEO_EG_DEFAULT_DECLINE_PCT')) {
    define('ALMASEO_EG_DEFAULT_DECLINE_PCT', -30);
}
if (!defined('ALMASEO_EG_DEFAULT_GRACE_DAYS')) {
    define('ALMASEO_EG_DEFAULT_GRACE_DAYS', 90);
}

// Cron event name
if (!defined('ALMASEO_EG_CRON_EVENT')) {
    define('ALMASEO_EG_CRON_EVENT', 'almaseo_eg_weekly');
}

// Settings option name
if (!defined('ALMASEO_EG_SETTINGS_OPTION')) {
    define('ALMASEO_EG_SETTINGS_OPTION', 'almaseo_eg_settings');
}

// Digest option name
if (!defined('ALMASEO_EG_DIGEST_OPTION')) {
    define('ALMASEO_EG_DIGEST_OPTION', 'almaseo_eg_last_digest_html');
}