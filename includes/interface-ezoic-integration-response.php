<?php
namespace Ezoic_Namespace;

interface iEzoic_Integration_Response {
    public function HandleEzoicResponse( $final, $response);
    public function GetActiveTemplate( $response );
}