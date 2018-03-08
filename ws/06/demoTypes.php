<?php

// !!! '_' underscore in Names is FORBIDDEN !!!

/******************************************/
/*         REQUESTS / RESPONSES	       		*/
/* extends RequestType / ResponseType */			
/******************************************/


class GetTimeRequestType extends RequestType
{	
	public static function WS_Properties()
	{
		return array();
	} 	
}

class UserInfoType extends WS_Type
{
	public $UserIP;
	public $IsPrivate;
	public $ShortCountryCode;
	public $LongCountryName;
	
	static public function WS_Properties()
	{
		return 
			array
			(
				"UserIP"				=> array
				(
					"type" 				=> "string",
					"acllevel"			=> 0
				),
				"IsPrivate"				=> array
				(
					"type" 				=> "boolean",
					"acllevel"			=> 0
				),
				"ShortCountryCode"				=> array
				(
					"type" 				=> "string",
					"acllevel"			=> 0
				),
				"LongCountryName"				=> array
				(
					"type" 				=> "string",
					"acllevel"			=> 0
				)				
			);
	}
	 	
}

class UserInfoBlockType extends WS_Type
{
	public $Info;
	
	static public function WS_Properties()
	{
		return 
			array
			(
				"Info"					=> array
				(
					"type" 				=> "object",
					"class" 			=> "UserInfoType",
					"acllevel"			=> 0
				)
			);
	}
	 	
}


class GetTimeResponseType extends ResponseType
{	
	public $DateTime;
	public $UserInfo;
	
	public static function WS_Properties()
	{
		return 
			array(
				"DateTime"				=> array
				(
					"type"				=> "datetime",
					"acllevel"			=> 0
				),
				"UserInfo"				=> array
				(
					"type"				=> "object",
					"class"				=> "UserInfoBlockType",
					"acllevel"			=> 0
				)				
			);
	} 	
}

function get_user_wstypes()
{
	return array
	(
		'GetTimeRequestType',					
		'GetTimeResponseType',
		'UserInfoBlockType',
		'UserInfoType'
	);
}

?>
