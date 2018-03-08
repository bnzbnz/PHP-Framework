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
	public $DateTimeSP;
	public $DateTimePV;
	public $CallType;
	
	public static function WS_Properties()
	{
		return 
			array(
				"DateTime"				=> array
				(
					"type"				=> "datetime",
					"acllevel"			=> 0  // Public
				),
				"DateTimeSP"			=> array
				(
					"type"				=> "datetime",
					"acllevel"			=> 100  // Service Provider Only
				),
				"DateTimePV"			=> array
				(
					"type"				=> "datetime",
					"acllevel"			=> 300  // Private Only
				),
				"CallType"				=> array
				(
					"type"				=> "string",
					"acllevel"			=> 0
				)			
			);
	} 	
}


?>
