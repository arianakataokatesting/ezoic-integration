<?php

namespace Ezoic_Namespace;

/**
 * Compatibility check for Ezoic plugin against other plugins.
 *
 * @link       https://ezoic.com
 * @since      1.0.0
 *
 * @package    Ezoic_Integration
 * @subpackage Ezoic_Integration/includes
 */

/**
 * Class Ezoic_Integration_Compatibility_Check
 * @package Ezoic_Namespace
 */
class Ezoic_Integration_Compatibility_Check {

	// incompatible plugins regardless of integration type
	static $allIncompatiblePlugins = array(
		'Ezoic CDN Manager' => array(
			'versions' => 'all',
			'message' => 'Ezoic CDN Manager functionality has been implemented into this main Ezoic plugin. Please deactivate and remove the Ezoic CDN Manager plugin.'
		),
	);

    // list of know incompatible plugins for wordpress integration
    static $knownIncompatiblePlugins = array(
        'Accelerated Mobile Pages' => array(
            'versions' => 'all',
            'message' => 'Must disable this plugin and can use another AMP plugin for conflict-free mobile site and monetization.'
        ),
        'AMP' => array(
            'versions' => 'all',
            'message' => 'Must disable this plugin and can use another AMP plugin for conflict-free mobile site and monetization.'
        ),
        'Wordfence Security' => array(
            'versions' => 'all',
            'message' => 'Please disable this plugin or contact Wordfence to whitelist Ezoic IPs to avoid Origin Error (if Ezoic IPs are already whitelisted, you can ignore this message). For more information on Origin Error, please visit <a target="_blank" rel="noopener noreferrer" href="https://support.ezoic.com/kb/article/how-to-fix-origin-errors">support.ezoic.com/kb/article/how-to-fix-origin-errors</a>.'
        ),
        'Wordfence Login Security' => array(
            'versions' => 'all',
            'message' => 'Please disable this plugin or contact Wordfence to whitelist Ezoic IPs to avoid Origin Error (if Ezoic IPs are already whitelisted, you can ignore this message). For more information on Origin Error, please visit <a target="_blank" rel="noopener noreferrer" href="https://support.ezoic.com/kb/article/how-to-fix-origin-errors">support.ezoic.com/kb/article/how-to-fix-origin-errors</a>.'
        ),
        'Wordfence Assistant' => array(
            'versions' => 'all',
            'message' => 'Please disable this plugin or contact Wordfence to whitelist Ezoic IPs to avoid Origin Error (if Ezoic IPs are already whitelisted, you can ignore this message). For more information on Origin Error, please visit <a target="_blank" rel="noopener noreferrer" href="https://support.ezoic.com/kb/article/how-to-fix-origin-errors">support.ezoic.com/kb/article/how-to-fix-origin-errors</a>.'
        ),
        'Swift Performance Lite' => array(
            'versions' => 'all',
            'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts. Sites can elect to use a whitelisted WP caching plugin; however, most WP caching plugins  should be disabled when using the Ezoic Site Speed Accelerator. Fortunately, the Site Speed Accelerator replaces all the functionality of these caching plugins and delivers better performance.'
        ),
        'LiteSpeed Cache' => array(
            'versions' => 'all',
            'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts. Sites can elect to use a whitelisted WP caching plugin; however, most WP caching plugins  should be disabled when using the Ezoic Site Speed Accelerator. Fortunately, the Site Speed Accelerator replaces all the functionality of these caching plugins and delivers better performance.'
        ),
        'WP Fastest Cache' => array(
            'versions' => 'all',
            'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts.'
        ),
        'Autoptimize' => array(
            'versions' => 'all',
            'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts.'
        ),
        'WP-Optimize - Clean, Compress, Cache' => array(
            'versions' => 'all',
            'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts. Sites can elect to use a whitelisted WP caching plugin; however,  most WP caching plugins  should be disabled when using the Ezoic Site Speed Accelerator. Fortunately, the Site Speed Accelerator replaces all the functionality of these caching plugins and delivers better performance.'
        ),
        'SG Optimizer' => array(
            'versions' => 'all',
            'message' => 'Plugin must be disabled to utilize Ezoic without issues or conflicts. Sites can elect to use a whitelisted WP caching plugin; however,  most WP caching plugins  should be disabled when using the Ezoic Site Speed Accelerator. Fortunately, the Site Speed Accelerator replaces all the functionality of these caching plugins and delivers better performance.'
        ),
    );

