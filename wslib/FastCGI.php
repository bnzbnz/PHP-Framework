<?php

// Events :
// fastCGIOnIdle
// fastCGIOnHeadersReceived
// fastCGIOnParamsReceived
// fastCGIOnChildStarted
// fastCGIOnChildExiting
// fastCGIOnCheckUsage

require_once(WS_ABSWSPATHLIB.'Child.php'); 

define('FCGI_VERSION_1', 1);
define('FCGI_BEGIN_REQUEST', 1);
define('FCGI_ABORT_REQUEST', 2);
define('FCGI_END_REQUEST', 3);
define('FCGI_PARAMS', 4);
define('FCGI_STDIN', 5);
define('FCGI_STDOUT', 6);
define('FCGI_STDERR', 7);
define('FCGI_DATA', 8);
define('FCGI_GET_VALUES', 9);
define('FCGI_GET_VALUES_RESULT', 10);

class FastCGI
{
	static private function socket_readbinary_fixedsize($socket, &$buffer, $fixedsize)
	{
		$buffer = "";		
		do {
    	$data = @socket_read($socket, $fixedsize-strlen($buffer), PHP_BINARY_READ);
    	if($data===false || $data=="")
    		return false;
    	$buffer.=$data;
		} while (strlen($buffer) < $fixedsize);
		return true;
	}
	
	static private function &FCGI_BuildResponseHeaders()
	{
		$resarray[] = 'Status: '.$_REQUEST['Response']['StatusCode'];
		foreach($_REQUEST['Response']['Headers'] as $h => $v)
			$resarray[] = trim($h).': '.trim($v);
		$res = implode("\r\n",$resarray);
		return $res;
	}

	static private function FCGI_GetHeader(&$buffer)
	{
		$H = unpack("CVersion/CRecType/CIdHigh/CIdLow/CLenHigh/CLenLow", $buffer);
		$H['Id'] = ($H['IdHigh'] << 8)+$H['IdLow'];
		$H['Len'] = ($H['LenHigh'] << 8)+$H['LenLow'];
		return $H;
	}

