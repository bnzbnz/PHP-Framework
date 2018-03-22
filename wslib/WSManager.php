<?php

define("WS_NONE", 0);
define("WS_XML", 1);
define("WS_REST", 1);
define("WS_SOAP", 2);
define("WS_JSON", 4);
define("WS_PHP", 8);
define("WS_PHPT", 16);
define("WS_ALL", 255) ;
	
class WS_Xml
{ 

    private function GetaArray(&$Xml,$arrayValue, $PropName) 
    { 
        foreach ($arrayValue as $Member) 
		{
			if(is_object($Member)) 
			{
				$this->SerializeClass($Xml, $Member,get_class($Member), $PropName);
			} else {
				$Xml.="<".$PropName.">".$Member."</".$PropName.">";  
			}
		}
    } 

    public function &Serialize($ObjectInstance) 
    { 
    	$Xml = "";
		$ClassName=get_class($ObjectInstance);
     	$this->SerializeClass($Xml, $ObjectInstance, $ClassName, $ClassName); 
    	return $Xml;
    } 

    public function SerializeClass(&$Xml, $ObjectInstance, $ClassName, $PropName = "") 
    { 
        $Data = "";
        $Class=new ReflectionClass($ClassName); 
        $ClassArray= ((array)$ObjectInstance); 
        $Properties=$Class->getProperties(); 
        $i=0;
        if($PropName != "") 
        	$Xml.="<".$PropName.">";  
        foreach ($ClassArray as $ClassMember) 
        { 
            if(isset($ClassMember))
			{
            	$prpName= $Properties[$i]->getName(); 
            	
            	$prpType= gettype($ClassMember); 

            	if ($prpType=='object') 
            	{ 
            		$Xml.="<".$prpName.">"; 
					$serializerinstance= new WS_Xml(); 
					$serializerinstance->SerializeClass($Xml, $ClassMember, get_class($ClassMember)); 
					$Xml.="</".$prpName.">"; 
            	} 
            	elseif ($prpType=='array') 
            	{ 
					$this->GetaArray($Xml, $ClassMember, $prpName); 
            	} 
            	else 
            	{
					$Xml.="<".$prpName.">".utf8_encode($ClassMember)."</".$prpName.">"; 
 	          	}
 	        } 	
           	$i++; 
        } 
      if($PropName != "") 
        	$Xml.="</".$PropName.">"; 
    } 

    public function WriteXmlFile($XmlData, $FilePath) 
    { 
        $Xml = simplexml_load_string($XmlData); 
        $Doc=new DOMDocument(); 
        $Doc->loadXML($Xml->asXML()); 
        $Doc->save($FilePath); 
    } 
    public function DeserializeClass($FilePath) 
    { 
		$Xml=simplexml_load_file($FilePath); 
		return $this->Deserialize($Xml); 
    } 
    public function Deserialize($Root) 
        { 
                $result=null; 
                $counter=0; 
        foreach ($Root as $member) 
        { 
                $instance = new ReflectionClass($member->getName()); 
                $ins=$instance->newInstance(); 
                foreach ($member as $child) 
                { 
                        $rp=$instance->getMethod("set_".$child->getName()); 
                        if (count($child->children())==0) 
                        { 
                                $rp->invoke($ins,$child); 
                        } 
                        else 
                        { 
                                $rp->invoke($ins,$this->Deserialize($child->children())); 
                                echo $child; 
                        } 
                } 
                if (count($Root)==1) { 
                        return $ins; 
                } 
                else 
                { 
                        $result[$counter]=$ins; 
                        $counter++; 
                } 
                if ($counter==count($Root)) { 
                        return $result; 
                } 
        } 
        } 

} 

class WS_WSDLGenFuncInfo
{
	public $Name 	= "";
	public $ParamName 	= "";
	public $ParamType 	= "";
	public $ResName 	= "";
	public $ResType 	= "";
	public $Comment = "";
}

class WS_WSDLGenPropInfo
{
	public $Name 	= "";
	public $Type 	= "";
	public $Array = False;
	public $Required = False;
	public $Comment = "";
}

class WS_WSDLGenTypeInfo
{
	public $Type = "";
	public $SoapType = False;
	public $Herit = "";
	public $Props;
	function __construct() { $this->Props = Array(); }
	function init($_Type, $_SoapType)
	{
		$this->Type = $_Type;
		$this->SoapType = $_SoapType;
		return $this;
	}
}

class WS_WSDLGenEnumInfo
{
	public $Type = "";
	public $Values;
	function __construct() { $this->Values = Array(); }
}

class WS_WSDLGenerator
{
	public $_ServiceClassName;
	public $_ServiceFunctions;
	public $_ServiceTypes;
	public $_ServiceEnums;
	public $_DefaultLocation;
	public $_DefaultNamespace;
	public $_ws;
	public $_AclLevel;
	public $_types = array();
	public $_enums = array();
	public $_tokens = array();
	public $_funcs = array();
	
	function __construct($ServiceClassName, &$ServiceFunctions, &$ServiceTypes, &$ServiceEnums, $DefaultLocation, $DefaultNamespace, $AclLevel, $DefaultTypes)
	{
		//init soap default type;
		
		foreach($DefaultTypes as $key => $value)
		{
			$tmp =  new WS_WSDLGenTypeInfo(); 
			$this->_types[$key] = $tmp->init($value, true);
		}
		
		$this->_ServiceClassName 	= $ServiceClassName;
		$this->_DefaultLocation 	= $DefaultLocation;
		$this->_DefaultNamespace  	= $DefaultNamespace;
		$this->_ServiceFunctions 	= &$ServiceFunctions;
		$this->_ServiceTypes 		= &$ServiceTypes;
		$this->_ServiceEnums 		= &$ServiceEnums;
		$this->_AclLevel 			= $AclLevel;
		
		
		foreach ($this->_ServiceFunctions as $funcname => $def) 
		{
			$facllevel = isset($def['AclLevel']) ? $def['AclLevel'] : 100;
			if ($facllevel <= $AclLevel)
			{
				$fc = new WS_WSDLGenFuncInfo();
				$fc->Name = $funcname;
				$this->_funcs[$fc->Name] = $fc;
	
				$fc->ParamName = $def['InName'];
				$fc->ParamType = $def['InType'];
				$fc->ResName = $def['OutName'];
				$fc->ResType = $def['OutType'];
				$fc->Comment = '';			

				$this->getType($fc->ParamType);
				$this->getType($fc->ResType);	
			}
		}
		
		/*
		foreach ($ServiceEnums  as $TypeName => $dummy) 
			$this->getEnum($TypeName);
		
		foreach ($this->_ServiceTypes  as $TypeName => $dummy) 
			$this->getType($TypeName);	
		*/
	}
	
