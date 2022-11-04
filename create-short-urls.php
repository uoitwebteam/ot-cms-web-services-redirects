<?php 
/**
 * Creates short URL block assets in the CMS using the SOAP Web Services API
 * https://www.hannonhill.com/cascadecms/latest/developing-in-cascade/soap-web-services-api/index.html
 */

require "config.php";
echo '<h1>Create short urls</h1>';

// Change these variables as needed
// --------------------------------
// CMS site name
$siteName = 'Redirects and Short URLs - cms.sitename.ca';
// Directory name/path within the /short-urls folder (no leading slash)
$directory = 'TEST';
// Array of short URLS. Format: "shorturl https://target.url"
$assets = [
	"test1 https://ontariotechu.ca/index.php?utm_medium=medium&utm_source=source&utm_campaign=test1",
	"test2 https://ontariotechu.ca/index.php?utm_medium=medium&utm_source=source&utm_campaign=test2",
];
// Enable UTM tracking on all links
$trackAll = false;
// Optional expiration date
$metadata = [];
// $date = date_create('2022-12-31', timezone_open('America/Toronto'));
// $expirationDate = date_format($date, DATE_W3C);
// $metadata = ['endDate' => "$expirationDate"]; // '2023-12-31T05:00:00.000Z'
// --------------------------------




/**
 * @param array assets Array of short URLs
 * @param string directory Folder path in /short-urls to create block in
 * @param string siteName CMS site name
 * @param array metadata Metadata fields (IE, endDate)
 * @param boolean trackAll Enable UTM tracking on all links (use defaults if no existing parameters)
 * @param boolean testMode Enable test mode (output testing variables instead of sending CMS request)
 */
