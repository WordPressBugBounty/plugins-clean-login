<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class CleanLogin_Blocks{
	private $shortcodes;

	function load(){
		$this->shortcodes = new CleanLogin_Shortcode();

		add_action( 'init', array( $this, 'register' ) );

		if( version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) )
			add_filter( 'block_categories_all', array( $this, 'add_category' ) );
		else
			add_filter( 'block_categories', array( $this, 'add_category' ) );
	}

	function get_render_callbacks(){
		return array(
			'login'           => 'render_login',
			'register'        => 'render_register',
			'restore'         => 'render_restore',
			'edit'            => 'render_edit',
			'change-password' => 'render_change_password',
			'logout'          => 'render_logout',
		);
	}

	function register(){
		if( !function_exists( 'register_block_type_from_metadata' ) )
			return;

		CleanLogin_Frontend::register_assets();

		wp_register_script(
			'clean-login-blocks',
			CLEAN_LOGIN_URL . 'content/js/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor' ),
			filemtime( CLEAN_LOGIN_PATH . 'content/js/blocks.js' ),
			true
		);

		foreach( $this->get_render_callbacks() as $block => $callback ){
			register_block_type_from_metadata( CLEAN_LOGIN_PATH . 'blocks/' . $block, array(
				'render_callback' => array( $this, $callback ),
			) );
		}
	}

	function add_category( $categories ){
		foreach( $categories as $category ){
			if( $category['slug'] === 'clean-login' )
				return $categories;
		}

		$categories[] = array(
			'slug'  => 'clean-login',
			'title' => 'Clean Login',
			'icon'  => null,
		);

		return $categories;
	}

	static function is_editor_preview(){
		if( !defined( 'REST_REQUEST' ) || !REST_REQUEST )
			return false;

		global $wp;
		$route = isset( $wp->query_vars['rest_route'] ) ? $wp->query_vars['rest_route'] : '';

		return strpos( $route, '/wp/v2/block-renderer/' ) === 0;
	}

	function render_template( $template, $param = array() ){
		ob_start();
		CleanLogin_Frontend::get_template_file( $template, $param );
		return ob_get_clean();
	}

	function render_login(){
		CleanLogin_Frontend::enqueue_assets();

		if( self::is_editor_preview() )
			return $this->render_template( 'login-form.php' );

		return $this->shortcodes->clean_login();
	}

	function render_register(){
		CleanLogin_Frontend::enqueue_assets();

		if( self::is_editor_preview() && get_option( 'users_can_register' ) )
			return $this->render_template( 'register-form.php', array( 'role' => false, 'template' => 'register-form.php' ) );

		return $this->shortcodes->clean_login_register( array() );
	}

	function render_restore(){
		CleanLogin_Frontend::enqueue_assets();

		if( self::is_editor_preview() )
			return $this->render_template( 'restore-form.php' );

		return $this->shortcodes->clean_login_restore();
	}

	function render_edit(){
		CleanLogin_Frontend::enqueue_assets();

		return $this->shortcodes->clean_login_edit( array() );
	}

	function render_change_password(){
		CleanLogin_Frontend::enqueue_assets();

		return $this->shortcodes->clean_login_change_password();
	}

	function render_logout(){
		CleanLogin_Frontend::enqueue_assets();

		return $this->shortcodes->clean_login_logout( array() );
	}
}
