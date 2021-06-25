<?php
namespace Ezoic_Namespace;

/**
 * Fired during plugin deactivation
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-cache-identifier.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-cache-integrator.php';
include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-cache.php';
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 * @author     Ezoic Inc. <support@ezoic.com>
 */
class Ezoic_Integration_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		//Lets figure out if any caching is going on
		$cacheIdentifier = new Ezoic_Integration_Cache_Identifier();

		//Lets determine what kind of caching is going on
		if ( $cacheIdentifier->GetCacheType() == Ezoic_Cache_Type::HTACCESS_CACHE ) {
			//modify htaccess files
			$cacheIdentifier->RemoveHTACCESSFile();
			//modify php files
			$cacheIdentifier->RestoreAdvancedCache();
		} elseif ( $cacheIdentifier->GetCacheType() == Ezoic_Cache_Type::PHP_CACHE ) {
			//modify htaccess files
			$cacheIdentifier->RemoveHTACCESSFile();
			//modify php files
			$cacheIdentifier->RestoreAdvancedCache();
		}

		// If we were using Ezoic caching, clean up the advanced-cache.php and wp-config.php.
		if (defined('EZOIC_CACHE') && EZOIC_CACHE) {
			$cache_integrator = new Ezoic_Integration_Cache_Integrator;
			$cache = new Ezoic_Integration_Cache;

			// Clear the cache just in case there are old files in it.
			$cache->Clear();

			// Remove the WP_CACHE define from wp-config.php.
			$cache_integrator->CleanWPConfig();

			// Remove the advanced cache file.
			$cache_integrator->RemoveAdvancedCache();
		}

		// send deactivation debug data
		set_transient( 'ezoic_send_debug', array( 1, 0 ) );
		$plugin_admin = new Ezoic_Integration_Admin( EZOIC__PLUGIN_NAME, EZOIC_INTEGRATION_VERSION );
		$plugin_admin->send_debug_to_ezoic();

	}

}
