<?php
/**
 * Immagini e PDF: sotto-dimensioni WebP, soglia immagini grandi, decoding async,
 * conteggio pagine dei PDF, rifilatura dei loghi.
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gestione media.
 */
final class ECM_Media {

	/**
	 * Cache del supporto WebP.
	 *
	 * @var bool|null
	 */
	private static ?bool $webp = null;

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_filter( 'image_editor_output_format', array( __CLASS__, 'output_format' ) );
		add_filter( 'big_image_size_threshold', array( __CLASS__, 'big_image_threshold' ) );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'image_attributes' ) );
		add_action( 'add_attachment', array( __CLASS__, 'on_add_attachment' ) );
	}

	/**
	 * Genera le sotto-dimensioni di JPEG e PNG in WebP (se il server lo supporta).
	 *
	 * @param array $formats Mappa mime sorgente => mime di output.
	 * @return array
	 */
	public static function output_format( $formats ) {
		$formats = is_array( $formats ) ? $formats : array();
		if ( ! apply_filters( 'ecm_webp_subsizes', true ) || ! self::webp_supported() ) {
			return $formats;
		}
		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png']  = 'image/webp';
		return $formats;
	}

	/**
	 * Soglia delle immagini grandi (lato lungo).
	 *
	 * @return int
	 */
	public static function big_image_threshold() {
		return 2560;
	}

	/**
	 * Attributo decoding="async" predefinito.
	 *
	 * @param array $attr Attributi.
	 * @return array
	 */
	public static function image_attributes( $attr ) {
		if ( is_array( $attr ) && empty( $attr['decoding'] ) ) {
			$attr['decoding'] = 'async';
		}
		return $attr;
	}

	/**
	 * Salva il numero di pagine quando viene caricato un PDF.
	 *
	 * @param int $attachment_id ID allegato.
	 */
	public static function on_add_attachment( $attachment_id ): void {
		if ( 'application/pdf' !== get_post_mime_type( $attachment_id ) ) {
			return;
		}
		if ( '' !== (string) get_post_meta( $attachment_id, '_ecm_pdf_pages', true ) ) {
			return;
		}
		$file  = get_attached_file( $attachment_id );
		$pages = $file ? self::pdf_page_count( $file ) : null;
		if ( $pages ) {
			update_post_meta( $attachment_id, '_ecm_pdf_pages', $pages );
		}
	}

	/**
	 * Verifica il supporto WebP dell'editor immagini.
	 *
	 * @return bool
	 */
	public static function webp_supported(): bool {
		if ( null === self::$webp ) {
			self::$webp = (bool) wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
		}
		return self::$webp;
	}

	/**
	 * Conta le pagine di un PDF senza dipendenze esterne (con ripiego su Imagick).
	 *
	 * @param string $file Percorso.
	 * @return int|null
	 */
	public static function pdf_page_count( string $file ): ?int {
		if ( ! is_readable( $file ) ) {
			return null;
		}
		$size = (int) filesize( $file );
		if ( $size > 0 && $size <= 80 * MB_IN_BYTES ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$content = (string) file_get_contents( $file );
			$count   = preg_match_all( '#/Type\s*/Page(?![a-zA-Z])#', $content );
			if ( $count ) {
				return (int) $count;
			}
			if ( preg_match_all( '#/Count\s+(\d+)#', $content, $m ) ) {
				$max = max( array_map( 'intval', $m[1] ) );
				if ( $max > 0 ) {
					return $max;
				}
			}
			unset( $content );
		}
		if ( class_exists( 'Imagick' ) ) {
			try {
				$im = new Imagick();
				$im->pingImage( $file );
				$n = (int) $im->getNumberImages();
				$im->clear();
				return $n > 0 ? $n : null;
			} catch ( Throwable $e ) {
				return null;
			}
		}
		return null;
	}

	/**
	 * Rifila i margini bianchi/trasparenti di un logo e aggiunge un piccolo margine uniforme.
	 *
	 * Lavora sul file indicato (una copia temporanea, mai l'originale del bundle).
	 *
	 * @param string $file Percorso dell'immagine (PNG, JPEG, WebP, GIF).
	 * @return bool true se il file è stato modificato.
	 */
	public static function trim_logo( string $file ): bool {
		if ( ! class_exists( 'Imagick' ) || ! is_writable( $file ) ) {
			return false;
		}
		try {
			$im = new Imagick( $file );
			if ( $im->getNumberImages() > 1 ) {
				$im->setIteratorIndex( 0 );
				$first = $im->getImage();
				$im->clear();
				$im = $first;
			}
			$format = strtoupper( (string) $im->getImageFormat() );
			if ( ! in_array( $format, array( 'PNG', 'JPEG', 'JPG', 'WEBP', 'GIF' ), true ) ) {
				$im->clear();
				return false;
			}

			$orig_w    = (int) $im->getImageWidth();
			$orig_h    = (int) $im->getImageHeight();
			$has_alpha = (bool) $im->getImageAlphaChannel();
			$range     = Imagick::getQuantumRange();
			$quantum   = (float) ( $range['quantumRangeLong'] ?? 65535 );

			$work = clone $im;
			if ( $has_alpha ) {
				// I margini trasparenti vengono rifilati rispetto al colore dell'angolo (trasparente).
				$work->setImageBackgroundColor( new ImagickPixel( 'transparent' ) );
			}
			$work->trimImage( 0.08 * $quantum );
			$work->setImagePage( 0, 0, 0, 0 );

			$w = (int) $work->getImageWidth();
			$h = (int) $work->getImageHeight();

			// Rifilatura nulla o eccessiva (immagine vuota): lascia l'originale.
			if ( $w < 8 || $h < 8 || ( $w === $orig_w && $h === $orig_h ) ) {
				$work->clear();
				$im->clear();
				return false;
			}

			$margin = max( 4, (int) round( max( $w, $h ) * 0.04 ) );
			$bg     = $has_alpha || in_array( $format, array( 'PNG', 'WEBP', 'GIF' ), true ) ? 'transparent' : 'white';
			$canvas = new Imagick();
			$canvas->newImage( $w + 2 * $margin, $h + 2 * $margin, new ImagickPixel( $bg ) );
			$canvas->setImageFormat( 'JPEG' === $format || 'JPG' === $format ? 'JPEG' : $format );
			if ( 'transparent' === $bg ) {
				$canvas->setImageAlphaChannel( Imagick::ALPHACHANNEL_ACTIVATE );
			}
			$canvas->compositeImage( $work, Imagick::COMPOSITE_OVER, $margin, $margin );
			if ( 'JPEG' === $format || 'JPG' === $format ) {
				$canvas->setImageCompressionQuality( 92 );
			}
			$canvas->stripImage();
			$ok = $canvas->writeImage( $format . ':' . $file );

			$canvas->clear();
			$work->clear();
			$im->clear();
			return (bool) $ok;
		} catch ( Throwable $e ) {
			ECM_Logger::log( 'warning', 'import', 'Rifilatura logo non riuscita: ' . $e->getMessage(), array( 'file' => wp_basename( $file ) ) );
			return false;
		}
	}
}
