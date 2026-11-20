<?php
/**
 * Script subordinati al consenso: il codice statistiche (`analytics_head`) viene stampato
 * come `<script type="text/plain" data-ecm-consent="statistics">` e attivato dal banner
 * cookie del tema solo dopo il consenso.
 *
 * Convenzione per il tema: per ogni `script[type="text/plain"][data-ecm-consent="statistics"]`
 * creare un nuovo <script> copiando gli attributi (escluso type/data-ecm-consent), `src`
 * se presente e il contenuto testuale.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Consenso.
 */
final class ECM_Consent {

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'print_gated_scripts' ), 99 );
	}

	/**
	 * Stampa il codice statistiche disattivato.
	 */
	public static function print_gated_scripts(): void {
		$html = self::gated_markup( (string) ecm_opt( 'analytics_head' ), 'statistics' );
		if ( '' !== $html ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- codice inserito da un amministratore con unfiltered_html, reso inerte.
			echo "\n" . $html . "\n";
		}
	}

	/**
	 * Trasforma uno snippet (JS puro o HTML con tag <script>) in script inerti con data-ecm-consent.
	 *
	 * Gli elementi diversi da <script> (es. <noscript> con pixel) vengono scartati perché
	 * caricherebbero risorse senza consenso.
	 *
	 * @param string $code     Codice.
	 * @param string $category Categoria di consenso.
	 * @return string
	 */
	public static function gated_markup( string $code, string $category = 'statistics' ): string {
		$code = trim( $code );
		if ( '' === $code ) {
			return '';
		}
		$category = sanitize_key( $category );

		if ( false === stripos( $code, '<script' ) ) {
			$code = str_ireplace( '</script', '<\/script', $code );
			return sprintf( '<script type="text/plain" data-ecm-consent="%s">%s</script>', esc_attr( $category ), $code );
		}

		if ( ! preg_match_all( '#<script\b([^>]*)>(.*?)</script\s*>#is', $code, $matches, PREG_SET_ORDER ) ) {
			return '';
		}

		$out = array();
		foreach ( $matches as $match ) {
			$attrs = self::parse_attributes( $match[1] );
			$type  = strtolower( $attrs['type'] ?? '' );
			if ( '' !== $type && ! in_array( $type, array( 'text/javascript', 'application/javascript', 'module' ), true ) ) {
				// Script non eseguibili (es. JSON-LD): lasciati com'erano non hanno bisogno di consenso.
				continue;
			}
			unset( $attrs['type'], $attrs['data-ecm-consent'] );
			if ( 'module' === $type ) {
				$attrs['data-type'] = 'module';
			}

			$attr_html = sprintf( ' type="text/plain" data-ecm-consent="%s"', esc_attr( $category ) );
			foreach ( $attrs as $name => $value ) {
				if ( 'src' === $name ) {
					$attr_html .= sprintf( ' src="%s"', esc_url( $value ) );
				} elseif ( true === $value ) {
					$attr_html .= ' ' . $name;
				} else {
					$attr_html .= sprintf( ' %s="%s"', $name, esc_attr( (string) $value ) );
				}
			}
			$out[] = '<script' . $attr_html . '>' . $match[2] . '</script>';
		}
		return implode( "\n", $out );
	}

	/**
	 * Analizza gli attributi di un tag.
	 *
	 * @param string $raw Attributi grezzi.
	 * @return array<string,string|true>
	 */
	private static function parse_attributes( string $raw ): array {
		$attrs = array();
		if ( preg_match_all( '/([a-zA-Z_:][-a-zA-Z0-9_:.]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+)))?/', $raw, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $attr ) {
				$name = strtolower( $attr[1] );
				if ( str_starts_with( $name, 'on' ) ) {
					continue;
				}
				if ( isset( $attr[2] ) && '' !== $attr[2] ) {
					$attrs[ $name ] = $attr[2];
				} elseif ( isset( $attr[3] ) && '' !== $attr[3] ) {
					$attrs[ $name ] = $attr[3];
				} elseif ( isset( $attr[4] ) && '' !== $attr[4] ) {
					$attrs[ $name ] = $attr[4];
				} else {
					$attrs[ $name ] = true;
				}
			}
		}
		return $attrs;
	}
}
