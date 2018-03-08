<?php
// XML Format :
//call http://MYIP/ws/?GetTime
// JSON Format
//call http://MYIP/ws/?GetTime&_out=JSON
// PHP Format
//call http://MYIP/ws/?GetTime&_out=PHP
// Types PHP Format
//call http://MYIP/ws/?GetTime&_out=PHPT
// SOAP WSDL
//call http://MYIP/ws/?wsdl

date_default_timezone_set("UTC");

define('WS_ABSWSPATH', dirname(__FILE__).'/');
define('WS_ABSWSPATHLIB', WS_ABSWSPATH.'../../wslib/');

require_once(WS_ABSWSPATHLIB.'WEBSvc.php');
require_once(WS_ABSWSPATH.'demoTypes.php');

class WebSVCDemo extends WS_MainClass
{
	// Enable / Disable Logging here;
	// see: tail -f /var/log/syslog
	public static $EnableLogging = true; 
	public static $LogLoopingMsg = false; 

	//////////////////////////////////////////
	//										//
	// Framework Callbacks                  //
	//										//
	//////////////////////////////////////////
	
	static public function daemonOnParentInitializing(&$stguserinfo, &$ctxuserinfo)
	{
		if (self::$EnableLogging)
		{
			syslogX('daemonOnParentInitializing stguserinfo :');
			syslogX(serializeX($stguserinfo));
			syslogX('daemonOnParentInitializing ctxuserinfo :');
			syslogX(serializeX($ctxuserinfo));
		}
		// The daemon is going to switch from the starting user
		// to the context user (ex: from root to www-data)
		// Return is mandatory : true = continue; false = error ("parentInit failed")
		return true;	
	}

	static public function childOnParentStarted(&$parent)
	{
		// The Parent process has started
		if (self::$EnableLogging)
		{
			syslogX('childOnParentStarted :');
			syslogX(serializeX($parent));
		}		
		// no return
	}
	
	static public function childOnParentExiting(&$parent)
	{
		
		// The Parent process is going to exit
		if (self::$EnableLogging)
		{
			syslogX('childOnParentExiting :');
			syslogX(serializeX($parent));
		}
		// no return
		
	}

	static public function childOnBackgroundRun(&$quit, $initialfork)
	{	
		// A unique background child is running here,
		// Useful to manage messages queues.
		// It can be blocking (set quit as needed)
		// or non blocking (set a sleep duration)
		
		declare(ticks=1); // MANDATORY

		// Blocking :
		/*
		while(!$quit)
		{
			try
			{
				if (self::$EnableLogging && self::$LogBkgRun)
				{
					syslogX('childOnBackgroundRun');
				}
				// DO SOMETHING
				msleepX(5000);
			} catch(Exception $ex) {}
		}
		return 0;
		*/
		
		// or
		// Non-Blocking :

		if (self::$EnableLogging && self::$LogLoopingMsg)
		{
			syslogX('childOnBackgroundRun');
		}
		// DO SOMETHING HERE
		
		// Return is mandatory : number of ms to wait before calling this function again
		return 5000; // sleep for 5000 ms (5s)
	}
	
	static public function fastCGIOnHeadersReceived(&$fcgiheaders)
	{
		// FastCGI Headers Received
		if (self::$EnableLogging)
		{
			syslogX('fastCGIOnHeadersReceived :');
			syslogX(serializeX($fcgiheaders));
		}		
	}
	
	static public function fastCGIOnParamsReceived(&$fcgiparams)
	{
		// FastCGI Request Parameters Received
		if (self::$EnableLogging)
		{
			syslogX('fastCGIOnParamsReceived :');
			syslogX(serializeX($fcgiparams));
		}
	}	
		
	static public function fastCGIOnChildStarted(&$fastcgi)
	{
		// A fastCGI child has been started.
		if (self::$EnableLogging)
		{
			syslogX('fastCGIOnChildStarted :');
			syslogX(serializeX($fastcgi));
		}
	}
	
	static public function fastCGIOnChildExiting(&$fastcgi)
	{
		// A fastCGI child is going to exit.
		if (self::$EnableLogging)
		{
			syslogX('fastCGIOnChildExiting :');
			syslogX(serializeX($fastcgi));
		}
	}
	