	function getType($ctname)
	{
		
		if (isset($this->_types[$ctname])) { return 0; }
		if ($ctname=="WS_Type") { return 0; }
		
		if ( !(isset($this->_types[$ctname])) )
		{
			$tinfo 	= new WS_WSDLGenTypeInfo();
			$this->_types[$ctname] = $tinfo;	
		
			$tinfo->Type  = $ctname;
			$tclass = new ReflectionClass($ctname);
			$pclass = $tclass->getParentClass(); 
			if ($pclass != False)
			{
				if($pclass->getName()!="WS_Type")
				{
					$tinfo->Herit = $pclass->getName();
					$this->getType($tinfo->Herit);
				}
			} 
			$propsdef = $this->_ServiceTypes[$ctname];

			
			foreach ($propsdef  as $propname => $prop) 
			{
				
				$facllevel =  isset($prop['acllevel']) ? $prop['acllevel'] : 100;
				if ($facllevel <= $this->_AclLevel)    
				{
				
  				$pinfo = new WS_WSDLGenPropInfo();
  				$pinfo->Name = $propname;
  				$tinfo->Props[$pinfo->Name] = $pinfo;
  				
  				if ($prop['type']=="array")
  				{
  					$pinfo->Array = True;
  					$pinfo->Type = $prop['class']; 
  				} elseif ($prop['type']=="object")
  				{
  					$pinfo->Type = $prop['class']; 
  				} else
  					$pinfo->Type = $prop['type'];
  				// Options
  				if(isset($prop['required']) && $prop['required']==true) { $pinfo->Required = true; }
  				if(isset($prop['comment'])) { $pinfo->Comment = $prop['comment']; }

					if ($pinfo->Type != "enum")
					{
	  				$this->getType($pinfo->Type);
	  			} else {
	  				$pinfo->Type = $prop['class'];
	  				$this->getEnum($pinfo->Type);
	  			}	
				}
			}
		}
	}
	
	
	function getEnum($ctname)
	{
		if (isset($this->_enums[$ctname])) { return 0; }
		$enum = new WS_WSDLGenEnumInfo(); 
		$this->_enums[$ctname] = $enum;
		$propsdef = $this->_ServiceEnums[$ctname]; 
		foreach ($propsdef  as $propname => $prop) 
		{			
			$pinfo = new WS_WSDLGenPropInfo();
			$pinfo->Type = $ctname;
			$pinfo->Default = $propname;
			if(isset($prop['comment']))
				{ $pinfo->Comment = $prop['comment']; } 
			$enum->Values[] = $pinfo;
		}
	}
	
