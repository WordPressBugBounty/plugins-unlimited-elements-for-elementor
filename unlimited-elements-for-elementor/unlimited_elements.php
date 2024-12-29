<?php
/*
* Plugin Name: Unlimited Elements for Elementor
* Plugin URI: http://unlimited-elements.com
* Description: Elementor all-in-one addons pack with the best widgets for Elementor, offering 100+ free widgets, templates, and tools to create stunning websites!
* Author: Unlimited Elements
* Version: 1.5.135
* Author URI: http://unlimited-elements.com
* Text Domain: unlimited-elements-for-elementor
* Domain Path: /languages
*  
* Tested up to: 6.7
* Elementor tested up to: 3.25.7
* Elementor Pro tested up to: 3.25.3
*/

if(!defined("UNLIMITED_ELEMENTS_INC"))
	define("UNLIMITED_ELEMENTS_INC", true);
	
if ( ! function_exists( 'uefe_fs' ) ) {
    // Create a helper function for easy SDK access.
    function uefe_fs() {
        global $uefe_fs;

        if ( ! isset( $uefe_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/provider/freemius/start.php';
	
            $uefe_fs = fs_dynamic_init( array(
                'id'                  => '4036',
                'slug'                => 'unlimited-elements-for-elementor',
				'premium_slug'        => 'unlimited-elements-pro',            
                'type'                => 'plugin',
                'public_key'          => 'pk_719fa791fb45bf1896e3916eca491',
                'is_premium'          => false,
				'premium_suffix'      => '(Pro)',            
            	'has_premium_version' => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'has_affiliation'     => false,
                'menu'                => array(
                    'slug'           => 'unlimitedelements',
                    'support'        => false,
					'affiliation'    => false,            
					'contact'    => false            
                )
            ) );
        }

        return $uefe_fs;
    }

    // Init Freemius.
    uefe_fs();
    // Signal that SDK was initiated.
    do_action( 'uefe_fs_loaded' );
    
}	
	
$mainFilepath = __FILE__;
$currentFolder = dirname($mainFilepath);
$pathProvider = $currentFolder."/provider/";


try{
	if(!class_exists("GlobalsUC")) {
		$pathAltLoader = $pathProvider."provider_alt_loader.php";
		if(file_exists($pathAltLoader)){
			
			require $pathAltLoader;
		
		}else{
			require_once $currentFolder.'/includes.php';
			
			require_once  GlobalsUC::$pathProvider."core/provider_main_file.php";
		}
		
	}
    
	//check for double include
	if(property_exists('GlobalsUC', 'active_plugins_versions')){
		
	    if(in_array('unlimited-elements-for-elementor', GlobalsUC::$active_plugins_versions))
		    define("UC_BOTH_VERSIONS_ACTIVE", true);
	    else
	        GlobalsUC::$active_plugins_versions[] = 'unlimited-elements-for-elementor';
	}

	
}catch(Exception $e){
	$message = $e->getMessage();
	$trace = $e->getTraceAsString();
	
	echo "<br>";
	echo esc_html($message);
	echo "<pre>";
	print_r($trace);
}

