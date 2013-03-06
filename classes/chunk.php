<?php
/**
 * Chunk
 * 
 * Reads a large file in as chunks for easier parsing.
 * 
 * The chunks returned are whole <$this->options['element']/>s found within file.
 * 
 * Each call to read() returns the whole element including start and end tags.
 * 
 * Tested with a 1.8MB file, extracted 500 elements in 0.11s
 * (with no work done, just extracting the elements)
 * 
 * Usage:
 * <code>
 *   // initialize the object
 *   $file = new Chunk('chunk-test.xml', array('element' => 'Chunk'));
 *   
 *   // loop through the file until all lines are read
 *   while ($xml = $file->read()) {
 *     // do whatever you want with the string
 *     $o = simplexml_load_string($xml);
 *   }
 * </code>
 * 
 * @package default
 * @author Dom Hastings
 */
class PMXI_Chunk {
  /**
   * options
   *
   * @var array Contains all major options
   * @access public
   */
  public $options = array(
    'path' => './',       // string The path to check for $file in
    'element' => '',      // string The XML element to return
    'chunkSize' => 4096,    // integer The amount of bytes to retrieve in each chunk
    'type' => 'upload'
  );
  
  /**
   * file
   *
   * @var string The filename being read
   * @access public
   */
  public $file = '';
  /**
   * pointer
   *
   * @var integer The current position the file is being read from
   * @access public
   */
  public $pointer = 0;
  
  public $cloud = array();
  
  /**
   * handle
   *
   * @var resource The fopen() resource
   * @access private
   */
  private $handle = null;
  /**
   * reading
   *
   * @var boolean Whether the script is currently reading the file
   * @access private
   */
  private $reading = false;
  /**
   * readBuffer
   * 
   * @var string Used to make sure start tags aren't missed
   * @access private
   */
  private $readBuffer = '';
  
  /**
   * __construct
   * 
   * Builds the Chunk object
   *
   * @param string $file The filename to work with
   * @param array $options The options with which to parse the file
   * @author Dom Hastings
   * @access public
   */
  public function __construct($file, $options = array(), $pointer = 0) {
    // merge the options together
    $this->options = array_merge($this->options, (is_array($options) ? $options : array()));
    
    // check that the path ends with a /
    if (substr($this->options['path'], -1) != '/') {
      $this->options['path'] .= '/';
    }
    
    // normalize the filename
    $file_base = basename($file);
    
    // make sure chunkSize is an int
    $this->options['chunkSize'] = intval($this->options['chunkSize']);
    
    // check it's valid
    if ($this->options['chunkSize'] < 64) {
      $this->options['chunkSize'] = 1024;
    }
    
    $this->pointer = $pointer;
    
    // set the filename
    $this->file = ($this->options['path'] != './') ? realpath($this->options['path'].$file_base) : $file;
    
    // check the file exists
    if (!file_exists($this->file)){
      throw new Exception('File doesn\'t exist');
    }
    
    // open the file
    $this->handle = fopen($this->file, 'rb');
    
    // check the file opened successfully
    if (!$this->handle) {
      throw new Exception('Error opening file for reading');
    }
    
  }
  
  /**
   * __destruct
   * 
   * Cleans up
   *
   * @return void
   * @author Dom Hastings
   * @access public
   */
  public function __destruct() {
    // close the file resource
    fclose($this->handle);
  }
  