	public function getWSDL()
	{
		if (count($this->_enums)>0) { ksort($this->_enums); }
		if (count($this->_types)>0) { ksort($this->_types); }
		if (count($this->_funcs)>0) { ksort($this->_funcs); }
		
		$xml = "";
		
		// $xml.='<wsdl:definitions xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:wsdlsoap="http://schemas.xmlsoap.org/wsdl/soap/" xmlns:ns="'.$this->_DefaultNamespace.'" xmlns="'.$this->_DefaultNamespace.'" targetNamespace="'.$this->_DefaultNamespace.'">'; $xml.="\r\n";
		$xml.='<wsdl:definitions xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:wsdlsoap="http://schemas.xmlsoap.org/wsdl/soap/" xmlns:ns="'.$this->_DefaultNamespace.'" xmlns="'.$this->_DefaultNamespace.'" targetNamespace="'.$this->_DefaultNamespace.'">'; $xml.="\r\n";
		//Types
		$xml.='<wsdl:types>'; $xml.="\r\n";
		$xml.='<xs:schema targetNamespace="'.$this->_DefaultNamespace.'" xmlns:ns="'.$this->_DefaultNamespace.'" xmlns="'.$this->_DefaultNamespace.'" xmlns:xmime="http://www.w3.org/2005/05/xmlmime" xmlns:xs="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified" attributeFormDefault="unqualified">'; $xml.="\r\n";
		
		// Enum (Simple Type)
	  if (count($this->_enums)>0) 
		foreach($this->_enums as $enumname => $enum)
		{
			$xml.='<xs:simpleType name="'.$enumname.'">'; $xml.="\r\n";
			$xml.='<xs:restriction base="xs:token">'; $xml.="\r\n";
			foreach($enum->Values as $tp)
			{
				$xml.='<xs:enumeration value="'.$tp->Default.'">'; $xml.="\r\n";
				if(isset($tp->Comment))
				{
					$xml.='<xs:annotation><xs:documentation>'; $xml.="\r\n";
					$xml.=$tp->Comment; $xml.="\r\n";
					$xml.='</xs:documentation></xs:annotation>'; $xml.="\r\n";
				}
				$xml.='</xs:enumeration>'; $xml.="\r\n";
			}
			$xml.='</xs:restriction>'; $xml.="\r\n";
			$xml.='</xs:simpleType>'; $xml.="\r\n";
		}
			
		// Complex type
		if (count($this->_types)>0) 
		foreach($this->_types as $tp)
		{
			if ($tp->SoapType == False)
			{
				$xml.='<xs:complexType name="'.$tp->Type.'">'; $xml.="\r\n";
				
				if ($tp->Herit != "")
				{
							$xml.='<xs:complexContent>'; $xml.="\r\n";
							$xml.='<xs:extension base="ns:'.$tp->Herit.'">'; $xml.="\r\n";
							
				}
				$xml.='<xs:sequence>'; $xml.="\r\n";
				foreach($tp->Props as $pp)
				{
					$ele = '<xs:element name="'.$pp->Name.'"';
					if ($this->_types[$pp->Type]->SoapType == False) 
					{ 
						$ele.=' type="ns:'.$pp->Type.'"'; 
					} else { 
						$ele.=' type="xs:'.$this->_types[$pp->Type]->Type.'"'; 
					}
					if ($pp->Required == True) { $ele.=' minOccurs="1" '; } else { $ele.=' minOccurs="0" '; }
					if ($pp->Array == True) { $ele.=' maxOccurs="unbounded" '; } else { $ele.=' maxOccurs="1" '; }
					$ele.=" >"; 
					$xml.=$ele;  $xml.="\r\n";
					
					if($pp->Comment!="")
					{
						$xml.='<xs:annotation>';  $xml.="\r\n";
						$xml.='<xs:documentation>';  $xml.="\r\n";
						$xml.=$pp->Comment;  $xml.="\r\n";
						$xml.='</xs:documentation>';  $xml.="\r\n";
						$xml.='</xs:annotation>';  $xml.="\r\n";
					}
					
					$xml.='</xs:element>'; $xml.="\r\n";
					
				}
				$xml.='</xs:sequence>'; $xml.="\r\n";
				if ($tp->Herit != "")
				{
					
					$xml.='</xs:extension>'; $xml.="\r\n";
					$xml.='</xs:complexContent>'; $xml.="\r\n";
				}
				
				$xml.='</xs:complexType>'; $xml.="\r\n";
			}
		}
		// define function elements

		$ParamExists = array();
		if (count($this->_funcs)>0) 
		foreach($this->_funcs as $fc)
		{
			if (!isset($ParamExists[$fc->ParamName]))
			{
				$xml.='<xs:element name="'.$fc->ParamName.'" type="ns:'.$fc->ParamType.'" />';$xml.="\r\n"; 
				$ParamExists[$fc->ParamName]=true;
			}
			if (!isset($ParamExists[$fc->ResName]))
			{
				$xml.='<xs:element name="'.$fc->ResName.'" type="ns:'.$fc->ResType.'" />';$xml.="\r\n"; 
				$ParamExists[$fc->ResName]=true;
			}
		}
		
		$xml.='</xs:schema>';$xml.="\r\n"; 
		$xml.='</wsdl:types>'; $xml.="\r\n";
		
		//messages
		$ParamExists = array();
		if (count($this->_funcs)>0)
		foreach($this->_funcs as $fc)
		{
			if (!isset($ParamExists[$fc->ParamName]))
			{
				$xml.='<wsdl:message name="'.$fc->ParamName.'">'; $xml.="\r\n";
				$xml.='<wsdl:part name="'.$fc->ParamName.'" element="ns:'.$fc->ParamName.'" />'; $xml.="\r\n"; 
				$xml.='</wsdl:message>'; $xml.="\r\n";
				$ParamExists[$fc->ParamName]=true;
			}
			if (!isset($ParamExists[$fc->ResName]))
			{
				$xml.='<wsdl:message name="'.$fc->ResName.'">'; $xml.="\r\n";
				$xml.='<wsdl:part name="'.$fc->ResName.'" element="ns:'.$fc->ResName.'" />'; $xml.="\r\n"; 
				$xml.='</wsdl:message>'; $xml.="\r\n";
				$ParamExists[$fc->ResName]=true;
			}
		}

		//portype
		$xml.='<wsdl:portType name="'.$this->_ServiceClassName.'Interface">'; $xml.="\r\n";
		if (count($this->_funcs)>0)
		foreach($this->_funcs as $fc)
		{
			$xml.='<wsdl:operation name="'.$fc->Name.'">'; $xml.="\r\n";
			$xml.='<wsdl:input message="ns:'.$fc->ParamName.'" />'; $xml.="\r\n"; 
			$xml.='<wsdl:output message="ns:'.$fc->ResName.'" />'; $xml.="\r\n"; 
			$xml.='</wsdl:operation>'; $xml.="\r\n";
		}  
		$xml.='</wsdl:portType>'; $xml.="\r\n";
		//binding
		$xml.='<wsdl:binding name="'.$this->_ServiceClassName.'Binding" type="ns:'.$this->_ServiceClassName.'Interface">'; $xml.="\r\n";
		$xml.='<wsdlsoap:binding style="document" transport="http://schemas.xmlsoap.org/soap/http" />'; $xml.="\r\n"; 
  	
  	if (count($this->_funcs)>0)
		foreach($this->_funcs as $fc)
		{
			
  	$xml.='<wsdl:operation name="'.$fc->Name.'">'; $xml.="\r\n";
  	$xml.='<wsdlsoap:operation soapAction="" />'; $xml.="\r\n";  
		$xml.='<wsdl:input>'; $xml.="\r\n";
  	$xml.='<wsdlsoap:body use="literal" />'; $xml.="\r\n"; 
  	$xml.='</wsdl:input>'; $xml.="\r\n";
		$xml.='<wsdl:output>'; $xml.="\r\n";
		$xml.='<wsdlsoap:body use="literal" />'; $xml.="\r\n"; 
		$xml.='</wsdl:output>'; $xml.="\r\n";
		$xml.='</wsdl:operation>'; $xml.="\r\n";
		
  	}
  	
  	$xml.='</wsdl:binding>'; $xml.="\r\n";
		//define service
		$xml.='<wsdl:service name="'.$this->_ServiceClassName.'">'; $xml.="\r\n";
		$xml.='<wsdl:port binding="ns:'.$this->_ServiceClassName.'Binding" name="'.$this->_ServiceClassName.'">'; $xml.="\r\n";
		$xml.='<wsdlsoap:address location="'.$this->_DefaultLocation.'" />'; $xml.="\r\n";
		$xml.='</wsdl:port>'; $xml.="\r\n";
		$xml.='</wsdl:service>';$xml.="\r\n";

		$xml.="</wsdl:definitions>";$xml.="\r\n";
		
		// header;
		$xml = utf8_encode('<?xml version="1.0" encoding="UTF-8" ?>'."\r\n".$xml); 
		return $xml;
	}
}

