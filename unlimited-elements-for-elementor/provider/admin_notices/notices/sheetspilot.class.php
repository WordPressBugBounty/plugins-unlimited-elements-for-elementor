<?php

/**
 * @package Unlimited Elements
 * @author UniteCMS http://unitecms.net
 * @copyright Copyright (c) 2016 UniteCMS
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class UCAdminNoticeSheetsPilot extends UCAdminNoticeAbstract{

	/**
	 * get the notice identifier
	 */
	public function getId(){

		return 'sheetspilot1';
	}

	/**
	 * get the notice html
	 */
	public function getHtml(){

		$heading = __('Bulk Edit WooCommerce Products in a Spreadsheet', 'unlimited-elements-for-elementor');
		$content = __('Turn your product catalog into a live spreadsheet with instant editing, built-in AI, and powerful filters.<br />✓ Live editing with instant saves<br />✓ Powerful filters & built-in AI<br />✓ Update thousands of products in minutes', 'unlimited-elements-for-elementor');

		$installText = __('Install SheetsPilot Now', 'unlimited-elements-for-elementor');
		$installUrl = UniteFunctionsWPUC::getInstallPluginLink('sheetspilot');
		$installUrl = UniteFunctionsUC::addUrlParams($installUrl, array('uc_dismiss_notice' => $this->getId()));

		$id = $this->getId();

		$builder = new UCAdminNoticeBuilder($id);
		$builder = $this->initBuilder($builder);

		$builder->dismissible();
		$builder->color(UCAdminNoticeBuilder::COLOR_INFO);
		$builder->withHeading($heading);
		$builder->withContent($content);
		$builder->withLinkAction($installText, $installUrl);

		$html = $builder->build();

		return $html;
	}

	/**
	 * initialize the notice
	 */
	protected function init(){

		$this->setDuration(168); // 7 days in hours
	}

	/**
	 * check if the notice condition is allowed
	 */
	protected function isConditionAllowed(){

		if($this->isSheetsPilotInstalled() === true)
			return false;

		if(class_exists('WooCommerce') === false)
			return false;

		return true;
	}

	/**
	 * check if the SheetsPilot plugin is installed
	 */
	private function isSheetsPilotInstalled(){

		if(defined( 'SHEETSPILOT_INC' ))
			return true;

		return false;
	}

}