  /**
   * read
   * 
   * Reads the first available occurence of the XML element $this->options['element']
   *
   * @return string The XML string from $this->file
   * @author Dom Hastings
   * @access public
   */
  public function read() {
    // check we have an element specified
    if (!empty($this->options['element'])) {
      // trim it
      $element = trim($this->options['element']);
      
    } else {
      $element = '';
    }
    
    // initialize the buffer
    $buffer = false;
    
    // if the element is empty, then start auto detect root element tag name
    if (empty($element)) {
      // let the script know we're reading
      $this->reading = true;
      $founded_tags = array();
      // read in the whole doc, cos we don't know what's wanted
      while ($this->reading) {
        $c = fread($this->handle, $this->options['chunkSize']);        
        if ( preg_match_all("/<\\w+\\s*[^<]*\\s*\/?>/i", $c, $matches, PREG_PATTERN_ORDER) ){   
          foreach ($matches[0] as $tag) {
            $tag = explode(" ",trim(str_replace(array('<','>','/'), '', $tag)));
            array_push($founded_tags, $tag[0]);
          }
        }
        $this->reading = (!feof($this->handle));
      }          
      
    // we must be looking for a specific element
    } 

    if (empty($element) and !empty($founded_tags)) {      
      
      $element_counts = array_count_values($founded_tags);            

      if (!empty($element_counts)){

        $this->cloud = array_slice($element_counts, 0, 2);

        foreach ($element_counts as $tag => $count) {    
          if ($count > 1 and empty($this->options['element'])) {
            $this->options['element'] = $element = $tag;            
          }          
          elseif ($count > 1){
            $this->cloud[$tag] = $count;    
          }
        }
      }
            
    }    
      
    // return it all if element doesn't founded
    if (empty($element))
      return false;
      
    // we must be looking for a specific element
    //} 
    
    // initialize the buffer
    $buffer = false;

    // set up the strings to find
    $open = '<'.$element;
    $close = '</'.$element.'>';
    
    // let the script know we're reading
    $this->reading = true;
    
    // reset the global buffer
    $this->readBuffer = '';
    
    // this is used to ensure all data is read, and to make sure we don't send the start data again by mistake
    $store = false;           

    // seek to the position we need in the file
    fseek($this->handle, $this->pointer);
    
    // start reading
    while ($this->reading && !feof($this->handle)) {

      // store the chunk in a temporary variable                        
      $tmp = fread($this->handle, $this->options['chunkSize']);
      
      // update the global buffer
      $this->readBuffer .= $tmp;
      
      // check for the open string
      $checkOpen = strpos($tmp, $open." ");
      if (!$checkOpen) $checkOpen = strpos($tmp, $open.">");

      // if it wasn't in the new buffer
      if (!$checkOpen && !($store)) {
        // check the full buffer (in case it was only half in this buffer)
        $checkOpen = strpos($this->readBuffer, $open." ");
        if (!$checkOpen) $checkOpen = strpos($this->readBuffer, $open.">");

        // if it was in there
        if ($checkOpen) {
          // set it to the remainder
          $checkOpen = $checkOpen % $this->options['chunkSize'];
        }
      }        
      
      // check for the close string
      $checkClose = strpos($tmp, $close);
      $withoutcloseelement = false;
      if (!$checkClose){ 
        $checkClose = (preg_match_all("/\/>\s*".$open."\s*/", $this->readBuffer, $matches)) ? strpos($this->readBuffer, $matches[0][0]) : false;                
        if ($checkClose) 
          $withoutcloseelement = true;
        else{
          /*$checkClose = (preg_match_all("/\s*\/>\s*<\/", $this->readBuffer, $matches)) ? strpos($this->readBuffer, $matches[0][0]) : false;
            if ($checkClose) 
              $withoutcloseelement = true;*/
        }
      }

      // if it wasn't in the new buffer
      if (!$checkClose && ($store)) {
        // check the full buffer (in case it was only half in this buffer)
        $checkClose = strpos($this->readBuffer, $close);
        
        $withoutcloseelement = false;
        if (!$checkClose){ 
          $checkClose = (preg_match_all("/\/>\s*".$open."\s*/", $this->readBuffer, $matches)) ? strpos($this->readBuffer, $matches[0][0]) : false;          
          if ($checkClose) 
            $withoutcloseelement = true;
          else{
            /*$checkClose = (preg_match_all("//>\\s*<\//", $this->readBuffer, $matches)) ? strpos($this->readBuffer, $matches[0][0]) : false;
            if ($checkClose) 
              $withoutcloseelement = true;*/
          }            
        }          
        // if it was in there
        if ($checkClose) {
          // set it to the remainder plus the length of the close string itself
          if (!$withoutcloseelement){
            $checkClose = ($checkClose + strlen($close)) % $this->options['chunkSize']; 
          }else{
            $checkClose = ($checkClose + strlen("/>")) % $this->options['chunkSize'];               
          }
        }          
        
      // if it was
      } elseif ($checkClose) {
        // add the length of the close string itself
        if ( ! $withoutcloseelement)
          $checkClose += strlen($close);
        else
          $checkClose += strlen("/>"); // "/>" symbols
      }
      
      // if we've found the opening string and we're not already reading another element
      if ($checkOpen !== false && !($store)) {
        // if we're found the end element too
        if ($checkClose !== false) {
          // append the string only between the start and end element
          $buffer .= substr($tmp, $checkOpen, ($checkClose - $checkOpen));
          
          // update the pointer
          $this->pointer += $checkClose;
          
          // let the script know we're done
          $this->reading = false;
          
        } else {
          // append the data we know to be part of this element
          $buffer .= substr($tmp, $checkOpen);
          
          // update the pointer
          $this->pointer += $this->options['chunkSize'];
          
          // let the script know we're gonna be storing all the data until we find the close element
          $store = true;
        }
        
      // if we've found the closing element
      } elseif ($checkClose !== false) {
        // update the buffer with the data upto and including the close tag
        $buffer .= substr($tmp, 0, $checkClose);
        
        // update the pointer
        $this->pointer += $checkClose;
        
        // let the script know we're done
        $this->reading = false;
        
      // if we've found the closing element, but half in the previous chunk
      } elseif ($store) {
        // update the buffer
        $buffer .= $tmp;
        
        // and the pointer
        $this->pointer += $this->options['chunkSize'];
      }        
      
    }   
    
    // return the element (or the whole file if we're not looking for elements)
    return $buffer;
  }  
}
