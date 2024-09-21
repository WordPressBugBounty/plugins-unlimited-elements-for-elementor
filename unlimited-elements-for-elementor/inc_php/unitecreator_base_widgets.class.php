<?php
/**
 * @package Unlimited Elements
 * @author unlimited-elements.com
 * @copyright (C) 2021 Unlimited Elements, All Rights Reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * */
defined('UNLIMITED_ELEMENTS_INC') or die('Restricted access');

class UniteCreatorBaseWidgets{

	private $addon;
	private $baseAddon;
	private $arrAlises = array();
	
	
	/**
	 * merge categories
	 */
	private function mergeBase_cats(){
		
		$baseCats = $this->baseAddon->getParamsCats();
		
		$cats = $this->addon->getParamsCats();
		
		$catsAssoc = UniteFunctionsUC::arrayToAssoc($cats,"id");
		
		$baseCatsAssoc = UniteFunctionsUC::arrayToAssoc($baseCats,"id");
		
		
		//modify base cats id's
		
		foreach($baseCatsAssoc as $id=>$cat){

			$idOld = $id;
			
			$id = "base_".$id;
			
			$cat["id"] = $id;
			
			$baseCatsAssoc[$id] = $cat;
			
			unset($baseCatsAssoc[$idOld]);
			
		}
		
		$newCats = array_merge($baseCatsAssoc, $catsAssoc);
		
		$newCats = UniteFunctionsUC::assocToArray($newCats);
		
				
		return($newCats);
	}
	
	
	/**
	 * merge params
	 */
	private function mergeBase_params(){
		
		$baseParams = $this->baseAddon->getParams();
		
		$params = $this->addon->getParams();
		
		//modify the ID
		
		foreach($baseParams as $key=>$param){
			
			$catID = UniteFunctionsUC::getVal($param, "__attr_catid__");
			
			$catID = "base_".$catID;
			
			$param["__attr_catid__"] = $catID;
			
			$baseParams[$key] = $param;
		}
		
		$params = array_merge($baseParams, $params);

		return($params);
	}
	
	
	/**
	 * merge base widget
	 */
	private function mergeBaseWidget($alias){
		
		if(isset($this->arrAlises[$alias]))
			UniteFunctionsUC::throwError("Can't merge base widget: $alias couple of times");
		
		$this->arrAlises[$alias] = true;
		
		$this->baseAddon = new UniteCreatorAddon();
		
		$this->baseAddon->initByAlias($alias, GlobalsUC::ADDON_TYPE_ELEMENTOR);
		
		//merge cats params
		
		$newParamCats = $this->mergeBase_cats();
		$this->addon->setParamsCats($newParamCats);
		
		//merge params
		
		$newParams = $this->mergeBase_params();
		
		$this->addon->setParams($newParams);
		
	}
	
	
	
	/**
	 * get base widgets
	 */
	private function getBaseWidgets(){
				
		$arrSpecialParams = $this->addon->getParams(UniteCreatorDialogParam::PARAM_SPECIAL);
		
		if(empty($arrSpecialParams))
			return(null);

		$arrBaseParams = UniteFunctionsUC::filterArrayByKeyValue($arrSpecialParams, "attribute_type", "base_widget");
		
		if(empty($arrBaseParams))
			return(null);
		
		foreach($arrBaseParams as $param){
			
			//take the hard coded meanwhile
			$alias = "base_layouts";
			
			$this->mergeBaseWidget($alias);
						
		}
		
	}
	
	/**
	 * init addon
	 */
	public function initAddon(UniteCreatorAddon $addon){

		if(!empty($this->addon))
			UniteFunctionsUC::showTrace("Base widgets alrady inited!");
		
		$this->addon = $addon;

		$arrBaseWidgets = $this->getBaseWidgets();
		
	}
	
	
	
	
	
	
}