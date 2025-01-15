<?php 
/**
 * @package Unlimited Elements
 * @author unlimited-elements.com
 * @copyright (C) 2021 Unlimited Elements, All Rights Reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * */
defined('UNLIMITED_ELEMENTS_INC') or die('Restricted access');

class UniteCreatorAjaxSeach{
	
	public static $arrCurrentParams;
	public static $customSearchEnabled = false;
	public static $enableThirdPartyHooks = false;

    private $searchInPostFields = false;
	private $searchInMeta = false;
	private $searchMetaSku = false;
	private $searchInTerms = false;
	private $strTerms = false;
	private $searchPostFields = array();
	private $searchMetaKey = "";

	
	/**
	 * on posts response
	 */
	public function onPostsResponse($arrPosts, $value, $filters){
		
		if(GlobalsProviderUC::$isUnderAjaxSearch == false)
			return($arrPosts);
		
		$name = UniteFunctionsUC::getVal($value, "uc_posts_name");
		
		$args = GlobalsProviderUC::$lastQueryArgs;
		
		$maxItems = UniteFunctionsUC::getVal($args, "posts_per_page", 9);
		
		$numPosts = count($arrPosts);
				
		//if maximum reached - return the original
		
		$addCount = $maxItems - count($arrPosts);
		
		if($addCount <= $maxItems){
						
			if(GlobalsProviderUC::$showPostsQueryDebug == true && $addCount <= 0){
				dmp("Max posts reach");
			}
			
		}
				
		//search by post fields
		if($this->searchInPostFields == true && $addCount > 0){
									
			$arrPosts = $this->getPostsByFields($arrPosts, $args);
			
			$addCount = $maxItems - count($arrPosts);
			
			if(GlobalsProviderUC::$showPostsQueryDebug == true && $addCount <= 0){
				dmp("Max posts reach");
			}
			
		}

		//search in meta
		if($this->searchInMeta == true && $addCount > 0){
			$arrPosts = $this->getPostsByMeta($arrPosts, $args, $addCount);
			$addCount = $maxItems - count($arrPosts);
			
			if(GlobalsProviderUC::$showPostsQueryDebug == true && $addCount <= 0){
				dmp("Max posts reach");
			}
			
		}
		
		//search in taxonomy
		if($this->searchInTerms == true && $addCount > 0){
			
			$arrPosts = $this->getPostsByTerms($arrPosts, $args, $addCount);
			
			$addCount = $maxItems - count($arrPosts);
			
			if(GlobalsProviderUC::$showPostsQueryDebug == true && $addCount <= 0){
				dmp("Max posts reach");
			}
			
		}

		if (GlobalsProviderUC::$showPostsQueryDebug == true ) {
			
			//print total posts
			
			$totalPosts = count($arrPosts);
			
			dmp("<strong>Total Posts: {$totalPosts} </strong>");
		}

		return($arrPosts);
	}


	/**
	 * get posts by title or content or excerpt
	 */
	private function getPostsByFields($arrPosts, $args) {

		if (in_array('all', $this->searchPostFields))
			return ($arrPosts);

		if(empty($this->searchPostFields))
			return($arrPosts);
		
		
		$db         = HelperUC::getDB();
		$tablePosts = UniteProviderFunctionsUC::$tablePosts;
		$postType   = UniteFunctionsUC::getVal($args, "post_type");
		$limit      = UniteFunctionsUC::getVal($args, "posts_per_page");
		$postFields = $this->searchPostFields;
		
		$search     = $db->escape($args["s"]);
		$arrPosts   = array();

		$sqlWhere   = array();
		$sqlAnd     = "";
		$sqlLike    = "";

		// if postType contains array of post types or one post type
		if (is_array($postType)) {
			$sqlAnd = "'" . implode("','", $postType) . "'";
			$sqlAnd = "post_type IN ({$sqlAnd})";
		} else {
			$sqlAnd = "post_type = '{$postType}'";
		}

		//prepare the where
		
		foreach ($postFields as $field) 
		    $sqlWhere[] = "{$field} LIKE '%{$search}%'";
    
		$sqlLike = implode(' OR ', $sqlWhere);
    	
		$sql = "
		    SELECT *
		      FROM {$tablePosts}
		     WHERE ({$sqlLike})
		       AND {$sqlAnd}
		       AND post_status = 'publish'
		     LIMIT {$limit}
		";
 
		try{
			$arrPostsByFields = @$db->fetchSql($sql, false, 'OBJECT');
		}catch(Exception $e){
			
			dmp("Wrong sql: ".$sql);
			
			throw($e);
		}

		//debug output
		if (GlobalsProviderUC::$showPostsQueryDebug == true) {
			
			dmp("<strong>Search By Post Fields</strong> - ".implode(', ', $postFields));
			dmp("Query:");
			dmp($sql);
			dmp("Found Posts: " . count($arrPostsByFields));
			
		}

		$arrPosts = array_merge($arrPosts, $arrPostsByFields);

		return($arrPosts);
	}

