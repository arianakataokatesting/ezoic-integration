<?php
namespace Ezoic_Namespace;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/admin
 * @author     Ezoic Inc. <support@ezoic.com>
 */
class Ezoic_Integration_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->load_dependencies();

	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = array(
			'<a href="options-general.php?page=' . EZOIC__PLUGIN_SLUG . '">' . __( 'Settings' ) . '</a>',
			'<a href="' . EZOIC__SITE_LOGIN . '" target="_blank">' .
			sprintf( __( '%s Login' ), EZOIC__SITE_NAME ) . '</a>',
		);

		return array_merge( $links, $settings_link );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ezoic_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ezoic_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ezoic-integration-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ezoic_Integration_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ezoic_Integration_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ezoic-integration-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Load the required dependencies for the Admin facing functionality.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Ezoic_Integration_Admin_Settings. Registers the admin settings and page.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ezoic-integration-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ezoic-cdn-settings.php';

	}

	/**
	 * @param $data
	 * @param bool $status
	 *
	 * @return array
	 */
	public function build_integration_request( $data, $status = 1 ) {
		global $wp;

		$domain = home_url( $wp->request );
		$domain = wp_parse_url( $domain )['host'];

		$request_params = array(
			'domain'    => $domain,
			'title'     => get_bloginfo( 'name' ),
			'url'       => get_bloginfo( 'url' ),
			'data'      => $data,
			'is_active' => boolval($status),
		);

		$request = array(
			'timeout' => 30,
			'body'    => json_encode( $request_params ),
			'headers' => array(
				'X-Wordpress-Integration' => 'true',
				'Expect'                  => '',
				'X-From-Req'              => 'wp'
			),
		);

		return $request;
	}

	public static function set_debug_to_ezoic() {
		set_transient( 'ezoic_send_debug', array( 1, 1 ) );
	}

	public function send_debug_to_ezoic() {

		if ( $ezoic_send_debug = get_transient( 'ezoic_send_debug' ) ) {

			if ( ! is_array( $ezoic_send_debug ) ) {
				$ezoic_send_debug = array( 1, 1 );
			}

			if ( ! class_exists( 'WP_Debug_Data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-debug-data.php';
			}
			if ( ! class_exists( 'WP_Site_Health' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
			}

			if ( class_exists( 'WP_Debug_Data' ) ) {
				$debug = new \WP_Debug_Data();
				$debug::check_for_updates();
				$info = ( $debug::debug_data() );

				$info['wp-get-plugins'] = self::get_plugin_data();

				$request = $this->build_integration_request( $info, $ezoic_send_debug[1] );

				//Ezoic_Integration_Request_Utils::GetEzoicServerAddress()
				$response = wp_remote_post( "https://g.ezoic.net/wp/debug.go", $request );
			}

			delete_transient( 'ezoic_send_debug' );

		} // endif;
	}

	/**
	 * Get list of wordpress plugins with status
	 *
	 * @return array[]
	 */
	function get_plugin_data() {

		// Get all plugins
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();

		// Get active plugins
		$active_plugins = get_option( 'active_plugins' );

		// Add 'Active' boolean to $all_plugins array.
		foreach ( $all_plugins as $key => $value ) {
			$is_active                     = in_array( $key, $active_plugins );
			$all_plugins[ $key ]['Active'] = $is_active;

			$plugin_slug                   = dirname( plugin_basename( $key ) );
			if ( $plugin_slug == "." ) {
				$plugin_slug = basename( $key, '.php' );
			}
			$all_plugins[ $key ]['Slug'] = $plugin_slug;
		}

		return $all_plugins;
	}

	public function theme_switch_notification() {
		global $pagenow;
		if ( 'themes.php' === $pagenow || 'theme-install.php' === $pagenow ) {
			include_once( EZOIC__PLUGIN_DIR . 'admin/partials/ezoic-integration-admin-theme-notification.php' );
		}
	}

	/**
	 * Checks to see if the site is Ezoic cloud integrated by searching for the x-middleton header.
	 */
	public static function IsCloudIntegrated() {
		$headers = getallheaders();
		$header = array_change_key_case($headers); // Convert all keys to lower

		return isset( $header['x-middleton'] ) && $header['x-middleton'] == '1';
	}
}
