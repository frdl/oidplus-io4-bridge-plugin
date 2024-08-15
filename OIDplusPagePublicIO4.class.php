<?php

/*
 * OIDplus 2.0
 * Copyright 2019 - 2023 Daniel Marschall, ViaThinkSoft/Till Wehowski, Frdlweb
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Frdlweb\OIDplus\Plugins\AdminPages\IO4 {


	use ViaThinkSoft\OIDplus\Core\OIDplus;
	use ViaThinkSoft\OIDplus\Core\OIDplusConfig;
	use ViaThinkSoft\OIDplus\Core\OIDplusException;
	use ViaThinkSoft\OIDplus\Core\OIDplusObject;
	use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginPublic;
	use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginRa;
	use ViaThinkSoft\OIDplus\Core\OIDplusPlugin;

	use ViaThinkSoft\OIDplus\Core\OIDplusPagePluginAdmin;
	use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\OIDplusNotification;
	use ViaThinkSoft\OIDplus\Plugins\ObjectTypes\OID\WeidOidConverter;
	use ViaThinkSoft\OIDplus\Plugins\PublicPages\Whois\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_4;
	use ViaThinkSoft\OIDplus\Plugins\PublicPages\Objects\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2;
	use ViaThinkSoft\OIDplus\Plugins\AdminPages\Notifications\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8;
	use ViaThinkSoft\OIDplus\Plugins\PublicPages\RestApi\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9;



use Webfan\DescriptorType;
use Webfan\RuntimeInterface;
use Webfan\ConfigType;
use Webfan\ExecutionContextType;

use Webfan\Batch;
use Webfan\Baum;

use League\Pipeline\PipelineBuilder;

use League\Pipeline\Pipeline;
use League\Pipeline\StageInterface;

use frdlweb\StubHelperInterface;
use frdlweb\StubRunnerInterface;
use Frdlweb\WebAppInterface; 

use InvalidArgumentException;


use Webfan\Webfat\App\ConfigContainer;
use Webfan\Webfat\App\ContainerCollection;
# use frdl\ContainerCollectionV2 as ContainerCollection;

use Frdlweb\Contract\Autoload\ClassLoaderInterface;

use Configula\ConfigFactory as Config;
use Configula\ConfigValues as Configuration;
use Configula\Loader;
use Doctrine\Common\Cache\FilesystemCache;
//use Eljam\CircuitBreaker\Breaker
use Webfan\Webfat\App\CircuitBreaker as Breaker;
use Eljam\CircuitBreaker\Circuit;


use Eljam\CircuitBreaker\Event\CircuitEvents;
//use Eljam\CircuitBreaker\Event\CircuitEvent as Event;
//use Fuz\Component\SharedMemory\SharedMemory;
use Webfan\Webfat\App\SharedMemory;
//use Fuz\Component\SharedMemory\Storage\StorageFile;
use Webfan\Webfat\App\SharedMemoryStorageFile as StorageFile;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use IvoPetkov\HTML5DOMDocument;
use Webfan\Webfat\HTMLServerComponentsCompiler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

use LogicException; 
use Spatie\Once\Backtrace as SpatieBacktrace;
use Spatie\Once\Cache as SpatieCache;

# use Monolog\Level as LogLevel;
use Psr\Log\LogLevel;
#use Monolog\Logger;
use Monolog\Handler\StreamHandler as LoggerStreamHandler;
use Psr\Log\LoggerInterface;
use Monolog\Registry as LoggerRegistry;

use Webfan\Webfat\Filesystems\PathResolvingFilesystem as StreamHandler;

use ActivityPhp\Server;
use ActivityPub\ActivityPub;
use ActivityPub\Config\ActivityPubConfig;

use ActivityPub\Utils\Logger;
use Monolog\Logger as MonoLogger;
	
use Jobby\Jobby;
use Opis\Closure\SerializableClosure;
use Jobby\Exception;
	

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

 

/*  
API:
public function packagist(string $method, array $params = [])
public function package(string $name) : array
*/
class OIDplusPagePublicIO4 extends OIDplusPagePluginAdmin //OIDplusPagePluginPublic // implements RequestHandlerInterface
	implements  //INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1, /* oobeEntry, oobeRequested */
	           INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_4,  //Ra+Whois Attributes
	           INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2, /* modifyContent */
	           INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8 , /* getNotifications */
	        INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9/*  restApi* */
	 //
				   /*   INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_7 getAlternativesForQuery() */
{

	public const WebfatDownloadUrl = 'https://packages.frdl.de/raw/webfan/website/webfan.setup.php';	   
				   
	const PAGE_ID_COMPOSER = 'oidplus:io4:composer';	
	const PAGE_ID_WEBFAT = 'webfan:webfat:setup';	
	const PAGE_ID_BRIDGE = 'webfan:io4:bridge';		   
	const PAGES = [
		    'webfan:io4:bridge' => 'gui_PAGE_ID_BRIDGE',
		    'webfan:webfat:setup' => 'gui_PAGE_ID_WEBFAT',
		    'oidplus:io4:composer' => 'gui_PAGE_ID_COMPOSER',
		
		];
		/* 
		[ 
				"oiplus-plugin-public-pages",
				"oiplus-plugin-ra-pages",
				"oiplus-plugin-admin-pages",
				"oiplus-plugin-auth",
				"oiplus-plugin-database",
				"oiplus-plugin-sql-slang",
				"oiplus-plugin-logger",
				"oiplus-plugin-object-types",
				"oiplus-plugin-language",
				"oiplus-plugin-design",
				"oiplus-plugin-captcha"
			"project",
			"library"
		],
		*/				   
   	protected $AppLauncher = null;		
    protected $_containerDeclared = false;		
	protected $StubRunner = null;		
	
	
	protected $schemaCacheDir;
	protected $schemaCacheExpires;
				   
	protected $packagistCacheDir;
	protected $packagistExpires;
				   
	protected $packagistClient = null;
	protected $composerUI = null;

	protected static $autoloaderRegistered = false;
	/**
	 * @var int
	 */
	public function __construct() { 
		$this->packagistCacheDir = OIDplus::baseConfig()->getValue('IO4_PACKAGIST_CACHE_DIRECTORY',
																   OIDplus::localpath().'userdata/cache/' );
		$this->packagistExpires = OIDplus::baseConfig()->getValue('IO4_PACKAGIST_CACHE_EXPIRES', 15 * 60 );
		
		$this->schemaCacheDir = OIDplus::baseConfig()->getValue('SCHEMA_CACHE_DIRECTORY', OIDplus::localpath().'userdata/cache/' );
		$this->schemaCacheExpires = OIDplus::baseConfig()->getValue('SCHEMA_CACHE_EXPIRES', 60 * 60 );		
		
		if(!static::is_cli() ){ 
			$this->ob_privacy();	
		}
	}				
			
	public static function is_cli()
{
    if ( defined('STDIN') )
    {
        return true;
    }

    if ( php_sapi_name() === 'cli' )
    {
        return true;
    }

    if ( array_key_exists('SHELL', $_ENV) ) {
        return true;
    }

    if ( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) 
    {
        return true;
    } 

    if ( !array_key_exists('REQUEST_METHOD', $_SERVER) )
    {
        return true;
    }

    return false;
	}			   
				   
				   
	protected static $ob_privacy_set = false;
				   
	public function ob_privacy(){
		if(true === static::$ob_privacy_set && ob_get_level() > 5 ){
		   return;	
		}
		static::$ob_privacy_set = true; 
		ob_start([$this, 'ob_privacy_handler']); 
	}
				   
	public function ob_privacy_handler(string $content) : string {
		if(1 == intval(OIDplus::baseConfig()->getValue('FRDLWEB_PRIVACY_HIDE_MAILS', 1 ) )
		   && !OIDplus::authUtils()->isAdminLoggedIn() 
		  ){
           $content  = $this->privacy_protect_mails($content);			
		}
		return $content;
	}

	public function htmlPostprocess(&$out): void {
		$out = $this->privacy_protect_mails($out);	
	}
				   
				   
	public static function hashMails($content){
	//	$m = $this->parse_mail_addresses($content);
		//$content .= print_r($this->parse_mail_addresses($content), true);
		$mails = static::parse_mail_addresses($content);
		foreach($mails as $num => $m){
		//	print_r($m);
			if( OIDplus::baseConfig()->getValue('FRDLWEB_ALIAS_PROVIDER', 'profalias.webfan.de' ) !== $m['provider']			  
			    && !OIDplus::authUtils()->isRALoggedIn($m['handle']) 			   
			   && 'wehowski.de' !== $m['provider']	
			   && 'webfan.de' !== $m['provider']
			   && 'weid.info' !== $m['provider']	
			   && 'oid.zone' !== $m['provider']		
			   && 'iana.org' !== $m['provider']	
			//   && OIDplus::baseConfig()->getValue('TENANT_APP_ID_OID' ) !== $m['handle']
			   && 'frdl.de' !== $m['provider']		 
			  ) {
				/*
			     $replace = 'PIDH'.str_pad(strlen($m['handle']), 4, "0", \STR_PAD_LEFT).'-'.sha1($m['handle'])
				 . '@'. OIDplus::baseConfig()->getValue('FRDLWEB_ALIAS_PROVIDER', 'alias.webfan.de' );
		    	$content = str_replace($m['handle'], $replace, $content);
				*/
				
				$Grofil = new \Webfan\Grofil(OIDplus::baseConfig()->getValue('FRDLWEB_ALIAS_PROVIDER', 'profalias.webfan.de' ), $m['handle']);
				$mailto = $Grofil->url($m['handle'], 'webfan', 'mailto', null);
			
				$p = explode(':', $mailto, 2);
				 $replace = $p[1];	
				
			 
				$content = str_replace($m['handle'], $replace, $content);
			}
		}
		return $content;		
	}
				   
	public function privacy_protect_mails($content){
	  return static::hashMails($content);
	}
				   
				   
	public static function parse_mail_addresses($string){
       preg_match_all(<<<REGEXP
/(?P<email>((?P<account>[\._a-zA-Z0-9-]+)@(?P<provider>[\._a-zA-Z0-9-]+)))/xsi
REGEXP, $string, $matches, \PREG_PATTERN_ORDER);
		
		$ext = [];
		foreach($matches[0] as $k => $v){						
		//	$ext[$matches['email'][$k]] =[
			$ext[] =[
				'handle'=>$matches['email'][$k],
				'account'=>$matches['account'][$k],
				'provider'=>$matches['provider'][$k],
				
			];
		}
      return $ext;
   }							   
				   
				   
	public static function cronjobGetJobbyTasksFromPlugins($Jobby = null){
		
		$jobby = null !== $Jobby ? $Jobby : new Jobby();
		
		foreach(OIDplus::getAllPlugins() as $pkey => $plugin){
		
			if(method_exists($plugin, 'cronjobJobbyPrepareTasks')){
				$jobby = \call_user_func_array([$plugin, 'cronjobJobbyPrepareTasks'], [$jobby]);
			}
		}
		
		return $jobby;
	}				   
				   
    public static function cronjobGetJobbyTasksFromTable($Jobby = null){ 

		$jobby = null !== $Jobby ? $Jobby : new Jobby();
		
		$res = OIDplus::db()->query("select * from ###cron_and_jobs WHERE enabled = 1");
		
		//$res->naturalSortByField('id');
		while ($job = $res->fetch_array()) {
           $job = array_filter($job);
           $job['closure'] = unserialize($job['command']);
           $jobName = $job['name'];
           unset($job['name']);
   
			try {       
				$jobby->add($jobName, $job);  
			} catch (\Exception $e) {    
				error_log($e->getMessage(), 0);
			}		 		
		}
		 
	  return $jobby;
	}
				   
				   
	public function cronjobJobbyPrepareTasks($Jobby = null){
		$jobby = null !== $Jobby ? $Jobby : new Jobby();
		//$this->bootIO4(   );	
		$jobby->add('bootIO4forpreload@1.3.6.1.4.1.37476.9000.108.19361.24196', [    
			// Use the 'closure' key  
			// instead of 'command'    
			'closure' => function() {     
				$io4Plugin = OIDplus::getPluginByOid("1.3.6.1.4.1.37476.9000.108.19361.24196");
		
				if (!is_null($io4Plugin) && \is_callable([$io4Plugin,'bootIO4']) ) {		
					$io4Plugin->bootIO4();	  	
				}else{					
	
				}
				
				return true;  
			},   
   
			//hourly
			'schedule' => '0 * * * *',
		]);
		
		
		
	   return $jobby;	
	}
				   
				   
				   
				   
	public function cronjobRunJobby($Jobby = null){
		$jobby = null !== $Jobby ? $Jobby : new Jobby();
		
		$jobby = static::cronjobGetJobbyTasksFromPlugins($jobby);
		$jobby = static::cronjobGetJobbyTasksFromTable($jobby);
		
		$jobby->run();	
		
	   return $jobby;	
	}
				   
				   
    public static function handleNext( $next, ?bool $skip404 = true ) {
				if(is_bool($next)){
					return $next;
				}elseif(is_string($next)){
					//return $next;
					$next = static::out_html($next, 200);
					(new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($next);								    
					die();
					//return static::out_html($next);
				}elseif(!is_null($next) && is_object($next) && $next instanceof \Psr\Http\Message\ResponseInterface){ 			   
					  switch($next->getStatusCode()){
						  case 404 :
							  if(!$skip404){
								  (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($next);	
							    die();
							  }else{
								  return false;
							  }
							  break;
							 	 
						  case 302 <= $next->getStatusCode() :
							   (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($next);	
							    die();
							  break;
							  
							  default :
							    (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($next);	
							   die();
							  break;
					  }
					
				 }elseif(!is_null($next) && (is_array($next) || is_object($next)  )){								
					OIDplus::invoke_shutdown();		
					@header('Content-Type:application/json; charset=utf-8');			
					echo json_encode($next);			
					exit; 
				}elseif(is_null($next) ){
					 return false;
				}else{
					return $next;
				}
	}
				   
				   
	/** c404=ErrorDocument
	 * @param string $request
	 * @return bool
	 * @throws OIDplusException
	 *
     *  @ToDO ??? : Use PSR Standards? https://registry.frdl.de/?goto=php%3APsr%5CHttp%5CServer
	 */
	public function handle404(string $request): bool {
		
			
		 if(!static::is_cli() ){ 
		 	$this->ob_privacy();	
		 }
		
		if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER["REQUEST_METHOD"])) return false; 
		
		$rel_url = false;
		$rel_url_original =substr($_SERVER['REQUEST_URI'], strlen(OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)));
		$requestMethod = $_SERVER["REQUEST_METHOD"];
        $next = false;
		
		$baseInstaller = OIDplus::baseConfig()->getValue('FRDLWEB_INSTALLER_REMOTE_SERVER_RELATIVE_BASE_URI', 
																			'api/v1/io4/remote-installer/'
											.OIDplus::baseConfig()->getValue('TENANT_OBJECT_ID_OID', 
											OIDplus::baseConfig()->getValue('TENANT_REQUESTED_HOST', 'webfan/website' ) ));
 
		if (str_starts_with($rel_url_original, $baseInstaller)) {
			if(file_exists(__DIR__.\DIRECTORY_SEPARATOR.'installer-server'.\DIRECTORY_SEPARATOR.'index.web.php') ){
				$installer_url_slug =trim(substr($rel_url_original,strlen($baseInstaller),strlen($rel_url_original)), '/ ');
				define('WEBFAN_INSTALLER_INSTALLER', $installer_url_slug);  
				  require __DIR__.\DIRECTORY_SEPARATOR.'installer-server'.\DIRECTORY_SEPARATOR.'index.web.php';
			//	return true;
				  die();		
			}
		}	
		

		
			if (str_starts_with($rel_url_original, OIDplus::baseConfig()->getValue('FRDLWEB_CONTAINER_REMOTE_SERVER_RELATIVE_BASE_URI', 
																			'api/v1/io4/remote-container/'
											.OIDplus::baseConfig()->getValue('TENANT_OBJECT_ID_OID', 
											OIDplus::baseConfig()->getValue('TENANT_REQUESTED_HOST', 'webfan/website' ) ) 


																			))
		   
		   ) {
			if(file_exists(__DIR__.\DIRECTORY_SEPARATOR.'container-server'.\DIRECTORY_SEPARATOR.'index.php') ){
				  require __DIR__.\DIRECTORY_SEPARATOR.'container-server'.\DIRECTORY_SEPARATOR.'index.php';
				  die();		
				//return true;
			}
		}	
			
		
		
		

			
		 $args = [$_SERVER['REQUEST_URI'], $request, $rel_url_original, $rel_url, $requestMethod];
		$next = \call_user_func_array([$this, 'handleFallbackRoutes'], $args);

		
		 //if(isset($_GET['test'])  )die($rel_url);
	  if($next === false && false===$rel_url){  		 
	   if(isset($_GET['c404']) && 'ErrorDocument' === $_GET['c404'] ){
			// http_response_code(404);
			// throw new OIDplusException(_L('Endpoint ErrorDocument for %s not found'), $request, 404);
			//   $next = $this->handleFallbackRoutes($rel_url_original);
		 }elseif(isset($_GET['c404']) && 'FallBackResource' === $_GET['c404'] ){
			// http_response_code(404);
			// throw new OIDplusException(_L('Endpoint FallBackResource for %s not found'), $request, 404);
			//  $next = $this->handleFallbackRoutes($rel_url_original);
		 }else{
		     // $next =  $this->handleFallbackRoutes($rel_url_original);
		}
	  }elseif($next === false && false!==$rel_url){
	     //  $next =  $this->handleFallbackRoutes($rel_url);
	  }
		
		$next =static::handleNext( $next, true );
		
		return $next;
	}
	 
	public static function out_html(string $html, ?int $code = 200, $callback = null, ?array $templateVars = []){			
		$contents = '';							
		$contents.=\is_callable($callback) ? $callback($html, $templateVars) : $html;					
		$response = new Response($code);			
		$response =  $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor($contents));	
		return $response;
	}
								   
	
	public function webUriRoot($dir = null, $absolute = false)
	{
		if(null===$dir){
		  $dir=getcwd();
		}
		$root = "";
		$dir = str_replace('\\', '/', realpath($dir));

		if(true===$absolute){
		 //HTTPS or HTTP
		 $root .= !empty($_SERVER['HTTPS']) ? 'https' : 'http';

		 //HOST
		 $root .= '://' . $_SERVER['HTTP_HOST'];
		}

		//ALIAS
		if(!empty($_SERVER['CONTEXT_PREFIX'])) {
		$root .= $_SERVER['CONTEXT_PREFIX'];
		$root .= substr($dir, strlen($_SERVER[ 'CONTEXT_DOCUMENT_ROOT' ]));
		} else {
		$root .= substr($dir, strlen($_SERVER[ 'DOCUMENT_ROOT' ]));
		}

		$root .= '/';

		return $root;
	}
		

	public function handleFallbackRoutes($REQUEST_URI, $request, $rel_url_original, $rel_url, $requestMethod){
		$html = '';
		
		/*	
		    	if ($obj = OIDplusObject::findFitting('uri:'.$request)) {
					//print_r($obj);
					ob_start();
					$page  = frdl_ini_dot_parse($obj->getDescription(), true);
					$data = $page['data']; 
					$html = $page['content']; 
					$html = \do_shortcode($html );
					ob_end_clean();
					 print_r($html);
					 print_r($data);
			 	//  die($html);
				}
	*/
		
	 if('/' === substr($request, -1)
	   && $request === OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)
	   && (0===count($_GET)
	   && $this->webUriRoot(OIDplus::localpath()) === OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT) )
	   ){	
	  // var_dump($REQUEST_URI, $request, $rel_url_original, $rel_url, $requestMethod);
	 //	die(basename(__FILE__).__LINE__);
	//		  ob_end_clean();
		  	 ignore_user_abort(true);
        	  header("Refresh:5; url=?goto=oidplus:system");
        	  header('Connection: close') ;

		 $html.= '<h1>@ToDo: Startseite in Arbeit...</h1><p class="btn-warning" style="color:red;background:url(https://cdn.startdir.de/ajax-
		 loader_2.gif) no-repeat;">We are working on a new System feature</p><p>Page will reload soon, please wait...!<br />Neue Seite bald verfügbar!</p><img src="https://cdn.startdir.de/ajax-loader_2.gif" style="border:0px;" />';

		// flush();
		  die($html);		
