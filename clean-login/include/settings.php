<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class CleanLogin_Settings
{
    function load()
    {
        add_action('after_setup_theme', array($this, 'maybe_remove_admin_bar'));
        add_action('admin_init', array($this, 'maybe_block_dashboard_access'), 1);
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
    }

    function enqueue( string $hook ) {
        if( $hook !== 'settings_page_clean_login_menu' )
            return;
        $newuserroles = get_option( 'cl_newuserroles' );
        wp_enqueue_script( 'clean-login-admin', plugin_dir_url( dirname( __FILE__ ) ) . 'content/js/clean-login-admin.js', array( 'jquery' ), '1.16', true );
        wp_localize_script( 'clean-login-admin', 'cleanLoginAdmin', array( 'newuserroles' => $newuserroles ? $newuserroles : array() ) );
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
        update_option( 'cl_lost_password_text', isset( $post['lost_password_text'] ) ? sanitize_text_field( $post['lost_password_text'] ) : '' );

        echo '<div class="updated"><p><strong>' . esc_html__( 'Settings saved.', 'clean-login' ) . '</strong></p></div>';
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
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php wp_nonce_field('codection-security'); ?>

                <p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php echo esc_attr__('Save Changes', 'clean-login'); ?>" /></p>
            </form>

        </div>
<?php
    }
}
