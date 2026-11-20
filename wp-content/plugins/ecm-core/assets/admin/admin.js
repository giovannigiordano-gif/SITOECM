/**
 * ECM Core – admin: campi media (wp.media), ripetitori ordinabili, contatori di caratteri.
 *
 * @package ECM_Core
 */
( function ( $ ) {
	'use strict';

	var l10n = window.ecmAdmin || {};

	/* ------------------------------------------------------------------
	 * Campi media.
	 * ------------------------------------------------------------------ */

	function mimeMatches( att, library ) {
		if ( ! library ) {
			return true;
		}
		if ( 'image' === library ) {
			return 'image' === att.type;
		}
		return att.mime === library;
	}

	function renderPreview( att, library ) {
		var $wrap = $( '<div/>' );
		var thumb = '';

		if ( att.sizes ) {
			var size = att.sizes.medium || att.sizes.thumbnail || att.sizes.full;
			if ( size ) {
				thumb = size.url;
			}
		}

		if ( 'image' === att.type ) {
			$( '<div class="ecm-media__thumb"/>' ).append( $( '<img alt=""/>' ).attr( 'src', thumb || att.url ) ).appendTo( $wrap );
			$( '<p class="ecm-media__meta"/>' ).append( $( '<span class="ecm-media__name"/>' ).text( att.filename || '' ) ).appendTo( $wrap );
			return $wrap.children();
		}

		var $file = $( '<div class="ecm-media__file"/>' ).appendTo( $wrap );
		if ( thumb ) {
			$( '<img class="ecm-media__pdfthumb" alt=""/>' ).attr( 'src', thumb ).appendTo( $file );
		} else {
			$( '<span class="dashicons dashicons-pdf ecm-media__icon" aria-hidden="true"/>' ).appendTo( $file );
		}
		var $meta = $( '<p class="ecm-media__meta"/>' ).appendTo( $file );
		$( '<strong class="ecm-media__name"/>' ).text( att.filename || att.title || '' ).appendTo( $meta );
		if ( att.filesizeHumanReadable ) {
			$( '<span class="ecm-media__details"/>' ).text( att.filesizeHumanReadable ).appendTo( $meta );
		}
		$( '<a class="ecm-media__open" target="_blank" rel="noopener"/>' ).attr( 'href', att.url ).text( l10n.open || 'Apri' ).appendTo( $meta );
		return $wrap.children();
	}

	function emptyPreview( library ) {
		return $( '<p class="ecm-media__empty"/>' ).text( 'image' === library ? l10n.noImage : l10n.noFile );
	}

	function bindMedia( $field ) {
		var frame = null;
		var library = String( $field.data( 'library' ) || '' );
		var $input = $field.children( '.ecm-media__input' );
		var $preview = $field.children( '.ecm-media__preview' );

		function own( node ) {
			return $( node ).closest( '.ecm-media' )[ 0 ] === $field[ 0 ];
		}

		function open() {
			if ( ! window.wp || ! wp.media ) {
				return;
			}
			if ( ! frame ) {
				var options = {
					title: $field.data( 'frameTitle' ) || l10n.chooseFile,
					button: { text: $field.data( 'frameButton' ) || l10n.useFile },
					multiple: false
				};
				if ( library ) {
					options.library = { type: library };
				}
				frame = wp.media( options );
				frame.on( 'select', function () {
					var att = frame.state().get( 'selection' ).first().toJSON();
					if ( ! mimeMatches( att, library ) ) {
						window.alert( l10n.wrongType );
						return;
					}
					set( att );
				} );
				frame.on( 'open', function () {
					var id = parseInt( $input.val(), 10 );
					var selection = frame.state().get( 'selection' );
					selection.reset( id ? [ wp.media.attachment( id ) ] : [] );
				} );
			}
			frame.open();
		}

		function set( att ) {
			var previous = $input.val();
			$input.val( att.id );
			$preview.empty().append( renderPreview( att, library ) );
			$field.addClass( 'has-value' );
			if ( String( previous ) !== String( att.id ) ) {
				$field.trigger( 'ecm:media-change', [ att.id, previous ] );
			}
		}

		function clear() {
			var previous = $input.val();
			$input.val( '' );
			$preview.empty().append( emptyPreview( library ) );
			$field.removeClass( 'has-value' );
			$field.trigger( 'ecm:media-change', [ '', previous ] );
		}

		$field.on( 'click', '.ecm-media__select, .ecm-media__replace', function ( e ) {
			if ( ! own( this ) ) {
				return;
			}
			e.preventDefault();
			open();
		} );
		$field.on( 'click', '.ecm-media__thumb, .ecm-media__pdfthumb', function ( e ) {
			if ( ! own( this ) ) {
				return;
			}
			e.preventDefault();
			open();
		} );
		$field.on( 'click', '.ecm-media__remove', function ( e ) {
			if ( ! own( this ) ) {
				return;
			}
			e.preventDefault();
			clear();
		} );
	}

	function initMedia( ctx ) {
		$( ctx ).find( '.ecm-media' ).each( function () {
			var $field = $( this );
			if ( $field.data( 'ecmReady' ) ) {
				return;
			}
			$field.data( 'ecmReady', 1 );
			bindMedia( $field );
		} );
	}

	/* Brochure sostituita: avvisa che l'anteprima verrà ricalcolata. */
	function initBrochureNotice() {
		var $brochure = $( '.ecm-media[data-role="brochure"]' );
		var $preview = $( '.ecm-media[data-role="brochure-preview"]' );
		var $notice = $( '[data-ecm-brochure-notice]' );
		if ( ! $brochure.length || ! $preview.length || ! $notice.length ) {
			return;
		}
		var original = String( $brochure.children( '.ecm-media__input' ).val() || '' );
		var previewOriginal = String( $preview.children( '.ecm-media__input' ).val() || '' );

		function update() {
			var brochure = String( $brochure.children( '.ecm-media__input' ).val() || '' );
			var preview = String( $preview.children( '.ecm-media__input' ).val() || '' );
			var show = original && brochure !== original && preview && preview === previewOriginal;
			$notice.text( show ? l10n.brochureSwitch : '' ).prop( 'hidden', ! show );
		}
		$brochure.on( 'ecm:media-change', update );
		$preview.on( 'ecm:media-change', update );
	}

	/* ------------------------------------------------------------------
	 * Ripetitori.
	 * ------------------------------------------------------------------ */

	var rowCounter = 0;

	function initRepeaters( ctx ) {
		$( ctx ).find( '.ecm-repeater' ).each( function () {
			var $rep = $( this );
			if ( $rep.data( 'ecmReady' ) ) {
				return;
			}
			$rep.data( 'ecmReady', 1 );

			var $list = $rep.children( '.ecm-repeater__list' );
			var tpl = $rep.children( '.ecm-repeater__tpl' ).html() || '';

			function refresh() {
				var $rows = $list.children( '.ecm-repeater__row' );
				$rep.toggleClass( 'is-empty', 0 === $rows.length );
				$rows.each( function ( i ) {
					$( this ).children( '.ecm-repeater__handle' ).find( '.ecm-repeater__num' ).text( i + 1 );
				} );
			}

			if ( $.fn.sortable ) {
				$list.sortable( {
					handle: '.ecm-repeater__handle',
					items: '> .ecm-repeater__row',
					axis: 'y',
					placeholder: 'ecm-repeater__placeholder',
					forcePlaceholderSize: true,
					tolerance: 'pointer',
					update: refresh
				} );
			}

			$rep.on( 'click', '.ecm-repeater__add', function ( e ) {
				e.preventDefault();
				rowCounter++;
				var html = tpl.replace( /__i__/g, 'n' + Date.now() + rowCounter );
				var $row = $( $.parseHTML( $.trim( html ) ) );
				$list.append( $row );
				initMedia( $row );
				initCounters( $row );
				refresh();
				$row.find( 'input[type="text"], textarea' ).first().trigger( 'focus' );
			} );

			$rep.on( 'click', '.ecm-repeater__remove', function ( e ) {
				e.preventDefault();
				var $row = $( this ).closest( '.ecm-repeater__row' );
				var filled = $row.find( 'input[type="text"], textarea, input.ecm-media__input' ).filter( function () {
					return '' !== $.trim( this.value ) && '0' !== this.value;
				} ).length;
				if ( filled && ! window.confirm( l10n.confirmRemove ) ) {
					return;
				}
				$row.remove();
				refresh();
			} );

			refresh();
		} );
	}

	/* ------------------------------------------------------------------
	 * Contatori di caratteri.
	 * ------------------------------------------------------------------ */

	function initCounters( ctx ) {
		$( ctx ).find( '[data-ecm-counter]' ).each( function () {
			var el = this;
			if ( $( el ).data( 'ecmCounterReady' ) ) {
				return;
			}
			$( el ).data( 'ecmCounterReady', 1 );
			var max = parseInt( el.getAttribute( 'data-ecm-counter' ), 10 ) || 0;
			var $counter = $( '<span class="ecm-counter" aria-live="polite"/>' ).insertAfter( el );

			function update() {
				var n = el.value.length;
				$counter.text( n + ' / ' + max + ' ' + ( l10n.chars || '' ) );
				$counter.toggleClass( 'is-warn', n > max * 0.9 && n <= max ).toggleClass( 'is-over', n > max );
			}
			$( el ).on( 'input', update );
			update();
		} );
	}

	/* Il modulo "Aggiungi termine" viene svuotato via AJAX: aggiorna il contatore. */
	$( document ).ajaxComplete( function ( event, xhr, settings ) {
		if ( settings && 'string' === typeof settings.data && -1 !== settings.data.indexOf( 'action=add-tag' ) ) {
			$( '#addtag [data-ecm-counter]' ).trigger( 'input' );
			$( '#ecm_order' ).val( '0' );
		}
	} );

	$( function () {
		initMedia( document );
		initRepeaters( document );
		initCounters( document );
		initBrochureNotice();
	} );
}( jQuery ) );
