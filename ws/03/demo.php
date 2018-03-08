<?php
// Demo about Credential management
// Credential are defined as follow :
// 0 / Empty : Public
// 1234 : a Service Provider
// 5678 : Private / internal
// http://MYIP/ws/?wsdl :
// Public Credential
// Only the function GetTime will appear
// Service Provider Credential
// http://MYIP/ws/?wsdl&Credential=1234
// Both GetTime & GetTimeSP will be shown
// Private (root) Credential
// http://MYIP/ws/?wsdl&Credential=5678
// All GetTime functions are present (GetTime, GetTimeSP, GetTimePV)
// Making a call like:
// http://MYIP/ws/?GetTimePV&Credential=1234
// (Calling a private function with Service Provider Credential)
// will result in an error : Invalid Credential
// Working as follow :
// http://MYIP/ws/?GetTime 		// Public : OK
// http://MYIP/ws/?GetTimeSP 	// Error
// http://MYIP/ws/?GetTimePV 	// Error
// http://MYIP/ws/?GetTime&Credential=1234 		// Service Provider : OK
// http://MYIP/ws/?GetTimeSP&Credential=1234 	// Service Provider : OK
// http://MYIP/ws/?GetTimePV&Credential=1234 	// Error
// http://MYIP/ws/?GetTime&Credential=5678 		// Private : OK
// http://MYIP/ws/?GetTimeSP&Credential=5678 	// Private : OK
// http://MYIP/ws/?GetTimePV&Credential=5678 	// Private : OK


date_default_timezone_set("UTC");

define('WS_ABSWSPATH', dirname(__FILE__).'/');
define('WS_ABSWSPATHLIB', WS_ABSWSPATH.'../../wslib/');

require_once(WS_ABSWSPATHLIB.'WEBSvc.php');
require_once(WS_ABSWSPATH.'demoTypes.php');

class WebSVCDemo extends WS_MainClass
{
	
	static public function webSvcOnValidateCredential($IsWsdl, &$AclLevel, $Credential, &$CredentialArray)
	{
		// Usually the AclLevel will be set after checking the Credential in a DB
		
		$AclLevel = 0;
		$CredentialArray['CallType'] = 'Public';
		
		if($Credential=='1234') // A Service Provider
		{
			$AclLevel = 100;
			$CredentialArray['CallType'] = 'Provider';
		}
		elseif($Credential=='5678') // Private
		{
			$AclLevel = 300;	
			$CredentialArray['CallType'] = 'Private';
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
								),							
				'GetTimeSP'	=> 	array(
									'Function'		=>	'WSGetTime', 			// Internal function to call
									'InName'  		=>	'GetTimeRequest',  		// In param name 		
									'InType'  		=>	'GetTimeRequestType',	// In param type, see demoTypes.php 		 		
									'OutName' 		=>	'GetTimeResponse', 		// Out param name 		
									'OutType' 		=>	'GetTimeResponseType',	// Out param type, see demoTypes.php 		 		
									'AclLevel'		=>	100	 					// Service Provider (arbitrary) Function
								),
				'GetTimePV'	=> 	array(
									'Function'		=>	'WSGetTime', 			// Internal function to call
									'InName'  		=>	'GetTimeRequest',  		// In param name 		
									'InType'  		=>	'GetTimeRequestType',	// In param type, see demoTypes.php 		 		
									'OutName' 		=>	'GetTimeResponse', 		// Out param name 		
									'OutType' 		=>	'GetTimeResponseType',	// Out param type, see demoTypes.php 		 		
									'AclLevel'		=>	300	 					// Private Function
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
	static public function GetTimePV($Req) { return WebSVC::GenericCall($Req); }
	static public function GetTimeSP($Req) { return WebSVC::GenericCall($Req); }
}

//////////////////////////////////////////
//										//
// Functions implementation				//
//										//
//////////////////////////////////////////

function WSGetTime($FInfo, $CredentialArray, $Req, $Res)
{   
	$Res->DateTime 		= gmdate("c"); // we return the Date
	$Res->DateTimeSP 	= gmdate("c"); // we return the Date as SP
	$Res->DateTimePV 	= gmdate("c"); // we return the Date as PV
	$Res->CallType 		= $CredentialArray['CallType'];
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

