<?php
/**
 * Plugin Name: InstaContact
 * Description: InstaContact plugin to connect your WordPress site to your InstaContact account.
 * Author:      InstaContact Team
 * Author URI:  https://instacontact.io
 * Version:     1.0.0
 * Text Domain: instacontact
 * Domain Path: languages
 *
 * InstaContact's WordPress Plugin is is free software: you can redistribute 
 * it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 2 of the 
 * License, or any later version.
 *
 * InstaContact's WordPress Plugin is distributed in the hope that it will 
 * be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with InstaContact's WordPress Plugin. If not, see <https://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// Autoload the class files.
spl_autoload_register( 'ICAPI::autoload' );

// Store base file location
define( 'ICAPI_FILE', __FILE__ );

/**
 * Main plugin class.
 *
 * @since 1.0.0
 *
 * @package ICAPI
 */
class ICAPI {

  /**
   * Holds the class object.
   *
   * @since 1.0.0
   *
   * @var object
   */
  public static $instance;

  /**
   * Plugin version, used for cache-busting of style and script file references.
   *
   * @since 1.0.0
   *
   * @var string
   */
  public $version = '1.0.0';

  /**
   * The name of the plugin.
   *
   * @since 1.0.0
   *
   * @var string
   */
  public $plugin_name = 'InstaContact';

  /**
   * Unique plugin slug identifier.
   *
   * @since 1.0.0
   *
   * @var string
   */
  public $plugin_slug = 'instacontact';

  /**
   * Plugin file.
   *
   * @since 1.0.0
   *
   * @var string
   */
  public $file = __FILE__;

  /**
   * Primary class constructor.
   *
   * @since 1.0.0
   */
  public function __construct() {

    // Load the plugin textdomain.
    add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

    // Load the plugin.
    add_action( 'init', array( $this, 'init' ) );

  }

  /**
   * Loads the plugin textdomain for translation.
   *
   * @since 1.0.0
   */
  public function load_plugin_textdomain() {

    $domain = 'instacontact';
    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
    load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

  }

  /**
   * Loads the plugin into WordPress.
   *
   * @since 1.0.0
   */
  public function init() {

    if ( ! defined( 'INSTACONTACT_APP_URL' ) ) {
      define( 'INSTACONTACT_APP_URL', 'https://app.instacontact.io' );
    }

    if ( ! defined( 'INSTACONTACT_APP_API_URL' ) ) {
      define( 'INSTACONTACT_APP_API_URL', 'https://app.instacontact.io/api/' );
    }

    // Load our global option.
    $this->load_option();

    // Load global components.
    $this->load_global();

    // Load admin only components.
    if ( is_admin() ) {
      $this->load_admin();
    }

    // Run hook once InstaContact has been fully loaded.
    do_action( 'instacontact_loaded' );

  }

  /**
   * Sets our global option if it is not found in the DB.
   *
   * @since 1.0.0
   */
  public function load_option() {

    $option = get_option( 'instacontact' );
    if ( ! $option || empty( $option ) ) {
      $option = ICAPI::default_options();
      update_option( 'instacontact', $option );
    }

  }

  /**
   * Loads all global related classes into scope.
   *
   * @since 1.0.0
   */
  public function load_global() {
    // Fire a hook to say that the global classes are loaded.
    do_action( 'instacontact_global_loaded' );

  }

  /**
   * Loads all admin related classes into scope.
   *
   * @since 1.0.0
   */
  public function load_admin() {
    // Register admin components.
    $this->settings = new ICAPI_Settings();
    $this->campaigns = new ICAPI_Campaigns();

    // Fire a hook to say that the admin classes are loaded.
    do_action( 'instacontact_admin_loaded' );

  }

  /**
   * Returns the main option for the plugin.
   *
   * @since 1.0.0
   *
   * @return array The main option array for the plugin.
   */
  public function get_option( $key = '', $subkey = '', $default = false ) {

    $option = get_option( 'instacontact' );
    if ( ! empty( $key ) && ! empty( $subkey ) ) {
      return isset( $option[ $key ][ $subkey ] ) ? $option[ $key ][ $subkey ] : $default;
    } else if ( ! empty( $key ) ) {
      return isset( $option[ $key ] ) ? $option[ $key ] : $default;
    } else {
      return $option;
    }

  }

  /**
   * Returns the API credentials for InstaContact.
   *
   * @since 1.0.0
   *
   * @return array|bool $creds The user's API creds for InstaContact.
   */
  public function get_api_credentials() {

    // Prepare variables.
    $option = $this->get_option();
    $user   = false;
    $key = false;


    // Attempt to grab the new API Key
    if ( empty( $option['api']['key'] ) ) {
      if ( defined( 'INSTACONTACT_API_KEY' ) ) {
        $key = INSTACONTACT_API_KEY;
      }
    } else {
      $key = $option['api']['key'];
    }

    if ( empty( $option['api']['user'] ) ) {
      if ( defined( 'INSTACONACT_API_USER' ) ) {
        $user = INSTACONACT_API_USER;
      }
    } else {
      $user = $option['api']['user'];
    }

    // Check if we have any of the authentication data
    if ( ! $key || ! $user ) {
      return false;
    }


    // Return the API credentials.
    return apply_filters( 'instacontact_api_creds',
      array(
        'user' => $user,
        'key' => $key,
      )
    );

  }

