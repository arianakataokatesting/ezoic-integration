<?php
namespace {
	include_once 'include-functions.php';
}

namespace Ezoic_Namespace {

	/**
	 * The file that defines the core plugin class
	 *
	 * A class definition that includes attributes and functions used across both the
	 * public-facing side of the site and the admin area.
	 *
	 * @link       https://ezoic.com
	 * @since      1.0.0
	 *
	 * @package    Ezoic_Integration
	 * @subpackage Ezoic_Integration/includes
	 */

	$GLOBALS['ezoic_integration_buffer'] = '';

	/**
	 * The core plugin class.
	 *
	 * This is used to define internationalization, admin-specific hooks, and
	 * public-facing site hooks.
	 *
	 * Also maintains the unique identifier of this plugin as well as the current
	 * version of the plugin.
	 *
	 * @since      1.0.0
	 * @package    Ezoic_Integration
	 * @subpackage Ezoic_Integration/includes
	 * @author     Ezoic Inc. <support@ezoic.com>
	 */
	class Ezoic_Integration {

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Ezoic_Integration_Loader $loader Maintains and registers all hooks for the plugin.
		 */
		protected $loader;

		/**
		 * The unique identifier of this plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string $plugin_name The string used to uniquely identify this plugin.
		 */
		protected $plugin_name;

		/**
		 * The current version of the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string $version The current version of the plugin.
		 */
		protected $version;

		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {
			if ( defined( 'EZOIC_INTEGRATION_VERSION' ) ) {
				$this->version = EZOIC_INTEGRATION_VERSION;
			} else {
				$this->version = '1.0.0';
			}
			$this->plugin_name = EZOIC__PLUGIN_SLUG;

			$this->load_dependencies();
			$this->set_locale();
			$this->define_admin_hooks();
			$this->define_public_hooks();

		}

