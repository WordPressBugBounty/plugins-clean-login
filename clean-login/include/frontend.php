<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class CleanLogin_Frontend{
    function load(){
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
        add_filter( 'wp_robots',          array( $this, 'noindex_robots' ) );
        add_filter( 'wpseo_robots',       array( $this, 'yoast_noindex' ) );
        add_filter( 'rank_math/frontend/robots', array( $this, 'rankmath_noindex' ) );
        add_action( 'wp_head',            array( $this, 'canonical_tag' ), 1 );
        add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'exclude_from_sitemap' ) );
    }

    function noindex_robots( array $robots ): array {
        if ( ! CleanLogin_Shortcode::has_clean_login() )
            return $robots;

        $robots['noindex']  = true;
        $robots['nofollow'] = true;
        unset( $robots['max-image-preview'] );

        return $robots;
    }

    function yoast_noindex( $robots_str ): string {
        if ( ! CleanLogin_Shortcode::has_clean_login() )
            return $robots_str;

        return 'noindex, nofollow';
    }

    function rankmath_noindex( array $robots ): array {
        if ( ! CleanLogin_Shortcode::has_clean_login() )
            return $robots;

        $robots['index']  = 'noindex';
        $robots['follow'] = 'nofollow';

        return $robots;
    }

    function canonical_tag(): void {
        if ( ! CleanLogin_Shortcode::has_clean_login() )
            return;

        // Major SEO plugins already handle canonical — avoid duplicates.
        if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) )
            return;

        $canonical = get_permalink( get_the_ID() );
        if ( $canonical )
            echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
    }

    function exclude_from_sitemap( array $args ): array {
        $ids = array_filter( array(
            (int) get_option( 'cl_login_id' ),
            (int) get_option( 'cl_edit_id' ),
            (int) get_option( 'cl_register_id' ),
            (int) get_option( 'cl_restore_id' ),
            (int) get_option( 'cl_change_password_id' ),
        ) );

        if ( ! empty( $ids ) )
            $args['post__not_in'] = array_merge( $args['post__not_in'] ?? array(), $ids );

        return $args;
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