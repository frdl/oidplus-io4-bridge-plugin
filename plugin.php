<?php
/**
 * Plugin Name: IO4 
 * Description: This is an example OIDplus/IO4-Plugin aware and enabled Plugin.
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
 
 


// header("Refresh:5; url=?goto=oidplus:system");
 function refresh_headér_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(
        array(
            'url' => OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT).'?goto=oidplus:system',
            'refresh' => 5,
			'title'=>'Go to...',
			'class'=>'btn btn-primary',
        ),
        $atts
    );

 
     header(sprintf('Refresh:%2$d; url=%1$s' , $atts['url'], $atts['refresh']));
    return sprintf('<a href="%1$s" title="%2$s" class="%4$s">%3$s</a>', $atts['url'], $atts['title'], $atts['title'], $atts['class']);
}

// Shortcode
add_shortcode('RefreshHeader', __NAMESPACE__.'\refresh_headér_shortcode');
add_shortcode('ListAllShortcodes', '\display_shortcodes');

//you can use autowiring!!!
return (function($container){
	//print_r(get_class($container));
});
