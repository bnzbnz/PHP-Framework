<?php
	
	class MemCachedPoolsLB 
	{
		private $Pool;
		
		private function sysLogError($fname, $excludeerror)
		{
			$err = $this->Pool->getResultCode();
			$excludeerror[]=0;
			if(in_array($err, $excludeerror)) return;
			$msg = " ERROR CODE ".$err; 
			switch ($err) {
				case 0  : $msg = "RES_SUCCESS"; break; 
				case 1  : $msg = "RES_FAILURE"; break; 
				case 2  : $msg = "RES_HOST_LOOKUP_FAILURE"; break; 
				case 5  : $msg = "RES_WRITE_FAILURE"; break; 
				case 7  : $msg = "RES_UNKNOWN_READ_FAILURE"; break; 
				case 8  : $msg = "RES_PROTOCOL_ERROR"; break; 
				case 9  : $msg = "RES_CLIENT_ERROR"; break; 
				case 10 : $msg = "RES_SERVER_ERROR"; break; 
				case 11 : $msg = "RES_CONNECTION_SOCKET_CREATE_FAILURE"; break; 				
				case 12 : $msg = "RES_DATA_EXISTS"; break; 
				case 14 : $msg = "RES_NOTSTORED"; break; 
				case 16 : $msg = "RES_NOTFOUND"; break; 
				case 18 : $msg = "RES_PARTIAL_READ"; break; 
				case 19 : $msg = "RES_SOME_ERRORS"; break; 
				case 20 : $msg = "RES_NO_SERVERS"; break; 
				case 21 : $msg = "RES_END"; break; 
				case 25 : $msg = "RES_ERRNO"; break; 
				case 30 : $msg = "RES_TIMEOUT"; break; 
				case 31 : $msg = "RES_BUFFERED"; break; 
				case 32 : $msg = "RES_BAD_KEY_PROVIDED"; break; 			
				case 31 : $msg = "RES_BUFFERED"; break; 
				case -1001 : $msg = "RES_PAYLOAD_FAILURE"; break; 				
			}
		}	
		
		public function __construct($servers, $conpersist) 
    {
    	$this->Pool = new Memcached;
    	foreach($servers as $server)
    		$this->Pool->addServer($server['host'], $server['port'], $conpersist);
    }
    
    public function &get($key) 
    {
    	if(is_array($key))
    		$v = $this->Pool->getMulti($key); 
 		else
 			$v = $this->Pool->get($key); 	
		if ($v === false) { $v = null; }
		$this->SysLogError('get', array(16));
		return $v;
  	}
  	
  	private function calcExpire($expiresec)
  	{
  		if($expiresec == 0) 			{ return 0; } // LRU
  		if($expiresec <  0) 			{ return floor(microtime(true)+(3600*24*365*2)); } // INFINITE		
  	  return time()+$expiresec;
  	}
    
    public function set($key, $var, $compress=0, $expiresec=0) 
    {
  		$v = $this->Pool->set($key, $var, $this->calcExpire($expiresec));
  		$this->SysLogError('set', array(16));
  		return $v; 	
    }
		
		public function add($key, $var, $compress=0, $expiresec=0) 
    {
  		return $this->Pool->add($key, $var, $this->calcExpire($expiresec)); 	
    }
    
    public function delete($key, $timeout=0) 
    {
   		return $this->Pool->delete($key, $timeout);
    }

    public function flush() 
    {
    	return $this->Pool->flush();	
  	}
    
    public function decrement($key, $value=1) 
    {
    	return $this->Pool->decrement($key, $value);
    }
     		  	
    public function increment($key, $value=1) 
    {
    	return $this->Pool->increment($key, $value);	
  	}
  	
	public function geterror()
	{
    	return $this->Pool->getResultCode();	
	}
	
	public function getLock($Key, $WaitMS = 0, $TtlSec = 300)
		{
		$exkey = "::__LoCk__::$Key";
		if($WaitMS == 0)
			return $this->add($exkey, '*', 0, $TtlSec);	
		$res = false;
		$timewait = 0; 
		$timedelta = 25;
		while(($res === false) &&  ($timewait < $WaitMS) )
		{
	    $res = $this->add($exkey, '*', 0,  $TtlSec);
	    $timewait = $timewait + $timedelta;
	    if(!$res) { usleep( $timedelta * 1000); } 
	  }
		return $res;
		}

  	public function releaseLock($key) {
  		$exkey = "::__LoCk__::$key";
  		$this->delete($exkey, 0);
  	}
  	
  	public function &getStats($Id=null) {
  		$r = $this->Pool->getStats();				
  		if(isset($Id))
  			$r = &$r[$Id];
			return $r;
  	}
    
	}	
	
	class MemCachedPoolsFailSafe
	{
	} 
