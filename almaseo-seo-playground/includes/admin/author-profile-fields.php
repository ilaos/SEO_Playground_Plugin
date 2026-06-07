<?php
/**
 * AlmaSEO Author Profile Fields
 *
 * Adds two optional fields to every user's WordPress profile screen:
 *   - Job Title  (e.g. "Founder & Principal")
 *   - Profile / Social URLs  (one per line — becomes schema sameAs)
 *
 * These enrich the linked author Person entity emitted in the page schema
 * (see almaseo_build_author_person_node() in schema-advanced-output.php).
 * Everything else on the author node — name, bio, avatar, author-archive URL —
 * comes from the user's existing WordPress profile, so these two fields are the
 * only manual additions needed to reach E-E-A-T author parity.
 *
 * Free feature: author identity in schema is table-stakes (Yoast/Rank Math do
 * it free). The Pro/agency layer is the dashboard-powered enhancement
 * (auto-fill author identity from the AlmaSEO client profile, AI bios, etc.) —
 * reserved as 'author_entity_dashboard' in license-helper.php, not enforced here.
 *
 * @package AlmaSEO
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the AlmaSEO author fields on the profile screen.
 *
 * @param WP_User $user
 */
if ( ! function_exists( 'almaseo_render_author_profile_fields' ) ) {
function almaseo_render_author_profile_fields( $user ) {
    if ( ! is_object( $user ) || empty( $user->ID ) ) {
        return;
    }

    $job_title    = get_user_meta( $user->ID, 'almaseo_author_job_title', true );
    $author_image = get_user_meta( $user->ID, 'almaseo_author_image', true );
    $same_as      = get_user_meta( $user->ID, 'almaseo_author_same_as', true );
    ?>
    <h2><?php esc_html_e( 'AlmaSEO Author Details', 'almaseo-seo-playground' ); ?></h2>
    <p class="description" style="margin-bottom: 12px;">
        <?php esc_html_e( 'Optional. These enrich the author (Person) schema on posts written by this user, strengthening E-E-A-T signals. Name and bio are taken from the fields above automatically; the photo falls back to the avatar/Gravatar above when no Author Photo URL is set.', 'almaseo-seo-playground' ); ?>
    </p>
    <table class="form-table" role="presentation">
        <tr>
            <th>
                <label for="almaseo_author_job_title"><?php esc_html_e( 'Job Title', 'almaseo-seo-playground' ); ?></label>
            </th>
            <td>
                <input type="text"
                       name="almaseo_author_job_title"
                       id="almaseo_author_job_title"
                       value="<?php echo esc_attr( $job_title ); ?>"
                       class="regular-text"
                       placeholder="<?php esc_attr_e( 'e.g. Founder & Principal', 'almaseo-seo-playground' ); ?>" />
                <p class="description"><?php esc_html_e( 'Added to the author schema as jobTitle.', 'almaseo-seo-playground' ); ?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="almaseo_author_image"><?php esc_html_e( 'Author Photo URL', 'almaseo-seo-playground' ); ?></label>
            </th>
            <td>
                <input type="url"
                       name="almaseo_author_image"
                       id="almaseo_author_image"
                       value="<?php echo esc_attr( $author_image ); ?>"
                       class="regular-text"
                       placeholder="https://example.com/author-photo.jpg" />
                <p class="description"><?php esc_html_e( 'Optional. Used as the author photo in schema (Person image). If left blank, the WordPress avatar / Gravatar is used instead. Recommended: a square image, at least 192x192px.', 'almaseo-seo-playground' ); ?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="almaseo_author_same_as"><?php esc_html_e( 'Profile / Social URLs', 'almaseo-seo-playground' ); ?></label>
            </th>
            <td>
                <textarea name="almaseo_author_same_as"
                          id="almaseo_author_same_as"
                          rows="4"
                          class="regular-text"
                          style="font-family: monospace;"
                          placeholder="https://www.linkedin.com/in/&#10;https://twitter.com/&#10;https://yoursite.com/about/team/jane"><?php echo esc_textarea( $same_as ); ?></textarea>
                <p class="description">
                    <?php esc_html_e( 'One URL per line (LinkedIn, X/Twitter, a bio/team page, etc.). Added to the author schema as sameAs to verify identity. The "Website" field above is included automatically.', 'almaseo-seo-playground' ); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}
}

/**
 * Save the AlmaSEO author fields.
 *
 * @param int $user_id
 * @return void
 */
if ( ! function_exists( 'almaseo_save_author_profile_fields' ) ) {
function almaseo_save_author_profile_fields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return;
    }

    // Job title — plain text.
    if ( isset( $_POST['almaseo_author_job_title'] ) ) {
        $job_title = sanitize_text_field( wp_unslash( $_POST['almaseo_author_job_title'] ) );
        if ( $job_title !== '' ) {
            update_user_meta( $user_id, 'almaseo_author_job_title', $job_title );
        } else {
            delete_user_meta( $user_id, 'almaseo_author_job_title' );
        }
    }

    // Author photo URL — single sanitized URL (overrides the avatar in schema).
    if ( isset( $_POST['almaseo_author_image'] ) ) {
        $image = esc_url_raw( trim( wp_unslash( $_POST['almaseo_author_image'] ) ) );
        if ( $image !== '' ) {
            update_user_meta( $user_id, 'almaseo_author_image', $image );
        } else {
            delete_user_meta( $user_id, 'almaseo_author_image' );
        }
    }

    // sameAs URLs — one per line, sanitized and re-joined.
    if ( isset( $_POST['almaseo_author_same_as'] ) ) {
        $raw   = wp_unslash( $_POST['almaseo_author_same_as'] );
        $lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
        $urls  = array();
        foreach ( $lines as $line ) {
            $clean = esc_url_raw( trim( $line ) );
            if ( $clean !== '' && ! in_array( $clean, $urls, true ) ) {
                $urls[] = $clean;
            }
        }
        if ( ! empty( $urls ) ) {
            update_user_meta( $user_id, 'almaseo_author_same_as', implode( "\n", $urls ) );
        } else {
            delete_user_meta( $user_id, 'almaseo_author_same_as' );
        }
    }
}
}

add_action( 'show_user_profile', 'almaseo_render_author_profile_fields' );
add_action( 'edit_user_profile', 'almaseo_render_author_profile_fields' );
add_action( 'personal_options_update', 'almaseo_save_author_profile_fields' );
add_action( 'edit_user_profile_update', 'almaseo_save_author_profile_fields' );
