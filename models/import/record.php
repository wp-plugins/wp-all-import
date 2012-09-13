<?php

class PMXI_Import_Record extends PMXI_Model_Record {
	
	/**
	 * Some pre-processing logic, such as removing control characters from xml to prevent parsing errors
	 * @param string $xml
	 */
	public static function preprocessXml( & $xml) {
		//$xml = preg_replace('%\pC(?<!\s)%u', '', $xml); // remove control chars but not white-spacing ones
		//use HTML::Entities;		
		
		$xml =  preg_replace("/&.{0,}?;/",'',$xml); 
		
	}

	public static function uft8decodeXml( & $xml ){
		
		$xml = str_replace(array("&"), array("&amp;"), utf8_decode($xml));			

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
			
			if (PMXI_Plugin::getInstance()->getOption('utf8_decode')) PMXI_Import_Record::uft8decodeXml($xml);
			if (PMXI_Plugin::getInstance()->getOption('html_entities')) PMXI_Import_Record::preprocessXml($xml);																	

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
	 * Check whether current import should be perfomed again according to scheduling options
	 */
	public function isDue()
	{
		if ( ! $this->scheduled or date('YmdHi') <= date('YmdHi', strtotime($this->registered_on))) return false; // scheduling is disabled or the task has been executed this very minute
		if ('0000-00-00 00:00:00' == $this->registered_on) return true; // never executed but scheduled
		
		$task = new _PMXI_Import_Record_Cron_Parser($this->scheduled);
		return $task->isDue($this->registered_on);
	}
	
	/**
	 * Import all files matched by path
	 * @param callback[optional] $logger Method where progress messages are submmitted
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function execute($logger = NULL) {
		$this->set('registered_on', date('Y-m-d H:i:s'))->save(); // update registered_on to indicated that job has been exectured even if no files are going to be imported by the rest of the method
		
		if ($this->path) {
			if (in_array($this->type, array('ftp', 'file'))) { // file paths support patterns
				$logger and call_user_func($logger, __('Reading files for import...', 'pmxi_plugin'));
				$files = PMXI_Helper::safe_glob($this->path, PMXI_Helper::GLOB_NODIR | PMXI_Helper::GLOB_PATH);
				$logger and call_user_func($logger, sprintf(_n('%s file found', '%s files found', count($files), 'pmxi_plugin'), count($files)));
			} else {  // single file path
				$files = array($this->path);
			}

			foreach ($files as $ind => $path) {
				$logger and call_user_func($logger, sprintf(__('Importing %s (%s of %s)', 'pmxi_plugin'), $path, $ind + 1, count($files)));
												
				$contents = get_headers($path,1 );				

				if (preg_match('%\W(zip)$%i', trim($path))){

					$uploads = wp_upload_dir();
				
					$newfile = $uploads['path']."/".md5(time()).'.zip';

					if (!copy($path, $newfile)) {
					    $this->errors->add('form-validation', __('Failed upload ZIP archive', 'pmxi_plugin'));
					}

					$zip = zip_open($newfile);
					if (is_resource($zip)) {
						$uploads = wp_upload_dir();
				    	if($uploads['error']){
							 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
						}
						$filename = '';
						while ($zip_entry = zip_read($zip)) {
							$filename = zip_entry_name($zip_entry);												
						    $fp = fopen($uploads['path']."/".$filename, "w");
						    if (zip_entry_open($zip, $zip_entry, "r")) {
						      $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
						      fwrite($fp,"$buf");
						      zip_entry_close($zip_entry);
						      fclose($fp);
						    }
						    break;
						}
						zip_close($zip);
						unlink($newfile);
						if (preg_match('%\W(csv)$%i', trim($filename)) or PMXI_Plugin::detect_csv($contents['Content-Type']))
							$xml = PMXI_Plugin::csv_to_xml($uploads['path']  .'/'. $filename);
						else
							$xml = @file_get_contents($uploads['path']  .'/'. $filename);
					}

				} elseif (preg_match('%\W(csv)$%i', trim($path)) or PMXI_Plugin::detect_csv($contents['Content-Type'])) {
					$uploads = wp_upload_dir();		
					$fdata = file_get_contents($path);		
					$fdata = utf8_encode($fdata);
					$tmpname = md5(time()).'.csv';				
					file_put_contents($uploads['path']  .'/'. $tmpname, $fdata);					
					$xml = PMXI_Plugin::csv_to_xml($uploads['path']  .'/'. $tmpname);
				}
				else
				{
					ob_start();
					readgzfile($path);
					$xml = ob_get_clean();

					$wp_uploads = wp_upload_dir();
					$url = $wp_uploads['url'] .'/'. basename($path);								
					file_put_contents($wp_uploads['path']  .'/'. basename($path), $xml);				
					chmod($wp_uploads['path']  .'/'. basename($path), '0755');

					if ($contents = get_headers($url,1 ) and PMXI_Plugin::detect_csv($contents['Content-Type'])) $xml = PMXI_Plugin::csv_to_xml($wp_uploads['path']. basename($path));					
				}				
				if ( ! $xml) {
					$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': file is not accessible or empty');
				} elseif ( ! PMXI_Import_Record::validateXml($xml)) {
					$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': file is not a properly fromatted XML document');
				} else {
					do_action( 'pmxi_before_xml_import' );
					$this->process($xml, $logger);
					do_action( 'pmxi_after_xml_import' );
				}
			}
			$logger and call_user_func($logger, __('Complete', 'pmxi_plugin'));
		}
		return $this;
	}
	
	/**
	 * Perform import operation
	 * @param string $xml XML string to import
	 * @param callback[optional] $logger Method where progress messages are submmitted
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function process($xml, $logger = NULL) {
		add_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // do not perform special filtering for imported content
		
		$this->options += PMXI_Plugin::get_default_import_options(); // make sure all options are defined								

		$history_file = new PMXI_File_Record();
		$history_file->set(array(
			'name' => $this->name,
			'import_id' => $this->id,
			'path' => $this->path,
			'contents' => $xml,
			'registered_on' => date('Y-m-d H:i:s'),
		))->save();

		$postRecord = new PMXI_Post_Record();
		
		$tmp_files = array();
		// compose records to import
		$records = array();
		if ($this->options['is_import_specified']) {
			foreach (preg_split('% *, *%', $this->options['import_specified'], -1, PREG_SPLIT_NO_EMPTY) as $chank) {
				if (preg_match('%^(\d+)-(\d+)$%', $chank, $mtch)) {
					$records = array_merge($records, range(intval($mtch[1]), intval($mtch[2])));
				} else {
					$records = array_merge($records, array(intval($chank)));
				}
			}
		}
		try { 
			
			$logger and call_user_func($logger, __('Composing titles...', 'pmxi_plugin'));
			$titles = XmlImportParser::factory($xml, $this->xpath, $this->template['title'], $file)->parse($records); $tmp_files[] = $file;

			$logger and call_user_func($logger, __('Composing contents...', 'pmxi_plugin'));			 						
			$contents = XmlImportParser::factory(
				(intval($this->template['is_keep_linebreaks']) ? $xml : preg_replace('%\r\n?|\n%', ' ', $xml)),
				$this->xpath,
				$this->template['content'],
				$file)->parse($records
			); $tmp_files[] = $file;						
												
			$logger and call_user_func($logger, __('Composing dates...', 'pmxi_plugin'));
			
			$dates = XmlImportParser::factory($xml, $this->xpath, $this->options['date'], $file)->parse($records); $tmp_files[] = $file;
			$warned = array(); // used to prevent the same notice displaying several times
			foreach ($dates as $i => $d) {
				$time = strtotime($d);
				if (FALSE === $time) {
					in_array($d, $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'pmxi_plugin'), $warned[] = $d));
					$time = time();
				}
				$dates[$i] = date('Y-m-d H:i:s', $time);
			}
							
			if ('post' == $this->options['type']) {
				$tags = array();
				if ($this->options['tags']) {
					$logger and call_user_func($logger, __('Composing tags...', 'pmxi_plugin'));
					$tags_raw = XmlImportParser::factory($xml, $this->xpath, $this->options['tags'], $file)->parse($records); $tmp_files[] = $file;
					foreach ($tags_raw as $i => $t_raw) {
						$tags[$i] = '';
						if ('' != $t_raw) $tags[$i] = implode(', ', str_getcsv($t_raw, $this->options['tags_delim']));
					}
				} else {
					count($titles) and $tags = array_fill(0, count($titles), '');
				}
								
				$cats = array();

				$categories_hierarchy = (!empty($this->options['categories'])) ?  json_decode($this->options['categories']) : array();

				if ((!empty($categories_hierarchy) and is_array($categories_hierarchy))){						

					$logger and call_user_func($logger, __('Composing categories...', 'pmxi_plugin'));
					$categories = array();
					
					foreach ($categories_hierarchy as $category) $categories[] = $category->xpath;
					
					$cats_raw = XmlImportParser::factory($xml, $this->xpath, ((!empty($categories)) ? implode(',', $categories) : ''), $file)->parse($records); $tmp_files[] = $file;
					$warned = array(); // used to prevent the same notice displaying several times
					foreach ($cats_raw as $i => $c_raw) {
						$cats[$i] = array();
						if ('' != $c_raw) foreach (str_getcsv($c_raw, ',') as $j => $c) if ('' != $c) {
							$cat = get_term_by('name', $c, 'category') or ctype_digit($c) and $cat = get_term_by('id', $c, 'category');
							if ( ! $cat) { // create category automatically
								if (!empty($categories_hierarchy[$j]->parent_id)) {								
									$parent_id = $this->reverse_hierarchy($categories_hierarchy, $c_raw, $categories_hierarchy[$j]->parent_id, $warned);
									$cat_id = wp_create_category($c, $parent_id);
									if ( ! $cat_id) {
										in_array($c, $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to create category `%s`, skipping', 'pmxi_plugin'), $warned[] = $c));
									} else {
										$cats[$i][] = $cat_id;
									}
								}
								else {									
									$cat_id = wp_create_category($c);
									if ( ! $cat_id) {
										in_array($c, $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to create category `%s`, skipping', 'pmxi_plugin'), $warned[] = $c));
									} else {
										$cats[$i][] = $cat_id;
									}
								}
							} else {
								$cats[$i][] = $cat->term_id;
							}
						}
					}					
				} else {
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
					$logger and call_user_func($logger, sprintf(__('Composing terms for `%s` taxonomy...', 'pmxi_plugin'), $tx->labels->name));
					$txes = array();

					$taxonomies_hierarchy = json_decode($tx_template);				
					foreach ($taxonomies_hierarchy as $taxonomy) $txes[] = $taxonomy->xpath;

					$txes_raw =  XmlImportParser::factory($xml, $this->xpath, ((!empty($txes)) ? implode(',', $txes) : ''), $file)->parse($records); $tmp_files[] = $file;
					$warned = array();
					foreach ($txes_raw as $i => $tx_raw) {
						$taxonomies[$tx_name][$i] = array();
						if ('' != $tx_raw) foreach (str_getcsv($tx_raw, ',') as $j => $c) if ('' != $c) {
							$cat = get_term_by('name', $c, $tx_name) or ctype_digit($c) and $cat = get_term_by('id', $c, $tx_name);
							if ( ! $cat) { // create taxonomy automatically
								if (!empty($taxonomies_hierarchy[$j]->parent_id)) {								
									$parent_term_id = $this->reverse_hierarchy($taxonomies_hierarchy, $tx_raw, $taxonomies_hierarchy[$j]->parent_id, $warned);
									
									$term = wp_insert_term(
										$c, // the term 
									  	$tx_name, // the taxonomy
									  	array(									    	
									    	'parent'=> $parent_term_id
									  	)
									);
									$cat_id = $term['term_id'];
									if ( ! $cat_id) {
										in_array($c, $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to create category `%s`, skipping', 'pmxi_plugin'), $warned[] = $c));
									} else {
										$taxonomies[$tx_name][$i][] = $c;
									}
								}
								else {									
									$term = wp_insert_term(
										$c, // the term 
									  	$tx_name // the taxonomy									  	
									);
									$cat_id = $term['term_id'];
									if ( ! $cat_id) {
										in_array($c, $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to create category `%s`, skipping', 'pmxi_plugin'), $warned[] = $c));
									} else {
										$taxonomies[$tx_name][$i][] = $c;
									}
								}
							} else {
								$taxonomies[$tx_name][$i][] = $cat->name;
							}
						}						
					}
				}
			}; endif;		
			// [/custom taxonomies]
			
			$logger and call_user_func($logger, __('Composing custom parameters...', 'pmxi_plugin'));
			$meta_keys = array(); $meta_values = array();
			foreach ($this->options['custom_name'] as $j => $custom_name) {
				$meta_keys[$j] = XmlImportParser::factory($xml, $this->xpath, $custom_name, $file)->parse($records); $tmp_files[] = $file;
				$meta_values[$j] = XmlImportParser::factory($xml, $this->xpath, $this->options['custom_value'][$j], $file)->parse($records); $tmp_files[] = $file;
			}					
			// serialized custom post fields
			$serialized_meta = array();
			if (!empty($meta_keys)){
				foreach ($meta_keys as $j => $custom_name) {					
					if (!in_array($custom_name[0], array_keys($serialized_meta))){
						$serialized_meta[$custom_name[0]] = array($meta_values[$j]);						
					}
					else{
						$serialized_meta[$custom_name[0]][] = $meta_values[$j];
					}
				}
			} 			
			if ( ! (($uploads = wp_upload_dir()) && false === $uploads['error'])) {
				$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': ' . $uploads['error']);
				$logger and call_user_func($logger, __('<b>WARNING</b>: No featured images will be created', 'pmxi_plugin'));
			} else {
				$logger and call_user_func($logger, __('Composing URLs for featured images...', 'pmxi_plugin'));
				$featured_images = array();
				if ($this->options['featured_image']) {
					// Detect if images is separated by comma
					$imgs = explode(',',$this->options['featured_image']);					
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
			$logger and call_user_func($logger, __('Composing unique keys...', 'pmxi_plugin'));
			$unique_keys = XmlImportParser::factory($xml, $this->xpath, $this->options['unique_key'], $file)->parse($records); $tmp_files[] = $file;
			
			$logger and call_user_func($logger, __('Processing posts...', 'pmxi_plugin'));
			
			if ('post' == $this->options['type'] and '' != $this->options['custom_type']) {
				$post_type = $this->options['custom_type'];
			} else {
				$post_type = $this->options['type'];
			}
			
			$current_post_ids = array();
			foreach ($titles as $i => $void) {
				$articleData = array(
					'post_type' => $post_type,
					'post_status' => $this->options['status'],
					'comment_status' => $this->options['comment_status'],
					'ping_status' => $this->options['ping_status'],
					'post_title' => $titles[$i],
					'post_content' => $contents[$i],
					'post_date' => $dates[$i],
					'post_date_gmt' => get_gmt_from_date($dates[$i]),
					'post_author' => $this->options['author'],
				);
				if ('post' == $articleData['post_type']) {
					$articleData += array(
						'post_category' => $cats[$i],
						'tags_input' => $tags[$i],
					);
				} else { // page
					$articleData += array(
						'menu_order' => $this->options['order'],
						'post_parent' => $this->options['parent'],
					);
				}
				$postRecord->clear();
				// find corresponding article among previously imported
				$postRecord->getBy(array(
					'unique_key' => $unique_keys[$i],
					'import_id' => $this->id,
				));
				if ( ! $postRecord->isEmpty()) {
					$post_to_update = get_post($post_to_update_id = $postRecord->post_id);
					if ($post_to_update) { // existing post is found
						if ($this->options['is_keep_former_posts']) {
							$current_post_ids[] = $postRecord->post_id;
							$logger and call_user_func($logger, sprintf(__('<b>SKIPPED</b>: Previously imported record found for `%s`', 'pmxi_plugin'), $articleData['post_title']));
							continue;
						}
						$articleData['ID'] = $postRecord->post_id;
						// preserve date of already existing article when duplicate is found
						$articleData['post_date'] = $post_to_update->post_date;
						$articleData['post_date_gmt'] = $post_to_update->post_date_gmt;
						if ($this->options['is_keep_categories']) { // preserve categories and tags of already existing article if corresponding setting is specified
							$cats_list = get_the_category($articleData['ID']);
							if (is_wp_error($cats_list)) {
								$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current categories for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
							} else {
								$cats_new = array();
								foreach ($cats_list as $c) {
									$cats_new[] = $c->cat_ID;
								}
								$articleData['post_category'] = $cats_new;
							}
							
							$tags_list = get_the_tags($articleData['ID']);
							if (is_wp_error($tags_list)) {
								$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current tags for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
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
								} else {
									$txes_new = array();
									foreach ($txes_list as $t) {
										$txes_new[] = $t->name;
									}
									$taxonomies[$tx_name][$i] = $txes_new;
								}
							}
						}
						if ($this->options['is_keep_status']) { // preserve status and trashed flag
							$articleData['post_status'] = $post_to_update->post_status;
						}
						if ($this->options['is_keep_content']){ // Re-run an importer to pull in one more custom field... without nuking their edits in the process..
							$articleData['post_content'] = $post_to_update->post_content;
						}
					} else { // existing post not found though it's track was found... clear the leftover, plugin will continue to treat record as new
						$postRecord->delete();
					}
				}

				if ( ! empty($articleData['ID'])) { // handle obsolete attachments (i.e. delete or keep) according to import settings
					empty($this->options['is_keep_attachments']) and wp_delete_attachments($articleData['ID']);
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
										$logger and call_user_func($logger, sprintf(__('<b>ERROR</b>: Unable to create cloaked link for %s', 'pmxi_plugin'), $url));
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
				} else {
					
					do_action( 'pmxi_saved_post', $pid); // hook that was triggered immediately after post saved

					$current_post_ids[] = $pid;
					// associate post with import
					$postRecord->isEmpty() and $postRecord->set(array(
						'post_id' => $pid,
						'import_id' => $this->id,
						'unique_key' => $unique_keys[$i],
					))->insert();
					
					// [custom taxonomies]
					foreach ($taxonomies as $tx_name => $txes) {
						$term_ids = wp_set_object_terms($pid, $txes[$i], $tx_name);
						if (is_wp_error($term_ids)) {
							$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': '.$term_ids->get_error_message());
						}
					}
					// [/custom taxonomies]
					
					if (empty($articleData['ID'])) {
						$logger and call_user_func($logger, sprintf(__('`%s` post created successfully', 'pmxi_plugin'), $articleData['post_title']));
					} else {
						$logger and call_user_func($logger, sprintf(__('`%s` post updated successfully', 'pmxi_plugin'), $articleData['post_title']));
					}
				}
				wp_cache_flush();
			}
			if ( ! empty($this->options['is_delete_missing'])) { // delete posts which are not in current import set
				$logger and call_user_func($logger, 'Removing previously imported posts which are no longer actual...');
				$postList = new PMXI_Post_List();
				foreach ($postList->getBy(array('import_id' => $this->id, 'post_id NOT IN' => $current_post_ids)) as $missingPost) {
					empty($this->options['is_keep_attachments']) and wp_delete_attachments($missingPost['post_id']);
					wp_delete_post($missingPost['post_id'], true);
				}
			}
			
		} catch (XmlImportException $e) {
			$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': ' . $e->getMessage());
		}
		$this->set('registered_on', date('Y-m-d H:i:s'))->save(); // specify execution is successful
		
		$logger and call_user_func($logger, __('Cleaning temporary data...', 'pmxi_plugin'));
		foreach ($tmp_files as $file) { // remove all temporary files created
			unlink($file);
		}
		
		if ($this->options['is_delete_source'] and $last_chank) {
			$logger and call_user_func($logger, __('Deleting source XML file...', 'pmxi_plugin'));
			if ( ! @unlink($this->path)) {
				$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to remove %s', 'pmxi_plugin'), $this->path));
			}
		}
		$logger and call_user_func($logger, 'Done');
		
		remove_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // return any filtering rules back if they has been disabled for import procedure
		
		return $this;
	}

	public function reverse_hierarchy($categories_hierarchy, $c_raw, $parent_id = null, &$warned, $taxonomy = 'category'){						
		foreach (str_getcsv($c_raw, ',') as $j => $c) if ('' != $c and $categories_hierarchy[$j]->item_id == $parent_id) {
			$cat = get_term_by('name', $c, $taxonomy) or ctype_digit($c) and $cat = get_term_by('id', $c, $taxonomy);
			if ( ! $cat) { // create category automatically
				if (!empty($categories_hierarchy[$j]->parent_id)) {								
					return $this->reverse_hierarchy($categories_hierarchy, $c_raw, $categories_hierarchy[$j]->parent_id, $warned, $taxonomy);
				}
				else {		
					if ($taxonomy == 'category')	
					{						
						$cat_id = wp_create_category($c);
					}
					else{						
						$term = wp_insert_term(
						  	$c, 
							$taxonomy
						);
						$cat_id = $term['term_id'];
					}
					if ( ! $cat_id) {
						in_array($c, $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to create category `%s`, skipping', 'pmxi_plugin'), $warned[] = $c));
					} else {
						return $cat_id;
					}
				}
			} else {
				return $cat->term_id;
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
	public function findDuplicates($articleData)
	{
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
			foreach ($post->getBy('import_id', $this->id)->convertRecords() as $p) {
				empty($this->options['is_keep_attachments']) and wp_delete_attachments($p->post_id);
				wp_delete_post($p->post_id, TRUE);
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

/**
 * Cron schedule parser 
 */
class _PMXI_Import_Record_Cron_Parser
{
    /**
     * @var array Cron parts
     */
    private $_cronParts;

    /**
     * Constructor
     *
     * @param string $schedule Cron schedule string (e.g. '8 * * * *').  The 
     *      schedule can handle ranges (10-12) and intervals
     *      (*\/10 [remove the backslash]).  Schedule parts should map to
     *      minute [0-59], hour [0-23], day of month, month [1-12], day of week [1-7]
     *
     * @throws InvalidArgumentException if $schedule is not a valid cron schedule
     */
    public function __construct($schedule)
    {
        $this->_cronParts = explode(' ', $schedule);
        if (count($this->_cronParts) != 5) {
            throw new Exception($schedule . ' is not a valid cron schedule string');
        }
    }

    /**
     * Check if a date/time unit value satisfies a crontab unit
     *
     * @param DateTime $nextRun Current next run date
     * @param string $unit Date/time unit type (e.g. Y, m, d, H, i)
     * @param string $schedule Cron schedule variable
     *
     * @return bool Returns TRUE if the unit satisfies the constraint
     */
    public function unitSatisfiesCron(DateTime $nextRun, $unit, $schedule)
    {
        $unitValue = (int)$nextRun->format($unit);

        if ($schedule == '*') {
            return true;
        } if (strpos($schedule, '-')) {
            list($first, $last) = explode('-', $schedule);
            return $unitValue >= $first && $unitValue <= $last;
        } else if (strpos($schedule, '*/') !== false) {
            list($delimiter, $interval) = explode('*/', $schedule);
            return $unitValue % (int)$interval == 0;
        } else {
            return $unitValue == (int)$schedule;
        }
    }

    /**
     * Get the date in which the cron will run next
     *
     * @param string|DateTime (optional) $fromTime Set the relative start time
     *
     * @return DateTime
     */
    public function getNextRunDate($fromTime = 'now')
    {
        $nextRun = ($fromTime instanceof DateTime) ? $fromTime : new DateTime($fromTime);
        $nextRun->setTime($nextRun->format('H'), $nextRun->format('i'), 0);
        $nextRun->modify('+1 minute'); // make sure we don't return the very date is submitted to the function
        $nextRunLimit = clone $nextRun; $nextRunLimit->modify('+1 year');
        
        while ($nextRun < $nextRunLimit) { // Set a hard limit to bail on an impossible date

            // Adjust the month until it matches.  Reset day to 1 and reset time.
            if ( ! $this->unitSatisfiesCron($nextRun, 'm', $this->getSchedule('month'))) {
                $nextRun->modify('+1 month');
                $nextRun->setDate($nextRun->format('Y'), $nextRun->format('m'), 1);
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the day of the month by incrementing the day until it matches. Reset time.
            if ( ! $this->unitSatisfiesCron($nextRun, 'd', $this->getSchedule('day_of_month'))) {
                $nextRun->modify('+1 day');
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the day of week by incrementing the day until it matches.  Resest time.
            if ( ! $this->unitSatisfiesCron($nextRun, 'N', $this->getSchedule('day_of_week'))) {
                $nextRun->modify('+1 day');
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the hour until it matches the set hour.  Set seconds and minutes to 0
            if ( ! $this->unitSatisfiesCron($nextRun, 'H', $this->getSchedule('hour'))) {
                $nextRun->modify('+1 hour');
                $nextRun->setTime($nextRun->format('H'), 0, 0);
                continue;
            }

            // Adjust the minutes until it matches a set minute
            if ( ! $this->unitSatisfiesCron($nextRun, 'i', $this->getSchedule('minute'))) {
                $nextRun->modify('+1 minute');
                continue;
            }

            break;
        }

        return $nextRun;
    }

    /**
     * Get all or part of the cron schedule string
     *
     * @param string $part Specify the part to retrieve or NULL to get the full
     *      cron schedule string.  $part can be the PHP date() part of a date
     *      formatted string or one of the following values:
     *      NULL, 'minute', 'hour', 'month', 'day_of_week', 'day_of_month'
     *
     * @return string
     */
    public function getSchedule($part = null)
    {
        switch ($part) {
            case 'minute': case 'i':
                return $this->_cronParts[0];
            case 'hour': case 'H':
                return $this->_cronParts[1];
            case 'day_of_month': case 'd':
                return $this->_cronParts[2];
            case 'month': case 'm':
                return $this->_cronParts[3];
            case 'day_of_week': case 'N':
                return $this->_cronParts[4];
            default:
                return implode(' ', $this->_cronParts);
        }
    }

    /**
     * Deterime if the cron is due to run based on the current time, last run
     * time, and the next run time.
     * 
     * If the relative next run time based on the last run time is not equal to 
     * the next suggested run time based on the current time, then the cron 
     * needs to run.
     *
     * @param string|DateTime $lastRun (optional) Date the cron was last run.
     *
     * @return bool Returns TRUE if the cron is due to run or FALSE if not
     */
    public function isDue($lastRun = 'now')
    {
        return $this->getNextRunDate($lastRun) < $this->getNextRunDate();
    }
}
