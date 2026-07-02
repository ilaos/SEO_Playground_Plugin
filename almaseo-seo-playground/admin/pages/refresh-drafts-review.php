<?php
/**
 * Refresh Drafts – Review Page Template
 *
 * Displays the section-by-section diff for a single draft.
 * The $draft variable is set by the controller before this file is included.
 *
 * @package AlmaSEO
 * @since   7.0.0
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- view/bootstrap file; locals and internal callbacks are not a public global API

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sections = json_decode( $draft->sections_json, true );
$post     = get_post( $draft->post_id );

// Drift: has the live post changed since this draft captured its baseline?
// Legacy drafts (no stored hash) can't be checked, so they report no drift.
$rd_drifted = false;
if ( ! empty( $draft->content_hash ) && $post && $draft->status !== 'applied' ) {
    $rd_drifted = ( md5( $post->post_content ) !== $draft->content_hash );
}
?>
<div class="wrap almaseo-rd-wrap almaseo-rd-review-wrap">

    <p>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-refresh-drafts' ) ); ?>"
           class="almaseo-rd-back">&larr; All Refreshes</a>
    </p>

    <h1 class="almaseo-rd-title">
        Review refresh for &ldquo;<?php echo esc_html( $post ? $post->post_title : '#' . $draft->post_id ); ?>&rdquo;
    </h1>

    <div class="almaseo-rd-howto">
        <strong>How it works:</strong>
        For each section below, the left column shows your current live content and the
        right column shows the suggested update. Toggle <em>Accept</em> on the sections
        you want to keep, then click <strong>Apply selected changes</strong> at the bottom.
        Sections you do not accept will remain unchanged.
    </div>

    <p class="almaseo-rd-meta">
        Status: <code><?php echo esc_html( $draft->status ); ?></code> &middot;
        Source: <code><?php echo esc_html( $draft->trigger_source ); ?></code> &middot;
        Created: <?php echo esc_html( $draft->created_at ); ?>
    </p>

    <?php if ( $rd_drifted ) : ?>
        <div class="notice notice-warning almaseo-rd-drift-warning" style="margin:16px 0;padding:12px 16px;border-left-width:4px;">
            <p style="margin:0;">
                <strong>&#9888; This post has changed since this refresh was created.</strong>
                The &ldquo;Current&rdquo; column below is a snapshot from when the draft was generated and may no
                longer match the live post. Applying will replace the live content with the reviewed version and
                <strong>discard any edits made in the meantime</strong> (the previous version is kept as a WordPress
                revision). You&rsquo;ll be asked to confirm before anything is applied.
            </p>
        </div>
    <?php endif; ?>

    <?php if ( $draft->status !== 'applied' ) : ?>
        <div class="almaseo-rd-actions-top">
            <button id="almaseo-rd-accept-all" class="button">Accept all</button>
            <button id="almaseo-rd-reject-all" class="button">Reject all</button>
        </div>
    <?php endif; ?>

    <div class="almaseo-rd-sections" id="almaseo-rd-sections" data-draft-id="<?php echo esc_attr( $draft->id ); ?>" data-drifted="<?php echo $rd_drifted ? '1' : '0'; ?>">
        <?php foreach ( $sections as $i => $sec ) :
            $changed = ! empty( $sec['changed'] );
            $cls     = $changed ? 'almaseo-rd-section changed' : 'almaseo-rd-section unchanged';
        ?>
        <div class="<?php echo esc_attr( $cls ); ?>" data-key="<?php echo esc_attr( $sec['key'] ); ?>">
            <div class="almaseo-rd-section-head">
                <span class="almaseo-rd-section-label">
                    <?php if ( $sec['heading'] ) : ?>
                        <?php echo wp_kses_post( $sec['heading'] ); ?>
                    <?php else : ?>
                        <em>(Intro / before first heading)</em>
                    <?php endif; ?>
                </span>
                <?php if ( $changed && $draft->status !== 'applied' ) : ?>
                    <label class="almaseo-rd-toggle">
                        <input type="checkbox" class="almaseo-rd-decision" value="accept" />
                        Accept
                    </label>
                <?php elseif ( ! $changed ) : ?>
                    <span class="almaseo-rd-unchanged-badge">Unchanged</span>
                <?php endif; ?>
            </div>

            <?php if ( $changed ) : ?>
            <div class="almaseo-rd-diff">
                <div class="almaseo-rd-col almaseo-rd-old">
                    <h4>Current</h4>
                    <div class="almaseo-rd-body"><?php echo wp_kses_post( $sec['old_body'] ); ?></div>
                </div>
                <div class="almaseo-rd-col almaseo-rd-new">
                    <h4>Proposed</h4>
                    <div class="almaseo-rd-body"><?php echo wp_kses_post( $sec['new_body'] ); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ( $draft->status !== 'applied' ) : ?>
    <div class="almaseo-rd-actions-bottom">
        <button id="almaseo-rd-apply" class="button button-primary button-hero">
            Apply selected changes
        </button>
        <button id="almaseo-rd-dismiss" class="button button-link-delete">
            Dismiss this draft
        </button>
    </div>
    <?php endif; ?>
</div>