function WS_ifsetor(&$var, $default)
{
	return  ( isset($var) ? $var : $default );
}

class WS_Exception extends Exception 
{ 
		public $Errors = array();
		
		public function addError($code, $key, $info1, $info2)
		{
			$this->Errors[] = array($code, $key, $info1, $info2);
		}

		public function __construct($code = 0 , $key = "", $info1 = "", $info2 = "") 
		{
    	if($code != 0)
    		$this->addError($code, $key, $info1, $info2);
    		
      $val = $key;
      if ( $info1 != null || $info1 != '' ) $val .= '-'.$info1;
      if ( $info2 != null || $info2 != '' ) $val .= '-'.$info2;      
    	parent::__construct($val, $code);
    }
    public function hasErrors() { return ( count($this->Errors) >0 ); }

}

class WS_EnumType
{
	static public function WS_Values()
	{	
		return array();
	}	
	
	static public function WS_EnumValues($classname, $inherited = true)
	{
		$prop = array();
		$prop = array_merge(call_user_func(array($classname,'WS_Values')), $prop);
		return $prop; 
	}
}

class WS_Type
{

	static public function WS_Properties()
	{	
		return array();
	}	
	
	public function __destruct()
	{
			foreach($this as $key => $dum) 
				unset($this->$key); 
	} 
	
	public function WS_Conditions($condarray)
	{
		$Ex = new WS_Exception;
		$Cpt = 0;
		foreach ($condarray as $conf)
		{
			$Cpt = $Cpt +1;
			if ($cond !== true)
					$Ex->addError( 109, $Cpt, '', '' );				
		}
		if ($Ex->hasErrors()) { throw $Ex; }
				
	}
	public function WS_Requires($proparray)
	{
		$Ex = new WS_Exception;
		$props = &WS_Manager::$WSDef['rprops'][get_class($this)];
		foreach ($proparray as $key)
			if ( !isset( $this->{$key} ) )
				$Ex->addError( 101, $key, '', '' );				
		if ($Ex->hasErrors()) { throw $Ex; }
	}

	public function WS_RequiresOne($proparray)
	{
		$Ex = new WS_Exception;
		$props = &WS_Manager::$WSDef['rprops'][get_class($this)];
		foreach ($proparray as $key)
			if ( isset( $this->{$key} ) )
				return true;
		$Ex->addError( 203, implode(', ',$proparray), '', '' );			
		throw $Ex; 
	}
	
