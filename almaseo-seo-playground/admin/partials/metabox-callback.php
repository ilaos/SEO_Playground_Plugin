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

    // Enqueue SEO Playground scripts on post/page edit screens
    if ($screen && in_array($screen->post_type, array('post', 'page'))) {
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
            
            // Main SEO Playground styles
            wp_enqueue_style(
                'almaseo-seo-playground',
                ALMASEO_PLUGIN_URL . 'assets/css/seo-playground.css',
                array('almaseo-seo-playground-consolidated'),
                ALMASEO_PLUGIN_VERSION
            );
            
            // Tab-specific styles (only load what's needed)
            $tab_styles = array(
                'llm-optimization', // LLM Optimization panel CSS
                'health', // Health and SERP preview styles
                'unified-tabs', // Unified tab styles with emojis
                'unified-health', // Unified health score styles
                'seo-playground-tabs',
                'seo-overview-polish',
                'seo-overview-improved', // New improved SEO Overview CSS
                'search-console-polish',
                'search-console-placeholder', // Search Console placeholder CSS
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
                    array('almaseo-seo-playground'),
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
            
            // Main JavaScript
            wp_enqueue_script(
                'almaseo-seo-playground',
                ALMASEO_PLUGIN_URL . 'assets/js/seo-playground.js',
                array('jquery', 'almaseo-seo-playground-consolidated'),
                ALMASEO_PLUGIN_VERSION,
                true
            );
            
            // Tab-specific scripts
            $tab_scripts = array(
                'seo-playground-tabs',
                'unified-health', // Unified health score JavaScript
                'seo-overview-polish',
                'seo-overview-improved', // New improved SEO Overview JS
                'search-console-polish',
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
                    array('jquery', 'almaseo-seo-playground'),
                    ALMASEO_PLUGIN_VERSION,
                    true
                );
            }
        }
        
        // Localize script with connection status and other data
        wp_localize_script('almaseo-seo-playground', 'seoPlaygroundData', array(
            'almaConnected' => seo_playground_is_alma_connected(),
            'connectionUrl' => admin_url('admin.php?page=seo-playground-connection'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seo_playground_nonce'),
            // API key is NOT exposed to the browser — all API calls go through server-side AJAX handlers
            'siteUrl' => get_site_url(),
            'postId' => get_the_ID() ?: 0,
            'strings' => array(
                'unlockMessage' => '🔒 Unlock AI suggestions and automated insights by connecting to your AlmaSEO account.',
                'connectButton' => 'Connect to AlmaSEO',
                'connectedMessage' => '✅ Connected to AlmaSEO - AI features are available!'
            )
        ));
    }
}
add_action('admin_enqueue_scripts', 'almaseo_enqueue_seo_playground_styles');

/**
 * Render LLM Optimization Panel
 *
 * @param WP_Post $post The post object
 */
function almaseo_render_llm_optimization_panel($post) {
    $is_pro = almaseo_feature_available('llm_optimization');
    $is_connected = seo_playground_is_alma_connected();
    ?>
    <div class="almaseo-llm-panel" data-post-id="<?php echo esc_attr($post->ID); ?>" data-is-pro-llm="<?php echo $is_pro ? '1' : '0'; ?>">
        <div class="almaseo-llm-header">
            <h2>🤖 LLM Optimization (AI-Ready Content)</h2>
            <p>See how AI systems like ChatGPT, Gemini, and Claude understand this page — and how to make it more reliable as a source.</p>
        </div>

        <?php if (!$is_connected): ?>
            <div class="almaseo-llm-connection-notice">
                <span class="dashicons dashicons-warning"></span>
                <div>
                    <strong>Connect to AlmaSEO for Enhanced LLM Analysis</strong>
                    <p>Get deeper insights powered by our AI engine. <a href="<?php echo admin_url('admin.php?page=seo-playground-connection'); ?>">Connect now &rarr;</a></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Summary Style Selector -->
        <div class="almaseo-llm-style-selector">
            <label for="almaseo-llm-summary-style">
                <span class="dashicons dashicons-admin-appearance"></span>
                <strong>Summary Style:</strong>
            </label>
            <select id="almaseo-llm-summary-style" class="almaseo-llm-style-dropdown">
                <option value="concise">Concise</option>
                <option value="detailed">Detailed</option>
                <option value="business">Business</option>
                <option value="technical">Technical</option>
                <option value="creative">Creative</option>
                <option value="academic">Academic</option>
                <option value="qa" <?php echo !$is_pro ? 'disabled' : ''; ?>>Q&A Format <?php echo !$is_pro ? '(Pro)' : ''; ?></option>
                <option value="ai_answer" <?php echo !$is_pro ? 'disabled' : ''; ?>>AI Answer <?php echo !$is_pro ? '(Pro)' : ''; ?></option>
            </select>
        </div>

        <div class="almaseo-llm-grid">
            <!-- Left Column: Free Insights -->
            <div class="almaseo-llm-column almaseo-llm-column-left">
                <div class="almaseo-llm-card" data-section="summary">
                    <h3><span class="dashicons dashicons-media-document"></span> LLM Summary Preview</h3>
                    <div class="almaseo-llm-summary-text almaseo-llm-loading">
                        <div class="almaseo-llm-spinner"></div>
                        <span>Loading summary...</span>
                    </div>
                </div>

                <div class="almaseo-llm-card" data-section="entities">
                    <h3><span class="dashicons dashicons-tag"></span> Key Entities</h3>
                    <ul class="almaseo-llm-entities-list almaseo-llm-loading">
                        <div class="almaseo-llm-spinner"></div>
                        <span>Extracting entities...</span>
                    </ul>
                </div>

                <div class="almaseo-llm-card" data-section="clarity">
                    <h3><span class="dashicons dashicons-visibility"></span> Clarity & Ambiguity</h3>
                    <ul class="almaseo-llm-ambiguities-list almaseo-llm-loading">
                        <div class="almaseo-llm-spinner"></div>
                        <span>Analyzing clarity...</span>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Pro Insights or Lock Card -->
            <div class="almaseo-llm-column almaseo-llm-column-right">
                <?php if ($is_pro): ?>
                    <!-- Pro Features: Scores and Insights -->
                    <div class="almaseo-llm-card almaseo-llm-card-highlight" data-section="scores">
                        <h3><span class="dashicons dashicons-chart-area"></span> LLM Optimization Scores</h3>
                        <div class="almaseo-llm-scores almaseo-llm-loading">
                            <div class="almaseo-llm-spinner"></div>
                            <span>Calculating scores...</span>
                        </div>
                    </div>

                    <div class="almaseo-llm-card" data-section="schema">
                        <h3><span class="dashicons dashicons-admin-links"></span> Advanced Insights</h3>
                        <div class="almaseo-llm-insights almaseo-llm-loading">
                            <div class="almaseo-llm-spinner"></div>
                            <span>Loading insights...</span>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Free Tier: Lock Card -->
                    <div class="almaseo-llm-card almaseo-llm-lock-card">
                        <div class="almaseo-llm-lock-icon">
                            <span class="dashicons dashicons-lock"></span>
                        </div>
                        <h3>Advanced LLM Insights</h3>
                        <p class="almaseo-llm-lock-subtitle">Pro unlocks deeper AI-focused analysis so your content becomes a trusted source for LLMs.</p>
                        <ul class="almaseo-llm-lock-features">
                            <li><span class="dashicons dashicons-yes"></span> <strong>LLM Optimization Score (0–100)</strong></li>
                            <li><span class="dashicons dashicons-yes"></span> <strong>Answerability Score</strong> — how usable this page is as an AI answer source</li>
                            <li><span class="dashicons dashicons-yes"></span> <strong>Schema enhancement suggestions</strong> for AI grounding</li>
                            <li><span class="dashicons dashicons-yes"></span> <strong>Internal link suggestions</strong> to strengthen topic clusters</li>
                            <li><span class="dashicons dashicons-yes"></span> Advanced clarity analysis</li>
                        </ul>
                        <a href="https://almaseo.com/pricing" target="_blank" class="almaseo-llm-upgrade-btn">
                            <span class="dashicons dashicons-star-filled"></span>
                            Upgrade to Pro for Full LLM Insights
                        </a>
                        <p class="almaseo-llm-lock-note">Get 30+ advanced SEO features with Pro</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section-Level Analysis (Pro or Limited Free) -->
        <div class="almaseo-llm-sections-container" style="display:none;">
            <div class="almaseo-llm-card">
                <h3><span class="dashicons dashicons-editor-alignleft"></span> Section-Level Analysis</h3>
                <p class="description">See how each section of your content performs for LLM understanding.</p>
                <div class="almaseo-llm-sections-list">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>

        <div class="almaseo-llm-footer">
            <button type="button" class="button button-primary almaseo-llm-refresh">
                <span class="dashicons dashicons-update"></span>
                Refresh LLM Analysis
            </button>
            <span class="almaseo-llm-status"></span>
        </div>
    </div>
    <?php
}

