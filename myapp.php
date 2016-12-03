<?
//Esempio d'uso del wrapper in PHP con autenticazione Oauth2
//Author: Mailforce
//Date: 2016-12-02 12:35:00

define(MF_ENDPOINT,'http://api.mailforcelab.it/');
define(MF_OAUTH_BASE_URI, 	MF_ENDPOINT.'oauth');
define(MF_OAUTH_TOKEN_URI,	MF_ENDPOINT.'token');
define(MF_API_BASE_URI,		MF_ENDPOINT.'v1/');

require_once('mf_api.php');

$mfclient = new MF_REST('oauthapp1');
$mfclient->setApplicationName("App di esempio");
$mfclient->setClientId('testclient');
$mfclient->setClientSecret('testpass');
$mfclient->setRedirectUri('http://beatrice.mailforcelab.it/mf3_2develop/oauth2/app/myapp.php');
$mfclient->setScopes('');	

echo '<hr><a href="?logout">Logout</a>';
	

if ( isset($_GET['logout']) ) {
	unset($_SESSION['access_token']);
	unset($_SESSION['refresh_token']);
	$redirect = "http://".$_SERVER['HTTP_HOST']. $_SERVER['PHP_SELF'];
	Header('Location: '.filter_var($redirect, FILTER_SANITIZE_URL));
	exit;
}
if ( isset($_GET['code']) ) {
	$code 		= $_GET['code'];
	$tokenObj 	= $mfclient->exchange_token($code);
	$mfclient->setToken($tokenObj->access_token,$tokenObj->refresh_token);
	$redirect = "http://".$_SERVER['HTTP_HOST']. $_SERVER['PHP_SELF'];
	Header('Location: '.filter_var($redirect, FILTER_SANITIZE_URL));
	exit;
}

if ( ! $mfclient->getAccessToken() ) {
	Header('Location: '.$mfclient->authorize_url());
	exit;
} 

if (  $mfclient->getAccessToken() ) {
	$mfclient->setToken($_SESSION['access_token'],$_SESSION['refresh_token']);

	$mflists = new MF_REST_Lists($mfclient);

	$listUID="a2f6319eadbe4666b1c9e4af64e53ra9";

	$result = $mflists->hardbounce('a2f6319eadbe4666b1c9e4af64e53ra9',11,0);
	if ($result->execute()) {
		echo '<textarea style="width:100%; height:200px">';
		print_r($result->response);
		echo "</textarea>";
	} else {
		echo $result->response->error;

		if ($result->response->error=="invalid_token") {
			$tokenObj = $mfclient->refreshToken();
			$mfclient->setToken($tokenObj->access_token);
			$redirect = "http://".$_SERVER['HTTP_HOST']. $_SERVER['PHP_SELF'];
			Header('Location: '.filter_var($redirect, FILTER_SANITIZE_URL));
			exit;
		}
	}

	echo "<hr>Token: ".$_SESSION['access_token'];
	echo '<hr><a href="?logout">Logout</a>';
}
?>
