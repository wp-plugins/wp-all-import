<?php

class PMXI_Import_Record extends PMXI_Model_Record {
	
	/**
	 * Some pre-processing logic, such as removing control characters from xml to prevent parsing errors
	 * @param string $xml
	 */
	public static function preprocessXml( & $xml) {		
		
		$xml = str_replace("&", "&amp;", str_replace("&amp;","&", $xml));
		
	}

	/**
	 * Validate XML to be valid for improt
	 * @param string $xml
	 * @param WP_Error[optional] $errors
	 * @return bool Validation status
	 */
	public static function validateXml( & $xml, $errors = NULL) {
		if (FALSE === $xml or '' == $xml) {
			$errors and $errors->add('form-validation', __('XML file does not exist, not accessible or empty', 'pmxi_plugin'));
		} else {
						
			PMXI_Import_Record::preprocessXml($xml);																						

			libxml_use_internal_errors(true);
			libxml_clear_errors();
			$_x = @simplexml_load_string($xml);
			$xml_errors = libxml_get_errors();			
			libxml_clear_errors();
			if ($xml_errors) {								
				$error_msg = '<strong>' . __('Invalid XML', 'pmxi_plugin') . '</strong><ul>';
				foreach($xml_errors as $error) {
					$error_msg .= '<li>';
					$error_msg .= __('Line', 'pmxi_plugin') . ' ' . $error->line . ', ';
					$error_msg .= __('Column', 'pmxi_plugin') . ' ' . $error->column . ', ';
					$error_msg .= __('Code', 'pmxi_plugin') . ' ' . $error->code . ': ';
					$error_msg .= '<em>' . trim(esc_html($error->message)) . '</em>';
					$error_msg .= '</li>';
				}
				$error_msg .= '</ul>';
				$errors and $errors->add('form-validation', $error_msg);				
			} else {
				return true;
			}
		}
		return false;
	}

	/**
	 * Initialize model instance
	 * @param array[optional] $data Array of record data to initialize object with
	 */
	public function __construct($data = array()) {
		parent::__construct($data);
		$this->setTable(PMXI_Plugin::getInstance()->getTablePrefix() . 'imports');
	}		
	
