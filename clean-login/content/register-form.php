<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="cleanlogin-container cleanlogin-full-width">
	<form class="cleanlogin-form" method="post" action="#">
		<fieldset>

			<?php do_action("cleanlogin_before_register_form"); ?>
			<?php if ( get_option( 'cl_nameandsurname' ) ) : ?>
				<div class="cleanlogin-field">
                    <label for="first_name"><?php echo esc_html__( 'First name', 'clean-login' ); ?></label>
					<input class="cleanlogin-field-name" type="text" name="first_name" value="" placeholder="<?php echo esc_attr__( 'First name', 'clean-login' ); ?>">
				</div>
				<div class="cleanlogin-field">
                    <label for="last_name"><?php echo esc_html__( 'Last name', 'clean-login' ); ?></label>
					<input class="cleanlogin-field-surname" type="text" name="last_name" value="" placeholder="<?php echo esc_attr__( 'Last name', 'clean-login' ); ?>">
				</div>
			<?php endif; ?>

			<?php if ( !get_option( 'cl_email_username' ) ) : ?>
				<div class="cleanlogin-field">
                    <label for="username"><?php echo esc_html__( 'Username', 'clean-login' ); ?></label>
					<input class="cleanlogin-field-username" type="text" name="username" value="" placeholder="<?php echo esc_attr__( 'Username', 'clean-login' ); ?>" aria-label="<?php echo esc_attr__( 'Username', 'clean-login' ); ?>">
				</div>
			<?php endif; ?>

			<div class="cleanlogin-field">
                <label for="email"><?php echo esc_html__( 'Email', 'clean-login' ); ?></label>
				<input class="cleanlogin-field-email" type="email" name="email" value="" placeholder="<?php echo esc_attr__( 'Email', 'clean-login' ); ?>" aria-label="<?php echo esc_attr__( 'Email', 'clean-login' ); ?>">
			</div>

			<div class="cleanlogin-field-website">
				<label for='website'>Website</label>
				<input type='text' name='website' value=".">
			</div>

			<div class="cleanlogin-field">
                <label for="pass1"><?php echo esc_html__( 'New password', 'clean-login' ); ?></label>
				<input class="cleanlogin-field-password" type="password" name="pass1" value="" autocomplete="off" placeholder="<?php echo esc_attr__( 'New password', 'clean-login' ); ?>" aria-label="<?php echo esc_attr__( 'New password', 'clean-login' ); ?>">
			</div>

			<?php if ( !get_option( 'cl_single_password' ) ) : ?>
				<div class="cleanlogin-field">
                    <label for="pass2"><?php echo esc_html__( 'Confirm password', 'clean-login' ); ?></label>
					<input class="cleanlogin-field-password" type="password" name="pass2" value="" autocomplete="off" placeholder="<?php echo esc_attr__( 'Confirm password', 'clean-login' ); ?>" aria-label="<?php echo esc_attr__( 'Confirm password', 'clean-login' ); ?>">
				</div>
			<?php endif; ?>

			<?php if ( get_option( 'cl_antispam' ) ) : ?>
				<div class="cleanlogin-field">
					<img src="<?php echo esc_url( CLEAN_LOGIN_CAPTCHA_URL ); ?>"/>
					<input class="cleanlogin-field-spam" type="text" name="captcha" value="" autocomplete="off" placeholder="<?php echo esc_attr__( 'Type the text above', 'clean-login' ); ?>" aria-label="<?php echo esc_attr__( 'Type the text above', 'clean-login' ); ?>">
				</div>
			<?php endif; ?>

			<?php if ( get_option( 'cl_gcaptcha' ) ) : ?>
				<?php CleanLogin_Frontend::gcaptcha_script(); ?>
				<div class="cleanlogin-field">
					<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( get_option( 'cl_gcaptcha_sitekey' ) ); ?>"></div>
				</div>
			<?php endif; ?>

			<?php if ( get_option( 'cl_chooserole' ) ) : ?>
				<?php if ($param['role']) : ?>
				<input type="text" name="role" value="<?php echo esc_attr( $param['role'] ); ?>" hidden >
				<?php else : ?>
				<div class="cleanlogin-field cleanlogin-field-role" <?php if ( get_option( 'cl_antispam' ) || get_option( 'cl_gcaptcha' ) ) echo 'style="margin-top: 46px;"'; ?> >
					<span><?php echo esc_html__( 'Choose your role:', 'clean-login' ); ?></span>
					<select name="role" id="role">
						<?php
						$clean_login_newuserroles = get_option ( 'cl_newuserroles' );
						global $wp_roles;
						foreach($clean_login_newuserroles as $role){
							echo '<option value="' . esc_attr( $role ) . '">' . esc_html( translate_user_role( $wp_roles->roles[ $role ]['name'] ) ) . '</option>';
						}
						?>
					</select>
				</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( get_option( 'cl_termsconditions' ) ) : ?>
				<div class="cleanlogin-field">
					<label class="cleanlogin-terms">
						<input name="termsconditions" type="checkbox" id="termsconditions">
						<a href="<?php echo esc_url( CleanLogin_Controller::get_translated_option_page( 'cl_termsconditionsURL' ) ); ?>" target="_blank"><?php echo esc_html( get_option( 'cl_termsconditionsMSG' ) ); ?></a>
					</label>
				</div>
			<?php endif; ?>

			<input type="hidden" name="clean_login_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'clean_login_wpnonce' ) ); ?>">

			<?php do_action("cleanlogin_after_register_form"); ?>
		</fieldset>

		<div>
			<input type="submit" value="<?php echo esc_attr__( 'Register', 'clean-login' ); ?>" name="btn-submit" onclick="this.form.submit(); this.disabled = true;">
			<input type="hidden" name="action" value="register">
		</div>

	</form>
</div>
