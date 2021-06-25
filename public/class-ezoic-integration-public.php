<?php
namespace Ezoic_Namespace;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/public
 * @author     Ezoic Inc. <support@ezoic.com>
 */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-factory.php';

class Ezoic_Integration_Public {
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

	private $output;

	private $numCallbacks;

	private $ezHeaders;

	private $startBufferLevel;

	private $ezDebugParam = "ez_wp_debug";

	private $isEzDebug = false;

	private $isBustEndpointCache = false;

	private $isWPComSite = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->output = '';
		$this->numCallbacks = 0;

		if( isset($_GET[$this->ezDebugParam]) && $_GET[$this->ezDebugParam] == "1" ) {
			$this->isEzDebug = true;
		}

	}

	public function ez_buffer_start() {
		ob_start();
	}

	public function ez_buffer_end() {
		$ezoic_factory = new Ezoic_Integration_Factory();
		$ezoic_integrator = $ezoic_factory->NewEzoicIntegrator(Ezoic_Cache_Type::NO_CACHE);
		$ezoic_integrator->ApplyEzoicMiddleware();
	}

	public function bypassCacheFilters() {

		// Prevent WP-Touch Cache(s)
		add_filter( 'wptouch_addon_cache_current_page', '__return_false', 99 );

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		//wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ezoic-integration-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ezoic-integration-public.js', array( 'jquery' ), $this->version, false );

	}

}
