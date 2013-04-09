<?php
	/**
	* IF statement		
	*
	* @access	public
	* @param	string
	* @param	string
	* @param	string
	* @param	string
	* @param	string
	* @return   string
	*/
	if (!function_exists('pmxi_if')){		
		function pmxi_if($left_condition, $operand = '', $right_condition = '', $then, $else = ''){			
			$str = trim(implode(' ', array($left_condition, html_entity_decode($operand), $right_condition)));												
			return (eval ("return ($str);")) ? $then : $else;
		}		
	}

	function is_empty($var)
	{ 
	 	return empty($var);
	}


	/**
	 * Word Limiter
	 *
	 * Limits a string to X number of words.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string	the end character. Usually an ellipsis
	 * @return	string
	 */
	if ( ! function_exists('pmxi_word_limiter'))
	{
		function pmxi_word_limiter($str, $limit = 100, $end_char = '&#8230;')
		{
			if (trim($str) == '')
			{
				return $str;
			}

			preg_match('/^\s*+(?:\S++\s*+){1,'.(int) $limit.'}/', $str, $matches);

			if (strlen($str) == strlen($matches[0]))
			{
				$end_char = '';
			}

			return rtrim($matches[0]).$end_char;
		}
	}

	/**
	 * Character Limiter
	 *
	 * Limits the string based on the character count.  Preserves complete words
	 * so the character count may not be exactly as specified.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	string	the end character. Usually an ellipsis
	 * @return	string
	 */
	if ( ! function_exists('pmxi_character_limiter'))
	{
		function pmxi_character_limiter($str, $n = 500, $end_char = '&#8230;')
		{
			if (strlen($str) < $n)
			{
				return $str;
			}

			$str = preg_replace("/\s+/", ' ', str_replace(array("\r\n", "\r", "\n"), ' ', $str));

			if (strlen($str) <= $n)
			{
				return $str;
			}

			$out = "";
			foreach (explode(' ', trim($str)) as $val)
			{
				$out .= $val.' ';

				if (strlen($out) >= $n)
				{
					$out = trim($out);
					return (strlen($out) == strlen($str)) ? $out : $out.$end_char;
				}
			}
		}
	}

	function url_title($str, $separator = 'dash', $lowercase = FALSE)
	{
		if ($separator == 'dash')
		{
			$search		= '_';
			$replace	= '-';
		}
		else
		{
			$search		= '-';
			$replace	= '_';
		}

		$trans = array(
			'&\#\d+?;'				=> '',
			'&\S+?;'				=> '',
			'\s+'					=> $replace,
			'[^a-z0-9\-\._]'		=> '',
			$replace.'+'			=> $replace,
			$replace.'$'			=> $replace,
			'^'.$replace			=> $replace,
			'\.+$'					=> ''
		);

		$str = strip_tags($str);

		foreach ($trans as $key => $val)
		{
			$str = preg_replace("#".$key."#i", $val, $str);
		}

		if ($lowercase === TRUE)
		{
			$str = strtolower($str);
		}

		return trim(stripslashes($str));
	}


	function rand_char($length) {
	  $random = '';
	  for ($i = 0; $i < $length; $i++) {
	    $random .= chr(mt_rand(33, 126));
	  }
	  return $random;
	}

	/**
	 * Reading large files from remote server
	 * @ $filePath - file URL
	 * return local path of copied file
	 */
	function pmxi_copy_url_file($filePath, $detect = false){
		/*$ctx = stream_context_create();
		stream_context_set_params($ctx, array("notification" => "stream_notification_callback"));*/
		
		$type = (preg_match('%\W(csv)$%i', basename($filePath))) ? 'csv' : false;
		if (!$type) $type = (preg_match('%\W(xml)$%i', basename($filePath))) ? 'xml' : false;

		$uploads = wp_upload_dir();
		$tmpname = wp_unique_filename($uploads['path'], basename($filePath));	
		$localPath = $uploads['path']  .'/'. $tmpname;		  	   	

		$file = @fopen($filePath, "rb");

   		if (is_resource($file)){   			
   			$fp = @fopen($localPath, 'w');
		   	$first_chunk = true;
			while (!feof($file)) {
				$chunk = @fread($file, 1024);				
				if (!$type and $first_chunk and strpos($chunk, "<") !== false) $type = 'xml'; elseif (!$type and $first_chunk) $type = 'csv'; // if it's a 1st chunk, then chunk <? symbols to detect XML file
				$first_chunk = false;
			 	@fwrite($fp, $chunk);		 	
			}
			@fclose($file);
			@fclose($fp); 	   	
		}

	   	if (!file_exists($localPath)) {
	   		
	   		get_file_curl($filePath, $localPath);

	   		if (!$type){	   			
		   		$file = @fopen($localPath, "rb");	   		
				while (!feof($file)) {
					$chunk = @fread($file, 1024);					
					if (strpos($chunk, "<?") !== false) $type = 'xml'; else $type = 'csv'; // if it's a 1st chunk, then chunk <? symbols to detect XML file					
				 	break;		 	
				}
				@fclose($file);	
			}
	   		
	   	} 			
		
		return ($detect) ? array('type' => $type, 'localPath' => $localPath) : $localPath;
	}

	function pmxi_gzfile_get_contents($filename, $use_include_path = 0) {

		$type = 'csv';
		$uploads = wp_upload_dir();
		$tmpname = wp_unique_filename($uploads['path'], basename($filename));
		$fp = @fopen($uploads['path']  .'/'. $tmpname, 'w');

	    $file = @gzopen($filename, 'rb', $use_include_path);
	    if ($file) {
	        $first_chunk = true;
	        while (!gzeof($file)) {
	            $chunk = gzread($file, 1024);
	            if ($first_chunk and strpos($chunk, "<?") !== false) { $type = 'xml'; $first_chunk = false; } // if it's a 1st chunk, then chunk <? symbols to detect XML file
	            @fwrite($fp, $chunk);
	        }
	        gzclose($file);
	    }
	    @fclose($fp);
	    $localPath = $uploads['path']  .'/'. $tmpname;
	    return array('type' => $type, 'localPath' => $localPath);
	}	

	function pmxi_strip_tags_content($text, $tags = '', $invert = FALSE) {

	  preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
	  $tags = array_unique($tags[1]);
	   
	  if(is_array($tags) AND count($tags) > 0) {
	    if($invert == FALSE) {
	      return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
	    }
	    else {
	      return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
	    }
	  }
	  elseif($invert == FALSE) {
	    return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
	  }
	  return $text;
	} 

