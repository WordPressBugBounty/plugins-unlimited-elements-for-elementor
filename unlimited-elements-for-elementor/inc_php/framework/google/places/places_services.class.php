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
		
		if(GlobalsUC::$isSaveBuilderMode == true)
			return(null);
			
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
	 * get demo google reviews data, formatted the same way as the serp api response,
	 * so the result can replace getDetailsSerp() output in the editor / preview mode.
	 */
	public function getDetailsDemo(){

		$this->isSerp = true;

		$data = array(
			"search_metadata" => array(
				"id" => "demo000000000000000000",
				"status" => "Success",
				"json_endpoint" => "",
				"created_at" => gmdate("Y-m-d H:i:s") . " UTC",
				"processed_at" => gmdate("Y-m-d H:i:s") . " UTC",
				"google_maps_reviews_url" => "",
				"raw_html_file" => "",
				"prettify_html_file" => "",
				"total_time_taken" => 0,
			),
			"search_parameters" => array(
				"engine" => "google_maps_reviews",
				"place_id" => "ChIJDemoPlaceIdXXXXXXXXXXXXXXXXX",
				"hl" => "en",
				"sort_by" => "qualityScore",
			),
			"place_info" => array(
				"title" => "Café Bellavista",
				"address" => "123 Demo Street, Sample City 10001",
				"rating" => 4.6,
				"reviews" => 1284,
				"type" => "Cafe",
			),
			"reviews" => $this->getDemoReviews(),
		);

		$place = UEGoogleAPIPlace::transform($data);

		return($place);
	}

	/**
	 * get a list of demo reviews with varied ratings and content.
	 */
	private function getDemoReviews(){

		$reviews = array();

		$reviews[] = array(
			"position" => 1,
			"link" => "https://www.google.com/maps/reviews/demo1",
			"rating" => 5,
			"date" => "a week ago",
			"iso_date" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-1 week")),
			"iso_date_of_last_edit" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-1 week")),
			"source" => "Google",
			"review_id" => "DemoReview000000000000000000001",
			"user" => array(
				"name" => "Emily Carter",
				"link" => "https://www.google.com/maps/contrib/100000000000000000001",
				"contributor_id" => "100000000000000000001",
				"thumbnail" => "https://lh3.googleusercontent.com/a/default-user=s120",
				"local_guide" => 1,
				"reviews" => 87,
				"photos" => 42,
			),
			"snippet" => "Absolutely stunning place! The interior takes you back in time and the coffee is some of the best I've had. The staff were warm, attentive, and clearly proud of what they do. We left feeling pampered. Highly recommended for anyone visiting the area.",
			"extracted_snippet" => array(
				"original" => "Absolutely stunning place! The interior takes you back in time and the coffee is some of the best I've had. The staff were warm, attentive, and clearly proud of what they do. We left feeling pampered. Highly recommended for anyone visiting the area.",
			),
			"details" => array(
				"trip_type" => "Vacation",
				"travel_group" => "Couple",
				"service" => 5,
				"location" => 5,
			),
			"likes" => 12,
		);

		$reviews[] = array(
			"position" => 2,
			"link" => "https://www.google.com/maps/reviews/demo2",
			"rating" => 4,
			"date" => "2 weeks ago",
			"iso_date" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-2 weeks")),
			"iso_date_of_last_edit" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-2 weeks")),
			"source" => "Google",
			"review_id" => "DemoReview000000000000000000002",
			"user" => array(
				"name" => "Marco Bianchi",
				"link" => "https://www.google.com/maps/contrib/100000000000000000002",
				"contributor_id" => "100000000000000000002",
				"thumbnail" => "https://lh3.googleusercontent.com/a/default-user=s120",
				"local_guide" => 0,
				"reviews" => 14,
				"photos" => 3,
			),
			"snippet" => "Great atmosphere and really tasty pastries. The cappuccino was rich and well prepared. Took off one star because it was quite crowded and we had to wait around 15 minutes for a table, but the experience was worth it overall.",
			"extracted_snippet" => array(
				"original" => "Great atmosphere and really tasty pastries. The cappuccino was rich and well prepared. Took off one star because it was quite crowded and we had to wait around 15 minutes for a table, but the experience was worth it overall.",
			),
			"details" => array(
				"service" => 4,
				"location" => 5,
			),
			"likes" => 4,
		);

		$reviews[] = array(
			"position" => 3,
			"link" => "https://www.google.com/maps/reviews/demo3",
			"rating" => 5,
			"date" => "3 weeks ago",
			"iso_date" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-3 weeks")),
			"iso_date_of_last_edit" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-3 weeks")),
			"images" => array(
				"https://via.placeholder.com/600x400.png?text=Demo+Image+1",
				"https://via.placeholder.com/600x400.png?text=Demo+Image+2",
				"https://via.placeholder.com/600x400.png?text=Demo+Image+3",
			),
			"source" => "Google",
			"review_id" => "DemoReview000000000000000000003",
			"user" => array(
				"name" => "Sofia Martinez",
				"link" => "https://www.google.com/maps/contrib/100000000000000000003",
				"contributor_id" => "100000000000000000003",
				"thumbnail" => "https://lh3.googleusercontent.com/a/default-user=s120",
				"local_guide" => 1,
				"reviews" => 211,
				"photos" => 156,
			),
			"snippet" => "What a hidden gem! From the moment we stepped in, we were greeted with smiles and shown to a beautiful table near the window. The brunch menu has plenty of vegetarian options and everything we ordered was fresh and delicious. The eggs benedict was perfectly cooked. Will be back!",
			"extracted_snippet" => array(
				"original" => "What a hidden gem! From the moment we stepped in, we were greeted with smiles and shown to a beautiful table near the window. The brunch menu has plenty of vegetarian options and everything we ordered was fresh and delicious. The eggs benedict was perfectly cooked. Will be back!",
			),
			"details" => array(
				"travel_group" => "Family",
				"service" => 5,
				"location" => 5,
				"food_drinks" => "Excellent",
			),
			"likes" => 28,
		);

		$reviews[] = array(
			"position" => 4,
			"link" => "https://www.google.com/maps/reviews/demo4",
			"rating" => 3,
			"date" => "a month ago",
			"iso_date" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-1 month")),
			"iso_date_of_last_edit" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-1 month")),
			"source" => "Google",
			"review_id" => "DemoReview000000000000000000004",
			"user" => array(
				"name" => "Liam O'Connor",
				"link" => "https://www.google.com/maps/contrib/100000000000000000004",
				"contributor_id" => "100000000000000000004",
				"thumbnail" => "https://lh3.googleusercontent.com/a/default-user=s120",
				"local_guide" => 0,
				"reviews" => 8,
				"photos" => 0,
			),
			"snippet" => "Mixed feelings. The decor is gorgeous and Instagram-worthy, but the food was just okay for the price. The latte was good, the avocado toast was a bit underseasoned. Service was friendly though. Worth a visit if you're in the area, but I wouldn't go out of my way.",
			"extracted_snippet" => array(
				"original" => "Mixed feelings. The decor is gorgeous and Instagram-worthy, but the food was just okay for the price. The latte was good, the avocado toast was a bit underseasoned. Service was friendly though. Worth a visit if you're in the area, but I wouldn't go out of my way.",
			),
			"details" => array(
				"service" => 4,
				"food_drinks" => "Average",
			),
			"likes" => 2,
		);

		$reviews[] = array(
			"position" => 5,
			"link" => "https://www.google.com/maps/reviews/demo5",
			"rating" => 5,
			"date" => "a month ago",
			"iso_date" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-5 weeks")),
			"iso_date_of_last_edit" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-5 weeks")),
			"source" => "Google",
			"review_id" => "DemoReview000000000000000000005",
			"user" => array(
				"name" => "Yuki Tanaka",
				"link" => "https://www.google.com/maps/contrib/100000000000000000005",
				"contributor_id" => "100000000000000000005",
				"thumbnail" => "https://lh3.googleusercontent.com/a/default-user=s120",
				"local_guide" => 1,
				"reviews" => 64,
				"photos" => 22,
			),
			"snippet" => "The matcha cake here is something I keep dreaming about. Beautifully presented, balanced flavor, and paired with a fantastic single-origin pour-over. The waiter even gave us recommendations on what to try next time. A wonderful spot to slow down and enjoy a quiet afternoon.",
			"extracted_snippet" => array(
				"original" => "The matcha cake here is something I keep dreaming about. Beautifully presented, balanced flavor, and paired with a fantastic single-origin pour-over. The waiter even gave us recommendations on what to try next time. A wonderful spot to slow down and enjoy a quiet afternoon.",
			),
			"details" => array(
				"trip_type" => "Business",
				"service" => 5,
				"location" => 5,
			),
			"likes" => 17,
		);

		$reviews[] = array(
			"position" => 6,
			"link" => "https://www.google.com/maps/reviews/demo6",
			"rating" => 2,
			"date" => "a month ago",
			"iso_date" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-6 weeks")),
			"iso_date_of_last_edit" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-6 weeks")),
			"source" => "Google",
			"review_id" => "DemoReview000000000000000000006",
			"user" => array(
				"name" => "Hannah Becker",
				"link" => "https://www.google.com/maps/contrib/100000000000000000006",
				"contributor_id" => "100000000000000000006",
				"thumbnail" => "https://lh3.googleusercontent.com/a/default-user=s120",
				"local_guide" => 0,
				"reviews" => 19,
				"photos" => 5,
			),
			"snippet" => "The place looks beautiful in photos but our experience was disappointing. We waited 25 minutes to order, the croissants were stale, and when we mentioned it nobody seemed to care. The coffee was decent, which is the only reason this isn't a one-star review.",
			"extracted_snippet" => array(
				"original" => "The place looks beautiful in photos but our experience was disappointing. We waited 25 minutes to order, the croissants were stale, and when we mentioned it nobody seemed to care. The coffee was decent, which is the only reason this isn't a one-star review.",
			),
			"details" => array(
				"service" => 2,
				"food_drinks" => "Disappointing",
			),
			"likes" => 6,
		);

		$reviews[] = array(
			"position" => 7,
			"link" => "https://www.google.com/maps/reviews/demo7",
			"rating" => 5,
			"date" => "2 months ago",
			"iso_date" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-2 months")),
			"iso_date_of_last_edit" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-2 months")),
			"images" => array(
				"https://via.placeholder.com/600x400.png?text=Demo+Image+4",
				"https://via.placeholder.com/600x400.png?text=Demo+Image+5",
			),
			"source" => "Google",
			"review_id" => "DemoReview000000000000000000007",
			"user" => array(
				"name" => "Priya Sharma",
				"link" => "https://www.google.com/maps/contrib/100000000000000000007",
				"contributor_id" => "100000000000000000007",
				"thumbnail" => "https://lh3.googleusercontent.com/a/default-user=s120",
				"local_guide" => 1,
				"reviews" => 142,
				"photos" => 98,
			),
			"snippet" => "Came here for my birthday and the team made the day extra special. They surprised me with a candle on a tiny cheesecake at the end of the meal. The pasta dishes are really well executed and the wine list has lovely surprises by the glass. Romantic, warm, memorable.",
			"extracted_snippet" => array(
				"original" => "Came here for my birthday and the team made the day extra special. They surprised me with a candle on a tiny cheesecake at the end of the meal. The pasta dishes are really well executed and the wine list has lovely surprises by the glass. Romantic, warm, memorable.",
			),
			"details" => array(
				"trip_type" => "Vacation",
				"travel_group" => "Couple",
				"service" => 5,
				"location" => 5,
				"hotel_highlights" => "Romantic, Great value",
			),
			"likes" => 33,
		);

		$reviews[] = array(
			"position" => 8,
			"link" => "https://www.google.com/maps/reviews/demo8",
			"rating" => 4,
			"date" => "2 months ago",
			"iso_date" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-9 weeks")),
			"iso_date_of_last_edit" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-9 weeks")),
			"source" => "Google",
			"review_id" => "DemoReview000000000000000000008",
			"user" => array(
				"name" => "David Kim",
				"link" => "https://www.google.com/maps/contrib/100000000000000000008",
				"contributor_id" => "100000000000000000008",
				"thumbnail" => "https://lh3.googleusercontent.com/a/default-user=s120",
				"local_guide" => 0,
				"reviews" => 31,
				"photos" => 7,
			),
			"snippet" => "Solid choice for a working breakfast. WiFi is fast, plenty of plug points, and the staff don't rush you out of your seat. A touch on the expensive side, but the quality of the espresso justifies it. The pastry selection rotates daily which keeps things interesting.",
			"extracted_snippet" => array(
				"original" => "Solid choice for a working breakfast. WiFi is fast, plenty of plug points, and the staff don't rush you out of your seat. A touch on the expensive side, but the quality of the espresso justifies it. The pastry selection rotates daily which keeps things interesting.",
			),
			"details" => array(
				"trip_type" => "Business",
				"service" => 4,
				"location" => 4,
			),
			"likes" => 5,
		);

		$reviews[] = array(
			"position" => 9,
			"link" => "https://www.google.com/maps/reviews/demo9",
			"rating" => 1,
			"date" => "3 months ago",
			"iso_date" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-3 months")),
			"iso_date_of_last_edit" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-3 months")),
			"source" => "Google",
			"review_id" => "DemoReview000000000000000000009",
			"user" => array(
				"name" => "Robert Fischer",
				"link" => "https://www.google.com/maps/contrib/100000000000000000009",
				"contributor_id" => "100000000000000000009",
				"thumbnail" => "https://lh3.googleusercontent.com/a/default-user=s120",
				"local_guide" => 0,
				"reviews" => 4,
				"photos" => 0,
			),
			"snippet" => "Very disappointing visit. We had a reservation and they still made us wait 30 minutes for a table. The food took forever, two of the three drinks we ordered came wrong, and the bill had an item we didn't order. Manager was apologetic but didn't actually fix anything. Won't return.",
			"extracted_snippet" => array(
				"original" => "Very disappointing visit. We had a reservation and they still made us wait 30 minutes for a table. The food took forever, two of the three drinks we ordered came wrong, and the bill had an item we didn't order. Manager was apologetic but didn't actually fix anything. Won't return.",
			),
			"details" => array(
				"service" => 1,
				"food_drinks" => "Poor",
			),
			"likes" => 9,
		);

		$reviews[] = array(
			"position" => 10,
			"link" => "https://www.google.com/maps/reviews/demo10",
			"rating" => 5,
			"date" => "3 months ago",
			"iso_date" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-14 weeks")),
			"iso_date_of_last_edit" => gmdate("Y-m-d\TH:i:s\Z", strtotime("-14 weeks")),
			"source" => "Google",
			"review_id" => "DemoReview000000000000000000010",
			"user" => array(
				"name" => "Aisha Mensah",
				"link" => "https://www.google.com/maps/contrib/100000000000000000010",
				"contributor_id" => "100000000000000000010",
				"thumbnail" => "https://lh3.googleusercontent.com/a/default-user=s120",
				"local_guide" => 1,
				"reviews" => 76,
				"photos" => 51,
			),
			"snippet" => "Celebrated our anniversary here and it was perfect from start to finish. The hostess remembered we'd booked for a special occasion and arranged a quiet table for two. The tasting menu was thoughtful, the wine pairings spot on, and the dessert came with a sweet handwritten note. Truly five-star service in every detail.",
			"extracted_snippet" => array(
				"original" => "Celebrated our anniversary here and it was perfect from start to finish. The hostess remembered we'd booked for a special occasion and arranged a quiet table for two. The tasting menu was thoughtful, the wine pairings spot on, and the dessert came with a sweet handwritten note. Truly five-star service in every detail.",
			),
			"details" => array(
				"trip_type" => "Vacation",
				"travel_group" => "Couple",
				"service" => 5,
				"location" => 5,
				"hotel_highlights" => "Luxury, Romantic, Great value",
			),
			"likes" => 41,
		);

		return($reviews);
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
