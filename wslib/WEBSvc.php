<?php

require_once(WS_ABSWSPATHLIB.'MemCachedPools.php');
require_once(WS_ABSWSPATHLIB.'PHPExt.php');
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
		include_once(WS_ABSWSPATHLIB.'WS.Lang.'.issetX($_ENV['WS']['language'], 'En').'.php');
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
	
	static public function ws_addasynctask($function, $params = null)
	{
		$ctx = array();
		$ctx['SERVER'] = $_SERVER;
		$_ENV['WS']['AsyncTask'][] =  array($function, serialize($params), serialize($ctx)); 
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
				self::SetError($Res, new WS_Exception(211, '', '', ''));
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
							self::SetError($Res, new WS_Exception(210, '', '', ''));
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
									self::setError($Res, new WS_Exception(201, $Req -> Options -> AsyncRequestId, '', ''));
								else
									self::setError($Res, new WS_Exception(202, $Req -> Options -> AsyncRequestId, '', ''));
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
							self::setError($Res, new WS_Exception(210, '', '', ''));	
							return $Res;
						}
						else
						{
							$Res -> AsyncRequestId = strtoupper(md5(uniqid(rand(), true)));
							$Req -> Options -> AsyncRequestId = $Res -> AsyncRequestId;
							$_ENV['WS']['async_memcachedLB']->Set("ws.async::processing::".$Res -> AsyncRequestId, 'true', 0, 30*60);
							self::ws_addasynctask("WebSVC::GenericCall", array($Req, true, $cparams, $CredentialArray['AclLevel'], $CredentialArray));
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
			self::setError($Res, $Ex);
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
	
	static public function setError(&$Res, $Ex)
	{
		$Res 			= issetX($Res, new ResponseType);
		$Res -> Errors 	= issetX($Res -> Errors, new ResponseErrorArrayContainerType);
		$Res->Ack = 1;

		if($Ex instanceof WS_Exception)
		{
			if($Res -> Errors -> Items == null) { $Res -> Errors -> Items = new ResponseErrorArrayType; }
			foreach($Ex->Errors as $err)
			{
				$error 	= new ResponseErrorType();
				$error 	-> Code = $err[0];
				$error 	-> Message = Language::get($err[0], array($err[1], $err[2], $err[3]) );
				$error 	-> Key   = $err[1];
				$error 	-> Info1 = $err[2];
				$error 	-> Info2 = $err[3];
				$Res 	-> Errors -> Items -> Error[] = $error;
			}		 
		} 
			else 
		{
				$error 	= new ResponseErrorType();
				$error 	-> Code = $Ex->getCode();
				$error 	-> Message = "::". $Ex->getMessage();
				$Res 	-> Errors -> Items = new ResponseErrorArrayType;
				$Res 	-> Errors -> Items ->Error[] = $error;			
		}
		$Res -> Errors -> Count = Count(issetX($Res -> Errors -> Items -> Error, null));
		return $Res;
	}

	static public function getDetailLevel(&$dlarr, $defaultLevel = "MIN" )
	{
		if(isset($dlarr['%_detaillevel_%'])) { return $dlarr['%_detaillevel_%']; }
		return $defaultLevel;
	}
	
	static public function  detailLevelToArray($dlstr, &$dlarr)
	{
		$dlval = array('MAX','MIN','STD','NONE','CNT');
		foreach(explode(',', $dlstr) as $dl)
		{
			$dlv = explode(':', $dl);
			if(count($dlv) == 2)
			{
				$pdl = explode('.', $dlv[0]);
				$pa = &$dlarr;
				foreach($pdl as $sdl)
					$pa = &$pa[trim($sdl)];
				$ldl = explode(';', $dlv[1]);
				if (in_array(strtoupper(trim($ldl[0])), $dlval))
					$pa['%_detaillevel_%'] = strtoupper(trim($ldl[0]));
				if ( (count($ldl) == 3) && (is_numeric(trim($ldl[1]))) && (is_numeric(trim($ldl[2]))))
				{
					$pa['%_limitfrom_%'] = strtoupper(trim($ldl[1]));
					$pa['%_limitcount_%'] = strtoupper(trim($ldl[2]));
				}	
			}
				else
			{
				if (in_array(strtoupper(trim($dl)), $dlval))
					$dlarr['%_detaillevel_%'] = strtoupper(trim($dl));		
			}
		}		
	}
	
	static public function &getSubDetailLevel(&$dlarr, $filter, $currentLevel)
	{
		if(!isset($dlarr[$filter]['%_detaillevel_%'])) 
			$dlarr[$filter]['%_detaillevel_%'] = $currentLevel;
		return $dlarr[$filter];
	}

	static public function detailLeveltoInt($dl)
	{
		switch ($dl) 
		{
			case "MIN":
				return 1;
			break;
			case "STD":
				return 2;
			break;
				case "MAX":
			return 3;
			break;
				default:
			return 0;
		}
	}	
	

	static public function detailLevelDec($dl)
	{
		if($dl=="MAX") { return "STD"; }
		elseif($dl=="STD") { return "MIN"; }
		return "NONE";
	}
	
	static public function clearDetailLevelPageInfo(&$dlarr)
	{
		$dlarr['%_limitfrom_%'] = null;
		$dlarr['%_limitcount_%'] = null;
	}
	
	static public function getDetailLevelPageInfo(&$dlarr, &$offset, &$length)
	{
		$len = 0;
		$start = 0;
		
		if (isset($dlarr['%_limitfrom_%']))
			$start = $dlarr['%_limitfrom_%'];
		
		if (isset($dlarr['%_limitcount_%']))
			$len = $dlarr['%_limitcount_%'];
		
		if( $start < 0) { $start = 0; } 
		if( $len < 0) { $len = 0; } 

		if(( $start == 0) && ( $len == 0)) 
			return false;
		
		$offset = $start;
		$length = $len;
		
		return true;
	}
	
	static public function run($className, $appName)
	{
		$_ENV['WS']['classname'] = $className;
		FastCGI::run('WebSVC', $appName);
	}
}