	public function WS_Validate($AclLevel = 0, $Ex = null)
	{
		
		if ($Ex != null) { $TEx = $Ex; } else { $TEx = new WS_Exception; } 
		
		$props = &WS_Manager::$WSDef['rprops'][get_class($this)];
		//$props = $this->WS_EnumProperties(get_class($this), true);
		foreach (array_keys($props) as $key)
		{
			
			$propinfo = &$props[$key];	
			$proptype  = &$propinfo['type'];
			
			// Soap bug does not call constructor of object : init arrays here
			if (($proptype == "array") && (!isset($this->{$key})))
				$this->{$key} = array();	

			if((isset($propinfo['default'])) && (!isset($this->{$key})))
				$this->{$key} = $propinfo['default'];

			if((isset($propinfo['required'])) && ($propinfo['required']==true) && (!isset($this->{$key})))
				$TEx->addError( 101, $key, '', '' );

			// Check AclLevel
			if($AclLevel<issetX($propinfo['acllevel'],0))
			{
				$this->{$key}=null;
			}
			//object
			elseif ($proptype == "object" && (isset($this->{$key})) )
			{
				if (!($this->{$key} instanceof $propinfo['class']))
				{
					$TEx->addError( 108, $key, '', '' );		
				}
				else
					$this->{$key}->WS_Validate($AclLevel, $TEx);
			}
			//enum
			elseif (($proptype == "enum") && (isset($this->{$key})))
			{
				$enumvalues = call_user_func(array($propinfo['class'], 'WS_EnumValues'), $propinfo['class']);
				if(!array_key_exists($this->{$key}, $enumvalues))
				{
					$TEx->addError( 108, $key, (string)$this->{$key}, '' );	
				}
			}
			//string
			elseif ( ($proptype == "string" || ($proptype == "str") ) && (isset($this->{$key})) )
			{
				if(!is_string($this->{$key}))
				{
					if (is_int($this->{$key})) { $this->{$key} = (string)$this->{$key}; }
					elseif (is_numeric($this->{$key}) && $this->{$key}<=8999999999999999) { $this->{$key} = sprintf ( "%.0f", $this->{$key} ) ; }
					if(!is_string($this->{$key}))
						$TEx->addError( 108, $key, '', '' );		
				}
				
				if ( (!empty($propinfo['notempty'])) && (trim($this->{$key})=="" ) ) 
				{
					$TEx->addError( 102, $key, '', '' );
				}	
				elseif ( (!empty($propinfo['minlength'])) && (strlen($this->{$key}) < $propinfo['minlength']) ) 
				{
					$TEx->addError( 103, $key, $propinfo['minlength'], '' );
				}
				elseif ( (!empty($propinfo['maxlength'])) && (strlen($this->{$key}) > $propinfo['maxlength']) )
				{ 
					$TEx->addError(104, $key, $propinfo['maxlength'], '' );
				}
				elseif ( (!empty($propinfo['emailaddress'])) && (!filter_var($this->{$key}, FILTER_VALIDATE_EMAIL)) )
				{
					$TEx->addError(105, $key, '', '' );
				}
				elseif ( (!empty($propinfo['ipv4'])) && (!filter_var($this->{$key}, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) )
				{
					$TEx->addError(106, $key, $this->{$key}, '' );
				}
				elseif ( (!empty($propinfo['md5'])) && (preg_match("#^[0-9a-fA-F]{32}$#i",$this->{$key}) == 0) )
				{
					$TEx->addError(107, $key, $this->{$key}, '' );
				}	
			}
			//integer
			elseif ( (($proptype == "int")  || ($proptype == "integer") || ($proptype == "long") || ($proptype == "longint")) && (isset($this->{$key})))
			{
				if(!is_int($this->{$key})) { $this->{$key} = (int) $this->{$key}; } 
				if(!is_int($this->{$key}))
				{
					$TEx->addError( 108, $key, (string)$this->{$key}, '' );	
				}
				else
				{
					if ( (!empty($propinfo['values'])) && (!array_key_exists($this->{$key}, $propinfo['values'])) )
					{
						$TEx->addError( 108, $key, (string)$this->{$key}, '' );		
					}
					elseif ( (!empty($propinfo['min'])) && ($this->{$key} < $propinfo['min']) )
					{
						$TEx->addError( 108, $key, (string)$this->{$key}, '' );		
					}
					elseif ( (!empty($propinfo['max'])) && ($this->{$key} > $propinfo['max']) )
					{
						$TEx->addError( 108, $key, (string)$this->{$key}, '' );		
					}
				}			
			}
			//boolean
			elseif ( (($proptype == "bool")  || ($proptype == "boolean")) && (isset($this->{$key})))
			{
				if(!is_bool($this->{$key}))
				{
					if(is_string($this->{$key}))
					{
						$tmp = strtolower($this->{$key});
						$this->{$key} = ( $tmp === 'true');
				  }
					else
					{
						$this->{$key} = (boolean)$this->{$key};
					}
					if(!is_bool($this->{$key}))
						$TEx->addError( 108, $key, (string)$this->{$key}, '' );	
				}
			}
			//float
			elseif ( ($proptype == "float") && (isset($this->{$key})) )
			{
				if(!is_float($this->{$key})) { $this->{$key} = (float) $this->{$key}; } 
				if(!is_float($this->{$key}))
				{
					$TEx->addError( 108, $key, (string)$this->{$key}, '' );	
				}
				else
				{
					if ((!empty($propinfo['decimals'])))
					{
						$this->{$key} =(float) number_format($this->{$key}, $propinfo['decimals']);	
					}
					if ( (!empty($propinfo['min'])) && ($this->{$key} < $propinfo['min']) )
					{
						$TEx->addError( 108, $key, (string)$this->{$key}, '' );		
					}
					elseif ( (!empty($propinfo['max'])) && ($this->{$key} > $propinfo['max']) )
					{
						$TEx->addError( 108, $key, (string)$this->{$key}, '' );		
					}
				}	
			}		
			elseif ( (($proptype == "double") || ($proptype == "number")) && (isset($this->{$key})) )
			{
				if(!is_float($this->{$key})) { $this->{$key} = (float) $this->{$key}; } 
				if(!is_float($this->{$key}))
				{
					$TEx->addError( 108, $key, (string)$this->{$key}, '' );	
				}
				else
				{
					if ((!empty($propinfo['decimals'])))
					{
						$this->{$key} =(double) number_format($this->{$key}, $propinfo['decimals']);	
					}
					if ( (!empty($propinfo['min'])) && ($this->{$key} < $propinfo['min']) )
					{
						$TEx->addError( 108, $key, (string)$this->{$key}, '' );		
					}
					elseif ( (!empty($propinfo['max'])) && ($this->{$key} > $propinfo['max']) )
					{
						$TEx->addError( 108, $key, (string)$this->{$key}, '' );		
					}
				}	
			}		
		}
		if ( ($Ex == null) && ($TEx->hasErrors()) ) { throw $TEx; }
	}
	
}

class WS_MainClass
{
	function __construct()
	{	
	}
	
	static public function WS_Functions()
	{	
		return array();
	}
}

class WS_Manager
{
	public static $WSDef			= null;
	public static $ReqParam			= null; 
	public static $classname		= "";
	public static $namespace 		= "";
	public static $acllevel	 		= 0;
	public static $credentials		= 0;
	public static $url				= "";
	public static $SOAPTypes 		= null;
	public static $getwsdlcallback	= null;
	public static $existwsdlcallback= null;
	public static $setwsdlcallback	= null;
	public static $soapclassmap		= null;
		
	private static function &wsTypedArrayToStdArray(&$typedArray, &$stdArray)
	{
		foreach($typedArray as $typedArrayValue)
		{
			if (is_object($typedArrayValue))
			{
				array_push($stdArray,  self::wsTypedObjectToStdObject($typedArrayValue,  new stdClass));
			} elseif (is_array($typedArrayValue))
			{
				$newarray = array();
				array_push($stdArray, self::wsTypedArrayToStdArray($typedArrayValue, $newarray));
			} else
			{
				array_push($stdArray, $typedArrayValue);
			}
		}
		return $stdArray;
	} 
	
