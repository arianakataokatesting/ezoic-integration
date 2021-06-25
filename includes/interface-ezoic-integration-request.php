<?php
namespace Ezoic_Namespace;

interface iEzoic_Integration_Request {
    public function GetContentResponseFromEzoic( $final_content, $available_templates = array() );
}