	static private function &FCGI_PrepareResponse($type, &$content, $endrequest = true)
	{
		$response = "";			
		if($type==FCGI_STDOUT)
		{
			$block= self::FCGI_BuildResponseHeaders()."\r\n\r\n";
			$clen=strlen($block);
			$response.=chr(FCGI_VERSION_1);
			$response.=chr($type);
			$response.=chr(0).chr(1); // Request id = 1
			$response.=chr((int)($clen/256)).chr($clen%256); // Content length
			$response.=chr(0).chr(0); // No padding and reserved
			$response.=$block;	
		}
		
		$len = strlen($content);
		if($len>65534)
		{
			$blocks  = str_split($content, 65534);
			foreach($blocks as $block)
			{
				$clen=strlen($block);
				$response.=chr(FCGI_VERSION_1);
				$response.=chr($type);
				$response.=chr(0).chr(1); // Request id = 1
				$response.=chr((int)($clen/256)).chr($clen%256); // Content length
				$response.=chr(0).chr(0); // No padding and reserved
				$response.=$block;	
			}
		}
		elseif($len>0)
		{
			$clen=$len;
			$response.=chr(FCGI_VERSION_1);
			$response.=chr($type);
			$response.=chr(0).chr(1); // Request id = 1
			$response.=chr((int)($len/256)).chr($len%256); // Content length
			$response.=chr(0).chr(0); // No padding and reserved
			$response.=$content;			
		}
		
		if($endrequest)
		{
			$dum = chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0).chr(0);
			$clen=strlen($dum);
			$response.=chr(FCGI_VERSION_1);
			$response.=chr(FCGI_END_REQUEST);
			$response.=chr(0).chr(1); // Request id = 1
			$response.=chr((int)($clen/256)).chr($clen%256); // Content length
			$response.=chr(0).chr(0); // No padding and reserved
			$response.=$dum;
		}
		return $response;
	} 

	static private function &FCGI_BeginRequest(&$buffer)
	{
		$i = unpack("CFiller1/CRole/CKeepConn", $buffer);
		return $i;
	}

	static private function &GetFCGIReqParams(&$buffer)
	{
		$params = array();
		$len    = strlen($buffer);
		$pos		= 0;
		
		while( $pos < $len)
		{
			$nlen = ord($buffer{$pos});
			if ($nlen > 0x7F)
			{ 
				$nlen = ((ord($buffer{$pos}) & 0x7F) << 24) + (ord($buffer{$pos+1}) << 16) + (ord($buffer{$pos+2}) << 8) + (ord($buffer{$pos+3}));
				$pos = $pos + 3;
			}	
			$pos = $pos + 1;
			$vlen = ord($buffer{$pos});
			if ($vlen > 0x7F)
			{ 
				$vlen = ((ord($buffer{$pos}) & 0x7F) << 24) + (ord($buffer{$pos+1}) << 16) + (ord($buffer{$pos+2}) << 8) + (ord($buffer{$pos+3}));
				$pos = $pos + 3;
			}
			$pos = $pos + 1;
			$params[substr($buffer, $pos, $nlen)] = substr($buffer, $pos+$nlen, $vlen); 
			$pos = $pos + $nlen	+$vlen;
		}
		return $params;
	}

	static public function getSocketsInfo($socket)
	{
		$_ENV['FASTCGI']['SOCKETS']= array();
		$_ENV['FASTCGI']['SOCKETS']['LOCAL']= array();
		$_ENV['FASTCGI']['SOCKETS']['REMOTE']= array();
		if (socket_getsockname($socket, $host, $port))
		{
			$_ENV['FASTCGI']['SOCKETS']['LOCAL']['host'] = $host;
			$_ENV['FASTCGI']['SOCKETS']['LOCAL']['port'] = $port;
		}
		if (socket_getpeername($socket, $host, $port))
		{
			$_ENV['FASTCGI']['SOCKETS']['REMOTE']['host'] = $host;
			$_ENV['FASTCGI']['SOCKETS']['REMOTE']['port'] = $port;
		}
	}
	
	static public function childSigHandler($signo)
	{
		switch($signo) 
		{		
			case SIGTERM:			
				$_ENV['PARENT']['quit']=true;	
			break;
			case SIGHUP:
				$_ENV['PARENT']['quit']=true;		
			break;
			default:
			break;
		}	
	}

	static public function bkgChildSigHandler($signo)
	{
		switch($signo) 
		{	
			case SIGTERM:			
				$_ENV['PARENT']['quit']=true;	
			break;
			case SIGHUP:
				$_ENV['PARENT']['quit']=true;		
			break;
			default:
			break;
		}	
	}

	static public function daemonOnParentInitializing(&$startinguserinfo, &$ctxuserinfo)
	{	
		if (($_ENV['FASTCGI']['socket'] = socket_create (AF_INET, SOCK_STREAM, SOL_TCP)) === false) 
		{
			echo "main socket_create() failed : ".socket_strerror(socket_last_error($_ENV['FASTCGI']['socket'])).PHP_EOL;
			$_ENV['FASTCGI']['socket'] = null;
			return false;
		}
		if (@socket_bind($_ENV['FASTCGI']['socket'], issetX($_ENV['FASTCGI']['host'], 0), issetX($_ENV['FASTCGI']['port'], 10000)) === false) 
		{
			echo "main socket_bind() failed : ".socket_strerror(socket_last_error($_ENV['FASTCGI']['socket'])).PHP_EOL;
			return false;
		}
		if ((@socket_listen ($_ENV['FASTCGI']['socket'], issetX($_ENV['FASTCGI']['backlog'], 48))) === false) 
		{
			echo  " main socket_listen() failed : ".socket_strerror(socket_last_error($_ENV['FASTCGI']['socket'])).PHP_EOL;
			return false;
		}
		@socket_set_nonblock($_ENV['FASTCGI']['socket']);
		return call_user_funcX($_ENV['FASTCGI']['classname'].'::daemonOnParentInitializing', array(&$startinguserinfo, &$ctxuserinfo), true);	
	}
	
	static public function childOnParentStarted(&$parent)
	{
		return call_user_funcX($_ENV['FASTCGI']['classname'].'::childOnParentStarted', array(&$parent), true);
	}
	
	static public function childOnParentExiting(&$parent)
	{
		if(isset($_ENV['FASTCGI']['socket'])) socket_close($_ENV['FASTCGI']['socket']);
		return call_user_funcX($_ENV['FASTCGI']['classname'].'::childOnParentExiting', array(&$parent), true);
	}
	
	static public function childBkgOnRun(&$quit, $initialfork)
	{
		declare(ticks=1);
		return call_user_funcX($_ENV['FASTCGI']['classname'].'::childBkgOnRun', array(&$quit, $initialfork), 1000);
	}
	
	static public function childOnRun()
	{
		declare(ticks=1);
		$quit = &$_ENV['PARENT']['quit'];
		$fcgisock = $_ENV['FASTCGI']['socket'];
		$maxreq	= issetX($_ENV['FASTCGI']['max_request'], 1000);
		$maxmem	= issetX($_ENV['FASTCGI']['max_memory'], 16);
		$reqcnt	= 0;
		$idletmg = 0;
		call_user_funcX($_ENV['FASTCGI']['classname'].'::fastCGIOnChildStarted', array(&$_ENV['FASTCGI']), true); 
		while(!$quit && ($reqcnt < $maxreq))
		{
			$ready  = false;
			$socket = false;
			while(!$socket && !$quit)
			{
				$read = array($fcgisock); 
				$write = null;
				$except = null;
				set_error_handler(function($code, $string, $file, $line){ return true; });
				if( ($ready = @socket_select($read, $write, $except, 0, issetX($_ENV['FASTCGI']['waitforms'], 1000) * 1000)) === false ) { $quit=true; }
				if($ready > 0)
				{					
					$socket = @socket_accept($fcgisock);
				}
				elseif($ready == 0)
				{ // Timeout
					call_user_funcX($_ENV['FASTCGI']['classname'].'::fastCGIOnIdle', array(), true);	
				}				
				restore_error_handler();
			} 
			 
			$reqtime = microtime(true);
			$requestended = false;
			while($socket && !$requestended && !$quit)
			{
				$read	= array($socket);
				$write=null;
				$except=null;
				$ready = @socket_select($read, $write, $except, 0);
				if($ready === false) 
				{ 
					// Socket error :
					$requestended = true; 
					break; 
				}
				elseif($ready>0 && in_array($socket, $read))
				{
					// We got a data block
					$data = "";
					while(self::socket_readbinary_fixedsize($socket, $data, 8))
					{
						$FCGIHeader = self::FCGI_GetHeader($data);
						$data = "";
						self::socket_readbinary_fixedsize($socket, $data, $FCGIHeader['Len']);
						call_user_funcX($_ENV['FASTCGI']['classname'].'::fastCGIOnHeadersReceived', array(&$FCGIHeader), true);
						switch($FCGIHeader['RecType'])
						{
							case FCGI_BEGIN_REQUEST: // begin Request
								$request['Id'] = $FCGIHeader['Id']; 
								unset($_SERVER); 	$_SERVER	= array();	
								unset($_GET); 		$_GET		= array();
								unset($_POST); 		$_POST		= array();
								unset($_REQUEST);	$_REQUEST	= array();
								$_REQUEST['Body'] 	= "";
								$_REQUEST['Response'] = array();
								$_REQUEST['Response']['StatusCode'] = '200 OK';
								$_REQUEST['Response']['Headers'] = array();
								$reqcnt++;
								break;
							case FCGI_PARAMS: // params
							{
								if ($FCGIHeader['Len']>0)
								{
									$_SERVER = array_merge($_SERVER, self::GetFCGIReqParams($data));	
								} else {
									parse_str(urldecode($_SERVER['QUERY_STRING']), $_GET);
									$_SERVER['PHP_SELF']=$_SERVER['SCRIPT_NAME'];
								}
								call_user_funcX($_ENV['FASTCGI']['classname'].'::fastCGIOnParamsReceived', array(&$_SERVER), true);
							}
							break;
							case FCGI_STDIN: // stdin POST
							{
								if ($FCGIHeader['Len']>0)
								{
									$_REQUEST['Body']=$_REQUEST['Body'].$data;
								} 
								else 
								{
									try
									{
										self::getSocketsInfo($socket); 
										$content = "";
										if (ob_get_length() > 0) { ob_end_clean(); }
										ob_start();
										call_user_func($_ENV['FASTCGI']['classname'].'::childRun');
										$content = ob_get_clean();
										if(issetX($_ENV['FASTCGI']['compress'],true))
											if(!isset($_REQUEST['Response']['Headers']['Content-Encoding']))
												if(isset($_SERVER['HTTP_ACCEPT_ENCODING'])) 
												  if(strlen($content)>1024) 
													{
														if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
														{ 
															$_REQUEST['Response']['Headers']['Content-Encoding'] ='gzip';
															$content = gzencode($content, 6, FORCE_GZIP);
														}
														elseif (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate'))
														{ 
															$_REQUEST['Response']['Headers']['Content-Encoding'] = 'deflate';
															$content = gzdeflate($content, 6);
														}
													}
										$_REQUEST['Response']['Headers']['Content-Length'] = strlen($content);
										$res = self::FCGI_PrepareResponse(FCGI_STDOUT, $content);
									} 
									catch(exception $ex)
									{ 
											ob_end_clean();
											$content = $ex->getMessage();
											syslogX(LOG_WARNING, 'FastCGI-stderr: '.$content);
											$res = self::FCGI_PrepareResponse(FCGI_STDERR, $content);
									}
									unset($content);
									unset($_SERVER);	
									unset($_GET);
									unset($_POST);
									unset($_REQUEST);
									@socket_write($socket, $res);
									unset($res);		
									$requestended = true;			
								}	
							}
							break;
							default: 
							{
								$requestended = true;
								syslogX("FASTCGI PACKET FORMAT ERROR : ".$FCGIHeader['RecType']."\n");
								$quit = true;
								break;
							}
						}  
					}					
				}
			}
			
			$reqtime = microtime(true) - $reqtime;
			if($socket) socket_close($socket);	               
			call_user_funcX($_ENV['FASTCGI']['classname'].'::fastCGIOnCheckUsage', array(&$_ENV['FASTCGI']), true); 	
			if (function_exists('gc_collect_cycles')) { gc_enable(); gc_collect_cycles(); } 
			$mema = memory_get_usage(false) / (1024*1024);
			$memb = memory_get_usage(true ) / (1024*1024);
			$memc = issetX($maxmem, 9999);
			if($memb > $memc)
			{
				syslogX('fastCGI Child Restarting, too much memory used : '.$memb);
				$quit = true;
			}
			
		}
		call_user_funcX($_ENV['FASTCGI']['classname'].'::fastCGIOnChildExiting', array(&$_ENV['FASTCGI']), true); 
	}
	
	static public function init()
	{
		Child::init();				
	}
	
	static public function run($className, $appName)
	{
		$_ENV['FASTCGI']['classname'] = $className;
		Child::run('FastCGI', $appName);
	}
}	
?>