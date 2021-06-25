<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-content-collector.php');

class Ezoic_Integration_File_Content_Collector implements iEzoic_Integration_Content_Collector {
    private $configPath;

    public function __construct() {
        $this->configPath = dirname(__FILE__) . "/config/ezoic_config.json";
    }

    public function GetOrigContent() {
        $content = "";

        if( file_exists($this->configPath) && is_readable($this->configPath) ) {
            $cacheContent = file_get_contents($this->configPath);
            $configContent = json_decode($cacheContent, true);
            $fileName = "";
            if( $configContent["cache_identity"] == Ezoic_Cache_Identity::W3_TOTAL_CACHE ) {
                $fileName = "_index.html";
            } elseif ($configContent["cache_identity"] == Ezoic_Cache_Identity::WP_SUPER_CACHE || 
                      $configContent["cache_identity"] == Ezoic_Cache_Identity::WP_ROCKET_CACHE ) {
                $fileName = "index.html";
            }

	        $cachedFile = $configContent['cache_path'] . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . $fileName;
	        if ( ! file_exists( $cachedFile ) || ! is_readable( $cachedFile ) ) {
		        return '';
	        }

	        $content = file_get_contents( $cachedFile, true );
	        $content .= "<!-- grabbed from cache file -->";
        }

        return $content;
    }
}