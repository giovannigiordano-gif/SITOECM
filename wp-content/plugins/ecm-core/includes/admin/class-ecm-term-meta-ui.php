<?php
/**
 * Campi aggiuntivi di aree tematiche e destinatari (ordine, chiave immagine, descrizione breve).
 *
 * @package ECM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta termini (UI).
 */
final class ECM_Term_Meta_UI {

	private const NONCE        = 'ecm_term_nonce';
	private const NONCE_ACTION = 'ecm_save_term';

	/**
	 * Aggancia gli hook.
	 */
	public static function init(): void {
		foreach ( array( 'area_tematica', 'destinatario' ) as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', array( __CLASS__, 'add_fields' ) );
			add_action( $taxonomy . '_edit_form_fields', array( __CLASS__, 'edit_fields' ), 10, 2 );
			add_action( 'created_' . $taxonomy, array( __CLASS__, 'save' ), 10, 1 );
			add_action( 'edited_' . $taxonomy, array( __CLASS__, 'save' ), 10, 1 );
			add_filter( 'manage_edit-' . $taxonomy . '_columns', array( __CLASS__, 'columns' ) );
			add_filter( 'manage_' . $taxonomy . '_custom_column', array( __CLASS__, 'cell' ), 10, 3 );
		}
	}

	/**
	 * Chiavi immagine suggerite dal tema (filtro `ecm_area_image_keys`).
	 *
	 * @return array<string,string> chiave => etichetta.
	 */
	private static function image_keys(): array {
		$keys = apply_filters( 'ecm_area_image_keys', array() );
		return is_array( $keys ) ? $keys : array();
	}

