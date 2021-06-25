<?php

/**
 * Global function includes
 */

//Needed for nginx servers
if (!function_exists('getallheaders')) {
	function getallheaders() {
		$headers = array();
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
			}
		}
		return $headers;
	}
}

if (!function_exists('is_ssl')) {
	function is_ssl() {
	        if ( isset( $_SERVER['HTTPS'] ) ) {
	                if ( 'on' == strtolower( $_SERVER['HTTPS'] ) ) {
	                        return true;
	                }
	
	                if ( '1' == $_SERVER['HTTPS'] ) {
	                        return true;
	                }
	        } elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
	                return true;
	        }
	        return false;
	}
}

//The global wp object strips ending slashes before exposing the request url path. This checks if it originally had it so we can add it back in 
if (!function_exists('shouldCurrentPathEndInSlash')) {
	function shouldCurrentPathEndInSlash() {
		$pathinfo         = isset( $_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : '';
		list( $pathinfo ) = explode( '?', $pathinfo );
		$pathinfo         = str_replace( '%', '%25', $pathinfo );
		list( $req_uri ) = explode( '?', $_SERVER['REQUEST_URI'] );
		$req_uri  = str_replace( $pathinfo, '', $req_uri );
		if (substr($req_uri,-1) == '/') {
			return true;
		}
		return false;
	}
}