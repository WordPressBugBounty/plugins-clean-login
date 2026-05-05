<?php
	if ( ! defined( 'ABSPATH' ) ) 
		exit; 
	
	if ( empty( $_GET['user_id'] ) || empty( $_GET['cl_nonce'] ) )
		wp_die( esc_html__( 'Invalid request.', 'clean-login' ) );

	$clean_login_user_id = absint( wp_unslash( $_GET['user_id'] ) );

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['cl_nonce'] ) ), 'cl_pass_changed_' . $clean_login_user_id ) )
		wp_die( esc_html__( 'Invalid request.', 'clean-login' ) );

	$clean_login_new_password = sanitize_text_field( get_transient( 'cl_temporary_pass_' . $clean_login_user_id ) );
	delete_transient( 'cl_temporary_pass_' . $clean_login_user_id );
	$clean_login_login_url = CleanLogin_Controller::get_login_url();
?>

<div class="cleanlogin-container">
	<form class="cleanlogin-form" method="POST">
		
		<fieldset>
			<div class="cleanlogin-field">
				<label><?php echo esc_html__( 'Your new password is', 'clean-login' ); ?></label>
				<input type="text" name="pass" value="<?php echo esc_attr( $clean_login_new_password ); ?>">
			</div>		
		</fieldset>
		
		<div class="cleanlogin-form-bottom">				
			<?php if ( $clean_login_login_url != '' )
				echo "<a href='" . esc_url( $clean_login_login_url ) . "' class='cleanlogin-form-login-link'>" . esc_html__( 'Log in', 'clean-login') . "</a>";
			?>						
		</div>
	</form>
</div>