<?php

//////////////////////////
// To be Translated...	//
// A traduire...		//
//////////////////////////


class Language
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
				'101' 	=> 'Property %s is required.',
				'102' 	=> 'Property %s is empty.', 
				'103' 	=> 'Property %s is too short.', 
				'104' 	=> 'Property %s is too long.',  
				'105' 	=> 'Invalid email address.',
				'106' 	=> 'Invalid IP.',
				'107' 	=> 'Invalid MD5.',
				'108' 	=> 'Property %s is invalid.',
				'109' 	=> 'Condition is False',    
				'110'   => 'Not implemented',
				'111'   => 'Deprecated',
				'200' 	=> 'Server busy.',
				'201' 	=> 'Invalid Async. RequestId unknown', 
				'202' 	=> 'Async. RequestId processing',
				'203'   => 'A property is required.' ,
				'204'   => 'Value %s not handled in switch', //for switch statements
				'205'   => 'Query %s returned no records', //generic query error
				'206'   => 'Failed to acquire def id %s',     //generic "failed to get a def" exception
				'207'   => 'Failed to acquire obj id %s',     //generic "failed to find an obj (e.g. factory->GetById failed)" exception			
				'208'   => 'Bad parameter %s',                //generic "someone passed a bad parameter"
				'209'   => 'Failed to acquire lot id %s',     //generic "failed to find an lot (e.g. factory->GetById failed)" exception
				'210'	=> 'Aysnchronous request not available.',
				'211'	=> 'Invalid Credentials'
			);
	}
}

	
?>
