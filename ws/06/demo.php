<?php
// Call :
//	http://MYIP/ws/?GetTime
//	http://MYIP/ws/?GetTime&DetailLevel=STD,UserInfo.Info:STD
//	http://MYIP/ws/?GetTime&DetailLevel=STD,UserInfo.Info:MIN
//	http://MYIP/ws/?GetTime&DetailLevel=STD,UserInfo.Info:MAX

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
		return array_merge(get_wstypes(), get_user_wstypes());
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

function WSGetTime($FInfo, $CArray, $Req, $Res)
{   

	$Res->DateTime = gmdate("c"); // we return the Date
	
	ws_detailLevelToArray($Req->DetailLevel, $dlarr);
	$sdl=ws_getSubDetailLevel($dlarr, 'UserInfo', 'NONE');
	$sdl=ws_getSubDetailLevel($sdl, 'Info', 'NONE');
	$dl = ws_getDetailLevel($sdl, 'NONE');
	$lvl=ws_detailLeveltoInt($dl);
	
	if( $lvl>0 )
	{
		$Res->UserInfo = new UserInfoBlockType;
		$Res->UserInfo->Info = new UserInfoType;
		$Res->UserInfo->Info->UserIP = $CArray['UserIp'];
		if($lvl>=2)
			$Res->UserInfo->Info->IsPrivate = $CArray['IsPrivateIp'];	
		if($lvl>=3)
		{
			$Res->UserInfo->Info->ShortCountryCode = $CArray['UserIpShortCountry'];
			$Res->UserInfo->Info->LongCountryName = $CArray['UserIpLongCountry'];
		}
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

