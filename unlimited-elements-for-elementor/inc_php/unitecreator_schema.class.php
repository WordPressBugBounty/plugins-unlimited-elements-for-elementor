<?php
/**
 * @package Unlimited Elements
 * @author UniteCMS Enhanced
 * @copyright Copyright (c) 2016-2024 UniteCMS
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

if(!defined('ABSPATH')) exit;
class UniteCreatorSchema {
	
	private static $arrCollectedSchemaData = array();
	private static $arrSchemas = array();
	
	private $debugFields = array();
	
	private static $showDebug = false;
	private $objAddon;
	
	
	const ROLE_TITLE = "title";
	const ROLE_DESCRIPTION = "description";
	const ROLE_HEADING = "heading";
		
	const ROLE_LINK = "link";
	const ROLE_CONTENT = "content";
	const ROLE_IMAGE = "image";
	
	const ROLE_AUTO = "_auto_";
	
	const CONTENT_FIELDS = "content_fields";
	
	const SCHEMA_ORG_SITE = "https://schema.org";
	
	/**
	 * get schemas array
	 */
	private function getArrSchemas(){
		
		if(!empty(self::$arrSchemas))
			return(self::$arrSchemas);
		
		$arrSchemas = array(
			array(
				"type"=>"FAQPage",
				"title"=>"FAQ",
				"multiple"=>true
			),
			array(
				"type"=>"Person",
				"title"=>"List Of Person"
			),
			array(
				"type"=>"HowTo"
			),
			array(
				"type"=>"Course",
				"title"=>"List Of Courses"
			),
			array(
				"type"=>"ItemList",
				"title"=>"Items List",
				"multiple"=>true
			),
			array(
				"type"=>"Event"
			),
			array(
				"type"=>"Place",
				"title"=>"List Of Places"
			),
			array(
				"type"=>"TouristDestination",
				"title"=>"Tourist Destinations List"
			),
			array(
				"type"=>"EventSeries",
				"title"=>"Event Series",
				"multiple"=>true
			),
			array(
				"type"=>"MusicPlaylist",
				"title"=>"Music Playlist",
				"multiple"=>true
			),
			array(
				"type"=>"SearchResultsPage",
				"title"=>"Search Results Page",
				"multiple"=>true
			),
			array(
				"type"=>"CourseCatalog",
				"title"=>"Course Catalog",
				"multiple"=>true
			),
			
		);
		
		$arrOutput = array();
		
		foreach($arrSchemas as $schama){
			
			$type = UniteFunctionsUC::getVal($schama, "type");
			
			$name = strtolower($type);
			
			$title = UniteFunctionsUC::getVal($schama, "title"); 
						
			if(!empty($title))
				$title .= " ($type)";
			else
				$title = $type;
			
			$schama["name"] = $name;
			$schama["title"] = $title;
			
			$arrOutput[$name] = $schama;
		}
		
		self::$arrSchemas = $arrOutput;
		
		return(self::$arrSchemas);
	}
	
	/**
	 * get schema options by name
	 */
	private function getSchemaOptionsByName($name){
		
		$arrSchemas = $this->getArrSchemas();
		
		$arrSchema = UniteFunctionsUC::getVal($arrSchemas, $name);
		
		return($arrSchema);
	}
	
	/**
	 * set the addon
	 */
	public function setObjAddon($addon){
		
		$this->objAddon = new UniteCreatorAddon();
		
		$this->objAddon = $addon;
	}
	
	
	/**
	 * put html items schema
	 */
	public function putSchemaItems($schemaType, $arrItems, $paramsItems, $arrSettings){
		
				
		if(empty($schemaType))
			$schemaType = self::SCHEMA_TYPE_FAQ;
		
		$title = UniteFunctionsUC::getVal($arrSettings, "title");
		
		$title = wp_strip_all_tags($title);
				
		if(!isset(self::$arrCollectedSchemaData[$schemaType]))
			self::$arrCollectedSchemaData[$schemaType] = array("addons"=>array());
		
		self::$arrCollectedSchemaData[$schemaType]["addons"][] = array(
			"items"=>$arrItems,
			"params"=>$paramsItems,
			"settings"=>$arrSettings
		);
		
		//---- add title
		
		$existingTitle = "";
		if(isset(self::$arrCollectedSchemaData[$schemaType]["title"]))
			$existingTitle = self::$arrCollectedSchemaData[$schemaType]["title"];
					
		if(!empty($title) && empty($existingTitle))
			self::$arrCollectedSchemaData[$schemaType]["title"] = $title;
		
		$showDebug = UniteFunctionsUC::getVal($arrSettings, "debug");
		$showDebug = UniteFunctionsUC::strToBool($showDebug);
		
		
		//set the debug
		
		if($showDebug === true){
			self::$showDebug = true;
			
			$this->showDebugMessage();
		}	
		
	}

	
	
	/**
	 * show schema code
	 */
	public function showAddonSchema(){
		
		if(self::$showDebug == false){
			self::$showDebug = HelperUC::hasPermissionsFromQuery("ucschemadebug");
		}

		if(empty(self::$arrCollectedSchemaData))
			return false;
		
		foreach (self::$arrCollectedSchemaData as $schemaType => $data)
				$this->putAddonSchema($schemaType, $data);
			
		
		self::$arrCollectedSchemaData = array();
	}
	
	


	/**
	 * generate schema code
	 */
	private function putAddonSchema($schemaType, $data) {
		
		$arrSchema = $this->generateSchemaByType($schemaType, $data);
		
		if(empty($arrSchema)) 
			return;
		
		$jsonItems = json_encode($arrSchema, JSON_UNESCAPED_UNICODE);
		
		if($jsonItems === false)
			return(false);
		
		$strSchema = '<script type="application/ld+json">' . $jsonItems . '</script>';
		
		//show debug by url
		if(self::$showDebug == true)
			$this->showDebugSchema($schemaType, $arrSchema);
		
		echo $strSchema;
	}
	




	
	/**
	 * get fields by type
	 */
	private function getParamNamesByTypes($params, $type){
				
		$arrFieldNames = array();
		
		foreach($params as $param){
			
			$fieldType = UniteFunctionsUC::getVal($param, "type");
			
			//content fields check
			
			if($type == self::CONTENT_FIELDS){
				
				$typeFound = in_array($fieldType, array(UniteCreatorDialogParam::PARAM_TEXTAREA, 
					  							   UniteCreatorDialogParam::PARAM_EDITOR) 
				);
				
			}else 
				$typeFound = $fieldType == $type;
				
			if($typeFound == false)
				continue;
			
			$name = UniteFunctionsUC::getVal($param, "name");
			
			$arrFieldNames[$name] = $fieldType;
		}
		
		return($arrFieldNames);
	}
	
	
	/**
	 * get fields by the params roles 
	 */
	private function getFieldsByRoles($params){
		
		$arrRoles = array();
		
		$roleTitle = "";
		$roleHeading = "";
		$roleDescription = "";
		$roleLink = "";
		$roleImage = "";
		
		//get title
		
		$arrTextParams = $this->getParamNamesByTypes($params, UniteCreatorDialogParam::PARAM_TEXTFIELD);
		
		if(isset($arrTextParams["title"]))
			$roleTitle = "title";
		else
			$roleTitle = UniteFunctionsUC::getFirstNotEmptyKey($arrTextParams);
		
		if(!empty($roleTitle))
			unset($arrTextParams[$roleTitle]);
		
		//guess heading
		
		if(isset($arrTextParams["heading"])){
			$roleHeading = "heading";
			unset($arrTextParams[$roleHeading]);
		}
		
		//get description from text or content
		
		$arrContentParams = $this->getParamNamesByTypes($params, self::CONTENT_FIELDS);
		
		
		//get from text params with name: 'description'
		
		if(isset($arrTextParams["description"])){
			$roleDescription = $arrTextParams["description"];
			unset($arrTextParams["description"]);
		}
		
		//get from content params
		
		if(empty($roleDescription)){
			
			if(isset($arrContentParams["description"]))
				$roleDescription = "description";
			else
				$roleDescription = UniteFunctionsUC::getFirstNotEmptyKey($arrContentParams);	//get first key
			
			if(!empty($roleDescription))
				unset($arrContentParams[$roleDescription]);
		}
		
		
		//guess content - get first field from the content
		if(!empty($arrContentParams))
			$roleContent = UniteFunctionsUC::getFirstNotEmptyKey($arrContentParams);
		
		//copy from description if empty
		if(empty($roleContent))
			$roleContent = $roleDescription;
		
		
		//guess link
		
		$arrLinkParams = $this->getParamNamesByTypes($params, UniteCreatorDialogParam::PARAM_LINK);
		
		if(!empty($arrLinkParams))
			$roleLink = UniteFunctionsUC::getFirstNotEmptyKey($arrLinkParams);
		
		//guess image
		
		$arrImageParams = $this->getParamNamesByTypes($params, UniteCreatorDialogParam::PARAM_IMAGE);
		
		if(isset($arrImageParams["image"]))
			$roleImage = "image";
		
		if(!empty($arrImageParams))
			$roleImage = UniteFunctionsUC::getFirstNotEmptyKey($arrImageParams);
		
		
		//return the params
		
		$arrOutput = array(
			self::ROLE_TITLE => $roleTitle,
			self::ROLE_DESCRIPTION => $roleDescription,
			self::ROLE_HEADING => $roleHeading,
			self::ROLE_CONTENT => $roleContent,
			self::ROLE_LINK => $roleLink,
			self::ROLE_IMAGE => $roleImage
		);
		
		
		return($arrOutput);
	}

	/**
	 * Get final fields mapping by roles, using manual settings if enabled.
	 *
	 * @param array $paramsItems The widget params items
	 * @param array $arrSettings The widget settings
	 * @return array The final mapping: role => paramName
	 */
	private function getFieldsByRolesFinal($paramsItems, $arrSettings) {
		
	    $arrFieldsByRoles = $this->getFieldsByRoles($paramsItems);
	
	    // Check if manual mapping is enabled
	    $isMappingEnabled = UniteFunctionsUC::getVal($arrSettings, "enable_mapping");
	    $isMappingEnabled = UniteFunctionsUC::strToBool($isMappingEnabled);
	    
	    if ($isMappingEnabled == false)
	        return $arrFieldsByRoles;
	
	    $arrManualRoles = array(
	        self::ROLE_TITLE,
	        self::ROLE_DESCRIPTION,
	        self::ROLE_HEADING,
	        self::ROLE_CONTENT
	    );
	
	    foreach ($arrManualRoles as $roleKey) {
	        $manualValue = UniteFunctionsUC::getVal($arrSettings, "fieldmap_" . $roleKey);
	        
	        if (!empty($manualValue) && $manualValue !== self::ROLE_AUTO) {
	            $arrFieldsByRoles[$roleKey] = $manualValue;
	        }
	    }
		
	    return $arrFieldsByRoles;
	}	

	

	/**
	 * get item schema content
	 */
	private function getItemSchemaContent($item, $arrFieldsByRoles){
		
		if(isset($item["item"]))
			$item = $item["item"];
		
		$arrContent = array();
				
		foreach($arrFieldsByRoles as $role => $fieldName){
			
			$value = UniteFunctionsUC::getVal($item, $fieldName);
			
			switch($role){
				case self::ROLE_TITLE:
				case self::ROLE_CONTENT:
				case self::ROLE_HEADING:
				case self::ROLE_DESCRIPTION:
					$value = wp_strip_all_tags($value);
				break;
				case self::ROLE_IMAGE:
				case self::ROLE_LINK:
					$value = UniteFunctionsUC::sanitize($value, UniteFunctionsUC::SANITIZE_URL);
				break;
				
			}
			
			$arrContent[$role] = $value;
			
		}		
		
		return($arrContent);
	}
	
	private function a____________SETTINGS________(){}
	
