<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-filter.php');
require_once( dirname(__FILE__) . '/class-ezoic-integration-request-utils.php');

class Ezoic_Integration_Cache_Filter implements iEzoic_Integration_Filter {
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
        return isset($this->headers['x-middleton'])
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

		$resp_headers = headers_list();
		$resp_headers = Ezoic_Integration_Request_Utils::ParseResponseHeaders($resp_headers);
		$headerResponse = $this->handleContentTypeHeader($resp_headers);

		return $headerResponse;
    }

	private function handleContentTypeHeader($responseHeaders) {
			$headerText = "";
			if(isset($responseHeaders["Content-type"])) {
				$headerText = "Content-type";
			} else if (isset($responseHeaders["Content-Type"])) {
				$headerText = "Content-Type";
			} else if (isset($responseHeaders["content-type"])) {
				$headerText = "content-type";
			}

			if($headerText == "") {
				return false;
			}

			$contentType = $responseHeaders[$headerText];
			$parsedHeader = explode(";", $contentType);
			if( trim($parsedHeader[0]) != "text/html" ) {
				return true;
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

	    if ( preg_match('/(.*wp-json.*)/', $current_url) ) {
		    return true;
	    }

	    if ( preg_match('/sitemap(.*)\.xml/', $current_url) ) {
		    return true;
	    }

		return false;
    }
}
