<?php
namespace {
	include_once 'include-functions.php';
}

namespace Ezoic_Namespace {

	if ( ! defined( 'EZOIC_INTEGRATION_VERSION' ) ) {
		define( 'EZOIC_INTEGRATION_VERSION', '1.6.8' ); // update plugin version number
	}

	if ( ! defined( 'EZOIC_API_VERSION' ) ) {
		define( 'EZOIC_API_VERSION', '1.0.0' );
	}

	require_once( dirname( __FILE__ ) . '/ezoic-integration-classes.php' );

	if ( ! isset( $GLOBALS['EZOIC_CALL_COUNT'] ) ) {
		$GLOBALS['EZOIC_CALL_COUNT'] = 0;
	}

	class Ezoic_Integration_Factory {
		private $cacheIdentifier;
		private $cacheType;

		public function __construct() {
			$this->cacheIdentifier = new Ezoic_Integration_Cache_Identifier();
		}

		public function NewEzoicIntegrator( $cacheType ) {
			$this->cacheType             = $cacheType;
			$GLOBALS['EZOIC_CALL_COUNT'] += 1;
			if ( $cacheType != Ezoic_Cache_Type::NO_CACHE ) {
				ob_start();
				//echo "we are caching";
			} else {
				//echo "we are not caching";
			}

			return new Ezoic_Integrator(
				$this->NewEzoicRequest(),
				$this->NewEzoicResponse(),
				$this->NewEzoicContentCollector(),
				$this->NewEzoicFilter(),
				$this->NewEzoicEndpoint(),
				$this->NewEzoicDebug(),
				$this->NewEzoicCache()
			);
		}

		private function NewEzoicRequest() {
			global $wp;
			if ( $this->cacheType != Ezoic_Cache_Type::NO_CACHE ) {
				//echo "we are curl request";
				return new Ezoic_Integration_CURL_Request();
			}

			return new Ezoic_Integration_WP_Request();
		}

		private function NewEzoicResponse() {
			if ( $this->cacheType != Ezoic_Cache_Type::NO_CACHE ) {
				//echo "we are curl response";
				return new Ezoic_Integration_CURL_Response();
			}

			return new Ezoic_Integration_WP_Response();
		}

		private function NewEzoicFilter() {

			$isDebug = isset($_GET["ez_wp_debug"]) && $_GET["ez_wp_debug"] == "1";

			if ( $this->cacheType != Ezoic_Cache_Type::NO_CACHE ) {
				//echo "we are cache filter";
				return new Ezoic_Integration_Cache_Filter( getallheaders(), $isDebug );
			}

			return new Ezoic_Integration_WP_Filter( getallheaders(), $isDebug );
		}

		private function NewEzoicContentCollector() {
			if ( $this->cacheType == Ezoic_Cache_Type::HTACCESS_CACHE ) {
				//echo "we are file collecting";
				return new Ezoic_Integration_File_Content_Collector();
			}

			return new Ezoic_Integration_Buffer_Content_Collector();
		}

		private function NewEzoicDebug() {
			if ( $this->cacheType != Ezoic_Cache_Type::NO_CACHE ) {
				//echo "we are cache debug";
				return new Ezoic_Integration_Cache_Debug();
			}

			return new Ezoic_Integration_WP_Debug($this->cacheType);
		}

		private function NewEzoicEndpoint() {
			//Always use file based routes since
			//Some database access stuff is broken on certain
			//domains
			return new Ezoic_Integration_Cache_Endpoints();
		}

		public function NewEzoicCache() {
			return new Ezoic_Integration_Cache;
		}
	}

}