/**
 * Add UI for fields mapping by roles.
 *
 * Adds enable mapping toggle + "Auto" option for each field.
 *
 * @param UniteCreatorDialogSettings $objSettings
 * @param string $name
 * @param array $paramsItems
 */
private function addFieldsMappingSettings($objSettings, $name, $paramsItems) {

    // ---- Add master toggle: enable mapping yes/no ----
    $arrParam = array();
    $arrParam["origtype"] = UniteCreatorDialogParam::PARAM_RADIOBOOLEAN;
    $arrParam["elementor_condition"] = array($name . "_enable" => "true");
    $arrParam["description"] = __("Enable manual fields mapping by roles", "unlimited-elements-for-elementor");
	
    $objSettings->addRadioBoolean(
        "{$name}_enable_mapping",
        __("Enable Fields Mapping", "unlimited-elements-for-elementor"),
        false,
        "Yes",
        "No",
        $arrParam
    );

	
    // ---- Build field options: only textfield, textarea, editor ----
    $arrFieldOptions = array();
    $arrFieldOptions[self::ROLE_AUTO] = __("[Auto Detect]", "unlimited-elements-for-elementor");
    
    foreach ($paramsItems as $param) {
        $paramName = UniteFunctionsUC::getVal($param, "name");
        $paramTitle = UniteFunctionsUC::getVal($param, "title");
        $paramType = UniteFunctionsUC::getVal($param, "type");

        if (empty($paramName))
            continue;

        $isTextType = ($paramType === UniteCreatorDialogParam::PARAM_TEXTFIELD);
        $isContentType = in_array($paramType, array(
            UniteCreatorDialogParam::PARAM_TEXTAREA,
            UniteCreatorDialogParam::PARAM_EDITOR
        ));

        if (!$isTextType && !$isContentType)
            continue;

        $arrFieldOptions[$paramName] = $paramTitle;
    }

    // ---- Flip options: label => value ----
    $arrFieldOptions = array_flip($arrFieldOptions);

    
    // ---- Define roles (only text/content roles) ----
    
    $arrRoles = array(
        self::ROLE_TITLE => __("Title Field", "unlimited-elements-for-elementor"),
        self::ROLE_DESCRIPTION => __("Description Field", "unlimited-elements-for-elementor"),
        self::ROLE_HEADING => __("Heading Field", "unlimited-elements-for-elementor"),
        self::ROLE_CONTENT => __("Content Field", "unlimited-elements-for-elementor"),
    );

    // ---- Add a select control for each text/content role ----
    foreach ($arrRoles as $roleKey => $roleLabel) {

        $arrParam = array();
        $arrParam["origtype"] = UniteCreatorDialogParam::PARAM_DROPDOWN;
        $arrParam["elementor_condition"] = array(
            $name . "_enable" => "true",
            "{$name}_enable_mapping" => "true"
        );

        $objSettings->addSelect(
            "{$name}_fieldmap_{$roleKey}",
            $arrFieldOptions,	//options
            $roleLabel,		//label
            self::ROLE_AUTO,		//default
            $arrParam
        );
    }
}
	

	/**
	 * put schema settings
	 */
	public function addSchemaSettings(&$objSettings, $name, $param){
		
		$arrParam = array();
		$arrParam["origtype"] = UniteCreatorDialogParam::PARAM_RADIOBOOLEAN;
		$arrParam["description"] = UniteFunctionsUC::getVal($param, "description");

		$objSettings->addRadioBoolean($name."_enable", $param["title"],false,"Yes","No",$arrParam);
		
		//------- schema types
		
		$arrSchemas = $this->getArrSchemas();
			
		$arrOptions = array();
		foreach($arrSchemas as $schema){
			
			$schemaName = UniteFunctionsUC::getVal($schema, "name");
			$schemaTitle = UniteFunctionsUC::getVal($schema, "title");
			
			$arrOptions[$schemaName] = $schemaTitle;
		}

		$arrParam = array();
		$arrParam["origtype"] = UniteCreatorDialogParam::PARAM_DROPDOWN;
		$arrParam["elementor_condition"] = array($name."_enable"=>"true");
		$arrOptions = array_flip($arrOptions);
		
		$title = __('Schema Type',"unlimited-elements-for-elementor");
		
		$objSettings->addSelect("{$name}_type", $arrOptions, $title, "faq", $arrParam);
				
		
		//------- main name
		
		$arrParam = array();
		$arrParam["origtype"] = UniteCreatorDialogParam::PARAM_TEXTFIELD;
		$arrParam["elementor_condition"] = array($name."_enable"=>"true",$name."_type"=>
			array("howto", 
				  "faq", 
				  "itemlist", 
				  "eventseries", 
				  "musicplaylist", 
				  "searchresultspage", 
				  "coursecatalog"));
		
		$arrParam["decsription"] = __('Use to describe the action, like how to tie a shoes',"unlimited-elements-for-elementor");
		$arrParam["label_block"] = true;
		
		$title = __('Schema Main Title',"unlimited-elements-for-elementor");
		
		$objSettings->addTextBox($name."_title","", $title, $arrParam);
		
		//---- add schema mapping here:
		
		$paramsItems = $this->objAddon->getParamsItems();
		
		if(!empty($paramsItems))
			$this->addFieldsMappingSettings($objSettings, $name, $paramsItems);
		
		
		
		//------- debug
		
		$arrParam = array();
		$arrParam["origtype"] = UniteCreatorDialogParam::PARAM_RADIOBOOLEAN;
		$arrParam["elementor_condition"] = array($name."_enable"=>"true");
		$arrParam["description"] = __('Show schema debug in front end footer',"unlimited-elements-for-elementor");
		
		$title = __('Show Schema Debug',"unlimited-elements-for-elementor");
		
		$objSettings->addRadioBoolean($name."_debug", $title, false, "Yes","No", $arrParam);
		
	}
	
	
	private function a____________DEBUG________(){}
	
	
	/**
	 * show the debug message under the widget
	 * 
	 */
	private function showDebugMessage(){
		
		$message = __('Schema Debug: This widget will generate schema debug at the footer',"unlimited-elements-for-elementor");

		$html = HelperHtmlUC::getDebugWarningMessageHtml($message);
		
		uelm_echo($html);
		
	}
	
	/**
	 * show schema debug
	 */
	private function showDebugSchema($schemaType, $arrSchema){
		
		dmp("Schema Output Debug: $schemaType");
		
		dmp("The Fields Mapping");
		
		HelperHtmlUC::putHtmlDataDebugBox($this->debugFields);
		
		dmp("The Schema Ouptut");
		
		HelperHtmlUC::putHtmlDataDebugBox($arrSchema);
	}
	
	
	private function a____________SCHEMAS________(){}

	/**
	 * get all schemas items content
	 */
	private function getAllSchemaItemsContent($data, $schemaType){
		
		$arrAddonsData = UniteFunctionsUC::getVal($data, "addons");
		
		if(empty($arrAddonsData))
			return(null);
			
		$arrItemsContent = array();
		
		foreach($arrAddonsData as $addonData){
			
			$items = UniteFunctionsUC::getVal($addonData, "items");
			$params = UniteFunctionsUC::getVal($addonData, "params");
			$settings = UniteFunctionsUC::getVal($addonData, "settings");
			
			//field quessing
			$arrFieldsByRoles = $this->getFieldsByRolesFinal($params, $settings);
			
			//add debug
			
			if(self::$showDebug == true){
				
				$arrParamsAssoc = UniteFunctionsUC::arrayToAssoc($params, "name", "type");
				
				$this->debugFields[$schemaType][] = array("params"=>$arrParamsAssoc,"fieldsbyroles"=>$arrFieldsByRoles);
			}
			
			foreach($items as $item){
				
				$arrContent = $this->getItemSchemaContent($item, $arrFieldsByRoles);
				$arrItemsContent[] = $arrContent;
			}
		}
				
		return($arrItemsContent);
	}
	
	
	/**
	 * Generate schema structure based on specified type
	 */
	private function generateSchemaByType($schemaType, $data) {
		
	    $items = $this->getAllSchemaItemsContent($data, $schemaType);
	    
	    $title = UniteFunctionsUC::getVal($data, "title");
	
		switch ($schemaType) {
		    case "person":
		        return $this->schemaPerson($items);
		    case "howto":
		        return $this->schemaHowTo($items, $title);
		    case "course":
		        return $this->schemaCourse($items);
		    case "itemlist":
		        return $this->schemaItemList($items, $title);
		    case "event":
		        return $this->schemaEvent($items);
		    case "place":
		        return $this->schemaPlace($items);
		    case "touristdestination":
		        return $this->schemaTouristDestination($items);
		    case "eventseries":
		        return $this->schemaEventSeries($items);
		    case "musicplaylist":
		        return $this->schemaMusicPlaylist($items);
		    case "searchresultspage":
		        return $this->schemaSearchResultsPage($items, $title);
		    case "coursecatalog":
		        return $this->schemaCourseCatalog($items, $title);
		    default:
		    case "faq":
		        return $this->schemaFaq($items, $title);
		}
		
}

	private function a____________SCHEMA_FUNCTIONS________(){}

