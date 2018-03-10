<?php

require_once(WS_ABSWSPATHLIB.'MemCachedPools.php');
require_once(WS_ABSWSPATHLIB.'PHPExt.php');
require_once(WS_ABSWSPATHLIB.'WSExt.php');
require_once(WS_ABSWSPATHLIB.'FastCGI.php');
require_once(WS_ABSWSPATHLIB.'WSManager.php'); 
require_once(WS_ABSWSPATHLIB.'WSTypes.php'); 

class WEBSvc
{
	
	static public function WSManager_GetWSDL($Key)
	{
		$path = issetX($_ENV['WS']['wsdl_store'], '/tmp/');
		$md5 = md5($Key);
		$fname = $path.$md5.'-gzip-'.$_ENV['DAEMON']['daemonid'].'.wsdl';
		if(file_exists($fname) && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
		{
			if( issetX($_SERVER['HTTP_IF_NONE_MATCH'], null) ==  $md5)
			{
				$_REQUEST['Response']['Headers'] = array();
				$_REQUEST['Response']['Headers']['ETag'] = $md5;
				$_REQUEST['Response']['StatusCode'] = '304 Not Modified';
				return true;
			}
			$_REQUEST['Response']['Headers'] = array();
	 		$_REQUEST['Response']['Headers']['ETag'] = $md5;
	 		$_REQUEST['Response']['Headers']['Content-Encoding'] ='gzip';
	 		$_REQUEST['Response']['Headers']['content-type']='text/xml; charset: utf-8';
	 		$_REQUEST['Response']['Headers']['X-LIGHTTPD-send-file']=$fname;
	 		return true;
		} 
		$fname = $path.$md5.'-'.$_ENV['DAEMON']['daemonid'].'.wsdl';
		if(file_exists($fname)) 
		{
			if( issetX($_SERVER['HTTP_IF_NONE_MATCH'], null) ==  $md5)
			{
				$_REQUEST['Response']['Headers'] = array();
				$_REQUEST['Response']['Headers']['ETag'] = $md5;
				$_REQUEST['Response']['StatusCode'] = '304 Not Modified';
				return true;
			}
			$_REQUEST['Response']['Headers'] = array();
	 		$_REQUEST['Response']['Headers']['ETag'] = $md5;
	 		$_REQUEST['Response']['Headers']['content-type']='text/xml; charset: utf-8';
	 		$_REQUEST['Response']['Headers']['X-LIGHTTPD-send-file']=$fname;		
	 		return true;
		} 
		return false;
	}
	
	static public function WSManager_SetWSDL($Key, &$Wsdl)
	{
		
		$path = issetX($_ENV['WS']['wsdl_store'], '/tmp/');
		$md5 = md5($Key);
		$fname = $path.$md5.'-'.$_ENV['DAEMON']['daemonid'].'.wsdl';
		$ftempname = $path.uuidX().'-'.$_ENV['DAEMON']['daemonid'].'.wsdl';
			if(!file_exists($fname))
			{
				$file = fopen ($ftempname, "w");
				fwrite($file, $Wsdl); 
				fclose ($file);
				rename($ftempname, $fname); // atomic
			}
			$fname = $path.$md5.'-gzip-'.$_ENV['DAEMON']['daemonid'].'.wsdl';
			$ftempname = $path.uuidX().'-gzip-'.$_ENV['DAEMON']['daemonid'].'.wsdl';
			if(!file_exists($fname))
			{
				$file = fopen ($ftempname, "a");
				fwrite($file, gzencode($Wsdl, 6, FORCE_GZIP));
				fclose ($file);
				rename($ftempname, $fname); // atomic
			}
		return true; 
	}
	
	static public function daemonOnParentInitializing(&$startinguserinfo, &$ctxuserinfo)
	{
		if(($_ENV['WS']['async_request']) && (isset($_ENV['WS']['async_memcached'])))
		{
			$tmpArray = array();
			foreach($_ENV['WS']['async_memcached'] as $srv)
				array_push($tmpArray, array('host'=>$srv['host'],'port'=>$srv['port'],'compress'=>$srv['compress']));
			$_ENV['WS']['async_memcachedLB'] = new MemCachedPoolsLB( $tmpArray, true );			
		}
		
		$path = issetX($_ENV['WS']['wsdl_store'], '/tmp/');
		foreach(glob($path.'*-'.$_ENV['DAEMON']['daemonid'].'.wsdl') as $file) { unlink($file); }
		include_once(WS_ABSWSPATHLIB.'WSLang.'.issetX($_ENV['WS']['language'], 'En').'.php');
		ob_implicit_flush(false);
		WS_Manager::Init(
			$_ENV['WS']['classname'],
			'urn:apis:components',
			'WebSVC::WSManager_GetWSDL',
			'WebSVC::WSManager_SetWSDL'		
		);
		return call_user_funcX($_ENV['WS']['classname'].'::daemonOnParentInitializing', array(&$startinguserinfo, &$ctxuserinfo), true);
	}
	
	static public function childOnParentStarted(&$parent)
	{
		return call_user_funcX($_ENV['WS']['classname'].'::childOnParentStarted', array(&$parent), true);
	}
	
	static public function childOnParentExiting(&$parent)
	{
		return call_user_funcX($_ENV['WS']['classname'].'::childOnParentExiting', array(&$parent), true);
	}
		
	static public function fastCGIOnChildStarted(&$fastcgi)
	{
		return call_user_funcX($_ENV['WS']['classname'].'::fastCGIOnChildStarted', array(&$fastcgi), true);
	}
	
	static public function fastCGIOnChildExiting(&$fastcgi)
	{
		return call_user_funcX($_ENV['WS']['classname'].'::fastCGIOnChildExiting', array(&$fastcgi), true);

	}
	
	static public function fastCGIOnHeadersReceived(&$fcgiheaders)
	{
		return call_user_funcX($_ENV['WS']['classname'].'::fastCGIOnHeadersReceived', array(&$fcgiheaders), true);

	}

	static public function fastCGIOnParamsReceived(&$fcgiparams)
	{
		return call_user_funcX($_ENV['WS']['classname'].'::fastCGIOnParams', array(&$fcgiparams), true);

	}
	
	static public function fastCGIOnCheckUsage() // Socket ms to wait
	{
		return call_user_funcX($_ENV['WS']['classname'].'::fastCGIOnCheckUsage', array(), true);
	}
	
	static public function fastCGIOnIdle()
	{
		// Process async. tasks.
		$clb = &$_ENV['WS']['AsyncTask'];;
		if(count($clb)>0)
		{
			$funcdef = array_shift($clb);
			$function = $funcdef[0];
			$params = unserialize($funcdef[1]);
			$ctx = unserialize($funcdef[2]);
			$_SERVER  = &$ctx['SERVER'];		
			call_user_funcX($function, $params);	
			unset($_SERVER);
		}
		return call_user_funcX($_ENV['WS']['classname'].'::webSvcOnIdle', array(), $timems);
	}
	
	static public function childRun()
	{	
		$compatlevel = WS_ALL;
		call_user_funcX($_ENV['WS']['classname'].'::webSvcOnGetCompatibilityLevel', array(&$compatlevel));
		call_user_funcX($_ENV['WS']['classname'].'::childRun', array(), true);
		$_SERVER['UserIp'] = getUserIpX($_SERVER['IsPrivateIp']);
		$_SERVER['UserIpShortCountry']='**';
		$_SERVER['UserIpLongCountry']='';
		if((!$_SERVER['IsPrivateIp']) && (extension_loaded('geoip')))
		{			
			$_SERVER['UserIpShortCountry'] = geoip_country_code_by_name($_SERVER['UserIp']);
			if($_SERVER['UserIpShortCountry']=== false) { $_SERVER['UserIpShortCountry']='**'; } 
			$_SERVER['UserIpLongCountry'] = geoip_country_name_by_name($_SERVER['UserIp']);
			if($_SERVER['UserIpLongCountry']=== false) { $_SERVER['UserIpLongCountry']='**'; } 
		}
		$Acl = $_SERVER['IsPrivateIp'] ? 300 : 0; 
		call_user_funcX($_ENV['WS']['classname'].'::webSvcGetDefaultACL', array(&$Acl), 0);
		$_REQUEST['Response']['Headers']['Pragma']='no-cache'; // HTTP/1.0
		$_REQUEST['Response']['Headers']['Cache-Control']='no-cache, must-revalidate'; // HTTP/1.1
	 	$_REQUEST['Response']['Headers']['Expires'] = 'Sat, 26 Jul 1997 05:00:00 GMT'; // Date in the past 	
		WS_Manager::Process(URLRootX().$_SERVER['PHP_SELF'], $Acl,  $compatlevel);		
	}
	
	static public function childOnBackgroundRun(&$quit, $initialfork)
	{
		declare(ticks=1);
		return call_user_funcX($_ENV['WS']['classname'].'::childOnBackgroundRun', array(&$quit, $initialfork), 250);
	}
	
	static public function init()
	{
		FastCGI::init();				
	}
	
	static public function GenericCall($Req, $isAsync = false, $cparams = null, $CredentialArray = null)
	{
		$starttime = microtime(true);
		$Res = null;
		try
		{
			if(!$isAsync)
				$cparams = WS_Manager::callParams();
			$finfo = $cparams['_FunctionInfo'];
			$Res = new $finfo['OutType'];
			$Res -> RequestId = isset($Req -> RequestId) ? $Req -> RequestId : null;
			$Validate=true;
			$AclLevel=WS_Manager::$acllevel;
			if(!$isAsync)
			{
				$CredentialArray = array();
				$CredentialArray['AclLevel'] = WS_Manager::$acllevel;
				$CredentialArray['Credential'] = $Req->Credential;			
				$CredentialArray['UserIp'] = $_SERVER['UserIp'];			
				$CredentialArray['IsPrivateIp'] = $_SERVER['IsPrivateIp'];
				$CredentialArray['UserIpShortCountry'] = $_SERVER['UserIpShortCountry'];
				$CredentialArray['UserIpLongCountry'] = $_SERVER['UserIpLongCountry'];
				$Validate=call_user_funcX($_ENV['WS']['classname'].'::webSvcOnValidateCredential', array(false, &$AclLevel, $Req->Credential, &$CredentialArray), true);
				$CredentialArray['AclLevel'] = $AclLevel;
			}
			if (!$isAsync && (!$Validate || ($finfo['AclLevel']>$AclLevel)))
			{
				ws_setError($Res, new WS_Exception(211, '', '', ''));
			}
			else
			{
				if(issetX($_ENV['WS']['validate_request'], true))
				{
					$Req->WS_Validate();
				}
				
				if (!$isAsync && issetX($Req->Options->Async, false))  
				{
					if(!empty($Req->Options->AsyncRequestId))
					{
						if(empty($_ENV['WS']['async_memcachedLB']) || !issetX($_ENV['WS']['async_request'], false))
						{
							ws_setError($Res, new WS_Exception(210, '', '', ''));
						}
						else
						{
							$Res =  $_ENV['WS']['async_memcachedLB']->Get("ws.async::".$Req -> Options -> AsyncRequestId);
							if ($Res == null)
							{
								$Res = new $finfo['OutType'];
								$Res -> RequestId = issetX($Req -> RequestId, null);
								$Processing = $_ENV['WS']['async_memcachedLB']->Get("ws.async::processing::".$Req -> Options -> AsyncRequestId);	
								if ($Processing == null)
									ws_setError($Res, new WS_Exception(201, $Req -> Options -> AsyncRequestId, '', ''));
								else
									ws_setError($Res, new WS_Exception(202, $Req -> Options -> AsyncRequestId, '', ''));
							}	
						}
						$tmp = microtime(true)- $starttime;
						$Res->Duration = (int)round($tmp * 1000); 
						return $Res;
					}
						else
					{
						if(empty($_ENV['WS']['async_memcachedLB']) || !issetX($_ENV['WS']['async_request'], false))
						{
							ws_setError($Res, new WS_Exception(210, '', '', ''));	
							return $Res;
						}
						else
						{
							$Res -> AsyncRequestId = strtoupper(md5(uniqid(rand(), true)));
							$Req -> Options -> AsyncRequestId = $Res -> AsyncRequestId;
							$_ENV['WS']['async_memcachedLB']->Set("ws.async::processing::".$Res -> AsyncRequestId, 'true', 0, 30*60);
							ws_addAsyncTask("WebSVC::GenericCall", array($Req, true, $cparams, $CredentialArray['AclLevel'], $CredentialArray));
						}					
						$tmp = microtime(true) - $starttime;
						$Res->Duration = (int)round($tmp * 1000); 
						$Res -> Ack = 0;	
						return $Res;
					}
				}
				$Res -> Ack = 0;	
				call_user_func($finfo['Function'], $finfo, $CredentialArray, $Req, $Res);
				$tmp = microtime(true) - $starttime;
				$Res->Duration = (int) round($tmp * 1000);
				if(issetX($_ENV['WS']['validate_response'], true))
				{
					$Res->WS_Validate();
				}
			}
		}
		catch(Exception $Ex)
		{			
			ws_setError($Res, $Ex);
		}

		if ($isAsync) 
		{

			$_ENV['WS']['async_memcachedLB']->Set("ws.async::".$Req -> Options -> AsyncRequestId, $Res, 0, 3*60);
			$_ENV['WS']['async_memcachedLB']->Delete("ws.async::processing::".$Req -> Options -> AsyncRequestId);
		}
		else
		{
			return $Res;
		}
	}

	static public function run($className, $appName)
	{
		$_ENV['WS']['classname'] = $className;
		FastCGI::run('WebSVC', $appName);
	}
}

?>