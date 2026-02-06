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
		
		if($asHtml === true)
			$text = nl2br($text);

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
