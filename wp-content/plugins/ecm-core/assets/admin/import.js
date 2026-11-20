/**
 * ECM Core – pagina Importazione: esegue gli step via AJAX, con avanzamento per i media.
 *
 * @package ECM_Core
 */
( function () {
	'use strict';

	var cfg = window.ecmImport || {};
	var i18n = cfg.i18n || {};
	var root = document.getElementById( 'ecm-import' );
	if ( ! root ) {
		return;
	}
	var logBox = root.querySelector( '.ecm-import__log' );
	var busy = false;
	var firstLog = true;

	function el( tag, cls, text ) {
		var node = document.createElement( tag );
		if ( cls ) {
			node.className = cls;
		}
		if ( undefined !== text && null !== text ) {
			node.textContent = String( text );
		}
		return node;
	}

	function options() {
		var out = {};
		root.querySelectorAll( '[data-ecm-opt]' ).forEach( function ( input ) {
			out[ input.getAttribute( 'data-ecm-opt' ) ] = input.checked ? '1' : '';
		} );
		return out;
	}

	function setBusy( state ) {
		busy = state;
		root.querySelectorAll( 'button[data-ecm-step], button[data-ecm-run-all]' ).forEach( function ( b ) {
			b.disabled = state;
		} );
	}

	function setState( step, text, cls ) {
		var row = root.querySelector( '[data-step-row="' + step + '"] .ecm-import__state' );
		if ( row ) {
			row.textContent = text;
			row.className = 'ecm-import__state' + ( cls ? ' ' + cls : '' );
		}
	}

	function post( step, extra ) {
		var body = new FormData();
		body.append( 'action', 'ecm_import_step' );
		body.append( '_ajax_nonce', cfg.nonce );
		body.append( 'step', step );
		var opts = Object.assign( {}, options(), extra || {} );
		Object.keys( opts ).forEach( function ( k ) {
			body.append( k, opts[ k ] );
		} );
		return fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( r ) {
			return r.json();
		} );
	}

	function summaryLine( s ) {
		var label = ( cfg.steps && cfg.steps[ s.step ] ) || s.step;
		return label + ': ' + s.created + ' ' + i18n.created + ', ' + s.updated + ' ' + i18n.updated + ', ' + s.skipped + ' ' + i18n.skipped + ', ' + ( s.errors || [] ).length + ' ' + i18n.errors + ( s.time !== undefined ? ' (' + s.time + ' s)' : '' );
	}

	function log( s ) {
		if ( firstLog ) {
			logBox.textContent = '';
			firstLog = false;
		}
		var box = el( 'div', 'ecm-import__entry' + ( s.errors && s.errors.length ? ' has-errors' : '' ) );
		box.appendChild( el( 'p', 'ecm-import__summary', summaryLine( s ) ) );

		if ( s.errors && s.errors.length ) {
			var ul = el( 'ul', 'ecm-import__errors' );
			s.errors.forEach( function ( e ) {
				ul.appendChild( el( 'li', '', e ) );
			} );
			box.appendChild( ul );
		}

		if ( 'verify' === s.step ) {
			if ( s.counts ) {
				var table = el( 'table', 'widefat striped ecm-import__verify' );
				var tbody = el( 'tbody' );
				Object.keys( s.counts ).forEach( function ( k ) {
					var tr = el( 'tr' );
					tr.appendChild( el( 'th', '', k ) );
					tr.appendChild( el( 'td', '', s.counts[ k ] ) );
					tbody.appendChild( tr );
				} );
				table.appendChild( tbody );
				box.appendChild( table );
			}
			var problems = s.problems || [];
			box.appendChild( el( 'p', problems.length ? 'ecm-import__problems-title' : 'ecm-import__ok', problems.length ? i18n.problems + ' (' + problems.length + ')' : i18n.noProblem ) );
			if ( problems.length ) {
				var pl = el( 'ul', 'ecm-import__errors' );
				problems.forEach( function ( p ) {
					pl.appendChild( el( 'li', '', p ) );
				} );
				box.appendChild( pl );
			}
		}

		if ( s.log && s.log.length && 'verify' !== s.step ) {
			var details = el( 'details' );
			details.appendChild( el( 'summary', '', i18n.details + ' (' + s.log.length + ')' ) );
			var pre = el( 'pre', 'ecm-import__details', s.log.join( '\n' ) );
			details.appendChild( pre );
			box.appendChild( details );
		}
		logBox.insertBefore( box, logBox.firstChild );
	}

	function logError( step, message ) {
		log( { step: step, created: 0, updated: 0, skipped: 0, errors: [ message ] } );
	}

	function runSimple( step ) {
		setState( step, i18n.running, 'is-running' );
		return post( step ).then( function ( res ) {
			if ( ! res || ! res.success ) {
				throw new Error( ( res && res.data && res.data.message ) || i18n.failed );
			}
			log( res.data );
			setState( step, res.data.errors && res.data.errors.length ? i18n.failed : i18n.done, res.data.errors && res.data.errors.length ? 'is-error' : 'is-done' );
			return res.data;
		} );
	}

	function runMedia() {
		var progress = root.querySelector( '[data-step-row="media"] .ecm-import__progress' );
		var offset = 0;
		var totals = { step: 'media', created: 0, updated: 0, skipped: 0, errors: [], log: [], time: 0 };
		if ( progress ) {
			progress.hidden = false;
			progress.value = 0;
		}
		setState( 'media', i18n.running, 'is-running' );

		function next() {
			return post( 'media', { offset: offset, limit: cfg.mediaBatch || 4 } ).then( function ( res ) {
				if ( ! res || ! res.success ) {
					throw new Error( ( res && res.data && res.data.message ) || i18n.failed );
				}
				var s = res.data;
				totals.created += s.created;
				totals.updated += s.updated;
				totals.skipped += s.skipped;
				totals.errors = totals.errors.concat( s.errors || [] );
				totals.log = totals.log.concat( s.log || [] );
				totals.time = Math.round( ( totals.time + ( s.time || 0 ) ) * 100 ) / 100;

				if ( s.total === undefined ) {
					return totals;
				}
				if ( progress && s.total > 0 ) {
					progress.value = Math.round( ( s.next_offset / s.total ) * 100 );
				}
				setState( 'media', i18n.media + ' ' + s.next_offset + ' / ' + s.total, 'is-running' );

				if ( s.done || s.next_offset <= offset ) {
					return totals;
				}
				offset = s.next_offset;
				return next();
			} );
		}

		return next().then( function ( t ) {
			log( t );
			setState( 'media', t.errors.length ? i18n.failed : i18n.done, t.errors.length ? 'is-error' : 'is-done' );
			return t;
		} );
	}

	function run( step ) {
		return 'media' === step ? runMedia() : runSimple( step );
	}

	function guarded( promiseFactory, step ) {
		if ( busy ) {
			return;
		}
		setBusy( true );
		return promiseFactory().catch( function ( err ) {
			logError( step || '—', ( err && err.message && 'Failed to fetch' !== err.message ? err.message + ' – ' : '' ) + i18n.network );
			if ( step ) {
				setState( step, i18n.failed, 'is-error' );
			}
		} ).then( function () {
			setBusy( false );
		} );
	}

	root.addEventListener( 'click', function ( e ) {
		var stepBtn = e.target.closest( 'button[data-ecm-step]' );
		if ( stepBtn ) {
			e.preventDefault();
			var step = stepBtn.getAttribute( 'data-ecm-step' );
			guarded( function () {
				return run( step );
			}, step );
			return;
		}
		var allBtn = e.target.closest( 'button[data-ecm-run-all]' );
		if ( allBtn ) {
			e.preventDefault();
			if ( ! window.confirm( i18n.confirm ) ) {
				return;
			}
			var steps = Object.keys( cfg.steps || {} );
			var current = '';
			guarded( function () {
				return steps.reduce( function ( chain, step ) {
					return chain.then( function () {
						current = step;
						return run( step );
					} );
				}, Promise.resolve() ).catch( function ( err ) {
					setState( current, i18n.failed, 'is-error' );
					throw err;
				} );
			} );
		}
	} );
}() );
