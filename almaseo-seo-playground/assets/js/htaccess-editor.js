/**
 * AlmaSEO .htaccess Editor — Admin JavaScript
 *
 * Handles save / restore AJAX operations with confirmation dialogs
 * and feedback notifications.
 *
 * @package AlmaSEO
 * @since   8.4.0
 */
( function ( $ ) {
    'use strict';

    var HtaccessEditor = {

        /* ── Initialization ─────────────────────────────────────────── */

        init: function () {
            this.$saveBtn       = $( '#htaccess-save' );
            this.$saveSpin      = this.$saveBtn.siblings( '.spinner' );
            this.$message       = $( '.almaseo-htaccess-message' );
            this.$textarea      = $( '#htaccess-content' );
            this.$restoreBtn    = $( '#htaccess-restore' );
            this.$restoreSpin   = this.$restoreBtn.siblings( '.spinner' );
            this.$backupSelect  = $( '#htaccess-backup-select' );

            this.bindEvents();
        },

        bindEvents: function () {
            this.$saveBtn.on( 'click', this.handleSave.bind( this ) );
            this.$restoreBtn.on( 'click', this.handleRestore.bind( this ) );

            // Clear notification when textarea changes.
            this.$textarea.on( 'input', function () {
                this.$message.text( '' ).removeClass( 'success error info' );
            }.bind( this ) );
        },

        /* ── Save ───────────────────────────────────────────────────── */

        handleSave: function ( e ) {
            e.preventDefault();

            if ( ! confirm( almaseoHtaccess.strings.confirmSave ) ) {
                return;
            }

            var self = this;
            var content = this.$textarea.val();

            this.$saveBtn.prop( 'disabled', true );
            this.$saveSpin.addClass( 'is-active' );
            this.showMessage( almaseoHtaccess.strings.saving, 'info' );

            $.post( almaseoHtaccess.ajaxUrl, {
                action:  'almaseo_htaccess_save',
                nonce:   almaseoHtaccess.nonce,
                content: content
            }, function ( response ) {
                self.$saveBtn.prop( 'disabled', false );
                self.$saveSpin.removeClass( 'is-active' );

                if ( response.success ) {
                    self.showMessage( response.data.message, 'success' );
                    self.updateBackupsDropdown( response.data.backups );

                    // Auto-hide after 5 s.
                    setTimeout( function () {
                        self.$message.fadeOut( function () {
                            $( this ).text( '' ).removeClass( 'success error info' ).show();
                        } );
                    }, 5000 );
                } else {
                    self.showMessage( response.data.message || almaseoHtaccess.strings.error, 'error' );
                }
            } ).fail( function () {
                self.$saveBtn.prop( 'disabled', false );
                self.$saveSpin.removeClass( 'is-active' );
                self.showMessage( almaseoHtaccess.strings.error, 'error' );
            } );
        },

        /* ── Restore ────────────────────────────────────────────────── */

        handleRestore: function ( e ) {
            e.preventDefault();

            var index = this.$backupSelect.val();
            if ( index === '' || index === null ) {
                alert( almaseoHtaccess.strings.selectBackup );
                return;
            }

            if ( ! confirm( almaseoHtaccess.strings.confirmRestore ) ) {
                return;
            }

            var self = this;

            this.$restoreBtn.prop( 'disabled', true );
            this.$restoreSpin.addClass( 'is-active' );
            this.showMessage( almaseoHtaccess.strings.restoring, 'info' );

            $.post( almaseoHtaccess.ajaxUrl, {
                action:       'almaseo_htaccess_restore',
                nonce:        almaseoHtaccess.nonce,
                backup_index: parseInt( index, 10 )
            }, function ( response ) {
                self.$restoreBtn.prop( 'disabled', false );
                self.$restoreSpin.removeClass( 'is-active' );

                if ( response.success ) {
                    self.showMessage( response.data.message, 'success' );
                    self.$textarea.val( response.data.content );
                    self.updateBackupsDropdown( response.data.backups );

                    setTimeout( function () {
                        self.$message.fadeOut( function () {
                            $( this ).text( '' ).removeClass( 'success error info' ).show();
                        } );
                    }, 5000 );
                } else {
                    self.showMessage( response.data.message || almaseoHtaccess.strings.error, 'error' );
                }
            } ).fail( function () {
                self.$restoreBtn.prop( 'disabled', false );
                self.$restoreSpin.removeClass( 'is-active' );
                self.showMessage( almaseoHtaccess.strings.error, 'error' );
            } );
        },

        /* ── Helpers ────────────────────────────────────────────────── */

        showMessage: function ( text, type ) {
            this.$message
                .text( text )
                .removeClass( 'success error info' )
                .addClass( type );
        },

        /**
         * Rebuild the backup <select> after a save or restore.
         */
        updateBackupsDropdown: function ( backups ) {
            if ( ! this.$backupSelect.length ) {
                // The dropdown may not exist yet (first save). Reload page.
                if ( backups && backups.length ) {
                    location.reload();
                }
                return;
            }

            this.$backupSelect.empty();
            this.$backupSelect.append(
                $( '<option>' ).val( '' ).text( '-- Select a backup --' )
            );

            var $select = this.$backupSelect;
            if ( backups && backups.length ) {
                $.each( backups, function ( i, b ) {
                    $select.append(
                        $( '<option>' ).val( b.index ).text( 'Backup #' + ( b.index + 1 ) + ' \u2014 ' + b.timestamp )
                    );
                } );
            }
        }
    };

    $( document ).ready( function () {
        HtaccessEditor.init();
    } );

} )( jQuery );
