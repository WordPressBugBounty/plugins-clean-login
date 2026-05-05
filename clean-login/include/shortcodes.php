<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class CleanLogin_Shortcode{
	function load(){
		add_shortcode( 'clean-login', array( $this, 'clean_login' ) );
		add_shortcode( 'clean-login-edit', array( $this, 'clean_login_edit' ) );
		add_shortcode( 'clean-login-register', array( $this, 'clean_login_register' ) );
		add_shortcode( 'clean-login-restore', array( $this, 'clean_login_restore' ) );
		
		add_action( 'save_post', array( $this, 'get_pages_with_shortcodes' ), 10, 1 );
		add_action( 'wp_trash_post', array( $this, 'maybe_delete_page_with_shortcodes' ), 10, 1 );
	}

    static function has_clean_login(){
        global $post;

        $shortcodes = array( 'clean-login', 'clean-login-edit', 'clean-login-register', 'clean-login-restore' );
        foreach( $shortcodes as $shortcode ){
            if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $shortcode ) )
                return true;
        }

        return false;
    }

	function clean_login( $atts ) {
		ob_start();
		
		if ( isset( $_GET['authentication'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$authentication = sanitize_text_field( wp_unslash( $_GET['authentication'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if( $authentication == 'wrongcaptcha' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'CAPTCHA is not valid, please try again', 'clean-login' ) ."</p></div>";
			elseif( $authentication == 'success' )
				echo "<div class='cleanlogin-notification success'><p>". esc_html__( 'Successfully logged in!', 'clean-login' ) ."</p></div>";
			elseif( $authentication == 'failed' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Wrong credentials', 'clean-login' ) ."</p></div>";
			elseif( $authentication == 'logout' )
				echo "<div class='cleanlogin-notification success'><p>". esc_html__( 'Successfully logged out!', 'clean-login' ) ."</p></div>";
			elseif( $authentication == 'failed-activation' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Something went wrong while activating your user', 'clean-login' ) ."</p></div>";
			elseif( $authentication == 'disabled' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Your account is currently disabled', 'clean-login' ) ."</p></div>";
			elseif( $authentication == 'success-activation' )
				echo "<div class='cleanlogin-notification success'><p>". esc_html__( 'Successfully activated', 'clean-login' ) ."</p></div>";
		}

		if ( is_user_logged_in() ) {
			CleanLogin_Frontend::get_template_file( 'login-preview.php' );
		} else {
			CleanLogin_Frontend::get_template_file( 'login-form.php' );
		}

		return ob_get_clean();
	}

	function clean_login_edit( $atts ) {
		$atts = shortcode_atts( array( 'show_email' => true ), $atts );
	
		ob_start();
	
		if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$updated_result = sanitize_text_field( wp_unslash( $_GET['updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			switch( $updated_result ){
				case 'success':
					echo "<div class='cleanlogin-notification success'><p>". esc_html__( 'Information updated', 'clean-login' ) ."</p></div>";
					break;

				case 'emailchangedsuccess':
					echo "<div class='cleanlogin-notification success'><p>". esc_html__( 'Confirmed email change', 'clean-login' ) ."</p></div>";
					break;

				case 'passcomplex':
					echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Passwords must be eight characters including one upper/lowercase letter, one special/symbol character and alphanumeric characters. Passwords should not contain the user\'s username, email, or first/last name.', 'clean-login' ) ."</p></div>";
					break;

				case 'wrongpass':
					echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Passwords must be identical', 'clean-login' ) ."</p></div>";
					break;

				case 'wrongmail':
					echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Error updating email', 'clean-login' ) ."</p></div>";
					break;

				case 'failed':
					echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Something strange has ocurred', 'clean-login' ) ."</p></div>";
					break;				
			}
		}
	
		if ( is_user_logged_in() ) {
			CleanLogin_Frontend::get_template_file( 'login-edit.php', $atts );
		} else {
			echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'You need to be logged in to edit your profile', 'clean-login' ) ."</p></div>";
			CleanLogin_Frontend::get_template_file( 'login-form.php' );
		}
	
		return ob_get_clean();
	}
	
	function clean_login_register( $atts ){
		if( !get_option( 'users_can_register' ) ){
			echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Registration is not allowed in this site', 'clean-login' ) ."</p></div>";
			return;
		}
		
		$param = shortcode_atts( array(
			'role' => false,
			'template' => 'register-form.php',
		), $atts );

		if( $param['role'] !== false && !in_array( $param['role'], array_keys( wp_roles()->roles ) ) )
			$param['role'] = get_option( 'default_role' );
	
		ob_start();

		if ( isset( $_GET['created'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$created = sanitize_text_field( wp_unslash( $_GET['created'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $created == 'success' )
				echo "<div class='cleanlogin-notification success'><p>". esc_html__( 'User created', 'clean-login' ) ."</p></div>";
			else if ( $created == 'success-link' )
				echo "<div class='cleanlogin-notification success'><p>". esc_html__( 'User created', 'clean-login' ) ."<br>". esc_html__( 'Please confirm your account, you will receive an email', 'clean-login' ) ."</p></div>";
			else if ( $created == 'created' )
				echo "<div class='cleanlogin-notification success'><p>". esc_html__( 'New user created', 'clean-login' ) ."</p></div>";
			else if ( $created == 'passcomplex' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Passwords must be eight characters including one upper/lowercase letter, one special/symbol character and alphanumeric characters. Passwords should not contain the user\'s username, email, or first/last name.', 'clean-login' ) ."</p></div>";
			else if ( $created == 'wronguser' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Username is not valid', 'clean-login' ) ."</p></div>";
			else if ( $created == 'wrongname' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'First name is not valid', 'clean-login' ) ."</p></div>";
			else if ( $created == 'wrongsurname' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Last name is not valid', 'clean-login' ) ."</p></div>";
			else if ( $created == 'wrongpass' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Passwords must be identical and filled', 'clean-login' ) ."</p></div>";
			else if ( $created == 'wrongmail' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Email is not valid', 'clean-login' ) ."</p></div>";
			else if ( $created == 'emailexists' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'There is already a user registered with this email. Login with this existing account. If you do not remember your password, you will find a recuperation link at the login form.', 'clean-login' ) ."</p></div>";
			else if ( $created == 'wrongcaptcha' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'CAPTCHA is not valid, please try again', 'clean-login' ) ."</p></div>";
			else if ( $created == 'failed' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Something strange has ocurred while created the new user', 'clean-login' ) ."</p></div>";
			else if ( $created == 'terms' )
				echo "<div class='cleanlogin-notification error'><p>\"". esc_html( get_option( 'cl_termsconditionsMSG' ) ) . '" ' . esc_html__( 'must be checked', 'clean-login' ) . "</p></div>";
		}
	
		if ( !is_user_logged_in() ) {
			CleanLogin_Frontend::get_template_file( sanitize_file_name( $param['template'] ), $param );
		} else {
			echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'You are now logged in. It makes no sense to register a new user', 'clean-login' ) ."</p></div>";
			CleanLogin_Frontend::get_template_file( 'login-preview.php' );
		}
	
		return ob_get_clean();
	}

	function clean_login_restore( $atts ) {
		ob_start();
	
		if ( isset( $_GET['sent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$sent = sanitize_text_field( wp_unslash( $_GET['sent'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $sent == 'success' )
				echo "<div class='cleanlogin-notification success'><p>". esc_html__( 'You will receive an email with the activation link', 'clean-login' ) ."</p></div>";
			else if ( $sent == 'sent' )
				echo "<div class='cleanlogin-notification success'><p>". esc_html__( 'You may receive an email with the activation link', 'clean-login' ) ."</p></div>";
			else if ( $sent == 'failed' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'An error has ocurred sending the email', 'clean-login' ) ."</p></div>";
			else if ( $sent == 'wronguser' )
				echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'Username is not valid', 'clean-login' ) ."</p></div>";
		}
	
		if ( !is_user_logged_in() ) {
			if ( isset( $_GET['pass_changed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				CleanLogin_Frontend::get_template_file( 'restore-new.php' );
			} else
				CleanLogin_Frontend::get_template_file( 'restore-form.php' );
		} else {
			echo "<div class='cleanlogin-notification error'><p>". esc_html__( 'You are now logged in. It makes no sense to restore your account', 'clean-login' ) ."</p></div>";
			CleanLogin_Frontend::get_template_file( 'login-preview.php' );
		}
	
		return ob_get_clean();
	}

	static function is_login_page(){
		if( get_the_ID() == get_option( 'cl_login_id' ) )
			return true;

		if( get_the_ID() == url_to_postid( CleanLogin_Controller::get_login_url() ) )
			return true;

		return false;
	}

	function get_pages_with_shortcodes( $post_id ) {
		if( 'trash' == get_post_status( $post_id ) )
			return;

		$revision = wp_is_post_revision( $post_id );
		if( $revision ) 
			$post_id = $revision;
		
		$post = get_post( $post_id );
	
		if( has_shortcode( $post->post_content, 'clean-login' ) ) {
			update_option( 'cl_login_url', get_permalink( $post->ID ) );
			update_option( 'cl_login_id', $post->ID );
		}
	
		if( has_shortcode( $post->post_content, 'clean-login-edit' ) ) {
			update_option( 'cl_edit_url', get_permalink( $post->ID ) );
			update_option( 'cl_edit_id', $post->ID );
		}
	
		if( has_shortcode( $post->post_content, 'clean-login-register' ) ) {
			update_option( 'cl_register_url', get_permalink( $post->ID ) );
			update_option( 'cl_register_id', $post->ID );
		}
	
		if( has_shortcode( $post->post_content, 'clean-login-restore' ) ) {
			update_option( 'cl_restore_url', get_permalink( $post->ID ) );
			update_option( 'cl_restore_id', $post->ID );
		}

		// delete if not used
		$keys = array( 'login' => 'clean-login', 'edit' => 'clean-login-edit', 'register' => 'clean-login-register', 'restore' => 'clean-login-restore' );
		foreach ( $keys as $key => $shortcode ) {
			if( $post_id == get_option( 'cl_' . $key . '_id' ) && !has_shortcode( $post->post_content, $shortcode ) ){
				delete_option( 'cl_' . $key . '_url' );
				delete_option( 'cl_' . $key . '_id' );
			}
		}
	}
	
	function maybe_delete_page_with_shortcodes( $post_id ){
		if( $post_id == get_option( 'cl_login_id' ) ){
			delete_option( 'cl_login_url' );
			delete_option( 'cl_login_id' );
		}

		if( $post_id == get_option( 'cl_edit_id' ) ){
			delete_option( 'cl_edit_url' );
			delete_option( 'cl_edit_id' );
		}

		if( $post_id == get_option( 'cl_register_id' ) ){
			delete_option( 'cl_register_url' );
			delete_option( 'cl_register_id' );
		}

		if( $post_id == get_option( 'cl_restore_id' ) ){
			delete_option( 'cl_restore_url' );
			delete_option( 'cl_restore_id' );
		}
	}	
}