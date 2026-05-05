<?php
	if ( ! defined( 'ABSPATH' ) ) exit;
	$clean_login_login_url = CleanLogin_Controller::get_login_url();
	$clean_login_register_url = CleanLogin_Controller::get_register_url();
	$clean_login_restore_url = CleanLogin_Controller::get_restore_password_url();
?>

<div class="cleanlogin-container">

	<form class="cleanlogin-form" method="post" action="<?php echo esc_url( $clean_login_login_url ); ?>" onsubmit="submit.disabled = true; return true;">

		<fieldset>

			<?php do_action("cleanlogin_before_login_form"); ?>
			<div class="cleanlogin-field">
                <label for="log"><?php echo esc_html__( 'Username', 'clean-login' ); ?></label>
				<input class="cleanlogin-field-username" type="text" name="log" placeholder="<?php echo esc_attr__( 'Username', 'clean-login' ); ?>" aria-label="<?php echo esc_attr__( 'Username', 'clean-login' ); ?>">
			</div>

			<div class="cleanlogin-field">
                <label for="pwd"><?php echo esc_html__( 'Password', 'clean-login' ); ?></label>
				<input class="cleanlogin-field-password" type="password" id="pwd" name="pwd" placeholder="<?php echo esc_attr__( 'Password', 'clean-login' ); ?>" aria-label="<?php echo esc_attr__( 'Password', 'clean-login' ); ?>">
                <i class="bi bi-eye-slash" id="togglePassword"></i>
			</div>

			<?php if ( get_option( 'cl_gcaptcha' ) ) : ?>
				<?php CleanLogin_Frontend::gcaptcha_script(); ?>
				<div class="cleanlogin-field">
					<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( get_option( 'cl_gcaptcha_sitekey' ) ); ?>"></div>
				</div>
			<?php endif; ?>

			<input type="hidden" name="clean_login_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'clean_login_wpnonce' ) ); ?>">
            <?php
            $clean_login_redirect = isset( $_GET['url'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_GET['url'] ) ), '' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( $clean_login_redirect !== '' ) :
            ?><input type="hidden" name="clean_login_redirect" value="<?php echo esc_url( $clean_login_redirect ); ?>"><?php endif; ?>

			<?php do_action("cleanlogin_after_login_form"); ?>
		</fieldset>

		<fieldset>
			<input class="cleanlogin-field" type="submit" value="<?php echo esc_attr__( 'Log in', 'clean-login' ); ?>" name="submit">
			<input type="hidden" name="action" value="login">

			<div class="cleanlogin-field cleanlogin-field-remember">
				<input type="checkbox" id="rememberme" name="rememberme" value="forever">
				<label for="rememberme"><?php echo esc_html__( 'Remember?', 'clean-login' ); ?></label>
			</div>
		</fieldset>

		<?php echo do_shortcode( apply_filters( 'clean_login_login_form', '') ); ?>

		<div class="cleanlogin-form-bottom">

            <?php if ( $clean_login_restore_url != '' )
				echo "<a href='" . esc_url( $clean_login_restore_url ) . "' class='cleanlogin-form-pwd-link'>" . esc_html__( 'Lost password?', 'clean-login' ) . "</a>";
			?>

			<?php if ( $clean_login_register_url != '' && get_option( 'users_can_register' ) )
				echo "<a href='" . esc_url( $clean_login_register_url ) . "' class='cleanlogin-form-register-link'>" . esc_html__( 'Register', 'clean-login' ) . "</a>";
			?>

		</div>

	</form>

</div>
