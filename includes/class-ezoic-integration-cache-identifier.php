<?php
namespace Ezoic_Namespace;

class Ezoic_Cache_Identity {
    const WP_SUPER_CACHE = 0;
    const W3_TOTAL_CACHE = 1;
    const UNKNOWN_CACHE = 2;
    const WP_ROCKET_CACHE = 3;
}

class Ezoic_Cache_Type {
    const HTACCESS_CACHE = 0;
    const PHP_CACHE = 1;
    const NO_CACHE = 2;
}

class Ezoic_Integration_Cache_Identifier {

    private $cacheMethod;
    private $cacheIdentity;
    private $cachePath;
    private $configPath;

    public function __construct() {
        $this->cacheIdentity = $this->determineCacheIdentity();
        $this->cacheMethod = $this->determineCacheMethod();
        $this->cachePath = $this->determineCacheAbsolutePath();
        $this->configPath = dirname(__FILE__) . "/config/ezoic_config.json";
    }

    public function GetCacheAbsolutePath() {
        return $this->cachePath;
    }

    public function ActivateCacheWorkaround() {
        if( $this->cacheMethod == Ezoic_Cache_Type::HTACCESS_CACHE ) {
            $this->generateHTACCESSFile();
        }

        if( $this->cacheMethod == Ezoic_Cache_Type::PHP_CACHE ) {
            $this->modifyWPSettings();
        }
    }

    public function DeactivateCacheWorkaround() {
        if( $this->cacheMethod == Ezoic_Cache_Type::HTACCESS_CACHE ) {
            $this->removeHTACCESSFile();
        }

        if( $this->cacheMethod == Ezoic_Cache_Type::PHP_CACHE ) {
            $this->restoreWPSettings();
        }
    }

    public function GetCacheIdentity() {
        return $this->cacheIdentity;
    }

    public function GetCacheType() {
        return $this->cacheMethod;
    }

    public function GetConfig() {
        if( file_exists($this->configPath) && is_readable($this->configPath) ) {
            $cacheContent = file_get_contents($this->configPath);
            $content = json_decode($cacheContent, true);
            if(is_array($content)) {
                return $content;
            }
        }

        return array();
    }

    private function determineCacheMethod() {
        if( $this->cacheIdentity == Ezoic_Cache_Identity::WP_SUPER_CACHE ) {
            return $this->determineWPSCCacheType();
        }

        if( $this->cacheIdentity == Ezoic_Cache_Identity::W3_TOTAL_CACHE ) {
            return $this->determineW3TCCacheType();
        }

        if( $this->cacheIdentity == Ezoic_Cache_Identity::WP_ROCKET_CACHE ) {
            return $this->determineWPRocketCacheType();
        }

        return Ezoic_Cache_Type::NO_CACHE;
    }

    private function determineWPRocketCacheType() {
        //We will attempt to modifiy the sub htaccess file anyway,
        //so lets just call it htaccess
        return Ezoic_Cache_Type::HTACCESS_CACHE;
    }

    private function determineW3TCCacheType() {
        $filename = WP_CONTENT_DIR . "/w3tc-config/master.php";
        if ( file_exists( $filename ) && is_readable( $filename ) ) {
            //Grab our config file and remove first 14 characters since it's php code
            $content = file_get_contents( $filename );
            $content = substr($content, 14);
            $config = json_decode( $content , true );
            if ( is_array( $config ) ) {
                if( isset($config["pgcache.enabled"]) ) {
                    if( $config["pgcache.enabled"] == 1 && !defined('WPINC') ) {
                        return Ezoic_Cache_Type::HTACCESS_CACHE;
                    } else {
                        return Ezoic_Cache_Type::PHP_CACHE;
                    }
                }
            }
        }

        return Ezoic_Cache_Type::PHP_CACHE;
    }

    private function determineWPSCCacheType() {
        global $wp_cache_mod_rewrite;

        if( isset( $wp_cache_mod_rewrite ) ) {
            if( $wp_cache_mod_rewrite == 1 ) {
                return Ezoic_Cache_Type::HTACCESS_CACHE;
            } else {
                return Ezoic_Cache_Type::PHP_CACHE;
            }
        }

        return Ezoic_Cache_Type::PHP_CACHE;
    }

    private function determineCacheIdentity() {

        if( $this->isWPSuperCache() ) {
            return Ezoic_Cache_Identity::WP_SUPER_CACHE;
        }

        if( $this->isW3TotalCache() ) {
            return Ezoic_Cache_Identity::W3_TOTAL_CACHE;
        }
        
        if( $this->isWPRocketCache() ) {
            return Ezoic_Cache_Identity::WP_ROCKET_CACHE;
        }

        return Ezoic_Cache_Identity::UNKNOWN_CACHE;
    }