  /**
   * Returns possible API key error flag.
   *
   * @since 1.0.0
   *
   * @return bool True if there are API key errors, false otherwise.
   */
  public function get_api_key_errors() {

    $option = $this->get_option();
    return isset( $option['is_expired'] ) && $option['is_expired'] || isset( $option['is_disabled'] ) && $option['is_disabled'] || isset( $option['is_invalid'] ) && $option['is_invalid'];

  }

  /**
   * Retrieves the proper default view for the InstaContact settings page.
   *
   * @since 1.0.0
   *
   * @return string $view The default view for the InstaContact settings page.
   */
  public function get_view() {

    return $this->get_api_credentials() ? 'campaigns' : 'api';

  }

  /**
   * Loads the default plugin options.
   *
   * @since 1.0.0
   *
   * @return array Array of default plugin options.
   */
  public static function default_options() {

    $options = array(
      'api'         => array(),
      'campaigns'   => array(),
      'is_expired'  => false,
      'is_disabled' => false,
      'is_invalid'  => false,
      'welcome'     => array(
        'status'  => 'none', //none, welcomed
        'review'    => 'ask', //ask, asked, dismissed
        'version'   => '1000', //base to check against
      )
    );
    return apply_filters( 'instacontact_default_options', $options );

  }

  /**
   * PRS-0 compliant autoloader.
   *
   * @since 1.0.0
   *
   * @param string $classname The classname to check with the autoloader.
   */
  public static function autoload( $classname ) {

    // Return early if not the proper classname.
    if ( 'ICAPI' !== mb_substr( $classname, 0, 5 ) ) {
      return;
    }

    // Check if the file exists. If so, load the file.
    $filename = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . str_replace( '_', DIRECTORY_SEPARATOR, $classname ) . '.php';
    if ( file_exists( $filename ) ) {
      require $filename;
    }

  }

  public function get_link($base = 'https://instacontact.io/') {
      return $base . '?utm_source=instacontact-wp-plugin&utm_medium=referral&utm_campaign=newsignup&rurl=' . 
          urlencode( trim( get_site_url() ) );
  }


  /**
   * Returns the singleton instance of the class.
   *
   * @since 1.0.0
   *
   * @return OMAPI
   */
  public static function get_instance() {

    if ( ! isset( self::$instance ) && ! ( self::$instance instanceof ICAPI ) ) {
      self::$instance = new ICAPI();
    }

    return self::$instance;

  }
}

register_activation_hook( __FILE__, 'instacontact_activation_hook' );
/**
 * Fired when the plugin is activated.
 *
 * @since 1.0.0
 *
 * @global int $wp_version      The version of WordPress for this install.
 * @global object $wpdb         The WordPress database object.
 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
 */
function instacontact_activation_hook( $network_wide ) {

  global $wp_version;
  if ( version_compare( $wp_version, '3.5.1', '<' ) && ! defined( 'INSTACONTACT_FORCE_ACTIVATION' ) ) {
    deactivate_plugins( plugin_basename( __FILE__ ) );
    wp_die( sprintf( __( 'Sorry, but your version of WordPress does not meet InstaContact\'s required version of <strong>3.5.1</strong> to run properly. The plugin has been deactivated. <a href="%s">Click here to return to the Dashboard</a>.', 'instacontact' ), get_admin_url() ) );
  }

  $instance = ICAPI::get_instance();

  global $wpdb;
  if ( is_multisite() && $network_wide ) {
    $site_list = $wpdb->get_results( "SELECT * FROM $wpdb->blogs ORDER BY blog_id" );
    foreach ( (array) $site_list as $site ) {
      switch_to_blog( $site->blog_id );

      // Set default option.
      $option = get_option( 'instacontact' );
      if ( ! $option || empty( $option ) ) {
        update_option( 'instacontact', ICAPI::default_options() );
      }

      restore_current_blog();
    }
  } else {
    // Set default option.
    $option = get_option( 'instacontact' );
    if ( ! $option || empty( $option ) ) {
      update_option( 'instacontact', ICAPI::default_options() );
    }

  }

}

register_uninstall_hook( __FILE__, 'instacontact_uninstall_hook' );
/**
 * Fired when the plugin is uninstalled.
 *
 * @since 1.0.0
 *
 * @global object $wpdb The WordPress database object.
 */
function instacontact_uninstall_hook() {

  $instance = OMAPI::get_instance();

  global $wpdb;
  if ( is_multisite() ) {
    $site_list = $wpdb->get_results( "SELECT * FROM $wpdb->blogs ORDER BY blog_id" );
    foreach ( (array) $site_list as $site ) {
      switch_to_blog( $site->blog_id );
      delete_option( 'instacontact' );
      restore_current_blog();
    }
  } else {
    delete_option( 'instacontact' );
  }

}

// Load the plugin.
$instacontact = ICAPI::get_instance();

add_action( 'wp_footer', 'instacontact_footer');
function instacontact_footer() {
  $option = get_option( 'instacontact' );
  if ($option['api']['universal_snippet']) {
    echo $option['api']['universal_snippet'];
  }
}