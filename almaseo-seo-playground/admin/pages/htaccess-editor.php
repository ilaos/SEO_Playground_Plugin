<?php
/**
 * AlmaSEO .htaccess Editor — Admin Page Template
 *
 * @package AlmaSEO
 * @since   8.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$editor   = AlmaSEO_Htaccess_Editor::get_instance();
$is_apache = $editor->is_apache();
$exists    = $editor->file_exists();
$writable  = $editor->is_writable();
$content   = $editor->read_file();
$file_path = $editor->get_file_path();
$backups   = get_option( AlmaSEO_Htaccess_Editor::BACKUP_OPTION, array() );
?>

<div class="wrap almaseo-htaccess-editor">
    <h1>
        <?php esc_html_e( '.htaccess Editor', 'almaseo-seo-playground' ); ?>
        <span class="almaseo-badge">AlmaSEO</span>
    </h1>

    <?php if ( function_exists( 'almaseo_render_help' ) ) :
        almaseo_render_help(
            __( 'Edit your .htaccess file directly from the WordPress admin. Backups are created automatically before every save.', 'almaseo-seo-playground' ),
            __( 'Be careful — an incorrect .htaccess rule can make your site inaccessible.', 'almaseo-seo-playground' )
        );
    else : ?>
        <p class="description">
            <?php esc_html_e( 'Edit your .htaccess file directly from the WordPress admin. Backups are created automatically before every save.', 'almaseo-seo-playground' ); ?>
        </p>
    <?php endif; ?>

    <?php /* ── Not-Apache warning ─────────────────────────────────────── */ ?>
    <?php if ( ! $is_apache ) : ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e( 'Warning:', 'almaseo-seo-playground' ); ?></strong>
                <?php esc_html_e( 'Your server does not appear to be running Apache or LiteSpeed. The .htaccess file may have no effect on your server.', 'almaseo-seo-playground' ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php /* ── Not-writable error ─────────────────────────────────────── */ ?>
    <?php if ( $exists && ! $writable ) : ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'Error:', 'almaseo-seo-playground' ); ?></strong>
                <?php esc_html_e( 'The .htaccess file is not writable. Please check file permissions.', 'almaseo-seo-playground' ); ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="almaseo-htaccess-container">

        <?php /* ── Danger notice card ─────────────────────────────────── */ ?>
        <div class="card almaseo-htaccess-warning-card">
            <p>
                <span class="dashicons dashicons-warning" style="color:#dba617;"></span>
                <strong><?php esc_html_e( 'Caution:', 'almaseo-seo-playground' ); ?></strong>
                <?php esc_html_e( 'Editing .htaccess incorrectly can take your site offline. A backup is created automatically before every save. If your site breaks, use FTP or File Manager to restore the file.', 'almaseo-seo-playground' ); ?>
            </p>
        </div>

        <?php /* ── Status card ────────────────────────────────────────── */ ?>
        <div class="card">
            <h2><?php esc_html_e( 'File Status', 'almaseo-seo-playground' ); ?></h2>
            <div class="almaseo-htaccess-status">
                <ul>
                    <li>
                        <?php esc_html_e( 'File exists:', 'almaseo-seo-playground' ); ?>
                        <strong><?php echo $exists ? esc_html__( 'Yes', 'almaseo-seo-playground' ) : esc_html__( 'No', 'almaseo-seo-playground' ); ?></strong>
                    </li>
                    <li>
                        <?php esc_html_e( 'Writable:', 'almaseo-seo-playground' ); ?>
                        <strong><?php echo $writable ? esc_html__( 'Yes', 'almaseo-seo-playground' ) : esc_html__( 'No', 'almaseo-seo-playground' ); ?></strong>
                    </li>
                    <li>
                        <?php esc_html_e( 'File path:', 'almaseo-seo-playground' ); ?>
                        <code><?php echo esc_html( $file_path ); ?></code>
                    </li>
                    <li>
                        <?php esc_html_e( 'Server:', 'almaseo-seo-playground' ); ?>
                        <strong><?php echo $is_apache ? esc_html__( 'Apache / LiteSpeed', 'almaseo-seo-playground' ) : esc_html__( 'Non-Apache', 'almaseo-seo-playground' ); ?></strong>
                    </li>
                </ul>
            </div>
        </div>

        <?php /* ── Editor card ────────────────────────────────────────── */ ?>
        <div class="card">
            <h2><?php esc_html_e( '.htaccess Content', 'almaseo-seo-playground' ); ?></h2>

            <div class="almaseo-htaccess-editor-wrap">
                <textarea id="htaccess-content"
                          name="htaccess_content"
                          rows="25"
                          class="large-text code"
                          <?php echo esc_attr( ( ! $writable && $exists ) ? 'readonly' : '' ); ?>
                ><?php echo esc_textarea( $content ); ?></textarea>
            </div>

            <div class="almaseo-htaccess-help">
                <h4><?php esc_html_e( 'Quick Reference:', 'almaseo-seo-playground' ); ?></h4>
                <ul>
                    <li><code>RewriteEngine On</code> &mdash; <?php esc_html_e( 'Enable URL rewriting', 'almaseo-seo-playground' ); ?></li>
                    <li><code>RewriteRule ^old$ /new [R=301,L]</code> &mdash; <?php esc_html_e( '301 redirect', 'almaseo-seo-playground' ); ?></li>
                    <li><code>Header set X-Content-Type-Options "nosniff"</code> &mdash; <?php esc_html_e( 'Security header', 'almaseo-seo-playground' ); ?></li>
                    <li><code># BEGIN WordPress ... # END WordPress</code> &mdash; <?php esc_html_e( 'WordPress core rules (do not remove)', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <div class="almaseo-htaccess-submit">
                <button type="button"
                        class="button button-primary"
                        id="htaccess-save"
                        <?php echo esc_attr( ( ! $writable && $exists ) ? 'disabled' : '' ); ?>>
                    <?php esc_html_e( 'Save Changes', 'almaseo-seo-playground' ); ?>
                </button>
                <span class="spinner"></span>
                <div class="almaseo-htaccess-message"></div>
            </div>
        </div>

        <?php /* ── Backups card ───────────────────────────────────────── */ ?>
        <div class="card">
            <h2><?php esc_html_e( 'Backups', 'almaseo-seo-playground' ); ?></h2>

            <?php if ( empty( $backups ) ) : ?>
                <p class="description"><?php esc_html_e( 'No backups yet. A backup will be created automatically when you save changes.', 'almaseo-seo-playground' ); ?></p>
            <?php else : ?>
                <p class="description"><?php esc_html_e( 'Select a backup to restore. The current .htaccess will be backed up before restoring.', 'almaseo-seo-playground' ); ?></p>

                <div class="almaseo-htaccess-backup-selector">
                    <select id="htaccess-backup-select">
                        <option value=""><?php esc_html_e( '-- Select a backup --', 'almaseo-seo-playground' ); ?></option>
                        <?php foreach ( $backups as $i => $backup ) : ?>
                            <option value="<?php echo esc_attr( $i ); ?>">
                                <?php
                                    printf(
                                        /* translators: %1$d: backup number, %2$s: timestamp */
                                        esc_html__( 'Backup #%1$d — %2$s', 'almaseo-seo-playground' ),
                                        intval($i + 1),
                                        esc_html( $backup['timestamp'] )
                                    );
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="button" class="button" id="htaccess-restore">
                        <?php esc_html_e( 'Restore Selected Backup', 'almaseo-seo-playground' ); ?>
                    </button>
                    <span class="spinner"></span>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- .almaseo-htaccess-container -->
</div><!-- .wrap -->
