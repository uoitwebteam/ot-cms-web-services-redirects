<?php 
// Development/error reporting
ini_set('display_errors',1);
error_reporting(E_ALL);
if (!function_exists('arrayDump')) {
	function arrayDump($array) {
		echo '<pre>',var_dump($array),'</pre>';
	}
}

$soapURL = "https://ontariotechu.cascadecms.com/ws/services/AssetOperationService?wsdl";
$username = "";
$password = "";