<?php
	
	define("CACHE_NONE", 0);
	define("CACHE_PROCESS", 1);
	define("CACHE_MEMCACHE", 4);
	define("CACHE_INFINITE", -1);
	define("CACHE_NOLOCALRECACHE", 128);
	define("CACHE_ALL", 255 - CACHE_NOLOCALRECACHE) ;

	class CacheMgr
	{
		private $_warmupcallback = null;
		private $_icache = array();
		private $_useicache = true;
		private $_memcache = null;
		
		public function Init( $MemcacheInstance, $UseICache = true, $WarmUpTag = '')
		{
				$this->_memcache			 = $MemcacheInstance;
				$this->_useicache			 = $UseICache;
				$this->_warmuptag  		 = $WarmUpTag;
		}
		
		public function WarmUp($warmupcallbackfunc, $ForcedWarmUp = false)
		{
			$this->_warmupcallback = $warmupcallbackfunc;
			$tmp = $this->_memcache->Get($this->_warmuptag."CacheVersion"); 
			if ( (!isset($tmp) || $ForcedWarmUp) && isset($this->_warmupcallback))
			{	
					$continue = false;		
		 			if ( $this->_memcache->Add($this->_warmuptag."CacheIsWarmingUp", 0, -1) )
		 			{
		 				try
		 				{
		 					if(isset($this->_warmupcallback))
		 						$continue = call_user_func_array($this->_warmupcallback, array()); 
		 				} catch (Exception $ex)  { }
		 				$this->_memcache->Add($this->_warmuptag."CacheVersion", 0, -1);
		 				$this->_memcache->Delete($this->_warmuptag."CacheIsWarmingUp");
		 			}
		 	}
		}
		
		public function Ready()
		{
			return isset($this->_memcache);	
		}
		
		public function ClearLocalCache()
		{
			$this->_icache = null;
			$this->_icache = array();
		}
			 
		private function &getCacheBlock($Value, $ttl, $CacheType)
		{
			$CacheBlock = array();
			$CacheBlock['DT'] = serialize($Value);
			$CacheBlock['TO'] = $CacheType;
			$CacheBlock['TL'] = $ttl;
			$CacheBlock['CD'] = time();
			return $CacheBlock;	
		}
		
		public function Set($Key, $Value, $ttl = 0, $Compress = false, $CacheType = CACHE_ALL, $Notify = false)
	  {
			$CacheBlock = $this->getCacheBlock($Value, $ttl, $CacheType);
			if(($CacheType & CACHE_MEMCACHE) == CACHE_MEMCACHE)
				$this->_memcache->Set($Key, $CacheBlock, $Compress ? MEMCACHE_COMPRESSED : 0, $ttl);
			if((($CacheType & CACHE_PROCESS) == CACHE_PROCESS) && $this->_useicache)
			{
				$this->_icache[$Key] = $CacheBlock;
			} else {
				unset($this->_icache[$Key]);
        unset($CacheBlock); 
			}
		}
		
		public function SetArray($KeyHeader, &$KeyArray, $ttl = 0, $Compress = false, $CacheType = CACHE_ALL)
		{
			if(!is_array($KeyArray)) 
				 throw new Exception('Cache GetArray, KeyArray must be an array of key.');	
			foreach($KeyArray as $Key => $Value) 
				$this->Set($KeyHeader.$Key, $Value, $ttl, $Compress, $CacheType);
		}
				
		public function Flush($Key, $CacheType = CACHE_ALL, $Notify = true)
	  {
			if((($CacheType & CACHE_PROCESS) == CACHE_PROCESS) && $this->_useicache) 
				$this->_icache[$Key] = null;
			if(($CacheType & CACHE_MEMCACHE) == CACHE_MEMCACHE)
				$this->_memcache->Delete($Key);
		}
				
		private function &GetFromLocalCache($Key)
		{
			$ret = null;
			if(!$this->_useicache) { return $ret; }  
			$v = isset($this->_icache[$Key]) ? $this->_icache[$Key] : null;
			if ($v !== null) 
				$ret = unserialize($v['DT']);	
			return $ret;
		}
		
		private function &GetFromMemCache($Key, $CacheType)
		{
			$ret = null;
			$v = $this->_memcache->Get($Key);
			if  (isset($v))
			{
				if(($this->_useicache) && ($CacheType & CACHE_NOLOCALRECACHE) == 0)
				{
					$this->_icache[$Key] = &$v;
				} else {
					unset($this->_icache[$Key]);
        	unset($CacheBlock); 
				}
				$ret = unserialize($v['DT']);	
			}					
			return $ret;
		}
		
		public function &Get($Key, $CacheType = CACHE_ALL)
		{
			$ret = null;
			if(($CacheType & CACHE_PROCESS) == CACHE_PROCESS)
			{
				$ret = $this->GetFromLocalCache( $Key);
				if($ret !== null) { return $ret; }
			}
			if(($CacheType & CACHE_MEMCACHE) == CACHE_MEMCACHE)
			{			
				$ret = $this->GetFromMemCache( $Key, $CacheType);
				if($ret !== null) { return $ret; }
			}
			return $ret;
		}
		
		public function GetArray($KeyHeader, &$KeyArray, &$ResArray, &$MissArray, $CacheType = CACHE_ALL)
		{
			
			if(!is_array($KeyArray)) 
				 throw new Exception('Cache GetArray, KeyArray must be an array of key.');
 
			$memcacheids = array();
			$KeyHeaderLen = strlen($KeyHeader);
			foreach($KeyArray as $Key)
			{
					$v = null;
					if(($v === null) && (($CacheType & CACHE_PROCESS) == CACHE_PROCESS))
					{
						$v = $this->GetFromLocalCache($KeyHeader.$Key);
						if ($v !== null)
						 $ResArray[$Key] = $v;
				 } 
				 if ($v === null) { $memcacheids[] = $KeyHeader.$Key; }
			}
			if ((($CacheType & CACHE_MEMCACHE) == CACHE_MEMCACHE) && (count($memcacheids)>0))
			{
				$tmpArray = $this->_memcache->Get($memcacheids);	
				if (count($tmpArray)>0)
					foreach($tmpArray as $Key => &$v)
						if ($KeyHeaderLen>0)
							$ResArray[substr($Key, strlen($KeyHeader))] = $v;
						else
							$ResArray[$Key] = $v;
			}
			
			foreach($KeyArray as $Key)
				if(!array_key_exists($Key, $ResArray)) { $MissArray[] = $Key; }
		}
		
		// Counter
		
		 private function IncDecCounter( $CounterName, $IncDec, $Offset)
		{
			 
			if($IncDec >= 0)
			{
				if(!$this->_memcache->Increment($CounterName, $IncDec))
				{
					$Default = $Offset + $IncDec < 0 ? 0 : $Offset + $IncDec;
					if(!$this->_memcache->Add($CounterName, $Default, -1))
						$this->_memcache->Increment($CounterName, $IncDec);
				}
			} 
				else
			{
				  
				if(!$this->_memcache->Decrement($CounterName, -$IncDec))
				{
					$Default = $Offset + $IncDec < 0 ? 0 : $Offset + $IncDec;
					if(!$this->_memcache->Add($CounterName, $Default, -1))
						$this->_memcache->Decrement($CounterName, -$IncDec);
				}
			}
			$this->Flush($CounterName, true, CACHE_PROCESS);
			return true;
		}
		
		 private function GetCounter( $CounterName)
		{
			$Cnt = null;
			if($this->_useicache) $Cnt = isset($this->_icache[$CounterName]) ? $this->_icache[$CounterName] : null;
			if ($Cnt === null)
			{
				$Cnt = $this->_memcache->Get($CounterName);
				if($Cnt !== null)
					if($this->_useicache) $this->_icache[$CounterName] = $Cnt;
			}					
			return $Cnt;
		}
		
		 public function IncSCounter($CounterName, $Increment = 1)
		{
			return $this->IncDecCounter($CounterName, $Increment, 0x7FFFFFF);
		}
		
		 public function IncUCounter($CounterName, $Increment = 1)
		{
			return $this->IncDecCounter($CounterName, $Increment, 0);
		}
		
		 public function DecSCounter($CounterName, $Decrement = 1)
		{
			return $this->IncDecCounter($CounterName, -$Decrement, 0x7FFFFFF);
		}

		 public function DecUCounter($CounterName, $Decrement = 1)
		{
			return $this->IncDecCounter($CounterName, -$Decrement, 0);
		}		
				
		 public function GetSCounter($CounterName)
		{
			$Cnt = $this->GetCounter($CounterName);
			if ($Cnt === null) { return 0; }
			return $Cnt - 0x7FFFFFF;
		}
		
		 public function GetUCounter($CounterName)
		{
			$Cnt = $this->GetCounter($CounterName);
			if ($Cnt === null) { return 0; }
			return $Cnt;
		}
	
		 public function FlushCounter($CounterName)
		{
			$this->Flush($CounterName, CACHE_ALL);
		}	
						
		 public function GetLock($Key, $CacheType = CACHE_MEMCACHE, $WaitMS = 0, $TtlSec = 300)
		{
			if ($CacheType == CACHE_MEMCACHE)
			{
				return $this->_memcache->GetLock($Key, $WaitMS, $TtlSec);	
			} 
			else
				throw new MyException('Invalid CacheType for cachemgr:GetLock');	
		}
	
		 public function ReleaseLock($Key, $CacheType = CACHE_MEMCACHE)
		{
			if ($CacheType == CACHE_MEMCACHE)
			{
				return $this->_memcache->ReleaseLock($Key);	
			} 
			else
				throw new MyException('Invalid CacheType for cachemgr:GetLock');	
		}
}
	
?>
