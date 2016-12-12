<?
class curlCall {
	private $curl;
	private $endpoint;
	
	function __construct($endpoint) {
		$this->endpoint = $endpoint;
		$this->curl = curl_init($this->endpoint);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		return $this->curl;
	}
	
	function setHttpHeader($httpHeader) {
		return curl_setopt($this->curl, CURLOPT_HTTPHEADER, $httpHeader);
	}

	function setUserPwd($httpUser, $httpPwd) {
		return curl_setopt($this->curl, CURLOPT_USERPWD, $httpUser.":".$httpPwd);
	}
	
	function setCustomRequest($customRequest) {
		return curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $customRequest);
	}
	
	function setPostFields($postFields) {
		curl_setopt($this->curl, CURLOPT_POST, true); //Aggiunto *****************************
		return curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postFields);
	}
	
	function getResource() {
		return $this->curl;
	}
}

class MF_REST {
	var $curl;
	var $response;
	var $applicationName;
	var $auth;
	var $listUID;
	var $mfclient;	

	private $clientID;
	private $clientPass;
	private $redirectUri;
	private $scopes;
	private $access_token;
	private $refresh_token;
	private $api_key;
	
	function __construct($name="MF_REST_API") {
		session_name($name);
		session_start();
	}
	
	function setToken($accessToken, $refreshToken="") {
		$this->access_token 	= $accessToken;
		$_SESSION['access_token'] = $accessToken;
		if ($refreshToken<>"") {
			$this->refresh_token 	= $refreshToken;
			$_SESSION['refresh_token'] = $refreshToken;
		}
	}
	
	function setApplicationName($appName) {
		$this->applicationName = $appName;
	}
	
	function setClientID($clientID) {
	 	$this->clientID=$clientID;
	}
	
	function setApiKey($api_key) {
	 	$this->api_key=$api_key;
	}
	
	function getApiKey() {
		return $this->api_key;
	}
	
	function setClientSecret($clientPass) {
		$this->clientPass=$clientPass;
	}
	
	function setRedirectUri($redirectUri) {
		$this->redirectUri=$redirectUri;
	}
	
	function setScopes($scopes = '') {
		$this->scopes=$scopes;
	}	
	
	function execute() {
		$this->response =  curl_exec($this->curl->getResource()) ; //restituisce un JSON
		return $this->response;
	}
	
	function authorize_url( $state = 1 ) {
		
		$client_id = $this->clientID;
		$redirect_uri = $this->redirectUri;
		$scopes = $this->scopes;
		
		if ( isset($_GET['error']) && $_GET['error']<>"" ) $state=0;
		$qs = "response_type=code&";
		$qs .= "client_id=".urlencode($client_id);
		$qs .= "&redirect_uri=".urlencode($redirect_uri);
		$qs .= "&scope=".urlencode($scopes);
		$qs .= "&state=".urlencode($state);

		return MF_OAUTH_BASE_URI.'?'.$qs;
	}

	function getAccessToken() {
		return isset($_SESSION['access_token']) && $_SESSION['access_token']<>"" ?$_SESSION['access_token']:false;
	} 
	
	function setAccessToken($accessToken) {
		$this->accessToken = $accessToken;	
	}
	
	function exchange_token($code) {
	 	$client_id = $this->clientID;
	 	$client_secret = $this->clientPass;
	 	$redirect_uri = $this->redirectUri;
	 	
		$body = "grant_type=authorization_code";
		$body .= "&client_id=".urlencode($client_id);
		$body .= "&client_secret=".urlencode($client_secret);
		$body .= "&redirect_uri=".urlencode($redirect_uri);
		$body .= "&code=".urlencode($code);
		$options = array('contentType' => 'application/x-www-form-urlencoded');
		
		$this->curl = new curlCall(MF_OAUTH_TOKEN_URI);

		$this->curl->setPostFields($body);

		 
		if ( $this->execute() ) {
			$tokenObject = json_decode($this->response);
			return json_decode(json_encode(array('access_token'=>$tokenObject->access_token, 'expires_in'=>$tokenObject->expires_in, 'refresh_token'=>$tokenObject->refresh_token)));
		} 
		return $this->response;
	}

