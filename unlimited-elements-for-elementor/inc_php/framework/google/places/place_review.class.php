<?php

class UEGoogleAPIPlaceReview extends UEGoogleAPIModel{

	private $isSerp = false;
	
	/**
	 * set that the review is from serp source
	 */
	public function setSerpSource(){
		
		$this->isSerp = true;
	}

	/**
	 * Transform list of Places API (New) reviews into normalized attributes.
	 *
	 * @param array $items
	 *
	 * @return array
	 */
	public static function transformAllNew($items){

		$data = array();

		foreach($items as $attributes){
			$data[] = self::normalizeNew($attributes);
		}

		return $data;
	}

	/**
	 * Transform Places API (New) review fields into legacy-compatible attributes.
	 *
	 * @param array $attributes
	 *
	 * @return UEGoogleAPIPlaceReview
	 */
	public static function transformNew($attributes){

		return self::transform(self::normalizeNew($attributes));
	}

	/**
	 * Normalize Places API (New) review fields.
	 *
	 * @param array $attributes
	 *
	 * @return array
	 */
	private static function normalizeNew($attributes){

		$authorAttribution = UniteFunctionsUC::getVal($attributes, "authorAttribution", array());

		return array(
			"author_name" => UniteFunctionsUC::getVal($authorAttribution, "displayName"),
			"profile_photo_url" => UniteFunctionsUC::getVal($authorAttribution, "photoUri"),
			"author_uri" => UniteFunctionsUC::getVal($authorAttribution, "uri"),
			"text" => self::getLocalizedTextValue($attributes, "text"),
			"original_text" => self::getLocalizedTextValue($attributes, "originalText"),
			"rating" => UniteFunctionsUC::getVal($attributes, "rating"),
			"relative_time_description" => UniteFunctionsUC::getVal($attributes, "relativePublishTimeDescription"),
			"time" => self::getPublishTimeValue($attributes),
			"review_name" => UniteFunctionsUC::getVal($attributes, "name"),
			"google_maps_uri" => UniteFunctionsUC::getVal($attributes, "googleMapsUri"),
			"flag_content_uri" => UniteFunctionsUC::getVal($attributes, "flagContentUri"),
			"authorAttribution" => $authorAttribution,
		);
	}

	/**
	 * Get plain text from a Places API (New) LocalizedText field.
	 *
	 * @param array $attributes
	 * @param string $key
	 *
	 * @return string
	 */
	private static function getLocalizedTextValue($attributes, $key){

		$value = UniteFunctionsUC::getVal($attributes, $key);

		if(is_array($value))
			return UniteFunctionsUC::getVal($value, "text", "");

		if($value === null)
			return "";

		return (string)$value;
	}

	/**
	 * Get unix timestamp from Places API (New) publishTime.
	 *
	 * @param array $attributes
	 *
	 * @return int
	 */
	private static function getPublishTimeValue($attributes){

		$publishTime = UniteFunctionsUC::getVal($attributes, "publishTime");

		if(empty($publishTime))
			return 0;

		$timestamp = strtotime($publishTime);

		if($timestamp === false)
			return 0;

		return $timestamp;
	}
	
	
	/**
	 * Get the identifier.
	 *
	 * @return int
	 */
	public function getId(){

		$id = $this->getTime();

		return $id;
	}

	/**
	 * Get the text.
	 *
	 * @param bool $asHtml
	 *
	 * @return string
	 */
	public function getText($asHtml = false){
		
		$name = "text";
		if($this->isSerp == true)
			$name = "snippet";
		
		$text = $this->getAttribute($name);

		// Places API (New) returns "text" as a structured object / array.
		// Try to normalize it into a plain string before formatting.
		if(is_array($text)){

			// Common shapes:
			// - [ 'text' => '...' ]
			// - [ 'originalText' => [ 'text' => '...' ] ]
			if(array_key_exists("text", $text)){
				$text = $text["text"];
			}elseif(isset($text["originalText"]["text"])){
				$text = $text["originalText"]["text"];
			}else{
				$first = reset($text);
				$text = is_array($first) ? "" : $first;
			}
		}

		if($text === null)
			$text = "";
		else
			$text = (string)$text;
		
		if($asHtml === true){
			$text = UniteFunctionsUC::normalizeContentForText($text);
			$text = nl2br($text);
		}

		return $text;
	}

	/**
	 * Get the rating.
	 *
	 * @return int
	 */
	public function getRating(){
		
		$rating = $this->getAttribute("rating");

		return $rating;
	}

	/**
	 * Get the date.
	 *
	 * @param string $format
	 *
	 * @return string
	 */
	public function getDate($format){

		$time = $this->getTime();
				
		$date = uelm_date($format, $time);

		return $date;
	}

	/**
	 * Get the author name.
	 *
	 * @return string
	 */
	public function getAuthorName(){

		
		if($this->isSerp == true){
			
			$user = $this->getAttribute("user");
			$name = UniteFunctionsUC::getVal($user, "name");
			
			return($name);
		}
		
		
		$name = $this->getAttribute("author_name");

		return $name;
	}

	/**
	 * Get the author photo URL.
	 *
	 * @return string|null
	 */
	public function getAuthorPhotoUrl(){
		
		if($this->isSerp == true){
			$user = $this->getAttribute("user");
			$url = UniteFunctionsUC::getVal($user, "thumbnail");
			
			return($url);
		}
		
		$url = $this->getAttribute("profile_photo_url");

		return $url;
	}

	/**
	 * Get the time.
	 *
	 * @return int
	 */
	private function getTime(){
		
		if($this->isSerp == true){
			
			$dateString = $this->getAttribute("iso_date");
			
			$timestamp = strtotime($dateString);
			
			return($timestamp);
		}
		
		$time = $this->getAttribute("time");
		
		return $time;
	}
	
	/**
	 * get time ago text
	 */
	public function getTimeAgoText(){
		
		$name = "relative_time_description";
		
		if($this->isSerp == true)
			$name = "date";
		
		$timeAgo = $this->getAttribute($name);
		
		return($timeAgo);
	}

}
