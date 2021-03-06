<?php

  namespace StructuredDynamics\osf\validator\checks; 
  
  use \StructuredDynamics\osf\php\api\ws\sparql\SparqlQuery;

  abstract class Check
  {
    protected $name;
    protected $description;
    protected $checkOnDatasets = array();
    protected $checkUsingOntologies = array();
    protected $network;
    protected $appID;
    protected $user;
    protected $apiKey;
    protected $errors = array(); 
    
    function __construct(){}
    
    abstract public function outputXML();
    abstract public function outputJSON();
    abstract public function run();
    abstract public function fix();
    
    public function setCheckOnDatasets($datasets)
    {
      $this->checkOnDatasets = $datasets;
    }
    
    public function setCheckUsingOntologies($ontologies)
    {
      $this->checkUsingOntologies = $ontologies;
    }
    
    public function setNetwork($network)
    {
      $this->network = $network;
    } 
    
    public function setAppID($appID)
    {
      $this->appID = $appID;
    }
    
    public function setUser($user)
    {
      $this->user = $user;
    }
    
    public function setApiKey($apiKey)
    {
      $this->apiKey = $apiKey;
    }
    
    /** Encode a string to put in a JSON value
            
        @param $string The string to escape

        @return returns the escaped string

        @author Frederick Giasson, Structured Dynamics LLC.
    */
    public function jsonEncode($string) { return str_replace(array ('\\', '"', "\n", "\r", "\t"), array ('\\\\', '\\"', " ", " ", "\\t"), $string); }

    /** Encode content to be included in XML files

        @param $string The content string to be encoded
        
        @return returns the encoded string
      
        @author Frederick Giasson, Structured Dynamics LLC.
    */
    public function xmlEncode($string)
    { 
      // Replace all the possible entities by their character. That way, we won't "double encode" 
      // these entities. Otherwise, we can endup with things such as "&amp;amp;" which some
      // XML parsers doesn't seem to like (and throws errors).
      $string = str_replace(array ("&amp;", "&lt;", "&gt;"), array ("&", "<", ">"), $string);
      
      return str_replace(array ("&", "<", ">"), array ("&amp;", "&lt;", "&gt;"), $string); 
    }
    
    /**
    * Validate xsd:dateTime
    */
    protected function validateDateTimeISO8601($value)
    {
      if(preg_match('/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/', $value) > 0) 
      {
        return(TRUE);
      } 
      else 
      {
        return(FALSE);
      }
    }
    
    /**
    * Validate xsd:base64Binary
    */
    protected function validateBase64Binary($value)
    {
      if(base64_encode(base64_decode($value)) === $value)
      {
        return(TRUE);
      } 
      else 
      {
        return(FALSE);
      }
    }
    
    /**
    * validate xsd:unsignedInt
    * 
    * @param mixed $value
    */
    protected function validateUnsignedInt($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if(is_int($value) && $value >= 0 && $value <= 4294967295)   
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }
    
    /**
    * Validate xsd:dateTimeStamp
    */
    protected function validateDateTimeStampISO8601($value)
    {
      if($this->validateDateTimeISO8601($value))
      {
        if(preg_match('/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)(\17[0-5]\d([\.,]\d+)?)([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?))?)?$/', $value) > 0) 
        {
          return(TRUE);
        } 
        else 
        {
          return(FALSE);
        }            
      }
      else
      {
        return(FALSE);
      }
    }
    
    /**
    * Validate xsd:anyURI
    */
    protected function validateAnyURI($value)
    {
      return((bool) preg_match('/^[a-z](?:[-a-z0-9\+\.])*:(?:\/\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&\'\(\)\*\+,;=:])*@)?(?:\[(?:(?:(?:[0-9a-f]{1,4}:){6}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|::(?:[0-9a-f]{1,4}:){5}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){4}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:[0-9a-f]{1,4}:[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){3}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,2}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){2}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,3}[0-9a-f]{1,4})?::[0-9a-f]{1,4}:(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,4}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,5}[0-9a-f]{1,4})?::[0-9a-f]{1,4}|(?:(?:[0-9a-f]{1,4}:){0,6}[0-9a-f]{1,4})?::)|v[0-9a-f]+[-a-z0-9\._~!\$&\'\(\)\*\+,;=:]+)\]|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}|(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&\'\(\)\*\+,;=@])*)(?::[0-9]*)?(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&\'\(\)\*\+,;=:@]))*)*|\/(?:(?:(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&\'\(\)\*\+,;=:@]))+)(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&\'\(\)\*\+,;=:@]))*)*)?|(?:(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&\'\(\)\*\+,;=:@]))+)(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&\'\(\)\*\+,;=:@]))*)*|(?!(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&\'\(\)\*\+,;=:@])))(?:\?(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&\'\(\)\*\+,;=:@])|[\x{E000}-\x{F8FF}\x{F0000}-\x{FFFFD}|\x{100000}-\x{10FFFD}\/\?])*)?(?:\#(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~\x{A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\x{10000}-\x{1FFFD}\x{20000}-\x{2FFFD}\x{30000}-\x{3FFFD}\x{40000}-\x{4FFFD}\x{50000}-\x{5FFFD}\x{60000}-\x{6FFFD}\x{70000}-\x{7FFFD}\x{80000}-\x{8FFFD}\x{90000}-\x{9FFFD}\x{A0000}-\x{AFFFD}\x{B0000}-\x{BFFFD}\x{C0000}-\x{CFFFD}\x{D0000}-\x{DFFFD}\x{E1000}-\x{EFFFD}!\$&\'\(\)\*\+,;=:@])|[\/\?])*)?$/iu', $value));
    }
    
    /**
    * Validate xsd:boolean
    */
    protected function validateBoolean($value)
    {
      if(filter_var((string)$value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== NULL)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }

    /**
    * Validate xsd:byte
    */    
    protected function validateByte($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE && $value >= -128 && $value <= 127)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }    
    

    /**
    * Validate xsd:unsignedByte
    */    
    protected function validateUnsignedByte($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE && $value >= 0 && $value <= 255)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }       
    
    /**
    * Validate xsd:decimal
    */
    protected function validateDecimal($value)
    {
      return((bool) preg_match('/^[+-]?(\d*\.?\d+([eE]?[+-]?\d+)?|\d+[eE][+-]?\d+)$/', $value));
    }
    
    /**
    *  Validate xsd:double
    */
    protected function validateDouble($value)
    {
      if($value == 'NaN' || $value == 'INF' || $value == '-INF')
      {
        return(TRUE);
      }
      
      return(is_numeric($value));
    }

    /**
    *  Validate xsd:float
    */
    protected function validateFloat($value)
    {
      if($value == 'NaN' || $value == 'INF' || $value == '-INF')
      {
        return(TRUE);
      }
      
      return(is_numeric($value));
    }
    
    /**
    * Validate xsd:int
    */
    protected function validateInt($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE && $value >= -2147483648 && $value <= 2147483647)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }
    
    /**
    * Validate xsd:integer
    */
    protected function validateInteger($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }    
    
    /**
    * Validate xsd:nonNegativeInteger
    */
    protected function validateNonNegativeInteger($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE && $value >= 0)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    } 
           
    /**
    * Validate xsd:nonPositiveInteger
    */
    protected function validateNonPositiveInteger($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE && $value <= 0)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }   
    
    
    /**
    * Validate xsd:positiveInteger
    */
    protected function validatePositiveInteger($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE && $value >= 1)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    } 
           
    /**
    * Validate xsd:negativeInteger
    */
    protected function validateNegativeInteger($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE && $value <= -1)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }      
    
    /**
    * Validate xsd:short
    */
    protected function validateShort($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE && $value >= -32768 && $value <= 32767)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }  
    
    
    /**
    * Validate xsd:unsignedShort
    */
    protected function validateUnsignedShort($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE && $value >= 0 && $value <= 65535)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }      
              
    /**
    * Validate xsd:long
    */
    protected function validateLong($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE && $value >= -9223372036854775808 && $value <= 9223372036854775807)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }
                  
    /**
    * Validate xsd:unsignedLong
    */
    protected function validateUnsignedLong($value)
    {
      $value = filter_var($value, FILTER_VALIDATE_INT);
      
      if($value !== FALSE && $value >= 0 && $value <= 18446744073709551615)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }  
    
    /**
    * Validate xsd:hexBinary
    */
    protected function validateHexBinary($value)
    {
      if(ctype_xdigit($value) && strlen($value) % 2 == 0)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }  
    
    /**
    * Validate xsd:language
    */
    protected function validateLanguage($value)
    {
      return((bool) preg_match('/^[a-zA-Z]{1,8}(-[a-zA-Z0-9]{1,8})*$/', $value));
    }
    
    /**
    * Validate xsd:Name
    */
    protected function validateName($value)
    {
      return((bool) preg_match('/^[a-zA-Z_:]{1}[a-zA-z0-9_:\-\.]*$/', $value));
    }
    
    /**
    * Validate xsd:NCName
    */
    protected function validateNCName($value)
    {
      return((bool) preg_match('/^[a-zA-Z_]{1}[a-zA-z0-9_\-\.]*$/', $value));
    }
    
    /**
    * Validate xsd:NMTOKEN
    */
    protected function validateNMTOKEN($value)
    {
      return((bool) preg_match('/^[\s]*[a-zA-z0-9_\-\.:]+[\s]*$/', $value));
    }
    
    /**
    * Validate xsd:string
    */
    protected function validateString($value)
    {
      $value = "<?xml version='1.0'?><test>".$value."</test>";
      
      libxml_use_internal_errors(true);
      
      if(simplexml_load_string($value) !== FALSE)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }

    
    /**
    * Validate xsd:anySimpleType
    */
    protected function validateAnySimpleType($value)
    {
      // Nothing to validate, everything is accepted
      return(TRUE);
    }    
    
    /**
    * Validate xsd:token
    */
    protected function validateToken($value)
    {
      $value = str_replace(array("\n", "\r", "\t"), ' ', $value);
      
      $value = preg_replace('/\s+/', ' ', $value);
      
      $value = trim($value);
      
      $value = "<?xml version='1.0'?><test>".$value."</test>";
      
      libxml_use_internal_errors(true);
      
      if(simplexml_load_string($value) !== FALSE)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }

    /**
    * Validate xsd:normalizedString
    */
    protected function validateNormalizedString($value)
    {
      $value = str_replace(array("\n", "\r", "\t"), ' ', $value);
      $value = "<?xml version='1.0'?><test>".$value."</test>";
      
      libxml_use_internal_errors(true);
      
      if(simplexml_load_string($value) !== FALSE)
      {
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }
      
    /**
    * Validate rdf:XMLLiteral
    */
    protected function validateXMLLiteral($value)
    {
      return($this->validateString($value));
    }

    /**
    * Validate rdf:PlainLiteral
    */
    protected function validatePlainLiteral($value)
    {
      return((bool) preg_match('/^.*@([a-zA-Z]{1,8}(-[a-zA-Z0-9]{1,8})*)*$/', $value));
    }    
    
    /**
    * Validate a custom datatype as defined in the loaded ontologies
    */
    protected function validateCustomDatatype($datatype, $value)
    {
      /**
      * Validate according to the XSP ontologies which are OWL properties used to map to the XSD
      * datatype facet vocabulary
      * 
      * This vocabulary is composed of the following properties:
      * 
      *   (1)  http://www.owl-ontologies.com/2005/08/07/xsp.owl#base
      *   (2)  http://www.owl-ontologies.com/2005/08/07/xsp.owl#length      
      *   (3)  http://www.owl-ontologies.com/2005/08/07/xsp.owl#maxExclusive      
      *   (4)  http://www.owl-ontologies.com/2005/08/07/xsp.owl#maxInclusive      
      *   (5)  http://www.owl-ontologies.com/2005/08/07/xsp.owl#maxLength      
      *   (6)  http://www.owl-ontologies.com/2005/08/07/xsp.owl#minExclusive       
      *   (7)  http://www.owl-ontologies.com/2005/08/07/xsp.owl#minInclusive      
      *   (8)  http://www.owl-ontologies.com/2005/08/07/xsp.owl#minLength      
      *   (9) http://www.owl-ontologies.com/2005/08/07/xsp.owl#pattern      
      * 
      */
      
      // Get the description of that custom datatype
      $sparql = new SparqlQuery($this->network, $this->appID, $this->apiKey, $this->user);

      $from = '';
      
      foreach($this->checkUsingOntologies as $ontology)
      {
        $from .= 'from <'.$ontology.'> ';
      }
      
      // Get the list of all the datatype properties used within the datasets
      $sparql->mime("application/sparql-results+json")
             ->query('select ?p ?o
                      '.$from.'
                      where
                      {
                        <'.$datatype.'> ?p ?o .
                      }')
             ->send();

      $datatypeDesc = array();
           
      if($sparql->isSuccessful())
      {
        $results = json_decode($sparql->getResultset(), TRUE);    
        
        // Compose the array that describes the content type
        if(!empty($results['results']['bindings']))
        {
          foreach($results['results']['bindings'] as $binding)
          {
            $datatypeDesc[$binding['p']['value']] = $binding['o']['value'];
          }
        }
        
        // Check if there is a base datatype defined for this custom datatype. If there is, then
        // the first thing we do is to validate the value according to this base datatype.
        if(isset($datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#base']))
        {
          switch($datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#base'])
          {
            case "http://www.w3.org/2001/XMLSchema#base64Binary":
              if(!$this->validateBase64Binary($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#boolean":
              if(!$this->validateBoolean($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#byte":
              if(!$this->validateByte($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#dateTimeStamp":
              if(!$this->validateDateTimeStampISO8601($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#dateTime":
              if(!$this->validateDateTimeISO8601($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#decimal":
              if(!$this->validateDecimal($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#double":
              if(!$this->validateDouble($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#float":
              if(!$this->validateFloat($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#hexBinary":
              if(!$this->validateHexBinary($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#int":
              if(!$this->validateInt($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#integer":
              if(!$this->validateInteger($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#language":
              if(!$this->validateLanguage($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#long":
              if(!$this->validateLong($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#Name":
              if(!$this->validateName($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#NCName":
              if(!$this->validateNCName($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#negativeInteger":
              if(!$this->validateNegativeInteger($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#NMTOKEN":
              if(!$this->validateNMTOKEN($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#nonNegativeInteger":
              if(!$this->validateNonNegativeInteger($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#nonPositiveInteger":
              if(!$this->validateNonPositiveInteger($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#normalizedString":
              if(!$this->validateNormalizedString($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/1999/02/22-rdf-syntax-ns#PlainLiteral":
              if(!$this->validatePlainLiteral($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#positiveInteger":
              if(!$this->validatePositiveInteger($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#short":
              if(!$this->validateShort($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#string":
              if(!$this->validateString($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#token":
              if(!$this->validateToken($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#unsignedByte":
              if(!$this->validateUnsignedByte($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#unsignedInt":
              if(!$this->validateUnsignedInt($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#unsignedLong":
              if(!$this->validateUnsignedLong($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#unsignedShort":
              if(!$this->validateUnsignedShort($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral":
              if(!$this->validateXMLLiteral($value))
              {
                return(FALSE);
              }
            break;
            
            case "http://www.w3.org/2001/XMLSchema#anyURI":
              if(!$this->validateAnyURI($value))
              {
                return(FALSE);
              }
            break;
            
            default:
              if(!$this->validateAnySimpleType($value))
              {
                return(FALSE);
              }
            break;
          }     
          
          // If we validated the base datatype, we simply continue to validate the custom datatype     
        }
        
        // Check if a pattern is defined. If there is one, then we validate according to it       
        if(isset($datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#pattern']))
        {
          if(preg_match('/'.str_replace('/', '\\/', $datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#pattern']).'/', $value) == 1)
          {
            return(TRUE);
          }
          else
          {
            return(FALSE);
          }
        }
        
        // Check if the value is a decimal. If it is then we check if we have min/max defined.
        if($this->validateDecimal($value))
        {
          if(isset($datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#minInclusive']))
          {
            if($value >= $datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#minInclusive'])
            {
              return(TRUE);
            }
            else
            {
              return(FALSE);
            }
          }          
          
          if(isset($datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#minExclusive']))
          {
            if($value > $datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#minExclusive'])
            {
              return(TRUE);
            }
            else
            {
              return(FALSE);
            }
          }  
          
          if(isset($datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#maxInclusive']))
          {
            if($value <= $datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#maxInclusive'])
            {
              return(TRUE);
            }
            else
            {
              return(FALSE);
            }
          }          
          
          if(isset($datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#maxExclusive']))
          {
            if($value < $datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#maxExclusive'])
            {
              return(TRUE);
            }
            else
            {
              return(FALSE);
            }
          }            
        }        

        // Otherwise we consider it a literal, and check for min/max length validation
        if($this->validateAnySimpleType($value))
        {
          if(isset($datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#minLength']))
          {
            if(strlen($value) >= $datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#minLength'])
            {
              return(TRUE);
            }
            else
            {
              return(FALSE);
            }
          }
          
          if(isset($datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#maxLength']))
          {
            if(strlen($value) <= $datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#maxLength'])
            {
              return(TRUE);
            }
            else
            {
              return(FALSE);
            }
          }
          
          if(isset($datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#length']))
          {
            if(strlen($value) == $datatypeDesc['http://www.owl-ontologies.com/2005/08/07/xsp.owl#maxLength'])
            {
              return(TRUE);
            }
            else
            {
              return(FALSE);
            }
          }            
        }
      }
    }
    
    protected function testValidators()
    {
      // test xsd:unsignedInt validator
      if($this->validateUnsignedInt(0) !== TRUE){ cecho("Validator testing issue: xsd:unsignedInt with 4294967296\n", 'RED'); }
      if($this->validateUnsignedInt(1) !== TRUE){ cecho("Validator testing issue: xsd:unsignedInt with 1\n", 'RED'); }
      if($this->validateUnsignedInt(4294967295) !== TRUE){ cecho("Validator testing issue: xsd:unsignedInt with 4294967295\n", 'RED'); }
      if($this->validateUnsignedInt(-1) !== FALSE){ cecho("Validator testing issue: xsd:unsignedInt with -1\n", 'RED'); }
      if($this->validateUnsignedInt(4294967296) !== FALSE){ cecho("Validator testing issue: xsd:unsignedInt with 4294967296\n", 'RED'); }
      
      // test xsd:base64Binary
      if($this->validateBase64Binary('dGhpcyBpcyBhIHRlc3Q=') !== TRUE){ cecho("Validator testing issue: xsd:base64Binary with 'dGhpcyBpcyBhIHRlc3Q='\n", 'RED'); }
      if($this->validateBase64Binary('dGhpcyBpcyBhIHRlc3Q-') !== FALSE){ cecho("Validator testing issue: xsd:base64Binary with 'dGhpcyBpcyBhIHRlc3Q-'\n", 'RED'); }
      
      // test xsd:dateTime
      if($this->validateDateTimeISO8601('1997') !== TRUE){ cecho("Validator testing issue: xsd:dateTime with '1997'\n", 'RED'); }
      if($this->validateDateTimeISO8601('1997-07') !== TRUE){ cecho("Validator testing issue: xsd:dateTime with '1997-07'\n", 'RED'); }
      if($this->validateDateTimeISO8601('1997-07-16') !== TRUE){ cecho("Validator testing issue: xsd:dateTime with '1997-07-16'\n", 'RED'); }
      if($this->validateDateTimeISO8601('1997-07-16T19:20+01:00') !== TRUE){ cecho("Validator testing issue: xsd:dateTime with '1997-07-16T19:20+01:00'\n", 'RED'); }
      if($this->validateDateTimeISO8601('1997-07-16T19:20:30+01:00') !== TRUE){ cecho("Validator testing issue: xsd:dateTime with '1997-07-16T19:20:30+01:00'\n", 'RED'); }
      if($this->validateDateTimeISO8601('1997-07-16T19:20:30.45+01:00') !== TRUE){ cecho("Validator testing issue: xsd:dateTime with '1997-07-16T19:20:30.45+01:00'\n", 'RED'); }
      if($this->validateDateTimeISO8601('1997-07-') !== FALSE){ cecho("Validator testing issue: xsd:dateTime with '1997-07-'\n", 'RED'); }
      if($this->validateDateTimeISO8601('19') !== FALSE){ cecho("Validator testing issue: xsd:dateTime with '19'\n", 'RED'); }
      if($this->validateDateTimeISO8601('1997 06 24') !== FALSE){ cecho("Validator testing issue: xsd:dateTime with '1997 06 24'\n", 'RED'); }
      if($this->validateDateTimeISO8601('') !== FALSE){ cecho("Validator testing issue: xsd:dateTime with ''\n", 'RED'); }
      
      // test xsd:dateTimeStamp
      if($this->validateDateTimeStampISO8601('2004-04-12T13:20:00-05:00') !== TRUE){ cecho("Validator testing issue: xsd:dateTimeStamp with '2004-04-12T13:20:00-05:00'\n", 'RED'); }
      if($this->validateDateTimeStampISO8601('2004-04-12T13:20:00Z') !== TRUE){ cecho("Validator testing issue: xsd:dateTimeStamp with '2004-04-12T13:20:00Z'\n", 'RED'); }
      if($this->validateDateTimeStampISO8601('2004-04-12T13:20:00') !== FALSE){ cecho("Validator testing issue: xsd:dateTimeStamp with '2004-04-12T13:20:00'\n", 'RED'); }
      if($this->validateDateTimeStampISO8601('2004-04-12T13:00Z') !== FALSE){ cecho("Validator testing issue: xsd:dateTimeStamp with '2004-04-12T13:00Z'\n", 'RED'); }
      if($this->validateDateTimeStampISO8601('2004-04-12Z') !== FALSE){ cecho("Validator testing issue: xsd:dateTimeStamp with '2004-04-12Z'\n", 'RED'); }
      if($this->validateDateTimeStampISO8601('1997-07-') !== FALSE){ cecho("Validator testing issue: xsd:dateTimeStamp with '1997-07-'\n", 'RED'); }
      if($this->validateDateTimeStampISO8601('19') !== FALSE){ cecho("Validator testing issue: xsd:dateTimeStamp with '19'\n", 'RED'); }
      if($this->validateDateTimeStampISO8601('1997 06 24') !== FALSE){ cecho("Validator testing issue: xsd:dateTimeStamp with '1997 06 24'\n", 'RED'); }
      if($this->validateDateTimeStampISO8601('') !== FALSE){ cecho("Validator testing issue: xsd:dateTimeStamp with ''\n", 'RED'); }
      
      // test xsd:anyURI
      if($this->validateAnyURI('http://datypic.com') !== TRUE){ cecho("Validator testing issue: xsd:anyURI with 'http://datypic.com'\n", 'RED'); }
      if($this->validateAnyURI('mailto:info@datypic.com') !== TRUE){ cecho("Validator testing issue: xsd:anyURI with 'mailto:info@datypic.com'\n", 'RED'); }
      if($this->validateAnyURI('http://datypic.com/prod.html#shirt') !== TRUE){ cecho("Validator testing issue: xsd:anyURI with 'http://datypic.com/prod.html#shirt'\n", 'RED'); }
      if($this->validateAnyURI('urn:example:org') !== TRUE){ cecho("Validator testing issue: xsd:anyURI with 'urn:example:org'\n", 'RED'); }
      if($this->validateAnyURI('http://datypic.com#frag1#frag2') !== FALSE){ cecho("Validator testing issue: xsd:anyURI with 'http://datypic.com#frag1#frag2'\n", 'RED'); }
      if($this->validateAnyURI('http://datypic.com#f% rag') !== FALSE){ cecho("Validator testing issue: xsd:anyURI with 'http://datypic.com#f% rag'\n", 'RED'); }
      if($this->validateAnyURI('') !== FALSE){ cecho("Validator testing issue: xsd:anyURI with ''\n", 'RED'); }
      
      // test xsd:boolean
      if($this->validateBoolean('true') !== TRUE){ cecho("Validator testing issue: xsd:boolean with 'true'\n", 'RED'); }
      if($this->validateBoolean('false') !== TRUE){ cecho("Validator testing issue: xsd:boolean with 'false'\n", 'RED'); }
      if($this->validateBoolean('0') !== TRUE){ cecho("Validator testing issue: xsd:boolean with '0'\n", 'RED'); }
      if($this->validateBoolean('1') !== TRUE){ cecho("Validator testing issue: xsd:boolean with '1'\n", 'RED'); }
      if($this->validateBoolean('TRUE') !== FALSE){ cecho("Validator testing issue: xsd:boolean with 'TRUE'\n", 'RED'); }
      if($this->validateBoolean('T') !== FALSE){ cecho("Validator testing issue: xsd:boolean with 'T'\n", 'RED'); }
      if($this->validateBoolean('') !== FALSE){ cecho("Validator testing issue: xsd:boolean with ''\n", 'RED'); }
      
      // test xsd:byte
      if($this->validateByte('+3') !== TRUE){ cecho("Validator testing issue: xsd:byte with '+3'\n", 'RED'); }
      if($this->validateByte('122') !== TRUE){ cecho("Validator testing issue: xsd:byte with '122'\n", 'RED'); }
      if($this->validateByte('0') !== TRUE){ cecho("Validator testing issue: xsd:byte with '0'\n", 'RED'); }
      if($this->validateByte('-123') !== TRUE){ cecho("Validator testing issue: xsd:byte with '-123'\n", 'RED'); }
      if($this->validateByte('130') !== FALSE){ cecho("Validator testing issue: xsd:byte with '130'\n", 'RED'); }
      if($this->validateByte('3.0') !== FALSE){ cecho("Validator testing issue: xsd:byte with '3.0'\n", 'RED'); }
      
      // test xsd:unsignedByte
      if($this->validateUnsignedByte('+3') !== TRUE){ cecho("Validator testing issue: xsd:unsignedByte with '+3'\n", 'RED'); }
      if($this->validateUnsignedByte('122') !== TRUE){ cecho("Validator testing issue: xsd:unsignedByte with '122'\n", 'RED'); }
      if($this->validateUnsignedByte('0') !== TRUE){ cecho("Validator testing issue: xsd:unsignedByte with '0'\n", 'RED'); }
      if($this->validateUnsignedByte('-123') !== FALSE){ cecho("Validator testing issue: xsd:unsignedByte with '-123'\n", 'RED'); }
      if($this->validateUnsignedByte('256') !== FALSE){ cecho("Validator testing issue: xsd:unsignedByte with '256'\n", 'RED'); }
      if($this->validateUnsignedByte('3.0') !== FALSE){ cecho("Validator testing issue: xsd:unsignedByte with '3.0'\n", 'RED'); }
      
      // test xsd:decimal
      if($this->validateDecimal('3.0') !== TRUE){ cecho("Validator testing issue: xsd:decimal with '3.0'\n", 'RED'); }
      if($this->validateDecimal('-3.0') !== TRUE){ cecho("Validator testing issue: xsd:decimal with '-3.0'\n", 'RED'); }
      if($this->validateDecimal('+3.5') !== TRUE){ cecho("Validator testing issue: xsd:decimal with '+3.5'\n", 'RED'); }
      if($this->validateDecimal('.3') !== TRUE){ cecho("Validator testing issue: xsd:decimal with '.3'\n", 'RED'); }
      if($this->validateDecimal('-.3') !== TRUE){ cecho("Validator testing issue: xsd:decimal with '-.3'\n", 'RED'); }
      if($this->validateDecimal('0003.0') !== TRUE){ cecho("Validator testing issue: xsd:decimal with '0003.0'\n", 'RED'); }
      if($this->validateDecimal('3.000') !== TRUE){ cecho("Validator testing issue: xsd:decimal with '3.000'\n", 'RED'); }
      if($this->validateDecimal('3,5') !== FALSE){ cecho("Validator testing issue: xsd:decimal with '3,5'\n", 'RED'); }
      
      // test xsd:double
      if($this->validateDouble('-3E2') !== TRUE){ cecho("Validator testing issue: xsd:double with '-3E2'\n", 'RED'); }
      if($this->validateDouble('4268.22752E11') !== TRUE){ cecho("Validator testing issue: xsd:double with '4268.22752E11'\n", 'RED'); }
      if($this->validateDouble('+24.3e-3') !== TRUE){ cecho("Validator testing issue: xsd:double with '+24.3e-3'\n", 'RED'); }
      if($this->validateDouble('12') !== TRUE){ cecho("Validator testing issue: xsd:double with '12'\n", 'RED'); }
      if($this->validateDouble('+3.5') !== TRUE){ cecho("Validator testing issue: xsd:double with '+3.5'\n", 'RED'); }
      if($this->validateDouble('-INF') !== TRUE){ cecho("Validator testing issue: xsd:double with '-INF'\n", 'RED'); }
      if($this->validateDouble('-0') !== TRUE){ cecho("Validator testing issue: xsd:double with '-0'\n", 'RED'); }
      if($this->validateDouble('NaN') !== TRUE){ cecho("Validator testing issue: xsd:double with 'NaN'\n", 'RED'); }
      if($this->validateDouble('-3E2.4') !== FALSE){ cecho("Validator testing issue: xsd:double with '-3E2.4'\n", 'RED'); }
      if($this->validateDouble('12E') !== FALSE){ cecho("Validator testing issue: xsd:double with '12E'\n", 'RED'); }
      if($this->validateDouble('NAN') !== FALSE){ cecho("Validator testing issue: xsd:double with 'NAN'\n", 'RED'); }
      
      // test xsd:float
      if($this->validateFloat('-3E2') !== TRUE){ cecho("Validator testing issue: xsd:float with '-3E2'\n", 'RED'); }
      if($this->validateFloat('4268.22752E11') !== TRUE){ cecho("Validator testing issue: xsd:float with '4268.22752E11'\n", 'RED'); }
      if($this->validateFloat('+24.3e-3') !== TRUE){ cecho("Validator testing issue: xsd:float with '+24.3e-3'\n", 'RED'); }
      if($this->validateFloat('12') !== TRUE){ cecho("Validator testing issue: xsd:float with '12'\n", 'RED'); }
      if($this->validateFloat('+3.5') !== TRUE){ cecho("Validator testing issue: xsd:float with '+3.5'\n", 'RED'); }
      if($this->validateFloat('-INF') !== TRUE){ cecho("Validator testing issue: xsd:float with '-INF'\n", 'RED'); }
      if($this->validateFloat('-0') !== TRUE){ cecho("Validator testing issue: xsd:float with '-0'\n", 'RED'); }
      if($this->validateFloat('NaN') !== TRUE){ cecho("Validator testing issue: xsd:float with 'NaN'\n", 'RED'); }
      if($this->validateFloat('-3E2.4') !== FALSE){ cecho("Validator testing issue: xsd:float with '-3E2.4'\n", 'RED'); }
      if($this->validateFloat('12E') !== FALSE){ cecho("Validator testing issue: xsd:float with '12E'\n", 'RED'); }
      if($this->validateFloat('NAN') !== FALSE){ cecho("Validator testing issue: xsd:float with 'NAN'\n", 'RED'); }

      // test xsd:int
      if($this->validateInt('+3') !== TRUE){ cecho("Validator testing issue: xsd:int with '+3'\n", 'RED'); }
      if($this->validateInt('122') !== TRUE){ cecho("Validator testing issue: xsd:int with '122'\n", 'RED'); }
      if($this->validateInt('0') !== TRUE){ cecho("Validator testing issue: xsd:int with '0'\n", 'RED'); }
      if($this->validateInt('-12312') !== TRUE){ cecho("Validator testing issue: xsd:int with '-12312'\n", 'RED'); }
      if($this->validateInt('2147483650') !== FALSE){ cecho("Validator testing issue: xsd:int with '2147483650'\n", 'RED'); }
      if($this->validateInt('-2147483650') !== FALSE){ cecho("Validator testing issue: xsd:int with '-2147483650'\n", 'RED'); }
      if($this->validateInt('3.0') !== FALSE){ cecho("Validator testing issue: xsd:int with '3.0'\n", 'RED'); }

      // test xsd:integer
      if($this->validateInteger('+3') !== TRUE){ cecho("Validator testing issue: xsd:integer with '+3'\n", 'RED'); }
      if($this->validateInteger('122') !== TRUE){ cecho("Validator testing issue: xsd:integer with '122'\n", 'RED'); }
      if($this->validateInteger('0') !== TRUE){ cecho("Validator testing issue: xsd:integer with '0'\n", 'RED'); }
      if($this->validateInteger('-12312') !== TRUE){ cecho("Validator testing issue: xsd:integer with '-12312'\n", 'RED'); }
      if($this->validateInteger('2147483650') !== TRUE){ cecho("Validator testing issue: xsd:integer with '2147483650'\n", 'RED'); }
      if($this->validateInteger('-2147483650') !== TRUE){ cecho("Validator testing issue: xsd:integer with '-2147483650'\n", 'RED'); }
      if($this->validateInteger('3.0') !== FALSE){ cecho("Validator testing issue: xsd:integer with '3.0'\n", 'RED'); }

      // test xsd:nonNegativeInteger
      if($this->validateNonNegativeInteger('+3') !== TRUE){ cecho("Validator testing issue: xsd:nonNegativeInteger with '+3'\n", 'RED'); }
      if($this->validateNonNegativeInteger('122') !== TRUE){ cecho("Validator testing issue: xsd:nonNegativeInteger with '122'\n", 'RED'); }
      if($this->validateNonNegativeInteger('0') !== TRUE){ cecho("Validator testing issue: xsd:nonNegativeInteger with '0'\n", 'RED'); }
      if($this->validateNonNegativeInteger('-3') !== FALSE){ cecho("Validator testing issue: xsd:nonNegativeInteger with '-3'\n", 'RED'); }
      if($this->validateNonNegativeInteger('3.0') !== FALSE){ cecho("Validator testing issue: xsd:nonNegativeInteger with '3.0'\n", 'RED'); }

      // test xsd:nonPositiveInteger
      if($this->validateNonPositiveInteger('-3') !== TRUE){ cecho("Validator testing issue: xsd:nonPositiveInteger with '-3'\n", 'RED'); }
      if($this->validateNonPositiveInteger('0') !== TRUE){ cecho("Validator testing issue: xsd:nonPositiveInteger with '0'\n", 'RED'); }
      if($this->validateNonPositiveInteger('3') !== FALSE){ cecho("Validator testing issue: xsd:nonPositiveInteger with '3'\n", 'RED'); }
      if($this->validateNonPositiveInteger('3.0') !== FALSE){ cecho("Validator testing issue: xsd:nonPositiveInteger with '3.0'\n", 'RED'); }

      // test xsd:positiveInteger
      if($this->validatePositiveInteger('+3') !== TRUE){ cecho("Validator testing issue: xsd:positiveInteger with '+3'\n", 'RED'); }
      if($this->validatePositiveInteger('122') !== TRUE){ cecho("Validator testing issue: xsd:positiveInteger with '122'\n", 'RED'); }
      if($this->validatePositiveInteger('1') !== TRUE){ cecho("Validator testing issue: xsd:positiveInteger with '1'\n", 'RED'); }
      if($this->validatePositiveInteger('0') !== FALSE){ cecho("Validator testing issue: xsd:positiveInteger with '0'\n", 'RED'); }
      if($this->validatePositiveInteger('-3') !== FALSE){ cecho("Validator testing issue: xsd:positiveInteger with '-3'\n", 'RED'); }
      if($this->validatePositiveInteger('3.0') !== FALSE){ cecho("Validator testing issue: xsd:positiveInteger with '3.0'\n", 'RED'); }

      // test xsd:negativeInteger
      if($this->validateNegativeInteger('-3') !== TRUE){ cecho("Validator testing issue: xsd:negativeInteger with '-3'\n", 'RED'); }
      if($this->validateNegativeInteger('-1') !== TRUE){ cecho("Validator testing issue: xsd:negativeInteger with '-1'\n", 'RED'); }
      if($this->validateNegativeInteger('0') !== FALSE){ cecho("Validator testing issue: xsd:negativeInteger with '0'\n", 'RED'); }
      if($this->validateNegativeInteger('3') !== FALSE){ cecho("Validator testing issue: xsd:negativeInteger with '3'\n", 'RED'); }
      if($this->validateNegativeInteger('3.0') !== FALSE){ cecho("Validator testing issue: xsd:negativeInteger with '3.0'\n", 'RED'); }

      // test xsd:short
      if($this->validateShort('+3') !== TRUE){ cecho("Validator testing issue: xsd:short with '+3'\n", 'RED'); }
      if($this->validateShort('122') !== TRUE){ cecho("Validator testing issue: xsd:short with '122'\n", 'RED'); }
      if($this->validateShort('0') !== TRUE){ cecho("Validator testing issue: xsd:short with '0'\n", 'RED'); }
      if($this->validateShort('-1213') !== TRUE){ cecho("Validator testing issue: xsd:short with '-1213'\n", 'RED'); }
      if($this->validateShort('32770') !== FALSE){ cecho("Validator testing issue: xsd:short with '32770'\n", 'RED'); }
      if($this->validateShort('-32770') !== FALSE){ cecho("Validator testing issue: xsd:short with '-32770'\n", 'RED'); }
      if($this->validateShort('3.0') !== FALSE){ cecho("Validator testing issue: xsd:short with '3.0'\n", 'RED'); }

      // test xsd:unsignedShort
      if($this->validateUnsignedShort('+3') !== TRUE){ cecho("Validator testing issue: xsd:unsignedShort with '+3'\n", 'RED'); }
      if($this->validateUnsignedShort('122') !== TRUE){ cecho("Validator testing issue: xsd:unsignedShort with '122'\n", 'RED'); }
      if($this->validateUnsignedShort('0') !== TRUE){ cecho("Validator testing issue: xsd:unsignedShort with '0'\n", 'RED'); }
      if($this->validateUnsignedShort('-121') !== FALSE){ cecho("Validator testing issue: xsd:unsignedShort with '-121'\n", 'RED'); }
      if($this->validateUnsignedShort('65540') !== FALSE){ cecho("Validator testing issue: xsd:unsignedShort with '65540'\n", 'RED'); }
      if($this->validateUnsignedShort('3.0') !== FALSE){ cecho("Validator testing issue: xsd:unsignedShort with '3.0'\n", 'RED'); }

      // test xsd:long
      if($this->validateLong('+3') !== TRUE){ cecho("Validator testing issue: xsd:long with '+3'\n", 'RED'); }
      if($this->validateLong('122') !== TRUE){ cecho("Validator testing issue: xsd:long with '122'\n", 'RED'); }
      if($this->validateLong('0') !== TRUE){ cecho("Validator testing issue: xsd:long with '0'\n", 'RED'); }
      if($this->validateLong('-1231235555') !== TRUE){ cecho("Validator testing issue: xsd:long with '-1231235555'\n", 'RED'); }
      if($this->validateLong('9223372036854775810') !== FALSE){ cecho("Validator testing issue: xsd:long with '9223372036854775810'\n", 'RED'); }
      if($this->validateLong('-9223372036854775810') !== FALSE){ cecho("Validator testing issue: xsd:long with '-9223372036854775810'\n", 'RED'); }
      if($this->validateLong('3.0') !== FALSE){ cecho("Validator testing issue: xsd:long with '3.0'\n", 'RED'); }

      // test xsd:unsignedLong
      if($this->validateUnsignedLong('+3') !== TRUE){ cecho("Validator testing issue: xsd:unsignedLong with '+3'\n", 'RED'); }
      if($this->validateUnsignedLong('122') !== TRUE){ cecho("Validator testing issue: xsd:unsignedLong with '122'\n", 'RED'); }
      if($this->validateUnsignedLong('0') !== TRUE){ cecho("Validator testing issue: xsd:unsignedLong with '0'\n", 'RED'); }
      if($this->validateUnsignedLong('-123') !== FALSE){ cecho("Validator testing issue: xsd:unsignedLong with '-123'\n", 'RED'); }
      if($this->validateUnsignedLong('18446744073709551620') !== FALSE){ cecho("Validator testing issue: xsd:unsignedLong with '18446744073709551620'\n", 'RED'); }
      if($this->validateUnsignedLong('3.0') !== FALSE){ cecho("Validator testing issue: xsd:unsignedLong with '3.0'\n", 'RED'); }

      // test xsd:hexBinary
      if($this->validateHexBinary('0FB8') !== TRUE){ cecho("Validator testing issue: xsd:hexBinary with '0FB8'\n", 'RED'); }
      if($this->validateHexBinary('0fb8') !== TRUE){ cecho("Validator testing issue: xsd:hexBinary with '0fb8'\n", 'RED'); }
      if($this->validateHexBinary('FB8') !== FALSE){ cecho("Validator testing issue: xsd:hexBinary with 'FB8'\n", 'RED'); }
      if($this->validateHexBinary('0G') !== FALSE){ cecho("Validator testing issue: xsd:hexBinary with '0G'\n", 'RED'); }

      // test xsd:language
      if($this->validateLanguage('en') !== TRUE){ cecho("Validator testing issue: xsd:language with 'en'\n", 'RED'); }
      if($this->validateLanguage('en-GB') !== TRUE){ cecho("Validator testing issue: xsd:language with 'en-GB'\n", 'RED'); }
      if($this->validateLanguage('fr') !== TRUE){ cecho("Validator testing issue: xsd:language with 'fr'\n", 'RED'); }
      if($this->validateLanguage('de') !== TRUE){ cecho("Validator testing issue: xsd:language with 'de'\n", 'RED'); }
      if($this->validateLanguage('i-navajo') !== TRUE){ cecho("Validator testing issue: xsd:language with 'i-navajo'\n", 'RED'); }
      if($this->validateLanguage('x-Newspeak') !== TRUE){ cecho("Validator testing issue: xsd:language with 'x-Newspeak'\n", 'RED'); }
      if($this->validateLanguage('longerThan8') !== FALSE){ cecho("Validator testing issue: xsd:language with 'longerThan8'\n", 'RED'); }

      // test xsd:Name
      if($this->validateName('myElement') !== TRUE){ cecho("Validator testing issue: xsd:Name with 'myElement'\n", 'RED'); }
      if($this->validateName('_my.Element') !== TRUE){ cecho("Validator testing issue: xsd:Name with '_my.Element'\n", 'RED'); }
      if($this->validateName('my-element') !== TRUE){ cecho("Validator testing issue: xsd:Name with 'my-element'\n", 'RED'); }
      if($this->validateName('pre:myelement3') !== TRUE){ cecho("Validator testing issue: xsd:Name with 'pre:myelement3'\n", 'RED'); }
      if($this->validateName('-myelement') !== FALSE){ cecho("Validator testing issue: xsd:Name with '-myelement'\n", 'RED'); }
      if($this->validateName('3rdElement') !== FALSE){ cecho("Validator testing issue: xsd:Name with '3rdElement'\n", 'RED'); }

      // test xsd:NCName
      if($this->validateNCName('myElement') !== TRUE){ cecho("Validator testing issue: xsd:NCName with 'myElement'\n", 'RED'); }
      if($this->validateNCName('_my.Element') !== TRUE){ cecho("Validator testing issue: xsd:NCName with '_my.Element'\n", 'RED'); }
      if($this->validateNCName('my-element') !== TRUE){ cecho("Validator testing issue: xsd:NCName with 'my-element'\n", 'RED'); }
      if($this->validateNCName('pre:myelement3') !== FALSE){ cecho("Validator testing issue: xsd:NCName with 'pre:myelement3'\n", 'RED'); }
      if($this->validateNCName('-myelement') !== FALSE){ cecho("Validator testing issue: xsd:NCName with '-myelement'\n", 'RED'); }
      if($this->validateNCName('3rdElement') !== FALSE){ cecho("Validator testing issue: xsd:NCName with '3rdElement'\n", 'RED'); }

      // test xsd:NMTOKEN
      if($this->validateNMTOKEN('ABCD') !== TRUE){ cecho("Validator testing issue: xsd:NMTOKEN with 'ABCD'\n", 'RED'); }
      if($this->validateNMTOKEN('123_456') !== TRUE){ cecho("Validator testing issue: xsd:NMTOKEN with '123_456'\n", 'RED'); }
      if($this->validateNMTOKEN('  starts_with_a_space') !== TRUE){ cecho("Validator testing issue: xsd:NMTOKEN with '  starts_with_a_space'\n", 'RED'); }
      if($this->validateNMTOKEN('contains a space') !== FALSE){ cecho("Validator testing issue: xsd:NMTOKEN with 'contains a space'\n", 'RED'); }
      if($this->validateNMTOKEN('') !== FALSE){ cecho("Validator testing issue: xsd:NMTOKEN with ''\n", 'RED'); }

      // test xsd:string
      if($this->validateString('This is a string!') !== TRUE){ cecho("Validator testing issue: xsd:string with 'This is a string!'\n", 'RED'); }
      if($this->validateString('12.5') !== TRUE){ cecho("Validator testing issue: xsd:string with '12.5'\n", 'RED'); }
      if($this->validateString('') !== TRUE){ cecho("Validator testing issue: xsd:string with ''\n", 'RED'); }
      if($this->validateString('PB&amp;J') !== TRUE){ cecho("Validator testing issue: xsd:string with 'PB&amp;J'\n", 'RED'); }
      if($this->validateString('   Separated   by   3   spaces.') !== TRUE){ cecho("Validator testing issue: xsd:string with '   Separated   by   3   spaces.'\n", 'RED'); }
      if($this->validateString("This\nis on two lines.") !== TRUE){ cecho("Validator testing issue: xsd:string with 'This\nis on two lines.'\n", 'RED'); }
      if($this->validateString('AT&T') !== FALSE){ cecho("Validator testing issue: xsd:string with 'AT&T'\n", 'RED'); }
      if($this->validateString('3 < 4') !== FALSE){ cecho("Validator testing issue: xsd:string with '3 < 4'\n", 'RED'); }

      // test rdf:XMLLiteral
      if($this->validateXMLLiteral('This is a string!') !== TRUE){ cecho("Validator testing issue: rdf:XMLLiteral with 'This is a string!'\n", 'RED'); }
      if($this->validateXMLLiteral('12.5') !== TRUE){ cecho("Validator testing issue: rdf:XMLLiteral with '12.5'\n", 'RED'); }
      if($this->validateXMLLiteral('') !== TRUE){ cecho("Validator testing issue: rdf:XMLLiteral with ''\n", 'RED'); }
      if($this->validateXMLLiteral('PB&amp;J') !== TRUE){ cecho("Validator testing issue: rdf:XMLLiteral with 'PB&amp;J'\n", 'RED'); }
      if($this->validateXMLLiteral('   Separated   by   3   spaces.') !== TRUE){ cecho("Validator testing issue: rdf:XMLLiteral with '   Separated   by   3   spaces.'\n", 'RED'); }
      if($this->validateXMLLiteral("This\nis on two lines.") !== TRUE){ cecho("Validator testing issue: rdf:XMLLiteral with 'This\nis on two lines.'\n", 'RED'); }
      if($this->validateXMLLiteral('AT&T') !== FALSE){ cecho("Validator testing issue: rdf:XMLLiteral with 'AT&T'\n", 'RED'); }
      if($this->validateXMLLiteral('3 < 4') !== FALSE){ cecho("Validator testing issue: rdf:XMLLiteral with '3 < 4'\n", 'RED'); }

      // test xsd:token
      if($this->validateToken('This is a string!') !== TRUE){ cecho("Validator testing issue: xsd:token with 'This is a string!'\n", 'RED'); }
      if($this->validateToken('12.5') !== TRUE){ cecho("Validator testing issue: xsd:token with '12.5'\n", 'RED'); }
      if($this->validateToken('') !== TRUE){ cecho("Validator testing issue: xsd:token with ''\n", 'RED'); }
      if($this->validateToken('PB&amp;J') !== TRUE){ cecho("Validator testing issue: xsd:token with 'PB&amp;J'\n", 'RED'); }
      if($this->validateToken('   Separated   by   3   spaces.') !== TRUE){ cecho("Validator testing issue: xsd:token with '   Separated   by   3   spaces.'\n", 'RED'); }
      if($this->validateToken("This\nis on two lines.") !== TRUE){ cecho("Validator testing issue: xsd:token with 'This\nis on two lines.'\n", 'RED'); }
      if($this->validateToken('AT&T') !== FALSE){ cecho("Validator testing issue: xsd:token with 'AT&T'\n", 'RED'); }
      if($this->validateToken('3 < 4') !== FALSE){ cecho("Validator testing issue: xsd:token with '3 < 4'\n", 'RED'); }

      // test xsd:normalizedString
      if($this->validateNormalizedString('This is a string!') !== TRUE){ cecho("Validator testing issue: xsd:normalizedString with 'This is a string!'\n", 'RED'); }
      if($this->validateNormalizedString('12.5') !== TRUE){ cecho("Validator testing issue: xsd:normalizedString with '12.5'\n", 'RED'); }
      if($this->validateNormalizedString('') !== TRUE){ cecho("Validator testing issue: xsd:normalizedString with ''\n", 'RED'); }
      if($this->validateNormalizedString('PB&amp;J') !== TRUE){ cecho("Validator testing issue: xsd:normalizedString with 'PB&amp;J'\n", 'RED'); }
      if($this->validateNormalizedString('   Separated   by   3   spaces.') !== TRUE){ cecho("Validator testing issue: xsd:normalizedString with '   Separated   by   3   spaces.'\n", 'RED'); }
      if($this->validateNormalizedString("This\nis on two lines.") !== TRUE){ cecho("Validator testing issue: xsd:normalizedString with 'This\nis on two lines.'\n", 'RED'); }
      if($this->validateNormalizedString('AT&T') !== FALSE){ cecho("Validator testing issue: xsd:normalizedString with 'AT&T'\n", 'RED'); }
      if($this->validateNormalizedString('3 < 4') !== FALSE){ cecho("Validator testing issue: xsd:normalizedString with '3 < 4'\n", 'RED'); }

      // test rdf:PlainLiteral
      if($this->validatePlainLiteral('Family Guy@en') !== TRUE){ cecho("Validator testing issue: xsd:normalizedString with 'Family Guy@en'\n", 'RED'); }
      if($this->validatePlainLiteral('Family Guy@EN') !== TRUE){ cecho("Validator testing issue: xsd:normalizedString with 'Family Guy@EN'\n", 'RED'); }
      if($this->validatePlainLiteral('Family Guy@FOX@en') !== TRUE){ cecho("Validator testing issue: xsd:normalizedString with 'Family Guy@FOX@en'\n", 'RED'); }
      if($this->validatePlainLiteral('Family Guy@') !== TRUE){ cecho("Validator testing issue: xsd:normalizedString with 'Family Guy@'\n", 'RED'); }
      if($this->validatePlainLiteral('Family Guy@FOX@') !== TRUE){ cecho("Validator testing issue: xsd:normalizedString with 'Family Guy@FOX@'\n", 'RED'); }
      if($this->validatePlainLiteral('Family Guy') !== FALSE){ cecho("Validator testing issue: xsd:normalizedString with 'Family Guy'\n", 'RED'); }
      if($this->validatePlainLiteral('Family Guy@12') !== FALSE){ cecho("Validator testing issue: xsd:normalizedString with 'Family Guy@12'\n", 'RED'); }      
    }        
    
    protected function getCustomDatatypes()
    {
      $sparql = new SparqlQuery($this->network, $this->appID, $this->apiKey, $this->user);

      $from = '';
      
      foreach($this->checkUsingOntologies as $ontology)
      {
        $from .= 'from <'.$ontology.'> ';
      }

      $sparql->mime("application/sparql-results+json")
             ->query('select ?customDatatype ?baseDatatype
                      '.$from.'
                      where
                      {
                        ?customDatatype a <http://www.w3.org/2002/07/owl#Datatype> ;
                                        <http://www.owl-ontologies.com/2005/08/07/xsp.owl#base> ?baseDatatype .
                      }')
             ->send();

      $customDatatypes = array();
             
      if($sparql->isSuccessful())
      {
        $results = json_decode($sparql->getResultset(), TRUE);   

        if(isset($results['results']['bindings']) && count($results['results']['bindings']) > 0)
        {
          foreach($results['results']['bindings'] as $result)
          {
            $customDatatype = $result['customDatatype']['value'];
            $baseDatatype = $result['baseDatatype']['value'];
            
            $customDatatypes[$customDatatype] = $baseDatatype;
          }
        }        
      }
      else
      {
        // Error
      }  
      
      return($customDatatypes);    
    }
  }
  
?>
