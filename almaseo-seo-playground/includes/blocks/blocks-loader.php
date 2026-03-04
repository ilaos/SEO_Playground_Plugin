<?php
/**
 * Gutenberg Blocks — Shared Bootstrap
 *
 * Loads all AlmaSEO custom blocks (FAQ, Table of Contents, etc.).
 *
 * @package AlmaSEO
 * @since   8.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── FAQ Block ──────────────────────────────────────────────────────── */
require_once __DIR__ . '/faq/faq-block.php';
AlmaSEO_FAQ_Block::init();

/* ── Table of Contents Block (built separately) ─────────────────────── */
if ( file_exists( __DIR__ . '/toc/toc-block.php' ) ) {
    require_once __DIR__ . '/toc/toc-block.php';
    AlmaSEO_TOC_Block::init();
}
