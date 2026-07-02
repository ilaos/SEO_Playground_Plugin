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

/* ── Breadcrumbs Block ─────────────────────────────────────────────── */
if ( file_exists( __DIR__ . '/breadcrumbs/breadcrumbs-block.php' ) ) {
    require_once __DIR__ . '/breadcrumbs/breadcrumbs-block.php';
    AlmaSEO_Breadcrumbs_Block::init();
}

/* ── How-To Block ──────────────────────────────────────────────────── */
if ( file_exists( __DIR__ . '/howto/howto-block.php' ) ) {
    require_once __DIR__ . '/howto/howto-block.php';
    AlmaSEO_HowTo_Block::init();
}
