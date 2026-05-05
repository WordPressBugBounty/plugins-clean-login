<?php
	if ( ! defined( 'ABSPATH' ) ) exit; 
	$clean_login_login_url = CleanLogin_Controller::get_login_url();
	$clean_login_current_user = wp_get_current_user();
	$clean_login_edit_url = CleanLogin_Controller::get_edit_url();
	
	$clean_login_show_user_information = get_option( 'cl_hideuser' );
?>

<div class="cleanlogin-container" >
	<div class="cleanlogin-preview">
		<div class="cleanlogin-preview-top">
			<a href="<?php echo esc_url( add_query_arg( 'action', 'logout', $clean_login_login_url) ); ?>" class="cleanlogin-preview-logout-link"><?php echo esc_html__( 'Log out', 'clean-login' ); ?></a>
			<?php if ( $clean_login_edit_url != '' )
				echo "<a href='" . esc_url( $clean_login_edit_url ) . "' class='cleanlogin-preview-edit-link'>" . esc_html__( 'Edit my profile', 'clean-login' ) . "</a>";
			?>
		</div>

		<?php echo wp_kses_post( get_avatar( $clean_login_current_user->ID, 128 ) ); ?>

		<?php // Since 1.1 (show username or not) ?>

		<h4>
			<?php
				if ( $clean_login_show_user_information ) echo esc_html( $clean_login_current_user->user_login );
			 ?>
			<small><?php echo esc_html( $clean_login_current_user->user_firstname ) . ' ' . esc_html( $clean_login_current_user->user_lastname ); ?></small>
		</h4>
	</div>		
</div>