<?php
/**
 * Formazione continua (numeri reali + foto di un corso dal vivo) e citazione della fondatrice,
 * come nella pagina testimonianze originale. Tutto dalle impostazioni "Testimonianze & fondatrice".
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

$ecm_stats = ecm_stats();
$ecm_event = (int) ecm_opt( 'evento_foto', 0 );
$ecm_name  = (string) ecm_opt( 'fondatrice_nome', '' );
$ecm_quote = (string) ecm_opt( 'fondatrice_citazione', '' );
$ecm_photo = (int) ecm_opt( 'fondatrice_foto', 0 );
?>
<section class="continuing section" aria-labelledby="continuing-title">
	<div class="wrap continuing__grid">
		<div class="continuing__copy">
			<h2 class="section-title" id="continuing-title" data-reveal="lines"><?php esc_html_e( 'Formazione continua in ambito medico e scientifico', 'ecm' ); ?></h2>
			<dl class="continuing__stats">
				<?php foreach ( ecm_stats_extra() as $ecm_x ) : ?>
					<?php
					if ( false !== stripos( $ecm_x['label'], 'docent' ) ) {
						continue;
					}
					?>
					<div><dt><?php echo esc_html( $ecm_x['label'] ); ?></dt><dd><?php echo wp_kses( ecm_stat_html( $ecm_x['value'] ), array( 'span' => array( 'class' => true, 'data-count' => true ) ) ); ?></dd></div>
				<?php endforeach; ?>
				<?php if ( $ecm_stats['docenti'] ) : ?>
					<div><dt><?php esc_html_e( 'Docenti', 'ecm' ); ?></dt><dd><span class="num" data-count="<?php echo (int) $ecm_stats['docenti']; ?>"><?php echo esc_html( ecm_num( $ecm_stats['docenti'] ) ); ?></span></dd></div>
				<?php endif; ?>
				<?php if ( $ecm_stats['corsi'] ) : ?>
					<div><dt><?php esc_html_e( 'Corsi online', 'ecm' ); ?></dt><dd><span class="num" data-count="<?php echo (int) $ecm_stats['corsi']; ?>"><?php echo esc_html( ecm_num( $ecm_stats['corsi'] ) ); ?></span></dd></div>
				<?php endif; ?>
			</dl>
		</div>
		<?php if ( $ecm_event ) : ?>
			<figure class="continuing__photo" data-reveal="fade">
				<?php echo wp_get_attachment_image( $ecm_event, 'large', false, array( 'loading' => 'lazy', 'sizes' => '(min-width: 1000px) 48vw, 92vw' ) ); ?>
			</figure>
		<?php endif; ?>
	</div>
</section>

<?php if ( $ecm_name && $ecm_quote ) : ?>
<section class="founder" aria-labelledby="founder-title">
	<div class="wrap founder__grid">
		<figure class="founder__quote">
			<h2 class="founder__name" id="founder-title"><?php echo esc_html( $ecm_name ); ?></h2>
			<?php if ( ecm_opt( 'fondatrice_ruolo' ) ) : ?>
				<p class="founder__role"><?php echo esc_html( (string) ecm_opt( 'fondatrice_ruolo' ) ); ?></p>
			<?php endif; ?>
			<blockquote data-scrub-words><?php echo esc_html( $ecm_quote ); ?></blockquote>
		</figure>
		<?php if ( $ecm_photo ) : ?>
			<div class="founder__media">
				<span class="founder__disc" aria-hidden="true"></span>
				<?php echo wp_get_attachment_image( $ecm_photo, 'large', false, array( 'class' => 'founder__photo', 'alt' => $ecm_name, 'loading' => 'lazy', 'sizes' => '(min-width: 900px) 36vw, 76vw' ) ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>
