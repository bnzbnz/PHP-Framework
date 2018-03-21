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
	public static $LogLoopingMsg = true; 

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

	static public function childBkgOnRun(&$quit, $initialfork)
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
			syslogX('childBkgOnRun');
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
	
	static public function fastCGIOnIdle()
	{
		// Called when the FastCGI socket has timeout,
		// Giving us a chance to do something.
		if (self::$EnableLogging && self::$LogLoopingMsg)
		{
			syslogX('fastCGIOnIdle...');
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
	static public function webSvcOnValidateCredential($Credential, &$AclLevel)
	{
		// Check and modify if necessary the aclevel against the credential
		
		if (self::$EnableLogging)
		{
			syslogX('webSvcOnValidateCredential :');
			syslogX('Credential : '.$Credential);
			syslogX('AclLevel : '.$AclLevel);
		}

		return true; // Returning False will break the request
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

function WSGetTime($Req, $Res, $FInfo, $Credential, $AclLevel)
{   
	syslogX('Function FInfo :');
	syslogX(serializeX($FInfo));
	syslogX('Function Credential :');
	syslogX(serializeX($Credential));
	syslogX('Function AclLevel :');
	syslogX(serializeX($AclLevel));	
	syslogX('Function Req :');
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