//		return $html;
	  }
		
	 /*	*/
		//$uri = explode('?', $REQUEST_URI, 2)[0];
		//$file = OIDplus::localpath().$uri;
		//if(file_exists($file)){
		//  die($file);	
		//}
		return false;
	}
				 				   
	
	public function get_http_response_code($url) {  
		$headers = \get_headers($url);   
		return intval(substr($headers[0], 9, 3));
	}
				
				   
				   
	public function selfToPackage(){
		$zipfile =OIDplus::localpath().\DIRECTORY_SEPARATOR.'frdl-plugins.zip';
		
	     if(!OIDplus::baseConfig()->getValue('IO4_BUNDLE_SELF', 
										isset($_SERVER['SERVER_NAME']) && 'registry.frdl.de' === $_SERVER['SERVER_NAME']) 
		&& !file_exists($zipfile)){
				$this->archiveDownloadTo( OIDplus::localpath(''),
									 'https://registry.frdl.de/frdl-plugins.zip' ,
										'frdl-plugins.zip',
										false);			 
		 }
		
		
		
     if(OIDplus::baseConfig()->getValue('IO4_BUNDLE_SELF', 
										isset($_SERVER['SERVER_NAME']) && 'registry.frdl.de' === $_SERVER['SERVER_NAME']) 
		&& !file_exists($zipfile )){
		 /*
    	//$this->getWebfat(true,false)->getRemoteAutoloader()->register( true );
	    //$Stunrunner =require __DIR__.'/webfan.setup.php';
	   $Stunrunner = $this->getWebfat(true,false);
      $container = $Stunrunner->getAsContainer(null); 
      $Stunrunner->init();
      $Stunrunner->autoloading();
    //$Stunrunner(false);
*/
	$Stunrunner = $this->getWebfat(true,false);	 
		 
     $Stunrunner->getRemoteAutoloader()->register( true );
	  if(!\class_exists(\Webfan\Archive\Zipper::class)){
		  \IO4\_installClass(\Webfan\Archive\Zipper::class);
	  }
	   \Webfan\Archive\Zipper::$NOT_COMPRESS[]='classes';
	   \Webfan\Archive\Zipper::$NOT_COMPRESS[]='.classes';		 
	   \Webfan\Archive\Zipper::$NOT_COMPRESS[]='.functions';
	   \Webfan\Archive\Zipper::$NOT_COMPRESS[]='container-server';
	   \Webfan\Archive\Zipper::$NOT_COMPRESS[]='installer-server';
	   \Webfan\Archive\Zipper::$NOT_COMPRESS[]='cache';
	   \Webfan\Archive\Zipper::$NOT_COMPRESS[]='801\_login\_webfan';
	   \Webfan\Archive\Zipper::$NOT_COMPRESS[]='webfan';
	   
	  $zip = new  \Webfan\Archive\Zipper;
     if ($zip->open($zipfile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) === TRUE) {
         $zip->addDir( 'plugins'.\DIRECTORY_SEPARATOR.'frdl',
				'plugins/frdl',
			  '/.*/',  // '/^.+(.png)$/i'
			   null, 
		   	  null/*\FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO*/);
		 
		 $zip->delete('plugins/frdl/publicPages/801_login_webfan', true);
		 $zip->delete('plugins/frdl/publicPages/200_frdlweb_freeoid', true);
			 
		 $temp_file = tempnam(\sys_get_temp_dir(), 'frdloidplusplugins');
		 $handle = fopen($temp_file, "w+");
         
		  fwrite($handle, file_get_contents('https://packages.frdl.de/raw/webfan/website/installer.php'));		 
		  $tmpfile_path = stream_get_meta_data($handle)['uri'];

           $zip->addFile($tmpfile_path,  'installer.php');
		 
		 fclose($handle);
		 
		 // do something here
	//	if(file_exists($tmpfile_path)){
	//		unlink($tmpfile_path);
	//	}
		
		 
       $zip->close();
    } else {
        echo 'Fehler creating archive in '.__METHOD__;
    }
	   
  }	//!file_exists('frdl-plugins.zip')
				
	}
				   
				   
				   
				   
				   
				   
				   /*
				   
				     if(isset($_GET['test'])){
				 //	  $isTenant = OIDplus::isTenant();
				//	die('$isTenant '.$isTenant.' '.__FILE__.__LINE__);
				 ob_end_clean();
				echo print_r(static::getQuotaUsedDB(), true);
				 die();
		 	}		   
				   
				   
				   SELECT table_name AS "table",
ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "size_bm"
FROM information_schema.TABLES
WHERE table_schema = "oidplus_production" AND table_name LIKE "oidplus\_%"
ORDER BY (data_length + index_length) DESC;

OIDplus::baseConfig()->setValue('PUBSUB_MYSQL_DATABASE',   'webfan_pubsub_reg');
OIDplus::baseConfig()->setValue('PUBSUB_TABLENAME_PREFIX', 'frdl_reg_');
*/
				   
	public static function getQuotaUsedDB(){
		$sum = 0;
		$q="SELECT table_name AS `table`,
ROUND(((data_length + index_length) / 1024 / 1024), 2) AS `megabyte`
FROM information_schema.TABLES
WHERE table_schema = ? AND table_name LIKE '".str_replace('_', '\_', OIDplus::baseConfig()->getValue('TABLENAME_PREFIX'))."%'
ORDER BY (data_length + index_length) DESC";
		
		//  die($q);
	  $resQ = OIDplus::db()->query($q, [
	OIDplus::baseConfig()->getValue('MYSQL_DATABASE'), 
]);
		$t = [];
		while ($row = $resQ->fetch_array()) { 
			$sum+=$row['megabyte'];
			$t[$row['table']] = $row['megabyte'];
		}
		
		return [
			'used'=>$sum,			
			'tables'=>$t,			
		];
	}				   
				   
				   
				   
				   
				   
				   
				   
				   
	public function init($html = true): void {
        			
		$Stunrunner = $this->getWebfat(true,false);
 //     $container = $Stunrunner->getAsContainer(null); 
      $Stunrunner->init();
      $Stunrunner->autoloading();
		 $container = $Stunrunner->getAsContainer(null); 
		
		if(!is_dir(__DIR__.\DIRECTORY_SEPARATOR.'.classes')){
		  mkdir(__DIR__.\DIRECTORY_SEPARATOR.'.classes', 0775, true);	
		}
		
		if(!is_dir(__DIR__.\DIRECTORY_SEPARATOR.'.functions')){
		  mkdir(__DIR__.\DIRECTORY_SEPARATOR.'.functions', 0775, true);	
		}
		
		if(!self::$autoloaderRegistered){
		      self::$autoloaderRegistered=true;	
			  $loader = new \Webfan\Autoload\LocalPsr4Autoloader; 
			  $loader->addNamespace('\\',
						   __DIR__.\DIRECTORY_SEPARATOR.'.classes',
						   false);
			  $loader->addNamespace('\\',
						   __DIR__.\DIRECTORY_SEPARATOR.'classes',
						   false);
		     $loader->register(true) ;		
		 }
	
        $isWPHooksFunctionsInstalled 
		   = (//true === @\WPHooksFunctions::defined ||
			  \call_user_func_array(function(string $url,string $file,int $limit){
	       if(!file_exists($file) || ($limit > 0 && filemtime($file) < time() - $limit ) ){
		      $code = file_get_contents($url);
			    
		       if(false!==$code){
			     file_put_contents($file, $code); 
	        	}
	        }
	 
	      require_once $file;
	 
              return function_exists('add_action');	 
           }, ['https://webfan.de/install/?source=WPHooksFunctions',
							__DIR__.\DIRECTORY_SEPARATOR.'.functions'.\DIRECTORY_SEPARATOR.'wp-shimmy-polyfill.php',
	     -1]));		

		
		  if(!$isWPHooksFunctionsInstalled){
			 throw new \Exception('Could not init wp-functions-shim in '.__METHOD__.' '.__LINE__);  
		  }
		
		
		 $this->selfToPackage();

		 if(!static::is_cli() || true === $html){
		    $this->ob_privacy();	
		  }	 
			
				OIDplus::config()->prepareConfigKey('TENANCY_CENTRAL_DOMAIN', 
												'TENANCY_CENTRAL_DOMAIN',
					OIDplus::baseConfig()->getValue('COOKIE_DOMAIN', $_SERVER['SERVER_NAME']) ,
													OIDplusConfig::PROTECTION_EDITABLE, function ($value) {
		       
			 if(! OIDplus::isTenant() ){
				 OIDplus::baseConfig()->setValue('TENANCY_CENTRAL_DOMAIN', $value );
			 }
		});		
		if(empty(OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN'))
		   && $_SERVER['SERVER_NAME'] === $_SERVER['HTTP_HOST']
		   && ! OIDplus::isTenant() 
		  ){
			OIDplus::baseConfig()->setValue('TENANCY_CENTRAL_DOMAIN', 
											OIDplus::baseConfig()->getValue('COOKIE_DOMAIN', $_SERVER['SERVER_NAME']) );
		}
		
		
		$rel_url = false;
		$rel_url_original =substr($_SERVER['REQUEST_URI'], strlen(OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)));
		$requestMethod = $_SERVER["REQUEST_METHOD"];
		
		
		 if('/' === substr($_SERVER['REQUEST_URI'], -1)	  
			&& 0===count($_GET)
			&& OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT) === $_SERVER['REQUEST_URI'] 
			&& $this->webUriRoot(OIDplus::localpath()) === OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)){	
			  $this->handle404('/');
			  return;
			 	//    die(  'BASE URI '.basename(__FILE__).__LINE__	.OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN') );
			// return $this->handleFallbackRoutes($_SERVER['REQUEST_URI'], '/', $rel_url_original, $rel_url, $requestMethod);
		 }			

	        
		
		$tenantDirFromHost =  $_SERVER['HTTP_HOST'];
	    if(substr($tenantDirFromHost, 0, strlen('www.'))==='www.'){
			$tenantDirFromHost = substr($tenantDirFromHost, strlen('www.'), strlen($tenantDirFromHost) );
		}
		
		$tenantDirFromHost = str_replace('---', '.', $tenantDirFromHost);
		if (str_ends_with($tenantDirFromHost, OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN'))) {
            $tenantDirFromHost = substr($tenantDirFromHost, 0, -1*strlen( OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN')) );
		}
		$tenantDirFromHost = trim($tenantDirFromHost,'.');
		
	//	$tenantDirFromHost = trim(str_replace(OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN'), '', $tenantDirFromHost),'.');
		 
		if(! OIDplus::isTenant() 
			 && OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN') !== $_SERVER['HTTP_HOST'] 
			 && OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN') !== $tenantDirFromHost
			 && OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN') !== $_SERVER['SERVER_NAME'] 
		     && is_dir(OIDplus::localpath('userdata/tenant').$tenantDirFromHost.'/')
			){
			
				$_SERVER['HTTP_HOST'] = $tenantDirFromHost;
					   OIDplus::forceTenantSubDirName(
						 $tenantDirFromHost
				 );
			         //  return OIDplus::init($html);
		//	die($tenantDirFromHost);
			$testUrl = 'https://'.$tenantDirFromHost.'/systeminfo.php?goto=oidplus:system';
			if($this->get_http_response_code($testUrl) === 200){
               $redirectUrl = 'https://'.$tenantDirFromHost.$_SERVER['REQUEST_URI'];
            //   header('Location: '.$redirectUrl, 302);
			//   die('<a href="'.$redirectUrl.'">Go to '.$redirectUrl.'</a>');
			}else{  			  			    
                   
			}
			if(true === $html){
				OIDplus::init(false);
			}
		  }
		
          if(! OIDplus::isTenant() 
			 && OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN') !== $_SERVER['HTTP_HOST'] 
			 && OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN') !== $_SERVER['SERVER_NAME'] 
			 && OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN') !== $tenantDirFromHost
			){
			  die(
				  'No tenant '.basename(__FILE__).__LINE__
				.OIDplus::baseConfig()->getValue('TENANCY_CENTRAL_DOMAIN')
			  );
		  }
		
		OIDplus::config()->prepareConfigKey('FRDLWEB_FRDL_WORKDIR', 
												'Scope or Directory to save frdlweb framework source code in. Default=emty',
												'', OIDplusConfig::PROTECTION_EDITABLE, function ($value) {
		       
			 	OIDplus::baseConfig()->setValue('FRDLWEB_FRDL_WORKDIR', $value );
		});	
		
		
		
		OIDplus::config()->prepareConfigKey('FRDLWEB_PRIVACY_HIDE_MAILS', 
												'Privacy Mail Protection Addon (1=default active)',
												'1', OIDplusConfig::PROTECTION_EDITABLE, function ($value) {
		        $value = intval($value);
			 	OIDplus::baseConfig()->setValue('FRDLWEB_RDAP_PRIVACY_HIDE_MAILS', $value );
		});		
		
		OIDplus::config()->prepareConfigKey('FRDLWEB_ALIAS_PROVIDER', 
												'Alias Service Provider Domain (e.g.: "profalias.webfan.de"). Alias and privacy service (e.g. mail-hashes for privacy and relation-mappings)',
												'profalias.webfan.de', OIDplusConfig::PROTECTION_EDITABLE, function ($value) {
 
			 	OIDplus::baseConfig()->setValue('FRDLWEB_ALIAS_PROVIDER', $value );
		});				
		
		
		OIDplus::config()->prepareConfigKey('FRDLWEB_CONTAINER_REMOTE_SERVER_RELATIVE_BASE_URI', 
												'Uri relative to the OIDplus webBase to serve as endpoint for the container server https://packages.frdl.de/webfan/container-remote-server/archive/main.zip',
												'api/v1/io4/remote-container/'
											.OIDplus::baseConfig()->getValue('TENANT_OBJECT_ID_OID', 
											OIDplus::baseConfig()->getValue('TENANT_REQUESTED_HOST', 'webfan/website' ) ) 
											
											, OIDplusConfig::PROTECTION_EDITABLE, function ($value) {
 
			 	OIDplus::baseConfig()->setValue('FRDLWEB_CONTAINER_REMOTE_SERVER_RELATIVE_BASE_URI', $value );
		});				
		
		
		OIDplus::config()->prepareConfigKey('FRDLWEB_INSTALLER_REMOTE_SERVER_RELATIVE_BASE_URI', 
												'Uri relative to the OIDplus webBase to serve as endpoint for the container server https://packages.frdl.de/webfan/installer-remote-server/archive/main.zip',
												'api/v1/io4/remote-installer/'
											.OIDplus::baseConfig()->getValue('TENANT_OBJECT_ID_OID', 
											OIDplus::baseConfig()->getValue('TENANT_REQUESTED_HOST', 'webfan/website' ) ) 
											
											, OIDplusConfig::PROTECTION_EDITABLE, function ($value) {
 
			 	OIDplus::baseConfig()->setValue('FRDLWEB_INSTALLER_REMOTE_SERVER_RELATIVE_BASE_URI', $value );
		});				
				
		
		if (!OIDplus::db()->tableExists("###cron_and_jobs")) {
			if (OIDplus::db()->getSlang()->id() == 'mysql') {
				OIDplus::db()->query("CREATE TABLE IF NOT EXISTS ###cron_and_jobs
(`name` VARCHAR(255) NOT NULL ,
 `command` TEXT NOT NULL ,
 `schedule` VARCHAR(255) NOT NULL ,
 `mailer` VARCHAR(255) NULL DEFAULT 'sendmail' ,
 `maxRuntime` INT UNSIGNED NULL ,
 `smtpHost` VARCHAR(255) NULL ,
 `smtpPort` SMALLINT UNSIGNED NULL ,
 `smtpUsername` VARCHAR(255) NULL ,
 `smtpPassword` VARCHAR(255) NULL ,
 `smtpSender` VARCHAR(255) NULL DEFAULT 'jobby@localhost' ,
 `smtpSenderName` VARCHAR(255) NULL DEFAULT 'Jobby' ,
 `smtpSecurity` VARCHAR(20) NULL ,
 `runAs` VARCHAR(255) NULL ,
 `environment` TEXT NULL ,
 `runOnHost` VARCHAR(255) NULL ,
 `output` VARCHAR(255) NULL ,
 `dateFormat` VARCHAR(100) NULL DEFAULT 'Y-m-d H:i:s' ,
 `enabled` BOOLEAN NULL DEFAULT TRUE ,
 `haltDir` VARCHAR(255) NULL , `debug` BOOLEAN NULL DEFAULT FALSE ,
 PRIMARY KEY (`name`)
)");
				$this->db_table_exists = true;
			} else if (OIDplus::db()->getSlang()->id() == 'mssql') {
				// We use nvarchar(225) instead of varchar(255), see https://github.com/frdl/oidplus-plugin-alternate-id-tracking/issues/18
				// Unfortunately, we cannot use nvarchar(255), because we need two of them for the primary key, and an index must not be greater than 900 bytes in SQL Server.
				// Therefore we can only use 225 Unicode characters instead of 255.
				// It is very unlikely that someone has such giant identifiers. But if they do, then saveAltIdsForQuery() will reject the INSERT commands to avoid that an SQL Exception is thrown.
				OIDplus::db()->query("CREATE TABLE IF NOT EXISTS ###cron_and_jobs
([name] nvarchar(255) NOT NULL ,
 [command] TEXT NOT NULL ,
 [schedule] nvarchar(255) NOT NULL ,
 [mailer] nvarchar(255) NULL DEFAULT 'sendmail' ,
 [maxRuntime] int UNSIGNED NULL ,
 [smtpHost] nvarchar(255) NULL ,
 [smtpPort] SMALLINT UNSIGNED NULL ,
 [smtpUsername] nvarchar(255) NULL ,
 [smtpPassword] nvarchar(255) NULL ,
 [smtpSende] nvarchar(255) NULL DEFAULT 'jobby@localhost' ,
 [smtpSenderName] nvarchar(255) NULL DEFAULT 'Jobby' ,
 [smtpSecurity] nvarchar(20) NULL ,
 [runAs] nvarchar(255) NULL ,
 [environment] TEXT NULL ,
 [runOnHost] nvarchar(255) NULL ,
 [output] nvarchar(255) NULL ,
 [dateFormat] nvarchar(100) NULL DEFAULT 'Y-m-d H:i:s' ,
 [enabled] BOOLEAN NULL DEFAULT TRUE ,
 [haltDir] nvarchar(255) NULL , [debug] BOOLEAN NULL DEFAULT FALSE ,
 CONSTRAINT [PK_###cron_and_jobs] PRIMARY KEY ( [name] )
)");
				$this->db_table_exists = true;
			} else if (OIDplus::db()->getSlang()->id() == 'oracle') {
				// TODO: Implement Table Creation for this DBMS (see CREATE TABLE syntax at plugins/viathinksoft/sqlSlang/oracle/sql/*.sql)
				$this->db_table_exists = false;
			} else if (OIDplus::db()->getSlang()->id() == 'pgsql') {
				// TODO: Implement Table Creation for this DBMS (see CREATE TABLE syntax at plugins/viathinksoft/sqlSlang/pgsql/sql/*.sql)
				$this->db_table_exists = false;
			} else if (OIDplus::db()->getSlang()->id() == 'access') {
				// TODO: Implement Table Creation for this DBMS (see CREATE TABLE syntax at plugins/viathinksoft/sqlSlang/access/sql/*.sql)
				$this->db_table_exists = false;
			} else if (OIDplus::db()->getSlang()->id() == 'sqlite') {
				// TODO: Implement Table Creation for this DBMS (see CREATE TABLE syntax at plugins/viathinksoft/sqlSlang/sqlite/sql/*.sql)
				$this->db_table_exists = false;
			} else if (OIDplus::db()->getSlang()->id() == 'firebird') {
				// TODO: Implement Table Creation for this DBMS (see CREATE TABLE syntax at plugins/viathinksoft/sqlSlang/firebird/sql/*.sql)
				$this->db_table_exists = false;
			} else {
				// DBMS not supported
				$this->db_table_exists = false;
			}
		} else {
			$this->db_table_exists = true;
		}		
		
		
		
		if(!is_dir(__DIR__.\DIRECTORY_SEPARATOR.'installer-server'.\DIRECTORY_SEPARATOR) 
		   || !file_exists(__DIR__.\DIRECTORY_SEPARATOR.'installer-server'.\DIRECTORY_SEPARATOR.'composer.json') 
		  ){
			$this->archiveDownloadTo(__DIR__.\DIRECTORY_SEPARATOR.'installer-server'.\DIRECTORY_SEPARATOR,
									 'https://packages.frdl.de/webfan/installer-remote-server/archive/main.zip' );			
		}
		
		if(!is_dir(__DIR__.\DIRECTORY_SEPARATOR.'container-server'.\DIRECTORY_SEPARATOR) 
		   || !file_exists(__DIR__.\DIRECTORY_SEPARATOR.'container-server'.\DIRECTORY_SEPARATOR.'composer.json')  ){
			$this->archiveDownloadTo(__DIR__.\DIRECTORY_SEPARATOR.'container-server'.\DIRECTORY_SEPARATOR,
									 'https://packages.frdl.de/webfan/container-remote-server/archive/main.zip' );
		}		
			
		if(!static::is_cli() || true === $html){
		   $this->ob_privacy();	
		}elseif(false === $html 
				&& (
					static::is_cli()
					 || str_contains($_SERVER['REQUEST_URI'], '/cron.')
					)
			   ){
		   $this->cronjobRunJobby();	
			// $this->bootIO4(   );	
		}
		
		
					

	//  if( '/' === $_SERVER['REQUEST_URI']){	
	//      $this->handle404('/');
	//  }
	}//init
	
  

				   
				   
	public function archiveDownloadTo(string $dir, string $archiveUrl, ?string $archiveFilenameLocal=null, ?bool $delAfter = null ){
		if(!is_dir($dir)){
		  mkdir($dir, 0775, true);	
		}
		
		    $archiveFilenameLocal = is_string($archiveFilenameLocal) ? $archiveFilenameLocal : "package.zip";
		    $delAfter = is_bool($delAfter) ? $delAfter : true;
		
			$file =$dir. $archiveFilenameLocal;
   
		if(!file_exists($file)){	
			file_put_contents($file, fopen($archiveUrl, 'r'));  
		}		
		
		
		$path = pathinfo(realpath($file), \PATHINFO_DIRNAME); // get the absolute path to $file (leave it as it is)

	  $zip = new \ZipArchive;
	  $res = $zip->open($file);

	if ($res === TRUE) {
	  $zip->extractTo($path);
	  $zip->close();

	//  echo "<strong>$file</strong> extracted to <strong>$path</strong><br>";
	  if ($delAfter && file_exists($dir.\DIRECTORY_SEPARATOR.'composer.json') ) { 
		  unlink($file);
	  } else {
	//	  echo "remember to delete <strong>$file</strong> & <strong>$script</strong>!";
	  }
	
	 
 	} else {
	 // echo "Couldn't open $file";
		error_log(sprintf("Couldn't open %s in %s", $file, __METHOD__), 0);
	}
		
	}//archiveDownloadTo
				   
				   
				   
    public function bootIO4($Runner = null){
		 if(null === $Runner){
		    $Runner=$this->getWebfat(true,false);	 
		 }
		$Runner->init();
		 $container = $Runner->getAsContainer(null);	
 
     $CircuitBreaker = $container->get('CircuitBreaker');	

    $check = $CircuitBreaker->protect(function() use($container){	
     $check = $container->get('script@inc.common.bootstrap');
     if(!is_array($check) || !isset($check['success']) || true !== $check['success']){
      if(is_array($check) && isset($check['error']) ){
         throw new \Exception( basename(__FILE__).' line '.__LINE__.' : '.$check['error'] );
     }elseif(is_object($check) && !is_null($check) && $check instanceof \Exception){
        throw $check;
    }
    throw new \Exception('Could not bootestrap! '.print_r($check, true) );
   }
	  return $check;
  });
	
	
		//if('cli' !== strtolower(substr(\php_sapi_name(), 0, 3))){	
	//		$container->get('script@service.html.bootstrap');	
		//} 		

	}
				   
				   
	public function getWebfat(bool $load = true, bool $serveRequest = false/* load app */) {

		$defDir = is_dir($_SERVER['DOCUMENT_ROOT'].\DIRECTORY_SEPARATOR.'..') 
			&&  is_writable($_SERVER['DOCUMENT_ROOT'].\DIRECTORY_SEPARATOR.'..')
			? $_SERVER['DOCUMENT_ROOT'].\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'.frdl'
			: __DIR__.\DIRECTORY_SEPARATOR.'.frdl';
		
		$d = OIDplus::baseConfig()->getValue('FRDLWEB_FRDL_WORKDIR', '@global' );
		$frdlDir = !empty($d) && (is_dir($d) || is_writable(dirname($d))) ? $d : $defDir; 
		
		putenv('IO4_WORKSPACE_SCOPE="'.$frdlDir.'"'); 
	//	$_ENV['FRDL_WORKSPACE']=$frdlDir;
		
	     if(null === $this->StubRunner){
			 
		   $webfatFile =$this->getWebfatFile();
			 
			 if(!is_dir(dirname($webfatFile)) && dirname($webfatFile) !== $_SERVER['DOCUMENT_ROOT']){
				mkdir(dirname($webfatFile), 0775, true); 
			 }
		      require_once __DIR__.\DIRECTORY_SEPARATOR.'autoloader.php';
			 
			$getter = new ( \IO4\Webfat::getWebfatTraitSingletonClass() );
			 $getter->setStubDownloadUrl(\Frdlweb\OIDplus\Plugins\AdminPages\IO4\OIDplusPagePublicIO4::WebfatDownloadUrl);
	    	$this->StubRunner = $getter->getWebfat($webfatFile,
														 $load 
														 && OIDplus::baseConfig()->getValue('IO4_ALLOW_AUTOLOAD_FROM_REMOTE', true )
														 , $serveRequest,
														2592000,
														$getter::$_stub_download_url );
			 
			 
			 $this->StubRunner->getAsContainer(null)->set('app.$dir', $frdlDir);			 
	    }//! $this->StubRunner | null
		
	//	if(true === $serveRequest){
		//	 $this->bootIO4($this->StubRunner);
		//}
		return $this->StubRunner;
	} 
				   
	public function getWebfatFile() {	 
	    // $webfatFile =OIDplus::localpath().'webfan.setup.php';	
	 //	$webfatFile =__DIR__.\DIRECTORY_SEPARATOR.'webfan-website'.\DIRECTORY_SEPARATOR.'webfan.setup.php';	
			$webfatFile =is_writable($_SERVER['DOCUMENT_ROOT'])
				 ? $_SERVER['DOCUMENT_ROOT'].\DIRECTORY_SEPARATOR.'webfan.setup.php'
				 : OIDplus::localpath().'webfan.setup.php';	
		//if(!is_dir(dirname($webfatFile))){
		//  mkdir(dirname($webfatFile), 0775, true);	
	//	}
	     return $webfatFile;
	} 				   

	public function getWebfatSetupLink(){
           return OIDplus::webpath(dirname($this->getWebfatFile()),true).basename($this->getWebfatFile());
	}
				   
   
				   
  
				   
				   
	protected function composer(){
	  if(null === $this->composerUI){
	    if(!class_exists(\Webfan\ComposerAdapter\Installer::class, true)){
		   $this->getWebfat(true, false);	
		}
		$this->composerUI = new \Webfan\ComposerAdapter\Installer(OIDplus::localpath());
	  }
		return $this->composerUI;
	}
				   
	protected function cc(){
	  if(null === $this->packagistClient){
	    if(!class_exists(\Packagist\Api\Client::class, true)){
		   $this->getWebfat(true, false);	
		}
		$this->packagistClient = new \Packagist\Api\Client();
	  }
		return $this->packagistClient;
	}
				   
	public function getNotifications(string $user=null): array {
		$notifications = array(); 
		$notifications[] = 
			new OIDplusNotification('INFO', _L('Running <a href="%1">%2</a><br /><a href="%3">Webfan Webfat Setup (%4)</a>', 
											   OIDplus::gui()->link('oid:1.3.6.1.4.1.37476.9000.108.19361.24196'),
											  htmlentities( 'OIDplus IO4 Bridge-Plugin' ),
							                                  $this->getWebfatSetupLink(),
							                                 $user
											  )
								   );
		return $notifications;
	}			

				   
   protected function p_head(){
	   return '
	   <thead>
	     <tr><td><strong>Package</strong></td>
		   <td><strong>Description</strong></td>
		   <td><strong>Status</strong></td>
		   <td><strong>Setup</strong></td>
		 </tr>
		</thead> 
	   ';
   }
   protected function p_row($name, $repository, $description, $status, $form){
	  return sprintf(
    '
	     <tr>
		   <td><a href="%s" target="_blank">%s</a></td>
		   <td>%s</td>
		   <td>%s</td>
		   <td>%s</td>
		 </tr>
	   ',
         $repository,
         $name,
         $description,
		  $status,
		   $form
        );
   }
				   
   protected function p_status($name, $composer){
	  return sprintf(
    '  
	  <table>
	     <tr>
		   <td>composer.json</td>
		   <td>%s</td>
		 </tr>
	   </table>
	   ',
         isset($composer['require'][$name])? $composer['require'][$name] : '<span style="color:red;">uninstalled</span>'
        );
   }
				   
				   
	public function package(string $name) : array {
			     $cacheFile = $this->packagist_cache_file(['method'=>__METHOD__, 'params' => [$name]]);
		         $out = $this->packagist_read_cache($cacheFile);
			     if(!$out){
					//$out =  $this->cc()->all(['type' => 'oiplus-plugin-object-types']);
					 set_time_limit( (@ini_get('max_execution_time')) + 60 );
					$p = \call_user_func_array([$this->cc(), 'get'], [$name]);
					$r = new \ReflectionObject($p); 
				    $methods = $r->getMethods();
					$out = [];
					foreach($methods as $m){
						if(count($m->getParameters()) > 0)continue;
						$n = $m->getName();
						$n=str_replace(['get', 'set', 'is'], ['','',''], $n);
						$n=strtolower($n);
						$out[$n] = \call_user_func([$p, $m->getName()]);
					}
					$this->packagist_write_cache($out, $cacheFile); 
				 }
			      return (array)$out; 
	}				   
				   
 
				   
				   
				   
	public function packagist(string $method, array $params = []){
			     $cacheFile = $this->packagist_cache_file(['method'=>$method, 'params' => $params]);
			     $out = $this->packagist_read_cache($cacheFile);
			     if(!$out){
					set_time_limit( (@ini_get('max_execution_time')) + 60 );
					$out = \call_user_func_array([$this->cc(), $method], $params);
					$this->packagist_write_cache($out, $cacheFile); 
				 }
			      return $out; 
	}				   
				   
	protected function packagist_write_cache($out, string $cacheFile){
		 file_put_contents($cacheFile, false===$out ? json_encode(false) : json_encode($out));
	}
	protected function packagist_cache_file($query){
		  $query = print_r($query, true);
			$cacheFile = $this->packagistCacheDir. 'packagist_'
			.sha1(\get_current_user()
				  .  filemtime(__FILE__).'-'.$query.'sfgf'
				  .OIDplus::authUtils()->makeSecret(['cee75760-f4f8-11ed-b67e-3c4a92df8582'])
				 )
			.'.'
			.strlen( $query )
			.'.json'
			;
		return $cacheFile;
	}				   
	protected function packagist_read_cache(string $cacheFile){
		if (file_exists($cacheFile) && filemtime($cacheFile) >= time() - $this->packagistExpires) {
			$out = json_decode(file_get_contents($cacheFile));
			if(is_object($out) || is_bool($out)){
				return $out;
			}
		}
		return false;
	}					   
				   
				   
   public function gui_PAGE_ID_COMPOSER(string $id, array $out) {
	   $href_example_composer = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'~composer.core.2.0.txt';
 
		if ($id == self::PAGE_ID_COMPOSER && OIDplus::authUtils()->isAdminLoggedIn()) {
		
			
		     $composer = json_decode(file_get_contents(OIDplus::localpath().'composer.json'));
			 $composer = (array)$composer;
			 $composer['require'] = (array)$composer['require'];
			
             $out['title'] = _L('Composer Plugins');
             $out['text'] .= '' 
				   .'Please take a look at the <a href="'.$href_example_composer.'" target="_blank">root composer.json example</a> to find out how the OIDplus composer plugin manager can be enabled. You MUST include the <b><i>trusted and allowed plugins sections</i></b> as in the example and you MUST require the package <b><i>frdl/oidplus-composer-plugin</i></b>! All composer-plugins (like the frdl/oidplus-composer-plugin, NOT the OIDplus-plugins-packages) MUST be listed <b>first</b> (after the php requirement) in the requirements section of the composer.json file, per composer spec.!';
			
			/* 
		[ 
				"oiplus-plugin-public-pages",
				"oiplus-plugin-ra-pages",
				"oiplus-plugin-admin-pages",
				"oiplus-plugin-auth",
				"oiplus-plugin-database",
				"oiplus-plugin-sql-slang",
				"oiplus-plugin-logger",
				"oiplus-plugin-object-types",
				"oiplus-plugin-language",
				"oiplus-plugin-design",
				"oiplus-plugin-captcha"
			"project",
			"library"
		],
		*/				
			$pp_public = array();
			$pp_ra = array();
			$pp_admin = array();

			foreach (OIDplus::getPagePlugins() as $plugin) {
				if (is_subclass_of($plugin, OIDplusPagePluginPublic::class)) {
					$pp_public[] = $plugin;
				}
				if (is_subclass_of($plugin, OIDplusPagePluginRa::class)) {
					$pp_ra[] = $plugin;
				}
				if (is_subclass_of($plugin, OIDplusPagePluginAdmin::class)) {
					$pp_admin[] = $plugin;
				}
			}	
			
			
					$out['text'] .= '<h2>'._L('Public page plugins').'</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			
			
						
			   $packs = $this->packagist('all', [
					  ['type' => 'oiplus-plugin-public-pages'],
				  ]);
			 
			
			    $out['text'] .= '<table>';
			    $out['text'] .= $this->p_head();
			    $out['text'] .= '<tbody>';
			    foreach($packs as $pname){ 
				  	  $package = $this->package($pname);
					  extract($package);
					  $out['text'] .= $this->p_row($name, $repository, $description, $this->p_status($name, $composer), '');
				}
			   $out['text'] .= '</tbody>';
			    $out['text'] .= '</table>';
			
			
      //    if ($show_pages_public) {
				if (count($plugins = $pp_public) > 0) {
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '<thead>';
					$this->pluginTableHead($out);
					$out['text'] .= '</thead>';
					$out['text'] .= '<tbody>';
					foreach ($plugins as $plugin) {
						$this->pluginTableLine($out, $plugin);
					}
					$out['text'] .= '</tbody>';
					$out['text'] .= '</table>';
				}
					$out['text'] .= '</div></div>';
		 //	}

					$out['text'] .= '<h2>'._L('RA page plugins').'</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
						
			$packs = $this->packagist('all', [
					  ['type' => 'oiplus-plugin-ra-pages'],
				  ]);
			 
			
			    $out['text'] .= '<table>';
			    $out['text'] .= $this->p_head();
			    $out['text'] .= '<tbody>';
			    foreach($packs as $pname){ 
				  	  $package = $this->package($pname);
					  extract($package);
					  $out['text'] .= $this->p_row($name, $repository, $description, $this->p_status($name, $composer), '');
				}
			   $out['text'] .= '</tbody>';
			    $out['text'] .= '</table>';
			
		 //	if ($show_pages_ra) {
				if (count($plugins = $pp_ra) > 0) {
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '<thead>';
					$this->pluginTableHead($out);
					$out['text'] .= '</thead>';
					$out['text'] .= '<tbody>';
					foreach ($plugins as $plugin) {
						$this->pluginTableLine($out, $plugin);
					}
					$out['text'] .= '</tbody>';
					$out['text'] .= '</table>';
				}
					$out['text'] .= '</div></div>';
			 //}

					$out['text'] .= '<h2>'._L('Admin page plugins').'</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			
			$packs = $this->packagist('all', [
					  ['type' => 'oiplus-plugin-admin-pages'],
				  ]);
			 
			
			    $out['text'] .= '<table>';
			    $out['text'] .= $this->p_head();
			    $out['text'] .= '<tbody>';
			    foreach($packs as $pname){ 
				  	  $package = $this->package($pname);
					  extract($package);
					  $out['text'] .= $this->p_row($name, $repository, $description, $this->p_status($name, $composer), '');
				}
			   $out['text'] .= '</tbody>';
			    $out['text'] .= '</table>';
			 //if ($show_pages_admin) {
				if (count($plugins = $pp_admin) > 0) {
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '<thead>';
					$this->pluginTableHead($out);
					$out['text'] .= '</thead>';
					$out['text'] .= '<tbody>';
					foreach ($plugins as $plugin) {
						$this->pluginTableLine($out, $plugin);
					}
					$out['text'] .= '</tbody>';
					$out['text'] .= '</table>';
				}
					$out['text'] .= '</div></div>';
		 //	}

			 //if ($show_obj_active || $show_obj_inactive) {
				$enabled = true ? OIDplus::getObjectTypePluginsEnabled() : array();
				$disabled = true ? OIDplus::getObjectTypePluginsDisabled() : array();
					$out['text'] .= '<h2>'._L('Object types').'</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			
		 
			      $packs = $this->packagist('all', [
					  ['type' => 'oiplus-plugin-object-types'],
				  ]);
			 
			
			    $out['text'] .= '<table>';
			    $out['text'] .= $this->p_head();
			    $out['text'] .= '<tbody>';
			    foreach($packs as $pname){ 
				  	  $package = $this->package($pname);
					  extract($package);
					  $out['text'] .= $this->p_row($name, $repository, $description, $this->p_status($name, $composer), '');
				}
			   $out['text'] .= '</tbody>';
			    $out['text'] .= '</table>';
			
				if (count($plugins = array_merge($enabled, $disabled)) > 0) {
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '<thead>';
					$this->pluginTableHead($out);
					$out['text'] .= '</thead>';
					$out['text'] .= '<tbody>';
					foreach ($plugins as $plugin) {
						if (in_array($plugin, $enabled)) {
							$this->pluginTableLine($out, $plugin, 0);
						} else {
							$this->pluginTableLine($out, $plugin, 2, _L('disabled'));
						}
					}
					$out['text'] .= '</tbody>';
					$out['text'] .= '</table>';
				}
					$out['text'] .= '</div></div>';
			 //}
			
			
			
			

					$out['text'] .= '<h2>'._L('Database providers').'</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			
			$packs = $this->packagist('all', [
					  ['type' => 'oiplus-plugin-database'],
				  ]);
			 
			
			    $out['text'] .= '<table>';
			    $out['text'] .= $this->p_head();
			    $out['text'] .= '<tbody>';
			    foreach($packs as $pname){ 
				  	  $package = $this->package($pname);
					  extract($package);
					  $out['text'] .= $this->p_row($name, $repository, $description, $this->p_status($name, $composer), '');
				}
			   $out['text'] .= '</tbody>';
			    $out['text'] .= '</table>';
		 //	if ($show_db_active || $show_db_inactive) {
				if (count($plugins = OIDplus::getDatabasePlugins()) > 0) {
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '<thead>';
					$this->pluginTableHead($out);
					$out['text'] .= '</thead>';
					$out['text'] .= '<tbody>';
					foreach ($plugins as $plugin) {
						$active = $plugin->isActive();
						//if ($active && !$show_db_active) continue;
						//if (!$active && !$show_db_inactive) continue;
						$this->pluginTableLine($out, $plugin, $active?1:0, $active?_L('active'):'');
					}
					$out['text'] .= '</tbody>';
					$out['text'] .= '</table>';
				}
					$out['text'] .= '</div></div>';
		 //	}

					$out['text'] .= '<h2>'._L('SQL slang plugins').'</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$packs = $this->packagist('all', [
					  ['type' => 'oiplus-plugin-sql-slang'],
				  ]);
			 
			
			    $out['text'] .= '<table>';
			    $out['text'] .= $this->p_head();
			    $out['text'] .= '<tbody>';
			    foreach($packs as $pname){ 
				  	  $package = $this->package($pname);
					  extract($package);
					  $out['text'] .= $this->p_row($name, $repository, $description, $this->p_status($name, $composer), '');
				}
			   $out['text'] .= '</tbody>';
			    $out['text'] .= '</table>';			
			
			 //if ($show_sql_active || $show_sql_inactive) {
				if (count($plugins = OIDplus::getSqlSlangPlugins()) > 0) {
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '<thead>';
					$this->pluginTableHead($out);
					$out['text'] .= '</thead>';
					$out['text'] .= '<tbody>';
					foreach ($plugins as $plugin) {
						$active = $plugin->isActive();
						//if ($active && !$show_sql_active) continue;
						//if (!$active && !$show_sql_inactive) continue;
						$this->pluginTableLine($out, $plugin, $active?1:0, $active?_L('active'):'');
					}
					$out['text'] .= '</tbody>';
					$out['text'] .= '</table>';
				}
					$out['text'] .= '</div></div>';
			 //}

					$out['text'] .= '<h2>'._L('RA authentication providers').'</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			
			$packs = $this->packagist('all', [
					  ['type' => 'oiplus-plugin-auth'],
				  ]);
			 
			
			    $out['text'] .= '<table>';
			    $out['text'] .= $this->p_head();
			    $out['text'] .= '<tbody>';
			    foreach($packs as $pname){ 
				  	  $package = $this->package($pname);
					  extract($package);
					  $out['text'] .= $this->p_row($name, $repository, $description, $this->p_status($name, $composer), '');
				}
			   $out['text'] .= '</tbody>';
			    $out['text'] .= '</table>';					
			 //if ($show_auth) {
				if (count($plugins = OIDplus::getAuthPlugins()) > 0) {
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '<thead>';
					$this->pluginTableHead($out);
					$out['text'] .= '</thead>';
					$out['text'] .= '<tbody>';
					foreach ($plugins as $plugin) {
						$default = OIDplus::getDefaultRaAuthPlugin(true)->getManifest()->getOid() === $plugin->getManifest()->getOid();

						$reason_hash = '';
						$can_hash = $plugin->availableForHash($reason_hash);

						$reason_verify = '';
						$can_verify = $plugin->availableForHash($reason_verify);

						if ($can_hash && !$can_verify) {
							$note = _L('Only hashing, no verification');
							if (!empty($reason_verify)) $note .= '. '.$reason_verify;
							$modifier = $default ? 1 : 0;
						}
						else if (!$can_hash && $can_verify) {
							$note = _L('Only verification, no hashing');
							if (!empty($reason_hash)) $note .= '. '.$reason_hash;
							$modifier = $default ? 1 : 0;
						}
						else if (!$can_hash && !$can_verify) {
							$note = _L('Not available on this system');
							$app1 = '';
							$app2 = '';
							if (!empty($reason_verify)) $app1 = $reason_verify;
							if (!empty($reason_hash)) $app2 = $reason_hash;
							if ($app1 != $app2) {
								$note .= '. '.$app1.'. '.$app2;
							} else {
								$note .= '. '.$app1;
							}
							$modifier = 2;
						}
						else /*if ($can_hash && $can_verify)*/ {
							$modifier = $default ? 1 : 0;
							$note = '';
						}

						$this->pluginTableLine($out, $plugin, $modifier, $note);
					}
					$out['text'] .= '</tbody>';
					$out['text'] .= '</table>';
				}
					$out['text'] .= '</div></div>';
		 //	}

					$out['text'] .= '<h2>'._L('Logger plugins').'</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$packs = $this->packagist('all', [
					  ['type' => 'oiplus-plugin-logger'],
				  ]);
			 
			
			    $out['text'] .= '<table>';
			    $out['text'] .= $this->p_head();
			    $out['text'] .= '<tbody>';
			    foreach($packs as $pname){ 
				  	  $package = $this->package($pname);
					  extract($package);
					  $out['text'] .= $this->p_row($name, $repository, $description, $this->p_status($name, $composer), '');
				}
			   $out['text'] .= '</tbody>';
			    $out['text'] .= '</table>';					
			
		 //	if ($show_logger) {
				if (count($plugins = OIDplus::getLoggerPlugins()) > 0) {
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '<thead>';
					$this->pluginTableHead($out);
					$out['text'] .= '</thead>';
					$out['text'] .= '<tbody>';
					foreach ($plugins as $plugin) {
						$reason = '';
						if ($plugin->available($reason)) {
							$this->pluginTableLine($out, $plugin, 0);
						} else if ($reason) {
							$this->pluginTableLine($out, $plugin, 2, _L('not available: %1',$reason));
						} else {
							$this->pluginTableLine($out, $plugin, 2, _L('not available'));
						}
					}
					$out['text'] .= '</tbody>';
					$out['text'] .= '</table>';
				}
					$out['text'] .= '</div></div>';
			 //}

					$out['text'] .= '<h2>'._L('Languages').'</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$packs = $this->packagist('all', [
					  ['type' => 'oiplus-plugin-language'],
				  ]);
			 
			
			    $out['text'] .= '<table>';
			    $out['text'] .= $this->p_head();
			    $out['text'] .= '<tbody>';
			    foreach($packs as $pname){ 
				  	  $package = $this->package($pname);
					  extract($package);
					  $out['text'] .= $this->p_row($name, $repository, $description, $this->p_status($name, $composer), '');
				}
			   $out['text'] .= '</tbody>';
			    $out['text'] .= '</table>';					
			
		 //	if ($show_language) {
				if (count($plugins = OIDplus::getLanguagePlugins()) > 0) {
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '<thead>';
					$this->pluginTableHead($out);
					$out['text'] .= '</thead>';
					$out['text'] .= '<tbody>';
					foreach ($plugins as $plugin) {
						$default = OIDplus::getDefaultLang() === $plugin->getLanguageCode();
						$this->pluginTableLine($out, $plugin, $default?1:0, $default?_L('default'):'');
					}
					$out['text'] .= '</tbody>';
					$out['text'] .= '</table>';
				}
					$out['text'] .= '</div></div>';
		 //	}

					$out['text'] .= '<h2>'._L('Designs').'</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$packs = $this->packagist('all', [
					  ['type' => 'oiplus-plugin-design'],
				  ]);
			 
			
			    $out['text'] .= '<table>';
			    $out['text'] .= $this->p_head();
			    $out['text'] .= '<tbody>';
			    foreach($packs as $pname){ 
				  	  $package = $this->package($pname);
					  extract($package);
					  $out['text'] .= $this->p_row($name, $repository, $description, $this->p_status($name, $composer), '');
				}
			   $out['text'] .= '</tbody>';
			    $out['text'] .= '</table>';				
			 //if ($show_design_active || $show_design_inactive) {
				if (count($plugins = OIDplus::getDesignPlugins()) > 0) {
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '<thead>';
					$this->pluginTableHead($out);
					$out['text'] .= '</thead>';
					$out['text'] .= '<tbody>';
					foreach ($plugins as $plugin) {
						$active = $plugin->isActive();
						//if ($active && !$show_design_active) continue;
						//if (!$active && !$show_design_inactive) continue;
						$this->pluginTableLine($out, $plugin, $active?1:0, $active?_L('active'):'');
					}
					$out['text'] .= '</tbody>';
					$out['text'] .= '</table>';
				}
					$out['text'] .= '</div></div>';
			 //}
 //
			 //if ($show_captcha_active || $show_captcha_inactive) {
					$out['text'] .= '<h2>'._L('CAPTCHA plugins').'</h2>';
					$out['text'] .= '<div class="container box"><div id="suboid_table" class="table-responsive">';
			$packs = $this->packagist('all', [
					  ['type' => 'oiplus-plugin-captcha'],
				  ]);
			 
			
			    $out['text'] .= '<table>';
			    $out['text'] .= $this->p_head();
			    $out['text'] .= '<tbody>';
			    foreach($packs as $pname){ 
				  	  $package = $this->package($pname);
					  extract($package);
					  $out['text'] .= $this->p_row($name, $repository, $description, $this->p_status($name, $composer), '');
				}
			   $out['text'] .= '</tbody>';
			    $out['text'] .= '</table>';				
				if (count($plugins = OIDplus::getCaptchaPlugins()) > 0) {
					$out['text'] .= '<table class="table table-bordered table-striped">';
					$out['text'] .= '<thead>';
					$this->pluginTableHead($out);
					$out['text'] .= '</thead>';
					$out['text'] .= '<tbody>';
					foreach ($plugins as $plugin) {
						$active = $plugin->isActive();
						//if ($active && !$show_captcha_active) continue;
						//if (!$active && !$show_captcha_inactive) continue;
						$this->pluginTableLine($out, $plugin, $active?1:0, $active?_L('active'):'');
					}
					$out['text'] .= '</tbody>';
					$out['text'] .= '</table>';
				}
					$out['text'] .= '</div></div>';
			 //}
		 			
			
		}//gui and is admin
	   return $out;
   }				   
				   
				   
				   
		   
	public function modifyContent($id, &$title, &$icon, &$text): void {
				
		if(!static::is_cli() ){ 
			$this->ob_privacy();	
		}
		$text = $this->privacy_protect_mails($text);
		
		$content = '';
		$CRUD = '';

		$weidObj = false;	 

		$id = explode('$', $id, 2)[0];
		$obj = OIDplusObject::parse($id);
		$textCircuit = '';
		
	//	$textCircuit.=print_r( [$id, $title], true);
	//	$content.=print_r( [$id, $icon], true);
		if($obj){
			  switch($obj::ns()){
				  case 'ipv4' :
				  case 'ipv6' :
					  //   putenv('IO4_WORKSPACE_SCOPE=@global');
					  //   putenv('IO4_WORKSPACE_SCOPE=@www');
					  //   putenv('IO4_WORKSPACE_SCOPE=@cwd');
					     $content.= //getenv('IO4_WORKSPACE_SCOPE').
							 'Please note that the IPs listed here are for internal use and'
							 .' may NOT be accessable via the REAL-IP in the internet as you might expect';
					  break;
				  default:
					    
					  break;
			  }
		}
		
		$CRUD = $textCircuit . $CRUD;


		$content = (false === strpos($content, '%%CRUD%%'))
			? $content . $CRUD 
			: str_replace('%%CRUD%%', \PHP_EOL . $CRUD . \PHP_EOL . '%%CRUD%%', $content);

		$handled = false;
		//  parent::gui($id, $content2, $handled);
		$content = $this->privacy_protect_mails($content);		
		$text = $content.$text;


		$text = str_replace($_SERVER['DOCUMENT_ROOT'], '***', $text);

		
		$this->modifyContent_schema( $id, $title, $icon, $text);
		$this->modifyContent_attributes( $id, $title, $icon, $text);
		$this->modifyContent_pki( $id, $title, $icon, $text);
		$this->modifyContent_log( $id, $title, $icon, $text);
		$text = $this->privacy_protect_mails($text);
	}
	

   public function gui(string $id, array &$out, bool &$handled): void {
	   	
	   if(!static::is_cli() ){ 
			$this->ob_privacy();	
		}
	   
	   	$out['text'] = $this->privacy_protect_mails($out['text']);
		$parts = explode('$',$id,2);	 
	   
		$id = $parts[0];
		$ra_email = $parts[1] ?? null/*no filter*/;

		   
	   if(isset(self::PAGES[$id]) && is_callable([$this, self::PAGES[$id]])){ 
		   $handled = true;
          $out = \call_user_func_array([$this, self::PAGES[$id]], [$id, $out]);   
	   }
 
	   	$out['text'] = $this->privacy_protect_mails($out['text']);
	}	
				   
				   
   public function gui_PAGE_ID_WEBFAT(string $id, array $out) {
		if ($id == self::PAGE_ID_WEBFAT && OIDplus::authUtils()->isAdminLoggedIn()) {
          //  header('Location: '.$this->getWebfatSetupLink());
			//die('<meta http-equiv="refresh" content="0; url='.$this->getWebfatSetupLink().'">');
             $out['text'] .= ''//'<meta http-equiv="refresh" content="0; url='.$this->getWebfatSetupLink().'">'
				   .'<a href="'.$this->getWebfatSetupLink().'">'.$this->getWebfatSetupLink().'</a>';
		}
	   return $out;
   }   
	
	public function gui_PAGE_ID_BRIDGE(string $id, array $out) {
		if ($id == self::PAGE_ID_BRIDGE && OIDplus::authUtils()->isAdminLoggedIn()) {
		
			$out['title'] = _L('IO4 Bridge');
			//IO4_ALLOW_AUTOLOAD_FROM_REMOTE
			$out['text'] .= <<<HTMLCODE
			<legend>Remote autoloading</legend>
			This is a functiuonallity for developer and admin purposes only when in setup/update or install mode. 
			If everything is up and running fine you should disable it. If a class is missing when disabled please contact the 
			developer of the plugin or core.<br />
			To DISABLE autoloading classes from remote servers of Github, Webfan or custom, please set the OIDplus config variable
			<br />
			<b>IO4_ALLOW_AUTOLOAD_FROM_REMOTE</b> to <i>false</i>.
			<br />
			HTMLCODE;
		}elseif($id == self::PAGE_ID_BRIDGE){
			$handled = true;
			
		}
		
	   return $out;
   }
				   



				   


		   
				   


 
	
		
   	protected function modifyContent_log(string $id, string &$title, string &$icon, string &$text) {

	}
				   
		
   	protected function modifyContent_pki(string $id, string &$title, string &$icon, string &$text) {

	}
		
   	protected function modifyContent_attributes(string $id, string &$title, string &$icon, string &$text) {

	}
				   

	public function whoisObjectAttributes_log(string $id, array &$out) {
		$xmlns = 'webfat-for-oidplus';
		$xmlschema = 'urn:oid:1.3.6.1.4.1.37553.8.1.8.8.53354196964.24196.1714020422';
		$xmlschemauri = OIDplus::webpath(__DIR__.'/io4.log.xsd',OIDplus::PATH_ABSOLUTE);		
		
			
		$handleShown = false;
		$canonicalShown = false;

		$out1 = array();

						   

			 
	      $out = array_merge($out, $out1);  		
	}	
	
	public function whoisObjectAttributes_pki(string $id, array &$out) {
		$xmlns = 'webfat-for-oidplus';
		$xmlschema = 'urn:oid:1.3.6.1.4.1.37553.8.1.8.8.53354196964.24196.1714020422';
		$xmlschemauri = OIDplus::webpath(__DIR__.'/io4.pki.xsd',OIDplus::PATH_ABSOLUTE);		
		
			
		$handleShown = false;
		$canonicalShown = false;

		$out1 = array();
		$out2 = array();
						   

			 
	      $out = array_merge($out, $out1);  
		  $out = array_merge($out, $out2); 		
	}					   
	
				   
	protected function schema_write_cache($out, string $cacheFile){
		 file_put_contents($cacheFile, false===$out ? json_encode(false) : json_encode($out));
	}
	protected function schema_cache_file($query){
			$cacheFile = $this->schemaCacheDir. 'schema_'
			.sha1(\get_current_user()
				  .  filemtime(__FILE__).'-'.$query.'sfgf'
				  .OIDplus::authUtils()->makeSecret(['cee75760-f4f8-11ed-b67e-3c4a92df8582'])
				 )
			.'.'
			.strlen( $query )
			.'.json'
			;
		return $cacheFile;
	}
	/**
	 * @param string $cacheFile
	 * @param int $rdapCacheExpires
	 * @return array|null
	 */
	protected function schema_read_cache(string $cacheFile, int $rdapCacheExpires){
		if (file_exists($cacheFile) && filemtime($cacheFile) >= time() - $rdapCacheExpires) {
			$out = json_decode(file_get_contents($cacheFile));
			if(is_object($out) || is_bool($out)){
				return $out;
			}
		}
		return false;
	}				
		
   	protected function modifyContent_schema(string $id, string &$title, string &$icon, string &$text) {
            //$tmp = $this->schema_read_cache($this->schema_cache_file($id), $this->schemaCacheExpires);
			//if ($tmp) return $tmp;
	}				   			   
				   
		
   	protected function schema_get(string $id ) {
           $cacheFile =$this->schema_cache_file($id);
		   $apiUrltemplateGetSchema = OIDplus::baseConfig()->getValue('API_UR_TEMPLATE_SCHEMA_GET',
																	   'https://api.webfan.de/registry/api/meta/schema/%1s'
																	 );
 
		 $url = sprintf($apiUrltemplateGetSchema, urlencode(WeidOidConverter::oid2weid($id)));
			 
			 
		$httpResult = $this->getWebfat(true,false)->getRemoteAutoloader()->transport($url, 'GET');
		$schema = false;
		if($httpResult->status == 200){
			 $res = json_decode($httpResult->body);
			$schema = $res->code == 200 ? $res->result : false;
			$this->schema_write_cache($schema,  $cacheFile);
		}else{
			$this->schema_write_cache(false,  $cacheFile);
		}
		
	 
		
	}				   			   
				
				   
				   
				   
	public function whoisObjectAttributes_attributes(string $id, array &$out) {
		$xmlns = 'webfat-for-oidplus';
		$xmlschema = 'urn:oid:1.3.6.1.4.1.37553.8.1.8.8.53354196964.24196.1714020422';
		$xmlschemauri = OIDplus::webpath(__DIR__.'/io4.attributes.xsd',OIDplus::PATH_ABSOLUTE);		
		
			
		$handleShown = false;
		$canonicalShown = false;

		$out1 = array(); 
						   
	  		
		$id_schema = $id;				
		 $obj = OIDplusObject::findFitting($id_schema);
		 if($obj){
			// $id = $obj->nodeId(); 
			 $id_schema = $obj->nodeId(false); 
		 }
		 //	if(isset($_GET['test']))die(OIDplusPagePublicAttachments::getUploadDir($obj->nodeId(true)));
		
		

			 
	      $out = array_merge($out, $out1);  	
	}				   
	   
				   
				   
				   
	public function whoisObjectAttributes(string $id, array &$out): void {
		if(true !== OIDplus::baseConfig()->getValue('ENABLE_IO4_ATTRIBUTES', true ) ){
		  return;	
		} 
		$xmlns = 'webfat-for-oidplus';
		$xmlschema = 'urn:oid:1.3.6.1.4.1.37553.8.1.8.8.53354196964.24196.1714020422';
		$xmlschemauri = OIDplus::webpath(__DIR__.'/io4.xsd',OIDplus::PATH_ABSOLUTE);		
		
			
		$handleShown = false;
		$canonicalShown = false;

		$out1 = array(); 
		$id_schema = $id;				
		 $obj = OIDplusObject::findFitting($id_schema);
		 if($obj){
			// $id = $obj->nodeId(); 
			 $id_schema = $obj->nodeId(false); 
		 }
			
		
		//if(@isset($_GET['test']))die($id_schema);
		
		
	             $cacheFile =$this->schema_cache_file($id_schema);
		
		      if(!file_exists($cacheFile)){
				  $this->schema_get($id_schema);
			  }
		
		
		         $schema = $this->schema_read_cache($cacheFile, $this->schemaCacheExpires);
				          
		        if ($schema){
					  				
				   $out1[] = [
					  'xmlns' => $xmlns,
					  'xmlschema' => $xmlschema,
					   'xmlschemauri' => $xmlschemauri,
					  'name' => 'json-schema',
					  'value' =>json_encode($schema),// $schema, 
			    	];
				  }
		
	

			 
	      $out = array_merge($out, $out1);   
		
		 	$this->whoisObjectAttributes_attributes( $id, $out);
		    $this->whoisObjectAttributes_pki( $id, $out);
		    $this->whoisObjectAttributes_log( $id, $out);
	}

	
	public function whoisRaAttributes(string $email = null, array &$out): void {
		
		if(true !== OIDplus::baseConfig()->getValue('ENABLE_IO4_ATTRIBUTES', true ) ){
		  return;	
		}	
		
		
            //reference-id
		$xmlns = 'webfat-for-oidplus';
		$xmlschema = 'urn:oid:1.3.6.1.4.1.37553.8.1.8.8.53354196964.24196.1714020422';
		$xmlschemauri = OIDplus::webpath(__DIR__.'/io4.xsd',OIDplus::PATH_ABSOLUTE);		
		
			
		$handleShown = false;
		$canonicalShown = false;

		$out1 = array();
		$out2 = array();
		
		
		$mailIn = null === $email ? '/@IN' : $email;
		$mailOut = null === $email ? '/@OUT' : $email;
		
			
	      $out = array_merge($out, $out1);  
		  $out = array_merge($out, $out2); 		
	}
			   
				   


	
	public function restApiInfo(string $kind='html'): string {
		if ($kind === 'html') {
			$struct = [
				
		
				_L('@ (ALL METHODS) Installer') => [
					'<b>Remote-Installer API Server Endpoint</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).OIDplus::baseConfig()->getValue('FRDLWEB_INSTALLER_REMOTE_SERVER_RELATIVE_BASE_URI', 
																			'api/v1/io4/remote-installer/'
											.OIDplus::baseConfig()->getValue('TENANT_OBJECT_ID_OID', 
											OIDplus::baseConfig()->getValue('TENANT_REQUESTED_HOST', 'webfan/website' ) ) )
,
					_L('Input parameters') => [
						'<i>'._L('None').'</i>'
					],
					_L('Output parameters') => [
						'mixed...'
					]
				],
						
						_L('@ (ALL METHODS) Container') => [
					'<b>Remote-Container API Server Endpoint</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL). OIDplus::baseConfig()->getValue('FRDLWEB_CONTAINER_REMOTE_SERVER_RELATIVE_BASE_URI', 
																			'api/v1/io4/remote-container/'
											.OIDplus::baseConfig()->getValue('TENANT_OBJECT_ID_OID', 
											OIDplus::baseConfig()->getValue('TENANT_REQUESTED_HOST', 'webfan/website' ) ) )
							,
					_L('Input parameters') => [
						'<i>'._L('None').'</i>'
					],
					_L('Output parameters') => [
						'mixed...'
					]
				],
						
						
				
				
				_L('@ Get') => [
					'<b>GET</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/io4/@/<abbr title="'._L('e.g. %1', '@/oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
						'<i>'._L('None').'</i>'
					],
					_L('Output parameters') => [
						'mixed...'
					]
				],
				
	
				_L('@ Set') => [
					'<b>POST</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/io4/@/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
					'mixed...'
					],
					_L('Output parameters') => [
						'mixed...'
					]
				],			
				
				_L('@ Remove') => [
					'<b>DELETE</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/io4/@/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
					'mixed...'
					],
					_L('Output parameters') => [
						'mixed...'
					]
				],	
				
	
				_L('@ Update') => [
					'<b>PUT</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/io4/@/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
					'mixed...'
					],
					_L('Output parameters') => [
						'mixed...'
					]
				],	
							
				
				_L('Receive') => [
					'<b>GET</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/io4/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
						'<i>'._L('None').'</i>'
					],
					_L('Output parameters') => [
						'mixed...'
					]
				],
				_L('Re-Create') => [
					'<b>PUT</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/io4/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
						'mixed...'
					],
					_L('Output parameters') => [
						'mixed...'
					]
				],
				_L('Create') => [
					'<b>POST</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/io4/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
					'mixed...'
					],
					_L('Output parameters') => [
						'mixed...'
					]
				],
				_L('Update') => [
					'<b>PATCH</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/io4/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
						'mixed...'
					],
					_L('Output parameters') => [
					'mixed...'
					]
				],
				_L('Remove') => [
					'<b>DELETE</b> '.OIDplus::webpath(null,OIDplus::PATH_ABSOLUTE_CANONICAL).'rest/v1/io4/<abbr title="'._L('e.g. %1', 'oid:2.999').'">[id]</abbr>',
					_L('Input parameters') => [
						'<i>'._L('None').'</i>'
					],
					_L('Output parameters') => [
						'mixed...'
					]
				]
			];
			return array_to_html_ul_li($struct);
		} else {
			throw new OIDplusException(_L('Invalid REST API information format'), null, 500);
		}
	}
	
	
	


	public function restApiCall(string $requestMethod, string $endpoint, array $json_in)/*: array|false*/ {
		 
		if (str_starts_with($endpoint, 'io4/')) {
			$id = substr($endpoint, strlen('io4/'));
			$obj = OIDplusObject::findFitting($id);
				
			if (!$obj) {
				$obj = OIDplusObject::parse($id);
			}
			/*	*/
				
	    	if (!$obj) {
              http_response_code(404);
           //   throw new OIDplusException(_L('REST endpoint not found'), null, 404);
           		OIDplus::invoke_shutdown();
		    	@header('Content-Type:application/json; charset=utf-8');
			    echo json_encode([
			      'code'=>404,
			      'message'=>'Not found',
			      'endpoint'=>$endpoint,
			      'method'=>$requestMethod,
			      'payload'=>$json_in,
			    ]);
			    die(); // return true;
			}
			
			if('GET' === $requestMethod){
				 if (!$obj->userHasReadRights() && $obj->isConfidential()){
    		        throw new OIDplusException('Insufficient authorization to read information about this object.', null, 401);
		         }	
		         
	              http_response_code(200);
           //   throw new OIDplusException(_L('REST endpoint not found'), null, 404);
           		OIDplus::invoke_shutdown();
		    	@header('Content-Type:application/json; charset=utf-8');
			    echo json_encode([
			      'code'=>200,
			      'message'=>'Allocation Information Data',
			      'endpoint'=>$endpoint,
			      'method'=>$requestMethod,
			      'object'=>(array)$obj,
			      'alloc'=>[],
			    ]);
			    die(); // return true;	         
		         
		         
			}elseif('DELETE' === $requestMethod){
				if (!$obj->userHasParentalWriteRights()){
		         throw new OIDplusException(_L('Authentication error. Please log in as the superior RA to delete this OID.'),
		           null, 401);
				}
	             
	              http_response_code(200);
            
           		OIDplus::invoke_shutdown();
		    	@header('Content-Type:application/json; charset=utf-8');
			    echo json_encode([
			      'code'=>200,
			      'message'=>'Allocation dereferenced',
			      'endpoint'=>$endpoint,
			      'method'=>$requestMethod,
			      'object'=>(array)$obj,
			      'alloc'=>[],
			    ]);
			    die(); // return true;	     
			}else{
				if (!$obj->userHasParentalWriteRights()){
		         throw new OIDplusException(_L('Authentication error. Please log in as the superior RA to maintain this OID.'),
		           null, 401);
				}
				
					          
			    http_response_code(200);
            
           		OIDplus::invoke_shutdown();
		    	@header('Content-Type:application/json; charset=utf-8');
			    echo json_encode([
			      'code'=>200,
			      'message'=>'Allocation modified',
			      'endpoint'=>$endpoint,
			      'method'=>$requestMethod,
			      'object'=>(array)$obj,
			      'alloc'=>[],
			    ]);
			    die(); // return true;				
			}

    	  
		}
	}
					   
 
		/**
	 * @param array $out
	 * @return void
	 */
	private function pluginTableHead(array &$out) {
		$out['text'] .= '	<tr>';
		$out['text'] .= '		<th width="30%">'._L('Class name').'</th>';
		$out['text'] .= '		<th width="30%">'._L('Plugin name').'</th>';
		$out['text'] .= '		<th width="10%">'._L('Version').'</th>';
		$out['text'] .= '		<th width="15%">'._L('Author').'</th>';
		$out['text'] .= '		<th width="15%">'._L('License').'</th>';
		$out['text'] .= '	</tr>';
	}

	/**
	 * @param array $out
	 * @param OIDplusPlugin $plugin
	 * @param int $modifier
	 * @param string $na_reason
	 * @return void
	 */
	private function pluginTableLine(array &$out, OIDplusPlugin $plugin, int $modifier=0, string $na_reason='') {
		$html_reason = empty($na_reason) ? '' : ' ('.htmlentities($na_reason).')';
		$out['text'] .= '	<tr>';
		if ($modifier == 0) {
			// normal line
			$out['text'] .= '		<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'>'.htmlentities(get_class($plugin)).'</a>'.$html_reason.'</td>';
		} else if ($modifier == 1) {
			// active
			$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'><b>'.htmlentities(get_class($plugin)).'</b>'.$html_reason.'</a></td>';
		} else if ($modifier == 2) {
			// not available with reason
			$out['text'] .= '<td><a '.OIDplus::gui()->link('oidplus:system_plugins$'.get_class($plugin)).'><font color="gray">'.htmlentities(get_class($plugin)).'</font></a><font color="gray">'.$html_reason.'</font></td>';
		}
		$out['text'] .= '		<td>' . htmlentities(empty($plugin->getManifest()->getName()) ? _L('n/a') : $plugin->getManifest()->getName()) . '</td>';
		$out['text'] .= '		<td>' . htmlentities(empty($plugin->getManifest()->getVersion()) ? _L('n/a') : $plugin->getManifest()->getVersion()) . '</td>';
		$out['text'] .= '		<td>' . htmlentities(empty($plugin->getManifest()->getAuthor()) ? _L('n/a') : $plugin->getManifest()->getAuthor()) . '</td>';
		$out['text'] .= '		<td>' . htmlentities(empty($plugin->getManifest()->getLicense()) ? _L('n/a') : $plugin->getManifest()->getLicense()) . '</td>';
		$out['text'] .= '	</tr>';
	}

				   
				   
 

	
	
		public static function getCommonHeadElems(string $title): array {
		// Get theme color (color of title bar)
		$design_plugin = OIDplus::getActiveDesignPlugin();
		$theme_color = is_null($design_plugin) ? '' : $design_plugin->getThemeColor();

		$head_elems = array();
		$head_elems[] = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
		$head_elems[] = '<meta charset="UTF-8">';
		if (OIDplus::baseConfig()->getValue('DATABASE_PLUGIN','') !== '') {
			$head_elems[] = '<meta name="OIDplus-SystemTitle" content="'.htmlentities(OIDplus::config()->getValue('system_title')).'">'; // Do not remove. This meta tag is acessed by oidplus_base.js
		}
		if ($theme_color != '') {
			$head_elems[] = '<meta name="theme-color" content="'.htmlentities($theme_color).'">';
		}
		$head_elems[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
		$head_elems[] = '<title>'.htmlentities($title).'</title>';
		$tmp = (OIDplus::insideSetup()) ? '?noBaseConfig=1' : '';
		$head_elems[] = '<script src="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'polyfill.min.js.php'.$tmp.'"></script>';
		$head_elems[] = '<script src="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'oidplus.min.js.php'.$tmp.'" type="text/javascript"></script>';
		$head_elems[] = '<link rel="stylesheet" href="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'oidplus.min.css.php'.$tmp.'">';
		$head_elems[] = '<link rel="icon" type="image/png" href="'.htmlentities(OIDplus::webpath(null, OIDplus::PATH_RELATIVE)).'favicon.png.php">';
		if (OIDplus::baseConfig()->exists('CANONICAL_SYSTEM_URL')) {
		/*
			$head_elems[] = '<link rel="canonical" href="'.htmlentities(OIDplus::canonicalURL().OIDplus::webpath(null, OIDplus::PATH_ABSOLUTE_CANONICAL)).'">';
			*/
						$head_elems[] = '<link rel="canonical" href="'.rtrim(OIDplus::webpath(null, 
                               OIDplus::PATH_ABSOLUTE_CANONICAL),'/ ').$_SERVER['REQUEST_URI']
							.'">';
		}

		//$files[] = 'var csrf_token = '.js_escape($_COOKIE['csrf_token'] ?? '').';';
		//$files[] = 'var samesite_policy = '.js_escape(OIDplus::baseConfig()->getValue('COOKIE_SAMESITE_POLICY','Strict')).';';
		$head_elems[] = '<script>var csrf_token = '.js_escape($_COOKIE['csrf_token'] ?? '').';</script>';		
		$head_elems[] = '<script>var csrf_token_weak = '.js_escape($_COOKIE['csrf_token_weak'] ?? '').';</script>';		
		$head_elems[] = 
			'<script>var samesite_policy = '.js_escape(OIDplus::baseConfig()->getValue('COOKIE_SAMESITE_POLICY','Strict')).';</script>';		
		return $head_elems;
	}	
	
	
	public static function showMainPage(string $page_title_1, string $page_title_2, string $static_icon, string $static_content, array $extra_head_tags=array(), string $static_node_id=''): string {
		
		
		$_REQUEST['goto'] = $static_node_id;
		
   if (!isset($_COOKIE['csrf_token'])) {
	// This is the main CSRF token used for AJAX.
	$token = OIDplus::authUtils()->genCSRFToken();
	OIDplus::cookieUtils()->setcookie('csrf_token', $token, 0, false);
	unset($token);
  }

  if (!isset($_COOKIE['csrf_token_weak'])) {
	// This CSRF token is created with SameSite=Lax and must be used
	// for OAuth 2.0 redirects or similar purposes.
	$token = OIDplus::authUtils()->genCSRFToken();
	OIDplus::cookieUtils()->setcookie('csrf_token_weak', $token, 0, false, 'Lax');
	unset($token);
  }	   		
		
	//	$head_elems = (new OIDplusGui())->getCommonHeadElems($page_title_1);
		$head_elems = static::getCommonHeadElems($page_title_1);
		$head_elems = array_merge($extra_head_tags, $head_elems);

		$plugins = OIDplus::getAllPlugins();
		foreach ($plugins as $plugin) {
			$plugin->htmlHeaderUpdate($head_elems);
		}

		# ---

		$out  = "<!DOCTYPE html>\n";

		$out .= "<html lang=\"".substr(OIDplus::getCurrentLang(),0,2)."\">\n";
		$out .= "<head>\n";
		$out .= "\t".implode("\n\t",$head_elems)."\n";
		$out .= "</head>\n";

		$out .= "<body>\n";

		$out .= '<div id="loading" style="display:none">Loading&#8230;</div>';

		$out .= '<div id="frames">';
		$out .= '<div id="content_window" class="borderbox">';

		$out .= '<h1 id="real_title">';
		if ($static_icon != '') $out .= '<img src="'.htmlentities($static_icon).'" width="48" height="48" alt=""> ';
		$out .= htmlentities($page_title_2).'</h1>';
		$out .= '<div id="real_content">'.$static_content.'</div>';
		if ((!isset($_SERVER['REQUEST_METHOD'])) || ($_SERVER['REQUEST_METHOD'] == 'GET')) {
			$out .= '<br><p><img src="img/share.png" width="15" height="15" alt="'._L('Share').'"> <a href="'
			//	.htmlentities(OIDplus::canonicalUrl($static_node_id))
				.htmlentities('?goto='.$static_node_id)
				.'" id="static_link" class="gray_footer_font">'._L('View repository page of entry').': '.htmlentities($static_node_id).'</a>';
			$out .= '</p>';
		}
		$out .= '<br>';

		$out .= '</div>';

		$out .= '<div id="system_title_bar">';

		$out .= '<div id="system_title_menu" onclick="mobileNavButtonClick(this)" onmouseenter="mobileNavButtonHover(this)" onmouseleave="mobileNavButtonHover(this)">';
		$out .= '	<div id="bar1"></div>';
		$out .= '	<div id="bar2"></div>';
		$out .= '	<div id="bar3"></div>';
		$out .= '</div>';

		$out .= '<div id="system_title_text">';
		$out .= '	<a '.OIDplus::gui()->link('oidplus:system').' id="system_title_a">';
		$out .= '		<span id="system_title_logo"></span>';
		$out .= '		<span id="system_title_1">'.htmlentities(OIDplus::getEditionInfo()['vendor'].' OIDplus 2.0').'</span><br>';
		$out .= '		<span id="system_title_2">'.htmlentities(OIDplus::config()->getValue('system_title')).'</span>';
		$out .= '	</a>';
		$out .= '</div>';

		$out .= '</div>';
 
		$out .= OIDplus::gui()->getLanguageBox($static_node_id, true);

		$out .= '<div id="gotobox">';
		$out .= '<input type="text" name="goto" id="gotoedit" value="'.$static_node_id.'">';
		$out .= '<input type="button" value="'._L('Go').'" onclick="gotoButtonClicked()" id="gotobutton">';
		$out .= '</div>';

	
		$out .= '<div id="oidtree" class="borderbox">';
		//$out .= '<noscript>';
		//$out .= '<p><b>'._L('Please enable JavaScript to use all features').'</b></p>';
		//$out .= '</noscript>';
		$out .= OIDplus::menuUtils()->nonjs_menu();
		$out .= '</div>';
/*	*/
		
		
		$out .= '</div>';

		$out .= "\n</body>\n";
		$out .= "</html>\n";

		# ---

		$plugins = OIDplus::getAllPlugins();
		foreach ($plugins as $plugin) {
         	$plugin->htmlPostprocess($out);
		}

		return $out;
	}

				   
				   
 	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusException
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
	
		
		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}
		/*
		 $json[] = array(
		 	'id' => 'oidplus:home',
			'icon' => $tree_icon,
			 'a_attr'=>[
				 'href'=>OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE),				 
			 ],
			'text' => _L('Home'), 
		);		
		

	  	$json[] = array(
			'id' => 'oidplus:system',
			//'icon' => $tree_icon,
			'text' => _L('Registry'), 
		);	
		
		*/
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;



		$json[] = array(
			'id' => self::PAGE_ID_COMPOSER,
			//'icon' => $tree_icon,
			'text' => _L('Composer Plugins'), 
		);
		
		
		$json[] = array(
			'id' => self::PAGE_ID_WEBFAT,
			//'icon' => $tree_icon,
			'text' => _L('Webfan Webfat Setup'),
			//'href'=>$this->getWebfatSetupLink(),
		);

		$json[] = array(
			'id' => self::PAGE_ID_BRIDGE,
			//'icon' => $tree_icon,
			'text' => _L('Webfan IO4 Bridge'), 
		);

		return true;
	}
	 public function publicSitemap(&$out) { 
		//$out[] = OIDplus::getSystemUrl().'?goto='.urlencode('com.frdlweb.freeweid'); 
	//	 $out[] = OIDplus::getSystemUrl().'?goto='.urlencode('oidplus:system'); 
	 }

 }//class	
}//plugin ns

