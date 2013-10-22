<?php 
/**
 * Manage Imports
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class PMXI_Admin_Manage extends PMXI_Controller_Admin {
	
	public function init() {
		parent::init();
		
		if ('update' == PMXI_Plugin::getInstance()->getAdminCurrentScreen()->action) {
			$this->isInline = true;			
		}
	}
	
	/**
	 * Previous Imports list
	 */
	public function index() {
		
		$get = $this->input->get(array(
			's' => '',
			'order_by' => 'registered_on',
			'order' => 'DESC',
			'pagenum' => 1,
			'perPage' => 25,
		));
		$get['pagenum'] = absint($get['pagenum']);
		extract($get);
		$this->data += $get;
		
		$list = new PMXI_Import_List();
		$post = new PMXI_Post_Record();
		$by = NULL;
		if ('' != $s) {
			$like = '%' . preg_replace('%\s+%', '%', preg_replace('/[%?]/', '\\\\$0', $s)) . '%';
			$by[] = array(array('name LIKE' => $like, 'type LIKE' => $like, 'path LIKE' => $like), 'OR');
		}
		
		$this->data['list'] = $list->join($post->getTable(), $list->getTable() . '.id = ' . $post->getTable() . '.import_id', 'LEFT')
			->setColumns(
				$list->getTable() . '.*',
				'COUNT(' . $post->getTable() . '.post_id' . ') AS post_count'
			)
			->getBy($by, "$order_by $order", $pagenum, $perPage, $list->getTable() . '.id');
			
		$this->data['page_links'] = paginate_links(array(
			'base' => add_query_arg('pagenum', '%#%', $this->baseUrl),
			'format' => '',
			'prev_text' => __('&laquo;', 'pmxi_plugin'),
			'next_text' => __('&raquo;', 'pmxi_plugin'),
			'total' => ceil($list->total() / $perPage),
			'current' => $pagenum,
		));
		
		//unset(PMXI_Plugin::$session['pmxi_import']);

		pmxi_session_unset();

		$this->render();
	}
	
	/**
	 * Edit Template
	 */
	public function edit() {
		// deligate operation to other controller
		$controller = new PMXI_Admin_Import();
		$controller->set('isTemplateEdit', true);
		$controller->template();
	}
	
	/**
	 * Edit Options
	 */
	public function options() {
		// deligate operation to other controller
		$controller = new PMXI_Admin_Import();
		$controller->set('isTemplateEdit', true);
		$controller->options();
	}
	
	/**
	 * Reimport
	 */
	public function update() {
		$id = $this->input->get('id');
		$action_type = $this->input->get('type');
		$pointer = 0;

		$this->data['item'] = $item = new PMXI_Import_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}				
				
		pmxi_session_unset();

		if ($this->input->post('is_confirmed')) {

			check_admin_referer('update-import', '_wpnonce_update-import');		
		
			$uploads = wp_upload_dir();			

			if ($item->large_import == 'No' or ($item->large_import == 'Yes' and empty(PMXI_Plugin::$session->data['pmxi_import']['chunk_number']))) {			
				
				if ( in_array($item->type, array('upload')) ) { // if import type NOT URL

					if (preg_match('%\W(zip)$%i', trim(basename($item->path)))) {
						
						include_once(PMXI_Plugin::ROOT_DIR.'/libraries/pclzip.lib.php');

						$archive = new PclZip(trim($item->path));
					    if (($v_result_list = $archive->extract(PCLZIP_OPT_PATH, $uploads['path'], PCLZIP_OPT_REPLACE_NEWER)) == 0) {
					    	$this->errors->add('form-validation', 'Failed to open uploaded ZIP archive : '.$archive->errorInfo(true));			    	
					   	}
						else {
							
							$filePath = '';

							if (!empty($v_result_list)){
								foreach ($v_result_list as $unzipped_file) {
									if ($unzipped_file['status'] == 'ok') $filePath = $unzipped_file['filename'];
								}
							}
					    	if($uploads['error']){
								 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
							}

							if(empty($filePath)){						
								$zip = zip_open(trim($item->path));
								if (is_resource($zip)) {																		
									while ($zip_entry = zip_read($zip)) {
										$filePath = zip_entry_name($zip_entry);												
									    $fp = fopen($uploads['path']."/".$filePath, "w");
									    if (zip_entry_open($zip, $zip_entry, "r")) {
									      $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
									      fwrite($fp,"$buf");
									      zip_entry_close($zip_entry);
									      fclose($fp);
									    }
									    break;
									}
									zip_close($zip);							

								} else {
							        $this->errors->add('form-validation', __('Failed to open uploaded ZIP archive. Can\'t extract files.', 'pmxi_plugin'));
							    }						
							}															

							if (preg_match('%\W(csv)$%i', trim($filePath))){ // If CSV file found in archieve						

								if($uploads['error']){
									 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
								}																		
								if (empty($item->large_import) or $item->large_import == 'No') {
									$filePath = PMXI_Plugin::csv_to_xml($filePath);																	
								}
								else{										
									include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');
									$csv = new PMXI_CsvParser($filePath, true, '', ( ! empty($item->options['delimiter']) ) ? $item->options['delimiter'] : '', ( ! empty($item->options['encoding']) ) ? $item->options['encoding'] : ''); // create chunks
									$filePath = $csv->xml_path;								
								}
							}							
						}					

					} elseif ( preg_match('%\W(csv)$%i', trim($item->path))) { // If CSV file uploaded										
						if($uploads['error']){
							 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
						}									
		    			$filePath = $post['filepath'];					
						if (empty($item->large_import) or $item->large_import == 'No') {
							$filePath = PMXI_Plugin::csv_to_xml($item->path);					
						} else{										
							include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
							$csv = new PMXI_CsvParser($item->path, true, '', ( ! empty($item->options['delimiter']) ) ? $item->options['delimiter'] : '', ( ! empty($item->options['encoding']) ) ? $item->options['encoding'] : '');					
							$filePath = $csv->xml_path;						
						}					   					
					} elseif(preg_match('%\W(gz)$%i', trim($item->path))){ // If gz file uploaded
						$fileInfo = pmxi_gzfile_get_contents($item->path);
						$filePath = $fileInfo['localPath'];				
						// detect CSV or XML 
						if ( $fileInfo['type'] == 'csv') { // it is CSV file									
							if (empty($item->large_import) or $item->large_import == 'No') {																
								$filePath = PMXI_Plugin::csv_to_xml($filePath); // convert CSV to XML																						
							}
							else{																
								include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');					
								$csv = new PMXI_CsvParser($filePath, true, '', ( ! empty($item->options['delimiter']) ) ? $item->options['delimiter'] : '', ( ! empty($item->options['encoding']) ) ? $item->options['encoding'] : ''); // create chunks
								$filePath = $csv->xml_path;												
							}
						}
					} else { // If XML file uploaded					
						
						$filePath = $item->path;
						
					}

				}

				if (empty($xml)){
					
					if ($item->large_import == 'Yes'){
						
						@set_time_limit(0);			

						$chunks = 0;
						
						$chunk_path = '';

						$local_paths = !empty($local_paths) ? $local_paths : array($filePath);				

						$chunk_founded = false;

						foreach ($local_paths as $key => $path) {

							$file = new PMXI_Chunk($path, array('element' => $item->root_element, 'path' => $uploads['path']));					
						    						    
						    while ($xml = $file->read()) {					      						    					    					    	
						    	
						    	if (!empty($xml))
						      	{				
						      		if (!empty($action_type) and $action_type == 'continue'){ 
						      			if ( !$chunk_founded) {											
						      				$xml = $file->encoding . "\n" . $xml;
								      		PMXI_Import_Record::preprocessXml($xml);	      						      							      					      		
									      					      		
									      	$dom = new DOMDocument('1.0', ( ! empty($item->options['encoding']) ) ? $item->options['encoding'] : 'UTF-8');															
											$old = libxml_use_internal_errors(true);
											$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $xml)); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load							
											libxml_use_internal_errors($old);
											$xpath = new DOMXPath($dom);
											if (($elements = @$xpath->query($item->xpath)) and !empty($elements) and !empty($elements->length)) $chunk_founded = true;
											unset($dom, $xpath, $elements);
						      			}						      			
						      			$chunks++;
						      			if ($chunks == $item->imported){
											$pointer = $file->pointer;
											$chunks = $item->count;
											break;
										}
									}
									else{
							      		$xml = $file->encoding . "\n" . $xml;
							      		PMXI_Import_Record::preprocessXml($xml);	      						      							      					      		
								      					      		
								      	$dom = new DOMDocument('1.0', ( ! empty($item->options['encoding']) ) ? $item->options['encoding'] : 'UTF-8');															
										$old = libxml_use_internal_errors(true);
										$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $xml)); // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load							
										libxml_use_internal_errors($old);
										$xpath = new DOMXPath($dom);
										if (($elements = @$xpath->query($item->xpath)) and !empty($elements) and !empty($elements->length)) $chunks++;
										unset($dom, $xpath, $elements);
									}
							    }
							}	
							unset($file);		
							
							!$key and $filePath = $path;					
						}

						if (empty($chunks)) 
							$this->errors->add('form-validation', __('No matching elements found for Root element and XPath expression specified', 'pmxi_plugin'));						
						
					} else {

						ob_start();
						$filePath && @readgzfile($filePath);					
						$xml = ob_get_clean();										
				
						if (empty($xml)){
							$xml = @file_get_contents($filePath);										
							if (empty($xml)) get_file_curl($filePath, $uploads['path']  .'/'. basename($filePath));
							if (empty($xml)) $xml = @file_get_contents($uploads['path']  .'/'. basename($filePath));
						}
					}								   
				}					
			}
			
			if ($item->large_import == 'Yes' or PMXI_Import_Record::validateXml($xml, $this->errors)) { // xml is valid		
				
				if ( ! PMXI_Plugin::is_ajax() and empty(PMXI_Plugin::$session->data['pmxi_import']['chunk_number'])){
				
					$item->set(array(
							'processing' => 0,
							'queue_chunk_number' => 0,
							'current_post_ids' => ''
						))->save();

					if (empty($action_type)){
						$item->set(array(						
							'imported' => 0,
							'created' => 0,
							'updated' => 0,
							'skipped' => 0
						))->save();
					}

					// compose data to look like result of wizard steps				
					PMXI_Plugin::$session['pmxi_import'] = array(
						//'xml' => (isset($xml)) ? $xml : '',
						'filePath' => $filePath,
						'source' => array(
							'name' => $item->name,
							'type' => $item->type,						
							'path' => $item->path,
							'root_element' => $item->root_element,
						),
						'feed_type' => $item->feed_type,
						'update_previous' => $item->id,
						'xpath' => $item->xpath,
						'template' => $item->template,
						'options' => $item->options,
						'encoding' => (!empty($item->options['encoding'])) ? $item->options['encoding'] : 'UTF-8',
						'is_csv' => (!empty($item->options['delimiter'])) ? $item->options['delimiter'] : PMXI_Plugin::$is_csv,
						'csv_path' => PMXI_Plugin::$csv_path,
						'scheduled' => $item->scheduled,				
						'current_post_ids' => '',
						'large_file' => ($item->large_import == 'Yes') ? true : false,
						'chunk_number' => (!empty($action_type) and $action_type == 'continue') ? $item->imported : 1,
						'pointer' => $pointer,
						'log' => '',
						'created_records' => (!empty($action_type) and $action_type == 'continue') ? $item->created : 0,
						'updated_records' => (!empty($action_type) and $action_type == 'continue') ? $item->updated : 0,
						'skipped_records' => (!empty($action_type) and $action_type == 'continue') ? $item->skipped : 0,
						'warnings' => 0,
						'errors' => 0,
						'start_time' => 0,
						'count' => (isset($chunks)) ? $chunks : 0,
						'local_paths' => (!empty($local_paths)) ? $local_paths : array(), // ftp import local copies of remote files
						'action' => (!empty($action_type) and $action_type == 'continue') ? 'continue' : 'update',					
					);										
					
					pmxi_session_commit();
					
				}

				// deligate operation to other controller
				$controller = new PMXI_Admin_Import();
				$controller->data['update_previous'] = $item;
				$controller->process();
				return;
			}
		}				
		$this->render();
	}
	
	/**
	 * Delete an import
	 */
	public function delete() {
		$id = $this->input->get('id');
		$this->data['item'] = $item = new PMXI_Import_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}
		
		if ($this->input->post('is_confirmed')) {
			check_admin_referer('delete-import', '_wpnonce_delete-import');
			
			$item->delete( ! $this->input->post('is_delete_posts'));
			wp_redirect(add_query_arg('pmxi_nt', urlencode(__('Import deleted', 'pmxi_plugin')), $this->baseUrl)); die();
		}
		
		$this->render();
	}
	
	/**
	 * Bulk actions
	 */
	public function bulk() {
		check_admin_referer('bulk-imports', '_wpnonce_bulk-imports');
		if ($this->input->post('doaction2')) {
			$this->data['action'] = $action = $this->input->post('bulk-action2');
		} else {
			$this->data['action'] = $action = $this->input->post('bulk-action');
		}
		$this->data['ids'] = $ids = $this->input->post('items');
		$this->data['items'] = $items = new PMXI_Import_List();
		if (empty($action) or ! in_array($action, array('delete')) or empty($ids) or $items->getBy('id', $ids)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}
		
		if ($this->input->post('is_confirmed')) {
			$is_delete_posts = $this->input->post('is_delete_posts');
			foreach($items->convertRecords() as $item) {
				$item->delete( ! $is_delete_posts);
			}
			
			wp_redirect(add_query_arg('pmxi_nt', urlencode(sprintf(__('<strong>%d</strong> %s deleted', 'pmxi_plugin'), $items->count(), _n('import', 'imports', $items->count(), 'pmxi_plugin'))), $this->baseUrl)); die();
		}
		
		$this->render();
	}

	/*
	 * Download import log file
	 *
	 */
	public function log(){

		$id = $this->input->get('id');
		
		$wp_uploads = wp_upload_dir();

		PMXI_download::csv($wp_uploads['basedir'] . '/wpallimport_logs/' .$id.'.html');

	}
}