/**
 * FAQ
 */
private function schemaFaq($items, $title = "") {
    $schema = array(
        '@context' => self::SCHEMA_ORG_SITE,
        '@type' => 'FAQPage',
    );
    if (!empty($title)) $schema['name'] = $title;

    $schema['mainEntity'] = array();
    foreach ($items as $item) {
        $schema['mainEntity'][] = array(
            '@type' => 'Question',
            'name' => $item[self::ROLE_TITLE],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => $item[self::ROLE_CONTENT],
            ),
        );
    }
    return $schema;
}

/**
 * HowTo
 */
private function schemaHowTo($items, $title = "") {
    $schema = array(
        '@context' => self::SCHEMA_ORG_SITE,
        '@type' => 'HowTo',
    );
    if (!empty($title)) $schema['name'] = $title;

    $schema['step'] = array();
    foreach ($items as $item) {
        $schema['step'][] = array(
            '@type' => 'HowToStep',
            'name' => $item[self::ROLE_TITLE],
            'text' => $item[self::ROLE_DESCRIPTION],
            'image' => $item[self::ROLE_IMAGE],
            'url' => $item[self::ROLE_LINK],
        );
    }
    return $schema;
}

/**
 * ItemList
 */
private function schemaItemList($items, $title = "") {
    $schema = array(
        '@context' => self::SCHEMA_ORG_SITE,
        '@type' => 'ItemList',
    );
    if (!empty($title)) $schema['name'] = $title;

    $schema['itemListElement'] = array();
    $position = 1;
    foreach ($items as $item) {
        $schema['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $item[self::ROLE_TITLE],
            'url' => $item[self::ROLE_LINK],
        );
    }
    return $schema;
}

