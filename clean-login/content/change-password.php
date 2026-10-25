<?php
	if ( ! defined( 'ABSPATH' ) ) exit;
	$clean_login_current_user = wp_get_current_user();
?>

<div class="cleanlogin-container">
	<form class="cleanlogin-form" method="post" action="#" onsubmit="submit.disabled = true; return true;">

		<fieldset>

			<div class="cleanlogin-field">
				<label for="pass1"><?php echo esc_html__( 'New password', 'clean-login' ); ?></label>
				<input type="password" id="pass1" name="pass1" value="" autocomplete="new-password">
				<i class="bi bi-eye-slash" id="togglePassword"></i>
				<div id="cleanlogin-strength-meter"
					data-label-weak="<?php echo esc_attr__( 'Weak', 'clean-login' ); ?>"
					data-label-fair="<?php echo esc_attr__( 'Fair', 'clean-login' ); ?>"
					data-label-good="<?php echo esc_attr__( 'Good', 'clean-login' ); ?>"
					data-label-strong="<?php echo esc_attr__( 'Strong', 'clean-login' ); ?>">
					<div class="cleanlogin-strength-bar"><span class="cleanlogin-strength-fill"></span></div>
					<p class="cleanlogin-strength-label"></p>
				</div>
			</div>

			<div class="cleanlogin-field">
				<label for="pass2"><?php echo esc_html__( 'Confirm password', 'clean-login' ); ?></label>
				<input type="password" id="pass2" name="pass2" value="" autocomplete="new-password">
				<i class="bi bi-eye-slash" id="togglePassword2"></i>
			</div>

			<input type="hidden" name="email" value="<?php echo esc_attr( $clean_login_current_user->user_email ); ?>">
			<input type="hidden" name="first_name" value="<?php echo esc_attr( $clean_login_current_user->user_firstname ); ?>">
			<input type="hidden" name="last_name" value="<?php echo esc_attr( $clean_login_current_user->user_lastname ); ?>">
			<input type="hidden" name="clean_login_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'clean_login_wpnonce' ) ); ?>">

		</fieldset>

		<div>
			<input type="submit" value="<?php echo esc_attr__( 'Change password', 'clean-login' ); ?>" name="submit">
			<input type="hidden" name="action" value="edit">
		</div>

	</form>
</div>