// SEO Playground meta box callback
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
                <button class="almaseo-tab-btn active" data-tab="seo-overview">
                    <span class="tab-icon">🩺</span>
                    <span class="tab-label">SEO Page Health</span>
                </button>
                <button class="almaseo-tab-btn" data-tab="search-console">
                    <span class="tab-icon">📈</span>
                    <span class="tab-label">Search Console</span>
                </button>
                <button class="almaseo-tab-btn" data-tab="schema-meta">
                    <span class="tab-icon">🧩</span>
                    <span class="tab-label">Schema & Meta</span>
                </button>
                <button class="almaseo-tab-btn" data-tab="ai-tools">
                    <span class="tab-icon">✨</span>
                    <span class="tab-label">AI Tools</span>
                </button>
                <button class="almaseo-tab-btn" data-tab="llm-optimization">
                    <span class="tab-icon">🤖</span>
                    <span class="tab-label">LLM Optimization</span>
                    <span class="tab-badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; margin-left: 4px;">BETA</span>
                </button>
                <button class="almaseo-tab-btn" data-tab="notes-history">
                    <span class="tab-icon">🗒️</span>
                    <span class="tab-label">Notes & History</span>
                </button>
                <?php if (!$is_connected): ?>
                <button class="almaseo-tab-btn almaseo-unlock-tab" data-tab="unlock-features">
                    <span class="tab-icon">🔒</span>
                    <span class="tab-label">Unlock AI Features</span>
                    <span class="tab-badge">NEW</span>
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
                <a href="<?php echo admin_url('options-reading.php'); ?>" class="button" style="background: white; color: #dc3232; border: none; font-weight: bold;">Fix Immediately →</a>
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
            <h4><?php _e('SEO Editor', 'almaseo'); ?></h4>
            <div class="almaseo-field-group">
                <label for="almaseo_seo_title">
                    SEO Title
                    <span class="label-helper">(Google recommends ≤ 60 characters. Use strong keywords naturally — <a href="https://developers.google.com/search/docs/appearance/title-link?hl=en&sjid=17527460305754617133-NA&visit_id=638911458818141308-2932973546&rd=1" target="_blank" rel="noopener">Google Guidelines</a>)</span>
                </label>
                <input type="text"
                       id="almaseo_seo_title"
                       name="almaseo_seo_title"
                       value="<?php echo esc_attr($seo_title ?? ''); ?>"
                       placeholder="<?php esc_attr_e('Enter a clear, keyword-rich title (max 60 characters)', 'almaseo'); ?>"
                       class="almaseo-input" />
                <div class="almaseo-char-count" data-field="title">
                    <span id="title-count" class="<?php echo strlen($seo_title ?? '') <= 60 ? 'good' : (strlen($seo_title ?? '') <= 70 ? 'caution' : 'too-long'); ?>"><?php echo strlen($seo_title ?? ''); ?></span>/60
                </div>
                <div class="almaseo-char-bar" data-field="title" aria-label="Title character usage">
                    <?php
                    $title_len = strlen($seo_title ?? '');
                    $title_percent = min(100, ($title_len / 70) * 100); // Max at 70 chars
                    $bar_class = $title_len <= 60 ? 'good' : ($title_len <= 70 ? 'caution' : 'too-long');
                    ?>
                    <div class="char-bar-track">
                        <div class="char-bar-fill <?php echo $bar_class; ?>" style="width: <?php echo $title_percent; ?>%"></div>
                        <div class="char-bar-marker good-zone" style="left: 85.7%" aria-label="60 character mark"></div>
                    </div>
                </div>
                <p class="field-subtext" style="margin: 5px 0 0 0; color: #666; font-size: 12px; font-style: italic;">
                    <?php printf(esc_html__('Or let AlmaSEO generate optimized titles automatically → %sUpgrade%s', 'almaseo'), '<a href="' . esc_url(admin_url('admin.php?page=seo-playground-connection')) . '">', '</a>'); ?>
                </p>

                <!-- Headline Analyzer -->
                <?php if ( class_exists( 'AlmaSEO_Headline_Analyzer' ) ) : ?>
                <div id="almaseo-headline-analyzer" class="almaseo-headline-analyzer" style="margin-top: 10px; display: none;">
                    <div class="headline-score-row" style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px;">
                        <div class="headline-score-badge" id="headline-score-badge" style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; color: #fff; background: #646970; flex-shrink: 0;">
                            --
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <strong style="font-size: 13px;"><?php _e( 'Headline Score', 'almaseo' ); ?></strong>
                            <span id="headline-score-label" style="font-size: 12px; color: #646970; margin-left: 6px;"></span>
                        </div>
                        <button type="button" id="headline-toggle-details" class="button-link" style="font-size: 12px; white-space: nowrap;">
                            <?php _e( 'Show Details', 'almaseo' ); ?>
                        </button>
                    </div>
                    <div id="headline-details" style="display: none; margin-top: 6px; padding: 10px 12px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; font-size: 12px;">
                        <ul id="headline-checks-list" style="margin: 0; list-style: none; padding: 0;"></ul>
                    </div>
                </div>
                <script>
                (function() {
                    var powerWords = <?php echo wp_json_encode( AlmaSEO_Headline_Analyzer::get_word_lists()['power'] ); ?>;
                    var emotionalWords = <?php echo wp_json_encode( AlmaSEO_Headline_Analyzer::get_word_lists()['emotional'] ); ?>;
                    var commonWords = <?php echo wp_json_encode( AlmaSEO_Headline_Analyzer::get_word_lists()['common'] ); ?>;

                    var $input = document.getElementById('almaseo_seo_title');
                    var $wrap  = document.getElementById('almaseo-headline-analyzer');
                    var $badge = document.getElementById('headline-score-badge');
                    var $label = document.getElementById('headline-score-label');
                    var $details = document.getElementById('headline-details');
                    var $list  = document.getElementById('headline-checks-list');
                    var $toggle = document.getElementById('headline-toggle-details');

                    if (!$input || !$wrap) return;

                    $toggle.addEventListener('click', function() {
                        var hidden = $details.style.display === 'none';
                        $details.style.display = hidden ? '' : 'none';
                        this.textContent = hidden ? '<?php echo esc_js( __( 'Hide Details', 'almaseo' ) ); ?>' : '<?php echo esc_js( __( 'Show Details', 'almaseo' ) ); ?>';
                    });

                    function countMatches(words, list) {
                        var lookup = {};
                        for (var i = 0; i < list.length; i++) lookup[list[i]] = true;
                        var c = 0;
                        for (var j = 0; j < words.length; j++) {
                            if (lookup[words[j].replace(/[^a-z]/g, '')]) c++;
                        }
                        return c;
                    }

                    function analyze(headline) {
                        headline = (headline || '').trim();
                        if (!headline) return { score: 0, checks: [] };

                        var words = headline.toLowerCase().split(/\s+/);
                        var wc = words.length;
                        var cc = headline.length;
                        var score = 0;
                        var checks = [];

                        // Word count 6-13
                        var wcP = wc >= 6 && wc <= 13;
                        if (wcP) score += 20;
                        checks.push({ label: 'Word Count', pass: wcP, tip: wc + ' words' + (wcP ? ' — ideal range' : ' — aim for 6–13') });

                        // Char length 50-60
                        var clP = cc >= 50 && cc <= 60;
                        if (clP) score += 15;
                        checks.push({ label: 'Character Length', pass: clP, tip: cc + ' chars' + (clP ? ' — fits Google title' : ' — aim for 50–60') });

                        // Power words
                        var pw = countMatches(words, powerWords);
                        if (pw > 0) score += 15;
                        checks.push({ label: 'Power Words', pass: pw > 0, tip: pw > 0 ? pw + ' found' : 'Add a power word (e.g., "proven", "essential")' });

                        // Emotional words
                        var ew = countMatches(words, emotionalWords);
                        if (ew > 0) score += 15;
                        checks.push({ label: 'Emotional Words', pass: ew > 0, tip: ew > 0 ? ew + ' found' : 'Add an emotional trigger word' });

                        // Number
                        var hn = /\d/.test(headline);
                        if (hn) score += 10;
                        checks.push({ label: 'Contains Number', pass: hn, tip: hn ? 'Numbers attract clicks' : 'Headlines with numbers get 36% more clicks' });

                        // Question
                        var iq = /\?$/.test(headline.trim()) || /^(how|what|why|when|where|who|which|can|do|does|is|are|will|should)\b/i.test(headline);
                        if (iq) score += 10;
                        checks.push({ label: 'Question Format', pass: iq, tip: iq ? 'Questions spark curiosity' : 'Try phrasing as a question' });

                        // Word balance
                        var cwc = countMatches(words, commonWords);
                        var pct = wc > 0 ? (cwc / wc) * 100 : 0;
                        var bp = pct >= 15 && pct <= 50;
                        if (bp) score += 15;
                        checks.push({ label: 'Word Balance', pass: bp, tip: Math.round(pct) + '% common words' + (bp ? ' — good balance' : (pct > 50 ? ' — too generic' : ' — add connecting words')) });

                        return { score: Math.min(100, score), checks: checks };
                    }

                    function render(result) {
                        $wrap.style.display = '';
                        var s = result.score;
                        $badge.textContent = s;

                        if (s >= 70) { $badge.style.background = '#00a32a'; $label.textContent = 'Great!'; }
                        else if (s >= 40) { $badge.style.background = '#dba617'; $label.textContent = 'Could be better'; }
                        else { $badge.style.background = '#d63638'; $label.textContent = 'Needs work'; }

                        var html = '';
                        for (var i = 0; i < result.checks.length; i++) {
                            var c = result.checks[i];
                            var dot = c.pass ? '<span style="color:#00a32a;">&#10003;</span>' : '<span style="color:#d63638;">&#10007;</span>';
                            html += '<li style="padding: 4px 0; border-bottom: 1px solid #f0f0f1;">' + dot + ' <strong>' + c.label + '</strong> — ' + c.tip + '</li>';
                        }
                        $list.innerHTML = html;
                    }

                    // Initial
                    var val = $input.value;
                    if (val.trim()) render(analyze(val));

                    // Live update
                    var timer;
                    $input.addEventListener('input', function() {
                        clearTimeout(timer);
                        timer = setTimeout(function() {
                            var v = $input.value;
                            if (v.trim()) { render(analyze(v)); }
                            else { $wrap.style.display = 'none'; }
                        }, 200);
                    });
                })();
                </script>
                <?php endif; ?>

                <!-- AI Headline Insights (Dashboard Enhanced) -->
                <?php if ( function_exists('seo_playground_is_alma_connected') && seo_playground_is_alma_connected() ) : ?>
                <?php
                $hl_dash_raw  = get_post_meta($post->ID, '_almaseo_headline_dashboard', true);
                $hl_dash_data = $hl_dash_raw ? json_decode($hl_dash_raw, true) : null;
                ?>
                <div id="almaseo-ai-headline-panel" style="margin-top: 8px; padding: 10px 12px; background: linear-gradient(135deg, #f0f4ff 0%, #f8f9ff 100%); border: 1px solid #d0d5ff; border-radius: 4px; <?php echo $hl_dash_data ? '' : 'display:none;'; ?>">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <span style="font-size: 12px; font-weight: 600; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">AI Headline Insights</span>
                        <button type="button" id="almaseo-ai-hl-refresh" style="padding: 2px 6px; font-size: 10px; background: #fff; border: 1px solid #c3c4c7; border-radius: 3px; cursor: pointer; color: #2271b1;">Analyze</button>
                    </div>
                    <div id="almaseo-ai-hl-content" style="font-size: 12px;">
                        <?php if ( $hl_dash_data ) : ?>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 6px;">
                            <?php if ( ! empty($hl_dash_data['ctr_potential']) ) : ?>
                            <span>CTR Potential: <strong><?php echo esc_html(number_format($hl_dash_data['ctr_potential'], 1)); ?>%</strong></span>
                            <?php endif; ?>
                            <?php if ( ! empty($hl_dash_data['emotional_impact']) ) : ?>
                            <span>Emotion: <strong><?php echo esc_html($hl_dash_data['emotional_impact']); ?></strong></span>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty($hl_dash_data['rewrite_suggestions']) ) : ?>
                        <div style="margin-top: 4px;">
                            <span style="color: #666;">Suggestions:</span>
                            <?php foreach ( array_slice($hl_dash_data['rewrite_suggestions'], 0, 3) as $sug ) : ?>
                            <div class="almaseo-ai-hl-suggestion" data-text="<?php echo esc_attr($sug); ?>" style="padding: 4px 8px; margin-top: 3px; background: #fff; border-radius: 3px; cursor: pointer; color: #1d2327; transition: background 0.15s;"><?php echo esc_html($sug); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <script>
                (function(){
                    var panel = document.getElementById('almaseo-ai-headline-panel');
                    var content = document.getElementById('almaseo-ai-hl-content');
                    var btn = document.getElementById('almaseo-ai-hl-refresh');
                    var titleInput = document.getElementById('almaseo_seo_title');
                    if (!panel || !content || !btn || !titleInput) return;

                    var nonce = (typeof almaseo_health !== 'undefined' && almaseo_health.nonce)
                                ? almaseo_health.nonce
                                : '<?php echo esc_js(wp_create_nonce('almaseo_nonce')); ?>';
                    var postId = <?php echo (int) $post->ID; ?>;

                    function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

                    function renderDashboard(data) {
                        if (!data || data.available === false) { panel.style.display = 'none'; return; }
                        panel.style.display = '';
                        var html = '<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:6px;">';
                        if (data.ctr_potential) html += '<span>CTR Potential: <strong>'+Number(data.ctr_potential).toFixed(1)+'%</strong></span>';
                        if (data.emotional_impact) html += '<span>Emotion: <strong>'+escHtml(data.emotional_impact)+'</strong></span>';
                        if (data.headline_score) html += '<span>AI Score: <strong>'+data.headline_score+'/100</strong></span>';
                        html += '</div>';
                        if (data.rewrite_suggestions && data.rewrite_suggestions.length) {
                            html += '<div style="margin-top:4px;"><span style="color:#666;">Suggestions:</span>';
                            data.rewrite_suggestions.slice(0, 3).forEach(function(s){
                                html += '<div class="almaseo-ai-hl-suggestion" data-text="'+escHtml(s)+'" style="padding:4px 8px;margin-top:3px;background:#fff;border-radius:3px;cursor:pointer;color:#1d2327;transition:background 0.15s;">'+escHtml(s)+'</div>';
                            });
                            html += '</div>';
                        }
                        if (data.competitor_headlines && data.competitor_headlines.length) {
                            html += '<div style="margin-top:6px;"><span style="color:#666;">Competitor headlines:</span>';
                            data.competitor_headlines.slice(0, 3).forEach(function(c){
                                html += '<div style="padding:3px 8px;margin-top:2px;font-size:11px;color:#555;">'+escHtml(c.headline)+(c.ctr?' <span style="color:#2271b1;">('+Number(c.ctr).toFixed(1)+'% CTR)</span>':'')+'</div>';
                            });
                            html += '</div>';
                        }
                        content.innerHTML = html;
                        bindSuggestions();
                    }

                    function bindSuggestions() {
                        content.querySelectorAll('.almaseo-ai-hl-suggestion').forEach(function(el){
                            el.addEventListener('mouseenter', function(){ this.style.background = '#f0f4ff'; });
                            el.addEventListener('mouseleave', function(){ this.style.background = '#fff'; });
                            el.addEventListener('click', function(){
                                titleInput.value = this.getAttribute('data-text');
                                titleInput.dispatchEvent(new Event('input', {bubbles:true}));
                                titleInput.dispatchEvent(new Event('change', {bubbles:true}));
                            });
                        });
                    }
                    bindSuggestions();

                    btn.addEventListener('click', function(){
                        var headline = titleInput.value.trim();
                        if (!headline) return;
                        btn.textContent = '...';
                        btn.disabled = true;
                        var fd = new FormData();
                        fd.append('action', 'almaseo_headline_ai_analyze');
                        fd.append('nonce', nonce);
                        fd.append('headline', headline);
                        fd.append('post_id', postId);
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '<?php echo esc_js(admin_url('admin-ajax.php')); ?>', true);
                        xhr.onreadystatechange = function(){
                            if (this.readyState !== 4) return;
                            btn.textContent = 'Analyze';
                            btn.disabled = false;
                            if (this.status === 200) {
                                try {
                                    var resp = JSON.parse(this.responseText);
                                    if (resp.success && resp.data) renderDashboard(resp.data);
                                } catch(e){}
                            }
                        };
                        xhr.send(fd);
                    });

                    // Show panel when title has content and we're connected
                    if (titleInput.value.trim()) panel.style.display = '';
                })();
                </script>
                <?php endif; ?>

            </div>

            <div class="almaseo-field-group">
                <label for="almaseo_seo_description">
                    Meta Description
                    <span class="label-helper">(Aim for 150–160 characters. Write for humans first; clarity beats keyword stuffing.)</span>
                </label>
                <textarea id="almaseo_seo_description"
                          name="almaseo_seo_description"
                          placeholder="<?php esc_attr_e('Write a compelling, keyword-focused description (150–160 characters)', 'almaseo'); ?>"
                          class="almaseo-textarea"><?php echo esc_textarea($seo_description ?? ''); ?></textarea>
                <div class="almaseo-char-count" data-field="description">
                    <?php
                    $desc_len = strlen($seo_description ?? '');
                    $desc_class = ($desc_len >= 150 && $desc_len <= 160) ? 'good' :
                                  (($desc_len >= 120 && $desc_len < 150) || ($desc_len > 160 && $desc_len <= 180) ? 'caution' : 'too-long');
                    ?>
                    <span id="description-count" class="<?php echo $desc_class; ?>"><?php echo $desc_len; ?></span>/160
                </div>
                <div class="almaseo-char-bar" data-field="description" aria-label="Description character usage">
                    <?php
                    $desc_percent = min(100, ($desc_len / 180) * 100); // Max at 180 chars
                    $bar_class = ($desc_len >= 150 && $desc_len <= 160) ? 'good' :
                                 (($desc_len >= 120 && $desc_len < 150) || ($desc_len > 160 && $desc_len <= 180) ? 'caution' :
                                 ($desc_len < 120 ? 'too-short' : 'too-long'));
                    ?>
                    <div class="char-bar-track">
                        <div class="char-bar-fill <?php echo $bar_class; ?>" style="width: <?php echo $desc_percent; ?>%"></div>
                        <div class="char-bar-marker caution-start" style="left: 66.7%" aria-label="120 character mark"></div>
                        <div class="char-bar-marker good-start" style="left: 83.3%" aria-label="150 character mark"></div>
                        <div class="char-bar-marker good-end" style="left: 88.9%" aria-label="160 character mark"></div>
                    </div>
                </div>
                <p class="field-subtext" style="margin: 5px 0 0 0; color: #666; font-size: 12px; font-style: italic;">
                    <?php printf(esc_html__('Or use AlmaSEO AI to auto-create optimized meta descriptions → %sUpgrade%s', 'almaseo'), '<a href="' . esc_url(admin_url('admin.php?page=seo-playground-connection')) . '">', '</a>'); ?>
                </p>
            </div>

            <div class="almaseo-field-group">
                <label for="almaseo_focus_keyword">
                    Focus Keyword
                    <span class="label-helper">(Choose a realistic target that matches search intent.)</span>
                </label>
                <input type="text"
                       id="almaseo_focus_keyword"
                       name="almaseo_focus_keyword"
                       value="<?php echo esc_attr($seo_focus_keyword ?? ''); ?>"
                       placeholder="<?php esc_attr_e('Choose a keyword or phrase that matches search intent', 'almaseo'); ?>"
                       class="almaseo-input" />
                <p class="field-subtext" style="margin: 5px 0 0 0; color: #666; font-size: 12px; font-style: italic;">
                    <?php printf(esc_html__('Or unlock smart keyword suggestions and intent detection → %sUpgrade%s', 'almaseo'), '<a href="' . esc_url(admin_url('admin.php?page=seo-playground-connection')) . '">', '</a>'); ?>
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

            <!-- AI Keyword Insights (Dashboard Enhanced) -->
            <?php if ( function_exists('seo_playground_is_alma_connected') && seo_playground_is_alma_connected() ) : ?>
            <?php
            $ai_kw_stored = get_post_meta($post->ID, '_almaseo_kw_suggestions', true);
            $ai_kw_data   = $ai_kw_stored ? json_decode($ai_kw_stored, true) : array();
            ?>
            <div id="almaseo-ai-keywords-panel" style="margin-top: 12px; padding: 12px; background: linear-gradient(135deg, #f0f4ff 0%, #f8f9ff 100%); border: 1px solid #d0d5ff; border-radius: 6px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <h4 style="margin: 0; font-size: 13px; font-weight: 600; color: #1d2327;">
                        <span style="background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">AI</span>
                        Keyword Insights
                    </h4>
                    <button type="button" id="almaseo-ai-kw-refresh" style="padding: 3px 8px; font-size: 11px; background: #fff; border: 1px solid #c3c4c7; border-radius: 3px; cursor: pointer; color: #2271b1;">Refresh</button>
                </div>
                <div id="almaseo-ai-kw-list" style="max-height: 200px; overflow-y: auto;">
                    <?php if ( ! empty($ai_kw_data) ) : ?>
                        <?php foreach ( array_slice($ai_kw_data, 0, 8) as $kw ) : ?>
                        <div class="almaseo-ai-kw-item" data-keyword="<?php echo esc_attr($kw['keyword']); ?>" style="display: flex; align-items: center; gap: 6px; padding: 6px 8px; margin-bottom: 4px; background: #fff; border-radius: 4px; cursor: pointer; transition: background 0.15s; font-size: 12px;">
                            <span style="flex: 1; color: #1d2327; font-weight: 500;"><?php echo esc_html($kw['keyword']); ?></span>
                            <?php if ( ! empty($kw['volume']) ) : ?>
                            <span style="padding: 1px 6px; background: #e8f5e9; color: #2e7d32; border-radius: 10px; font-size: 10px; white-space: nowrap;"><?php echo esc_html(number_format($kw['volume'])); ?>/mo</span>
                            <?php endif; ?>
                            <?php if ( ! empty($kw['competition']) ) : ?>
                            <span style="padding: 1px 6px; background: <?php echo $kw['competition'] === 'low' ? '#e8f5e9' : ($kw['competition'] === 'high' ? '#fce4ec' : '#fff3e0'); ?>; color: <?php echo $kw['competition'] === 'low' ? '#2e7d32' : ($kw['competition'] === 'high' ? '#c62828' : '#e65100'); ?>; border-radius: 10px; font-size: 10px;"><?php echo esc_html(ucfirst($kw['competition'])); ?></span>
                            <?php endif; ?>
                            <?php if ( ! empty($kw['intent']) ) : ?>
                            <span style="padding: 1px 6px; background: #e3f2fd; color: #1565c0; border-radius: 10px; font-size: 10px;"><?php echo esc_html(ucfirst(substr($kw['intent'], 0, 5))); ?></span>
                            <?php endif; ?>
                            <?php if ( ! empty($kw['trend']) && $kw['trend'] !== 'stable' ) : ?>
                            <span style="font-size: 11px;"><?php echo $kw['trend'] === 'up' ? '↑' : '↓'; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p style="margin: 0; color: #666; font-size: 12px; font-style: italic;">Type a focus keyword above, then click Refresh to get AI keyword insights.</p>
                    <?php endif; ?>
                </div>
            </div>
            <script>
            (function(){
                var panel = document.getElementById('almaseo-ai-keywords-panel');
                var listEl = document.getElementById('almaseo-ai-kw-list');
                var refreshBtn = document.getElementById('almaseo-ai-kw-refresh');
                var kwInput = document.getElementById('almaseo_focus_keyword');
                if (!panel || !listEl || !refreshBtn || !kwInput) return;

                var nonce = (typeof almaseo_health !== 'undefined' && almaseo_health.nonce)
                            ? almaseo_health.nonce
                            : '<?php echo esc_js(wp_create_nonce('almaseo_nonce')); ?>';
                var postId = <?php echo (int) $post->ID; ?>;

                function renderAiKeywords(keywords) {
                    if (!keywords || !keywords.length) {
                        listEl.innerHTML = '<p style="margin:0;color:#666;font-size:12px;font-style:italic;">No AI keyword insights available yet.</p>';
                        return;
                    }
                    listEl.innerHTML = '';
                    keywords.slice(0, 8).forEach(function(kw) {
                        var div = document.createElement('div');
                        div.className = 'almaseo-ai-kw-item';
                        div.setAttribute('data-keyword', kw.keyword);
                        div.style.cssText = 'display:flex;align-items:center;gap:6px;padding:6px 8px;margin-bottom:4px;background:#fff;border-radius:4px;cursor:pointer;transition:background 0.15s;font-size:12px;';
                        var html = '<span style="flex:1;color:#1d2327;font-weight:500;">' + escHtml(kw.keyword) + '</span>';
                        if (kw.volume) html += '<span style="padding:1px 6px;background:#e8f5e9;color:#2e7d32;border-radius:10px;font-size:10px;white-space:nowrap;">' + Number(kw.volume).toLocaleString() + '/mo</span>';
                        if (kw.competition) {
                            var bg = kw.competition==='low'?'#e8f5e9':kw.competition==='high'?'#fce4ec':'#fff3e0';
                            var fg = kw.competition==='low'?'#2e7d32':kw.competition==='high'?'#c62828':'#e65100';
                            html += '<span style="padding:1px 6px;background:'+bg+';color:'+fg+';border-radius:10px;font-size:10px;">'+kw.competition.charAt(0).toUpperCase()+kw.competition.slice(1)+'</span>';
                        }
                        if (kw.intent) html += '<span style="padding:1px 6px;background:#e3f2fd;color:#1565c0;border-radius:10px;font-size:10px;">'+kw.intent.charAt(0).toUpperCase()+kw.intent.slice(1,5)+'</span>';
                        if (kw.trend && kw.trend !== 'stable') html += '<span style="font-size:11px;">'+(kw.trend==='up'?'↑':'↓')+'</span>';
                        div.innerHTML = html;
                        div.addEventListener('mouseenter', function(){ this.style.background = '#f0f4ff'; });
                        div.addEventListener('mouseleave', function(){ this.style.background = '#fff'; });
                        div.addEventListener('click', function(){
                            kwInput.value = kw.keyword;
                            kwInput.dispatchEvent(new Event('input', {bubbles:true}));
                            kwInput.dispatchEvent(new Event('change', {bubbles:true}));
                        });
                        listEl.appendChild(div);
                    });
                }

                function escHtml(str) {
                    var d = document.createElement('div'); d.textContent = str; return d.innerHTML;
                }

                refreshBtn.addEventListener('click', function() {
                    var q = kwInput.value.trim();
                    if (q.length < 2) { listEl.innerHTML = '<p style="margin:0;color:#666;font-size:12px;">Enter a focus keyword first.</p>'; return; }
                    refreshBtn.textContent = '...';
                    refreshBtn.disabled = true;
                    var url = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>'
                        + '?action=almaseo_keyword_ai_suggest&nonce=' + encodeURIComponent(nonce)
                        + '&q=' + encodeURIComponent(q)
                        + '&post_id=' + postId;
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', url, true);
                    xhr.onreadystatechange = function() {
                        if (this.readyState !== 4) return;
                        refreshBtn.textContent = 'Refresh';
                        refreshBtn.disabled = false;
                        if (this.status === 200) {
                            try {
                                var resp = JSON.parse(this.responseText);
                                if (resp.success && resp.data && resp.data.keywords) {
                                    renderAiKeywords(resp.data.keywords);
                                }
                            } catch(e) {}
                        }
                    };
                    xhr.send();
                });

                // Click handler for pre-rendered items
                listEl.querySelectorAll('.almaseo-ai-kw-item').forEach(function(item) {
                    item.addEventListener('mouseenter', function(){ this.style.background = '#f0f4ff'; });
                    item.addEventListener('mouseleave', function(){ this.style.background = '#fff'; });
                    item.addEventListener('click', function(){
                        kwInput.value = this.getAttribute('data-keyword');
                        kwInput.dispatchEvent(new Event('input', {bubbles:true}));
                        kwInput.dispatchEvent(new Event('change', {bubbles:true}));
                    });
                });
            })();
            </script>
            <?php endif; ?>

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
                            __('This simulates how your result may appear in Google. Aim for concise, clear titles and descriptions.', 'almaseo'),
                            __('Titles typically truncate around ~580px; descriptions around ~920px on desktop.', 'almaseo')
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
                            $site_url = parse_url(get_site_url());
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

            <!-- Static Keyword Intelligence -->
            <div class="almaseo-keyword-suggestions">
                <label>📊 Keyword Suggestions</label>
                <div class="keyword-suggestions-box">
                    <?php
                    // Basic keyword extraction from content (non-AI)
                    $content_text = strip_tags($post->post_content);
                    $words = str_word_count(strtolower($content_text), 1);
                    $word_freq = array_count_values($words);

                    // Filter out common stop words
                    $stop_words = ['the', 'is', 'at', 'which', 'on', 'a', 'an', 'as', 'are', 'was', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'shall', 'to', 'of', 'in', 'for', 'with', 'by', 'from', 'about', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'up', 'down', 'out', 'off', 'over', 'under', 'again', 'further', 'then', 'once', 'and', 'or', 'but', 'if', 'because', 'as', 'until', 'while', 'of', 'at', 'by', 'for', 'with', 'about', 'against', 'between', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'to', 'from', 'up', 'down', 'in', 'out', 'on', 'off', 'over', 'under', 'again', 'further', 'then', 'once'];

                    foreach ($stop_words as $stop_word) {
                        unset($word_freq[$stop_word]);
                    }

                    // Remove short words
                    $word_freq = array_filter($word_freq, function($key) {
                        return strlen($key) > 3;
                    }, ARRAY_FILTER_USE_KEY);

                    // Sort by frequency
                    arsort($word_freq);
                    $top_keywords = array_slice($word_freq, 0, 8, true);

                    if (empty($top_keywords)) {
                        echo '<p class="no-suggestions">Add more content to see keyword suggestions</p>';
                    } else {
                        echo '<div class="keyword-chips">';
                        foreach ($top_keywords as $keyword => $count) {
                            echo '<span class="keyword-chip" data-keyword="' . esc_attr($keyword) . '">';
                            echo esc_html($keyword) . ' (' . $count . ')';
                            echo '</span>';
                        }
                        echo '</div>';
                        echo '<p class="suggestion-hint">Click a keyword to use it as your focus keyword. Frequency shown in parentheses.</p>';
                    }

                    if (!$is_connected) {
                        echo '<div class="ai-upsell-note">';
                        echo __('Unlock competitor insights, trending keywords, and search intent detection with AlmaSEO AI', 'almaseo');
                        echo ' → <a href="' . admin_url('admin.php?page=seo-playground-connection') . '">' . __('Connect Now', 'almaseo') . '</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
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
                    <h3><?php _e('Overall SEO Health', 'almaseo'); ?></h3>
                    <p class="health-status health-<?php echo esc_attr($score_class); ?>">
                        <?php
                        if ($health_score >= 80) {
                            _e('Excellent! Your content is well-optimized.', 'almaseo');
                        } elseif ($health_score >= 50) {
                            _e('Good, but there\'s room for improvement.', 'almaseo');
                        } else {
                            _e('Needs attention. Follow the suggestions below.', 'almaseo');
                        }
                        ?>
                    </p>
                    <div class="health-stats">
                        <span class="health-stat">
                            <span class="stat-icon">✅</span>
                            <strong><?php echo $pass_count; ?></strong> Passed
                        </span>
                        <span class="health-stat">
                            <span class="stat-icon">❌</span>
                            <strong><?php echo $fail_count; ?></strong> Issues
                        </span>
                    </div>
                    <button type="button" class="button button-primary" id="almaseo-health-recalculate" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Recalculate', 'almaseo'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Middle Section: Signal Breakdown -->
        <div class="almaseo-health-signals">
            <h4><?php _e('SEO Signal Analysis', 'almaseo'); ?></h4>
            
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
                    $extra_style = ' style="border: 2px solid #dc3232; background: #fff5f5;"';
                }
            ?>
            <div class="almaseo-health-signal <?php echo esc_attr($status_class . $extra_class); ?>"<?php echo $extra_style; ?>>
                <div class="signal-header">
                    <span class="signal-icon"><?php echo $icon; ?></span>
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
                        <?php _e('Go to Field', 'almaseo'); ?> ↓
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="signal-bar-wrapper">
                    <div class="signal-bar">
                        <div class="signal-bar-fill <?php echo esc_attr($status_class); ?>" 
                             style="width: <?php echo $result['pass'] ? '100' : '0'; ?>%"></div>
                    </div>
                </div>
                
                <div class="signal-note">
                    <?php echo esc_html($result['note']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Post Intelligence Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-post-intelligence-section" id="almaseo-post-intelligence-section">
                <div class="almaseo-field-group" role="region" aria-labelledby="post-intelligence-heading">
                    <label for="almaseo_post_intelligence" class="almaseo-post-intelligence-label" id="post-intelligence-heading">
                        <span aria-hidden="true">🧠</span>
                        <span>Post Intelligence</span>
                        <span class="post-intelligence-tooltip" role="tooltip" aria-label="Get a quick AI-powered summary of your post's topic and tone">ⓘ</span>
                    </label>
                    
                    <div class="post-intelligence-container">
                        <div class="post-intelligence-textarea-container">
                            <textarea id="almaseo_post_intelligence" 
                                      name="almaseo_post_intelligence" 
                                      placeholder="Click 'Refresh Summary' to get an AI-powered insight about your post..."
                                      class="almaseo-textarea post-intelligence-textarea"
                                      readonly></textarea>
                        </div>
                        
                        <div class="post-intelligence-controls">
                            <button type="button" class="post-intelligence-btn" id="refresh-post-intelligence">
                                🔄 Refresh Summary
                            </button>
                        </div>
                        
                        <div class="post-intelligence-loading" id="post-intelligence-loading" style="display: none;">
                            <div class="post-intelligence-loading-spinner"></div>
                            <div class="post-intelligence-loading-text">Analyzing your post content...</div>
                        </div>
                        
                        <div class="post-intelligence-error" id="post-intelligence-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text" id="post-intelligence-error-text"></div>
                        </div>
                        
                        <div class="post-intelligence-tooltip" id="post-intelligence-tooltip" style="display: none;">
                            <div class="tooltip-icon">💡</div>
                            <div class="tooltip-text">Note: Post Intelligence is not auto-inserted into page builder editors. Copy manually if needed.</div>
                        </div>
                        
                        <div class="post-intelligence-timestamp" id="post-intelligence-timestamp" style="display: none;">
                            <small>Last updated: <span id="post-intelligence-last-updated"></span></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Keyword Intelligence Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-keyword-intelligence-section" id="almaseo-keyword-intelligence-section">
                <div class="almaseo-field-group">
                    <label for="almaseo_keyword_intelligence" class="almaseo-keyword-intelligence-label">
                        🔍 Keyword Intelligence (AI Powered)
                        <span class="keyword-intelligence-tooltip" title="Shows search intent, difficulty, and keyword tips">ⓘ</span>
                    </label>
                    
                    <div class="keyword-intelligence-container">
                        <!-- Empty State -->
                        <div class="keyword-intelligence-empty" id="keyword-intelligence-empty">
                            <div class="empty-icon">🔍</div>
                            <div class="empty-text">Enter a Focus Keyword above to get AI-powered keyword intelligence</div>
                            <div class="empty-hint">This will show search intent, difficulty, related terms, and pro tips</div>
                        </div>
                        
                        <!-- Intelligence Content -->
                        <div class="keyword-intelligence-content" id="keyword-intelligence-content" style="display: none;">
                            <div class="intelligence-field">
                                <label class="intelligence-field-label">Search Intent</label>
                                <div class="intelligence-field-value" id="keyword-intent">-</div>
                            </div>
                            
                            <div class="intelligence-field">
                                <label class="intelligence-field-label">Difficulty Estimate</label>
                                <div class="intelligence-field-value" id="keyword-difficulty">-</div>
                            </div>
                            
                            <div class="intelligence-field">
                                <label class="intelligence-field-label">Related Terms</label>
                                <div class="intelligence-field-value" id="keyword-related-terms">-</div>
                            </div>
                            
                            <div class="intelligence-field">
                                <label class="intelligence-field-label">Pro Tip</label>
                                <div class="intelligence-field-value" id="keyword-tip">-</div>
                            </div>
                        </div>
                        
                        <div class="keyword-intelligence-controls">
                            <button type="button" class="keyword-intelligence-btn" id="refresh-keyword-intelligence" disabled>
                                🔄 Refresh Keyword Intelligence
                            </button>
                        </div>
                        
                        <div class="keyword-intelligence-loading" id="keyword-intelligence-loading" style="display: none;">
                            <div class="keyword-intelligence-loading-spinner"></div>
                            <div class="keyword-intelligence-loading-text">Analyzing keyword intelligence...</div>
                        </div>
                        
                        <div class="keyword-intelligence-error" id="keyword-intelligence-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text" id="keyword-intelligence-error-text"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Meta Health Panel -->
            <?php if ($is_connected): ?>
            <div class="almaseo-meta-health-section" id="almaseo-meta-health-section">
                <div class="almaseo-field-group" role="region" aria-labelledby="meta-health-heading">
                    <label class="almaseo-meta-health-label" id="meta-health-heading">
                        <span aria-hidden="true">🧬</span>
                        <span>Meta Health Score</span>
                        <span class="meta-health-tooltip" role="tooltip" aria-label="Real-time AI analysis of your meta tags and SEO health">ⓘ</span>
                    </label>
                    
                    <div class="meta-health-container" aria-live="polite">
                        <!-- Score Display -->
                        <div class="meta-health-content" id="meta-health-content">
                            <div class="meta-score-circle" role="img" aria-label="Meta health score">
                                <div class="score-number" id="meta-score-number">--</div>
                                <div class="score-label">Score</div>
                            </div>
                            
                            <div class="meta-health-feedback">
                                <div class="feedback-text" id="meta-health-feedback-text">
                                    Click "Analyze Metadata" to get your SEO health score and recommendations.
                                </div>
                            </div>
                            
                            <div class="meta-health-controls">
                                <button type="button" class="meta-health-btn" id="analyze-metadata" aria-label="Analyze metadata for SEO health">
                                    <span aria-hidden="true">🔄</span> Analyze Metadata
                                </button>
                            </div>
                        </div>
                        
                        <!-- Loading State -->
                        <div class="meta-health-loading" id="meta-health-loading" style="display: none;" aria-hidden="true">
                            <div class="meta-health-loading-spinner"></div>
                            <div class="meta-health-loading-text">Analyzing your metadata...</div>
                        </div>
                        
                        <!-- Timestamp -->
                        <div class="meta-health-timestamp" id="meta-health-timestamp" style="display: none;">
                            <small>Last analyzed: <span id="meta-health-last-analyzed"></span></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Focus Keyword Suggestions placeholder -->
            <?php if ($is_connected): ?>
            <div class="almaseo-focus-suggestions-section" id="almaseo-focus-suggestions-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-focus-suggestions-label">
                        💡 Focus Keyword Suggestions
                        <span class="focus-suggestions-tooltip" title="AI-powered keyword recommendations">ⓘ</span>
                    </label>
                    
                    <div class="focus-suggestions-container">
                        <!-- Focus keyword suggestions will be populated by JavaScript -->
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
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
                const postId = $btn.data('post-id') || <?php echo $post->ID; ?>;
                
                $btn.prop('disabled', true).find('.dashicons').addClass('spin');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'almaseo_health_recalculate',
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce('almaseo_health_nonce'); ?>'
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
                                    const $signal = $('.almaseo-health-signal').filter(function() {
                                        return $(this).find('.signal-label').text().toLowerCase().includes(signal.replace('_', ' '));
                                    });
                                    
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
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .dashicons.spin {
            animation: spin 1s linear infinite;
        }
        .field-highlight {
            background-color: #fffbcc !important;
            border-color: #dba617 !important;
            box-shadow: 0 0 5px rgba(219, 166, 23, 0.5) !important;
            transition: all 0.3s ease;
        }
        </style>
        
        <!-- Search Console Tab -->
        <div class="almaseo-tab-panel" id="tab-search-console">
            <!-- Tab Header -->
            <div class="almaseo-search-console-header">
                <h2 class="almaseo-search-console-title">Google Search Console</h2>
                <p class="almaseo-search-console-subtitle">Performance metrics from Google Search Console (coming soon).</p>
            </div>
            
            <!-- Date Range Selector (Disabled) -->
            <div class="almaseo-search-console-controls">
                <div class="almaseo-date-range-wrapper">
                    <select class="almaseo-date-range-selector" disabled aria-label="Date range selector">
                        <option value="28" selected>Last 28 days</option>
                        <option value="7">Last 7 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
            </div>
            
            <!-- Placeholder Card -->
            <div class="almaseo-search-console-placeholder">
                <div class="almaseo-placeholder-card">
                    <div class="almaseo-placeholder-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5Z"/>
                            <path d="M12 5L8 21l4-7 4 7-4-16"/>
                        </svg>
                    </div>
                    <h3 class="almaseo-placeholder-heading">Connect to AlmaSEO Dashboard</h3>
                    <p class="almaseo-placeholder-body">
                        To view Google Search Console data here, you'll connect your site from the AlmaSEO Dashboard (feature coming soon).
                    </p>
                    <button type="button" class="almaseo-placeholder-button" disabled>
                        Coming Soon
                    </button>
                    <p class="almaseo-placeholder-note">
                        OAuth connection will be enabled in a future update.
                    </p>
                </div>
            </div>
            
            <!-- Schema Markup Section -->
            <div class="almaseo-schema-section">
                <div class="almaseo-field-group">
                    <label for="almaseo_schema_type" class="almaseo-schema-label">
                        📊 Schema Markup
                    </label>
                    
                    <!-- AI Schema Suggestion -->
                    <?php if ($is_connected && empty($seo_schema_type)): ?>
                    <div class="schema-suggestion-container" id="schema-suggestion-container">
                        <div class="schema-suggestion-loading" id="schema-suggestion-loading">
                            <div class="suggestion-loading-spinner"></div>
                            <div class="suggestion-loading-text">Analyzing content for schema type...</div>
                        </div>
                        <div class="schema-suggestion-content" id="schema-suggestion-content" style="display: none;">
                            <div class="suggestion-icon">💡</div>
                            <div class="suggestion-text">
                                <strong>Suggested Schema Type:</strong> <span id="suggested-schema-type"></span>
                            </div>
                            <div class="suggestion-action">
                                <button type="button" class="use-suggestion-btn" id="use-schema-suggestion">
                                    Use This
                                </button>
                            </div>
                        </div>
                        <div class="schema-suggestion-error" id="schema-suggestion-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text">Could not fetch schema suggestion</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <select id="almaseo_schema_type" 
                            name="almaseo_schema_type" 
                            class="almaseo-schema-select">
                        <option value="">None (default)</option>
                        <option value="Article" <?php selected($seo_schema_type, 'Article'); ?>>Article</option>
                        <option value="BlogPosting" <?php selected($seo_schema_type, 'BlogPosting'); ?>>BlogPosting</option>
                        <option value="NewsArticle" <?php selected($seo_schema_type, 'NewsArticle'); ?>>NewsArticle</option>
                        <option value="Product" <?php selected($seo_schema_type, 'Product'); ?>>Product</option>
                        <option value="Event" <?php selected($seo_schema_type, 'Event'); ?>>Event</option>
                        <option value="FAQPage" <?php selected($seo_schema_type, 'FAQPage'); ?>>FAQPage</option>
                        <option value="HowTo" <?php selected($seo_schema_type, 'HowTo'); ?>>HowTo</option>
                        <option value="LocalBusiness" <?php selected($seo_schema_type, 'LocalBusiness'); ?>>LocalBusiness</option>
                    </select>
                    
                    <?php if (!$is_connected): ?>
                    <div class="schema-advanced-notice">
                        <div class="notice-icon">🔒</div>
                        <div class="notice-text">
                            <strong>Advanced schema types</strong> (FAQPage, HowTo, LocalBusiness) are available with AlmaSEO connection.
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Schema Preview -->
                    <div class="schema-preview-container" id="schema-preview-container" style="display: none;">
                        <div class="schema-preview-header">
                            <strong>JSON-LD Preview</strong>
                            <span class="schema-preview-note">This will be automatically generated for your content</span>
                        </div>
                        <div class="schema-preview-content" id="schema-preview-content">
                            <!-- Preview content will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Schema Analyzer Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-schema-analyzer-section" id="almaseo-schema-analyzer-section" style="display: none;">
                <div class="almaseo-field-group">
                    <label class="almaseo-schema-analyzer-label">
                        🧪 Schema Analyzer (AI Powered)
                        <span class="schema-analyzer-tooltip" title="AI-powered analysis of your schema markup implementation">ⓘ</span>
                    </label>
                    
                    <div class="schema-analyzer-container">
                        <!-- Empty State -->
                        <div class="schema-analyzer-empty" id="schema-analyzer-empty">
                            <div class="empty-icon">🧪</div>
                            <div class="empty-text">Please select a Schema Type to begin.</div>
                            <div class="empty-hint">Choose a schema type above to get AI-powered analysis</div>
                        </div>
                        
                        <!-- Analysis Content -->
                        <div class="schema-analyzer-content" id="schema-analyzer-content" style="display: none;">
                            <div class="schema-analysis-info-box">
                                <div class="analysis-icon">🧠</div>
                                <div class="analysis-text" id="schema-analysis-text">
                                    <!-- Analysis text will be populated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="schema-analyzer-controls">
                                <button type="button" class="schema-analyzer-btn" id="refresh-schema-analysis">
                                    🔄 Reanalyze Schema
                                </button>
                            </div>
                            
                            <div class="schema-analyzer-timestamp" id="schema-analyzer-timestamp">
                                <!-- Timestamp will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="schema-analyzer-loading" id="schema-analyzer-loading" style="display: none;">
                            <div class="schema-analyzer-loading-spinner"></div>
                            <div class="schema-analyzer-loading-text">Analyzing...</div>
                        </div>
                        
                        <div class="schema-analyzer-error" id="schema-analyzer-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text" id="schema-analyzer-error-text"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Meta Health Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-meta-health-section" id="almaseo-meta-health-section" style="display: none;">
                <div class="almaseo-field-group">
                    <label class="almaseo-meta-health-label">
                        🧬 Meta Health (AI Feedback)
                        <span class="meta-health-tooltip" title="Get AI feedback on your SEO title and meta description to improve clicks and clarity">ⓘ</span>
                    </label>
                    
                    <div class="meta-health-container">
                        <!-- Empty State -->
                        <div class="meta-health-empty" id="meta-health-empty">
                            <div class="empty-icon">🧬</div>
                            <div class="empty-text">Please fill in both SEO Title and Meta Description to begin.</div>
                            <div class="empty-hint">Add your SEO title and meta description above to get AI-powered feedback</div>
                        </div>
                        
                        <!-- Analysis Content -->
                        <div class="meta-health-content" id="meta-health-content" style="display: none;">
                            <div class="meta-health-score-block">
                                <div class="meta-score-circle" id="meta-score-circle">
                                    <div class="score-number" id="meta-score-number">0</div>
                                    <div class="score-label">Meta Score</div>
                                </div>
                            </div>
                            
                            <div class="meta-health-feedback">
                                <div class="feedback-text" id="meta-health-feedback-text">
                                    <!-- Feedback text will be populated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="meta-health-controls">
                                <button type="button" class="meta-health-btn" id="analyze-metadata">
                                    🔄 Analyze Metadata
                                </button>
                            </div>
                            
                            <div class="meta-health-timestamp" id="meta-health-timestamp">
                                <!-- Timestamp will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="meta-health-loading" id="meta-health-loading" style="display: none;">
                            <div class="meta-health-loading-spinner"></div>
                            <div class="meta-health-loading-text">Analyzing metadata...</div>
                        </div>
                        
                        <div class="meta-health-error" id="meta-health-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text" id="meta-health-error-text"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Content Aging Section (Consolidated) -->
            <div class="almaseo-content-aging-section" id="almaseo-content-aging-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-content-aging-label">
                        📆 Content Aging
                        <span class="content-aging-tooltip" title="Track content age and set refresh reminders for SEO freshness">ⓘ</span>
                    </label>
                    
                    <div class="content-aging-container">
                        <?php 
                        $post_date = get_the_date('U', $post->ID);
                        $post_modified = get_post_modified_time('U', false, $post->ID);
                        $current_time = current_time('U');
                        $days_old = floor(($current_time - $post_date) / (60 * 60 * 24));
                        $days_since_update = floor(($current_time - $post_modified) / (60 * 60 * 24));
                        
                        // Format age display
                        if ($days_old === 0) {
                            $age_text = 'Published Today';
                        } elseif ($days_old === 1) {
                            $age_text = '1 Day Old';
                        } elseif ($days_old < 30) {
                            $age_text = $days_old . ' Days Old';
                        } elseif ($days_old < 365) {
                            $months = floor($days_old / 30);
                            $age_text = $months . ' Month' . ($months !== 1 ? 's' : '') . ' Old';
                        } else {
                            $years = floor($days_old / 365);
                            $age_text = $years . ' Year' . ($years !== 1 ? 's' : '') . ' Old';
                        }
                        
                        // Get reminder settings
                        $reminder_days = get_post_meta($post->ID, '_almaseo_update_reminder_days', true) ?: '';
                        $reminder_enabled = get_post_meta($post->ID, '_almaseo_update_reminder_enabled', true) ?: '';
                        $reminder_email = get_post_meta($post->ID, '_almaseo_update_reminder_email', true) ?: '';
                        $scheduled_time = get_post_meta($post->ID, '_almaseo_update_reminder_scheduled', true) ?: '';
                        $admin_email = get_option('admin_email');
                        ?>
                        
                        <div class="content-aging-content">
                            <!-- Age Display -->
                            <div class="content-age-display">
                                <div class="age-text">
                                    <strong><?php echo esc_html($age_text); ?></strong>
                                </div>
                                <div class="age-subtitle">
                                    Published: <?php echo get_the_date('F j, Y', $post->ID); ?>
                                </div>
                            </div>
                            
                            <!-- Mark as Refreshed Button -->
                            <div class="content-aging-controls">
                                <button type="button" class="content-aging-btn" id="mark-as-refreshed">
                                    🔁 Mark as Refreshed
                                </button>
                            </div>
                            
                            <!-- Update Reminder Settings -->
                            <div class="update-reminder-section">
                                <div class="reminder-toggle">
                                    <label>
                                        <input type="checkbox" 
                                               id="almaseo_update_reminder_enabled" 
                                               name="almaseo_update_reminder_enabled"
                                               value="1" 
                                               <?php checked($reminder_enabled, '1'); ?> />
                                        Set content update reminder after 
                                        <input type="number" 
                                               id="almaseo_update_reminder_days" 
                                               name="almaseo_update_reminder_days"
                                               value="<?php echo esc_attr($reminder_days ?: '90'); ?>" 
                                               min="1" 
                                               max="365"
                                               style="width: 60px;" /> days
                                    </label>
                                </div>
                                
                                <p class="description" style="margin: 8px 0 0 0; color: #666; font-size: 13px;">
                                    <?php _e('We\'ll remind you via an admin notice on this site.', 'almaseo'); ?>
                                    <?php if ($reminder_email): ?>
                                        <br><?php echo sprintf(__('Also emailing %s', 'almaseo'), '<strong>' . esc_html($admin_email) . '</strong>'); ?>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if ($reminder_enabled && $scheduled_time): ?>
                                <div class="reminder-scheduled-pill" style="margin: 10px 0; display: inline-block; background: #e8f4f8; color: #0073aa; padding: 5px 12px; border-radius: 15px; font-size: 13px;">
                                    <?php 
                                    $scheduled_date = date_i18n(get_option('date_format'), $scheduled_time);
                                    echo sprintf(__('Reminder set for %s', 'almaseo'), $scheduled_date); 
                                    ?>
                                    <a href="#" class="cancel-reminder" data-post-id="<?php echo $post->ID; ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('almaseo_cancel_reminder')); ?>" style="margin-left: 8px; color: #d63638; text-decoration: none;">
                                        <?php _e('Cancel', 'almaseo'); ?>
                                    </a>
                                </div>
                                <script>
                                jQuery(document).on('click', '.cancel-reminder', function(e) {
                                    e.preventDefault();
                                    var $link = jQuery(this);
                                    jQuery.post(ajaxurl, {
                                        action: 'almaseo_cancel_reminder',
                                        nonce: $link.data('nonce'),
                                        post_id: $link.data('post-id')
                                    }, function(response) {
                                        if (response.success) {
                                            $link.closest('.reminder-scheduled-pill').fadeOut();
                                        }
                                    });
                                });
                                </script>
                                <?php endif; ?>
                                
                                <div style="margin: 10px 0 0 0;">
                                    <label>
                                        <input type="checkbox" 
                                               id="almaseo_update_reminder_email" 
                                               name="almaseo_update_reminder_email"
                                               value="1" 
                                               <?php checked($reminder_email, '1'); ?> />
                                        <?php echo sprintf(__('Also email me at %s', 'almaseo'), '<strong>' . esc_html($admin_email) . '</strong>'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- AI Keyword Suggestions Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-ai-keywords-section" id="almaseo-ai-keywords-section" style="display: none;">
                <div class="almaseo-field-group">
                    <label class="almaseo-ai-label">
                        🎯 Keyword Suggestions (Powered by AlmaSEO AI)
                    </label>
                    
                    <?php if ($is_connected): ?>
                    <!-- Connected State: Show AI keyword suggestions placeholder -->
                    <div class="almaseo-ai-keywords-connected">
                        <div class="ai-keywords-message">
                            Your AI assistant will suggest keywords based on your content and audience.
                        </div>
                        <div class="ai-keywords-placeholder">
                            <div class="keyword-item">
                                <span class="keyword-text">marketing tips</span>
                                <span class="keyword-score">95%</span>
                            </div>
                            <div class="keyword-item">
                                <span class="keyword-text">seo checklist</span>
                                <span class="keyword-score">87%</span>
                            </div>
                            <div class="keyword-item">
                                <span class="keyword-text">wordpress plugin</span>
                                <span class="keyword-score">82%</span>
                            </div>
                        </div>
                        <div class="ai-keywords-note">
                            <em>Real AI suggestions will appear here based on your content analysis.</em>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Disconnected State: Show locked message -->
                    <div class="almaseo-ai-keywords-locked">
                        <div class="ai-keywords-locked-message">
                            🔒 Connect to AlmaSEO to unlock intelligent keyword suggestions tailored to your content.
                        </div>
                        <div class="ai-keywords-locked-action">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-playground-connection')); ?>" class="button button-primary">
                                Connect to AlmaSEO
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Internal Link Suggestions Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-internal-links-section" id="almaseo-internal-links-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-internal-links-label">
                        🔗 Internal Link Suggestions (Powered by AlmaSEO)
                    </label>
                    
                    <div class="internal-links-container">
                        <div class="internal-links-loading" id="internal-links-loading">
                            <div class="links-loading-spinner"></div>
                            <div class="links-loading-text">Analyzing your content for internal link opportunities...</div>
                        </div>
                        
                        <div class="internal-links-content" id="internal-links-content" style="display: none;">
                            <div class="internal-links-list" id="internal-links-list">
                                <!-- Links will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="internal-links-error" id="internal-links-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text">Could not fetch internal link suggestions</div>
                        </div>
                        
                        <div class="internal-links-no-results" id="internal-links-no-results" style="display: none;">
                            <div class="no-results-icon">🔍</div>
                            <div class="no-results-text">No strong internal link suggestions were found for this post.</div>
                            <div class="no-results-hint">Try adding more content or specific topics to your post.</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- AI Rewrite Assistant Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-rewrite-section" id="almaseo-rewrite-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-rewrite-label">
                        ✍️ AI Rewrite Assistant
                    </label>
                    
                    <div class="rewrite-container">
                        <div class="rewrite-input-area">
                            <textarea id="rewrite-input" 
                                      class="almaseo-textarea rewrite-textarea" 
                                      placeholder="Paste content to rewrite..."
                                      rows="4"></textarea>
                            
                            <div class="rewrite-controls">
                                <select id="rewrite-type" class="rewrite-type-select">
                                    <option value="paragraph">Paragraph</option>
                                    <option value="title">SEO Title</option>
                                    <option value="description">Meta Description</option>
                                </select>
                                
                                <button type="button" class="rewrite-btn" id="rewrite-submit">
                                    🔁 Rewrite with AlmaSEO
                                </button>
                            </div>
                        </div>
                        
                        <div class="rewrite-loading" id="rewrite-loading" style="display: none;">
                            <div class="rewrite-loading-spinner"></div>
                            <div class="rewrite-loading-text">Rewriting your content...</div>
                        </div>
                        
                        <div class="rewrite-result" id="rewrite-result" style="display: none;">
                            <div class="rewrite-result-header">
                                <strong>Rewritten Content:</strong>
                            </div>
                            <div class="rewrite-result-content" id="rewrite-result-content">
                                <!-- Rewritten content will be populated by JavaScript -->
                            </div>
                            <div class="rewrite-result-actions">
                                <button type="button" class="rewrite-action-btn copy-rewrite-btn" id="copy-rewrite">
                                    📋 Copy
                                </button>
                                <button type="button" class="rewrite-action-btn replace-rewrite-btn" id="replace-rewrite">
                                    ↩️ Replace Input
                                </button>
                                <button type="button" class="rewrite-action-btn clear-rewrite-btn" id="clear-rewrite">
                                    ❌ Clear
                                </button>
                            </div>
                        </div>
                        
                        <div class="rewrite-error" id="rewrite-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text">Could not rewrite content</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Content Brief (AI-Powered) Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-content-brief-section" id="almaseo-content-brief-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-content-brief-label">
                        🧠 Content Brief (AI-Powered)
                    </label>
                    
                    <div class="content-brief-container">
                        <div class="content-brief-textarea-container">
                            <textarea id="content-brief-textarea" 
                                      class="almaseo-textarea content-brief-textarea" 
                                      placeholder="Your AI-generated content brief will appear here..."
                                      rows="8"
                                      readonly></textarea>
                        </div>
                        
                        <div class="content-brief-controls">
                            <button type="button" class="content-brief-btn" id="generate-content-brief">
                                🪄 Generate Brief
                            </button>
                            
                            <div class="content-brief-loading" id="content-brief-loading" style="display: none;">
                                <div class="content-brief-loading-spinner"></div>
                                <div class="content-brief-loading-text">Generating content brief...</div>
                            </div>
                        </div>
                        
                        <div class="content-brief-error" id="content-brief-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text">Could not generate content brief</div>
                        </div>
                        
                        <div class="content-brief-tooltip" id="content-brief-tooltip" style="display: none;">
                            <div class="tooltip-icon">💡</div>
                            <div class="tooltip-text">Note: Content Brief is not auto-inserted into page builder editors. Copy manually if needed.</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- AI FAQ Generator Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-faq-generator-section" id="almaseo-faq-generator-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-faq-generator-label">
                        ❓ AI FAQ Generator
                    </label>
                    
                    <div class="faq-generator-container">
                        <div class="faq-generator-textarea-container">
                            <textarea id="faq-generator-textarea" 
                                      class="almaseo-textarea faq-generator-textarea" 
                                      placeholder="Your AI-generated FAQs will appear here..."
                                      rows="10"
                                      readonly></textarea>
                        </div>
                        
                        <div class="faq-generator-controls">
                            <button type="button" class="faq-generator-btn" id="generate-faqs">
                                ✨ Generate FAQs
                            </button>
                            
                            <div class="faq-generator-loading" id="faq-generator-loading" style="display: none;">
                                <div class="faq-generator-loading-spinner"></div>
                                <div class="faq-generator-loading-text">Generating FAQs...</div>
                            </div>
                        </div>
                        
                        <div class="faq-generator-error" id="faq-generator-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text">Could not generate FAQs</div>
                        </div>
                        
                        <div class="faq-generator-tooltip" id="faq-generator-tooltip" style="display: none;">
                            <div class="tooltip-icon">💡</div>
                            <div class="tooltip-text">Note: FAQs are not auto-inserted into page builder editors. Copy manually if needed.</div>
                        </div>
                        
                        <div class="faq-generator-validation" id="faq-generator-validation" style="display: none;">
                            <div class="validation-icon">📝</div>
                            <div class="validation-text">Content must be at least 100 words to generate meaningful FAQs.</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Saved Prompts & Snippets Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-saved-snippets-section" id="almaseo-saved-snippets-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-saved-snippets-label">
                        💾 Saved Prompts & Snippets
                    </label>
                    
                    <div class="saved-snippets-container">
                        <div class="saved-snippets-header">
                            <button type="button" class="new-snippet-btn" id="new-snippet-btn">
                                + New Snippet
                            </button>
                        </div>
                        
                        <div class="saved-snippets-list" id="saved-snippets-list">
                            <!-- Snippets will be populated by JavaScript -->
                        </div>
                        
                        <div class="saved-snippets-empty" id="saved-snippets-empty">
                            <div class="empty-icon">📝</div>
                            <div class="empty-text">No saved snippets yet</div>
                            <div class="empty-hint">Create your first snippet to get started</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- SEO Scorecard Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-scorecard-section" id="almaseo-scorecard-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-scorecard-label">
                        ✅ SEO Scorecard & Publish Assistant
                    </label>
                    
                    <div class="scorecard-container">
                        <!-- SEO Confidence Score Indicator -->
                        <div class="seo-confidence-score" id="seo-confidence-score" role="region" aria-label="SEO Confidence Score">
                            <div class="confidence-score-title" id="confidence-score-title">SEO Confidence Score</div>
                            <div class="confidence-score-ring" role="img" aria-labelledby="confidence-score-title">
                                <svg class="confidence-ring" width="120" height="120" viewBox="0 0 120 120" aria-hidden="true">
                                    <circle class="confidence-ring-bg" cx="60" cy="60" r="54" stroke-width="8" fill="none"/>
                                    <circle class="confidence-ring-progress" cx="60" cy="60" r="54" stroke-width="8" fill="none" 
                                            stroke-dasharray="339.292" stroke-dashoffset="339.292" 
                                            transform="rotate(-90 60 60)"/>
                                </svg>
                                <div class="confidence-score-percentage" id="confidence-score-percentage" aria-live="polite">0%</div>
                            </div>
                            <div class="confidence-score-label" id="confidence-score-label" aria-live="polite">Analyzing...</div>
                        </div>
                        
                        <div class="scorecard-header" id="scorecard-header">
                            <div class="scorecard-summary">
                                <span class="scorecard-title">Publish Checklist</span>
                                <span class="scorecard-status" id="scorecard-status">Analyzing...</span>
                            </div>
                        </div>
                        
                        <div class="scorecard-checklist" id="scorecard-checklist">
                            <div class="scorecard-item" data-check="seo-title">
                                <div class="scorecard-icon" id="seo-title-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">SEO Title</span>
                                    <span class="scorecard-tooltip">Check if SEO title field is filled</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="meta-description">
                                <div class="scorecard-icon" id="meta-description-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Meta Description</span>
                                    <span class="scorecard-tooltip">Check if meta description field is filled</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="focus-keywords">
                                <div class="scorecard-icon" id="focus-keywords-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Focus Keyword(s)</span>
                                    <span class="scorecard-tooltip">Check if keyword suggestions exist or field has text</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="internal-links">
                                <div class="scorecard-icon" id="internal-links-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Internal Links</span>
                                    <span class="scorecard-tooltip">At least 1 Alma-inserted internal link</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="schema-type">
                                <div class="scorecard-icon" id="schema-type-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Schema Type</span>
                                    <span class="scorecard-tooltip">Any schema other than "None" selected</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="ai-rewrite">
                                <div class="scorecard-icon" id="ai-rewrite-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">AI Rewrite</span>
                                    <span class="scorecard-tooltip">Optional: Check if user has submitted rewrite</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="content-length">
                                <div class="scorecard-icon" id="content-length-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Content Length</span>
                                    <span class="scorecard-tooltip">Warn if content < 300 words</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="reoptimization">
                                <div class="scorecard-icon" id="reoptimization-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Reoptimization Alert</span>
                                    <span class="scorecard-tooltip">Show if reoptimize flag is TRUE</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="scorecard-footer" id="scorecard-footer" style="display: none;">
                            <div class="scorecard-passed" id="scorecard-passed" style="display: none;">
                                <div class="passed-icon">🎉</div>
                                <div class="passed-text">Publish Checklist Passed!</div>
                                <div class="passed-subtext">Your post is ready for publication.</div>
                            </div>
                            
                            <div class="scorecard-warning" id="scorecard-warning" style="display: none;">
                                <div class="warning-icon">⚠️</div>
                                <div class="warning-text">Some improvements recommended</div>
                                <div class="warning-subtext">Consider addressing the items above before publishing.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- SEO Playground Tips Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-seo-tips-section" id="almaseo-seo-tips-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-seo-tips-label" for="seo-tips-toggle">
                        💡 AlmaSEO SEO Playground Tips (From Alma)
                        <span class="tips-tooltip" title="Helpful SEO tips to improve your workflow and results.">ⓘ</span>
                    </label>
                    
                    <div class="seo-tips-container">
                        <div class="seo-tips-header">
                            <button type="button" class="seo-tips-toggle" id="seo-tips-toggle" aria-expanded="false">
                                <span class="toggle-icon">▼</span>
                                <span class="toggle-text">Show Tips</span>
                            </button>
                        </div>
                        
                        <div class="seo-tips-content" id="seo-tips-content" style="display: none;">
                            <div class="seo-tips-display">
                                <div class="tip-content" id="tip-content">
                                    <!-- Tip content will be populated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="seo-tips-controls">
                                <button type="button" class="shuffle-tip-btn" id="shuffle-tip-btn">
                                    🔀 Shuffle Tip
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
                        __('Schema helps search engines understand your content type (e.g., Article, Product). Add types that match the page.', 'almaseo'),
                        __('Avoid duplicate schema from multiple plugins on the same page.', 'almaseo')
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
                <a href="#tab-unlock-features" class="button button-secondary almaseo-tab-link" style="white-space: nowrap;">Connect Now →</a>
            </div>
            <?php else: 
                $last_sync = get_option('almaseo_last_sync', '');
                $sync_text = $last_sync ? human_time_diff(strtotime($last_sync)) . ' ago' : 'Never';
            ?>
            <div class="almaseo-connection-status" style="margin: 20px 0; padding: 10px 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 20px; display: inline-block; color: #155724; font-size: 13px;">
                ✓ Connected to AlmaSEO • Last sync: <?php echo esc_html($sync_text); ?>
            </div>
            <?php endif; ?>
            
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
                        <!-- Index/NoIndex Pair (Mutually Exclusive) -->
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
                        
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_noindex" 
                                       name="almaseo_robots_noindex" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_index', true) === 'noindex'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">NoIndex</span>
                            </label>
                            <p class="option-description">Hide this page from search results</p>
                        </div>
                        
                        <!-- Follow/NoFollow Pair (Mutually Exclusive) -->
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
                                       id="almaseo_robots_nofollow" 
                                       name="almaseo_robots_nofollow" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_follow', true) === 'nofollow'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">NoFollow</span>
                            </label>
                            <p class="option-description">Tell search engines not to follow links</p>
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
                               value="<?php echo esc_attr(get_post_meta($post->ID, '_almaseo_canonical_url', true) ?: get_permalink($post->ID)); ?>" 
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
                        <select id="almaseo_schema_type" 
                                name="almaseo_schema_type" 
                                class="almaseo-select">
                            <?php 
                            $current_schema = get_post_meta($post->ID, '_almaseo_schema_type', true) ?: 'Article';
                            ?>
                            <?php 
                            $is_connected = seo_playground_is_alma_connected();
                            $schema_options = array(
                                array('value' => 'Article', 'label' => 'Article (BlogPosting) (Free)', 'locked' => false),
                                array('value' => 'FAQPage', 'label' => 'FAQPage', 'locked' => !$is_connected),
                                array('value' => 'HowTo', 'label' => 'HowTo', 'locked' => !$is_connected),
                                array('value' => 'LocalBusiness', 'label' => 'LocalBusiness', 'locked' => !$is_connected)
                            );
                            
                            foreach ($schema_options as $option): ?>
                                <option value="<?php echo esc_attr($option['value']); ?>" 
                                        <?php selected($current_schema, $option['value']); ?>
                                        <?php echo $option['locked'] ? 'disabled aria-disabled="true" class="is-locked"' : ''; ?>
                                        data-locked="<?php echo $option['locked'] ? '1' : '0'; ?>">
                                    <?php echo esc_html($option['label']); ?><?php echo $option['locked'] ? ' 🔒' : ''; ?>
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
                        
                        <!-- Locked schema upsell notice -->
                        <div id="schema-locked-notice" style="display: none; margin-top: 10px; padding: 10px; background: #fff8e5; border-left: 3px solid #dba617; border-radius: 3px;">
                            <small style="color: #2c3338;">
                                Advanced schema types are available with an AlmaSEO connection. 
                                <a href="#tab-unlock-features" class="almaseo-tab-link">Connect now →</a>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Collapsible Schema Preview -->
                    <div class="almaseo-collapsible" style="margin-top: 20px;">
                        <button type="button" class="almaseo-collapsible-toggle" data-target="schema-jsonld-preview" style="width: 100%; text-align: left; padding: 10px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 3px; cursor: pointer;">
                            <span class="dashicons dashicons-arrow-down-alt2" style="margin-right: 5px;"></span>
                            Preview: Article JSON-LD
                        </button>
                        <div id="schema-jsonld-preview" class="almaseo-collapsible-content" style="display: none; margin-top: 10px; padding: 15px; background: #2c3338; border-radius: 3px;">
                            <div style="position: relative;">
                                <button type="button" class="copy-json-btn" style="position: absolute; top: 5px; right: 5px; padding: 5px 10px; background: #2271b1; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">Copy JSON</button>
                                <pre id="schema-json-preview" style="color: #50fa7b; font-family: 'Courier New', monospace; font-size: 12px; overflow-x: auto; margin: 0;">Loading preview...</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Schema (Pro) -->
                    <div class="almaseo-field-group" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dcdcde;">
                        <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #1d2327;">
                            Advanced Schema (Pro)
                        </h4>

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
                            $is_faqpage = get_post_meta($post->ID, '_almaseo_schema_is_faqpage', true);
                            $is_howto = get_post_meta($post->ID, '_almaseo_schema_is_howto', true);
                            $disable_advanced = get_post_meta($post->ID, '_almaseo_schema_disable', true);
                            ?>

                            <div class="almaseo-field-group">
                                <label for="almaseo_schema_primary_type">Primary Schema Type</label>
                                <select id="almaseo_schema_primary_type"
                                        name="almaseo_schema_primary_type"
                                        class="almaseo-select">
                                    <option value=""><?php _e('Use default for this post type', 'almaseo'); ?></option>
                                    <option value="Article" <?php selected($primary_type, 'Article'); ?>>Article</option>
                                    <option value="BlogPosting" <?php selected($primary_type, 'BlogPosting'); ?>>BlogPosting</option>
                                    <option value="NewsArticle" <?php selected($primary_type, 'NewsArticle'); ?>>NewsArticle</option>
                                    <option value="FAQPage" <?php selected($primary_type, 'FAQPage'); ?>>FAQPage</option>
                                    <option value="HowTo" <?php selected($primary_type, 'HowTo'); ?>>HowTo</option>
                                    <option value="Service" <?php selected($primary_type, 'Service'); ?>>Service</option>
                                    <option value="LocalBusiness" <?php selected($primary_type, 'LocalBusiness'); ?>>LocalBusiness</option>
                                </select>
                                <p class="field-hint">Override the global default for this specific post</p>
                            </div>

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
                            $lb_hours_raw  = get_post_meta($post->ID, '_almaseo_lb_hours', true);
                            $lb_hours      = is_array($lb_hours_raw) ? $lb_hours_raw : ( $lb_hours_raw ? json_decode($lb_hours_raw, true) : array() );
                            if ( ! is_array($lb_hours) ) $lb_hours = array();
                            $show_lb = ( $primary_type === 'LocalBusiness' || $current_schema === 'LocalBusiness' );
                            ?>
                            <div id="almaseo-localbusiness-fields" style="<?php echo $show_lb ? '' : 'display:none;'; ?> margin-top: 15px; padding: 15px; background: #f9fafb; border: 1px solid #e2e4e7; border-radius: 6px;">
                                <h4 style="margin: 0 0 12px 0; font-size: 13px; font-weight: 600; color: #1d2327;">Local Business Details</h4>

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

                            <div class="almaseo-field-group">
                                <label>
                                    <input type="checkbox"
                                           name="almaseo_schema_is_faqpage"
                                           value="1"
                                           <?php checked($is_faqpage, true); ?>>
                                    <?php _e('Treat this page as FAQPage (when it contains Q&A content)', 'almaseo'); ?>
                                </label>
                            </div>

                            <div class="almaseo-field-group">
                                <label>
                                    <input type="checkbox"
                                           name="almaseo_schema_is_howto"
                                           value="1"
                                           <?php checked($is_howto, true); ?>>
                                    <?php _e('Treat this page as HowTo (step-by-step instructions)', 'almaseo'); ?>
                                </label>
                            </div>

                            <div class="almaseo-field-group">
                                <label>
                                    <input type="checkbox"
                                           name="almaseo_schema_disable"
                                           value="1"
                                           <?php checked($disable_advanced, true); ?>>
                                    <?php _e('Disable Advanced Schema for this post', 'almaseo'); ?>
                                </label>
                                <p class="field-hint">Turn off advanced schema output while keeping basic schema</p>
                            </div>
                        <?php endif; ?>
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
                                    data-has-featured-image="<?php echo has_post_thumbnail($post->ID) ? 'true' : 'false'; ?>">
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
                        <h4 style="margin: 0 0 12px; font-size: 14px; font-weight: 600; color: #1d2327;">
                            <?php _e( 'Social Sharing Preview', 'almaseo' ); ?>
                        </h4>

                        <?php
                        $sp_title       = get_post_meta( $post->ID, '_almaseo_og_title', true ) ?: ( $seo_title ?: get_the_title( $post->ID ) );
                        $sp_description = get_post_meta( $post->ID, '_almaseo_og_description', true ) ?: ( $seo_description ?: wp_trim_words( $post->post_content, 25 ) );
                        $sp_image       = get_post_meta( $post->ID, '_almaseo_og_image', true ) ?: get_the_post_thumbnail_url( $post->ID, 'large' );
                        $sp_domain      = wp_parse_url( home_url(), PHP_URL_HOST );
                        $sp_tw_card     = get_post_meta( $post->ID, '_almaseo_twitter_card', true ) ?: 'summary_large_image';
                        ?>

                        <!-- Facebook Preview -->
                        <div class="almaseo-fb-preview" style="max-width: 500px; border: 1px solid #dadde1; border-radius: 3px; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #fff; margin-bottom: 16px;">
                            <div id="fb-preview-image" style="width: 100%; aspect-ratio: 1.91/1; background: #e4e6eb url('<?php echo esc_url( $sp_image ); ?>') center/cover no-repeat; position: relative;">
                                <?php if ( empty( $sp_image ) ) : ?>
                                <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #8a8d91; font-size: 13px;">
                                    <?php _e( 'No image set', 'almaseo' ); ?>
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
                        <div class="almaseo-twitter-preview" id="twitter-preview-container" data-card-type="<?php echo esc_attr( $sp_tw_card ); ?>" style="max-width: 500px; border: 1px solid #cfd9de; border-radius: 16px; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #fff;">
                            <?php if ( $sp_tw_card === 'summary_large_image' ) : ?>
                            <!-- Large Image Card -->
                            <div id="tw-preview-image" style="width: 100%; aspect-ratio: 2/1; background: #eff3f4 url('<?php echo esc_url( $sp_image ); ?>') center/cover no-repeat; position: relative;">
                                <?php if ( empty( $sp_image ) ) : ?>
                                <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #8a8d91; font-size: 13px;">
                                    <?php _e( 'No image set', 'almaseo' ); ?>
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
                            <?php _e( 'Previews are approximate. Actual appearance may vary by platform.', 'almaseo' ); ?>
                        </p>
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

                        var postTitle = <?php echo wp_json_encode( get_the_title( $post->ID ) ); ?>;
                        var postExcerpt = <?php echo wp_json_encode( wp_trim_words( $post->post_content, 25 ) ); ?>;
                        var featuredImage = <?php echo wp_json_encode( get_the_post_thumbnail_url( $post->ID, 'large' ) ?: '' ); ?>;

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
                            $date = date('F j, Y g:i A', $entry['timestamp']);
                            ?>
                            <div class="history-entry" data-index="<?php echo $index; ?>">
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
                                            data-index="<?php echo $index; ?>"
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
            
            <!-- Schema Type Selector -->
            <div class="almaseo-field-group">
                <label for="almaseo_schema_type">Schema Type</label>
                <select id="almaseo_schema_type" name="almaseo_schema_type" class="almaseo-select">
                    <option value="">None</option>
                    <option value="Article" <?php selected($seo_schema_type, 'Article'); ?>>Article</option>
                    <option value="BlogPosting" <?php selected($seo_schema_type, 'BlogPosting'); ?>>Blog Post</option>
                    <option value="NewsArticle" <?php selected($seo_schema_type, 'NewsArticle'); ?>>News Article</option>
                    <option value="Product" <?php selected($seo_schema_type, 'Product'); ?>>Product</option>
                    <option value="Recipe" <?php selected($seo_schema_type, 'Recipe'); ?>>Recipe</option>
                    <option value="Review" <?php selected($seo_schema_type, 'Review'); ?>>Review</option>
                    <option value="FAQPage" <?php selected($seo_schema_type, 'FAQPage'); ?>>FAQ Page</option>
                    <option value="HowTo" <?php selected($seo_schema_type, 'HowTo'); ?>>How-To Guide</option>
                </select>
            </div>
            
            <!-- Troubleshooting Schema Issues Help Section -->
            <div class="almaseo-card" style="margin-top: 20px; background: #f8f9fa; border: 1px solid #e0e0e0;">
                <div class="almaseo-card-header" style="background: linear-gradient(135deg, #fff8e5 0%, #fffef5 100%); border-bottom: 1px solid #f0f0f1;">
                    <h3 class="almaseo-card-title">
                        <span class="card-icon">❓</span>
                        Troubleshooting Schema Issues
                    </h3>
                </div>
                <div class="almaseo-card-body">
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
                            → Enable Exclusive Schema Mode in the <a href="<?php echo admin_url('admin.php?page=almaseo-settings'); ?>">Settings</a>
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

            <!-- Cornerstone Content -->
            <div class="almaseo-field-group" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #dcdcde;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="almaseo_is_cornerstone" value="1"
                           <?php checked( get_post_meta( $post->ID, '_almaseo_is_cornerstone', true ) ); ?> />
                    <span class="dashicons dashicons-star-filled" style="color: #dba617;"></span>
                    <strong><?php _e( 'Cornerstone Content', 'almaseo' ); ?></strong>
                </label>
                <p class="field-hint"><?php _e( 'Mark this as a key page that should be prioritized in your SEO strategy.', 'almaseo' ); ?></p>
                <?php
                $cs_suggested = get_post_meta( $post->ID, '_almaseo_cornerstone_suggested', true );
                $cs_current   = get_post_meta( $post->ID, '_almaseo_is_cornerstone', true );
                if ( $cs_suggested && ! $cs_current ) :
                    $cs_score  = get_post_meta( $post->ID, '_almaseo_cornerstone_score', true );
                    $cs_reason = get_post_meta( $post->ID, '_almaseo_cornerstone_reason', true );
                ?>
                <div style="margin-top: 8px; padding: 8px 12px; background: linear-gradient(135deg, #f0f4ff 0%, #f8f9ff 100%); border: 1px solid #d0d5ff; border-radius: 4px; font-size: 12px;">
                    <span style="font-weight: 600; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">AI</span>
                    <?php printf( __( 'AlmaSEO suggests marking this as cornerstone content (score: %s/100).', 'almaseo' ), '<strong>' . intval( $cs_score ) . '</strong>' ); ?>
                    <?php if ( $cs_reason ) : ?>
                    <br><span style="color: #666;"><?php echo esc_html( $cs_reason ); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- End Schema & Meta Tab -->
        
        <!-- AI Tools Tab -->
        <div class="almaseo-tab-panel" id="tab-ai-tools">
            <?php if (!$is_connected || $user_tier === 'free'): ?>
            <!-- Upsell Screen for Unconnected/Free Users -->
            <div class="almaseo-ai-upsell-screen">
                <div class="upsell-hero">
                    <h2>🚀 Unlock AI-Powered SEO Tools</h2>
                    <p class="upsell-subtitle">
                        <?php if (!$is_connected): ?>
                        Connect to AlmaSEO to access powerful AI features that will transform your content optimization workflow.
                        <?php else: ?>
                        You're on the <strong>Free tier</strong>. Upgrade to Pro or Max to unlock AI-powered features that will transform your content optimization.
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="locked-features-grid">
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>✏️ AI Rewrite</h3>
                        <p>Instantly rewrite and improve your content with AI that understands SEO best practices.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                    
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>💡 AI Title Generator</h3>
                        <p>Generate compelling, SEO-optimized titles that drive clicks and rankings.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                    
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>📝 AI Description Generator</h3>
                        <p>Create perfect meta descriptions that improve CTR and search visibility.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                    
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>🎯 Smart Schema Suggestions</h3>
                        <p>Get AI-powered schema markup recommendations based on your content.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                    
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>🔍 Keyword Boost</h3>
                        <p>Advanced keyword research with competition analysis and search intent detection.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                    
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>📊 Content Intelligence</h3>
                        <p>Deep content analysis with readability scores and optimization suggestions.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                </div>
                
                <div class="upsell-benefits">
                    <h3>✨ Why Connect to AlmaSEO?</h3>
                    <ul class="benefits-list">
                        <li>✅ <strong>Free Trial Available</strong> - Try all features risk-free</li>
                        <li>✅ <strong>Instant Setup</strong> - Connect in less than 60 seconds</li>
                        <li>✅ <strong>No Credit Card Required</strong> - Start optimizing immediately</li>
                        <li>✅ <strong>Unlimited AI Generations</strong> - No usage limits during trial</li>
                        <li>✅ <strong>Priority Support</strong> - Get help when you need it</li>
                    </ul>
                </div>
                
                <div class="upsell-cta-section">
                    <div class="cta-buttons">
                        <a href="<?php echo admin_url('admin.php?page=seo-playground-connection'); ?>" class="cta-btn-primary">
                            🔓 Connect to AlmaSEO (Free Trial)
                        </a>
                        <a href="https://almaseo.com/pricing?utm_source=plugin&utm_medium=ai_tools&utm_campaign=upsell" target="_blank" class="cta-btn-secondary">
                            💡 Learn More About Pricing
                        </a>
                    </div>
                    
                    <div class="already-connected">
                        <p>🧠 Already connected? <a href="<?php echo admin_url('admin.php?page=seo-playground-connection'); ?>">Check your connection status</a></p>
                    </div>
                </div>
                
                <div class="upsell-testimonial">
                    <blockquote>
                        "AlmaSEO's AI tools saved me hours of work. The content suggestions are spot-on and my rankings have improved significantly!"
                        <cite>- Sarah M., Content Manager</cite>
                    </blockquote>
                </div>
            </div>
            <?php elseif ($can_use_ai): ?>
            <!-- AI Rewrite Assistant Panel for Pro/Max Users -->
            <?php if ($user_tier === 'pro'): ?>
            <div class="ai-usage-indicator">
                <span class="usage-label">AI Generations Remaining:</span>
                <span class="usage-count"><?php echo $generations_info['remaining']; ?>/<?php echo $generations_info['total']; ?></span>
                <div class="usage-bar">
                    <div class="usage-progress" style="width: <?php echo ($generations_info['remaining'] / $generations_info['total']) * 100; ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="ai-rewrite-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="ai-rewrite-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="ai-rewrite-heading">
                            <span class="panel-icon" aria-hidden="true">✍️</span>
                            <span>AI Rewrite Assistant</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Rewrite your content with AI for better engagement">ⓘ</span>
                        </div>
                    </div>
                    
                    <div class="ai-rewrite-input-section">
                        <textarea id="ai-rewrite-input" 
                                  class="ai-rewrite-textarea" 
                                  placeholder="Paste or type content you want to rewrite..."
                                  aria-label="Content to rewrite"
                                  rows="5"></textarea>
                        
                        <div class="ai-rewrite-controls">
                            <select id="ai-rewrite-tone" class="ai-rewrite-tone-select" aria-label="Select rewrite tone">
                                <option value="professional">Professional</option>
                                <option value="casual">Casual</option>
                                <option value="friendly">Friendly</option>
                                <option value="formal">Formal</option>
                                <option value="creative">Creative</option>
                            </select>
                            
                            <button type="button" class="ai-rewrite-btn" id="ai-rewrite-submit" aria-label="Rewrite content">
                                <span class="btn-icon" aria-hidden="true">🔄</span>
                                <span>Rewrite with AI</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="ai-rewrite-output-section" id="ai-rewrite-output-section">
                        <div class="ai-rewrite-output-label">
                            <span aria-hidden="true">✨</span>
                            <span>Rewritten Content</span>
                        </div>
                        <div class="ai-rewrite-output">
                            <div class="ai-rewrite-output-text" id="ai-rewrite-output-text" aria-live="polite"></div>
                        </div>
                        <div class="ai-rewrite-output-actions">
                            <button type="button" class="ai-output-action-btn" id="ai-copy-rewrite" aria-label="Copy rewritten content">
                                <span aria-hidden="true">📋</span> Copy
                            </button>
                            <button type="button" class="ai-output-action-btn primary" id="ai-apply-rewrite" aria-label="Apply rewritten content">
                                <span aria-hidden="true">✔️</span> Apply
                            </button>
                            <button type="button" class="ai-output-action-btn" id="ai-regenerate-rewrite" aria-label="Regenerate content">
                                <span aria-hidden="true">🔄</span> Regenerate
                            </button>
                            <button type="button" class="ai-output-action-btn" id="ai-clear-rewrite" aria-label="Clear results">
                                <span aria-hidden="true">❌</span> Clear
                            </button>
                        </div>
                    </div>
                    
                    <div class="ai-rewrite-loading" style="display: none;" aria-hidden="true">
                        <div class="ai-rewrite-spinner"></div>
                        <div class="ai-rewrite-loading-text">Rewriting your content...</div>
                    </div>
                    
                    <div class="ai-rewrite-error" style="display: none;" role="alert">
                        <span class="ai-rewrite-error-icon" aria-hidden="true">⚠️</span>
                        <span class="ai-rewrite-error-text"></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Title Generator Panel -->
            <?php if ($is_connected): ?>
            <div class="ai-title-generator-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="ai-title-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="ai-title-heading">
                            <span class="panel-icon" aria-hidden="true">📝</span>
                            <span>AI Title Generator</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Generate compelling SEO titles">ⓘ</span>
                        </div>
                    </div>
                    
                    <div class="ai-title-input-section">
                        <input type="text" 
                               id="ai-title-context" 
                               class="ai-title-context-input" 
                               placeholder="Enter topic or keywords (optional - will use post content if empty)"
                               aria-label="Title generation context">
                        
                        <button type="button" class="ai-title-generate-btn" id="ai-generate-titles" aria-label="Generate title suggestions">
                            <span aria-hidden="true">✨</span>
                            <span>Generate Titles</span>
                        </button>
                    </div>
                    
                    <div class="ai-title-suggestions" id="ai-title-suggestions">
                        <div class="ai-title-suggestions-label">Generated Titles</div>
                        <div class="ai-title-suggestions-list" id="ai-title-suggestions-list" role="list">
                            <!-- Title suggestions will be populated here -->
                        </div>
                    </div>
                    
                    <div class="ai-title-loading" style="display: none;" aria-hidden="true">
                        <div class="ai-rewrite-spinner"></div>
                        <div class="ai-rewrite-loading-text">Generating title suggestions...</div>
                    </div>
                    
                    <div class="ai-title-error" style="display: none;" role="alert">
                        <span aria-hidden="true">⚠️</span>
                        <span class="error-text"></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Meta Description Generator Panel -->
            <?php if ($is_connected): ?>
            <div class="ai-meta-generator-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="ai-meta-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="ai-meta-heading">
                            <span class="panel-icon" aria-hidden="true">📄</span>
                            <span>AI Meta Description Generator</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Generate optimized meta descriptions">ⓘ</span>
                        </div>
                    </div>
                    
                    <div class="ai-meta-input-section">
                        <input type="text" 
                               id="ai-meta-keywords" 
                               class="ai-meta-keywords-input" 
                               placeholder="Target keywords (optional)"
                               aria-label="Target keywords for meta description">
                        
                        <button type="button" class="ai-meta-generate-btn" id="ai-generate-meta" aria-label="Generate meta description">
                            <span aria-hidden="true">✨</span>
                            <span>Generate Meta Description</span>
                        </button>
                    </div>
                    
                    <div class="ai-meta-output" id="ai-meta-output">
                        <div class="ai-meta-output-text" id="ai-meta-output-text" aria-live="polite"></div>
                        <div class="ai-meta-char-count">
                            <span class="ai-meta-char-indicator"></span>
                            <span>Characters: <span id="ai-meta-char-count">0</span>/160</span>
                        </div>
                        <button type="button" class="ai-output-action-btn primary" id="ai-apply-meta" aria-label="Apply meta description">
                            <span aria-hidden="true">✔️</span> Apply to Meta Description
                        </button>
                    </div>
                    
                    <div class="ai-meta-loading" style="display: none;" aria-hidden="true">
                        <div class="ai-rewrite-spinner"></div>
                        <div class="ai-rewrite-loading-text">Generating meta description...</div>
                    </div>
                    
                    <div class="ai-meta-error" style="display: none;" role="alert">
                        <span aria-hidden="true">⚠️</span>
                        <span class="error-text"></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- AI Suggestions Panel -->
            <?php if ($is_connected): ?>
            <div class="ai-suggestions-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="ai-suggestions-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="ai-suggestions-heading">
                            <span class="panel-icon" aria-hidden="true">💡</span>
                            <span>AI SEO Suggestions</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Get AI-powered SEO recommendations">ⓘ</span>
                        </div>
                        <button type="button" class="ai-refresh-btn" id="ai-refresh-suggestions" aria-label="Refresh suggestions">
                            <span aria-hidden="true">🔄</span> Refresh
                        </button>
                    </div>
                    
                    <div class="ai-suggestions-grid" id="ai-suggestions-grid" role="list">
                        <!-- AI suggestions will be populated here -->
                    </div>
                    
                    <div class="ai-suggestions-loading" style="display: none;" aria-hidden="true">
                        <div class="ai-rewrite-spinner"></div>
                        <div class="ai-rewrite-loading-text">Analyzing your content...</div>
                    </div>
                    
                    <div class="ai-empty-state" id="ai-suggestions-empty" style="display: none;">
                        <div class="ai-empty-icon" aria-hidden="true">🤖</div>
                        <div class="ai-empty-title">No Suggestions Yet</div>
                        <div class="ai-empty-description">Add more content to get AI-powered SEO suggestions</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Usage Indicator -->
            <div class="ai-usage-indicator" role="status" aria-label="Content usage statistics">
                <div class="ai-word-count">
                    <span aria-hidden="true">📊</span>
                    Words: <span class="ai-word-count-value">0</span>
                </div>
                <div class="ai-token-usage">
                    <span aria-hidden="true">🎯</span>
                    Tokens: <span class="ai-token-count-value">0</span>
                    <div class="ai-usage-bar">
                        <div class="ai-usage-bar-fill" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End AI Tools Tab -->

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
            <input type="hidden" id="almaseo_nonce" value="<?php echo wp_create_nonce('almaseo_nonce'); ?>">
            <input type="hidden" id="almaseo-can-edit" value="<?php echo current_user_can('edit_posts') ? 'true' : 'false'; ?>">
        </div>
        <!-- End Notes & History Tab -->
        
        <?php if (!$is_connected): ?>
        <!-- Unlock AI Features Tab -->
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
                                AlmaSEO isn't just an AI toolkit — it's your SEO command center. Write high-authority blog posts, landing pages, and articles with a click, and publish them instantly to WordPress. Then manage, optimize, and track everything from your AlmaSEO dashboard.
                            </p>
                            <div class="hero-cta-group">
                                <a href="<?php echo admin_url('admin.php?page=seo-playground-connection'); ?>" class="hero-cta-primary">
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
                    <h2 class="section-title">Premium AI Features Waiting for You</h2>
                    
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
                            <h3 class="feature-title">Meta Description AI</h3>
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
                                <h3>Instant AI Access</h3>
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
                                for our main keywords in just 6 weeks. The AI tools save us hours every day and the results 
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
                                <a href="<?php echo admin_url('admin.php?page=seo-playground-connection'); ?>" class="footer-cta-primary">
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
            AlmaSEO SEO Playground v<?php echo ALMASEO_PLUGIN_VERSION; ?>
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
            $('.almaseo-llm-status').text('Loading analysis...');
            $('.almaseo-llm-summary-text, .almaseo-llm-entities-list, .almaseo-llm-ambiguities-list, .almaseo-llm-scores, .almaseo-llm-insights').addClass('almaseo-llm-loading');

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
                    xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
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

            // Summary
            var $summary = $('.almaseo-llm-summary-text');
            $summary.removeClass('almaseo-llm-loading');
            if (data.summary) {
                $summary.html('<p>' + escapeHtml(data.summary) + '</p>');
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

            // Pro Features: Scores
            var $scores = $('.almaseo-llm-scores');
            if ($scores.length) {
                $scores.removeClass('almaseo-llm-loading');

                var llmScore = data.llm_score || 0;
                var answerabilityScore = data.answerability_score || 0;

                var llmColor = getScoreColor(llmScore);
                var answerColor = getScoreColor(answerabilityScore);

                var scoresHtml = '<div class="almaseo-llm-score-item">' +
                    '<div class="almaseo-llm-score-label">LLM Optimization Score</div>' +
                    '<div class="almaseo-llm-score-value" style="color: ' + llmColor + '">' + llmScore + '</div>' +
                    '<div class="almaseo-llm-score-bar"><div class="almaseo-llm-score-fill" style="width: ' + llmScore + '%; background: ' + llmColor + '"></div></div>' +
                    '</div>' +
                    '<div class="almaseo-llm-score-item">' +
                    '<div class="almaseo-llm-score-label">Answerability Score</div>' +
                    '<div class="almaseo-llm-score-value" style="color: ' + answerColor + '">' + answerabilityScore + '</div>' +
                    '<div class="almaseo-llm-score-bar"><div class="almaseo-llm-score-fill" style="width: ' + answerabilityScore + '%; background: ' + answerColor + '"></div></div>' +
                    '</div>';

                // Status message
                if (llmScore >= 75 && answerabilityScore >= 75) {
                    scoresHtml += '<p class="almaseo-llm-score-status" style="color: #10b981;"><span class="dashicons dashicons-yes-alt"></span> Great for AI answers!</p>';
                } else if (llmScore >= 50 || answerabilityScore >= 50) {
                    scoresHtml += '<p class="almaseo-llm-score-status" style="color: #f59e0b;"><span class="dashicons dashicons-warning"></span> Needs improvement</p>';
                } else {
                    scoresHtml += '<p class="almaseo-llm-score-status" style="color: #dc3232;"><span class="dashicons dashicons-dismiss"></span> Needs structure</p>';
                }

                $scores.html(scoresHtml);
            }

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

            // Render sections if available
            if (data.sections && data.sections.length > 0) {
                renderLLMSections(data.sections);
            }
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

            var sectionsHtml = '';

            sections.forEach(function(section, index) {
                var typeLabel = getSectionTypeLabel(section.type);
                var clarityColor = getScoreColor(section.clarity_score);
                var llmColor = getScoreColor(section.llm_readiness);
                var answerColor = getScoreColor(section.answerability);

                sectionsHtml += '<div class="almaseo-llm-section-item">';
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
                sectionsHtml += '<span class="label">Clarity:</span>';
                sectionsHtml += '<span class="value" style="color: ' + clarityColor + ';">' + section.clarity_score + '</span>';
                sectionsHtml += '</div>';

                // LLM Readiness & Answerability (Pro only or show locked)
                if (isPro) {
                    sectionsHtml += '<div class="almaseo-llm-section-score-pill" style="border-color: ' + llmColor + ';">';
                    sectionsHtml += '<span class="label">LLM:</span>';
                    sectionsHtml += '<span class="value" style="color: ' + llmColor + ';">' + section.llm_readiness + '</span>';
                    sectionsHtml += '</div>';

                    sectionsHtml += '<div class="almaseo-llm-section-score-pill" style="border-color: ' + answerColor + ';">';
                    sectionsHtml += '<span class="label">Answerability:</span>';
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
                'thin_content': 'Thin content',
                'ambiguous_pronouns': 'Ambiguous pronouns',
                'vague_language': 'Vague language',
                'missing_specifics': 'Missing specifics'
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
                alert('This summary style requires Pro. Please upgrade to access Q&A Format and AI Answer styles.');
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
    <?php
}