	/**
	 * get posts from meta query
	 */
	private function getPostsByMeta($arrPosts, $args, $maxPosts){

		$search = $args["s"];
		unset($args["s"]);
		
		if($this->searchMetaSku == true){
			$metaKeySku = "_sku";
			$this->searchMetaKey = ($this->searchMetaKey) ? $this->searchMetaKey.','.$metaKeySku : $metaKeySku;
		}
		
		if(empty($this->searchMetaKey))
			return($arrPosts);

		$searchMetaKeys = explode(",", $this->searchMetaKey);
		$arrMetaQuery = array("relation" => "OR");

		foreach ($searchMetaKeys as $metaKey) {
			$metaKey = trim($metaKey);
			$arrMetaQuery[] = array(
				'key'     => $metaKey,
				'value'   => $search,
				'compare' => "LIKE"
			);
		}

		$arrExistingMeta = UniteFunctionsUC::getVal($args, "meta_query", array());
		$args["meta_query"] = array_merge($arrExistingMeta, $arrMetaQuery);

		$query = new WP_Query();
		$query->query($args);
		$arrPostsByMeta = $query->posts;

		//debug output
		if(GlobalsProviderUC::$showPostsQueryDebug == true){
			
			dmp("<strong>Search By Meta Fields</strong>");
			dmp("Query:");
			dmp($args);
			dmp("Found Posts: ".count($arrPostsByMeta));
			
		}

		$arrPosts = array_merge($arrPosts, $arrPostsByMeta);

		return($arrPosts);
	}


	/**
	 * search posts by terms
	 */
	private function getPostsByTerms($arrPosts, $args, $maxPosts){

		if($this->searchInTerms == false)
			return($arrPosts);

		$search = $args["s"];

		unset($args["s"]);

		$postType = UniteFunctionsUC::getVal($args, "post_type");

		if(empty($postType))
			return($arrPosts);

		$arrTax = UniteFunctionsWPUC::getPostTypeTaxomonies($postType);



		if(empty($arrTax))
			return($arrPosts);

		$arrAllTaxNames = array_keys($arrTax);

		$arrTaxNames = UniteFunctionsUC::csvToArray($this->strTerms);

		if(!empty($arrTaxNames))
			$arrTaxNames = array_intersect($arrAllTaxNames, $arrTaxNames);
		else
			$arrTaxNames = $arrAllTaxNames;


		if(empty($arrTaxNames)){

			if(GlobalsProviderUC::$showPostsQueryDebug == true) {
				
				dmp("<strong>Search By Terms</strong> ");
				dmp("Taxonomies not found: {$this->strTerms}. please use some of those: ");
				dmp($arrAllTaxNames);
				
			}

			return($arrPosts);
		}


		$arrTermsSearch = array();
		$arrTermsSearch["taxonomy"] = $arrTaxNames;
		$arrTermsSearch["search"] = $search;
		$arrTermsSearch["hide_empty"] = true;
		$arrTermsSearch["number"] = 50;
		//$arrTermsSearch["fields"] = "id=>name";

		$termsQuery = new WP_Term_Query();
		$arrTermsFound = $termsQuery->query($arrTermsSearch);


		if(empty($arrTermsFound)){
			if(GlobalsProviderUC::$showPostsQueryDebug == true){
				dmp("no terms found by: <b>$search</b>. Terms Query:");
				dmp($arrTermsSearch);
			}

			return($arrPosts);
		}
		
		$arrTaxQuery = UniteFunctionsWPUC::getTaxQueryFromTerms($arrTermsFound);
		$args = UniteFunctionsWPUC::mergeArgsTaxQuery($args,$arrTaxQuery);

		$query = new WP_Query();
		$query->query($args);
		$arrPostsByTerms = $query->posts;

		//debug output
		if(GlobalsProviderUC::$showPostsQueryDebug == true){
			dmp("<strong>Search By Terms</strong>");
			dmp("Query:");
			$strTerms = UniteFunctionsWPUC::getTermsTitlesString($arrTermsFound, true);
			dmp($strTerms);
			dmp($args);
			dmp("Found Terms: ".count($arrTermsFound));
			dmp("Found Posts: ".count($arrPostsByTerms));
			
		}


		if(empty($arrPostsByTerms))
			return($arrPosts);

		$arrPosts = array_merge($arrPosts, $arrPostsByTerms);

		//remove duplicates if there are posts with the same ID in the array after merging two arrays "$arrPostsByTerms" and "$arrPosts"
		if (!empty($arrPosts))
			$arrPosts = UniteFunctionsWPUC::deleteDuplicatePostsFromArray($arrPosts);
		

		return($arrPosts);
	}


