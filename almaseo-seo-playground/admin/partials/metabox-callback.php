<?php
/**
 * AlmaSEO SEO Playground Metabox
 *
 * Contains the metabox callback that renders the entire SEO Playground UI,
 * the LLM Optimization panel, and the admin style/script enqueue function.
 *
 * @package AlmaSEO
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue SEO Playground styles and scripts
if (!function_exists('almaseo_enqueue_seo_playground_styles')) {
function almaseo_enqueue_seo_playground_styles() {
    $screen = get_current_screen();
    
    // Enqueue admin help CSS on AlmaSEO screens
    if (function_exists('almaseo_is_admin_screen') && almaseo_is_admin_screen()) {
        wp_enqueue_style(
            'almaseo-admin-help',
            ALMASEO_PLUGIN_URL . 'assets/css/admin-help.css',
            array(),
            ALMASEO_PLUGIN_VERSION
        );
    }
    
    // Enqueue admin connection scripts and styles on settings page
    if ($screen && $screen->id === 'settings_page_almaseo-connector') {
        // Enqueue CSS
        wp_enqueue_style(
            'almaseo-admin-connection',
            ALMASEO_PLUGIN_URL . 'assets/css/admin-connection.css',
            array(),
            ALMASEO_PLUGIN_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'almaseo-admin-connection',
            ALMASEO_PLUGIN_URL . 'assets/js/admin-connection.js',
            array('jquery'),
            ALMASEO_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('almaseo-admin-connection', 'almaseoAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almaseo_nonce')
        ));
    }

    // Enqueue SEO Playground scripts on every post type the user has
    // enabled under Settings → SEO Panel Visibility. Previously this was
    // hardcoded to ['post','page'], so the metabox would *render* on CPTs
    // (Avada Portfolio etc.) but load none of its CSS/JS — the panel
    // appeared but unstyled. Falls back to ['post','page'] if the helper
    // is missing for any reason.
    $almaseo_metabox_types = function_exists('almaseo_get_metabox_post_types')
        ? almaseo_get_metabox_post_types()
        : array('post', 'page');
    if ($screen && in_array($screen->post_type, $almaseo_metabox_types, true)) {
        // Check user capability
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        // Determine if we should use minified files (check if they exist first)
        $suffix = '';
        $use_combined = false;
        
        // Check if minified combined file exists
        $combined_css = ALMASEO_PLUGIN_DIR . 'assets/css/seo-playground-all.min.css';
        $combined_js = ALMASEO_PLUGIN_DIR . 'assets/js/seo-playground-all.min.js';
        
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // Production mode - try to use minified if available
            if (file_exists($combined_css) && file_exists($combined_js)) {
                $use_combined = true;
            }
        }
        
        if ($use_combined) {
            // Production: Use combined minified files
            wp_enqueue_style(
                'almaseo-seo-playground-all',
                ALMASEO_PLUGIN_URL . 'assets/css/seo-playground-all.min.css',
                array(),
                ALMASEO_PLUGIN_VERSION
            );
            
            wp_enqueue_script(
                'almaseo-seo-playground-all',
                ALMASEO_PLUGIN_URL . 'assets/js/seo-playground-all.min.js',
                array('jquery'),
                ALMASEO_PLUGIN_VERSION,
                true
            );
        } else {
            // Development: Use individual files for easier debugging
            // Consolidated styles first
            wp_enqueue_style(
                'almaseo-seo-playground-consolidated',
                ALMASEO_PLUGIN_URL . 'assets/css/seo-playground-consolidated.css',
                array(),
                ALMASEO_PLUGIN_VERSION
            );
            
            // Tab-specific styles (only load what's needed)
            $tab_styles = array(
                'llm-optimization', // LLM Optimization panel CSS
                'health', // Health and SERP preview styles
                'unified-tabs', // Unified tab styles with emojis
                'unified-health', // Unified health score styles
                'seo-overview-consolidated', // SEO Overview styles
                'schema-meta-tab', // Schema & Meta tab CSS
                'ai-tools-polish',
                'notes-history-polish',
                'new-features', // New features CSS
                'unlock-features', // Unlock Features tab CSS
                'unlock-features-updated', // Updated Unlock Features CSS
                'tier-system' // Tier system CSS
            );
            
            foreach ($tab_styles as $style) {
                wp_enqueue_style(
                    'almaseo-' . $style,
                    ALMASEO_PLUGIN_URL . 'assets/css/' . $style . '.css',
                    array('almaseo-seo-playground-consolidated'),
                    ALMASEO_PLUGIN_VERSION
                );
            }

            // Consolidated JavaScript first
            wp_enqueue_script(
                'almaseo-seo-playground-consolidated',
                ALMASEO_PLUGIN_URL . 'assets/js/seo-playground-consolidated.js',
                array('jquery'),
                ALMASEO_PLUGIN_VERSION,
                true
            );

            // Tab-specific scripts
            $tab_scripts = array(
                'seo-overview-consolidated', // SEO Overview JavaScript
                'unified-health', // Unified health score JavaScript
                'schema-meta-tab', // Schema & Meta tab JavaScript
                'ai-tools-polish',
                'notes-history-polish',
                'new-features', // New features JavaScript
                'unlock-features', // Unlock Features tab JavaScript
                'tier-management' // Tier management JavaScript
            );
            
            foreach ($tab_scripts as $script) {
                wp_enqueue_script(
                    'almaseo-' . $script,
                    ALMASEO_PLUGIN_URL . 'assets/js/' . $script . '.js',
                    array('jquery', 'almaseo-seo-playground-consolidated'),
                    ALMASEO_PLUGIN_VERSION,
                    true
                );
            }
        }

        // Localize script with connection status and other data
        wp_localize_script('almaseo-seo-playground-consolidated', 'seoPlaygroundData', array(
            'almaConnected' => seo_playground_is_alma_connected(),
            'connectionUrl' => admin_url('admin.php?page=seo-playground-connection'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seo_playground_nonce'),
            // API key is NOT exposed to the browser — all API calls go through server-side AJAX handlers
            'siteUrl' => get_site_url(),
            'postId' => get_the_ID() ?: 0,
            'strings' => array(
                'unlockMessage' => '🔒 Unlock smart suggestions and automated insights by connecting to your AlmaSEO account.',
                'connectButton' => 'Connect to AlmaSEO',
                'connectedMessage' => '✅ Connected to AlmaSEO — premium features are available.'
            )
        ));
    }
}
} // end function_exists guard: almaseo_enqueue_seo_playground_styles
add_action('admin_enqueue_scripts', 'almaseo_enqueue_seo_playground_styles');

/**
 * Render LLM Optimization Panel
 *
 * @param WP_Post $post The post object
 */
if (!function_exists('almaseo_render_llm_optimization_panel')) {
function almaseo_render_llm_optimization_panel($post) {
    $is_pro = almaseo_feature_available('llm_optimization');
    $is_connected = seo_playground_is_alma_connected();
    ?>
    <div class="almaseo-llm-panel" data-post-id="<?php echo esc_attr($post->ID); ?>" data-is-pro-llm="<?php echo esc_attr($is_pro ? '1' : '0'); ?>">
        <div class="almaseo-llm-header">
            <h2>LLM Readiness Analysis</h2>
            <p>See how LLM systems like ChatGPT, Gemini, and Google AI Overviews understand this page — and what to improve.</p>
        </div>

        <?php if (!$is_connected): ?>
            <div class="almaseo-llm-connection-notice">
                <span class="dashicons dashicons-warning"></span>
                <div>
                    <strong>Connect to AlmaSEO for Enhanced Analysis</strong>
                    <p>Get deeper insights powered by our Alma engine. <a href="<?php echo esc_url(admin_url('admin.php?page=seo-playground-connection')); ?>">Connect now &rarr;</a></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- 1. LLM Readiness Snapshot (scores at the top) -->
        <?php if ($is_pro): ?>
        <div class="almaseo-llm-card almaseo-llm-card-highlight" data-section="scores">
            <h3><span class="dashicons dashicons-chart-area"></span> LLM Readiness Snapshot</h3>
            <div class="almaseo-llm-scores almaseo-llm-loading">
                <div class="almaseo-llm-spinner"></div>
                <span>Calculating scores...</span>
            </div>
        </div>
        <?php else: ?>
        <div class="almaseo-llm-card almaseo-llm-lock-card">
            <div class="almaseo-llm-lock-icon">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <h3>LLM Readiness Snapshot</h3>
            <p class="almaseo-llm-lock-subtitle">Pro unlocks LLM readiness scoring so you can measure how well LLM systems can use your content.</p>
            <ul class="almaseo-llm-lock-features">
                <li><span class="dashicons dashicons-yes"></span> <strong>LLM Readiness Score (0-100)</strong></li>
                <li><span class="dashicons dashicons-yes"></span> <strong>Answer Strength Score</strong> — how usable this page is as an LLM source</li>
                <li><span class="dashicons dashicons-yes"></span> <strong>Score explanations</strong> showing exactly what to fix</li>
                <li><span class="dashicons dashicons-yes"></span> <strong>Schema &amp; internal link suggestions</strong></li>
            </ul>
            <a href="https://almaseo.com" target="_blank" class="almaseo-llm-upgrade-btn">
                <span class="dashicons dashicons-star-filled"></span>
                Learn More
            </a>
        </div>
        <?php endif; ?>

        <!-- 2. Top Issues -->
        <div class="almaseo-llm-card" data-section="top-issues" style="display:none;">
            <h3><span class="dashicons dashicons-flag"></span> Top Issues</h3>
            <div class="almaseo-llm-top-issues">
                <!-- Populated by JavaScript -->
            </div>
        </div>

        <!-- 3. Summary Preview with style selector -->
        <div class="almaseo-llm-card" data-section="summary">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                <h3 style="margin: 0;"><span class="dashicons dashicons-media-document"></span> LLM Summary Preview</h3>
                <select id="almaseo-llm-summary-style" class="almaseo-llm-style-dropdown" style="min-width: 140px;">
                    <option value="concise">Concise</option>
                    <option value="detailed">Detailed</option>
                    <option value="business">Business</option>
                    <option value="technical">Technical</option>
                    <option value="creative">Creative</option>
                    <option value="academic">Academic</option>
                    <option value="qa" <?php echo esc_attr(!$is_pro ? 'disabled' : ''); ?>>Q&A Format <?php echo esc_html(!$is_pro ? '(Pro)' : ''); ?></option>
                    <option value="ai_answer" <?php echo esc_attr(!$is_pro ? 'disabled' : ''); ?>>LLM Answer <?php echo esc_html(!$is_pro ? '(Pro)' : ''); ?></option>
                </select>
            </div>
            <div class="almaseo-llm-summary-text almaseo-llm-loading">
                <div class="almaseo-llm-spinner"></div>
                <span>Loading summary...</span>
            </div>
        </div>

        <!-- 4. Section-Level Breakdown -->
        <div class="almaseo-llm-sections-container" style="display:none;">
            <div class="almaseo-llm-card">
                <h3><span class="dashicons dashicons-editor-alignleft"></span> Section Breakdown</h3>
                <p class="description">How each section of your content performs for LLM understanding.</p>
                <div class="almaseo-llm-sections-list">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- 5. Supporting Detail Cards -->
        <div class="almaseo-llm-grid">
            <div class="almaseo-llm-column almaseo-llm-column-left">
                <div class="almaseo-llm-card" data-section="entities">
                    <h3><span class="dashicons dashicons-tag"></span> Key Entities</h3>
                    <ul class="almaseo-llm-entities-list almaseo-llm-loading">
                        <div class="almaseo-llm-spinner"></div>
                        <span>Extracting entities...</span>
                    </ul>
                </div>

                <div class="almaseo-llm-card" data-section="clarity">
                    <h3><span class="dashicons dashicons-visibility"></span> Language Clarity</h3>
                    <ul class="almaseo-llm-ambiguities-list almaseo-llm-loading">
                        <div class="almaseo-llm-spinner"></div>
                        <span>Analyzing clarity...</span>
                    </ul>
                </div>
            </div>

            <div class="almaseo-llm-column almaseo-llm-column-right">
                <div class="almaseo-llm-card" data-section="content-structure">
                    <h3><span class="dashicons dashicons-editor-table"></span> Content Structure</h3>
                    <div class="almaseo-llm-structure-content almaseo-llm-loading">
                        <div class="almaseo-llm-spinner"></div>
                        <span>Analyzing structure...</span>
                    </div>
                </div>

                <?php if ($is_pro): ?>
                <div class="almaseo-llm-card" data-section="schema">
                    <h3><span class="dashicons dashicons-admin-links"></span> Advanced Insights</h3>
                    <div class="almaseo-llm-insights almaseo-llm-loading">
                        <div class="almaseo-llm-spinner"></div>
                        <span>Loading insights...</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="almaseo-llm-footer">
            <button type="button" class="button button-primary almaseo-llm-refresh">
                <span class="dashicons dashicons-update"></span>
                Refresh Analysis
            </button>
            <span class="almaseo-llm-status"></span>
        </div>
    </div>
    <?php
}
} // end function_exists guard: almaseo_render_llm_optimization_panel

