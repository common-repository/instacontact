<?php

class ICAPI_Settings
{
    const PAGE_SLUG = 'instacontact-settings';
    const OPTION_GROUP = 'instacontact';
    const SECTION_ID = 'credentials';

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        // self::$instance = $this;
        $this->base     = ICAPI::get_instance();

        if ( is_admin() ) {
            add_action( 'wp_ajax_instacontact-connect', array( $this, 'connect' ) );
            add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
            add_action( 'admin_init', array( $this, 'page_init' ) );
        }
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        $this->hook = add_menu_page(
            'InstaContact Settings', 
            'InstaContact',
            'manage_options',
            self::PAGE_SLUG, 
            array( $this, 'create_admin_page' ),
            'none',
            577
        );

        // Load global icon font styles.
        add_action( 'admin_head', array( $this, 'icon' ) );

        // Load settings page assets.
        if ( $this->hook ) {
          add_action( 'load-' . $this->hook, array( $this, 'assets' ) );
        }
    }

    public function hasConnected() {
        $option     = $this->base->get_option();
        // if ($option['api']['api_key'] && $option['api']['email'])
        if ($option['api']['universal_snippet'])
            return true;
        return false;
    }

    public function connect() {
        global $wpdb;
        $option     = $this->base->get_option();

        $data       = $_POST;
        $api_key    = isset( $data['api_key'] ) ? $data['api_key'] : false;
        $email      = isset( $data['email'] ) ? $data['email'] : false;

        if ( $api_key && $email ) {
            // Verify this new API Key works
            $api = new ICAPI_Api( 'account', array( 'email' => $email, 'api_key' => $api_key ), 'GET' );
            $ret = $api->request();

            if ( is_wp_error( $ret ) ) {
                $err = $ret->get_error_message();
                echo json_encode(array(
                    'status' => 'error',
                    'message' => 'Invalid Email or API Key.'
                ));
            } else {
                // print_r($ret);
                $option['api']['email'] = $email;
                $option['api']['api_key']  = $api_key;
                $option['api']['universal_snippet']  = $ret->universal_snippet;

                // Remove any error messages.
                $option['is_invalid']  = false;
                $option['is_expired']  = false;
                $option['is_disabled'] = false;

                // Save the option.
                update_option( 'instacontact', $option );

                echo json_encode(array(
                    'status' => 'success'
                ));
            }
        } else {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Missing Email or API Key'
            ));
        }

        wp_die();
    }

    public function icon()
    {
        ?>
        <style type="text/css">
            #toplevel_page_instacontact-settings .dashicons-before {
                padding-top: 0px;
                padding-bottom: 0px;
            }
            #toplevel_page_instacontact-settings .dashicons-before,
            #toplevel_page_instacontact-settings .dashicons-before:before,
            #toplevel_page_instacontact-welcome .dashicons-before,
            #toplevel_page_instacontact-welcome .dashicons-before:before{
                speak: none;
                font-style: normal;
                font-weight: normal;
                font-variant: normal;
                text-transform: none;line-height: 1;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;
            }
            #toplevel_page_instacontact-settings .dashicons-before:before,
            #toplevel_page_instacontact-welcome .dashicons-before:before{
                content: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2MSA1MCIgZmlsbD0iI2ZmZiI+PHJlY3QgY2xhc3M9ImNscy0xIiB4PSIxNS41MiIgeT0iMTMuNzgiIHdpZHRoPSIzLjI4IiBoZWlnaHQ9IjE5LjMiIHJ4PSIxLjYxIiByeT0iMS42MSIvPjxyZWN0IGNsYXNzPSJjbHMtMSIgeD0iMTAuNzIiIHk9IjE2LjM3IiB3aWR0aD0iMy4yOCIgaGVpZ2h0PSIxNC44MyIgcng9IjEuNjEiIHJ5PSIxLjYxIi8+PHBhdGggY2xhc3M9ImNscy0xIiBkPSJNNDYsMTBIMjQuODFhNC4zNCw0LjM0LDAsMCwwLTQuMzQsNC4zNFYzMS45M2E0LjM0LDQuMzQsMCwwLDAsNC4zNCw0LjM0aC42MUwyOS4xMSw0MGwzLjc5LTMuNzNINDZhNC4zMyw0LjMzLDAsMCwwLDQuMzMtNC4zNFYxNC4zNEE0LjMzLDQuMzMsMCwwLDAsNDYsMTBabS0xLjUsMjAuMTNhNS44Nyw1Ljg3LDAsMCwxLTEuMzEsMS4yOCwzLjc3LDMuNzcsMCwwLDEtMS45LjcxLDUuMjQsNS4yNCwwLDAsMS0yLjE2LS4yNEExMy40OSwxMy40OSwwLDAsMSwzNywzMC45M2MtLjU3LS4zNi0xLjM4LS44OC0xLjgzLTEuMjRzLTEuNDEtMS4xMy0xLjg1LTEuNTMtMS43OS0xLjctMi0xLjkxYTIwLDIwLDAsMCwxLTEuNTctMS42OWMtMS0xLjE4LTEuNS0xLjgxLTEuNzQtMi4xNnMtLjctMS0xLTEuNDZhMTAuNTIsMTAuNTIsMCwwLDEtLjctMS40Myw1LjExLDUuMTEsMCwwLDEtLjE0LTQsNC40Nyw0LjQ3LDAsMCwxLDIuNDctMi4zOCwxLjA5LDEuMDksMCwwLDEsMS4zOS4zNWwyLjQ4LDQuMjRhMS40NCwxLjQ0LDAsMCwxLC4yLjY1LDEuNjUsMS42NSwwLDAsMS0uMzYuODlsLS43NiwxLjI4YTEuMjYsMS4yNiwwLDAsMC0uMjEuNjMsMS4zMSwxLjMxLDAsMCwwLC4zLjdjLjE5LjI0LDEuMTQsMS40MSwxLjU4LDEuODZhMjcuNDgsMjcuNDgsMCwwLDAsMywyLjY1Ljg1Ljg1LDAsMCwwLC42Mi4xNSwxLjkzLDEuOTMsMCwwLDAsLjc5LS4zMmwuODItLjVhMi43MSwyLjcxLDAsMCwxLDEtLjQ5LDEuMTgsMS4xOCwwLDAsMSwuNzYuMjJjLjMuMTksMS42NywxLDIsMS4xOHMyLDEuMTQsMi4xMywxLjI0YTEsMSwwLDAsMSwuNTMuODRBMi41NCwyLjU0LDAsMCwxLDQ0LjQ1LDMwLjEzWiIvPjwvc3ZnPg==);
            }
        </style>
        <?php
    }

    public function assets() {
        add_action( 'admin_enqueue_scripts', array( $this, 'styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
    // add_filter( 'admin_footer_text', array( $this, 'footer' ) );
    // add_action( 'in_admin_header', array( $this, 'output_plugin_screen_banner') );
    // add_action( 'admin_enqueue_scripts', array( $this, 'fix_plugin_js_conflicts'), 100 );
    }

    /**
    * Register and enqueue settings page specific CSS.
    *
    * @since 1.0.0
    */
    public function styles() {
        wp_register_style( $this->base->plugin_slug . '-settings', plugins_url( '/assets/css/settings.css', ICAPI_FILE ), array(), $this->base->version );
        wp_enqueue_style( $this->base->plugin_slug . '-settings' );

        // Run a hook to load in custom styles.
        // do_action( 'optin_monster_api_admin_styles', $this->view );

    }

    /**
     * Register and enqueue settings page specific JS.
     *
     * @since 1.0.0
     */
    public function scripts() {
        wp_register_script( $this->base->plugin_slug . '-settings', plugins_url( '/assets/js/settings.js', ICAPI_FILE ), array( 'jquery' ), $this->base->version, true );
        wp_enqueue_script( $this->base->plugin_slug . '-settings' );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'instacontact' );
        $hasConnected = $this->hasConnected();
        ?>
        <div class="instacontact-wrap">
            <div class="inner-container">
                <h1>Welcome to InstaContact</h1>
                <div class="error" id="error" hidden>
                </div>
                <div class="updated" id="updated" hidden>
                    <p>Your InstaContact account has been linked successfully!</p>
                </div>
                <div class="well" id="instacontact-welcome" <?php if ($hasConnected) { echo 'hidden'; } ?>>
                    <p>
                        Please connect to or create an InstaContact account to start using InstaContact. This will enable you to start turning website visitors into calls & customers.
                    </p>
                    <div class="actions">
                        <a href="<?php echo $this->get_link(); ?>" target="_blank" class="btn btn-primary">Signup For Free Now</a>
                        <span class="or">or</span>
                        <a href="javascript:;" class="btn btn-secondary" onclick="document.querySelector('#instacontact-form').hidden = false; document.querySelector('#instacontact-welcome').hidden = true;">Connect Your Account</a>
                    </div>
                </div>
                <div class="well" id="instacontact-connected" <?php if (!$hasConnected) { echo 'hidden'; } ?>>
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid" width="72" height="72" viewBox="0 0 48 48">
                      <defs>
                        <style>
                          .cls-1 {
                            fill: green;
                            fill-rule: evenodd;
                          }
                        </style>
                      </defs>
                      <path d="M24.000,47.999 C10.767,47.999 -0.000,37.233 -0.000,24.000 C-0.000,10.766 10.767,-0.000 24.000,-0.000 C37.233,-0.000 48.000,10.766 48.000,24.000 C48.000,37.233 37.233,47.999 24.000,47.999 ZM24.000,2.000 C11.869,2.000 2.000,11.868 2.000,24.000 C2.000,36.131 11.869,45.999 24.000,45.999 C36.131,45.999 46.000,36.131 46.000,24.000 C46.000,11.868 36.131,2.000 24.000,2.000 ZM21.861,32.680 C21.677,32.879 21.421,32.994 21.151,33.000 C21.144,33.000 21.136,33.000 21.129,33.000 C20.866,33.000 20.614,32.896 20.427,32.712 L11.298,23.711 C10.905,23.324 10.900,22.691 11.288,22.297 C11.675,21.904 12.309,21.900 12.702,22.288 L21.098,30.565 L35.268,15.319 C35.643,14.913 36.277,14.892 36.681,15.267 C37.085,15.643 37.108,16.276 36.732,16.681 L21.861,32.680 Z" class="cls-1"/>
                    </svg>
                    <h2>Connected!</h2>
                    <p>
                        You are all setup. You can manage your settings from your InstaContact account.
                        Your campaigns will automatically show up on your site.
                    </p>
                    <div class="actions">
                        <a href="<?php echo esc_url_raw( admin_url( 'admin.php?page=' . ICAPI_Campaigns::PAGE_SLUG ) ); ?>" class="btn btn-primary">Manage</a>
                        <span class="or">or</span>
                        <a href="https://support.instacontact.io/" target="_blank" class="btn btn-secondary">Need Help?</a>
                    </div>
                    <p class="text-small">
                        Need to connect to a different account? <a href="javascript:;" onclick="document.querySelector('#instacontact-form').hidden = false; document.querySelector('#instacontact-connected').hidden = true;">Click Here</a>
                    </p>
                </div>
                <div class="well" id="instacontact-form" hidden>
                    <form class="ajax-form" method="post" action="<?php echo esc_attr( stripslashes( $_SERVER['REQUEST_URI'] ) ); ?>">
                        <input type="hidden" name="action" value="instacontact-connect"/>
                        <?php
                            settings_fields( self::OPTION_GROUP );
                            do_settings_sections( self::PAGE_SLUG );
                            submit_button('Connect to InstaContact');
                        ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        // register_setting( string $option_group, string $option_name, array $args = array() )
        register_setting(
            self::OPTION_GROUP, // Option group
            'credentials', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        // add_settings_section( string $id, string $title, callable $callback, string $page )
        add_settings_section(
            self::SECTION_ID, // ID
            'API Credentials', // Title
            array( $this, 'print_section_info' ), // Callback
            self::PAGE_SLUG // Page
        );  

        // add_settings_field( string $id, string $title, callable $callback, string $page, string $section = 'default', array $args = array() )
        add_settings_field(
            'email', 
            'Email', 
            array( $this, 'email_callback' ), 
            self::PAGE_SLUG, // Page
            self::SECTION_ID // Section
        );
        add_settings_field(
            'api_key', // ID
            'API Key', // Title 
            array( $this, 'api_key_callback' ), // Callback
            self::PAGE_SLUG, // Page
            self::SECTION_ID // Section
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['api_key'] ) )
            $new_input['api_key'] = sanitize_text_field( $input['api_key'] );

        if( isset( $input['email'] ) )
            $new_input['email'] = sanitize_text_field( $input['email'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        ?>
        <p class="text-normal">You must authenticate your InstaContact account before you can use InstaContact on this site.</p>
        <p class="text-normal">
            Need an InstaContact account?
            <a href="<?php echo $this->get_link(); ?>" target="_blank">Click here to learn more about InstaContact!</a>
        </p>
        <?php
        // print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function api_key_callback()
    {
        printf(
            '<input type="password" id="api_key" name="instacontact[api_key]" value="%s" />',
            isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function email_callback()
    {
        printf(
            '<input type="email" id="email" name="instacontact[email]" value="%s" />',
            isset( $this->options['email'] ) ? esc_attr( $this->options['email']) : ''
        );
    }

    public function get_link($base = 'https://instacontact.io/') {
        return $base . '?utm_source=instacontact-wp-plugin&utm_medium=referral&utm_campaign=newsignup&rurl=' . 
            urlencode( trim( get_site_url() ) );
    }

}

// if( is_admin() )
//     $my_settings_page = new MySettingsPage();