	private static function wsTypedObjectToStdObject($typedObject, $stdObject)
  {
		if(!($typedObject instanceof WS_Type))
			throw new Exception("wsTypedObjectToStdObject Object type should be WSDL_Type :".get_class($typedObject));
		$tprops  = &self::$WSDef['rprops'][get_class($typedObject)];
				
		foreach(array_keys($tprops) as $tpropname)
		{
			$tpropvalue =  $typedObject->$tpropname;
			if (is_object($tpropvalue))
			{
				$stdObject->$tpropname =  self::wsTypedObjectToStdObject($tpropvalue,  new stdClass);
				
			} elseif (is_array($tpropvalue))
			{
				$newarray = array();
				$stdObject->$tpropname = self::wsTypedArrayToStdArray($tpropvalue, $newarray);
			} else
			{
			  $pType = $tprops[$tpropname]['type'];
				if (($pType === "base64") || ($pType === "base64binary"))
				{
					$stdObject->$tpropname = base64_encode($tpropvalue); 
				} else {
					$stdObject->$tpropname = $tpropvalue; 
				}
			}
		}
		return $stdObject;
	}
	
  private static function stdObjectToWsTypedObject($stdObject, $typedObject)
  {
		$sprops 		= get_object_vars($stdObject);
		$tpropsinfo = &self::$WSDef['rprops'][get_class($typedObject)];
	
		if($sprops !== false)
		foreach($sprops as $spropname => $spropvalue)
		{
			$spropname = ltrim(trim($spropname), '_');
			if (isset($spropvalue))
			{
				$tpropinfo = isset($tpropsinfo[$spropname]) ? $tpropsinfo[$spropname] : null;
				if ($tpropinfo != null)
				{
					$tpropinfotype = $tpropinfo['type'];
					if ($tpropinfotype == 'object') 
					{
						$typedObject->{$spropname} = self::stdObjectToWsTypedObject($spropvalue, new $tpropinfo['class']); 
					} 
					elseif ($tpropinfotype == 'array')
					{
							$tmpArray = array();
							foreach($spropvalue as $tmpStdObj)
							{
								if ( is_numeric($tmpStdObj) ) { $tmpArray[] = $tmpStdObj; } # Intended to deal with TNIntArrayType only, not ardvarks!
								else 
								{
									array_push(
										$tmpArray,
										self::stdObjectToWsTypedObject($tmpStdObj, new $tpropinfo['class'])
										);
								}
							}
							if ( (count($tmpArray)) > 0 ) 
								$typedObject->{$spropname} = $tmpArray;
					} 
					else
					{						
						if (($tpropinfotype === "base64") || ($tpropinfotype === "base64binary"))
						{
							$typedObject->{$spropname} = base64_decode($spropvalue);
						} else {
							$typedObject->{$spropname} = $spropvalue;
						}
					}
				}
			}
		}
		return $typedObject;
	}

  private static function PHPToTypedObject($phpData, $typedObject)
  {
  	return self::stdObjectToWsTypedObject( unserialize($phpData), $typedObject);
	}
		
	private static function JSONtoTypedObject($jsonData, $typedObject)
	{
		return self::stdObjectToWsTypedObject( json_decode($jsonData, false), $typedObject);
	}

	private static function RESTtoStdObject($restDataArray, &$stdObject)
	{		
		foreach($restDataArray as $restprop => $restvalue)
		{
				if($restvalue !== "")
				{
					$parts = explode('_', $restprop);
					switch (count($parts)) 
					{
    				case 0: 
    					break;
    				case 1:
    					$prop = $parts[0];
    					if($prop != "")
    						$stdObject->{$prop} = urldecode($restvalue);
    					break;
    				default:
    					$prop = $parts[0];
    					if($prop != "")
    					{
							if(!property_exists($stdObject, $prop))
							{
								$stdObject->{$prop} = new stdclass;
    						}
    						unset($parts[0]);
    						self::RESTtoStdObject(array(implode('_',$parts) => $restvalue), $stdObject->{$prop});
    					}
  				}
  			}
		}
		return $stdObject;
	}	
		
	private static function RESTtoTypedObject($restDataArray, $typedObject)
	{
		$Uobj = new stdclass;
		self::RESTtoStdObject($restDataArray, $Uobj);
		$typedObject = self::stdObjectToWsTypedObject( $Uobj, $typedObject);	
		return $typedObject;	
	}		
		
	private static function json_filter($jsonstr)
	{
	 $patterns = array( 
			'/\"\w*\":null/'
   		,	'/,(,+)/'
   		,	'/\{,/'
   		,	'/,\}/'
   );
   
	 $replacements = array( 
	 		''
   		,	','
   		,	'{'
   		,	'}'
   );
   		
   return preg_replace($patterns, $replacements, $jsonstr, -1, $cnt);
	}
	
	public static function &getTypeDef($typename)
	{
		return self::$WSDef['props'][$typename];
	}
	  
  public static function callParams() 
  { 
  	if(!isset(self::$ReqParam['_FunctionName'])) 
  	{
  		$data = debug_backtrace();
  		foreach($data as $trace)
  		{
  			if(isset(self::$WSDef['functions'][$trace["function"]]))
  			{
  				self::$ReqParam['_FunctionName'] = $trace["function"];
					self::$ReqParam['_FunctionInfo'] = self::$WSDef['functions'][$trace["function"]];
					break;
  			}
  		}
  	}
  	return self::$ReqParam; 
  }
   	
	private static function _getCallParams()
	{
		$wsdlparams 		= array();
		$wsdlparam			= "";
		self::$ReqParam = array();
		
		// get optional fields
		foreach($_GET as $key => $value)	
			//if(substr($key, 0, 1) == "_") 
			{ 
				self::$ReqParam[strtolower($key)] = urldecode($value);
				$wsdlparams[] = "$key=$value"; 
			}
		
		// get function name and info
		$params = explode('&', $_SERVER['QUERY_STRING']);
		foreach($params as $param)
		{
			if (isset(self::$WSDef['functions'][$param])) 
			{ 
				self::$ReqParam['_FunctionName'] = $param;
				self::$ReqParam['_FunctionInfo'] = self::$WSDef['functions'][$param];
				break;
			} 
			elseif (strtoupper($param)=='WSDL')
			{
				self::$ReqParam['_FunctionName'] = "WSDL";
				if (count($wsdlparams)>0)
					self::$ReqParam['_WSDLParams'] = '?'.implode('&', $wsdlparams);
				else
					self::$ReqParam['_WSDLParams'] = '';
				break;
			}
		}	
	}	
	
