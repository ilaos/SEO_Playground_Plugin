/**
 * AlmaSEO How-To Block — Gutenberg Editor Component
 *
 * Pure vanilla JS — no JSX, no build step.
 * Uses wp.element.createElement for all UI rendering.
 *
 * @package AlmaSEO
 * @since   8.4.0
 */
( function () {
    'use strict';

    var el                = wp.element.createElement;
    var Fragment          = wp.element.Fragment;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody         = wp.components.PanelBody;
    var TextControl       = wp.components.TextControl;
    var TextareaControl   = wp.components.TextareaControl;
    var Button            = wp.components.Button;
    var __                = wp.i18n.__;

    registerBlockType( 'almaseo/howto', {
        title:       __( 'How-To', 'almaseo' ),
        description: __( 'Create step-by-step guides with HowTo schema markup.', 'almaseo' ),
        icon:        'editor-ol',
        category:    'widgets',
        keywords:    [
            __( 'how-to', 'almaseo' ),
            __( 'steps', 'almaseo' ),
            __( 'guide', 'almaseo' ),
            __( 'schema', 'almaseo' ),
        ],

        attributes: {
            steps: {
                type:    'array',
                default: [],
            },
            totalTime: {
                type:    'string',
                default: '',
            },
            estimatedCost: {
                type:    'string',
                default: '',
            },
            currency: {
                type:    'string',
                default: 'USD',
            },
            supply: {
                type:    'array',
                default: [],
            },
            tool: {
                type:    'array',
                default: [],
            },
        },

        edit: function ( props ) {
            var attributes = props.attributes;
            var steps      = attributes.steps;
            var supply     = attributes.supply;
            var tool       = attributes.tool;

            /* ── Step helpers ──────────────────────────────────────────── */

            /**
             * Update a single field within a specific step.
             */
            function updateStep( index, field, value ) {
                var updated = steps.map( function ( item, i ) {
                    if ( i !== index ) {
                        return item;
                    }
                    var copy    = {};
                    copy.title   = item.title;
                    copy.content = item.content;
                    copy.image   = item.image;
                    copy[ field ] = value;
                    return copy;
                } );
                props.setAttributes( { steps: updated } );
            }

            /**
             * Add a new empty step.
             */
            function addStep() {
                var updated = steps.concat( [ { title: '', content: '', image: '' } ] );
                props.setAttributes( { steps: updated } );
            }

            /**
             * Remove a step by index.
             */
            function removeStep( index ) {
                var updated = steps.filter( function ( _, i ) {
                    return i !== index;
                } );
                props.setAttributes( { steps: updated } );
            }

            /* ── Supply helpers ────────────────────────────────────────── */

            function updateSupply( index, value ) {
                var updated = supply.map( function ( item, i ) {
                    return i === index ? value : item;
                } );
                props.setAttributes( { supply: updated } );
            }

            function addSupply() {
                props.setAttributes( { supply: supply.concat( [ '' ] ) } );
            }

            function removeSupply( index ) {
                var updated = supply.filter( function ( _, i ) {
                    return i !== index;
                } );
                props.setAttributes( { supply: updated } );
            }

            /* ── Tool helpers ──────────────────────────────────────────── */

            function updateTool( index, value ) {
                var updated = tool.map( function ( item, i ) {
                    return i === index ? value : item;
                } );
                props.setAttributes( { tool: updated } );
            }

            function addTool() {
                props.setAttributes( { tool: tool.concat( [ '' ] ) } );
            }

            function removeTool( index ) {
                var updated = tool.filter( function ( _, i ) {
                    return i !== index;
                } );
                props.setAttributes( { tool: updated } );
            }

            /* ── Sidebar (InspectorControls) ───────────────────────────── */

            /* Details panel. */
            var detailsPanel = el(
                PanelBody,
                { title: __( 'Details', 'almaseo' ), initialOpen: true },
                el( TextControl, {
                    label:       __( 'Total Time', 'almaseo' ),
                    value:       attributes.totalTime,
                    placeholder: 'PT30M',
                    help:        __( 'ISO 8601 duration (e.g. PT30M, PT1H30M)', 'almaseo' ),
                    onChange:    function ( val ) {
                        props.setAttributes( { totalTime: val } );
                    },
                } ),
                el( TextControl, {
                    label:    __( 'Estimated Cost', 'almaseo' ),
                    value:    attributes.estimatedCost,
                    onChange: function ( val ) {
                        props.setAttributes( { estimatedCost: val } );
                    },
                } ),
                el( TextControl, {
                    label:       __( 'Currency', 'almaseo' ),
                    value:       attributes.currency,
                    placeholder: 'USD',
                    onChange:    function ( val ) {
                        props.setAttributes( { currency: val } );
                    },
                } )
            );

            /* Supplies panel. */
            var supplyItems = supply.map( function ( item, index ) {
                return el(
                    'div',
                    { className: 'almaseo-howto-sidebar-list-item', key: 'supply-' + index },
                    el( TextControl, {
                        value:       item,
                        placeholder: __( 'Supply name', 'almaseo' ),
                        onChange:    function ( val ) { updateSupply( index, val ); },
                    } ),
                    el(
                        Button,
                        {
                            isSmall:       true,
                            isDestructive: true,
                            variant:       'link',
                            onClick:       function () { removeSupply( index ); },
                        },
                        __( 'Remove', 'almaseo' )
                    )
                );
            } );

            var suppliesPanel = el(
                PanelBody,
                { title: __( 'Supplies', 'almaseo' ), initialOpen: false },
                supplyItems,
                el(
                    Button,
                    { variant: 'secondary', isSmall: true, onClick: addSupply },
                    __( '+ Add Supply', 'almaseo' )
                )
            );

            /* Tools panel. */
            var toolItems = tool.map( function ( item, index ) {
                return el(
                    'div',
                    { className: 'almaseo-howto-sidebar-list-item', key: 'tool-' + index },
                    el( TextControl, {
                        value:       item,
                        placeholder: __( 'Tool name', 'almaseo' ),
                        onChange:    function ( val ) { updateTool( index, val ); },
                    } ),
                    el(
                        Button,
                        {
                            isSmall:       true,
                            isDestructive: true,
                            variant:       'link',
                            onClick:       function () { removeTool( index ); },
                        },
                        __( 'Remove', 'almaseo' )
                    )
                );
            } );

            var toolsPanel = el(
                PanelBody,
                { title: __( 'Tools', 'almaseo' ), initialOpen: false },
                toolItems,
                el(
                    Button,
                    { variant: 'secondary', isSmall: true, onClick: addTool },
                    __( '+ Add Tool', 'almaseo' )
                )
            );

            var sidebar = el(
                InspectorControls,
                null,
                detailsPanel,
                suppliesPanel,
                toolsPanel
            );

            /* ── Empty state ──────────────────────────────────────────── */
            if ( ! steps.length ) {
                return el(
                    Fragment,
                    null,
                    sidebar,
                    el(
                        'div',
                        { className: 'almaseo-howto-editor-placeholder' },
                        el( 'p', null, __( 'No steps yet. Click the button below to add your first step.', 'almaseo' ) ),
                        el(
                            Button,
                            { variant: 'primary', onClick: addStep },
                            __( 'Add Step', 'almaseo' )
                        )
                    )
                );
            }

            /* ── Step cards ────────────────────────────────────────────── */
            var stepCards = steps.map( function ( item, index ) {
                return el(
                    'div',
                    { className: 'almaseo-howto-editor-step', key: index },
                    el(
                        'div',
                        { className: 'almaseo-howto-editor-step-header' },
                        el(
                            'span',
                            { className: 'almaseo-howto-editor-step-number' },
                            __( 'Step ', 'almaseo' ) + ( index + 1 )
                        ),
                        el(
                            Button,
                            {
                                className:     'almaseo-howto-editor-remove',
                                variant:       'link',
                                isDestructive: true,
                                isSmall:       true,
                                onClick:       function () { removeStep( index ); },
                            },
                            __( 'Remove', 'almaseo' )
                        )
                    ),
                    el( TextareaControl, {
                        label:    __( 'Title', 'almaseo' ),
                        value:    item.title || '',
                        rows:     2,
                        onChange: function ( val ) { updateStep( index, 'title', val ); },
                    } ),
                    el( TextareaControl, {
                        label:    __( 'Content', 'almaseo' ),
                        value:    item.content || '',
                        rows:     3,
                        onChange: function ( val ) { updateStep( index, 'content', val ); },
                    } ),
                    el( TextControl, {
                        label:       __( 'Image URL (optional)', 'almaseo' ),
                        value:       item.image || '',
                        placeholder: 'https://',
                        onChange:    function ( val ) { updateStep( index, 'image', val ); },
                    } )
                );
            } );

            return el(
                Fragment,
                null,
                sidebar,
                el(
                    'div',
                    { className: 'almaseo-howto-editor-wrap' },
                    el( 'div', { className: 'almaseo-howto-editor-list' }, stepCards ),
                    el(
                        Button,
                        {
                            className: 'almaseo-howto-editor-add',
                            variant:   'secondary',
                            onClick:   addStep,
                        },
                        __( '+ Add Step', 'almaseo' )
                    )
                )
            );
        },

        save: function () {
            // Server-side rendered — return null.
            return null;
        },
    } );
} )();
