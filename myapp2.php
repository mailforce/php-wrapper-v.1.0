<?
//Esempio d'uso del wrapper in PHP con uso di API KEY
//Author: Mailforce
//Date: 2016-12-12 18:21:00

define(MF_ENDPOINT,'http://api.mailforcelab.it/');
define(MF_OAUTH_BASE_URI, 	MF_ENDPOINT.'oauth');
define(MF_OAUTH_TOKEN_URI,	MF_ENDPOINT.'token');
define(MF_API_BASE_URI,		MF_ENDPOINT.'v1/');

require_once('mf_api.php');

$mfclient = new MF_REST('basicauth1');
$mfclient->setApplicationName("Nome univoco");
$mfclient->setApiKey('43062f8c1d9c155af9ea4642cc03sunu');

$mflists = new MF_REST_Lists($mfclient);
$listUID="77ff3df1e6ee0df64e6ac6805e3k5ppm";
$result = $mflists->pending($listUID,20,0);

if ($result->execute()) {
	print_r($result->response);
} 

?>