  private static function pProcess($compat)
  {
	$res	= null;
	$req	= null;
				
   	try
		{
		self::_getCallParams();
		if ( isset(self::$ReqParam['_FunctionName']) && (self::$ReqParam['_FunctionName']=="WSDL") )
		{	
			self::$acllevel = 0;
			self::$credentials = issetX(self::$ReqParam['credentials'], '');
			if (!call_user_funcX(self::$classname.'::webSvcOnValidateCredentials', array(self::$credentials, &self::$acllevel), true))
				return false;
			$key = "wsdl:".self::$url.":".self::$classname.":".self::$acllevel.":".self::$namespace;
			if( !call_user_funcX(self::$getwsdlcallback, array($key), false) )
			{	
					$wsdlgen = new WS_WSDLGenerator(
						self::$classname, 
						self::$WSDef['functions'],
						self::$WSDef['props'],
						self::$WSDef['enums'], 
						self::$url, 
						self::$namespace,
						self::$acllevel, 
						self::$SOAPTypes
					);
					$wsdl = $wsdlgen->getWSDL();
					$wsdlgen = null;
					call_user_funcX(self::$setwsdlcallback, array($key, &$wsdl, &$filename) );
					if( !call_user_funcX(self::$getwsdlcallback, array($key), false) )
					{
						unset($_REQUEST['Response']['Headers']['Pragma']);
						unset($_REQUEST['Response']['Headers']['Cache-Control']);
						unset($_REQUEST['Response']['Headers']['Expires']);
						$_REQUEST['Response']['Headers']['Content-Type']='text/xml;charset=utf-8';
						echo $wsdl;
					}	
			}
			return true;
		}
		 		
		if (($_SERVER['REQUEST_METHOD'] == 'GET') && (!isset(self::$ReqParam['_in']))) 
		{ 
				self::$ReqParam['_in'] = "REST"; 
		}	
		elseif (($_SERVER['REQUEST_METHOD'] == 'POST') && (!isset(self::$ReqParam['_in'])))
		{ 
				self::$ReqParam['_in'] = "SOAP"; 
		}						
	
		if ( (self::$ReqParam['_in'] != "SOAP" ) && (!isset(self::$ReqParam['_FunctionInfo'])) )
			throw new Exception('Function call unknown');

		// process REQUEST
		if ((self::$ReqParam['_in'] == "REST") && (($compat & WS_REST) == WS_REST)) 
		{	
			if (!isset(self::$ReqParam['_out'])) { self::$ReqParam['_out'] = "XML"; }
			$req = self::RESTtoTypedObject( $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $_GET, new self::$ReqParam['_FunctionInfo']['InType']);
		} elseif ((self::$ReqParam['_in'] == "XML") && (($compat & WS_XML) == WS_XML))
		{
			if (!isset(self::$ReqParam['_out'])) { self::$ReqParam['_out'] = "XML"; }
			$req = self::stdObjectToWsTypedObject(simplexml_load_string($_REQUEST['Body']), new self::$ReqParam['_FunctionInfo']['InType']); 
			} elseif ((self::$ReqParam['_in'] == "JSON") && (($compat & WS_JSON) == WS_JSON)) 
		{
			if (!isset(self::$ReqParam['_out'])) { self::$ReqParam['_out'] = "JSON"; }
			$req = self::JSONtoTypedObject($_REQUEST['Body'], new self::$ReqParam['_FunctionInfo']['InType']);
		} elseif ((self::$ReqParam['_in'] == "PHP") && (($compat & WS_PHP) == WS_PHP))
		{
			if (!isset(self::$ReqParam['_out'])) { self::$ReqParam['_out'] = "PHP"; }
			$req = self::PHPToTypedObject($_REQUEST['Body'], new self::$ReqParam['_FunctionInfo']['InType']);
		} elseif  ((self::$ReqParam['_in'] == "PHPT") && (($compat & WS_PHPT) == WS_PHPT)) 
		{
			if (!isset(self::$ReqParam['_out'])) { self::$ReqParam['_out'] = "PHPT"; }
			$req = self::PHPToTypedObject($_REQUEST['Body'], new self::$ReqParam['_FunctionInfo']['InType']);
		} elseif ((self::$ReqParam['_in'] == "SOAP") && (($compat & WS_SOAP) == WS_SOAP)) 
		{
			// SOAP RequestType
			self::$acllevel = 0;
			$Start = 11 + strpos($_REQUEST['Body'], 'redentials>');
			$Len = strpos($_REQUEST['Body'], '</', $Start) - $Start;
			if($Start==11) { $Start=0; $Len=0;}	
			self::$credentials = trim(substr($_REQUEST['Body'], $Start, $Len));
			if (!call_user_funcX(self::$classname.'::webSvcOnValidateCredentials', array(self::$credentials, &self::$acllevel), true))
				return false;
			// Soap
			$key = "wsdl:".self::$url.":".self::$classname.":".self::$acllevel.":".self::$namespace;
			if(!call_user_funcX(self::$existwsdlcallback, array($key, &$filename)))
			{
				$wsdlgen = new WS_WSDLGenerator(
						self::$classname, 
						self::$WSDef['functions'],
						self::$WSDef['props'],
						self::$WSDef['enums'], 
						self::$url, 
						self::$namespace,
						self::$acllevel, 
						self::$SOAPTypes
					);
				$wsdl = $wsdlgen->getWSDL();
				$wsdlgen = null;
				call_user_funcX(self::$setwsdlcallback, array($key, &$wsdl, &$filename) );
			}
			$server = new SoapServer($filename, array('classmap' => self::$soapclassmap, 'encoding'=>'UTF-8', 'cache_wsdl' => WSDL_CACHE_MEMORY));			
			$server->setClass(self::$classname);             
 			$_REQUEST['Response']['Headers']['Content-Type']='text/xml;charset=utf-8';
			$server->handle($_REQUEST['Body']);
			return true;
		}
		else
			throw new Exception('WEBManager Unsupported Format...');	
	
		// CALL
		if (!isset($req))
		 		throw new Exception('Unknown function call1.');

		$res = call_user_func(array(self::$classname, self::$ReqParam['_FunctionName']), $req);

		if (!isset($res))
		 		throw new Exception('Unknown function call2.');
					
    // Process RESPONSE	
		if (self::$ReqParam['_out'] == "XML")
		{
			$xml= new WS_Xml(); 
			$_REQUEST['Response']['Headers']['Content-Type'] = 'text/xml;charset=utf-8';
			echo('<?xml version="1.0" encoding="UTF-8" ?>');
			echo $xml->Serialize($res);
			return true;
		} elseif  (self::$ReqParam['_out'] == "JSON")
		{
			$ures = new stdClass(); 
			$ures = self::wsTypedObjectToStdObject($res, $ures);
			$_REQUEST['Response']['Headers']['Content-Type'] = 'application/json';
			echo self::json_filter(UTF8_encode(json_encode($ures)));
			return true;
		} elseif	(self::$ReqParam['_out'] == "PHP")
	 	{
	 		$_REQUEST['Response']['Headers']['Content-Type'] = 'text/plain';
			$ures = new stdClass(); 
			$ures = self::wsTypedObjectToStdObject($res, $ures);
			echo serialize($ures); 
			return true;
		} elseif	(self::$ReqParam['_out'] == "PHPT")
	 	{
	 		$_REQUEST['Response']['Headers']['Content-Type'] = 'text/plain';
			echo serialize($res); 
			return true;
		}
	
	} 
		catch(Exception $ex)
	{
		syslogX('WS_GLOBAL_EXCEPTION message = '. $ex->getMessage());
		stacktraceX();
		$_REQUEST['Response']['Headers'] = array();
		$_REQUEST['Response']['StatusCode'] = '400 Bad Request';
		echo $_REQUEST['Response']['StatusCode'].' : '.$ex->getMessage();
		return true;
	} 
				
	} 
  public static function registerEnum($type) 
  { 
  	self::$WSDef['enums'][$type] = call_user_func(array($type, 'WS_EnumValues'), $type); 
  }
  		
