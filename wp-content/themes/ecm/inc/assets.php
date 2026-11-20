<?php
/**
 * CSS, font e script. Tutto self-hosted: Mona Sans, GSAP + ScrollTrigger + Flip, Lenis.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

/**
 * Versione asset basata su filemtime (cache busting automatico a ogni deploy).
 */
function ecm_asset_ver( string $rel ): string {
	$path = ECM_THEME_DIR . '/' . ltrim( $rel, '/' );
	return file_exists( $path ) ? (string) filemtime( $path ) : ECM_THEME_VERSION;
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		$css = file_exists( ECM_THEME_DIR . '/assets/css/main.min.css' ) ? 'assets/css/main.min.css' : 'assets/css/main.css';
		wp_enqueue_style( 'ecm', ECM_THEME_URI . '/' . $css, array(), ecm_asset_ver( $css ) );

		$v = static fn( string $f ) => ecm_asset_ver( 'assets/vendor/' . $f );
		$args = array( 'strategy' => 'defer', 'in_footer' => true );

		wp_register_script( 'gsap', ECM_THEME_URI . '/assets/vendor/gsap.min.js', array(), $v( 'gsap.min.js' ), $args );
		wp_register_script( 'gsap-st', ECM_THEME_URI . '/assets/vendor/ScrollTrigger.min.js', array( 'gsap' ), $v( 'ScrollTrigger.min.js' ), $args );
		wp_register_script( 'gsap-flip', ECM_THEME_URI . '/assets/vendor/Flip.min.js', array( 'gsap' ), $v( 'Flip.min.js' ), $args );
		wp_register_script( 'gsap-split', ECM_THEME_URI . '/assets/vendor/SplitText.min.js', array( 'gsap' ), $v( 'SplitText.min.js' ), $args );
		wp_register_script( 'lenis', ECM_THEME_URI . '/assets/vendor/lenis.min.js', array(), $v( 'lenis.min.js' ), $args );

		$deps = array( 'gsap', 'gsap-st', 'gsap-split', 'lenis' );
		if ( is_post_type_archive( 'corso' ) || is_tax( 'area_tematica' ) ) {
			$deps[] = 'gsap-flip';
		}
		wp_enqueue_script( 'ecm-app', ECM_THEME_URI . '/assets/js/app.js', $deps, ecm_asset_ver( 'assets/js/app.js' ), $args );

		wp_localize_script(
			'ecm-app',
			'ECM',
			array(
				'rest'    => esc_url_raw( rest_url( 'ecm/v1/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'catalog' => esc_url_raw( (string) get_post_type_archive_link( 'corso' ) ),
				'i18n'    => array(
					'copied'      => __( 'Copiato negli appunti', 'ecm' ),
					'sending'     => __( 'Invio in corso…', 'ecm' ),
					'error'       => __( 'Non è stato possibile inviare. Controlla la connessione e riprova.', 'ecm' ),
					'results_one' => __( '1 corso', 'ecm' ),
					'results'     => __( '%d corsi', 'ecm' ),
					'results_0'   => __( 'Nessun corso', 'ecm' ),
					'readMore'    => __( 'Leggi tutto', 'ecm' ),
					'readLess'    => __( 'Mostra meno', 'ecm' ),
				),
			)
		);
	}
);

/**
 * Preload del font principale (latin, normale) e degli hint di prestazione.
 */
add_action(
	'wp_head',
	static function () {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( ECM_THEME_URI . '/assets/fonts/mona-sans-latin-normal.woff2' )
		);
	},
	1
);

/**
 * Script inline minimo, bloccante e piccolo:
 * 1. classe "js" (gli stati iniziali delle animazioni valgono solo con JS attivo);
 * 2. transizione di pagina circolare (View Transitions cross-document) dal punto del click.
 */
add_action(
	'wp_head',
	static function () {
		?>
<script>
(function(d,w){var h=d.documentElement;h.classList.remove('no-js');h.classList.add('js');
var rm=w.matchMedia('(prefers-reduced-motion: reduce)').matches;if(rm){h.classList.add('reduced-motion');}
w.addEventListener('pagereveal',function(e){if(!e.viewTransition)return;if(rm){e.viewTransition.skipTransition();return;}
var p=null;try{p=JSON.parse(sessionStorage.getItem('ecm-vt')||'null');sessionStorage.removeItem('ecm-vt');}catch(_){}
var x=p&&p.x!=null?p.x:w.innerWidth/2,y=p&&p.y!=null?p.y:w.innerHeight/2,r=Math.hypot(Math.max(x,w.innerWidth-x),Math.max(y,w.innerHeight-y));
e.viewTransition.ready.then(function(){h.animate({clipPath:['circle(0px at '+x+'px '+y+'px)','circle('+r+'px at '+x+'px '+y+'px)']},{duration:820,easing:'cubic-bezier(0.65,0,0.35,1)',pseudoElement:'::view-transition-new(root)'});}).catch(function(){});});
})(document,window);
</script>
		<?php
	},
	0
);

/**
 * Sprite delle icone Phosphor inline (un solo file, zero richieste).
 */
add_action(
	'wp_body_open',
	static function () {
		$sprite = ECM_THEME_DIR . '/assets/icons/sprite.svg';
		if ( is_readable( $sprite ) ) {
			// Contenuto statico generato in build dal pacchetto @phosphor-icons/core.
			echo file_get_contents( $sprite ); // phpcs:ignore WordPress.Security.EscapeOutput, WordPress.WP.AlternativeFunctions
		}
	},
	1
);
