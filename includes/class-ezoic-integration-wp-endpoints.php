<?php
namespace {
	include_once 'include-functions.php';
}

namespace Ezoic_Namespace {

	require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-endpoints.php' );

	/**
	 * Used for grabbing endpoints from the database or from ezoic servers.
	 *
	 * This class defines all code necessary to run during the plugin's activation.
	 *
	 * @since      1.0.0
	 * @package    Ezoic_Integration
	 * @subpackage Ezoic_Integration/includes
	 * @author     Ezoic Inc. <support@ezoic.com>
	 */
	class Ezoic_Integration_WP_Endpoints implements iEzoic_Integration_Endpoints {

		private $endpoints;
		private $tableName;
		private $cacheTime;
		private $request_url;
		private $current_endpoint;
		private $protocol;

		public function __construct() {
			global $wpdb;

			$this->tableName = $wpdb->prefix . "ezoic_endpoints";

			//Cache endpoints for 24hours
			$this->cacheTime = 86400;

			$this->protocol    = isset( $_SERVER["HTTPS"] ) ? 'https' : 'http';
			$this->request_url = "https://g.ezoic.net/wp/endpoints.go";
		}

		public function GetTableCreateStatement() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$createStatement = "CREATE TABLE " . $this->tableName . " (
            cachetime datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            endpoint varchar(80) NOT NULL,
            PRIMARY KEY  (endpoint)
          ) " . $charset_collate . ";";

			return $createStatement;
		}

		public function GetTableVersion() {
			return "1.0.0";
		}

		public function BustEndpointCache() {
			global $wpdb;
			$delete = $wpdb->query( "TRUNCATE TABLE {$this->tableName}" );
		}

		public function IsEzoicEndpoint() {
			global $wp;

			$request_path = '';

			if ( $_SERVER['QUERY_STRING'] == null ) {
				$request_path = $_SERVER['SCRIPT_NAME'];
			} else {
				$request_path = $_SERVER['QUERY_STRING'];
			}

			$current_url = "{$this->protocol}://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

			//Make sure we have our endpoints available eh?
			$this->getEndpoints();

			if ( is_array( $this->endpoints ) ) {
				foreach ( $this->endpoints as $endpoint ) {
					$matches = array();
					if ( preg_match( '/(' . preg_quote( $endpoint, '/' ) . '.*)/', $current_url, $matches ) ) {
						if ( isset( $matches[0] ) ) {
							$this->current_endpoint = str_replace( "/?", "?", $matches[0] );
						} else {
							$this->current_endpoint = $endpoint;
						}

						return true;
					}
				}
			}


			return false;
		}

		public function GetEndpointAsset() {
			global $wp;

			$ip = "";

			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				//to check ip is pass from proxy
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			//set endpoint headers
			$endpoint_headers                    = $this->parseEndpointHeaders();
			$endpoint_headers['Referer']        = "{$this->protocol}://" . $_SERVER["HTTP_HOST"];
			$endpoint_headers['X-Forwarded-for'] = $ip;
			$endpoint_headers['X-Wordpress-Integration'] = "true";

			//create endpoint request
			$request = array(
				'timeout' => 5,
				'headers' => $endpoint_headers,
			);

			$response = wp_remote_get( "https://g.ezoic.net" . $this->current_endpoint, $request );

			if ( is_array( $response ) && isset( $response['body'] ) ) {
				$final   = $response['body'];
				$headers = $response['headers'];
				if ( ! empty( $headers ) ) {
					status_header( 200 );
					if ( is_array( $headers ) || is_object( $headers ) ) {
						foreach ( $headers as $key => $header ) {
							$this->handleHeaderObject( $key, $header );
						}
					} else {
						header( "Content-type: {$headers}" );
					}
				}

				//a hack to fix bad cache-control header with anchorfix.js
				$clean_endpoint = preg_replace('/\?.*/', '', trim($this->current_endpoint));
				if ($clean_endpoint == '/ezoic/anchorfix.js')
				{
					header('Cache-Control: public, max-age=86400');
				}

				return $response['body'];
			}

			return "";
		}

		private function getEndpoints() {
			$result = $this->getEndpointsFromDatabase();

			if ( $result == false ) {
				$this->getEndpointsFromServer();
				$this->storeEndpointsToDatabase();
			}
		}

		private function getEndpointsFromDatabase() {
			global $wpdb;
			$results = $wpdb->get_results( "SELECT * FROM {$this->tableName}", OBJECT );

			if ( count( $results ) == 0 ) {
				return false;
			}

			$time_now = strtotime( date( "Y-m-d H:i:s" ) );

			foreach ( $results as $result ) {
				if ( ( $time_now - strtotime( $result->cachetime ) ) > $this->cacheTime ) {
					return false;
				}
				$this->endpoints[] = $result->endpoint;
			}

			return true;
		}

		private function getEndpointsFromServer() {
			$result = wp_remote_get( $this->request_url, array() );

			$this->endpoints = array();

			$ez_data = json_decode( $result["body"] );
			if ( $ez_data->result === "true" ) {
				foreach ( $ez_data->endpoints as $endpoint ) {
					$this->endpoints[] = $endpoint;
				}
			}
		}

		private function storeEndpointsToDatabase() {
			global $wpdb;

			if ( ! is_array( $this->endpoints ) || count( $this->endpoints ) == 0 ) {
				//Bad data don't do anything
				return;
			}

			$data         = array();
			$values       = array();
			$query        = "INSERT INTO $this->tableName (cachetime, endpoint) VALUES ";
			$current_date = date( "Y-m-d H:i:s" );

			foreach ( $this->endpoints as $endpoint ) {
				$values[] = "(%s,%s)";
				$data[]   = $current_date;
				$data[]   = $endpoint;
			}

			$values_string = implode( " , ", $values );
			$query         = $query . $values_string . " ON DUPLICATE KEY UPDATE cachetime = '{$current_date}'";


			$wpdb->query(
				$wpdb->prepare( $query, $data )
			);

			$wpdb->print_error();
		}

		private function parseEndpointHeaders() {

			$headers = getallheaders();

			if ( is_array( $headers ) ) {
				foreach ( $headers as $key => $header ) {
					// remove headers that should not be passed through
					if ( $this->isBadHeader( $key ) ) {
						unset($headers[$key]);
					}
				}
			}
			return $headers;
		}

		private function isBadHeader( $key ) {
			return ( $key == 'Accept-Encoding'
			         || $key == 'accept-encoding'
			         || $key == 'Connection'
			         || $key == 'connection'
			         || $key == 'Range'
			         || $key == 'range'
			         || $key == 'Host'
							 || $key == 'host' 
							 || $key == 'cache-control'
							 || $key == 'Cache-Control');
		}

		private function handleHeaderObject( $key, $header ) {
			if ( is_array( $header ) ) {
				foreach ( $header as $subheader ) {
					header( $key . ': ' . $subheader, false );
				}
			} else {
				header( $key . ': ' . $header );
			}
		}

	}

}