	public static function Process($url, $acllevel=0, $compat=WS_ALL)	
	{
		self::$acllevel = $acllevel;
		self::$url = $url;
		self::pProcess($compat);
	}
	
	public static function Init($classname, $namespace, $getwsdlcallback=null, $existwsdlcallback=null, $setwsdlcallback=null)
	{
	  
		ini_set("soap.wsdl_cache_enabled", true);
		
		self::$acllevel				= 0;
		self::$credentials			= '';
		self::$classname  			= $classname;
		self::$namespace 			= $namespace;
		self::$getwsdlcallback		= $getwsdlcallback;
		self::$existwsdlcallback	= $existwsdlcallback;
		self::$setwsdlcallback		= $setwsdlcallback;
		self::$WSDef				= array();
		self::$WSDef['props'] 		= array();
		self::$WSDef['rprops'] 		= array();
		self::$WSDef['enums'] 		= array();	
		self::$WSDef['functions']	= array();	
		// https://www.w3.org/TR/xmlschema-2/#built-in-datatypes
		self::$SOAPTypes = array(
				"str"	 		=> "string",
				"string" 		=> "string",
				"int" 			=> "int",
				"integer" 		=> "int",
				"long" 			=> "long",
				"longint" 		=> "long",
				"datetime" 		=> "dateTime",
				"base64binary" 	=> "base64Binary",
				"base64" 		=> "base64Binary",
				"hex" 			=> "hexBinary",
				"hexbinary"		=> "hexBinary",
				"float" 		=> "float",
				"double" 		=> "double",
				"number" 		=> "double",
				"boolean" 		=> "boolean",
				"bool" 			=> "boolean",
				"anyuri" 		=> "anyURI",
				"token" 		=> "token"	
			);	
	 	
	 	self::$WSDef['functions'] = call_user_func(array($classname, 'WS_Functions'));
		$types = call_user_func(array($classname, 'WS_Types'));	
		foreach($types as $type)
		{
			$class = $type;
			if (!array_key_exists($type, self::$WSDef['props'])) 
			{
				self::$WSDef['props'][$type] = call_user_func(array($type,'WS_Properties'));
				self::$WSDef['rprops'][$type] = self::$WSDef['props'][$type];
				while(get_parent_class($class)!=null)
				{
					$class = get_parent_class($class);
					if (array_key_exists($class, self::$WSDef['rprops'])) 
					{ 
						self::$WSDef['rprops'][$type] = array_merge(self::$WSDef['rprops'][$class], self::$WSDef['rprops'][$type]);
					} else
					self::$WSDef['rprops'][$type] = array_merge(call_user_func(array($class, 'WS_Properties')), self::$WSDef['rprops'][$type]);
				}
			}
		}
		
		//class mapping
		self::$soapclassmap = array();
		foreach (array_keys(self::$WSDef['enums']) as $typename ) { self::$soapclassmap[$typename] = $typename; }
		foreach (array_keys(self::$WSDef['props']) as $typename ) { self::$soapclassmap[$typename] = $typename; }
		 
	}
	  
}
					