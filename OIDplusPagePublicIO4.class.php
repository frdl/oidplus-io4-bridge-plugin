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

namespace Frdlweb\OIDplus{

use ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2;
use ViaThinkSoft\OIDplus\OIDplus;
use ViaThinkSoft\OIDplus\OIDplusObject;
use ViaThinkSoft\OIDplus\OIDplusException;
use ViaThinkSoft\OIDplus\OIDplusPagePluginPublic;
use ViaThinkSoft\OIDplus\OIDplusNotification;
use ViaThinkSoft\OIDplus\OIDplusPagePluginAdmin;
 
use ViaThinkSoft\OIDplus\OIDplusGui;
use ViaThinkSoft\OIDplus\OIDplusPagePublicAttachments;


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

// phpcs:disable PSR1.Files.SideEffects
\defined('INSIDE_OIDPLUS') or die;
// phpcs:enable PSR1.Files.SideEffects

 


class OIDplusPagePublicIO4 extends OIDplusPagePluginAdmin //OIDplusPagePluginPublic // implements RequestHandlerInterface
	implements  //INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1, /* oobeEntry, oobeRequested */
	           \ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_4,  //Ra+Whois Attributes
	           \ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2, /* modifyContent */
	           \ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8 , /* getNotifications */
	        \ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9/*  restApi* */
	 //
				   /*   \ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_7 getAlternativesForQuery() */
{

   	protected $AppLauncher = null;		
    protected $_containerDeclared = false;		
	protected $StubRunner = null;		
	
	
	protected $schemaCacheDir;

	/**
	 * @var int
	 */
	protected $schemaCacheExpires;
	public function __construct() { 
		$this->schemaCacheDir = OIDplus::baseConfig()->getValue('SCHEMA_CACHE_DIRECTORY', OIDplus::localpath().'userdata/cache/' );
		$this->schemaCacheExpires = OIDplus::baseConfig()->getValue('SCHEMA_CACHE_EXPIRES', 60 * 60 );
	}				   

				   
				   
	
	public function modifyContent($id, &$title, &$icon, &$text) {
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
		$text = $content.$text;


		$text = str_replace($_SERVER['DOCUMENT_ROOT'], '***', $text);

		
		$this->modifyContent_schema( $id, $title, $icon, $text);
		$this->modifyContent_attributes( $id, $title, $icon, $text);
		$this->modifyContent_pki( $id, $title, $icon, $text);
		$this->modifyContent_log( $id, $title, $icon, $text);
	}
	
	


	public function restApiCall(string $requestMethod, string $endpoint, array $json_in) {
		 
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
				 if (!$obj->userHasReadRights() && $obj->isConfidental()){    		
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
					   

   public function gui(string $id, array &$out, bool &$handled) {
		$parts = explode('$',$id,2);
		$id = $parts[0];
		$ra_email = $parts[1] ?? null/*no filter*/;

		if ($id == 'io4:bridge') {
			$handled = true;

			$out['title'] = _L('IO4 Bridge');
		
		}
	}				   
 

	public function init($html = true) {
       //  $app = $this->getApp();
		 //  $this->getWebfat(true, false); 	   
	}
				   
	public function getWebfat(bool $load = false, bool $serveRequest = false) {
	 
	 if(null === $this->StubRunner){
		$webfatFile =OIDplus::localpath().'webfat.php';
		 require_once __DIR__.\DIRECTORY_SEPARATOR.'autoloader.php';
		$this->StubRunner = (new \IO4\Webfat)->getWebfat($webfatFile, $load, $serveRequest);

	 }
		
		return $this->StubRunner;
	} 
				   
				   

				   
				   
				   
	public function loadWebApp(StubHelperInterface | WebAppInterface | StubRunnerInterface $payload,
							   bool $serveRequest = false) : WebAppInterface {
		if($payload instanceof WebAppInterface){
			$AppLauncher =$payload;
		}elseif($payload instanceof StubRunnerInterface){
			$AppLauncher = new \Webfan\AppLauncherWebfatInstaller($payload); 
		}elseif($payload instanceof StubHelperInterface){
			$AppLauncher = new \Webfan\AppLauncherWebfatInstaller($payload->getRunner()); 
		}else{
			throw new InvalidArgumentException(sprintf('Invalid parameter in %s payload of %s given but %s expected',
													  __METHOD__,
													  is_object($payload) && !is_null($paylod) ? \get_class($payload) : gettype($payload),
													  'one of StubHelperInterface | WebAppInterface | StubRunnerInterface'));
		}
	 

    if(true === $serveRequest && \method_exists($AppLauncher, 'launch')){
	   $AppLauncher->launch();
	}elseif(true === $serveRequest && !$AppLauncher->KernelFunctions()->isCLI() ){
		   $response = $AppLauncher->handle($AppLauncher->getContainer()->get('request'));
		   if(is_object($response) && $response instanceof \Psr\Http\Message\ResponseInterface){ 
			   (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($response);
		   }
	   }elseif(true === $serveRequest && $AppLauncher->KernelFunctions()->isCLI() ){
		 return $AppLauncher->handleCliRequest();
	   }elseif(true === $serveRequest){
	     throw new \Exception('Could not handle request ('.\PHP_SAPI.')');	
       }	
		return $AppLauncher;
	}		   
				   
 	public function getApp() : WebAppInterface {
		if(null === $this->AppLauncher){			
			$this->AppLauncher = $this->loadWebApp( $this->getWebfat(true, false) );
	        $this->AppLauncher->boot();
            $this->l()->withWebfanWebfatDefaultSettings();
		}
		return $this->AppLauncher;
	}
    public function l()  : ClassLoaderInterface  {
			return $this->getApp()->getContainer()->get('app.runtime.autoloader.remote');
	}		
    public function c()  : ContainerInterface  {
			return $this->getApp()->getContainer();
	}				   
 	public function getContainer() : ContainerInterface {
 
	    	if( ! $this->_containerDeclared ){	
				$this->_containerDeclared=true;

		
				$this->c()->factory('router.builder.main.decorated', function( ContainerInterface $container, $router = null ) {
					$router->group('/', function (\League\Route\RouteGroup $route) { 
				
						$route->map('GET', '/test/{path}', 
							 function(  \Psr\Http\Message\ServerRequestInterface $request,
									  array $args
									//  ,\ActivityPub\ActivityPub $activitypub
									  ,  ContainerInterface $container
									 ){
								
								 
								  $contents = '';
								  $contents.="Tester::test('hallo')";
								  $response = new Response(200);            
								  $response =  $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor($contents));
								 //$response =$activitypub->handle($request);
								 return $response;						
							 })
					->setName('oidplus.test.get');	
						
						
					$route->map('GET', '/@{path}', 
							 function(  \Psr\Http\Message\ServerRequestInterface $request,
									  array $args
									//  ,\ActivityPub\ActivityPub $activitypub
									 ){
								  $contents = '';
								  $contents.='ToDo admin...';
								  $response = new Response(200);            
								  $response =  $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor($contents));
								 //$response =$activitypub->handle($request);
								 return $response;						
							 })
					->setName('oidplus.federation.wildcardpath.get');	
					
					 
					
				$route->map('GET', '/download/{file}/{namespace}/{object}', 
							 function(  \Psr\Http\Message\ServerRequestInterface $request,
									  array $args
									//  ,\ActivityPub\ActivityPub $activitypub
									 ){
								 
	
	if (OIDplus::baseConfig()->getValue('DISABLE_PLUGIN_ViaThinkSoft\OIDplus\OIDplusPagePublicAttachments', false)) {
		throw new OIDplusException(_L('This plugin (Attachments) was disabled by the system administrator but is recommended by IO4!'));
	}							 
 
          $id = str_replace('~~~', '/', urldecode($args['namespace']).':'.urldecode($args['object']));
								 		
		  $obj = OIDplusObject::findFitting($id);
				
			if (!$obj) {
				$obj = OIDplusObject::parse($id);
			}
			/*	*/
				
	    	if (!$obj) {
               // http_response_code(404);
				$e = new OIDplusException(_L('The object does not exist'));
				return OIDplusPagePublicIO4::out_html($e->getMessage(), 404);	
			}
try {
	 
	$filename = $args['file'];
	if (strpos($filename, '/') !== false) throw new OIDplusException(_L('Illegal file name'));
	if (strpos($filename, '\\') !== false) throw new OIDplusException(_L('Illegal file name'));
	if (strpos($filename, '..') !== false) throw new OIDplusException(_L('Illegal file name'));
	if (strpos($filename, chr(0)) !== false) throw new OIDplusException(_L('Illegal file name'));

 

	$uploaddir = OIDplusPagePublicAttachments::getUploadDir($id);
	$local_file = $uploaddir.'/'.$filename;

	if (!file_exists($local_file)) {
		http_response_code(404);
		throw new OIDplusException(_L('The file does not exist'));
	}
	
     
	\VtsBrowserDownload::output_file($local_file, '', 1);
} catch (\Exception $e) {
	$htmlmsg = $e instanceof OIDplusException ? $e->getHtmlMessage() : htmlentities($e->getMessage());
	echo '<h1>Download-'._L('Error').'</h1><p>'.$htmlmsg.'<p>';
}
								exit;						
							 })
					->setName('download.file.get');	
						
						
						
				})
				//->middleware(new MyWaynieeMiddleware)
				;	
					
					return $router;
				 });
				
							
				
			}//$this->_containerDeclared
		
		
		return $this->c();
	}
				   
				 
   
 	public function handle(ServerRequestInterface $request) : ResponseInterface
	{		
	   $app = $this->getApp();
	   $app->boot();
 
			$origin = $request->getHeader('Origin');
 

	if(  $this->getContainer()->has('router') ){	
		
		 
		$path = $request->getUri()->getPath();//$this->getContainer()->get('request')->getUri()->getPath();
		$method =$request->getMethod();//$this->getContainer()->get('request')->getMethod();
		
		$homeRoute = 'home.'.strtolower($method);
		if(!$app->hasRoute($homeRoute) && $method !== 'GET'){
			$homeRoute = 'home.post';
		}
		
		 
		if('/'===$path && $path !== $this->getContainer()->get('params.webroot.uri') && $app->hasRoute($homeRoute)  ){
			$response = $this->getContainer()->get('response');
			$response =  $response->withHeader('Location', $app->getRoute($homeRoute,  []) );	
		    $response = $response->withStatus(302);
			return $response;
		}//  / = $path
		
		
		try{
		  $router = $this->getContainer()->get('router');
		  $response = $router->dispatch($request);
		   $response = $response->withStatus(200);
		}catch(\League\Route\Http\Exception\NotFoundException $e){
		//	die($e->getMessage());
		     $response = new Response(404);
          //   $response =  $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor( $this->_patch_index( '404.html') ));		
			$response =  $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor( 
				//$this->_patch_index( 'template.404.php')
				'Not found: <br />'
				. $method.'<br />'
				 .$path.'<br />'
				. $response->getStatusCode() .'<br />'
				. ((string) $response->getBody()).'<br />'
				. ($app->hasRoute('oidplus.federation.wildcardpath.get')
				  ? $app->getRoute('oidplus.federation.wildcardpath.get', [
					    'path' => '2.999',
					  ]) 
			//	   .(print_r((new \Webfan\Webfat\App\KernelFunctions)->getNetRequest(),true))
				  : ' - Not registered: oidplus.federation.wildcardpath.get')
			));		
		   $response = $response->withStatus(404);
		}catch(\Exception $e){
		     $response = new Response(500);
             $response =  $response->withBody(
				 \GuzzleHttp\Psr7\Utils::streamFor( '<h1>Request-Error</h1>['.$method.' '.$path.']<error>'. $e->getMessage().'</error>')
			 );				
		   $response = $response->withStatus(500);
		}
		
			if(is_string($response)){
	           $response2 = new Response(200);
               $response =  $response2->withBody(\GuzzleHttp\Psr7\Utils::streamFor($response));			
			}
	}else{//$this->hasContainer()
		     $response = new Response(404);
             $response =  $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor( 
				 '<h1>Not found</h1>'																				
				 .'There is no container available which has a router!'
			     .' - You should continue the installation!'										   
			 ));			
		   $response = $response->withStatus(404);
	}
		
	    if($origin){
			$response =  $response->withHeader('Access-Control-Allow-Origin', $origin);	
		}else{
           $response = $response->withHeader('Access-Control-Allow-Origin', '*');	
		}

			
		$ConentType = $app->getResponseHeader('Content-Type', $response);
		if(false === $ConentType || 'text/html' === $ConentType){
		  $contents = (string) $response->getBody();
	//	  $contents = $this->patch_ob_handler($this->Document->compile($contents));
		  $contents = $app->Document->compile($contents);
		  $response =  $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor($contents));
		}	
		
		
		return $response;
	}			   
				   
				   

	public function handleFallbackRoutes($rel_url){
	
		return false;
	}
				   
				   
    public static function out_html(string $html, ?int $code = 200, $callback = null, ?array $templateVars = []){			
		$contents = '';							
		$contents.=\is_callable($callback) ? $callback($html, $templateVars) : $html;					
		$response = new Response($code);			
		$response =  $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor($contents));	
		return $response;
	}
								   

    public static function handleNext( $next, ?bool $skip404 = true ) {
				if(is_bool($next)){
					return $next;
				}elseif(is_string($next)){
					$next = static::out_html($next, 200);
					(new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($next);								    
					exit;
				}elseif(!is_null($next) && is_object($next) && $next instanceof \Psr\Http\Message\ResponseInterface){ 			   
					  switch($next->getStatusCode()){
						  case 404 :
							  if(!$skip404){
								  (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($next);	
							    exit;
							  }else{
								  return false;
							  }
							  break;
							 	 
						  case 302 <= $next->getStatusCode() :
							   (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($next);	
							    exit;
							  break;
							  
							  default :
							    (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit($next);	
							    exit;
							  break;
					  }
				 }elseif(!is_null($next) && is_array($next) || is_object($next) ){								
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

		if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER["REQUEST_METHOD"])) return false;
		$rel_url = false;
		$rel_url_original =substr($_SERVER['REQUEST_URI'], strlen(OIDplus::webpath(null, OIDplus::PATH_RELATIVE_TO_ROOT)));
		$requestMethod = $_SERVER["REQUEST_METHOD"];
        $next = false;
			
		 $args = [$rel_url];
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
	
	
	public function getNotifications(string $user=null): array {
		$notifications = array();
//'.OIDplus::gui()->link($row['id']).'
		$notifications[] = 
			new OIDplusNotification('INFO', _L('Running <a href="%1">%2</a>', 
											  // '<a href="https://registry.frdl.de/?goto=oid%3A1.3.6.1.4.1.37553.8.1.8.8.53354196964">'
											     //.htmlentities($row['id'])
											  // '<a href="%1">%2</a>', 
											   OIDplus::gui()->link('oid:1.3.6.1.4.1.37476.9000.108.19361.24196'),
											  htmlentities( 'OIDplus IO4 Bridge-Plugin' )
											  )
								   );
		return $notifications;
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
 
		 $url = sprintf($apiUrltemplateGetSchema, urlencode(\WeidOidConverter::oid2weid($id)));
			 
			 
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
	   
				   
				   
				   
	public function whoisObjectAttributes(string $id, array &$out) {
		
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

	
	public function whoisRaAttributes(string $email = null, array &$out) {
		
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
	
	

	


}
}//plugin ns

