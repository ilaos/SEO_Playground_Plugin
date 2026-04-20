<?php
/**
 * AlmaSEO LLMs.txt Editor Admin Page
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$content = get_option( 'almaseo_llms_txt_content', '' );
$mode    = get_option( 'almaseo_llms_txt_mode', 'virtual' );

if ( empty( $content ) ) {
    $content = AlmaSEO_LLMS_Txt_Controller::get_default_content();
}
?>
<div class="wrap almaseo-llms-txt-wrap">
    <h1><?php esc_html_e( 'LLMs.txt Editor', 'almaseo-seo-playground' ); ?></h1>
    <p class="description">
        <?php esc_html_e( 'The llms.txt file helps AI language models understand your site structure and content. Similar to robots.txt for search engines, this file guides LLMs on how to interact with your content.', 'almaseo-seo-playground' ); ?>
    </p>

    <div class="almaseo-llms-txt-notice" id="almaseo-llms-txt-notice" style="display:none;"></div>

    <div class="almaseo-llms-txt-mode">
        <h3><?php esc_html_e( 'Mode', 'almaseo-seo-playground' ); ?></h3>
        <label>
            <input type="radio" name="llms_txt_mode" value="virtual" <?php checked( $mode, 'virtual' ); ?> />
            <?php esc_html_e( 'Virtual (serve dynamically via WordPress)', 'almaseo-seo-playground' ); ?>
        </label>
        <br>
        <label>
            <input type="radio" name="llms_txt_mode" value="disabled" <?php checked( $mode, 'disabled' ); ?> />
            <?php esc_html_e( 'Disabled (do not serve llms.txt)', 'almaseo-seo-playground' ); ?>
        </label>
    </div>

    <div class="almaseo-llms-txt-editor-wrap">
        <h3><?php esc_html_e( 'Content', 'almaseo-seo-playground' ); ?></h3>
        <textarea id="almaseo-llms-txt-content" class="large-text code" rows="20"><?php echo esc_textarea( $content ); ?></textarea>
    </div>

    <div class="almaseo-llms-txt-actions">
        <button type="button" class="button button-primary" id="almaseo-llms-txt-save">
            <?php esc_html_e( 'Save', 'almaseo-seo-playground' ); ?>
        </button>
        <button type="button" class="button" id="almaseo-llms-txt-generate">
            <?php esc_html_e( 'Auto-Generate from Content', 'almaseo-seo-playground' ); ?>
        </button>
        <a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" class="button">
            <?php esc_html_e( 'View llms.txt', 'almaseo-seo-playground' ); ?>
        </a>
        <span class="spinner" id="almaseo-llms-txt-spinner"></span>
    </div>
</div>
