/**
 * AlmaSEO — Breadcrumbs Block (Editor)
 *
 * Vanilla JS Gutenberg block — no build step required.
 * Uses wp.element.createElement for all rendering.
 *
 * @package AlmaSEO
 * @since   8.4.0
 */
( function () {
    'use strict';

    var el                = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody         = wp.components.PanelBody;
    var TextControl       = wp.components.TextControl;
    var ToggleControl     = wp.components.ToggleControl;
    var __                = wp.i18n.__;

    /* ── Block Registration ────────────────────────────────────────── */

    registerBlockType( 'almaseo/breadcrumbs', {

        title:       __( 'Breadcrumbs', 'almaseo' ),
        description: __( 'Display breadcrumb navigation with schema markup.', 'almaseo' ),
        icon:        'admin-links',
        category:    'widgets',
        keywords:    [
            __( 'breadcrumbs', 'almaseo' ),
            __( 'navigation', 'almaseo' ),
            __( 'schema', 'almaseo' ),
        ],

        attributes: {
            separator: {
                type:    'string',
                default: '>',
            },
            homeText: {
                type:    'string',
                default: 'Home',
            },
            showCurrent: {
                type:    'boolean',
                default: true,
            },
            showSchema: {
                type:    'boolean',
                default: true,
            },
        },

        /* ── Edit Component ─────────────────────────────────────── */
        edit: function ( props ) {

            var attributes    = props.attributes;
            var setAttributes = props.setAttributes;

            /* Build preview breadcrumb path */
            var sep         = ' ' + ( attributes.separator || '>' ) + ' ';
            var homeLabel   = attributes.homeText || __( 'Home', 'almaseo' );
            var previewPath = homeLabel + sep + __( 'Category', 'almaseo' ) + sep + __( 'Post Title', 'almaseo' );

            /* Inspector sidebar controls */
            var inspector = el(
                InspectorControls,
                null,
                el(
                    PanelBody,
                    { title: __( 'Breadcrumb Settings', 'almaseo' ), initialOpen: true },

                    el( TextControl, {
                        label:    __( 'Separator', 'almaseo' ),
                        help:     __( 'Character(s) between breadcrumb items.', 'almaseo' ),
                        value:    attributes.separator,
                        onChange: function ( val ) { setAttributes( { separator: val } ); },
                    } ),

                    el( TextControl, {
                        label:    __( 'Home Text', 'almaseo' ),
                        help:     __( 'Label for the home page link.', 'almaseo' ),
                        value:    attributes.homeText,
                        onChange: function ( val ) { setAttributes( { homeText: val } ); },
                    } ),

                    el( ToggleControl, {
                        label:    __( 'Show Current Page', 'almaseo' ),
                        help:     attributes.showCurrent
                            ? __( 'The current page title is shown at the end.', 'almaseo' )
                            : __( 'The current page title is hidden.', 'almaseo' ),
                        checked:  attributes.showCurrent,
                        onChange: function ( val ) { setAttributes( { showCurrent: val } ); },
                    } ),

                    el( ToggleControl, {
                        label:    __( 'Schema Markup', 'almaseo' ),
                        help:     attributes.showSchema
                            ? __( 'JSON-LD BreadcrumbList schema is output on the frontend.', 'almaseo' )
                            : __( 'No schema markup will be added.', 'almaseo' ),
                        checked:  attributes.showSchema,
                        onChange: function ( val ) { setAttributes( { showSchema: val } ); },
                    } )
                )
            );

            /* Main editor placeholder */
            var placeholder = el(
                'div',
                { className: 'almaseo-breadcrumbs-editor-placeholder' },

                /* Icon */
                el(
                    'div',
                    { className: 'almaseo-breadcrumbs-editor-icon' },
                    el( 'span', { className: 'dashicons dashicons-admin-links almaseo-breadcrumbs-dashicon' } )
                ),

                /* Title */
                el(
                    'p',
                    { className: 'almaseo-breadcrumbs-editor-title' },
                    __( 'Breadcrumbs', 'almaseo' )
                ),

                /* Description */
                el(
                    'p',
                    { className: 'almaseo-breadcrumbs-editor-desc' },
                    __( 'Breadcrumb navigation will appear here on the frontend.', 'almaseo' )
                ),

                /* Preview path */
                el(
                    'div',
                    { className: 'almaseo-breadcrumbs-editor-preview' },
                    el( 'span', { className: 'almaseo-breadcrumbs-preview-home' }, homeLabel ),
                    el( 'span', { className: 'almaseo-breadcrumbs-preview-sep' }, ' ' + ( attributes.separator || '>' ) + ' ' ),
                    el( 'span', { className: 'almaseo-breadcrumbs-preview-mid' }, __( 'Category', 'almaseo' ) ),
                    el( 'span', { className: 'almaseo-breadcrumbs-preview-sep' }, ' ' + ( attributes.separator || '>' ) + ' ' ),
                    el(
                        'span',
                        { className: 'almaseo-breadcrumbs-preview-current' },
                        attributes.showCurrent ? __( 'Post Title', 'almaseo' ) : ''
                    )
                ),

                /* Meta info */
                el(
                    'p',
                    { className: 'almaseo-breadcrumbs-editor-meta' },
                    __( 'Schema: ', 'almaseo' ) + ( attributes.showSchema ? __( 'On', 'almaseo' ) : __( 'Off', 'almaseo' ) ) +
                    ' \u00B7 ' +
                    __( 'Separator: ', 'almaseo' ) + ( attributes.separator || '>' )
                )
            );

            return el( wp.element.Fragment, null, inspector, placeholder );
        },

        /* ── Save (server-side rendered) ────────────────────────── */
        save: function () {
            return null;
        },
    } );
} )();
