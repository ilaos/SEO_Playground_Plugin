<?php
/**
 * Sitemap Class Autoloader
 * 
 * Handles automatic loading of sitemap-related classes
 * 
 * @package AlmaSEO
 * @since 5.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Sitemap_Autoloader {
    
    /**
     * Class mapping for sitemap classes
     */
    private static $class_map = [
        // Core classes
        'Alma_Sitemap_Manager' => '/class-alma-sitemap-manager.php',
        'Alma_Sitemap_Responder' => '/class-alma-sitemap-responder.php',
        'Alma_Sitemap_Validator' => '/class-alma-sitemap-validator.php',
        'Alma_Sitemap_Writer' => '/class-alma-sitemap-writer.php',
        'Alma_Sitemap_Conflicts' => '/class-alma-sitemap-conflicts.php',
        'Alma_Sitemap_Diff' => '/class-alma-sitemap-diff.php',
        'Alma_Sitemap_CLI' => '/class-alma-sitemap-cli.php',
        
        // Feature classes
        'Alma_Hreflang' => '/class-alma-hreflang.php',
        'Alma_IndexNow' => '/class-alma-indexnow.php',
        'Alma_HTML_Sitemap' => '/class-alma-html-sitemap.php',
        'Alma_Health_Log' => '/class-alma-health-log.php',
        'Alma_Robots_Integration' => '/class-alma-robots-integration.php',
        'Alma_Settings_Porter' => '/class-alma-settings-porter.php',
        
        // Provider classes
        'Alma_Provider_Posts' => '/providers/class-alma-provider-posts.php',
        'Alma_Provider_Pages' => '/providers/class-alma-provider-pages.php',
        'Alma_Provider_CPTs' => '/providers/class-alma-provider-cpts.php',
        'Alma_Provider_Tax' => '/providers/class-alma-provider-tax.php',
        'Alma_Provider_Users' => '/providers/class-alma-provider-users.php',
        'Alma_Provider_Extra' => '/providers/class-alma-provider-extra.php',
        'Alma_Provider_Delta' => '/providers/class-alma-provider-delta.php',
        'Alma_Provider_Image' => '/providers/class-alma-provider-image.php',
        'Alma_Provider_Video' => '/providers/class-alma-provider-video.php',
        'Alma_Provider_News' => '/providers/class-alma-provider-news.php',
        
        // Admin classes
        'Alma_Sitemap_Admin_Page' => '/admin/class-sitemap-admin-page.php',
        'Alma_Sitemap_Ajax_Handlers' => '/admin/class-sitemap-ajax-handlers.php',
        'Alma_Sitemaps_Screen_V2' => '/admin/sitemaps-screen-v2.php',
    ];
    
    /**
     * Base directory for sitemap classes
     */
    private static $base_dir = null;
    
    /**
     * Initialize the autoloader
     */
    public static function init() {
        if (self::$base_dir === null) {
            self::$base_dir = dirname(__FILE__);
        }
        
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    /**
     * Autoload sitemap classes
     * 
     * @param string $class The class name to load
     */
    public static function autoload($class) {
        // Check if this is one of our classes
        if (!isset(self::$class_map[$class])) {
            return;
        }
        
        $file = self::$base_dir . self::$class_map[$class];
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
    
    /**
     * Load a specific class manually
     * 
     * @param string $class The class name to load
     * @return bool Whether the class was loaded
     */
    public static function load_class($class) {
        if (class_exists($class)) {
            return true;
        }
        
        if (!isset(self::$class_map[$class])) {
            return false;
        }
        
        $file = self::$base_dir . self::$class_map[$class];
        
        if (file_exists($file)) {
            require_once $file;
            return class_exists($class);
        }
        
        return false;
    }
    
    /**
     * Load all provider classes
     * Used when all providers need to be available
     */
    public static function load_all_providers() {
        $providers = [
            'Alma_Provider_Posts',
            'Alma_Provider_Pages',
            'Alma_Provider_CPTs',
            'Alma_Provider_Tax',
            'Alma_Provider_Users',
            'Alma_Provider_Extra',
            'Alma_Provider_Delta',
            'Alma_Provider_Image',
            'Alma_Provider_Video',
            'Alma_Provider_News'
        ];
        
        foreach ($providers as $provider) {
            self::load_class($provider);
        }
    }
    
    /**
     * Check if a class is registered
     * 
     * @param string $class The class name to check
     * @return bool
     */
    public static function is_registered($class) {
        return isset(self::$class_map[$class]);
    }
}

// Initialize the autoloader
Alma_Sitemap_Autoloader::init();