		/**
		 * Load the required dependencies for this plugin.
		 *
		 * Include the following files that make up the plugin:
		 *
		 * - Ezoic_Integration_Loader. Orchestrates the hooks of the plugin.
		 * - Ezoic_Integration_i18n. Defines internationalization functionality.
		 * - Ezoic_Integration_Admin. Defines all hooks for the admin area.
		 * - Ezoic_Integration_Public. Defines all hooks for the public side of the site.
		 *
		 * Create an instance of the loader which will be used to register the hooks
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function load_dependencies() {

			/**
			 * The class responsible for orchestrating the actions and filters of the
			 * core plugin.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-loader.php';

			/**
			 * The class responsible for defining internationalization functionality
			 * of the plugin.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-i18n.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ezoic-integration-admin.php';

			/**
			 * The class responsible for defining all actions that occur in the public-facing
			 * side of the site.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ezoic-integration-public.php';

			$this->loader = new Ezoic_Integration_Loader();

		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the Ezoic_Integration_i18n class in order to set the domain and to register the hook
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function set_locale() {

			$plugin_i18n = new Ezoic_Integration_i18n();

			$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_admin_hooks() {

			$plugin_admin    = new Ezoic_Integration_Admin( $this->get_plugin_name(), $this->get_version() );
			$plugin_settings = new Ezoic_Integration_Admin_Settings( $this->get_plugin_name(), $this->get_version() );
			$cdn_settings    = new Ezoic_Integration_CDN_Settings( $this->get_plugin_name(), $this->get_version() );

			// We need to make sure that caching is not enabled while a pub is using a cloud integration. If the request is
			// coming from a cloud integrated site, we turn caching off and clean up any cache files and modifications.
			$plugin_settings->handle_cloud_integrated_with_caching($plugin_admin);

			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

			$this->loader->add_action( 'admin_menu', $plugin_settings, 'setup_plugin_options_menu' );
			$this->loader->add_action( 'admin_init', $plugin_settings, 'initialize_display_options' );
			$this->loader->add_action( 'admin_init', $plugin_settings, 'initialize_advanced_options' );
			$this->loader->add_action( 'admin_init', $cdn_settings, 'initialize_cdn_settings' );

			$this->loader->add_action( 'admin_footer', $plugin_admin, 'theme_switch_notification' );

			// Hooks related to ezoic caching.
			$this->loader->add_action( 'update_option_ezoic_integration_options', $plugin_settings, 'handle_update_ezoic_integration_options', 0, 3 );
			$this->loader->add_action( 'post_updated', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action( 'comment_post', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action( 'update_option_permalink_structure', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action( 'save_post', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action( 'after_delete_post', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action( 'create_category', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action( 'delete_category', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action( 'create_term', $plugin_settings, 'handle_clear_cache');
			$this->loader->add_action( 'delete_term', $plugin_settings, 'handle_clear_cache');

			// Add Settings link to the plugin.
			$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' );
			$this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links' );

			// Send debug data on core/theme/plugin updates
			$this->loader->add_action( 'switch_theme', $plugin_admin, 'set_debug_to_ezoic' );
			$this->loader->add_action( 'activated_plugin', $plugin_admin, 'set_debug_to_ezoic' );
			$this->loader->add_action( 'deactivated_plugin', $plugin_admin, 'set_debug_to_ezoic' );
			$this->loader->add_action( 'upgrader_process_complete', $plugin_admin, 'set_debug_to_ezoic' );
			$this->loader->add_action( 'admin_init', $plugin_admin, 'send_debug_to_ezoic' );


			$plugin_cdn = new Ezoic_Integration_CDN( $this->get_plugin_name(), $this->get_version() );
			$this->loader->add_action( 'post_updated', $plugin_cdn, 'ezoic_cdn_post_updated', 100, 3 );
			$this->loader->add_action( 'save_post', $plugin_cdn, 'ezoic_cdn_save_post', 100, 3 );
			$this->loader->add_action( 'template_redirect', $plugin_cdn, 'ezoic_cdn_add_headers' );
			$this->loader->add_action( 'admin_notices', $plugin_cdn, 'ezoic_cdn_display_admin_notices' );

			$this->loader->add_action( 'comment_post', $plugin_cdn, 'ezoic_cdn_comment_post', 100, 3 );
			$this->loader->add_action( 'edit_comment', $plugin_cdn, 'ezoic_cdn_edit_comment', 100, 2 );
			$this->loader->add_action( 'delete_comment', $plugin_cdn, 'ezoic_cdn_delete_comment', 100, 2 );
			$this->loader->add_action( 'trash_comment', $plugin_cdn, 'ezoic_cdn_delete_comment', 100, 2 );
			$this->loader->add_action('wp_set_comment_status', $plugin_cdn, 'ezoic_cdn_comment_change_status', 100, 2);

			$this->loader->add_action( 'after_delete_post', $plugin_cdn, 'ezoic_cdn_post_deleted', 100, 2 );
			$this->loader->add_action( 'ezoic_cdn_scheduled_clear', $plugin_cdn, 'ezoic_cdn_scheduled_clear_action', 1, 1 );

			$this->loader->add_action( 'switch_theme', $plugin_cdn, 'ezoic_cdn_switch_theme', 100, 3 );
			$this->loader->add_action( 'activated_plugin', $plugin_cdn, 'ezoic_cdn_activated_plugin', 100, 2 );
			$this->loader->add_action( 'deleted_plugin', $plugin_cdn, 'ezoic_cdn_deleted_plugin', 100, 2 );
			$this->loader->add_action( 'deactivated_plugin', $plugin_cdn, 'ezoic_cdn_deactivated_plugin', 100, 2 );

			// When W3TC is instructed to purge cache for entire site, also purge cache from Ezoic CDN.
			$this->loader->add_action( 'w3tc_flush_posts', $plugin_cdn, 'ezoic_cdn_cachehook_purge_posts_action', 2100 );
			$this->loader->add_action( 'w3tc_flush_all', $plugin_cdn, 'ezoic_cdn_cachehook_purge_posts_action', 2100 );
			// Also hook into WP Super Cache's wp_cache_cleared action.
			$this->loader->add_action( 'wp_cache_cleared', $plugin_cdn, 'ezoic_cdn_cachehook_purge_posts_action', 2100 );
			// When W3TC is instructed to purge cache for a post, also purge cache from Ezoic CDN.
			$this->loader->add_action( 'w3tc_flush_post', $plugin_cdn, 'ezoic_cdn_cachehook_purge_post_action', 2100, 1 );
			// WP-Rocket Purge Cache Hook.
			$this->loader->add_action( 'rocket_purge_cache', $plugin_cdn, 'ezoic_cdn_rocket_purge_action', 2100, 4 );
			$this->loader->add_action( 'after_rocket_clean_post', $plugin_cdn, 'ezoic_cdn_rocket_clean_post_action', 2100, 3 );


		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_public_hooks() {

			$plugin_public = new Ezoic_Integration_Public( $this->get_plugin_name(), $this->get_version() );

			$plugin_public->bypassCacheFilters();

			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
			$this->loader->add_action( 'plugins_loaded', $plugin_public, 'ez_buffer_start', 0 );
			$this->loader->add_action( 'shutdown', $plugin_public, 'ez_buffer_end', 0 );

			//Do not run the ob end flush action as this causes an error with zlib compression
			//Should be removed from core anyway
			//remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
		}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since    1.0.0
		 */
		public function run() {
			$this->loader->run();
		}

		/**
		 * The name of the plugin used to uniquely identify it within the context of
		 * WordPress and to define internationalization functionality.
		 *
		 * @since     1.0.0
		 * @return    string    The name of the plugin.
		 */
		public function get_plugin_name() {
			return $this->plugin_name;
		}

		/**
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @since     1.0.0
		 * @return    Ezoic_Integration_Loader    Orchestrates the hooks of the plugin.
		 */
		public function get_loader() {
			return $this->loader;
		}

		/**
		 * Retrieve the version number of the plugin.
		 *
		 * @since     1.0.0
		 * @return    string    The version number of the plugin.
		 */
		public function get_version() {
			return $this->version;
		}

	}

}