// SEO Playground meta box callback
if (!function_exists('almaseo_seo_playground_meta_box_callback')) {
function almaseo_seo_playground_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('almaseo_seo_playground_nonce', 'almaseo_seo_playground_nonce');
    
    // Get existing values - check new canonical keys first, then legacy
    $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
    if (empty($seo_title)) {
        $seo_title = get_post_meta($post->ID, '_seo_playground_title', true) ?: '';
    }
    
    $seo_description = get_post_meta($post->ID, '_almaseo_description', true);
    if (empty($seo_description)) {
        $seo_description = get_post_meta($post->ID, '_seo_playground_description', true) ?: '';
    }
    
    $seo_focus_keyword = get_post_meta($post->ID, '_almaseo_focus_keyword', true);
    if (empty($seo_focus_keyword)) {
        $seo_focus_keyword = get_post_meta($post->ID, '_seo_playground_focus_keyword', true) ?: '';
    }
    $seo_notes = get_post_meta($post->ID, '_seo_playground_notes', true) ?: '';
    $seo_schema_type = get_post_meta($post->ID, '_seo_playground_schema_type', true) ?: '';
    
    // Check connection status and tier
    $is_connected = seo_playground_is_alma_connected();
    $user_tier = almaseo_get_user_tier();
    $can_use_ai = almaseo_can_use_ai_features();
    $generations_info = almaseo_get_remaining_generations();

    // Expose AI autofill availability. Computed up here (not at the bottom of
    // the callback) because the SEO Title / Meta Description autofill buttons
    // read $ai_autofill_available while rendering far above — previously it was
    // only assigned near the end of the function, so those buttons always saw
    // an undefined (falsy) value and rendered the non-Pro fallback label.
    $ai_autofill_available = false;
    if ( file_exists( dirname( __DIR__, 2 ) . '/includes/bulkmeta/ai-autofill-generator.php' ) ) {
        require_once dirname( __DIR__, 2 ) . '/includes/bulkmeta/ai-autofill-generator.php';
        $ai_autofill_available = \AlmaSEO\BulkMeta\AI_Autofill_Generator::is_available();
    }

    // Get motivational quote
    $quotes = array(
        "Great content is the foundation of great SEO.",
        "Every word counts in the digital landscape.",
        "Optimize for users, not just search engines.",
        "Quality content creates lasting impact.",
        "SEO is a marathon, not a sprint."
    );
    $current_quote = $quotes[array_rand($quotes)];
    
    ?>
    <div class="almaseo-seo-playground">
        <div class="almaseo-seo-header">
            <div class="almaseo-quote">
                <em>"<?php echo esc_html($current_quote); ?>"</em>
            </div>
        </div>
        
        <!-- Tab Navigation Bar -->
        <div class="almaseo-tab-navigation" id="almaseo-tab-navigation">
            <div class="almaseo-tab-scroll-wrapper">
                <button type="button" class="almaseo-tab-btn active" data-tab="seo-overview">
                    <span class="tab-icon">🩺</span>
                    <span class="tab-label">SEO Page Health</span>
                </button>
                <button type="button" class="almaseo-tab-btn" data-tab="search-console">
                    <span class="tab-icon">📈</span>
                    <span class="tab-label">Search Console</span>
                </button>
                <button type="button" class="almaseo-tab-btn" data-tab="schema-meta">
                    <span class="tab-icon">🧩</span>
                    <span class="tab-label">Schema & Meta</span>
                </button>
                <button type="button" class="almaseo-tab-btn" data-tab="analytics">
                    <span class="tab-icon">📊</span>
                    <span class="tab-label">Analytics</span>
                </button>
                <button type="button" class="almaseo-tab-btn" data-tab="llm-optimization">
                    <span class="tab-icon">🤖</span>
                    <span class="tab-label">LLM Optimization</span>
                    <span class="tab-badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; margin-left: 4px;">BETA</span>
                </button>
                <button type="button" class="almaseo-tab-btn" data-tab="notes-history">
                    <span class="tab-icon">🗒️</span>
                    <span class="tab-label">Notes & History</span>
                </button>
                <?php if (!$is_connected && function_exists('almaseo_is_free_tier') && almaseo_is_free_tier()): ?>
                <button type="button" class="almaseo-tab-btn almaseo-unlock-tab" data-tab="unlock-features">
                    <span class="tab-icon">🔒</span>
                    <span class="tab-label">Unlock Full Features</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Re-Optimization Alert Section -->
        <?php if ($is_connected): ?>
        <div class="almaseo-reoptimize-section" id="almaseo-reoptimize-section" style="display: none;">
            <div class="almaseo-field-group">
                <div class="reoptimize-alert" id="reoptimize-alert">
                    <div class="reoptimize-loading" id="reoptimize-loading">
                        <div class="reoptimize-loading-spinner"></div>
                        <div class="reoptimize-loading-text">Analyzing post for re-optimization opportunities...</div>
                    </div>
                    
                    <div class="reoptimize-content" id="reoptimize-content" style="display: none;">
                        <div class="reoptimize-header">
                            <div class="reoptimize-icon">📉</div>
                            <div class="reoptimize-title">This post may benefit from re-optimization</div>
                        </div>
                        <div class="reoptimize-reason" id="reoptimize-reason">
                            <!-- Reason will be populated by JavaScript -->
                        </div>
                        <div class="reoptimize-suggestions" id="reoptimize-suggestions">
                            <!-- Suggestions will be populated by JavaScript -->
                        </div>
                        <div class="reoptimize-actions">
                            <button type="button" class="reoptimize-btn" id="start-reoptimization" disabled>
                                Start AI Re-Optimization
                            </button>
                            <span class="reoptimize-note">(Coming soon)</span>
                        </div>
                    </div>
                    
                    <div class="reoptimize-error" id="reoptimize-error" style="display: none;">
                        <div class="error-icon">⚠️</div>
                        <div class="error-text">Could not check re-optimization status</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Tab Content Areas -->
        <div class="almaseo-tab-content" id="almaseo-tab-content">

        <!-- SEO Health Tab (Unified) -->
        <div class="almaseo-tab-panel active" id="tab-seo-overview">
        
        <?php
        // Check if search engines are discouraged and show warning
        if (get_option('blog_public') == '0') {
            ?>
            <div class="almaseo-search-engine-tab-warning" style="background: linear-gradient(135deg, #dc3232, #c92c2c); color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 4px 4px 0 0; display: flex; align-items: center; box-shadow: 0 2px 8px rgba(220,50,50,0.3);">
                <span class="dashicons dashicons-warning" style="font-size: 24px; margin-right: 12px;"></span>
                <div style="flex: 1;">
                    <strong style="font-size: 15px; display: block; margin-bottom: 3px;">🚨 CRITICAL: Your Site is Hidden from Search Engines!</strong>
                    <span style="font-size: 13px; opacity: 0.95;">WordPress "Discourage search engines" is enabled. Your content will NOT appear in Google! This affects ALL posts and pages.</span>
                </div>
                <a href="<?php echo esc_url(admin_url('options-reading.php')); ?>" class="button" style="background: white; color: #dc3232; border: none; font-weight: bold;">Fix Immediately →</a>
            </div>
            <?php
        }
        
        // Get health score data
        $health_score = get_post_meta($post->ID, '_almaseo_health_score', true);
        $health_breakdown_json = get_post_meta($post->ID, '_almaseo_health_breakdown', true);
        $health_updated = get_post_meta($post->ID, '_almaseo_health_updated_at', true);
        
        // If no score exists, calculate it
        if ($health_score === '' || $health_breakdown_json === '') {
            if (function_exists('almaseo_health_calculate')) {
                $result = almaseo_health_calculate($post->ID);
                $health_score = $result['score'];
                $health_breakdown = $result['breakdown'];
                
                // Save for next time
                update_post_meta($post->ID, '_almaseo_health_score', $health_score);
                update_post_meta($post->ID, '_almaseo_health_breakdown', json_encode($health_breakdown));
                update_post_meta($post->ID, '_almaseo_health_updated_at', current_time('U'));
            } else {
                $health_score = 0;
                $health_breakdown = array();
            }
        } else {
            $health_breakdown = json_decode($health_breakdown_json, true);
        }
        
        // Count passes/fails
        $pass_count = 0;
        $fail_count = 0;
        foreach ($health_breakdown as $signal => $result) {
            if ($result['pass']) {
                $pass_count++;
            } else {
                $fail_count++;
            }
        }
        
        // Determine score color
        $score_class = 'poor';
        $color_hex = '#d63638';
        if ($health_score >= 80) {
            $score_class = 'excellent';
            $color_hex = '#00a32a';
        } elseif ($health_score >= 50) {
            $score_class = 'good';
            $color_hex = '#dba617';
        }
        ?>

        <!-- Top Section: SEO Editor Fields -->
        <div class="almaseo-seo-fields">
            <h4><?php esc_html_e('SEO Editor', 'almaseo-seo-playground'); ?></h4>
            <div class="almaseo-field-group">
                <label for="almaseo_focus_keyword">
                    Focus Keyword
                    <span class="label-helper">(Choose a realistic target that matches search intent.)</span>
                </label>
                <input type="text"
                       id="almaseo_focus_keyword"
                       name="almaseo_focus_keyword"
                       value="<?php echo esc_attr($seo_focus_keyword ?? ''); ?>"
                       placeholder="<?php esc_attr_e('Choose a keyword or phrase that matches search intent', 'almaseo-seo-playground'); ?>"
                       class="almaseo-input" />
                <p class="field-subtext" style="margin: 5px 0 0 0; color: #666; font-size: 12px; font-style: italic;">
                    <?php esc_html_e('Choose a realistic keyword that matches what people actually search for.', 'almaseo-seo-playground'); ?>
                </p>
                <!-- Google Keyword Suggestions Dropdown -->
                <div id="almaseo-keyword-suggestions-wrap" style="position: relative; margin-top: 4px;">
                    <ul id="almaseo-keyword-suggestions" style="
                        display: none;
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        z-index: 1000;
                        background: #fff;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                        list-style: none;
                        margin: 0;
                        padding: 4px 0;
                        max-height: 260px;
                        overflow-y: auto;
                    "></ul>
                </div>
                <script>
                (function(){
                    var input = document.getElementById('almaseo_focus_keyword');
                    var list  = document.getElementById('almaseo-keyword-suggestions');
                    if (!input || !list) return;

                    var debounceTimer = null;
                    var currentXhr    = null;

                    function fetchSuggestions(query) {
                        if (currentXhr) { currentXhr.abort(); currentXhr = null; }
                        if (query.length < 2) { list.style.display = 'none'; return; }

                        var nonce = (typeof almaseo_health !== 'undefined' && almaseo_health.nonce)
                                    ? almaseo_health.nonce
                                    : '<?php echo esc_js(wp_create_nonce('almaseo_nonce')); ?>';

                        var url = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>'
                                + '?action=almaseo_keyword_suggest&nonce=' + encodeURIComponent(nonce)
                                + '&q=' + encodeURIComponent(query);

                        currentXhr = new XMLHttpRequest();
                        currentXhr.open('GET', url, true);
                        currentXhr.onreadystatechange = function() {
                            if (this.readyState !== 4) return;
                            currentXhr = null;
                            if (this.status !== 200) { list.style.display = 'none'; return; }
                            try {
                                var resp = JSON.parse(this.responseText);
                                if (resp.success && resp.data && resp.data.suggestions && resp.data.suggestions.length) {
                                    renderSuggestions(resp.data.suggestions);
                                } else {
                                    list.style.display = 'none';
                                }
                            } catch(e) { list.style.display = 'none'; }
                        };
                        currentXhr.send();
                    }

                    function renderSuggestions(items) {
                        list.innerHTML = '';
                        items.forEach(function(text) {
                            var li = document.createElement('li');
                            li.textContent = text;
                            li.style.cssText = 'padding: 8px 12px; cursor: pointer; font-size: 13px; color: #333; border-bottom: 1px solid #f0f0f0; transition: background 0.15s;';
                            li.addEventListener('mouseenter', function(){ this.style.background = '#f0f4ff'; });
                            li.addEventListener('mouseleave', function(){ this.style.background = ''; });
                            li.addEventListener('mousedown', function(e){
                                e.preventDefault();
                                input.value = text;
                                list.style.display = 'none';
                                input.dispatchEvent(new Event('input', {bubbles: true}));
                                input.dispatchEvent(new Event('change', {bubbles: true}));
                            });
                            list.appendChild(li);
                        });
                        var last = list.lastElementChild;
                        if (last) last.style.borderBottom = 'none';
                        list.style.display = 'block';
                    }

                    input.addEventListener('input', function() {
                        clearTimeout(debounceTimer);
                        var val = this.value.trim();
                        debounceTimer = setTimeout(function(){ fetchSuggestions(val); }, 300);
                    });

                    input.addEventListener('blur', function() {
                        setTimeout(function(){ list.style.display = 'none'; }, 200);
                    });

                    input.addEventListener('focus', function() {
                        if (this.value.trim().length >= 2 && list.children.length > 0) {
                            list.style.display = 'block';
                        }
                    });

                    input.addEventListener('keydown', function(e) {
                        if (list.style.display === 'none') return;
                        var items = list.querySelectorAll('li');
                        var active = list.querySelector('li[data-active]');
                        var idx = -1;
                        if (active) {
                            for (var i = 0; i < items.length; i++) { if (items[i] === active) { idx = i; break; } }
                        }
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            if (active) { active.removeAttribute('data-active'); active.style.background = ''; }
                            idx = (idx + 1) % items.length;
                            items[idx].setAttribute('data-active', '1');
                            items[idx].style.background = '#f0f4ff';
                            items[idx].scrollIntoView({block: 'nearest'});
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            if (active) { active.removeAttribute('data-active'); active.style.background = ''; }
                            idx = idx <= 0 ? items.length - 1 : idx - 1;
                            items[idx].setAttribute('data-active', '1');
                            items[idx].style.background = '#f0f4ff';
                            items[idx].scrollIntoView({block: 'nearest'});
                        } else if (e.key === 'Enter' && active) {
                            e.preventDefault();
                            input.value = active.textContent;
                            list.style.display = 'none';
                            input.dispatchEvent(new Event('input', {bubbles: true}));
                            input.dispatchEvent(new Event('change', {bubbles: true}));
                        } else if (e.key === 'Escape') {
                            list.style.display = 'none';
                        }
                    });
                })();
                </script>
            </div>


            <div class="almaseo-field-group">
                <label for="almaseo_seo_title">
                    SEO Title
                    <span class="label-helper">(Google recommends ≤ 60 characters. Use strong keywords naturally — <a href="https://developers.google.com/search/docs/appearance/title-link?hl=en&sjid=17527460305754617133-NA&visit_id=638911458818141308-2932973546&rd=1" target="_blank" rel="noopener">Google Guidelines</a>)</span>
                </label>
                <input type="text"
                       id="almaseo_seo_title"
                       name="almaseo_seo_title"
                       value="<?php echo esc_attr($seo_title ?? ''); ?>"
                       placeholder="<?php esc_attr_e('Enter a clear, keyword-rich title (max 60 characters)', 'almaseo-seo-playground'); ?>"
                       class="almaseo-input" />
                <div class="almaseo-char-count" data-field="title">
                    <span id="title-count" class="<?php echo esc_attr(strlen($seo_title ?? '') <= 60 ? 'good' : (strlen($seo_title ?? '') <= 70 ? 'caution' : 'too-long')); ?>"><?php echo intval(strlen($seo_title ?? '')); ?></span>/60
                </div>
                <div class="almaseo-char-bar" data-field="title" aria-label="Title character usage">
                    <?php
                    $title_len = strlen($seo_title ?? '');
                    $title_percent = min(100, ($title_len / 70) * 100); // Max at 70 chars
                    $bar_class = $title_len <= 60 ? 'good' : ($title_len <= 70 ? 'caution' : 'too-long');
                    ?>
                    <div class="char-bar-track">
                        <div class="char-bar-fill <?php echo esc_attr($bar_class); ?>" style="width: <?php echo esc_attr($title_percent); ?>%"></div>
                        <div class="char-bar-marker good-zone" style="left: 85.7%" aria-label="60 character mark"></div>
                    </div>
                </div>
                <p class="field-subtext" style="margin: 5px 0 0 0; color: #666; font-size: 12px; display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="button button-small almaseo-autofill-btn" data-field="title" style="font-size: 11px; line-height: 22px; padding: 0 8px; <?php echo esc_attr($ai_autofill_available ? 'background: linear-gradient(135deg, #f0f0ff, #f5f0ff); border-color: #c4b5fd; color: #4c1d95;' : ''); ?>">
                        <span class="dashicons dashicons-edit-page" style="font-size: 14px; line-height: 22px; width: 14px; height: 14px;"></span>
                        <span class="almaseo-autofill-label"><?php echo $ai_autofill_available ? esc_html__('Generate Title', 'almaseo-seo-playground') : esc_html__('Auto-Generate Title', 'almaseo-seo-playground'); ?></span>
                        <?php if ($ai_autofill_available): ?>
                        <span style="background: #7c3aed; color: #fff; font-size: 8px; padding: 1px 4px; border-radius: 6px; margin-left: 3px; font-weight: 700; letter-spacing: 0.5px; vertical-align: middle;">PRO</span>
                        <?php endif; ?>
                    </button>
                    <span style="font-style: italic; color: #666;"><?php echo $ai_autofill_available ? esc_html__('Alma reads your content and writes a click-worthy title', 'almaseo-seo-playground') : esc_html__('Generate an SEO-optimized title from your content', 'almaseo-seo-playground'); ?></span>
                </p>



            </div>

            <div class="almaseo-field-group">
                <label for="almaseo_seo_description">
                    Meta Description
                    <span class="label-helper">(Aim for 150–160 characters. Write for humans first; clarity beats keyword stuffing.)</span>
                </label>
                <textarea id="almaseo_seo_description"
                          name="almaseo_seo_description"
                          placeholder="<?php esc_attr_e('Write a compelling, keyword-focused description (150–160 characters)', 'almaseo-seo-playground'); ?>"
                          class="almaseo-textarea"><?php echo esc_textarea($seo_description ?? ''); ?></textarea>
                <div class="almaseo-char-count" data-field="description">
                    <?php
                    $desc_len = strlen($seo_description ?? '');
                    $desc_class = ($desc_len >= 150 && $desc_len <= 160) ? 'good' :
                                  (($desc_len >= 120 && $desc_len < 150) || ($desc_len > 160 && $desc_len <= 180) ? 'caution' : 'too-long');
                    ?>
                    <span id="description-count" class="<?php echo esc_attr($desc_class); ?>"><?php echo intval($desc_len); ?></span>/160
                </div>
                <div class="almaseo-char-bar" data-field="description" aria-label="Description character usage">
                    <?php
                    $desc_percent = min(100, ($desc_len / 180) * 100); // Max at 180 chars
                    $bar_class = ($desc_len >= 150 && $desc_len <= 160) ? 'good' :
                                 (($desc_len >= 120 && $desc_len < 150) || ($desc_len > 160 && $desc_len <= 180) ? 'caution' :
                                 ($desc_len < 120 ? 'too-short' : 'too-long'));
                    ?>
                    <div class="char-bar-track">
                        <div class="char-bar-fill <?php echo esc_attr($bar_class); ?>" style="width: <?php echo esc_attr($desc_percent); ?>%"></div>
                        <div class="char-bar-marker caution-start" style="left: 66.7%" aria-label="120 character mark"></div>
                        <div class="char-bar-marker good-start" style="left: 83.3%" aria-label="150 character mark"></div>
                        <div class="char-bar-marker good-end" style="left: 88.9%" aria-label="160 character mark"></div>
                    </div>
                </div>
                <p class="field-subtext" style="margin: 5px 0 0 0; color: #666; font-size: 12px; display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="button button-small almaseo-autofill-btn" data-field="description" style="font-size: 11px; line-height: 22px; padding: 0 8px; <?php echo esc_attr($ai_autofill_available ? 'background: linear-gradient(135deg, #f0f0ff, #f5f0ff); border-color: #c4b5fd; color: #4c1d95;' : ''); ?>">
                        <span class="dashicons dashicons-edit-page" style="font-size: 14px; line-height: 22px; width: 14px; height: 14px;"></span>
                        <span class="almaseo-autofill-label"><?php echo $ai_autofill_available ? esc_html__('Generate Description', 'almaseo-seo-playground') : esc_html__('Auto-Generate Description', 'almaseo-seo-playground'); ?></span>
                        <?php if ($ai_autofill_available): ?>
                        <span style="background: #7c3aed; color: #fff; font-size: 8px; padding: 1px 4px; border-radius: 6px; margin-left: 3px; font-weight: 700; letter-spacing: 0.5px; vertical-align: middle;">PRO</span>
                        <?php endif; ?>
                    </button>
                    <span style="font-style: italic; color: #666;"><?php echo $ai_autofill_available ? esc_html__('Alma writes a compelling 155-char description from your content', 'almaseo-seo-playground') : esc_html__('Generate from your page content', 'almaseo-seo-playground'); ?></span>
                </p>
            </div>

            <!-- Google SERP Preview -->
            <div class="almaseo-serp-preview">
                <div class="serp-preview-header">
                    <h4>
                        Google Search Preview
                        <span class="serp-info-tooltip" title="This preview is approximate. Actual Google search results may look different." style="cursor: help; margin-left: 5px; color: #999;">ⓘ</span>
                    </h4>
                    <?php
                    // Add help text for SERP preview
                    if (function_exists('almaseo_render_help')) {
                        almaseo_render_help(
                            __('This simulates how your result may appear in Google. Aim for concise, clear titles and descriptions.', 'almaseo-seo-playground'),
                            __('Titles typically truncate around ~580px; descriptions around ~920px on desktop.', 'almaseo-seo-playground')
                        );
                    }
                    ?>
                    <div class="serp-toggle">
                        <button type="button" class="serp-toggle-btn active" data-view="desktop">Desktop</button>
                        <button type="button" class="serp-toggle-btn" data-view="mobile">Mobile</button>
                    </div>
                </div>
                <div class="serp-preview-container desktop-view">
                    <div class="serp-result">
                        <div class="serp-title" id="serp-preview-title">
                            <?php echo !empty($seo_title) ? esc_html($seo_title) : esc_html(get_the_title($post->ID)); ?>
                        </div>
                        <div class="serp-url">
                            <?php
                            $site_url = wp_parse_url(get_site_url());
                            $post_slug = $post->post_name ?: 'sample-post';
                            echo esc_html($site_url['host']) . ' › ' . esc_html($post_slug);
                            ?>
                        </div>
                        <div class="serp-description" id="serp-preview-description">
                            <?php echo !empty($seo_description) ? esc_html($seo_description) : esc_html(wp_trim_words($post->post_content, 20)); ?>
                        </div>
                    </div>
                </div>
                <p class="serp-caption">Preview is approximate and for guidance only.</p>
            </div>


        </div>
        <!-- End SEO Editor Fields -->

        <!-- Health Summary Section -->
        <div class="almaseo-health-summary">
            <div class="health-summary-container">
                <!-- Radial Gauge -->
                <div class="health-gauge-wrapper">
                    <canvas id="almaseo-health-gauge" width="200" height="200"></canvas>
                    <div class="health-score-text <?php echo esc_attr($score_class); ?>">
                        <span class="score-number"><?php echo esc_html($health_score); ?></span>
                        <span class="score-label">SEO Score</span>
                    </div>
                </div>
                
                <!-- Stats & Status -->
                <div class="health-info">
                    <h3><?php esc_html_e('Overall SEO Health', 'almaseo-seo-playground'); ?></h3>
                    <p class="health-status health-<?php echo esc_attr($score_class); ?>">
                        <?php
                        if ($health_score >= 80) {
                            esc_html_e('Excellent! Your content is well-optimized.', 'almaseo-seo-playground');
                        } elseif ($health_score >= 50) {
                            esc_html_e('Good, but there\'s room for improvement.', 'almaseo-seo-playground');
                        } else {
                            esc_html_e('Needs attention. Follow the suggestions below.', 'almaseo-seo-playground');
                        }
                        ?>
                    </p>
                    <div class="health-stats">
                        <span class="health-stat">
                            <span class="stat-icon">✅</span>
                            <strong><?php echo intval($pass_count); ?></strong> Passed
                        </span>
                        <span class="health-stat">
                            <span class="stat-icon">❌</span>
                            <strong><?php echo intval($fail_count); ?></strong> Issues
                        </span>
                    </div>
                    <button type="button" class="button button-primary" id="almaseo-health-recalculate" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Recalculate', 'almaseo-seo-playground'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Middle Section: Signal Breakdown -->
        <div class="almaseo-health-signals">
            <h4><?php esc_html_e('SEO Signal Analysis', 'almaseo-seo-playground'); ?></h4>
            
            <?php
            // Show critical warning if search engines are blocked
            if (get_option('blog_public') == '0') {
                ?>
                <div class="almaseo-signal-critical-warning" style="background: #dc3232; color: white; padding: 12px; margin-bottom: 15px; border-radius: 4px; display: flex; align-items: center;">
                    <span class="dashicons dashicons-warning" style="font-size: 20px; margin-right: 10px;"></span>
                    <div style="flex: 1;">
                        <strong>⚠️ Critical Issue Detected!</strong>
                        <span style="margin-left: 8px; opacity: 0.9; font-size: 13px;">
                            Search engines are blocked. This overrides all other SEO efforts!
                        </span>
                    </div>
                </div>
                <?php
            }
            
            // Debug: Check if breakdown is empty
            if (empty($health_breakdown)) {
                echo '<p style="color: orange; font-style: italic;">Note: Recalculating health signals...</p>';
                // Force recalculation
                if (function_exists('almaseo_health_calculate')) {
                    $result = almaseo_health_calculate($post->ID);
                    $health_breakdown = $result['breakdown'];
                    $health_score = $result['score'];
                    update_post_meta($post->ID, '_almaseo_health_score', $health_score);
                    update_post_meta($post->ID, '_almaseo_health_breakdown', json_encode($health_breakdown));
                    update_post_meta($post->ID, '_almaseo_health_updated_at', current_time('U'));
                }
            }
            
            // Signal labels and helpers
            $signal_labels = array(
                'title' => 'Meta Title',
                'meta_desc' => 'Meta Description',
                'h1' => 'H1 Heading',
                'kw_intro' => 'Keyword in First 100 Words',
                'internal_link' => 'Internal Links',
                'outbound_link' => 'Outbound Links',
                'image_alt' => 'Image Alt Text',
                'readability' => 'Readability',
                'canonical' => 'Canonical URL',
                'robots' => 'Robots Meta'
            );
            
            // Brief descriptions for each signal
            $signal_descriptions = array(
                'title' => 'The clickable headline shown in search results',
                'meta_desc' => 'The summary text displayed under your title in search results',
                'h1' => 'The main heading that tells visitors and search engines what the page is about',
                'kw_intro' => 'Having your target keyword early shows relevance to search engines',
                'internal_link' => 'Links to other pages on your site to help navigation and SEO',
                'outbound_link' => 'Links to external sites that provide value and credibility',
                'image_alt' => 'Text descriptions for images to improve accessibility and SEO',
                'readability' => 'How easy your content is to read and understand',
                'canonical' => 'Tells search engines the preferred URL for this content',
                'robots' => 'Controls whether search engines can index and follow this page'
            );
            
            $signal_fields = array(
                'title' => 'almaseo_seo_title',
                'meta_desc' => 'almaseo_seo_description',
                'kw_intro' => 'almaseo_focus_keyword',
                'canonical' => 'almaseo_canonical_url'
            );
            
            foreach ($health_breakdown as $signal => $result):
                $label = isset($signal_labels[$signal]) ? $signal_labels[$signal] : ucfirst(str_replace('_', ' ', $signal));
                $description = isset($signal_descriptions[$signal]) ? $signal_descriptions[$signal] : '';
                $icon = $result['pass'] ? '✅' : '❌';
                $status_class = $result['pass'] ? 'pass' : 'fail';
                $field_id = isset($signal_fields[$signal]) ? $signal_fields[$signal] : '';
                
                // Highlight robots signal if search engines are blocked
                $extra_class = '';
                $extra_style = '';
                if ($signal === 'robots' && !$result['pass'] && strpos($result['note'], 'Discourage search engines') !== false) {
                    $extra_class = ' robots-critical';
                    $extra_style = 'border: 2px solid #dc3232; background: #fff5f5;';
                }
            ?>
            <div class="almaseo-health-signal <?php echo esc_attr($status_class . $extra_class); ?>" data-signal="<?php echo esc_attr($signal); ?>"<?php echo $extra_style ? ' style="' . esc_attr($extra_style) . '"' : ''; ?>>
                <div class="signal-header">
                    <span class="signal-icon"><?php echo esc_html($icon); ?></span>
                    <span class="signal-label">
                        <?php echo esc_html($label); ?>
                        <?php if ($description): ?>
                        <span style="color: #666; font-weight: normal; font-size: 12px; margin-left: 8px;">
                            — <?php echo esc_html($description); ?>
                        </span>
                        <?php endif; ?>
                    </span>
                    <?php if (!$result['pass'] && $field_id): ?>
                    <button type="button" class="signal-goto-btn" data-field="<?php echo esc_attr($field_id); ?>">
                        <?php esc_html_e('Go to Field', 'almaseo-seo-playground'); ?> ↓
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="signal-bar-wrapper">
                    <div class="signal-bar">
                        <div class="signal-bar-fill <?php echo esc_attr($status_class); ?>" 
                             style="width: <?php echo esc_attr($result['pass'] ? '100' : '0'); ?>%"></div>
                    </div>
                </div>
                
                <div class="signal-note">
                    <?php echo esc_html($result['note']); ?>
                </div>

                <?php if ($signal === 'readability'): ?>
                <div class="signal-readability-detail" style="margin-top: 8px;">
                    <?php if (!empty($result['checks'])): ?>
                    <button type="button" class="signal-detail-toggle button-link" style="font-size: 12px; cursor: pointer;">
                        <?php esc_html_e('Show what to fix', 'almaseo-seo-playground'); ?> ▾
                    </button>
                    <ul class="signal-detail-list" style="display: none; list-style: none; margin: 8px 0 0; padding: 8px 10px; background: #f6f7f7; border: 1px solid #e0e0e0; border-radius: 4px;">
                        <?php foreach ($result['checks'] as $check): ?>
                        <li style="padding: 4px 0; border-bottom: 1px solid #f0f0f1; font-size: 12px;">
                            <span class="signal-detail-icon" style="color: <?php echo !empty($check['pass']) ? '#00a32a' : '#d63638'; ?>;"><?php echo !empty($check['pass']) ? '✓' : '✗'; ?></span>
                            <strong><?php echo esc_html($check['label']); ?></strong> — <?php echo esc_html($check['tip']); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <span class="signal-detail-hint" style="font-size: 12px; color: #646970; font-style: italic;">
                        <?php esc_html_e('Click Recalculate to see the per-check breakdown.', 'almaseo-seo-playground'); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        </div>
        <!-- End SEO Overview Tab -->
        
        <script>
        jQuery(document).ready(function($) {
            // Draw Health Gauge
            function drawHealthGauge(score) {
                const canvas = document.getElementById('almaseo-health-gauge');
                if (!canvas) return;
                
                const ctx = canvas.getContext('2d');
                const centerX = canvas.width / 2;
                const centerY = canvas.height / 2;
                const radius = 80;
                
                // Determine color based on score
                let color = '#d63638'; // red
                if (score >= 80) {
                    color = '#00a32a'; // green
                } else if (score >= 50) {
                    color = '#dba617'; // yellow
                }
                
                // Clear canvas
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Draw background arc
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, Math.PI * 0.7, Math.PI * 2.3, false);
                ctx.lineWidth = 20;
                ctx.strokeStyle = '#e0e0e0';
                ctx.stroke();
                
                // Draw score arc
                const scoreAngle = (score / 100) * Math.PI * 1.6;
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, Math.PI * 0.7, Math.PI * 0.7 + scoreAngle, false);
                ctx.lineWidth = 20;
                ctx.strokeStyle = color;
                ctx.lineCap = 'round';
                ctx.stroke();
                
                // Draw tick marks
                ctx.strokeStyle = '#999';
                ctx.lineWidth = 1;
                for (let i = 0; i <= 10; i++) {
                    const angle = Math.PI * 0.7 + (i / 10) * Math.PI * 1.6;
                    const x1 = centerX + Math.cos(angle) * (radius - 10);
                    const y1 = centerY + Math.sin(angle) * (radius - 10);
                    const x2 = centerX + Math.cos(angle) * (radius + 10);
                    const y2 = centerY + Math.sin(angle) * (radius + 10);
                    
                    ctx.beginPath();
                    ctx.moveTo(x1, y1);
                    ctx.lineTo(x2, y2);
                    ctx.stroke();
                }
            }
            
            // Initialize gauge
            const score = parseInt($('.health-score-text .score-number').text()) || <?php echo intval($health_score); ?>;
            drawHealthGauge(score);
            
            // Setup custom tooltip for gauge
            function setupGaugeTooltip() {
                // Create tooltip element if it doesn't exist
                if (!$('#almaseo-gauge-tooltip').length) {
                    $('body').append(`
                        <div id="almaseo-gauge-tooltip" style="
                            position: absolute;
                            background: rgba(0, 0, 0, 0.9);
                            color: white;
                            padding: 10px 12px;
                            border-radius: 4px;
                            font-size: 12px;
                            line-height: 1.5;
                            white-space: pre-line;
                            max-width: 280px;
                            pointer-events: none;
                            z-index: 100000;
                            display: none;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        ">
                            <strong>SEO Health Score Breakdown:</strong>
                            
                            • Title: 20 points
                            • Meta Description: 15 points
                            • H1 Heading: 10 points
                            • Keyword in Introduction: 10 points
                            • Internal Link: 10 points
                            • Outbound Link: 10 points
                            • Image Alt Text: 10 points
                            • Readability: 10 points
                            • Canonical URL: 3 points
                            • Robots Settings: 2 points
                            
                            <em>Total: 100 points (weighted by importance)</em>
                        </div>
                    `);
                }
                
                // Add hover handlers to the gauge wrapper
                $('.health-gauge-wrapper').on('mouseenter', function(e) {
                    const $tooltip = $('#almaseo-gauge-tooltip');
                    $tooltip.css({
                        left: e.pageX + 15,
                        top: e.pageY - 10
                    }).fadeIn(200);
                });
                
                $('.health-gauge-wrapper').on('mousemove', function(e) {
                    const $tooltip = $('#almaseo-gauge-tooltip');
                    $tooltip.css({
                        left: e.pageX + 15,
                        top: e.pageY - 10
                    });
                });
                
                $('.health-gauge-wrapper').on('mouseleave', function() {
                    $('#almaseo-gauge-tooltip').fadeOut(200);
                });
            }
            
            // Initialize tooltip
            setupGaugeTooltip();
            
            // Handle recalculate button
            $('#almaseo-health-recalculate').on('click', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const postId = $btn.data('post-id') || <?php echo intval($post->ID); ?>;
                
                $btn.prop('disabled', true).find('.dashicons').addClass('spin');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'almaseo_health_recalculate',
                        post_id: postId,
                        nonce: '<?php echo esc_js(wp_create_nonce('almaseo_health_nonce')); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update score display
                            $('.score-number').text(response.data.score);
                            
                            // Redraw gauge
                            drawHealthGauge(response.data.score);
                            
                            // Update signals
                            if (response.data.breakdown) {
                                $.each(response.data.breakdown, function(signal, result) {
                                    // Match by data-signal (exact). The old fuzzy label-text
                                    // match dropped 'kw_intro' — "kw intro" isn't a substring
                                    // of "Keyword in First 100 Words", so that card never refreshed.
                                    const $signal = $('.almaseo-health-signal[data-signal="' + signal + '"]');

                                    if ($signal.length) {
                                        // Update icon
                                        $signal.find('.signal-icon').text(result.pass ? '✅' : '❌');
                                        
                                        // Update classes
                                        $signal.removeClass('pass fail').addClass(result.pass ? 'pass' : 'fail');
                                        
                                        // Update bar
                                        $signal.find('.signal-bar-fill')
                                            .removeClass('pass fail')
                                            .addClass(result.pass ? 'pass' : 'fail')
                                            .css('width', result.pass ? '100%' : '0%');
                                        
                                        // Update note
                                        $signal.find('.signal-note').text(result.note);
                                        
                                        // Show/hide Go to Field button
                                        if (result.pass) {
                                            $signal.find('.signal-goto-btn').hide();
                                        } else {
                                            $signal.find('.signal-goto-btn').show();
                                        }

                                        // Rebuild the readability per-check breakdown so
                                        // "what to fix" reflects the fresh calculation.
                                        if (signal === 'readability') {
                                            renderReadabilityDetail($signal, result.checks);
                                        }
                                    }
                                });
                                
                                // Update pass/fail counts
                                let passCount = 0, failCount = 0;
                                $.each(response.data.breakdown, function(signal, result) {
                                    if (result.pass) passCount++;
                                    else failCount++;
                                });
                                
                                $('.health-stat').first().find('strong').text(passCount);
                                $('.health-stat').last().find('strong').text(failCount);
                            }
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
                    }
                });
            });
            
            // Expand / collapse the Readability "what to fix" breakdown.
            // Delegated so it keeps working after a recalc rebuilds the list.
            $(document).on('click', '.signal-detail-toggle', function() {
                const $list = $(this).siblings('.signal-detail-list');
                const showing = $list.is(':visible');
                $list.toggle(!showing);
                $(this).text(showing
                    ? '<?php echo esc_js(__('Show what to fix', 'almaseo-seo-playground')); ?> ▾'
                    : '<?php echo esc_js(__('Hide breakdown', 'almaseo-seo-playground')); ?> ▴');
            });

            // (Re)build the per-check readability list from an AJAX response.
            function renderReadabilityDetail($signal, checks) {
                const $wrap = $signal.find('.signal-readability-detail');
                if (!$wrap.length) return;

                if (!checks || !checks.length) {
                    $wrap.html('<span class="signal-detail-hint" style="font-size: 12px; color: #646970; font-style: italic;"><?php echo esc_js(__('Click Recalculate to see the per-check breakdown.', 'almaseo-seo-playground')); ?></span>');
                    return;
                }

                let items = '';
                checks.forEach(function(c) {
                    const color = c.pass ? '#00a32a' : '#d63638';
                    const mark = c.pass ? '✓' : '✗';
                    items += '<li style="padding: 4px 0; border-bottom: 1px solid #f0f0f1; font-size: 12px;">'
                        + '<span class="signal-detail-icon" style="color: ' + color + ';">' + mark + '</span> '
                        + '<strong></strong> — <span class="signal-detail-tip"></span></li>';
                });
                const $list = $('<ul class="signal-detail-list" style="display: none; list-style: none; margin: 8px 0 0; padding: 8px 10px; background: #f6f7f7; border: 1px solid #e0e0e0; border-radius: 4px;"></ul>').html(items);
                // Set label/tip via .text() to avoid injecting markup from the API.
                $list.find('li').each(function(i) {
                    $(this).find('strong').text(checks[i].label || '');
                    $(this).find('.signal-detail-tip').text(checks[i].tip || '');
                });

                // Preserve whether the user had it expanded.
                const wasOpen = $wrap.find('.signal-detail-list').is(':visible');
                $wrap.empty()
                    .append('<button type="button" class="signal-detail-toggle button-link" style="font-size: 12px; cursor: pointer;"><?php echo esc_js(__('Show what to fix', 'almaseo-seo-playground')); ?> ▾</button>')
                    .append($list);
                if (wasOpen) {
                    $wrap.find('.signal-detail-toggle').trigger('click');
                }
            }

            // Handle Go to Field buttons
            $('.signal-goto-btn').on('click', function() {
                const fieldId = $(this).data('field');
                const $field = $('#' + fieldId);
                
                if ($field.length) {
                    // Scroll to field
                    $('html, body').animate({
                        scrollTop: $field.offset().top - 100
                    }, 500, function() {
                        // Focus the field
                        $field.focus().addClass('field-highlight');
                        
                        // Remove highlight after 2 seconds
                        setTimeout(function() {
                            $field.removeClass('field-highlight');
                        }, 2000);
                    });
                }
            });
        });
        </script>
        
        <style>
        @keyframes almaseo-field-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .almaseo-seo-playground .dashicons.spin {
            animation: almaseo-field-spin 1s linear infinite;
        }
        .almaseo-seo-playground .field-highlight {
            background-color: #fffbcc !important;
            border-color: #dba617 !important;
            box-shadow: 0 0 5px rgba(219, 166, 23, 0.5) !important;
            transition: all 0.3s ease;
        }
        </style>
        
        <!-- Search Console Tab -->
        <div class="almaseo-tab-panel" id="tab-search-console">
            <?php
            // Determine the current page URL for GSC lookups
            $gsc_page_url = get_permalink($post->ID);
            $gsc_post_id = $post->ID;
            ?>

            <!-- ═══ GSC Container — always rendered, JS manages states ═══ -->
            <div class="almaseo-gsc-container"
                 id="almaseo-gsc-container"
                 data-post-id="<?php echo esc_attr($gsc_post_id); ?>"
                 data-page-url="<?php echo esc_attr($gsc_page_url); ?>"
                 data-nonce="<?php echo esc_attr(wp_create_nonce('almaseo_gsc_nonce')); ?>"
                 data-connected="<?php echo esc_attr($is_connected ? '1' : '0'); ?>">

                <!-- ═══ State 1: Connection Issue (fallback) ═══ -->
                <div class="almaseo-gsc-state" id="gsc-state-not-connected" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#dba617" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">AlmaSEO connection not detected</h3>
                        <p class="gsc-state-description">
                            The plugin couldn't verify a connection to your AlmaSEO Dashboard. Please check your connection settings.
                        </p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=seo-playground-connection')); ?>" class="gsc-state-btn gsc-btn-secondary">
                            Check Connection Settings
                        </a>
                    </div>
                </div>

                <!-- ═══ State 2: Default — prompt to load GSC data ═══ -->
                <div class="almaseo-gsc-state" id="gsc-state-no-gsc">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                                <path d="M11 8v6"/>
                                <path d="M8 11h6"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">Google Search Console</h3>
                        <p class="gsc-state-description">
                            See how this page performs in Google Search — clicks, impressions, top queries, and ranking position — all right here while you edit.
                        </p>
                        <button type="button" id="gsc-connect-btn" class="gsc-state-btn gsc-btn-primary">
                            Load Search Data
                        </button>
                        <p class="gsc-state-hint">
                            Don't have Search Console connected yet?
                            <a href="https://app.almaseo.com/profile/google-services" target="_blank">Connect it in your Dashboard</a>
                        </p>
                    </div>
                </div>

                <!-- ═══ State 3: Loading ═══ -->
                <div class="almaseo-gsc-state" id="gsc-state-loading" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-loading-spinner"></div>
                        <h3 class="gsc-state-heading">Fetching search performance...</h3>
                        <p class="gsc-state-description">Loading Google Search Console data for this page.</p>
                    </div>
                    <!-- Skeleton placeholders -->
                    <div class="gsc-skeleton-metrics">
                        <div class="gsc-skeleton-card"></div>
                        <div class="gsc-skeleton-card"></div>
                        <div class="gsc-skeleton-card"></div>
                        <div class="gsc-skeleton-card"></div>
                    </div>
                </div>

                <!-- ═══ State 4: Page Not Indexed ═══ -->
                <div class="almaseo-gsc-state" id="gsc-state-not-indexed" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#dba617" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">This page isn't indexed yet</h3>
                        <p class="gsc-state-description">
                            Google hasn't indexed this URL. It may be too new, blocked by robots.txt, or missing from your sitemap.
                        </p>
                        <div class="gsc-state-actions">
                            <a href="https://search.google.com/search-console/inspect?resource_id=<?php echo esc_attr(urlencode(home_url())); ?>&id=<?php echo esc_attr(urlencode($gsc_page_url)); ?>" target="_blank" class="gsc-state-btn gsc-btn-secondary">
                                Request Indexing in GSC
                            </a>
                        </div>
                        <div class="gsc-state-tips">
                            <p><strong>Common reasons:</strong></p>
                            <ul>
                                <li>Page was recently published</li>
                                <li>Page is blocked by robots.txt or a noindex tag</li>
                                <li>Page is not linked from your sitemap</li>
                                <li>Page has a canonical pointing elsewhere</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- ═══ State 5: Indexed, No Data ═══ -->
                <div class="almaseo-gsc-state" id="gsc-state-no-data" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3v18h18"/>
                                <path d="M18 17V9"/>
                                <path d="M13 17V5"/>
                                <path d="M8 17v-3"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">No search data yet</h3>
                        <p class="gsc-state-description">
                            This page is indexed but hasn't received any impressions in the selected time period. This is normal for newer or low-traffic pages.
                        </p>
                        <div class="gsc-index-badge gsc-badge-indexed">
                            <span class="gsc-badge-dot"></span>
                            Indexed by Google
                        </div>
                    </div>
                </div>

                <!-- ═══ State 6: Data Available (Main View) ═══ -->
                <div class="almaseo-gsc-state" id="gsc-state-data" style="display: none;">

                    <!-- Tab Header -->
                    <div class="gsc-data-header">
                        <div class="gsc-data-title-row">
                            <div>
                                <h2 class="gsc-data-title">Search Performance for This Page</h2>
                                <div class="gsc-page-url" id="gsc-page-url" title="<?php echo esc_attr($gsc_page_url); ?>">
                                    <?php echo esc_html($gsc_page_url); ?>
                                </div>
                            </div>
                            <div class="gsc-index-badge gsc-badge-indexed" id="gsc-index-status">
                                <span class="gsc-badge-dot"></span>
                                <span id="gsc-index-label">Indexed</span>
                            </div>
                        </div>
                        <div class="gsc-data-context">
                            This data is specific to the page you are currently editing — not your entire site. Use it to understand how this page is performing in Google Search and identify opportunities to improve its rankings.
                        </div>
                        <div class="gsc-data-controls">
                            <select class="gsc-date-range-selector" id="gsc-date-range" aria-label="Date range">
                                <option value="7">Last 7 days</option>
                                <option value="28" selected>Last 28 days</option>
                                <option value="90">Last 90 days</option>
                            </select>
                            <button type="button" class="gsc-refresh-btn" id="gsc-refresh-btn" title="Refresh data">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Performance Metric Cards -->
                    <div class="gsc-metrics-grid">
                        <div class="gsc-metric-card gsc-metric-clicks">
                            <div class="gsc-metric-label">Clicks</div>
                            <div class="gsc-metric-value" id="gsc-clicks">--</div>
                            <div class="gsc-metric-trend" id="gsc-clicks-trend">
                                <span class="gsc-trend-arrow"></span>
                                <span class="gsc-trend-value"></span>
                                <span class="gsc-trend-label">vs prev period</span>
                            </div>
                        </div>
                        <div class="gsc-metric-card gsc-metric-impressions">
                            <div class="gsc-metric-label">Impressions</div>
                            <div class="gsc-metric-value" id="gsc-impressions">--</div>
                            <div class="gsc-metric-trend" id="gsc-impressions-trend">
                                <span class="gsc-trend-arrow"></span>
                                <span class="gsc-trend-value"></span>
                                <span class="gsc-trend-label">vs prev period</span>
                            </div>
                        </div>
                        <div class="gsc-metric-card gsc-metric-ctr">
                            <div class="gsc-metric-label">Avg CTR</div>
                            <div class="gsc-metric-value" id="gsc-ctr">--</div>
                            <div class="gsc-metric-trend" id="gsc-ctr-trend">
                                <span class="gsc-trend-arrow"></span>
                                <span class="gsc-trend-value"></span>
                                <span class="gsc-trend-label">vs prev period</span>
                            </div>
                        </div>
                        <div class="gsc-metric-card gsc-metric-position">
                            <div class="gsc-metric-label">Avg Position</div>
                            <div class="gsc-metric-value" id="gsc-position">--</div>
                            <div class="gsc-metric-trend" id="gsc-position-trend">
                                <span class="gsc-trend-arrow"></span>
                                <span class="gsc-trend-value"></span>
                                <span class="gsc-trend-label">vs prev period</span>
                            </div>
                        </div>
                    </div>

                    <!-- Top Queries Table -->
                    <div class="gsc-queries-section">
                        <div class="gsc-queries-header">
                            <h3 class="gsc-queries-title">Top Search Queries</h3>
                            <span class="gsc-queries-subtitle">Queries that surfaced this page in search results</span>
                        </div>
                        <div class="gsc-queries-table-wrapper">
                            <table class="gsc-queries-table" id="gsc-queries-table">
                                <thead>
                                    <tr>
                                        <th class="gsc-col-query">Query</th>
                                        <th class="gsc-col-clicks">Clicks</th>
                                        <th class="gsc-col-impressions">Impr.</th>
                                        <th class="gsc-col-ctr">CTR</th>
                                        <th class="gsc-col-position">Position</th>
                                    </tr>
                                </thead>
                                <tbody id="gsc-queries-tbody">
                                    <!-- Rows populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        <div class="gsc-queries-footer" id="gsc-queries-footer" style="display: none;">
                            <a href="https://app.almaseo.com" target="_blank" class="gsc-view-all-link">
                                View all queries in Dashboard &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- Opportunities (computed client-side from the queries above) -->
                    <div class="gsc-opportunities" id="gsc-opportunities" style="display: none; padding: 0 20px;"></div>

                    <!-- Last Updated -->
                    <div class="gsc-last-updated" id="gsc-last-updated"></div>
                </div>

                <!-- ═══ State 7: API Error ═══ -->
                <div class="almaseo-gsc-state" id="gsc-state-error" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#d63638" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">Couldn't load search data</h3>
                        <p class="gsc-state-description" id="gsc-error-message">
                            An error occurred while fetching Search Console data. Please try again.
                        </p>
                        <button type="button" class="gsc-state-btn gsc-btn-primary" id="gsc-retry-btn">
                            Retry
                        </button>
                    </div>
                </div>

                <!-- ═══ State 8: GSC Token Expired ═══ -->
                <div class="almaseo-gsc-state" id="gsc-state-token-expired" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#dba617" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 9.9-1"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">Search Console access needs renewal</h3>
                        <p class="gsc-state-description">
                            Your Google authorization has expired or been revoked. Please re-connect Google Search Console in your AlmaSEO Dashboard.
                        </p>
                        <a href="https://app.almaseo.com/profile/google-services" target="_blank" class="gsc-state-btn gsc-btn-primary">
                            Re-connect in Dashboard
                            <span class="gsc-btn-arrow">&rarr;</span>
                        </a>
                    </div>
                </div>

            </div>
            <!-- End almaseo-gsc-container -->

            <style>
                /* ── GSC State Screens ── */
                .almaseo-gsc-state { padding: 20px; }
                .gsc-state-card {
                    text-align: center;
                    padding: 40px 30px;
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                }
                .gsc-state-icon { margin-bottom: 16px; }
                .gsc-state-icon svg { display: inline-block; }
                .gsc-state-heading {
                    font-size: 18px;
                    font-weight: 600;
                    color: #1e293b;
                    margin: 0 0 8px 0;
                }
                .gsc-state-description {
                    font-size: 14px;
                    color: #64748b;
                    margin: 0 0 20px 0;
                    max-width: 480px;
                    margin-left: auto;
                    margin-right: auto;
                    line-height: 1.5;
                }
                .gsc-state-btn {
                    display: inline-block;
                    padding: 10px 24px;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 600;
                    text-decoration: none;
                    cursor: pointer;
                    border: none;
                    transition: all 0.2s ease;
                }
                .gsc-btn-primary {
                    background: #2271b1;
                    color: #fff;
                }
                .gsc-btn-primary:hover { background: #135e96; color: #fff; }
                .gsc-btn-secondary {
                    background: #f0f6fc;
                    color: #2271b1;
                    border: 1px solid #2271b1;
                }
                .gsc-btn-secondary:hover { background: #e1ecf7; }
                .gsc-btn-arrow { margin-left: 4px; }
                .gsc-state-hint {
                    font-size: 12px;
                    color: #94a3b8;
                    margin-top: 10px;
                }
                .gsc-state-tips {
                    text-align: left;
                    max-width: 400px;
                    margin: 20px auto 0;
                    font-size: 13px;
                    color: #64748b;
                }
                .gsc-state-tips ul {
                    margin: 6px 0 0 18px;
                    padding: 0;
                }
                .gsc-state-tips li { margin-bottom: 4px; }
                .gsc-state-actions { margin-bottom: 16px; }

                /* ── Loading Spinner ── */
                .gsc-loading-spinner {
                    width: 40px;
                    height: 40px;
                    border: 3px solid #e2e8f0;
                    border-top-color: #2271b1;
                    border-radius: 50%;
                    animation: gsc-spin 0.8s linear infinite;
                    margin: 0 auto 16px;
                }
                @keyframes gsc-spin { to { transform: rotate(360deg); } }

                /* ── Skeleton Placeholders ── */
                .gsc-skeleton-metrics {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 12px;
                    padding: 0 20px 20px;
                }
                .gsc-skeleton-card {
                    height: 80px;
                    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
                    background-size: 200% 100%;
                    animation: gsc-shimmer 1.5s infinite;
                    border-radius: 8px;
                }
                @keyframes gsc-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

                /* ── Index Badge ── */
                .gsc-index-badge {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                }
                .gsc-badge-indexed {
                    background: #d4edda;
                    color: #155724;
                }
                .gsc-badge-dot {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    background: #28a745;
                    display: inline-block;
                }

                /* ── Data Header ── */
                .gsc-data-header {
                    padding: 20px 20px 0;
                }
                .gsc-data-title-row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 12px;
                }
                .gsc-data-title {
                    font-size: 18px;
                    font-weight: 600;
                    color: #1e293b;
                    margin: 0 0 4px 0;
                }
                .gsc-page-url {
                    font-size: 12px;
                    color: #64748b;
                    max-width: 400px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    font-family: monospace;
                }
                .gsc-data-context {
                    font-size: 13px;
                    color: #64748b;
                    line-height: 1.5;
                    padding: 10px 14px;
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 6px;
                    margin: 12px 0;
                }
                .gsc-data-controls {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .gsc-date-range-selector {
                    padding: 6px 12px;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    font-size: 13px;
                    color: #1e293b;
                    background: #fff;
                    cursor: pointer;
                }
                .gsc-refresh-btn {
                    padding: 6px 8px;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    background: #fff;
                    cursor: pointer;
                    color: #50575e;
                    display: flex;
                    align-items: center;
                    transition: all 0.2s;
                }
                .gsc-refresh-btn:hover { background: #f0f0f1; color: #2271b1; }
                .gsc-refresh-btn.spinning svg {
                    animation: gsc-spin 0.8s linear infinite;
                }

                /* ── Metric Cards Grid ── */
                .gsc-metrics-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 12px;
                    padding: 16px 20px;
                }
                .gsc-metric-card {
                    background: #fff;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 16px;
                    border-top: 3px solid #e2e8f0;
                }
                .gsc-metric-clicks { border-top-color: #4285f4; }
                .gsc-metric-impressions { border-top-color: #5e35b1; }
                .gsc-metric-ctr { border-top-color: #00897b; }
                .gsc-metric-position { border-top-color: #e8710a; }
                .gsc-metric-label {
                    font-size: 12px;
                    font-weight: 600;
                    color: #64748b;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin-bottom: 4px;
                }
                .gsc-metric-value {
                    font-size: 28px;
                    font-weight: 700;
                    color: #1e293b;
                    line-height: 1.2;
                }
                .gsc-metric-trend {
                    margin-top: 6px;
                    font-size: 12px;
                    color: #64748b;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                }
                .gsc-trend-up { color: #16a34a; }
                .gsc-trend-down { color: #dc2626; }
                .gsc-trend-neutral { color: #64748b; }
                /* For position, lower is better so invert colors */
                .gsc-metric-position .gsc-trend-up { color: #dc2626; }
                .gsc-metric-position .gsc-trend-down { color: #16a34a; }

                /* ── Queries Table ── */
                .gsc-queries-section {
                    padding: 0 20px 20px;
                }
                .gsc-queries-header {
                    margin-bottom: 12px;
                }
                .gsc-queries-title {
                    font-size: 15px;
                    font-weight: 600;
                    color: #1e293b;
                    margin: 0 0 2px 0;
                }
                .gsc-queries-subtitle {
                    font-size: 12px;
                    color: #94a3b8;
                }
                .gsc-queries-table-wrapper {
                    overflow-x: auto;
                }
                .gsc-queries-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 13px;
                }
                .gsc-queries-table th {
                    text-align: left;
                    padding: 8px 12px;
                    background: #f8fafc;
                    border-bottom: 2px solid #e2e8f0;
                    font-weight: 600;
                    color: #475569;
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .gsc-queries-table td {
                    padding: 10px 12px;
                    border-bottom: 1px solid #f1f5f9;
                    color: #334155;
                }
                .gsc-queries-table tr:hover td {
                    background: #f8fafc;
                }
                .gsc-col-query { min-width: 200px; }
                .gsc-col-clicks,
                .gsc-col-impressions,
                .gsc-col-ctr,
                .gsc-col-position {
                    text-align: right;
                    width: 80px;
                }
                .gsc-queries-table th.gsc-col-clicks,
                .gsc-queries-table th.gsc-col-impressions,
                .gsc-queries-table th.gsc-col-ctr,
                .gsc-queries-table th.gsc-col-position {
                    text-align: right;
                }
                .gsc-queries-footer {
                    text-align: center;
                    padding-top: 12px;
                }
                .gsc-view-all-link {
                    font-size: 13px;
                    color: #2271b1;
                    text-decoration: none;
                }
                .gsc-view-all-link:hover { text-decoration: underline; }

                /* ── Last Updated ── */
                .gsc-last-updated {
                    padding: 10px 20px;
                    text-align: right;
                    font-size: 11px;
                    color: #94a3b8;
                }

                /* ── Responsive ── */
                @media (max-width: 782px) {
                    .gsc-metrics-grid { grid-template-columns: repeat(2, 1fr); }
                    .gsc-skeleton-metrics { grid-template-columns: repeat(2, 1fr); }
                    .gsc-metric-value { font-size: 22px; }
                }
            </style>

            <script>
            jQuery(document).ready(function($) {
                var $container = $('#almaseo-gsc-container');
                if (!$container.length) return;

                var postId = $container.data('post-id');
                var pageUrl = $container.data('page-url');
                var nonce = $container.data('nonce');
                var isConnected = $container.data('connected') === 1 || $container.data('connected') === '1';

                // Show a specific state, hide all others
                function showState(stateId) {
                    $container.find('.almaseo-gsc-state').hide();
                    $('#' + stateId).show();
                }

                // If plugin connection is not detected, show fallback and stop
                if (!isConnected) {
                    showState('gsc-state-not-connected');
                    return;
                }

                // Connection exists — default is State 2 (GSC not connected).
                // On tab click we'll fetch data; if GSC IS connected the API
                // will return data and we'll transition to the right state.

                // Format numbers with commas
                function formatNumber(n) {
                    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }

                // Update a trend indicator
                // Note: the position card's "lower is better" colour inversion is
                // handled in CSS (.gsc-metric-position .gsc-trend-up/down), so no
                // per-metric flag is needed here.
                function updateTrend($el, current, previous) {
                    if (previous === null || previous === undefined) {
                        $el.find('.gsc-trend-value').text('--');
                        $el.find('.gsc-trend-arrow').text('');
                        return;
                    }
                    var diff = current - previous;
                    var arrow, cls;

                    if (Math.abs(diff) < 0.01) {
                        arrow = '→'; cls = 'gsc-trend-neutral';
                    } else if (diff > 0) {
                        arrow = '↑'; cls = 'gsc-trend-up';
                    } else {
                        arrow = '↓'; cls = 'gsc-trend-down';
                    }

                    $el.removeClass('gsc-trend-up gsc-trend-down gsc-trend-neutral').addClass(cls);
                    $el.find('.gsc-trend-arrow').text(arrow);

                    // With no baseline (previous period was 0) a percentage is
                    // meaningless — show "New" instead of a misleading 0%.
                    if (previous === 0) {
                        $el.find('.gsc-trend-value').text(Math.abs(diff) < 0.01 ? '0%' : 'New');
                    } else {
                        $el.find('.gsc-trend-value').text(Math.abs((diff / previous) * 100).toFixed(1) + '%');
                    }
                }

                // Render the data view
                // Surface actionable opportunities from the top-queries list:
                //  - CTR opportunities: page-1 queries (pos <= 10) with meaningful
                //    impressions but lower CTR than typical for their position.
                //  - Striking distance: page-2 queries (pos 11-20) close to page 1.
                function renderOpportunities(queries) {
                    var $box = $('#gsc-opportunities').empty();
                    if (!queries || !queries.length) { $box.hide(); return; }

                    function expectedCtr(pos) {
                        var t = {1:0.28,2:0.15,3:0.11,4:0.08,5:0.06,6:0.04,7:0.035,8:0.03,9:0.028,10:0.025};
                        return t[Math.max(1, Math.min(10, Math.round(pos)))] || 0.025;
                    }
                    var ctrOpps = [], striking = [];
                    queries.forEach(function(q) {
                        var pos = Number(q.position) || 0, impr = Number(q.impressions) || 0, ctr = Number(q.ctr) || 0;
                        if (pos >= 1 && pos <= 10 && impr >= 20 && ctr < expectedCtr(pos) * 0.6) { ctrOpps.push(q); }
                        else if (pos > 10 && pos <= 20 && impr >= 10) { striking.push(q); }
                    });
                    function topBy(a, n) {
                        return a.sort(function(x, y){ return (Number(y.impressions)||0) - (Number(x.impressions)||0); }).slice(0, n);
                    }
                    ctrOpps = topBy(ctrOpps, 3); striking = topBy(striking, 3);
                    if (!ctrOpps.length && !striking.length) { $box.hide(); return; }

                    function groupHtml(title, sub, color, items, showCtr) {
                        var h = '<div style="margin: 0 0 10px; padding: 12px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">';
                        h += '<div style="font-size:12px; font-weight:700; color:' + color + ';">' + title + '</div>';
                        h += '<p style="font-size:11px; color:#64748b; margin:4px 0 8px;">' + sub + '</p>';
                        h += '<ul style="margin:0; padding:0; list-style:none;">';
                        items.forEach(function(q) {
                            var meta = 'pos ' + (Number(q.position)||0).toFixed(1) + (showCtr ? ' &middot; ' + ((Number(q.ctr)||0)*100).toFixed(1) + '% CTR' : '') + ' &middot; ' + formatNumber(Number(q.impressions)||0) + ' impr';
                            h += '<li style="padding:4px 0; border-bottom:1px solid #eef2f7; font-size:12px; color:#334155;"><span class="gsc-opp-q" style="font-weight:600;"></span> <span style="color:#94a3b8; font-size:11px;">' + meta + '</span></li>';
                        });
                        return h + '</ul></div>';
                    }

                    var html = '<div style="font-size:13px; font-weight:700; color:#1e293b; margin:14px 0 8px;">Opportunities for this page</div>';
                    if (ctrOpps.length) { html += groupHtml('&uarr; Improve click-through', 'You rank on page 1 for these but get fewer clicks than typical for that position — a sharper title or meta description could win them.', '#b45309', ctrOpps, true); }
                    if (striking.length) { html += groupHtml('&#9678; Almost on page 1', 'These rank just below page 1 — a little optimization could push them up.', '#1d4ed8', striking, false); }

                    $box.html(html);
                    // Inject the query text via .text() so a query string can't break the markup.
                    var allQ = ctrOpps.concat(striking);
                    $box.find('.gsc-opp-q').each(function(i){ $(this).text(allQ[i] ? allQ[i].query : ''); });
                    $box.show();
                }

                function renderData(data) {
                    var m = data.metrics;

                    // Summary cards (coerce to numbers — values come from the API)
                    $('#gsc-clicks').text(formatNumber(Number(m.clicks) || 0));
                    $('#gsc-impressions').text(formatNumber(Number(m.impressions) || 0));
                    $('#gsc-ctr').text(((Number(m.ctr) || 0) * 100).toFixed(1) + '%');
                    $('#gsc-position').text((Number(m.position) || 0).toFixed(1));

                    // Trends (compare to previous period)
                    if (data.previous) {
                        var p = data.previous;
                        updateTrend($('#gsc-clicks-trend'), m.clicks, p.clicks);
                        updateTrend($('#gsc-impressions-trend'), m.impressions, p.impressions);
                        updateTrend($('#gsc-ctr-trend'), m.ctr, p.ctr);
                        updateTrend($('#gsc-position-trend'), m.position, p.position);
                    }

                    // Queries table
                    var $tbody = $('#gsc-queries-tbody').empty();
                    if (data.queries && data.queries.length) {
                        $.each(data.queries.slice(0, 10), function(i, q) {
                            $tbody.append(
                                '<tr>' +
                                '<td class="gsc-col-query">' + $('<span>').text(q.query).html() + '</td>' +
                                '<td class="gsc-col-clicks">' + formatNumber(Number(q.clicks) || 0) + '</td>' +
                                '<td class="gsc-col-impressions">' + formatNumber(Number(q.impressions) || 0) + '</td>' +
                                '<td class="gsc-col-ctr">' + ((Number(q.ctr) || 0) * 100).toFixed(1) + '%</td>' +
                                '<td class="gsc-col-position">' + (Number(q.position) || 0).toFixed(1) + '</td>' +
                                '</tr>'
                            );
                        });
                        if (data.queries.length > 10) {
                            $('#gsc-queries-footer').show();
                        }
                    } else {
                        $tbody.append('<tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:20px;">No queries found for this page</td></tr>');
                    }

                    // Last updated
                    $('#gsc-last-updated').text('Data last updated: ' + (data.last_updated || 'just now'));

                    // Actionable opportunities computed from the query data
                    renderOpportunities(data.queries);

                    showState('gsc-state-data');
                }

                // Fetch GSC data from AlmaSEO dashboard
                function fetchGSCData() {
                    var days = $('#gsc-date-range').val() || 28;
                    showState('gsc-state-loading');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'almaseo_fetch_gsc_page_data',
                            nonce: nonce,
                            post_id: postId,
                            page_url: pageUrl,
                            days: days
                        },
                        timeout: 30000,
                        success: function(response) {
                            if (!response.success) {
                                handleError(response.data);
                                return;
                            }
                            var d = response.data;

                            // Route to the right state based on API response
                            if (d.status === 'gsc_not_connected') {
                                showState('gsc-state-no-gsc');
                            } else if (d.status === 'token_expired') {
                                showState('gsc-state-token-expired');
                            } else if (d.status === 'not_indexed') {
                                showState('gsc-state-not-indexed');
                            } else if (d.status === 'no_data') {
                                showState('gsc-state-no-data');
                            } else if (d.status === 'ok') {
                                renderData(d);
                            } else {
                                handleError({ message: 'Unexpected response from server.' });
                            }
                        },
                        error: function(xhr, status) {
                            var msg = status === 'timeout'
                                ? 'The request timed out. Please try again.'
                                : 'Could not reach the server. Check your connection.';
                            handleError({ message: msg });
                        }
                    });
                }

                function handleError(data) {
                    var msg = (data && data.message) ? data.message : 'An unexpected error occurred.';
                    $('#gsc-error-message').text(msg);
                    showState('gsc-state-error');
                }

                // Track whether GSC has been confirmed connected this session
                var gscConfirmed = false;
                var gscAttempted = false;

                // Event handlers (only active once data view is showing)
                $('#gsc-date-range').on('change', function() {
                    if (gscConfirmed) fetchGSCData();
                });
                $('#gsc-refresh-btn').on('click', function() {
                    if (!gscConfirmed) return;
                    $(this).addClass('spinning');
                    fetchGSCData();
                    setTimeout(function() { $('#gsc-refresh-btn').removeClass('spinning'); }, 1000);
                });
                $('#gsc-retry-btn').on('click', fetchGSCData);

                // "Connect Search Console" button — triggers the first data fetch
                // to check if GSC is actually connected on the dashboard
                $(document).on('click', '#gsc-connect-btn', function(e) {
                    e.preventDefault();
                    fetchGSCData();
                });

                // On successful data load, mark GSC as confirmed so future
                // tab visits auto-fetch without showing the connect screen
                var origRenderData = renderData;
                renderData = function(data) {
                    gscConfirmed = true;
                    origRenderData(data);
                };

                // Tab activation — auto-load the data the first time the tab is
                // opened on this page (and again after a reload), so it persists
                // without re-clicking "Load Search Data". Attempt at most once per
                // page load even if GSC isn't connected or returns no data; the
                // refresh button and date-range selector handle re-fetching after.
                $(document).on('click', '.almaseo-tab-btn[data-tab="search-console"]', function() {
                    if (!gscAttempted) {
                        gscAttempted = true;
                        fetchGSCData();
                    }
                });
            });
            </script>
        </div>
        <!-- End Search Console Tab -->
        
        <!-- Schema & Meta Tab -->
        <div class="almaseo-tab-panel" id="tab-schema-meta">
            <!-- Tab Header -->
            <div class="almaseo-schema-meta-header">
                <h2 class="almaseo-schema-meta-title">Schema & Meta</h2>
                <p class="almaseo-schema-meta-subtitle">Control how search engines and social networks understand this page.</p>
                <?php
                // Add help text for Schema & Meta
                if (function_exists('almaseo_render_help')) {
                    almaseo_render_help(
                        __('Schema helps search engines understand your content type (e.g., Article, Product). Add types that match the page.', 'almaseo-seo-playground'),
                        __('Avoid duplicate schema from multiple plugins on the same page.', 'almaseo-seo-playground')
                    );
                }
                ?>
            </div>
            
            <!-- Connection Status Row -->
            <?php 
            $is_connected = seo_playground_is_alma_connected();
            if (!$is_connected): ?>
            <div class="almaseo-connection-notice" style="margin: 20px 0; padding: 15px; background: #f0f6fc; border-left: 3px solid #2271b1; border-radius: 3px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong style="color: #0c5460;">Connect to AlmaSEO to unlock advanced schema types and dashboard presets.</strong>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">Free features like Article schema and meta robots are always available.</p>
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=seo-playground-connection')); ?>" class="button button-secondary" style="white-space: nowrap;">Connect Now →</a>
            </div>
            <?php else: 
                $last_sync = get_option('almaseo_last_sync', '');
                $sync_text = $last_sync ? human_time_diff(strtotime($last_sync)) . ' ago' : 'Never';
            ?>
            <div class="almaseo-connection-status" style="margin: 20px 0; padding: 10px 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 20px; display: inline-block; color: #155724; font-size: 13px;">
                ✓ Connected to AlmaSEO • Last sync: <?php echo esc_html($sync_text); ?>
            </div>
            <?php endif; ?>

            <!-- Cornerstone Content -->
            <div class="almaseo-card" style="margin-bottom: 0;">
                <div class="almaseo-card-body" style="padding: 12px 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="almaseo_is_cornerstone" value="1"
                               <?php checked( get_post_meta( $post->ID, '_almaseo_is_cornerstone', true ) ); ?> />
                        <span class="dashicons dashicons-star-filled" style="color: #dba617;"></span>
                        <strong><?php esc_html_e( 'Cornerstone Content', 'almaseo-seo-playground' ); ?></strong>
                        <span style="color: #94a3b8; font-size: 12px; font-weight: normal; margin-left: 4px;"><?php esc_html_e( '— Mark as a key page for your SEO strategy', 'almaseo-seo-playground' ); ?></span>
                    </label>
                    <?php
                    $cs_suggested = get_post_meta( $post->ID, '_almaseo_cornerstone_suggested', true );
                    $cs_current   = get_post_meta( $post->ID, '_almaseo_is_cornerstone', true );
                    if ( $cs_suggested && ! $cs_current ) :
                        $cs_score  = get_post_meta( $post->ID, '_almaseo_cornerstone_score', true );
                        $cs_reason = get_post_meta( $post->ID, '_almaseo_cornerstone_reason', true );
                    ?>
                    <div style="margin-top: 8px; padding: 8px 12px; background: linear-gradient(135deg, #f0f4ff 0%, #f8f9ff 100%); border: 1px solid #d0d5ff; border-radius: 4px; font-size: 12px;">
                        <span style="font-weight: 600; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Alma</span>
                        <?php
                        /* translators: %s: cornerstone confidence score wrapped in <strong> */
                        echo wp_kses( sprintf( __( 'AlmaSEO suggests marking this as cornerstone content (score: %s/100).', 'almaseo-seo-playground' ), '<strong>' . intval( $cs_score ) . '</strong>' ), array( 'strong' => array() ) ); ?>
                        <?php if ( $cs_reason ) : ?>
                        <br><span style="color: #666;"><?php echo esc_html( $cs_reason ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Meta Robots Card -->
            <div class="almaseo-card">
                <div class="almaseo-card-header">
                    <h3 class="almaseo-card-title">
                        <span class="card-icon">🤖</span>
                        Meta Robots
                    </h3>
                    <p class="almaseo-card-description">Control how search engines crawl and index this page</p>
                </div>
                <div class="almaseo-card-body">
                    <div class="almaseo-meta-robots-grid">
                        <!-- Index toggle (off = noindex) -->
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_index" 
                                       name="almaseo_robots_index" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_index', true) !== 'noindex'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Index</span>
                            </label>
                            <p class="option-description">Allow search engines to show this page in search results</p>
                        </div>
                        
                        <!-- Follow toggle (off = nofollow) -->
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_follow" 
                                       name="almaseo_robots_follow" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_follow', true) !== 'nofollow'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Follow</span>
                            </label>
                            <p class="option-description">Allow search engines to follow links on this page</p>
                        </div>
                        
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_archive" 
                                       name="almaseo_robots_archive" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_archive', true) !== 'noarchive'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Archive</span>
                            </label>
                            <p class="option-description">Allow search engines to show cached versions</p>
                        </div>
                        
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_snippet" 
                                       name="almaseo_robots_snippet" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_snippet', true) !== 'nosnippet'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Snippet</span>
                            </label>
                            <p class="option-description">Allow search engines to show text snippets</p>
                        </div>
                        
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_imageindex" 
                                       name="almaseo_robots_imageindex" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_imageindex', true) !== 'noimageindex'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Image Index</span>
                            </label>
                            <p class="option-description">Allow images to appear in search results</p>
                        </div>
                        
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_translate" 
                                       name="almaseo_robots_translate" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_translate', true) !== 'notranslate'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Translate</span>
                            </label>
                            <p class="option-description">Allow translation services to translate this page</p>
                        </div>
                    </div>
                    
                    <div class="meta-robots-preview">
                        <h4>Preview:</h4>
                        <code id="meta-robots-preview-code">&lt;meta name="robots" content="index, follow" /&gt;</code>
                    </div>
                </div>
            </div>
            
            <!-- Canonical URL Card -->
            <div class="almaseo-card">
                <div class="almaseo-card-header">
                    <h3 class="almaseo-card-title">
                        <span class="card-icon">🔗</span>
                        Canonical URL
                    </h3>
                    <p class="almaseo-card-description">Specify the preferred version of this page to prevent duplicate content issues</p>
                </div>
                <div class="almaseo-card-body">
                    <div class="almaseo-field-group">
                        <label for="almaseo_canonical_url">Canonical URL</label>
                        <input type="url" 
                               id="almaseo_canonical_url" 
                               name="almaseo_canonical_url" 
                               value="<?php echo esc_attr(get_post_meta($post->ID, '_almaseo_canonical_url', true)); ?>"
                               placeholder="<?php echo esc_attr(get_permalink($post->ID)); ?>"
                               class="almaseo-input">
                        <p class="field-hint">Leave empty to use the default permalink. Only change if this content exists at another URL.</p>
                    </div>
                </div>
            </div>
            
            <!-- Schema Markup Card -->
            <div class="almaseo-card">
                <div class="almaseo-card-header">
                    <h3 class="almaseo-card-title">
                        <span class="card-icon">📋</span>
                        Schema Markup
                    </h3>
                    <p class="almaseo-card-description">Add structured data to help search engines understand your content</p>
                </div>
                <div class="almaseo-card-body">
                    <div class="almaseo-field-group">
                        <label for="almaseo_schema_type">Schema Type</label>
                        <p class="field-hint" style="margin: 0 0 8px 0;">Pick the type that matches the <strong>main</strong> content actually on this page. Schema should describe what's really there — don't add a type for content the page doesn't have.</p>
                        <select id="almaseo_schema_type"
                                name="almaseo_schema_type" 
                                class="almaseo-select">
                            <?php 
                            $current_schema = get_post_meta($post->ID, '_almaseo_schema_type', true) ?: 'Article';
                            ?>
                            <?php
                            // Gate Pro types on the actual Pro-feature check, not just the
                            // cloud-connection state. almaseo_feature_available('schema_advanced')
                            // resolves to almaseo_is_pro_active() which defaults to 'pro' until
                            // server-side tier sync is wired up — so local/dev sites unlock by
                            // default, matching the behaviour the rest of the metabox uses.
                            $advanced_unlocked = almaseo_feature_available('schema_advanced');
                            $schema_options = array(
                                array('value' => 'Article',       'label' => 'Article (BlogPosting) (Free)', 'locked' => false),
                                array('value' => 'FAQPage',       'label' => 'FAQPage',                      'locked' => !$advanced_unlocked),
                                array('value' => 'HowTo',         'label' => 'HowTo',                        'locked' => !$advanced_unlocked),
                                array('value' => 'LocalBusiness', 'label' => 'LocalBusiness',                'locked' => !$advanced_unlocked),
                                array('value' => 'Service',       'label' => 'Service (Service / Offering)',  'locked' => !$advanced_unlocked),
                                array('value' => 'MusicGroup',    'label' => 'MusicGroup (Band/Artist)',     'locked' => !$advanced_unlocked),
                                array('value' => 'Person',        'label' => 'Person (Author/Profile)',      'locked' => !$advanced_unlocked),
                                array('value' => 'Organization',  'label' => 'Organization (Company/NGO)',   'locked' => !$advanced_unlocked),
                                array('value' => 'Product',       'label' => 'Product (E-commerce)',         'locked' => !$advanced_unlocked),
                                array('value' => 'Event',         'label' => 'Event (Concert/Conference)',   'locked' => !$advanced_unlocked),
                                array('value' => 'Recipe',        'label' => 'Recipe (Food/Cooking)',        'locked' => !$advanced_unlocked),
                            );
                            
                            foreach ($schema_options as $option): ?>
                                <option value="<?php echo esc_attr($option['value']); ?>" 
                                        <?php selected($current_schema, $option['value']); ?>
                                        <?php if ($option['locked']) { echo 'disabled aria-disabled="true" class="is-locked"'; } ?>
                                        data-locked="<?php echo esc_attr($option['locked'] ? '1' : '0'); ?>">
                                    <?php echo esc_html($option['label']); ?><?php echo esc_html($option['locked'] ? ' 🔒' : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Smart fallback hints for Article schema -->
                        <?php if ($current_schema === 'Article'): ?>
                        <div class="schema-fallback-hint" style="margin-top: 10px; padding: 10px; background: #f0f6fc; border-left: 3px solid #2271b1; border-radius: 3px;">
                            <small style="color: #2c3338;">
                                <strong>Auto-fill enabled:</strong> Headline and description will auto-fill from your SEO title and description if left blank.
                                The image will auto-use your OG image or featured image.
                            </small>
                        </div>
                        <?php endif; ?>

                        <!-- Image reminder — every schema type in the dropdown above
                             accepts an image, and Google's Rich Results test flags
                             "Missing field 'image' (optional)" as a non-critical issue
                             when none is emitted. The schema builder reads
                             _almaseo_og_image first, then falls back to the post's
                             featured image; this nudge points users at both. -->
                        <div class="almaseo-schema-image-reminder" style="margin-top: 8px; padding: 8px 10px; background: #f6f7f7; border-left: 3px solid #8c8f94; border-radius: 3px;">
                            <small style="color: #50575e; line-height: 1.5; display: block;">
                                <strong><?php esc_html_e('Image reminder:', 'almaseo-seo-playground'); ?></strong>
                                <?php
                                printf(
                                    /* translators: %1$s: "Featured Image" (bold), %2$s: "OG Image URL" (bold), %3$s: Google warning text (italic). */
                                    esc_html__( 'Set a %1$s on this page (or fill the %2$s field below) to avoid Google\'s %3$s non-critical warning.', 'almaseo-seo-playground' ),
                                    '<strong>' . esc_html__( 'Featured Image', 'almaseo-seo-playground' ) . '</strong>',
                                    '<strong>' . esc_html__( 'OG Image URL', 'almaseo-seo-playground' ) . '</strong>',
                                    '<em>"' . esc_html__( 'Missing field \'image\' (optional)', 'almaseo-seo-playground' ) . '"</em>'
                                );
                                ?>
                            </small>
                        </div>
                        
                        <!-- Locked schema upsell notice -->
                        <div id="schema-locked-notice" style="display: none; margin-top: 10px; padding: 10px; background: #fff8e5; border-left: 3px solid #dba617; border-radius: 3px;">
                            <small style="color: #2c3338;">
                                Advanced schema types are available with an AlmaSEO connection.
                                <a href="#tab-unlock-features" class="almaseo-tab-link">Connect now →</a>
                            </small>
                        </div>

                    <!-- Advanced Schema (Pro) -->
                    <div class="almaseo-field-group" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dcdcde;">
                        <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 600; color: #1d2327;">
                            Type Details &amp; Advanced Schema (Pro)
                        </h4>
                        <p class="field-hint" style="margin: 0 0 15px 0;">Configure details for the schema type you selected above — the right fields appear automatically based on your choice (e.g. address &amp; hours for LocalBusiness).</p>

                        <?php if (!almaseo_feature_available('schema_advanced')): ?>
                            <!-- Free Tier: Lock Card -->
                            <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 2px dashed #cbd5e1; border-radius: 8px; padding: 20px; text-align: center;">
                                <span class="dashicons dashicons-lock" style="font-size: 36px; width: 36px; height: 36px; color: #94a3b8; margin-bottom: 10px;"></span>
                                <p style="margin: 0 0 10px 0; font-weight: 600; color: #1e293b;">Advanced Schema Types</p>
                                <p style="margin: 0 0 15px 0; color: #64748b; font-size: 13px;">
                                    Unlock FAQPage, HowTo, Service, LocalBusiness, and more advanced schema types.
                                </p>
                                <a href="https://almaseo.com/pricing" target="_blank" class="button button-secondary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                                    Upgrade to Pro
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Pro Tier: Full Controls -->
                            <?php
                            $primary_type = get_post_meta($post->ID, '_almaseo_schema_primary_type', true);
                            $disable_advanced = get_post_meta($post->ID, '_almaseo_schema_disable', true);
                            ?>

                            <!-- Save reminder — fields below are post meta, not a separate
                                 settings page; users sometimes miss that they need to click
                                 WordPress's Update button for changes to persist. -->
                            <p style="margin: 0 0 12px 0; padding: 8px 10px; background: #fffbe6; border: 1px solid #f5d76e; border-radius: 4px; font-size: 12px; color: #5c4a00; line-height: 1.4;">
                                💾 <?php esc_html_e('Changes here save when you click', 'almaseo-seo-playground'); ?> <strong><?php esc_html_e('Update', 'almaseo-seo-playground'); ?></strong> <?php esc_html_e('at the top of the page.', 'almaseo-seo-playground'); ?>
                            </p>

                            <!-- Primary Schema Type dropdown removed: the merged Schema Type
                                 dropdown at the top of this tab is now the single source of
                                 truth, and the save handler syncs the value to both meta keys
                                 (_almaseo_schema_type + _almaseo_schema_primary_type) so all
                                 consumers stay in sync. $primary_type is still read above for
                                 backward-compat in the $show_lb / $show_mg panel-visibility
                                 checks (covers posts saved before the sync was added). -->

                            <!-- LocalBusiness Fields (shown when LocalBusiness selected) -->
                            <?php
                            $lb_subtype    = get_post_meta($post->ID, '_almaseo_lb_subtype', true) ?: 'LocalBusiness';
                            $lb_street     = get_post_meta($post->ID, '_almaseo_lb_street', true);
                            $lb_city       = get_post_meta($post->ID, '_almaseo_lb_city', true);
                            $lb_state      = get_post_meta($post->ID, '_almaseo_lb_state', true);
                            $lb_zip        = get_post_meta($post->ID, '_almaseo_lb_zip', true);
                            $lb_country    = get_post_meta($post->ID, '_almaseo_lb_country', true);
                            $lb_phone      = get_post_meta($post->ID, '_almaseo_lb_phone', true);
                            $lb_email      = get_post_meta($post->ID, '_almaseo_lb_email', true);
                            $lb_price      = get_post_meta($post->ID, '_almaseo_lb_price_range', true);
                            $lb_lat        = get_post_meta($post->ID, '_almaseo_lb_lat', true);
                            $lb_lng        = get_post_meta($post->ID, '_almaseo_lb_lng', true);
                            $lb_area       = get_post_meta($post->ID, '_almaseo_lb_area_served', true);
                            $lb_payment    = get_post_meta($post->ID, '_almaseo_lb_payment', true);
                            $lb_google_profile = get_post_meta($post->ID, '_almaseo_lb_google_profile', true);
                            $lb_hours_raw  = get_post_meta($post->ID, '_almaseo_lb_hours', true);
                            $lb_hours      = is_array($lb_hours_raw) ? $lb_hours_raw : ( $lb_hours_raw ? json_decode($lb_hours_raw, true) : array() );
                            if ( ! is_array($lb_hours) ) $lb_hours = array();
                            $show_lb = ( $primary_type === 'LocalBusiness' || $current_schema === 'LocalBusiness' );
                            ?>
                            <div id="almaseo-localbusiness-fields" style="<?php echo esc_attr(($show_lb ? '' : 'display:none; ') . 'margin-top: 15px; padding: 15px; background: #f9fafb; border: 1px solid #e2e4e7; border-radius: 6px;'); ?>">
                                <h4 style="margin: 0 0 12px 0; font-size: 13px; font-weight: 600; color: #1d2327;">Local Business Details</h4>

                                <?php if ( $is_connected ) : ?>
                                <!-- One-click fill from the client's AlmaSEO dashboard profile.
                                     Pulls name/address/phone/email/Google Maps URL/service areas
                                     so the agency doesn't re-type data that already lives on the
                                     dashboard. Hours/geo/price stay manual (see Fill follow-up). -->
                                <div style="margin-bottom: 12px; padding: 10px 12px; background: linear-gradient(135deg, #eef5ff 0%, #f5f0ff 100%); border: 1px solid #c7d7f0; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                                    <span style="font-size: 12px; color: #475569; flex: 1; min-width: 200px;">
                                        <span class="dashicons dashicons-cloud" style="color: #2271b1; vertical-align: middle;"></span>
                                        <?php esc_html_e('Pull this business\'s address, phone, email, service areas, opening hours and Google Maps URL from its AlmaSEO dashboard profile (hours come from the connected Google Business Profile).', 'almaseo-seo-playground'); ?>
                                    </span>
                                    <button type="button" id="almaseo-lb-fill-btn" class="button button-secondary"
                                            data-nonce="<?php echo esc_attr( wp_create_nonce('almaseo_lb_fill') ); ?>">
                                        <span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
                                        <?php esc_html_e('Fill from AlmaSEO', 'almaseo-seo-playground'); ?>
                                    </button>
                                </div>
                                <p id="almaseo-lb-fill-status" style="display:none; margin: -4px 0 12px 0; font-size: 11px; line-height: 1.5;"></p>
                                <script>
                                (function(){
                                    var btn = document.getElementById('almaseo-lb-fill-btn');
                                    if (!btn || btn.dataset.bound) { return; }
                                    btn.dataset.bound = '1';
                                    var statusEl = document.getElementById('almaseo-lb-fill-status');
                                    function setStatus(msg, color){
                                        if (!statusEl) { return; }
                                        statusEl.textContent = msg;
                                        statusEl.style.color = color;
                                        statusEl.style.display = '';
                                    }
                                    btn.addEventListener('click', function(){
                                        var orig = btn.innerHTML;
                                        btn.disabled = true;
                                        setStatus('<?php echo esc_js( __('Fetching from AlmaSEO…', 'almaseo-seo-playground') ); ?>', '#475569');
                                        fetch(ajaxurl, {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                            body: new URLSearchParams({
                                                action: 'almaseo_lb_fill_from_profile',
                                                nonce: btn.dataset.nonce
                                            })
                                        })
                                        .then(function(r){ return r.json(); })
                                        .then(function(res){
                                            btn.disabled = false; btn.innerHTML = orig;
                                            if (!res || !res.success) {
                                                setStatus((res && res.data && res.data.message) ? res.data.message : 'Could not load the dashboard profile.', '#b32d2e');
                                                return;
                                            }
                                            var fields = res.data.fields || {};
                                            var filled = 0;
                                            Object.keys(fields).forEach(function(name){
                                                if (!fields[name]) { return; }
                                                var el = document.querySelector('#almaseo-localbusiness-fields [name="' + name + '"]');
                                                if (el) { el.value = fields[name]; filled++; }
                                            });
                                            // Opening hours -> the per-day time pickers
                                            // (name="almaseo_lb_hours[day][open|close]").
                                            var hours = res.data.hours || {};
                                            Object.keys(hours).forEach(function(day){
                                                var h = hours[day];
                                                if (!h) { return; }
                                                var o = document.querySelector('#almaseo-localbusiness-fields [name="almaseo_lb_hours[' + day + '][open]"]');
                                                var c = document.querySelector('#almaseo-localbusiness-fields [name="almaseo_lb_hours[' + day + '][close]"]');
                                                if (o && h.open)  { o.value = h.open;  filled++; }
                                                if (c && h.close) { c.value = h.close; filled++; }
                                            });
                                            if (filled > 0) {
                                                setStatus(filled + ' <?php echo esc_js( __('field(s) filled from the dashboard profile. Review them, then click Update to save.', 'almaseo-seo-playground') ); ?>', '#1e7e34');
                                            } else {
                                                setStatus('<?php echo esc_js( __('No address or contact details found on the dashboard profile for this site.', 'almaseo-seo-playground') ); ?>', '#b3801e');
                                            }
                                        })
                                        .catch(function(){
                                            btn.disabled = false; btn.innerHTML = orig;
                                            setStatus('<?php echo esc_js( __('Network error contacting AlmaSEO.', 'almaseo-seo-playground') ); ?>', '#b32d2e');
                                        });
                                    });
                                })();
                                </script>
                                <?php endif; ?>

                                <div class="almaseo-field-group" style="margin-bottom: 10px;">
                                    <label for="almaseo_lb_subtype" style="font-size: 12px; font-weight: 600;">Business Type</label>
                                    <select id="almaseo_lb_subtype" name="almaseo_lb_subtype" class="almaseo-select" style="width: 100%;">
                                        <?php
                                        if ( function_exists('almaseo_get_local_business_types') ) {
                                            foreach ( almaseo_get_local_business_types() as $group_label => $group_types ) {
                                                echo '<optgroup label="' . esc_attr($group_label) . '">';
                                                foreach ( $group_types as $type_key => $type_label ) {
                                                    echo '<option value="' . esc_attr($type_key) . '" ' . selected($lb_subtype, $type_key, false) . '>' . esc_html($type_label) . '</option>';
                                                }
                                                echo '</optgroup>';
                                            }
                                        } else {
                                            echo '<option value="LocalBusiness" selected>Local Business</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px;">
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 3px;">Phone</label>
                                        <input type="text" name="almaseo_lb_phone" value="<?php echo esc_attr($lb_phone); ?>" placeholder="+1 (555) 123-4567" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 3px;">Email</label>
                                        <input type="email" name="almaseo_lb_email" value="<?php echo esc_attr($lb_email); ?>" placeholder="info@example.com" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <fieldset style="border: 1px solid #dcdcde; border-radius: 4px; padding: 10px; margin-bottom: 10px;">
                                    <legend style="font-size: 12px; font-weight: 600; padding: 0 5px;">Address</legend>
                                    <div style="margin-bottom: 6px;">
                                        <input type="text" name="almaseo_lb_street" value="<?php echo esc_attr($lb_street); ?>" placeholder="Street Address" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 6px; margin-bottom: 6px;">
                                        <input type="text" name="almaseo_lb_city" value="<?php echo esc_attr($lb_city); ?>" placeholder="City" class="almaseo-input" />
                                        <input type="text" name="almaseo_lb_state" value="<?php echo esc_attr($lb_state); ?>" placeholder="State" class="almaseo-input" />
                                        <input type="text" name="almaseo_lb_zip" value="<?php echo esc_attr($lb_zip); ?>" placeholder="ZIP" class="almaseo-input" />
                                    </div>
                                    <input type="text" name="almaseo_lb_country" value="<?php echo esc_attr($lb_country); ?>" placeholder="Country (e.g. US)" class="almaseo-input" style="width: 100%;" />
                                </fieldset>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px;">
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 3px;">Price Range</label>
                                        <input type="text" name="almaseo_lb_price_range" value="<?php echo esc_attr($lb_price); ?>" placeholder="$$ or $10-$50" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 3px;">Area Served</label>
                                        <input type="text" name="almaseo_lb_area_served" value="<?php echo esc_attr($lb_area); ?>" placeholder="e.g. Greater Boston" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px;">
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 3px;">Latitude</label>
                                        <input type="text" name="almaseo_lb_lat" value="<?php echo esc_attr($lb_lat); ?>" placeholder="42.3601" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 3px;">Longitude</label>
                                        <input type="text" name="almaseo_lb_lng" value="<?php echo esc_attr($lb_lng); ?>" placeholder="-71.0589" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <label style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 3px;">Payment Accepted</label>
                                    <input type="text" name="almaseo_lb_payment" value="<?php echo esc_attr($lb_payment); ?>" placeholder="Cash, Credit Card, Bitcoin" class="almaseo-input" style="width: 100%;" />
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_lb_google_profile" style="font-size: 11px; font-weight: 600; display: block; margin-bottom: 3px;">Google Business Profile / Maps URL</label>
                                    <input type="url" id="almaseo_lb_google_profile" name="almaseo_lb_google_profile" value="<?php echo esc_attr($lb_google_profile); ?>" placeholder="https://maps.google.com/?cid=1234567890" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;">Your Google Maps listing URL. Emitted in the schema's <code>sameAs</code> so Google can connect this page to your Business Profile.</p>
                                </div>

                                <fieldset style="border: 1px solid #dcdcde; border-radius: 4px; padding: 10px;">
                                    <legend style="font-size: 12px; font-weight: 600; padding: 0 5px;">Opening Hours</legend>
                                    <?php
                                    $days = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');
                                    foreach ( $days as $day ) :
                                        $open  = isset($lb_hours[$day]['open'])  ? $lb_hours[$day]['open']  : '';
                                        $close = isset($lb_hours[$day]['close']) ? $lb_hours[$day]['close'] : '';
                                    ?>
                                    <div style="display: grid; grid-template-columns: 80px 1fr 1fr; gap: 6px; align-items: center; margin-bottom: 4px;">
                                        <span style="font-size: 12px; text-transform: capitalize;"><?php echo esc_html($day); ?></span>
                                        <input type="time" name="almaseo_lb_hours[<?php echo esc_attr($day); ?>][open]" value="<?php echo esc_attr($open); ?>" class="almaseo-input" style="width: 100%;" />
                                        <input type="time" name="almaseo_lb_hours[<?php echo esc_attr($day); ?>][close]" value="<?php echo esc_attr($close); ?>" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <?php endforeach; ?>
                                    <p class="description" style="margin-top: 6px; font-size: 11px;">Leave blank for closed days.</p>
                                </fieldset>
                            </div>

                            <!-- MusicGroup Fields (shown when MusicGroup selected) -->
                            <?php
                            $mg_genre             = get_post_meta($post->ID, '_almaseo_mg_genre', true);
                            $mg_founding_date     = get_post_meta($post->ID, '_almaseo_mg_founding_date', true);
                            $mg_founding_location = get_post_meta($post->ID, '_almaseo_mg_founding_location', true);
                            $mg_area_served       = get_post_meta($post->ID, '_almaseo_mg_area_served', true);
                            $mg_street            = get_post_meta($post->ID, '_almaseo_mg_street', true);
                            $mg_city              = get_post_meta($post->ID, '_almaseo_mg_city', true);
                            $mg_state             = get_post_meta($post->ID, '_almaseo_mg_state', true);
                            $mg_zip               = get_post_meta($post->ID, '_almaseo_mg_zip', true);
                            $mg_country           = get_post_meta($post->ID, '_almaseo_mg_country', true);
                            $mg_members           = get_post_meta($post->ID, '_almaseo_mg_members', true);
                            $mg_image             = get_post_meta($post->ID, '_almaseo_mg_image', true);
                            $mg_same_as           = get_post_meta($post->ID, '_almaseo_mg_same_as', true);
                            $show_mg = ( $primary_type === 'MusicGroup' || $current_schema === 'MusicGroup' );
                            ?>
                            <div id="almaseo-musicgroup-fields" style="<?php echo esc_attr(($show_mg ? '' : 'display:none; ') . 'margin-top: 15px; padding: 15px; background: #f9fafb; border: 1px solid #e2e4e7; border-radius: 6px;'); ?>">
                                <h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #1d2327;">🎵 <?php esc_html_e('Music Group / Artist Details', 'almaseo-seo-playground'); ?></h4>

                                <div style="margin-bottom: 14px; padding: 10px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; color: #475569; line-height: 1.5;">
                                    <?php esc_html_e('Use these fields when the page represents a band, musician, or music ensemble. The post title becomes the artist name, the URL is the canonical artist URL, and the featured image is used if no override is set.', 'almaseo-seo-playground'); ?>
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_mg_genre" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Genre', 'almaseo-seo-playground'); ?></label>
                                    <input type="text" id="almaseo_mg_genre" name="almaseo_mg_genre" value="<?php echo esc_attr($mg_genre); ?>" placeholder="<?php esc_attr_e('Indie Rock, Alternative', 'almaseo-seo-playground'); ?>" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Comma-separated for multiple genres.', 'almaseo-seo-playground'); ?></p>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_mg_founding_date" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Founding Date', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_mg_founding_date" name="almaseo_mg_founding_date" value="<?php echo esc_attr($mg_founding_date); ?>" placeholder="2009 or 2009-04-15" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_mg_founding_location" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Founding Location', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_mg_founding_location" name="almaseo_mg_founding_location" value="<?php echo esc_attr($mg_founding_location); ?>" placeholder="Brooklyn, NY" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_mg_area_served" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Area Served', 'almaseo-seo-playground'); ?></label>
                                    <input type="text" id="almaseo_mg_area_served" name="almaseo_mg_area_served" value="<?php echo esc_attr($mg_area_served); ?>" placeholder="<?php esc_attr_e('Greater Atlanta, GA', 'almaseo-seo-playground'); ?>" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Geographic region the band performs in. Use this for service-area acts (e.g. wedding bands, cover bands).', 'almaseo-seo-playground'); ?></p>
                                </div>

                                <fieldset style="border: 1px solid #dcdcde; border-radius: 4px; padding: 10px; margin-bottom: 10px;">
                                    <legend style="font-size: 12px; font-weight: 600; padding: 0 5px;"><?php esc_html_e('Business Address', 'almaseo-seo-playground'); ?></legend>
                                    <p class="field-hint" style="margin: 0 0 8px 0; font-size: 11px;"><?php esc_html_e('Optional. Booking or mailing address. Leave blank if not publicly listed.', 'almaseo-seo-playground'); ?></p>
                                    <div style="margin-bottom: 6px;">
                                        <input type="text" name="almaseo_mg_street" value="<?php echo esc_attr($mg_street); ?>" placeholder="<?php esc_attr_e('Street Address', 'almaseo-seo-playground'); ?>" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 6px; margin-bottom: 6px;">
                                        <input type="text" name="almaseo_mg_city" value="<?php echo esc_attr($mg_city); ?>" placeholder="<?php esc_attr_e('City', 'almaseo-seo-playground'); ?>" class="almaseo-input" />
                                        <input type="text" name="almaseo_mg_state" value="<?php echo esc_attr($mg_state); ?>" placeholder="<?php esc_attr_e('State', 'almaseo-seo-playground'); ?>" class="almaseo-input" />
                                        <input type="text" name="almaseo_mg_zip" value="<?php echo esc_attr($mg_zip); ?>" placeholder="<?php esc_attr_e('ZIP', 'almaseo-seo-playground'); ?>" class="almaseo-input" />
                                    </div>
                                    <input type="text" name="almaseo_mg_country" value="<?php echo esc_attr($mg_country); ?>" placeholder="<?php esc_attr_e('Country (e.g. US)', 'almaseo-seo-playground'); ?>" class="almaseo-input" style="width: 100%;" />
                                </fieldset>

                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_mg_members" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Members', 'almaseo-seo-playground'); ?></label>
                                    <textarea id="almaseo_mg_members" name="almaseo_mg_members" rows="4" class="almaseo-input" style="width: 100%; font-family: monospace; font-size: 12px;" placeholder="Jane Doe | Lead Vocals&#10;John Smith | Guitar&#10;Pat Lee | Drums"><?php echo esc_textarea($mg_members); ?></textarea>
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('One per line: Name | Role. Role is optional.', 'almaseo-seo-playground'); ?></p>
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_mg_image" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Image URL', 'almaseo-seo-playground'); ?></label>
                                    <input type="url" id="almaseo_mg_image" name="almaseo_mg_image" value="<?php echo esc_attr($mg_image); ?>" placeholder="https://example.com/band-photo.jpg" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Optional. Falls back to featured image if blank.', 'almaseo-seo-playground'); ?></p>
                                </div>

                                <div style="margin-bottom: 0;">
                                    <label for="almaseo_mg_same_as" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Same As (External Profiles)', 'almaseo-seo-playground'); ?></label>
                                    <textarea id="almaseo_mg_same_as" name="almaseo_mg_same_as" rows="3" class="almaseo-input" style="width: 100%; font-family: monospace; font-size: 12px;" placeholder="https://open.spotify.com/artist/...&#10;https://music.apple.com/artist/...&#10;https://www.instagram.com/..."><?php echo esc_textarea($mg_same_as); ?></textarea>
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('One URL per line. Spotify, Apple Music, Wikipedia, Instagram, official site.', 'almaseo-seo-playground'); ?></p>
                                </div>
                            </div>

                            <!-- Person Fields (shown when Person selected) -->
                            <?php
                            $person_job_title    = get_post_meta($post->ID, '_almaseo_person_job_title', true);
                            $person_works_for    = get_post_meta($post->ID, '_almaseo_person_works_for', true);
                            $person_email        = get_post_meta($post->ID, '_almaseo_person_email', true);
                            $person_telephone    = get_post_meta($post->ID, '_almaseo_person_telephone', true);
                            $person_image        = get_post_meta($post->ID, '_almaseo_person_image', true);
                            $person_given_name   = get_post_meta($post->ID, '_almaseo_person_given_name', true);
                            $person_family_name  = get_post_meta($post->ID, '_almaseo_person_family_name', true);
                            $person_birth_date   = get_post_meta($post->ID, '_almaseo_person_birth_date', true);
                            $person_knows_about  = get_post_meta($post->ID, '_almaseo_person_knows_about', true);
                            $person_same_as      = get_post_meta($post->ID, '_almaseo_person_same_as', true);
                            $show_person = ( $primary_type === 'Person' || $current_schema === 'Person' );
                            ?>
                            <div id="almaseo-person-fields" style="<?php echo esc_attr(($show_person ? '' : 'display:none; ') . 'margin-top: 15px; padding: 15px; background: #f9fafb; border: 1px solid #e2e4e7; border-radius: 6px;'); ?>">
                                <h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #1d2327;">👤 <?php esc_html_e('Person Profile Details', 'almaseo-seo-playground'); ?></h4>

                                <div style="margin-bottom: 12px; padding: 10px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; color: #475569; line-height: 1.6;">
                                    <?php esc_html_e('Use these fields when the page represents a person — author profile, public figure, team member, speaker, etc.', 'almaseo-seo-playground'); ?>
                                    <ul style="margin: 6px 0 0 0; padding-left: 18px;">
                                        <li><?php esc_html_e('The person\'s name comes from this page\'s title, and the profile URL is this page\'s permalink.', 'almaseo-seo-playground'); ?></li>
                                        <li><?php esc_html_e('A headshot, job title, areas of expertise (Knows About) and verified profile links (Same As) are the strongest E-E-A-T signals — fill in as many as you can.', 'almaseo-seo-playground'); ?></li>
                                        <li><?php esc_html_e('To reuse a WordPress user\'s details, pick them below and click "Fill from user." You can edit anything afterward. Manage a user\'s photo, bio, job title and links under Users → Profile.', 'almaseo-seo-playground'); ?></li>
                                    </ul>
                                </div>

                                <?php if ( current_user_can( 'list_users' ) ) :
                                    $almaseo_person_users = get_users( array( 'fields' => array( 'ID', 'display_name' ), 'number' => 200, 'orderby' => 'display_name' ) );
                                    if ( ! empty( $almaseo_person_users ) ) : ?>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; padding: 10px 12px; background: linear-gradient(135deg, #eef5ff 0%, #f5f0ff 100%); border: 1px solid #c7d7f0; border-radius: 6px;">
                                    <label for="almaseo-person-user-select" style="font-size: 12px; font-weight: 600; margin: 0;"><?php esc_html_e( 'Populate from user:', 'almaseo-seo-playground' ); ?></label>
                                    <select id="almaseo-person-user-select" class="almaseo-select" style="flex: 1; min-width: 150px; max-width: 240px;">
                                        <option value=""><?php esc_html_e( '— Select a user —', 'almaseo-seo-playground' ); ?></option>
                                        <?php foreach ( $almaseo_person_users as $almaseo_pu ) {
                                            echo '<option value="' . esc_attr( $almaseo_pu->ID ) . '">' . esc_html( $almaseo_pu->display_name ) . '</option>';
                                        } ?>
                                    </select>
                                    <button type="button" class="button button-secondary" id="almaseo-person-fill-btn" data-nonce="<?php echo esc_attr( wp_create_nonce( 'almaseo_person_fill' ) ); ?>">
                                        <span class="dashicons dashicons-admin-users" style="vertical-align: middle;"></span>
                                        <?php esc_html_e( 'Fill from user', 'almaseo-seo-playground' ); ?>
                                    </button>
                                    <span id="almaseo-person-fill-status" style="font-size: 11px; flex-basis: 100%; line-height: 1.5;"></span>
                                </div>
                                <script>
                                (function(){
                                    var btn = document.getElementById('almaseo-person-fill-btn');
                                    if (!btn || btn.dataset.bound) { return; }
                                    btn.dataset.bound = '1';
                                    var statusEl = document.getElementById('almaseo-person-fill-status');
                                    function setStatus(msg, color){ if (statusEl){ statusEl.textContent = msg; statusEl.style.color = color; } }
                                    btn.addEventListener('click', function(){
                                        var sel = document.getElementById('almaseo-person-user-select');
                                        var uid = sel ? sel.value : '';
                                        if (!uid) { setStatus('<?php echo esc_js( __( 'Pick a user first.', 'almaseo-seo-playground' ) ); ?>', '#b3801e'); return; }
                                        var orig = btn.innerHTML; btn.disabled = true;
                                        setStatus('<?php echo esc_js( __( 'Loading…', 'almaseo-seo-playground' ) ); ?>', '#475569');
                                        fetch(ajaxurl, {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                            body: new URLSearchParams({ action: 'almaseo_person_fill_from_user', nonce: btn.dataset.nonce, user_id: uid })
                                        })
                                        .then(function(r){ return r.json(); })
                                        .then(function(res){
                                            btn.disabled = false; btn.innerHTML = orig;
                                            if (!res || !res.success) { setStatus((res && res.data && res.data.message) ? res.data.message : '<?php echo esc_js( __( 'Could not load that user.', 'almaseo-seo-playground' ) ); ?>', '#b32d2e'); return; }
                                            var fields = res.data.fields || {}; var filled = 0;
                                            Object.keys(fields).forEach(function(name){
                                                var el = document.querySelector('#almaseo-person-fields [name="' + name + '"]');
                                                if (el && fields[name]) { el.value = fields[name]; filled++; }
                                            });
                                            setStatus(filled + ' <?php echo esc_js( __( 'field(s) filled from the user profile. Review, then click Update to save. (The person\'s name still comes from the page title.)', 'almaseo-seo-playground' ) ); ?>', '#1e7e34');
                                        })
                                        .catch(function(){ btn.disabled = false; btn.innerHTML = orig; setStatus('<?php echo esc_js( __( 'Network error.', 'almaseo-seo-playground' ) ); ?>', '#b32d2e'); });
                                    });
                                })();
                                </script>
                                <?php endif; endif; ?>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_person_given_name" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Given Name (First)', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_person_given_name" name="almaseo_person_given_name" value="<?php echo esc_attr($person_given_name); ?>" placeholder="Jane" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_person_family_name" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Family Name (Last)', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_person_family_name" name="almaseo_person_family_name" value="<?php echo esc_attr($person_family_name); ?>" placeholder="Smith" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_person_job_title" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Job Title', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_person_job_title" name="almaseo_person_job_title" value="<?php echo esc_attr($person_job_title); ?>" placeholder="Senior Engineer" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_person_works_for" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Works For (Organization)', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_person_works_for" name="almaseo_person_works_for" value="<?php echo esc_attr($person_works_for); ?>" placeholder="Acme Corporation" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_person_email" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Email', 'almaseo-seo-playground'); ?></label>
                                        <input type="email" id="almaseo_person_email" name="almaseo_person_email" value="<?php echo esc_attr($person_email); ?>" placeholder="jane@example.com" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_person_telephone" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Telephone', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_person_telephone" name="almaseo_person_telephone" value="<?php echo esc_attr($person_telephone); ?>" placeholder="+1-555-0100" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_person_birth_date" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Birth Date', 'almaseo-seo-playground'); ?></label>
                                    <input type="text" id="almaseo_person_birth_date" name="almaseo_person_birth_date" value="<?php echo esc_attr($person_birth_date); ?>" placeholder="1985 or 1985-04-15" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Year only or full ISO 8601 date (YYYY-MM-DD).', 'almaseo-seo-playground'); ?></p>
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_person_image" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Image URL', 'almaseo-seo-playground'); ?></label>
                                    <input type="url" id="almaseo_person_image" name="almaseo_person_image" value="<?php echo esc_attr($person_image); ?>" placeholder="https://example.com/headshot.jpg" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Optional. Falls back to featured image if blank.', 'almaseo-seo-playground'); ?></p>
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_person_knows_about" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Knows About (Expertise)', 'almaseo-seo-playground'); ?></label>
                                    <input type="text" id="almaseo_person_knows_about" name="almaseo_person_knows_about" value="<?php echo esc_attr($person_knows_about); ?>" placeholder="<?php esc_attr_e('Machine Learning, Distributed Systems, Rust', 'almaseo-seo-playground'); ?>" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Comma-separated list of areas of expertise or interest.', 'almaseo-seo-playground'); ?></p>
                                </div>

                                <div style="margin-bottom: 0;">
                                    <label for="almaseo_person_same_as" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Same As (External Profiles)', 'almaseo-seo-playground'); ?></label>
                                    <textarea id="almaseo_person_same_as" name="almaseo_person_same_as" rows="3" class="almaseo-input" style="width: 100%; font-family: monospace; font-size: 12px;" placeholder="https://www.linkedin.com/in/...&#10;https://twitter.com/...&#10;https://github.com/..."><?php echo esc_textarea($person_same_as); ?></textarea>
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('One URL per line. LinkedIn, Twitter, GitHub, Wikipedia, etc.', 'almaseo-seo-playground'); ?></p>
                                </div>
                            </div>

                            <!-- Organization Fields (shown when Organization selected) -->
                            <?php
                            $org_legal_name    = get_post_meta($post->ID, '_almaseo_org_legal_name', true);
                            $org_founding_date = get_post_meta($post->ID, '_almaseo_org_founding_date', true);
                            $org_founder       = get_post_meta($post->ID, '_almaseo_org_founder', true);
                            $org_employees     = get_post_meta($post->ID, '_almaseo_org_employees', true);
                            $org_industry      = get_post_meta($post->ID, '_almaseo_org_industry', true);
                            $org_email         = get_post_meta($post->ID, '_almaseo_org_email', true);
                            $org_telephone     = get_post_meta($post->ID, '_almaseo_org_telephone', true);
                            $org_logo          = get_post_meta($post->ID, '_almaseo_org_logo', true);
                            $org_same_as       = get_post_meta($post->ID, '_almaseo_org_same_as', true);
                            $show_org = ( $primary_type === 'Organization' || $current_schema === 'Organization' );
                            ?>
                            <div id="almaseo-organization-fields" style="<?php echo esc_attr(($show_org ? '' : 'display:none; ') . 'margin-top: 15px; padding: 15px; background: #f9fafb; border: 1px solid #e2e4e7; border-radius: 6px;'); ?>">
                                <h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #1d2327;">🏢 <?php esc_html_e('Organization Details', 'almaseo-seo-playground'); ?></h4>

                                <div style="margin-bottom: 14px; padding: 10px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; color: #475569; line-height: 1.5;">
                                    <?php esc_html_e('Use these fields when the page represents a company, non-profit, school, or any non-physical organization. For brick-and-mortar businesses with a physical location, use LocalBusiness instead. The post title becomes the organization\'s name.', 'almaseo-seo-playground'); ?>
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_org_legal_name" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Legal Name', 'almaseo-seo-playground'); ?></label>
                                    <input type="text" id="almaseo_org_legal_name" name="almaseo_org_legal_name" value="<?php echo esc_attr($org_legal_name); ?>" placeholder="Acme Corporation, Inc." class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Official registered legal entity name (often differs from brand name).', 'almaseo-seo-playground'); ?></p>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_org_founding_date" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Founding Date', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_org_founding_date" name="almaseo_org_founding_date" value="<?php echo esc_attr($org_founding_date); ?>" placeholder="1998 or 1998-09-04" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_org_founder" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Founder', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_org_founder" name="almaseo_org_founder" value="<?php echo esc_attr($org_founder); ?>" placeholder="Jane Smith" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_org_employees" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Number of Employees', 'almaseo-seo-playground'); ?></label>
                                        <input type="number" min="0" id="almaseo_org_employees" name="almaseo_org_employees" value="<?php echo esc_attr($org_employees); ?>" placeholder="250" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_org_industry" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Industry', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_org_industry" name="almaseo_org_industry" value="<?php echo esc_attr($org_industry); ?>" placeholder="Software, Healthcare, Education" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_org_email" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Email', 'almaseo-seo-playground'); ?></label>
                                        <input type="email" id="almaseo_org_email" name="almaseo_org_email" value="<?php echo esc_attr($org_email); ?>" placeholder="contact@example.com" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_org_telephone" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Telephone', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_org_telephone" name="almaseo_org_telephone" value="<?php echo esc_attr($org_telephone); ?>" placeholder="+1-555-0100" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_org_logo" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Logo URL', 'almaseo-seo-playground'); ?></label>
                                    <input type="url" id="almaseo_org_logo" name="almaseo_org_logo" value="<?php echo esc_attr($org_logo); ?>" placeholder="https://example.com/logo.png" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Used as both logo (ImageObject) and image. Falls back to featured image.', 'almaseo-seo-playground'); ?></p>
                                </div>

                                <div style="margin-bottom: 0;">
                                    <label for="almaseo_org_same_as" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Same As (External Profiles)', 'almaseo-seo-playground'); ?></label>
                                    <textarea id="almaseo_org_same_as" name="almaseo_org_same_as" rows="3" class="almaseo-input" style="width: 100%; font-family: monospace; font-size: 12px;" placeholder="https://www.linkedin.com/company/...&#10;https://twitter.com/...&#10;https://www.crunchbase.com/organization/..."><?php echo esc_textarea($org_same_as); ?></textarea>
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('One URL per line. LinkedIn, Twitter, Crunchbase, Wikipedia, etc.', 'almaseo-seo-playground'); ?></p>
                                </div>
                            </div>

                            <!-- Product Fields (shown when Product selected) -->
                            <?php
                            $product_brand        = get_post_meta($post->ID, '_almaseo_product_brand', true);
                            $product_sku          = get_post_meta($post->ID, '_almaseo_product_sku', true);
                            $product_gtin         = get_post_meta($post->ID, '_almaseo_product_gtin', true);
                            $product_mpn          = get_post_meta($post->ID, '_almaseo_product_mpn', true);
                            $product_price        = get_post_meta($post->ID, '_almaseo_product_price', true);
                            $product_currency     = get_post_meta($post->ID, '_almaseo_product_currency', true) ?: 'USD';
                            $product_availability = get_post_meta($post->ID, '_almaseo_product_availability', true) ?: 'InStock';
                            $product_condition    = get_post_meta($post->ID, '_almaseo_product_condition', true) ?: 'NewCondition';
                            $product_image        = get_post_meta($post->ID, '_almaseo_product_image', true);
                            $product_rating_value = get_post_meta($post->ID, '_almaseo_product_rating_value', true);
                            $product_review_count = get_post_meta($post->ID, '_almaseo_product_review_count', true);
                            $show_product = ( $primary_type === 'Product' || $current_schema === 'Product' );
                            $availability_opts = array(
                                'InStock'             => __('In Stock', 'almaseo-seo-playground'),
                                'OutOfStock'          => __('Out of Stock', 'almaseo-seo-playground'),
                                'PreOrder'            => __('Pre-Order', 'almaseo-seo-playground'),
                                'BackOrder'           => __('Back-Order', 'almaseo-seo-playground'),
                                'Discontinued'        => __('Discontinued', 'almaseo-seo-playground'),
                                'LimitedAvailability' => __('Limited Availability', 'almaseo-seo-playground'),
                                'SoldOut'             => __('Sold Out', 'almaseo-seo-playground'),
                            );
                            $condition_opts = array(
                                'NewCondition'         => __('New', 'almaseo-seo-playground'),
                                'UsedCondition'        => __('Used', 'almaseo-seo-playground'),
                                'RefurbishedCondition' => __('Refurbished', 'almaseo-seo-playground'),
                                'DamagedCondition'     => __('Damaged', 'almaseo-seo-playground'),
                            );
                            ?>
                            <div id="almaseo-product-fields" style="<?php echo esc_attr(($show_product ? '' : 'display:none; ') . 'margin-top: 15px; padding: 15px; background: #f9fafb; border: 1px solid #e2e4e7; border-radius: 6px;'); ?>">
                                <h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #1d2327;">🛍️ <?php esc_html_e('Product Details', 'almaseo-seo-playground'); ?></h4>

                                <div style="margin-bottom: 14px; padding: 10px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; color: #475569; line-height: 1.5;">
                                    <?php esc_html_e('Use these fields when the page represents a sellable product. Google requires at least price, priceCurrency, and availability for the rich result. If WooCommerce is active, its product schema runs separately — only fill in here for non-WC products.', 'almaseo-seo-playground'); ?>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">🏷️ <?php esc_html_e('Identifiers', 'almaseo-seo-playground'); ?></h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_product_brand" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Brand', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_product_brand" name="almaseo_product_brand" value="<?php echo esc_attr($product_brand); ?>" placeholder="Acme" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_product_sku" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('SKU', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_product_sku" name="almaseo_product_sku" value="<?php echo esc_attr($product_sku); ?>" placeholder="ACM-WIDGET-001" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_product_gtin" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('GTIN / UPC / EAN', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_product_gtin" name="almaseo_product_gtin" value="<?php echo esc_attr($product_gtin); ?>" placeholder="0123456789012" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_product_mpn" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('MPN (Mfr Part #)', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_product_mpn" name="almaseo_product_mpn" value="<?php echo esc_attr($product_mpn); ?>" placeholder="WIDGET-2024" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">💰 <?php esc_html_e('Offer', 'almaseo-seo-playground'); ?></h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_product_price" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Price', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_product_price" name="almaseo_product_price" value="<?php echo esc_attr($product_price); ?>" placeholder="29.99" class="almaseo-input" style="width: 100%;" />
                                        <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Numeric value, no currency symbol.', 'almaseo-seo-playground'); ?></p>
                                    </div>
                                    <div>
                                        <label for="almaseo_product_currency" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Currency', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_product_currency" name="almaseo_product_currency" value="<?php echo esc_attr($product_currency); ?>" placeholder="USD" maxlength="3" class="almaseo-input" style="width: 100%; text-transform: uppercase;" />
                                        <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('3-letter ISO 4217 code (USD, EUR, GBP…).', 'almaseo-seo-playground'); ?></p>
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_product_availability" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Availability', 'almaseo-seo-playground'); ?></label>
                                        <select id="almaseo_product_availability" name="almaseo_product_availability" class="almaseo-select" style="width: 100%;">
                                            <?php foreach ($availability_opts as $val => $lbl): ?>
                                                <option value="<?php echo esc_attr($val); ?>" <?php selected($product_availability, $val); ?>><?php echo esc_html($lbl); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="almaseo_product_condition" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Item Condition', 'almaseo-seo-playground'); ?></label>
                                        <select id="almaseo_product_condition" name="almaseo_product_condition" class="almaseo-select" style="width: 100%;">
                                            <?php foreach ($condition_opts as $val => $lbl): ?>
                                                <option value="<?php echo esc_attr($val); ?>" <?php selected($product_condition, $val); ?>><?php echo esc_html($lbl); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">⭐ <?php esc_html_e('Reviews', 'almaseo-seo-playground'); ?></h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_product_rating_value" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Average Rating', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_product_rating_value" name="almaseo_product_rating_value" value="<?php echo esc_attr($product_rating_value); ?>" placeholder="4.5" class="almaseo-input" style="width: 100%;" />
                                        <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Out of 5. Decimals allowed.', 'almaseo-seo-playground'); ?></p>
                                    </div>
                                    <div>
                                        <label for="almaseo_product_review_count" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Number of Reviews', 'almaseo-seo-playground'); ?></label>
                                        <input type="number" min="0" id="almaseo_product_review_count" name="almaseo_product_review_count" value="<?php echo esc_attr($product_review_count); ?>" placeholder="124" class="almaseo-input" style="width: 100%;" />
                                        <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Both rating + count must be set for the AggregateRating to emit.', 'almaseo-seo-playground'); ?></p>
                                    </div>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">🖼️ <?php esc_html_e('Image', 'almaseo-seo-playground'); ?></h5>
                                <div style="margin-bottom: 0;">
                                    <label for="almaseo_product_image" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Image URL', 'almaseo-seo-playground'); ?></label>
                                    <input type="url" id="almaseo_product_image" name="almaseo_product_image" value="<?php echo esc_attr($product_image); ?>" placeholder="https://example.com/product.jpg" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Optional. Falls back to featured image if blank. Required by Google for the Product rich result.', 'almaseo-seo-playground'); ?></p>
                                </div>
                            </div>

                            <!-- Event Fields (shown when Event selected) -->
                            <?php
                            $event_start_date       = get_post_meta($post->ID, '_almaseo_event_start_date', true);
                            $event_end_date         = get_post_meta($post->ID, '_almaseo_event_end_date', true);
                            $event_location_name    = get_post_meta($post->ID, '_almaseo_event_location_name', true);
                            $event_location_address = get_post_meta($post->ID, '_almaseo_event_location_address', true);
                            $event_location_url     = get_post_meta($post->ID, '_almaseo_event_location_url', true);
                            $event_performer        = get_post_meta($post->ID, '_almaseo_event_performer', true);
                            $event_organizer        = get_post_meta($post->ID, '_almaseo_event_organizer', true);
                            $event_status           = get_post_meta($post->ID, '_almaseo_event_status', true) ?: 'EventScheduled';
                            $event_attendance_mode  = get_post_meta($post->ID, '_almaseo_event_attendance_mode', true) ?: 'OfflineEventAttendanceMode';
                            $event_ticket_price     = get_post_meta($post->ID, '_almaseo_event_ticket_price', true);
                            $event_ticket_currency  = get_post_meta($post->ID, '_almaseo_event_ticket_currency', true) ?: 'USD';
                            $event_ticket_url       = get_post_meta($post->ID, '_almaseo_event_ticket_url', true);
                            $event_image            = get_post_meta($post->ID, '_almaseo_event_image', true);
                            $show_event = ( $primary_type === 'Event' || $current_schema === 'Event' );
                            $event_status_opts = array(
                                'EventScheduled'   => __('Scheduled', 'almaseo-seo-playground'),
                                'EventCancelled'   => __('Cancelled', 'almaseo-seo-playground'),
                                'EventPostponed'   => __('Postponed', 'almaseo-seo-playground'),
                                'EventRescheduled' => __('Rescheduled', 'almaseo-seo-playground'),
                                'EventMovedOnline' => __('Moved Online', 'almaseo-seo-playground'),
                            );
                            $attendance_opts = array(
                                'OfflineEventAttendanceMode' => __('In-person only', 'almaseo-seo-playground'),
                                'OnlineEventAttendanceMode'  => __('Online only', 'almaseo-seo-playground'),
                                'MixedEventAttendanceMode'   => __('Hybrid (in-person + online)', 'almaseo-seo-playground'),
                            );
                            ?>
                            <div id="almaseo-event-fields" style="<?php echo esc_attr(($show_event ? '' : 'display:none; ') . 'margin-top: 15px; padding: 15px; background: #f9fafb; border: 1px solid #e2e4e7; border-radius: 6px;'); ?>">
                                <h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #1d2327;">📅 <?php esc_html_e('Event Details', 'almaseo-seo-playground'); ?></h4>

                                <div style="margin-bottom: 14px; padding: 10px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; color: #475569; line-height: 1.5;">
                                    <?php esc_html_e('Use these fields when the page represents a concert, conference, webinar, festival, etc. Google requires startDate + a location (physical address or URL for online events) for the rich result. The post title becomes the event name.', 'almaseo-seo-playground'); ?>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">⏰ <?php esc_html_e('When', 'almaseo-seo-playground'); ?></h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_event_start_date" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Start Date / Time', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_event_start_date" name="almaseo_event_start_date" value="<?php echo esc_attr($event_start_date); ?>" placeholder="2026-08-15T19:30:00-04:00" class="almaseo-input" style="width: 100%;" />
                                        <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('ISO 8601 format. Date or date+time+timezone.', 'almaseo-seo-playground'); ?></p>
                                    </div>
                                    <div>
                                        <label for="almaseo_event_end_date" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('End Date / Time', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_event_end_date" name="almaseo_event_end_date" value="<?php echo esc_attr($event_end_date); ?>" placeholder="2026-08-15T22:30:00-04:00" class="almaseo-input" style="width: 100%;" />
                                        <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Optional but strongly recommended.', 'almaseo-seo-playground'); ?></p>
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_event_status" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Event Status', 'almaseo-seo-playground'); ?></label>
                                        <select id="almaseo_event_status" name="almaseo_event_status" class="almaseo-select" style="width: 100%;">
                                            <?php foreach ($event_status_opts as $val => $lbl): ?>
                                                <option value="<?php echo esc_attr($val); ?>" <?php selected($event_status, $val); ?>><?php echo esc_html($lbl); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="almaseo_event_attendance_mode" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Attendance Mode', 'almaseo-seo-playground'); ?></label>
                                        <select id="almaseo_event_attendance_mode" name="almaseo_event_attendance_mode" class="almaseo-select" style="width: 100%;">
                                            <?php foreach ($attendance_opts as $val => $lbl): ?>
                                                <option value="<?php echo esc_attr($val); ?>" <?php selected($event_attendance_mode, $val); ?>><?php echo esc_html($lbl); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">📍 <?php esc_html_e('Where', 'almaseo-seo-playground'); ?></h5>
                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_event_location_name" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Venue / Location Name', 'almaseo-seo-playground'); ?></label>
                                    <input type="text" id="almaseo_event_location_name" name="almaseo_event_location_name" value="<?php echo esc_attr($event_location_name); ?>" placeholder="Madison Square Garden" class="almaseo-input" style="width: 100%;" />
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_event_location_address" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Physical Address', 'almaseo-seo-playground'); ?></label>
                                    <input type="text" id="almaseo_event_location_address" name="almaseo_event_location_address" value="<?php echo esc_attr($event_location_address); ?>" placeholder="4 Pennsylvania Plaza, New York, NY 10001" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('For in-person events. Leave blank for online-only.', 'almaseo-seo-playground'); ?></p>
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_event_location_url" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Online URL (VirtualLocation)', 'almaseo-seo-playground'); ?></label>
                                    <input type="url" id="almaseo_event_location_url" name="almaseo_event_location_url" value="<?php echo esc_attr($event_location_url); ?>" placeholder="https://example.com/livestream" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('For online or hybrid events. Required when Attendance Mode is Online or Hybrid.', 'almaseo-seo-playground'); ?></p>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">🎤 <?php esc_html_e('People', 'almaseo-seo-playground'); ?></h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_event_performer" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Performer / Speaker', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_event_performer" name="almaseo_event_performer" value="<?php echo esc_attr($event_performer); ?>" placeholder="Coldplay" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_event_organizer" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Organizer', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_event_organizer" name="almaseo_event_organizer" value="<?php echo esc_attr($event_organizer); ?>" placeholder="Live Nation" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">🎟️ <?php esc_html_e('Tickets', 'almaseo-seo-playground'); ?></h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_event_ticket_price" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Price', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_event_ticket_price" name="almaseo_event_ticket_price" value="<?php echo esc_attr($event_ticket_price); ?>" placeholder="49.50" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_event_ticket_currency" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Currency', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_event_ticket_currency" name="almaseo_event_ticket_currency" value="<?php echo esc_attr($event_ticket_currency); ?>" placeholder="USD" maxlength="3" class="almaseo-input" style="width: 100%; text-transform: uppercase;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_event_ticket_url" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Ticket URL', 'almaseo-seo-playground'); ?></label>
                                        <input type="url" id="almaseo_event_ticket_url" name="almaseo_event_ticket_url" value="<?php echo esc_attr($event_ticket_url); ?>" placeholder="https://tickets.example.com/..." class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">🖼️ <?php esc_html_e('Image', 'almaseo-seo-playground'); ?></h5>
                                <div style="margin-bottom: 0;">
                                    <label for="almaseo_event_image" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Image URL', 'almaseo-seo-playground'); ?></label>
                                    <input type="url" id="almaseo_event_image" name="almaseo_event_image" value="<?php echo esc_attr($event_image); ?>" placeholder="https://example.com/event.jpg" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Optional. Falls back to featured image. Required by Google for the Event rich result.', 'almaseo-seo-playground'); ?></p>
                                </div>
                            </div>

                            <!-- Service Fields (shown when Service selected) -->
                            <?php
                            $show_service = ( $primary_type === 'Service' || $current_schema === 'Service' );
                            $service_type = get_post_meta($post->ID, '_almaseo_service_type', true);
                            $service_area = get_post_meta($post->ID, '_almaseo_service_area', true);
                            ?>
                            <div id="almaseo-service-fields" style="<?php echo esc_attr(($show_service ? '' : 'display:none; ') . 'margin-top: 15px; padding: 15px; background: #f9fafb; border: 1px solid #e2e4e7; border-radius: 6px;'); ?>">
                                <h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #1d2327;">🛠️ <?php esc_html_e('Service Details', 'almaseo-seo-playground'); ?></h4>
                                <p class="field-hint" style="margin: 0 0 12px 0;"><?php esc_html_e('Describes a service you offer. The name and description come from your SEO title/description above; the fields below add specifics.', 'almaseo-seo-playground'); ?></p>
                                <div class="almaseo-field-group">
                                    <label for="almaseo_service_type" style="font-size: 12px; font-weight: 600;"><?php esc_html_e('Service Type', 'almaseo-seo-playground'); ?></label>
                                    <input type="text" id="almaseo_service_type" name="almaseo_service_type" class="almaseo-input" style="width: 100%;" value="<?php echo esc_attr($service_type); ?>" placeholder="<?php esc_attr_e('e.g. Property Management', 'almaseo-seo-playground'); ?>" />
                                </div>
                                <div class="almaseo-field-group">
                                    <label for="almaseo_service_area" style="font-size: 12px; font-weight: 600;"><?php esc_html_e('Area Served', 'almaseo-seo-playground'); ?></label>
                                    <input type="text" id="almaseo_service_area" name="almaseo_service_area" class="almaseo-input" style="width: 100%;" value="<?php echo esc_attr($service_area); ?>" placeholder="<?php esc_attr_e('e.g. Ferndale, MI', 'almaseo-seo-playground'); ?>" />
                                </div>
                            </div>

                            <!-- Recipe Fields (shown when Recipe selected) -->
                            <?php
                            $recipe_cuisine       = get_post_meta($post->ID, '_almaseo_recipe_cuisine', true);
                            $recipe_category      = get_post_meta($post->ID, '_almaseo_recipe_category', true);
                            $recipe_prep_minutes  = get_post_meta($post->ID, '_almaseo_recipe_prep_minutes', true);
                            $recipe_cook_minutes  = get_post_meta($post->ID, '_almaseo_recipe_cook_minutes', true);
                            $recipe_yield         = get_post_meta($post->ID, '_almaseo_recipe_yield', true);
                            $recipe_ingredients   = get_post_meta($post->ID, '_almaseo_recipe_ingredients', true);
                            $recipe_instructions  = get_post_meta($post->ID, '_almaseo_recipe_instructions', true);
                            $recipe_calories      = get_post_meta($post->ID, '_almaseo_recipe_calories', true);
                            $recipe_rating_value  = get_post_meta($post->ID, '_almaseo_recipe_rating_value', true);
                            $recipe_review_count  = get_post_meta($post->ID, '_almaseo_recipe_review_count', true);
                            $recipe_image         = get_post_meta($post->ID, '_almaseo_recipe_image', true);
                            $recipe_keywords      = get_post_meta($post->ID, '_almaseo_recipe_keywords', true);
                            $show_recipe = ( $primary_type === 'Recipe' || $current_schema === 'Recipe' );
                            ?>
                            <div id="almaseo-recipe-fields" style="<?php echo esc_attr(($show_recipe ? '' : 'display:none; ') . 'margin-top: 15px; padding: 15px; background: #f9fafb; border: 1px solid #e2e4e7; border-radius: 6px;'); ?>">
                                <h4 style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #1d2327;">🍳 <?php esc_html_e('Recipe Details', 'almaseo-seo-playground'); ?></h4>

                                <div style="margin-bottom: 14px; padding: 10px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; color: #475569; line-height: 1.5;">
                                    <?php esc_html_e('Use these fields when the page is a cooking recipe. Google requires name, image, and either ingredients OR instructions for the rich result. Author defaults to the post author. The post title becomes the recipe name.', 'almaseo-seo-playground'); ?>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">🏷️ <?php esc_html_e('Classification', 'almaseo-seo-playground'); ?></h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_recipe_cuisine" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Cuisine', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_recipe_cuisine" name="almaseo_recipe_cuisine" value="<?php echo esc_attr($recipe_cuisine); ?>" placeholder="Italian" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_recipe_category" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Category', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_recipe_category" name="almaseo_recipe_category" value="<?php echo esc_attr($recipe_category); ?>" placeholder="Dinner, Dessert, Appetizer" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">⏱️ <?php esc_html_e('Times & Yield', 'almaseo-seo-playground'); ?></h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_recipe_prep_minutes" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Prep Time (min)', 'almaseo-seo-playground'); ?></label>
                                        <input type="number" min="0" id="almaseo_recipe_prep_minutes" name="almaseo_recipe_prep_minutes" value="<?php echo esc_attr($recipe_prep_minutes); ?>" placeholder="15" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_recipe_cook_minutes" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Cook Time (min)', 'almaseo-seo-playground'); ?></label>
                                        <input type="number" min="0" id="almaseo_recipe_cook_minutes" name="almaseo_recipe_cook_minutes" value="<?php echo esc_attr($recipe_cook_minutes); ?>" placeholder="30" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_recipe_yield" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Yield / Servings', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_recipe_yield" name="almaseo_recipe_yield" value="<?php echo esc_attr($recipe_yield); ?>" placeholder="4 servings" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">📝 <?php esc_html_e('Recipe Content', 'almaseo-seo-playground'); ?></h5>
                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_recipe_ingredients" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Ingredients', 'almaseo-seo-playground'); ?></label>
                                    <textarea id="almaseo_recipe_ingredients" name="almaseo_recipe_ingredients" rows="6" class="almaseo-input" style="width: 100%; font-family: monospace; font-size: 12px;" placeholder="2 cups all-purpose flour&#10;1 tsp salt&#10;3 large eggs&#10;1/2 cup milk"><?php echo esc_textarea($recipe_ingredients); ?></textarea>
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('One ingredient per line.', 'almaseo-seo-playground'); ?></p>
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_recipe_instructions" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Instructions', 'almaseo-seo-playground'); ?></label>
                                    <textarea id="almaseo_recipe_instructions" name="almaseo_recipe_instructions" rows="6" class="almaseo-input" style="width: 100%; font-family: monospace; font-size: 12px;" placeholder="Preheat oven to 350°F.&#10;Whisk together dry ingredients in a large bowl.&#10;Add eggs and milk; mix until smooth.&#10;Pour into greased pan; bake 30 minutes."><?php echo esc_textarea($recipe_instructions); ?></textarea>
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('One step per line. Each becomes a HowToStep node.', 'almaseo-seo-playground'); ?></p>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">🥗 <?php esc_html_e('Nutrition & Reviews', 'almaseo-seo-playground'); ?></h5>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                                    <div>
                                        <label for="almaseo_recipe_calories" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Calories (per serving)', 'almaseo-seo-playground'); ?></label>
                                        <input type="number" min="0" id="almaseo_recipe_calories" name="almaseo_recipe_calories" value="<?php echo esc_attr($recipe_calories); ?>" placeholder="320" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_recipe_rating_value" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Avg Rating', 'almaseo-seo-playground'); ?></label>
                                        <input type="text" id="almaseo_recipe_rating_value" name="almaseo_recipe_rating_value" value="<?php echo esc_attr($recipe_rating_value); ?>" placeholder="4.7" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                    <div>
                                        <label for="almaseo_recipe_review_count" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('# Reviews', 'almaseo-seo-playground'); ?></label>
                                        <input type="number" min="0" id="almaseo_recipe_review_count" name="almaseo_recipe_review_count" value="<?php echo esc_attr($recipe_review_count); ?>" placeholder="89" class="almaseo-input" style="width: 100%;" />
                                    </div>
                                </div>

                                <h5 style="margin: 14px 0 6px 0; font-size: 12px; font-weight: 600; color: #475569;">🖼️ <?php esc_html_e('Image & Keywords', 'almaseo-seo-playground'); ?></h5>
                                <div style="margin-bottom: 10px;">
                                    <label for="almaseo_recipe_image" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Image URL', 'almaseo-seo-playground'); ?></label>
                                    <input type="url" id="almaseo_recipe_image" name="almaseo_recipe_image" value="<?php echo esc_attr($recipe_image); ?>" placeholder="https://example.com/dish.jpg" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Required by Google. Falls back to featured image.', 'almaseo-seo-playground'); ?></p>
                                </div>
                                <div style="margin-bottom: 0;">
                                    <label for="almaseo_recipe_keywords" style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 3px;"><?php esc_html_e('Keywords', 'almaseo-seo-playground'); ?></label>
                                    <input type="text" id="almaseo_recipe_keywords" name="almaseo_recipe_keywords" value="<?php echo esc_attr($recipe_keywords); ?>" placeholder="<?php esc_attr_e('weeknight, vegetarian, quick', 'almaseo-seo-playground'); ?>" class="almaseo-input" style="width: 100%;" />
                                    <p class="field-hint" style="margin: 3px 0 0 0; font-size: 11px;"><?php esc_html_e('Comma-separated.', 'almaseo-seo-playground'); ?></p>
                                </div>
                            </div>

                            <!-- Generic typed-panel show/hide based on selected schema type
                                 AND the "Also describe this page as:" secondary checkboxes.
                                 A panel shows if its type is the primary OR a checked secondary.
                                 Uses event delegation so handlers survive Gutenberg re-renders. -->
                            <script>
                            (function(){
                                var typePanelMap = {
                                    'MusicGroup':   'almaseo-musicgroup-fields',
                                    'Person':       'almaseo-person-fields',
                                    'Organization': 'almaseo-organization-fields',
                                    'Product':      'almaseo-product-fields',
                                    'Event':        'almaseo-event-fields',
                                    'Recipe':       'almaseo-recipe-fields',
                                    'LocalBusiness': 'almaseo-localbusiness-fields',
                                    'Service':      'almaseo-service-fields'
                                };

                                function getActiveTypes() {
                                    var active = {};
                                    var sel = document.getElementById('almaseo_schema_type');
                                    if (sel && sel.options[sel.selectedIndex]) {
                                        active[sel.options[sel.selectedIndex].value] = true;
                                    }
                                    var cbs = document.querySelectorAll('.almaseo-secondary-schema-cb');
                                    for (var i = 0; i < cbs.length; i++) {
                                        if (cbs[i].checked && !cbs[i].disabled) {
                                            active[cbs[i].getAttribute('data-type')] = true;
                                        }
                                    }
                                    return active;
                                }

                                function update() {
                                    var active = getActiveTypes();
                                    Object.keys(typePanelMap).forEach(function(type) {
                                        var panel = document.getElementById(typePanelMap[type]);
                                        if (panel) {
                                            panel.style.display = active[type] ? '' : 'none';
                                        }
                                    });
                                    // LocalBusiness usage warning visible whenever LB is active
                                    // (primary OR secondary) so users see the guidance once.
                                    var lbNote = document.getElementById('almaseo-lb-usage-note');
                                    if (lbNote) {
                                        lbNote.style.display = active['LocalBusiness'] ? '' : 'none';
                                    }
                                    // Disable the secondary checkbox matching the current primary
                                    // (can't add a type as both primary and secondary).
                                    var primary = (document.getElementById('almaseo_schema_type') || {}).value;
                                    var cbs = document.querySelectorAll('.almaseo-secondary-schema-cb');
                                    for (var i = 0; i < cbs.length; i++) {
                                        var match = (cbs[i].getAttribute('data-type') === primary);
                                        cbs[i].disabled = match;
                                        var label = cbs[i].closest('label');
                                        if (label) {
                                            label.style.opacity = match ? '0.45' : '1';
                                            label.style.cursor = match ? 'not-allowed' : 'pointer';
                                        }
                                        if (match) cbs[i].checked = false;
                                    }
                                }

                                document.addEventListener('change', function(e) {
                                    if (!e.target) return;
                                    if (e.target.id === 'almaseo_schema_type' ||
                                        e.target.classList.contains('almaseo-secondary-schema-cb')) {
                                        update();
                                    }
                                });

                                if (document.readyState === 'loading') {
                                    document.addEventListener('DOMContentLoaded', update);
                                } else {
                                    update();
                                }
                            })();
                            </script>

                            <div class="almaseo-field-group">
                                <label>
                                    <input type="checkbox"
                                           name="almaseo_schema_disable"
                                           value="1"
                                           <?php checked($disable_advanced, true); ?>>
                                    <?php esc_html_e('Disable Advanced Schema for this post', 'almaseo-seo-playground'); ?>
                                </label>
                                <p class="field-hint">Turn off advanced schema output while keeping basic schema</p>
                            </div>
                        <?php endif; ?>
                    </div>
                        <?php
                        // --- Additional Schema Types (multi-schema) ---
                        // A page can describe multiple things at once (e.g. a band that's
                        // also a venue → MusicGroup + LocalBusiness). The primary type above
                        // is what the page IS; these are additional facets. Output is a
                        // JSON-LD @graph with one node per checked type.
                        $secondary_raw = get_post_meta($post->ID, '_almaseo_schema_secondary_types', true);
                        $secondary_active = array();
                        if ($secondary_raw) {
                            $decoded = is_array($secondary_raw) ? $secondary_raw : json_decode($secondary_raw, true);
                            if (is_array($decoded)) {
                                $secondary_active = array_values(array_filter($decoded));
                            }
                        }
                        // Gated behind its own Pro feature flag (schema_multi) rather than
                        // the broader schema_advanced gate, so multi-schema can be priced/
                        // marketed independently. During dev both flags pass because
                        // almaseo_is_pro_active() defaults to 'pro'; flips to enforced
                        // when the tier-sync endpoint goes live.
                        $multi_schema_unlocked = almaseo_feature_available('schema_multi');
                        if ($multi_schema_unlocked):
                        ?>
                        <div style="margin-top: 18px; padding-top: 14px; border-top: 1px dashed #dcdcde;">
                            <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 4px;">
                                <?php esc_html_e('Also describe this page as:', 'almaseo-seo-playground'); ?>
                                <span style="font-weight: normal; color: #94a3b8; font-size: 11px; margin-left: 4px;"><?php esc_html_e('(optional — adds a separate node to the JSON-LD @graph)', 'almaseo-seo-playground'); ?></span>
                                <span class="almaseo-tier-badge almaseo-tier-pro" style="display: inline-block; font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 1px 5px; border-radius: 3px; line-height: 1.4; vertical-align: middle; margin-left: 4px; background: rgba(107, 33, 168, 0.15); color: #6b21a8;">PRO</span>
                            </label>
                            <p class="field-hint" style="margin: 0 0 8px 0; font-size: 11px;">
                                <?php esc_html_e('Use when one page genuinely represents more than one entity. Each checked type opens its own field panel below.', 'almaseo-seo-playground'); ?>
                            </p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 6px 12px; padding: 10px 12px; background: #f9fafb; border: 1px solid #e2e4e7; border-radius: 4px;">
                                <?php
                                // Build secondary checkbox list from the same options as the
                                // primary dropdown, minus Article (default fallback — no panel).
                                $secondary_hints = array(
                                    'FAQPage'       => 'only if the page has a visible Q&A section',
                                    'HowTo'         => 'only if it has step-by-step instructions',
                                    'LocalBusiness' => 'only if customers visit a physical address',
                                    'Service'       => 'only for a specific service you offer',
                                    'Product'       => 'only for a sellable product (price/availability)',
                                    'Event'         => 'only for a specific dated event',
                                    'Person'        => 'only for a page about one individual',
                                    'Organization'  => 'usually only on the homepage / about page',
                                    'MusicGroup'    => 'only for a band or artist page',
                                    'Recipe'        => 'only for a cooking recipe',
                                );
                                foreach ($schema_options as $opt) {
                                    if ($opt['value'] === 'Article' || $opt['locked']) continue;
                                    $opt_hint = isset($secondary_hints[$opt['value']]) ? $secondary_hints[$opt['value']] : '';
                                    $is_checked = in_array($opt['value'], $secondary_active, true);
                                    $is_primary = ($opt['value'] === $current_schema);
                                    ?>
                                    <label style="display: flex; align-items: flex-start; gap: 6px; font-size: 12px; cursor: <?php echo $is_primary ? 'not-allowed' : 'pointer'; ?>; opacity: <?php echo $is_primary ? '0.45' : '1'; ?>;">
                                        <input type="checkbox"
                                               class="almaseo-secondary-schema-cb"
                                               name="almaseo_schema_secondary_types[]"
                                               value="<?php echo esc_attr($opt['value']); ?>"
                                               data-type="<?php echo esc_attr($opt['value']); ?>"
                                               <?php checked($is_checked); ?>
                                               <?php disabled($is_primary); ?>
                                               style="margin-top: 2px;" />
                                        <span>
                                            <?php echo esc_html(preg_replace('/\s*\(.*$/', '', $opt['label'])); ?>
                                            <?php if ($is_primary): ?><em style="color: #94a3b8; font-size: 10px;"> <?php esc_html_e('(primary)', 'almaseo-seo-playground'); ?></em><?php endif; ?>
                                            <?php if ($opt_hint): ?><br><span style="color: #94a3b8; font-size: 10px;"><?php echo esc_html($opt_hint); ?></span><?php endif; ?>
                                        </span>
                                    </label>
                                <?php } ?>
                            </div>
                            <!-- LocalBusiness-specific guidance: most-misused schema type. -->
                            <div id="almaseo-lb-usage-note" style="display: none; margin-top: 8px; padding: 8px 12px; background: #fff8e5; border-left: 3px solid #dba617; border-radius: 3px; font-size: 11px; color: #5c4a00; line-height: 1.5;">
                                <strong>⚠ <?php esc_html_e('LocalBusiness usage tip:', 'almaseo-seo-playground'); ?></strong>
                                <?php esc_html_e('Use this only when customers physically visit your address (storefront, restaurant, clinic, studio). For service-area businesses that travel to clients (wedding bands, plumbers, mobile groomers), prefer the primary type with `areaServed` instead — adding LocalBusiness without a real visitable address can mislead Google\'s entity resolution and trigger the wrong rich result.', 'almaseo-seo-playground'); ?>
                            </div>
                        </div>
                        <?php endif; // $multi_schema_unlocked ?>
                    </div>

                    <!-- Validator callout — a prominent card (not plain buttons) so
                         users actually notice they can test. Both tools fetch the
                         live public URL, so the notice tells users to publish/save
                         first; the validators check what's deployed, not the editor. -->
                    <?php
                    $perma = get_permalink($post->ID);
                    $is_published = ($post->post_status === 'publish' && !empty($perma));
                    $rr_url  = 'https://search.google.com/test/rich-results?url=' . rawurlencode($perma);
                    $sch_url = 'https://validator.schema.org/?url=' . rawurlencode($perma);
                    ?>
                    <div class="almaseo-schema-validator" style="margin-top: 20px; padding: 16px; background: linear-gradient(135deg, #eef5ff 0%, #f5f0ff 100%); border: 1px solid #c7d7f0; border-radius: 8px;">
                        <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: #1d2327; display: flex; align-items: center; gap: 6px;">
                            <span class="dashicons dashicons-search" style="color: #2271b1;"></span>
                            <?php esc_html_e('Test your structured data', 'almaseo-seo-playground'); ?>
                        </h4>
                        <p style="margin: 0 0 12px 0; font-size: 12px; color: #50575e; line-height: 1.5;">
                            <?php esc_html_e('Run this page through the official Google and Schema.org validators to confirm the markup is valid and eligible for rich results.', 'almaseo-seo-playground'); ?>
                        </p>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="<?php echo $is_published ? esc_url($rr_url) : '#'; ?>"
                               target="_blank" rel="noopener noreferrer"
                               class="button button-primary button-hero"
                               style="<?php echo $is_published ? '' : 'pointer-events:none; opacity:0.5;'; ?>"
                               <?php echo $is_published ? '' : 'aria-disabled="true"'; ?>>
                                🔍 <?php esc_html_e('Test in Google Rich Results', 'almaseo-seo-playground'); ?>
                            </a>
                            <a href="<?php echo $is_published ? esc_url($sch_url) : '#'; ?>"
                               target="_blank" rel="noopener noreferrer"
                               class="button button-secondary button-hero"
                               style="<?php echo $is_published ? '' : 'pointer-events:none; opacity:0.5;'; ?>"
                               <?php echo $is_published ? '' : 'aria-disabled="true"'; ?>>
                                ✓ <?php esc_html_e('Validate on Schema.org', 'almaseo-seo-playground'); ?>
                            </a>
                        </div>
                        <p style="margin: 12px 0 0 0; padding: 8px 10px; background: #fffbe6; border: 1px solid #f5d76e; border-radius: 4px; font-size: 11px; color: #5c4a00; line-height: 1.5;">
                            <?php if (!$is_published): ?>
                                <strong>⚠ <?php esc_html_e('Publish this page first.', 'almaseo-seo-playground'); ?></strong>
                                <?php esc_html_e('The validators load the page\'s live public URL, which does not exist until the page is published.', 'almaseo-seo-playground'); ?>
                            <?php else: ?>
                                <strong>💾 <?php esc_html_e('Save your changes before testing.', 'almaseo-seo-playground'); ?></strong>
                                <?php esc_html_e('These tools fetch the live published page — they validate what is currently public, not unsaved edits in this editor. Click Update first, then test.', 'almaseo-seo-playground'); ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <!-- Collapsible Schema Preview -->
                    <div class="almaseo-collapsible" style="margin-top: 20px;">
                        <button type="button" class="almaseo-collapsible-toggle" data-target="schema-jsonld-preview" style="width: 100%; text-align: left; padding: 10px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 3px; cursor: pointer;">
                            <span class="dashicons dashicons-arrow-down-alt2" style="margin-right: 5px;"></span>
                            Preview: <span id="schema-preview-type-label">Schema</span> JSON-LD
                        </button>
                        <div id="schema-jsonld-preview" class="almaseo-collapsible-content" style="display: none; margin-top: 10px; padding: 15px; background: #2c3338; border-radius: 3px;">
                            <div style="position: relative;">
                                <button type="button" class="copy-json-btn" style="position: absolute; top: 5px; right: 5px; padding: 5px 10px; background: #2271b1; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">Copy JSON</button>
                                <pre id="schema-json-preview" style="color: #50fa7b; font-family: 'Courier New', monospace; font-size: 12px; overflow-x: auto; margin: 0;">Loading preview...</pre>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Social Metadata Card -->
            <div class="almaseo-card">
                <div class="almaseo-card-header">
                    <h3 class="almaseo-card-title">
                        <span class="card-icon">📱</span>
                        Social Metadata
                    </h3>
                    <p class="almaseo-card-description">Control how your content appears when shared on social networks</p>
                </div>
                <div class="almaseo-card-body">
                    <!-- Open Graph Section -->
                    <div class="social-section">
                        <h4 class="social-section-title">
                            <span style="display: inline-flex; align-items: center; gap: 8px;">
                                <svg style="width: 20px; height: 20px; fill: #1877f2;" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                <svg style="width: 20px; height: 20px; fill: #0077b5;" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                                Open Graph
                            </span>
                        </h4>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_og_title">OG Title</label>
                            <input type="text" 
                                   id="almaseo_og_title" 
                                   name="almaseo_og_title" 
                                   value="<?php echo esc_attr(get_post_meta($post->ID, '_almaseo_og_title', true)); ?>" 
                                   placeholder="<?php echo esc_attr($seo_title ?: get_the_title($post->ID)); ?>"
                                   class="almaseo-input">
                            <p class="field-hint">Leave empty to use SEO title</p>
                        </div>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_og_description">OG Description</label>
                            <textarea id="almaseo_og_description" 
                                      name="almaseo_og_description" 
                                      placeholder="<?php echo esc_attr($seo_description ?: wp_trim_words($post->post_content, 30)); ?>"
                                      class="almaseo-textarea"><?php echo esc_textarea(get_post_meta($post->ID, '_almaseo_og_description', true)); ?></textarea>
                            <p class="field-hint">Leave empty to use meta description</p>
                        </div>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_og_image">OG Image URL</label>
                            <input type="url" 
                                   id="almaseo_og_image" 
                                   name="almaseo_og_image" 
                                   value="<?php echo esc_attr(get_post_meta($post->ID, '_almaseo_og_image', true)); ?>" 
                                   placeholder="<?php echo esc_attr(get_the_post_thumbnail_url($post->ID, 'large')); ?>"
                                   class="almaseo-input">
                            <p class="field-hint">Recommended: 1200x630px. Leave empty to use the featured image.</p>
                        </div>
                    </div>
                    
                    <!-- Twitter Section -->
                    <div class="social-section">
                        <h4 class="social-section-title">
                            <span style="display: inline-flex; align-items: center; gap: 8px;">
                                <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24">
                                    <path fill="#000" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                                Twitter Card
                            </span>
                        </h4>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_twitter_card">Card Type</label>
                            <select id="almaseo_twitter_card" 
                                    name="almaseo_twitter_card" 
                                    class="almaseo-select"
                                    data-has-featured-image="<?php echo esc_attr(has_post_thumbnail($post->ID) ? 'true' : 'false'); ?>">
                                <?php 
                                $twitter_card = get_post_meta($post->ID, '_almaseo_twitter_card', true) ?: 'summary_large_image';
                                ?>
                                <option value="summary" <?php selected($twitter_card, 'summary'); ?>>Summary</option>
                                <option value="summary_large_image" <?php selected($twitter_card, 'summary_large_image'); ?>>Summary with Large Image</option>
                            </select>
                        </div>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_twitter_title">Twitter Title</label>
                            <input type="text" 
                                   id="almaseo_twitter_title" 
                                   name="almaseo_twitter_title" 
                                   value="<?php echo esc_attr(get_post_meta($post->ID, '_almaseo_twitter_title', true)); ?>" 
                                   placeholder="<?php echo esc_attr($seo_title ?: get_the_title($post->ID)); ?>"
                                   class="almaseo-input">
                            <p class="field-hint">Leave empty to use OG title or SEO title</p>
                        </div>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_twitter_description">Twitter Description</label>
                            <textarea id="almaseo_twitter_description" 
                                      name="almaseo_twitter_description" 
                                      placeholder="<?php echo esc_attr($seo_description ?: wp_trim_words($post->post_content, 30)); ?>"
                                      class="almaseo-textarea"><?php echo esc_textarea(get_post_meta($post->ID, '_almaseo_twitter_description', true)); ?></textarea>
                            <p class="field-hint">Leave empty to use OG description or meta description</p>
                        </div>
                    </div>
                    
                    <!-- Visual Social Previews -->
                    <div class="almaseo-social-previews" style="margin-top: 20px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <h4 style="margin: 0; font-size: 14px; font-weight: 600; color: #1d2327;">
                                <?php esc_html_e( 'Social Sharing Preview', 'almaseo-seo-playground' ); ?>
                            </h4>
                            <button type="button" class="button button-small" id="toggle-social-previews" style="font-size: 12px;">Show Previews</button>
                        </div>
                        <div id="social-previews-content" style="display: none;">

                        <?php
                        $sp_title       = get_post_meta( $post->ID, '_almaseo_og_title', true ) ?: ( $seo_title ?: get_the_title( $post->ID ) );
                        $sp_description = get_post_meta( $post->ID, '_almaseo_og_description', true ) ?: ( $seo_description ?: wp_trim_words( $post->post_content, 25 ) );
                        $sp_image       = get_post_meta( $post->ID, '_almaseo_og_image', true ) ?: get_the_post_thumbnail_url( $post->ID, 'large' );
                        $sp_domain      = wp_parse_url( home_url(), PHP_URL_HOST );
                        $sp_tw_card     = get_post_meta( $post->ID, '_almaseo_twitter_card', true ) ?: 'summary_large_image';
                        ?>

                        <!-- Facebook Preview -->
                        <p style="font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 6px 0;">Facebook / LinkedIn Preview</p>
                        <div class="almaseo-fb-preview" style="max-width: 500px; border: 1px solid #dadde1; border-radius: 3px; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #fff; margin-bottom: 16px;">
                            <div id="fb-preview-image" style="width: 100%; aspect-ratio: 1.91/1; background: #e4e6eb url('<?php echo esc_url( $sp_image ); ?>') center/cover no-repeat; position: relative;">
                                <?php if ( empty( $sp_image ) ) : ?>
                                <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #8a8d91; font-size: 13px;">
                                    <?php esc_html_e( 'No image set', 'almaseo-seo-playground' ); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div style="padding: 10px 12px; border-top: 1px solid #dadde1; background: #f2f3f5;">
                                <div id="fb-preview-domain" style="font-size: 12px; color: #606770; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 3px;">
                                    <?php echo esc_html( $sp_domain ); ?>
                                </div>
                                <div id="fb-preview-title" style="font-size: 16px; font-weight: 600; color: #1d2129; line-height: 1.3; margin-bottom: 3px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo esc_html( $sp_title ); ?>
                                </div>
                                <div id="fb-preview-desc" style="font-size: 14px; color: #606770; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo esc_html( $sp_description ); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Twitter/X Preview -->
                        <p style="font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin: 16px 0 6px 0;">Twitter / X Preview</p>
                        <div class="almaseo-twitter-preview" id="twitter-preview-container" data-card-type="<?php echo esc_attr( $sp_tw_card ); ?>" style="max-width: 500px; border: 1px solid #cfd9de; border-radius: 16px; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #fff;">
                            <?php if ( $sp_tw_card === 'summary_large_image' ) : ?>
                            <!-- Large Image Card -->
                            <div id="tw-preview-image" style="width: 100%; aspect-ratio: 2/1; background: #eff3f4 url('<?php echo esc_url( $sp_image ); ?>') center/cover no-repeat; position: relative;">
                                <?php if ( empty( $sp_image ) ) : ?>
                                <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #8a8d91; font-size: 13px;">
                                    <?php esc_html_e( 'No image set', 'almaseo-seo-playground' ); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div style="padding: 12px;">
                                <div id="tw-preview-domain" style="font-size: 13px; color: #536471; margin-bottom: 2px;">
                                    <?php echo esc_html( $sp_domain ); ?>
                                </div>
                                <div id="tw-preview-title" style="font-size: 15px; font-weight: 700; color: #0f1419; line-height: 1.3; margin-bottom: 2px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo esc_html( $sp_title ); ?>
                                </div>
                                <div id="tw-preview-desc" style="font-size: 15px; color: #536471; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo esc_html( $sp_description ); ?>
                                </div>
                            </div>
                            <?php else : ?>
                            <!-- Summary Card (small image left) -->
                            <div style="display: flex;">
                                <div id="tw-preview-image" style="width: 130px; min-height: 130px; background: #eff3f4 url('<?php echo esc_url( $sp_image ); ?>') center/cover no-repeat; flex-shrink: 0; border-right: 1px solid #cfd9de;">
                                </div>
                                <div style="padding: 12px; flex: 1; min-width: 0;">
                                    <div id="tw-preview-domain" style="font-size: 13px; color: #536471; margin-bottom: 2px;">
                                        <?php echo esc_html( $sp_domain ); ?>
                                    </div>
                                    <div id="tw-preview-title" style="font-size: 15px; font-weight: 700; color: #0f1419; line-height: 1.3; margin-bottom: 2px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo esc_html( $sp_title ); ?>
                                    </div>
                                    <div id="tw-preview-desc" style="font-size: 15px; color: #536471; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo esc_html( $sp_description ); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <p style="margin-top: 8px; font-size: 12px; color: #646970; font-style: italic;">
                            <?php esc_html_e( 'Previews are approximate. Actual appearance may vary by platform.', 'almaseo-seo-playground' ); ?>
                        </p>
                        </div><!-- end social-previews-content -->
                        <script>
                        jQuery(document).ready(function($){
                            $('#toggle-social-previews').on('click', function(){
                                var $content = $('#social-previews-content');
                                $content.slideToggle(200);
                                $(this).text($content.is(':visible') ? 'Show Previews' : 'Hide Previews');
                            });
                        });
                        </script>
                    </div>

                    <script>
                    (function(){
                        /* Live Social Preview Updates */
                        var fields = {
                            ogTitle:    document.getElementById('almaseo_og_title'),
                            ogDesc:     document.getElementById('almaseo_og_description'),
                            ogImage:    document.getElementById('almaseo_og_image'),
                            twTitle:    document.getElementById('almaseo_twitter_title'),
                            twDesc:     document.getElementById('almaseo_twitter_description'),
                            twCard:     document.getElementById('almaseo_twitter_card'),
                            seoTitle:   document.getElementById('almaseo_seo_title'),
                            seoDesc:    document.getElementById('almaseo_seo_description'),
                        };

                        var fbImage = document.getElementById('fb-preview-image');
                        var fbTitle = document.getElementById('fb-preview-title');
                        var fbDesc  = document.getElementById('fb-preview-desc');
                        var twImage = document.getElementById('tw-preview-image');
                        var twTitle = document.getElementById('tw-preview-title');
                        var twDesc  = document.getElementById('tw-preview-desc');
                        var twWrap  = document.getElementById('twitter-preview-container');

                        var postTitle = <?php echo wp_json_encode( get_the_title( $post->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional JSON output ?>;
                        var postExcerpt = <?php echo wp_json_encode( wp_trim_words( $post->post_content, 25 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional JSON output ?>;
                        var featuredImage = <?php echo wp_json_encode( get_the_post_thumbnail_url( $post->ID, 'large' ) ?: '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional JSON output ?>;

                        function getVal(el) { return el ? el.value.trim() : ''; }

                        function resolveTitle() {
                            return getVal(fields.ogTitle) || getVal(fields.seoTitle) || postTitle || '';
                        }
                        function resolveDesc() {
                            return getVal(fields.ogDesc) || getVal(fields.seoDesc) || postExcerpt || '';
                        }
                        function resolveImage() {
                            return getVal(fields.ogImage) || featuredImage;
                        }

                        function updatePreviews() {
                            var title = resolveTitle();
                            var desc  = resolveDesc();
                            var image = resolveImage();

                            /* Facebook */
                            if (fbTitle) fbTitle.textContent = title;
                            if (fbDesc)  fbDesc.textContent  = desc;
                            if (fbImage) {
                                fbImage.style.backgroundImage = image ? 'url(' + image + ')' : 'none';
                                fbImage.innerHTML = image ? '' : '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#8a8d91;font-size:13px;">No image set</div>';
                            }

                            /* Twitter */
                            var twTitleVal = getVal(fields.twTitle) || title;
                            var twDescVal  = getVal(fields.twDesc) || desc;
                            if (twTitle) twTitle.textContent = twTitleVal;
                            if (twDesc)  twDesc.textContent  = twDescVal;
                            if (twImage) {
                                twImage.style.backgroundImage = image ? 'url(' + image + ')' : 'none';
                            }
                        }

                        /* Bind input events */
                        var inputFields = [fields.ogTitle, fields.ogDesc, fields.ogImage, fields.twTitle, fields.twDesc, fields.seoTitle, fields.seoDesc];
                        for (var i = 0; i < inputFields.length; i++) {
                            if (inputFields[i]) {
                                inputFields[i].addEventListener('input', updatePreviews);
                            }
                        }

                        /* Card type change rebuilds Twitter preview layout */
                        if (fields.twCard) {
                            fields.twCard.addEventListener('change', function() {
                                /* Reload the page section would be complex; for now just update the data attr */
                                if (twWrap) twWrap.setAttribute('data-card-type', this.value);
                            });
                        }
                    })();
                    </script>
                </div>
            </div>
            
            <!-- Historical Metadata Tracker -->
            <div class="almaseo-metadata-history">
                <div class="history-header">
                    <h3>📜 Metadata History</h3>
                    <button type="button" class="history-toggle-btn" id="toggle-metadata-history">
                        <span class="toggle-icon">▼</span>
                        <span class="toggle-text">Show History</span>
                    </button>
                </div>
                
                <div class="metadata-history-content" id="metadata-history-content" style="display: none;">
                    <?php
                    // Get historical metadata
                    $metadata_history = get_post_meta($post->ID, '_almaseo_metadata_history', true);
                    if (!is_array($metadata_history)) {
                        $metadata_history = [];
                    }
                    
                    // Sort by timestamp (newest first)
                    usort($metadata_history, function($a, $b) {
                        return $b['timestamp'] - $a['timestamp'];
                    });
                    
                    if (empty($metadata_history)) {
                        echo '<p class="no-history">No metadata history available yet. Changes will be tracked when you update SEO fields.</p>';
                    } else {
                        echo '<div class="history-timeline">';
                        foreach ($metadata_history as $index => $entry) {
                            $date = wp_date('F j, Y g:i A', $entry['timestamp']);
                            ?>
                            <div class="history-entry" data-index="<?php echo esc_attr($index); ?>">
                                <div class="history-date">
                                    <span class="date-icon">📅</span>
                                    <?php echo esc_html($date); ?>
                                </div>
                                
                                <div class="history-fields">
                                    <?php if (!empty($entry['title'])): ?>
                                    <div class="history-field">
                                        <strong>Title:</strong> 
                                        <span class="field-value"><?php echo esc_html($entry['title']); ?></span>
                                        <button type="button" 
                                                class="restore-btn" 
                                                data-field="title" 
                                                data-value="<?php echo esc_attr($entry['title']); ?>"
                                                title="Restore this title">
                                            ↩️
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($entry['description'])): ?>
                                    <div class="history-field">
                                        <strong>Description:</strong> 
                                        <span class="field-value"><?php echo esc_html($entry['description']); ?></span>
                                        <button type="button" 
                                                class="restore-btn" 
                                                data-field="description" 
                                                data-value="<?php echo esc_attr($entry['description']); ?>"
                                                title="Restore this description">
                                            ↩️
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($entry['keyword'])): ?>
                                    <div class="history-field">
                                        <strong>Focus Keyword:</strong> 
                                        <span class="field-value"><?php echo esc_html($entry['keyword']); ?></span>
                                        <button type="button" 
                                                class="restore-btn" 
                                                data-field="keyword" 
                                                data-value="<?php echo esc_attr($entry['keyword']); ?>"
                                                title="Restore this keyword">
                                            ↩️
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($entry['user'])): ?>
                                    <div class="history-meta">
                                        <small>Changed by: <?php echo esc_html($entry['user']); ?></small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="history-actions">
                                    <button type="button" 
                                            class="restore-all-btn" 
                                            data-index="<?php echo esc_attr($index); ?>"
                                            title="Restore all fields from this version">
                                        Restore All
                                    </button>
                                </div>
                            </div>
                            <?php
                        }
                        echo '</div>';
                        
                        // Limit history to last 20 entries to prevent bloat
                        if (count($metadata_history) > 20) {
                            $metadata_history = array_slice($metadata_history, 0, 20);
                            update_post_meta($post->ID, '_almaseo_metadata_history', $metadata_history);
                        }
                    }
                    ?>
                </div>
            </div>
            
            <!-- Troubleshooting Schema Issues Help Section (collapsible) -->
            <div class="almaseo-card" style="margin-top: 20px; background: #f8f9fa; border: 1px solid #e0e0e0;">
                <div class="almaseo-card-header" style="background: linear-gradient(135deg, #fff8e5 0%, #fffef5 100%); border-bottom: 1px solid #f0f0f1; cursor: pointer;" id="troubleshooting-toggle">
                    <h3 class="almaseo-card-title" style="display: flex; align-items: center; justify-content: space-between;">
                        <span>
                            <span class="card-icon">❓</span>
                            Troubleshooting Schema Issues
                        </span>
                        <span class="dashicons dashicons-arrow-down-alt2" id="troubleshooting-arrow" style="color: #94a3b8;"></span>
                    </h3>
                </div>
                <div class="almaseo-card-body" id="troubleshooting-content" style="display: none;">
                    <!-- Schema Markup Output Section -->
                    <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e0e0e0;">
                        <h4 style="font-size: 15px; margin-bottom: 12px; color: #1d2327; font-weight: 600;">📋 Schema Markup Output</h4>
                        
                        <div style="margin-bottom: 15px;">
                            <h5 style="font-size: 13px; margin-bottom: 8px; color: #2c3338; font-weight: 600;">Missing or Empty Schema Block</h5>
                            <p style="font-size: 13px; color: #646970; margin-bottom: 8px;">
                                If you enable Exclusive Schema Mode, AlmaSEO will suppress ALL schema markup (including its own) to prevent duplicate JSON-LD from appearing. 
                                In this mode you may still see debug comments such as:
                            </p>
                            <ul style="font-size: 12px; color: #8c8f94; margin-left: 20px; font-family: monospace;">
                                <li>&lt;!-- AlmaSEO Generator: START --&gt;</li>
                                <li>&lt;!-- AlmaSEO Generator: OK --&gt;</li>
                            </ul>
                            <p style="font-size: 13px; color: #646970; margin-top: 8px;">
                                But you will NOT see a <code style="background: #f0f0f1; padding: 2px 4px; border-radius: 2px; font-size: 12px;">&lt;script type="application/ld+json"&gt;</code> block. 
                                This is expected behavior when Exclusive Mode is enabled.
                            </p>
                        </div>
                        
                        <div>
                            <h5 style="font-size: 13px; margin-bottom: 8px; color: #2c3338; font-weight: 600;">"Missing Field" Warnings in Rich Results Test</h5>
                            <p style="font-size: 13px; color: #646970; margin-bottom: 8px;">
                                Sometimes Google's Rich Results tool shows "missing field" warnings. These may come from:
                            </p>
                            <ul style="font-size: 13px; color: #646970; margin-left: 20px;">
                                <li>Other active SEO/Schema plugins</li>
                                <li>Your theme's built-in structured data</li>
                                <li>Third-party scripts (reviews, events, etc.)</li>
                            </ul>
                            <p style="font-size: 13px; color: #646970; margin-top: 8px;">
                                AlmaSEO does not generate these warnings itself. If Exclusive Mode is OFF, check for overlapping plugins 
                                (e.g., Yoast, AIOSEO, RankMath). If Exclusive Mode is ON, AlmaSEO's schema is completely removed, 
                                so any remaining warnings originate elsewhere.
                            </p>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <h4 style="font-size: 14px; margin-bottom: 8px; color: #1d2327;">Duplicate Schema Warnings</h4>
                        <p style="font-size: 13px; color: #646970; margin-bottom: 10px;">
                            Duplicate schema markup can come from multiple sources:
                        </p>
                        <ul style="font-size: 13px; color: #646970; margin-left: 20px;">
                            <li>Your WordPress theme may include built-in schema</li>
                            <li>WordPress core adds basic schema on certain page types</li>
                            <li>Other SEO plugins may output competing schema markup</li>
                        </ul>
                        <div style="padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; margin-top: 10px; border-radius: 3px;">
                            <p style="font-size: 13px; color: #856404; margin: 0;">
                                <strong>⚠️ Important:</strong> Deactivated plugins can STILL cause schema conflicts. 
                                Other SEO plugins must be completely removed/deleted, not just deactivated, to fully eliminate conflicts.
                            </p>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <h4 style="font-size: 14px; margin-bottom: 8px; color: #1d2327;">"Missing Field" Warnings</h4>
                        <p style="font-size: 13px; color: #646970;">
                            Non-critical "missing field" warnings in validation tools are often caused by schema from external sources 
                            (themes, plugins, including deactivated but not removed SEO plugins). 
                            These are typically NOT errors in AlmaSEO's schema output.
                        </p>
                    </div>
                    
                    <div style="margin-bottom: 15px; padding: 12px; background: #e8f5e9; border-radius: 4px;">
                        <h4 style="font-size: 14px; margin-bottom: 8px; color: #1d2327;">
                            ✅ Solution: Exclusive Schema Mode
                        </h4>
                        <p style="font-size: 13px; color: #646970; margin-bottom: 10px;">
                            AlmaSEO's Exclusive Schema Mode helps prevent these issues by removing competing schema markup from other plugins and themes, 
                            ensuring only AlmaSEO's complete, validated schema appears.
                        </p>
                        <?php 
                        // Get exclusive mode setting
                        $exclusive_mode = get_option('almaseo_exclusive_schema_enabled', false);
                        if (!$exclusive_mode): 
                        ?>
                        <p style="font-size: 13px; color: #667eea; font-weight: 500;">
                            → Enable Exclusive Schema Mode in the <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-settings')); ?>">Settings</a>
                        </p>
                        <?php else: ?>
                        <p style="font-size: 13px; color: #00a32a; font-weight: 500;">
                            ✓ Exclusive Schema Mode is currently ENABLED
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="padding: 12px; background: #f0f8ff; border-radius: 4px;">
                        <h4 style="font-size: 14px; margin-bottom: 8px; color: #1d2327;">🔍 Verification Tools</h4>
                        <p style="font-size: 13px; color: #646970; margin-bottom: 8px;">
                            Always verify your schema output using:
                        </p>
                        <ul style="font-size: 13px; margin-left: 20px;">
                            <li><a href="https://search.google.com/test/rich-results" target="_blank" style="color: #2271b1;">Google's Rich Results Test</a> - Test how Google sees your schema</li>
                            <li><a href="https://validator.schema.org/" target="_blank" style="color: #2271b1;">Schema.org Validator</a> - Validate schema syntax</li>
                            <li>View page source and search for <code style="background: #f0f0f1; padding: 2px 4px; border-radius: 2px;">&lt;script type="application/ld+json"</code></li>
                        </ul>
                    </div>
                </div>
            </div>

            <script>
            jQuery(document).ready(function($){
                $('#troubleshooting-toggle').on('click', function(){
                    var $content = $('#troubleshooting-content');
                    var $arrow = $('#troubleshooting-arrow');
                    $content.slideToggle(200);
                    $arrow.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
                });
            });
            </script>
        </div>
        <!-- End Schema & Meta Tab -->
        
        <!-- Analytics Tab -->
        <div class="almaseo-tab-panel" id="tab-analytics">
            <?php
            $ga_page_url = get_permalink($post->ID);
            $ga_post_id  = $post->ID;
            ?>

            <!-- ═══ GA Container — always rendered, JS manages states ═══ -->
            <div class="almaseo-ga-container"
                 id="almaseo-ga-container"
                 data-post-id="<?php echo esc_attr($ga_post_id); ?>"
                 data-page-url="<?php echo esc_attr($ga_page_url); ?>"
                 data-nonce="<?php echo esc_attr(wp_create_nonce('almaseo_ga_nonce')); ?>"
                 data-connected="<?php echo esc_attr($is_connected ? '1' : '0'); ?>">

                <!-- ═══ State 1: Connection Issue (fallback) ═══ -->
                <div class="almaseo-ga-state" id="ga-state-not-connected" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#dba617" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">AlmaSEO connection not detected</h3>
                        <p class="gsc-state-description">
                            The plugin couldn't verify a connection to your AlmaSEO Dashboard. Please check your connection settings.
                        </p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=seo-playground-connection')); ?>" class="gsc-state-btn gsc-btn-secondary">
                            Check Connection Settings
                        </a>
                    </div>
                </div>

                <!-- ═══ State 2: Default — prompt to load GA data ═══ -->
                <div class="almaseo-ga-state" id="ga-state-no-ga">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3v18h18"/>
                                <path d="M18 17V9"/>
                                <path d="M13 17V5"/>
                                <path d="M8 17v-3"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">Google Analytics</h3>
                        <p class="gsc-state-description">
                            See how this page performs in Google Analytics — visitors, sessions, engagement, traffic sources, and conversions — all right here while you edit.
                        </p>
                        <button type="button" id="ga-connect-btn" class="gsc-state-btn gsc-btn-primary">
                            Load Analytics Data
                        </button>
                        <p class="gsc-state-hint">
                            Don't have Analytics connected yet?
                            <a href="https://app.almaseo.com/profile/google-services" target="_blank">Connect it in your Dashboard</a>
                        </p>
                    </div>
                </div>

                <!-- ═══ State 3: No GA4 property selected ═══ -->
                <div class="almaseo-ga-state" id="ga-state-no-property" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#dba617" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-6"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">Pick a GA4 property</h3>
                        <p class="gsc-state-description">
                            Google Analytics is connected, but no GA4 property is mapped to this site yet. Choose which property this site reports to in your Dashboard.
                        </p>
                        <a href="https://app.almaseo.com/profile/google-services" target="_blank" class="gsc-state-btn gsc-btn-primary">
                            Select Property in Dashboard
                            <span class="gsc-btn-arrow">&rarr;</span>
                        </a>
                    </div>
                </div>

                <!-- ═══ State 4: Loading ═══ -->
                <div class="almaseo-ga-state" id="ga-state-loading" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-loading-spinner"></div>
                        <h3 class="gsc-state-heading">Fetching analytics…</h3>
                        <p class="gsc-state-description">Loading Google Analytics data for this page.</p>
                    </div>
                    <div class="gsc-skeleton-metrics">
                        <div class="gsc-skeleton-card"></div>
                        <div class="gsc-skeleton-card"></div>
                        <div class="gsc-skeleton-card"></div>
                        <div class="gsc-skeleton-card"></div>
                    </div>
                </div>

                <!-- ═══ State 5: No Data ═══ -->
                <div class="almaseo-ga-state" id="ga-state-no-data" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">No analytics data yet</h3>
                        <p class="gsc-state-description">
                            This page hasn't recorded any visits in the selected time period. This is normal for newer or low-traffic pages.
                        </p>
                    </div>
                </div>

                <!-- ═══ State 6: Data Available (Main View) ═══ -->
                <div class="almaseo-ga-state" id="ga-state-data" style="display: none;">

                    <div class="gsc-data-header">
                        <div class="gsc-data-title-row">
                            <div>
                                <h2 class="gsc-data-title">Page Analytics</h2>
                                <div class="gsc-page-url" id="ga-page-url" title="<?php echo esc_attr($ga_page_url); ?>">
                                    <?php echo esc_html($ga_page_url); ?>
                                </div>
                            </div>
                            <div class="gsc-index-badge gsc-badge-indexed" id="ga-property-badge">
                                <span class="gsc-badge-dot"></span>
                                <span id="ga-property-name">GA4</span>
                            </div>
                        </div>
                        <div class="gsc-data-context">
                            This data is specific to the page you are currently editing — not your entire site. Use it to understand how visitors find and engage with this page.
                        </div>
                        <div class="gsc-data-controls">
                            <select class="gsc-date-range-selector" id="ga-date-range" aria-label="Date range">
                                <option value="7">Last 7 days</option>
                                <option value="28" selected>Last 28 days</option>
                                <option value="90">Last 90 days</option>
                            </select>
                            <button type="button" class="gsc-refresh-btn" id="ga-refresh-btn" title="Refresh data">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Metric Cards -->
                    <div class="ga-metrics-grid">
                        <div class="gsc-metric-card"><div class="gsc-metric-label">Views</div><div class="gsc-metric-value" id="ga-views">--</div><div class="gsc-metric-trend" id="ga-views-trend"><span class="gsc-trend-arrow"></span><span class="gsc-trend-value"></span><span class="gsc-trend-label">vs prev</span></div></div>
                        <div class="gsc-metric-card"><div class="gsc-metric-label">Sessions</div><div class="gsc-metric-value" id="ga-sessions">--</div><div class="gsc-metric-trend" id="ga-sessions-trend"><span class="gsc-trend-arrow"></span><span class="gsc-trend-value"></span><span class="gsc-trend-label">vs prev</span></div></div>
                        <div class="gsc-metric-card"><div class="gsc-metric-label">Users</div><div class="gsc-metric-value" id="ga-users">--</div><div class="gsc-metric-trend" id="ga-users-trend"><span class="gsc-trend-arrow"></span><span class="gsc-trend-value"></span><span class="gsc-trend-label">vs prev</span></div></div>
                        <div class="gsc-metric-card"><div class="gsc-metric-label">New Users</div><div class="gsc-metric-value" id="ga-newusers">--</div><div class="gsc-metric-trend" id="ga-newusers-trend"><span class="gsc-trend-arrow"></span><span class="gsc-trend-value"></span><span class="gsc-trend-label">vs prev</span></div></div>
                        <div class="gsc-metric-card"><div class="gsc-metric-label">Engaged Sessions</div><div class="gsc-metric-value" id="ga-engaged">--</div><div class="gsc-metric-trend" id="ga-engaged-trend"><span class="gsc-trend-arrow"></span><span class="gsc-trend-value"></span><span class="gsc-trend-label">vs prev</span></div></div>
                        <div class="gsc-metric-card"><div class="gsc-metric-label">Engagement Rate</div><div class="gsc-metric-value" id="ga-engagementrate">--</div><div class="gsc-metric-trend" id="ga-engagementrate-trend"><span class="gsc-trend-arrow"></span><span class="gsc-trend-value"></span><span class="gsc-trend-label">vs prev</span></div></div>
                        <div class="gsc-metric-card"><div class="gsc-metric-label">Bounce Rate</div><div class="gsc-metric-value" id="ga-bounce">--</div><div class="gsc-metric-trend" id="ga-bounce-trend"><span class="gsc-trend-arrow"></span><span class="gsc-trend-value"></span><span class="gsc-trend-label">vs prev</span></div></div>
                        <div class="gsc-metric-card"><div class="gsc-metric-label">Avg Session Duration</div><div class="gsc-metric-value" id="ga-avgduration">--</div><div class="gsc-metric-trend" id="ga-avgduration-trend"><span class="gsc-trend-arrow"></span><span class="gsc-trend-value"></span><span class="gsc-trend-label">vs prev</span></div></div>
                        <div class="gsc-metric-card"><div class="gsc-metric-label">Avg Engagement Time</div><div class="gsc-metric-value" id="ga-engagementtime">--</div><div class="gsc-metric-trend" id="ga-engagementtime-trend"><span class="gsc-trend-arrow"></span><span class="gsc-trend-value"></span><span class="gsc-trend-label">vs prev</span></div></div>
                        <div class="gsc-metric-card"><div class="gsc-metric-label">Events</div><div class="gsc-metric-value" id="ga-events">--</div><div class="gsc-metric-trend" id="ga-events-trend"><span class="gsc-trend-arrow"></span><span class="gsc-trend-value"></span><span class="gsc-trend-label">vs prev</span></div></div>
                        <div class="gsc-metric-card"><div class="gsc-metric-label">Conversions</div><div class="gsc-metric-value" id="ga-conversions">--</div><div class="gsc-metric-trend"><span class="gsc-trend-label">key events</span></div></div>
                        <div class="gsc-metric-card" id="ga-revenue-card" style="display:none;"><div class="gsc-metric-label">Revenue</div><div class="gsc-metric-value" id="ga-revenue">--</div><div class="gsc-metric-trend"><span class="gsc-trend-label">this page</span></div></div>
                    </div>

                    <!-- Trend chart -->
                    <div class="ga-section">
                        <div class="ga-section-header"><h3 class="gsc-queries-title">Views &amp; Sessions Over Time</h3></div>
                        <div id="ga-trend-chart" class="ga-trend-chart"></div>
                    </div>

                    <!-- Breakdowns -->
                    <div class="ga-breakdowns">
                        <div class="ga-section">
                            <div class="ga-section-header"><h3 class="gsc-queries-title">Traffic Sources</h3><span class="gsc-queries-subtitle">How visitors reached this page</span></div>
                            <div class="gsc-queries-table-wrapper">
                                <table class="gsc-queries-table">
                                    <thead><tr><th class="gsc-col-query">Channel</th><th class="gsc-col-clicks">Sessions</th><th class="gsc-col-impressions">Users</th></tr></thead>
                                    <tbody id="ga-sources-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="ga-section">
                            <div class="ga-section-header"><h3 class="gsc-queries-title">Devices</h3></div>
                            <div class="gsc-queries-table-wrapper">
                                <table class="gsc-queries-table">
                                    <thead><tr><th class="gsc-col-query">Device</th><th class="gsc-col-clicks">Sessions</th><th class="gsc-col-impressions">Share</th></tr></thead>
                                    <tbody id="ga-devices-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="ga-section">
                            <div class="ga-section-header"><h3 class="gsc-queries-title">Top Countries</h3></div>
                            <div class="gsc-queries-table-wrapper">
                                <table class="gsc-queries-table">
                                    <thead><tr><th class="gsc-col-query">Country</th><th class="gsc-col-clicks">Sessions</th><th class="gsc-col-impressions">Users</th></tr></thead>
                                    <tbody id="ga-countries-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="gsc-last-updated" id="ga-last-updated"></div>
                </div>

                <!-- ═══ State 7: API Error ═══ -->
                <div class="almaseo-ga-state" id="ga-state-error" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#d63638" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">Couldn't load analytics</h3>
                        <p class="gsc-state-description" id="ga-error-message">An error occurred while fetching Analytics data. Please try again.</p>
                        <button type="button" class="gsc-state-btn gsc-btn-primary" id="ga-retry-btn">Retry</button>
                    </div>
                </div>

                <!-- ═══ State 8: Token Expired ═══ -->
                <div class="almaseo-ga-state" id="ga-state-token-expired" style="display: none;">
                    <div class="gsc-state-card">
                        <div class="gsc-state-icon">
                            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#dba617" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/>
                            </svg>
                        </div>
                        <h3 class="gsc-state-heading">Analytics access needs renewal</h3>
                        <p class="gsc-state-description">
                            Your Google authorization has expired or been revoked. Please re-connect Google Analytics in your AlmaSEO Dashboard.
                        </p>
                        <a href="https://app.almaseo.com/profile/google-services" target="_blank" class="gsc-state-btn gsc-btn-primary">
                            Re-connect in Dashboard
                            <span class="gsc-btn-arrow">&rarr;</span>
                        </a>
                    </div>
                </div>

            </div>
            <!-- End almaseo-ga-container -->

            <style>
                .ga-metrics-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                    gap: 12px;
                    padding: 16px 20px;
                }
                .ga-section { padding: 8px 20px 16px; }
                .ga-section-header { display: flex; align-items: baseline; gap: 10px; margin-bottom: 8px; flex-wrap: wrap; }
                .ga-breakdowns { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 0; }
                .ga-trend-chart { width: 100%; }
                .ga-trend-chart svg { width: 100%; height: 80px; display: block; }
                .ga-bar-track { display: inline-block; width: 60px; height: 8px; background: #eef2f7; border-radius: 4px; overflow: hidden; vertical-align: middle; margin-right: 6px; }
                .ga-bar-fill { display: block; height: 100%; background: #2271b1; }
                @media (max-width: 782px) {
                    .ga-breakdowns { grid-template-columns: 1fr; }
                    .ga-metrics-grid { grid-template-columns: repeat(2, 1fr); }
                }
            </style>

            <script>
            jQuery(document).ready(function($) {
                var $container = $('#almaseo-ga-container');
                if (!$container.length) return;

                var postId = $container.data('post-id');
                var pageUrl = $container.data('page-url');
                var nonce = $container.data('nonce');
                var isConnected = $container.data('connected') === 1 || $container.data('connected') === '1';
                var gaConfirmed = false;

                function showState(stateId) {
                    $container.find('.almaseo-ga-state').hide();
                    $('#' + stateId).show();
                }

                if (!isConnected) {
                    showState('ga-state-not-connected');
                    return;
                }

                function formatNumber(n) {
                    return Math.round(n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }
                function formatPct(ratio) {
                    return ((ratio || 0) * 100).toFixed(1) + '%';
                }
                function formatDuration(sec) {
                    sec = Math.round(sec || 0);
                    if (sec < 60) return sec + 's';
                    var m = Math.floor(sec / 60), s = sec % 60;
                    return m + 'm ' + s + 's';
                }

                // Update a trend indicator. invert=true means lower is better (e.g. bounce rate).
                function updateTrend(elId, current, previous, invert) {
                    var $el = $('#' + elId);
                    if (previous === null || previous === undefined) {
                        $el.find('.gsc-trend-value').text('--');
                        $el.find('.gsc-trend-arrow').text('');
                        return;
                    }
                    var diff = current - previous;
                    var pct = previous !== 0 ? Math.abs((diff / previous) * 100).toFixed(1) : '0.0';
                    var arrow, cls, better;
                    if (Math.abs(diff) < 0.0001) {
                        arrow = '→'; cls = 'gsc-trend-neutral';
                    } else {
                        better = invert ? (diff < 0) : (diff > 0);
                        arrow = diff > 0 ? '↑' : '↓';
                        cls = better ? 'gsc-trend-up' : 'gsc-trend-down';
                    }
                    $el.removeClass('gsc-trend-up gsc-trend-down gsc-trend-neutral').addClass(cls);
                    $el.find('.gsc-trend-arrow').text(arrow);
                    $el.find('.gsc-trend-value').text(pct + '%');
                }

                function renderTrendChart(trend) {
                    var $chart = $('#ga-trend-chart').empty();
                    if (!trend || !trend.length) { $chart.html('<p style="color:#94a3b8;font-size:12px;text-align:center;padding:20px;">No daily data.</p>'); return; }
                    var W = 600, H = 80, pad = 4;
                    var max = Math.max.apply(null, trend.map(function(d){ return d.views; })) || 1;
                    var n = trend.length;
                    var stepX = n > 1 ? (W - pad * 2) / (n - 1) : 0;
                    function y(v){ return H - pad - (v / max) * (H - pad * 2); }
                    var pts = trend.map(function(d, i){ return (pad + i * stepX).toFixed(1) + ',' + y(d.views).toFixed(1); });
                    var area = 'M' + (pad) + ',' + (H - pad) + ' L' + pts.join(' L') + ' L' + (pad + (n - 1) * stepX).toFixed(1) + ',' + (H - pad) + ' Z';
                    var line = 'M' + pts.join(' L');
                    var svg = '<svg viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">' +
                        '<path d="' + area + '" fill="rgba(34,113,177,0.12)" />' +
                        '<path d="' + line + '" fill="none" stroke="#2271b1" stroke-width="2" vector-effect="non-scaling-stroke" />' +
                        '</svg>';
                    $chart.html(svg);
                }

                function renderTable(tbodyId, rows, cols, withBar) {
                    var $tbody = $('#' + tbodyId).empty();
                    if (!rows || !rows.length) {
                        $tbody.append('<tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:16px;">No data</td></tr>');
                        return;
                    }
                    var total = 0;
                    if (withBar) { rows.forEach(function(r){ total += (r[cols[1]] || 0); }); }
                    rows.forEach(function(r) {
                        var c2;
                        if (withBar) {
                            var share = total ? Math.round((r[cols[1]] / total) * 100) : 0;
                            c2 = '<span class="ga-bar-track"><span class="ga-bar-fill" style="width:' + share + '%"></span></span>' + share + '%';
                        } else {
                            c2 = formatNumber(r[cols[2]]);
                        }
                        $tbody.append(
                            '<tr>' +
                            '<td class="gsc-col-query">' + $('<span>').text(r.label || '(not set)').html() + '</td>' +
                            '<td class="gsc-col-clicks">' + formatNumber(r[cols[1]]) + '</td>' +
                            '<td class="gsc-col-impressions">' + c2 + '</td>' +
                            '</tr>'
                        );
                    });
                }

                function renderData(data) {
                    var m = data.metrics, p = data.previous;
                    gaConfirmed = true;

                    if (data.property && data.property.name) {
                        $('#ga-property-name').text(data.property.name);
                    }

                    $('#ga-views').text(formatNumber(m.screenPageViews));
                    $('#ga-sessions').text(formatNumber(m.sessions));
                    $('#ga-users').text(formatNumber(m.activeUsers));
                    $('#ga-newusers').text(formatNumber(m.newUsers));
                    $('#ga-engaged').text(formatNumber(m.engagedSessions));
                    $('#ga-engagementrate').text(formatPct(m.engagementRate));
                    $('#ga-bounce').text(formatPct(m.bounceRate));
                    $('#ga-avgduration').text(formatDuration(m.averageSessionDuration));
                    $('#ga-events').text(formatNumber(m.eventCount));

                    var engTime = m.activeUsers ? (m.userEngagementDuration / m.activeUsers) : 0;
                    $('#ga-engagementtime').text(formatDuration(engTime));

                    if (data.conversions !== null && data.conversions !== undefined) {
                        $('#ga-conversions').text(formatNumber(data.conversions));
                    } else {
                        $('#ga-conversions').text('--');
                    }
                    if (data.total_revenue && data.total_revenue > 0) {
                        $('#ga-revenue-card').show();
                        $('#ga-revenue').text('$' + Number(data.total_revenue).toFixed(2));
                    } else {
                        $('#ga-revenue-card').hide();
                    }

                    if (p) {
                        updateTrend('ga-views-trend', m.screenPageViews, p.screenPageViews);
                        updateTrend('ga-sessions-trend', m.sessions, p.sessions);
                        updateTrend('ga-users-trend', m.activeUsers, p.activeUsers);
                        updateTrend('ga-newusers-trend', m.newUsers, p.newUsers);
                        updateTrend('ga-engaged-trend', m.engagedSessions, p.engagedSessions);
                        updateTrend('ga-engagementrate-trend', m.engagementRate, p.engagementRate);
                        updateTrend('ga-bounce-trend', m.bounceRate, p.bounceRate, true);
                        updateTrend('ga-avgduration-trend', m.averageSessionDuration, p.averageSessionDuration);
                        updateTrend('ga-events-trend', m.eventCount, p.eventCount);
                        var prevEng = p.activeUsers ? (p.userEngagementDuration / p.activeUsers) : 0;
                        updateTrend('ga-engagementtime-trend', engTime, prevEng);
                    }

                    renderTrendChart(data.trend);
                    renderTable('ga-sources-tbody', data.sources, ['label', 'sessions', 'activeUsers'], false);
                    renderTable('ga-devices-tbody', data.devices, ['label', 'sessions', 'sessions'], true);
                    renderTable('ga-countries-tbody', data.countries, ['label', 'sessions', 'activeUsers'], false);

                    $('#ga-last-updated').text('Data through: ' + (data.last_updated || 'today'));
                    showState('ga-state-data');
                }

                function handleError(data) {
                    var msg = (data && data.message) ? data.message : 'An unexpected error occurred.';
                    $('#ga-error-message').text(msg);
                    showState('ga-state-error');
                }

                function fetchGAData() {
                    var days = $('#ga-date-range').val() || 28;
                    showState('ga-state-loading');
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'almaseo_fetch_ga_page_data',
                            nonce: nonce,
                            post_id: postId,
                            page_url: pageUrl,
                            days: days
                        },
                        timeout: 30000,
                        success: function(response) {
                            if (!response.success) { handleError(response.data); return; }
                            var d = response.data;
                            if (d.status === 'ga_not_connected') {
                                showState('ga-state-no-ga');
                            } else if (d.status === 'no_property') {
                                showState('ga-state-no-property');
                            } else if (d.status === 'token_expired') {
                                showState('ga-state-token-expired');
                            } else if (d.status === 'no_data') {
                                showState('ga-state-no-data');
                            } else if (d.status === 'ok') {
                                renderData(d);
                            } else {
                                handleError({ message: 'Unexpected response from server.' });
                            }
                        },
                        error: function(xhr, status) {
                            var msg = status === 'timeout'
                                ? 'The request timed out. Please try again.'
                                : 'Could not reach the server. Check your connection.';
                            handleError({ message: msg });
                        }
                    });
                }

                $('#ga-date-range').on('change', function() { if (gaConfirmed) fetchGAData(); });
                $('#ga-refresh-btn').on('click', function() {
                    if (!gaConfirmed) return;
                    $(this).addClass('spinning');
                    fetchGAData();
                    setTimeout(function() { $('#ga-refresh-btn').removeClass('spinning'); }, 1000);
                });
                $('#ga-retry-btn').on('click', fetchGAData);
                $(document).on('click', '#ga-connect-btn', function(e) { e.preventDefault(); fetchGAData(); });

                $(document).on('click', '.almaseo-tab-btn[data-tab="analytics"]', function() {
                    if (gaConfirmed) fetchGAData();
                });
            });
            </script>
        </div>
        <!-- End Analytics Tab -->

        <!-- LLM Optimization Tab -->
        <div class="almaseo-tab-panel" id="tab-llm-optimization">
            <?php
            // Render LLM Optimization panel
            almaseo_render_llm_optimization_panel($post);
            ?>
        </div>
        <!-- End LLM Optimization Tab -->

        <!-- Notes & History Tab -->
        <div class="almaseo-tab-panel" id="tab-notes-history">
            <!-- Search & Filter Bar -->
            <div class="notes-toolbar">
                <div class="notes-search-container">
                    <input type="text" 
                           id="notes-search-input" 
                           class="notes-search-input" 
                           placeholder="Search notes..."
                           aria-label="Search notes">
                    <span class="notes-search-icon" aria-hidden="true">🔍</span>
                </div>
                <div class="notes-sort-container">
                    <select id="notes-sort-select" class="notes-sort-select" aria-label="Sort notes">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="author">By Author</option>
                    </select>
                </div>
            </div>
            
            <!-- Message Container -->
            <div id="notes-message-container"></div>
            
            <!-- SEO Notes Panel -->
            <div class="seo-notes-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="notes-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="notes-heading">
                            <span class="panel-icon" aria-hidden="true">📝</span>
                            <span>SEO Notes</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Private notes for SEO strategies and reminders">ⓘ</span>
                        </div>
                    </div>
                    
                    <!-- Note Input Section -->
                    <div class="note-input-section">
                        <textarea id="seo-note-textarea" 
                                  class="note-textarea" 
                                  placeholder="Add your SEO notes, strategies, competitor analysis, or keyword rankings..."
                                  aria-label="SEO note content"
                                  rows="5"
                                  maxlength="1000"></textarea>
                        
                        <div class="note-char-counter">
                            <span class="note-char-count">
                                <span id="note-char-count">0</span>/1000 characters
                            </span>
                            <span class="note-hint">Press Ctrl+Enter to save</span>
                        </div>
                        
                        <div class="note-controls">
                            <button type="button" class="add-note-btn" id="add-note-btn" aria-label="Add note">
                                <span aria-hidden="true">➕</span> Add Note
                            </button>
                            <button type="button" class="clear-note-btn" id="clear-note-btn" aria-label="Clear note">
                                <span aria-hidden="true">🗑️</span> Clear
                            </button>
                        </div>
                    </div>
                    
                    <!-- Notes List -->
                    <div class="notes-list-container" id="notes-list-container" role="list" aria-label="SEO notes list">
                        <!-- Notes will be populated here by JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Post History Tracker Panel -->
            <?php if ($is_connected): ?>
            <div class="history-tracker-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="history-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="history-heading">
                            <span class="panel-icon" aria-hidden="true">📜</span>
                            <span>Post History Tracker</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Track all edits and changes to this post">ⓘ</span>
                        </div>
                    </div>
                    
                    <!-- History Filters -->
                    <div class="history-filters">
                        <input type="date" 
                               id="history-date-filter" 
                               class="history-filter-input" 
                               aria-label="Filter by date">
                        
                        <select id="history-user-filter" class="history-filter-select" aria-label="Filter by user">
                            <option value="all">All Users</option>
                            <?php
                            // Get users who can edit posts
                            $users = get_users(array(
                                'capability' => 'edit_posts'
                            ));
                            foreach ($users as $user) {
                                echo '<option value="' . esc_attr($user->display_name) . '">' . esc_html($user->display_name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- History Table -->
                    <div class="history-table-container">
                        <table class="history-table" role="table" aria-label="Post edit history">
                            <thead>
                                <tr>
                                    <th scope="col">Date/Time</th>
                                    <th scope="col">Editor</th>
                                    <th scope="col">Summary</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="history-table-tbody">
                                <!-- History entries will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Metadata History Card -->
            <?php
            // Render metadata history card if available
            if (function_exists('almaseo_history_render_card')) {
                almaseo_history_render_card($post);
            }
            ?>
            
            <!-- Hidden fields for data -->
            <input type="hidden" id="almaseo-current-user" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
            <input type="hidden" id="almaseo_nonce" value="<?php echo esc_attr(wp_create_nonce('almaseo_nonce')); ?>">
            <input type="hidden" id="almaseo-can-edit" value="<?php echo esc_attr(current_user_can('edit_posts') ? 'true' : 'false'); ?>">
        </div>
        <!-- End Notes & History Tab -->
        
        <?php if (!$is_connected && function_exists('almaseo_is_free_tier') && almaseo_is_free_tier()): ?>
        <!-- Unlock Full Features Tab -->
        <div class="almaseo-tab-panel" id="tab-unlock-features">
            <div class="almaseo-unlock-container">
                
                <!-- Hero Header Section -->
                <div class="unlock-hero-section">
                    <div class="hero-content">
                        <div class="hero-text">
                            <h1 class="hero-title">
                                <span class="gradient-text">Supercharge Your Website with AlmaSEO Dashboard + Plugin</span>
                            </h1>
                            <p class="hero-description">
                                AlmaSEO isn't just a smart toolkit — it's your SEO command center. Write high-authority blog posts, landing pages, and articles with a click, and publish them instantly to WordPress. Then manage, optimize, and track everything from your AlmaSEO dashboard.
                            </p>
                            <div class="hero-cta-group">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=seo-playground-connection')); ?>" class="hero-cta-primary">
                                    <span class="cta-icon">⚡</span>
                                    Connect My Site
                                    <span class="cta-arrow">→</span>
                                </a>
                                <a href="https://almaseo.com/features?utm_source=plugin&utm_medium=unlock_tab&utm_campaign=upsell" target="_blank" class="hero-cta-secondary">
                                    <span class="cta-icon">💡</span>
                                    Learn More
                                </a>
                            </div>
                        </div>
                        <div class="hero-illustration">
                            <div class="illustration-wrapper">
                                <div class="floating-card card-1">
                                    <span class="card-icon">🚀</span>
                                    <span class="card-text">10x Faster</span>
                                </div>
                                <div class="floating-card card-2">
                                    <span class="card-icon">📈</span>
                                    <span class="card-text">+250% CTR</span>
                                </div>
                                <div class="floating-card card-3">
                                    <span class="card-icon">⭐</span>
                                    <span class="card-text">Page #1</span>
                                </div>
                                <div class="central-graphic">
                                    <div class="orbit-ring"></div>
                                    <div class="orbit-ring ring-2"></div>
                                    <div class="orbit-ring ring-3"></div>
                                    <div class="center-logo">🧠</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 2x3 Locked Features Grid -->
                <div class="unlock-features-section">
                    <h2 class="section-title">Premium AlmaSEO Features Waiting for You</h2>
                    
                    <!-- Dashboard Superpowers Row -->
                    <div class="row-label">Dashboard Superpowers</div>
                    <div class="locked-features-grid">
                        
                        <!-- Card 1: Automated Article Writer -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">✍️</span>
                            </div>
                            <h3 class="feature-title">Automated Article Writer</h3>
                            <p class="feature-benefit">Publish SEO-driven blog posts and landing pages instantly — written for authority, optimized for ranking, delivered straight to WordPress.</p>
                            <div class="feature-stats">
                                <span class="stat-item">High authority</span>
                                <span class="stat-item">One-click publish</span>
                            </div>
                        </div>
                        
                        <!-- Card 2: SEO Profile & Brand Voice -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">🎯</span>
                            </div>
                            <h3 class="feature-title">SEO Profile & Brand Voice</h3>
                            <p class="feature-benefit">Tell AlmaSEO your tone, keywords, competitors, and brand details once — it will apply them consistently across all content.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Consistent</span>
                                <span class="stat-item">On-brand</span>
                            </div>
                        </div>
                        
                        <!-- Card 3: Multi-Site Dashboard -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">🌐</span>
                            </div>
                            <h3 class="feature-title">Multi-Site Dashboard</h3>
                            <p class="feature-benefit">Control multiple WordPress sites from one dashboard. Centralized publishing, tracking, and reporting at scale.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Multi-site</span>
                                <span class="stat-item">Scalable</span>
                            </div>
                        </div>
                        
                        <!-- Card 4: Evergreen Tracking & Reporting -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">📊</span>
                            </div>
                            <h3 class="feature-title">Evergreen Tracking & Reporting</h3>
                            <p class="feature-benefit">Track content freshness, SEO health, and rankings across your sites. AlmaSEO keeps your strategy up to date automatically.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Fresh</span>
                                <span class="stat-item">Data-driven</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Plugin Enhancements Row -->
                    <div class="row-label">Plugin Enhancements</div>
                    <div class="locked-features-grid locked-features-grid-plugin">
                        
                        <!-- Card A: Smart Title Generator -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">💡</span>
                            </div>
                            <h3 class="feature-title">Smart Title Generator</h3>
                            <p class="feature-benefit">Create click-worthy titles that attract readers and boost SEO performance.</p>
                            <div class="feature-stats">
                                <span class="stat-item">High CTR</span>
                                <span class="stat-item">Keyword rich</span>
                            </div>
                        </div>
                        
                        <!-- Card B: Meta Description AI -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">📝</span>
                            </div>
                            <h3 class="feature-title">Meta Description Generator</h3>
                            <p class="feature-benefit">Generate compelling descriptions that increase clicks and improve search visibility.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Perfect length</span>
                                <span class="stat-item">Engaging</span>
                            </div>
                        </div>
                        
                        <!-- Card C: Schema Generator -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">🔗</span>
                            </div>
                            <h3 class="feature-title">Schema Generator</h3>
                            <p class="feature-benefit">Add structured data markup automatically to stand out in search results.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Rich snippets</span>
                                <span class="stat-item">Stand out</span>
                            </div>
                        </div>
                        
                        <!-- Card D: Keyword Research Pro -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">🎯</span>
                            </div>
                            <h3 class="feature-title">Keyword Research Pro</h3>
                            <p class="feature-benefit">Discover untapped keywords with high potential and low competition.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Deep analysis</span>
                                <span class="stat-item">Trend data</span>
                            </div>
                        </div>
                        
                        <!-- Card E: Content Intelligence -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">🧠</span>
                            </div>
                            <h3 class="feature-title">Content Intelligence</h3>
                            <p class="feature-benefit">Get real-time SEO analysis with actionable recommendations as you optimize content.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Smart tips</span>
                                <span class="stat-item">Live scoring</span>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Two Halves of the Same Engine Explainer -->
                <div class="unlock-explainer-section">
                    <h3 class="explainer-title">Two Halves of the Same Engine</h3>
                    <p class="explainer-text">
                        The AlmaSEO dashboard is your SEO mission control. The WordPress plugin is your on-site assistant. Together, they automate content creation, optimization, and publishing — so you can focus on strategy instead of manual work.
                    </p>
                </div>
                
                <!-- What You Get Section -->
                <div class="unlock-benefits-section">
                    <h2 class="section-title">What You Get with AlmaSEO</h2>
                    <div class="benefits-grid">
                        <div class="benefit-item">
                            <div class="benefit-icon">🚀</div>
                            <div class="benefit-content">
                                <h3>Instant Alma Access</h3>
                                <p>Connect in 60 seconds and start optimizing immediately. No complex setup, no technical knowledge required.</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">♾️</div>
                            <div class="benefit-content">
                                <h3>Unlimited Generations</h3>
                                <p>Generate as many titles, descriptions, and rewrites as you need. No usage limits during your trial period.</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">🎯</div>
                            <div class="benefit-content">
                                <h3>Proven Results</h3>
                                <p>Join 10,000+ websites seeing average ranking improvements of 3-5 positions within 30 days.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Testimonial Section -->
                <div class="unlock-testimonial-section">
                    <div class="testimonial-card">
                        <div class="testimonial-quote">
                            <span class="quote-mark">"</span>
                            <p class="testimonial-text">
                                AlmaSEO transformed our content strategy completely. We went from page 5 to the first page 
                                for our main keywords in just 6 weeks. The Alma tools save us hours every day and the results
                                speak for themselves - our organic traffic is up 312%!
                            </p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <span>JD</span>
                            </div>
                            <div class="author-info">
                                <div class="author-name">Jennifer Davis</div>
                                <div class="author-title">Marketing Director, TechStartup Inc.</div>
                                <div class="author-rating">
                                    ⭐⭐⭐⭐⭐
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="trust-indicators">
                        <div class="trust-item">
                            <span class="trust-number">10,000+</span>
                            <span class="trust-label">Active Users</span>
                        </div>
                        <div class="trust-item">
                            <span class="trust-number">4.9/5</span>
                            <span class="trust-label">Average Rating</span>
                        </div>
                        <div class="trust-item">
                            <span class="trust-number">24/7</span>
                            <span class="trust-label">Support</span>
                        </div>
                    </div>
                </div>
                
                <!-- Footer Section -->
                <div class="unlock-footer-section">
                    <div class="footer-card">
                        <div class="footer-icon">🔑</div>
                        <div class="footer-content">
                            <h3>Already have an AlmaSEO account?</h3>
                            <p>If you've already signed up for AlmaSEO, simply connect your site to start using all premium features immediately.</p>
                            <div class="footer-cta-group">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=seo-playground-connection')); ?>" class="footer-cta-primary">
                                    Connect Existing Account
                                </a>
                                <a href="https://almaseo.com/login?utm_source=plugin&utm_medium=unlock_footer" target="_blank" class="footer-cta-secondary">
                                    Login to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="footer-links">
                        <a href="https://almaseo.com/pricing?utm_source=plugin" target="_blank">View Pricing</a>
                        <span class="separator">•</span>
                        <a href="https://almaseo.com/docs?utm_source=plugin" target="_blank">Documentation</a>
                        <span class="separator">•</span>
                        <a href="https://almaseo.com/support?utm_source=plugin" target="_blank">Get Support</a>
                    </div>
                </div>
                
            </div>
        </div>
        <!-- End Unlock AI Features Tab -->
        <?php endif; ?>
        
        </div>
        <!-- End Tab Content -->
        
        <!-- Version Footer -->
        <div class="almaseo-version-footer" style="text-align: right; padding: 10px 20px; color: #999; font-size: 12px; border-top: 1px solid #e0e0e0; margin-top: 20px;">
            AlmaSEO SEO Playground v<?php echo esc_html(ALMASEO_PLUGIN_VERSION); ?>
        </div>
    </div>
    <!-- End almaseo-seo-playground -->
    
    <script>
    jQuery(document).ready(function($) {
        // Tab Navigation
        $('.almaseo-tab-btn').on('click', function() {
            var targetTab = $(this).data('tab');
            
            // Update active button
            $('.almaseo-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Update active panel with fade animation
            $('.almaseo-tab-panel').removeClass('active').fadeOut(200, function() {
                $('#tab-' + targetTab).fadeIn(200).addClass('active');
            });
            
            // Save tab preference
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('almaseo_active_tab', targetTab);
            }
        });
        
        // Restore last active tab
        if (typeof(Storage) !== "undefined") {
            var lastTab = localStorage.getItem('almaseo_active_tab');
            if (lastTab && $('.almaseo-tab-btn[data-tab="' + lastTab + '"]').length) {
                $('.almaseo-tab-btn[data-tab="' + lastTab + '"]').trigger('click');
            }
        }
        
        // Character count for SEO title
        $('#almaseo_seo_title').on('input', function() {
            var count = $(this).val().length;
            var countSpan = $('#title-count');
            countSpan.text(count);
            
            countSpan.removeClass('warning danger');
            if (count > 50) countSpan.addClass('warning');
            if (count > 58) countSpan.addClass('danger');
        });
        
        // Character count for meta description
        $('#almaseo_seo_description').on('input', function() {
            var count = $(this).val().length;
            var countSpan = $('#description-count');
            countSpan.text(count);

            countSpan.removeClass('warning danger');
            if (count > 140) countSpan.addClass('warning');
            if (count > 155) countSpan.addClass('danger');
        });

        // ========== LLM Optimization Panel ==========

        /**
         * Fetch and render LLM analysis
         */
        function loadLLMAnalysis() {
            var $panel = $('.almaseo-llm-panel');
            var postId = $panel.data('post-id');
            var selectedStyle = $('#almaseo-llm-summary-style').val() || 'concise';

            if (!postId) return;

            // Set loading state
            $('.almaseo-llm-status').text('Loading analysis...').show();
            $('.almaseo-llm-summary-text, .almaseo-llm-entities-list, .almaseo-llm-ambiguities-list, .almaseo-llm-scores, .almaseo-llm-insights, .almaseo-llm-structure-content').addClass('almaseo-llm-loading');
            $('[data-section="top-issues"]').hide();

            // Make AJAX request to REST endpoint
            $.ajax({
                url: '/wp-json/almaseo/v1/llm-analysis',
                method: 'GET',
                data: {
                    post_id: postId,
                    style: selectedStyle,
                    include_sections: 1
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>');
                },
                success: function(response) {
                    renderLLMAnalysis(response);
                    $('.almaseo-llm-status').text('Analysis complete').fadeOut(3000);
                },
                error: function(xhr, status, error) {
                    $('.almaseo-llm-status').text('Error loading analysis. Please try again.').css('color', '#dc3232');
                    $('.almaseo-llm-summary-text').removeClass('almaseo-llm-loading').html('<p style="color: #666;">Could not load LLM analysis. Please refresh and try again.</p>');
                    $('.almaseo-llm-entities-list, .almaseo-llm-ambiguities-list, .almaseo-llm-scores, .almaseo-llm-insights').removeClass('almaseo-llm-loading');
                }
            });
        }

        /**
         * Render LLM analysis results
         */
        function renderLLMAnalysis(data) {
            // Connection status banner (if not connected)
            if (!data.connected && !$('.almaseo-llm-connection-notice').length) {
                // Already shown in PHP, don't duplicate
            }

            // Summary with context
            var $summary = $('.almaseo-llm-summary-text');
            $summary.removeClass('almaseo-llm-loading');
            if (data.summary) {
                var summaryHtml = '<p class="almaseo-llm-summary-context">This is roughly how LLM systems may interpret and summarize your page:</p>';
                summaryHtml += '<div class="almaseo-llm-summary-excerpt">' + escapeHtml(data.summary) + '</div>';

                // Conditional warning if signals suggest poor extractability
                var hasWeakSignals = (data.llm_score && data.llm_score < 50) ||
                    (data.heading_hierarchy && !data.heading_hierarchy.hierarchy_valid) ||
                    (data.paragraph_analysis && data.paragraph_analysis.wall_of_text_detected) ||
                    (data.heading_hierarchy && data.heading_hierarchy.total_headings === 0);
                if (hasWeakSignals) {
                    summaryHtml += '<p class="almaseo-llm-summary-warning"><span class="dashicons dashicons-warning"></span> This summary may be incomplete due to missing structure, weak context, or limited detail.</p>';
                }

                $summary.html(summaryHtml);
            } else {
                $summary.html('<p style="color: #999;">No summary available.</p>');
            }

            // Entities (with confidence bars if available)
            var $entities = $('.almaseo-llm-entities-list');
            $entities.removeClass('almaseo-llm-loading');
            if (data.entities && data.entities.length > 0) {
                var entitiesHtml = '';
                data.entities.forEach(function(entity) {
                    entitiesHtml += '<li class="almaseo-llm-entity">';
                    entitiesHtml += '<div class="almaseo-llm-entity-header">';
                    entitiesHtml += '<strong>' + escapeHtml(entity.name) + '</strong>';
                    entitiesHtml += '<span class="almaseo-llm-entity-type">' + escapeHtml(entity.type) + '</span>';
                    entitiesHtml += '</div>';

                    // Show confidence bar if available
                    if (entity.confidence !== undefined) {
                        var confidence = entity.confidence;
                        var confidenceColor = getScoreColor(confidence);
                        entitiesHtml += '<div class="almaseo-llm-entity-confidence">';
                        entitiesHtml += '<div class="almaseo-llm-entity-confidence-label">Confidence: ' + confidence + '%</div>';
                        entitiesHtml += '<div class="almaseo-llm-entity-confidence-bar">';
                        entitiesHtml += '<div class="almaseo-llm-entity-confidence-fill" style="width: ' + confidence + '%; background: ' + confidenceColor + '"></div>';
                        entitiesHtml += '</div></div>';
                    }

                    entitiesHtml += '</li>';
                });
                $entities.html(entitiesHtml);
                entitiesHtml += '<li class="almaseo-llm-entity-disclaimer">Entities are detected using basic pattern matching and may include non-specific terms.</li>';
                $entities.html(entitiesHtml);
            } else {
                $entities.html('<li style="color: #999;">No entities detected.</li>');
            }

            // Ambiguities
            var $ambiguities = $('.almaseo-llm-ambiguities-list');
            $ambiguities.removeClass('almaseo-llm-loading');
            var clarityItems = [];

            if (data.ambiguities && data.ambiguities.length > 0) {
                data.ambiguities.forEach(function(item) {
                    clarityItems.push('<li class="almaseo-llm-clarity-item">' +
                        '<span class="almaseo-llm-clarity-excerpt">' + escapeHtml(item.excerpt) + '</span>' +
                        '<span class="almaseo-llm-clarity-note">' + escapeHtml(item.note) + '</span>' +
                        '</li>');
                });
            }

            if (data.consistency_issues && data.consistency_issues.length > 0) {
                data.consistency_issues.forEach(function(item) {
                    clarityItems.push('<li class="almaseo-llm-clarity-item">' +
                        '<span class="almaseo-llm-clarity-excerpt">' + escapeHtml(item.excerpt) + '</span>' +
                        '<span class="almaseo-llm-clarity-note">' + escapeHtml(item.note) + '</span>' +
                        '</li>');
                });
            }

            if (clarityItems.length > 0) {
                $ambiguities.html(clarityItems.join(''));
            } else {
                $ambiguities.html('<li style="color: #10b981;"><span class="dashicons dashicons-yes-alt"></span> No clarity issues detected</li>');
            }

            // Scores (AI Readiness + Answer Strength)
            var $scores = $('.almaseo-llm-scores');
            if ($scores.length) {
                $scores.removeClass('almaseo-llm-loading');

                var llmScore = data.llm_score || 0;
                var answerabilityScore = data.answerability_score || 0;

                var llmColor = getScoreColor(llmScore);
                var answerColor = getScoreColor(answerabilityScore);

                var scoresHtml = '<div class="almaseo-llm-score-row">';

                // AI Readiness score
                scoresHtml += '<div class="almaseo-llm-score-item">' +
                    '<div class="almaseo-llm-score-label">LLM Readiness</div>' +
                    '<div class="almaseo-llm-score-sublabel">Structure & extractability</div>' +
                    '<div class="almaseo-llm-score-value" style="color: ' + llmColor + '">' + llmScore + '</div>' +
                    '<div class="almaseo-llm-score-bar"><div class="almaseo-llm-score-fill" style="width: ' + llmScore + '%; background: ' + llmColor + '"></div></div>' +
                    '<ul class="almaseo-llm-score-reasons">' + buildScoreReasons(data, 'llm') + '</ul>' +
                    '</div>';

                // Answer Strength score
                scoresHtml += '<div class="almaseo-llm-score-item">' +
                    '<div class="almaseo-llm-score-label">Answer Strength</div>' +
                    '<div class="almaseo-llm-score-sublabel">Direct answer clarity</div>' +
                    '<div class="almaseo-llm-score-value" style="color: ' + answerColor + '">' + answerabilityScore + '</div>' +
                    '<div class="almaseo-llm-score-bar"><div class="almaseo-llm-score-fill" style="width: ' + answerabilityScore + '%; background: ' + answerColor + '"></div></div>' +
                    '<ul class="almaseo-llm-score-reasons">' + buildScoreReasons(data, 'answer') + '</ul>' +
                    '</div>';

                scoresHtml += '</div>';

                // Status message — tiered
                if (llmScore >= 75 && answerabilityScore >= 75) {
                    scoresHtml += '<p class="almaseo-llm-score-status" style="color: #10b981;"><span class="dashicons dashicons-yes-alt"></span> High LLM Visibility — This page is well-structured for LLM-generated answers</p>';
                } else if (llmScore >= 50 || answerabilityScore >= 50) {
                    scoresHtml += '<p class="almaseo-llm-score-status" style="color: #f59e0b;"><span class="dashicons dashicons-warning"></span> Moderate LLM Visibility — This page may appear in LLM answers but could be improved</p>';
                } else {
                    scoresHtml += '<p class="almaseo-llm-score-status" style="color: #dc3232;"><span class="dashicons dashicons-dismiss"></span> Low LLM Visibility — This page is unlikely to be used in LLM-generated answers</p>';
                }

                $scores.html(scoresHtml);
            }

            // Top Issues
            renderTopIssues(data);

            // Pro Features: Insights
            var $insights = $('.almaseo-llm-insights');
            if ($insights.length) {
                $insights.removeClass('almaseo-llm-loading');

                var insightsHtml = '';

                // Schema hint
                if (data.schema_hint) {
                    insightsHtml += '<div class="almaseo-llm-insight-item">' +
                        '<span class="dashicons dashicons-admin-site-alt3"></span>' +
                        '<div>' +
                        '<strong>Schema Suggestion</strong>' +
                        '<p>' + escapeHtml(data.schema_hint) + '</p>' +
                        '</div>' +
                        '</div>';
                }

                // Cluster suggestions
                if (data.cluster_suggestions && data.cluster_suggestions.length > 0) {
                    insightsHtml += '<div class="almaseo-llm-insight-item">' +
                        '<span class="dashicons dashicons-admin-links"></span>' +
                        '<div>' +
                        '<strong>Internal Link Opportunities</strong>' +
                        '<ul class="almaseo-llm-cluster-list">';

                    data.cluster_suggestions.forEach(function(suggestion) {
                        insightsHtml += '<li>' +
                            '<a href="' + suggestion.url + '" target="_blank">' + escapeHtml(suggestion.title) + '</a>' +
                            '</li>';
                    });

                    insightsHtml += '</ul></div></div>';
                }

                if (insightsHtml) {
                    $insights.html(insightsHtml);
                } else {
                    $insights.html('<p style="color: #999;">No additional insights available.</p>');
                }
            }

            // Render content structure analysis
            renderContentStructure(data);

            // Render sections if available
            if (data.sections && data.sections.length > 0) {
                renderLLMSections(data.sections);
            }
        }

        /**
         * Build "Why this score" reasons from existing data
         */
        function buildScoreReasons(data, type) {
            var reasons = [];

            if (type === 'llm') {
                // AI Readiness reasons
                var wc = data.paragraph_analysis ? data.paragraph_analysis.total_paragraphs : 0;
                if ((data.llm_score || 0) < 75) {
                    if (data.heading_hierarchy && !data.heading_hierarchy.hierarchy_valid) {
                        reasons.push('<li>Heading structure has issues</li>');
                    }
                    if (data.heading_hierarchy && data.heading_hierarchy.vague_headings && data.heading_hierarchy.vague_headings.length > 0) {
                        reasons.push('<li>' + data.heading_hierarchy.vague_headings.length + ' vague heading' + (data.heading_hierarchy.vague_headings.length > 1 ? 's' : '') + ' detected</li>');
                    }
                    if (data.paragraph_analysis && data.paragraph_analysis.wall_of_text_detected) {
                        reasons.push('<li>Contains a wall of text (300+ words in one block)</li>');
                    }
                    if (data.formatting && data.formatting.formatting_score < 30) {
                        reasons.push('<li>Few or no lists, tables, or structured elements</li>');
                    }
                    if (data.answer_positioning && data.answer_positioning.starts_with_fluff) {
                        reasons.push('<li>Content opens with filler instead of a direct answer</li>');
                    }
                    if (data.summary_detection && !data.summary_detection.has_tldr && !data.summary_detection.has_key_takeaways) {
                        reasons.push('<li>No summary or key takeaways section</li>');
                    }
                    if (!data.faq_structure || !data.faq_structure.has_faq_structure) {
                        reasons.push('<li>No FAQ-style question headings</li>');
                    }
                }
                if (reasons.length === 0) {
                    reasons.push('<li style="color: #10b981;">All key signals are strong</li>');
                }
            } else {
                // Answer Strength reasons
                if ((data.answerability_score || 0) < 75) {
                    if (data.answer_positioning && !data.answer_positioning.has_direct_answer) {
                        reasons.push('<li>No direct answer pattern in the opening</li>');
                    }
                    if (data.answer_positioning && data.answer_positioning.starts_with_fluff) {
                        reasons.push('<li>Opens with filler text</li>');
                    }
                    if (!data.faq_structure || !data.faq_structure.has_faq_structure) {
                        reasons.push('<li>No FAQ structure detected</li>');
                    }
                    if (data.formatting && !data.formatting.has_lists && !data.formatting.has_tables) {
                        reasons.push('<li>No lists or tables for LLMs to extract</li>');
                    }
                    if (data.summary_detection && !data.summary_detection.has_tldr && !data.summary_detection.has_table_of_contents) {
                        reasons.push('<li>No summary or table of contents</li>');
                    }
                    if (data.heading_hierarchy && data.heading_hierarchy.question_heading_count === 0) {
                        reasons.push('<li>No question-based headings</li>');
                    }
                }
                if (reasons.length === 0) {
                    reasons.push('<li style="color: #10b981;">Content is well-structured for LLM answers</li>');
                }
            }

            return reasons.slice(0, 4).join('');
        }

        /**
         * Render Top Issues card from existing analysis data
         */
        function renderTopIssues(data) {
            var issues = [];

            // Check each signal and build prioritized issue list
            if (data.paragraph_analysis && data.paragraph_analysis.wall_of_text_detected) {
                issues.push({icon: 'dashicons-warning', text: 'Wall of text — a paragraph has ' + data.paragraph_analysis.longest_paragraph_words + '+ words. Break it into shorter blocks.', severity: 'high'});
            }
            if (data.heading_hierarchy && !data.heading_hierarchy.hierarchy_valid && data.heading_hierarchy.hierarchy_issues) {
                issues.push({icon: 'dashicons-warning', text: 'Heading structure broken — ' + data.heading_hierarchy.hierarchy_issues[0], severity: 'high'});
            }
            if (data.answer_positioning && data.answer_positioning.starts_with_fluff) {
                issues.push({icon: 'dashicons-warning', text: 'Content starts with filler ("' + escapeHtml(data.answer_positioning.fluff_detected) + '"). Lead with the main point.', severity: 'high'});
            }
            if (data.heading_hierarchy && data.heading_hierarchy.vague_headings && data.heading_hierarchy.vague_headings.length >= 2) {
                issues.push({icon: 'dashicons-info', text: data.heading_hierarchy.vague_headings.length + ' vague headings (e.g., "' + escapeHtml(data.heading_hierarchy.vague_headings[0]) + '"). Use specific, descriptive headings.', severity: 'medium'});
            }
            if (data.formatting && data.formatting.formatting_score < 20) {
                issues.push({icon: 'dashicons-info', text: 'No lists, tables, or structured formatting. AI extracts structured content more reliably.', severity: 'medium'});
            }
            if (data.summary_detection && !data.summary_detection.has_tldr && !data.summary_detection.has_key_takeaways) {
                issues.push({icon: 'dashicons-info', text: 'No summary or key takeaways section. Adding one helps AI cite your content.', severity: 'medium'});
            }
            if (!data.faq_structure || !data.faq_structure.has_faq_structure) {
                if (data.heading_hierarchy && data.heading_hierarchy.question_heading_count === 0) {
                    issues.push({icon: 'dashicons-info', text: 'No question-based headings. FAQ-style content is highly extractable by AI.', severity: 'low'});
                }
            }

            // Thin content from sections
            if (data.sections) {
                var thinCount = 0;
                data.sections.forEach(function(s) {
                    if (s.flags && s.flags.indexOf('thin_content') !== -1) thinCount++;
                });
                if (thinCount > 0) {
                    issues.push({icon: 'dashicons-info', text: thinCount + ' section' + (thinCount > 1 ? 's' : '') + ' too short to be useful for LLM extraction (under 30 words).', severity: 'medium'});
                }
            }

            var $card = $('[data-section="top-issues"]');
            if (issues.length === 0) {
                $card.hide();
                return;
            }

            // Show top 5
            var html = '';
            issues.slice(0, 5).forEach(function(issue) {
                var color = issue.severity === 'high' ? '#dc3232' : issue.severity === 'medium' ? '#f59e0b' : '#64748b';
                html += '<div class="almaseo-llm-issue-item" style="border-left-color: ' + color + ';">';
                html += '<span class="dashicons ' + issue.icon + '" style="color: ' + color + ';"></span>';
                html += '<span>' + issue.text + '</span>';
                html += '</div>';
            });

            $card.find('.almaseo-llm-top-issues').html(html);
            $card.show();
        }

        /**
         * Render Content Structure analysis card
         */
        function renderContentStructure(data) {
            var $container = $('.almaseo-llm-structure-content');
            $container.removeClass('almaseo-llm-loading');

            if (!data.heading_hierarchy && !data.paragraph_analysis) {
                $container.html('<p style="color: #999;">Content structure analysis not available.</p>');
                return;
            }

            var html = '';

            // --- Heading Hierarchy ---
            if (data.heading_hierarchy) {
                var hh = data.heading_hierarchy;
                html += '<div class="almaseo-llm-structure-section">';
                html += '<h4>Heading Hierarchy</h4>';

                if (hh.hierarchy_valid) {
                    html += '<div class="almaseo-llm-structure-ok"><span class="dashicons dashicons-yes-alt"></span> Heading hierarchy is valid</div>';
                } else if (hh.hierarchy_issues && hh.hierarchy_issues.length > 0) {
                    hh.hierarchy_issues.forEach(function(issue) {
                        html += '<div class="almaseo-llm-structure-warn"><span class="dashicons dashicons-warning"></span> ' + escapeHtml(issue) + '</div>';
                    });
                }

                if (hh.vague_headings && hh.vague_headings.length > 0) {
                    hh.vague_headings.forEach(function(heading) {
                        html += '<div class="almaseo-llm-structure-warn"><span class="dashicons dashicons-warning"></span> Vague heading: "' + escapeHtml(heading) + '"</div>';
                    });
                }

                if (hh.question_heading_count > 0) {
                    html += '<div class="almaseo-llm-structure-ok"><span class="dashicons dashicons-yes-alt"></span> ' + hh.question_heading_count + ' question heading' + (hh.question_heading_count > 1 ? 's' : '') + ' detected (good for FAQ)</div>';
                }

                html += '</div>';
            }

            // --- Paragraph Health ---
            if (data.paragraph_analysis) {
                var pa = data.paragraph_analysis;
                html += '<div class="almaseo-llm-structure-section">';
                html += '<h4>Paragraph Health</h4>';

                html += '<div class="almaseo-llm-structure-stats">';
                html += '<span class="almaseo-llm-structure-stat">' + pa.total_paragraphs + ' paragraphs</span>';
                html += '<span class="almaseo-llm-structure-stat">Avg ' + pa.avg_words_per_paragraph + ' words</span>';
                html += '<span class="almaseo-llm-structure-stat">' + pa.paragraphs_optimal + ' optimal</span>';
                html += '</div>';

                if (pa.wall_of_text_detected) {
                    html += '<div class="almaseo-llm-structure-warn"><span class="dashicons dashicons-warning"></span> Wall of text detected (' + pa.longest_paragraph_words + ' words in one paragraph)</div>';
                }
                if (pa.paragraphs_too_long > 0) {
                    html += '<div class="almaseo-llm-structure-warn"><span class="dashicons dashicons-warning"></span> ' + pa.paragraphs_too_long + ' paragraph' + (pa.paragraphs_too_long > 1 ? 's' : '') + ' over 150 words</div>';
                }
                if (pa.total_paragraphs > 0 && !pa.wall_of_text_detected && pa.paragraphs_too_long === 0) {
                    html += '<div class="almaseo-llm-structure-ok"><span class="dashicons dashicons-yes-alt"></span> Paragraph lengths are well-balanced</div>';
                }

                html += '</div>';
            }

            // --- Answer Positioning ---
            if (data.answer_positioning) {
                var ap = data.answer_positioning;
                html += '<div class="almaseo-llm-structure-section">';
                html += '<h4>Answer Positioning</h4>';

                if (ap.starts_with_fluff) {
                    html += '<div class="almaseo-llm-structure-warn"><span class="dashicons dashicons-warning"></span> Content starts with filler: "' + escapeHtml(ap.fluff_detected) + '"</div>';
                } else if (ap.has_direct_answer) {
                    html += '<div class="almaseo-llm-structure-ok"><span class="dashicons dashicons-yes-alt"></span> Opens with a direct answer</div>';
                } else {
                    html += '<div class="almaseo-llm-structure-neutral"><span class="dashicons dashicons-info"></span> Consider leading with a direct answer to the main question</div>';
                }

                html += '</div>';
            }

            // --- FAQ Structure ---
            if (data.faq_structure) {
                var faq = data.faq_structure;
                html += '<div class="almaseo-llm-structure-section">';
                html += '<h4>FAQ Structure</h4>';

                if (faq.has_faq_structure) {
                    html += '<div class="almaseo-llm-structure-ok"><span class="dashicons dashicons-yes-alt"></span> FAQ-style content detected (' + faq.question_heading_count + ' question headings)</div>';
                } else if (faq.question_heading_count === 1) {
                    html += '<div class="almaseo-llm-structure-neutral"><span class="dashicons dashicons-info"></span> 1 question heading found — add more for a FAQ structure</div>';
                } else {
                    html += '<div class="almaseo-llm-structure-neutral"><span class="dashicons dashicons-info"></span> No FAQ-style headings — consider adding question-based H2s</div>';
                }

                html += '</div>';
            }

            // --- Formatting ---
            if (data.formatting) {
                var fmt = data.formatting;
                html += '<div class="almaseo-llm-structure-section">';
                html += '<h4>Formatting Elements</h4>';

                html += '<div class="almaseo-llm-structure-stats">';
                if (fmt.has_lists) html += '<span class="almaseo-llm-structure-stat">' + (fmt.ul_count + fmt.ol_count) + ' list' + ((fmt.ul_count + fmt.ol_count) > 1 ? 's' : '') + ' (' + fmt.li_count + ' items)</span>';
                if (fmt.has_tables) html += '<span class="almaseo-llm-structure-stat">' + fmt.table_count + ' table' + (fmt.table_count > 1 ? 's' : '') + '</span>';
                if (fmt.blockquote_count > 0) html += '<span class="almaseo-llm-structure-stat">' + fmt.blockquote_count + ' blockquote' + (fmt.blockquote_count > 1 ? 's' : '') + '</span>';
                html += '</div>';

                // Mini score bar
                var fmtColor = getScoreColor(fmt.formatting_score);
                html += '<div class="almaseo-llm-mini-bar-container">';
                html += '<span class="almaseo-llm-mini-bar-label">Formatting score: ' + fmt.formatting_score + '</span>';
                html += '<div class="almaseo-llm-mini-bar"><div class="almaseo-llm-mini-bar-fill" style="width: ' + fmt.formatting_score + '%; background: ' + fmtColor + '"></div></div>';
                html += '</div>';

                if (!fmt.has_lists && !fmt.has_tables) {
                    html += '<div class="almaseo-llm-structure-warn"><span class="dashicons dashicons-warning"></span> No lists or tables — add structured elements for better LLM extraction</div>';
                }

                html += '</div>';
            }

            // --- Summary / TL;DR ---
            if (data.summary_detection) {
                var sd = data.summary_detection;
                html += '<div class="almaseo-llm-structure-section">';
                html += '<h4>Summary & TL;DR</h4>';

                if (sd.has_tldr || sd.has_key_takeaways) {
                    var label = sd.has_key_takeaways ? 'Key Takeaways' : 'TL;DR';
                    html += '<div class="almaseo-llm-structure-ok"><span class="dashicons dashicons-yes-alt"></span> ' + label + ' found at ' + sd.summary_location + ' of content</div>';
                } else {
                    html += '<div class="almaseo-llm-structure-neutral"><span class="dashicons dashicons-info"></span> Consider adding a TL;DR or Key Takeaways section</div>';
                }

                if (sd.has_table_of_contents) {
                    html += '<div class="almaseo-llm-structure-ok"><span class="dashicons dashicons-yes-alt"></span> Table of Contents detected</div>';
                }

                html += '</div>';
            }

            $container.html(html);
        }

        /**
         * Render section-level analysis
         */
        function renderLLMSections(sections) {
            var $container = $('.almaseo-llm-sections-container');
            var $list = $('.almaseo-llm-sections-list');
            var $panel = $('.almaseo-llm-panel');
            var isPro = $panel.data('is-pro-llm') === 1;

            if (!sections || sections.length === 0) {
                $container.hide();
                return;
            }

            // Sort: flagged/problem sections first, then by original order
            var sorted = sections.slice().sort(function(a, b) {
                var aFlags = (a.flags && a.flags.length) || 0;
                var bFlags = (b.flags && b.flags.length) || 0;
                if (aFlags > 0 && bFlags === 0) return -1;
                if (aFlags === 0 && bFlags > 0) return 1;
                return 0; // preserve original order within groups
            });

            var sectionsHtml = '';

            sorted.forEach(function(section, index) {
                var typeLabel = getSectionTypeLabel(section.type);
                var clarityColor = getScoreColor(section.clarity_score);
                var llmColor = getScoreColor(section.llm_readiness);
                var answerColor = getScoreColor(section.answerability);

                var hasFlags = section.flags && section.flags.length > 0;
                sectionsHtml += '<div class="almaseo-llm-section-item' + (hasFlags ? ' almaseo-llm-section-flagged' : '') + '">';
                sectionsHtml += '<div class="almaseo-llm-section-header">';
                sectionsHtml += '<strong>' + escapeHtml(section.heading) + '</strong>';
                sectionsHtml += '<span class="almaseo-llm-section-type">' + typeLabel + '</span>';
                sectionsHtml += '<span class="almaseo-llm-section-wordcount">' + section.word_count + ' words</span>';
                sectionsHtml += '</div>';

                sectionsHtml += '<div class="almaseo-llm-section-excerpt">' + escapeHtml(section.excerpt) + '</div>';

                // Scores
                sectionsHtml += '<div class="almaseo-llm-section-scores">';

                // Clarity (Free for all)
                sectionsHtml += '<div class="almaseo-llm-section-score-pill" style="border-color: ' + clarityColor + ';">';
                sectionsHtml += '<span class="label">Language Clarity:</span>';
                sectionsHtml += '<span class="value" style="color: ' + clarityColor + ';">' + section.clarity_score + '</span>';
                sectionsHtml += '</div>';

                // AI Readiness & Answer Strength (Pro only or show locked)
                if (isPro) {
                    sectionsHtml += '<div class="almaseo-llm-section-score-pill" style="border-color: ' + llmColor + ';">';
                    sectionsHtml += '<span class="label">LLM Readiness:</span>';
                    sectionsHtml += '<span class="value" style="color: ' + llmColor + ';">' + section.llm_readiness + '</span>';
                    sectionsHtml += '</div>';

                    sectionsHtml += '<div class="almaseo-llm-section-score-pill" style="border-color: ' + answerColor + ';">';
                    sectionsHtml += '<span class="label">Answer Strength:</span>';
                    sectionsHtml += '<span class="value" style="color: ' + answerColor + ';">' + section.answerability + '</span>';
                    sectionsHtml += '</div>';
                } else {
                    sectionsHtml += '<div class="almaseo-llm-section-score-pill-locked">';
                    sectionsHtml += '<span class="dashicons dashicons-lock"></span> Pro';
                    sectionsHtml += '</div>';
                }

                sectionsHtml += '</div>';

                // Flags (if any)
                if (section.flags && section.flags.length > 0) {
                    sectionsHtml += '<div class="almaseo-llm-section-flags">';
                    section.flags.forEach(function(flag) {
                        var flagLabel = getFlagLabel(flag);
                        sectionsHtml += '<span class="almaseo-llm-section-flag">' + flagLabel + '</span>';
                    });
                    sectionsHtml += '</div>';
                }

                sectionsHtml += '</div>';
            });

            $list.html(sectionsHtml);
            $container.show();
        }

        /**
         * Get section type label
         */
        function getSectionTypeLabel(type) {
            var labels = {
                'title': 'Title',
                'intro': 'Introduction',
                'h2_section': 'Section',
                'lists': 'Lists',
                'conclusion': 'Conclusion'
            };
            return labels[type] || type;
        }

        /**
         * Get flag label
         */
        function getFlagLabel(flag) {
            var labels = {
                'thin_content': 'Too short for LLM extraction',
                'ambiguous_pronouns': 'Unclear references (it, this, that)',
                'vague_language': 'Vague wording (many, some, few)',
                'missing_specifics': 'No specific names or terms',
                'wall_of_text': 'Wall of text — break into shorter blocks',
                'starts_with_fluff': 'Opens with filler text'
            };
            return labels[flag] || flag;
        }

        /**
         * Get color based on score
         */
        function getScoreColor(score) {
            if (score >= 75) return '#10b981';
            if (score >= 50) return '#f59e0b';
            return '#dc3232';
        }

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Refresh button
        $(document).on('click', '.almaseo-llm-refresh', function(e) {
            e.preventDefault();
            loadLLMAnalysis();
        });

        // Style selector change handler
        $(document).on('change', '#almaseo-llm-summary-style', function() {
            var selectedStyle = $(this).val();
            var $panel = $('.almaseo-llm-panel');
            var isPro = $panel.data('is-pro-llm') === 1;

            // Check if Pro style is selected but user is not Pro
            if ((selectedStyle === 'qa' || selectedStyle === 'ai_answer') && !isPro) {
                alert('This summary style requires Pro. Please upgrade to access Q&A Format and LLM Answer styles.');
                $(this).val('concise'); // Reset to concise
                return;
            }

            // Reload analysis with new style
            loadLLMAnalysis();
        });

        // Load analysis when LLM tab becomes active
        $(document).on('click', '.almaseo-tab-btn[data-tab="llm-optimization"]', function() {
            // Check if analysis has already been loaded
            if (!$('.almaseo-llm-summary-text').hasClass('almaseo-llm-loading')) {
                // Already loaded, don't reload
                return;
            }

            // Wait a bit for tab transition
            setTimeout(function() {
                loadLLMAnalysis();
            }, 300);
        });

        // Auto-load if LLM tab is active on page load
        if ($('.almaseo-tab-btn[data-tab="llm-optimization"]').hasClass('active')) {
            setTimeout(function() {
                loadLLMAnalysis();
            }, 500);
        }
    });
    </script>
    
    <!-- Saved Snippets Modals -->
    <div id="snippet-modal" class="almaseo-modal" style="display: none;">
        <div class="almaseo-modal-content">
            <div class="almaseo-modal-header">
                <h3 id="snippet-modal-title">Create New Snippet</h3>
                <span class="almaseo-modal-close">&times;</span>
            </div>
            <div class="almaseo-modal-body">
                <form id="snippet-form">
                    <div class="almaseo-field-group">
                        <label for="snippet-title">Snippet Title</label>
                        <input type="text" id="snippet-title" name="snippet-title" class="almaseo-input" placeholder="Enter a descriptive title..." />
                    </div>
                    <div class="almaseo-field-group">
                        <label for="snippet-content">Snippet Content</label>
                        <textarea id="snippet-content" name="snippet-content" class="almaseo-textarea" placeholder="Enter your snippet content..." rows="6"></textarea>
                    </div>
                    <div class="almaseo-modal-actions">
                        <button type="button" class="almaseo-modal-btn almaseo-modal-cancel">Cancel</button>
                        <button type="submit" class="almaseo-modal-btn almaseo-modal-save">Save Snippet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="delete-snippet-modal" class="almaseo-modal" style="display: none;">
        <div class="almaseo-modal-content">
            <div class="almaseo-modal-header">
                <h3>Delete Snippet</h3>
                <span class="almaseo-modal-close">&times;</span>
            </div>
            <div class="almaseo-modal-body">
                <p>Are you sure you want to delete this snippet?</p>
                <p><strong id="delete-snippet-title"></strong></p>
                <div class="almaseo-modal-actions">
                    <button type="button" class="almaseo-modal-btn almaseo-modal-cancel">Cancel</button>
                    <button type="button" class="almaseo-modal-btn almaseo-modal-delete">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Auto-Fill Buttons Handler -->
    <script>
    // $ai_autofill_available is computed once at the top of this callback.
    var almaseoAiAutofillAvailable = <?php echo esc_js($ai_autofill_available ? 'true' : 'false'); ?>;
    var almaseoDashboardUrl = '<?php echo esc_js( defined('ALMASEO_DASHBOARD_URL') ? ALMASEO_DASHBOARD_URL : 'https://app.almaseo.com' ); ?>';
    (function() {
        var btns = document.querySelectorAll('.almaseo-autofill-btn');
        if (!btns.length) return;

        var postId = document.getElementById('post_ID')
                     ? document.getElementById('post_ID').value
                     : 0;

        var nonce = document.getElementById('almaseo_nonce')
                    ? document.getElementById('almaseo_nonce').value
                    : '';

        var aiAvailable = typeof almaseoAiAutofillAvailable !== 'undefined' && almaseoAiAutofillAvailable;

        // Helper: get or create the status element next to a button
        function getStatusEl(btn) {
            var parent = btn.closest('.field-subtext') || btn.parentNode;
            var statusEl = parent.querySelector('.almaseo-gen-status');
            if (!statusEl) {
                statusEl = document.createElement('span');
                statusEl.className = 'almaseo-gen-status';
                statusEl.style.cssText = 'display: inline-flex; align-items: center; gap: 4px; font-size: 12px; margin-left: 8px; transition: opacity 0.3s;';
                // Insert after the helper text span, or append to parent
                var helperSpan = parent.querySelector('span[style*="font-style"]');
                if (helperSpan) {
                    helperSpan.parentNode.insertBefore(statusEl, helperSpan.nextSibling);
                } else {
                    parent.appendChild(statusEl);
                }
            }
            return statusEl;
        }

        // Helper: show patience banner during generation, explaining what's
        // happening so the user doesn't bail mid-request. Reuses one DOM
        // node — show on click, hide on success/error.
        function showPatienceBanner(fieldLabel) {
            var banner = document.getElementById('almaseo-patience-banner');
            if (!banner) {
                banner = document.createElement('div');
                banner.id = 'almaseo-patience-banner';
                banner.style.cssText = 'margin: 0 0 12px 0; padding: 10px 14px; background: linear-gradient(135deg,#f5f3ff,#ede9fe); border: 1px solid #c4b5fd; border-left: 3px solid #7c3aed; border-radius: 4px; font-size: 12px; display: flex; align-items: center; gap: 8px;';
                var fieldsSection = document.querySelector('.almaseo-seo-fields');
                if (fieldsSection) {
                    fieldsSection.insertBefore(banner, fieldsSection.firstChild);
                }
            }
            var copy = aiAvailable
                ? 'Personalizing your ' + fieldLabel + ' from your AlmaSEO profile — pulling business details, services, and service areas. This usually takes a few seconds.'
                : 'Generating a ' + fieldLabel + ' from your post content. <a href="' + (typeof almaseoDashboardUrl !== "undefined" ? almaseoDashboardUrl : "https://app.almaseo.com") + '" target="_blank" style="color:#5b21b6;">Connect to AlmaSEO</a> for sharper, profile-aware results.';
            banner.innerHTML = '<span class="dashicons dashicons-update" style="color: #7c3aed; font-size: 16px; width: 16px; height: 16px; flex-shrink: 0; animation:rotation 1s linear infinite;"></span>'
                + '<span style="color: #4c1d95;">' + copy + '</span>';
            banner.style.display = 'flex';
        }
        function hidePatienceBanner() {
            var banner = document.getElementById('almaseo-patience-banner');
            if (banner) {
                banner.style.display = 'none';
            }
        }

        // Helper: show save reminder banner (once per session)
        var saveReminderShown = false;
        function showSaveReminder() {
            if (saveReminderShown) return;
            saveReminderShown = true;

            var prev = document.getElementById('almaseo-save-reminder');
            if (prev) prev.remove();

            var banner = document.createElement('div');
            banner.id = 'almaseo-save-reminder';
            banner.style.cssText = 'margin: 0 0 12px 0; padding: 10px 14px; background: #e8f0fe; border: 1px solid #a8c7fa; border-left: 3px solid #1a73e8; border-radius: 4px; font-size: 12px; display: flex; align-items: center; gap: 8px;';
            banner.innerHTML = '<span class="dashicons dashicons-info" style="color: #1a73e8; font-size: 16px; width: 16px; height: 16px; flex-shrink: 0;"></span>'
                + '<span style="color: #174ea6;">Don\'t forget to click <strong>Update</strong> (or <strong>Publish</strong>) at the top of this page to save your new metadata.</span>'
                + '<button type="button" style="margin-left: auto; background: none; border: none; color: #5f6368; cursor: pointer; font-size: 16px; padding: 0; line-height: 1;" aria-label="Dismiss">&times;</button>';

            banner.querySelector('button').addEventListener('click', function() {
                banner.style.opacity = '0';
                setTimeout(function() { banner.remove(); }, 300);
            });

            // Insert at the TOP of the SEO fields section so it's immediately visible
            var fieldsSection = document.querySelector('.almaseo-seo-fields');
            if (fieldsSection) {
                fieldsSection.insertBefore(banner, fieldsSection.firstChild);
                // Scroll it into view
                banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // Clear the save reminder once the post is actually saved. The block
        // editor saves via AJAX with no page reload, so nothing removed the
        // banner before. Re-arm it (saveReminderShown=false) so it can show
        // again after the next generation.
        if (window.wp && wp.data && typeof wp.data.subscribe === 'function') {
            var _wasSaving = false;
            wp.data.subscribe(function() {
                var ed = wp.data.select('core/editor');
                if (!ed || typeof ed.isSavingPost !== 'function') return;
                var saving = ed.isSavingPost() && !ed.isAutosavingPost();
                if (_wasSaving && !saving) {
                    var b = document.getElementById('almaseo-save-reminder');
                    if (b) b.remove();
                    saveReminderShown = false;
                }
                _wasSaving = saving;
            });
        }

        btns.forEach(function(btn) {
            // Update button label if AI is available
            if (aiAvailable) {
                var label = btn.querySelector('.almaseo-autofill-label');
                if (label) {
                    var field = btn.getAttribute('data-field');
                    label.textContent = field === 'title' ? 'Generate Title' : 'Generate Description';
                }
            }

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var field = this.getAttribute('data-field');
                var origHTML = this.innerHTML;
                var fieldLabel = field === 'title' ? 'title' : 'description';
                var statusEl = getStatusEl(this);

                // Button just shows spinner while disabled
                this.innerHTML = '<span class="dashicons dashicons-update" style="font-size:14px;line-height:22px;width:14px;height:14px;animation:rotation 1s linear infinite;"></span> Generating...';
                this.disabled = true;

                // Status message appears outside the button — clearly visible
                if (aiAvailable) {
                    statusEl.innerHTML = '<span style="color: #5b21b6; font-weight: 500;">AlmaSEO is generating your ' + fieldLabel + '...</span>';
                    statusEl.style.opacity = '1';
                }

                // Patience banner explains what's actually happening behind
                // the spinner so users don't think the page has hung.
                showPatienceBanner(fieldLabel);

                var fd = new FormData();
                fd.append('action', 'almaseo_autofill_field');
                fd.append('post_id', postId);
                fd.append('field', field);
                fd.append('nonce', nonce);
                fd.append('mode', 'auto');

                // Send the values currently on screen so generation uses what the
                // user just typed — the focus keyword, title and description are
                // only in the DB after a post save, but generation should reflect
                // the live fields (e.g. set keyword, then Generate without saving).
                var fkEl = document.getElementById('almaseo_focus_keyword');
                var tEl  = document.getElementById('almaseo_seo_title');
                var dEl  = document.getElementById('almaseo_seo_description');
                if (fkEl) fd.append('focus_keyword', fkEl.value);
                if (tEl)  fd.append('current_title', tEl.value);
                if (dEl)  fd.append('current_description', dEl.value);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxurl, true);
                xhr.timeout = aiAvailable ? 40000 : 10000;
                xhr.onload = function() {
                    btn.innerHTML = origHTML;
                    btn.disabled = false;
                    hidePatienceBanner();

                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp.success && resp.data && resp.data.value) {
                            var value = resp.data.value;
                            if (field === 'title') {
                                var input = document.getElementById('almaseo_seo_title');
                                if (input) {
                                    input.value = value;
                                    input.dispatchEvent(new Event('input', {bubbles: true}));
                                    input.dispatchEvent(new Event('change', {bubbles: true}));
                                }
                            } else if (field === 'description') {
                                var textarea = document.getElementById('almaseo_seo_description');
                                if (textarea) {
                                    textarea.value = value;
                                    textarea.dispatchEvent(new Event('input', {bubbles: true}));
                                    textarea.dispatchEvent(new Event('change', {bubbles: true}));
                                }
                            }

                            // Resolution badge — shows exactly which path
                            // ran and whether the business profile was used.
                            // Tooltip lists the cached profile fields that
                            // were available, so the user can verify their
                            // dashboard data is reaching the plugin.
                            var res = resp.data.resolution || (resp.data.ai ? 'ai' : 'local');
                            var fieldsPresent = resp.data.profile_fields_present || [];
                            var fieldsTip = fieldsPresent.length
                                ? 'Cached profile fields used: ' + fieldsPresent.join(', ')
                                : 'No profile fields cached on this site yet.';
                            var badgeMap = {
                                'ai_with_profile':    { color: '#15803d', icon: 'yes-alt',  text: 'Generated by AlmaSEO (using your profile)' },
                                'ai':                 { color: '#5b21b6', icon: 'yes-alt',  text: 'Generated by AlmaSEO' },
                                'local_with_profile': { color: '#0369a1', icon: 'admin-site-alt3', text: 'Generated locally using your profile' },
                                'local':              { color: '#64748b', icon: 'admin-site-alt3', text: 'Generated locally (no profile cached)' },
                                'local_locked':       { color: '#a16207', icon: 'lock',     text: 'Generated locally — Upgrade to AlmaSEO Pro for profile-aware generation' }
                            };
                            var b = badgeMap[res] || badgeMap.local;
                            statusEl.innerHTML = '<span class="dashicons dashicons-' + b.icon + '" style="color: ' + b.color + '; font-size: 14px; width: 14px; height: 14px;"></span>'
                                + '<span style="color: ' + b.color + '; font-weight: 500;" title="' + fieldsTip.replace(/"/g, '&quot;') + '">' + b.text + '</span>';
                            // Don't auto-hide — leave the badge so the
                            // user can read it and inspect the tooltip.

                            // Show save reminder
                            showSaveReminder();

                            // Show profile suggestions if any
                            if (resp.data.ai && resp.data.profile_suggestions && resp.data.profile_suggestions.length) {
                                showProfileHints(resp.data.profile_suggestions);
                            }
                        } else {
                            statusEl.innerHTML = '<span style="color: #d63638;">Generation failed. Try again.</span>';
                            setTimeout(function() { statusEl.innerHTML = ''; }, 4000);
                        }
                    } catch (ex) {
                        statusEl.innerHTML = '<span style="color: #d63638;">Error: ' + ex.message + '</span>';
                        setTimeout(function() { statusEl.innerHTML = ''; }, 4000);
                    }
                };
                xhr.onerror = function() {
                    btn.innerHTML = origHTML;
                    btn.disabled = false;
                    hidePatienceBanner();
                    statusEl.innerHTML = '<span style="color: #d63638;">Request failed. Check your connection.</span>';
                    setTimeout(function() { statusEl.innerHTML = ''; }, 4000);
                };
                xhr.ontimeout = function() {
                    btn.innerHTML = origHTML;
                    btn.disabled = false;
                    hidePatienceBanner();
                    statusEl.innerHTML = '<span style="color: #d63638;">Request timed out. Try again.</span>';
                    setTimeout(function() { statusEl.innerHTML = ''; }, 4000);
                };
                xhr.send(fd);
            });
        });
        function showProfileHints(suggestions) {
            // Remove any previous hint
            var prev = document.getElementById('almaseo-profile-hints');
            if (prev) prev.remove();

            var high = suggestions.filter(function(s) { return s.impact === 'high'; });
            var medium = suggestions.filter(function(s) { return s.impact === 'medium'; });
            if (!high.length && !medium.length) return;

            var container = document.createElement('div');
            container.id = 'almaseo-profile-hints';
            container.style.cssText = 'margin: 12px 0; padding: 12px 14px; background: #fef9ee; border: 1px solid #f0dca0; border-left: 3px solid #dba617; border-radius: 4px; font-size: 12px;';

            var html = '<div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">';
            html += '<strong style="color: #92660a;">For better Alma results, complete these in your AlmaSEO profile:</strong>';
            html += '</div><ul style="margin: 0; padding: 0 0 0 18px; line-height: 1.8;">';

            high.forEach(function(s) {
                html += '<li><strong>' + s.field + '</strong> <span style="background:#dc3232;color:#fff;font-size:9px;padding:1px 5px;border-radius:8px;margin-left:3px;">HIGH</span>';
                html += ' <span style="color:#666;"> — ' + s.reason + '</span></li>';
            });
            medium.forEach(function(s) {
                html += '<li><strong>' + s.field + '</strong> <span style="background:#dba617;color:#fff;font-size:9px;padding:1px 5px;border-radius:8px;margin-left:3px;">MEDIUM</span>';
                html += ' <span style="color:#666;"> — ' + s.reason + '</span></li>';
            });

            html += '</ul>';
            container.innerHTML = html;

            // Insert after the SEO fields section
            var fieldsSection = document.querySelector('.almaseo-seo-fields');
            if (fieldsSection) {
                fieldsSection.appendChild(container);
            }
        }
    })();
    </script>
    <style>
    @keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
    <?php
}
} // end function_exists guard: almaseo_seo_playground_meta_box_callback
