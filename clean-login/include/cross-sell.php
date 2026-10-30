<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

class CleanLogin_CrossSell{
	static function is_spain(){
		$locales = array( 'es_ES', 'ca', 'gl_ES', 'eu' );

		if ( in_array( get_locale(), $locales ) || in_array( get_user_locale(), $locales ) )
			return true;

		return class_exists( 'WooCommerce' ) && strpos( (string) get_option( 'woocommerce_default_country' ), 'ES' ) === 0;
	}

	static function url( $url, $campaign ){
		return add_query_arg( array(
			'utm_source'   => 'wp-free-dashboard',
			'utm_medium'   => 'clean-login',
			'utm_campaign' => $campaign,
		), $url );
	}

	static function codection_url( $slug, $campaign ){
		return self::url( empty( $slug ) ? 'https://codection.com/' : 'https://codection.com/plugin/' . $slug . '/', $campaign );
	}

	static function import_wp_url( $slug, $campaign ){
		return self::url( empty( $slug ) ? 'https://import-wp.com/shop/' : 'https://import-wp.com/plugins/' . $slug . '/', $campaign );
	}

	static function get_gateways(){
		$integrations = array(
			array( defined( 'PMPRO_VERSION' ), 'Paid Memberships Pro', 'redsys-for-paid-memberships-pro', '' ),
			array( class_exists( 'WooCommerce' ), 'WooCommerce', 'redsys-gateway-for-woocommerce', 'ceca-gateway-for-woocommerce-pro' ),
			array( defined( 'GIVE_VERSION' ), 'GiveWP', 'redsys-bizum-for-givewp', 'ceca-for-givewp' ),
			array( class_exists( 'Tribe__Tickets__Main' ), 'Event Tickets', 'redsys-for-event-tickets', 'ceca-for-event-tickets' ),
			array( class_exists( 'TC' ), 'Tickera', 'redsys-bizum-gateway-for-tickera', 'ceca-gateway-for-tickera' ),
			array( class_exists( 'GFForms' ), 'Gravity Forms', 'redsys-bizum-gateway-for-gravity-forms', 'ceca-gateway-for-gravity-forms' ),
			array( defined( 'WPCF7_VERSION' ), 'Contact Form 7', 'redsys-gateway-for-contact-form-7', 'ceca-gateway-for-contact-form-7' ),
			array( defined( 'WPFORMS_VERSION' ), 'WPForms', 'redsys-gateway-for-wpforms', '' ),
			array( class_exists( 'Ninja_Forms' ), 'Ninja Forms', 'redsys-for-ninja-forms', 'ceca-for-ninja-forms' ),
			array( defined( 'ELEMENTOR_PRO_VERSION' ), 'Elementor Pro', 'redsys-for-elementor', 'ceca-for-elementor' ),
			array( class_exists( 'Easy_Digital_Downloads' ), 'Easy Digital Downloads', 'redsys-gateway-for-edd-pro', 'ceca-gateway-for-edd-pro' ),
		);

		$gateways = array();
		foreach ( $integrations as $integration ) {
			if ( $integration[0] )
				$gateways[] = array( 'name' => $integration[1], 'redsys' => $integration[2], 'ceca' => $integration[3] );
		}

		return array_slice( $gateways, 0, 3 );
	}

	static function get_addons(){
		$addons = array();

		if ( class_exists( 'WC_Subscriptions' ) )
			$addons[] = array(
				'name' => 'Payment Schedule for WooCommerce Subscriptions',
				'url'  => self::import_wp_url( 'payment-schedule-for-woocommerce-subscriptions', 'settings-card' ),
				'desc' => __( 'Show your customers when every payment of their subscription will be charged.', 'clean-login' ),
			);

		if ( defined( 'ACUI_VERSION' ) ) {
			$addons[] = array(
				'name' => 'Recurring Import',
				'url'  => self::import_wp_url( 'recurring-import-addon', 'settings-card' ),
				'desc' => __( 'Keep your users in sync with scheduled imports from a CSV file.', 'clean-login' ),
			);
			$addons[] = array(
				'name' => 'Recurring Export',
				'url'  => self::import_wp_url( 'recurring-export-addon', 'settings-card' ),
				'desc' => __( 'Schedule automatic exports of your users and customers.', 'clean-login' ),
			);
		}
		elseif ( current_user_can( 'install_plugins' ) ) {
			$addons[] = array(
				'name' => 'Import and export users and customers',
				'url'  => admin_url( 'plugin-install.php?tab=plugin-information&plugin=import-users-from-csv-with-meta&TB_iframe=true&width=772&height=700' ),
				'desc' => __( 'Free plugin by the same team: create or update all your users at once from a CSV file, and export them whenever you need.', 'clean-login' ),
			);
		}

		return $addons;
	}