	/**
	 * Perform import operation
	 * @param string $xml XML string to import
	 * @param callback[optional] $logger Method where progress messages are submmitted
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function process($xml, $logger = NULL, $chunk = false, $is_cron = false) {
		add_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // do not perform special filtering for imported content
		
		$this->options += PMXI_Plugin::get_default_import_options(); // make sure all options are defined
		
		$avoid_pingbacks = PMXI_Plugin::getInstance()->getOption('pingbacks');
		$legacy_handling = PMXI_Plugin::getInstance()->getOption('legacy_special_character_handling');

		if ( $avoid_pingbacks and ! defined( 'WP_IMPORTING' ) ) define( 'WP_IMPORTING', true );		

		in_array($this->type, array('ftp')) and ($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Reading files for import...', 'pmxi_plugin'));
		in_array($this->type, array('ftp')) and ($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, sprintf(_n('%s file found', '%s files found', count(PMXI_Plugin::$session->data['pmxi_import']['local_paths']), 'pmxi_plugin'), count(PMXI_Plugin::$session->data['pmxi_import']['local_paths'])));

		$postRecord = new PMXI_Post_Record();		
		
		$tmp_files = array();
		// compose records to import
		$records = array();
		$chunk_records = array();

		if ($this->options['is_import_specified']) {
			
			foreach (preg_split('% *, *%', $this->options['import_specified'], -1, PREG_SPLIT_NO_EMPTY) as $chank) {
				if (preg_match('%^(\d+)-(\d+)$%', $chank, $mtch)) {
					$records = array_merge($records, range(intval($mtch[1]), intval($mtch[2])));
				} else {
					$records = array_merge($records, array(intval($chank)));
				}
			}
			
			$chunk_records = $records;

			if ($this->large_import == 'Yes' and !empty($records)){

				$this->set(array('count' => count($records)))->save();				

				$records_count = $this->created + $this->updated + $this->skipped + PMXI_Plugin::$session->data['pmxi_import']['errors'];

				if (!in_array($chunk, $records)){
					$this->set(array(
						'skipped' => $this->skipped + 1
					))->save();
					PMXI_Plugin::$session['pmxi_import']['skipped_records'] = $this->skipped;
					$logger and call_user_func($logger, __('<b>SKIPPED</b>: by specified records option', 'pmxi_plugin'));
					PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
					// Time Elapsed
					if ( ! $is_cron ){						
						$progress_msg = '<p class="import_process_bar"> Created ' . $this->created . ' / Updated ' . $this->updated . ' of '. $this->count .' records.</p><span class="import_percent">' . ceil(($records_count/$this->count) * 100) . '</span><span class="warnings_count">' .  PMXI_Plugin::$session['pmxi_import']['warnings'] . '</span><span class="errors_count">' . PMXI_Plugin::$session['pmxi_import']['errors'] . '</span>';
						$logger and call_user_func($logger, $progress_msg);
					}
					PMXI_Plugin::$session['pmxi_import']['chunk_number'] = ++PMXI_Plugin::$session->data['pmxi_import']['chunk_number'];
					pmxi_session_commit();
					return;
				}
				else $records = array();
			}				
		}
		try { 						
			
			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing titles...', 'pmxi_plugin'));
			$titles = XmlImportParser::factory($xml, $this->xpath, $this->template['title'], $file)->parse($records); $tmp_files[] = $file;
			if ($this->large_import != 'Yes')
				$this->set(array('count' => count($titles)))->save();							

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing excerpts...', 'pmxi_plugin'));			
			$post_excerpt = array();
			if (!empty($this->options['post_excerpt'])){
				$post_excerpt = XmlImportParser::factory($xml, $this->xpath, $this->options['post_excerpt'], $file)->parse($records); $tmp_files[] = $file;
			}
			else{
				count($titles) and $post_excerpt = array_fill(0, count($titles), '');
			}

			if ( "xpath" == $this->options['status'] ){
				($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing statuses...', 'pmxi_plugin'));			
				$post_status = array();
				if (!empty($this->options['status_xpath'])){
					$post_status = XmlImportParser::factory($xml, $this->xpath, $this->options['status_xpath'], $file)->parse($records); $tmp_files[] = $file;
				}
				else{
					count($titles) and $post_status = array_fill(0, count($titles), '');
				}
			}

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing authors...', 'pmxi_plugin'));			
			$post_author = array();
			$current_user = wp_get_current_user();

			if (!empty($this->options['author'])){
				$post_author = XmlImportParser::factory($xml, $this->xpath, $this->options['author'], $file)->parse($records); $tmp_files[] = $file;
				foreach ($post_author as $key => $author) {
					$user = get_user_by('login', $author) or $user = get_user_by('slug', $author) or $user = get_user_by('email', $author) or ctype_digit($author) and $user = get_user_by('id', $author);
					$post_author[$key] = (!empty($user)) ? $user->ID : $current_user->ID;
				}
			}
			else{								
				count($titles) and $post_author = array_fill(0, count($titles), $current_user->ID);
			}			

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing slugs...', 'pmxi_plugin'));			
			$post_slug = array();
			if (!empty($this->options['post_slug'])){
				$post_slug = XmlImportParser::factory($xml, $this->xpath, $this->options['post_slug'], $file)->parse($records); $tmp_files[] = $file;
			}
			else{
				count($titles) and $post_slug = array_fill(0, count($titles), '');
			}

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing contents...', 'pmxi_plugin'));			 						
			$contents = XmlImportParser::factory(
				(intval($this->template['is_keep_linebreaks']) ? $xml : preg_replace('%\r\n?|\n%', ' ', $xml)),
				$this->xpath,
				$this->template['content'],
				$file)->parse($records
			); $tmp_files[] = $file;						
										
			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing dates...', 'pmxi_plugin'));
			if ('specific' == $this->options['date_type']) {
				$dates = XmlImportParser::factory($xml, $this->xpath, $this->options['date'], $file)->parse($records); $tmp_files[] = $file;
				$warned = array(); // used to prevent the same notice displaying several times
				foreach ($dates as $i => $d) {
					$time = strtotime($d);
					if (FALSE === $time) {
						in_array($d, $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'pmxi_plugin'), $warned[] = $d));
						PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
						$time = time();
					}
					$dates[$i] = date('Y-m-d H:i:s', $time);
				}
			} else {
				$dates_start = XmlImportParser::factory($xml, $this->xpath, $this->options['date_start'], $file)->parse($records); $tmp_files[] = $file;
				$dates_end = XmlImportParser::factory($xml, $this->xpath, $this->options['date_end'], $file)->parse($records); $tmp_files[] = $file;
				$warned = array(); // used to prevent the same notice displaying several times
				foreach ($dates_start as $i => $d) {
					$time_start = strtotime($dates_start[$i]);
					if (FALSE === $time_start) {
						in_array($dates_start[$i], $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'pmxi_plugin'), $warned[] = $dates_start[$i]));
						PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
						$time_start = time();
					}
					$time_end = strtotime($dates_end[$i]);
					if (FALSE === $time_end) {
						in_array($dates_end[$i], $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'pmxi_plugin'), $warned[] = $dates_end[$i]));
						PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
						$time_end = time();
					}					
					$dates[$i] = date('Y-m-d H:i:s', mt_rand($time_start, $time_end));
				}
			}
			
			$tags = array();
			if ($this->options['tags']) {
				($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing tags...', 'pmxi_plugin'));
				$tags_raw = XmlImportParser::factory($xml, $this->xpath, $this->options['tags'], $file)->parse($records); $tmp_files[] = $file;
				foreach ($tags_raw as $i => $t_raw) {
					$tags[$i] = '';
					if ('' != $t_raw) $tags[$i] = implode(', ', str_getcsv($t_raw, $this->options['tags_delim']));
				}
			} else {
				count($titles) and $tags = array_fill(0, count($titles), '');
			}

			// [posts categories]
			require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');

			if ('post' == $this->options['type']) {				
								
				$cats = array();

				$categories_hierarchy = (!empty($this->options['categories'])) ?  json_decode($this->options['categories']) : array();

				if ((!empty($categories_hierarchy) and is_array($categories_hierarchy))){						

					($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing categories...', 'pmxi_plugin'));
					$categories = array();
					
					foreach ($categories_hierarchy as $k => $category): if ("" == $category->xpath) continue;							
						$cats_raw = XmlImportParser::factory($xml, $this->xpath, str_replace('\'','"',$category->xpath), $file)->parse($records); $tmp_files[] = $file;										
						$warned = array(); // used to prevent the same notice displaying several times
						foreach ($cats_raw as $i => $c_raw) {
							if (empty($categories_hierarchy[$k]->cat_ids[$i])) $categories_hierarchy[$k]->cat_ids[$i] = array();
							if (empty($cats[$i])) $cats[$i] = array();
							$count_cats = count($cats[$i]);
							
							$delimeted_categories = explode($this->options['categories_delim'],  $c_raw);
							
							if ('' != $c_raw) foreach (explode($this->options['categories_delim'], $c_raw) as $j => $cc) if ('' != $cc) {																								
								$cat = get_term_by('name', trim($cc), 'category') or $cat = get_term_by('slug', trim($cc), 'category') or ctype_digit($cc) and $cat = get_term_by('id', trim($cc), 'category');									
								if ( !empty($category->parent_id) ) {
									foreach ($categories_hierarchy as $key => $value){
										if ($value->item_id == $category->parent_id and !empty($value->cat_ids[$i])){												
											foreach ($value->cat_ids[$i] as $parent) {		
												if (!$j or !$this->options['categories_auto_nested']){
													$cats[$i][] = array(
														'name' => trim($cc),
														'parent' => (is_array($parent)) ? $parent['name'] : $parent, // if parent taxonomy exists then return ID else return TITLE
														'assign' => $category->assign
													);
												}
												elseif($this->options['categories_auto_nested']){
													$cats[$i][] = array(
														'name' => trim($cc),
														'parent' => (!empty($delimeted_categories[$j - 1])) ? trim($delimeted_categories[$j - 1]) : false, // if parent taxonomy exists then return ID else return TITLE
														'assign' => $category->assign
													);	
												}													
											}
										}
									}
								}
								else {
									if (!$j or !$this->options['categories_auto_nested']){
										$cats[$i][] = array(
											'name' => trim($cc),
											'parent' => false,
											'assign' => $category->assign
										);
									}
									elseif ($this->options['categories_auto_nested']){
										$cats[$i][] = array(
											'name' => trim($cc),
											'parent' => (!empty($delimeted_categories[$j - 1])) ? trim($delimeted_categories[$j - 1]) : false,
											'assign' => $category->assign
										);
									}
									
								}									
							}
							if ($count_cats < count($cats[$i])) $categories_hierarchy[$k]->cat_ids[$i][] = $cats[$i][count($cats[$i]) - 1];
						}						
					endforeach;					
				} else{
					count($titles) and $cats = array_fill(0, count($titles), '');
				}
				
			}			
			// [/posts categories]
			
			// [custom taxonomies]
			$taxonomies = array();
			$taxonomies_param = $this->options['type'].'_taxonomies';
			if ('page' == $this->options['type']) {
				$taxonomies_object_type = 'page';
			} elseif ('' != $this->options['custom_type']) {
				$taxonomies_object_type = $this->options['custom_type'];
			} else {
				$taxonomies_object_type = 'post';
			}

			if (!empty($this->options[$taxonomies_param]) and is_array($this->options[$taxonomies_param])): foreach ($this->options[$taxonomies_param] as $tx_name => $tx_template) if ('' != $tx_template) {
				$tx = get_taxonomy($tx_name);		
				$taxonomies[$tx_name] = array();
				if (!empty($tx->object_type) and in_array($taxonomies_object_type, $tx->object_type)) {
					($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, sprintf(__('Composing terms for `%s` taxonomy...', 'pmxi_plugin'), $tx->labels->name));
					$txes = array();
					
					$taxonomies_hierarchy = json_decode($tx_template);
					foreach ($taxonomies_hierarchy as $k => $taxonomy){	if ("" == $taxonomy->xpath) continue;								
						$txes_raw =  XmlImportParser::factory($xml, $this->xpath, str_replace('\'','"',$taxonomy->xpath), $file)->parse($records); $tmp_files[] = $file;						
						$warned = array();
						foreach ($txes_raw as $i => $tx_raw) {
							if (empty($taxonomies_hierarchy[$k]->txn_names[$i])) $taxonomies_hierarchy[$k]->txn_names[$i] = array();
							if (empty($taxonomies[$tx_name][$i])) $taxonomies[$tx_name][$i] = array();
							$count_cats = count($taxonomies[$tx_name][$i]);
							
							$delimeted_taxonomies = explode((!empty($taxonomy->delim)) ? $taxonomy->delim : ',', $tx_raw);

							if ('' != $tx_raw) foreach (explode((!empty($taxonomy->delim)) ? $taxonomy->delim : ',', $tx_raw) as $j => $cc) if ('' != $cc) {										
																																
								$cat = get_term_by('name', trim($cc), $tx_name) or $cat = get_term_by('slug', trim($cc), $tx_name) or ctype_digit($cc) and $cat = get_term_by('id', $cc, $tx_name);
								if (!empty($taxonomy->parent_id)) {																			
									foreach ($taxonomies_hierarchy as $key => $value){
										if ($value->item_id == $taxonomy->parent_id and !empty($value->txn_names[$i])){													
											foreach ($value->txn_names[$i] as $parent) {	
												if (!$j or !$taxonomy->auto_nested){																																																																
													$taxonomies[$tx_name][$i][] = array(
														'name' => trim($cc),
														'parent' => $parent,
														'assign' => $taxonomy->assign
													);
												}
												elseif ($taxonomy->auto_nested){
													$taxonomies[$tx_name][$i][] = array(
														'name' => trim($cc),
														'parent' => (!empty($delimeted_taxonomies[$j - 1])) ? trim($delimeted_taxonomies[$j - 1]) : false,
														'assign' => $taxonomy->assign
													);
												}																	
											}											
										}
									}
									
								}
								else {	
									if (!$j or !$taxonomy->auto_nested){
										$taxonomies[$tx_name][$i][] = array(
											'name' => trim($cc),
											'parent' => false,
											'assign' => $taxonomy->assign
										);
									}
									elseif ($taxonomy->auto_nested) {
										$taxonomies[$tx_name][$i][] = array(
											'name' => trim($cc),
											'parent' => (!empty($delimeted_taxonomies[$j - 1])) ? trim($delimeted_taxonomies[$j - 1]) : false,
											'assign' => $taxonomy->assign
										);
									}
								}								
							}
							if ($count_cats < count($taxonomies[$tx_name][$i])) $taxonomies_hierarchy[$k]->txn_names[$i][] = $taxonomies[$tx_name][$i][count($taxonomies[$tx_name][$i]) - 1];
						}
					}
				}
			}; endif;
			// [/custom taxonomies]											

			// serialized featured images
			if ( ! (($uploads = wp_upload_dir()) && false === $uploads['error'])) {
				$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $uploads['error']);
				$logger and call_user_func($logger, __('<b>WARNING</b>: No featured images will be created', 'pmxi_plugin'));				
				PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];				
			} else {
				($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing URLs for featured images...', 'pmxi_plugin'));
				$featured_images = array();				
				if ($this->options['featured_image']) {
					// Detect if images is separated by comma										
					$imgs = ( "" == $this->options['featured_delim']) ? explode("\n", $this->options['featured_image']) : explode(',',$this->options['featured_image']);
					if (!empty($imgs)){
						$parse_multiple = true;
						foreach($imgs as $img) if (!preg_match("/{.*}/", trim($img))) $parse_multiple = false;			

						if ($parse_multiple)
						{
							foreach($imgs as $img) 
							{								
								$posts_images = XmlImportParser::factory($xml, $this->xpath, trim($img), $file)->parse($records); $tmp_files[] = $file;								
								foreach($posts_images as $i => $val) $featured_images[$i][] = $val;																
							}
						}
						else
						{
							$featured_images = XmlImportParser::factory($xml, $this->xpath, $this->options['featured_image'], $file)->parse($records); $tmp_files[] = $file;															
						}
					}
					
				} else {
					count($titles) and $featured_images = array_fill(0, count($titles), '');
				}
			}	

			// serialized images meta data
			if ( $this->options['set_image_meta_data'] ){
				$uploads = wp_upload_dir();
									
				// serialized images meta titles
				($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing image meta data (titles)...', 'pmxi_plugin'));
				$image_meta_titles = array();				

				if ($this->options['image_meta_title']) {
					// Detect if images is separated by comma
					$imgs = ( "" == $this->options['image_meta_title_delim']) ? explode("\n",$this->options['image_meta_title']) : explode(',',$this->options['image_meta_title']);
					
					if (!empty($imgs)){
						$parse_multiple = true;
						foreach($imgs as $img) if (!preg_match("/{.*}/", trim($img))) $parse_multiple = false;			

						if ($parse_multiple)
						{
							foreach($imgs as $img) 
							{								
								$posts_images = XmlImportParser::factory($xml, $this->xpath, trim($img), $file)->parse($records); $tmp_files[] = $file;								
								foreach($posts_images as $i => $val) $image_meta_titles[$i][] = $val;								
							}
						}
						else
						{
							$image_meta_titles = XmlImportParser::factory($xml, $this->xpath, $this->options['image_meta_title'], $file)->parse($records); $tmp_files[] = $file;								
						}
					}
					
				} else {
					count($titles) and $image_meta_titles = array_fill(0, count($titles), '');
				}

				// serialized images meta captions
				($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing image meta data (captions)...', 'pmxi_plugin'));
				$image_meta_captions = array();				
				if ($this->options['image_meta_caption']) {
					// Detect if images is separated by comma
					$imgs = ( "" == $this->options['image_meta_caption_delim']) ? explode("\n",$this->options['image_meta_caption']) : explode(',',$this->options['image_meta_caption']);
					if (!empty($imgs)){
						$parse_multiple = true;
						foreach($imgs as $img) if (!preg_match("/{.*}/", trim($img))) $parse_multiple = false;			

						if ($parse_multiple)
						{
							foreach($imgs as $img) 
							{								
								$posts_images = XmlImportParser::factory($xml, $this->xpath, trim($img), $file)->parse($records); $tmp_files[] = $file;								
								foreach($posts_images as $i => $val) $image_meta_captions[$i][] = $val;								
							}
						}
						else
						{
							$image_meta_captions = XmlImportParser::factory($xml, $this->xpath, $this->options['image_meta_caption'], $file)->parse($records); $tmp_files[] = $file;								
						}
					}
					
				} else {
					count($titles) and $image_meta_captions = array_fill(0, count($titles), '');
				}
				// serialized images meta alt text
				($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing image meta data (alt text)...', 'pmxi_plugin'));
				$image_meta_alts = array();				
				if ($this->options['image_meta_alt']) {
					// Detect if images is separated by comma
					$imgs = ( "" == $this->options['image_meta_alt_delim']) ? explode("\n",$this->options['image_meta_alt']) : explode(',',$this->options['image_meta_alt']);
					if (!empty($imgs)){
						$parse_multiple = true;
						foreach($imgs as $img) if (!preg_match("/{.*}/", trim($img))) $parse_multiple = false;			

						if ($parse_multiple)
						{
							foreach($imgs as $img) 
							{								
								$posts_images = XmlImportParser::factory($xml, $this->xpath, trim($img), $file)->parse($records); $tmp_files[] = $file;								
								foreach($posts_images as $i => $val) $image_meta_alts[$i][] = $val;								
							}
						}
						else
						{
							$image_meta_alts = XmlImportParser::factory($xml, $this->xpath, $this->options['image_meta_alt'], $file)->parse($records); $tmp_files[] = $file;								
						}
					}
					
				} else {
					count($titles) and $image_meta_alts = array_fill(0, count($titles), '');
				}
				// serialized images meta description
				($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing image meta data (description)...', 'pmxi_plugin'));
				$image_meta_descriptions = array();				
				if ($this->options['image_meta_description']) {
					// Detect if images is separated by comma
					$imgs = ( "" == $this->options['image_meta_description_delim']) ? explode("\n",$this->options['image_meta_description']) : explode(',',$this->options['image_meta_description']);
					if (!empty($imgs)){
						$parse_multiple = true;
						foreach($imgs as $img) if (!preg_match("/{.*}/", trim($img))) $parse_multiple = false;			

						if ($parse_multiple)
						{
							foreach($imgs as $img) 
							{								
								$posts_images = XmlImportParser::factory($xml, $this->xpath, trim($img), $file)->parse($records); $tmp_files[] = $file;								
								foreach($posts_images as $i => $val) $image_meta_descriptions[$i][] = $val;								
							}
						}
						else
						{
							$image_meta_descriptions = XmlImportParser::factory($xml, $this->xpath, $this->options['image_meta_description'], $file)->parse($records); $tmp_files[] = $file;								
						}
					}
					
				} else {
					count($titles) and $image_meta_descriptions = array_fill(0, count($titles), '');
				}								
			}

			// Composing images suffix
			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $this->options['auto_rename_images'] and $logger and call_user_func($logger, __('Composing images suffix...', 'pmxi_plugin'));			
			$auto_rename_images = array();
			if ( $this->options['auto_rename_images'] and ! empty($this->options['auto_rename_images_suffix'])){
				$auto_rename_images = XmlImportParser::factory($xml, $this->xpath, $this->options['auto_rename_images_suffix'], $file)->parse($records); $tmp_files[] = $file;
			}
			else{
				count($titles) and $auto_rename_images = array_fill(0, count($titles), '');
			}

			// serialized attachments
			if ( ! (($uploads = wp_upload_dir()) && false === $uploads['error'])) {
				$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $uploads['error']);				
				$logger and call_user_func($logger, __('<b>WARNING</b>: No attachments will be created', 'pmxi_plugin')); 				
				PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session['pmxi_import']['warnings'];
			} else {
				($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing URLs for attachments files...', 'pmxi_plugin'));
				$attachments = array();

				if ($this->options['attachments']) {
					// Detect if attachments is separated by comma
					$atchs = explode(',', $this->options['attachments']);					
					if (!empty($atchs)){
						$parse_multiple = true;
						foreach($atchs as $atch) if (!preg_match("/{.*}/", trim($atch))) $parse_multiple = false;			

						if ($parse_multiple)
						{							
							foreach($atchs as $atch) 
							{								
								$posts_attachments = XmlImportParser::factory($xml, $this->xpath, trim($atch), $file)->parse($records); $tmp_files[] = $file;																
								foreach($posts_attachments as $i => $val) $attachments[$i][] = $val;								
							}
						}
						else
						{
							$attachments = XmlImportParser::factory($xml, $this->xpath, $this->options['attachments'], $file)->parse($records); $tmp_files[] = $file;								
						}
					}
					
				} else {
					count($titles) and $attachments = array_fill(0, count($titles), '');
				}
			}				

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing unique keys...', 'pmxi_plugin'));
			$unique_keys = XmlImportParser::factory($xml, $this->xpath, $this->options['unique_key'], $file)->parse($records); $tmp_files[] = $file;
			
			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Processing posts...', 'pmxi_plugin'));
			
			if ('post' == $this->options['type'] and '' != $this->options['custom_type']) {
				$post_type = $this->options['custom_type'];
			} else {
				$post_type = $this->options['type'];
			}					

			// Import WooCommerce products
			if ( $post_type == "product" and class_exists('PMWI_Plugin')) {				

				$product = new PMWI_Import_Record();

				extract( $product->process($this, count($titles), $xml, $logger, $chunk) );
												
			}

			$current_post_ids = array();
			foreach ($titles as $i => $void) {							

				if (empty($titles[$i])) {
					if (class_exists('PMWI_Plugin') and !empty($single_product_parent_ID[$i])){
						$titles[$i] = $single_product_parent_ID[$i] . ' Product Variation';
					}
					else{
						$logger and call_user_func($logger, __('<b>SKIPPED</b>: by empty title', 'pmxi_plugin'));						
						PMXI_Plugin::$session['pmxi_import']['chunk_number'] = ++PMXI_Plugin::$session->data['pmxi_import']['chunk_number'];	
						PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
						$this->set(array(							
							'skipped' => $this->skipped + 1
						))->save();				
						PMXI_Plugin::$session['pmxi_import']['skipped_records'] = $this->skipped;	
						if ( ! $is_cron ){
							$records_count = $this->created + $this->updated + $this->skipped + PMXI_Plugin::$session->data['pmxi_import']['errors'];
							$progress_msg = '<p class="import_process_bar"> ' . __('Created', 'pmxi_plugin') . ' ' . $this->created . ' / ' . __('Updated','pmxi_plugin') . ' ' . $this->updated . ' ' . __('of', 'pmxi_plugin') . ' '. $this->count .' ' . __('records', 'pmxi_plugin') . '.</p><span class="import_percent">' . ceil(($records_count/$this->count) * 100) . '</span><span class="warnings_count">' .  PMXI_Plugin::$session->data['pmxi_import']['warnings'] . '</span><span class="errors_count">' . PMXI_Plugin::$session->data['pmxi_import']['errors'] . '</span>';
							$logger and call_user_func($logger, $progress_msg);
						}
						pmxi_session_commit();	
						continue;										
					}
				}
						
				$articleData = array(
					'post_type' => $post_type,
					'post_status' => ("xpath" == $this->options['status']) ? $post_status[$i] : $this->options['status'],
					'comment_status' => $this->options['comment_status'],
					'ping_status' => $this->options['ping_status'],
					'post_title' => ($this->template['is_leave_html']) ? html_entity_decode($titles[$i]) : $titles[$i], 
					'post_excerpt' => ($this->template['is_leave_html']) ? html_entity_decode($post_excerpt[$i]) : $post_excerpt[$i],
					'post_name' => $post_slug[$i],
					'post_content' => ($this->template['is_leave_html']) ? html_entity_decode($contents[$i]) : $contents[$i],
					'post_date' => $dates[$i],
					'post_date_gmt' => get_gmt_from_date($dates[$i]),
					'post_author' => $post_author[$i] ,
					'tags_input' => $tags[$i]
				);				

				if ('post' != $articleData['post_type']){					
					$articleData += array(
						'menu_order' => $this->options['order'],
						'post_parent' => $this->options['parent'],
					);
				}				
				
				// Re-import Records Matching
				$post_to_update = false; $post_to_update_id = false;
				
				// if Auto Matching re-import option selected
				if ("manual" != $this->options['duplicate_matching']){
					$postRecord->clear();
					// find corresponding article among previously imported
					$postRecord->getBy(array(
						'unique_key' => $unique_keys[$i],
						'import_id' => $this->id,
					));
					if ( ! $postRecord->isEmpty() ) 
						$post_to_update = get_post($post_to_update_id = $postRecord->post_id);
											
				// if Manual Matching re-import option seleted
				} else {
					
					$postRecord->clear();
					// find corresponding article among previously imported
					$postRecord->getBy(array(
						'unique_key' => $unique_keys[$i],
						'import_id' => $this->id,
					));
					
					if ('custom field' == $this->options['duplicate_indicator']) {
						$custom_duplicate_value = XmlImportParser::factory($xml, $this->xpath, $this->options['custom_duplicate_value'], $file)->parse($records); $tmp_files[] = $file;
						$custom_duplicate_name = XmlImportParser::factory($xml, $this->xpath, $this->options['custom_duplicate_name'], $file)->parse($records); $tmp_files[] = $file;
					}
					else{
						count($titles) and $custom_duplicate_name = $custom_duplicate_value = array_fill(0, count($titles), '');					
					}

					// handle duplicates according to import settings
					if ($duplicates = $this->findDuplicates($articleData, $custom_duplicate_name[$i], $custom_duplicate_value[$i], $this->options['duplicate_indicator'])) {															
						$duplicate_id = array_shift($duplicates);
						if ($duplicate_id) {														
							$post_to_update = get_post($post_to_update_id = $duplicate_id);
						}						
					}
				}
				
				// Duplicate record is founded
				if ($post_to_update){
					// Do not update already existing records option selected
					if ("yes" == $this->options['is_keep_former_posts']) {
												
						$tmp_array = (!empty($this->current_post_ids)) ? json_decode($this->current_post_ids, true) : array();
						if ( ! in_array($post_to_update_id, $tmp_array) ){
							$tmp_array[] = $post_to_update_id;
							$this->set(array(
								'current_post_ids' => json_encode($tmp_array)
							))->save();
						}
											
						// Do not update product variations
						if ($post_type == "product" and class_exists('PMWI_Plugin')){
							
							$children = get_posts( array(
								'post_parent' 	=> $post_to_update_id,
								'posts_per_page'=> -1,
								'post_type' 	=> 'product_variation',
								'fields' 		=> 'ids',
								'post_status'	=> 'publish'
							) );

							if ( $children ) {
								foreach ( $children as $child ) {
									
									$tmp_array = (!empty($this->current_post_ids)) ? json_decode($this->current_post_ids, true) : array();
									if ( ! in_array($child, $tmp_array)){
										$tmp_array[] = $child;
										$this->set(array(
											'current_post_ids' => json_encode($tmp_array)
										))->save();
									}
								}
							}
						}
						$this->set(array(
							'skipped' => $this->skipped + 1
						))->save();
						PMXI_Plugin::$session['pmxi_import']['skipped_records'] = $this->skipped;	
						$logger and call_user_func($logger, sprintf(__('<b>SKIPPED</b>: Previously imported record found for `%s`', 'pmxi_plugin'), $articleData['post_title']));
						PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
						if ( ! $is_cron ){
							$records_count = $this->created + $this->updated + $this->skipped + PMXI_Plugin::$session->data['pmxi_import']['errors'];
							$progress_msg = '<p class="import_process_bar"> ' . __('Created', 'pmxi_plugin') . ' ' . $this->created . ' / ' . __('Updated','pmxi_plugin') . ' ' . $this->updated . ' ' . __('of', 'pmxi_plugin') . ' '. $this->count .' ' . __('records', 'pmxi_plugin') . '.</p><span class="import_percent">' . ceil(($records_count/$this->count) * 100) . '</span><span class="warnings_count">' .  PMXI_Plugin::$session->data['pmxi_import']['warnings'] . '</span><span class="errors_count">' . PMXI_Plugin::$session->data['pmxi_import']['errors'] . '</span>';
							$logger and call_user_func($logger, $progress_msg);
						}					
						PMXI_Plugin::$session['pmxi_import']['chunk_number'] = ++PMXI_Plugin::$session->data['pmxi_import']['chunk_number'];	
						pmxi_session_commit();	
						continue;
					}					

					$articleData['ID'] = $post_to_update_id;
					// preserve date of already existing article when duplicate is found					
					if ($this->options['is_keep_categories']) { // preserve categories and tags of already existing article if corresponding setting is specified
						$cats_list = get_the_category($articleData['ID']);
						$existing_cats = array();
						if (is_wp_error($cats_list)) {
							$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current categories for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
							PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
						} else {
							$cats_new = array();
							foreach ($cats_list as $c) {
								$cats_new[] = $c->slug;
							}
							$existing_cats[$i] = $cats_new;							
						}
						
						$tags_list = get_the_tags($articleData['ID']);
						if (is_wp_error($tags_list)) {
							$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current tags for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
							PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
						} else {
							$tags_new = array();
							if ($tags_list) foreach ($tags_list as $t) {
								$tags_new[] = $t->name;
							}
							$articleData['tags_input'] = implode(', ', $tags_new);
						}
						$existing_taxonomies = array();
						foreach (array_keys($taxonomies) as $tx_name) {
							$txes_list = get_the_terms($articleData['ID'], $tx_name);
							if (is_wp_error($txes_list)) {
								$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current taxonomies for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
								PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
							} else {
								$txes_new = array();
								if (!empty($txes_list)):
									foreach ($txes_list as $t) {
										$txes_new[] = $t->slug;
									}
								endif;
								$existing_taxonomies[$tx_name][$i] = $txes_new;								
							}
						}							
					}	
					else{
						foreach (array_keys($taxonomies) as $tx_name) wp_set_object_terms($articleData['ID'], NULL, $tx_name); 
					}						
					if ($this->options['is_keep_dates']) { // preserve date of already existing article when duplicate is found
						$articleData['post_date'] = $post_to_update->post_date;
						$articleData['post_date_gmt'] = $post_to_update->post_date_gmt;
					}
					if ($this->options['is_keep_status']) { // preserve status and trashed flag
						$articleData['post_status'] = $post_to_update->post_status;
					}
					if ($this->options['is_keep_content']){ 
						$articleData['post_content'] = $post_to_update->post_content;
					}
					if ($this->options['is_keep_title']){ 
						$articleData['post_title'] = $post_to_update->post_title;												
					}
					if ($this->options['is_keep_excerpt']){ 
						$articleData['post_excerpt'] = $post_to_update->post_excerpt;												
					}										
					if ($this->options['is_keep_menu_order']){ 
						$articleData['menu_order'] = $post_to_update->menu_order;
					}
					if ($this->options['is_keep_parent']){ 
						$articleData['post_parent'] = $post_to_update->post_parent;
					}
					// handle obsolete attachments (i.e. delete or keep) according to import settings
					if ( ! $this->options['is_keep_images'] and ! $this->options['is_keep_attachments_on_update'] and ! $this->options['no_create_featured_image']){ 								
						wp_delete_attachments($articleData['ID'], $this->options['download_images']);
					}

				}
				elseif ( ! $postRecord->isEmpty() ){
					
					// existing post not found though it's track was found... clear the leftover, plugin will continue to treat record as new
					$postRecord->delete();
					
				}
				
				// no new records are created. it will only update posts it finds matching duplicates for
				if ($this->options['not_create_records'] and empty($articleData['ID'])){ 
					PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
					$this->set(array(
						'skipped' => $this->skipped + 1
					))->save();
					PMXI_Plugin::$session['pmxi_import']['skipped_records'] = $this->skipped;
					$logger and call_user_func($logger, sprintf(__('<b>SKIPPED</b>: by "Not add new records" option for `%s`', 'pmxi_plugin'), $articleData['post_title']));
					if ( ! $is_cron ){
						$records_count = $this->created + $this->updated + $this->skipped + PMXI_Plugin::$session->data['pmxi_import']['errors'];
						$progress_msg = '<p class="import_process_bar"> '. __('Created','pmxi_plugin') . ' ' . $this->created . ' / ' . __('Updated','pmxi_plugin') . ' ' . $this->updated . ' ' . __('of', 'pmxi_plugin') . ' '. $this->count .' ' . __('records', 'pmxi_plugin') . '.</p><span class="import_percent">' . ceil(($records_count/$this->count) * 100) . '</span><span class="warnings_count">' .  PMXI_Plugin::$session->data['pmxi_import']['warnings'] . '</span><span class="errors_count">' . PMXI_Plugin::$session->data['pmxi_import']['errors'] . '</span>';
						$logger and call_user_func($logger, $progress_msg);
					}					
					PMXI_Plugin::$session['pmxi_import']['chunk_number'] = ++PMXI_Plugin::$session->data['pmxi_import']['chunk_number'];					
					pmxi_session_commit();	
					continue;
				}

				// cloak urls with `WP Wizard Cloak` if corresponding option is set
				if ( ! empty($this->options['is_cloak']) and class_exists('PMLC_Plugin')) {
					if (preg_match_all('%<a\s[^>]*href=(?(?=")"([^"]*)"|(?(?=\')\'([^\']*)\'|([^\s>]*)))%is', $articleData['post_content'], $matches, PREG_PATTERN_ORDER)) {
						$hrefs = array_unique(array_merge(array_filter($matches[1]), array_filter($matches[2]), array_filter($matches[3])));
						foreach ($hrefs as $url) {
							if (preg_match('%^\w+://%i', $url)) { // mask only links having protocol
								// try to find matching cloaked link among already registered ones
								$list = new PMLC_Link_List(); $linkTable = $list->getTable();
								$rule = new PMLC_Rule_Record(); $ruleTable = $rule->getTable();
								$dest = new PMLC_Destination_Record(); $destTable = $dest->getTable();
								$list->join($ruleTable, "$ruleTable.link_id = $linkTable.id")
									->join($destTable, "$destTable.rule_id = $ruleTable.id")
									->setColumns("$linkTable.*")
									->getBy(array(
										"$linkTable.destination_type =" => 'ONE_SET',
										"$linkTable.is_trashed =" => 0,
										"$linkTable.preset =" => '',
										"$linkTable.expire_on =" => '0000-00-00',
										"$ruleTable.type =" => 'ONE_SET',
										"$destTable.weight =" => 100,
										"$destTable.url LIKE" => $url,
									), NULL, 1, 1)->convertRecords();
								if ($list->count()) { // matching link found
									$link = $list[0];
								} else { // register new cloaked link
									global $wpdb;
									$slug = max(
										intval($wpdb->get_var("SELECT MAX(CONVERT(name, SIGNED)) FROM $linkTable")),
										intval($wpdb->get_var("SELECT MAX(CONVERT(slug, SIGNED)) FROM $linkTable")),
										0
									);
									$i = 0; do {
										is_int(++$slug) and $slug > 0 or $slug = 1;
										$is_slug_found = ! intval($wpdb->get_var("SELECT COUNT(*) FROM $linkTable WHERE name = '$slug' OR slug = '$slug'"));
									} while( ! $is_slug_found and $i++ < 100000);
									if ($is_slug_found) {
										$link = new PMLC_Link_Record(array(
											'name' => strval($slug),
											'slug' => strval($slug),
											'header_tracking_code' => '',
											'footer_tracking_code' => '',
											'redirect_type' => '301',
											'destination_type' => 'ONE_SET',
											'preset' => '',
											'forward_url_params' => 1,
											'no_global_tracking_code' => 0,
											'expire_on' => '0000-00-00',
											'created_on' => date('Y-m-d H:i:s'),
											'is_trashed' => 0,
										));
										$link->insert();
										$rule = new PMLC_Rule_Record(array(
											'link_id' => $link->id,
											'type' => 'ONE_SET',
											'rule' => '',
										));
										$rule->insert();
										$dest = new PMLC_Destination_Record(array(
											'rule_id' => $rule->id,
											'url' => $url,
											'weight' => 100,
										));
										$dest->insert();
									} else {
										$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to create cloaked link for %s', 'pmxi_plugin'), $url));
										PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
										$link = NULL;
									}
								}
								if ($link) { // cloaked link is found or created for url
									$articleData['post_content'] = preg_replace('%' . preg_quote($url, '%') . '(?=([\s\'"]|$))%i', $link->getUrl(), $articleData['post_content']);
								}
							}
						}
					}
				}															

				// insert article being imported				
				$pid = wp_insert_post($articleData, true);				

				if (is_wp_error($pid)) {
					$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': ' . $pid->get_error_message());
					PMXI_Plugin::$session['pmxi_import']['errors'] = ++PMXI_Plugin::$session->data['pmxi_import']['errors'];
				} else {										
					
					$tmp_array = (!empty($this->current_post_ids)) ? json_decode($this->current_post_ids, true) : array();
					if ( ! in_array($pid, $tmp_array)){
						$tmp_array[] = $pid;
						$this->set(array(
							'current_post_ids' => json_encode($tmp_array)
						))->save();
					}
					
					if ("manual" != $this->options['duplicate_matching'] or empty($articleData['ID'])){						
						// associate post with import
						$postRecord->isEmpty() and $postRecord->set(array(
							'post_id' => $pid,
							'import_id' => $this->id,
							'unique_key' => $unique_keys[$i],
							'product_key' => (class_exists('PMWI_Plugin')) ? $single_product_ID[$i] : null
						))->insert();
					}

					// [post format]
					if ( current_theme_supports( 'post-formats' ) && post_type_supports( $post_type, 'post-formats' ) ){						
						set_post_format($pid, $this->options['post_format'] ); 
					}
					// [/post format]									

					// Woocommerce add-on
					if ( $post_type == "product" and class_exists('PMWI_Plugin')){

						$product->import($pid, $i, $this, $articleData, $xml, $is_cron);						

					} 

					if ('post' != $articleData['post_type'] and !empty($this->options['page_template'])) update_post_meta($pid, '_wp_page_template', $this->options['page_template']);
					
					// [featured image]
					if ( ! empty($uploads) and false === $uploads['error'] and !empty($featured_images[$i]) and (empty($articleData['ID']) or empty($this->options['is_keep_images']) or ! has_post_thumbnail($pid))) {
						
						require_once(ABSPATH . 'wp-admin/includes/image.php');
						
						if ( ! is_array($featured_images[$i]) ) $featured_images[$i] = array($featured_images[$i]);
						if ( ! is_array($image_meta_titles[$i]) ) $image_meta_titles[$i] = array($image_meta_titles[$i]);
						if ( ! is_array($image_meta_captions[$i]) ) $image_meta_captions[$i] = array($image_meta_captions[$i]);
						if ( ! is_array($image_meta_descriptions[$i]) ) $image_meta_descriptions[$i] = array($image_meta_descriptions[$i]);
						$post_thumbnail = false;	
						$success_images = false;	
						$gallery_attachment_ids = array();											

						$_pmxi_images = array();

						foreach ($featured_images[$i] as $k => $featured_image)
						{							
							$imgs = ( ! empty($this->options['featured_delim']) ) ? str_getcsv($featured_image, $this->options['featured_delim']) : explode("\n", $featured_image);
							if ( $this->options['set_image_meta_data'] ){								
								$img_titles = ( ! empty($this->options['image_meta_title_delim']) ) ? str_getcsv($image_meta_titles[$i][$k], $this->options['image_meta_title_delim']) : array($image_meta_titles[$i][$k]);
								$img_captions = ( ! empty($this->options['image_meta_caption_delim']) ) ? str_getcsv($image_meta_captions[$i][$k], $this->options['image_meta_caption_delim']) : array($image_meta_captions[$i][$k]);
								$img_alts = ( ! empty($this->options['image_meta_alt_delim']) ) ? str_getcsv($image_meta_alts[$i][$k], $this->options['image_meta_alt_delim']) : array($image_meta_alts[$i][$k]);
								$img_descriptions = ( ! empty($this->options['image_meta_description_delim']) ) ? str_getcsv($image_meta_descriptions[$i][$k], $this->options['image_meta_description_delim']) : array($image_meta_descriptions[$i][$k]);
							}
							if (!empty($imgs)) {											

								foreach ($imgs as $img_key => $img_url) { if (empty($img_url)) continue;																		

									$url = str_replace(" ", "%20", trim(pmxi_convert_encoding($img_url)));
									$img_ext = pmxi_get_remote_image_ext($url);										
									$image_name = (($this->options['auto_rename_images'] and "" != $auto_rename_images[$i]) ? url_title($auto_rename_images[$i] . '_' . (($this->options['images_name'] != 'auto') ? array_shift(explode('?', basename($url))) : uniqid())) : (($this->options['images_name'] != 'auto') ? array_shift(explode('?', basename($url))) : uniqid())) . (("" != $img_ext and $this->options['images_name'] == 'auto') ? '.'.$img_ext : '');

									// if wizard store image data to custom field									
									$create_image = false;
									$download_image = true;

									if (base64_decode($url, true) !== false){
										$img = @imagecreatefromstring(base64_decode($url));									    
									    if($img)
									    {	
									    	$image_filename = md5(time()) . '.jpg';
									    	$image_filepath = $uploads['path'] . '/' . $image_filename;
									    	imagejpeg($img, $image_filepath);
									    	if( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
												$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'pmxi_plugin'), $image_filepath));
												PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
											} else {
												$create_image = true;											
											}
									    } 
									} 
									else {										
										
										$image_filename = wp_unique_filename($uploads['path'], $image_name);
										$image_filepath = $uploads['path'] . '/' . url_title($image_filename);

										// keep existing and add newest images
										if ( $this->options['no_create_featured_image'] ){ 																																											
											$attachment_imgs = get_posts( array(
												'post_type' => 'attachment',
												'posts_per_page' => -1,
												'post_parent' => $pid,												
											) );

											if ( $attachment_imgs ) {
												foreach ( $attachment_imgs as $attachment_img ) {													
													if ($attachment_img->guid == $uploads['url'] . '/' . $image_name){
														$download_image = false;														
														$logger and call_user_func($logger, sprintf(__('<b>Image SKIPPED</b>: The image %s is always exists for the %s', 'pmxi_plugin'), basename($attachment_img->guid), $articleData['post_title']));							
													}
												}
												
											}
										}

										if ($download_image){											

											// do not download images
											if ( ! $this->options['download_images'] ){ 																																											
												
												$image_filepath = $uploads['path'] . '/' . url_title( $image_filename = $image_name );
												
												if ( @file_exists($image_filepath) ){
													$download_image = false;																				
													if( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
														$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'pmxi_plugin'), $image_filepath));
														PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
													} else {
														$create_image = true;											
													}
												}									
											}	

											if ($download_image){

												if ( ! get_file_curl($url, $image_filepath) and ! @file_put_contents($image_filepath, @file_get_contents($url))) {
													unlink($image_filepath); // delete file since failed upload may result in empty file created
												} elseif( ($image_info = @getimagesize($image_filepath)) and in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
													$create_image = true;											
												}
												
												if ( ! $create_image ){

													$url = str_replace(" ", "%20", trim($img_url));

													if ( ! get_file_curl($url, $image_filepath) and ! @file_put_contents($image_filepath, @file_get_contents($url))) {
														$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: File %s cannot be saved locally as %s', 'pmxi_plugin'), $url, $image_filepath));
														PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
														unlink($image_filepath); // delete file since failed upload may result in empty file created										
													} elseif( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
														$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'pmxi_plugin'), $url));
														PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
													} else {
														$create_image = true;											
													}

												}
											}
										}	
									}									

									if ($create_image){

										$attachment = array(
											'post_mime_type' => image_type_to_mime_type($image_info[2]),
											'guid' => $uploads['url'] . '/' . $image_filename,
											'post_title' => $image_filename,
											'post_content' => '',
										);
										if (($image_meta = wp_read_image_metadata($image_filepath))) {
											if (trim($image_meta['title']) && ! is_numeric(sanitize_title($image_meta['title'])))
												$attachment['post_title'] = $image_meta['title'];
											if (trim($image_meta['caption']))
												$attachment['post_content'] = $image_meta['caption'];
										}

										$attid = wp_insert_attachment($attachment, $image_filepath, $pid);										

										if (is_wp_error($attid)) {
											$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $attid->get_error_message());
											PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
										} else {
											// you must first include the image.php file
											// for the function wp_generate_attachment_metadata() to work
											require_once(ABSPATH . 'wp-admin/includes/image.php');
											wp_update_attachment_metadata($attid, wp_generate_attachment_metadata($attid, $image_filepath));																							
											
											if ( $this->options['set_image_meta_data'] ){											
												$update_attachment_meta = array();
												if ( ! empty($img_titles[$img_key]) )       $update_attachment_meta['post_title'] = $img_titles[$img_key];
												if ( ! empty($img_captions[$img_key]) )     $update_attachment_meta['post_excerpt'] = $img_captions[$img_key];											
												if ( ! empty($img_descriptions[$img_key]) ) $update_attachment_meta['post_content'] = $img_descriptions[$img_key];
												if ( ! empty($img_alts[$img_key]) ) update_post_meta($attid, '_wp_attachment_image_alt', $img_alts[$img_key]);
												
												if ( ! empty($update_attachment_meta)){
													$update_attachment_meta['ID'] = $attid;
													wp_update_post($update_attachment_meta);	
												}
											}

											do_action( 'pmxi_gallery_image', $pid, $attid, $image_filepath); 

											$success_images = true;
											if ( ! $post_thumbnail ) { 												
												if ( ! $this->options['no_create_featured_image'] or ! has_post_thumbnail($pid)){ 
													set_post_thumbnail($pid, $attid); 
													$post_thumbnail = true; 
												}
												else $gallery_attachment_ids[] = $attid;
											}
											else $gallery_attachment_ids[] = $attid;												
										}
									}																		
								}									
							}
						}	
						//if (!$is_cron) update_post_meta($pid, '_pmxi_images', $_pmxi_images);
						// Set product gallery images
						if ( $post_type == "product" and !empty($gallery_attachment_ids) )
							update_post_meta($pid, '_product_image_gallery', implode(',', $gallery_attachment_ids));
						// Create entry as Draft if no images are downloaded successfully
						if ( ! $success_images and "yes" == $this->options['create_draft'] ) wp_update_post(array('ID' => $pid, 'post_status' => 'draft'));
					}
					// [/featured image]

					// [attachments]
					if ( ! empty($uploads) and false === $uploads['error'] and !empty($attachments[$i])) {

						// you must first include the image.php file
						// for the function wp_generate_attachment_metadata() to work
						require_once(ABSPATH . 'wp-admin/includes/image.php');

						if ( ! is_array($attachments[$i]) ) $attachments[$i] = array($attachments[$i]);

						foreach ($attachments[$i] as $attachment) { if ("" == $attachment) continue;
							
							$atchs = str_getcsv($attachment, $this->options['atch_delim']);

							if (!empty($atchs)) {
								foreach ($atchs as $atch_url) {	if (empty($atch_url)) continue;									

									$attachment_filename = wp_unique_filename($uploads['path'], basename(parse_url(trim($atch_url), PHP_URL_PATH)));										
									$attachment_filepath = $uploads['path'] . '/' . url_title($attachment_filename);
																		
									if ( ! get_file_curl(trim($atch_url), $attachment_filepath) and ! @file_put_contents($attachment_filepath, @file_get_contents(trim($atch_url)))) {												
										$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Attachment file %s cannot be saved locally as %s', 'pmxi_plugin'), trim($atch_url), $attachment_filepath));
										PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
										unlink($attachment_filepath); // delete file since failed upload may result in empty file created												
									} elseif( ! $wp_filetype = wp_check_filetype(basename($attachment_filename), null )) {
										$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Can\'t detect attachment file type %s', 'pmxi_plugin'), trim($atch_url)));
										PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
									} else {
										
										$attachment_data = array(
										    'guid' => $uploads['baseurl'] . '/' . _wp_relative_upload_path( $attachment_filepath ), 
										    'post_mime_type' => $wp_filetype['type'],
										    'post_title' => preg_replace('/\.[^.]+$/', '', basename($attachment_filepath)),
										    'post_content' => '',
										    'post_status' => 'inherit'
										);
										$attach_id = wp_insert_attachment( $attachment_data, $attachment_filepath, $pid );												

										if (is_wp_error($attach_id)) {
											$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $pid->get_error_message());
											PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
										} else {
											do_action( 'pmxi_attachment_uploaded', $pid, $attid, $image_filepath); 
											wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $attachment_filepath));											
										}										
									}																
								}
							}
						}
					}
					// [/attachments]
					
					// [custom taxonomies]
					if (!empty($taxonomies)){					
						foreach ($taxonomies as $tx_name => $txes) {
							
							if ( empty($articleData['ID']) or !$this->options['is_keep_categories'] or ( $this->options['is_keep_categories'] and $this->options['is_add_newest_categories'] ) ){

								$assign_taxes = array();

								if ($this->options['is_add_newest_categories'] and !empty($existing_taxonomies[$tx_name][$i])){
									$assign_taxes = $existing_taxonomies[$tx_name][$i];	
									unset($existing_taxonomies[$tx_name][$i]);
								}

								// create term if not exists
								if (!empty($txes[$i])):
									foreach ($txes[$i] as $key => $single_tax) {
										if (is_array($single_tax)){								

											$parent_id = (!empty($single_tax['parent'])) ? $this->recursion_taxes($single_tax['parent'], $tx_name, $txes[$i], $key) : '';
											
											$term = is_exists_term($tx_name, $single_tax['name'], (int)$parent_id);		
											
											if ( empty($term) and !is_wp_error($term) ){
												$term_attr = array('parent'=> (!empty($parent_id)) ? $parent_id : 0);
												$term = wp_insert_term(
													$single_tax['name'], // the term 
												  	$tx_name, // the taxonomy
												  	$term_attr
												);
											}
											
											if ( is_wp_error($term) ){									
												$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: `%s`', 'pmxi_plugin'), $term->get_error_message()));
												PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
											}
											elseif (!empty($term)) {
												$cat_id = $term['term_id'];
												if ($cat_id and $single_tax['assign']) 
												{
													$term = get_term_by('id', $cat_id, $tx_name);
													if (!in_array($term->slug, $assign_taxes)) $assign_taxes[] = $term->slug;		
												}									
											}									
										}
									}				
								endif;										
								if (!empty($assign_taxes)){
									// associate taxes with post
									$term_ids = wp_set_object_terms($pid, $assign_taxes, $tx_name);
									if (is_wp_error($term_ids)) {
										$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': '.$term_ids->get_error_message());
										PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
									}
								}
							}
						}
						if (!empty($existing_taxonomies) and $this->options['is_keep_categories'] and $this->options['is_add_newest_categories']) {
							foreach ($existing_taxonomies as $tx_name => $txes) {
								if (!empty($txes[$i])){
									$term_ids = wp_set_object_terms($pid, $txes[$i], $tx_name);
									if (is_wp_error($term_ids)) {
										$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': '.$term_ids->get_error_message());
										PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
									}
								}
							}
						}
					}					
					// [/custom taxonomies]
					
					// [categories]
					if (!empty($cats[$i])) {
					
						if ( empty($articleData['ID']) or !$this->options['is_keep_categories'] or ( $this->options['is_keep_categories'] and $this->options['is_add_newest_categories'] ) ){

							wp_set_object_terms( $pid, NULL, 'category' );

							$assign_cats = array();

							if ($this->options['is_add_newest_categories'] and !empty($existing_cats[$i])){
								$assign_cats = $existing_cats[$i];	
								unset($existing_cats[$i]);
							}

							// create categories if it's doesn't exists						
							foreach ($cats[$i] as $key => $single_cat) {												

								if (is_array($single_cat)){								

									$parent_id = (!empty($single_cat['parent'])) ? $this->recursion_taxes($single_cat['parent'], 'category', $cats[$i], $key) : '';

									//$term = term_exists( trim($single_cat['name']), 'category', $parent_id );																									
									$term = is_exists_term('category', $single_cat['name'], (int)$parent_id);		
									
									if ( empty($term) and !is_wp_error($term) ){																								
										$term_attr = array('parent'=> (!empty($parent_id)) ? $parent_id : 0);									
										$term = wp_insert_term(
											$single_cat['name'], // the term 
										  	'category', // the taxonomy
										  	$term_attr
										);									
									}
									
									if ( is_wp_error($term) ){									
										$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: `%s`', 'pmxi_plugin'), $term->get_error_message()));
										PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
									}
									elseif ( ! empty($term) ) {
										$cat_id = $term['term_id'];
										if ($cat_id and $single_cat['assign']) 
										{
											$term = get_term_by('id', $cat_id, 'category');
											if ( ! in_array($term->slug, $assign_cats)) $assign_cats[] = $term->slug;		
										}									
									}									
								}
							}	

							// associate categories with post
							$cats_ids = wp_set_object_terms($pid, $assign_cats, 'category');
							if (is_wp_error($cats_ids)) {
								$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': '.$cats_ids->get_error_message());
								PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
							}
						}
					}
					
					if (!empty($existing_cats[$i]) and $this->options['is_keep_categories']) {												
						$cats_ids = wp_set_object_terms($pid, $existing_cats[$i], 'category');
						
						if (is_wp_error($cats_ids)) {
							$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': '.$cats_ids->get_error_message());
							PMXI_Plugin::$session['pmxi_import']['warnings'] = ++PMXI_Plugin::$session->data['pmxi_import']['warnings'];
						}
					}
					// [/categories]

					if (empty($articleData['ID'])) {
						PMXI_Plugin::$session['pmxi_import']['created_records'] = $this->created + 1;																								
						$logger and call_user_func($logger, sprintf(__('`%s` post created successfully', 'pmxi_plugin'), $articleData['post_title']));
					} else {
						PMXI_Plugin::$session['pmxi_import']['updated_records'] = $this->updated + 1;									
						$logger and call_user_func($logger, sprintf(__('`%s` post updated successfully', 'pmxi_plugin'), $articleData['post_title']));
					}
					
					do_action( 'pmxi_saved_post', $pid); // hook that was triggered immediately after post saved
					
					if ($this->large_import == 'Yes' and $chunk){
						$this->set(array(
							'imported' => $this->imported + 1,	
							'created'  => (empty($articleData['ID'])) ? $this->created + 1 : $this->created,
							'updated'  => (empty($articleData['ID'])) ? $this->updated : $this->updated + 1			
						))->save();						
					}	
					
					$records_count = 0;

					// Time Elapsed
					if ( ! $is_cron){																								
						
						$records_count = $this->created + $this->updated + $this->skipped + PMXI_Plugin::$session->data['pmxi_import']['errors'];

						$progress_msg = '<p class="import_process_bar"> '.__('Created','pmxi_plugin'). ' ' . $this->created . ' / '.__('Updated','pmxi_plugin') . ' ' . $this->updated . ' ' . __('of','pmxi_plugin') . ' '. $this->count .' ' . __('records', 'pmxi_plugin') . '.</p><span class="import_percent">' . ceil(($records_count/$this->count) * 100) . '</span><span class="warnings_count">' .  PMXI_Plugin::$session->data['pmxi_import']['warnings'] . '</span><span class="errors_count">' . PMXI_Plugin::$session->data['pmxi_import']['errors'] . '</span>';
						$logger and call_user_func($logger, $progress_msg);
					}
					
				}											

				wp_cache_flush();
			}

			if ($this->large_import == 'Yes' and $chunk) PMXI_Plugin::$session['pmxi_import']['chunk_number'] = ++PMXI_Plugin::$session->data['pmxi_import']['chunk_number'];				
			
			pmxi_session_commit();	

			$is_import_complete = ($records_count == $this->count);

			if ( ! $is_cron and $is_import_complete and ! empty($this->options['is_delete_missing'])) { // delete posts which are not in current import set

				$logger and call_user_func($logger, 'Removing previously imported posts which are no longer actual...');
				$postList = new PMXI_Post_List();				
				$current_post_ids = (empty($this->current_post_ids)) ? array() : json_decode($this->current_post_ids, true);	

				$missing_ids = array();
				foreach ($postList->getBy(array('import_id' => $this->id, 'post_id NOT IN' => $current_post_ids)) as $missingPost) {
					empty($this->options['is_keep_attachments']) and wp_delete_attachments($missingPost['post_id']);
					$missing_ids[] = $missingPost['post_id'];
					
					$sql = "delete a
					FROM ".PMXI_Plugin::getInstance()->getTablePrefix()."posts a
					WHERE a.id=%d";
					
					$this->wpdb->query( 
						$this->wpdb->prepare($sql, $missingPost['id'])
					);					
				}

				if (!empty($missing_ids)){

					foreach ($missing_ids as $id) wp_delete_object_term_relationships($id, get_object_taxonomies('' != $this->options['custom_type'] ? $this->options['custom_type'] : 'post'));

					$sql = "delete a,b,c
					FROM ".$this->wpdb->posts." a
					LEFT JOIN ".$this->wpdb->term_relationships." b ON ( a.ID = b.object_id )
					LEFT JOIN ".$this->wpdb->postmeta." c ON ( a.ID = c.post_id )				
					WHERE a.ID IN (".implode(',', $missing_ids).");";

					$this->wpdb->query( 
						$this->wpdb->prepare($sql, '')
					);
				}								

			}

			// Set out of stock status for missing records [Woocommerce add-on option]
			if ( ! $is_cron and $is_import_complete and empty($this->options['is_delete_missing']) and $post_type == "product" and class_exists('PMWI_Plugin') and !empty($this->options['missing_records_stock_status'])) {

				$logger and call_user_func($logger, 'Update stock status previously imported posts which are no longer actual...');
				$postList = new PMXI_Post_List();				
				$current_post_ids = (empty($this->current_post_ids)) ? array() : json_decode($this->current_post_ids, true);	
				foreach ($postList->getBy(array('import_id' => $this->id, 'post_id NOT IN' => $current_post_ids)) as $missingPost) {
					update_post_meta( $missingPost['post_id'], '_stock_status', 'outofstock' );
					update_post_meta( $missingPost['post_id'], '_stock', 0 );
				}

			}

			if ( ! $is_cron and $is_import_complete and empty($this->options['is_delete_missing']) and $this->options['is_update_missing_cf'] ) {
				
				$logger and call_user_func($logger, 'Update custom fields previously imported posts which are no longer actual...');
				$postList = new PMXI_Post_List();											
				$current_post_ids = (empty($this->current_post_ids)) ? array() : json_decode($this->current_post_ids, true);	
				foreach ($postList->getBy(array('import_id' => $this->id, 'post_id NOT IN' => $current_post_ids)) as $missingPost) {
					update_post_meta( $missingPost['post_id'], $this->options['update_missing_cf_name'], $this->options['update_missing_cf_value'] );
				}

			}
			
		} catch (XmlImportException $e) {
			$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': ' . $e->getMessage());
			PMXI_Plugin::$session['pmxi_import']['errors'] = ++PMXI_Plugin::$session->data['pmxi_import']['errors'];	
		}		

		$this->set('registered_on', date('Y-m-d H:i:s'))->save(); // specify execution is successful
		
		!$is_cron and $is_import_complete and $logger and call_user_func($logger, __('Cleaning temporary data...', 'pmxi_plugin'));
		foreach ($tmp_files as $file) { // remove all temporary files created
			unlink($file);
		}
		
		if (($is_cron or $is_import_complete) and $this->options['is_delete_source']) {
			$logger and call_user_func($logger, __('Deleting source XML file...', 'pmxi_plugin'));
			if ($this->type != "ftp"){
				if ( ! @unlink($this->path)) {
					$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to remove %s', 'pmxi_plugin'), $this->path));
				}
			}
			else{
				$file_path_array = PMXI_Helper::safe_glob($this->path, PMXI_Helper::GLOB_NODIR | PMXI_Helper::GLOB_PATH);
				if (!empty($file_path_array)){
					foreach ($file_path_array as $path) {
						if ( ! @unlink($path)) {
							$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to remove %s', 'pmxi_plugin'), $path));
						}
					}
				}
			}
		}
		if ( ! $is_cron and $is_import_complete ){
			$this->set(array(
				'processing' => 0,
				'triggered' => 0,
				'queue_chunk_number' => 0,
				'current_post_ids' => ''
			))->save();
			$logger and call_user_func($logger, 'Done');			
		}
		
		remove_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // return any filtering rules back if they has been disabled for import procedure
		
		return $this;
	}
	
	public function recursion_taxes($parent, $tx_name, $txes, $key){		

		if (is_array($parent)){
			$parent['name'] = sanitize_text_field($parent['name']);
			if (empty($parent['parent'])){
				//$term = term_exists( htmlspecialchars($parent['name']), $tx_name);

				$term = is_exists_term($tx_name, $parent['name']);				

				if ( empty($term) and !is_wp_error($term) ){
					$term = wp_insert_term(
						$parent['name'], // the term 
					  	$tx_name // the taxonomy			  	
					);
				}
				return ( ! is_wp_error($term)) ? $term['term_id'] : 0;
			}
			else{
				$parent_id = $this->recursion_taxes($parent['parent'], $tx_name, $txes, $key);
				//$term = term_exists( htmlspecialchars($parent['name']), $tx_name, $parent_id);
				
				$term = is_exists_term($tx_name, $parent['name'], (int)$parent_id);				

				if ( empty($term) and  !is_wp_error($term) ){
					$term = wp_insert_term(
						$parent, // the term 
					  	$tx_name, // the taxonomy			  	
					  	array('parent'=> (!empty($parent_id)) ? $parent_id : 0)
					);
				}
				return ( ! is_wp_error($term)) ? $term['term_id'] : 0;
			}			
		}
		else{	

			if ( !empty($txes[$key - 1]) and !empty($txes[$key - 1]['parent']) and $parent != $txes[$key - 1]['parent']) {				
				$parent_id = $this->recursion_taxes($txes[$key - 1]['parent'], $tx_name, $txes, $key - 1);

				//$term = term_exists( htmlspecialchars($parent), $tx_name, $parent_id);
				$term = is_exists_term($tx_name, $parent, (int)$parent_id);

				if ( empty($term) and !is_wp_error($term) ){
					$term = wp_insert_term(
						$parent, // the term 
					  	$tx_name, // the taxonomy			  	
					  	array('parent'=> (!empty($parent_id)) ? $parent_id : 0)
					);
				}
				return ( ! is_wp_error($term)) ? $term['term_id'] : 0;
			}
			else{
				//$term = term_exists( htmlspecialchars($parent), $tx_name);
				$term = is_exists_term($tx_name, $parent);
				if ( empty($term) and !is_wp_error($term) ){					
					$term = wp_insert_term(
						$parent, // the term 
					  	$tx_name // the taxonomy			  	
					);
				}				
				return ( ! is_wp_error($term)) ? $term['term_id'] : 0;
			}
		}
	}	

	public function _filter_has_cap_unfiltered_html($caps)
	{
		$caps['unfiltered_html'] = true;
		return $caps;
	}
	
	/**
	 * Find duplicates according to settings
	 */
	public function findDuplicates($articleData, $custom_duplicate_name = '', $custom_duplicate_value = '', $duplicate_indicator = 'title')
	{		
		if ('custom field' == $duplicate_indicator){
			$duplicate_ids = array();
			$args = array(
				'post_type' => $articleData['post_type'],
				'meta_query' => array(
					array(
						'key' => $custom_duplicate_name,
						'value' => $custom_duplicate_value,						
					)
				)
			);			
			$query = new WP_Query( $args );
			
			if ( $query->have_posts() ) $duplicate_ids[] = $query->post->ID;

			wp_reset_postdata();

			return $duplicate_ids;
		}
		else{
			$field = 'post_' . $duplicate_indicator; // post_title or post_content
			return $this->wpdb->get_col($this->wpdb->prepare("
				SELECT ID FROM " . $this->wpdb->posts . "
				WHERE
					post_type = %s
					AND ID != %s
					AND REPLACE(REPLACE(REPLACE($field, ' ', ''), '\\t', ''), '\\n', '') = %s
				",
				$articleData['post_type'],
				isset($articleData['ID']) ? $articleData['ID'] : 0,
				preg_replace('%[ \\t\\n]%', '', $articleData[$field])
			));
		}
	}
	
