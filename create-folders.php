<?php
/**
 * Creates folder assets in the CMS using the SOAP Web Services API
 * https://www.hannonhill.com/cascadecms/latest/developing-in-cascade/soap-web-services-api/index.html
 */

require "config.php";
echo '<h1>Create folders</h1>';

// Change these variables as needed
// --------------------------------
// CMS site name
$siteName = 'Redirects and Short URLs - cms.sitename.ca';
// Directory name/path within the site root folder (no leading slash)
$directory = 'short-urls/faculty-department/registrar';
// Array of folders. Format: ['system-name', 'Display name', 'Description']
$assets = [
	['system-name', 'Display Name', 'Description'],
	['system-name2', 'Display Name 2', ''],
];
// --------------------------------




/**
 * @param array folders Array of folder names
 * @param string directory Folder path in /short-urls to create block in
 * @param string siteName CMS site name
 * @param boolean testMode Enable test mode (output testing variables instead of sending CMS request)
 */
function createFolder($assets, $directory, $siteName, $testMode = false) {
	global $soapURL;
	global $username;
	global $password;

	echo "<p><strong>Site</strong>: $siteName</p>";
	echo "<p><strong>Directory</strong>: /$directory</p>";

	$client = new SoapClient 
	( 
		$soapURL, 
		array ('trace' => 1, 'location' => str_replace('?wsdl', '', $soapURL)) 
	);	
	$auth = array ('username' => 'admin', 'password' => 'qAw3T3BEFw' );

	foreach($assets as $i=>$folder) {
		$content = array
		(
			'metadata' => [
				'displayName' => "$folder[1]",
				'metaDescription' => "$folder[2]"
			],
			'parentFolderPath' => "/$directory",
			'name' => "$folder[0]",
			'siteName' => "$siteName",
		);

		echo "<hr>$i ";
		if ($testMode) arrayDump($content);

		$asset = array('folder' => $content);
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
}

if (isset($_POST['submit'])) {
	echo '<input type="button" value="TEST NEW CONTENT" style="padding: 10px" onclick="window.location.href=window.location.href">';
	createFolder($assets, $directory, $siteName, false);
}
else {
	createFolder($assets, $directory, $siteName, true);
	echo "<hr>";
	echo '<form action="" method="post"><input type="submit" name="submit" value="SUBMIT TO CMS" style="padding: 10px"></form>';
}

?>