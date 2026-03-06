<?php
/**
 * AlmaSEO SEO Playground Overview Page
 */

if (!defined('ABSPATH')) exit;

function seo_playground_render_overview_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Check if AlmaSEO is connected
    $is_connected = seo_playground_is_alma_connected();

    // Get latest 25 posts
    $posts_query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 25,
        'orderby' => 'date',
        'order' => 'DESC'
    ));

    // Calculate key metrics efficiently using SQL counts instead of loading all posts
    $total_posts = wp_count_posts('post')->publish;
    global $wpdb;

    // Count posts that have an SEO title set (proxy for "optimized")
    $optimized_count = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_seo_playground_title' AND pm1.meta_value != ''
         INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_seo_playground_description' AND pm2.meta_value != ''
         WHERE p.post_type = 'post' AND p.post_status = 'publish'"
    );

    // Posts with a title OR description but not both = needs review
    $has_any_meta = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            AND pm.meta_key IN ('_seo_playground_title', '_seo_playground_description')
            AND pm.meta_value != ''
         WHERE p.post_type = 'post' AND p.post_status = 'publish'"
    );
    $needs_review_count = max(0, $has_any_meta - $optimized_count);

    // Calculate health score percentage
    $health_score = $total_posts > 0 ? round(($optimized_count / $total_posts) * 100) : 0;

    // Get 404 count if available
    $error_404_count = 0;
    if (function_exists('seo_playground_get_404_count')) {
        $error_404_count = seo_playground_get_404_count();
    }

    // Check if Pro is active
    $is_pro = almaseo_is_pro_active();

    ?>
    <div class="wrap almaseo-overview-wrap">
        <!-- Header / Hero Bar -->
        <div class="almaseo-overview-header">
            <div class="almaseo-overview-title-section">
                <h1 class="almaseo-overview-title">AlmaSEO Overview</h1>
                <p class="almaseo-overview-subtitle">A quick snapshot of your site's SEO health and activity.</p>
            </div>
            <div class="almaseo-overview-connection-status">
                <?php if ($is_connected): ?>
                    <span class="almaseo-connection-pill almaseo-connected">
                        <span class="almaseo-pill-icon">✓</span> Connected to AlmaSEO
                    </span>
                <?php else: ?>
                    <span class="almaseo-connection-pill almaseo-disconnected">
                        <span class="almaseo-pill-icon">⚠</span> Not Connected
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Key Metrics Row -->
        <div class="almaseo-metrics-row">
            <div class="almaseo-metric-card">
                <div class="almaseo-metric-icon">
                    <span class="dashicons dashicons-dashboard"></span>
                </div>
                <div class="almaseo-metric-content">
                    <div class="almaseo-metric-value"><?php echo esc_html($health_score); ?>%</div>
                    <div class="almaseo-metric-label">SEO Health Score</div>
                    <div class="almaseo-metric-sublabel"><?php echo esc_html($optimized_count); ?> / <?php echo esc_html($total_posts); ?> posts optimized</div>
                </div>
            </div>

            <div class="almaseo-metric-card">
                <div class="almaseo-metric-icon">
                    <span class="dashicons dashicons-admin-post"></span>
                </div>
                <div class="almaseo-metric-content">
                    <div class="almaseo-metric-value"><?php echo esc_html($optimized_count); ?></div>
                    <div class="almaseo-metric-label">Optimized Posts</div>
                    <div class="almaseo-metric-sublabel">Fully SEO ready</div>
                </div>
            </div>

            <div class="almaseo-metric-card">
                <div class="almaseo-metric-icon">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <div class="almaseo-metric-content">
                    <div class="almaseo-metric-value"><?php echo esc_html($needs_review_count); ?></div>
                    <div class="almaseo-metric-label">Need Review</div>
                    <div class="almaseo-metric-sublabel">Requires attention</div>
                </div>
            </div>

            <div class="almaseo-metric-card">
                <div class="almaseo-metric-icon">
                    <span class="dashicons dashicons-dismiss"></span>
                </div>
                <div class="almaseo-metric-content">
                    <div class="almaseo-metric-value"><?php echo esc_html($error_404_count); ?></div>
                    <div class="almaseo-metric-label">404 Errors</div>
                    <div class="almaseo-metric-sublabel">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-404-monitor')); ?>">View all &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Link Suggestions Metric -->
            <div class="almaseo-metric-card">
                <div class="almaseo-metric-icon">
                    <span class="dashicons dashicons-admin-links"></span>
                </div>
                <div class="almaseo-metric-content">
                    <?php
                    $pending_links = 0;
                    if (class_exists('AlmaSEO_Internal_Links_Model') && method_exists('AlmaSEO_Internal_Links_Model', 'get_stats')) {
                        $link_stats = AlmaSEO_Internal_Links_Model::get_stats();
                        $pending_links = isset($link_stats['pending']) ? $link_stats['pending'] : 0;
                    }
                    ?>
                    <div class="almaseo-metric-value"><?php echo esc_html($pending_links); ?></div>
                    <div class="almaseo-metric-label">Link Suggestions</div>
                    <div class="almaseo-metric-sublabel">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-internal-links')); ?>">Review &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Content Refresh Metric -->
            <div class="almaseo-metric-card">
                <div class="almaseo-metric-icon">
                    <span class="dashicons dashicons-update"></span>
                </div>
                <div class="almaseo-metric-content">
                    <?php
                    $pending_drafts = 0;
                    if (class_exists('AlmaSEO_Refresh_Draft_Model') && method_exists('AlmaSEO_Refresh_Draft_Model', 'count')) {
                        $pending_drafts = AlmaSEO_Refresh_Draft_Model::count(array('status' => 'pending'));
                    }
                    ?>
                    <div class="almaseo-metric-value"><?php echo esc_html($pending_drafts); ?></div>
                    <div class="almaseo-metric-label">Content Refresh</div>
                    <div class="almaseo-metric-sublabel">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-refresh-drafts')); ?>">Review &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Refresh Queue Metric -->
            <div class="almaseo-metric-card">
                <div class="almaseo-metric-icon">
                    <span class="dashicons dashicons-flag"></span>
                </div>
                <div class="almaseo-metric-content">
                    <?php
                    $high_priority = 0;
                    if (class_exists('AlmaSEO_Refresh_Queue_Model') && method_exists('AlmaSEO_Refresh_Queue_Model', 'get_stats')) {
                        $rq_stats = AlmaSEO_Refresh_Queue_Model::get_stats();
                        $high_priority = isset($rq_stats['high']) ? $rq_stats['high'] : 0;
                    }
                    ?>
                    <div class="almaseo-metric-value"><?php echo esc_html($high_priority); ?></div>
                    <div class="almaseo-metric-label">High Priority</div>
                    <div class="almaseo-metric-sublabel">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-refresh-queue')); ?>">View queue &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Date Hygiene Metric -->
            <div class="almaseo-metric-card">
                <div class="almaseo-metric-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="almaseo-metric-content">
                    <?php
                    $dh_high = 0;
                    if (class_exists('AlmaSEO_Date_Hygiene_Model') && method_exists('AlmaSEO_Date_Hygiene_Model', 'get_stats')) {
                        $dh_stats = AlmaSEO_Date_Hygiene_Model::get_stats();
                        $dh_high = isset($dh_stats['high']) ? $dh_stats['high'] : 0;
                    }
                    ?>
                    <div class="almaseo-metric-value"><?php echo esc_html($dh_high); ?></div>
                    <div class="almaseo-metric-label">Stale Findings</div>
                    <div class="almaseo-metric-sublabel">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-date-hygiene')); ?>">View findings &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two-Column Layout -->
        <div class="almaseo-two-column-layout">
            <!-- Left Column: Content & Optimization -->
            <div class="almaseo-column almaseo-column-left">
                <!-- Top Opportunities Card -->
                <div class="almaseo-card almaseo-opportunities-card">
                    <div class="almaseo-card-header">
                        <h2 class="almaseo-card-title">Top Optimization Opportunities</h2>
                        <select id="almaseo-status-filter" class="almaseo-filter-dropdown">
                            <option value="all">Show All Posts</option>
                            <option value="optimized">Fully Optimized</option>
                            <option value="needs-review">Needs Review</option>
                            <option value="missing-data">Missing Data</option>
                        </select>
                    </div>
                    <div class="almaseo-card-body">
                        <table class="almaseo-overview-table">
                            <thead>
                                <tr>
                                    <th>Post Title</th>
                                    <th>Status</th>
                                    <th>Last AI Action</th>
                                    <th>Schema</th>
                                    <th>Score</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                        <?php if ($posts_query->have_posts()): ?>
                            <?php while ($posts_query->have_posts()): $posts_query->the_post();
                                $post_id = get_the_ID();

                                // Get post meta data
                                $seo_title = get_post_meta($post_id, '_seo_playground_title', true);
                                $seo_description = get_post_meta($post_id, '_seo_playground_description', true);
                                $schema_type = get_post_meta($post_id, '_seo_playground_schema_type', true);
                                $keyword_suggestions = get_post_meta($post_id, '_seo_playground_keyword_suggestions', true);
                                $internal_links = get_post_meta($post_id, '_seo_playground_internal_links', true);
                                $rewrite_data = get_post_meta($post_id, '_seo_playground_rewrite', true);
                                $reoptimize_flag = get_post_meta($post_id, '_seo_playground_reoptimize_flag', true);

                                // Calculate scorecard status (similar to JavaScript logic)
                                $scorecard_checks = array(
                                    'seo_title' => !empty($seo_title),
                                    'meta_description' => !empty($seo_description),
                                    'focus_keywords' => !empty($keyword_suggestions),
                                    'internal_links' => !empty($internal_links),
                                    'schema_type' => !empty($schema_type) && $schema_type !== 'none',
                                    'ai_rewrite' => !empty($rewrite_data),
                                    'content_length' => strlen(get_the_content()) >= 300,
                                    'reoptimization' => empty($reoptimize_flag)
                                );

                                $passed_checks = count(array_filter($scorecard_checks));
                                $total_checks = count($scorecard_checks);
                                $scorecard_percentage = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100) : 0;

                                // Determine status
                                if ($passed_checks >= 6) {
                                    $status = 'Fully Optimized';
                                    $status_class = 'status-optimized';
                                } elseif ($passed_checks >= 4) {
                                    $status = 'Needs Review';
                                    $status_class = 'status-review';
                                } else {
                                    $status = 'Missing Data';
                                    $status_class = 'status-missing';
                                }

                                // Determine last AI action
                                $last_ai_action = 'None';
                                if (!empty($rewrite_data)) {
                                    $last_ai_action = 'AI Rewrite';
                                } elseif (!empty($keyword_suggestions)) {
                                    $last_ai_action = 'Keyword Suggestions';
                                } elseif (!empty($seo_title) || !empty($seo_description)) {
                                    $last_ai_action = 'Metadata';
                                } elseif (!empty($schema_type) && $schema_type !== 'none') {
                                    $last_ai_action = 'Schema';
                                }
                            ?>
                            <tr class="almaseo-post-row" data-status="<?php echo esc_attr(strtolower(str_replace(' ', '-', $status ?? ''))); ?>">
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>" target="_blank">
                                        <?php echo esc_html(get_the_title()); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="almaseo-status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="almaseo-ai-action"><?php echo esc_html($last_ai_action); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($schema_type) && $schema_type !== 'none'): ?>
                                        <span class="almaseo-schema-type"><?php echo esc_html(ucfirst($schema_type)); ?></span>
                                    <?php else: ?>
                                        <span class="almaseo-schema-none">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="almaseo-scorecard-summary">
                                        <span class="almaseo-scorecard-score"><?php echo esc_html($passed_checks); ?>/<?php echo esc_html($total_checks); ?></span>
                                        <div class="almaseo-scorecard-bar">
                                            <div class="almaseo-scorecard-fill" style="width: <?php echo esc_attr($scorecard_percentage); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="almaseo-action-buttons">
                                        <button class="almaseo-action-btn almaseo-reoptimize-btn" data-post-id="<?php echo esc_attr($post_id); ?>" title="Click to auto-check post again for reoptimization">
                                            🔄 Reoptimize
                                        </button>
                                        <button class="almaseo-action-btn almaseo-rewrite-btn" data-post-id="<?php echo esc_attr($post_id); ?>">
                                            ✍️ Rewrite
                                        </button>
                                        <button class="almaseo-action-btn almaseo-view-meta-btn" data-post-id="<?php echo esc_attr($post_id); ?>">
                                            👁 View Meta
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; wp_reset_postdata(); ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="almaseo-no-posts">
                                    <p>No posts found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: Technical & Monitoring -->
            <div class="almaseo-column almaseo-column-right">
                <!-- Sitemaps & Indexing Card -->
                <div class="almaseo-card almaseo-sitemap-card">
                    <div class="almaseo-card-header">
                        <h2 class="almaseo-card-title">
                            <span class="dashicons dashicons-networking"></span> Sitemaps & Indexing
                        </h2>
                    </div>
                    <div class="almaseo-card-body">
                        <div class="almaseo-status-item">
                            <span class="almaseo-status-dot almaseo-status-success"></span>
                            <div class="almaseo-status-content">
                                <div class="almaseo-status-label">XML Sitemap</div>
                                <div class="almaseo-status-value">
                                    <a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank">View Sitemap</a>
                                </div>
                            </div>
                        </div>
                        <div class="almaseo-status-item">
                            <span class="almaseo-status-dot almaseo-status-info"></span>
                            <div class="almaseo-status-content">
                                <div class="almaseo-status-label">Total Pages</div>
                                <div class="almaseo-status-value"><?php echo esc_html($total_posts); ?> posts</div>
                            </div>
                        </div>
                        <?php if ($is_connected): ?>
                            <div class="almaseo-status-item">
                                <span class="almaseo-status-dot almaseo-status-success"></span>
                                <div class="almaseo-status-content">
                                    <div class="almaseo-status-label">AlmaSEO Integration</div>
                                    <div class="almaseo-status-value">Active</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Redirects & Logs Card -->
                <div class="almaseo-card almaseo-redirects-card">
                    <div class="almaseo-card-header">
                        <h2 class="almaseo-card-title">
                            <span class="dashicons dashicons-randomize"></span> Redirects & Monitoring
                        </h2>
                    </div>
                    <div class="almaseo-card-body">
                        <div class="almaseo-quick-link">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-redirects')); ?>">
                                <span class="dashicons dashicons-randomize"></span>
                                Manage Redirects
                            </a>
                        </div>

                        <div class="almaseo-quick-link">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-404-monitor')); ?>">
                                <span class="dashicons dashicons-warning"></span>
                                404 Monitor (<?php echo esc_html($error_404_count); ?>)
                            </a>
                        </div>

                        <div class="almaseo-quick-link">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-settings')); ?>">
                                <span class="dashicons dashicons-admin-settings"></span>
                                Plugin Settings
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="almaseo-card almaseo-quick-actions-card">
                    <div class="almaseo-card-header">
                        <h2 class="almaseo-card-title">
                            <span class="dashicons dashicons-admin-tools"></span> Quick Actions
                        </h2>
                    </div>
                    <div class="almaseo-card-body">
                        <?php if (!$is_connected): ?>
                            <div class="almaseo-action-item almaseo-action-highlight">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-settings')); ?>">
                                    <span class="dashicons dashicons-admin-plugins"></span>
                                    Connect to AlmaSEO
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="almaseo-action-item">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-bulk-meta')); ?>">
                                <span class="dashicons dashicons-welcome-write-blog"></span>
                                Bulk Meta Editor
                            </a>
                        </div>
                        <div class="almaseo-action-item">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=almaseo-schema')); ?>">
                                <span class="dashicons dashicons-media-code"></span>
                                Schema Manager
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pro Feature Teaser Strip -->
        <?php if (!$is_pro): ?>
            <div class="almaseo-pro-teaser-strip">
                <div class="almaseo-pro-teaser-content">
                    <div class="almaseo-pro-teaser-icon">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="almaseo-pro-teaser-text">
                        <h3>Unlock Advanced SEO Features with AlmaSEO Pro</h3>
                        <p>Get access to <strong>LLM Optimization</strong> (optimize for ChatGPT & AI models), 301 Redirects, Bulk Meta Editor, WooCommerce SEO, Advanced Schema, 404 Monitoring, and more!</p>
                    </div>
                    <div class="almaseo-pro-teaser-action">
                        <a href="https://almaseo.com/pricing" target="_blank" class="almaseo-pro-upgrade-btn">
                            Upgrade to Pro &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- View Meta Modal -->
        <div id="almaseo-view-meta-modal" class="almaseo-modal">
            <div class="almaseo-modal-content">
                <div class="almaseo-modal-header">
                    <h3>Post SEO Meta Data</h3>
                    <span class="almaseo-modal-close">&times;</span>
                </div>
                <div class="almaseo-modal-body">
                    <div id="almaseo-meta-content"></div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Overview Page Styles */
        .almaseo-overview-wrap {
            max-width: 1400px;
        }

        /* Header / Hero Bar */
        .almaseo-overview-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .almaseo-overview-title {
            color: #fff;
            font-size: 32px;
            margin: 0 0 8px 0;
            font-weight: 700;
        }

        .almaseo-overview-subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 16px;
            margin: 0;
            font-weight: 400;
        }

        .almaseo-connection-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .almaseo-connection-pill.almaseo-connected {
            background: rgba(76, 175, 80, 0.9);
        }

        .almaseo-connection-pill.almaseo-disconnected {
            background: rgba(255, 152, 0, 0.9);
        }

        .almaseo-pill-icon {
            font-weight: bold;
        }

        /* Metrics Row */
        .almaseo-metrics-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .almaseo-metric-card {
            background: #fff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            gap: 16px;
            align-items: flex-start;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .almaseo-metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .almaseo-metric-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .almaseo-metric-icon .dashicons {
            color: #fff;
            font-size: 28px;
            width: 28px;
            height: 28px;
        }

        .almaseo-metric-content {
            flex: 1;
        }

        .almaseo-metric-value {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .almaseo-metric-label {
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .almaseo-metric-sublabel {
            font-size: 13px;
            color: #94a3b8;
        }

        .almaseo-metric-sublabel a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .almaseo-metric-sublabel a:hover {
            text-decoration: underline;
        }

        /* Two-Column Layout */
        .almaseo-two-column-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 30px;
        }

        .almaseo-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Card Styles */
        .almaseo-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .almaseo-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .almaseo-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .almaseo-card-title .dashicons {
            color: #667eea;
        }

        .almaseo-card-body {
            padding: 24px;
        }

        /* Filter Dropdown */
        .almaseo-filter-dropdown {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            color: #475569;
            background: #fff;
            cursor: pointer;
        }

        .almaseo-filter-dropdown:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Table Styles */
        .almaseo-overview-table {
            width: 100%;
            border-collapse: collapse;
        }

        .almaseo-overview-table thead th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .almaseo-overview-table tbody td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #334155;
        }

        .almaseo-overview-table tbody tr:hover {
            background: #f8fafc;
        }

        .almaseo-overview-table tbody tr td:first-child a {
            color: #1e293b;
            text-decoration: none;
            font-weight: 500;
        }

        .almaseo-overview-table tbody tr td:first-child a:hover {
            color: #667eea;
        }

        /* Status Badges */
        .almaseo-status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .almaseo-status-badge.status-optimized {
            background: #d1fae5;
            color: #065f46;
        }

        .almaseo-status-badge.status-review {
            background: #fef3c7;
            color: #92400e;
        }

        .almaseo-status-badge.status-missing {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Scorecard */
        .almaseo-scorecard-summary {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .almaseo-scorecard-score {
            font-weight: 600;
            color: #1e293b;
            min-width: 40px;
        }

        .almaseo-scorecard-bar {
            flex: 1;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            max-width: 100px;
        }

        .almaseo-scorecard-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.3s ease;
        }

        /* Action Buttons */
        .almaseo-action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .almaseo-action-btn {
            padding: 6px 12px;
            border: 1px solid #cbd5e1;
            background: #fff;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .almaseo-action-btn:hover {
            background: #f8fafc;
            border-color: #667eea;
            color: #667eea;
        }

        /* Right Column Status Items */
        .almaseo-status-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .almaseo-status-item:last-child {
            border-bottom: none;
        }

        .almaseo-status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-top: 4px;
            flex-shrink: 0;
        }

        .almaseo-status-dot.almaseo-status-success {
            background: #10b981;
        }

        .almaseo-status-dot.almaseo-status-info {
            background: #3b82f6;
        }

        .almaseo-status-dot.almaseo-status-warning {
            background: #f59e0b;
        }

        .almaseo-status-content {
            flex: 1;
        }

        .almaseo-status-label {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 2px;
        }

        .almaseo-status-value {
            font-size: 14px;
            color: #1e293b;
        }

        .almaseo-status-value a {
            color: #667eea;
            text-decoration: none;
        }

        .almaseo-status-value a:hover {
            text-decoration: underline;
        }

        /* Quick Links */
        .almaseo-quick-link {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .almaseo-quick-link:last-child {
            border-bottom: none;
        }

        .almaseo-quick-link a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .almaseo-quick-link a:hover {
            color: #667eea;
        }

        .almaseo-quick-link .dashicons {
            color: #667eea;
        }

        /* Action Items */
        .almaseo-action-item {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .almaseo-action-item:last-child {
            border-bottom: none;
        }

        .almaseo-action-item a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #334155;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .almaseo-action-item a:hover {
            color: #667eea;
        }

        .almaseo-action-item.almaseo-action-highlight {
            background: #fef3c7;
            padding: 12px;
            border-radius: 8px;
            border: none;
        }

        .almaseo-action-item.almaseo-action-highlight a {
            color: #92400e;
            font-weight: 600;
        }

        .almaseo-action-item .dashicons {
            color: #667eea;
        }

        /* Pro Badge Small */
        .almaseo-pro-badge-small {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            font-size: 13px;
            padding: 8px 0;
        }

        .almaseo-pro-badge-small .dashicons {
            color: #cbd5e1;
        }

        /* Pro Teaser Strip */
        .almaseo-pro-teaser-strip {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 12px;
            padding: 30px 40px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
        }

        .almaseo-pro-teaser-content {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .almaseo-pro-teaser-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .almaseo-pro-teaser-icon .dashicons {
            color: #fff;
            font-size: 32px;
            width: 32px;
            height: 32px;
        }

        .almaseo-pro-teaser-text {
            flex: 1;
        }

        .almaseo-pro-teaser-text h3 {
            color: #fff;
            font-size: 22px;
            margin: 0 0 8px 0;
            font-weight: 700;
        }

        .almaseo-pro-teaser-text p {
            color: rgba(255,255,255,0.95);
            font-size: 15px;
            margin: 0;
        }

        .almaseo-pro-teaser-action {
            flex-shrink: 0;
        }

        .almaseo-pro-upgrade-btn {
            display: inline-block;
            padding: 14px 28px;
            background: #fff;
            color: #f59e0b;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.2s ease;
        }

        .almaseo-pro-upgrade-btn:hover {
            background: #fef3c7;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Modal Styles */
        .almaseo-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .almaseo-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        .almaseo-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .almaseo-modal-header h3 {
            margin: 0;
            color: #1e293b;
            font-size: 18px;
        }

        .almaseo-modal-close {
            color: #94a3b8;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .almaseo-modal-close:hover {
            color: #1e293b;
        }

        .almaseo-modal-body {
            padding: 24px;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .almaseo-two-column-layout {
                grid-template-columns: 1fr;
            }

            .almaseo-column-right {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .almaseo-overview-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                padding: 24px;
            }

            .almaseo-overview-title {
                font-size: 24px;
            }

            .almaseo-overview-subtitle {
                font-size: 14px;
            }

            .almaseo-metrics-row {
                grid-template-columns: 1fr;
            }

            .almaseo-pro-teaser-content {
                flex-direction: column;
                text-align: center;
            }

            .almaseo-pro-teaser-text h3 {
                font-size: 18px;
            }

            .almaseo-overview-table {
                font-size: 12px;
            }

            .almaseo-action-buttons {
                flex-direction: column;
            }

            .almaseo-action-btn {
                width: 100%;
                text-align: center;
            }
        }

        /* No posts message */
        .almaseo-no-posts {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Filter functionality
        $('#almaseo-status-filter').on('change', function() {
            var filter = $(this).val();
            $('.almaseo-post-row').each(function() {
                var row = $(this);
                if (filter === 'all' || row.data('status') === filter) {
                    row.show();
                } else {
                    row.hide();
                }
            });
        });

        // View Meta button
        $('.almaseo-view-meta-btn').on('click', function() {
            var postId = $(this).data('post-id');
            var postTitle = $(this).closest('tr').find('td:first a').text();

            // Show modal with post meta data
            $('#almaseo-meta-content').empty().append($('<p>').text('Loading meta data for: ' + postTitle));
            $('#almaseo-view-meta-modal').show();

            // In a real implementation, you would fetch the meta data via AJAX
            // For now, we'll show a placeholder
            setTimeout(function() {
                $('#almaseo-meta-content').html(
                    '<div class="almaseo-meta-details">' +
                    '<h4>SEO Meta Data for: ' + postTitle + '</h4>' +
                    '<p><strong>SEO Title:</strong> <span class="meta-value">Sample SEO Title</span></p>' +
                    '<p><strong>Meta Description:</strong> <span class="meta-value">Sample meta description for this post...</span></p>' +
                    '<p><strong>Schema Type:</strong> <span class="meta-value">Article</span></p>' +
                    '<p><strong>Keywords:</strong> <span class="meta-value">keyword1, keyword2, keyword3</span></p>' +
                    '</div>'
                );
            }, 500);
        });

        // Close modal
        $('.almaseo-modal-close').on('click', function() {
            $('#almaseo-view-meta-modal').hide();
        });

        // Close modal when clicking outside
        $(window).on('click', function(e) {
            if (e.target === $('#almaseo-view-meta-modal')[0]) {
                $('#almaseo-view-meta-modal').hide();
            }
        });

        // Reoptimize button (placeholder for future implementation)
        $('.almaseo-reoptimize-btn').on('click', function() {
            var postId = $(this).data('post-id');
            alert('Reoptimize functionality will be implemented in a future update for post ID: ' + postId);
        });

        // Rewrite button (placeholder for future implementation)
        $('.almaseo-rewrite-btn').on('click', function() {
            var postId = $(this).data('post-id');
            alert('Rewrite functionality will be implemented in a future update for post ID: ' + postId);
        });
    });
    </script>
    <?php

    wp_reset_postdata();
}
