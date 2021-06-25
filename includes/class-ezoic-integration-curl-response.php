<?php
namespace Ezoic_Namespace;

require_once( dirname( __FILE__ ) . '/interface-ezoic-integration-response.php');

class Ezoic_Integration_CURL_Response implements iEzoic_Integration_Response {
    private $ezHeaders;

    public function HandleEzoicResponse( $final, $response ) {

        if( $response["error"] == NULL && 
            $response["status_code"] == 200  ) {
                $this->ezHeaders = $response["headers"];
                //$this->ezHeaders = wp_remote_retrieve_headers( $response );
                $this->alterResponseHeaders();
    
                //Replace final content with ezoic content
                if( is_array($response) && isset($response['body']) ) {
                    $final = $response['body'];
                } else {
                    $final = $response;
                }
        } else {
            if( $response["error"] != NULL ) {
                $final = $final . "<!-- " . $response["error"] . " -->";
            } elseif ($response["status_code"] != 200) {
                $final = $final . "<!-- " . $response["status_code"] . " -->";
            }
        }

        return $final;
    }

	/**
	 * Grabs the active template provided in the header.
	 */
	public function GetActiveTemplate( $response ) {
		if ($response["error"] == NULL && isset($response['headers']) && isset($response['headers']['x-wordpress-use-template'])) {
			return $response['headers']['x-wordpress-use-template'];
		}
		return '';
    }

    private function alterResponseHeaders() {
		if( !is_null($this->ezHeaders) && !headers_sent() ){

			$headers = array();

			if( is_array($this->ezHeaders) ) {
				$headers = $this->ezHeaders;
			} else {
				$headers = $this->ezHeaders->getAll();
			}

			foreach( $headers as $key => $header ) {
				//Avoid content encoding as this will cause rendering problems
				if( !$this->isBadHeader($key) ) {
					$this->handleHeaderObject($key, $header);
				}
			}
		}
	}
	
	private function isBadHeader($key) {
		return ($key == 'Content-Encoding' 
		    || $key == 'content-encoding'
			|| $key == 'Transfer-Encoding' 
			|| $key == 'transfer-encoding'
			|| $key == 'Content-Length' 
			|| $key == 'content-length'
			|| $key == 'Accept-Ranges' 
			|| $key == 'accept-ranges'
			|| $key == 'Status'
			|| $key == 'status');
	}

	private function handleHeaderObject($key, $header) {
		if( is_array($header) ) {
			foreach( $header as $subheader) {
				header($key . ': ' . $subheader, false);
			}
		} else {
			header($key . ': ' . $header);
		}
	}
}