<?php

class PMXI_ArrayToXML
{
    /**
    * The main function for converting to an XML document.
    * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
    *
    * @param array $data
    * @param string $rootNodeName - what you want the root node to be - defaultsto data.
    * @param SimpleXMLElement $xml - should only be used recursively
    * @return string XML
    */
    public static function toXml($data, $rootNodeName = 'data', $xml=null, $lvl = 0)
    {                
        // turn off compatibility mode as simple xml throws a wobbly if you don't.
        if (ini_get('zend.ze1_compatibility_mode') == 1)
        {
            ini_set ('zend.ze1_compatibility_mode', 0);
        }
     
        if ($xml == null)
        {
            $xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><'.$rootNodeName .'/>');
        }
     	if ( !empty($data)){
	        // loop through the data passed in.
	        foreach($data as $key => $value)
	        {
	            // no numeric keys in our xml please!
	            if (!$key or is_numeric($key))
	            {
	                // make string key...
	                $key = "item_" . $lvl;

	            }
	            
	            // replace anything not alpha numeric
	            $key = preg_replace('/[^a-z0-9_]/i', '', $key);
	             
	            // if there is another array found recrusively call this function
	            if (is_array($value) or is_object($value))
	            {
	                $node = $xml->addChild($key);
	                // recrusive call.
	                PMXI_ArrayToXML::toXml($value, $rootNodeName, $node, $lvl + 1);
	            }
	            else
	            {                
	                // add single node.
	                $value =  htmlspecialchars($value);
	                $xml->addChild($key,$value);
	            }
	            
	        }
	    }
        // pass back as string. or simple xml object if you want!
        return $xml->asXML();
    }


}