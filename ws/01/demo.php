<?php
// XML Format :
//	http://MYIP/ws/?GetTime
// JSON Format :
//	http://MYIP/ws/?GetTime&_out=JSON
// PHP Format :
//	http://MYIP/ws/?GetTime&_out=PHP
// Types PHP Format :
//	http://MYIP/ws/?GetTime&_out=PHPT
// SOAP WSDL :
//	http://MYIP/ws/?wsdl
// Asynchronous call :
//	http://MYIP/ws/?GetTime&Options.Async=True
//	http://MYIP/ws/?GetTime&Options.Async=True&Options.AsyncRequestId=(ASYNCREQUESTID RETURNED BY THE PREVIOUS CALL)
// You may also POST :
//	http://MYIP/ws/?GetTime&_in=JSON&_out=PHP
//	http://MYIP/ws/?GetTime&_in=XML&_out=JSON
//	http://MYIP/ws/?GetTime&_in=PHP&_out=XML
// 	Etc...

date_default_timezone_set("UTC");

define('WS_ABSWSPATH', dirname(__FILE__).'/');
define('WS_ABSWSPATHLIB', WS_ABSWSPATH.'../../wslib/');

require_once(WS_ABSWSPATHLIB.'WEBSvc.php');
require_once(WS_ABSWSPATH.'demoTypes.php');

class WebSVCDemo extends WS_MainClass
{
	
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
	// Explicit SOAP Function Entry Point //
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

    // a very simple web service...
	$Res->DateTime = gmdate("c"); // we return the Date
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

