<?php

/**
 * @link https://developers.google.com/maps/documentation/places/web-service/overview
 */
class UEGoogleAPIPlacesService extends UEGoogleAPIClient{
	
	private $isSerp = false;
	
	/**
	 * Get the place details.
	 *
	 * @param string $placeId
	 * @param array $params
	 *
	 * @return UEGoogleAPIPlace
	 */
	public function getDetails($placeId, $params = array(),$showDebug = false){
		
		$this->isSerp = false;
		
		$params["place_id"] = $placeId;
		
		$lang = UniteFunctionsUC::getVal($params, "lang");
		
		if(!empty($lang))
			$params["language"] = $lang;
		else
			$params["reviews_no_translations"] = true;
					
		$response = $this->get("/details/json", $params);

		//debug
		if($showDebug == true){
			
			HelperHtmlUC::putHtmlDataDebugBox_start();
						
			dmp("Official API Request Debug");
			
			$paramsForDebug = $params;
			
			dmp("Send Params");
			dmp($paramsForDebug);
			
			$dataShow = UniteFunctionsUC::modifyDataArrayForShow($response);
			
			dmp("Response Data");
			dmp($dataShow);
			
			HelperHtmlUC::putHtmlDataDebugBox_end();
		}
		
		$response = UEGoogleAPIPlace::transform($response["result"]);
		
		
		return $response;
	}
	
	/**
	 * get details using serp function
	 */
	public function getDetailsSerp($placeID, $apiKey, $params = array(),$showDebug = false, $cacheTime = 86400){

		if(empty($apiKey))
			UniteFunctionsUC::throwError("No serp api key");
		
		$this->isSerp = true;
		
		//cache time is passed as parameter (default: 1 day in seconds)
		
		$params["place_id"] = $placeID;
		$params["api_key"] = $apiKey;
		
		$headers = array();
		
		$request = UEHttp::make();
				
		if(!empty($headers))
			$request->withHeaders($headers);
				
		$request->asJson();
		$request->acceptJson();
		
		$request->cacheTime($cacheTime);
		$request->withQuery($params);
		
		$url = "https://serpapi.com/search?engine=google_maps_reviews";
		
		//first call
		
		$response = $request->request(UEHttpRequest::METHOD_GET, $url);
		
		$data = $response->json();
		
		if($showDebug == true){
			
			HelperHtmlUC::putHtmlDataDebugBox_start();
						
			dmp("Serp API Request Debug");
			
			$paramsForDebug = $params;
			
			$apiKey = UniteFunctionsUC::getVal($paramsForDebug, "api_key");
			
			$paramsForDebug["api_key"] = substr($apiKey, 0, 10) . '********';
			
			dmp("Send Params");
			dmp($paramsForDebug);
			
			$dataShow = UniteFunctionsUC::modifyDataArrayForShow($data);
			
			dmp("Response Data");
			dmp($dataShow);
			
		}
		
		$error = UniteFunctionsUC::getVal($data, "error");
		if(!empty($error)){
			dmp($data);
			UniteFunctionsUC::throwError($error);
		}
		
		$pagination = UniteFunctionsUC::getVal($data, "serpapi_pagination");
		$nextPageToken = UniteFunctionsUC::getVal($pagination, "next_page_token");
		
		//second call:
		
		if(!empty($nextPageToken)){
			
			$params["next_page_token"] = $nextPageToken;
			$params["num"] = 20;
			
			$request->withQuery($params);
			
			$response = $request->request(UEHttpRequest::METHOD_GET, $url);
			$data2 = $response->json();

			if($showDebug == true){
				
				dmp("Second Request - Send Params2");
				dmp($params);
				
				$dataShow2 = UniteFunctionsUC::modifyDataArrayForShow($data);
				
				dmp("Second Request - Response Data");
				dmp($dataShow2);
				
			}
			
			$arrReviews2 = UniteFunctionsUC::getVal($data2, "reviews");
			
			if(!empty($arrReviews2))
				$data["reviews"] += $arrReviews2;
			
		}
		
		if($showDebug == true)
			HelperHtmlUC::putHtmlDataDebugBox_end();
		
		
		$place = UEGoogleAPIPlace::transform($data);		
		
		return($place);
	}
	
	
	/**
	 * Get the base URL for the API.
	 *
	 * @return string
	 */
	protected function getBaseUrl(){
		
		if($this->isSerp == true)
			return("https://serpapi.com/search?engine=google_maps_reviews");
		else		
			return "https://maps.googleapis.com/maps/api/place";
		
	}

}
