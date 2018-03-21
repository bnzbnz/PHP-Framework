<?php
// Try
//	http://MYIP/ws/?GetTime&DetailLevel=STD,DemoArray:STD;4;0
//	http://MYIP/ws/?GetTime&DetailLevel=STD,DemoArray:STD;2;6
//	http://MYIP/ws/?GetTime&DetailLevel=STD,DemoArray:STD;0;0
//	http://MYIP/ws/?GetTime&DetailLevel=STD,DemoArray:STD;4;0
//	http://MYIP/ws/?GetTime&DetailLevel=STD,DemoArray:STD;20;1

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

	// How to handle arrays with page infos:
	
	$Res->DateTime = gmdate("c"); // we return the Date
	
	ws_detailLevelToArray($Req->DetailLevel, $dlarr);
	$sdl=ws_getSubDetailLevel($dlarr, 'DemoArray', 'NONE');
	$dl = ws_getDetailLevel($sdl, 'NONE');
	if( ws_detailLeveltoInt($dl)>0 )
	{
		//Integer Array, 8 Values 1-8
		ws_getDetailLevelPageInfo($sdl, $offset, $length);
		// Sanity Check :
		if($length==0) { $length=8; }
		if($offset<1) { $offset=1; }
		if($offset>8) { $offset=8; }
		if($length+$offset > 9) { $length = 9-$offset; } 

		$Res->DemoArray = new IntArrayContainerType;
		$Res->DemoArray->Items = new IntArrayType;
		$Res->DemoArray->LimitFrom = $offset; 
		$Res->DemoArray->LimitCount = $length;
		$Res->DemoArray->TotalCount = 8;
		
		for($i=($offset);$i<$offset+$length;$i++)
			$Res->DemoArray->Items->Int[]=$i;
		
		$Res->DemoArray->Count = count($Res->DemoArray->Items->Int);
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

