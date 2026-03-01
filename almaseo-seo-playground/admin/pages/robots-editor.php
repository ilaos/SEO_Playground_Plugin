<?php
/**
 * AlmaSEO Robots.txt Editor Admin Page
 * 
 * @package AlmaSEO
 * @since 6.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get controller instance
$controller = AlmaSEO_Robots_Controller::get_instance();

// Get current settings
$mode = get_option('almaseo_robots_mode', 'virtual');
$content = get_option('almaseo_robots_content', '');

// Check file status
$physical_exists = $controller->physical_file_exists();
$is_writable = $controller->is_file_writable();
$file_path = $controller->get_robots_file_path();

// If content is empty, use default
if (empty($content)) {
    $content = $controller->get_default_content();
}

// Get physical file content if it exists
$physical_content = '';
if ($physical_exists) {
    $physical_content = $controller->read_physical_file();
}
?>

<div class="wrap almaseo-robots-editor">
    <h1>
        <?php echo esc_html__('Robots.txt Editor', 'almaseo'); ?>
        <span class="almaseo-badge">AlmaSEO</span>
    </h1>
    
    <?php 
    // Include UI helpers if not already included
    if (function_exists('almaseo_render_help')) {
        almaseo_render_help(
            __('This controls what search engines can crawl. Most sites allow all public pages and block /wp-admin/. Use Virtual Mode if you\'re unsure.', 'almaseo'),
            __('If a physical robots.txt exists, it overrides the virtual one served by WordPress.', 'almaseo')
        );
    } else {
        ?>
        <p class="description">
            <?php echo esc_html__('Control how search engines crawl your site by managing your robots.txt file.', 'almaseo'); ?>
        </p>
        <?php
    }
    ?>
    
    <?php if ($physical_exists && $mode === 'virtual'): ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php echo esc_html__('Warning:', 'almaseo'); ?></strong>
            <?php echo esc_html__('A physical robots.txt file exists at', 'almaseo'); ?> 
            <code><?php echo esc_html($file_path); ?></code>. 
            <?php echo esc_html__('WordPress will serve this file regardless of your virtual mode settings. Consider switching to Physical mode to edit the file directly, or delete the physical file to use virtual mode.', 'almaseo'); ?>
        </p>
    </div>
    <?php endif; ?>
    
    <?php if (!$is_writable && $mode === 'file'): ?>
    <div class="notice notice-error">
        <p>
            <strong><?php echo esc_html__('Error:', 'almaseo'); ?></strong>
            <?php echo esc_html__('The robots.txt file or directory is not writable. Physical mode has been disabled. Please check file permissions or use Virtual mode.', 'almaseo'); ?>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="almaseo-robots-container">
        
        <!-- Mode Selection Card -->
        <div class="card">
            <h2><?php echo esc_html__('Mode Selection', 'almaseo'); ?></h2>
            
            <div class="almaseo-mode-selector">
                <label class="mode-option">
                    <input type="radio" name="robots_mode" value="virtual" <?php checked($mode, 'virtual'); ?>>
                    <div class="mode-content">
                        <strong><?php echo esc_html__('Virtual Mode', 'almaseo'); ?></strong>
                        <span class="recommended"><?php echo esc_html__('(Recommended)', 'almaseo'); ?></span>
                        <p><?php echo esc_html__('Serve robots.txt content via WordPress without creating a physical file. Safe and portable.', 'almaseo'); ?></p>
                    </div>
                </label>
                
                <label class="mode-option <?php echo !$is_writable ? 'disabled' : ''; ?>">
                    <input type="radio" name="robots_mode" value="file" 
                           <?php checked($mode, 'file'); ?>
                           <?php echo !$is_writable ? 'disabled' : ''; ?>>
                    <div class="mode-content">
                        <strong><?php echo esc_html__('Physical File Mode', 'almaseo'); ?></strong>
                        <p><?php echo esc_html__('Write directly to robots.txt file in your site root. Requires write permissions.', 'almaseo'); ?></p>
                        <?php if (!$is_writable): ?>
                        <p class="error-text"><?php echo esc_html__('Not available - insufficient permissions', 'almaseo'); ?></p>
                        <?php endif; ?>
                    </div>
                </label>
            </div>
            
            <div class="mode-status">
                <h3><?php echo esc_html__('Current Status', 'almaseo'); ?></h3>
                <ul>
                    <li>
                        <?php echo esc_html__('Mode:', 'almaseo'); ?> 
                        <strong><?php echo $mode === 'virtual' ? esc_html__('Virtual', 'almaseo') : esc_html__('Physical', 'almaseo'); ?></strong>
                    </li>
                    <li>
                        <?php echo esc_html__('Physical file exists:', 'almaseo'); ?> 
                        <strong><?php echo $physical_exists ? esc_html__('Yes', 'almaseo') : esc_html__('No', 'almaseo'); ?></strong>
                    </li>
                    <li>
                        <?php echo esc_html__('File writable:', 'almaseo'); ?> 
                        <strong><?php echo $is_writable ? esc_html__('Yes', 'almaseo') : esc_html__('No', 'almaseo'); ?></strong>
                    </li>
                    <li>
                        <?php echo esc_html__('File path:', 'almaseo'); ?> 
                        <code><?php echo esc_html($file_path); ?></code>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Editor Card -->
        <div class="card">
            <h2><?php echo esc_html__('Robots.txt Content', 'almaseo'); ?></h2>
            
            <div class="editor-toolbar">
                <button type="button" class="button" id="insert-defaults">
                    <?php echo esc_html__('Insert AlmaSEO Defaults', 'almaseo'); ?>
                </button>
                <button type="button" class="button" id="reset-wp-default">
                    <?php echo esc_html__('Reset to WordPress Default', 'almaseo'); ?>
                </button>
                <button type="button" class="button" id="test-output">
                    <?php echo esc_html__('Test Output', 'almaseo'); ?>
                </button>
            </div>
            
            <div class="editor-container">
                <textarea id="robots-content" name="robots_content" rows="20" class="large-text code"><?php echo esc_textarea($content); ?></textarea>
                
                <div class="editor-help">
                    <h4><?php echo esc_html__('Quick Reference:', 'almaseo'); ?></h4>
                    <ul>
                        <li><code>User-agent: *</code> - <?php echo esc_html__('Applies to all crawlers', 'almaseo'); ?></li>
                        <li><code>Disallow: /path/</code> - <?php echo esc_html__('Block access to path', 'almaseo'); ?></li>
                        <li><code>Allow: /path/</code> - <?php echo esc_html__('Allow access to path', 'almaseo'); ?></li>
                        <li><code>Sitemap: <?php echo esc_url(home_url('/sitemap.xml')); ?></code> - <?php echo esc_html__('Sitemap location', 'almaseo'); ?></li>
                        <li><code>Crawl-delay: 10</code> - <?php echo esc_html__('Delay between requests (seconds)', 'almaseo'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="submit-container">
                <button type="button" class="button button-primary" id="save-robots">
                    <?php echo esc_html__('Save Changes', 'almaseo'); ?>
                </button>
                <span class="spinner"></span>
                <div class="save-message"></div>
            </div>
        </div>
        
        <!-- Preview Card -->
        <div class="card" id="preview-card" style="display: none;">
            <h2><?php echo esc_html__('Output Preview', 'almaseo'); ?></h2>
            
            <div class="preview-status"></div>
            
            <div class="preview-container">
                <pre id="preview-output" class="preview-output"></pre>
            </div>
            
            <div class="preview-actions">
                <a href="<?php echo esc_url(home_url('/robots.txt')); ?>" target="_blank" class="button">
                    <?php echo esc_html__('View Live robots.txt', 'almaseo'); ?> ↗
                </a>
            </div>
        </div>
        
        <?php if ($physical_exists && $mode === 'virtual'): ?>
        <!-- Physical File Content (Read-only) -->
        <div class="card warning-card">
            <h2><?php echo esc_html__('Physical File Content (Read-only)', 'almaseo'); ?></h2>
            <p class="description">
                <?php echo esc_html__('This is the content of the physical robots.txt file that is currently being served:', 'almaseo'); ?>
            </p>
            <div class="physical-content-container">
                <pre class="physical-content"><?php echo esc_html($physical_content); ?></pre>
            </div>
            <p class="description">
                <?php echo esc_html__('To edit this content, switch to Physical mode above.', 'almaseo'); ?>
            </p>
        </div>
        <?php endif; ?>
        
    </div>
</div>