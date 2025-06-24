<?php

class UELanguageAlphabets {
	
    private $alphabets = array(
        'english' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'],
        'german' => ['A', 'Г„', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'Г–', 'P', 'Q', 'R', 'S', 'T', 'U', 'Гњ', 'V', 'W', 'X', 'Y', 'Z'],
        'french' =>  [ 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z','ГЂ', 'Г‚', 'Г†', 'Г‡', 'Г‰', 'Г€', 'ГЉ', 'Г‹', 'ГЋ', 'ГЏ', 'Г”', 'Е’', 'Г™', 'Г›', 'Гњ', 'Её'],
		'spanish' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'L', 'M', 'N', 'Г‘', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'X', 'Y', 'Z'],
        'arabic' => ['Ш§', 'ШЁ', 'ШЄ', 'Ш«', 'Ш¬', 'Ш­', 'Ш®', 'ШЇ', 'Ш°', 'Ш±', 'ШІ', 'Ші', 'Шґ', 'Шµ', 'Ш¶', 'Ш·', 'Шё', 'Ш№', 'Шє', 'ЩЃ', 'Щ‚', 'Щѓ', 'Щ„', 'Щ…', 'Щ†', 'Щ‡', 'Щ€', 'ЩЉ'],
		'italian' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'Z'],
		'portuguese' => ['A', 'ГЃ', 'Г‚', 'Гѓ', 'B', 'C', 'Г‡', 'D', 'E', 'Г‰', 'ГЉ', 'F', 'G', 'H', 'I', 'ГЌ', 'J', 'K', 'L', 'M', 'N', 'O', 'Г“', 'Г”', 'Г•', 'P', 'Q', 'R', 'S', 'T', 'U', 'Гљ', 'V', 'W', 'X', 'Y', 'Z'],
        'hebrew' => ['Чђ', 'Ч‘', 'Ч’', 'Ч“', 'Ч”', 'Ч•', 'Ч–', 'Ч—', 'Ч�', 'Ч™', 'Ч›', 'Чњ', 'Чћ', 'Ч ', 'ЧЎ', 'Чў', 'Ч¤', 'Ч¦', 'Ч§', 'ЧЁ', 'Ч©', 'ЧЄ'],
        'russian' => ['Рђ', 'Р‘', 'Р’', 'Р“', 'Р”', 'Р•', 'РЃ', 'Р–', 'Р—', 'Р�', 'Р™', 'Рљ', 'Р›', 'Рњ', 'Рќ', 'Рћ', 'Рџ', 'Р ', 'РЎ', 'Рў', 'РЈ', 'Р¤', 'РҐ', 'Р¦', 'Р§', 'РЁ', 'Р©', 'РЄ', 'Р«', 'Р¬', 'Р­', 'Р®', 'РЇ'],
        'ukrainian' => ['Рђ', 'Р‘', 'Р’', 'Р“', 'Тђ', 'Р”', 'Р•', 'Р„', 'Р–', 'Р—', 'Р�', 'Р†', 'Р‡', 'Р™', 'Рљ', 'Р›', 'Рњ', 'Рќ', 'Рћ', 'Рџ', 'Р ', 'РЎ', 'Рў', 'РЈ', 'Р¤', 'РҐ', 'Р¦', 'Р§', 'РЁ', 'Р©', 'Р¬', 'Р®', 'РЇ'],
        'thai' => ['аёЃ', 'аё‚', 'аёѓ', 'аё„', 'аё…', 'аё†', 'аё‡', 'аё€', 'аё‰', 'аёЉ', 'аё‹', 'аёЊ', 'аёЌ', 'аёЋ', 'аёЏ', 'аёђ', 'аё‘', 'аё’', 'аё“', 'аё”', 'аё•', 'аё–', 'аё—', 'аё�', 'аё™', 'аёљ', 'аё›', 'аёњ', 'аёќ', 'аёћ', 'аёџ', 'аё ', 'аёЎ', 'аёў', 'аёЈ', 'аёҐ', 'аё§', 'аёЁ', 'аё©', 'аёЄ', 'аё«', 'аё¬', 'аё­', 'аё®'],
		'dutch' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'],
        'hindi' => ['а¤…', 'а¤†', 'а¤‡', 'а¤€', 'а¤‰', 'а¤Љ', 'а¤‹', 'а¤Џ', 'а¤ђ', 'а¤“', 'а¤”', 'а¤…а¤‚', 'а¤…а¤ѓ', 'а¤•', 'а¤–', 'а¤—', 'а¤�', 'а¤љ', 'а¤›', 'а¤њ', 'а¤ќ', 'а¤џ', 'а¤ ', 'а¤Ў', 'а¤ў', 'а¤Ј', 'а¤¤', 'а¤Ґ', 'а¤¦', 'а¤§', 'а¤Ё', 'а¤Є', 'а¤«', 'а¤¬', 'а¤­', 'а¤®', 'а¤Ї', 'а¤°', 'а¤І', 'а¤µ', 'а¤¶', 'а¤·', 'а¤ё', 'а¤№'],
        'bengali' => ['а¦…', 'а¦†', 'а¦‡', 'а¦€', 'а¦‰', 'а¦Љ', 'а¦‹', 'а¦Џ', 'а¦ђ', 'а¦“', 'а¦”', 'а¦•', 'а¦–', 'а¦—', 'а¦�', 'а¦™', 'а¦љ', 'а¦›', 'а¦њ', 'а¦ќ', 'а¦ћ', 'а¦џ', 'а¦ ', 'а¦Ў', 'а¦ў', 'а¦Ј', 'а¦¤', 'а¦Ґ', 'а¦¦', 'а¦§', 'а¦Ё', 'а¦Є', 'а¦«', 'а¦¬', 'а¦­', 'а¦®', 'а¦Ї', 'а¦°', 'а¦І', 'а¦¶', 'а¦·', 'а¦ё', 'а¦№'],
    );
	
    /**
     * 
     * get the alphabet
     * @param $language
     */
    public function getAlphabet($language) {
         
    	$language = strtolower($language);
        
    	if(isset($this->alphabets[$language]) == false)
    		return(array());
    	
    	return($this->alphabets[$language]);
    }
    
    /**
     * check or output error if language not match
     */
    private function checkOutputError($arrAlphabet){
		
    	if(empty($arrAlphabet)){
			dmp("$arg1 language not exists. Please choose one of those: ");
			$arrLanguages = $this->getLanguages();
			
			dmp($arrLanguages);
		}
    	
    }
    
    /**
     * get alphabet for widget output
     */
    public function getAlphabetForWidget($language) {

    	$arrAlphabet = $this->getAlphabet($language);
    	
    	$this->checkOutputError($arrAlphabet);
    	
    	return($arrAlphabet);
    }
    
    /**
     * get new way of output for the widget
     */
    public function getAlphabetForWidgetNew($language){
    	
    	$objFilters = new UniteCreatorFiltersProcess();
    	    	
    	$arrAlphabet = $this->getAlphabet($language);
    	$this->checkOutputError($arrAlphabet);
    	
    	$arrPostsCount = array();
		$includeCount = false;
    	
		if(GlobalsProviderUC::$isUnderAjax == true){
			
			$includeCount = true;
			
    		$arrPostsCount = $objFilters->getAlphabetPostsCount();
		}
    	
		//prepare output
		
		$arrOutput = array();
		
		foreach($arrAlphabet as $letter){
			
			$item = array("letter"=>$letter);
			
			if($includeCount == true)
				$item["count"] = UniteFunctionsUC::getVal($arrPostsCount, $letter,0);
			
			$arrOutput[] = $item;
		}
		
		return($arrOutput);
    }
    
    
    
    /**
     * get the alphabet languages
     */
    public function getLanguages(){
    	
    	return(array_keys($this->alphabets));
    }
    
    /**
     * print the languages
     */
    public function printLanguages(){
    	
    	$langs = $this->getLanguages();
    	dmp($langs);
    	
    }
    
} 
