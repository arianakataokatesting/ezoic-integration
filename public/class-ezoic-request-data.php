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
class Ezoic_Request_Data {

    private $req_headers;
    private $resp_headers;
    private $http_method;
    private $ez_request_url;
    private $ez_api_version;
    private $ez_wp_plugin_version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
    public function __construct() {
		$this->req_headers = getallheaders();
		$resp_headers = headers_list();
		$this->resp_headers = $this->parseResponseHeaders($resp_headers);
        $this->http_method = $_SERVER['REQUEST_METHOD'];
        $this->ez_request_url = $this->getEzoicServerAddress();

        if( defined('EZOIC_API_VERSION') ) {
            $this->ez_api_version = EZOIC_API_VERSION;
        } else {
            $this->ez_api_version = '';
        }

		if ( defined( 'EZOIC_INTEGRATION_VERSION' ) ) {
			$this->ez_wp_plugin_version = EZOIC_INTEGRATION_VERSION;
		} else {
			$this->ez_wp_plugin_version = '1.0.0';
		}
	}
	
	private function parseResponseHeaders( $resp_headers ) {
		$modified_headers = array();
		if( is_array($resp_headers) ) {
			foreach($resp_headers as $key => $header) {
					list($headername, $headervalue) = explode(":", $header, 2);
					$modified_headers[$headername] = $headervalue;
			}
		}

		return $modified_headers;
	}

    public function GetContentResponseFromEzoic( $final_content ) {
        $cache_key = md5($final_content);
        //Create proper request data structure
        $request = $this->getEzoicRequest($cache_key);

        //Attempt to retrieve cached content
        $response = $this->getCachedContentEzoicResponse( $request );

		//Only upload non cached data on bad cache response and no wordpress error
        if( !is_wp_error($response) && $this->nonValidCachedContent($response) ) {
            //Send content to ezoic and return back altered content
            $response = $this->getNonCachedContentEzoicResponse($final_content, $request);
        }

        return $response;
    }
    
    private function getEzoicServerAddress() {
        $protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';
	    return "https://g.ezoic.net/wp/data.go";
    }

    private function getCachedContentEzoicResponse( $request ) {
        $request['body']['request_type'] = 'cache_only';
		$result = wp_remote_post($this->ez_request_url, $request);

		return $result;
	}

	private function getNonCachedContentEzoicResponse( $final, $request ) {
		//Set content for non cached response
		$request['body']['content'] = $final;
		$request['body']['request_type'] = 'with_content';

		$result = wp_remote_post($this->ez_request_url, $request);

		return $result;
    }
    
    private function nonValidCachedContent( $result ) {
		return ($result['response']['code'] == 404 || $result['response']['code'] == 400);
	}
    
    private function getEzoicRequest( $cache_key ) {
        global $wp;
        //Form current url 
		$home_url = home_url( $wp->request );
		if (substr($home_url,-1) != '/' && function_exists('shouldCurrentPathEndInSlash') && shouldCurrentPathEndInSlash()) {
			$home_url = $home_url . '/';
		}
        $current_url = add_query_arg( $_SERVER['QUERY_STRING'], '', $home_url );

        $request_params = array(
            'cache_key' => $cache_key,
            'action' => 'get-index-series',
            'content_url' => $current_url,
            'request_headers' => $this->req_headers,
            'response_headers' => $this->resp_headers,
            'http_method' => $this->http_method,
            'ezoic_api_version' => $this->ez_api_version,
            'ezoic_wp_integration_version' => $this->ez_wp_plugin_version,
        );

		if(!empty($_GET)){
		    $request_params = array_merge($request_params, $_GET);
		}

		unset($this->req_headers["Content-Length"]);
        $this->req_headers['X-Wordpress-Integration'] = 'true';

        //Get IP for X-Forwarded-For
        $ip = $this->getClientIp();

	    $request = array(
	    	'timeout' => 5,
	        'body' => $request_params,
            'headers' => array('X-Wordpress-Integration' => 'true', 'X-Forwarded-For' => $ip, 'Expect' => ''),
            'cookies' => $this->buildCookiesForRequest(),
        );

	    return $request;
    }

    private function getClientIp() {
        $ip = "";

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    private function buildCookiesForRequest() {
		//Build proper cookies for WP remote post
		$cookies = array();
		foreach ( $_COOKIE as $name => $value ) {
			$cookies[] = new \WP_Http_Cookie( array( 'name' => $name, 'value' => $value ) );
        }

        return $cookies;
    }

}
