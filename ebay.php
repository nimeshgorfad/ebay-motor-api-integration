<?php 


class eBayMoter{
	
	var $token = '';
	var $client_id = '';
	var $client_secret = '';
	var $ebay_dev_id = '';
	var $refresh_token = '';
	
	
	function __construct(){
		
		$this->token = get_option('ebay_access_token');	
		$this->client_id = get_option('ebay_client_id', '');
		$this->client_secret = get_option('ebay_client_secret', '');
		$this->ebay_dev_id = get_option('ebay_dev_id', '');
		$this->refresh_token = get_option("ebay_refresh_token","");
		$ebay_expires_in_next = get_option("ebay_expires_in_next","");
		// Call refresh token.
		if( time() > $ebay_expires_in_next ){
			$this->ebay_refresh_token_nkg();
		}
	}
	
	
		
	function ebay_refresh_token_nkg(){
		 
		
		$url = "https://api.ebay.com/identity/v1/oauth2/token";

		// Base64 encode client_id and client_secret for Authorization header
		$auth_header = base64_encode("$this->client_id:$this->client_secret");
	 
		
		$data = [
			'grant_type' => 'refresh_token',
			'refresh_token' => $this->refresh_token,
			'scope' => 'https://api.ebay.com/oauth/api_scope', // Replace with required scopes
		];
		
		$ch = curl_init();


		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				"Authorization: Basic $auth_header",
				"Content-Type: application/x-www-form-urlencoded",
			],
			CURLOPT_POSTFIELDS => http_build_query($data),
		]);

		$response = curl_exec($ch);

		// Check for errors
		if ($response === false) {
			/* echo "cURL Error: " . curl_error($ch);
			curl_close($ch);
			exit; */
		}

		$response_data = json_decode($response, true);
 
		if (isset($response_data['access_token'])) {
			$expires_in =  $response_data['expires_in'];
			update_option("ebay_access_token", $response_data['access_token']);
			update_option("ebay_token_type", $response_data['token_type']);
			update_option("ebay_expires_in",$expires_in );
			$ebay_expires_in_next = time() + $expires_in ;
			update_option("ebay_expires_in_next", $ebay_expires_in_next);
			
		
			 
		} else {
			//echo "Error: " . json_encode($response_data, JSON_PRETTY_PRINT) . "\n";
		} 
		
	}
	
		
	function ebay_search_item( $tag = '', $next_page = '' ){
		
		$authToken = $this->token; // Replace with your OAuth token
		$devID = $this->ebay_dev_id;          // Developer ID
		$appID = $this->client_id;          // Application ID
		$certID = $this->client_secret;        // Certificate ID
		$endpoint = "https://api.ebay.com/buy/browse/v1/item_summary/search";
		//$tag = '2205000049';
		$query = [
			'q' => $tag,          // Search term
			'category_ids' => 6030,   
			'limit' => 100,
			'offset' => 0
		]; 
		
		$url = $endpoint . '?' . http_build_query($query);
		if( !empty( $next_page ) ){
			$url = $next_page;
		}	

			$headers = [
				"Authorization:Bearer $authToken", // Add the access token
				"Content-Type: application/json",   
				"X-EBAY-C-MARKETPLACE-ID:EBAY_US",			"X-EBAY-C-ENDUSERCTX:affiliateCampaignId=<ePNCampaignId>,affiliateReferenceId=<referenceId>" 

			];

			// Initialize cURL
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			//curl_setopt($ch, CURLOPT_POST, true);
			//curl_setopt($ch, CURLOPT_POSTFIELDS, $requestXml);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Execute the API call
			$response = curl_exec($ch);
				curl_close($ch);	
			// Check for errors
			if (curl_errno($ch)) {
				echo 'cURL error: ' . curl_error($ch);
			} else {
				
				$data	 = json_decode($response,true);		

				if( isset( $data["errors"] )){
					
					if( "1001" == $data["errors"][0]["errorId"] ){
						
						$this->ebay_refresh_token_nkg();
						
						$data = $this->ebay_search_item( $tag, $next_page );
					}
				}	
			 

				return $data;
			}

			// Close cURL
			
	}	
		
	function ebay_product_compatibility($itemId){
			
		// Sandbox URL
		/// $endpoint = "https://api.sandbox.ebay.com/ws/api.dll";
		
		$endpoint = "https://api.ebay.com/ws/api.dll"; // for production

		$authToken = $this->token; // Replace with your OAuth token
		$devID = $this->ebay_dev_id;          // Developer ID
		$appID = $this->client_id;          // Application ID
		$certID = $this->client_secret;        // Certificate ID

			// API call headers
			$headers = [
				'Content-Type: text/xml',
				'X-EBAY-API-CALL-NAME: GetItem', // Replace with the API call name
				'X-EBAY-API-SITEID: 0',          // eBay site ID (0 for US)
				'X-EBAY-API-DEV-NAME: ' . $devID,
				'X-EBAY-API-APP-NAME: ' . $appID,
				'X-EBAY-API-CERT-NAME: ' . $certID,
				'X-EBAY-API-COMPATIBILITY-LEVEL: 967' // Compatibility level
			];

			// 356307772466 
			$requestXml ='<?xml version="1.0" encoding="utf-8"?>
			<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">    
				<RequesterCredentials>
				<eBayAuthToken>' . $this->token . '</eBayAuthToken>
			  </RequesterCredentials>
				<ErrorLanguage>en_US</ErrorLanguage>
				<WarningLevel>High</WarningLevel>
			  <IncludeItemCompatibilityList>true</IncludeItemCompatibilityList>
			  <ItemID>' . $itemId . '</ItemID>
			</GetItemRequest>';
			
			/* var_dump($ItemID);
			var_dump(htmlentities($requestXml)); */
			
			
			// Initialize cURL
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $endpoint);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $requestXml);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Execute the API call
			$response = curl_exec($ch);

			if ($response === false) {
				
				$errorMessage = curl_error($ch);
				echo "Error fetching item details: $errorMessage";
				curl_close($curl);
				return array("error" => $errorMessage);
				 
			}
			
			 
			
			curl_close($ch);	
			
			
			// Parse the response
			libxml_use_internal_errors(true);
			$xmlResponse = simplexml_load_string($response);

			// Check if the response is valid XML
			if ($xmlResponse === false) {
				$errorMessage = "Error parsing XML response.";
				foreach (libxml_get_errors() as $error) {
					$errorMessage .=  $error->message;
				}
				return array("error" => $errorMessage);
				 
			}

			// Check if the response contains any errors
			if (isset($xmlResponse->Error)) {
				$errorMessage = "eBay API Error: " . $xmlResponse->Error->LongMessage;
				
				return array("error" => $errorMessage);
				
				 
			}
			
			return $xmlResponse;
			  
	}

	
}


?>