/**
 * SearchResultsPage
 */
private function schemaSearchResultsPage($items, $title = "") {
    $schema = array(
        '@context' => self::SCHEMA_ORG_SITE,
        '@type' => 'SearchResultsPage',
    );
    if (!empty($title)) $schema['name'] = $title;

    $schema['mainEntity'] = array(
        '@type' => 'ItemList',
        'itemListElement' => array(),
    );

    $position = 1;
    foreach ($items as $item) {
        $schema['mainEntity']['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $item[self::ROLE_TITLE],
            'url' => $item[self::ROLE_LINK],
        );
    }
    return $schema;
}

/**
 * CourseCatalog
 */
private function schemaCourseCatalog($items, $title = "") {
    $schema = array(
        '@context' => self::SCHEMA_ORG_SITE,
        '@type' => 'CourseCatalog',
    );
    if (!empty($title)) $schema['name'] = $title;

    $schema['hasCourse'] = array();
    foreach ($items as $item) {
        $schema['hasCourse'][] = array(
            '@type' => 'Course',
            'name' => $item[self::ROLE_TITLE],
            'description' => $item[self::ROLE_DESCRIPTION],
        );
    }
    return $schema;
}

/**
 * Person
 */
private function schemaPerson($items) {
    $schema = array();
    foreach ($items as $item) {
        $schema[] = array(
            '@context' => self::SCHEMA_ORG_SITE,
            '@type' => 'Person',
            'name' => $item[self::ROLE_TITLE],
            'jobTitle' => $item[self::ROLE_HEADING],
            'description' => $item[self::ROLE_DESCRIPTION],
            'image' => $item[self::ROLE_IMAGE],
            'sameAs' => $item[self::ROLE_LINK],
        );
    }
    return $schema;
}

