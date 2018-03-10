<?php
// PHP extensions

function issetX(&$var, $defaultvalue) { if (!isset($var)) { return $defaultvalue; } return $var; }

function asarrayvalX($v) { // asArrayVal
	if ($v === null) { return null; }; 
	if (is_array($v)) {	return $v; } else	{ return array($v); }
}

function asarrayptrX(&$v) { //asArrayPtr
	if (!is_array($v)) { $v=array($v); }
}
	
function syslogX($msg) { syslog(LOG_WARNING,getmypid().' '.$msg); }

function msleepX($ms) { usleep($ms*1000); }

function nullerrorhandlerX($errno, $errstr, $errfile, $errline) { return true; }

function syslogerrorhandlerX($errno, $errstr, $errfile, $errline) { syslogX($errno.' - '.$errstr.' - '.$errfile.' - '.$errline); return true; }

function serializeX(&$o) { return preg_replace('/s:\d*:"\w*\";N;/', '', serialize($o)); }

function set_error_handlerX($errlevel=E_ALL, $handlername='syslogerrorhandlerX')
{
	set_error_handler($handlername);
	error_reporting($errlevel);	
}

function stacktraceX($logname = "")
{
	$dt = debug_backtrace();
	for($i=1;$i<count($dt);$i++)
	{
		syslogX("stacktrace $logname : ".$i);
		syslogX(serializeX($dt[$i]));
	}
}
	
function call_user_funcX($funcname, $params, $defaultreturn = false)
{
	if(!is_callable($funcname)) { return $defaultreturn; }
	try
	{
		return call_user_func_array($funcname, $params); 
	} catch (Exception $ex) { syslogX($ex->getMessage()); }
	return $defaultreturn;
}

function uuidX($dashed = false)
{
	if (!$dashed) return strtr(uuid_create(UUID_TYPE_RANDOM), array("-"=>"") );
	else return uuid_create(UUID_TYPE_RANDOM);
}	

function URLRootX($protocol="", $domain="", $port=0)
{
	$host = issetX($_SERVER['HTTP_HOST'], "none");
	$protocol = strtolower($protocol);
	if ($protocol=="")
	{
		$url = "http://";
		if ( ($_SERVER["SERVER_PORT"] != 80) && ($_SERVER["SERVER_PORT"] != 8080) )			
			$url = "https://";
	} 
		else
	{
		$url = $protocol."://";
	}           
	$url = $url . $host;           
	if (($_SERVER['SERVER_PORT']!=80) && ($_SERVER['SERVER_PORT']!=81) && ($_SERVER['SERVER_PORT']!=8080) && ($_SERVER['SERVER_PORT']!=8081))
	{
		$url = $url . ':' . $_SERVER['SERVER_PORT'];
	}
	return $url;
}

function getUserIpX(&$isPrivateIp)
{
	 $private_ip = array(
			"/^10\..*/", 
			"/^192\.168\..*/",
			"/^0\./", "/^127\.0\.0\.1/", "/^127\.0\.1\.1/",
			"/^172\.((1[6-9])|(2[0-9])|(3[0-1]))\..*/",
			"/^224\..*/",
			"/^240\..*/"
		);
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$tmpArr = explode('#',$_SERVER['HTTP_X_FORWARDED_FOR']);
		}
			else
		{
			if(isset($_SERVER['REMOTE_ADDR']))
				$tmpArr = explode('#',$_SERVER['REMOTE_ADDR']);
			else
				$tmpArr = array('127.0.0.1');
		}
		$tmpArr = explode(',', $tmpArr[0]);
		$Ip = trim($tmpArr[0]);	
		$isPrivateIp = false;
		while ((list($key, $val) = each ($private_ip)) && (!$isPrivateIp))
			$isPrivateIp = $isPrivateIp || preg_match($val, $Ip);;			
		return $Ip;
}