<?php
//call http://MYIP/ws/?GetTime&DetailLevel=MAX,UserIP:STD
//call http://MYIP/ws/?GetTime&DetailLevel=MAX,UserIP:STD,DummyArray:STD;1;4

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

function WSGetTime($Req, $Res, $FInfo, $AclLevel)
{   
    // a very simple web service...

	//always returned
	$Res->DateTime = gmdate("c"); // we return the Date
	
	//we get the default DetailLevel for this call (ex DetailLevel=MAX), if not present STD will be used.
	ws_detailLevelToArray($Req->DetailLevel, $dlarr);
	$mdl =  ws_getDetailLevel($dlarr, 'STD');

	// we get the DetailLevel for the property "UserIP". If not present NONE will be used.
	$sdl=ws_getSubDetailLevel($dlarr, 'UserIP', 'NONE');
	$dl = ws_getDetailLevel($sdl, 'NONE');
	if((ws_detailLevelToInt($mdl)==3) || (ws_detailLevelToInt($dl)>=2))
	{
		$Res->UserIP = $_SERVER['UserIp']; // The User IP
		if(!$_SERVER['IsPrivateIp'])
			$Res->UserIPCountry = $_SERVER['UserIpShortCountry']; // The User IP Country Code
	}
	
	$sdl = ws_getSubDetailLevel($dlarr, 'DummyArray', 'NONE');
	$dl = ws_getDetailLevel($sdl, 'NONE');
	if((ws_detailLevelToInt($mdl)==3) || (ws_detailLevelToInt($dl)>=2))
	{
		ws_getDetailLevelPageInfo($sdl, $offset, $length);
		if($length+$offset > 9) { $length = 9-$offset; } // Sanity Check

		$Res->DummyArray = new IntArrayContainerType;
		$Res->DummyArray->Items = new IntArrayType;
		$Res->DummyArray->LimitFrom = $offset; 
		$Res->DummyArray->LimitCount = $length;
		$Res->DummyArray->TotalCount = 8;
		
		for($i=($offset);$i<$offset+$length;$i++)
			$Res->DummyArray->Items->Int[]=$i;
		
		$Res->DummyArray->Count = count($Res->DummyArray->Items->Int);
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

