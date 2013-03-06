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
		// If import process NOT in large file mode the save history file

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

				$_SESSION['pmxi_import']['count'] = count($records);								

				$records_count = $_SESSION['pmxi_import']['created_records'] + $_SESSION['pmxi_import']['updated_records'] + $_SESSION['pmxi_import']['skipped_records'] + $_SESSION['pmxi_import']['errors'];;

				if (!in_array($chunk, $records) and (!$this->options['create_chunks'] or $is_cron)){
					$logger and call_user_func($logger, __('<b>SKIPPED</b>: by specified records option', 'pmxi_plugin'));
					$_SESSION['pmxi_import']['warnings']++;
					// Time Elapsed
					if ( ! $is_cron ){						
						$progress_msg = '<p class="import_process_bar"> Created ' . $_SESSION['pmxi_import']['created_records'] . ' / Updated ' . $_SESSION['pmxi_import']['updated_records'] . ' of '. $_SESSION['pmxi_import']['count'].' records.</p><span class="import_percent">' . ceil(($records_count/$_SESSION['pmxi_import']['count']) * 100) . '</span><span class="warnings_count">' .  $_SESSION['pmxi_import']['warnings'] . '</span><span class="errors_count">' . $_SESSION['pmxi_import']['errors'] . '</span>';
						$logger and call_user_func($logger, $progress_msg);
					}
					$_SESSION['pmxi_import']['chunk_number']++;
					return;
				}
				else $records = array();
			}				
		}
		try { 						
			
			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing titles...', 'pmxi_plugin'));
			$titles = XmlImportParser::factory($xml, $this->xpath, $this->template['title'], $file)->parse($records); $tmp_files[] = $file;
			if ($this->large_import != 'Yes') $_SESSION['pmxi_import']['count'] = count($titles);

			($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, __('Composing excerpts...', 'pmxi_plugin'));			
			$post_excerpt = array();
			if (!empty($this->options['post_excerpt'])){
				$post_excerpt = XmlImportParser::factory($xml, $this->xpath, $this->options['post_excerpt'], $file)->parse($records); $tmp_files[] = $file;
			}
			else{
				count($titles) and $post_excerpt = array_fill(0, count($titles), '');
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
						$_SESSION['pmxi_import']['warnings']++;
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
						$_SESSION['pmxi_import']['warnings']++;
						$time_start = time();
					}
					$time_end = strtotime($dates_end[$i]);
					if (FALSE === $time_end) {
						in_array($dates_end[$i], $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'pmxi_plugin'), $warned[] = $dates_end[$i]));
						$_SESSION['pmxi_import']['warnings']++;
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
							if ('' != $c_raw) foreach (str_getcsv($c_raw, ',') as $c_cell) if ('' != $c_cell) {
								foreach (str_getcsv($c_cell, $this->options['categories_delim']) as $j => $cc) if ('' != $cc) {								
									$cat = get_term_by('name', trim($cc), 'category') or $cat = get_term_by('slug', trim($cc), 'category') or ctype_digit($cc) and $cat = get_term_by('id', trim($cc), 'category');								
									if ( !empty($categories_hierarchy[$k]->parent_id) ) {											
										foreach ($categories_hierarchy as $key => $value){ 
											if ($value->item_id == $categories_hierarchy[$k]->parent_id and !empty($value->cat_ids[$i])){												
												foreach ($value->cat_ids[$i] as $parent) {													
													$cats[$i][] = array(
														'name' => trim($cc),
														'parent' => (is_array($parent)) ? $parent['name'] : $parent, // if parent taxonomy exists then return ID else return TITLE
														'assign' => $category->assign
													);													
												}
											}
										}
									}
									else {
										if ( !$cat ){
											$cats[$i][] = array(
												'name' => trim($cc),
												'parent' => false,
												'assign' => $category->assign
											);
										}
										else if ( $category->assign ){
											$cats[$i][] = $cat->term_id;		
										}
									}
								}								
							}
							$categories_hierarchy[$k]->cat_ids[$i] = $cats[$i];
						}
					endforeach;					
				} else{
					count($titles) and $cats = array_fill(0, count($titles), '');
				}								
			}			
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
				if (in_array($taxonomies_object_type, $tx->object_type)) {
					($chunk == 1 or (empty($this->large_import) or $this->large_import == 'No')) and $logger and call_user_func($logger, sprintf(__('Composing terms for `%s` taxonomy...', 'pmxi_plugin'), $tx->labels->name));
					$txes = array();
					
					$taxonomies_hierarchy = json_decode($tx_template);
					foreach ($taxonomies_hierarchy as $k => $taxonomy){	if ("" == $taxonomy->xpath) continue;								
						$txes_raw =  XmlImportParser::factory($xml, $this->xpath, str_replace('\'','"',$taxonomy->xpath), $file)->parse($records); $tmp_files[] = $file;						
						$warned = array();
						foreach ($txes_raw as $i => $tx_raw) {
							if (empty($taxonomies_hierarchy[$k]->txn_names[$i])) $taxonomies_hierarchy[$k]->txn_names[$i] = array();
							if (empty($taxonomies[$tx_name][$i])) $taxonomies[$tx_name][$i] = array();
							if ('' != $tx_raw) foreach (str_getcsv($tx_raw, ',') as $tx_cell) if ('' != $tx_cell) {										
								foreach (str_getcsv($tx_cell, (!empty($taxonomy->delim)) ? $taxonomy->delim : ',') as $j => $cc) if ('' != $cc) {									
									$cat = get_term_by('name', trim($cc), $tx_name) or $cat = get_term_by('slug', trim($cc), $tx_name) or ctype_digit($cc) and $cat = get_term_by('id', $cc, $tx_name);
									if (!empty($taxonomies_hierarchy[$k]->parent_id)) {																			
										foreach ($taxonomies_hierarchy as $key => $value){
											if ($value->item_id == $taxonomies_hierarchy[$k]->parent_id and !empty($value->txn_names[$i])){													
												foreach ($value->txn_names[$i] as $parent) {																																																																	
													$taxonomies[$tx_name][$i][] = array(
														'name' => trim($cc),
														'parent' => (is_array($parent)) ? $parent['name'] : $parent,
														'assign' => $taxonomy->assign
													);																	
												}											
											}
										}
										
									}
									else {	
										if ( ! $cat ) {													
											$taxonomies[$tx_name][$i][] = array(
												'name' => trim($cc),
												'parent' => false,
												'assign' => $taxonomy->assign
											);
										}
										elseif ($taxonomy->assign) {
											$taxonomies[$tx_name][$i][] = $cc;		
										}
									}								
								}	
							}
							$taxonomies_hierarchy[$k]->txn_names[$i] = $taxonomies[$tx_name][$i];
						}
					}
				}
			}; endif;		
			// [/custom taxonomies]								

			// serialized attachments
			if ( ! (($uploads = wp_upload_dir()) && false === $uploads['error'])) {
				$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $uploads['error']);				
				$logger and call_user_func($logger, __('<b>WARNING</b>: No attachments will be created', 'pmxi_plugin')); 				
				$_SESSION['pmxi_import']['warnings']++;
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

			$current_post_ids = array();
			foreach ($titles as $i => $void) {					

				if (!empty($chunk_records) and $this->large_import == 'Yes' and $this->options['create_chunks'] and !$is_cron and !in_array($_SESSION['pmxi_import']['chunk_number'], $chunk_records)) {
					$_SESSION['pmxi_import']['skipped_records']++;
					$_SESSION['pmxi_import']['chunk_number']++;

					$logger and call_user_func($logger, __('<b>SKIPPED</b>: by specified records option', 'pmxi_plugin'));
					$_SESSION['pmxi_import']['warnings']++;
					// Time Elapsed
					if ( ! $is_cron ){
						$records_count = $_SESSION['pmxi_import']['created_records'] + $_SESSION['pmxi_import']['updated_records'] + $_SESSION['pmxi_import']['skipped_records'] + $_SESSION['pmxi_import']['errors'];
						$progress_msg = '<p class="import_process_bar"> Created ' . $_SESSION['pmxi_import']['created_records'] . ' / Updated ' . $_SESSION['pmxi_import']['updated_records'] . ' of '. $_SESSION['pmxi_import']['count'].' records.</p><span class="import_percent">' . ceil(($records_count/$_SESSION['pmxi_import']['count']) * 100) . '</span><span class="warnings_count">' .  $_SESSION['pmxi_import']['warnings'] . '</span><span class="errors_count">' . $_SESSION['pmxi_import']['errors'] . '</span>';
						$logger and call_user_func($logger, $progress_msg);
					}						

					continue;
				} 						
						
				$articleData = array(
					'post_type' => $post_type,
					'post_status' => $this->options['status'],
					'comment_status' => $this->options['comment_status'],
					'ping_status' => $this->options['ping_status'],
					'post_title' => ($this->template['is_leave_html']) ? utf8_encode(html_entity_decode($titles[$i])) : $titles[$i],
					'post_excerpt' => ($this->template['is_leave_html']) ? utf8_encode(html_entity_decode($post_excerpt[$i])) : $post_excerpt[$i],
					'post_slug' => $post_slug[$i],
					'post_content' => ($this->template['is_leave_html']) ? utf8_encode(html_entity_decode($contents[$i])) : $contents[$i],
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
					if ($duplicates = $this->findDuplicates($articleData, $custom_duplicate_name[$i], $custom_duplicate_value[$i])) {															
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
						$current_post_ids[] = $_SESSION['pmxi_import']['current_post_ids'][]  = $post_to_update_id;
						if ($is_cron){
							$tmp_array = (!empty($this->current_post_ids)) ? json_decode($this->current_post_ids, true) : array();
							$tmp_array[] = $post_to_update_id;
							$this->set(array(
								'current_post_ids' => json_encode($tmp_array)
							))->save;
						}					
						$_SESSION['pmxi_import']['skipped_records']++;		
						$logger and call_user_func($logger, sprintf(__('<b>SKIPPED</b>: Previously imported record found for `%s`', 'pmxi_plugin'), $articleData['post_title']));
						$_SESSION['pmxi_import']['warnings']++;
						if ( ! $is_cron ){
							$records_count = $_SESSION['pmxi_import']['created_records'] + $_SESSION['pmxi_import']['updated_records'] + $_SESSION['pmxi_import']['skipped_records'] + $_SESSION['pmxi_import']['errors'];
							$progress_msg = '<p class="import_process_bar"> Created ' . $_SESSION['pmxi_import']['created_records'] . ' / Updated ' . $_SESSION['pmxi_import']['updated_records'] . ' of '. $_SESSION['pmxi_import']['count'].' records.</p><span class="import_percent">' . ceil(($records_count/$_SESSION['pmxi_import']['count']) * 100) . '</span><span class="warnings_count">' .  $_SESSION['pmxi_import']['warnings'] . '</span><span class="errors_count">' . $_SESSION['pmxi_import']['errors'] . '</span>';
							$logger and call_user_func($logger, $progress_msg);
						}					
						$_SESSION['pmxi_import']['chunk_number']++;
						continue;
					}
					$articleData['ID'] = $post_to_update_id;
					// preserve date of already existing article when duplicate is found					
					if ($this->options['is_keep_categories']) { // preserve categories and tags of already existing article if corresponding setting is specified
						$cats_list = get_the_category($articleData['ID']);
						if (is_wp_error($cats_list)) {
							$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current categories for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
							$_SESSION['pmxi_import']['warnings']++;
						} else {
							$cats_new = array();
							foreach ($cats_list as $c) {
								$cats_new[] = $c->cat_ID;
							}
							$cats[$i] = $cats_new;
						}
						
						$tags_list = get_the_tags($articleData['ID']);
						if (is_wp_error($tags_list)) {
							$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current tags for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
							$_SESSION['pmxi_import']['warnings']++;
						} else {
							$tags_new = array();
							if ($tags_list) foreach ($tags_list as $t) {
								$tags_new[] = $t->name;
							}
							$articleData['tags_input'] = implode(', ', $tags_new);
						}
						
						foreach (array_keys($taxonomies) as $tx_name) {
							$txes_list = get_the_terms($articleData['ID'], $tx_name);
							if (is_wp_error($txes_list)) {
								$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current taxonomies for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
								$_SESSION['pmxi_import']['warnings']++;
							} else {
								$txes_new = array();
								if (!empty($txes_list)):
									foreach ($txes_list as $t) {
										$txes_new[] = $t->name;
									}
								endif;
								$taxonomies[$tx_name][$i] = $txes_new;
							}
						}
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
					// handle obsolete attachments (i.e. delete or keep) according to import settings
					if ( ! $this->options['is_keep_images'] and ! $this->options['no_create_featured_image']){ 						
						wp_delete_attachments($articleData['ID']);
					}
				}
				elseif ( ! $postRecord->isEmpty() ){
					
					// existing post not found though it's track was found... clear the leftover, plugin will continue to treat record as new
					$postRecord->delete();
					
				}
				
				// no new records are created. it will only update posts it finds matching duplicates for
				if ($this->options['not_create_records'] and empty($articleData['ID'])){ 
					$_SESSION['pmxi_import']['skipped_records']++;		
					$logger and call_user_func($logger, sprintf(__('<b>SKIPPED</b>: by "Not add new records" option for `%s`', 'pmxi_plugin'), $articleData['post_title']));
					if ( ! $is_cron ){
						$records_count = $_SESSION['pmxi_import']['created_records'] + $_SESSION['pmxi_import']['updated_records'] + $_SESSION['pmxi_import']['skipped_records'] + $_SESSION['pmxi_import']['errors'];
						$progress_msg = '<p class="import_process_bar"> Created ' . $_SESSION['pmxi_import']['created_records'] . ' / Updated ' . $_SESSION['pmxi_import']['updated_records'] . ' of '. $_SESSION['pmxi_import']['count'].' records.</p><span class="import_percent">' . ceil(($records_count/$_SESSION['pmxi_import']['count']) * 100) . '</span><span class="warnings_count">' .  $_SESSION['pmxi_import']['warnings'] . '</span><span class="errors_count">' . $_SESSION['pmxi_import']['errors'] . '</span>';
						$logger and call_user_func($logger, $progress_msg);
					}					
					$_SESSION['pmxi_import']['chunk_number']++;					
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
										$_SESSION['pmxi_import']['warnings']++;
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
					$_SESSION['pmxi_import']['errors']++;
				} else {
					
					do_action( 'pmxi_saved_post', $pid); // hook that was triggered immediately after post saved

					$current_post_ids[] = $_SESSION['pmxi_import']['current_post_ids'][] = $pid;
					if ($is_cron){
						$tmp_array = (!empty($this->current_post_ids)) ? json_decode($this->current_post_ids, true) : array();
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
						))->insert();
					}										

					if ('post' != $articleData['post_type'] and !empty($this->options['page_template'])) update_post_meta($pid, '_wp_page_template', $this->options['page_template']);									

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
																		
									if ( ! file_put_contents($attachment_filepath, @file_get_contents(trim($atch_url))) and ! get_file_curl(trim($atch_url), $attachment_filepath)) {												
										$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Attachment file %s cannot be saved locally as %s', 'pmxi_plugin'), trim($atch_url), $attachment_filepath)); 
										$_SESSION['pmxi_import']['warnings']++;
										unlink($attachment_filepath); // delete file since failed upload may result in empty file created												
									} elseif( ! $wp_filetype = wp_check_filetype(basename($attachment_filename), null )) {
										$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Can\'t detect attachment file type %s', 'pmxi_plugin'), trim($atch_url)));
										$_SESSION['pmxi_import']['warnings']++;
									} else {

										$attachment_data = array(
										    'guid' => $uploads['baseurl'] . _wp_relative_upload_path( $attachment_filepath ), 
										    'post_mime_type' => $wp_filetype['type'],
										    'post_title' => preg_replace('/\.[^.]+$/', '', basename($attachment_filepath)),
										    'post_content' => '',
										    'post_status' => 'inherit'
										);
										$attach_id = wp_insert_attachment( $attachment_data, $attachment_filepath, $pid );												

										if (is_wp_error($attach_id)) {
											$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $pid->get_error_message());
											$_SESSION['pmxi_import']['warnings']++;
										} else {
											wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $attachment_filepath));											
										}										
									}																
								}
							}
						}
					}
					// [/attachments]
					
					// [custom taxonomies]
					foreach ($taxonomies as $tx_name => $txes) {
						// create term if not exists
						foreach ($txes[$i] as $key => $single_tax) {							
							if (is_array($single_tax)){
								$term = term_exists( $single_tax['name'], $tx_name, (!empty($single_tax['parent'])) ? $single_tax['parent'] : '' );								
								if ( empty($term) ){
									$term_attr = (!empty($single_tax['parent'])) ? $term_attr = array('parent'=> $single_tax['parent']) : '';																			
																		
									$term = wp_insert_term(
										$single_tax['name'], // the term 
									  	$tx_name, // the taxonomy
									  	$term_attr
									);
								}
								
								if ( empty($term) or is_wp_error($term) ){
									unset($txes[$i][$key]);
									$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: `%s`', 'pmxi_plugin'), $term->get_error_message()));
									$_SESSION['pmxi_import']['warnings']++;
								}
								else{
									$cat_id = $term['term_id'];
									if ($cat_id and $single_tax['assign']) 
										$txes[$i][$key] = $single_tax['name'];		
									else 
										unset($txes[$i][$key]);
								}									
							}
						}
						
						// associate taxes with post
						$term_ids = wp_set_object_terms($pid, $txes[$i], $tx_name);
						if (is_wp_error($term_ids)) {
							$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': '.$term_ids->get_error_message());
							$_SESSION['pmxi_import']['warnings']++;
						}
					}
					// [/custom taxonomies]
					
					// [categories]
					if (!empty($cats[$i])){
						// create categories if it's doesn't exists						
						foreach ($cats[$i] as $key => $single_cat) {												
							if (is_array($single_cat)){									
								if (!empty($single_cat['parent'])){									
									$cat = get_term_by('name', $single_cat['parent'], 'category') or $cat = get_term_by('slug', $single_cat['parent'], 'category') or ctype_digit($single_cat['parent']) and $cat = get_term_by('id', $single_cat['parent'], 'category');								
									if ($cat)
										$cat_id = wp_create_category($single_cat['name'], $cat->term_id);
								}
								else $cat_id = wp_create_category($single_cat['name']);		
								
								if ($cat_id and $single_cat['assign']) 
									$cats[$i][$key] = $cat_id;																									
							}
						}
						// associate categories with post
						$cats_ids = wp_set_post_terms($pid, $cats[$i], 'category');
						if (is_wp_error($cats_ids)) {
							$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': '.$cats_ids->get_error_message());
							$_SESSION['pmxi_import']['warnings']++;
						}
					}
					// [/categories]

					if (empty($articleData['ID'])) {
						$_SESSION['pmxi_import']['created_records']++;																									
						$logger and call_user_func($logger, sprintf(__('`%s` post created successfully', 'pmxi_plugin'), $articleData['post_title']));
					} else {
						$_SESSION['pmxi_import']['updated_records']++;										
						$logger and call_user_func($logger, sprintf(__('`%s` post updated successfully', 'pmxi_plugin'), $articleData['post_title']));
					}
					
					$records_count = 0;

					// Time Elapsed
					if ( ! $is_cron){												
						
						if ($this->large_import == 'No') $_SESSION['pmxi_import']['count'] = count($titles);
						
						$records_count = $_SESSION['pmxi_import']['created_records'] + $_SESSION['pmxi_import']['updated_records'] + $_SESSION['pmxi_import']['skipped_records'] + $_SESSION['pmxi_import']['errors'];

						$progress_msg = '<p class="import_process_bar"> Created ' . $_SESSION['pmxi_import']['created_records'] . ' / Updated ' . $_SESSION['pmxi_import']['updated_records'] . ' of '. $_SESSION['pmxi_import']['count'].' records.</p><span class="import_percent">' . ceil(($records_count/$_SESSION['pmxi_import']['count']) * 100) . '</span><span class="warnings_count">' .  $_SESSION['pmxi_import']['warnings'] . '</span><span class="errors_count">' . $_SESSION['pmxi_import']['errors'] . '</span>';
						$logger and call_user_func($logger, $progress_msg);
					}
					
				}
				
				if ($this->large_import == 'Yes' and $chunk){
					$this->set(array(
						'imported' => $this->imported + 1,	
						'created'  => $_SESSION['pmxi_import']['created_records'],
						'updated'  => $_SESSION['pmxi_import']['updated_records']				
					))->save();
					$_SESSION['pmxi_import']['chunk_number']++;
				}

				wp_cache_flush();
			}			
			if (!$is_cron and ($records_count == $_SESSION['pmxi_import']['count'] + $_SESSION['pmxi_import']['skipped_records']) and ! empty($this->options['is_delete_missing'])) { // delete posts which are not in current import set				
				$logger and call_user_func($logger, 'Removing previously imported posts which are no longer actual...');
				$postList = new PMXI_Post_List();				
				
				foreach ($postList->getBy(array('import_id' => $this->id, 'post_id NOT IN' => $_SESSION['pmxi_import']['current_post_ids'])) as $missingPost) {
					empty($this->options['is_keep_attachments']) and wp_delete_attachments($missingPost['post_id']);
					wp_delete_post($missingPost['post_id'], true);
				}
			}
			
		} catch (XmlImportException $e) {
			$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': ' . $e->getMessage());
			$_SESSION['pmxi_import']['errors']++;	
		}		

		$this->set('registered_on', date('Y-m-d H:i:s'))->save(); // specify execution is successful
		
		!$is_cron and ($records_count == $_SESSION['pmxi_import']['count'] + $_SESSION['pmxi_import']['skipped_records']) and $logger and call_user_func($logger, __('Cleaning temporary data...', 'pmxi_plugin'));
		foreach ($tmp_files as $file) { // remove all temporary files created
			unlink($file);
		}
		
		if (($is_cron or $records_count == $_SESSION['pmxi_import']['count'] + $_SESSION['pmxi_import']['skipped_records']) and $this->options['is_delete_source']) {
			$logger and call_user_func($logger, __('Deleting source XML file...', 'pmxi_plugin'));
			if ( ! @unlink($this->path)) {
				$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to remove %s', 'pmxi_plugin'), $this->path));
			}
		}
		!$is_cron and ($records_count == $_SESSION['pmxi_import']['count'] + $_SESSION['pmxi_import']['skipped_records']) and $logger and call_user_func($logger, 'Done');
		
		remove_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // return any filtering rules back if they has been disabled for import procedure
		
		return $this;
	}
	
	public function _filter_has_cap_unfiltered_html($caps)
	{
		$caps['unfiltered_html'] = true;
		return $caps;
	}
	
	/**
	 * Find duplicates according to settings
	 */
	public function findDuplicates($articleData, $custom_duplicate_name = '', $custom_duplicate_value = '')
	{		
		if ('custom field' == $this->options['duplicate_indicator']){
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
			$field = 'post_' . $this->options['duplicate_indicator']; // post_title or post_content
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
		if ($keepPosts) {
			$this->wpdb->query($this->wpdb->prepare('DELETE FROM ' . $post->getTable() . ' WHERE import_id = %s', $this->id));
		} else {
			$ids = array();
			foreach ($post->getBy('import_id', $this->id)->convertRecords() as $p) {
				empty($this->options['is_keep_attachments']) and empty($this->options['is_keep_images']) and wp_delete_attachments($p->post_id);
				$ids[] = $p->post_id;				
				//wp_delete_post($p->post_id, TRUE);
			}
			if (!empty($ids)){

				/*$sql = "delete a,b,c,d
				FROM ".$this->wpdb->posts." a
				LEFT JOIN ".$this->wpdb->term_relationships." b ON ( a.ID = b.object_id )
				LEFT JOIN ".$this->wpdb->postmeta." c ON ( a.ID = c.post_id )
				LEFT JOIN ".$this->wpdb->term_taxonomy." d ON ( d.term_taxonomy_id = b.term_taxonomy_id )
				LEFT JOIN ".$this->wpdb->terms." e ON ( e.term_id = d.term_id )
				WHERE a.ID IN (".implode(',', $ids).");";*/

				$sql = "delete a,b,c
				FROM ".$this->wpdb->posts." a
				LEFT JOIN ".$this->wpdb->term_relationships." b ON ( a.ID = b.object_id )
				LEFT JOIN ".$this->wpdb->postmeta." c ON ( a.ID = c.post_id )				
				WHERE a.ID IN (".implode(',', $ids).");";

				$this->wpdb->query( 
					$this->wpdb->prepare($sql)
				);
			}
		}
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
