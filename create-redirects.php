<?php 
/**
 * Creates redirect URL block assets in the CMS using the SOAP Web Services API
 * https://www.hannonhill.com/cascadecms/latest/developing-in-cascade/soap-web-services-api/index.html
 */

require "config.php";
echo '<h1>Create redirects</h1>';

// Change these variables as needed
// --------------------------------
// CMS site name
$siteName = 'Redirects and Short URLs - cms.sitename.ca';
// Directory name/path within the /redirects folder (no leading slash)
$directory = 'TEST';
// Array of redirects. Format: "Directive status /request https://target.url"
$assets = [
	"Redirect 301 /test1/index.php https://ontariotechu.ca/index.php",
	"Redirect 301 /test2/index.php https://ontariotechu.ca/index.php",
];
// Expiration date - minimum of one year from today
$date = date_create('2023-12-31', timezone_open('America/Toronto'));
$expirationDate = date_format($date, DATE_W3C); // '2022-12-31T05:00:00.000Z';
// --------------------------------



/**
 * Creates redirect block in the CMS
 * @param array redirects Array of redirects
 * @param string directory Folder path in /redirects to create block in
 * @param string siteName CMS site name
 * @param string expirationDate Block expiration date
 * @param boolean testMode Enable test mode (output testing variables instead of sending CMS request)
 */
function createRedirect($assets, $directory, $siteName, $expirationDate, $testMode = false) {
	global $soapURL;
	global $username;
	global $password;

	echo "<p><strong>Site</strong>: $siteName</p>";
	echo "<p><strong>Directory</strong>: /redirects/$directory</p>";
	echo "<p><strong>Expiration date</strong>: /$expirationDate</p>";
	
	$client = new SoapClient 
	( 
		$soapURL, 
		array ('trace' => 1, 'location' => str_replace('?wsdl', '', $soapURL)) 
	);	
	$auth = array ('username' => "$username", 'password' => $password);

	foreach ($assets as $i=>$r) {
		$parts = explode(" ",$r);
		if (count($parts) == 4) {
			$nodes = [];

			// Directive
			$directive = $parts[0];
			$directiveNode = ['type' => 'text', 'identifier' => 'directive', 'text' => "$directive"];
			array_push($nodes, $directiveNode);

			// Status
			$status = $parts[1];
			$statusNode = ['type' => 'text', 'identifier' => 'type', 'text' => "$status"];
			array_push($nodes, $statusNode);

			// Request
			$request = $parts[2];
			$requestNode = ['type' => 'text', 'identifier' => 'request', 	'text' => "$request"];
			array_push($nodes, $requestNode);

			// Target
			$target = $parts[3];
			
			// Query parameters
			if (strpos($parts[3],'?') !== false) {
				// Update target (without query string)
				$target = explode('?',$parts[3])[0];

				// UTM
				$utm = '::CONTENT-XML-CHECKBOX::Yes';
				$utmNode = ['type' => 'text', 'identifier' => 'utm', 'text' => "$utm"];
				array_push($nodes, $utmNode);

				$query = explode('?',$parts[3])[1];
				$queryParts = explode("&",$query);
				foreach($queryParts as $param) {
					if (strpos($param, "utm_medium") !== false) {
						$utm_medium = explode("=",$param)[1];
						$mediumNode = ['type' => 'text', 'identifier' => 'utm_medium', 'text' => "$utm_medium"];
						array_push($nodes, $mediumNode);
					}
					elseif (strpos($param, "utm_source") !== false) {
						$utm_source = explode("=",$param)[1];
						$sourceNode = ['type' => 'text', 'identifier' => 'utm_source', 'text' => "$utm_source"];
						array_push($nodes, $sourceNode);
					}
					elseif (strpos($param, "utm_campaign") !== false) {
						$utm_campaign = explode("=",$param)[1];
						$campaignNode = ['type' => 'text', 'identifier' => 'utm_campaign', 'text' => "$utm_campaign"];
						array_push($nodes, $campaignNode);
					}
					else {
						$utm_other = $param;
						$otherNode = ['type' => 'text', 'identifier' => 'utm-other', 'text' => "$utm_other"];
						array_push($nodes, $otherNode);
					}
				}
			}
			
			// Add target to $nodes
			$targetNode = ['type' => 'text', 'identifier' => 'url', 'text' => "$target"];
			array_push($nodes, $targetNode);

			// Asset name
			$leadingSlash = substr_replace($request,"",0,1);
			$alpha = preg_replace( '/[^a-z0-9\/\-_ ]/i', '', $leadingSlash);
			$name = str_replace("/","_",$alpha);

			$content = array(
				'structuredData' => [
					'definitionId' => 'f5c69d710a0000b3596e09fb45854285',
					'structuredDataNodes' => $nodes
				],
				'metadata' => [
					'endDate' => $expirationDate
				],
				'expirationFolderPath' => "redirects/_expired",
				'metadataSetPath' => '/Redirect expiration date',
				'parentFolderPath' => "/redirects/$directory",
				'name' => "$name",
				'siteName' => "$siteName",
			);
			echo "<hr>$i $request ";
			if ($testMode) arrayDump($content);

			$asset = array('xhtmlDataDefinitionBlock' => $content);
			$createParams = array ('authentication' => $auth, 'asset' => $asset); 

			if (!$testMode) {
				$reply = $client->create($createParams); 

				if ($reply->createReturn->success=='true') {
					echo "<br><span style='color: green'>Success. Created asset's id is " . $reply->createReturn->createdAssetId."</span>";
				} 
				else {
					echo "<br><span style='color: red'>Error occurred: " . $reply->createReturn->message."</span>";
				}
			}
		} 
		else {
			echo "<br><span style='color: red'>Error parsing redirect";
			exit;
		}
	}
}

if (isset($_POST['submit'])) {
	echo '<input type="button" value="TEST NEW CONTENT" style="padding: 10px" onclick="window.location.href=window.location.href">';
	createRedirect($assets, $directory, $siteName, $expirationDate, false);
}
else {
	createRedirect($assets, $directory, $siteName, $expirationDate, true);
	echo "<hr>";
	echo '<form action="" method="post"><input type="submit" name="submit" value="SUBMIT TO CMS" style="padding: 10px"></form>';
}