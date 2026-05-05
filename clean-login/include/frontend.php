<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class CleanLogin_Frontend{
    function load(){
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );                
    }

    function enqueue() {
        if( !CleanLogin_Shortcode::has_clean_login() )
            return;

        wp_enqueue_style( 'clean-login-css', CLEAN_LOGIN_URL . 'content/style.css', array(), CLEAN_LOGIN_VERSION );
        wp_enqueue_style( 'clean-login-bootstrap-icons', CLEAN_LOGIN_URL . 'content/bootstrap-icons.css', array(), CLEAN_LOGIN_VERSION );
        wp_enqueue_script( 'clean-login-js', CLEAN_LOGIN_URL . 'content/js/clean-login.js', array(), CLEAN_LOGIN_VERSION, true );
    }

    static function get_template_file( $template, $param = array() ){
        if ( $overridden_template = locate_template( 'clean-login/' . $template ) ) {
            require( $overridden_template );
        } else {
            require( CLEAN_LOGIN_PATH . 'content/' . sanitize_file_name( $template ) );
        }
    }
    
    static function gcaptcha_script() {
        $lang_gcaptcha_options = array('nb_NO' => 'no', 'en_US' => 'en', 'en_GB' => 'en', 'es_ES' => 'es');
        $locale = get_locale();
        $url = 'https://www.google.com/recaptcha/api.js';
        if( isset( $lang_gcaptcha_options[ $locale ] ) ){
            $url = add_query_arg( 'hl', $lang_gcaptcha_options[ $locale ], $url );
        }
        wp_enqueue_script( 'google-recaptcha', $url, array(), CLEAN_LOGIN_VERSION, false );
    }
}