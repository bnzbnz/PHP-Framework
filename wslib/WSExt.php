<?php

//////////////////////////////////////////
//										//
// WS Helpers							//
//										//
//////////////////////////////////////////

function ws_addAsyncTask($function, $params = null)
{
	$ctx = array();
	$ctx['SERVER'] = $_SERVER;
	$_ENV['WS']['AsyncTask'][] =  array($function, serialize($params), serialize($ctx)); 
}

function ws_setError(&$Res, $Ex)
{
	$Res 			= issetX($Res, new ResponseType);
	$Res -> Errors 	= issetX($Res -> Errors, new ResponseErrorArrayContainerType);
	$Res->Ack = 1;

	if($Ex instanceof WS_Exception)
	{
		if($Res -> Errors -> Items == null) { $Res -> Errors -> Items = new ResponseErrorArrayType; }
		foreach($Ex->Errors as $err)
		{
			$error 	= new ResponseErrorType();
			$error 	-> Code = $err[0];
			$error 	-> Message = WSLang::get($err[0], array($err[1], $err[2], $err[3]) );
			$error 	-> Key   = $err[1];
			$error 	-> Info1 = $err[2];
			$error 	-> Info2 = $err[3];
			$Res 	-> Errors -> Items -> Error[] = $error;
		}		 
	} 
		else 
	{
			$error 	= new ResponseErrorType();
			$error 	-> Code = $Ex->getCode();
			$error 	-> Message = "::". $Ex->getMessage();
			$Res 	-> Errors -> Items = new ResponseErrorArrayType;
			$Res 	-> Errors -> Items ->Error[] = $error;			
	}
	$Res -> Errors -> Count = Count(issetX($Res -> Errors -> Items -> Error, null));
	return $Res;
}

function ws_get($obj, $default)
{	
	if (!(isset($obj))) { return $default; } else { return $obj; }
}
	
function ws_new($obj, $prop)
{
	if ($obj -> $prop == null)
	{
		$pinfo = $obj -> WSPropertiesInfo();
		$ptype = $pinfo[$prop];
		$obj -> $prop = new $ptype['type'];
	}
}

function ws_getProp($req, $name, $defaultvalue)
{
	if (!isset($req->{$name})) { return $defaultvalue; };
	return $req->{$name};	
}


function ws_raiseException($code, $key, $info1, $info2)
{
	$Ex = new WS_Exception;
	$Ex->addError( $code, $key, $info1, $info2 );			
	throw $Ex; 
}

function ws_clearDetailLevelPageInfo(&$dlarr)
{
	$dlarr['%_limitfrom_%'] = null;
	$dlarr['%_limitcount_%'] = null;
}

function ws_getDetailLevelPageInfo(&$dlarr, &$offset, &$length)
{
	$len = 0;
	$start = 0;
	
	if (isset($dlarr['%_limitfrom_%']))
		$start = $dlarr['%_limitfrom_%'];
	
	if (isset($dlarr['%_limitcount_%']))
		$len = $dlarr['%_limitcount_%'];
	
	if( $start < 0) { $start = 0; } 
	if( $len < 0) { $len = 0; } 

	if(( $start == 0) && ( $len == 0)) 
		return false;
	
	$offset = $start;
	$length = $len;
	
	return true;
}

function ws_paginateArray($container, &$idarr, &$dlarr, $maxpubliccount=5)
{
	
	$len = 0;
	$start = 0;
	$total = count($idarr);
	
	if(!ws_getPageInfo($dlarr, $start, $len))
		return false;
		
	$container -> LimitCount = $len;
	$container -> LimitFrom = $start;	
	$container -> TotalCount = $total;
	
	if( $len == 0 )
		$idarr = array_slice($idarr, $start);
	else			
		$idarr = array_slice($idarr, $start, $len);			
	return true;		
}

function ws_getDetailLevel(&$dlarr, $defaultLevel = "MIN" )
{
	if(isset($dlarr['%_detaillevel_%'])) { return $dlarr['%_detaillevel_%']; }
	return $defaultLevel;
}

function ws_detailLevelToArray($dlstr, &$dlarr)
{
	$dlval = array('MAX','MIN','STD','NONE','CNT');
	foreach(explode(',', $dlstr) as $dl)
	{
		$dlv = explode(':', $dl);
		if(count($dlv) == 2)
		{
			$pdl = explode('.', $dlv[0]);
			$pa = &$dlarr;
			foreach($pdl as $sdl)
				$pa = &$pa[trim($sdl)];
			$ldl = explode(';', $dlv[1]);
			if (in_array(strtoupper(trim($ldl[0])), $dlval))
				$pa['%_detaillevel_%'] = strtoupper(trim($ldl[0]));
			if ( (count($ldl) == 3) && (is_numeric(trim($ldl[1]))) && (is_numeric(trim($ldl[2]))))
			{
				$pa['%_limitfrom_%'] = strtoupper(trim($ldl[1]));
				$pa['%_limitcount_%'] = strtoupper(trim($ldl[2]));
			}	
		}
			else
		{
			if (in_array(strtoupper(trim($dl)), $dlval))
				$dlarr['%_detaillevel_%'] = strtoupper(trim($dl));		
		}
	}		
}

function &ws_getSubDetailLevel(&$dlarr, $filter, $currentLevel)
{
	if(!isset($dlarr[$filter]['%_detaillevel_%'])) 
		$dlarr[$filter]['%_detaillevel_%'] = $currentLevel;
	return $dlarr[$filter];
}

function ws_detailLeveltoInt($dl)
{
	switch ($dl) 
	{
		case "MIN":
			return 1;
		break;
		case "STD":
			return 2;
		break;
			case "MAX":
		return 3;
		break;
			default:
		return 0;
	}
}	

function ws_detailLevelDec($dl)
{
	if($dl=="MAX") { return "STD"; }
	elseif($dl=="STD") { return "MIN"; }
	return "NONE";
}

?>
