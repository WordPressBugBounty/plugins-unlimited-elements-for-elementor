<?php

/**
 * @package Unlimited Elements
 * @author unlimited-elements.com
 * @copyright (C) 2021 Unlimited Elements, All Rights Reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('UNLIMITED_ELEMENTS_INC') or die('Restricted access');


class UniteCreatorRSS{
	
    /**
     * get rss fields
     */
    public function getRssFields($name = null) {
    	
        $fields = array(
            array(
                "type" => UniteCreatorDialogParam::PARAM_TEXTFIELD,
                "id" => "rss_url",
                "text" => $name ? __("RSS Feed URL(".$name.")", "unlimited-elements-for-elementor") : __("RSS URL", "unlimited-elements-for-elementor"),
                "desc" => __("Enter some RSS service url. Example: https://wired.com/feed/rss", "unlimited-elements-for-elementor"),
                "placeholder" => "Example: https://wired.com/feed/rss",
                "label_block" => true,
            	"default" => "https://wired.com/feed/rss"
            ),
            array(
                "type" => UniteCreatorDialogParam::PARAM_RADIOBOOLEAN,
                "id" => "rss_show_filter_by_date",
                "text" => "Filter By Date",
                "default" => "false"
            ),
            array(
                "type" => UniteCreatorDialogParam::PARAM_DROPDOWN,
                "id" => "rss_filter_by_date_option",
                "text" => "Select value",
                "conditions" => array("rss_show_filter_by_date"=>"true"),
                "options" => array(
                    "all" => __("All", "unlimited-elements-for-elementor"),
                    "today" => __("Today", "unlimited-elements-for-elementor"),
                    "yesterday" => __("Yesterday", "unlimited-elements-for-elementor"),
                    "last_2_days" => __("Last 2 Days", "unlimited-elements-for-elementor"),
                    "last_3_days" => __("Last 3 Days", "unlimited-elements-for-elementor")
                ),
                "default" => "all"
            ),
            array(
                "type" => UniteCreatorDialogParam::PARAM_RADIOBOOLEAN,
                "id" => "rss_show_date_formatted",
                "text" => "Format Date",
                "default" => "true"
            ),
            array(
                "type" => UniteCreatorDialogParam::PARAM_TEXTFIELD,
                "id" => "rss_date_format",
                "conditions" => array("rss_show_date_formatted"=>"true"),
                "text" => "",
                "desc" => __("Specify the <a target='_blank' href='https://www.php.net/manual/en/datetime.format.php'>date format</a>, default is 'd/m/Y, H:i'", "unlimited-elements-for-elementor"),
                "placeholder" => "d/m/Y, H:i",
                "default" => "d/m/Y, H:i"
            ),
            array(
                "type" => UniteCreatorDialogParam::PARAM_TEXTFIELD,
                "id" => "rss_items_limit",
                "text" => __("Items Limit", "unlimited-elements-for-elementor"),
                "desc" => __("Optional. You can specify the maximum number of items: from 1 to 50., Use 0 for all", "unlimited-elements-for-elementor"),
                "placeholder" => "0",
                "default" => "0"
            ),
        );

        return $fields;
    }
	
	
	/**
	 * get Rss Feed data
	 */
	public function getRssFeedData($data, $arrValues, $name, $showDebug = false){
		
		if($showDebug == true){
			dmp("---- the debug is ON, please turn it off before release --- ");
		}
		
		if(empty($arrValues)){
			
			if($showDebug)
				dmp("no data found");
			
			return(null);
		}
			
		
		$rss_url = UniteFunctionsUC::getVal($arrValues, $name.'_rss_url');

		if(empty($rss_url)){
			if($showDebug)
				dmp("no url found for rss");

			return(null);
		}

		if(!empty($rss_url)){
			
			$rssContent = HelperUC::$operations->getUrlContents($rss_url);

			if(!empty($rssContent)) {
				$arrData = UniteFunctionsUC::maybeXmlDecode($rssContent);

				if($showDebug == true && is_array($arrData)) {
					dmp("Original rss data");

					$arrDataShow = UniteFunctionsUC::modifyDataArrayForShow($arrData);

					dmp($arrDataShow);
				}

				if(is_array($arrData) == false){

					if($showDebug == true){
						dmp("No RSS data found. The input is: ");
						echo "<div style='background-color:lightgray'>";
						dmp(htmlspecialchars($rssContent));
						echo "</div>";
					}

					return(null);
				}

				if(!empty($arrData)) {
					(bool) $showDateFormated = UniteFunctionsUC::getVal($arrValues, $name."_rss_show_date_formatted");
					$dateFormat = null;
					if ($showDateFormated) {
						$dateFormat = UniteFunctionsUC::getVal($arrValues, $name."_rss_date_format");
						if (empty($dateFormat)) {
							$dateFormat = 'd/m/Y, H:i';
						}
					}

					$arrData = $this->simplifyRssDataArray($arrData, $dateFormat);

					// trim by date limits
					(bool) $showFilterByDate = UniteFunctionsUC::getVal($arrValues, $name."_rss_show_filter_by_date");
					if($showFilterByDate && !empty($arrData)) {
						$filterByDateOption = UniteFunctionsUC::getVal($arrValues, $name . "_rss_filter_by_date_option");
						if (!empty($filterByDateOption) && $filterByDateOption != 'all') {
							$arrData = $this->limitArrayByPublishDate($arrData, $filterByDateOption);
						}
					}

					// trim by items limit
					(int) $dataItemsLimit = UniteFunctionsUC::getVal($arrValues, $name."_rss_items_limit");
					if($dataItemsLimit > 0 && !empty($arrData))
						$arrData = array_slice($arrData, 0, $dataItemsLimit, true);

					$data[$name] = $arrData;
				}
			}
		}

		return $data;
	}

	/**
	 * limit the array (filter) by publish date
	 */
	private function limitArrayByPublishDate($arrData, $option){
		
		// calculate period
		$from_time = 0;
		$to_time = 0;

		switch($option){
			case 'today':
				$from_time = strtotime('today midnight');
				$to_time = strtotime('tomorrow midnight');
				break;
			case 'yesterday':
				$from_time = strtotime('yesterday midnight');
				$to_time = strtotime('today midnight');
				break;
			case 'last_2_days':
				$from_time = strtotime("-2 days");;
				$to_time = time();
				break;
			case 'last_3_days':
				$from_time = strtotime("-3 days");
				$to_time = time();
				break;
			default:
				break;
		}

		if ($from_time > 0 && $to_time > 0) {
			$newArray = array();

			foreach ($arrData as $key => $value) {
				if (array_key_exists('publish_date', $value)) {
					
					$timestamp = UniteFunctionsUC::date2Timestamp($value['publish_date']);
					
					if ($timestamp >= $from_time && $timestamp < $to_time) {
						$newArray[] = $arrData[$key];
					}
				} else {
					$newArray[] = $arrData[$key];
				}
			}

			return $newArray;
		}

		return $arrData;
	}
	
	
	/**
	 * modify rss array to simplify the use
	 */
	private function simplifyRssDataArray($arrRss, $dateFormat = null){
		
		if($items = $this->findRssItems($arrRss)) {
			$items = $this->createNiceKeys($items, $dateFormat);

			return $items;
		}

		$arrRss = $this->createNiceKeys($arrRss, $dateFormat);

		return($arrRss);
	}

	private function createNiceKeys($arrRss, $dateFormat, $prefix = '') {
		
		$niceArr = array();

		foreach ($arrRss as $key => $value) {
			// Replace colons with underscores and append to prefix
			$newKey = $prefix . ($prefix ? '_' : '') . str_replace(':', '_', $key);

			if (is_array($value)) {
				// If the key is numeric, keep it as part of the array
				if (is_numeric($key)) {
					$niceArr[$key] = $this->createNiceKeys($value, $dateFormat);
				} else {
					$niceArr = array_merge($niceArr, $this->createNiceKeys($value, $dateFormat, $newKey));
				}
			} else {
				if (is_numeric($key)) {
					$niceArr[$prefix][$key] = $value;
				} else {
					$niceArr[$newKey] = $value;

					if (!empty($dateFormat) && $newKey == 'publish_date') {
						$date_time = UniteFunctionsUC::date2Timestamp($value);

						if (!empty($date_time))
							$niceArr['publish_date_formatted'] = date($dateFormat, $date_time);
					}
				}
			}
		}

		return $niceArr;
	}

	
	/**
	 * create data for rss
	 */
	private function createDate($arrRss, $dateFormat) {
		
		$niceArr = array();

		foreach ($arrRss as $key => $value) {
			if (is_array($value)) {
				$niceArr[$key] = $this->createDate($value, $dateFormat);
			} else {
				$timestamp = UniteFunctionsUC::date2Timestamp($value);
				if (!empty($timestamp)) {
					$formatedDate = date($dateFormat, $timestamp);
					if (empty($formatedDate)) {
						$formatedDate = date('d/m/Y H:i:s', $timestamp);
					}

					$niceArr[$key] = $formatedDate;
				} else {
					$niceArr[$key] = $value;
				}
			}
		}

		return $niceArr;
	}
	
	/**
	 * find rss items in rss array
	 */
	private function findRssItems($arrRss) {
		if (array_key_exists('item', $arrRss)) {
			return $arrRss['item'];
		} elseif (array_key_exists('entry', $arrRss)) {
			return $arrRss['entry'];
		} else {
			foreach ($arrRss as $value) {
				if (is_array($value)) {
					if ($items = $this->findRssItems($value)) {
						return $items;
					}
				}
			}
		}

		return false;
	}

    /* Filter widget params to get only a specific sublist by catid */
    public function filterByTypeAndCatId($array, $type, $catIdValue) {
        $result = array();

        foreach ($array as $key => $subArray) {
            if ($subArray['type'] != $type) {
                continue;
            }

            if ($subArray['__attr_catid__'] != $catIdValue) {
                continue;
            }

            $result[] = $subArray;
        }

        return $result;
    }

    /* Get all rss widget keys, if any key is empty use default key for it */
    public function getRssFeedKeys($addon, $data)
    {
        $param = $addon->getParams();

        $keys = array();

        $rssFeedArr = UniteFunctionsUC::getVal($data, "rss_feed");
        if (empty($rssFeedArr)) {
            return;
        }

        $firstRssElement = UniteFunctionsUC::getArrFirstValue($rssFeedArr);
        if (empty($firstRssElement)) {
            return;
        }

        $catValue = $addon->getParamByName("auto_detect_keys");
        if (empty($catValue)) {
            return;
        }

        $rssKeyArr = $this->filterByTypeAndCatId($param, UniteCreatorDialogParam::PARAM_TEXTFIELD, $catValue['__attr_catid__']);

        $isShowDebug = UniteFunctionsUC::getVal($data, "show_debug");
        $isShowDebug = UniteFunctionsUC::strToBool($isShowDebug);

        if ($isShowDebug) {
            dmp('--- Values for Item Keys ---');
        }
        
        
        
        foreach ($rssKeyArr as $rssKeyValue) {
            $keyValue = UniteFunctionsUC::getVal($data, $rssKeyValue['name']);

            if (empty($keyValue)) {
                $keys[$rssKeyValue['name']] = $rssKeyValue['default_value'];
            } else {
                $keys[$rssKeyValue['name']] = $keyValue;
            }

            if ($isShowDebug) {
                // show publish_date_formatted instead of publish_date if show formatted == true
                if($keys[$rssKeyValue['name']] == 'publish_date' && array_key_exists('publish_date_formatted', $firstRssElement)) {
                    dmp($rssKeyValue['title'] . ': "publish_date_formatted"');
                } else if (array_key_exists($keys[$rssKeyValue['name']], $firstRssElement)) {
                    dmp($rssKeyValue['title'] . ': "' . $keys[$rssKeyValue['name']] . '"');
                } else {
                    dmp($rssKeyValue['title'] . ': "' . $keys[$rssKeyValue['name']] . '" not found, please select another one');
                }
            }
        }
		
        
        if ($isShowDebug) {
            dmp('--- first RSS item structure ---');
            
            $firstRssElement = UniteFunctionsUC::modifyDataArrayForShow($firstRssElement);
            
            HelperHtmlUC::putHtmlDataDebugBox($firstRssElement);
        }

        return $keys;
    }
}
