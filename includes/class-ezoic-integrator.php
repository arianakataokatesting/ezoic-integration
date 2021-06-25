<?php
namespace Ezoic_Namespace;

class Ezoic_Integrator {
    private $ezRequest;
    private $ezResponse;
    private $ezContentCollector;
    private $ezFilter;
    private $ezEndpoints;
    private $ezDebug;

    private $ezBustEndpointCacheParam = "ez_bust_wp_endpoint_cache";

    public function __construct(iEzoic_Integration_Request $request,
                                iEzoic_Integration_Response $response,
                                iEzoic_Integration_Content_Collector $contentCollector,
                                iEzoic_Integration_Filter $filter,
                                iEzoic_Integration_Endpoints $endpoints,
                                iEzoic_Integration_Debug $debug,
                                iEzoic_Integration_Cache $cache ) {
        $this->ezRequest = $request;
        $this->ezResponse = $response;
        $this->ezContentCollector = $contentCollector;
        $this->ezFilter = $filter;
        $this->ezEndpoints = $endpoints;
        $this->ezDebug = $debug;
        $this->ezCache = $cache;
    }

    public function ApplyEzoicMiddleware() {
		//Get Orig Content
        $orig_content = $this->ezContentCollector->GetOrigContent();
        
        if( isset($_GET[$this->ezBustEndpointCacheParam]) && $_GET[$this->ezBustEndpointCacheParam] == "1" ) {
			$this->ezEndpoints->BustEndpointCache();
		}

	    if( $this->ezFilter->WeShouldReturnOrig() ) {
            if ( $this->ezDebug->WeShouldDebug() ) {
                $orig_content .= $this->ezDebug->GetDebugInformation();
            }
	    	//Do nothing this should just return our final content
        } elseif( $this->ezEndpoints->IsEzoicEndpoint() ) {
            $orig_content = $this->ezEndpoints->GetEndpointAsset();
        } elseif( $this->ezDebug->WeShouldDebug() ) {
			$orig_content .= $this->ezDebug->GetDebugInformation();
		} else {

            // Only run the caching logic if EZOIC_CACHE is set in wp-config.php.
            if (defined('EZOIC_CACHE') && EZOIC_CACHE && $this->ezCache->IsCacheable()) {

                // Get the available templates that we currently have cached.
                $available_templates = $this->ezCache->GetAvailableTemplates();

                // Send the page content to sol along with the available templates we have. If sol wants us to
                // use one of our available templates, it will not do any processing and return a header specifying
                // which template to use.
                $response = $this->ezRequest->GetContentResponseFromEzoic( $orig_content, $available_templates );
                $active_template = $this->ezResponse->GetActiveTemplate( $response );

                // Check to see if we have the active template cached. If we do, just set the orig content as that.
                // If not, process the content sent back from sol as the new orig_content and then cache the content.
                if ( $this->ezCache->IsCached($active_template)) {
                    $orig_content = $this->ezCache->GetPage($active_template);
                } else {
                    $orig_content = $this->ezResponse->HandleEzoicResponse( $orig_content, $response );
                    $this->ezCache->SetPage($active_template, $orig_content);
                }

            } else {
                $response = $this->ezRequest->GetContentResponseFromEzoic( $orig_content );
                $orig_content = $this->ezResponse->HandleEzoicResponse( $orig_content, $response );
            }
		}

        //Remove white space from front and back of html/xml content to prevent xml errors on map
	    echo trim($orig_content);
    }
}