	static function plugin_row_link(){
		if ( self::is_spain() )
			return '<a href="' . esc_url( self::codection_url( '', 'plugin-row' ) ) . '" target="_blank" style="color:#d54e21;font-weight:bold">' . esc_html( 'Pasarelas RedSys, Bizum y Ceca' ) . '</a>';

		return '<a href="' . esc_url( self::import_wp_url( '', 'plugin-row' ) ) . '" target="_blank" style="color:#d54e21;font-weight:bold">' . esc_html__( 'Premium addons and plugins', 'clean-login' ) . '</a>';
	}

	static function render_card(){
		if ( self::is_spain() )
			self::render_spain_card();
		else
			self::render_addons_card();
	}

	static function render_spain_card(){
		$gateways = self::get_gateways();
		?>
		<div class="card">
			<h3 class="title">¿Cobras en tu web?</h3>
			<?php if ( empty( $gateways ) ) : ?>
				<p>En <a href="<?php echo esc_url( self::codection_url( '', 'settings-card' ) ); ?>" target="_blank">Codection</a> hacemos las pasarelas de pago RedSys, Bizum y Ceca para WooCommerce, formularios, membresías, donaciones y venta de entradas.</p>
				<p>Si no tienes tienda, con <a href="<?php echo esc_url( self::codection_url( 'redsys-link-generator', 'settings-card' ) ); ?>" target="_blank">RedSys Link Generator</a> puedes crear enlaces de pago y mandarlos por correo o WhatsApp.</p>
			<?php else : ?>
				<p>Somos los autores de Clean Login y también hacemos pasarelas de pago para los plugins que usas:</p>
				<ul style="list-style:disc;padding-left:20px">
					<?php foreach ( $gateways as $gateway ) : ?>
						<li>
							<strong><?php echo esc_html( $gateway['name'] ); ?>:</strong>
							<a href="<?php echo esc_url( self::codection_url( $gateway['redsys'], 'settings-card' ) ); ?>" target="_blank">RedSys y Bizum</a>
							<?php if ( ! empty( $gateway['ceca'] ) ) : ?>
								· <a href="<?php echo esc_url( self::codection_url( $gateway['ceca'], 'settings-card' ) ); ?>" target="_blank">Ceca</a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<p><a href="<?php echo esc_url( self::codection_url( '', 'settings-card' ) ); ?>" target="_blank">Ver todas las pasarelas</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	static function render_addons_card(){
		$addons = self::get_addons();
		?>
		<div class="card">
			<h3 class="title"><?php echo esc_html__( 'More plugins for your users', 'clean-login' ); ?></h3>
			<?php if ( ! empty( $addons ) ) : ?>
				<ul style="list-style:disc;padding-left:20px">
					<?php foreach ( $addons as $addon ) : ?>
						<li><a href="<?php echo esc_url( $addon['url'] ); ?>" <?php echo strpos( $addon['url'], admin_url() ) === 0 ? 'class="thickbox open-plugin-details-modal"' : 'target="_blank"'; ?>><strong><?php echo esc_html( $addon['name'] ); ?></strong></a>: <?php echo esc_html( $addon['desc'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p><a href="<?php echo esc_url( self::import_wp_url( '', 'settings-card' ) ); ?>" target="_blank"><?php echo esc_html__( 'See all our premium addons and plugins', 'clean-login' ); ?></a></p>
		</div>
		<?php
	}
}
