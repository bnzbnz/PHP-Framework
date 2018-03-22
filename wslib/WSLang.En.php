<?php


class WSLang
{
	  
  static public function get($id, $params = null)
  {
		$tt = self::transtable();
		$str = $tt[$id]; 
		if (isset($str))
			return utf8_encode(vsprintf($str,  $params));
		else
			return "";
	}
	
	static private function transtable()
	{
		return
			array(
				'101' 	=> 'Property %s is required',
				'102' 	=> 'Property %s is empty', 
				'103' 	=> 'Property %s is too short', 
				'104' 	=> 'Property %s is too long',  
				'105' 	=> 'Invalid email address',
				'106' 	=> 'Invalid IP',
				'107' 	=> 'Invalid MD5',
				'108' 	=> 'Property %s is invalid',
				'109' 	=> 'Condition is False',    
				'110'   => 'Not implemented',
				'111'   => 'Deprecated',
				'200' 	=> 'Server busy',
				'201' 	=> 'Invalid Async. RequestId unknown', 
				'202' 	=> 'Async. RequestId processing',
				'203'   => 'A property is required' ,
				'210'	=> 'Aysnchronous requests not enabled',
				'211'	=> 'Invalid Credentials',
				'1000'	=> 'Application specific messages start here...'
			);
	}
}

	
?>
