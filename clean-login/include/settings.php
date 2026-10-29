<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class CleanLogin_Settings
{
    function load()
    {
        add_action('after_setup_theme', array($this, 'maybe_remove_admin_bar'));
        add_action('admin_init', array($this, 'maybe_block_dashboard_access'), 1);
        add_action('login_init', array($this, 'maybe_redirect_wp_login'), 1);
        add_filter('login_url', array($this, 'filter_login_url'), 10, 2);
        add_filter('lostpassword_url', array($this, 'filter_lostpassword_url'), 10, 2);
        add_filter('register_url', array($this, 'filter_register_url'));
        add_action('admin_notices', array($this, 'wp_login_redirect_notice'));
        add_action('admin_post_clean_login_wp_login_check', array($this, 'wp_login_check_action'));
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
        add_action('admin_post_clean_login_gcaptcha_test', array($this, 'gcaptcha_test'));
        add_action('admin_post_clean_login_gcaptcha_log', array($this, 'gcaptcha_log_action'));
    }

    function enqueue( string $hook ) {
        if( $hook !== 'settings_page_clean_login_menu' )
            return;
        $newuserroles = get_option( 'cl_newuserroles' );
        wp_enqueue_script( 'clean-login-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'content/js/clean-login-admin.js', array( 'jquery' ), filemtime( CLEAN_LOGIN_PATH . 'content/js/clean-login-admin.js' ), true );
        wp_localize_script( 'clean-login-admin', 'cleanLoginAdmin', array( 'newuserroles' => $newuserroles ? $newuserroles : array() ) );

        if( get_option( 'cl_gcaptcha' ) && get_option( 'cl_gcaptcha_sitekey' ) )
            CleanLogin_Frontend::gcaptcha_script();
    }

    function check_gcaptcha_admin_request( $nonce_action ) {
        if ( ! current_user_can( apply_filters( 'clean_login_admin_capability', 'manage_options' ) ) )
            wp_die( esc_html__( 'Admin area', 'clean-login' ) );

        check_admin_referer( $nonce_action );
    }

    function redirect_to_gcaptcha_diagnostics() {
        wp_safe_redirect( admin_url( 'options-general.php?page=clean_login_menu#cl-gcaptcha-diagnostics' ) );
        exit;
    }

    function gcaptcha_test() {
        $this->check_gcaptcha_admin_request( 'clean_login_gcaptcha_test' );

        $token = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '';
        $secret = get_option( 'cl_gcaptcha_secretkey' );

        if ( $token === '' ) {
            $result = CleanLogin_Controller::verify_gcaptcha_token( 'clean-login-secret-check', $secret );
            $result['secret_only'] = true;
        } else {
            $result = CleanLogin_Controller::verify_gcaptcha_token( $token, $secret );
            $result['secret_only'] = false;
        }

        set_transient( 'cl_gcaptcha_test_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );

        $this->redirect_to_gcaptcha_diagnostics();
    }

    function gcaptcha_log_action() {
        $this->check_gcaptcha_admin_request( 'clean_login_gcaptcha_log' );

        $do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';

        if ( $do === 'enable' )
            update_option( 'cl_gcaptcha_debug', true );
        elseif ( $do === 'disable' )
            update_option( 'cl_gcaptcha_debug', false );
        elseif ( $do === 'clear' )
            delete_option( 'cl_gcaptcha_log' );

        $this->redirect_to_gcaptcha_diagnostics();
    }

    function describe_gcaptcha_error( $code ) {
        $messages = array(
            'missing-input-response' => __( 'No reCAPTCHA answer was received. The box did not load on the form (script blocked or delayed by a cache/optimisation or cookie plugin, or a theme template without the box) or the user did not tick it.', 'clean-login' ),
            'invalid-input-response' => __( 'The answer is invalid or expired, or the Site Key and the Secret Key do not belong to the same reCAPTCHA key.', 'clean-login' ),
            'missing-input-secret'   => __( 'The Secret Key is empty.', 'clean-login' ),
            'invalid-input-secret'   => __( 'The Secret Key is invalid or malformed.', 'clean-login' ),
            'timeout-or-duplicate'   => __( 'The answer expired (more than two minutes old) or was already used.', 'clean-login' ),
            'bad-request'            => __( 'Google rejected the request as malformed.', 'clean-login' ),
        );

        return isset( $messages[ $code ] ) ? $messages[ $code ] : $code;
    }

    function describe_gcaptcha_result( $result ) {
        if ( ! empty( $result['http_error'] ) ) {
            /* translators: %s: connection error message */
            return array( sprintf( __( 'WordPress could not connect to Google (%s). The server may be blocking outgoing connections to www.google.com.', 'clean-login' ), $result['http_error'] ) );
        }

        if ( empty( $result['error_codes'] ) )
            return array( __( 'Google answered without an error code.', 'clean-login' ) );

        return array_map( array( $this, 'describe_gcaptcha_error' ), $result['error_codes'] );
    }

    function get_gcaptcha_conflicting_plugins() {
        $slugs = array(
            'wp-rocket', 'litespeed-cache', 'autoptimize', 'perfmatters', 'w3-total-cache', 'wp-optimize', 'sg-cachepress',
            'flying-scripts', 'nitropack', 'wp-fastest-cache', 'hummingbird-performance', 'async-javascript', 'swift-performance-lite',
            'complianz-gdpr', 'complianz-gdpr-premium', 'cookie-law-info', 'cookiebot', 'cookie-notice', 'gdpr-cookie-compliance',
            'iubenda-cookie-law-solution', 'real-cookie-banner', 'borlabs-cookie', 'termly', 'uk-cookie-consent',
        );

        return $this->get_active_plugins_by_slug( $slugs );
    }

    function get_active_plugins_by_slug( $slugs ) {
        if ( ! function_exists( 'get_plugins' ) )
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $active = (array) get_option( 'active_plugins', array() );
        if ( is_multisite() )
            $active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );

        $all = get_plugins();
        $found = array();

        foreach ( $active as $file ) {
            if ( in_array( dirname( $file ), $slugs, true ) )
                $found[] = isset( $all[ $file ]['Name'] ) ? $all[ $file ]['Name'] : dirname( $file );
        }

        return array_unique( $found );
    }

    function get_gcaptcha_checks() {
        $checks = array();

        $sitekey = get_option( 'cl_gcaptcha_sitekey' );
        $secretkey = get_option( 'cl_gcaptcha_secretkey' );
        $checks[] = array(
            'label'  => __( 'Keys', 'clean-login' ),
            'status' => ( $sitekey && $secretkey ) ? 'ok' : 'error',
            'text'   => ( $sitekey && $secretkey ) ? __( 'Site Key and Secret Key are filled in. Use the test below to validate them with Google.', 'clean-login' ) : __( 'The Site Key or the Secret Key is empty, so every login will fail.', 'clean-login' ),
        );

        $checks[] = array(
            'label'  => __( 'Login page', 'clean-login' ),
            'status' => get_option( 'cl_login_url' ) ? 'ok' : 'warning',
            'text'   => get_option( 'cl_login_url' ) ? __( 'The login form (Login form block or [clean-login] shortcode) is in use. Open that page in a private window to check that the box is shown.', 'clean-login' ) : __( 'The login form (Login form block or [clean-login] shortcode) is not detected on any page.', 'clean-login' ),
        );

        $templates = array( 'login-form.php' );
        if ( get_option( 'users_can_register' ) )
            $templates[] = 'register-form.php';

        foreach ( $templates as $template ) {
            $override = locate_template( 'clean-login/' . $template );

            if ( ! $override ) {
                /* translators: %s: template file name */
                $text = sprintf( __( '%s: the plugin template is used.', 'clean-login' ), $template );
                $status = 'ok';
            } elseif ( strpos( (string) file_get_contents( $override ), 'g-recaptcha' ) === false ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                /* translators: %s: template file path */
                $text = sprintf( __( 'The theme overrides this form with %s and that file does not contain the reCAPTCHA box. Delete it or update it from the plugin template.', 'clean-login' ), $override );
                $status = 'error';
            } else {
                /* translators: %s: template file path */
                $text = sprintf( __( 'The theme overrides this form with %s, which includes the reCAPTCHA box.', 'clean-login' ), $override );
                $status = 'ok';
            }

            $checks[] = array( 'label' => __( 'Form template', 'clean-login' ), 'status' => $status, 'text' => $text );
        }

        $plugins = $this->get_gcaptcha_conflicting_plugins();
        $checks[] = array(
            'label'  => __( 'Other plugins', 'clean-login' ),
            'status' => empty( $plugins ) ? 'ok' : 'warning',
            /* translators: %s: comma separated list of plugin names */
            'text'   => empty( $plugins ) ? __( 'No known cache, optimisation or cookie consent plugin detected.', 'clean-login' ) : sprintf( __( 'These plugins can delay or block the Google reCAPTCHA script: %s. Exclude "recaptcha/api.js" from JavaScript delay, defer and combine options, and allow Google reCAPTCHA in the cookie banner.', 'clean-login' ), implode( ', ', $plugins ) ),
        );

        return $checks;
    }

    function render_gcaptcha_status_icon( $status ) {
        $icons = array( 'ok' => array( 'yes-alt', '#00a32a' ), 'warning' => array( 'warning', '#dba617' ), 'error' => array( 'dismiss', '#d63638' ) );
        $icon = isset( $icons[ $status ] ) ? $icons[ $status ] : $icons['warning'];
        echo '<span class="dashicons dashicons-' . esc_attr( $icon[0] ) . '" style="color:' . esc_attr( $icon[1] ) . ';"></span>';
    }

    function render_gcaptcha_diagnostics() {
        ?>
        <hr>
        <h2 id="cl-gcaptcha-diagnostics"><?php echo esc_html__( 'Google reCAPTCHA diagnostics', 'clean-login' ); ?></h2>
        <?php
        if ( ! get_option( 'cl_gcaptcha' ) ) {
            echo '<p>' . esc_html__( 'Google reCAPTCHA is disabled, so the login and registration forms do not validate it.', 'clean-login' ) . '</p>';
            return;
        }

        $test = get_transient( 'cl_gcaptcha_test_' . get_current_user_id() );
        if ( $test !== false )
            delete_transient( 'cl_gcaptcha_test_' . get_current_user_id() );

        $log = get_option( 'cl_gcaptcha_log', array() );
        $log = is_array( $log ) ? $log : array();
        $debug = get_option( 'cl_gcaptcha_debug' );
        ?>
        <p><?php echo esc_html__( 'When Google reCAPTCHA is enabled every login and registration is rejected with "CAPTCHA is not valid" unless the reCAPTCHA box is shown and ticked. Use these tools to find out why it fails.', 'clean-login' ); ?></p>

        <h3><?php echo esc_html__( '1. Automatic checks', 'clean-login' ); ?></h3>
        <table class="widefat striped">
            <tbody>
                <?php foreach ( $this->get_gcaptcha_checks() as $check ) : ?>
                    <tr>
                        <td style="width:30px;"><?php $this->render_gcaptcha_status_icon( $check['status'] ); ?></td>
                        <td style="width:160px;"><strong><?php echo esc_html( $check['label'] ); ?></strong></td>
                        <td><?php echo esc_html( $check['text'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3><?php echo esc_html__( '2. Test the keys with Google', 'clean-login' ); ?></h3>
        <?php if ( $test !== false ) : ?>
            <?php if ( $test['success'] ) : ?>
                <div class="notice notice-success inline"><p>
                    <?php echo esc_html__( 'Google validated the answer: the Site Key and the Secret Key work.', 'clean-login' ); ?>
                    <?php if ( $test['hostname'] && $test['hostname'] !== wp_parse_url( home_url(), PHP_URL_HOST ) ) : ?>
                        <?php /* translators: %s: hostname returned by Google */ echo esc_html( sprintf( __( 'Hostname reported by Google: %s.', 'clean-login' ), $test['hostname'] ) ); ?>
                    <?php endif; ?>
                </p></div>
            <?php elseif ( $test['secret_only'] && empty( $test['http_error'] ) && ! in_array( 'invalid-input-secret', $test['error_codes'], true ) && ! in_array( 'missing-input-secret', $test['error_codes'], true ) ) : ?>
                <div class="notice notice-warning inline"><p><?php echo esc_html__( 'The server can reach Google and the Secret Key is accepted. The Site Key was not tested because the box was not ticked: tick it and run the test again.', 'clean-login' ); ?></p></div>
            <?php else : ?>
                <div class="notice notice-error inline">
                    <?php foreach ( $this->describe_gcaptcha_result( $test ) as $message ) : ?>
                        <p><?php echo esc_html( $message ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="clean_login_gcaptcha_test">
            <?php wp_nonce_field( 'clean_login_gcaptcha_test' ); ?>
            <div class="g-recaptcha" id="cl-gcaptcha-test-box" data-sitekey="<?php echo esc_attr( get_option( 'cl_gcaptcha_sitekey' ) ); ?>"></div>
            <div class="notice notice-error inline hidden" id="cl-gcaptcha-test-noscript"><p><?php echo esc_html__( 'The Google reCAPTCHA script did not load in this browser. Check the browser console and any ad or script blocker.', 'clean-login' ); ?></p></div>
            <p class="description">
                <?php /* translators: %s: site domain */ echo esc_html( sprintf( __( 'This is the same box the login form uses. If it shows "Invalid domain for site key", add %s to the key domains in the Google reCAPTCHA admin console. If it shows "Invalid key type" or no checkbox, the key is not a reCAPTCHA v2 "I\'m not a robot" checkbox key (v3 and Invisible keys are not supported).', 'clean-login' ), wp_parse_url( home_url(), PHP_URL_HOST ) ) ); ?>
            </p>
            <p><input type="submit" class="button" value="<?php echo esc_attr__( 'Tick the box and verify with Google', 'clean-login' ); ?>"></p>
        </form>

        <h3><?php echo esc_html__( '3. Failed validations log', 'clean-login' ); ?></h3>
        <p class="description"><?php echo esc_html__( 'While enabled, every login or registration rejected because of reCAPTCHA is stored with the reason Google gave (last 20 attempts). Enable it, reproduce the problem on the login page and come back here. Disable it when you are done.', 'clean-login' ); ?></p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="clean_login_gcaptcha_log">
            <?php wp_nonce_field( 'clean_login_gcaptcha_log' ); ?>
            <p>
                <?php if ( $debug ) : ?>
                    <strong style="color:#00a32a;"><?php echo esc_html__( 'Logging is enabled.', 'clean-login' ); ?></strong>
                    <button type="submit" name="do" value="disable" class="button"><?php echo esc_html__( 'Disable logging', 'clean-login' ); ?></button>
                <?php else : ?>
                    <button type="submit" name="do" value="enable" class="button"><?php echo esc_html__( 'Enable logging', 'clean-login' ); ?></button>
                <?php endif; ?>
                <?php if ( ! empty( $log ) ) : ?>
                    <button type="submit" name="do" value="clear" class="button"><?php echo esc_html__( 'Clear log', 'clean-login' ); ?></button>
                <?php endif; ?>
            </p>
        </form>

        <?php if ( empty( $log ) ) : ?>
            <p><?php echo esc_html__( 'No failed validations logged.', 'clean-login' ); ?></p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Date', 'clean-login' ); ?></th>
                        <th><?php echo esc_html__( 'Form', 'clean-login' ); ?></th>
                        <th><?php echo esc_html__( 'Reason', 'clean-login' ); ?></th>
                        <th><?php echo esc_html__( 'Browser', 'clean-login' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $log as $entry ) : ?>
                        <tr>
                            <td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $entry['time'] ) ); ?></td>
                            <td><?php echo esc_html( $entry['context'] ); ?></td>
                            <td>
                                <?php foreach ( $this->describe_gcaptcha_result( $entry ) as $message ) : ?>
                                    <div><?php echo esc_html( $message ); ?></div>
                                <?php endforeach; ?>
                                <?php if ( ! empty( $entry['error_codes'] ) ) : ?>
                                    <code><?php echo esc_html( implode( ', ', $entry['error_codes'] ) ); ?></code>
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo esc_html( $entry['user_agent'] ); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php
    }

    function menu()
    {
        add_options_page('Clean Login Options', 'Clean Login', apply_filters('clean_login_admin_capability', 'manage_options'), 'clean_login_menu', array($this, 'render'));
    }

    function maybe_remove_admin_bar()
    {
        $remove_adminbar_roles = get_option('cl_adminbar_roles');
        $remove_adminbar = get_option('cl_adminbar');

        if( $remove_adminbar_roles === false ){ // retro compatibility
            if ($remove_adminbar && !current_user_can(apply_filters('clean_login_admin_capability', 'manage_options')))
                show_admin_bar(false);
        }
        else{
            if( !$remove_adminbar )
                return;

            $user_roles = CleanLogin_Roles::get_current_user_roles();
            $remove_adminbar_roles = ( is_array( $remove_adminbar_roles ) ? $remove_adminbar_roles : array() );

            $result = array_intersect( $user_roles, $remove_adminbar_roles );

            if( count( $result ) > 0 )
                show_admin_bar(false);
        }
    }

    function maybe_block_dashboard_access()
    {
        $block_dashboard = get_option('cl_dashboard');

        if ($block_dashboard && !current_user_can(apply_filters('clean_login_admin_capability', 'manage_options')) && (!defined('DOING_AJAX') || !DOING_AJAX)) {
            wp_safe_redirect( home_url() );
            exit;
        }
    }

    static function is_wp_login_redirect_disabled_by_constant()
    {
        return defined( 'CLEAN_LOGIN_DISABLE_LOGIN_REDIRECT' ) && CLEAN_LOGIN_DISABLE_LOGIN_REDIRECT;
    }

    static function get_wp_login_redirect_url()
    {
        if ( ! get_option( 'cl_wp_login_redirect' ) || self::is_wp_login_redirect_disabled_by_constant() )
            return '';

        $login_id = get_option( 'cl_login_id' );
        if ( empty( $login_id ) || get_post_status( $login_id ) !== 'publish' )
            return '';

        return CleanLogin_Controller::get_login_url();
    }

    function maybe_redirect_wp_login()
    {
        $login_url = self::get_wp_login_redirect_url();
        if ( $login_url === '' )
            return;

        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) !== 'GET' )
            return;

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['interim-login'] ) || isset( $_GET['checkemail'] ) )
            return;

        $action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'login';
        $redirect_to = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ), '' ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ( $action === 'login' ) {
            $url = $login_url;
        } elseif ( $action === 'register' ) {
            $url = get_option( 'users_can_register' ) ? CleanLogin_Controller::get_register_url() : '';
        } elseif ( $action === 'lostpassword' || $action === 'retrievepassword' ) {
            $url = CleanLogin_Controller::get_restore_password_url();
        } else {
            return;
        }

        if ( empty( $url ) )
            return;

        if ( $action === 'login' && $redirect_to !== '' )
            $url = add_query_arg( 'url', rawurlencode( $redirect_to ), $url );

        wp_safe_redirect( $url );
        exit;
    }

    function filter_login_url( $login_url, $redirect )
    {
        if ( is_admin() )
            return $login_url;

        $url = self::get_wp_login_redirect_url();
        if ( $url === '' )
            return $login_url;

        return empty( $redirect ) ? $url : add_query_arg( 'url', rawurlencode( $redirect ), $url );
    }

    function filter_lostpassword_url( $lostpassword_url, $redirect )
    {
        if ( is_admin() || self::get_wp_login_redirect_url() === '' )
            return $lostpassword_url;

        $url = CleanLogin_Controller::get_restore_password_url();
        return empty( $url ) ? $lostpassword_url : $url;
    }

    function filter_register_url( $register_url )
    {
        if ( is_admin() || ! get_option( 'users_can_register' ) || self::get_wp_login_redirect_url() === '' )
            return $register_url;

        $url = CleanLogin_Controller::get_register_url();
        return empty( $url ) ? $register_url : $url;
    }

    function wp_login_redirect_notice()
    {
        if ( ! get_option( 'cl_wp_login_redirect' ) || ! current_user_can( apply_filters( 'clean_login_admin_capability', 'manage_options' ) ) )
            return;

        if ( self::is_wp_login_redirect_disabled_by_constant() )
            $message = __( 'Clean Login: the redirection of wp-login.php to the login page is disabled by the CLEAN_LOGIN_DISABLE_LOGIN_REDIRECT constant in wp-config.php. Remove it when the login page works again.', 'clean-login' );
        elseif ( self::get_wp_login_redirect_url() === '' )
            $message = __( 'Clean Login: the redirection of wp-login.php is enabled but there is no published page with the Login form block or the [clean-login] shortcode, so it is not being applied and wp-login.php keeps working as usual.', 'clean-login' );
        else
            return;

        echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
    }

    function get_callback_file( $callback )
    {
        try {
            if ( is_string( $callback ) && strpos( $callback, '::' ) !== false )
                $callback = explode( '::', $callback, 2 );

            if ( is_array( $callback ) && count( $callback ) === 2 )
                $reflection = new ReflectionMethod( $callback[0], $callback[1] );
            elseif ( is_string( $callback ) || $callback instanceof Closure )
                $reflection = new ReflectionFunction( $callback );
            elseif ( is_object( $callback ) && method_exists( $callback, '__invoke' ) )
                $reflection = new ReflectionMethod( $callback, '__invoke' );
            else
                return '';
        } catch ( ReflectionException $e ) {
            return '';
        }

        return (string) $reflection->getFileName();
    }

    function get_file_origin( $file )
    {
        $file = wp_normalize_path( $file );
        if ( $file === '' || strpos( $file, wp_normalize_path( CLEAN_LOGIN_PATH ) ) === 0 )
            return '';

        $plugin_dir = trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) );
        if ( strpos( $file, $plugin_dir ) === 0 ) {
            $slug = strtok( substr( $file, strlen( $plugin_dir ) ), '/' );

            if ( ! function_exists( 'get_plugins' ) )
                require_once ABSPATH . 'wp-admin/includes/plugin.php';

            foreach ( get_plugins() as $plugin_file => $data ) {
                if ( dirname( $plugin_file ) === $slug || $plugin_file === $slug )
                    return $data['Name'];
            }

            return $slug;
        }

        $mu_plugin_dir = trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) );
        if ( strpos( $file, $mu_plugin_dir ) === 0 )
            /* translators: %s: must-use plugin file name */
            return sprintf( __( 'must-use plugin %s', 'clean-login' ), strtok( substr( $file, strlen( $mu_plugin_dir ) ), '/' ) );

        $theme_root = trailingslashit( wp_normalize_path( get_theme_root() ) );
        if ( strpos( $file, $theme_root ) === 0 )
            /* translators: %s: theme folder name */
            return sprintf( __( 'theme %s', 'clean-login' ), strtok( substr( $file, strlen( $theme_root ) ), '/' ) );

        return '';
    }

    function get_wp_login_hooked_origins()
    {
        global $wp_filter;

        $hooks = array( 'login_init', 'login_form_login', 'login_form_register', 'login_form_lostpassword', 'auth_redirect', 'login_url', 'lostpassword_url', 'register_url', 'site_url', 'network_site_url' );
        $origins = array();

        foreach ( $hooks as $hook ) {
            if ( empty( $wp_filter[ $hook ] ) )
                continue;

            foreach ( $wp_filter[ $hook ]->callbacks as $callbacks ) {
                foreach ( $callbacks as $callback ) {
                    $origin = $this->get_file_origin( $this->get_callback_file( $callback['function'] ) );
                    if ( $origin !== '' )
                        $origins[ $origin ][ $hook ] = $hook;
                }
            }
        }

        return $origins;
    }

    function wp_login_loopback( $url )
    {
        $response = wp_remote_get( $url, array(
            'redirection' => 0,
            'timeout'     => 10,
            'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
            'cookies'     => array(),
        ) );

        if ( is_wp_error( $response ) )
            return array( 'error' => $response->get_error_message(), 'code' => 0, 'location' => '', 'body' => '' );

        return array(
            'error'    => '',
            'code'     => (int) wp_remote_retrieve_response_code( $response ),
            'location' => (string) wp_remote_retrieve_header( $response, 'location' ),
            'body'     => (string) wp_remote_retrieve_body( $response ),
        );
    }

    function is_url_of_page( $url, $page_url )
    {
        $page_path = untrailingslashit( strtok( $page_url, '?' ) );
        return $page_path !== '' && strpos( $url, $page_path ) === 0;
    }

    function get_wp_login_static_checks()
    {
        $checks = array();

        $login_id = get_option( 'cl_login_id' );
        $login_published = ! empty( $login_id ) && get_post_status( $login_id ) === 'publish';
        $checks[] = array(
            'label'  => __( 'Login page', 'clean-login' ),
            'status' => $login_published ? 'ok' : 'error',
            /* translators: %s: login page URL */
            'text'   => $login_published ? sprintf( __( 'Visitors will be sent to %s.', 'clean-login' ), CleanLogin_Controller::get_login_url() ) : __( 'There is no published page with the Login form block or the [clean-login] shortcode. Create it before enabling the redirection.', 'clean-login' ),
        );

        if ( self::is_wp_login_redirect_disabled_by_constant() ) {
            $checks[] = array(
                'label'  => 'wp-config.php',
                'status' => 'warning',
                'text'   => __( 'CLEAN_LOGIN_DISABLE_LOGIN_REDIRECT is defined, so the redirection is not applied even if it is enabled.', 'clean-login' ),
            );
        }

        $hiding_plugins = $this->get_active_plugins_by_slug( array(
            'wps-hide-login', 'rename-wp-login', 'wp-hide-security-enhancer', 'hide-my-wp', 'lockdown-wp-admin',
            'change-wp-admin-login', 'easy-hide-login', 'theme-my-login',
        ) );
        $checks[] = array(
            'label'  => __( 'Login URL plugins', 'clean-login' ),
            'status' => empty( $hiding_plugins ) ? 'ok' : 'error',
            /* translators: %s: comma separated list of plugin names */
            'text'   => empty( $hiding_plugins ) ? __( 'No plugin that hides, renames or replaces wp-login.php detected.', 'clean-login' ) : sprintf( __( 'These plugins hide, rename or replace wp-login.php and would clash with this redirection: %s. Deactivate them before enabling it.', 'clean-login' ), implode( ', ', $hiding_plugins ) ),
        );

        $optional_plugins = $this->get_active_plugins_by_slug( array(
            'better-wp-security', 'all-in-one-wp-security-and-firewall', 'defender-security', 'loginizer', 'perfmatters',
            'admin-site-enhancements', 'wp-cerber', 'wp-force-login', 'restricted-site-access',
        ) );
        if ( ! empty( $optional_plugins ) ) {
            $checks[] = array(
                'label'  => __( 'Security plugins', 'clean-login' ),
                'status' => 'warning',
                /* translators: %s: comma separated list of plugin names */
                'text'   => sprintf( __( 'These plugins have options to change the login URL, hide wp-admin or force visitors to log in: %s. Make sure those options are disabled.', 'clean-login' ), implode( ', ', $optional_plugins ) ),
            );
        }

        $origins = $this->get_wp_login_hooked_origins();
        $list = array();
        foreach ( $origins as $origin => $hooks )
            $list[] = $origin . ' (' . implode( ', ', $hooks ) . ')';

        $checks[] = array(
            'label'  => __( 'Login hooks', 'clean-login' ),
            'status' => empty( $list ) ? 'ok' : 'warning',
            /* translators: %s: list of plugins or themes with the hooks they use */
            'text'   => empty( $list ) ? __( 'No other plugin or theme uses the hooks of the login screen or of its URLs while loading the dashboard.', 'clean-login' ) : sprintf( __( 'These plugins or themes also use hooks of the login screen or of its URLs: %s. It is usually harmless, but if one of them hides or redirects wp-login.php, disable that feature.', 'clean-login' ), implode( '; ', $list ) ),
        );

        return $checks;
    }

    function get_wp_login_loopback_checks()
    {
        $checks = array();
        $active = self::get_wp_login_redirect_url() !== '';
        $login_page = CleanLogin_Controller::get_login_url();

        $result = $this->wp_login_loopback( site_url( 'wp-login.php', 'login' ) );
        if ( $result['error'] !== '' ) {
            $status = 'warning';
            /* translators: %s: connection error message */
            $text = sprintf( __( 'WordPress could not open wp-login.php by itself (%s). Open it in a private window to check it.', 'clean-login' ), $result['error'] );
        } elseif ( $result['code'] === 200 ) {
            $status = $active ? 'error' : 'ok';
            $text = $active ? __( 'wp-login.php is still shown to visitors. A cache of wp-login.php or another plugin is preventing the redirection.', 'clean-login' ) : __( 'wp-login.php is shown normally: no other plugin hides it or redirects it.', 'clean-login' );
        } elseif ( $result['code'] >= 300 && $result['code'] < 400 ) {
            $ours = $this->is_url_of_page( $result['location'], $login_page );
            $status = $ours ? 'ok' : 'error';
            /* translators: %s: URL wp-login.php redirects to */
            $text = $ours ? __( 'wp-login.php sends visitors to the Clean Login page.', 'clean-login' ) : sprintf( __( 'wp-login.php already sends visitors to %s, so another plugin or a server rule redirects it. Disable that redirection first.', 'clean-login' ), $result['location'] );
        } elseif ( in_array( $result['code'], array( 404, 410 ), true ) ) {
            $status = 'error';
            /* translators: %d: HTTP status code */
            $text = sprintf( __( 'wp-login.php answers with HTTP %d to visitors, so another plugin or a security rule hides it. Disable that feature first.', 'clean-login' ), $result['code'] );
        } else {
            $status = 'warning';
            /* translators: %d: HTTP status code */
            $text = sprintf( __( 'wp-login.php answers with HTTP %d to WordPress itself (the server may protect it with a password or block requests from the server itself). Open it in a private window to check it.', 'clean-login' ), $result['code'] );
        }
        $checks[] = array( 'label' => 'wp-login.php', 'status' => $status, 'text' => $text );

        $result = $this->wp_login_loopback( admin_url() );
        if ( $result['error'] !== '' ) {
            $status = 'warning';
            /* translators: %s: connection error message */
            $text = sprintf( __( 'WordPress could not open wp-admin by itself (%s). Open it in a private window to check it.', 'clean-login' ), $result['error'] );
        } elseif ( $result['code'] >= 300 && $result['code'] < 400 && ( strpos( $result['location'], 'wp-login.php' ) !== false || $this->is_url_of_page( $result['location'], $login_page ) ) ) {
            $status = 'ok';
            $text = __( 'wp-admin sends visitors to the login screen.', 'clean-login' );
        } elseif ( $result['code'] >= 300 && $result['code'] < 400 ) {
            $status = 'warning';
            /* translators: %s: URL wp-admin redirects to */
            $text = sprintf( __( 'wp-admin sends visitors to %s instead of the login screen, so another plugin or a server rule redirects it. Visitors opening wp-admin will not reach the Clean Login page.', 'clean-login' ), $result['location'] );
        } else {
            $status = 'warning';
            /* translators: %d: HTTP status code */
            $text = sprintf( __( 'wp-admin answers with HTTP %d to visitors, so another plugin or a security rule hides it. Visitors opening wp-admin will not reach the Clean Login page.', 'clean-login' ), $result['code'] );
        }
        $checks[] = array( 'label' => 'wp-admin', 'status' => $status, 'text' => $text );

        if ( $login_page !== '' ) {
            $result = $this->wp_login_loopback( $login_page );
            if ( $result['error'] !== '' ) {
                $status = 'warning';
                /* translators: %s: connection error message */
                $text = sprintf( __( 'WordPress could not open the login page by itself (%s). Open it in a private window to check it.', 'clean-login' ), $result['error'] );
            } elseif ( $result['code'] !== 200 ) {
                $status = 'error';
                /* translators: %d: HTTP status code */
                $text = sprintf( __( 'The login page answers with HTTP %d to visitors, so nobody could log in with it.', 'clean-login' ), $result['code'] );
            } elseif ( strpos( $result['body'], 'cleanlogin-form' ) === false ) {
                $status = 'warning';
                $text = __( 'The login page loads but the login form was not found in it (a cached copy, a page builder or a membership plugin may be hiding it). Check it in a private window.', 'clean-login' );
            } else {
                $status = 'ok';
                $text = __( 'The login page shows the login form to visitors.', 'clean-login' );
            }
            $checks[] = array( 'label' => __( 'Login form', 'clean-login' ), 'status' => $status, 'text' => $text );
        }

        return $checks;
    }

    function run_wp_login_checks()
    {
        $checks = array_merge( $this->get_wp_login_static_checks(), $this->get_wp_login_loopback_checks() );
        set_transient( 'cl_wp_login_check_' . get_current_user_id(), $checks, 10 * MINUTE_IN_SECONDS );

        return $checks;
    }

    function has_error_check( $checks )
    {
        foreach ( $checks as $check ) {
            if ( $check['status'] === 'error' )
                return true;
        }

        return false;
    }

    function wp_login_check_action()
    {
        if ( ! current_user_can( apply_filters( 'clean_login_admin_capability', 'manage_options' ) ) )
            wp_die( esc_html__( 'Admin area', 'clean-login' ) );

        check_admin_referer( 'clean_login_wp_login_check' );

        $this->run_wp_login_checks();

        wp_safe_redirect( admin_url( 'options-general.php?page=clean_login_menu#cl-wp-login-diagnostics' ) );
        exit;
    }

    function render_checks_table( $checks )
    {
        ?>
        <table class="widefat striped">
            <tbody>
                <?php foreach ( $checks as $check ) : ?>
                    <tr>
                        <td style="width:30px;"><?php $this->render_gcaptcha_status_icon( $check['status'] ); ?></td>
                        <td style="width:160px;"><strong><?php echo esc_html( $check['label'] ); ?></strong></td>
                        <td><?php echo esc_html( $check['text'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    function render_wp_login_diagnostics()
    {
        $checks = get_transient( 'cl_wp_login_check_' . get_current_user_id() );
        if ( $checks !== false )
            delete_transient( 'cl_wp_login_check_' . get_current_user_id() );
        ?>
        <hr>
        <h2 id="cl-wp-login-diagnostics"><?php echo esc_html__( 'Login page redirection checks', 'clean-login' ); ?></h2>
        <p><?php echo esc_html__( 'These checks look for plugins, themes or server rules that also hide or redirect wp-login.php and wp-admin. They run automatically when the redirection is enabled, and it is not enabled while any of them fails.', 'clean-login' ); ?></p>
        <?php
        if ( $checks !== false ) {
            $this->render_checks_table( $checks );
        } else {
            $this->render_checks_table( $this->get_wp_login_static_checks() );
            echo '<p class="description">' . esc_html__( 'Run all the checks to also open wp-login.php, wp-admin and the login page as a visitor would.', 'clean-login' ) . '</p>';
        }
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="clean_login_wp_login_check">
            <?php wp_nonce_field( 'clean_login_wp_login_check' ); ?>
            <p><input type="submit" class="button" value="<?php echo esc_attr__( 'Run all the checks', 'clean-login' ); ?>"></p>
        </form>
        <?php
    }

    function render_donation_box()
    {
?>
        <div class="card">
            <h3 class="title" id="like-donate-more" style="cursor: pointer;"><?php echo esc_html__('Do you like it?', 'clean-login'); ?> <span id="like-donate-arrow" class="dashicons dashicons-arrow-down"></span><span id="like-donate-smile" class="dashicons dashicons-smiley hidden"></span></h3>
            <div class="hidden" id="like-donate">
                <p>Hi there! We are <a href="https://twitter.com/fjcarazo" target="_blank" title="Javier Carazo">Javier Carazo</a> and <a href="https://twitter.com/ahornero" target="_blank" title="Alberto Hornero">Alberto Hornero</a> from <a href="http://codection.com">Codection</a>, developers of this plugin. We have been spending many hours to develop this plugin, we keep updating it and we always try do the best in the <a href="https://wordpress.org/support/plugin/clean-login">support forum</a>.</p>
                <p>If you like it, you can <strong>buy us a cup of coffee</strong> or whatever ;-)</p>
                <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                    <input type="hidden" name="cmd" value="_s-xclick">
                    <input type="hidden" name="hosted_button_id" value="HGAS22NVY7Q8N">
                    <input type="submit" name="submit" value="<?php echo esc_attr__( 'Donate via PayPal', 'clean-login' ); ?>">
                </form>
                <p>Sure! You can also <strong><a href="https://wordpress.org/support/view/plugin-reviews/clean-login?filter=5">rate our plugin</a></strong> and provide us your feedback. Thanks!</p>
            </div>
        </div>
    <?php
    }

    function render_used_shortcode_table()
    {
        $login_url = get_option('cl_login_url');
        $edit_url = get_option('cl_edit_url');
        $register_url = get_option('cl_register_url');
        $restore_url = get_option('cl_restore_url');
        $change_password_url = get_option('cl_change_password_url');
    ?>
        <h2><?php echo esc_html__('Clean Login status', 'clean-login'); ?></h2>

        <p><?php echo esc_html__('Below you can check the plugin status regarding the shortcodes usage and the pages/posts which contain  it.', 'clean-login'); ?></p>
        <p><?php echo esc_html__('Each shortcode is also available as a block in the "Clean Login" category of the block editor, and pages using the blocks are detected in the same way.', 'clean-login'); ?></p>

        <table class="widefat importers">
            <tbody>
                <tr class="alternate">
                    <td class="import-system row-title"><a>[clean-login]</a></td>
                    <?php if (!$login_url) : ?>
                        <td class="desc"><?php echo esc_html__('Currently not used', 'clean-login'); ?></td>
                    <?php else : ?>
                        <td class="desc"><?php /* translators: %s: page URL */ printf( wp_kses_post( __( 'Used <a href="%s">here</a>', 'clean-login' ) ), esc_url( $login_url ) ); ?></td>
                    <?php endif; ?>
                    <td class="desc"><?php echo esc_html__('This shortcode contains login form and login information.', 'clean-login'); ?></td>
                </tr>
                <tr>
                    <td class="import-system row-title"><a>[clean-login-edit]</a></td>
                    <?php if (!$edit_url) : ?>
                        <td class="desc"><?php echo esc_html__('Currently not used', 'clean-login'); ?></td>
                    <?php else : ?>
                        <td class="desc"><?php /* translators: %s: page URL */ printf( wp_kses_post( __( 'Used <a href="%s">here</a>', 'clean-login' ) ), esc_url( $edit_url ) ); ?></td>
                    <?php endif; ?>
                    <td class="desc"><?php echo esc_html__('This shortcode contains the profile editor. If you include in a page/post a link will appear on your login preview. You can hide email field using attribute show_email with value false.', 'clean-login'); ?></td>
                </tr>
                <?php if (get_option('users_can_register')) : ?>
                    <tr class="alternate">
                        <td class="import-system row-title"><a>[clean-login-register]</a></td>
                        <?php if (!$register_url) : ?>
                            <td class="desc"><?php echo esc_html__('Currently not used', 'clean-login'); ?></td>
                        <?php else : ?>
                            <td class="desc"><?php /* translators: %s: page URL */ printf( wp_kses_post( __( 'Used <a href="%s">here</a>', 'clean-login' ) ), esc_url( $register_url ) ); ?></td>
                        <?php endif; ?>
                        <td class="desc"><?php echo esc_html__('This shortcode contains the register form. If you include in a page/post a link will appear on your login form.', 'clean-login'); ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td class="import-system row-title"><a>[clean-login-restore]</a></td>
                    <?php if (!$restore_url) : ?>
                        <td class="desc"><?php echo esc_html__('Currently not used', 'clean-login'); ?></td>
                    <?php else : ?>
                        <td class="desc"><?php /* translators: %s: page URL */ printf( wp_kses_post( __( 'Used <a href="%s">here</a>', 'clean-login' ) ), esc_url( $restore_url ) ); ?></td>
                    <?php endif; ?>
                    <td class="desc"><?php echo esc_html__('This shortcode contains the restore (lost password?) form. If you include in a page/post a link will appear on your login form.', 'clean-login'); ?></td>
                </tr>
                <tr class="alternate">
                    <td class="import-system row-title"><a>[clean-login-change-password]</a></td>
                    <?php if (!$change_password_url) : ?>
                        <td class="desc"><?php echo esc_html__('Currently not used', 'clean-login'); ?></td>
                    <?php else : ?>
                        <td class="desc"><?php /* translators: %s: page URL */ printf( wp_kses_post( __( 'Used <a href="%s">here</a>', 'clean-login' ) ), esc_url( $change_password_url ) ); ?></td>
                    <?php endif; ?>
                    <td class="desc"><?php echo esc_html__('This shortcode shows a dedicated password change form (new password + confirm). Ideal as the destination after a password reset link. Includes a password strength meter.', 'clean-login'); ?></td>
                </tr>
            </tbody>
        </table>
    <?php
    }

    function maybe_update_options()
    {
        if ( empty( $_POST ) )
            return;

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'codection-security' ) )
            wp_die( 'Security check' );

        $post = wp_unslash( $_POST );

        update_option( 'cl_adminbar', isset( $post['adminbar'] ) );
        update_option( 'cl_adminbar_roles', isset( $post['adminbar_roles'] ) ? array_map( 'sanitize_text_field', (array) $post['adminbar_roles'] ) : array() );
        update_option( 'cl_dashboard', isset( $post['dashboard'] ) );
        $wp_login_redirect = isset( $post['wp_login_redirect'] );
        $wp_login_redirect_blocked = false;
        if ( $wp_login_redirect && ! get_option( 'cl_wp_login_redirect' ) ) {
            $wp_login_redirect_blocked = $this->has_error_check( $this->run_wp_login_checks() );
            $wp_login_redirect = ! $wp_login_redirect_blocked;
        }
        update_option( 'cl_wp_login_redirect', $wp_login_redirect );
        update_option( 'cl_antispam', isset( $post['antispam'] ) );
        update_option( 'cl_gcaptcha', isset( $post['gcaptcha'] ) );
        update_option( 'cl_gcaptcha_sitekey', isset( $post['gcaptcha_sitekey'] ) ? sanitize_text_field( $post['gcaptcha_sitekey'] ) : '' );
        update_option( 'cl_gcaptcha_secretkey', isset( $post['gcaptcha_secretkey'] ) ? sanitize_text_field( $post['gcaptcha_secretkey'] ) : '' );
        update_option( 'cl_standby', isset( $post['standby'] ) );
        update_option( 'cl_hideuser', isset( $post['hideuser'] ) );
        update_option( 'cl_passcomplex', isset( $post['passcomplex'] ) );
        update_option( 'cl_emailnotification', isset( $post['emailnotification'] ) );
        update_option( 'cl_emailnotificationcontent', isset( $post['emailnotificationcontent'] ) ? sanitize_textarea_field( $post['emailnotificationcontent'] ) : '' );
        update_option( 'cl_chooserole', isset( $post['chooserole'] ) );
        update_option( 'cl_newuserroles', isset( $post['newuserroles'] ) ? array_map( 'sanitize_text_field', (array) $post['newuserroles'] ) : array() );
        update_option( 'cl_termsconditions', isset( $post['termsconditions'] ) );
        update_option( 'cl_termsconditionsMSG', isset( $post['termsconditionsMSG'] ) ? sanitize_text_field( $post['termsconditionsMSG'] ) : '' );
        update_option( 'cl_termsconditionsURL', isset( $post['termsconditionsURL'] ) ? esc_url_raw( $post['termsconditionsURL'] ) : '' );
        update_option( 'cl_email_username', isset( $post['emailusername'] ) );
        update_option( 'cl_single_password', isset( $post['singlepassword'] ) );
        update_option( 'cl_automatic_login', isset( $post['automaticlogin'] ) );
        update_option( 'cl_url_redirect', isset( $post['automaticlogin'] ) && isset( $post['urlredirect'] ) ? esc_url_raw( $post['urlredirect'] ) : home_url() );
        update_option( 'cl_nameandsurname', isset( $post['nameandsurname'] ) );
        update_option( 'cl_emailvalidation', isset( $post['emailvalidation'] ) );
        update_option( 'cl_enable_hash_in_login_page', isset( $post['enable_hash_in_login_page'] ) );
        update_option( 'cl_login_redirect', isset( $post['loginredirect'] ) );
        update_option( 'cl_login_redirect_url', isset( $post['loginredirect'] ) && isset( $post['loginredirect_url'] ) ? esc_url_raw( $post['loginredirect_url'] ) : home_url() );
        update_option( 'cl_logout_redirect', isset( $post['logoutredirect'] ) );
        update_option( 'cl_logout_redirect_url', isset( $post['logoutredirect'] ) && isset( $post['logoutredirect_url'] ) ? esc_url_raw( $post['logoutredirect_url'] ) : home_url() );
        update_option( 'cl_register_redirect', isset( $post['registerredirect'] ) );
        update_option( 'cl_register_redirect_url', isset( $post['registerredirect'] ) && isset( $post['registerredirect_url'] ) ? esc_url_raw( $post['registerredirect_url'] ) : home_url() );
        update_option( 'cl_change_password_redirect', isset( $post['changepasswordredirect'] ) );
        update_option( 'cl_change_password_redirect_url', isset( $post['changepasswordredirect'] ) && isset( $post['changepasswordredirect_url'] ) ? esc_url_raw( $post['changepasswordredirect_url'] ) : home_url() );
        update_option( 'cl_restore_redirect', isset( $post['restoreredirect'] ) );
        update_option( 'cl_restore_redirect_url', isset( $post['restoreredirect'] ) && isset( $post['restoreredirect_url'] ) ? esc_url_raw( $post['restoreredirect_url'] ) : home_url() );
        update_option( 'cl_lost_password_text', isset( $post['lost_password_text'] ) ? sanitize_text_field( $post['lost_password_text'] ) : '' );

        echo '<div class="updated"><p><strong>' . esc_html__( 'Settings saved.', 'clean-login' ) . '</strong></p></div>';

        if ( $wp_login_redirect_blocked )
            echo '<div class="error"><p>' . wp_kses_post( __( 'The login page redirection has <strong>not</strong> been enabled because some checks failed. See the <a href="#cl-wp-login-diagnostics">login page redirection checks</a> below.', 'clean-login' ) ) . '</p></div>';
    }

    function render()
    {
        if (!current_user_can(apply_filters('clean_login_admin_capability', 'manage_options'))) {
            wp_die(esc_html__('Admin area', 'clean-login'));
        }

        $roles_helper = new CleanLogin_Roles();
        $this->maybe_update_options();
    ?>
        <div class="wrap">
            <?php $this->render_donation_box(); ?>
            <br />
            <?php $this->render_used_shortcode_table(); ?>
            <h2><?php echo esc_html__('Options', 'clean-login'); ?></h2>

            <?php
            $adminbar = get_option('cl_adminbar', true);
            $adminbar_roles = is_array( get_option('cl_adminbar_roles', true) ) ? get_option('cl_adminbar_roles', true) : array();
            $dashboard = get_option('cl_dashboard');
            $wp_login_redirect = get_option('cl_wp_login_redirect');
            $antispam = get_option('cl_antispam');
            $gcaptcha = get_option('cl_gcaptcha');
            $gcaptcha_sitekey = get_option('cl_gcaptcha_sitekey');
            $gcaptcha_secretkey = get_option('cl_gcaptcha_secretkey');
            $standby = get_option('cl_standby');
            $hideuser = get_option('cl_hideuser');
            $passcomplex = get_option('cl_passcomplex');
            $emailnotification = get_option('cl_emailnotification');
            $emailnotificationcontent = get_option('cl_emailnotificationcontent');
            $chooserole = get_option('cl_chooserole');
            $termsconditions = get_option('cl_termsconditions');
            $termsconditionsMSG = get_option('cl_termsconditionsMSG');
            $termsconditionsURL = get_option('cl_termsconditionsURL');
            $emailusername = get_option('cl_email_username');
            $singlepassword = get_option('cl_single_password');
            $automaticlogin = get_option('cl_automatic_login', false) ? true : false;
            $urlredirect = get_option('cl_url_redirect', false) ? esc_url(get_option('cl_url_redirect')) : home_url();
            $nameandsurname = get_option('cl_nameandsurname', false) ? true : false;
            $emailvalidation = get_option('cl_emailvalidation', false) ? true : false;
            $enable_hash_in_login_page = get_option('cl_enable_hash_in_login_page', false) ? true : false;
            $loginredirect = get_option('cl_login_redirect', false) ? true : false;
            $loginredirect_url = get_option('cl_login_redirect_url', false) ? esc_url(get_option('cl_login_redirect_url')) : home_url();
            $logoutredirect = get_option('cl_logout_redirect', false) ? true : false;
            $logoutredirect_url = get_option('cl_logout_redirect_url', false) ? esc_url(get_option('cl_logout_redirect_url')) : home_url();
            $registerredirect = get_option('cl_register_redirect', false) ? true : false;
            $registerredirect_url = get_option('cl_register_redirect_url', false) ? esc_url(get_option('cl_register_redirect_url')) : home_url();
            $changepasswordredirect = get_option('cl_change_password_redirect', false) ? true : false;
            $changepasswordredirect_url = get_option('cl_change_password_redirect_url', false) ? esc_url(get_option('cl_change_password_redirect_url')) : home_url();
            $restoreredirect = get_option('cl_restore_redirect', false) ? true : false;
            $restoreredirect_url = get_option('cl_restore_redirect_url', false) ? esc_url(get_option('cl_restore_redirect_url')) : home_url();
            $lost_password_text = get_option('cl_lost_password_text', '');
            ?>
            <form id="form1" name="form1" method="post" action="">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Admin bar', 'clean-login'); ?></th>
                            <td>
                                <label><input name="adminbar" type="checkbox" id="adminbar" <?php checked($adminbar); ?>><?php echo esc_html__('Hide admin bar for some roles?', 'clean-login'); ?></label>
                                <div id="adminbar_roles">
                                    <p class="description"><?php echo esc_html__('Choose which will roles will have the admin bar hidden', 'clean-login'); ?></p>
                                    <label>
                                        <select name="adminbar_roles[]" multiple="multiple">
                                            <?php foreach ($roles_helper->get_non_admin_roles() as $slug => $name) : ?>
                                                <option value="<?php echo esc_attr( $slug ); ?>" <?php if( in_array( $slug, $adminbar_roles ) ) echo 'selected="selected"'; ?>><?php echo esc_html( $name ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Dashboard access', 'clean-login'); ?></th>
                            <td>
                                <label><input name="dashboard" type="checkbox" id="dashboard" <?php checked($dashboard); ?>><?php echo esc_html__('Disable dashboard access for non-admin users?', 'clean-login'); ?></label>
                                <p class="description"><?php echo wp_kses_post( __( 'Please note that you can only log in through <strong>wp-login.php</strong> and this plugin. <strong>wp-admin</strong> permalink will be inaccessible.', 'clean-login' ) ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Login page redirection', 'clean-login'); ?></th>
                            <td>
                                <label><input name="wp_login_redirect" type="checkbox" id="wp_login_redirect" <?php checked($wp_login_redirect); ?>><?php echo esc_html__('Send visitors who open wp-login.php or wp-admin without being logged in to the Clean Login page?', 'clean-login'); ?></label>
                                <p class="description"><?php echo wp_kses_post( __( 'The register and lost password links of wp-login.php also go to the [clean-login-register] and [clean-login-restore] pages when they exist (registration only when "Anyone can register" is enabled; in a multisite network the network registration of wp-signup.php is kept otherwise). Logout, password reset links, password protected posts and the "session expired" box of the dashboard keep using <strong>wp-login.php</strong>, and forms sent to it keep working.', 'clean-login' ) ); ?></p>
                                <p class="description"><?php echo wp_kses_post( __( '<strong>Before logging out</strong>, open the login page in a private window and check that you can log in with it. If you get locked out, add <code>define( \'CLEAN_LOGIN_DISABLE_LOGIN_REDIRECT\', true );</code> to wp-config.php to disable this redirection, or deactivate the plugin via FTP.', 'clean-login' ) ); ?></p>
                                <p class="description"><?php echo wp_kses_post( __( 'When you enable it, the <a href="#cl-wp-login-diagnostics">login page redirection checks</a> are run first, and it is not enabled if another plugin or a server rule already hides or redirects wp-login.php.', 'clean-login' ) ); ?></p>
                                <?php if ( $wp_login_redirect && self::get_wp_login_redirect_url() === '' && ! self::is_wp_login_redirect_disabled_by_constant() ) : ?>
                                    <p class="description" style="color:#b32d2e;"><?php echo esc_html__( 'There is no published page with the Login form block or the [clean-login] shortcode, so the redirection is not applied.', 'clean-login' ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Antispam protection', 'clean-login'); ?><p class="description">[Letters captcha]</p>
                            </th>
                            <td>
                                <label><input name="antispam" <?php if ($gcaptcha) echo 'disabled'; ?> type="checkbox" id="antispam" <?php checked($antispam); ?>><?php echo esc_html__('Enable captcha?', 'clean-login'); ?></label>
                                <p class="description"><?php echo esc_html__('Honeypot antispam detection is enabled by default.', 'clean-login'); ?></p>
                                <p class="description"><?php echo esc_html__('For captcha usage the PHP-GD library needs to be enabled in your server/hosting.', 'clean-login'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Antispam protection', 'clean-login'); ?><p class="description">[Google checkbox captcha]</p>
                            </th>
                            <td>
                                <label><input name="gcaptcha" <?php if ($antispam) echo 'disabled'; ?> type="checkbox" id="gcaptcha" <?php if ($gcaptcha) echo 'checked="checked"'; ?>><?php echo esc_html__('Enable Google reCaptcha?', 'clean-login'); ?></label>
                                <div style="color:red; display:none;" id="gcaptcha_error"><?php echo esc_html__('Google reCaptcha site key and secret key must not be empty', 'clean-login'); ?></div>
                                <div id="gcaptcha_sitekey-label" <?php if (!$gcaptcha) echo 'style="display:none;"'; ?>>
                                    <p class="description"><?php echo esc_html__('Google reCaptcha Site Key', 'clean-login'); ?></p>
                                    <label><input class="regular-text" value="<?php echo esc_attr( $gcaptcha_sitekey ); ?>" name="gcaptcha_sitekey" type="text" id="gcaptcha_sitekey"></label>
                                </div>
                                <div id="gcaptcha_secretkey-label" <?php if (!$gcaptcha) echo 'style="display:none;"'; ?>>
                                    <p class="description"><?php echo esc_html__('Google reCaptcha Secret Key', 'clean-login'); ?></p>
                                    <label><input class="regular-text" value="<?php echo esc_attr( $gcaptcha_secretkey ); ?>" name="gcaptcha_secretkey" type="text" id="gcaptcha_secretkey"></label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('User role', 'clean-login'); ?></th>
                            <td>
                                <label><input name="standby" type="checkbox" id="standby" <?php checked($standby); ?>><?php echo esc_html__('Enable Standby role?', 'clean-login'); ?></label>
                                <p class="description"><?php echo esc_html__('Standby role disables all the capabilities for new users, until the administrator changes. It usefull for site with restricted components.', 'clean-login'); ?></p>
                                <br>
                                <label><input name="chooserole" type="checkbox" id="chooserole" <?php checked($chooserole); ?>><?php echo esc_html__('Choose the role(s) in the registration form?', 'clean-login'); ?></label>
                                <p class="description"><?php echo esc_html__('This feature allows you to choose the role from the frontend, with the selected roles you want to show. You can also define an standard predefined role through a shortcode parameter, e.g. [clean-login-register role="contributor"]. Anyway, you need to choose only the role(s) you want to accept to avoid security/infiltration issues.', 'clean-login'); ?></p>
                                <p>
                                    <select name="newuserroles[]" id="newuserroles" multiple size="5"><?php wp_dropdown_roles(); ?></select>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Hide username', 'clean-login'); ?></th>
                            <td>
                                <label><input name="hideuser" type="checkbox" id="hideuser" <?php checked($hideuser); ?>><?php echo esc_html__('Hide username?', 'clean-login'); ?></label>
                                <p class="description"><?php echo esc_html__('Hide username from the preview form.', 'clean-login'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Password complexity', 'clean-login'); ?></th>
                            <td>
                                <label><input name="passcomplex" type="checkbox" id="passcomplex" <?php checked($passcomplex); ?>><?php echo esc_html__('Enable password complexity?', 'clean-login'); ?></label>
                                <p class="description"><?php echo esc_html__('Passwords must be eight characters including one upper/lowercase letter, one special/symbol character and alphanumeric characters. Passwords should not contain the user\'s username, email, or first/last name.', 'clean-login'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Email notification', 'clean-login'); ?></th>
                            <td>
                                <label><input name="emailnotification" type="checkbox" id="emailnotification" <?php checked($emailnotification); ?>><?php echo esc_html__('Enable email notification for new registered users?', 'clean-login'); ?></label>
                                <p><textarea name="emailnotificationcontent" id="emailnotificationcontent" placeholder="<?php echo esc_attr__('Please use HMTL tags for all formatting. And also you can use:', 'clean-login') . ' {username} {password} {email}'; ?>" rows="8" cols="50" class="large-text code"><?php echo esc_textarea( $emailnotificationcontent ); ?></textarea></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Terms and conditions', 'clean-login'); ?></th>
                            <td>
                                <label><input name="termsconditions" type="checkbox" id="termsconditions" <?php if ($termsconditions) echo 'checked="checked"'; ?>><?php echo esc_html__('Accept terms / conditions in the registration form?', 'clean-login'); ?></label>
                                <p><input name="termsconditionsMSG" type="text" id="termsconditionsMSG" value="<?php echo esc_attr( $termsconditionsMSG ); ?>" placeholder="<?php echo esc_attr__('Terms and conditions message', 'clean-login'); ?>" class="regular-text"></p>
                                <p><input name="termsconditionsURL" type="url" id="termsconditionsURL" value="<?php echo esc_url( $termsconditionsURL ); ?>" placeholder="<?php echo esc_attr__('Target URL', 'clean-login'); ?>" class="regular-text"></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Use Email as Username', 'clean-login'); ?></th>
                            <td>
                                <label><input name="emailusername" type="checkbox" id="emailusername" <?php checked($emailusername); ?>><?php echo esc_html__('Allow user to use email as username?', 'clean-login'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Single Password', 'clean-login'); ?></th>
                            <td>
                                <label><input name="singlepassword" type="checkbox" id="singlepassword" <?php checked($singlepassword); ?>><?php echo esc_html__('Only ask for password once on registration form?', 'clean-login'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Registration', 'clean-login'); ?></th>
                            <td>
                                <label><input name="automaticlogin" type="checkbox" id="automaticlogin" <?php if ($automaticlogin != '') echo 'checked="checked"'; ?>><?php echo esc_html__('Automatically Login after registration?', 'clean-login'); ?></label>
                                <div id="urlredirect">
                                    <p class="description"><?php echo esc_html__('URL after registration (if blank then homepage)', 'clean-login'); ?></p>
                                    <label><input class="regular-text" type="text" name="urlredirect" value="<?php echo esc_url( $urlredirect ); ?>"></label>
                                </div>
                                <br>
                                <label><input name="nameandsurname" type="checkbox" id="nameandsurname" <?php if ($nameandsurname != '') echo 'checked="checked"'; ?>><?php echo esc_html__('Add name and surname?', 'clean-login'); ?></label>
                                <br>
                                <label><input name="emailvalidation" type="checkbox" id="emailvalidation" <?php if ($emailvalidation != '') echo 'checked="checked"'; ?>><?php echo esc_html__('Validate user registration through an email?', 'clean-login'); ?></label>
                                <p class="description"><?php echo esc_html__('This feature cannot be used with the automatic login after registration', 'clean-login'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Login', 'clean-login'); ?></th>
                            <td>
                                <label><input name="enable_hash_in_login_page" type="checkbox" id="enable_hash_in_login_page" <?php checked($enable_hash_in_login_page); ?>><?php echo esc_html__('Enable timestamp GET parameter in login page to avoid problems with page cache', 'clean-login'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Lost password link text', 'clean-login'); ?></th>
                            <td>
                                <input name="lost_password_text" type="text" id="lost_password_text" value="<?php echo esc_attr( $lost_password_text ); ?>" placeholder="<?php echo esc_attr__( 'Lost password?', 'clean-login' ); ?>" class="regular-text">
                                <p class="description"><?php echo esc_html__('Custom label for the "Lost password?" link on the login form. Leave blank to use the default.', 'clean-login'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__('Redirections', 'clean-login'); ?></th>
                            <td>
                                <label><input name="loginredirect" type="checkbox" id="loginredirect" <?php if ($loginredirect != '') echo 'checked="checked"'; ?>><?php echo esc_html__('Redirect after log in?', 'clean-login'); ?></label>
                                <div id="loginredirect_url">
                                    <p class="description"><?php echo esc_html__('URL after login (if blank then homepage)', 'clean-login'); ?></p>
                                    <label><input class="regular-text" type="text" name="loginredirect_url" value="<?php echo esc_url( $loginredirect_url ); ?>"></label>
                                </div>
                                <br>
                                <label><input name="logoutredirect" type="checkbox" id="logoutredirect" <?php if ($logoutredirect != '') echo 'checked="checked"'; ?>><?php echo esc_html__('Redirect after log out?', 'clean-login'); ?></label>
                                <div id="logoutredirect_url">
                                    <p class="description"><?php echo esc_html__('URL after logout (if blank then homepage)', 'clean-login'); ?></p>
                                    <label><input class="regular-text" type="text" name="logoutredirect_url" value="<?php echo esc_url( $logoutredirect_url ); ?>"></label>
                                </div>
                                <br>
                                <label><input name="registerredirect" type="checkbox" id="registerredirect" <?php if ($registerredirect != '') echo 'checked="checked"'; ?>><?php echo esc_html__('Redirect after register?', 'clean-login'); ?></label>
                                <div id="registerredirect_url">
                                    <p class="description"><?php echo esc_html__('URL after redirect (if blank then homepage)', 'clean-login'); ?></p>
                                    <label><input class="regular-text" type="text" name="registerredirect_url" value="<?php echo esc_url( $registerredirect_url ); ?>"></label>
                                </div>
                                <br>
                                <label><input name="changepasswordredirect" type="checkbox" id="changepasswordredirect" <?php if ($changepasswordredirect != '') echo 'checked="checked"'; ?>><?php echo esc_html__('Redirect after password change?', 'clean-login'); ?></label>
                                <div id="changepasswordredirect_url">
                                    <p class="description"><?php echo esc_html__('URL after the password has been changed (if blank then homepage)', 'clean-login'); ?></p>
                                    <label><input class="regular-text" type="text" name="changepasswordredirect_url" value="<?php echo esc_url( $changepasswordredirect_url ); ?>"></label>
                                </div>
                                <br>
                                <label><input name="restoreredirect" type="checkbox" id="restoreredirect" <?php if ($restoreredirect != '') echo 'checked="checked"'; ?>><?php echo esc_html__('Redirect after restore password request?', 'clean-login'); ?></label>
                                <div id="restoreredirect_url">
                                    <p class="description"><?php echo esc_html__('URL after the restore link has been sent (if blank then homepage)', 'clean-login'); ?></p>
                                    <label><input class="regular-text" type="text" name="restoreredirect_url" value="<?php echo esc_url( $restoreredirect_url ); ?>"></label>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php wp_nonce_field('codection-security'); ?>

                <p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php echo esc_attr__('Save Changes', 'clean-login'); ?>" /></p>
            </form>

            <?php $this->render_wp_login_diagnostics(); ?>

            <?php $this->render_gcaptcha_diagnostics(); ?>

        </div>
<?php
    }
}
