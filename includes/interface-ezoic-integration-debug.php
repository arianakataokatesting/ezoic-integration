<?php
namespace Ezoic_Namespace;

interface iEzoic_Integration_Debug {
    public function GetDebugInformation();
    public function WeShouldDebug();
}