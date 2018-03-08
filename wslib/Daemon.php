<?php

// Events :
// daemonOnParentInitializing
// daemonOnParentProcessRun
// daemonOnParentExiting

include_once(WS_ABSWSPATHLIB.'PHPExt.php');

function _parent_sig_handler($signo) 
{
	call_user_funcX($_ENV['DAEMON']['classname'].'::parentSigHandler', array($signo), false);
}

class Daemon
{	
	
	static private function exitCleanup($msg=null, $unlink)
	{
		if(isset($_ENV['DAEMON']['pidfilehandle']))
			fclose($_ENV['DAEMON']['pidfilehandle']);
		if($unlink) 
			unlink($_ENV['DAEMON']['pidfile']);
		if(isset($msg))
			print $msg.PHP_EOL;
		exit(0);
	}
	
	static public function init()
	{
		global $argc, $argv;
		set_time_limit(0);
		$env = $_ENV;
		$_ENV = array();
		$_ENV['ENV'] = $env;
		$_ENV['SERVER']['SCRIPT_NAME'] = $argv[0];
		$_ENV['DAEMON'] = array();
		for($i=1; $i < $argc; $i++)	
		{
			$d = explode('=', $argv[$i]);
			if(count($d)==2) 
				$_ENV['ARG'][strtolower(trim($d[0]))]=trim($d[1]); 
			else
				$_ENV['ARG'][strtolower(trim($d[0]))]=true; 
			
		}
		$_ENV['DAEMON']['conffile'] = issetX($_ENV['ARG']['--conffile'], issetX($_ENV['ARG']['-c'], pathinfo($argv[0])['filename'].'.conf.php'));
		if (file_exists ($_ENV['DAEMON']['conffile'])) 
			require_once($_ENV['DAEMON']['conffile']);
	}
	
	static public function run($className, $appName)
	{
		$_ENV['DAEMON']['appname'] = $appName;
		$_ENV['DAEMON']['classname'] = $className;
		$_ENV['DAEMON']['daemonid'] = issetX($_ENV['ARG']['--daemonid'], issetX($_ENV['ARG']['-d'], 0));
		$_ENV['DAEMON']['pidfile'] = '/tmp/'.$_ENV['DAEMON']['appname'] .$_ENV['DAEMON']['daemonid'].'.pid';	
		$_ENV['DAEMON']['startinguserinfo'] =  posix_getpwuid(posix_geteuid());
		$username = $_ENV['DAEMON']['startinguserinfo']['name'];
		$username = !empty($_ENV['DAEMON']['user']) ? $_ENV['DAEMON']['user'] : $username;
		$username = !empty($_ENV['ARG']['--user']) ? $_ENV['ARG']['--user'] : $username;
		$username = !empty($_ENV['ARG']['-u']) ? $_ENV['ARG']['-u'] : $username;
		$_ENV['DAEMON']['ctxuserinfo'] = posix_getpwnam($username);		
		
		if (isset($_ENV['ARG']['stop']))
		{	
			if(!file_exists($_ENV['DAEMON']['pidfile']))  self::exitCleanup("Process does not exist.", false);
			$pidh = fopen($_ENV['DAEMON']['pidfile'],"r"); 
			$pid = fgets($pidh, 1024); 
			fclose($pidh);
			if (trim($pid)=="" || @pcntl_getpriority($pid) === false) 
					unlink($_ENV['DAEMON']['pidfile']);	
			elseif(posix_kill($pid, 0)) 
				while(posix_kill($pid, SIGTERM)) 
					msleepX(100);	
			print "$appName (Id : {$_ENV['DAEMON']['daemonid']}) stopped".PHP_EOL;
			exit(0);
		} 
		if (isset($_ENV['ARG']['restart']))
		{
		
			if(file_exists($_ENV['DAEMON']['pidfile']))
			{ 
			$pidh = fopen($_ENV['DAEMON']['pidfile'],"r"); 
			$pid = fgets($pidh, 1024); 
			fclose($pidh);
			if (trim($pid)=="" || @pcntl_getpriority($pid) === false)
				unlink($_ENV['DAEMON']['pidfile']);	
			elseif(posix_kill($pid, 0)) 
				while(posix_kill($pid, SIGTERM)) 
					msleepX(100);	
			}
			msleepX(2000);
		} 
		if (isset($_ENV['ARG']['start']) || isset($_ENV['ARG']['restart']))
		{
			if(file_exists($_ENV['DAEMON']['pidfile'])) 
			{
				$pidh = fopen($_ENV['DAEMON']['pidfile'],"r"); 
				$pid = fgets($pidh, 1024); 
				fclose($pidh);
				if(trim($pid)!="")
					if (@pcntl_getpriority($pid) !== false)
						self::exitCleanup("Process is already running PID : $pid", false);
				unlink($_ENV['DAEMON']['pidfile']);	
			}
			if(!($_ENV['DAEMON']['pidfilehandle'] = fopen($_ENV['DAEMON']['pidfile'],"w")))
			{ 
				$_ENV['DAEMON']['pidfilehandle'] = null;
				self::exitCleanup("Unable to open PID file $file for writing.", false);
			}
			if(!call_user_funcX($_ENV['DAEMON']['classname'].'::daemonOnParentInitializing', array(&$_ENV['DAEMON']['startinguserinfo'], &$_ENV['DAEMON']['ctxuserinfo']), true))
				self::exitCleanup("parentInit failed.", true);	

			chown($_ENV['DAEMON']['pidfile'], $_ENV['DAEMON']['ctxuserinfo']['uid']);
			chmod($_ENV['DAEMON']['pidfile'], 0755);  
			if(!posix_setgid($_ENV['DAEMON']['ctxuserinfo']['gid']))
				self::exitCleanup("Unable to set group identity : ".$_ENV['DAEMON']['ctxuserinfo']['name'], true);
			if(!posix_setuid($_ENV['DAEMON']['ctxuserinfo']['uid']))
				self::exitCleanup("Unable to set user identity : ".$_ENV['DAEMON']['ctxuserinfo']['name'], true);	
			$dad = pcntl_fork();
			if($dad) 
			{
				if(isset($_ENV['ARG']['start']))
				print "$appName (Id : {$_ENV['DAEMON']['daemonid']}) started.".PHP_EOL;
				if(isset($_ENV['ARG']['restart']))
				print "$appName (Id : {$_ENV['DAEMON']['daemonid']}) restarted".PHP_EOL;
				exit(0); // kill parent
			}
			declare(ticks=1);
			set_time_limit(0);
			pcntl_signal(SIGTERM, "_parent_sig_handler", false);
			pcntl_signal(SIGHUP, "_parent_sig_handler");
			pcntl_signal(SIGCHLD, "_parent_sig_handler");
			pcntl_signal(SIGUSR1, "_parent_sig_handler");
			pcntl_signal(SIGINT, "_parent_sig_handler");
			posix_setsid(); // become session leader
			umask(0); // clear umask
			$_ENV['DAEMON']['pid'] = posix_getpid();
			fputs($_ENV['DAEMON']['pidfilehandle'], $_ENV['DAEMON']['pid']);
			fclose($_ENV['DAEMON']['pidfilehandle']);
			$_ENV['DAEMON']['pidfilehandle'] = null;
			call_user_funcX($_ENV['DAEMON']['classname'].'::daemonOnParentStarted', array(&$_ENV['DAEMON']), false);	
			call_user_funcX($_ENV['DAEMON']['classname'].'::daemonOnParentExiting', array(&$_ENV['DAEMON']), false);
			self::exitCleanup(null, true);
		}
	}
}
