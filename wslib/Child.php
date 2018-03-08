<?php
// Events :
// childOnParentStarted
// childOnParentExiting
// childOnRun
// childOnBackgroundRun

require_once(WS_ABSWSPATHLIB.'Daemon.php'); 

function tick_handler() { pcntl_signal_dispatch(); }

function _child_sig_handler($signo) 
{
	call_user_funcX($_ENV['PARENT']['childclassname'].'::childSigHandler', array($signo), true);
}

function _bkg_child_sig_handler($signo) 
{
	call_user_funcX($_ENV['PARENT']['childclassname'].'::bkgChildSigHandler', array($signo), true);
}

class Child
{
	static public function parentSigHandler($signo)
	{
		$quit = &$_ENV['PARENT']['quit'];
		switch($signo) {
   		case SIGTERM:
			// The SIGTERM signal is a generic signal used to cause program termination. Unlike SIGKILL , this signal can be blocked, handled,
			// and ignored. It is the normal way to politely ask a program to terminate. The shell command kill generates SIGTERM by default. 
			// Macro: int SIGINT.

   			$_ENV['PARENT']['quit']=true;
			
			if(issetX($_ENV['PARENT']['bkgchild'], false))
				if(issetX($_ENV['PARENT']['BKGCHILDID'], false))
					if(posix_kill($_ENV['PARENT']['BKGCHILDID'], SIGTERM))
						unset($_ENV['PARENT']['BKGCHILDID']);
			
			if(count($_ENV['PARENT']['CHILDLIST']['ids'])>0)
				foreach($_ENV['PARENT']['CHILDLIST']['ids'] as $key => $cpid) 
					if(posix_kill($cpid, SIGTERM))
						unset($_ENV['PARENT']['CHILDLIST']['ids'][$key]);					
        break;
		case SIGHUP:			
			break;
		case SIGCHLD:
			// When a child process stops or terminates, SIGCHLD is sent to the parent process. The default response to the signal is to ignore // it. The signal can be caught and the exit status from the child process can be obtained by immediately calling wait(2) and 		// wait 3(3C).
			while(( $pid= pcntl_wait ( $signo, WNOHANG ) ) > 0 )
			{	
				if(issetX($_ENV['PARENT']['BKGCHILDID'], false) == $pid )
				{
					unset($_ENV['PARENT']['BKGCHILDID']);
					if(!$_ENV['PARENT']['quit']) self::forkBkgChild($quit, false);
				}					
				if(count($_ENV['PARENT']['CHILDLIST']['ids'])>0)
					foreach($_ENV['PARENT']['CHILDLIST']['ids']  as $key => $cpid)
						if($pid == $cpid) 
						{
							unset($_ENV['PARENT']['CHILDLIST']['ids'][$key]);	
							if(!$_ENV['PARENT']['quit']) self::forkChild(); 		
						}
			}
			break;		
		}
	}
	
	static private function forkBkgChild(&$quit, $initialfork=false)
	{
		$quit = &$_ENV['PARENT']['quit'];
		$child = pcntl_fork();
		if($child == 0) 
		{
			set_time_limit(0);
			pcntl_signal(SIGTERM, "_bkg_child_sig_handler");
			pcntl_signal(SIGHUP,  "_bkg_child_sig_handler");
			pcntl_signal(SIGCHLD, "_bkg_child_sig_handler");
			pcntl_signal(SIGUSR1, "_bkg_child_sig_handler");
			pcntl_signal(SIGINT,  "_bkg_child_sig_handler");
			while(!$quit)
			{
				try
				{
					declare(ticks=1);
					$ms=call_user_funcX($_ENV['PARENT']['childclassname'].'::childOnBackgroundRun', array(&$quit, $initialfork), 250);
					msleepX($ms);
					$initialfork = false;
				} catch(Exception $ex) {}
			}
			exit(0);	
		}
		$_ENV['PARENT']['BKGCHILDID'] = $child;
	}
	
	static private function forkChild()
	{
		$child = pcntl_fork();
		if($child == 0) 
		{
			set_time_limit(0);
			pcntl_signal(SIGTERM, "_child_sig_handler");
			pcntl_signal(SIGHUP,  "_child_sig_handler");
			pcntl_signal(SIGCHLD, "_child_sig_handler");
			pcntl_signal(SIGUSR1, "_child_sig_handler");
			pcntl_signal(SIGINT,  "_child_sig_handler");
			declare(ticks=1);
			call_user_funcX($_ENV['PARENT']['childclassname'].'::childOnRun', array(), true); 			
			exit(0);	
		}
		$_ENV['PARENT']['CHILDLIST']['ids'][] = $child;
	}	
	
	static public function daemonOnParentInitializing(&$startinguserinfo, &$ctxuserinfo)
	{	
		return call_user_funcX($_ENV['PARENT']['childclassname'].'::daemonOnParentInitializing', array(&$startinguserinfo, &$ctxuserinfo), true);	
	}
	
	static public function daemonOnParentStarted(&$daemon)
	{		
		$_ENV['PARENT']['quit'] = false;
		$quit = &$_ENV['PARENT']['quit'];
		call_user_funcX($_ENV['PARENT']['childclassname'].'::childOnParentStarted', array(&$_ENV['PARENT']), true);
		for($i = 0;$i < $_ENV['DAEMON']['childcount'];$i++) { self::forkChild(); }			
		self::forkBkgChild($quit, true);
		while(count($_ENV['PARENT']['CHILDLIST']['ids'])>0)
		{
			$pid = pcntl_wait($status);
			if(issetX($_ENV['PARENT']['BKGCHILDID'], false)==$pid)
			{
				unset($_ENV['PARENT']['BKGCHILDID']);	
				if(!$_ENV['PARENT']['quit']) { self::forkBkgChild($quit, false);	}	
			}
			foreach($_ENV['PARENT']['CHILDLIST']['ids']  as $key => $cpid)
				if($pid == $cpid) 
				{
					unset($_ENV['PARENT']['CHILDLIST']['ids'][$key]);	
					if(!$_ENV['PARENT']['quit']) { self::forkChild();	}		
				}
		}
		
	}	
	
	static public function daemonOnParentExiting(&$daemon)
	{
		return call_user_funcX($_ENV['PARENT']['childclassname'].'::childOnParentExiting', array(&$_ENV['PARENT']), true);
	}
	
	static public function init()
	{
		Daemon::init();				
		$_ENV['DAEMON']['childcount'] = issetX($_ENV['DAEMON']['childcount'], 4);
	}
	
	static public function run($className, $appName)
	{
		$_ENV['PARENT']['childclassname'] = $className;
		Daemon::run('Child', $appName);
	}
	
}
?>
