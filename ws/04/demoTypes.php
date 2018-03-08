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
	public $IsMIN;
	public $IsSTD;
	public $IsMAX;
	
	public static function WS_Properties()
	{
		return 
			array(
				"DateTime"				=> array
				(
					"type"				=> "datetime",
					"acllevel"			=> 0
				),
				"IsMIN"					=> array
				(
					"type"				=> "boolean",
					"acllevel"			=> 0
				),
				"IsSTD"					=> array
				(
					"type"				=> "boolean",
					"acllevel"			=> 0
				),
				"IsMAX"					=> array
				(
					"type"				=> "boolean",
					"acllevel"			=> 0
				)				
			);
	} 	
}


?>
