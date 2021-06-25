<?php

namespace Ezoic_Namespace;

/**
 * Fired during plugin activation
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 */
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-wp-endpoints.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-cache-identifier.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ezoic-integration-compatibility-check.php';

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 * @author     Ezoic Inc. <support@ezoic.com>
 */
class Ezoic_Integration_Activator {
	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		// check plugin compatibility
		self::checkCompatibility();

		//Create endpoints db table
		$ez_endpoints      = new Ezoic_Integration_WP_Endpoints();
		$sql               = $ez_endpoints->GetTableCreateStatement();
		$current_version   = $ez_endpoints->GetTableVersion();
		$installed_version = \get_option( 'ezoic_db_option' );

		if ( $installed_version !== $current_version ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			update_option( 'ezoic_db_version', $current_version );
		}

		//Lets figure out if any caching is going on
		$cacheIndetifier = new Ezoic_Integration_Cache_Identifier();

		//Lets determine what kind of caching is going on
		if ( $cacheIndetifier->GetCacheType() == Ezoic_Cache_Type::HTACCESS_CACHE ) {
			//modify htaccess files
			$cacheIndetifier->GenerateHTACCESSFile();
			//modify php files
			$cacheIndetifier->ModifyAdvancedCache();
		} elseif ( $cacheIndetifier->GetCacheType() == Ezoic_Cache_Type::PHP_CACHE ) {
			//modify htaccess files
			$cacheIndetifier->GenerateHTACCESSFile();
			//modify php files
			$cacheIndetifier->ModifyAdvancedCache();
		}

		//Generate our config so we know where our possible HTACCESS files will be located
		$cacheIndetifier->GenerateConfig();

		// send activation debug data
		set_transient( 'ezoic_send_debug', array( 1, 1 ) );
		$plugin_admin = new Ezoic_Integration_Admin( EZOIC__PLUGIN_NAME, EZOIC_INTEGRATION_VERSION );
		$plugin_admin->send_debug_to_ezoic();

	}

	/**
	 * Check plugin compatibility
	 */
	private static function checkCompatibility() {

		// Check for incompatible plugins with Ezoic
		$incompatible_plugins = Ezoic_Integration_Compatibility_Check::getActiveIncompatiblePlugins();
		if ( count( $incompatible_plugins ) > 0 ) {
			$pluginString = '';
			foreach ( $incompatible_plugins as $plugin ) {
				// don't wp_die() on detection of Wordfence plugins
				if ( $plugin['name'] != 'Wordfence Security' && $plugin['name'] != 'Wordfence Login Security' && $plugin['name'] != 'Wordfence Assistant' ) {
					$pluginString .= '<strong>' . $plugin['name'] . ' (' . $plugin['version'] . ') </strong><br />';
					$pluginString .= $plugin['message'] . '';

					$deactivateLink = Ezoic_Integration_Compatibility_Check::pluginActionUrl( $plugin['filename'] );
					$pluginString   .= '<p><a class="button button-primary" href="' . $deactivateLink . '">Deactivate Plugin</a></p>';

					$pluginString .= '<br /><br />';
				}
			}

			if ( $pluginString != '' ) {
				deactivate_plugins( EZOIC__PLUGIN_FILE );
				$title   = 'Incompatible Plugins Detected!';
				$message = '<h3>Incompatible Plugins Detected!</h3>';
				$message .= 'The following plugins are not compatible with ' . EZOIC__PLUGIN_NAME . ':<br /><br /><br />
                       ' . $pluginString;

				$message .= '<strong>Please deactivate the incompatible plugins, and reactivate the ' . EZOIC__PLUGIN_NAME . ' plugin.</strong><br/><br/>For more information, please visit <a href="https://www.ezoic.com/compatibility" target="_blank">https://www.ezoic.com/compatibility</a>.';

				$args = array(
					'back_link' => true,
				);

				wp_die( $message, $title, $args );

			}
		}
	}
}
