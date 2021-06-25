<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-debug.php');

class Ezoic_Integration_Cache_Debug implements iEzoic_Integration_Debug {
    public function GetDebugInformation() {
        $debug_data = array();

        $debug_data["config_file"] = $this->getConfigFileInfo();
        $debug_data["sub_htaccess"] = $this->getLowLevelHTACCESS();
        $debug_data["advanced_cache"] = $this->getAdvancedCacheFileInfo();
        $debug_data["main_htaccess"] = $this->getTopLevelHTACCESS();

        $request_params = array(
            //'cache_key' => $cache_key,
            'action' => 'get-index-series',
            'wp_debug_info' => print_r($debug_data,true),
            'content_url' => $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],
        );

        if(!empty($_GET)){
		    $request_params = array_merge($request_params, $_GET);
        }

        $settings = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://g.ezoic.net/wp/data.go',
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => array(
	            'X-Wordpress-Integration: true',
	            'X-Forwarded-For: ' => Ezoic_Integration_Request_Utils::GetClientIp(),
	            'Content-Type: application/x-www-form-urlencoded',
	            'Expect:',
            ),
            CURLOPT_POST => true,
            CURLOPT_HEADER => true,
            CURLOPT_POSTFIELDS => http_build_query($request_params)
        );

        Ezoic_Integration_Request_Utils::MakeCurlRequest($settings);

        return "<!-- debug information was stored -->";
    }

    public function WeShouldDebug() {
        if( isset($_GET["ez_store_wp_debug"]) && $_GET["ez_store_wp_debug"] == "1" ) {
			return true;
		}

        return false;
    }

    private function getConfigFileInfo() {
        $configFile = dirname(__FILE__) . "/config/ezoic_config.json";
        $configString = "";

        if( file_exists($configFile) && is_readable($configFile) ) {
            $cacheContent = file_get_contents($configFile);
            $content = json_decode($cacheContent, true);
            $configString .= print_r($content, true);
        }

        return $configString;
    }

    private function getAdvancedCacheFileInfo() {
        $current_dir = dirname(__FILE__);
        $directories = explode("/", $current_dir);
        //pop off three directories
        array_pop($directories);
        array_pop($directories);
        array_pop($directories);

        $wpContentDir = implode("/", $directories);
        $advancedCacheFile = $wpContentDir . "/advanced-cache.php";

        if (!file_exists($advancedCacheFile) || !is_readable($advancedCacheFile)) {
            return "Advanced Cache File does not exist ($advancedCacheFile)";
        }
        return file_get_contents($advancedCacheFile);
    }

    private function getTopLevelHTACCESS() {
        $current_dir = dirname(__FILE__);
        $directories = explode("/", $current_dir);
        //pop off four directories
        array_pop($directories);
        array_pop($directories);
        array_pop($directories);
        array_pop($directories);

        $wpDir = implode("/", $directories);
        $htaccessFile = $wpDir . "/.htaccess";

        if (!file_exists($htaccessFile) || !is_readable($htaccessFile)) {
            return "Top level htaccess does not exist ($htaccessFile)";
        }
        return file_get_contents($htaccessFile);
    }

    private function getLowLevelHTACCESS() {
        //Get path to cache folder and insert out htaccess file or modify current htaccess file
        $configFile = dirname(__FILE__) . "/config/ezoic_config.json";
        $content = file_get_contents($configFile);
        $configContent = json_decode($content, true);

	    $htaccessFile = $configContent['cache_path'] . '.htaccess';
	    if (!file_exists($htaccessFile) || !is_readable($htaccessFile)) {
            return "Low level htaccess does not exist (" . $htaccessFile . ")";
        }
        $content = file_get_contents($htaccessFile, true);

        return $content;
    }
}
