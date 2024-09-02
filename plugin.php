<?php
/**
 * Plugin Name: IO4 
 * Description: This is an example OIDplus/IO4-Plugin aware and enabled Plugin.
 * Version: 0.0.1
 * Author: Frdlweb
 * Author URI: https://frdl.de
 * License: MIT
 */


namespace Frdlweb\OIDplus\IO4\plugin{
	
use ViaThinkSoft\OIDplus\Core\OIDplus;	
use ViaThinkSoft\OIDplus\Core\OIDplusConfig;	
use ViaThinkSoft\OIDplus\Core\OIDplusException;	
use ViaThinkSoft\OIDplus\Core\OIDplusObject;	
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;	
use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginRa;	
use ViaThinkSoft\OIDplus\Core\OIDplusPlugin;   
use Frdlweb\OIDplus\Plugins\AdminPages\IO4\OIDplusPagePublicIO4;

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

 function object_repository_link($atts) {
    global $oidplus_current_page_context;
	 
    $atts = shortcode_atts(
        array(
            'url' => OIDplus::webpath(null, OIDplus::PATH_ABSOLUTE_CANONICAL).'?goto='.urlencode($oidplus_current_page_context['data']['id']),
			'title'=>'Link to this page in the repository',
			'class'=>'btn btn-link',
        ),
        $atts
    );

  
    return sprintf('<a href="%1$s" title="%2$s" class="%4$s">%3$s</a>', $atts['url'], $atts['title'], $atts['title'], $atts['class']);
}



function io4_plugin_admin_pages_tree(?string $ra_mail = null){
	global $oidplus_admin_pages_tree_json;
	if (!OIDplus::authUtils()->isAdminLoggedIn()) return;
	$json = $oidplus_admin_pages_tree_json;
	   $json[] = array(
			'id' => OIDplusPagePublicIO4::PAGE_ID_COMPOSER,
			//'icon' => $tree_icon,
			'text' => _L('Composer Plugins'), 
		);
		
		
		$json[] = array(
			'id' => OIDplusPagePublicIO4::PAGE_ID_WEBFAT,
			//'icon' => $tree_icon,
			'text' => _L('Webfan Webfat Setup'),
			//'href'=>$this->getWebfatSetupLink(),
		);

		$json[] = array(
			'id' => OIDplusPagePublicIO4::PAGE_ID_BRIDGE,
			//'icon' => $tree_icon,
			'text' => _L('Webfan IO4 Bridge'), 
		);
	$oidplus_admin_pages_tree_json = $json;
}



function public_pages_tree(?string $ra_mail){
	  global $oidplus_public_pages_tree_json;
	 
 
	
	$json =$oidplus_public_pages_tree_json;
		$Array = (new \Wehowski\Helpers\ArrayHelper($json)) ;
	
		 
	 
		$Array
			//->after(1)
			->add([
		    'id' => 'oidplus:webfan_registry_hosting',
		 	'icon' => 'https://webfan.de/favicon.ico',
			 'a_attr'=>[
			 	 'href'=>'https://webfan.de/admin/registry/?host='.urlencode($_SERVER['HTTP_HOST']),
			 ],
			 //  //'href'=>OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL),
			'text' => _L('OID & Registry Hosting'),
	   ]);
	
	  

 
	$json = $Array->all();
	$oidplus_public_pages_tree_json = $json; 
}




function public_pages_gui(?string $id = null){
	  global $oidplus_public_pages_gui_out;
	  global $oidplus_public_pages_gui_handled;
	
	 
		 if('oidplus:webfan_registry_hosting'===$id){	 
			 $oidplus_public_pages_gui_handled = true;
			 $oidplus_public_pages_gui_out['title'] =  'OID Hosting';
			 $homelink ='https://webfan.de/admin/registry/?host='.urlencode($_SERVER['HTTP_HOST']);
			 $oidplus_public_pages_gui_out['text']  .= '<a href="'.$homelink.'">'.$homelink.'</a>'
				 .sprintf('<meta http-equiv="refresh" content="0; URL=%s">', $homelink);

			 $oidplus_public_pages_gui_out['icon'] = 'https://webfan.de/favicon.ico'; 
		 }	 
		
}

// Shortcode
//add_shortcode('markdown', __NAMESPACE__.'\markdown');
add_shortcode('RefreshHeader', __NAMESPACE__.'\refresh_headér_shortcode');
add_shortcode('ObjectRepositoryLink', __NAMESPACE__.'\object_repository_link');
add_shortcode('ListAllShortcodes', '\display_shortcodes');

add_action(
		'oidplus_public_pages_tree',
		__NAMESPACE__.'\public_pages_tree',
		0//,
		//string $include_path = null,
	);
add_action(
		'oidplus_public_pages_gui',
		__NAMESPACE__.'\public_pages_gui',
		0//,
		//string $include_path = null,
	);

add_action(
		'oidplus_admin_pages_tree',
		__NAMESPACE__.'\io4_plugin_admin_pages_tree',
		0//,
		//string $include_path = null,
	);

//you can use autowiring as from container->invoker->call( \callable | closure(autowired arguments), [parameters]) !!!
return (function($container){
	//print_r(get_class($container));
	// \Webfan\Patches\Start\Timezone2::defaults( );
	
});
	
}//namespace of the plugin