	/**
	 * Clear associations with posts
	 * @param bool[optional] $keepPosts When set to false associated wordpress posts will be deleted as well
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function deletePosts($keepPosts = TRUE) {
		$post = new PMXI_Post_List();		
		if ( ! $keepPosts) {								
			$ids = array();
			foreach ($post->getBy('import_id', $this->id)->convertRecords() as $p) {
				empty($this->options['is_keep_attachments']) and wp_delete_attachments($p->post_id);
				$ids[] = $p->post_id;								
			}
			if (!empty($ids)){				

				foreach ($ids as $id) wp_delete_object_term_relationships($id, get_object_taxonomies('' != $this->options['custom_type'] ? $this->options['custom_type'] : 'post'));

				$sql = "delete a,b,c
				FROM ".$this->wpdb->posts." a
				LEFT JOIN ".$this->wpdb->term_relationships." b ON ( a.ID = b.object_id )
				LEFT JOIN ".$this->wpdb->postmeta." c ON ( a.ID = c.post_id )
				LEFT JOIN ".$this->wpdb->posts." d ON ( a.ID = d.post_parent )
				WHERE a.ID IN (".implode(',', $ids).");";

				$this->wpdb->query( 
					$this->wpdb->prepare($sql, '')
				);				
				
			}			
		}
		
		$this->wpdb->query($this->wpdb->prepare('DELETE FROM ' . $post->getTable() . ' WHERE import_id = %s', $this->id));

		return $this;
	}
	/**
	 * Delete associated files
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function deleteFiles() {
		$fileList = new PMXI_File_List();
		foreach($fileList->getBy('import_id', $this->id)->convertRecords() as $f) {
			$f->delete();
		}
		return $this;
	}
	
	/**
	 * @see parent::delete()
	 * @param bool[optional] $keepPosts When set to false associated wordpress posts will be deleted as well
	 */
	public function delete($keepPosts = TRUE) {
		$this->deletePosts($keepPosts)->deleteFiles();
		
		return parent::delete();
	}
	
}
