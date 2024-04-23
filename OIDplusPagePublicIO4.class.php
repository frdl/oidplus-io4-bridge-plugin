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

use ViaThinkSoft\OIDplus\OIDplusPlugin;

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

 

/*  
API:
public function packagist(string $method, array $params = [])
public function package(string $name) : array
*/
class OIDplusPagePublicIO4 extends OIDplusPagePluginAdmin //OIDplusPagePluginPublic // implements RequestHandlerInterface
	implements  //INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_1, /* oobeEntry, oobeRequested */
	           \ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_4,  //Ra+Whois Attributes
	           \ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_2, /* modifyContent */
	           \ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_8 , /* getNotifications */
	        \ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_9/*  restApi* */
	 //
				   /*   \ViaThinkSoft\OIDplus\INTF_OID_1_3_6_1_4_1_37476_2_5_2_3_7 getAlternativesForQuery() */
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

	/**
	 * @var int
	 */
	public function __construct() { 
		$this->packagistCacheDir = OIDplus::baseConfig()->getValue('IO4_PACKAGIST_CACHE_DIRECTORY',
																   OIDplus::localpath().'userdata/cache/' );
		$this->packagistExpires = OIDplus::baseConfig()->getValue('IO4_PACKAGIST_CACHE_EXPIRES', 15 * 60 );
		
		$this->schemaCacheDir = OIDplus::baseConfig()->getValue('SCHEMA_CACHE_DIRECTORY', OIDplus::localpath().'userdata/cache/' );
		$this->schemaCacheExpires = OIDplus::baseConfig()->getValue('SCHEMA_CACHE_EXPIRES', 60 * 60 );		
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
	

   public function gui(string $id, array &$out, bool &$handled) {
		$parts = explode('$',$id,2);
		$id = $parts[0];
		$ra_email = $parts[1] ?? null/*no filter*/;

		   
	   if(isset(self::PAGES[$id]) && is_callable([$this, self::PAGES[$id]])){ 
		   $handled = true;
          $out = \call_user_func_array([$this, self::PAGES[$id]], [$id, $out]);   
	   }
 
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
				   

	/**
	 * @param array $json
	 * @param string|null $ra_email
	 * @param bool $nonjs
	 * @param string $req_goto
	 * @return bool
	 * @throws OIDplusException
	 */
	public function tree(array &$json, string $ra_email=null, bool $nonjs=false, string $req_goto=''): bool {
		if (!OIDplus::authUtils()->isAdminLoggedIn()) return false;

		if (file_exists(__DIR__.'/img/main_icon16.png')) {
			$tree_icon = OIDplus::webpath(__DIR__,OIDplus::PATH_RELATIVE).'img/main_icon16.png';
		} else {
			$tree_icon = null; // default icon (folder)
		}

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

	public function init($html = true) {
       //  $app = $this->getApp();
		 //  $this->getWebfat(true, false); 	   
	}
			
				   
				   
	/*
	public function setStubDownloadUrl(string $url)
    {
        $class = static::getWebfatTraitSingletonClass();
         $class::$_stub_download_url = $url;
    }

    public function getWebfat(
        string $file = null,
        bool $load = true,
        bool $serveRequest = false,
        bool|int $autoupdate = 2592000,
        ?string $download_url = null,
    )
	*/
	public function getWebfat(bool $load = true, bool $serveRequest = false) {

		$webfatFile =$this->getWebfatFile();
	     if(null === $this->StubRunner){
			 if(!is_dir(dirname($webfatFile))){
				mkdir($webfatFile, 0775, true); 
			 }
		 require_once __DIR__.\DIRECTORY_SEPARATOR.'autoloader.php';
			 
			$getter = new ( \IO4\Webfat::getWebfatTraitSingletonClass() );
			 $getter->setStubDownloadUrl(\Frdlweb\OIDplus\OIDplusPagePublicIO4::WebfatDownloadUrl);
		$this->StubRunner = $getter->getWebfat($webfatFile,
														 $load 
														 && OIDplus::baseConfig()->getValue('IO4_ALLOW_AUTOLOAD_FROM_REMOTE', true )
														 , $serveRequest,
														2592000,
														$getter::$_stub_download_url );
	    }
		
		return $this->StubRunner;
	} 
				   
	public function getWebfatFile() {	 
	    // $webfatFile =OIDplus::localpath().'webfan.setup.php';	
	 //	$webfatFile =__DIR__.\DIRECTORY_SEPARATOR.'webfan-website'.\DIRECTORY_SEPARATOR.'webfan.setup.php';	
			$webfatFile = $_SERVER['DOCUMENT_ROOT'].\DIRECTORY_SEPARATOR.'webfan.setup.php';	
		//if(!is_dir(dirname($webfatFile))){
		//  mkdir(dirname($webfatFile), 0775, true);	
	//	}
	     return $webfatFile;
	} 				   

	public function getWebfatSetupLink(){
           return OIDplus::webpath(dirname($this->getWebfatFile()),true).basename($this->getWebfatFile());
	}
				   
			
				   
				   
				   
				   
				   
	//deprecte and rewrite todo!			   
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
				   
	//deprecte and rewrite todo!			   
 	public function getApp() : WebAppInterface {
		if(null === $this->AppLauncher){			
			$this->AppLauncher = $this->loadWebApp( $this->getWebfat(true, false) );
	        $this->AppLauncher->boot();
            $this->l()->withWebfanWebfatDefaultSettings();
		}
		return $this->AppLauncher;
	}
	//deprecte and rewrite todo!			   
    public function l()  : ClassLoaderInterface  {
			return $this->getApp()->getContainer()->get('app.runtime.autoloader.remote');
	}	
	//deprecte and rewrite todo!			   
    public function c()  : ContainerInterface  {
			return $this->getApp()->getContainer();
	}			
	//deprecte and rewrite todo!			   
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
				   
				 
   //deprecte and rewrite todo!
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
 
		 $url = sprintf($apiUrltemplateGetSchema, urlencode(\Frdl\Weid\WeidOidConverter::oid2weid($id)));
			 
			 
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

				   
				   
 

}//class
	
	
	
	
}//plugin ns

