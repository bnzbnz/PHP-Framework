PWS Framework is a PHP Web Service Framework that allow for fast Web Service development. 

This is a fastcgi (PHP native) server running in cli mode (php-cli) as a daemon. 

It is able to handle requests and responses using the following formats : REST, SOAP, JSON, XML, PHP and Typed PHP, these formats can be mixed.

It also provides supports for asynchronous requests, credentials and request details.

(PHP Linux Only)

How to Setup :

The following procedure has been validated on Debian Stretch. 

An install under Ubuntu should be straightforward (use sudo). For other linux, please refer to your OS documentation.


The installation procedure will install the following:

Lighttpd : 1.4.45 (ssl) or later (you could use nginx, apache...)

Php cli/cgi : 7.0.27 or later

Memcached : 1.4.33 or later.

Setup:

	1-Memcached :
		apt-get install memcached
		We are using the default setup (Listen on 127.0.0.1 port 11211)
		
	2-PHP cli/cgi :
		apt-get install php php-cgi php-cli php-soap php-uuid php-curl php-geoip php-memcached
		Again the default setup should be fine
		
	3-Lighttpd :
		apt-get install lighttpd
		edit (vi,nano,joe)  /etc/lighttpd/lighttpd.conf
		
		Enable the following modules:
		
		server.modules = (
		"mod_setenv",
		"mod_access",
		"mod_cgi",
		"mod_fastcgi",
		"mod_compress",
		"mod_alias",
		"mod_accesslog",
		"mod_rewrite",
		"mod_redirect",
		"mod_auth",
		)
		
		Define the last section as follow:
		$HTTP["url"] =~ "^/ws/" 
		{
			fastcgi.server = ( "/ws/" => ((
			"allow-x-send-file" => "enable",
			"allow-x-sendfile" => "enable",
			"host" => "127.0.0.1",
			"port" => 10000,
			"check-local" => "disable"
        )))
		} else $HTTP["url"] =~ "^/" {
			fastcgi.server = ( ".php" => ((
			"bin-path" => "/usr/bin/php-cgi",
			"socket" => "/tmp/php.socket",
			"max-procs" => 1,
			"bin-environment" => (
			"PHP_FCGI_CHILDREN" => "8",
			"PHP_FCGI_MAX_REQUESTS" => "5000",
			"DEV_MODE" => "DEV"             ),
			"bin-copy-environment" => ("PATH", "SHELL", "USER"),
			"broken-scriptfilename" => "enable"        )))
		}

		CGI and html requests will stil be redirected to /var/www but /ws/ (Web Service requests) will be redirected to the fastcgi server located at 127.0.0.1 port 10000 (pws).
		
		Restart lighttpd : /etc/init.d/lighttpd restart
		
	4-PWS :
		Create a directory (MYFLDR). Untar PWS inside, you get 2 folders (ws,wslib).
		Now cd to the first demo (basic call) :
			cd /MYFLDR/ws/01/
		Start the daemon :
			./demo start (optionally stop or restart)
	
	You are ready to go:
		http://MYIP/ws/?wsdl
		http://MYIP/ws/?GetTime
		
	Look at the other demos to learn the basics....
	ENJOY!!!
		
		
