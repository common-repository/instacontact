<?php

class ICAPI_Campaigns
{
    const PAGE_SLUG = 'instacontact-campaigns';

    /**
     * Start up
     */
    public function __construct()
    {
        // self::$instance = $this;
        $this->base     = ICAPI::get_instance();

        if ( is_admin() ) {
            // add_action( 'wp_ajax_instacontact-connect', array( $this, 'connect' ) );
            add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
            // add_action( 'admin_init', array( $this, 'page_init' ) );
        }
    }

    public function add_plugin_page()
    {
      $this->hook = add_submenu_page(
          ICAPI_Settings::PAGE_SLUG,
          'InstaContact Campaigns', 
          'Campaigns',
          'manage_options',
          self::PAGE_SLUG, 
          array( $this, 'create_admin_page' )
      );

      // Load global icon font styles.
      // add_action( 'admin_head', array( $this, 'icon' ) );

      // Load settings page assets.
      if ( $this->hook ) {
        add_action( 'load-' . $this->hook, array( $this, 'assets' ) );
      }
    }

    public function assets() {
      add_action( 'admin_enqueue_scripts', array( $this, 'styles' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
    }

    public function styles() {
      wp_register_style( $this->base->plugin_slug . '-settings', plugins_url( '/assets/css/settings.css', ICAPI_FILE ), array(), $this->base->version );
      wp_enqueue_style( $this->base->plugin_slug . '-settings' );
    }

    public function scripts() {
      wp_register_script( $this->base->plugin_slug . '-campaigns', plugins_url( '/assets/js/campaigns.js', ICAPI_FILE ), array( 'jquery' ), $this->base->version, true );
      wp_enqueue_script( $this->base->plugin_slug . '-campaigns' );
    }

    public function getCampaigns() {
      $site_url = get_site_url();
      $domain = parse_url($site_url, PHP_URL_HOST);

      $option     = $this->base->get_option();
      $api = new ICAPI_Api( 'campaigns', array( 'email' => $option['api']['email'], 'api_key' => $option['api']['api_key'] ), 'GET' );

      $ret = $api->request(array(
          'domain' => $domain
      ));
      return $ret;
    }

    public function create_admin_page()
    {
      $campaigns = $this->getCampaigns();
      ?>
      <div class="instacontact-wrap">
        <div class="inner-container">
          <h1>Your Campaigns</h1>
          <div class="well">
            <?php if (is_wp_error( $campaigns )): ?>
              <p>
                No Campaigns Yet...Add one to get started!
              </p>
              <div class="actions">
                <a href="<?php echo $this->base->get_link('https://app.instacontact.io/sites'); ?>" target="_blank" class="btn btn-primary">Add a Campaign</a>
              </div>
            <?php else: ?>
              <table class="table">
                <thead>
                  <tr>
                    <th>Campaign</th>
                    <th>Type</th>
                    <th>Agents</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ( $campaigns as $campaign ): ?>
                    <tr>
                      <td><?php echo $campaign->name; ?></td>
                      <td><?php echo $campaign->display_type; ?></td>
                      <td><?php echo $campaign->agents_available_count; ?></td>
                      <td><?php echo ucwords($campaign->status); ?></td>
                      <td><a href="<?php echo $campaign->url; ?>" target="_blank">Manage</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php
    }
}