<?php
/**
 * AlmaSEO Component Isolator
 *
 * DROP THIS FILE into wp-content/mu-plugins/ to systematically disable
 * plugin components and find which one causes the reload bug.
 *
 * Usage: Add ?almaseo_disable=component_name to the post edit URL.
 * Multiple: ?almaseo_disable=gutenberg_panels,evergreen,tier_management
 *
 * Components you can disable:
 *   gutenberg_panels  — Block editor sidebar panels (schema, evergreen, optimization)
 *   evergreen         — All evergreen scripts and styles
 *   tier_management   — Tier management JS
 *   tab_scripts       — All tab-specific JS files
 *   tab_styles        — All tab-specific CSS files
 *   metabox_scripts   — The main consolidated JS
 *   llm_panel         — LLM optimization panel assets
 *   health            — Health score module scripts
 *   all_almaseo_js    — ALL AlmaSEO JavaScript (nuclear option)
 *   all_almaseo_css   — ALL AlmaSEO CSS (nuclear option)
 *
 * REMOVE THIS FILE after debugging is complete.
 */

if (!defined('ABSPATH')) exit;
if (!is_admin()) return;

add_action('admin_enqueue_scripts', function($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'))) return;
    if (!isset($_GET['almaseo_disable'])) return;

    $disable = array_map('trim', explode(',', sanitize_text_field($_GET['almaseo_disable'])));

    // Map component names to script/style handles to dequeue
    $component_map = array(
        'gutenberg_panels' => array(
            'scripts' => array('almaseo-schema-panel', 'almaseo-evergreen-panel-enhanced-v2',
                'almaseo-evergreen-panel-enhanced', 'almaseo-evergreen-panel-simple',
                'almaseo-optimization-sidebar', 'almaseo-keyword-intelligence'),
            'styles' => array('almaseo-evergreen-sidebar', 'almaseo-optimization-sidebar'),
        ),
        'evergreen' => array(
            'scripts' => array('almaseo-evergreen', 'almaseo-evergreen-panel-enhanced-v2',
                'almaseo-evergreen-panel-enhanced', 'almaseo-evergreen-panel-simple'),
            'styles' => array('almaseo-evergreen', 'almaseo-evergreen-sidebar'),
        ),
        'tier_management' => array(
            'scripts' => array('almaseo-tier-management'),
            'styles' => array('almaseo-tier-system'),
        ),
        'tab_scripts' => array(
            'scripts' => array('almaseo-seo-overview-consolidated', 'almaseo-unified-health',
                'almaseo-search-console-polish', 'almaseo-schema-meta-tab',
                'almaseo-ai-tools-polish', 'almaseo-notes-history-polish',
                'almaseo-new-features', 'almaseo-unlock-features', 'almaseo-tier-management'),
            'styles' => array(),
        ),
        'tab_styles' => array(
            'scripts' => array(),
            'styles' => array('almaseo-llm-optimization', 'almaseo-health', 'almaseo-unified-tabs',
                'almaseo-unified-health', 'almaseo-seo-overview-consolidated',
                'almaseo-search-console-polish', 'almaseo-search-console-placeholder',
                'almaseo-schema-meta-tab', 'almaseo-ai-tools-polish',
                'almaseo-notes-history-polish', 'almaseo-new-features',
                'almaseo-unlock-features', 'almaseo-unlock-features-updated', 'almaseo-tier-system'),
        ),
        'metabox_scripts' => array(
            'scripts' => array('almaseo-seo-playground-consolidated'),
            'styles' => array('almaseo-seo-playground-consolidated'),
        ),
        'llm_panel' => array(
            'scripts' => array(),
            'styles' => array('almaseo-llm-optimization'),
        ),
        'health' => array(
            'scripts' => array('almaseo-unified-health'),
            'styles' => array('almaseo-health', 'almaseo-unified-health'),
        ),
    );

    foreach ($disable as $component) {
        if ($component === 'all_almaseo_js' || $component === 'all_almaseo_css') {
            // Nuclear option — dequeue everything with "almaseo" in the handle
            add_action('admin_print_scripts', function() use ($component) {
                global $wp_scripts, $wp_styles;
                $target = ($component === 'all_almaseo_js') ? $wp_scripts : $wp_styles;
                if (!$target) return;
                foreach ($target->registered as $handle => $dep) {
                    if (stripos($handle, 'almaseo-seo-playground') !== false || stripos($handle, 'alma') !== false) {
                        if ($component === 'all_almaseo_js') {
                            wp_dequeue_script($handle);
                        } else {
                            wp_dequeue_style($handle);
                        }
                    }
                }
            }, 9999);
            continue;
        }

        if (!isset($component_map[$component])) continue;

        foreach ($component_map[$component]['scripts'] as $handle) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
        foreach ($component_map[$component]['styles'] as $handle) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
        }
    }

    // Show admin notice about disabled components
    add_action('admin_notices', function() use ($disable) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>[AlmaSEO Debug]</strong> Disabled components: <code>' . esc_html(implode(', ', $disable)) . '</code></p>';
        echo '<p>Remove <code>?almaseo_disable=...</code> from URL to re-enable.</p>';
        echo '</div>';
    });
}, 9999); // Very late priority to run after all enqueues
