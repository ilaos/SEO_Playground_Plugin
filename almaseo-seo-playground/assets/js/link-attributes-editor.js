/**
 * AlmaSEO Link Attributes — Block Editor Script
 *
 * Registers a custom RichText format type that piggybacks on <a> tags
 * to expose nofollow / sponsored / ugc rel-attribute toggles inside the
 * block-editor toolbar whenever a link is active.
 *
 * @package AlmaSEO
 * @since   8.4.0
 */
( function () {
    'use strict';

    /* ── WordPress dependencies ─────────────────────────────────────── */
    var el              = wp.element.createElement;
    var Fragment        = wp.element.Fragment;
    var useState        = wp.element.useState;
    var useEffect       = wp.element.useEffect;
    var useCallback     = wp.element.useCallback;
    var registerFormat  = wp.richText.registerFormatType;
    var applyFormat     = wp.richText.applyFormat;
    var getActiveFormat = wp.richText.getActiveFormat;
    var RichTextToolbarButton = wp.blockEditor.RichTextToolbarButton;
    var Popover         = wp.components.Popover;
    var CheckboxControl = wp.components.CheckboxControl;
    var __              = wp.i18n.__;

    /* ── Helpers ────────────────────────────────────────────────────── */

    /**
     * Parse a space-separated rel string into a Set of tokens.
     */
    function parseRel( relString ) {
        if ( ! relString ) {
            return new Set();
        }
        var tokens = relString.split( /\s+/ ).filter( Boolean );
        return new Set( tokens );
    }

    /**
     * Toggle a single token in a Set and return the joined string.
     */
    function toggleToken( relSet, token, enabled ) {
        var copy = new Set( relSet );
        if ( enabled ) {
            copy.add( token );
        } else {
            copy.delete( token );
        }
        return Array.from( copy ).join( ' ' );
    }

    /* ── Format type registration ───────────────────────────────────── */

    var FORMAT_NAME = 'almaseo/link-rel';

    registerFormat( FORMAT_NAME, {
        title:       __( 'Link Rel Attributes', 'almaseo' ),
        tagName:     'a',
        className:   null,       // match ANY <a>, not just a specific class
        interactive: true,

        /* Attributes we want to read / write on the <a> element */
        attributes: {
            href:   'href',
            target: 'target',
            rel:    'rel'
        },

        /**
         * The edit component renders a toolbar button + popover.
         *
         * It only shows when a core link format is active (the user has
         * placed the cursor inside an existing <a> tag).
         */
        edit: function AlmaSEOLinkRelEdit( props ) {
            var value            = props.value;
            var onChange          = props.onChange;
            var isActive         = props.isActive;
            var activeAttributes = props.activeAttributes || {};

            var showPopover  = useState( false );
            var isOpen       = showPopover[0];
            var setIsOpen    = showPopover[1];

            // Nothing to do if no link is currently selected.
            if ( ! isActive ) {
                // Close popover when link is deselected.
                if ( isOpen ) {
                    setIsOpen( false );
                }
                return null;
            }

            /* ── Current state ──────────────────────────────────────── */
            var currentRel   = activeAttributes.rel || '';
            var relTokens    = parseRel( currentRel );
            var hasNofollow  = relTokens.has( 'nofollow' );
            var hasSponsored = relTokens.has( 'sponsored' );
            var hasUgc       = relTokens.has( 'ugc' );
            var anyActive    = hasNofollow || hasSponsored || hasUgc;

            /* ── Updater ────────────────────────────────────────────── */
            function updateRel( token, enabled ) {
                var newRel   = toggleToken( relTokens, token, enabled );

                // Build new attributes — keep everything the core link
                // already set (href, target, etc.) and just update rel.
                var newAttrs = {};
                Object.keys( activeAttributes ).forEach( function ( k ) {
                    newAttrs[ k ] = activeAttributes[ k ];
                } );

                if ( newRel ) {
                    newAttrs.rel = newRel;
                } else {
                    delete newAttrs.rel;
                }

                onChange(
                    applyFormat( value, {
                        type: FORMAT_NAME,
                        attributes: newAttrs
                    } )
                );
            }

            /* ── Render ─────────────────────────────────────────────── */
            return el(
                Fragment,
                null,

                /* Toolbar button */
                el( RichTextToolbarButton, {
                    icon:    'admin-links',
                    title:   __( 'Link Attributes (nofollow / sponsored / ugc)', 'almaseo' ),
                    onClick: function () {
                        setIsOpen( ! isOpen );
                    },
                    isActive: anyActive,
                    className: anyActive ? 'almaseo-link-attrs-active' : ''
                } ),

                /* Popover with checkboxes */
                isOpen && el(
                    Popover,
                    {
                        position:     'bottom center',
                        onClose:      function () { setIsOpen( false ); },
                        focusOnMount: 'container',
                        className:    'almaseo-link-attrs-popover'
                    },
                    el(
                        'div',
                        { className: 'almaseo-link-attrs-panel' },

                        el( 'h4', null, __( 'Link Rel Attributes', 'almaseo' ) ),

                        el( CheckboxControl, {
                            label:    'nofollow',
                            help:     __( 'Tell search engines not to follow this link.', 'almaseo' ),
                            checked:  hasNofollow,
                            onChange:  function ( val ) { updateRel( 'nofollow',  val ); }
                        } ),

                        el( CheckboxControl, {
                            label:    'sponsored',
                            help:     __( 'Mark as a paid or sponsored link.', 'almaseo' ),
                            checked:  hasSponsored,
                            onChange:  function ( val ) { updateRel( 'sponsored', val ); }
                        } ),

                        el( CheckboxControl, {
                            label:    'ugc',
                            help:     __( 'Mark as user-generated content.', 'almaseo' ),
                            checked:  hasUgc,
                            onChange:  function ( val ) { updateRel( 'ugc',       val ); }
                        } )
                    )
                )
            );
        }
    } );
} )();
