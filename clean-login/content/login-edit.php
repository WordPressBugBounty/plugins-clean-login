<?php
	if ( ! defined( 'ABSPATH' ) ) exit;
	$clean_login_current_user = wp_get_current_user();
?>

<?php do_action("cleanlogin_before_login_edit_form_container"); ?>

<div class="cleanlogin-container cleanlogin-full-width">
	<form class="cleanlogin-form" method="post" action="#" onsubmit="submit.disabled = true; return true;">

		<h4><?php echo esc_html__( 'General information', 'clean-login' ); ?></h4>

		<fieldset>
			<?php do_action("cleanlogin_before_login_edit_form"); ?>
			<div class="cleanlogin-field">
				<label for="first_name"><?php echo esc_html__( 'First name', 'clean-login' ); ?></label>
				<input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $clean_login_current_user->user_firstname ); ?>">
			</div>

			<div class="cleanlogin-field">
				<label for="last_name"><?php echo esc_html__( 'Last name', 'clean-login' ); ?></label>
				<input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $clean_login_current_user->user_lastname ); ?>">
			</div>

			<?php if( $param['show_email'] == "true" ): ?>
			<div class="cleanlogin-field">
				<label for="email"><?php echo esc_html__( 'E-mail', 'clean-login' ); ?></label>
				<input type="text" id="email" name="email" value="<?php echo esc_attr( $clean_login_current_user->user_email ); ?>">
			</div>
			<?php else: ?>
				<input type="hidden" name="email" value="<?php echo esc_attr( $clean_login_current_user->user_email ); ?>">
			<?php endif; ?>

			<input type="hidden" name="clean_login_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'clean_login_wpnonce' ) ); ?>">

			<?php do_action("cleanlogin_after_login_edit_form"); ?>
		</fieldset>

		<h4><?php echo esc_html__( 'Change password', 'clean-login' ); ?></h4>

		<p class="cleanlogin-form-description"><?php echo esc_html__( "If you would like to change the password type a new one. Otherwise leave this blank.", 'clean-login' ); ?></p>

		<fieldset>

			<div class="cleanlogin-field">
				<label for="pass1"><?php echo esc_html__( 'New password', 'clean-login' ); ?></label>
				<input type="password" id="pass1" name="pass1" value="" autocomplete="off">
				<i class="bi bi-eye-slash" id="togglePassword"></i>
			</div>

			<div class="cleanlogin-field">
				<label for="pass2"><?php echo esc_html__( 'Confirm password', 'clean-login' ); ?></label>
				<input type="password" id="pass2" name="pass2" value="" autocomplete="off">
				<i class="bi bi-eye-slash" id="togglePassword2"></i>
			</div>

		</fieldset>

		<div>
			<input type="submit" value="<?php echo esc_attr__( 'Update profile', 'clean-login' ); ?>" name="submit">
			<input type="hidden" name="action" value="edit">
		</div>

	</form>
</div>