//////////////////////////////////////////
//										//
// Helpers
//										//
//////////////////////////////////////////

function ws_get($obj, $default)
{	
	if (!(isset($obj))) { return $default; } else { return $obj; }
}
	
function ws_getprop($req, $name, $defaultvalue)
{
	if (!isset($req->{$name})) { return $defaultvalue; };
	return $req->{$name};	
}

function ws_raise_exception($code, $key, $info1, $info2)
{
	$Ex = new WS_Exception;
	$Ex->addError( $code, $key, $info1, $info2 );			
	throw $Ex; 
}

function ws_new($obj, $prop)
{
	if ($obj -> $prop == null)
	{
		$pinfo = $obj -> WSPropertiesInfo();
		$ptype = $pinfo[$prop];
		$obj -> $prop = new $ptype['type'];
	}
}

function ws_dbrowcompareupdate($newval, $curval, &$row)
{
	if(!isset($newval)) { return false; } 
	$row = $newval;
	return true;
}

function ws_clearpageinfo(&$dlarr)
{
	$dlarr['%_limitfrom_%'] = null;
	$dlarr['%_limitcount_%'] = null;
}

function ws_getpageinfo(&$dlarr, &$offset, &$length)
{
	$len = 0;
	$start = 0;
	
	if (isset($dlarr['%_limitfrom_%']))
		$start = $dlarr['%_limitfrom_%'];
	
	if (isset($dlarr['%_limitcount_%']))
		$len = $dlarr['%_limitcount_%'];
	
	if( $start < 0) { $start = 0; } 
	if( $len < 0) { $len = 0; } 

	if(( $start == 0) && ( $len == 0)) 
		return false;
	
	$offset = $start;
	$length = $len;
	
	return true;
}

function ws_paginatearray($container, &$idarr, &$dlarr, $maxpubliccount=5)
{
	
	$len = 0;
	$start = 0;
	$total = count($idarr);
	
	if(!ws_getpageinfo($dlarr, $start, $len))
		return false;
		
	$container -> LimitCount = $len;
	$container -> LimitFrom = $start;	
	$container -> TotalCount = $total;
	
	if( $len == 0 )
		$idarr = array_slice($idarr, $start);
	else			
		$idarr = array_slice($idarr, $start, $len);			
	return true;		
}


?>