    private function isWPSuperCache() {
        return  function_exists( 'wp_cache_set_home' );
    }

    private function isW3TotalCache() {
        $filename = WP_CONTENT_DIR . "/w3tc-config/master.php";
        return defined( 'W3TC' ) && defined( 'W3TC_DIR' ) && file_exists( $filename );
    }

    private function isWPRocketCache() {
        return file_exists( WP_CONTENT_DIR . '/plugins/wp-rocket/inc/front/process.php' ) && 
               file_exists( WP_CONTENT_DIR . '/plugins/wp-rocket/vendor/autoload.php' ) &&
               defined( 'WP_ROCKET_ADVANCED_CACHE' );
    }

    private function determineCacheAbsolutePath() {
        if( $this->cacheIdentity == Ezoic_Cache_Identity::WP_SUPER_CACHE ) {
            global $cache_path;

            if( is_string($cache_path) ) {
                return $cache_path . 'supercache/';
            }
        }

        if( $this->cacheIdentity == Ezoic_Cache_Identity::W3_TOTAL_CACHE ) {
            return WP_CONTENT_DIR . '/cache/page_enhanced/';
        }

        if( $this->cacheIdentity == Ezoic_Cache_Identity::WP_ROCKET_CACHE ) {
            return WP_CONTENT_DIR . '/cache/wp-rocket/';
        }

        return "";
    }

    public function GenerateHTACCESSFile() {

        //Get path to cache folder and insert out htaccess file or modify current htaccess file
        $filePath = $this->determineCacheAbsolutePath() . ".htaccess";

	    if(file_exists($filePath) && !is_writable($filePath)) {
		    //wp_die( 'Ezoic Integration not activated due to htaccess permissions: ' . $filePath );
		    return;
	    }

	    // verify cache handler file exists
	    if(!file_exists( WP_CONTENT_DIR . '/plugins' . '/' . EZOIC__PLUGIN_SLUG . '/ezoic-cache-handle/ezoic-handle-cache.php') ) {
	    	self::RemoveHTACCESSFile();
		    return;
        }

        //Make sure we start clean
        self::RemoveHTACCESSFile();

	    $content = '';
	    if ( file_exists( $filePath ) ) {
		    $content = file_get_contents( $filePath );
	    }

        $lineContent = preg_split("/\r\n|\n|\r/", $content);

        $ezoicContent = array("#BEGIN_EZOIC_INTEGRATION_HTACCESS_CACHE_HANDLER",
                            '<IfModule mod_rewrite.c>',
                            '   RewriteEngine On',
                            '   RewriteRule .* "/wp-content/plugins/' . EZOIC__PLUGIN_SLUG . '/ezoic-cache-handle/ezoic-handle-cache.php" [L]',
                            '</IfModule>',
                            "#END_EZOIC_INTEGRATION_HTACCESS_CACHE_HANDLER");

        $finalLineContent = array_merge($ezoicContent, $lineContent);
        $modifiedContent = implode("\n", $finalLineContent);

	    $success = file_put_contents($filePath, $modifiedContent);
        if( !$success ) {
            echo "We failed to modify our HTACCESS file.";
	    }
    }

