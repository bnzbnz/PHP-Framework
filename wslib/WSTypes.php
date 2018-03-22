<?php

/********************************************/
/*                ENUM						*/		
/*        extends WS_EnumType 				*/		
/********************************************/

// NONE

/********************************************/
/*                TYPES						*/		
/*           extends WS_Type				*/		
/********************************************/


//RequestOptionsType
class RequestOptionsType extends WS_Type
{
	public $Async;
	public $AsyncRequestId;
	public $UserIP;
	
	static public function WS_Properties()
	{
		return  
			array
			(		  
				"Async"					=> array
				(
					"type" 				=> "boolean",
					"default"			=> false,
					"acllevel"			=> 0
				),
				"AsyncRequestId"		=> array
				(
					"type" 				=> "string",
					"acllevel"			=> 0
				)
				/*
				,
				"UserIP"				=> array
				(
					"type" 				=> "string",
					"notempty"			=> true,
					"ipv4"				=> true,
					'default'			=> '0.0.0.0',
					"acllevel"			=> 0
				)
				*/
			);
	}	
}

class RequestType extends WS_Type
{
	public $Credential;
	public $DetailLevel;
	public $RequestId;
	public $Options;
	
	static public function WS_Properties()
	{
		return  
			array
			(
				"Credentials"		 	=> array
				(
					"type"				=> "string",
					"acllevel"			=> 0
				),
				"RequestId"		 		=> array
				(
					"type" 				=> "int",
					"acllevel"			=> 0
				),
				"DetailLevel"		 	=> array
				(
					"type" 				=> "string",
					"acllevel"			=> 0
				),
				"Options"				=> array
				(
					"type" 				=> "object",
					"class"				=> "RequestOptionsType",
					"acllevel"			=> 0
				)
			);
	} 	
}

class ResponseErrorType extends WS_Type
{
	public $Message;
	public $Code;
	public $Key;
	public $Info1;
	public $Info2;
		
	static public function WS_Properties()
	{
		return 
			array
			(
				"Message"					=> array
				(
					"type" 					=> "string",
					"required"				=> true,
					"acllevel"				=> 0
				),
				"Code"						=> array
				(
					"type" 					=> "int",
					"required"				=> true,
					"acllevel"				=> 0
				),
				"Key"             			=> array
				(
					"type"          		=> 'string',
					"acllevel"           	=> 0
				),
				"Info1"						=> array
				(
					"type" 					=> "string",
					"acllevel"				=> 0
				),
				"Info2" 					=> array
				(
					"type" 					=> "string",
					"acllevel"				=> 0
				)
			);
	} 	
}

class ResponseErrorArrayType extends WS_Type
{
	public $Error;
	
	static public function WS_Properties()
	{
		return 
			array
			(
				"Error"				=> array
				(
					"type" 			=> "array",
					"class" 		=> "ResponseErrorType",
					"acllevel"		=> 0
				)
			);
	}	
}

class ResponseErrorArrayContainerType extends ArrayContainerType
{
	public $Items;

	static public function WS_Properties()
	{
		return 
			array
			(
				"Items"					=> array
				(
					"type" 				=> "object",
					"class"				=> "ResponseErrorArrayType",
					"acllevel"			=> 0
				)
			);
	} 
}

class ResponseType extends WS_Type
{
	public $Ack;
	public $RequestId;
	public $AsyncRequestId;
	public $Duration;
	public $Errors;
	
	static public function WS_Properties()
	{
		return 
			array
			(
				"Ack"					=> array
				(
					"type" 				=> "int",
					"values"			=> array(0,1),
					"comment"			=> "0 = Success, 1 = Failure",
					"acllevel"			=> 0	
				),
				"RequestId"				=> array
				(
					"type" 				=> "string",
					"maxlength"			=> 32,
					"acllevel"			=> 0
				),
				"AsyncRequestId"		=> array
				(
					"type"				=> "string",
					"acllevel"			=> 0
				),
				"Duration"				=> array
				(
					"type" 				=> "int",
					"required"			=> true,
					"acllevel"			=> 0
				),
				"Errors"				=> array
				(
					"type" 				=> "object",
					"class" 			=> "ResponseErrorArrayContainerType",
					"acllevel"			=> 0
				)
			);
	}
	 	
}

class RecordInfosType extends WS_Type
{
	public $Created;
	public $Updated;
	
	static public function WS_Properties()
	{
		return 
			array
			(
				"Created"				=> array
				(
					"type" 				=> "datetime",
					"acllevel"			=> 0
				),
				"Updated"				=> array
				(
					"type"				=> "datetime",
					"acllevel"			=> 0
				)
			);
	}
	 	
}

//////////////////// UTILS ////////////////


class ArrayContainerType extends WS_Type
{
	public $Count;
	public $LimitFrom;
	public $LimitCount;
	public $TotalCount;
		
	static public function WS_Properties()
	{
		return 
			array
			(
				"Count"	 	  			=> array
				(
					"type"				=> "int",
					"min"				=> 0,
					"required"			=> true,
					"acllevel"			=> 0
				),
				"LimitFrom"	 	  	 	=> array
				(
					"type"				=> "int",
					"min"				=> 0,
					"acllevel"			=> 0
				),
				"LimitCount"	 	 	=> array
				(
					"type"				=> "int",
					"min"				=> 0,
					"acllevel"			=> 0
				),
				"TotalCount"	 	 	=> array
				(
					"type"				=> "int",
					"min"				=> 0,
					"acllevel"			=> 0
				)
			);
	} 
}

class IntArrayType extends WS_Type
{
	public $Int;	

	static public function WS_Properties()
	{
		return 
			array
			(
				"Int"					=> array
				(
					"type" 				=> "array",
					"class"				=> "int",
					"acllevel"			=> 0				
				)
			);
	} 
}

class IntArrayContainerType extends ArrayContainerType
{
	public $Items;

	static public function WS_Properties()
	{
		return 
			array
			(
				"Items"					=> array
				(
					"type" 				=> "object",
					"class"				=> "IntArrayType",
					"acllevel"			=> 0
				)
			);
	} 
}

class StringArrayType extends WS_Type
{
  public $Str;  

  static public function WS_Properties()
  {
    return 
		array
		(
			"Str"        			=> array
			(
				"type"          	=> "array",
				"class"         	=> "string",
				"acllevel"			=> 0
			)
		);
  } 
}

class StringArrayContainerType extends ArrayContainerType
{
  public $Items;

  static public function WS_Properties()
  {
    return 
		array
		(
			"Items"          	 	=> array
			(
				"type"          	=> "object",
				"class"        		=> "StringArrayType",
				"acllevel"			=> 0
			)
		);
  } 
}

/********************************************/
/*       get_wstypes / get_wsfunctions		*/		
/*                          				*/		
/********************************************/

function get_wstypes()
{
	return array
	(
		'RequestOptionsType',
		'RequestType',
		'ResponseErrorType',
		'ResponseErrorArrayType',
		'ResponseErrorArrayContainerType',
		'ResponseType',
		'RecordInfosType',
		'ArrayContainerType',
		'IntArrayType',
		'IntArrayContainerType',
		'StringArrayType',
		'StringArrayContainerType',
	);
}

function get_wsfunctions()
{
	return array();
}

?>