	/**
	 * supress third party filters except of this class ones
	 */
	public static function supressThirdPartyFilters(){


		//on the enable hooks setting - don't supress hooks

		if(self::$enableThirdPartyHooks === true)
			return(false);

		global $wp_filter;

		if(self::$customSearchEnabled == false){

			$wp_filter = array();
			return(false);
		}

		$arrKeys = array("uc_filter_posts_list");

		$newFilters = array();

		foreach($arrKeys as $key){

			$filter = UniteFunctionsUC::getVal($wp_filter, $key);

			if(!empty($filter))
				$newFilters[$key] = $filter;
		}

		$wp_filter = $newFilters;

	}
	
	
	/**
	 * init the ajax search - before the get posts accure, from ajax request
	 */
	public function initCustomAjaxSeach(UniteCreatorAddon $addon){
		
		$arrParams = $addon->getProcessedMainParamsValues(UniteCreatorParamsProcessor::PROCESS_TYPE_CONFIG);
		
		self::$arrCurrentParams = $arrParams;
		
		//enable hooks
		
		$enableHooks = UniteFunctionsUC::getVal($arrParams, "enable_third_party_hooks");
		$enableHooks = UniteFunctionsUC::strToBool($enableHooks);
		
		if($enableHooks == true)
			self::$enableThirdPartyHooks = true;

		$applyModifyFilter = false;

		//search by meta fields
		$searchInMeta = UniteFunctionsUC::getVal($arrParams, "search_in_meta");
		$searchInMeta = UniteFunctionsUC::strToBool($searchInMeta);
		$searchMetaKey = UniteFunctionsUC::getVal($arrParams, "searchin_meta_name");
		if($searchInMeta == true){
			$applyModifyFilter = true;
			self::$customSearchEnabled = true;
			$this->searchInMeta = true;
			$this->searchMetaKey = $searchMetaKey;
		}

		//search by meta field SKU
		$searchMetaSku = UniteFunctionsUC::getVal($arrParams, "search_by_sku");
		$searchMetaSku = UniteFunctionsUC::strToBool($searchMetaSku);
		if($searchMetaSku == true){
			self::$customSearchEnabled = true;
			$this->searchInMeta = true;
			$this->searchMetaSku = $searchMetaSku;
		}
	
		//search by terms
		$searchInTerms = UniteFunctionsUC::getVal($arrParams, "search_in_terms");
		$searchInTerms = UniteFunctionsUC::strToBool($searchInTerms);
		if($searchInTerms == true){
			$applyModifyFilter = true;
			self::$customSearchEnabled = true;
			$this->searchInTerms = true;
			$this->strTerms = UniteFunctionsUC::getVal($arrParams, "search_in_taxonomy");
		}
		
		//search by post fields
		$arrSearchPostFields = UniteFunctionsUC::getVal($arrParams, "search_by");
		
		if(!empty($arrSearchPostFields) && in_array("all", $arrSearchPostFields) == false){
			
			//set skip main post query
			GlobalsProviderUC::$skipRunPostQueryOnce = true;
			
			$applyModifyFilter = true;
			
			self::$customSearchEnabled = true;
			$this->searchInPostFields = true;
			$this->searchPostFields = $arrSearchPostFields;
		}
		
		//skip main query if just meta or terms selected for example
		
		if(empty($arrSearchPostFields) && $applyModifyFilter == true){
			GlobalsProviderUC::$skipRunPostQueryOnce = true;			
		}
		
		if($applyModifyFilter == true)
			UniteProviderFunctionsUC::addFilter("uc_filter_posts_list", array($this,"onPostsResponse"),10,3);
		
	}

}
