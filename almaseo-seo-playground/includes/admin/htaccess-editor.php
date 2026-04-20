<?php
/**
 * AlmaSEO .htaccess Editor
 *
 * Provides a safe admin UI for viewing and editing the .htaccess file
 * with automatic backups and one-click restore.
 *
 * Architecture mirrors includes/robots/robots-controller.php.
 *
 * @package AlmaSEO
 * @since   8.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Htaccess_Editor {

    /** @var self|null */
    private static $instance = null;

    /** Maximum number of backups to keep. */
    const MAX_BACKUPS = 3;

    /** Option key used to store backups. */
    const BACKUP_OPTION = 'almaseo_htaccess_backups';

    /** Menu slug. */
    const SLUG = 'almaseo-htaccess';

    /* ------------------------------------------------------------------ */
    /*  Singleton                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor.
     */
    private function __construct() {
        $this->init();
    }

    /* ------------------------------------------------------------------ */
    /*  Bootstrap                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Hook everything up.
     */
    private function init() {
        add_action( 'admin_menu',            array( $this, 'add_admin_menu' ), 16 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // AJAX handlers.
        add_action( 'wp_ajax_almaseo_htaccess_save',    array( $this, 'ajax_save' ) );
        add_action( 'wp_ajax_almaseo_htaccess_restore', array( $this, 'ajax_restore' ) );
    }

    /* ------------------------------------------------------------------ */
    /*  Admin menu                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Register submenu page under "SEO Playground".
     */
    public function add_admin_menu() {
        add_submenu_page(
            'seo-playground',
            '.htaccess Editor - AlmaSEO',
            '.htaccess',
            'manage_options',
            self::SLUG,
            array( $this, 'render_admin_page' )
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Assets                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Enqueue CSS / JS only on this page.
     *
     * @param string $hook Current admin page hook suffix.
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        wp_enqueue_style(
            'almaseo-htaccess-editor',
            ALMASEO_URL . 'assets/css/htaccess-editor.css',
            array(),
            ALMASEO_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'almaseo-htaccess-editor',
            ALMASEO_URL . 'assets/js/htaccess-editor.js',
            array( 'jquery' ),
            ALMASEO_PLUGIN_VERSION,
            true
        );

        wp_localize_script( 'almaseo-htaccess-editor', 'almaseoHtaccess', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'almaseo_htaccess_nonce' ),
            'strings' => array(
                'saving'         => __( 'Saving...', 'almaseo-seo-playground' ),
                'saved'          => __( '.htaccess saved successfully!', 'almaseo-seo-playground' ),
                'error'          => __( 'An error occurred. Please try again.', 'almaseo-seo-playground' ),
                'confirmSave'    => __( 'Are you sure you want to save changes to .htaccess? An incorrect configuration can take your site offline.', 'almaseo-seo-playground' ),
                'confirmRestore' => __( 'Are you sure you want to restore this backup? The current .htaccess will be overwritten.', 'almaseo-seo-playground' ),
                'restoring'      => __( 'Restoring...', 'almaseo-seo-playground' ),
                'restored'       => __( 'Backup restored successfully!', 'almaseo-seo-playground' ),
                'selectBackup'   => __( 'Please select a backup to restore.', 'almaseo-seo-playground' ),
            ),
        ) );
    }

    /* ------------------------------------------------------------------ */
    /*  Render page                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Render the admin page template.
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(esc_html__( 'You do not have sufficient permissions to access this page.', 'almaseo-seo-playground' ) );
        }

        require_once ALMASEO_PATH . 'admin/pages/htaccess-editor.php';
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX: Save                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * AJAX handler — save .htaccess content.
     */
    public function ajax_save() {
        check_ajax_referer( 'almaseo_htaccess_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'almaseo-seo-playground' ) ) );
        }

        if ( ! $this->is_apache() ) {
            wp_send_json_error( array( 'message' => __( 'This server does not appear to be running Apache.', 'almaseo-seo-playground' ) ) );
        }

        $content = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '';
        $content = $this->sanitize_htaccess( $content );

        $file_path = $this->get_file_path();

        // Create backup of current content before writing.
        if ( file_exists( $file_path ) ) {
            $current = file_get_contents( $file_path );
            if ( false !== $current ) {
                $this->push_backup( $current );
            }
        }

        // Write file.
        $result = $this->write_file( $content );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message' => __( '.htaccess saved successfully!', 'almaseo-seo-playground' ),
            'backups' => $this->get_backups_for_js(),
        ) );
    }

    /* ------------------------------------------------------------------ */
    /*  AJAX: Restore                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * AJAX handler — restore a backup.
     */
    public function ajax_restore() {
        check_ajax_referer( 'almaseo_htaccess_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'almaseo-seo-playground' ) ) );
        }

        $index   = isset( $_POST['backup_index'] ) ? absint( $_POST['backup_index'] ) : -1;
        $backups = get_option( self::BACKUP_OPTION, array() );

        if ( ! isset( $backups[ $index ] ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid backup selection.', 'almaseo-seo-playground' ) ) );
        }

        $restore_content = $backups[ $index ]['content'];

        // Backup current content before restoring.
        $file_path = $this->get_file_path();
        if ( file_exists( $file_path ) ) {
            $current = file_get_contents( $file_path );
            if ( false !== $current ) {
                $this->push_backup( $current );
            }
        }

        $result = $this->write_file( $restore_content );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message' => __( 'Backup restored successfully!', 'almaseo-seo-playground' ),
            'content' => $restore_content,
            'backups' => $this->get_backups_for_js(),
        ) );
    }

    /* ------------------------------------------------------------------ */
    /*  File helpers                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Get the full path to .htaccess.
     *
     * @return string
     */
    public function get_file_path() {
        return trailingslashit( ABSPATH ) . '.htaccess';
    }

    /**
     * Check whether the .htaccess file exists.
     *
     * @return bool
     */
    public function file_exists() {
        return file_exists( $this->get_file_path() );
    }

    /**
     * Check whether the .htaccess file (or its parent directory) is writable.
     *
     * @return bool
     */
    public function is_writable() {
        $path = $this->get_file_path();
        if ( file_exists( $path ) ) {
            return is_writable( $path );
        }
        return is_writable( ABSPATH );
    }

    /**
     * Read current .htaccess content.
     *
     * @return string
     */
    public function read_file() {
        $path = $this->get_file_path();
        if ( ! file_exists( $path ) ) {
            return '';
        }
        $content = file_get_contents( $path );
        return ( false !== $content ) ? $content : '';
    }

    /**
     * Write content to the .htaccess file.
     *
     * @param string $content New file content.
     * @return true|WP_Error
     */
    public function write_file( $content ) {
        if ( ! $this->is_writable() ) {
            return new WP_Error(
                'not_writable',
                __( 'The .htaccess file is not writable. Please check file permissions.', 'almaseo-seo-playground' )
            );
        }

        global $wp_filesystem;

        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $creds = request_filesystem_credentials( '', '', false, ABSPATH, null );

        if ( ! WP_Filesystem( $creds ) ) {
            return new WP_Error(
                'filesystem_error',
                __( 'Could not access the filesystem.', 'almaseo-seo-playground' )
            );
        }

        $path   = $this->get_file_path();
        $result = $wp_filesystem->put_contents( $path, $content, FS_CHMOD_FILE );

        if ( ! $result ) {
            return new WP_Error(
                'write_failed',
                __( 'Failed to write the .htaccess file.', 'almaseo-seo-playground' )
            );
        }

        return true;
    }

    /* ------------------------------------------------------------------ */
    /*  Backup helpers                                                     */
    /* ------------------------------------------------------------------ */

    /**
     * Push the current content onto the backup stack (max 3).
     *
     * @param string $content Content to back up.
     */
    private function push_backup( $content ) {
        $backups = get_option( self::BACKUP_OPTION, array() );

        // Prepend new backup.
        array_unshift( $backups, array(
            'content'   => $content,
            'timestamp' => current_time( 'mysql' ),
        ) );

        // Trim to max.
        $backups = array_slice( $backups, 0, self::MAX_BACKUPS );

        update_option( self::BACKUP_OPTION, $backups, false );
    }

    /**
     * Get backups formatted for the JS dropdown.
     *
     * @return array
     */
    public function get_backups_for_js() {
        $backups = get_option( self::BACKUP_OPTION, array() );
        $result  = array();

        foreach ( $backups as $i => $b ) {
            $result[] = array(
                'index'     => $i,
                'timestamp' => $b['timestamp'],
                'preview'   => mb_substr( $b['content'], 0, 80 ) . ( mb_strlen( $b['content'] ) > 80 ? '...' : '' ),
            );
        }

        return $result;
    }

    /* ------------------------------------------------------------------ */
    /*  Server detection                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Determine whether the server is running Apache.
     *
     * @return bool
     */
    public function is_apache() {
        if ( function_exists( 'apache_get_version' ) ) {
            return true;
        }

        $software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
        if ( stripos( $software, 'apache' ) !== false ) {
            return true;
        }

        // LiteSpeed is compatible with .htaccess.
        if ( stripos( $software, 'litespeed' ) !== false ) {
            return true;
        }

        // If a .htaccess file already exists, assume the server supports it.
        if ( $this->file_exists() ) {
            return true;
        }

        return false;
    }

    /* ------------------------------------------------------------------ */
    /*  Sanitization                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * Sanitize .htaccess content (strip PHP tags, normalize line endings).
     *
     * @param string $content Raw content.
     * @return string
     */
    public function sanitize_htaccess( $content ) {
        // Remove PHP tags.
        $content = str_replace( array( '<?php', '<?', '?>', '<%', '%>' ), '', $content );

        // Normalize line endings.
        $content = str_replace( "\r\n", "\n", $content );
        $content = str_replace( "\r",   "\n", $content );

        // Limit individual line length to prevent abuse.
        $lines     = explode( "\n", $content );
        $sanitized = array();
        foreach ( $lines as $line ) {
            if ( strlen( $line ) > 1000 ) {
                $line = substr( $line, 0, 1000 );
            }
            $sanitized[] = $line;
        }

        return implode( "\n", $sanitized );
    }
}