    public function RemoveHTACCESSFile() {
        //Get path to cache folder and din htaccess file,
        //see if we are the only code in the file and then remove it
        $filePath = $this->determineCacheAbsolutePath() . ".htaccess";

        if(empty($filePath) || !file_exists($filePath) || !is_writable($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $lineContent = preg_split("/\r\n|\n|\r/", $content);
        //Find all text between #EZOIC_INTEGRATION_MODIFICATION
        $beginEzoicContent = 0;
        $endEzoicContent = 0;
        $foundStart = false;
        foreach( $lineContent as $key => $value ) {
            if( $value == "#BEGIN_EZOIC_INTEGRATION_HTACCESS_CACHE_HANDLER" ) {
                $beginEzoicContent = $key;
                $foundStart = true;
            } elseif ( $value == "#END_EZOIC_INTEGRATION_HTACCESS_CACHE_HANDLER") {
                $endEzoicContent = $key;
            }
        }

        // If we never found the starting comment block return early.
        if(!$foundStart) {
            return;
        }

        for( $i = $beginEzoicContent; $i <= $endEzoicContent; $i++ ) {
            unset($lineContent[$i]);
        }

        $modifiedContent = implode("\n", $lineContent);
        //Dump out to advanced cache file
        file_put_contents($filePath, $modifiedContent);
    }

    public function ModifyAdvancedCache() {
        //get advanced cache file
        $advancedPath = "";
        if( $this->cacheIdentity == Ezoic_Cache_Identity::W3_TOTAL_CACHE ) {
            $advancedPath = "/plugins/w3-total-cache/wp-content";
        }

        $filePath = WP_CONTENT_DIR . $advancedPath . '/advanced-cache.php';
	    if(!file_exists($filePath) || !is_writable($filePath)) {
	    	return;
	    }

	    // verify factory file exists
	    if(!file_exists(WP_CONTENT_DIR . '/plugins' . '/' . EZOIC__PLUGIN_SLUG. '/includes/class-ezoic-integration-factory.php') ) {
		    self::RestoreAdvancedCache();
		    return;
        }

        //Make sure we start clean
        self::RestoreAdvancedCache();

        $content = file_get_contents($filePath);
        $lineContent = preg_split("/\r\n|\n|\r/", $content);

        //Insert our ezoic middleware code
        $ezoicContent = array("#BEGIN_EZOIC_INTEGRATION_PHP_CACHE_HANDLER",
            '$ezoic_factory_file = WP_CONTENT_DIR . \'/plugins\' . \'/' . EZOIC__PLUGIN_SLUG. '/includes/class-ezoic-integration-factory.php\';',
            'if ( false == strpos( $_SERVER[\'REQUEST_URI\'], \'wp-admin\' ) && file_exists( $ezoic_factory_file ) ) {',
            '   require_once($ezoic_factory_file);',
            '   $ezoic_factory = new Ezoic_Namespace\Ezoic_Integration_Factory();',
            '   $ezoic_integrator = $ezoic_factory->NewEzoicIntegrator(Ezoic_Namespace\Ezoic_Cache_Type::PHP_CACHE);' ,
            '   register_shutdown_function(array($ezoic_integrator, "ApplyEzoicMiddleware"));' ,
            "}",
            "#END_EZOIC_INTEGRATION_PHP_CACHE_HANDLER" );

        array_splice($lineContent, 1, 0, $ezoicContent);
        $modifiedContent = implode("\n", $lineContent);

        //Dump out to advanced cache file
        $success = file_put_contents($filePath, $modifiedContent);
        if( !$success ) {
            echo "We failed to modify our advanced Cache file.";
        }
    }

    public function RestoreAdvancedCache() {
        //get advanced cache file
        $advancedPath = "";
        if( $this->cacheIdentity == Ezoic_Cache_Identity::W3_TOTAL_CACHE ) {
            $advancedPath = "/plugins/w3-total-cache/wp-content";
        }

        $filePath = WP_CONTENT_DIR . $advancedPath . '/advanced-cache.php';
	    if(!file_exists($filePath) || !is_writable($filePath)) {
		    return;
	    }

        $content = file_get_contents($filePath);
        $lineContent = preg_split("/\r\n|\n|\r/", $content);
        //Find all text between #EZOIC_INTEGRATION_MODIFICATION
        $beginEzoicContent = 0;
        $endEzoicContent = 0;
        foreach( $lineContent as $key => $value ) {
            if( $value == "#BEGIN_EZOIC_INTEGRATION_PHP_CACHE_HANDLER" ) {
                $beginEzoicContent = $key;
            } elseif ( $value == "#END_EZOIC_INTEGRATION_PHP_CACHE_HANDLER") {
                $endEzoicContent = $key;
            }
        }

        if($beginEzoicContent == 0 ) {
            //No modification need 0th line should be php declaration
            return;
        }

        for( $i = $beginEzoicContent; $i <= $endEzoicContent; $i++ ) {
            unset($lineContent[$i]);
        }

        $modifiedContent = implode("\n", $lineContent);
        //Dump out to advanced cache file
        file_put_contents($filePath, $modifiedContent);
    }

	public function GenerateConfig() {
		$cacheContent = array( "cache_path" => $this->cachePath, "cache_identity" => $this->cacheIdentity );
		$fileContents = json_encode($cacheContent);

		if(!file_exists($this->configPath) || !is_writable($this->configPath)) {
			return;
		}

		if ( ( $handle = fopen( $this->configPath, 'w' ) ) !== false ) {
			fwrite( $handle, $fileContents );
			fclose( $handle );
		}
	}

	public function modifyWPSettings() {

	}

	public function restoreWPSettings() {

	}

}
