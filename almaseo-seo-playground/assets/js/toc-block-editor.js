/**
 * AlmaSEO — Table of Contents Block (Editor)
 *
 * Vanilla JS Gutenberg block — no build step required.
 * Uses wp.element.createElement for all rendering.
 *
 * @package AlmaSEO
 * @since   8.3.0
 */
( function () {
    'use strict';

    var el                = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody         = wp.components.PanelBody;
    var TextControl       = wp.components.TextControl;
    var CheckboxControl   = wp.components.CheckboxControl;
    var SelectControl     = wp.components.SelectControl;
    var ToggleControl     = wp.components.ToggleControl;
    var __                = wp.i18n.__;

    /* ── Heading-level helpers ─────────────────────────────────────── */

    var ALL_LEVELS = [ 2, 3, 4, 5, 6 ];

    function hasLevel( headingLevels, level ) {
        return headingLevels.indexOf( level ) !== -1;
    }

    function toggleLevel( headingLevels, level ) {
        if ( hasLevel( headingLevels, level ) ) {
            return headingLevels.filter( function ( l ) { return l !== level; } );
        }
        return headingLevels.concat( [ level ] ).sort( function ( a, b ) { return a - b; } );
    }

    /* ── Block Registration ────────────────────────────────────────── */

    registerBlockType( 'almaseo/toc', {

        title:       __( 'Table of Contents', 'almaseo' ),
        description: __( 'Auto-generated table of contents from post headings.', 'almaseo' ),
        icon:        'list-view',
        category:    'widgets',
        keywords:    [
            __( 'toc', 'almaseo' ),
            __( 'table of contents', 'almaseo' ),
            __( 'headings', 'almaseo' ),
            __( 'navigation', 'almaseo' ),
        ],

        attributes: {
            title: {
                type: 'string',
                default: 'Table of Contents',
            },
            headingLevels: {
                type: 'array',
                default: [ 2, 3 ],
                items: { type: 'integer' },
            },
            listStyle: {
                type: 'string',
                default: 'ol',
            },
            collapsible: {
                type: 'boolean',
                default: false,
            },
        },

        /* ── Edit Component ─────────────────────────────────────── */
        edit: function ( props ) {

            var attributes    = props.attributes;
            var setAttributes = props.setAttributes;

            /* Build a readable list of tracked levels */
            var trackedText = attributes.headingLevels.length
                ? attributes.headingLevels.map( function ( l ) { return 'H' + l; } ).join( ', ' )
                : __( 'None', 'almaseo' );

            /* Inspector sidebar controls */
            var inspector = el(
                InspectorControls,
                null,

                /* -- Title ------------------------------------------------- */
                el(
                    PanelBody,
                    { title: __( 'TOC Settings', 'almaseo' ), initialOpen: true },

                    el( TextControl, {
                        label:    __( 'Title', 'almaseo' ),
                        value:    attributes.title,
                        onChange: function ( val ) { setAttributes( { title: val } ); },
                    } ),

                    /* -- Heading Levels ------------------------------------ */
                    el( 'p', { style: { fontWeight: 600, marginBottom: '8px' } },
                        __( 'Heading Levels', 'almaseo' )
                    ),

                    ALL_LEVELS.map( function ( level ) {
                        return el( CheckboxControl, {
                            key:      'h' + level,
                            label:    'H' + level,
                            checked:  hasLevel( attributes.headingLevels, level ),
                            onChange: function () {
                                setAttributes( { headingLevels: toggleLevel( attributes.headingLevels, level ) } );
                            },
                        } );
                    } ),

                    /* -- List Style ---------------------------------------- */
                    el( SelectControl, {
                        label:    __( 'List Style', 'almaseo' ),
                        value:    attributes.listStyle,
                        options:  [
                            { label: __( 'Ordered List (1, 2, 3)', 'almaseo' ), value: 'ol' },
                            { label: __( 'Unordered List', 'almaseo' ),         value: 'ul' },
                        ],
                        onChange: function ( val ) { setAttributes( { listStyle: val } ); },
                    } ),

                    /* -- Collapsible --------------------------------------- */
                    el( ToggleControl, {
                        label:    __( 'Collapsible', 'almaseo' ),
                        help:     attributes.collapsible
                            ? __( 'Readers can collapse the table of contents.', 'almaseo' )
                            : __( 'Table of contents is always visible.', 'almaseo' ),
                        checked:  attributes.collapsible,
                        onChange: function ( val ) { setAttributes( { collapsible: val } ); },
                    } )
                )
            );

            /* Main editor placeholder */
            var placeholder = el(
                'div',
                { className: 'almaseo-toc-editor-placeholder' },
                el(
                    'div',
                    { className: 'almaseo-toc-editor-icon' },
                    el( wp.components.Dashicon || 'span', { icon: 'list-view', className: 'dashicons dashicons-list-view almaseo-toc-dashicon' } )
                ),
                el(
                    'p',
                    { className: 'almaseo-toc-editor-title' },
                    attributes.title || __( 'Table of Contents', 'almaseo' )
                ),
                el(
                    'p',
                    { className: 'almaseo-toc-editor-desc' },
                    __( 'Auto-generated from your post headings on the frontend.', 'almaseo' )
                ),
                el(
                    'p',
                    { className: 'almaseo-toc-editor-meta' },
                    __( 'Tracking: ', 'almaseo' ) + trackedText +
                    ' \u00B7 ' +
                    ( attributes.listStyle === 'ol'
                        ? __( 'Ordered', 'almaseo' )
                        : __( 'Unordered', 'almaseo' ) ) +
                    ( attributes.collapsible ? ' \u00B7 ' + __( 'Collapsible', 'almaseo' ) : '' )
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
