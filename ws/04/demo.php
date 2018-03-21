<?php
// Try
//	http://MYIP/ws/?GetTime&DetailLevel=NONE
//	http://MYIP/ws/?GetTime&DetailLevel=MIN
//	http://MYIP/ws/?GetTime&DetailLevel=STD
//	http://MYIP/ws/?GetTime&DetailLevel=MAX

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
	syslogx(' DetailLevel : '.$Req->DetailLevel);
	
	$dlarr = array();
	ws_detailLevelToArray($Req->DetailLevel, $dlarr);
	$dl = ws_getDetailLevel($dlarr, 'STD');
	switch( ws_detailLeveltoInt($dl) ) {
		case 1 : // MIN
			$Res->IsMIN = true;
		break;
		case 2 : // STD
			$Res->IsSTD = true;
		break;
		case 3 : // MAX
			$Res->IsMAX = true;
		break;
		default: // NONE
		
	}
	
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

