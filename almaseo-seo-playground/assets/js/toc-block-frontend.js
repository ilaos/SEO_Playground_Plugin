/**
 * AlmaSEO — Table of Contents Block (Frontend Smooth Scroll)
 *
 * Adds smooth-scroll behaviour to all TOC anchor links and updates
 * the URL hash without triggering a page jump.
 *
 * @package AlmaSEO
 * @since   8.3.0
 */
( function () {
    'use strict';

    document.addEventListener( 'DOMContentLoaded', function () {

        var links = document.querySelectorAll( '.almaseo-toc-block a[href^="#"]' );

        if ( ! links.length ) {
            return;
        }

        for ( var i = 0; i < links.length; i++ ) {
            links[ i ].addEventListener( 'click', handleClick );
        }
    } );

    function handleClick( e ) {
        var href = this.getAttribute( 'href' );
        if ( ! href || href.length < 2 ) {
            return;
        }

        var targetId = href.substring( 1 );
        var target   = document.getElementById( targetId );

        if ( ! target ) {
            return;
        }

        e.preventDefault();

        target.scrollIntoView( {
            behavior: 'smooth',
            block:    'start',
        } );

        /* Update the URL hash without jumping */
        if ( history.pushState ) {
            history.pushState( null, null, href );
        }
    }
} )();