/**
 * Course
 */
private function schemaCourse($items) {
    $schema = array();
    foreach ($items as $item) {
        $schema[] = array(
            '@context' => self::SCHEMA_ORG_SITE,
            '@type' => 'Course',
            'name' => $item[self::ROLE_TITLE],
            'description' => $item[self::ROLE_DESCRIPTION],
            'provider' => array(
                '@type' => 'Organization',
                'name' => $item[self::ROLE_HEADING],
                'sameAs' => $item[self::ROLE_LINK],
            ),
        );
    }
    return $schema;
}

/**
 * Event
 */
private function schemaEvent($items) {
    $schema = array();
    foreach ($items as $item) {
        $schema[] = array(
            '@context' => self::SCHEMA_ORG_SITE,
            '@type' => 'Event',
            'name' => $item[self::ROLE_TITLE],
            'description' => $item[self::ROLE_DESCRIPTION],
            'image' => $item[self::ROLE_IMAGE],
            'url' => $item[self::ROLE_LINK],
        );
    }
    return $schema;
}

/**
 * EventSeries
 */
private function schemaEventSeries($items) {
    $schema = array();
    foreach ($items as $item) {
        $schema[] = array(
            '@context' => self::SCHEMA_ORG_SITE,
            '@type' => 'EventSeries',
            'name' => $item[self::ROLE_TITLE],
            'description' => $item[self::ROLE_DESCRIPTION],
            'image' => $item[self::ROLE_IMAGE],
            'sameAs' => $item[self::ROLE_LINK],
        );
    }
    return $schema;
}