    // list of known compatible plugins
    static $whitelistedPlugins = array(
        'W3 Total Cache' => array(
            'versions' => 'all',
            'message' => 'The Site Speed Accelerator may require that these plugins be turned off or that all minification, caching, or "speed" optimizations are disabled to prevent conflicts. The Site Speed Accelerator more optimally replaces the functionality of these plugins as it relates to site speed.'
        ),
        'WP Super Cache' => array(
            'versions' => 'all',
            'message' => 'The Site Speed Accelerator may require that these plugins be turned off or that all minification, caching, or "speed" optimizations are disabled to prevent conflicts. The Site Speed Accelerator more optimally replaces the functionality of these plugins as it relates to site speed.'
        ),
        'WP Rocket' => array(
            'versions' => 'all',
            'message' => 'The Site Speed Accelerator may require that these plugins be turned off or that all minification, caching, or "speed" optimizations are disabled to prevent conflicts. The Site Speed Accelerator more optimally replaces the functionality of these plugins as it relates to site speed.'
        ),
        'ShortPixel Image Optimizer' => array(
            'versions' => 'all',
            'message' => 'This plugin is no longer required when using Ezoic\'s Site Speed Accelerator and may cause conflicts in performance. Ezoic performs all the paid features of this plugin and much more.'
        ),
        'Imagify' => array(
            'versions' => 'all',
            'message' => 'This plugin is no longer required when using Ezoic\'s Site Speed Accelerator and may cause conflicts in performance. Ezoic performs all the paid features of this plugin and much more.'
        ),
        'reSmush.it Image Optimizer' => array(
            'versions' => 'all',
            'message' => 'This plugin is no longer required when using Ezoic\'s Site Speed Accelerator and may cause conflicts in performance. Ezoic performs all the paid features of this plugin and much more.'
        ),
        'Smush' => array(
            'versions' => 'all',
            'message' => 'This plugin is no longer required when using Ezoic\'s Site Speed Accelerator and may cause conflicts in performance. Ezoic performs all the paid features of this plugin and much more.'
        ),
        'EWWW Image Optimizer' => array(
            'versions' => 'all',
            'message' => 'This plugin is no longer required when using Ezoic\'s Site Speed Accelerator and may cause conflicts in performance. Ezoic performs all the paid features of this plugin and much more.'
        ),
    );


    /**
     * Get plugins that are known to be NOT compatible with Ezoic.
     * @return array
     */
	public static function getActiveIncompatiblePlugins() {
		$activePlugins       = self::getActivePlugins();
		$incompatiblePlugins = array();

		// incompatible with wordpress integration
		if ( Ezoic_Integration_Admin::IsCloudIntegrated() == false ) {
			foreach ( $activePlugins as $filename => $plugin ) {
				if ( self::isInPluginsList( $plugin, self::$knownIncompatiblePlugins ) ) {
					$plugin['message']  = self::$knownIncompatiblePlugins[ $plugin['name'] ]['message'];
					$plugin['filename'] = $filename;
					array_push( $incompatiblePlugins, $plugin );
				}
			}
		}

	    // incompatible with any integration type
	    foreach ( $activePlugins as $filename => $plugin ) {
		    if ( self::isInPluginsList( $plugin, self::$allIncompatiblePlugins ) ) {
			    $plugin['message']  = self::$allIncompatiblePlugins[ $plugin['name'] ]['message'];
			    $plugin['filename'] = $filename;
			    array_push( $incompatiblePlugins, $plugin );
		    }
	    }
        return $incompatiblePlugins;
    }

    /**
     * Get plugins that are known to be compatible with Ezoic but can be replaced by another Ezoic product.
     * @return array
     */
    public static function getCompatiblePluginsWithRecommendations() {
        $activePlugins = self::getActivePlugins();
        $plugins = array();
        foreach ($activePlugins as $filename => $plugin) {
            if ($plugin['name'] == EZOIC__PLUGIN_NAME || self::isInPluginsList($plugin, self::$knownIncompatiblePlugins)) {
                continue;
            }
            if (self::isInPluginsList($plugin, self::$whitelistedPlugins)) {
                $plugin['message'] = self::$whitelistedPlugins[$plugin['name']]['message'];
                array_push($plugins, $plugin);
            }
        }
        return $plugins;
    }

    private static function getActivePlugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        $plugins = array();
        foreach ($all_plugins as $key => $value) {
            if (in_array($key, $active_plugins)) {
                $plugins[$key] = array(
                    'name'    => $value['Name'],
                    'version' => $value['Version'],
                );
            }
        }

        return $plugins;
    }

    private static function isInPluginsList($plugin, $pluginsList) {
        foreach ($pluginsList as $name => $info) {
            $versions = $info['versions'];
            if ($plugin['name'] == $name) {
                if ($versions == 'all' || in_array($plugin['version'], $versions)) {
                    return true;
                }
            }
        }
        return false;
    }

	/**
	 * Get activation or deactivation link of a plugin
	 *
	 * @param $plugin
	 * @param string $action
	 *
	 * @return string
	 */
	public static function pluginActionUrl( $plugin, $action = 'deactivate' ) {
		if ( strpos( $plugin, '/' ) ) {
			$plugin = str_replace( '\/', '%2F', $plugin );
		}
		$url = sprintf( admin_url( 'plugins.php?action=' . $action . '&plugin=%s&plugin_status=all&paged=1&s' ), $plugin );
		$_REQUEST['plugin'] = $plugin;
		$url = wp_nonce_url( $url, $action . '-plugin_' . $plugin );
		return $url;
	}
}
