<?php
/**
 * Api class.
 *
 * @since 1.0.0
 *
 * @package OMAPI
 * @author  Thomas Griffin
 */
class ICAPI_Api {

  /**
   * Base API route.
   *
   * @since 1.0.0
   *
   * @var string
   */
  public $base = INSTACONTACT_APP_API_URL;

  /**
   * Current API route.
   *
   * @since 1.0.0
   *
   * @var bool|string
   */
  public $route = false;

  /**
   * Full API URL endpoint.
   *
   * @since 1.0.0
   *
   * @var bool|string
   */
  public $url = false;

  /**
   * Current API method.
   *
   * @since 1.0.0
   *
   * @var bool|string
   */
  public $method = false;

  /**
   * API Username.
   *
   * @since 1.0.0
   *
   * @var bool|string
   */
  public $user = false;

  /**
   * API Key.
   *
   * @since 1.0.0
   *
   * @var bool|string
   */
  public $key = false;

  /**
   * Plugin slug.
   *
   * @since 1.0.0
   *
   * @var bool|string
   */
  public $plugin = false;

  /**
   * Additional data to add to request body
   *
   * @since 1.0.0
   *
   * @var array
   */
  protected $additional_data = array();

  /**
   * Primary class constructor.
   *
   * @since 1.0.0
   *
   * @param string $route  The API route to target.
   * @param array $creds   Array of API credentials.
   * @param string $method The API method.
   */
  public function __construct( $route, $creds, $method = 'POST' ) {
    // Set class properties.
    $this->route    = $route;
    $this->url      = $this->base . $this->route . '/';
    $this->method   = $method;
    $this->email    = ! empty( $creds['email'] ) ? $creds['email'] : '';
    $this->api_key  = ! empty( $creds['api_key'] ) ? $creds['api_key'] : '';
    $this->plugin   = ICAPI::get_instance()->plugin_slug;
  }

  /**
   * Processes the API request.
   *
   * @since 1.0.0
   *
   * @return mixed $value The response to the API call.
   */
  public function request($body = array()) {
    // Build the body of the request.
    // $body = array(
    // );

    // If a plugin API request, add the data.
    // if ( 'info' == $this->route || 'update' == $this->route ) {
    //   $body['omapi-plugin'] = $this->plugin;
    // }

    // // Add in additional data if needed.
    // if ( ! empty( $this->additional_data ) ) {
    //   $body['omapi-data'] = maybe_serialize( $this->additional_data );
    // }

    $string = http_build_query( $body, '', '&' );

    // Build the headers of the request.
    $headers = array(
      'Content-Type'          => 'application/x-www-form-urlencoded',
      'Cache-Control'         => 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0',
      'Pragma'                => 'no-cache',
      'Expires'               => 0,
      'X-API-Referer'         => site_url(),
      'X-API-Sender'          => 'WordPress',
      'X-User-Email'          => $this->email,
      'X-User-Token'          => $this->api_key,
    );

    // Setup data to be sent to the API.
    $data = array(
      'headers'   => $headers,
      'body'      => $body,
      'timeout'   => 3000,
      'sslverify' => false
    );

    // Perform the query and retrieve the response.
    $response      = 'GET' == $this->method ? wp_remote_get( esc_url_raw( $this->url ) . '?' . $string, $data ) : wp_remote_post( esc_url_raw( $this->url ), $data );
    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = json_decode( wp_remote_retrieve_body( $response ) );
    //return new WP_Error( 'debug', '<pre>' . var_export( $response, true ) . '</pre>' );

    // Bail out early if there are any errors.
    if ( is_wp_error( $response_body ) ) {
      return $response_body;
    }

    // If not a 200 status header, send back error.
    if ( 200 != $response_code ) {
      $type  = ! empty( $response_body->type ) ? $response_body->type : 'api-error';
      $error = ! empty( $response_body->message ) ? stripslashes( $response_body->message ) : '';
      if ( empty( $error ) ) {
        $error = ! empty( $response_body->status_message ) ? stripslashes( $response_body->status_message ) : '';
      }
      if ( empty( $error ) ) {
        $error = ! empty( $response_body->error ) ? stripslashes( $response_body->error ) : 'unknown';
      }

      return new WP_Error( $type, sprintf( __( 'The API returned a <strong>%s</strong> response with this message: <strong>%s</strong>', 'instacontact' ), $response_code, $error ) );
    }

    // Return the json decoded content.
    return $response_body;
  }

  /**
   * Sets a class property.
   *
   * @since 1.0.0
   *
   * @param string $key The property to set.
   * @param string $val The value to set for the property.
   * @return mixed $value The response to the API call.
   */
  public function set( $key, $val ) {
    $this->{$key} = $val;
  }

  /**
   * Allow additional data to be passed in the request
   *
   * @since 1.0.0
   *
   * @param array $data
   * return void
   */
  public function set_additional_data( array $data ) {
    $this->additional_data = array_merge( $this->additional_data, $data );
  }

  /**
   * Checks for SSL for making API requests.
   *
   * @since 1.0.0
   *
   * return bool True if SSL is enabled, false otherwise.
   */
  public function is_ssl() {
    // Use the base is_ssl check first.
    if ( is_ssl() ) {
      return true;
    } else if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
      // Also catch proxies and load balancers.
      return true;
    } else if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) {
      return true;
    }

    // Otherwise, return false.
    return false;
  }

}