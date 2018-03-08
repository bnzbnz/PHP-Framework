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

class GetTimeResponseType extends ResponseType
{	
	public $DateTime;
	public $UserIP;
	public $UserIPCountry;
	public $DummyArray;	
	
	public static function WS_Properties()
	{
		return 
			array(
				"DateTime"				=> array
				(
					"type"				=> "datetime",
					"acl"				=> 0
				),
				"UserIP"				=> array
				(
					"type" 				=> "string",
					"ipv4"				=> true,
					"acl"				=> 0
				),
				"UserIPCountry"			=> array
				(
					"type" 				=> "string",
					"acl"				=> 0
				),				"DummyArray"            => array
				(
					"type"         	 	=> "object",
					"class"         	=> "IntArrayContainerType",
					"acl"           	=> 0  
				),
			);
	} 	
}


?>
