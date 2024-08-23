<?php
/**
 * Plugin Name: IO4 
 * Description: This is an example OIDplus/IO4-Plugin aware and enabled Plugin.
 * Version: 0.0.1
 * Author: Frdlweb
 * Author URI: https://frdl.de
 * License: MIT
 */
namespace Frdlweb\OIDplus\IO4\plugin;

	use ViaThinkSoft\OIDplus\Core\OIDplus;
	use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
	use ViaThinkSoft\OIDplus\Core\OIDplusException;
	use ViaThinkSoft\OIDplus\Core\OIDplusObject;
	use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;
	use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginRa;
	use ViaThinkSoft\OIDplus\Core\OIDplusPlugin;
 

 function base64_url_encode($input) {
   return strtr(base64_encode($input), '+/=', '~_-');
 }

 function base64_url_decode($input) {
   return base64_decode(strtr($input, '~_-', '+/='));
 }	


 function isBase64Encoded($str)
	{
		try

		{
		$decoded = base64_decode($str, true);

		if ( base64_encode($decoded) === $str ) {
		    return true;
		}
		else {
		    return false;
		}

		}catch(\Exception $e){
			// If exception is caught, then it is not a base64 encoded string
			return false;
		}
 }

 function markdown($atts) {
	 if(!isset($atts['content'])){
		throw new \Exception('Content attribute must be set in '.__FUNCTION__); 
	 }
    // Extract shortcode attributes
    $atts = shortcode_atts(
        array(
            'content'=>isBase64Encoded($atts['content']) ? base64_decode($atts['content']) : $atts['content'],
        ),
        $atts
    );
       $atts['content'] = isBase64Encoded($atts['content']) ? base64_decode($atts['content']) : $atts['content'];
	 
	 
	     $frontMatter = new \Webuni\FrontMatter\FrontMatter();

          $document = $frontMatter->parse($atts['content'] );

           $data = $document->getData();
           $content = $document->getContent();
	 
	 
 
    return $content;
}

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
//add_shortcode('markdown', __NAMESPACE__.'\markdown');
add_shortcode('RefreshHeader', __NAMESPACE__.'\refresh_headér_shortcode');
add_shortcode('ListAllShortcodes', '\display_shortcodes');

//you can use autowiring as from container->invoker !!!
return (function($container){
	//print_r(get_class($container));
});
