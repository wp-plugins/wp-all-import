<?php

class PMXI_Import_Record extends PMXI_Model_Record {

	public static $cdata = array();

	protected $errors;	
	
	/**
	 * Some pre-processing logic, such as removing control characters from xml to prevent parsing errors
	 * @param string $xml
	 */
	public static function preprocessXml( & $xml) {		
		
		if ( empty(PMXI_Plugin::$session->is_csv) and empty(PMXI_Plugin::$is_csv)){ 
		
			self::$cdata = array();			

			$xml = preg_replace_callback('/<!\[CDATA\[[^\]\]>]*\]\]>/s', 'pmxi_cdata_filter', $xml );

			$xml = str_replace("&", "&amp;", str_replace("&amp;","&", $xml));

			if ( ! empty(self::$cdata) ){
			    foreach (self::$cdata as $key => $val) {
			        $xml = str_replace('{{CPLACE_' . ($key + 1) . '}}', $val, $xml);
			    }
			}
		}		
	}

	/**
	 * Validate XML to be valid for import
	 * @param string $xml
	 * @param WP_Error[optional] $errors
	 * @return bool Validation status
	 */
	public static function validateXml( & $xml, $errors = NULL) {
		if (FALSE === $xml or '' == $xml) {
			$errors and $errors->add('form-validation', __('WP All Import can\'t read your file.<br/><br/>Probably, you are trying to import an invalid XML feed. Try opening the XML feed in a web browser (Google Chrome is recommended for opening XML files) to see if there is an error message.<br/>Alternatively, run the feed through a validator: http://validator.w3.org/<br/>99% of the time, the reason for this error is because your XML feed isn\'t valid.<br/>If you are 100% sure you are importing a valid XML feed, please contact WP All Import support.', 'pmxi_plugin'));
		} else {
						
			PMXI_Import_Record::preprocessXml($xml);																						

			if ( function_exists('simplexml_load_string')){
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
			else{
				$errors and $errors->add('form-validation', __('Required PHP components are missing.', 'pmxi_plugin'));				
				$errors and $errors->add('form-validation', __('WP All Import requires the SimpleXML PHP module to be installed. This is a standard feature of PHP, and is necessary for WP All Import to read the files you are trying to import.<br/>Please contact your web hosting provider and ask them to install and activate the SimpleXML PHP module.', 'pmxi_plugin'));				
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
		$this->errors = new WP_Error();
	}
	
	public $post_meta_to_insert = array();

	/**
	 * Perform import operation
	 * @param string $xml XML string to import
	 * @param callback[optional] $logger Method where progress messages are submmitted
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function process($xml, $logger = NULL, $chunk = false, $is_cron = false, $xpath_prefix = '', $loop = 0) {
		add_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // do not perform special filtering for imported content
		
		kses_remove_filters();

		$cxpath = $xpath_prefix . $this->xpath;		

		$this->options += PMXI_Plugin::get_default_import_options(); // make sure all options are defined
		
		$avoid_pingbacks = PMXI_Plugin::getInstance()->getOption('pingbacks');

		$cron_sleep = (int) PMXI_Plugin::getInstance()->getOption('cron_sleep');
		
		if ( $avoid_pingbacks and ! defined( 'WP_IMPORTING' ) ) define( 'WP_IMPORTING', true );

		$postRecord = new PMXI_Post_Record();		
		
		$tmp_files = array();
		// compose records to import
		$records = array();

		$is_import_complete = false;
		
		try { 						
			
			$chunk == 1 and $logger and call_user_func($logger, __('Composing titles...', 'pmxi_plugin'));
			if ( ! empty($this->options['title'])){
				$titles = XmlImportParser::factory($xml, $cxpath, $this->options['title'], $file)->parse($records); $tmp_files[] = $file;							
			}
			else{
				$loop and $titles = array_fill(0, $loop, '');
			}

			$chunk == 1 and $logger and call_user_func($logger, __('Composing excerpts...', 'pmxi_plugin'));			
			$post_excerpt = array();
			if ( ! empty($this->options['post_excerpt']) ){
				$post_excerpt = XmlImportParser::factory($xml, $cxpath, $this->options['post_excerpt'], $file)->parse($records); $tmp_files[] = $file;
			}
			else{
				count($titles) and $post_excerpt = array_fill(0, count($titles), '');
			}			

			if ( "xpath" == $this->options['status'] ){
				$chunk == 1 and $logger and call_user_func($logger, __('Composing statuses...', 'pmxi_plugin'));			
				$post_status = array();
				if (!empty($this->options['status_xpath'])){
					$post_status = XmlImportParser::factory($xml, $cxpath, $this->options['status_xpath'], $file)->parse($records); $tmp_files[] = $file;
				}
				else{
					count($titles) and $post_status = array_fill(0, count($titles), '');
				}
			}

			$chunk == 1 and $logger and call_user_func($logger, __('Composing authors...', 'pmxi_plugin'));			
			$post_author = array();
			$current_user = wp_get_current_user();

			if (!empty($this->options['author'])){
				$post_author = XmlImportParser::factory($xml, $cxpath, $this->options['author'], $file)->parse($records); $tmp_files[] = $file;
				foreach ($post_author as $key => $author) {
					$user = get_user_by('login', $author) or $user = get_user_by('slug', $author) or $user = get_user_by('email', $author) or ctype_digit($author) and $user = get_user_by('id', $author);					
					$post_author[$key] = (!empty($user)) ? $user->ID : $current_user->ID;
				}
			}
			else{								
				count($titles) and $post_author = array_fill(0, count($titles), $current_user->ID);
			}			

			$chunk == 1 and $logger and call_user_func($logger, __('Composing slugs...', 'pmxi_plugin'));			
			$post_slug = array();
			if (!empty($this->options['post_slug'])){
				$post_slug = XmlImportParser::factory($xml, $cxpath, $this->options['post_slug'], $file)->parse($records); $tmp_files[] = $file;
			}
			else{
				count($titles) and $post_slug = array_fill(0, count($titles), '');
			}

			$chunk == 1 and $logger and call_user_func($logger, __('Composing menu order...', 'pmxi_plugin'));			
			$menu_order = array();
			if (!empty($this->options['order'])){
				$menu_order = XmlImportParser::factory($xml, $cxpath, $this->options['order'], $file)->parse($records); $tmp_files[] = $file;
			}
			else{
				count($titles) and $menu_order = array_fill(0, count($titles), '');
			}

			$chunk == 1 and $logger and call_user_func($logger, __('Composing contents...', 'pmxi_plugin'));			 						
			if (!empty($this->options['content'])){
				$contents = XmlImportParser::factory(
					((!empty($this->options['is_keep_linebreaks']) and intval($this->options['is_keep_linebreaks'])) ? $xml : preg_replace('%\r\n?|\n%', ' ', $xml)),
					$cxpath,
					$this->options['content'],
					$file)->parse($records
				); $tmp_files[] = $file;						
			}
			else{
				count($titles) and $contents = array_fill(0, count($titles), '');
			}
										
			$chunk == 1 and $logger and call_user_func($logger, __('Composing dates...', 'pmxi_plugin'));
			if ('specific' == $this->options['date_type']) {
				$dates = XmlImportParser::factory($xml, $cxpath, $this->options['date'], $file)->parse($records); $tmp_files[] = $file;
				$warned = array(); // used to prevent the same notice displaying several times
				foreach ($dates as $i => $d) {
					if ($d == 'now') $d = current_time('mysql'); // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
					$time = strtotime($d);
					if (FALSE === $time) {
						in_array($d, $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'pmxi_plugin'), $warned[] = $d));
						$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
						$time = time();
					}
					$dates[$i] = date('Y-m-d H:i:s', $time);
				}
			} else {
				$dates_start = XmlImportParser::factory($xml, $cxpath, $this->options['date_start'], $file)->parse($records); $tmp_files[] = $file;
				$dates_end = XmlImportParser::factory($xml, $cxpath, $this->options['date_end'], $file)->parse($records); $tmp_files[] = $file;
				$warned = array(); // used to prevent the same notice displaying several times
				foreach ($dates_start as $i => $d) {
					$time_start = strtotime($dates_start[$i]);
					if (FALSE === $time_start) {
						in_array($dates_start[$i], $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'pmxi_plugin'), $warned[] = $dates_start[$i]));
						$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
						$time_start = time();
					}
					$time_end = strtotime($dates_end[$i]);
					if (FALSE === $time_end) {
						in_array($dates_end[$i], $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'pmxi_plugin'), $warned[] = $dates_end[$i]));
						$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
						$time_end = time();
					}					
					$dates[$i] = date('Y-m-d H:i:s', mt_rand($time_start, $time_end));
				}
			}
						
			// [custom taxonomies]
			require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');

			$taxonomies = array();						
			$exclude_taxonomies = (class_exists('PMWI_Plugin')) ? array('post_format', 'product_type', 'product_shipping_class') : array('post_format');	
			$post_taxonomies = array_diff_key(get_taxonomies_by_object_type(array($this->options['custom_type']), 'object'), array_flip($exclude_taxonomies));
			if ( ! empty($post_taxonomies) ):
				foreach ($post_taxonomies as $ctx): if ("" == $ctx->labels->name or (class_exists('PMWI_Plugin') and strpos($ctx->name, "pa_") === 0 and $this->options['custom_type'] == "product")) continue;
					$chunk == 1 and $logger and call_user_func($logger, sprintf(__('Composing terms for `%s` taxonomy...', 'pmxi_plugin'), $ctx->labels->name));
					$tx_name = $ctx->name;
					$mapping_rules = ( ! empty($this->options['tax_mapping'][$tx_name])) ? json_decode($this->options['tax_mapping'][$tx_name], true) : false;
					$taxonomies[$tx_name] = array();
					if ( ! empty($this->options['tax_logic'][$tx_name]) ){
						switch ($this->options['tax_logic'][$tx_name]){
							case 'single':
								if ( ! empty($this->options['tax_single_xpath'][$tx_name]) ){
									$txes = XmlImportParser::factory($xml, $cxpath, str_replace('\'','"',$this->options['tax_single_xpath'][$tx_name]), $file)->parse($records); $tmp_files[] = $file;		
									foreach ($txes as $i => $tx) {
										$taxonomies[$tx_name][$i][] = pmxi_ctx_mapping(array(
											'name' => $tx,
											'parent' => false,
											'assign' => $this->options['tax_assing'][$tx_name],
											'is_mapping' => (!empty($this->options['tax_enable_mapping'][$tx_name]))
										), $mapping_rules, $tx_name);
									}									
								}
								break;
							case 'multiple':
								if ( ! empty($this->options['tax_multiple_xpath'][$tx_name]) ){
									$txes = XmlImportParser::factory($xml, $cxpath, str_replace('\'','"',$this->options['tax_multiple_xpath'][$tx_name]), $file)->parse($records); $tmp_files[] = $file;		
									foreach ($txes as $i => $tx) {
										$delimeted_taxonomies = explode( ! empty($this->options['tax_multiple_delim'][$tx_name]) ? $this->options['tax_multiple_delim'][$tx_name] : ',', $tx);
										if ( ! empty($delimeted_taxonomies) ){
											foreach ($delimeted_taxonomies as $cc) {												
												$taxonomies[$tx_name][$i][] = pmxi_ctx_mapping(array(
													'name' => $cc,
													'parent' => false,
													'assign' => $this->options['tax_assing'][$tx_name],
													'is_mapping' => (!empty($this->options['tax_enable_mapping'][$tx_name]))
												), $mapping_rules, $tx_name);
											}
										}
									}
								}
								break;
							case 'hierarchical':
								if ( ! empty($this->options['tax_hierarchical_logic'][$tx_name])){
									switch ($this->options['tax_hierarchical_logic'][$tx_name]) {
										case 'entire':
											if (! empty($this->options['tax_hierarchical_xpath'][$tx_name])){
												$txes = XmlImportParser::factory($xml, $cxpath, str_replace('\'','"',$this->options['tax_hierarchical_xpath'][$tx_name]), $file)->parse($records); $tmp_files[] = $file;		
												foreach ($txes as $i => $tx) {
													$delimeted_taxonomies = explode( ! empty($this->options['tax_hierarchical_delim'][$tx_name]) ? $this->options['tax_hierarchical_delim'][$tx_name] : ',', $tx);
													if ( ! empty($delimeted_taxonomies) ){
														foreach ($delimeted_taxonomies as $j => $cc) {												
															$taxonomies[$tx_name][$i][] = pmxi_ctx_mapping(array(
																'name' => $cc,
																'parent' => (!empty($taxonomies[$tx_name][$i][$j - 1])) ? $taxonomies[$tx_name][$i][$j - 1] : false,
																'assign' => $this->options['tax_assing'][$tx_name],
																'is_mapping' => (!empty($this->options['tax_enable_mapping'][$tx_name]))
															), $mapping_rules, $tx_name);
														}
													}
												}
											}
											break;										
										case 'manual':
											if ( ! empty($this->options['post_taxonomies'][$tx_name]) ){
												$taxonomies_hierarchy = json_decode($this->options['post_taxonomies'][$tx_name], true);
												
												foreach ($taxonomies_hierarchy as $k => $taxonomy){	if ("" == $taxonomy['xpath']) continue;								
													$txes_raw =  XmlImportParser::factory($xml, $cxpath, str_replace('\'','"',$taxonomy['xpath']), $file)->parse($records); $tmp_files[] = $file;						
													$warned = array();
													
													foreach ($txes_raw as $i => $cc) {
														if (empty($taxonomies_hierarchy[$k]['txn_names'][$i])) $taxonomies_hierarchy[$k]['txn_names'][$i] = array();
														if (empty($taxonomies[$tx_name][$i])) $taxonomies[$tx_name][$i] = array();
														$count_cats = count($taxonomies[$tx_name][$i]);																											
														
														if (!empty($taxonomy['parent_id'])) {																			
															foreach ($taxonomies_hierarchy as $key => $value){
																if ($value['item_id'] == $taxonomy['parent_id'] and !empty($value['txn_names'][$i])){													
																	foreach ($value['txn_names'][$i] as $parent) {																			
																		$taxonomies[$tx_name][$i][] = pmxi_ctx_mapping(array(
																			'name' => trim($cc),
																			'parent' => $parent,
																			'assign' => $this->options['tax_assing'][$tx_name],
																			'is_mapping' => (!empty($this->options['tax_enable_mapping'][$tx_name]))
																		), $mapping_rules, $tx_name);																		
																	}																												
																}
															}															
														}
														else {																
															$taxonomies[$tx_name][$i][] = pmxi_ctx_mapping(array(
																'name' => trim($cc),
																'parent' => false,
																'assign' => $this->options['tax_assing'][$tx_name],
																'is_mapping' => (!empty($this->options['tax_enable_mapping'][$tx_name]))
															), $mapping_rules, $tx_name);
														}								
														
														if ($count_cats < count($taxonomies[$tx_name][$i])) $taxonomies_hierarchy[$k]['txn_names'][$i][] = $taxonomies[$tx_name][$i][count($taxonomies[$tx_name][$i]) - 1];
													}
												}
											}
											break;

										default:
											
											break;
									}
								}								
								break;

							default:
											
								break;
						}
					}
				endforeach;
			endif;			
			// [/custom taxonomies]												

			// Composing featured images
			if ( ! (($uploads = wp_upload_dir()) && false === $uploads['error'])) {
				$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $uploads['error']);
				$logger and call_user_func($logger, __('<b>WARNING</b>: No featured images will be created. Uploads folder is not found.', 'pmxi_plugin'));				
				$logger and !$is_cron and PMXI_Plugin::$session->warnings++;				
			} else {
				$chunk == 1 and $logger and call_user_func($logger, __('Composing URLs for featured images...', 'pmxi_plugin'));
				$featured_images = array();				
				if ( "no" == $this->options['download_images']){
					if ($this->options['featured_image']) {					
						$featured_images = XmlImportParser::factory($xml, $cxpath, $this->options['featured_image'], $file)->parse($records); $tmp_files[] = $file;																				
					} else {
						count($titles) and $featured_images = array_fill(0, count($titles), '');
					}					
				}
				else{
					if ($this->options['download_featured_image']) {					
						$featured_images = XmlImportParser::factory($xml, $cxpath, $this->options['download_featured_image'], $file)->parse($records); $tmp_files[] = $file;																				
					} else {
						count($titles) and $featured_images = array_fill(0, count($titles), '');
					}
				}				
			}	
			
			// Composing images meta titles
			if ( $this->options['set_image_meta_title'] ){																	
				$chunk == 1 and $logger and call_user_func($logger, __('Composing image meta data (titles)...', 'pmxi_plugin'));
				$image_meta_titles = array();				

				if ($this->options['image_meta_title']) {					
					$image_meta_titles = XmlImportParser::factory($xml, $cxpath, $this->options['image_meta_title'], $file)->parse($records); $tmp_files[] = $file;						
				} else {
					count($titles) and $image_meta_titles = array_fill(0, count($titles), '');
				}
			}

			// Composing images meta captions
			if ( $this->options['set_image_meta_caption'] ){	
				$chunk == 1 and $logger and call_user_func($logger, __('Composing image meta data (captions)...', 'pmxi_plugin'));
				$image_meta_captions = array();				
				if ($this->options['image_meta_caption']) {					
					$image_meta_captions = XmlImportParser::factory($xml, $cxpath, $this->options['image_meta_caption'], $file)->parse($records); $tmp_files[] = $file;								
				} else {
					count($titles) and $image_meta_captions = array_fill(0, count($titles), '');
				}
			}

			// Composing images meta alt text
			if ( $this->options['set_image_meta_alt'] ){	
				$chunk == 1 and $logger and call_user_func($logger, __('Composing image meta data (alt text)...', 'pmxi_plugin'));
				$image_meta_alts = array();				
				if ($this->options['image_meta_alt']) {					
					$image_meta_alts = XmlImportParser::factory($xml, $cxpath, $this->options['image_meta_alt'], $file)->parse($records); $tmp_files[] = $file;						
				} else {
					count($titles) and $image_meta_alts = array_fill(0, count($titles), '');
				}
			}

			// Composing images meta description
			if ( $this->options['set_image_meta_description'] ){	
				$chunk == 1 and $logger and call_user_func($logger, __('Composing image meta data (description)...', 'pmxi_plugin'));
				$image_meta_descriptions = array();				
				if ($this->options['image_meta_description']) {					
					$image_meta_descriptions = XmlImportParser::factory($xml, $cxpath, $this->options['image_meta_description'], $file)->parse($records); $tmp_files[] = $file;						
				} else {
					count($titles) and $image_meta_descriptions = array_fill(0, count($titles), '');
				}								
			}

			if ( "yes" == $this->options['download_images'] ){
				// Composing images suffix
				$chunk == 1 and $this->options['auto_rename_images'] and $logger and call_user_func($logger, __('Composing images suffix...', 'pmxi_plugin'));			
				$auto_rename_images = array();
				if ( $this->options['auto_rename_images'] and ! empty($this->options['auto_rename_images_suffix'])){
					$auto_rename_images = XmlImportParser::factory($xml, $cxpath, $this->options['auto_rename_images_suffix'], $file)->parse($records); $tmp_files[] = $file;
				}
				else{
					count($titles) and $auto_rename_images = array_fill(0, count($titles), '');
				}

				// Composing images extensions
				$chunk == 1 and $this->options['auto_set_extension'] and $logger and call_user_func($logger, __('Composing images extensions...', 'pmxi_plugin'));			
				$auto_extensions = array();
				if ( $this->options['auto_set_extension'] and ! empty($this->options['new_extension'])){
					$auto_extensions = XmlImportParser::factory($xml, $cxpath, $this->options['new_extension'], $file)->parse($records); $tmp_files[] = $file;
				}
				else{
					count($titles) and $auto_extensions = array_fill(0, count($titles), '');
				}
			}

			// Composing attachments
			if ( ! (($uploads = wp_upload_dir()) && false === $uploads['error'])) {
				$logger and call_user_func($logger, __('<b>WARNING</b>', 'pmxi_plugin') . ': ' . $uploads['error']);				
				$logger and call_user_func($logger, __('<b>WARNING</b>: No attachments will be created', 'pmxi_plugin')); 				
				$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
			} else {
				$chunk == 1 and $logger and call_user_func($logger, __('Composing URLs for attachments files...', 'pmxi_plugin'));
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
								$posts_attachments = XmlImportParser::factory($xml, $cxpath, trim($atch), $file)->parse($records); $tmp_files[] = $file;																
								foreach($posts_attachments as $i => $val) $attachments[$i][] = $val;								
							}
						}
						else
						{
							$attachments = XmlImportParser::factory($xml, $cxpath, $this->options['attachments'], $file)->parse($records); $tmp_files[] = $file;								
						}
					}
					
				} else {
					count($titles) and $attachments = array_fill(0, count($titles), '');
				}
			}				

			$chunk == 1 and $logger and call_user_func($logger, __('Composing unique keys...', 'pmxi_plugin'));
			if (!empty($this->options['unique_key'])){
				$unique_keys = XmlImportParser::factory($xml, $cxpath, $this->options['unique_key'], $file)->parse($records); $tmp_files[] = $file;
			}
			else{
				count($titles) and $unique_keys = array_fill(0, count($titles), '');
			}
			
			$chunk == 1 and $logger and call_user_func($logger, __('Processing posts...', 'pmxi_plugin'));
			
			if ('post' == $this->options['type'] and '' != $this->options['custom_type']) {
				$post_type = $this->options['custom_type'];
			} else {
				$post_type = $this->options['type'];
			}					
			
			$custom_type_details = get_post_type_object( $post_type );

			$addons = array();
			$addons_data = array();

			// data parsing for WP All Import add-ons
			$chunk == 1 and $logger and call_user_func($logger, __('Data parsing via add-ons...', 'pmxi_plugin'));
			$parsingData = array(
				'import' => $this,
				'count'  => count($titles),
				'xml'    => $xml,
				'logger' => $logger,
				'chunk'  => $chunk,
				'xpath_prefix' => $xpath_prefix
			);			
			foreach (PMXI_Admin_Addons::get_active_addons() as $class) {							
				$model_class = str_replace("_Plugin", "_Import_Record", $class);	
				if (class_exists($model_class)){						
					$addons[$class] = new $model_class();
					$addons_data[$class] = ( method_exists($addons[$class], 'parse') ) ? $addons[$class]->parse($parsingData) : false;				
				}
				else{
					$parse_func = $class . '_parse';					
					if (function_exists($parse_func)) $addons_data[$class] = call_user_func($parse_func, $parsingData);					
				}
			}

			// save current import state to variables before import			
			$created = $this->created;
			$updated = $this->updated;
			$skipped = $this->skipped;			
			
			$specified_records = array();

			if ($this->options['is_import_specified']) {
				$chunk == 1 and $logger and call_user_func($logger, __('Calculate specified records to import...', 'pmxi_plugin'));
				foreach (preg_split('% *, *%', $this->options['import_specified'], -1, PREG_SPLIT_NO_EMPTY) as $chank) {
					if (preg_match('%^(\d+)-(\d+)$%', $chank, $mtch)) {
						$specified_records = array_merge($specified_records, range(intval($mtch[1]), intval($mtch[2])));
					} else {
						$specified_records = array_merge($specified_records, array(intval($chank)));
					}
				}

			}					

			foreach ($titles as $i => $void) {			

				if ($is_cron and $cron_sleep) sleep($cron_sleep);		

				$logger and call_user_func($logger, __('---', 'pmxi_plugin'));
				$logger and call_user_func($logger, sprintf(__('Record #%s', 'pmxi_plugin'), $this->imported + $i + 1));

				wp_cache_flush();

				$logger and call_user_func($logger, __('<b>ACTION</b>: pmxi_before_post_import ...', 'pmxi_plugin'));
				do_action('pmxi_before_post_import', $this->id);															

				if ( empty($titles[$i]) ) {
					if ( ! empty($addons_data['PMWI_Plugin']) and !empty($addons_data['PMWI_Plugin']['single_product_parent_ID'][$i]) ){
						$titles[$i] = $addons_data['PMWI_Plugin']['single_product_parent_ID'][$i] . ' Product Variation';
					}					
					else{
						$logger and call_user_func($logger, __('<b>WARNING</b>: title is empty.', 'pmxi_plugin'));
						$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
					}
				}				
				
				if ( $this->options['custom_type'] == 'import_users' ){					
					$articleData = array(			
						'user_pass' => $addons_data['PMUI_Plugin']['pmui_pass'][$i],
						'user_login' => $addons_data['PMUI_Plugin']['pmui_logins'][$i],
						'user_nicename' => $addons_data['PMUI_Plugin']['pmui_nicename'][$i],
						'user_url' =>  $addons_data['PMUI_Plugin']['pmui_url'][$i],
						'user_email' =>  $addons_data['PMUI_Plugin']['pmui_email'][$i],
						'display_name' =>  $addons_data['PMUI_Plugin']['pmui_display_name'][$i],
						'user_registered' =>  $addons_data['PMUI_Plugin']['pmui_registered'][$i],
						'first_name' =>  $addons_data['PMUI_Plugin']['pmui_first_name'][$i],
						'last_name' =>  $addons_data['PMUI_Plugin']['pmui_last_name'][$i],
						'description' =>  $addons_data['PMUI_Plugin']['pmui_description'][$i],
						'nickname' => $addons_data['PMUI_Plugin']['pmui_nickname'][$i],
						'role' => ('' == $addons_data['PMUI_Plugin']['pmui_role'][$i]) ? 'subscriber' : $addons_data['PMUI_Plugin']['pmui_role'][$i],
					);		
					$logger and call_user_func($logger, sprintf(__('Combine all data for user %s...', 'pmxi_plugin'), $articleData['user_login']));
				} 
				else {					
					$articleData = array(
						'post_type' => $post_type,
						'post_status' => ("xpath" == $this->options['status']) ? $post_status[$i] : $this->options['status'],
						'comment_status' => $this->options['comment_status'],
						'ping_status' => $this->options['ping_status'],
						'post_title' => (!empty($this->options['is_leave_html'])) ? html_entity_decode($titles[$i]) : $titles[$i], 
						'post_excerpt' => apply_filters('pmxi_the_excerpt', ((!empty($this->options['is_leave_html'])) ? html_entity_decode($post_excerpt[$i]) : $post_excerpt[$i]), $this->id),
						'post_name' => $post_slug[$i],
						'post_content' => apply_filters('pmxi_the_content', ((!empty($this->options['is_leave_html'])) ? html_entity_decode($contents[$i]) : $contents[$i]), $this->id),
						'post_date' => $dates[$i],
						'post_date_gmt' => get_gmt_from_date($dates[$i]),
						'post_author' => $post_author[$i],						
						'menu_order' => (int) $menu_order[$i],
						'post_parent' => (int) $this->options['parent']
					);
					$logger and call_user_func($logger, sprintf(__('Combine all data for post `%s`...', 'pmxi_plugin'), $articleData['post_title']));		
				}						
				
				// Re-import Records Matching
				$post_to_update = false; $post_to_update_id = false;
				
				// if Auto Matching re-import option selected
				if ( "manual" != $this->options['duplicate_matching'] ){
					
					// find corresponding article among previously imported				
					$logger and call_user_func($logger, sprintf(__('Find corresponding article among previously imported for post `%s`...', 'pmxi_plugin'), $articleData['post_title']));
					$postRecord->clear();
					$postRecord->getBy(array(
						'unique_key' => $unique_keys[$i],
						'import_id' => $this->id,
					));

					if ( ! $postRecord->isEmpty() ) {
						$logger and call_user_func($logger, sprintf(__('Duplicate post was founded for post %s with unique key `%s`...', 'pmxi_plugin'), $articleData['post_title'], $unique_keys[$i]));
						if ( $this->options['custom_type'] == 'import_users'){
							$post_to_update = get_user_by('id', $post_to_update_id = $postRecord->post_id);							
						}
						else{
							$post_to_update = get_post($post_to_update_id = $postRecord->post_id);
						}
					}
					else{
						$logger and call_user_func($logger, sprintf(__('Duplicate post wasn\'t founded with unique key `%s`...', 'pmxi_plugin'), $unique_keys[$i]));
					}
																
				// if Manual Matching re-import option seleted
				} else {
										
					if ('custom field' == $this->options['duplicate_indicator']) {
						$custom_duplicate_value = XmlImportParser::factory($xml, $cxpath, $this->options['custom_duplicate_value'], $file)->parse($records); $tmp_files[] = $file;
						$custom_duplicate_name = XmlImportParser::factory($xml, $cxpath, $this->options['custom_duplicate_name'], $file)->parse($records); $tmp_files[] = $file;
					}
					else{
						count($titles) and $custom_duplicate_name = $custom_duplicate_value = array_fill(0, count($titles), '');
					}
					
					$logger and call_user_func($logger, sprintf(__('Find corresponding article among database for post `%s`...', 'pmxi_plugin'), $articleData['post_title']));
					// handle duplicates according to import settings
					if ($duplicates = pmxi_findDuplicates($articleData, $custom_duplicate_name[$i], $custom_duplicate_value[$i], $this->options['duplicate_indicator'])) {															
						$duplicate_id = array_shift($duplicates);						
						if ($duplicate_id) {	
							$logger and call_user_func($logger, sprintf(__('Duplicate post was founded for post `%s`...', 'pmxi_plugin'), $articleData['post_title']));
							if ( $this->options['custom_type'] == 'import_users'){													
								$post_to_update = get_user_by('id', $post_to_update_id = $duplicate_id);
							}
							else{
								$post_to_update = get_post($post_to_update_id = $duplicate_id);
							}
						}	
						else{
							$logger and call_user_func($logger, sprintf(__('Duplicate post wasn\'n founded for post `%s`...', 'pmxi_plugin'), $articleData['post_title']));
						}					
					}					
				}

				if ( ! empty($specified_records) ) {

					if ( ! in_array($created + $updated + $skipped + 1, $specified_records) ){

						if ( ! $postRecord->isEmpty() ) $postRecord->set(array('iteration' => $this->iteration))->update();

						$skipped++;											
						$logger and call_user_func($logger, __('<b>SKIPPED</b>: by specified records option', 'pmxi_plugin'));
						$logger and !$is_cron and PMXI_Plugin::$session->warnings++;					
						$logger and !$is_cron and PMXI_Plugin::$session->chunk_number++;
						PMXI_Plugin::$session->save_data();						
						continue;
					}										
				}				

				// Duplicate record is founded
				if ($post_to_update){

					//$logger and call_user_func($logger, sprintf(__('Duplicate record is founded for `%s`', 'pmxi_plugin'), $articleData['post_title']));

					// Do not update already existing records option selected
					if ("yes" == $this->options['is_keep_former_posts']) {	

						if ( ! $postRecord->isEmpty() ) $postRecord->set(array('iteration' => $this->iteration))->update();	

						do_action('pmxi_do_not_update_existing', $post_to_update_id, $this->id, $this->iteration);																																											

						$skipped++;
						$logger and call_user_func($logger, sprintf(__('<b>SKIPPED</b>: Previously imported record found for `%s`', 'pmxi_plugin'), $articleData['post_title']));
						$logger and !$is_cron and PMXI_Plugin::$session->warnings++;							
						$logger and !$is_cron and PMXI_Plugin::$session->chunk_number++;	
						PMXI_Plugin::$session->save_data();	
						continue;
					}					

					$articleData['ID'] = $post_to_update_id;					
					// Choose which data to update
					if ( $this->options['update_all_data'] == 'no' ){

						if ( ! in_array($this->options['custom_type'], array('import_users'))){

							// preserve date of already existing article when duplicate is found					
							if ( ! $this->options['is_update_categories'] or ($this->options['is_update_categories'] and $this->options['update_categories_logic'] != "full_update")) { 							
								$logger and call_user_func($logger, sprintf(__('Preserve taxonomies of already existing article for `%s`', 'pmxi_plugin'), $articleData['post_title']));	
								$existing_taxonomies = array();
								foreach (array_keys($taxonomies) as $tx_name) {
									$txes_list = get_the_terms($articleData['ID'], $tx_name);
									if (is_wp_error($txes_list)) {
										$logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: Unable to get current taxonomies for article #%d, updating with those read from XML file', 'pmxi_plugin'), $articleData['ID']));
										$logger and !$is_cron and PMXI_Plugin::$session->warnings++;		
									} else {
										$txes_new = array();
										if (!empty($txes_list)):
											foreach ($txes_list as $t) {
												$txes_new[] = $t->term_taxonomy_id;
											}
										endif;
										$existing_taxonomies[$tx_name][$i] = $txes_new;								
									}
								}							
							}	
										
							if ( ! $this->options['is_update_dates']) { // preserve date of already existing article when duplicate is found
								$articleData['post_date'] = $post_to_update->post_date;
								$articleData['post_date_gmt'] = $post_to_update->post_date_gmt;
								$logger and call_user_func($logger, sprintf(__('Preserve date of already existing article for `%s`', 'pmxi_plugin'), $articleData['post_title']));								
							}
							if ( ! $this->options['is_update_status']) { // preserve status and trashed flag
								$articleData['post_status'] = $post_to_update->post_status;
								$logger and call_user_func($logger, sprintf(__('Preserve status of already existing article for `%s`', 'pmxi_plugin'), $articleData['post_title']));								
							}
							if ( ! $this->options['is_update_content']){ 
								$articleData['post_content'] = $post_to_update->post_content;
								$logger and call_user_func($logger, sprintf(__('Preserve content of already existing article for `%s`', 'pmxi_plugin'), $articleData['post_title']));								
							}
							if ( ! $this->options['is_update_title']){ 
								$articleData['post_title'] = $post_to_update->post_title;		
								$logger and call_user_func($logger, sprintf(__('Preserve title of already existing article for `%s`', 'pmxi_plugin'), $articleData['post_title']));																		
							}
							if ( ! $this->options['is_update_slug']){ 
								$articleData['post_name'] = $post_to_update->post_name;			
								$logger and call_user_func($logger, sprintf(__('Preserve slug of already existing article for `%s`', 'pmxi_plugin'), $articleData['post_title']));																	
							}
							if ( ! $this->options['is_update_excerpt']){ 
								$articleData['post_excerpt'] = $post_to_update->post_excerpt;
								$logger and call_user_func($logger, sprintf(__('Preserve excerpt of already existing article for `%s`', 'pmxi_plugin'), $articleData['post_title']));																				
							}										
							if ( ! $this->options['is_update_menu_order']){ 
								$articleData['menu_order'] = $post_to_update->menu_order;
								$logger and call_user_func($logger, sprintf(__('Preserve menu order of already existing article for `%s`', 'pmxi_plugin'), $articleData['post_title']));								
							}
							if ( ! $this->options['is_update_parent']){ 
								$articleData['post_parent'] = $post_to_update->post_parent;
								$logger and call_user_func($logger, sprintf(__('Preserve post parent of already existing article for `%s`', 'pmxi_plugin'), $articleData['post_title']));								
							}
							if ( ! $this->options['is_update_author']){ 
								$articleData['post_author'] = $post_to_update->post_author;
								$logger and call_user_func($logger, sprintf(__('Preserve post author of already existing article for `%s`', 'pmxi_plugin'), $articleData['post_title']));
							}
						}
						else {
							if ( ! $this->options['is_update_first_name'] ) $articleData['first_name'] = $post_to_update->first_name;
							if ( ! $this->options['is_update_last_name'] ) $articleData['last_name'] = $post_to_update->last_name;
							if ( ! $this->options['is_update_role'] ) unset($articleData['role']);
							if ( ! $this->options['is_update_nickname'] ) $articleData['nickname'] = get_user_meta($post_to_update->ID, 'nickname', true);
							if ( ! $this->options['is_update_description'] ) $articleData['description'] = get_user_meta($post_to_update->ID, 'description', true);
							if ( ! $this->options['is_update_login'] ) $articleData['user_login'] = $post_to_update->user_login; 
							if ( ! $this->options['is_update_password'] ) unset($articleData['user_pass']);
							if ( ! $this->options['is_update_nicename'] ) $articleData['user_nicename'] = $post_to_update->user_nicename;
							if ( ! $this->options['is_update_email'] ) $articleData['user_email'] = $post_to_update->user_email;
							if ( ! $this->options['is_update_registered'] ) $articleData['user_registered'] = $post_to_update->user_registered;
							if ( ! $this->options['is_update_display_name'] ) $articleData['display_name'] = $post_to_update->display_name;
							if ( ! $this->options['is_update_url'] ) $articleData['user_url'] = $post_to_update->user_url;
						}

						$logger and call_user_func($logger, sprintf(__('Applying filter `pmxi_article_data` for `%s`', 'pmxi_plugin'), $articleData['post_title']));	
						$articleData = apply_filters('pmxi_article_data', $articleData, $this, $post_to_update);
																
					}

					if ( ! in_array($this->options['custom_type'], array('import_users'))){

						if ( $this->options['update_all_data'] == 'yes' or ( $this->options['update_all_data'] == 'no' and $this->options['is_update_attachments'])) {
							$logger and call_user_func($logger, sprintf(__('Deleting attachments for `%s`', 'pmxi_plugin'), $articleData['post_title']));								
							wp_delete_attachments($articleData['ID'], true, 'files');
						}
						// handle obsolete attachments (i.e. delete or keep) according to import settings
						if ( $this->options['update_all_data'] == 'yes' or ( $this->options['update_all_data'] == 'no' and $this->options['is_update_images'] and $this->options['update_images_logic'] == "full_update")){
							$logger and call_user_func($logger, sprintf(__('Deleting images for `%s`', 'pmxi_plugin'), $articleData['post_title']));								
							wp_delete_attachments($articleData['ID'], ($this->options['download_images'] == 'yes'), 'images');
						}

					}
				}
				elseif ( ! $postRecord->isEmpty() ){
					
					// existing post not found though it's track was found... clear the leftover, plugin will continue to treat record as new
					$postRecord->clear();
					
				}					
				
				// no new records are created. it will only update posts it finds matching duplicates for
				if ( ! $this->options['create_new_records'] and empty($articleData['ID'])){ 
					
					if ( ! $postRecord->isEmpty() ) $postRecord->set(array('iteration' => $this->iteration))->update();

					$logger and call_user_func($logger, __('<b>SKIPPED</b>: by do not create new posts option.', 'pmxi_plugin'));
					$logger and !$is_cron and PMXI_Plugin::$session->warnings++;								
					$logger and !$is_cron and PMXI_Plugin::$session->chunk_number++;
					$skipped++;		
					PMXI_Plugin::$session->save_data();	
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
										$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
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
				if ( ! in_array($this->options['custom_type'], array('import_users'))){						
					if (empty($articleData['ID'])){
						$logger and call_user_func($logger, sprintf(__('<b>CREATING</b> `%s` `%s`', 'pmxi_plugin'), $articleData['post_title'], $custom_type_details->labels->singular_name));
					}
					else{
						$logger and call_user_func($logger, sprintf(__('<b>UPDATING</b> `%s` `%s`', 'pmxi_plugin'), $articleData['post_title'], $custom_type_details->labels->singular_name));
					}

					$pid = ($this->options['is_fast_mode']) ? pmxi_insert_post($articleData, true) : wp_insert_post($articleData, true);
				}
				else{
					$pid = (empty($articleData['ID'])) ? wp_insert_user( $articleData ) : wp_update_user( $articleData );
					$articleData['post_title'] = $articleData['user_login'];
				}
				
				if (is_wp_error($pid)) {
					$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': ' . $pid->get_error_message());
					$logger and !$is_cron and PMXI_Plugin::$session->errors++;
				} else {										
															
					if ("manual" != $this->options['duplicate_matching'] or empty($articleData['ID'])){						
						// associate post with import												
						$postRecord->isEmpty() and $postRecord->set(array(
							'post_id' => $pid,
							'import_id' => $this->id,
							'unique_key' => $unique_keys[$i],
							'product_key' => (($post_type == "product" and PMXI_Admin_Addons::get_addon('PMWI_Plugin')) ? $addons_data['PMWI_Plugin']['single_product_ID'][$i] : '')
						))->insert();

						$postRecord->set(array('iteration' => $this->iteration))->update();						

						$logger and call_user_func($logger, sprintf(__('Associate post `%s` with current import ...', 'pmxi_plugin'), $articleData['post_title']));
					}

					// [post format]
					if ( current_theme_supports( 'post-formats' ) && post_type_supports( $post_type, 'post-formats' ) ){						
						set_post_format($pid, $this->options['post_format'] ); 						
						$logger and call_user_func($logger, sprintf(__('Associate post `%s` with post format %s ...', 'pmxi_plugin'), $articleData['post_title'], (!empty($this->options['post_format'])) ? $this->options['post_format'] : 'Standart'));
					}
					// [/post format]
										
					// [addons import]

					// prepare data for import
					$importData = array(
						'pid' => $pid,
						'i' => $i,
						'import' => $this,
						'articleData' => $articleData,
						'xml' => $xml,
						'is_cron' => $is_cron,
						'logger' => $logger,
						'xpath_prefix' => $xpath_prefix
					);

					// deligate operation to addons
					foreach (PMXI_Admin_Addons::get_active_addons() as $class){ 						
						if (class_exists($class)){
							if ( method_exists($addons[$class], 'import') ) $addons[$class]->import($importData);	
						}
						else{
							$import_func = $class . '_import';							
							if (function_exists($import_func)) call_user_func($import_func, $importData, $addons_data[$class]);
						}
					}
					
					// [/addons import]

					// Page Template
					if ('post' != $articleData['post_type'] and !empty($this->options['page_template'])) update_post_meta($pid, '_wp_page_template', $this->options['page_template']);
					
					// [featured image]
					if ( ! empty($uploads) and false === $uploads['error'] and $articleData['post_type'] == "product" and class_exists('PMWI_Plugin') and (empty($articleData['ID']) or $this->options['update_all_data'] == "yes" or ( $this->options['update_all_data'] == "no" and $this->options['is_update_images']))) {
						
						if (!empty($featured_images[$i])){

							$targetDir = $uploads['path'];
							$targetUrl = $uploads['url'];

							$logger and call_user_func($logger, __('<b>IMAGES:</b>', 'pmxi_plugin'));

							if ( ! @is_writable($targetDir) ){

								$logger and call_user_func($logger, sprintf(__('<b>ERROR</b>: Target directory %s is not writable', 'pmxi_plugin'), $targetDir));

							}
							else{

								require_once(ABSPATH . 'wp-admin/includes/image.php');						
								
								$success_images = false;	
								$gallery_attachment_ids = array();																			
								$imgs = array();

								$featured_delim = ( "yes" == $this->options['download_images'] ) ? $this->options['download_featured_delim'] : $this->options['featured_delim'];

								$line_imgs = explode("\n", $featured_images[$i]);
								if ( ! empty($line_imgs) )
									foreach ($line_imgs as $line_img)
										$imgs = array_merge($imgs, ( ! empty($featured_delim) ) ? str_getcsv($line_img, $featured_delim) : array($line_img) );								
														
								if (!empty($imgs)) {											

									if ( $this->options['set_image_meta_title'] ){		
										$img_titles = array();									
										$line_img_titles = explode("\n", $image_meta_titles[$i]);
										if ( ! empty($line_img_titles) )
											foreach ($line_img_titles as $line_img_title)
												$img_titles = array_merge($img_titles, ( ! empty($this->options['image_meta_title_delim']) ) ? str_getcsv($line_img_title, $this->options['image_meta_title_delim']) : array($line_img_title) );
			
									}
									if ( $this->options['set_image_meta_caption'] ){								
										$img_captions = array();									
										$line_img_captions = explode("\n", $image_meta_captions[$i]);
										if ( ! empty($line_img_captions) )
											foreach ($line_img_captions as $line_img_caption)
												$img_captions = array_merge($img_captions, ( ! empty($this->options['image_meta_caption_delim']) ) ? str_getcsv($line_img_caption, $this->options['image_meta_caption_delim']) : array($line_img_caption) );

									}
									if ( $this->options['set_image_meta_alt'] ){								
										$img_alts = array();									
										$line_img_alts = explode("\n", $image_meta_alts[$i]);
										if ( ! empty($line_img_alts) )
											foreach ($line_img_alts as $line_img_alt)
												$img_alts = array_merge($img_alts, ( ! empty($this->options['image_meta_alt_delim']) ) ? str_getcsv($line_img_alt, $this->options['image_meta_alt_delim']) : array($line_img_alt) );

									}
									if ( $this->options['set_image_meta_description'] ){								
										$img_descriptions = array();									
										$line_img_descriptions = explode("\n", $image_meta_alts[$i]);
										if ( ! empty($line_img_descriptions) )
											foreach ($line_img_descriptions as $line_img_description)
												$img_descriptions = array_merge($img_descriptions, ( ! empty($this->options['image_meta_description_delim']) ) ? str_getcsv($line_img_description, $this->options['image_meta_description_delim']) : array($line_img_description) );

									}										

									foreach ($imgs as $k => $img_url) { if (empty($img_url)) continue;																											

										$url = str_replace(" ", "%20", trim($img_url));
										$bn = preg_replace('/[\\?|&].*/', '', basename($url));
										
										if ( "yes" == $this->options['download_images'] and ! empty($auto_extensions[$i]) and preg_match('%^(jpg|jpeg|png|gif)$%i', $auto_extensions[$i])){
											$img_ext = $auto_extensions[$i];
										}
										else {
											$img_ext = pmxi_getExtensionFromStr($url);									
											$default_extension = pmxi_getExtension($bn);																									

											if ($img_ext == "") $img_ext = pmxi_get_remote_image_ext($url);		
										}

										$logger and call_user_func($logger, sprintf(__('- Importing image `%s` for `%s` ...', 'pmxi_plugin'), $img_url, $articleData['post_title']));

										// generate local file name
										$image_name = urldecode(($this->options['auto_rename_images'] and "" != $auto_rename_images[$i]) ? sanitize_file_name(($img_ext) ? str_replace("." . $default_extension, "", $auto_rename_images[$i]) : $auto_rename_images[$i]) : sanitize_file_name((($img_ext) ? str_replace("." . $default_extension, "", $bn) : $bn))) . (("" != $img_ext) ? '.' . $img_ext : '');																																				
											
										// if wizard store image data to custom field									
										$create_image = false;
										$download_image = true;

										if (base64_decode($url, true) !== false){
											$img = @imagecreatefromstring(base64_decode($url));									    
										    if($img)
										    {	
										    	$logger and call_user_func($logger, __('- Founded base64_encoded image', 'pmxi_plugin'));

										    	$image_filename = md5(time()) . '.jpg';
										    	$image_filepath = $targetDir . '/' . $image_filename;
										    	imagejpeg($img, $image_filepath);
										    	if( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
													$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'pmxi_plugin'), $image_filepath));
													$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
												} else {
													$create_image = true;											
												}
										    } 
										} 
										else {										
											
											$image_filename = wp_unique_filename($targetDir, $image_name);
											$image_filepath = $targetDir . '/' . $image_filename;
											
											$logger and call_user_func($logger, sprintf(__('- Image `%s` will be saved with name `%s` ...', 'pmxi_plugin'), $img_url, $image_filename));

											// keep existing and add newest images
											if ( ! empty($articleData['ID']) and $this->options['is_update_images'] and $this->options['update_images_logic'] == "add_new" and $this->options['update_all_data'] == "no"){ 																																											
												
												$logger and call_user_func($logger, __('- Keep existing and add newest images ...', 'pmxi_plugin'));

												$attachment_imgs = get_posts( array(
													'post_type' => 'attachment',
													'posts_per_page' => -1,
													'post_parent' => $pid,												
												) );

												if ( $attachment_imgs ) {
													foreach ( $attachment_imgs as $attachment_img ) {													
														if ($attachment_img->guid == $targetUrl . '/' . $image_name){
															$download_image = false;
															$success_images = true;
															if ( ! has_post_thumbnail($pid) and $this->options['is_featured'] ) 
																set_post_thumbnail($pid, $attachment_img->ID);
															elseif ( ! in_array($attachment_img->ID, $gallery_attachment_ids))
																$gallery_attachment_ids[] = $attachment_img->ID;	

															$logger and call_user_func($logger, sprintf(__('- <b>Image SKIPPED</b>: The image %s is always exists for the `%s`', 'pmxi_plugin'), basename($attachment_img->guid), $articleData['post_title']));							
														}
													}												
												}

											}

											if ($download_image){											

												// do not download images
												if ( "yes" != $this->options['download_images'] ){

													$logger and call_user_func($logger, sprintf(__('- Trying to find existing image for `%s` in attachments ...', 'pmxi_plugin'), $image_filename));

													$image_filename = $image_name;
													$image_filepath = $targetDir . '/' . $image_filename;																							
																																			
														
													$wpai_uploads = $uploads['basedir'] . '/wpallimport/files/';
													$wpai_image_path = $wpai_uploads . $image_name;

													$logger and call_user_func($logger, sprintf(__('- Searching for existing image `%s` in `%s` folder', 'pmxi_plugin'), $wpai_image_path, $wpai_uploads));

													if ( @file_exists($wpai_image_path) and @copy( $wpai_image_path, $image_filepath )){
														$download_image = false;																				
														if( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
															$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'pmxi_plugin'), $image_filepath));
															$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
															@unlink($image_filepath);
														} else {
															$create_image = true;											
															$logger and call_user_func($logger, sprintf(__('- Image `%s` has been successfully founded', 'pmxi_plugin'), $wpai_image_path));
														}
													}													
												}	

												if ($download_image){
													
													$logger and call_user_func($logger, sprintf(__('- Downloading image from `%s`', 'pmxi_plugin'), $url));

													$request = get_file_curl($url, $image_filepath);

													if ( (is_wp_error($request) or $request === false) and ! @file_put_contents($image_filepath, @file_get_contents($url))) {
														@unlink($image_filepath); // delete file since failed upload may result in empty file created
													} elseif( ($image_info = @getimagesize($image_filepath)) and in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
														$create_image = true;		
														$logger and call_user_func($logger, sprintf(__('- Image `%s` has been successfully downloaded', 'pmxi_plugin'), $url));									
													}												
													
													if ( ! $create_image ){

														$url = str_replace(" ", "%20", trim(pmxi_convert_encoding($img_url)));
														
														$request = get_file_curl($url, $image_filepath);

														if ( (is_wp_error($request) or $request === false) and ! @file_put_contents($image_filepath, @file_get_contents($url))) {
															$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: File %s cannot be saved locally as %s', 'pmxi_plugin'), $url, $image_filepath));
															$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
															@unlink($image_filepath); // delete file since failed upload may result in empty file created										
														} elseif( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
															$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'pmxi_plugin'), $url));
															$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
															@unlink($image_filepath);
														} else {
															$create_image = true;	
															$logger and call_user_func($logger, sprintf(__('- Image `%s` has been successfully downloaded', 'pmxi_plugin'), $url));												
														}
													}
												}
											}
										}

										if ($create_image){
											
											$logger and call_user_func($logger, sprintf(__('- Creating an attachment for image `%s`', 'pmxi_plugin'), $targetUrl . '/' . $image_filename));	

											$attachment = array(
												'post_mime_type' => image_type_to_mime_type($image_info[2]),
												'guid' => $targetUrl . '/' . $image_filename,
												'post_title' => $image_filename,
												'post_content' => '',
												'post_author' => $post_author[$i],
											);
											if (($image_meta = wp_read_image_metadata($image_filepath))) {
												if (trim($image_meta['title']) && ! is_numeric(sanitize_title($image_meta['title'])))
													$attachment['post_title'] = $image_meta['title'];
												if (trim($image_meta['caption']))
													$attachment['post_content'] = $image_meta['caption'];
											}

											$attid = ($this->options['is_fast_mode']) ? pmxi_insert_attachment($attachment, $image_filepath, $pid) : wp_insert_attachment($attachment, $image_filepath, $pid);										

											if (is_wp_error($attid)) {
												$logger and call_user_func($logger, __('- <b>WARNING</b>', 'pmxi_plugin') . ': ' . $attid->get_error_message());
												$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
											} else {
												// you must first include the image.php file
												// for the function wp_generate_attachment_metadata() to work
												require_once(ABSPATH . 'wp-admin/includes/image.php');
												wp_update_attachment_metadata($attid, wp_generate_attachment_metadata($attid, $image_filepath));																							
																							
												$update_attachment_meta = array();
												if ( $this->options['set_image_meta_title'] and ! empty($img_titles[$k]) ) $update_attachment_meta['post_title'] = $img_titles[$k];
												if ( $this->options['set_image_meta_caption'] and ! empty($img_captions[$k]) ) $update_attachment_meta['post_excerpt'] =  $img_captions[$k];								
												if ( $this->options['set_image_meta_description'] and ! empty($img_descriptions[$k]) ) $update_attachment_meta['post_content'] =  $img_descriptions[$k];
												if ( $this->options['set_image_meta_alt'] and ! empty($img_alts[$k]) ) update_post_meta($attid, '_wp_attachment_image_alt', $img_alts[$k]);
												
												if ( ! empty($update_attachment_meta)) $this->wpdb->update( $this->wpdb->posts, $update_attachment_meta, array('ID' => $attid) );																
												
												$logger and call_user_func($logger, __('- <b>ACTION</b>: pmxi_gallery_image', 'pmxi_plugin'));																							
												do_action( 'pmxi_gallery_image', $pid, $attid, $image_filepath); 

												$success_images = true;
												if ( ! has_post_thumbnail($pid) and $this->options['is_featured'] ) 
													set_post_thumbnail($pid, $attid); 											
												elseif ( ! in_array($attid, $gallery_attachment_ids)) 
													$gallery_attachment_ids[] = $attid;		

												$logger and call_user_func($logger, sprintf(__('- Attachment has been successfully created for image `%s`', 'pmxi_plugin'), $targetUrl . '/' . $image_filename));											
											}
										}																		
									}									
								}							
								// Set product gallery images
								if ( $post_type == "product" and !empty($gallery_attachment_ids) )
									update_post_meta($pid, '_product_image_gallery', implode(',', $gallery_attachment_ids));
								// Create entry as Draft if no images are downloaded successfully
								if ( ! $success_images and "yes" == $this->options['create_draft'] ) {								
									$this->wpdb->update( $this->wpdb->posts, array('post_status' => 'draft'), array('ID' => $pid) );
									$logger and call_user_func($logger, sprintf(__('- Post `%s` saved as Draft, because no images are downloaded successfully', 'pmxi_plugin'), $articleData['post_title']));
								}
							}
						}
						else{							
							// Create entry as Draft if no images are downloaded successfully
							if ( "yes" == $this->options['create_draft'] ){ 
								$this->wpdb->update( $this->wpdb->posts, array('post_status' => 'draft'), array('ID' => $pid) );
								$logger and call_user_func($logger, sprintf(__('Post `%s` saved as Draft, because no images are downloaded successfully', 'pmxi_plugin'), $articleData['post_title']));
							}
						}
					}
					// [/featured image]

					// [attachments]
					if ( ! empty($uploads) and false === $uploads['error'] and !empty($attachments[$i]) and (empty($articleData['ID']) or $this->options['update_all_data'] == "yes" or ($this->options['update_all_data'] == "no" and $this->options['is_update_attachments']))) {

						$targetDir = $uploads['path'];
						$targetUrl = $uploads['url'];

						$logger and call_user_func($logger, __('<b>ATTACHMENTS:</b>', 'pmxi_plugin'));

						if ( ! @is_writable($targetDir) ){
							$logger and call_user_func($logger, sprintf(__('- <b>ERROR</b>: Target directory %s is not writable', 'pmxi_plugin'), trim($targetDir)));
						}
						else{
							// you must first include the image.php file
							// for the function wp_generate_attachment_metadata() to work
							require_once(ABSPATH . 'wp-admin/includes/image.php');

							if ( ! is_array($attachments[$i]) ) $attachments[$i] = array($attachments[$i]);

							$logger and call_user_func($logger, sprintf(__('- Importing attachments for `%s` ...', 'pmxi_plugin'), $articleData['post_title']));

							foreach ($attachments[$i] as $attachment) { if ("" == $attachment) continue;
								
								$atchs = str_getcsv($attachment, $this->options['atch_delim']);

								if ( ! empty($atchs) ) {

									foreach ($atchs as $atch_url) {	if (empty($atch_url)) continue;		

										$atch_url = str_replace(" ", "%20", trim($atch_url));							

										$attachment_filename = wp_unique_filename($targetDir, urldecode(basename(parse_url(trim($atch_url), PHP_URL_PATH))));										
										$attachment_filepath = $targetDir . '/' . sanitize_file_name($attachment_filename);

										$logger and call_user_func($logger, sprintf(__('- Filename for attachment was generated as %s', 'pmxi_plugin'), $attachment_filename));
										
										$request = get_file_curl(trim($atch_url), $attachment_filepath);
																
										if ( (is_wp_error($request) or $request === false)  and ! @file_put_contents($attachment_filepath, @file_get_contents(trim($atch_url)))) {												
											$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: Attachment file %s cannot be saved locally as %s', 'pmxi_plugin'), trim($atch_url), $attachment_filepath));
											is_wp_error($request) and $logger and call_user_func($logger, sprintf(__('- <b>WP Error</b>: %s', 'pmxi_plugin'), $request->get_error_message()));
											$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
											unlink($attachment_filepath); // delete file since failed upload may result in empty file created												
										} elseif( ! $wp_filetype = wp_check_filetype(basename($attachment_filename), null )) {
											$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: Can\'t detect attachment file type %s', 'pmxi_plugin'), trim($atch_url)));
											$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
										} else {
											$logger and call_user_func($logger, sprintf(__('- File %s has been successfully downloaded', 'pmxi_plugin'), $atch_url));
											$attachment_data = array(
											    'guid' => $targetUrl . '/' . basename($attachment_filepath), 
											    'post_mime_type' => $wp_filetype['type'],
											    'post_title' => preg_replace('/\.[^.]+$/', '', basename($attachment_filepath)),
											    'post_content' => '',
											    'post_status' => 'inherit',
											    'post_author' => $post_author[$i],
											);
											$attach_id = ($this->options['is_fast_mode']) ? pmxi_insert_attachment( $attachment_data, $attachment_filepath, $pid ) : wp_insert_attachment( $attachment_data, $attachment_filepath, $pid );												

											if (is_wp_error($attach_id)) {
												$logger and call_user_func($logger, __('- <b>WARNING</b>', 'pmxi_plugin') . ': ' . $pid->get_error_message());
												$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
											} else {											
												wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $attachment_filepath));											
												$logger and call_user_func($logger, sprintf(__('- Attachment has been successfully created for post `%s`', 'pmxi_plugin'), $articleData['post_title']));
												$logger and call_user_func($logger, __('- <b>ACTION</b>: pmxi_attachment_uploaded', 'pmxi_plugin'));
												do_action( 'pmxi_attachment_uploaded', $pid, $attach_id, $attachment_filepath);
											}										
										}																
									}
								}
							}
						}
					}
					// [/attachments]
					
					// [custom taxonomies]
					if ( ! empty($taxonomies) ){

						$logger and call_user_func($logger, __('<b>TAXONOMIES:</b>', 'pmxi_plugin'));	

						$custom_type = get_post_type_object( $this->options['custom_type'] );	

						foreach ($taxonomies as $tx_name => $txes) {								

							// Skip updating product attributes
							if ( PMXI_Admin_Addons::get_addon('PMWI_Plugin') and strpos($tx_name, "pa_") === 0 ) continue;

							if ( empty($articleData['ID']) or $this->options['update_all_data'] == "yes" or ( $this->options['update_all_data'] == "no" and $this->options['is_update_categories'] )) {
								
								$logger and call_user_func($logger, sprintf(__('- Importing taxonomy `%s` ...', 'pmxi_plugin'), $tx_name));	

								if ( ! empty($this->options['tax_logic'][$tx_name]) and $this->options['tax_logic'][$tx_name] == 'hierarchical' and ! empty($this->options['tax_hierarchical_logic'][$tx_name]) and $this->options['tax_hierarchical_logic'][$tx_name] == 'entire'){
									$logger and call_user_func($logger, sprintf(__('- Auto-nest enabled with separator `%s` ...', 'pmxi_plugin'), ( ! empty($this->options['tax_hierarchical_delim'][$tx_name]) ? $this->options['tax_hierarchical_delim'][$tx_name] : ',')));
								}

								if (!empty($articleData['ID'])){
									if ($this->options['update_all_data'] == "no" and $this->options['update_categories_logic'] == "all_except" and !empty($this->options['taxonomies_list']) 
										and is_array($this->options['taxonomies_list']) and in_array($tx_name, $this->options['taxonomies_list'])){ 
											$logger and call_user_func($logger, sprintf(__('- %s %s `%s` has been skipped attempted to `Leave these taxonomies alone, update all others`...', 'pmxi_plugin'), $custom_type->labels->singular_name, $tx_name, $single_tax['name']));
											continue;
										}		
									if ($this->options['update_all_data'] == "no" and $this->options['update_categories_logic'] == "only" and ((!empty($this->options['taxonomies_list']) 
										and is_array($this->options['taxonomies_list']) and ! in_array($tx_name, $this->options['taxonomies_list'])) or empty($this->options['taxonomies_list']))){ 
											$logger and call_user_func($logger, sprintf(__('- %s %s `%s` has been skipped attempted to `Update only these taxonomies, leave the rest alone`...', 'pmxi_plugin'), $custom_type->labels->singular_name, $tx_name, $single_tax['name']));
											continue;
										}
								}								

								$assign_taxes = array();

								if ($this->options['update_categories_logic'] == "add_new" and !empty($existing_taxonomies[$tx_name][$i])){
									$assign_taxes = $existing_taxonomies[$tx_name][$i];	
									unset($existing_taxonomies[$tx_name][$i]);
								}
								elseif(!empty($existing_taxonomies[$tx_name][$i])){
									unset($existing_taxonomies[$tx_name][$i]);
								}

								// create term if not exists								
								if ( ! empty($txes[$i]) ):
									foreach ($txes[$i] as $key => $single_tax) {
										$is_created_term = false;
										if (is_array($single_tax) and ! empty($single_tax['name'])){																														

											$parent_id = (!empty($single_tax['parent'])) ? pmxi_recursion_taxes($single_tax['parent'], $tx_name, $txes[$i], $key) : '';
											
											$term = term_exists($single_tax['name'], $tx_name, (int)$parent_id);		
											
											if ( empty($term) and !is_wp_error($term) ){
												$term = term_exists(htmlspecialchars($single_tax['name']), $tx_name, (int)$parent_id);		
												if ( empty($term) and !is_wp_error($term) ){
													$term_attr = array('parent'=> (!empty($parent_id)) ? $parent_id : 0);
													$term = wp_insert_term(
														$single_tax['name'], // the term 
													  	$tx_name, // the taxonomy
													  	$term_attr
													);
													if ( ! is_wp_error($term) ){
														$is_created_term = true;
														if ( empty($parent_id) ){
															$logger and call_user_func($logger, sprintf(__('- Creating parent %s %s `%s` ...', 'pmxi_plugin'), $custom_type->labels->singular_name, $tx_name, $single_tax['name']));	
														}
														else{
															$logger and call_user_func($logger, sprintf(__('- Creating child %s %s for %s named `%s` ...', 'pmxi_plugin'), $custom_type->labels->singular_name, $tx_name, (is_array($single_tax['parent']) ? $single_tax['parent']['name'] : $single_tax['parent']), $single_tax['name']));		
														}
													}
												}
											}											
											
											if ( is_wp_error($term) ){									
												$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: `%s`', 'pmxi_plugin'), $term->get_error_message()));
												$logger and !$is_cron and PMXI_Plugin::$session->warnings++;
											}
											elseif ( ! empty($term)) {												
												$cat_id = $term['term_id'];
												if ($cat_id and $single_tax['assign']) 
												{
													$term = get_term_by('id', $cat_id, $tx_name);
													if (!in_array($term->slug, $assign_taxes)) $assign_taxes[] = $term->term_taxonomy_id;		
													if (!$is_created_term){														
														if ( empty($parent_id) ){															
															$logger and call_user_func($logger, sprintf(__('- Attempted to create parent %s %s `%s`, duplicate detected. Importing %s to existing `%s` %s, ID %d, slug `%s` ...', 'pmxi_plugin'), $custom_type->labels->singular_name, $tx_name, $single_tax['name'], $custom_type->labels->singular_name, $term->name, $tx_name, $term->term_id, $term->slug));	
														}
														else{															
															$logger and call_user_func($logger, sprintf(__('- Attempted to create child %s %s `%s`, duplicate detected. Importing %s to existing `%s` %s, ID %d, slug `%s` ...', 'pmxi_plugin'), $custom_type->labels->singular_name, $tx_name, $single_tax['name'], $custom_type->labels->singular_name, $term->name, $tx_name, $term->term_id, $term->slug));	
														}	
													}
												}									
											}									
										}
									}				
								endif;										
								
								// associate taxes with post								
								$this->associate_terms($pid, ( empty($assign_taxes) ? false : $assign_taxes ), $tx_name, $logger);	
								
							}
						}
						if ( ! empty($existing_taxonomies) and $this->options['update_all_data'] == "no" and ($this->options['is_update_categories'] and $this->options['update_categories_logic'] != 'full_update') or !$this->options['is_update_categories']) {
							
							foreach ($existing_taxonomies as $tx_name => $txes) {
								// Skip updating product attributes
								if ( PMXI_Admin_Addons::get_addon('PMWI_Plugin') and strpos($tx_name, "pa_") === 0 ) continue;

								if (!empty($txes[$i]))									
									$this->associate_terms($pid, $txes[$i], $tx_name, $logger);									
							}
						}
					}					
					// [/custom taxonomies]										

					if (empty($articleData['ID'])) {																												
						$logger and call_user_func($logger, sprintf(__('<b>CREATED</b> `%s` `%s` (ID: %s)', 'pmxi_plugin'), $articleData['post_title'], $custom_type_details->labels->singular_name, $pid));
					} else {						
						$logger and call_user_func($logger, sprintf(__('<b>UPDATED</b> `%s` `%s` (ID: %s)', 'pmxi_plugin'), $articleData['post_title'], $custom_type_details->labels->singular_name, $pid));
					}

					// [addons import]

					// prepare data for import
					$importData = array(
						'pid' => $pid,						
						'import' => $this,						
						'logger' => $logger						
					);

					// deligate operation to addons
					foreach (PMXI_Admin_Addons::get_active_addons() as $class){ 
						if (class_exists($class)){
							if ( method_exists($addons[$class], 'saved_post') ) $addons[$class]->saved_post($importData);	
						}
						else{
							$saved_func = $class . '_saved_post';
							if (function_exists($saved_func)) call_user_func($saved_func, $importData);
						}
					}
					
					// [/addons import]										
					$logger and call_user_func($logger, __('<b>ACTION</b>: pmxi_saved_post', 'pmxi_plugin'));
					do_action( 'pmxi_saved_post', $pid); // hook that was triggered immediately after post saved
					
					if (empty($articleData['ID'])) $created++; else $updated++;						

					if ( ! $is_cron and "default" == $this->options['import_processing'] ){
						$processed_records = $created + $updated + $skipped + PMXI_Plugin::$session->errors;
						$logger and call_user_func($logger, sprintf(__('<span class="processing_info"><span class="created_count">%s</span><span class="updated_count">%s</span><span class="percents_count">%s</span></span>', 'pmxi_plugin'), $created, $updated, ceil(($processed_records/$this->count) * 100)));
					}
																					
				}				
				$logger and call_user_func($logger, __('<b>ACTION</b>: pmxi_after_post_import', 'pmxi_plugin'));
				do_action('pmxi_after_post_import', $this->id);

				$logger and !$is_cron and PMXI_Plugin::$session->chunk_number++; 
			}			

			wp_cache_flush();						

			$this->set(array(		
				'imported' => $created + $updated,	
				'created'  => $created,
				'updated'  => $updated,
				'skipped'  => $skipped,
				'last_activity' => date('Y-m-d H:i:s')				
			))->update();
			
			if ( ! $is_cron ){

				PMXI_Plugin::$session->save_data();	

				$records_count = $this->created + $this->updated + $this->skipped + PMXI_Plugin::$session->errors;

				$is_import_complete = ($records_count == $this->count);						

				// Delete posts that are no longer present in your file
				if ( $is_import_complete and ! empty($this->options['is_delete_missing']) and $this->options['duplicate_matching'] == 'auto') { 

					$logger and call_user_func($logger, __('Removing previously imported posts which are no longer actual...', 'pmxi_plugin'));
					$postList = new PMXI_Post_List();									

					$missing_ids = array();
					$missingPosts = $postList->getBy(array('import_id' => $this->id, 'iteration !=' => $this->iteration));

					if ( ! $missingPosts->isEmpty() ): 
						
						foreach ($missingPosts as $missingPost) {
						
							$missing_ids[] = $missingPost['post_id'];

							// Instead of deletion, set Custom Field
							if ($this->options['is_update_missing_cf']){
								update_post_meta( $missingPost['post_id'], $this->options['update_missing_cf_name'], $this->options['update_missing_cf_value'] );
								$logger and call_user_func($logger, sprintf(__('Instead of deletion post `%s`, set Custom Field `%s` to value `%s`', 'pmxi_plugin'), $articleData['post_title'], $this->options['update_missing_cf_name'], $this->options['update_missing_cf_value']));
							}

							// Instead of deletion, change post status to Draft
							if ($this->options['set_missing_to_draft']){ 
								$this->wpdb->update( $this->wpdb->posts, array('post_status' => 'draft'), array('ID' => $missingPost['post_id']) );								
								$logger and call_user_func($logger, sprintf(__('Instead of deletion, change post `%s` status to Draft', 'pmxi_plugin'), $articleData['post_title']));
							}

							// Delete posts that are no longer present in your file
							if ( ! $this->options['is_update_missing_cf'] and ! $this->options['set_missing_to_draft']){

								// Remove attachments
								$logger and call_user_func($logger, __('Deleting attachments...', 'pmxi_plugin'));
								empty($this->options['is_keep_attachments']) and wp_delete_attachments($missingPost['post_id'], true, 'files');						
								// Remove images
								$logger and call_user_func($logger, __('Deleting images...', 'pmxi_plugin'));
								empty($this->options['is_keep_imgs']) and wp_delete_attachments($missingPost['post_id'], ($this->options['download_images'] == 'yes'));							
								
								$logger and call_user_func($logger, sprintf(__('Deleting post `%s` from pmxi_posts table', 'pmxi_plugin'), $missingPost['post_id']));			
								if ( ! empty($missingPost['id'])){									
									// Delete record form pmxi_posts												
									$missingRecord = new PMXI_Post_Record();
									$missingRecord->getById($missingPost['id'])->delete();						
								}
								else {									
									$sql = "DELETE FROM " . PMXI_Plugin::getInstance()->getTablePrefix() . "posts WHERE post_id = " . $missingPost['post_id'] . " AND import_id = " . $missingPost['import_id'];
									$this->wpdb->query( 
										$this->wpdb->prepare($sql, '')
									);	
								}

								// Clear post's relationships
								if ( $post_type != "import_users" ) wp_delete_object_term_relationships($missingPost['post_id'], get_object_taxonomies('' != $this->options['custom_type'] ? $this->options['custom_type'] : 'post'));

							}
															
						}

					endif;							

					// Delete posts from database
					if (!empty($missing_ids) && is_array($missing_ids) and ! $this->options['is_update_missing_cf'] and ! $this->options['set_missing_to_draft']){																	
						
						$logger and call_user_func($logger, __('<b>ACTION</b>: pmxi_delete_post', 'pmxi_plugin'));
						do_action('pmxi_delete_post', $missing_ids);		

						if ( $this->options['custom_type'] == "import_users" ){
							$sql = "delete a,b
							FROM ".$this->wpdb->users." a
							LEFT JOIN ".$this->wpdb->usermeta." b ON ( a.ID = b.user_id )										
							WHERE a.ID IN (".implode(',', $missing_ids).");";
						}
						else {
							$sql = "delete a,b,c
							FROM ".$this->wpdb->posts." a
							LEFT JOIN ".$this->wpdb->term_relationships." b ON ( a.ID = b.object_id )
							LEFT JOIN ".$this->wpdb->postmeta." c ON ( a.ID = c.post_id )				
							WHERE a.ID IN (".implode(',', $missing_ids).");";
						}						

						$logger and call_user_func($logger, __('Deleting posts from database', 'pmxi_plugin'));
						$this->wpdb->query( 
							$this->wpdb->prepare($sql, '')
						);						

						$this->set(array('deleted' => count($missing_ids)))->update();			
					}								

				}

				// Set out of stock status for missing records [Woocommerce add-on option]
				if ( $is_import_complete and empty($this->options['is_delete_missing']) and $post_type == "product" and class_exists('PMWI_Plugin') and !empty($this->options['missing_records_stock_status'])) {

					$logger and call_user_func($logger, __('Update stock status previously imported posts which are no longer actual...', 'pmxi_plugin'));
					$postList = new PMXI_Post_List();				
					$missingPosts = $postList->getBy(array('import_id' => $this->id, 'iteration !=' => $this->iteration));
					if ( ! $missingPosts->isEmpty() ){
						foreach ($missingPosts as $missingPost) {
							update_post_meta( $missingPost['post_id'], '_stock_status', 'outofstock' );
							update_post_meta( $missingPost['post_id'], '_stock', 0 );
						}
					}
				}	
			}		
			
		} catch (XmlImportException $e) {
			$logger and call_user_func($logger, __('<b>ERROR</b>', 'pmxi_plugin') . ': ' . $e->getMessage());
			$logger and !$is_cron and PMXI_Plugin::$session->errors++;	
		}				
		
		$logger and $is_import_complete and call_user_func($logger, __('Cleaning temporary data...', 'pmxi_plugin'));
		foreach ($tmp_files as $file) { // remove all temporary files created
			@unlink($file);
		}
		
		if (($is_cron or $is_import_complete) and $this->options['is_delete_source']) {
			$logger and call_user_func($logger, __('Deleting source XML file...', 'pmxi_plugin'));			

			// Delete chunks
			foreach (PMXI_Helper::safe_glob($uploads['basedir'] . '/wpallimport/temp/pmxi_chunk_*', PMXI_Helper::GLOB_RECURSE | PMXI_Helper::GLOB_PATH) as $filePath) {
				$logger and call_user_func($logger, __('Deleting chunks files...', 'pmxi_plugin'));
				@file_exists($filePath) and pmxi_remove_source($filePath, false);		
			}

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
				'processing' => 0, // unlock cron requests	
				'triggered' => 0,
				'queue_chunk_number' => 0,				
				'registered_on' => date('Y-m-d H:i:s'),
				'iteration' => ++$this->iteration
			))->update();

			$logger and call_user_func($logger, 'Done');			
		}
		
		remove_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // return any filtering rules back if they has been disabled for import procedure
		
		return $this;
	}	

	protected function pushmeta($pid, $meta_key, $meta_value){

		if (empty($meta_key)) return;		

		$this->post_meta_to_insert[] = array(
			'meta_key' => $meta_key,
			'meta_value' => $meta_value,
			'pid' => $pid
		);		

	}

	protected function executeSQL(){
		
		$import_entry = ( $this->options['custom_type'] == 'import_users') ? 'user' : 'post';

		// prepare bulk SQL query
		$meta_table = _get_meta_table( $import_entry );
		
		if ( $this->post_meta_to_insert ){			
			$values = array();
			$already_added = array();
			
			foreach (array_reverse($this->post_meta_to_insert) as $key => $value) {
				if ( ! empty($value['meta_key']) and ! in_array($value['pid'] . '-' . $value['meta_key'], $already_added) ){
					$already_added[] = $value['pid'] . '-' . $value['meta_key'];						
					$values[] = '(' . $value['pid'] . ',"' . $value['meta_key'] . '",\'' . maybe_serialize($value['meta_value']) .'\')';						
				}
			}
			
			$this->wpdb->query("INSERT INTO $meta_table (`" . $import_entry . "_id`, `meta_key`, `meta_value`) VALUES " . implode(',', $values));
			$this->post_meta_to_insert = array();
		}	
	}
	
	public function _filter_has_cap_unfiltered_html($caps)
	{
		$caps['unfiltered_html'] = true;
		return $caps;
	}		
	
	protected function associate_terms($pid, $assign_taxes, $tx_name, $logger){
		
		$terms = wp_get_object_terms( $pid, $tx_name );
		$term_ids = array();        

		if ( ! empty($terms) ){
			if ( ! is_wp_error( $terms ) ) {				
				foreach ($terms as $term_info) {
					$term_ids[] = $term_info->term_taxonomy_id;
					$this->wpdb->query(  $this->wpdb->prepare("UPDATE {$this->wpdb->term_taxonomy} SET count = count - 1 WHERE term_taxonomy_id = %d", $term_info->term_taxonomy_id) );
				}				
				$in_tt_ids = "'" . implode( "', '", $term_ids ) . "'";
				$this->wpdb->query( $this->wpdb->prepare( "DELETE FROM {$this->wpdb->term_relationships} WHERE object_id = %d AND term_taxonomy_id IN ($in_tt_ids)", $pid ) );
			}
		}

		if (empty($assign_taxes)) return;

		foreach ($assign_taxes as $tt) {
			$this->wpdb->insert( $this->wpdb->term_relationships, array( 'object_id' => $pid, 'term_taxonomy_id' => $tt ) );
			$this->wpdb->query( "UPDATE {$this->wpdb->term_taxonomy} SET count = count + 1 WHERE term_taxonomy_id = $tt" );
		}

		$values = array();
        $term_order = 0;
		foreach ( $assign_taxes as $tt )			                        	
    		$values[] = $this->wpdb->prepare( "(%d, %d, %d)", $pid, $tt, ++$term_order);
		                					

		if ( $values ){
			if ( false === $this->wpdb->query( "INSERT INTO {$this->wpdb->term_relationships} (object_id, term_taxonomy_id, term_order) VALUES " . join( ',', $values ) . " ON DUPLICATE KEY UPDATE term_order = VALUES(term_order)" ) ){
				$logger and call_user_func($logger, __('<b>ERROR</b> Could not insert term relationship into the database', 'pmxi_plugin') . ': '. $this->wpdb->last_error);
				$logger and PMXI_Plugin::$session['pmxi_import']['errors'] = ++PMXI_Plugin::$session->data['pmxi_import']['errors'];
			}
		}                        			

		wp_cache_delete( $pid, $tx_name . '_relationships' ); 
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
				// Remove attachments
				empty($this->options['is_keep_attachments']) and wp_delete_attachments($p->post_id, true, 'files');
				// Remove images
				empty($this->options['is_keep_imgs']) and wp_delete_attachments($p->post_id, ($this->options['download_images'] == 'yes'));			
				$ids[] = $p->post_id;								
			}

			if ( ! empty($ids) ){

				foreach ($ids as $id) {
					do_action('pmxi_delete_post', $id);
					if ( $this->options['custom_type'] != 'import_users' ) wp_delete_object_term_relationships($id, get_object_taxonomies('' != $this->options['custom_type'] ? $this->options['custom_type'] : 'post'));
				}

				if ( $this->options['custom_type'] == 'import_users' ){
					$sql = "delete a,b
					FROM ".$this->wpdb->users." a
					LEFT JOIN ".$this->wpdb->usermeta." b ON ( a.ID = b.user_id )					
					WHERE a.ID IN (".implode(',', $ids).");";
				}
				else {
					$sql = "delete a,b,c
					FROM ".$this->wpdb->posts." a
					LEFT JOIN ".$this->wpdb->term_relationships." b ON ( a.ID = b.object_id )
					LEFT JOIN ".$this->wpdb->postmeta." c ON ( a.ID = c.post_id )
					LEFT JOIN ".$this->wpdb->posts." d ON ( a.ID = d.post_parent )
					WHERE a.ID IN (".implode(',', $ids).");";
				}

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
			if ( @file_exists($f->path) ){ 
				pmxi_remove_source($f->path);				
			}
			$f->delete();
		}
		return $this;
	}
	/**
	 * Delete associated history logs
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function deleteHistories(){
		$historyList = new PMXI_History_List();
		foreach ($historyList->getBy('import_id', $this->id)->convertRecords() as $h) {
			$h->delete();
		}
		return $this;
	}
	/**
	 * Delete associated sub imports
	 * @return PMXI_Import_Record
	 * @chainable
	 */
	public function deleteChildren($keepPosts = TRUE){
		$importList = new PMXI_Import_List();
		foreach ($importList->getBy('parent_import_id', $this->id)->convertRecords() as $i) {
			$i->delete($keepPosts);
		}
		return $this;
	}	
	/**
	 * @see parent::delete()
	 * @param bool[optional] $keepPosts When set to false associated wordpress posts will be deleted as well
	 */
	public function delete($keepPosts = TRUE) {
		$this->deletePosts($keepPosts)->deleteFiles()->deleteHistories()->deleteChildren($keepPosts);
		
		return parent::delete();
	}
	
}
