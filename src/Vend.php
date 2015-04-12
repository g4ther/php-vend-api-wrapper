<?php
	namespace g4ther;

	/*
		Other affiliated files (do not delete these):
			vend_data.txt
			vend_redirect.php
	
		@author Nick Clark <nick@itsoftware.net.au>
		@version 1.0.0
	*/

	class Vend {
		// constants
		define("VEND_REDIRECT", dirname("http" . (isset($_SERVER['HTTPS']) ? 's' : '') . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) . "vend_redirect.php");
		
		// variables
		private $prefix;
		private $token;
		private $vendData;
		
		/* 	Create a new instance
			
		*/
		function __construct($clientId = null, $clientSecret = null) {
			// Has vend been initialised
			if(file_exists("vend_data.txt")) {
				$vendData = explode("|", file_get_contents("vend_data.txt"));
				
				// Get the token
				$token = getToken();
				
				// Get the web prefix for Vend
				$prefix = getWebPrefix();
				
				// Check if the token hasn't been initialised (is false)
				// If it has, check if the token has expired and fetch a new one
				if($token === false) {
					// Initialise the token
					tokenInitilise($clientId, $clientSecret);
				} else {
					tokenRefresh($clientId, $clientSecret);
				}
			} else {
				// If vend hasn't been initialised before, initialise
				// Note: please only initialise once
				if(!empty($clientId)) {
					// Set the redirect URI - e.g. https://example.com/redirect.php
					$redirect = VEND_REDIRECT;

					// Request Authentication
					$url = "https://secure.vendhq.com/connect?response_type=code&client_id=" . $clientId . "&redirect_uri=" . $redirect;
					
					// Redirect to Vend
					header("Location: " . $url);
					
					// Kill the script - vend_redirect.php will handle from here
					die();
				}
			}
		}
		
		/* Initialise the token
		
		*/
		private function tokenInitilise($clientId, $clientSecret) {
			// The url to get the token from (e.g. https://example.vendhq.com/api/1.0/token)
			$tokenURL = "https://" . $prefix . ".vendhq.com/api/1.0/token";
			
			// The parameters to send to get the token
			$tokenParams = array(
				'code' => urlencode($vendData[1]),
				'client_id' => urlencode($clientId),
				'client_secret' => urlencode($clientSecret),
				'grant_type' => urlencode('authorization_code'),
				'redirect_uri' => urlencode(VEND_REDIRECT)
			);
			
			// The parameters to send in string form
			$tokenParamsString = "";
			foreach($tokenParams as $key => $value) { $tokenParamsString .= $key . '=' . $value . '&'; }
			rtrim($tokenParamsString, '&');
			
			// Query Vend for the token using cURL
			$curl = curl_init($tokenURL);
			curl_setopt($curl, CURLOPT_POST, count($token_params));
			curl_setopt($curl, CURLOPT_POSTFIELDS, $tokenParamsString);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			
			// Get the return value
			$result = curl_exec($curl);
			$result = json_decode($result, true);
			
			// Close cURL
			curl_close($curl);
			
			// Encode the values to put back to vend_data.txt
			$new_vend_data = $vend_data[0] . "|" . $vend_data[1] . "|" . $result['access_token'] . "|" . $result['token_type'] . "|" . $result['expires'] . "|" . $result['expires_in'] . "|" , $result['refresh_token'];
			
			// Put the new data into vend_data.txt
			file_put_contents("vend_data.txt", $new_vend_data);
			
			// reasign the vend_data variable
			$vend_data = explode("|", $new_vend_data);
		}
		
		/* Refresh the token
			This is used within the construct method but can also be called manually (to force a refresh, set force to true)
		*/
		public function tokenRefresh($clientId, $clientSecret, $force = false) {
			// Check if the token has expired
			// 	if so, refresh the token
			if($vendData[3] <= time() || $force) {
				// The token to get the token from
				$tokenURL = "https://" . $prefix . ".vendhq.com/api/1.0/token";
				
				// The parameters to send in order to get the token
				$tokenParams = array(
					'refresh_token' => urlencode($vend_data[6]),
					'client_id' => urlencode($clientId),
					'client_secret' => urlencode($clientSecret),
					'grant_type' => urlencode('refresh_token'),
				);
				
				// The parameters to send in string form
				$tokenParamsString = "";
				foreach($tokenParams as $key => $value) { $tokenParamsString .= $key . '=' . $value . '&'; }
				rtrim($tokenParamsString, '&');
				
				// Query Vend for the token using cURL
				$curl = curl_init($tokenURL);
				curl_setopt($curl, CURLOPT_POST, count($token_params));
				curl_setopt($curl, CURLOPT_POSTFIELDS, $tokenParamsString);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

				// Get the return value
				$result = curl_exec($curl);
				$result = json_decode($result, true);

				// Close cURL
				curl_close($curl);
				
				// Encode the values to put back into vend_data.txt
				$new_vend_data = $vend_data[0] . "|" . $vend_data[1] . "|" . $result['access_token'] . "|" . $result['token_type'] . "|" . $result['expires'] . "|" . $result['expires_in'] . "|" . $vend_data[6];
				
				// Put the new data into vend_data.txt
				file_put_contents("vend_data.txt", $new_vend_data);

				// reasign the vend_data variable
				$vend_data = explode("|", $new_vend_data);
			}
		}
		
		/* Make a get call to Vend's API
			@param	string	$request	The api request (e.g. customers, products)
			@param	array	$args		The parameters of the call
			@param	bool	$decode		If the returned value should be decoded from JSON
		*/
		public function get($request, $args = array(), $decode = false) {
			// Set the url to call the api from (e.g. https://example.vendhq.com/api/customers)
			$vendURL = "https://" . $prefix . ".vendhq.com/api/" . $request;
			
			// Add the parameters to the URL
			if(!empty($args)) {
				$vendURL .= "?";
				$argCount = count($args);
				
				// Cycle through the arguments, adding them to the url
				for($i = 0; $i < $argCount; $i++) {
					$vendURL .= $args[$i] . "&";
				}
				
				// Remove the trailing ampersand
				rtrim($vendURL, '&');
			}
			
			// Query Vend
			$curl = curl_init($vendURL);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				  'Accept: application/json'
				, 'Content-Type: application/json'
				, 'Authorization: ' . $vendData[3] . ' ' . $vendData[2]
			));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			
			// Get the returned value
			$response = curl_exec($curl);
			
			// If an error occurred
			if($response === false) {
				echo "An error occurred: " . curl_error($curl);
			}
			
			// Close cURL
			curl_close($curl);
			
			// If the response should be decoded
			if($decode) {
				return json_decode($response, true);
			} else {
				return $response;
			}
		}
		
		/* Make a post call to Vend's API
			@param	string	$request	The api request (e.g. customers, products)
			@param	array	$data		The data to post
			@param	bool	$decode		If the returned value should be decoded from JSON (if true, objects are converted into associative arrays)
		*/
		public function post($request, $data = array(), $decode = false) {
			// Set the url to call the api from (e.g. https://example.vendhq.com/api/customers)
			$vendURL = "https://" . $prefix . ".vendhq.com/api/" . $request;
			
			// Check if $data is already in JSON form, if not, make it JSON
			if(!is_object(json_decode($data))) {
				$data = json_encode($data);
			}
			
			// Query Vend
			$curl = curl_init($vendURL);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				  'Accept: application/json'
				, 'Content-Type: application/json'
				, 'Content-Length: ' . strlen($data)
				, 'Authorization: ' . $vendData[3] . ' ' . $vendData[2]
			));
			
			// Get the returned value
			$response = curl_exec($curl);
			
			// If an error occurred
			if($response === false) {
				echo "An error occurred: " . curl_error($curl);
			}
			
			// Close cURL
			curl_close($curl);
			
			// If the response should be decoded
			if($decode) {
				return json_decode($response, true);
			} else {
				return $response;
			}
		}
		
		/* Get the token
		
		*/
		private function getToken() {
			// If token is empty return false
			// Else return token
			if(empty($vendData[2])) {
				return false;
			} else {
				return $vendData[2];
			}
		}
		
		/* Get the Web Prefix
		
		*/
		private function getWebPrefix() {
			return $vendData[1];
		}
	}
?>