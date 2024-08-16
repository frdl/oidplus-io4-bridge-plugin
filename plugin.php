<?php
/**
 * Plugin Name: IO4 
 * Description: This is an example WP plugin that utilizes Keygen for licensing.
 * Version: 0.0.1
 * Author: Frdlweb
 * Author URI: https://frdl.de
 * License: MIT
 */
namespace Frdlweb\OIDplus;

	use ViaThinkSoft\OIDplus\Core\OIDplus;
	use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
	use ViaThinkSoft\OIDplus\Core\OIDplusException;
	use ViaThinkSoft\OIDplus\Core\OIDplusObject;
	use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;
	use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginRa;
	use ViaThinkSoft\OIDplus\Core\OIDplusPlugin;
/*
	use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginAdmin;
	use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\OIDplusNotification;
	use ViaThinkSoft\OIDplus\Plugins\ObjectTypes\OID\WeidOidConverter;
	use ViaThinkSoft\OIDplus\Plugins\PublicPages\Whois\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_4;
	use ViaThinkSoft\OIDplus\Plugins\PublicPages\Objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2;
	use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8;
	use ViaThinkSoft\OIDplus\Plugins\PublicPages\RestApi\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9;

*/

// header("Refresh:5; url=?goto=oidplus:system");
 function refresh_headér_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(
        array(
            'url' => OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT).'?goto=oidplus:system',
            'refresh' => 5,
			'title'=>'Go to...',
        ),
        $atts
    );

 
     header(sprintf('Refresh:%2$d; url=%1$s' , $atts['url'], $atts['refresh']));
    return sprintf('<a href="%1$s" title="%2$s">%3$s</a>', $atts['url'], $atts['title'], $atts['title']);
}

// Shortcode
add_shortcode('RefreshHeader', __NAMESPACE__.'\refresh_headér_shortcode');

//you can use autowiring!!!
return (function($container){
	//print_r(get_class($container));
});
