<?php

if ((!defined('CONST_INCLUDE_KEY')) || (CONST_INCLUDE_KEY !== 'd4e2ad09-b1c3-4d70-9a9a-0e6149302486')) {
	// If someone tries to browse directly to this PHP file, send 404 and exit. It can only included
	// as part of our API.
	header("Location: /404.html", TRUE, 404);
	echo file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/404.html');
	die;
}

class API_Handler {

	private $function_map;
	private $apiSecretKey = "3uhdyur92jdjhfgkj2893";
	//--------------------------------------------------------------------------------------------------------------------
	public function __construct() {
		$this->loadFunctionMap();
	}

	//----------------------------------------------------------------------------------------------------------------------
	public function execCommand($varFunctionName, $varFunctionParams) {
		// get the actual function name (if necessary) and the class it belongs to.
		$returnArray = $this->getCommand($varFunctionName);

		// if we don't get a function back, then raise the error
		if ($returnArray['success'] == FALSE) {
			return $returnArray;
		}

		$class = $returnArray['dataArray']['class'];
		$functionName = $returnArray['dataArray']['function_name'];

		// Execute User Profile Commands
		$cObjectClass = new $class();
		$returnArray = $cObjectClass->$functionName($varFunctionParams);

		return $returnArray;

	}

	//----------------------------------------------------------------------------------------------------------------------
	private function getCommand($varFunctionName) {
		// get the actual function name and the class it belongs to.
		if (isset($this->function_map[$varFunctionName])) {
			$dataArray['class'] = $this->function_map[$varFunctionName]['class'];
			$dataArray['function_name'] = $this->function_map[$varFunctionName]['function_name'];
			$returnArray = App_Response::getResponse('200');
			$returnArray['dataArray'] = $dataArray;
		} else {
			$returnArray = App_Response::getResponse('405');
		}

		return $returnArray;

	}

	//----------------------------------------------------------------------------------------------------
	private function getToken($varParams) {

		// api key is required
		if (!isset($varParams['apiKey']) || empty($varParams['apiKey'])) {
			$returnArray = App_Response::getResponse('400');
			return $returnArray;
		}

		$apiKey = $varParams['apiKey'];

		// get the api key object
		$cApp_API_Key = new App_API_Key;
		$res = $cApp_API_Key->getRecordByAPIKey($apiKey);

		// if anything looks sketchy, bail.
		if ($res['response'] !== '200') {
			return $res;
		}

		$payloadArray = array();
		$payloadArray['apiKey'] = $apiKey;
		$token = JWT::encode($payloadArray, $this->apiSecretKey);

		$returnArray = App_Response::getResponse('200');
		$returnArray['data'] = array("token" => $token);

		return $returnArray;
	}

	//----------------------------------------------------------------------------------------------------------------------
	private function loadFunctionMap() {

		// load up all public facing functions
		$this->function_map = [
			'getToken' => ['class' => 'API_Handler', 'function_name' => 'getToken'],
			'getInterceptRules' => ['class' => 'App_API_InterceptionRule', 'function_name' => 'getInterceptRules'],
		];

	}

	//--------------------------------------------------------------------------------------------------------------------
	public function validateRequest($varToken = NULL) {
		$returnArray = null;
		if ($varToken) {
			// decode the token
			try {
				$payload = JWT::decode($varToken, $this->apiSecretKey, array('HS256'));
			}
			catch(Exception $e) {
				$returnArray = App_Response::getResponse('403');
				$returnArray['responseDescription'] .= " ".$e->getMessage();
				return $returnArray;
			}

			// get items out of the payload
			if (isset($payload->exp)) {$expire = $payload->exp;} else {$expire = 0;}

			// if token is expired, kick'em out
			$currentTime = time();
			if (($expire !== 0) && ($expire < $currentTime)) {
				$returnArray = App_Response::getResponse('403');
				$returnArray['responseDescription'] .= " Token has expired.";
				return $returnArray;
			}

			$returnArray = App_Response::getResponse('200');
			return $returnArray;
		}
		$returnArray = App_Response::getResponse('402');
		return $returnArray;;
	}

} // end of class