	function refreshToken() {
		$client_id = $this->clientID;
	 	$client_secret = $this->clientPass;
	 	$redirect_uri = $this->redirectUri;
		$refresh_token = $_SESSION['refresh_token'];

		$body = "grant_type=refresh_token";
		$body .= "&client_id=".urlencode($client_id);
		$body .= "&client_secret=".urlencode($client_secret);
		$body .= "&refresh_token=".urlencode($_SESSION['refresh_token']);

		$this->curl = new curlCall(MF_OAUTH_TOKEN_URI);
		$this->curl->setPostFields($body);
		 
		if ( $this->execute() ) {
			$tokenObject = json_decode($this->response);
			return json_decode(json_encode(array('access_token'=>$tokenObject->access_token, 'expires_in'=>$tokenObject->expires_in, 'refresh_token'=>$tokenObject->refresh_token)));
		}
		return $this->response;		
	}
	
	function doCall($endpoint,$method,$postfields="") {
		$this->curl = new curlCall($endpoint);
		$api_key = $this->mfclient->getApiKey();
		$token = $this->mfclient->getAccessToken();
		
		if ($postfields<>"") {
			$field_string = http_build_query($postfields);
			$this->curl->setPostFields($field_string);
		}


		if ($api_key<>"" && $token=="") {
			$this->curl->setUserPwd($api_key,"dummy");
		} elseif ($api_key=="" && $token<>"") {
			$this->curl->setHttpHeader(array('Content-Type: application/json; charset=utf-8',"Authorization: Bearer $token"));
		}
		$this->curl->setCustomRequest($method);
		return $this;
	}
	
}

class MF_REST_Lists extends MF_REST {

	function __construct($mfclient) {
		$this->mfclient = $mfclient;
	}
	
	//Elenco delle funzioni
	public function active($listUID,$pagesize=10,$pagenumber=0) {
		$endpoint = MF_API_BASE_URI . "lists/{$listUID}/contacts/active?pagesize={$pagesize}&pagenumber={$pagenumber}";
		return $this->doCall($endpoint,"GET");
	}
	
	public function hardbounce($listUID,$pagesize=10,$pagenumber=0) {
		$endpoint = MF_API_BASE_URI . "lists/{$listUID}/contacts/hardbounce?pagesize={$pagesize}&pagenumber={$pagenumber}";
		return $this->doCall($endpoint,"GET");
	}

	public function softbounce($listUID,$pagesize=10,$pagenumber=0) {
		$endpoint = MF_API_BASE_URI . "lists/{$listUID}/contacts/softbounce?pagesize={$pagesize}&pagenumber={$pagenumber}";
		return $this->doCall($endpoint,"GET");
	}

	public function suspended($listUID,$pagesize=10,$pagenumber=0) {
		$endpoint = MF_API_BASE_URI . "lists/{$listUID}/contacts/suspended?pagesize={$pagesize}&pagenumber={$pagenumber}";
		return $this->doCall($endpoint,"GET");
	}

	public function pending($listUID,$pagesize=10,$pagenumber=0) {
		$endpoint = MF_API_BASE_URI . "lists/{$listUID}/contacts/pending?pagesize={$pagesize}&pagenumber={$pagenumber}";
		return $this->doCall($endpoint,"GET");
	}
			
	public function getContact($listUID,$contactUID) {
		$endpoint = MF_API_BASE_URI . "lists/{$listUID}/contacts/{$contactUID}";
		return $this->doCall($endpoint,"GET");
	}
	public function addContact($listUID,$contact) {
		$endpoint = MF_API_BASE_URI . "lists/{$listUID}/contacts/";
		return $this->doCall($endpoint,"POST",$contact);
	}	
	public function newList($list) {
		$endpoint = MF_API_BASE_URI . "lists/new/";
		return $this->doCall($endpoint,"POST",$list);
	}

	public function getLists() {
		$endpoint = MF_API_BASE_URI . "lists/";
		return $this->doCall($endpoint,"GET",$list);
	}

	public function getListDetails($listUID) {
		$endpoint = MF_API_BASE_URI . "lists/{$listUID}/";
		return $this->doCall($endpoint,"GET");
	}
	public function getListName($listUID) {
		$endpoint = MF_API_BASE_URI . "lists/{$listUID}/name/";
		return $this->doCall($endpoint,"GET");
	}
	public function getListDescription($listUID) {
		$endpoint = MF_API_BASE_URI . "lists/{$listUID}/description/";
		return $this->doCall($endpoint,"GET");
	}	
}


?>