	/**
	 * Campi nella schermata "Aggiungi".
	 *
	 * @param string $taxonomy Tassonomia.
	 */
	public static function add_fields( $taxonomy ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE, false );
		?>
		<div class="form-field">
			<label for="ecm_order"><?php esc_html_e( 'Ordine', 'ecm' ); ?></label>
			<input type="number" id="ecm_order" name="ecm_term[order]" value="0" step="1" style="width:8em">
			<p><?php esc_html_e( 'Posizione negli elenchi del sito: i numeri più bassi compaiono prima.', 'ecm' ); ?></p>
		</div>
		<?php if ( 'area_tematica' === $taxonomy ) : ?>
			<div class="form-field">
				<label for="ecm_image_key"><?php esc_html_e( 'Chiave immagine', 'ecm' ); ?></label>
				<?php self::image_key_input( '' ); ?>
				<p><?php esc_html_e( 'Identificativo dell\'illustrazione dell\'area definita nel tema (es. nutrizione-umana). Se non sei sicuro, lascia vuoto.', 'ecm' ); ?></p>
			</div>
			<div class="form-field">
				<label for="ecm_short"><?php esc_html_e( 'Descrizione breve', 'ecm' ); ?></label>
				<textarea id="ecm_short" name="ecm_term[short]" rows="3" maxlength="160" data-ecm-counter="160"></textarea>
				<p><?php esc_html_e( 'Una frase (massimo 160 caratteri) mostrata nelle schede delle aree. La descrizione completa va nel campo "Descrizione".', 'ecm' ); ?></p>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Campi nella schermata "Modifica".
	 *
	 * @param WP_Term $term     Termine.
	 * @param string  $taxonomy Tassonomia.
	 */
	public static function edit_fields( $term, $taxonomy ): void {
		$order = (int) get_term_meta( $term->term_id, '_ecm_order', true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="ecm_order"><?php esc_html_e( 'Ordine', 'ecm' ); ?></label></th>
			<td>
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE, false ); ?>
				<input type="number" id="ecm_order" name="ecm_term[order]" value="<?php echo (int) $order; ?>" step="1" style="width:8em">
				<p class="description"><?php esc_html_e( 'Posizione negli elenchi del sito: i numeri più bassi compaiono prima.', 'ecm' ); ?></p>
			</td>
		</tr>
		<?php if ( 'area_tematica' === $taxonomy ) : ?>
			<tr class="form-field">
				<th scope="row"><label for="ecm_image_key"><?php esc_html_e( 'Chiave immagine', 'ecm' ); ?></label></th>
				<td>
					<?php self::image_key_input( (string) get_term_meta( $term->term_id, '_ecm_image_key', true ) ); ?>
					<p class="description"><?php esc_html_e( 'Identificativo dell\'illustrazione dell\'area definita nel tema (es. nutrizione-umana).', 'ecm' ); ?></p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row"><label for="ecm_short"><?php esc_html_e( 'Descrizione breve', 'ecm' ); ?></label></th>
				<td>
					<textarea id="ecm_short" name="ecm_term[short]" rows="3" maxlength="160" data-ecm-counter="160"><?php echo esc_textarea( (string) get_term_meta( $term->term_id, '_ecm_short', true ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Una frase (massimo 160 caratteri) mostrata nelle schede delle aree.', 'ecm' ); ?></p>
				</td>
			</tr>
		<?php endif; ?>
		<?php
	}

	/**
	 * Campo chiave immagine con suggerimenti.
	 *
	 * @param string $value Valore.
	 */
	private static function image_key_input( string $value ): void {
		$keys = self::image_keys();
		printf(
			'<input type="text" id="ecm_image_key" name="ecm_term[image_key]" value="%1$s" class="regular-text code" %2$s placeholder="nutrizione-umana">',
			esc_attr( $value ),
			$keys ? 'list="ecm_image_keys"' : ''
		);
		if ( $keys ) {
			echo '<datalist id="ecm_image_keys">';
			foreach ( $keys as $key => $label ) {
				printf( '<option value="%1$s">%2$s</option>', esc_attr( (string) $key ), esc_html( (string) $label ) );
			}
			echo '</datalist>';
		}
	}

	/**
	 * Salvataggio.
	 *
	 * @param int $term_id ID termine.
	 */
	public static function save( $term_id ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitizzato sotto.
		$in = isset( $_POST['ecm_term'] ) && is_array( $_POST['ecm_term'] ) ? wp_unslash( $_POST['ecm_term'] ) : array();

		$term = get_term( (int) $term_id );
		if ( ! $term instanceof WP_Term ) {
			return;
		}
		update_term_meta( $term_id, '_ecm_order', ECM_Meta::sanitize_int( $in['order'] ?? 0 ) );
		if ( 'area_tematica' === $term->taxonomy ) {
			update_term_meta( $term_id, '_ecm_image_key', ECM_Meta::sanitize_slug( $in['image_key'] ?? '' ) );
			update_term_meta( $term_id, '_ecm_short', wp_slash( ECM_Meta::sanitize_short( $in['short'] ?? '' ) ) );
		}
	}

	/**
	 * Colonne dell'elenco termini.
	 *
	 * @param array $columns Colonne.
	 * @return array
	 */
	public static function columns( $columns ): array {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'name' === $key ) {
				$out['ecm_order'] = __( 'Ordine', 'ecm' );
				$screen           = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
				if ( $screen && 'area_tematica' === $screen->taxonomy ) {
					$out['ecm_image_key'] = __( 'Chiave immagine', 'ecm' );
				}
			}
		}
		return $out;
	}

	/**
	 * Celle dell'elenco termini.
	 *
	 * @param string $content Contenuto.
	 * @param string $column  Colonna.
	 * @param int    $term_id ID.
	 * @return string
	 */
	public static function cell( $content, $column, $term_id ) {
		if ( 'ecm_order' === $column ) {
			return (string) (int) get_term_meta( $term_id, '_ecm_order', true );
		}
		if ( 'ecm_image_key' === $column ) {
			$key = (string) get_term_meta( $term_id, '_ecm_image_key', true );
			return '' !== $key ? '<code>' . esc_html( $key ) . '</code>' : '<span class="ecm-muted">—</span>';
		}
		return $content;
	}
}