function createShort($assets, $directory, $siteName, $metadata, $trackAll = false, $testMode = false) {
	global $soapURL;
	global $username;
	global $password;
	global $mediums;
	global $sources;
	
	echo "<p><strong>Site</strong>: $siteName</p>";
	echo "<p><strong>Directory</strong>: /short-urls/$directory</p>";
	if ($metadata != null) echo "<p><strong>Expiration date</strong>: ".$metadata['endDate']."</p>";

	$client = new SoapClient 
	( 
		$soapURL, 
		array ('trace' => 1, 'location' => str_replace('?wsdl', '', $soapURL)) 
	);	
	$auth = array ('username' => "$username", 'password' => $password);

	foreach ($assets as $i=>$r) {
		$parts = explode(" ",$r);
		if (count($parts) == 2) {
			$nodes = [];

			// Defaults
			$typeNode = ['type' => 'text', 'identifier' => 'type', 'text' => "short"];
			array_push($nodes, $typeNode);
			$directiveNode = ['type' => 'text', 'identifier' => 'directive', 'text' => "Redirect"];
			array_push($nodes, $directiveNode);

			// Short URL
			$assets = $parts[0];
			$assetsNode = ['type' => 'text', 'identifier' => 'short', 'text' => "$assets"];
			array_push($nodes, $assetsNode);

			// Target
			$target = $parts[1];

			// Query parameters
			unset($utm);
			unset($utm_medium);
			unset($utm_source);
			unset($utm_campaign);
			if (strpos($parts[1],'?') !== false) {

				// Update target (without query string)
				$target = explode('?',$parts[1])[0];

				// UTM
				$utm = '::CONTENT-XML-CHECKBOX::Yes';
				$utmNode = ['type' => 'text', 'identifier' => 'utm', 'text' => "$utm"];
				array_push($nodes, $utmNode);
				
				$query = explode('?',$parts[1])[1];
				$queryParts = explode("&",$query);
				foreach($queryParts as $param) {
					if (strpos($param, "utm_medium") !== false) {
						$utm_medium = explode("=",$param)[1];
						if ($utm_medium == "mixed" || $utm_medium == "web") $utm_medium = "short-url";

						$mediumNode = ['type' => 'text', 'identifier' => 'utm_medium', 'text' => "$utm_medium"];
						array_push($nodes, $mediumNode);

						// For testing: check unique utm_mediums
						if (!in_array($utm_medium,$mediums)) array_push($mediums, $utm_medium);
					}
					elseif (strpos($param, "utm_source") !== false) {
						$utm_source = explode("=",$param)[1];
						if ($utm_source == "redirect") {
							// check medium
							$matches = [];
							preg_match("/utm_medium=(.+)&/",$query, $matches);
							// arrayDump($matches);
							$med = $matches[1];
							if ($med == "mixed") $utm_source = "mixed";
							elseif ($med == "web") $utm_source = "web";
							else $utm_source = "";
						}
						$sourceNode = ['type' => 'text', 'identifier' => 'utm_source', 'text' => "$utm_source"];
						array_push($nodes, $sourceNode);
						// For testing: check unique utm_mediums
						if (!in_array($utm_source,$sources)) array_push($sources, $utm_source);
					}
					elseif (strpos($param, "utm_campaign") !== false) {
						$utm_campaign = $assets;
						$campaignNode = ['type' => 'text', 'identifier' => 'utm_campaign', 'text' => "$utm_campaign"];
						array_push($nodes, $campaignNode);
						$utm_campaign = explode("=",$param)[1];
					}
					else {
						$utm_other = $param;
						$otherNode = ['type' => 'text', 'identifier' => 'utm-other', 'text' => "$utm_other"];
						array_push($nodes, $otherNode);
					}
				}
			}

			// Track all links (UTM defaults)
			if ($trackAll) {
				if (!isset($utm)) {
					$utm = '::CONTENT-XML-CHECKBOX::Yes';
					$utmNode = ['type' => 'text', 'identifier' => 'utm', 'text' => "$utm"];
					array_push($nodes, $utmNode);
				}
				if (!isset($utm_medium)) {
					$utm_medium = 'short-url';
					$mediumNode = ['type' => 'text', 'identifier' => 'utm_medium', 'text' => "$utm_medium"];
					array_push($nodes, $mediumNode);
				}

				if (!isset($utm_source)) {
					$utm_source = 'mixed';
					$sourceNode = ['type' => 'text', 'identifier' => 'utm_source', 'text' => "$utm_source"];
					array_push($nodes, $sourceNode);
				}

				if (!isset($utm_campaign)) {
					$utm_campaign = $assets;
					$campaignNode = ['type' => 'text', 'identifier' => 'utm_campaign', 'text' => "$utm_campaign"];
					array_push($nodes, $campaignNode);
				}
			}


			// Add target to $nodes
			$targetNode = ['type' => 'text', 'identifier' => 'url', 'text' => "$target"];
			array_push($nodes, $targetNode);


			// Asset name
			$alpha = preg_replace( '/[^a-z0-9\/\-_ ]/i', '', $assets);
			$name = str_replace("/","_",$alpha);

			$content = array(
				'structuredData' => [
					'definitionId' => 'f5c69d710a0000b3596e09fb45854285',
					'structuredDataNodes' => $nodes
				],
				'metadata' => $metadata,
				'expirationFolderPath' => "short-urls/_expired",
				'metadataSetPath' => '/Redirect expiration date',
				'parentFolderPath' => "/short-urls/$directory",
				'name' => "$name",
				'siteName' => "$siteName",
			);
			echo "<hr>$i $assets ";
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

$mediums = [];
$sources = [];

if (isset($_POST['submit'])) {
	echo '<input type="button" value="TEST NEW CONTENT" style="padding: 10px" onclick="window.location.href=window.location.href">';
	createShort($assets, $directory, $siteName, $metadata, $trackAll, false);
}
else {
	createShort($assets, $directory, $siteName, $metadata, $trackAll, true);
	echo "<hr>";
	echo "<p><strong>utm_mediums</strong></p>"; arrayDump($mediums);
	echo "<p><strong>utm_sources</strong></p>"; arrayDump($sources);
	echo '<form action="" method="post"><input type="submit" name="submit" value="SUBMIT TO CMS" style="padding: 10px"></form>';
}
