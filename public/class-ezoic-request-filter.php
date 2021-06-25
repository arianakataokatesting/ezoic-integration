<?php
namespace Ezoic_Namespace;

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
class Ezoic_Request_Filter {

    private $isEzDebug;
    private $headers;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($headers, $isEzDebug) {
        $this->headers = $headers;
        $this->isEzDebug = $isEzDebug;
    }

    public function WeShouldReturnOrig() {
        return is_admin()
			|| isset($this->headers['x-middleton'])
			|| isset($this->headers['X-Middleton'])
			|| $this->isSpecialContentType()
	    	|| $this->isSpecialRoute()
	    	|| $_SERVER['REQUEST_METHOD'] === 'POST'
	    	|| $_SERVER['REQUEST_METHOD'] === 'PUT'
			|| $_SERVER['REQUEST_METHOD'] === 'DELETE'
			|| $this->isEzDebug;
    }

    private function isSpecialContentType() {
		if(isset($this->headers['Accept']) ) {
			$contentType = $this->headers['Accept'];

			if( is_array($contentType) ) {
				foreach( $contentType as $name => $value ) {
					if( $value == "application/json" ) {
						return true;
					}
				}
			}
		}

		return false;
    }

    private function isSpecialRoute() {
		global $wp;

	    // relative current URI:
	    if ( isset( $wp ) ) {
		    $current_url = add_query_arg( null, null );
	    } else {
		    $current_url = $_SERVER['REQUEST_URI'];
	    }

		if( preg_match('/(.*\/wp\/v2\/.*)/', $current_url) ) {
			return true;
		}

		if( preg_match('/(.*wp-login.*)/', $current_url) ) {
			return true;
		}

		if( preg_match('/(.*wp-admin.*)/', $current_url) ) {
			return true;
		}

		/*if( preg_match('/(.*wp-content.*)/', $current_url) ) {
			return true;
		}*/

		return false;
    }

}
