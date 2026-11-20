<?php
/**
 * Header del sito.
 *
 * @package ecm
 */

defined( 'ABSPATH' ) || exit;

$ecm_login = (string) ecm_opt( 'login_url', '' );
?><!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Vai al contenuto', 'ecm' ); ?></a>

<header class="site-header" data-header>
	<div class="site-header__inner wrap">
		<a class="site-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Corsi di Formazione ECM, vai alla home', 'ecm' ); ?>">
			<?php echo ecm_logo( 'dark', 'logo--dark' ) . ecm_logo( 'light', 'logo--light', '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</a>

		<nav class="site-nav" aria-label="<?php esc_attr_e( 'Principale', 'ecm' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'site-nav__list',
					'depth'          => 1,
					'fallback_cb'    => 'ecm_nav_fallback',
				)
			);
			?>
		</nav>

		<div class="site-header__actions">
			<?php if ( $ecm_login ) : ?>
				<?php
				echo ecm_button( // phpcs:ignore WordPress.Security.EscapeOutput
					array(
						'label'    => __( 'Accedi', 'ecm' ),
						'url'      => $ecm_login,
						'variant'  => 'secondary',
						'icon'     => 'sign-in',
						'icon_pos' => 'start',
						'class'    => 'btn--sm btn--login',
						'attrs'    => array( 'aria-label' => __( 'Accedi alla piattaforma dei corsi', 'ecm' ) ),
					)
				);
				?>
			<?php endif; ?>
			<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="menu-panel" aria-label="<?php esc_attr_e( 'Menu', 'ecm' ); ?>" data-menu-toggle>
				<span class="menu-toggle__label"><?php esc_html_e( 'Menu', 'ecm' ); ?></span>
				<span class="menu-toggle__bars" aria-hidden="true"><span></span><span></span></span>
			</button>
		</div>
	</div>
</header>

<div class="menu-panel" id="menu-panel" data-menu-panel hidden>
	<div class="menu-panel__inner wrap">
		<nav aria-label="<?php esc_attr_e( 'Menu mobile', 'ecm' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'menu-panel__list',
					'depth'          => 1,
					'fallback_cb'    => 'ecm_nav_fallback',
				)
			);
			?>
		</nav>
		<div class="menu-panel__foot">
			<?php if ( $ecm_login ) : ?>
				<?php
				echo ecm_button( // phpcs:ignore WordPress.Security.EscapeOutput
					array(
						'label'    => __( 'Accedi alla piattaforma', 'ecm' ),
						'url'      => $ecm_login,
						'variant'  => 'on-ink',
						'icon'     => 'sign-in',
						'icon_pos' => 'start',
					)
				);
				?>
			<?php endif; ?>
			<?php if ( ecm_opt( 'whatsapp_number' ) ) : ?>
				<a class="menu-panel__contact" href="<?php echo esc_url( ecm_whatsapp_generic() ); ?>" target="_blank" rel="noopener">
					<?php echo ecm_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php echo esc_html( (string) ecm_opt( 'whatsapp_display', '' ) ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( ecm_opt( 'email_segreteria' ) ) : ?>
				<a class="menu-panel__contact" href="mailto:<?php echo esc_attr( antispambot( (string) ecm_opt( 'email_segreteria' ) ) ); ?>">
					<?php echo ecm_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<span><?php echo esc_html( antispambot( (string) ecm_opt( 'email_segreteria' ) ) ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>

<main id="main" class="site-main" tabindex="-1">