	static public function webSvcOnIdle()
	{
		// Called when the FastCGI socket has timeout,
		// Giving us a chance to do something.
		if (self::$EnableLogging && self::$LogLoopingMsg)
		{
			syslogX('webSvcOnIdle...');
		}
	}	
					
	static public function webSvcOnGetCompatibilityLevel(&$compatlevel)
	{
		// What format will be supported by the WS Engine :
		// WS_XML, WS_REST, WS_SOAP, WS_JSON, WS_PHP, WS_PHPT, WS_ALL
		// WS_ALL = WS_XML + WS_REST + WS_SOAP + WS_JSON + WS_PHP + WS_PHPT
		
		if (self::$EnableLogging)
		{
			syslogX('webSvcGetCompatibilityLevel :');
			syslogX($compatlevel);
		}
		$compatlevel = WS_ALL;  // Default value (255)
	}	
	static public function webSvcOnValidateCredential($iswsdl, &$acllevel, $credential, &$credentialarray)
	{
		// The default value of acllevel depends of :
		// The value passed as parameter (in case of wsdl request),
		// 300 if it comes from the internal network,
		// 0 : in all other cases.
		// Check and modify if necessary the aclevel against the credential
		// Also fill the $credentialarray array
		// Predefined :
		// $credentialarray['AclLevel'] The current AclLevel (Will be updated on this function's exit)
		// $credentialarray['Credential'] = The current Credential
		// $credentialarray['UserIp'] = The user IP
		// $credentialArray['IsPrivateIP'] = The request comes from the internal network
		// $credentialArray['UserIpShortCountry'] = 2 letters IP Country code
		// $credentialArray['UserIpLongCountry'] = Full IP Country name
		// Anyway you better check the current acl, the credential, and other parameters like $credentialArray['Internal']
		// to assign a value to acllevel.
		
		if (self::$EnableLogging)
		{
			syslogX('webSvcOnValidateCredential :');
			syslogX('iswsdl : '.$iswsdl);
			syslogX('acllevel : '.$acllevel);
			syslogX('credential : '.$credential);
			syslogX('credentialarray : '.serialize($credentialarray));
		}

		return true;
	}
		
	//////////////////////////////////////////////
	//											//
	// MANDATORY : WS TYPES Registration	 	//
	//											//
	//////////////////////////////////////////////
	
	static public function WS_Types()
	{
		return array_merge(get_wstypes(),
			array
				(		
				// Types defined in demoTypes.php
				'GetTimeRequestType',					
				'GetTimeResponseType',				
				)
			);
	}

	//////////////////////////////////////////////
	//											//
	// MANDATORY : WS FUNCTIONS Registration 	//
	//											//
	//////////////////////////////////////////////

	static public function WS_Functions()
	{
		return array_merge(get_wsfunctions(),
			array(
				'GetTime' 	=> 	array(
									'Function'		=>	'WSGetTime', 			// Internal function to call
									'InName'  		=>	'GetTimeRequest',  		// In param name 		
									'InType'  		=>	'GetTimeRequestType',	// In param type, see demoTypes.php 		 		
									'OutName' 		=>	'GetTimeResponse', 		// Out param name 		
									'OutType' 		=>	'GetTimeResponseType',	// Out param type, see demoTypes.php 		 		
									'AclLevel'		=>	0	 					// Public Function
								)							
			)
		);
	}
	
	//////////////////////////////////////////
	//										//
	// Explicit SOAP Function Entry Point   //
	//										//
	//////////////////////////////////////////

	static public function GetTime($Req) { return WebSVC::GenericCall($Req); }
}

//////////////////////////////////////////
//										//
// Functions implementation				//
//										//
//////////////////////////////////////////

function WSGetTime($FInfo, $CredentialArray, $Req, $Res)
{   
	syslogX('FInfo :');
	syslogX(serializeX($FInfo));
	syslogX('CredentialArray :');
	syslogX(serializeX($CredentialArray));
	syslogX('Req :');
	syslogX(serializeX($Req));

    // a very simple web service...
	$Res->DateTime = gmdate("c"); // we return the Date
	
	syslogX('Res :');
	syslogX(serializeX($Res));
}
 
//////////////////////////////////////////
//										//
// Initialization						//
//										//
//////////////////////////////////////////

set_error_handlerX(E_ALL); // Default
WebSVC::init();
WebSVC::run('WebSVCDemo', 'PHP WEB Services Demo');
restore_error_handler();

