<?php
/**
 * SEO di base: titolo, meta description, Open Graph/Twitter, JSON-LD, sitemap core.
 *
 * Si disattiva da solo se è attivo Yoast SEO, Rank Math o SEOPress.
 * Il canonical è quello di WordPress core (rel_canonical).
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * SEO.
 */
final class ECM_SEO {

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		add_action( 'wp', array( __CLASS__, 'setup' ) );
		add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'sitemap_providers' ), 10, 2 );
		add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'sitemap_post_types' ) );
		add_filter( 'wp_sitemaps_taxonomies', array( __CLASS__, 'sitemap_taxonomies' ) );
	}

	/**
	 * true se nessun plugin SEO noto è attivo.
	 *
	 * @return bool
	 */
	public static function enabled(): bool {
		$other = defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| class_exists( 'RankMath', false )
			|| defined( 'SEOPRESS_VERSION' )
			|| function_exists( 'seopress_init' );

		/**
		 * Consente di forzare l'attivazione/disattivazione dell'output SEO del plugin.
		 *
		 * @param bool $enabled
		 */
		return (bool) apply_filters( 'ecm_seo_enabled', ! $other );
	}

	/**
	 * Registra l'output nel front end.
	 */
	public static function setup(): void {
		if ( is_admin() || ! self::enabled() ) {
			return;
		}
		add_filter( 'pre_get_document_title', array( __CLASS__, 'document_title' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'head' ), 2 );
	}

	/**
	 * Titolo del documento personalizzato (_ecm_seo_title).
	 *
	 * @param string $title Titolo.
	 * @return string
	 */
	public static function document_title( $title ) {
		if ( is_singular() ) {
			$custom = trim( (string) get_post_meta( get_queried_object_id(), '_ecm_seo_title', true ) );
			if ( '' !== $custom ) {
				return esc_html( $custom );
			}
		}
		return $title;
	}

	/**
	 * Stampa description, Open Graph, Twitter e JSON-LD.
	 */
	public static function head(): void {
		$description = self::description();
		$title       = self::social_title();
		$url         = self::current_url();
		$image       = self::image();

		echo "\n<!-- ECM Core SEO -->\n";
		if ( '' !== $description ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
		}

		$og = array(
			'og:locale'    => 'it_IT',
			'og:type'      => 'website',
			'og:site_name' => self::site_name(),
			'og:title'     => $title,
			'og:url'       => $url,
		);
		if ( '' !== $description ) {
			$og['og:description'] = $description;
		}
		foreach ( $og as $property => $content ) {
			if ( '' !== (string) $content ) {
				printf( '<meta property="%s" content="%s" />' . "\n", esc_attr( $property ), esc_attr( (string) $content ) );
			}
		}
		if ( $image ) {
			printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image['url'] ) );
			if ( $image['width'] && $image['height'] ) {
				printf( '<meta property="og:image:width" content="%d" />' . "\n", (int) $image['width'] );
				printf( '<meta property="og:image:height" content="%d" />' . "\n", (int) $image['height'] );
			}
			if ( '' !== $image['alt'] ) {
				printf( '<meta property="og:image:alt" content="%s" />' . "\n", esc_attr( $image['alt'] ) );
			}
		}

		printf( '<meta name="twitter:card" content="%s" />' . "\n", $image ? 'summary_large_image' : 'summary' );
		printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );
		if ( '' !== $description ) {
			printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $description ) );
		}
		if ( $image ) {
			printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $image['url'] ) );
		}

		$graph = array_values( array_filter( array_merge( array( self::organization(), self::website() ), self::course_graph( $description, $image ) ) ) );
		echo '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
		) . "</script>\n";
		echo "<!-- /ECM Core SEO -->\n";
	}

	/* ------------------------------------------------------------------
	 * Valori.
	 * ------------------------------------------------------------------ */

	/**
	 * Nome del sito.
	 *
	 * @return string
	 */
	private static function site_name(): string {
		return html_entity_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Meta description della pagina corrente.
	 *
	 * @return string
	 */
	public static function description(): string {
		$text = '';

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post instanceof WP_Post ) {
				$text = (string) get_post_meta( $post->ID, '_ecm_seo_desc', true );
				if ( '' === trim( $text ) ) {
					if ( 'corso' === $post->post_type ) {
						$course = ecm_course( $post );
						$text   = (string) ( $course['excerpt'] ?? '' );
					} elseif ( is_front_page() ) {
						$text = (string) ecm_opt( 'hero_text' );
					} elseif ( has_excerpt( $post ) ) {
						$text = (string) $post->post_excerpt;
					} else {
						$text = wp_trim_words( excerpt_remove_blocks( strip_shortcodes( (string) $post->post_content ) ), 40, '' );
					}
				}
			}
		} elseif ( is_post_type_archive( 'corso' ) ) {
			/* translators: %s: nome del sito. */
			$text = (string) apply_filters( 'ecm_archive_description', sprintf( __( 'Catalogo dei corsi ECM online di %s per biologi nutrizionisti, dietisti, medici, farmacisti, veterinari e professionisti della salute.', 'ecm' ), self::site_name() ) );
		} elseif ( is_tax( 'area_tematica' ) || is_tax( 'destinatario' ) ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$text = (string) get_term_meta( $term->term_id, '_ecm_short', true );
				if ( '' === trim( $text ) ) {
					$text = (string) $term->description;
				}
			}
		} elseif ( is_front_page() || is_home() ) {
			$text = (string) ecm_opt( 'hero_text' );
		}

		if ( '' === trim( wp_strip_all_tags( $text ) ) && ( is_front_page() || is_home() ) ) {
			$text = (string) get_bloginfo( 'description' );
		}

		return self::clean_text( $text, 160 );
	}

	/**
	 * Titolo per Open Graph/Twitter.
	 *
	 * @return string
	 */
	private static function social_title(): string {
		if ( is_front_page() ) {
			$custom = is_singular() ? trim( (string) get_post_meta( get_queried_object_id(), '_ecm_seo_title', true ) ) : '';
			return '' !== $custom ? $custom : self::site_name();
		}
		if ( is_singular() ) {
			$custom = trim( (string) get_post_meta( get_queried_object_id(), '_ecm_seo_title', true ) );
			return '' !== $custom ? $custom : ecm_plain_title( get_queried_object() );
		}
		if ( is_post_type_archive( 'corso' ) ) {
			return __( 'Catalogo corsi ECM', 'ecm' ) . ' | ' . self::site_name();
		}
		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			return $term instanceof WP_Term ? html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . ' | ' . self::site_name() : self::site_name();
		}
		return html_entity_decode( wp_get_document_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * URL canonico della pagina corrente.
	 *
	 * @return string
	 */
	private static function current_url(): string {
		if ( is_front_page() ) {
			return home_url( '/' );
		}
		if ( is_singular() ) {
			$url = wp_get_canonical_url( get_queried_object_id() );
			return $url ? (string) $url : '';
		}
		if ( is_post_type_archive( 'corso' ) ) {
			return (string) get_post_type_archive_link( 'corso' );
		}
		if ( is_tax() || is_category() || is_tag() ) {
			$link = get_term_link( get_queried_object() );
			return is_wp_error( $link ) ? '' : (string) $link;
		}
		return '';
	}

	/**
	 * Immagine di condivisione: copertina/immagine in evidenza o immagine predefinita.
	 *
	 * @return array{url:string,width:int,height:int,alt:string}|null
	 */
	private static function image(): ?array {
		$id = 0;
		if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
			$id = (int) get_post_thumbnail_id( get_queried_object_id() );
		}
		if ( ! $id ) {
			$id = (int) ecm_opt( 'og_default_image_id', 0 );
		}
		if ( ! $id ) {
			return null;
		}
		return self::image_data( $id );
	}

	/**
	 * Dati immagine, preferendo l'originale JPEG/PNG (le sotto-dimensioni sono WebP).
	 *
	 * @param int $id ID allegato.
	 * @return array{url:string,width:int,height:int,alt:string}|null
	 */
	private static function image_data( int $id ): ?array {
		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( ! $src ) {
			return null;
		}
		$url    = (string) $src[0];
		$width  = (int) $src[1];
		$height = (int) $src[2];
		$mime   = (string) get_post_mime_type( $id );
		$meta   = wp_get_attachment_metadata( $id );

		if ( in_array( $mime, array( 'image/jpeg', 'image/png' ), true ) && ! empty( $meta['original_image'] ) ) {
			$original = wp_get_original_image_url( $id );
			if ( $original ) {
				$url    = (string) $original;
				$width  = 0;
				$height = 0;
			}
		}

		return array(
			'url'    => $url,
			'width'  => $width,
			'height' => $height,
			'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
		);
	}

	/**
	 * Logo dell'organizzazione: opzione, logo del tema o icona del sito.
	 *
	 * @return array|null
	 */
	private static function logo(): ?array {
		$id = (int) ecm_opt( 'logo_id', 0 );
		if ( ! $id ) {
			$id = (int) get_theme_mod( 'custom_logo' );
		}
		if ( ! $id ) {
			$id = (int) get_option( 'site_icon' );
		}
		$data = $id ? self::image_data( $id ) : null;
		if ( ! $data ) {
			return null;
		}
		$logo = array(
			'@type' => 'ImageObject',
			'@id'   => home_url( '/#logo' ),
			'url'   => $data['url'],
		);
		if ( $data['width'] && $data['height'] ) {
			$logo['width']  = $data['width'];
			$logo['height'] = $data['height'];
		}
		return $logo;
	}

	/**
	 * Nodo Organization.
	 *
	 * @return array
	 */
	private static function organization(): array {
		$name = trim( (string) ecm_opt( 'ragione_sociale' ) );
		$site = self::site_name();
		$org  = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => '' !== $name ? $name : $site,
			'url'   => home_url( '/' ),
		);
		if ( '' !== $name && $name !== $site ) {
			$org['alternateName'] = $site;
		}
		$logo = self::logo();
		if ( $logo ) {
			$org['logo']  = $logo;
			$org['image'] = array( '@id' => $logo['@id'] );
		}
		$same_as = array_values( array_filter( array( (string) ecm_opt( 'instagram_url' ), (string) ecm_opt( 'facebook_url' ), (string) ecm_opt( 'youtube_url' ) ) ) );
		if ( $same_as ) {
			$org['sameAs'] = $same_as;
		}
		$piva = trim( (string) ecm_opt( 'piva' ) );
		if ( '' !== $piva ) {
			$org['vatID'] = $piva;
		}
		$email = (string) ecm_opt( 'email_segreteria' );
		if ( '' !== $email ) {
			$org['email'] = $email;
		}
		$address = trim( (string) ecm_opt( 'indirizzo' ) );
		if ( '' !== $address ) {
			$org['address'] = preg_replace( '/\s*\n\s*/', ', ', $address );
		}
		$phone = (string) ecm_opt( 'whatsapp_number' );
		if ( '' !== $phone || '' !== $email ) {
			$contact = array(
				'@type'             => 'ContactPoint',
				'contactType'       => 'customer service',
				'availableLanguage' => 'Italian',
			);
			if ( '' !== $phone ) {
				$contact['telephone'] = '+' . $phone;
			}
			if ( '' !== $email ) {
				$contact['email'] = $email;
			}
			$org['contactPoint'] = $contact;
		}
		return $org;
	}

	/**
	 * Nodo WebSite.
	 *
	 * @return array
	 */
	private static function website(): array {
		return array(
			'@type'      => 'WebSite',
			'@id'        => home_url( '/#website' ),
			'url'        => home_url( '/' ),
			'name'       => self::site_name(),
			'inLanguage' => 'it-IT',
			'publisher'  => array( '@id' => home_url( '/#organization' ) ),
		);
	}

	/**
	 * Nodi Course e BreadcrumbList sulla scheda corso.
	 *
	 * @param string     $description Descrizione.
	 * @param array|null $image       Immagine.
	 * @return array
	 */
	private static function course_graph( string $description, ?array $image ): array {
		if ( ! is_singular( 'corso' ) ) {
			return array();
		}
		$course = ecm_course( get_queried_object() );
		if ( ! $course ) {
			return array();
		}

		$node = array(
			'@type'       => 'Course',
			'@id'         => $course['url'] . '#course',
			'name'        => $course['title'],
			'description' => '' !== $description ? $description : self::clean_text( $course['excerpt'], 300 ),
			'url'         => $course['url'],
			'inLanguage'  => 'it',
			'provider'    => array(
				'@type'  => 'Organization',
				'@id'    => home_url( '/#organization' ),
				'name'   => '' !== trim( (string) ecm_opt( 'ragione_sociale' ) ) ? trim( (string) ecm_opt( 'ragione_sociale' ) ) : self::site_name(),
				'sameAs' => home_url( '/' ),
			),
		);
		if ( $image ) {
			$node['image'] = $image['url'];
		}
		$crediti = trim( (string) $course['crediti'] );
		if ( '' !== $crediti ) {
			/* translators: %s: numero di crediti. */
			$node['educationalCredentialAwarded'] = is_numeric( str_replace( ',', '.', $crediti ) ) ? sprintf( __( '%s crediti ECM', 'ecm' ), $crediti ) : __( 'Crediti ECM', 'ecm' );
		}
		if ( ! empty( $course['areas'] ) ) {
			$node['about'] = wp_list_pluck( $course['areas'], 'name' );
		}
		if ( ! empty( $course['destinatari'] ) ) {
			$node['audience'] = array(
				'@type'        => 'Audience',
				'audienceType' => implode( ', ', wp_list_pluck( $course['destinatari'], 'name' ) ),
			);
		}

		$archive = get_post_type_archive_link( 'corso' );
		$crumbs  = array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => __( 'Home', 'ecm' ),
				'item'     => home_url( '/' ),
			),
			array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => __( 'Corsi', 'ecm' ),
				'item'     => $archive ? $archive : home_url( '/corsi/' ),
			),
			array(
				'@type'    => 'ListItem',
				'position' => 3,
				'name'     => $course['title'],
				'item'     => $course['url'],
			),
		);

		return array(
			$node,
			array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $course['url'] . '#breadcrumb',
				'itemListElement' => $crumbs,
			),
		);
	}

	/**
	 * Testo semplice accorciato a parola intera.
	 *
	 * @param string $text Testo.
	 * @param int    $max  Lunghezza massima.
	 * @return string
	 */
	public static function clean_text( string $text, int $max = 160 ): string {
		$text = wp_strip_all_tags( strip_shortcodes( $text ) );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
		if ( mb_strlen( $text ) <= $max ) {
			return $text;
		}
		$cut   = mb_substr( $text, 0, $max - 1 );
		$space = mb_strrpos( $cut, ' ' );
		if ( false !== $space && $space > $max * 0.6 ) {
			$cut = mb_substr( $cut, 0, $space );
		}
		return (string) preg_replace( '/[\s,;:.\-–]+$/u', '', $cut ) . '…';
	}

	/* ------------------------------------------------------------------
	 * Sitemap core.
	 * ------------------------------------------------------------------ */

	/**
	 * Rimuove il provider utenti.
	 *
	 * @param WP_Sitemaps_Provider|false $provider Provider.
	 * @param string                     $name     Nome.
	 * @return WP_Sitemaps_Provider|false
	 */
	public static function sitemap_providers( $provider, $name ) {
		if ( 'users' === $name && self::enabled() ) {
			return false;
		}
		return $provider;
	}

	/**
	 * Solo pagine e corsi.
	 *
	 * @param array $post_types Tipi.
	 * @return array
	 */
	public static function sitemap_post_types( $post_types ) {
		if ( ! self::enabled() || ! is_array( $post_types ) ) {
			return $post_types;
		}
		$allowed = (array) apply_filters( 'ecm_sitemap_post_types', array( 'page', 'corso' ) );
		return array_intersect_key( $post_types, array_flip( $allowed ) );
	}

	/**
	 * Solo aree tematiche.
	 *
	 * @param array $taxonomies Tassonomie.
	 * @return array
	 */
	public static function sitemap_taxonomies( $taxonomies ) {
		if ( ! self::enabled() || ! is_array( $taxonomies ) ) {
			return $taxonomies;
		}
		$allowed = (array) apply_filters( 'ecm_sitemap_taxonomies', array( 'area_tematica' ) );
		return array_intersect_key( $taxonomies, array_flip( $allowed ) );
	}
}