/**
 * MusicPlaylist
 */
private function schemaMusicPlaylist($items) {
    $schema = array();
    foreach ($items as $item) {
        $schema[] = array(
            '@context' => self::SCHEMA_ORG_SITE,
            '@type' => 'MusicPlaylist',
            'name' => $item[self::ROLE_TITLE],
            'description' => $item[self::ROLE_DESCRIPTION],
        );
    }
    return $schema;
}

/**
 * Place
 */
private function schemaPlace($items) {
    $schema = array();
    foreach ($items as $item) {
        $schema[] = array(
            '@context' => self::SCHEMA_ORG_SITE,
            '@type' => 'Place',
            'name' => $item[self::ROLE_TITLE],
            'description' => $item[self::ROLE_DESCRIPTION],
            'image' => $item[self::ROLE_IMAGE],
            'sameAs' => $item[self::ROLE_LINK],
        );
    }
    return $schema;
}

/**
 * TouristDestination
 */
private function schemaTouristDestination($items) {
    $schema = array();
    foreach ($items as $item) {
        $schema[] = array(
            '@context' => self::SCHEMA_ORG_SITE,
            '@type' => 'TouristDestination',
            'name' => $item[self::ROLE_TITLE],
            'description' => $item[self::ROLE_DESCRIPTION],
            'image' => $item[self::ROLE_IMAGE],
            'sameAs' => $item[self::ROLE_LINK],
        );
    }
    return $schema;
}
	
}

