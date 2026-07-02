/**
 * AlmaSEO FAQ Block — Gutenberg Editor Component
 *
 * Pure vanilla JS — no JSX, no build step.
 * Uses wp.element.createElement for all UI rendering.
 *
 * @package AlmaSEO
 * @since   8.3.0
 */
( function () {
    'use strict';

    var el                = wp.element.createElement;
    var Fragment          = wp.element.Fragment;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody         = wp.components.PanelBody;
    var SelectControl     = wp.components.SelectControl;
    var TextareaControl   = wp.components.TextareaControl;
    var Button            = wp.components.Button;
    var __                = wp.i18n.__;

    registerBlockType( 'almaseo/faq', {
        title:       __( 'FAQ', 'almaseo' ),
        description: __( 'Add frequently asked questions with FAQPage schema markup.', 'almaseo' ),
        icon:        'editor-help',
        category:    'widgets',
        keywords:    [ __( 'faq', 'almaseo' ), __( 'questions', 'almaseo' ), __( 'schema', 'almaseo' ) ],

        attributes: {
            questions: {
                type:    'array',
                default: [],
            },
            layout: {
                type:    'string',
                default: 'accordion',
            },
        },

        edit: function ( props ) {
            var attributes = props.attributes;
            var questions  = attributes.questions;
            var layout     = attributes.layout;

            /**
             * Update a single field within a specific Q&A pair.
             */
            function updateQuestion( index, field, value ) {
                var updated = questions.map( function ( item, i ) {
                    if ( i !== index ) {
                        return item;
                    }
                    var copy = {};
                    copy.question = item.question;
                    copy.answer   = item.answer;
                    copy[ field ] = value;
                    return copy;
                } );
                props.setAttributes( { questions: updated } );
            }

            /**
             * Add a new empty Q&A pair.
             */
            function addQuestion() {
                var updated = questions.concat( [ { question: '', answer: '' } ] );
                props.setAttributes( { questions: updated } );
            }

            /**
             * Remove a Q&A pair by index.
             */
            function removeQuestion( index ) {
                var updated = questions.filter( function ( _, i ) {
                    return i !== index;
                } );
                props.setAttributes( { questions: updated } );
            }

            /* ── Sidebar controls ──────────────────────────────────────── */
            var sidebar = el(
                InspectorControls,
                null,
                el(
                    PanelBody,
                    { title: __( 'FAQ Settings', 'almaseo' ), initialOpen: true },
                    el( SelectControl, {
                        label:    __( 'Layout', 'almaseo' ),
                        value:    layout,
                        options:  [
                            { label: __( 'Accordion', 'almaseo' ), value: 'accordion' },
                            { label: __( 'List', 'almaseo' ),      value: 'list' },
                        ],
                        onChange: function ( val ) {
                            props.setAttributes( { layout: val } );
                        },
                    } )
                )
            );

            /* ── Empty state ───────────────────────────────────────────── */
            if ( ! questions.length ) {
                return el(
                    Fragment,
                    null,
                    sidebar,
                    el(
                        'div',
                        { className: 'almaseo-faq-editor-placeholder' },
                        el( 'p', null, __( 'No questions yet. Click the button below to add your first FAQ item.', 'almaseo' ) ),
                        el(
                            Button,
                            { variant: 'primary', onClick: addQuestion },
                            __( 'Add Question', 'almaseo' )
                        )
                    )
                );
            }

            /* ── Q&A pair list ─────────────────────────────────────────── */
            var qaPairs = questions.map( function ( item, index ) {
                return el(
                    'div',
                    { className: 'almaseo-faq-editor-pair', key: index },
                    el(
                        'div',
                        { className: 'almaseo-faq-editor-pair-header' },
                        el(
                            'span',
                            { className: 'almaseo-faq-editor-pair-number' },
                            __( 'Q', 'almaseo' ) + ( index + 1 )
                        ),
                        el(
                            Button,
                            {
                                className:   'almaseo-faq-editor-remove',
                                variant:     'link',
                                isDestructive: true,
                                isSmall:     true,
                                onClick:     function () { removeQuestion( index ); },
                            },
                            __( 'Remove', 'almaseo' )
                        )
                    ),
                    el( TextareaControl, {
                        label:    __( 'Question', 'almaseo' ),
                        value:    item.question || '',
                        rows:     2,
                        onChange: function ( val ) { updateQuestion( index, 'question', val ); },
                    } ),
                    el( TextareaControl, {
                        label:    __( 'Answer', 'almaseo' ),
                        value:    item.answer || '',
                        rows:     3,
                        onChange: function ( val ) { updateQuestion( index, 'answer', val ); },
                    } )
                );
            } );

            return el(
                Fragment,
                null,
                sidebar,
                el(
                    'div',
                    { className: 'almaseo-faq-editor-wrap' },
                    el( 'div', { className: 'almaseo-faq-editor-list' }, qaPairs ),
                    el(
                        Button,
                        {
                            className: 'almaseo-faq-editor-add',
                            variant:   'secondary',
                            onClick:   addQuestion,
                        },
                        __( '+ Add Question', 'almaseo' )
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
