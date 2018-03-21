<?php
	
	// The name of the user in which the fcgi server will be started
	$_ENV['DAEMON']['user']				= 'www-data';
	// The number of fcgi child processes
	$_ENV['DAEMON']['childcount']		= 4;
	
	// Indicates if a background process should be started
	$_ENV['PARENT']['bkgchild']			= true;
	
	// Fast CGI host IP : 0 means all
	$_ENV['FASTCGI']['host']			= 0;
	// Fast CGI port
	$_ENV['FASTCGI']['port']			= 10000;
	// The number of pending requests
	$_ENV['FASTCGI']['backlog']			= 48;
	// The maximun number of requests to be processed by a child
	$_ENV['FASTCGI']['max_request'] 	= 1000;
	// The maximum amount of memory used by a child
	$_ENV['FASTCGI']['max_memory'] 		= 16; // MB
	// If the http response should be compressed
	$_ENV['FASTCGI']['compress']		= true;
	// Fast CGI Socket Timeout (ms)
	$_ENV['FASTCGI']['waitforms']		= 5000;
	
	// Where is stored the temporary wsdl
	$_ENV['WS']['wsdl_store']			= '/dev/shm/'; 
	// If the async. requests can be made
	$_ENV['WS']['async_request']		= true;
	// For Async. requests : a memcached array :
	$_ENV['WS']['async_memcached']		= array(
			array( 'host' => '127.0.0.1', 'port' => 11211, 'compress'  => true ),
			array( 'host' => '127.0.0.1', 'port' => 11211, 'compress'  => true)
	);
	// Which language is used En, Fr... for errors/exception
	$_ENV['WS']['language']				= 'En';
	
	// The following depends of the application : 
	$_ENV['APP']['dummy']				='DUMMY';
		
	
?>