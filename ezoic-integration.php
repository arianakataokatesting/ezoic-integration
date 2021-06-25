<?php

namespace Ezoic_Namespace;

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://ezoic.com
 * @since             1.0.0
 * @package           Ezoic_Integration
 *
 * @wordpress-plugin
 * Plugin Name:       Ezoic
 * Plugin URI:        https://ezoic.com/
 * Description:       Easily integrate and connect with Ezoic using WordPress. In order to activate this plugin properly you will need an Ezoic account. You can create an account here: https://pubdash.ezoic.com/join
 * Version:           1.6.8
 * Author:            Ezoic Inc.
 * Author URI:        https://ezoic.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ezoic-integration
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin Constants
 */

if ( ! defined( 'EZOIC_INTEGRATION_VERSION' ) ) {
	define( 'EZOIC_INTEGRATION_VERSION', '1.6.8' ); // also update version in 'class-ezoic-integration-factory.php'.
}
define( 'EZOIC__PLUGIN_NAME', 'Ezoic' );
define( 'EZOIC__PLUGIN_SLUG', dirname( plugin_basename( __FILE__ ) ) );
define( 'EZOIC__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EZOIC__PLUGIN_FILE', plugin_basename( __FILE__ ) );

define( 'EZOIC__SITE_NAME', 'Ezoic' );
define( 'EZOIC__SITE_LOGIN', 'https://pubdash.ezoic.com/user/login' );

/**
 * Current API version.
 * Starts at version 1.0.0
 * Rename this constant if we change anything about what the api accepts
 */
if ( ! defined( 'EZOIC_API_VERSION' ) ) {
	define( 'EZOIC_API_VERSION', '1.0.0' );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ezoic-integration-activator.php
 */
function activate_ezoic_integration() {
	require_once EZOIC__PLUGIN_DIR . 'includes/class-ezoic-integration-activator.php';
	Ezoic_Integration_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ezoic-integration-deactivator.php
 */
function deactivate_ezoic_integration() {
	require_once EZOIC__PLUGIN_DIR . 'includes/class-ezoic-integration-deactivator.php';
	Ezoic_Integration_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'Ezoic_Namespace\activate_ezoic_integration' );
register_deactivation_hook( __FILE__, 'Ezoic_Namespace\deactivate_ezoic_integration' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require EZOIC__PLUGIN_DIR . 'includes/class-ezoic-integration.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ezoic_integration() {

	$ezoic_integration = new Ezoic_Integration();
	$ezoic_integration->run();

}

run_ezoic_integration();
