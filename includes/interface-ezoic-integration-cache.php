<?php
namespace Ezoic_Namespace;

interface iEzoic_Integration_Cache {
    public function GetPage( $active_template );
    public function SetPage( $active_template, $content );
    public function IsCached( $active_template );
    public function IsCacheable();
}