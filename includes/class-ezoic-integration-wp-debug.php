<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-debug.php');

class Ezoic_Integration_WP_Debug implements iEzoic_Integration_Debug {

	private $ezHeaders;
	private $cache_identity;

	/**
	 * Ezoic_Integration_WP_Debug constructor.
	 *
	 * @param $cache_identity
	 */
	public function __construct( $cache_identity ) {
		$this->cache_identity = $cache_identity;
	}

    public function GetDebugInformation() {
		global $wp;
		$home_url = home_url( $wp->request );
		if (substr($home_url,-1) != '/' && function_exists('shouldCurrentPathEndInSlash') && shouldCurrentPathEndInSlash()) {
			$home_url = $home_url . '/';
		}

        $current_url = add_query_arg( $_SERVER['QUERY_STRING'], '', $home_url );

		$data = array();

	    if ( function_exists( 'get_plugins' ) ) {
		    $all_plugins    = get_plugins();
		    $active_plugins = get_option( 'active_plugins' );
		    $plugins        = array();
		    foreach ( $all_plugins as $key => $value ) {
			    $plugins[ $key ]           = $value;
			    $is_active                 = in_array( $key, $active_plugins );
			    $plugins[ $key ]['Active'] = $is_active ? "true" : "false";
		    }
		    $data['Plugins'] = $plugins;
	    }

	    if ( function_exists( 'phpversion' ) ) {
		    $data['PHP'] = phpversion();
	    }

	    $ez_plugin = $this->GetEzPluginSettings();

	    $debug_content = array( "Home URL"    => $home_url,
	                            "Current URL" => $current_url,
	                            "Cache Type"  => $this->cache_identity,
	                            "EZ Plugin"   => $ez_plugin
	    );
	    $debug_content = array_merge( $debug_content, $data );

	    return "<!-- " . print_r( $debug_content, true ) . "-->";
    }

	public function WeShouldDebug() {
		if ( isset( $_GET["ez_wp_debug"] ) && $_GET["ez_wp_debug"] == "1" ) {
			return true;
		}

		return false;
	}

	/**
	 * Debug output of Ezoic plugin setting values
	 *
	 * @return array
	 */
	private function GetEzPluginSettings() {
		$ez_plugin                              = array();
		$ez_plugin['ezoic_integration_status']  = \get_option( 'ezoic_integration_status' );
		$ez_plugin['ezoic_integration_options'] = \get_option( 'ezoic_integration_options' );

		$ping_test = "empty";
		$ping      = array( false, "" );
		if ( ! empty( Ezoic_Integration_CDN::ezoic_cdn_api_key() ) ) {
			$ping = Ezoic_Integration_CDN::ezoic_cdn_ping();
			if ( $ping[0] == true ) {
				$ping_test = "valid";
			} else {
				$ping_test = "error";
			}
		}
		$ez_plugin['ezoic_cdn_api_key']['status'] = $ping_test;
		$ez_plugin['ezoic_cdn_api_key']['error']  = $ping[1];
		$ez_plugin['ezoic_cdn_enabled']           = \get_option( 'ezoic_cdn_enabled' );
		$ez_plugin['ezoic_cdn_domain']            = \get_option( 'ezoic_cdn_domain' );
		$ez_plugin['ezoic_cdn_always_home']       = \get_option( 'ezoic_cdn_always_home' );
		$ez_plugin['ezoic_integration_status']    = \get_option( 'ezoic_integration_status' );

		return $ez_plugin;
	}
}
