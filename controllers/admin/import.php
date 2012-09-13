<?php 
/**
 * Import configuration wizard
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */

class PMXI_Admin_Import extends PMXI_Controller_Admin {
	protected $isWizard = true; // indicates whether controller is in wizard mode (otherwize it called to be deligated an edit action)
	protected $isTemplateEdit = false; // indicates whether controlled is deligated by manage imports controller	

	protected function init() {
		parent::init();
		
		// enable sessions
		if ( ! session_id()) session_start();
		
		if ('PMXI_Admin_Manage' == PMXI_Plugin::getInstance()->getAdminCurrentScreen()->base) { // prereqisites are not checked when flow control is deligated
			$id = $this->input->get('id');
			$this->data['import'] = $import = new PMXI_Import_Record();
			if ( ! $id or $import->getById($id)->isEmpty()) { // specified import is not found
				wp_redirect(add_query_arg('page', 'pmxi-admin-manage', admin_url('admin.php'))); die();
			}
			$this->isWizard = false;
			
		} else {						
			$action = PMXI_Plugin::getInstance()->getAdminCurrentScreen()->action;
			$this->_step_ready($action);
			$this->isInline = 'process' == $action;
		}
		
		XmlImportConfig::getInstance()->setCacheDirectory(sys_get_temp_dir());
		
		// preserve id parameter as part of baseUrl
		$id = $this->input->get('id') and $this->baseUrl = add_query_arg('id', $id, $this->baseUrl);
	}

	public function set($var, $val)
	{
		$this->{$var} = $val;
	}
	public function get($var)
	{
		return $this->{$var};
	}

	/**
	 * Checks whether corresponding step of wizard is complete
	 * @param string $action
	 */
	protected function _step_ready($action) {
		// step #1: xml selction - has no prerequisites
		if ('index' == $action) return true;
		
		// step #2: element selection
		$this->data['dom'] = $dom = new DOMDocument();
		$this->data['update_previous'] = $update_previous = new PMXI_Import_Record();
		$old = libxml_use_internal_errors(true);
		if (empty($_SESSION['pmxi_import'])
			or ! $dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $_SESSION['pmxi_import']['xml'])) // FIX: libxml xpath doesn't handle default namespace properly, so remove it upon XML load
			or empty($_SESSION['pmxi_import']['source'])
			or ! empty($_SESSION['pmxi_import']['update_previous']) and $update_previous->getById($_SESSION['pmxi_import']['update_previous'])->isEmpty()
		) {
			wp_redirect_or_javascript($this->baseUrl); die();
		}
		libxml_use_internal_errors($old);
		if ('element' == $action) return true;
		if ('evaluate' == $action) return true;
		
		// step #3: template
		$xpath = new DOMXPath($dom);
		if (empty($_SESSION['pmxi_import']['xpath']) or ! ($this->data['elements'] = $elements = $xpath->query($_SESSION['pmxi_import']['xpath'])) or ! $elements->length) {
			wp_redirect_or_javascript(add_query_arg('action', 'element', $this->baseUrl)); die();
		}
		if ('template' == $action or 'preview' == $action or 'tag' == $action) return true;
		
		// step #4: options
		if (empty($_SESSION['pmxi_import']['template']) or empty($_SESSION['pmxi_import']['template']['title']) or empty($_SESSION['pmxi_import']['template']['title'])) {
			wp_redirect_or_javascript(add_query_arg('action', 'template', $this->baseUrl)); die();
		}
		if ('options' == $action) return true;
		
		if (empty($_SESSION['pmxi_import']['options'])) {
			wp_redirect(add_query_arg('action', 'options', $this->baseUrl)); die();
		}
	}
	
	/**
	 * Step #1: Choose File
	 */
	public function index() {
		
		$import = new PMXI_Import_Record();
		$this->data['id'] = $id = $this->input->get('id');
		if ($id and $import->getById($id)->isEmpty()) { // update requested but corresponding import is not found
			wp_redirect(remove_query_arg('id', $this->baseUrl)); die();
		}
		
		$this->data['post'] = $post = $this->input->post(array(
			'type' => 'upload',
			'url' => 'http://',
			'ftp' => array('url' => 'ftp://'),
			'file' => '',
			'reimport' => '',
			'is_update_previous' => $id ? 1 : 0,
			'update_previous' => $id,
		));
		
		$this->data['imports'] = $imports = new PMXI_Import_List();
		$imports->setColumns('id', 'name', 'registered_on', 'path')->getBy(NULL, 'name ASC, registered_on DESC');
		
		$this->data['history'] = $history = new PMXI_File_List();
		$history->setColumns('id', 'name', 'registered_on', 'path')->getBy(NULL, 'id DESC');
		
		if ($this->input->post('is_submitted_continue')) { 
			if ( ! empty($_SESSION['pmxi_import']['xml'])) {
				wp_redirect(add_query_arg('action', 'element', $this->baseUrl)); die();
			}
		} elseif ('upload' == $this->input->post('type')) { 			
			if (empty($_FILES['upload']) or empty($_FILES['upload']['name'])) {
				$this->errors->add('form-validation', __('XML/CSV file must be specified', 'pmxi_plugin'));
			} elseif (empty($_FILES['upload']['size'])) {
				$this->errors->add('form-validation', __('Uploaded file is empty', 'pmxi_plugin'));
			} elseif ( ! preg_match('%\W(xml|gzip|zip|csv|gz)$%i', trim($_FILES['upload']['name'])) and !PMXI_Plugin::detect_csv($_FILES['upload']['type'])) {				
				$this->errors->add('form-validation', __('Uploaded file must be XML, CSV or ZIP, GZIP', 'pmxi_plugin'));
			} elseif (preg_match('%\W(zip)$%i', trim($_FILES['upload']['name']))) {
				
				$zip = zip_open(trim($_FILES['upload']['tmp_name']));
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

					if (preg_match('%\W(csv)$%i', trim($filename))){

						$xml = PMXI_Plugin::csv_to_xml($uploads['path'] . '/' . trim($filename));								
						if( is_array($xml) && isset($xml['error'])){
							$this->errors->add('form-validation', __($xml['error'], 'pmxi_plugin'));
						}
						else {
							
							// delete file in temporary folder
							unlink( $uploads['path'] .'/'. trim($filename));
							$fullfilename = $uploads['path']."/".$filename;							
							// Let's make sure the file exists and is writable first.
														
						    if (!$handle = fopen($fullfilename, 'w')) {
						         $this->errors->add('form-validation', __('Cannot open file ' . $fullfilename, 'pmxi_plugin'));
						    }
						
						    // Write $somecontent to our opened file.
						    if (fwrite($handle, $xml) === FALSE) {
						    	$this->errors->add('form-validation', __('Cannot write to file ' . $fullfilename, 'pmxi_plugin'));
						    }
						
						    fclose($handle);
							
							
							$filePath = $fullfilename;
							$source = array(
								'name' => $filename,
								'type' => 'upload',
								'path' => '',
							); 
						}

					}
					else
					{
						$filePath = $uploads['path']."/".$filename;
						$source = array(
							'name' => $filename,
							'type' => 'upload',
							'path' => '',
						);
					}

				} else {
			        $this->errors->add('form-validation', __('Failed to open uploaded ZIP archive', 'pmxi_plugin'));
			    }

			} elseif ( preg_match('%\W(csv)$%i', trim($_FILES['upload']['name'])) or PMXI_Plugin::detect_csv($_FILES['upload']['type'])) {
				$uploads = wp_upload_dir();
				if($uploads['error']){
					 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
				}					
				// copy file in temporary folder						
				$fdata = file_get_contents($_FILES['upload']['tmp_name']);								
				file_put_contents($uploads['path']  . '/' . trim(basename($_FILES['upload']['name'])), $fdata);
				chmod($uploads['path']  . '/'. trim(basename($_FILES['upload']['name'])), "0777");
				// end file convertion
				$xml = PMXI_Plugin::csv_to_xml($uploads['path'] . '/' . trim(basename($_FILES['upload']['name'])));								
				if( is_array($xml) && isset($xml['error'])){
					$this->errors->add('form-validation', __($xml['error'], 'pmxi_plugin'));
				}
				else {
					// delete file in temporary folder
					unlink( $uploads['path'] .'/'. trim(basename($_FILES['upload']['name']) ));
					$filename = $_FILES['upload']['tmp_name'];
					
					// Let's make sure the file exists and is writable first.
					if (is_writable($filename)) {
					
					    if (!$handle = fopen($filename, 'w')) {
					         $this->errors->add('form-validation', __('Cannot open file ' . $filename, 'pmxi_plugin'));
					    }
					
					    // Write $somecontent to our opened file.
					    if (fwrite($handle, $xml) === FALSE) {
					    	$this->errors->add('form-validation', __('Cannot write to file ' . $filename, 'pmxi_plugin'));
					    }
					
					    fclose($handle);
					
					} else {
						$this->errors->add('form-validation', __('The file' . $filename . 'is not writable', 'pmxi_plugin'));
					}
					$filePath = $_FILES['upload']['tmp_name'];
					$source = array(
						'name' => $_FILES['upload']['name'],
						'type' => 'upload',
						'path' => '',
					); 
				}
			} else {
				$filePath = $_FILES['upload']['tmp_name'];
				$source = array(
					'name' => $_FILES['upload']['name'],
					'type' => 'upload',
					'path' => '',
				);
			}
		} elseif ('url' == $this->input->post('type')) { 
			if (empty($post['url'])) {
				$this->errors->add('form-validation', __('XML/CSV file must be specified', 'pmxi_plugin'));
			} elseif ( ! preg_match('%^https?://%i', $post['url'])) {
				$this->errors->add('form-validation', __('Specified URL has wrong format'), 'pmxi_plugin');
			} elseif (preg_match('%\W(zip)$%i', trim($post['url']))) {
				
				$uploads = wp_upload_dir();
				
				$newfile = $uploads['path']."/".md5(time()).'.zip';

				if (!copy($post['url'], $newfile)) {
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
					if (preg_match('%\W(csv)$%i', trim($filename))){

						$xml = PMXI_Plugin::csv_to_xml($uploads['path'] . '/' . trim($filename));								
						if( is_array($xml) && isset($xml['error'])){
							$this->errors->add('form-validation', __($xml['error'], 'pmxi_plugin'));
						}
						else {
							
							// delete file in temporary folder
							unlink( $uploads['path'] .'/'. trim($filename));
							$fullfilename = $uploads['path']."/".$filename;							
							// Let's make sure the file exists and is writable first.
														
						    if (!$handle = fopen($fullfilename, 'w')) {
						         $this->errors->add('form-validation', __('Cannot open file ' . $fullfilename, 'pmxi_plugin'));
						    }
						
						    // Write $somecontent to our opened file.
						    if (fwrite($handle, $xml) === FALSE) {
						    	$this->errors->add('form-validation', __('Cannot write to file ' . $fullfilename, 'pmxi_plugin'));
						    }
						
						    fclose($handle);
							
							
							$filePath = $fullfilename;
							$source = array(
								'name' => $filename,
								'type' => 'url',
								'path' => $post['url'],
							); 
						}

					}
					else
					{
						$filePath = $uploads['path']."/".$filename;
						$source = array(
							'name' => $filename,
							'type' => 'url',
							'path' => $post['url'],
						);
					}

				} else {
			        $this->errors->add('form-validation', __('Failed to open uploaded ZIP archive', 'pmxi_plugin'));
			    }
			} elseif ( preg_match('%\W(csv)$%i', trim($post['url'])) or $contents = get_headers($post['url'],1 ) and PMXI_Plugin::detect_csv($contents['Content-Type'])) {
				$uploads = wp_upload_dir();
				$fdata = file_get_contents($post['url']);				
				$tmpname = md5(time()).'.csv';				
				file_put_contents($uploads['path']  .'/'. $tmpname, $fdata);
				$xml = PMXI_Plugin::csv_to_xml($uploads['path']  .'/'. $tmpname);
				if( is_array($xml) && isset($xml['error'])){
					$this->errors->add('form-validation', __($xml['error'], 'pmxi_plugin'));
				}
				else {
					$filename = tempnam(XmlImportConfig::getInstance()->getCacheDirectory(), 'xim');
					
					// Let's make sure the file exists and is writable first.
					if (is_writable($filename)) {
					
					    if (!$handle = fopen($filename, 'w')) {
					         $this->errors->add('form-validation', __('Cannot open file ' . $filename, 'pmxi_plugin'));
					    }
					
					    // Write $somecontent to our opened file.
					    if (fwrite($handle, $xml) === FALSE) {
					    	$this->errors->add('form-validation', __('Cannot write to file ' . $filename, 'pmxi_plugin'));
					    }
					
					    fclose($handle);
					
					} else {
						$this->errors->add('form-validation', __('The file' . $filename . 'is not writable', 'pmxi_plugin'));
					}
					$filePath = $filename;
					$source = array(
						'name' => basename(parse_url($post['url'], PHP_URL_PATH)),
						'type' => 'url',
						'path' => $post['url'],
					);
				}
			}else {
				$filePath = $post['url'];
				$source = array(
					'name' => basename(parse_url($filePath, PHP_URL_PATH)),
					'type' => 'url',
					'path' => $filePath,
				);

			}
		} elseif ('ftp' == $this->input->post('type')) {
			if (empty($post['ftp']['url'])) {
				$this->errors->add('form-validation', __('XML/CSV file must be specified', 'pmxi_plugin'));
			} elseif ( ! preg_match('%^ftps?://%i', $post['ftp']['url'])) {
				$this->errors->add('form-validation', __('Specified FTP resource has wrong format'), 'pmxi_plugin');
			} elseif ( preg_match('%\W(csv)$%i', trim($post['ftp']['url']))) {
				// path to remote file
				$remote_file = $post['ftp']['url'];
				$local_file = tempnam(XmlImportConfig::getInstance()->getCacheDirectory(), 'xim');
				
				// open some file to write to
				$handle = fopen($local_file, 'w');
				
				// set up basic connection
				$ftp_url = $post['ftp']['url'];
				$parsed_url = parse_url($ftp_url);
				$ftp_server = $parsed_url['host'] ;
				$conn_id = ftp_connect( $ftp_server );
				$is_ftp_ok = TRUE;
				
				// login with username and password
				$ftp_user_name = $post['ftp']['user'];
				$ftp_user_pass = $post['ftp']['pass'];

				// hide warning message
					echo '<span style="display:none">';
					if ( !ftp_login($conn_id, $ftp_user_name, $ftp_user_pass) ){
						$this->errors->add('form-validation', __('Login authentication failed', 'pmxi_plugin'));
						$is_ftp_ok = false;
					}
					echo '</span>';
					

					if ( $is_ftp_ok ){
						// try to download $remote_file and save it to $handle
						if (!ftp_fget($conn_id, $handle, $parsed_url['path'], FTP_ASCII, 0)) {
							 $this->errors->add('form-validation', __('There was a problem while downloading' . $remote_file . 'to' . $local_file, 'pmxi_plugin'));
						}
						
						// close the connection and the file handler
						ftp_close($conn_id);
						fclose($handle);
						
						// copy file in temporary folder
						$uploads = wp_upload_dir();
						if($uploads['error']){
							 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
						}			
						copy( $local_file, $uploads['path']  . basename($local_file));
						$url = $uploads['url'] . basename($local_file);
						// convert file to utf8
						chmod($uploads['path']  . basename($local_file), '0755');
						$fdata = file_get_contents($url);						
						file_put_contents($uploads['path']  . basename($local_file), $fdata);
						// end file convertion
						$xml = PMXI_Plugin::csv_to_xml($uploads['path']  . basename($local_file));
						if( is_array($xml) && isset($xml['error'])){
						$this->errors->add('form-validation', __($xml['error'], 'pmxi_plugin'));
						}
						else {
							unlink( $uploads['path'] . basename($local_file) );
							$filename = $local_file;
							
							// Let's make sure the file exists and is writable first.
							if (is_writable($filename)) {
							
							    if (!$handle = fopen($filename, 'w')) {
							         $this->errors->add('form-validation', __('Cannot open file ' . $filename, 'pmxi_plugin'));
							    }
							
							    // Write $somecontent to our opened file.
							    if (fwrite($handle, $xml) === FALSE) {
							    	$this->errors->add('form-validation', __('Cannot write to file ' . $filename, 'pmxi_plugin'));
							    }
							
							    fclose($handle);
							
							} else {
								$this->errors->add('form-validation', __('The file' . $filename . 'is not writable', 'pmxi_plugin'));
							}
							$filePath = $local_file;
							$source = array(
								'name' => basename($local_file),
								'type' => 'ftp',
								'path' => $filePath,
							);
						}
					}
			} else {
				$filePath = $post['ftp']['url'];
				if (isset($post['ftp']['user']) and $post['ftp']['user'] !== '') {
					$filePath = preg_replace('%://([^@/]*@)?%', '://' . urlencode($post['ftp']['user']) . ':' . urlencode($post['ftp']['pass']) . '@', $filePath, 1);
				}
				$source = array(
					'name' => basename(parse_url($filePath, PHP_URL_PATH)),
					'type' => 'ftp',
					'path' => $filePath,
				);
			}
		} elseif ('file' == $this->input->post('type')) {
			if (empty($post['file'])) {
				$this->errors->add('form-validation', __('XML/CSV file must be specified', 'pmxi_plugin'));
			} elseif (preg_match('%\W(csv)$%i', trim($post['file']))) {
				$uploads = PMXI_Plugin::ROOT_DIR . '/upload/';
				$wp_uploads = wp_upload_dir();
				if($wp_uploads['error']){
					 $this->errors->add('form-validation', __('Can not create upload folder. Permision denied', 'pmxi_plugin'));
				}		
				// copy file in temporary folder
				// hide warning message
				echo '<span style="display:none">';
				copy( $uploads . $post['file'], $wp_uploads['path']  . basename($post['file']));
				echo '</span>';
				$url = $wp_uploads['url'] . basename($post['file']);
				// convert file to utf8
				chmod($wp_uploads['path']  . basename($post['file']), '0755');
				$fdata = file_get_contents($url);				
				file_put_contents($wp_uploads['path']  . basename($post['file']), $fdata);
				// end file convertion
				$xml = PMXI_Plugin::csv_to_xml($wp_uploads['path']  . basename($post['file']));
				if( is_array($xml) && isset($xml['error'])){
					$this->errors->add('form-validation', __($xml['error'], 'pmxi_plugin'));
				}
				else {
					$filename = $wp_uploads['path']  . basename($post['file']);
					
					// Let's make sure the file exists and is writable first.
					if (is_writable($filename)) {
					
					    if (!$handle = fopen($filename, 'w')) {
					         $this->errors->add('form-validation', __('Cannot open file ' . $filename, 'pmxi_plugin'));
					    }
					
					    // Write $somecontent to our opened file.
					    if (fwrite($handle, $xml) === FALSE) {
					    	$this->errors->add('form-validation', __('Cannot write to file ' . $filename, 'pmxi_plugin'));
					    }
					
					    fclose($handle);
					
					} else {
						$this->errors->add('form-validation', __('The file ' . $filename . ' is not writable or you use wildcard for CSV file', 'pmxi_plugin'));
					}
					$filePath = $wp_uploads['path']  . basename($post['file']);
					$source = array(
						'name' => basename(parse_url($filePath, PHP_URL_PATH)),
						'type' => 'file',
						'path' => $filePath,
					); 
				}		
				}
				else {
				$filePath = PMXI_Plugin::ROOT_DIR . '/upload/' . $post['file'];
				$source = array(
					'name' => basename(parse_url($filePath, PHP_URL_PATH)),
					'type' => 'file',
					'path' => $filePath,
				);
			}
		} elseif ('reimport' == $this->input->post('type')) {
			if (empty($post['reimport'])) {
				$this->errors->add('form-validation', __('XML/CSV file must be specified', 'pmxi_plugin'));
			}
		}
		
		if ($post['is_update_previous'] and empty($post['update_previous'])) {
			$this->errors->add('form-validation', __('Previous import for update must be selected or uncheck `Update Previous Import` option to proceed with a new one', 'pmxi_plugin'));
		}
		
		if ($this->input->post('is_submitted') and ! $this->errors->get_error_codes()) {
			
			check_admin_referer('choose-file', '_wpnonce_choose-file');
			
			if ('reimport' == $this->input->post('type')) { // get file content from database
				preg_match('%^#(\d+):%', $post['reimport'], $mtch) and $reimport_id = $mtch[1] or $reimport_id = 0;
				$file = new PMXI_File_Record();
				if ( ! $reimport_id or $file->getById($reimport_id)->isEmpty()) {
					$xml = FALSE;
				} else {
					$xml = @file_get_contents($file->path);			

					if ($contents = get_headers($file->path,1 ) and PMXI_Plugin::detect_csv($contents['Content-Type'])) $xml = PMXI_Plugin::csv_to_xml($file->path);

					$source = array(
						'name' => $file->name,
						'type' => 'reimport',
						'path' => $file->path,
					);

				}
			} else {
				if (in_array($this->input->post('type'), array('ftp', 'file'))) { // file may be specified by pattern
					$file_path_array = @PMXI_Helper::safe_glob($filePath, PMXI_Helper::GLOB_NODIR | PMXI_Helper::GLOB_PATH);
					if ($file_path_array) {
						$filePath = array_shift($file_path_array); // take only 1st matching one
					} else {
						$filePath = FALSE;
					}					
				}  

				ob_start();
				$filePath && @readgzfile($filePath);								
				
				$xml = ob_get_clean();
				
				$wp_uploads = wp_upload_dir();
				$url = $wp_uploads['url'] .'/'. basename($filePath);								
				file_put_contents($wp_uploads['path']  .'/'. basename($filePath), $xml);				
				chmod($wp_uploads['path']  .'/'. basename($filePath), '0755');				
				
				if ($contents = get_headers($url,1 ) and PMXI_Plugin::detect_csv($contents['Content-Type'])) $xml = PMXI_Plugin::csv_to_xml($wp_uploads['path']. basename($filePath));

			}
			
			if (PMXI_Import_Record::validateXml($xml, $this->errors)) {
				// xml is valid
								
				$_SESSION['pmxi_import'] = array(
					'xml' => $xml,
					'source' => $source,
				);
				
				$update_previous = new PMXI_Import_Record();
				if ($post['is_update_previous'] and ! $update_previous->getById($post['update_previous'])->isEmpty()) {
					$_SESSION['pmxi_import'] += array(
						'update_previous' => $update_previous->id,
						'xpath' => $update_previous->xpath,
						'template' => $update_previous->template,
						'options' => $update_previous->options,
					);
				} else {
					$_SESSION['pmxi_import']['update_previous'] = '';
				}		
				
				wp_redirect(add_query_arg('action', 'element', $this->baseUrl)); die();
			}
		}
		
		$this->render();
	}
	
	/**
	 * Step #2: Choose elements
	 */
	public function element()
	{
		
		
		$xpath = new DOMXPath($this->data['dom']);		
		$post = $this->input->post(array('xpath' => ''));
		$this->data['post'] =& $post;

		if ($this->input->post('is_submitted')) {
			check_admin_referer('choose-elements', '_wpnonce_choose-elements');
			if ('' == $post['xpath']) {
				$this->errors->add('form-validation', __('No elements selected', 'pmxi_plugin'));
			} else {
				$node_list = @ $xpath->query($post['xpath']); // make sure only element selection is allowed; prevent parsing warning to be displayed
				
				if (FALSE === $node_list) {
					$this->errors->add('form-validation', __('Invalid XPath expression', 'pmxi_plugin'));
				} elseif ( ! $node_list->length) {
					$this->errors->add('form-validation', __('No matching elements found for XPath expression specified', 'pmxi_plugin'));
				} else {
					foreach ($node_list as $el) {
						if ( ! $el instanceof DOMElement) {
							$this->errors->add('form-validation', __('XPath must match only elements', 'pmxi_plugin'));
							break;
						};
					}
				}
			}
			if ( ! $this->errors->get_error_codes()) {
				$_SESSION['pmxi_import']['xpath'] = $post['xpath'];
				wp_redirect(add_query_arg('action', 'template', $this->baseUrl)); die();
			}
		} else {

			$this->shrink_xml_element($this->data['dom']->documentElement);

			if (isset($_SESSION['pmxi_import']['xpath'])) {
				$post['xpath'] = $_SESSION['pmxi_import']['xpath'];
				if ( ! $xpath->query($post['xpath'])->length and ! empty($_SESSION['pmxi_import']['update_previous'])) {
					$_GET['pmxi_nt'] = __('<b>Warning</b>: No matching elements found for XPath expression from the import being updated. It probably means that new XML file has different format. Though you can update XPath, procceed only if you sure about update operation being valid.', 'pmxi_plugin');
				}
			} else {
				// suggest 1st repeating element as default selection
				$post['xpath'] = $this->xml_find_repeating($this->data['dom']->documentElement);
			}
		}
		
		// workaround to prevent rendered XML representation to eat memory since it has to be stored in momory when output is bufferred
		$this->render();
		add_action('pmxi_action_after', array($this, 'element_after'));
	}
	public function element_after()
	{
		$this->render();
	}
	
	/**
	 * Helper to evaluate xpath and return matching elements as direct paths for javascript side to highlight them
	 */
	public function evaluate()
	{
		if ( ! PMXI_Plugin::getInstance()->getAdminCurrentScreen()->is_ajax) { // call is only valid when send with ajax
			wp_redirect(add_query_arg('action', 'element', $this->baseUrl)); die();
		}

		$xpath = new DOMXPath($this->data['dom']);
		$post = $this->input->post(array('xpath' => ''));
		if ('' == $post['xpath']) {
			$this->errors->add('form-validation', __('No elements selected', 'pmxi_plugin'));
		} else {
			$node_list = @ $xpath->query($post['xpath']); // prevent parsing warning to be displayed
			$this->data['node_list_count'] = $node_list->length;
			if (FALSE === $node_list) {
				$this->errors->add('form-validation', __('Invalid XPath expression', 'pmxi_plugin'));
			} elseif ( ! $node_list->length) {
				$this->errors->add('form-validation', __('No matching elements found for XPath expression specified', 'pmxi_plugin'));
			} else {
				foreach ($node_list as $el) {
					if ( ! $el instanceof DOMElement) {
						$this->errors->add('form-validation', __('XPath must match only elements', 'pmxi_plugin'));
						break;
					};
				}
			}
		}
		if ( ! $this->errors->get_error_codes()) {
			$this->shrink_xml_element($this->data['dom']->documentElement);
			$xpath = new DOMXPath($this->data['dom']);
			$this->data['node_list'] = $node_list = @ $xpath->query($post['xpath']); // prevent parsing warning to be displayed
			
			$paths = array(); $this->data['paths'] =& $paths;
			if (PMXI_Plugin::getInstance()->getOption('highlight_limit') and $node_list->length <= PMXI_Plugin::getInstance()->getOption('highlight_limit')) {
				foreach ($node_list as $el) {
					if ( ! $el instanceof DOMElement) continue;
					
					$p = $this->get_xml_path($el, $xpath) and $paths[] = $p;
				}
			}
			$this->render();
		} else {
			$this->error();
		}
	}
	
	/**
	 * Step #3: Choose template
	 */
	public function template()
	{
		
		$template = new PMXI_Template_Record();
		$default = array(
			'title' => '',
			'content' => '',
			'name' => '',
			'is_keep_linebreaks' => 0,
		);
		if ($this->isWizard) {			
			$this->data['post'] = $post = $this->input->post(
				(isset($_SESSION['pmxi_import']['template']) ? $_SESSION['pmxi_import']['template'] : array())
				+ $default
			);
		} else {
			$this->data['post'] = $post = $this->input->post(
				$this->data['import']->template
				+ $default
			);
		}
		
		if (($load_template = $this->input->post('load_template'))) { // init form with template selected
			if ( ! $template->getById($load_template)->isEmpty()) {
				$this->data['post'] = array(
					'title' => $template->title,
					'content' => $template->content,
					'is_keep_linebreaks' => $template->is_keep_linebreaks,					
					'name' => '', // template is always empty
				);
				$_SESSION['pmxi_import']['is_loaded_template'] = $load_template;
			}

		} elseif ($this->input->post('is_submitted')) { // save template submission
			check_admin_referer('template', '_wpnonce_template');
			
			if (empty($post['title'])) {
				$this->errors->add('form-validation', __('Post title is empty', 'pmxi_plugin'));
			} else {
				$this->_validate_template($post['title'], 'Post title');
			}
			if (empty($post['content'])) {
				$this->errors->add('form-validation', __('Post content is empty', 'pmxi_plugin'));
			} else {
				$this->_validate_template($post['content'], 'Post content');
			}
			
			
			if ( ! $this->errors->get_error_codes()) {
				if ( ! empty($post['name'])) { // save template in database
					$template->getByName($post['name'])->set($post)->save();
					$_SESSION['pmxi_import']['saved_template'] = $template->id;
				}
				if ($this->isWizard) {
					$_SESSION['pmxi_import']['template'] = $post;
					wp_redirect(add_query_arg('action', 'options', $this->baseUrl)); die();
				} else {
					$this->data['import']->set('template', $post)->save();
					wp_redirect(add_query_arg(array('page' => 'pmxi-admin-manage', 'pmlc_nt' => urlencode(__('Template updated', 'pmxi_plugin'))) + array_intersect_key($_GET, array_flip($this->baseUrlParamNames)), admin_url('admin.php'))); die();
				}
			}
		}
		
		if (user_can_richedit()) {
			wp_enqueue_script('editor');
		}
		wp_enqueue_script('word-count');
		add_thickbox();
		wp_enqueue_script('media-upload');
		add_action('admin_print_footer_scripts', 'wp_tiny_mce', 25);
		wp_enqueue_script('quicktags');
		$this->render();
	}
	protected function _validate_template($text, $field_title)
	{
		try {
			$scanner = new XmlImportTemplateScanner();
			$tokens = $scanner->scan(new XmlImportStringReader($text));
			$parser = new XmlImportTemplateParser($tokens);
			$tree = $parser->parse();
		} catch (XmlImportException $e) {
			$this->errors->add('form-validation', sprintf(__('%s template is invalid: %s', 'pmxi_plugin'), $field_title, $e->getMessage()));
		}
	}
	
	/**
	 * Preview selected xml tag (called with ajax from `template` step)
	 */
	public function tag()
	{
		if (empty($this->data['elements']))
		{

			$update_previous = new PMXI_Import_Record();
			if ($update_previous->getById($this->input->get('id'))) {				
				$_SESSION['pmxi_import'] = array(
					'update_previous' => $update_previous->id,
					'xpath' => $update_previous->xpath,
					'template' => $update_previous->template,
					'options' => $update_previous->options,
				);
				$history_file = new PMXI_File_Record();
				$history_file->getBy('import_id', $update_previous->id);
				$history_file->__get('contents');
				$_SESSION['pmxi_import']['xml'] = $history_file->contents;
			} else {
				$_SESSION['pmxi_import']['update_previous'] = '';
			}		
			if (!empty($_SESSION['pmxi_import']['xml']))
			{	
				$dom = new DOMDocument();			
				$dom->loadXML(preg_replace('%xmlns\s*=\s*([\'"]).*\1%sU', '', $_SESSION['pmxi_import']['xml']));
				$xpath = new DOMXPath($dom);

				$this->data['elements'] = $elements = $xpath->query($_SESSION['pmxi_import']['xpath']);
			}
		}
		
		$this->data['tagno'] = min(max(intval($this->input->getpost('tagno', 1)), 1), $this->data['elements']->length);
		$this->render();
	}
	
	/**
	 * Preview future post based on current template and tag (called with ajax from `template` step)
	 */
	public function preview()
	{
		$post = $this->input->post(array(
			'title' => '',
			'content' => '',
			'is_keep_linebreaks' => 0,
		));
		$tagno = min(max(intval($this->input->getpost('tagno', 1)), 1), $this->data['elements']->length);
		$xpath = "(" . $_SESSION['pmxi_import']['xpath'] . ")[$tagno]";
		// validate
		try {

			if (empty($post['title'])) {
				$this->errors->add('form-validation', __('Post title is empty', 'pmxi_plugin'));
			} else {
				list($this->data['title']) = XmlImportParser::factory($_SESSION['pmxi_import']['xml'], $xpath, $post['title'], $file)->parse(); unlink($file);
				if ( ! isset($this->data['title']) or '' == strval(trim(strip_tags($this->data['title'], '<img><input><textarea><iframe><object><embed>')))) {
					$this->errors->add('xml-parsing', __('<strong>Warning</strong>: resulting post title is empty', 'pmxi_plugin'));
				}
			}
		} catch (XmlImportException $e) {
			$this->errors->add('form-validation', sprintf(__('Error parsing title: %s', 'pmxi_plugin'), $e->getMessage()));
		}
		try {
			if (empty($post['content'])) {
				$this->errors->add('form-validation', __('Post content is empty', 'pmxi_plugin'));
			} else {
				list($this->data['content']) = XmlImportParser::factory($post['is_keep_linebreaks'] ? $_SESSION['pmxi_import']['xml'] : preg_replace('%\r\n?|\n%', ' ', $_SESSION['pmxi_import']['xml']), $xpath, $post['content'], $file)->parse(); unlink($file);
				if ( ! isset($this->data['content']) or '' == strval(trim(strip_tags($this->data['content'], '<img><input><textarea><iframe><object><embed>')))) {
					$this->errors->add('xml-parsing', __('<strong>Warning</strong>: resulting post content is empty', 'pmxi_plugin'));
				}
			}
		} catch (XmlImportException $e) {
			$this->errors->add('form-validation', sprintf(__('Error parsing content: %s', 'pmxi_plugin'), $e->getMessage()));
		}
		
		$this->render();
	}
	
	/**
	 * Step #4: Options
	 */
	public function options()
	{
		include_once(PMXI_Plugin::ROOT_DIR.'/libraries/XmlImportCsvParse.php');

		$default = PMXI_Plugin::get_default_import_options();
		
		if ($this->isWizard) {			
			$this->data['source_type'] = $_SESSION['pmxi_import']['source']['type'];
			$default['unique_key'] = $_SESSION['pmxi_import']['template']['title'];
			$post = $this->input->post(
				(isset($_SESSION['pmxi_import']['options']) ? $_SESSION['pmxi_import']['options'] : array())
				+ $default
			);

			$scheduled = $this->input->post(array(
				'is_scheduled' => ! empty($post['scheduled']),
				'scheduled_period' => ! empty($post['scheduled']) ? $post['scheduled'] : '0 0 * * *', // daily by default
			));

		} else {
			$this->data['source_type'] = $this->data['import']->type;
			$post = $this->input->post(
				$this->data['import']->options
				+ $default
			);
			$scheduled = $this->input->post(array(
				'is_scheduled' => ! empty($this->data['import']->scheduled),
				'scheduled_period' => ! empty($this->data['import']->scheduled) ? $this->data['import']->scheduled : '0 0 * * *', // daily by default
			));
		}
		$this->data['post'] =& $post;		
		$this->data['scheduled'] =& $scheduled;
		$this->data['is_loaded_template'] = $_SESSION['pmxi_import']['is_loaded_template'];

		if (($load_options = $this->input->post('load_options'))) { // init form with template selected
			$this->data['load_options'] = true;
			$template = new PMXI_Template_Record();
			if ( ! $template->getById($this->data['is_loaded_template'])->isEmpty()) {				
				$post = $template->options + $default;
				$scheduled = array(
					'is_scheduled' => ! empty($template->scheduled),
					'scheduled_period' => ! empty($template->scheduled) ? $template->scheduled : '0 0 * * *', // daily by default
				);
			}
			
		} elseif (($reset_options = $this->input->post('reset_options'))){
			$post = $default;
			$scheduled = $this->input->post(array(
				'is_scheduled' => ! empty($post['scheduled']),
				'scheduled_period' => ! empty($post['scheduled']) ? $post['scheduled'] : '0 0 * * *', // daily by default
			));
		} elseif ($this->input->post('is_submitted')) {
			check_admin_referer('options', '_wpnonce_options');
			// remove entires where both custom_name and custom_value are empty 
			$not_empty = array_flip(array_values(array_merge(array_keys(array_filter($post['custom_name'])), array_keys(array_filter($post['custom_value'])))));
			$post['custom_name'] = array_intersect_key($post['custom_name'], $not_empty);
			$post['custom_value'] = array_intersect_key($post['custom_value'], $not_empty);
			// validate
			if (array_keys(array_filter($post['custom_name'])) != array_keys(array_filter($post['custom_value']))) {
				$this->errors->add('form-validation', __('Both name and value must be set for all custom parameters', 'pmxi_plugin'));
			} else {
				foreach ($post['custom_name'] as $custom_name) {
					$this->_validate_template($custom_name, __('Custom Field Name', 'pmxi_plugin'));
				}
				foreach ($post['custom_value'] as $custom_value) {
					$this->_validate_template($custom_value, __('Custom Field Value', 'pmxi_plugin'));
				}
			}
			if ('page' == $post['type'] and ! preg_match('%^(-?\d+)?$%', $post['order'])) {
				$this->errors->add('form-validation', __('Order must be an integer number', 'pmxi_plugin'));
			}
			if ('post' == $post['type']) {
				/*'' == $post['categories'] or $this->_validate_template($post['categories'], __('Categories', 'pmxi_plugin'));*/
				'' == $post['tags'] or $this->_validate_template($post['tags'], __('Tags', 'pmxi_plugin'));
			}
			if ('specific' == $post['date_type']) {
				'' == $post['date'] or $this->_validate_template($post['date'], __('Date', 'pmxi_plugin'));
			} else {
				'' == $post['date_start'] or $this->_validate_template($post['date_start'], __('Start Date', 'pmxi_plugin'));
				'' == $post['date_end'] or $this->_validate_template($post['date_end'], __('Start Date', 'pmxi_plugin'));
			}			
			if ('' == $post['tags_delim']) {
				$this->errors->add('form-validation', __('Tag list delimiter must cannot be empty', 'pmxi_plugin'));
			}
			if ($post['is_import_specified']) {
				if (empty($post['import_specified'])) {
					$this->errors->add('form-validation', __('Records to import must be specified or uncheck `Import only specified records` option to process all records', 'pmxi_plugin'));
				} else {
					$chanks = preg_split('% *, *%', $post['import_specified']);
					foreach ($chanks as $chank) {
						if ( ! preg_match('%^([1-9]\d*)( *- *([1-9]\d*))?$%', $chank, $mtch)) {
							$this->errors->add('form-validation', __('Wrong format of `Import only specified records` value', 'pmxi_plugin'));
							break;
						} elseif ($this->isWizard and (intval($mtch[1]) > $this->data['elements']->length or isset($mtch[3]) and intval($mtch[3]) > $this->data['elements']->length)) {
							$this->errors->add('form-validation', __('One of the numbers in `Import only specified records` value exceeds record quantity in XML file', 'pmxi_plugin'));
							break;
						}
					}
				}
			}
			if ('' == $post['unique_key']) {
				$this->errors->add('form-validation', __('Expression for `Post Unique Key` must be set, use the same expression as specified for post title if you are not sure what to put there', 'pmxi_plugin'));
			} else {
				$this->_validate_template($post['unique_key'], __('Post Unique Key', 'pmxi_plugin'));
			}
			
			if ( ! $this->errors->get_error_codes()) { // no validation errors found
				// assign some defaults
				'' !== $post['date'] or $post['date'] = 'now';
				'' !== $post['date_start'] or $post['date_start'] = 'now';
				'' !== $post['date_end'] or $post['date_end'] = 'now';
				
				if ($this->isWizard) {
					$_SESSION['pmxi_import']['options'] = $post;
					$_SESSION['pmxi_import']['scheduled'] = $scheduled['is_scheduled'] ? $scheduled['scheduled_period'] : '';

					// Update template options
					if (!empty($_SESSION['pmxi_import']['saved_template']))  {
						$template = new PMXI_Template_Record();
						$template->getById($_SESSION['pmxi_import']['saved_template'])->set(array(
																								'options' => $_SESSION['pmxi_import']['options'],
																								'scheduled' => $_SESSION['pmxi_import']['scheduled']))->update();
					}
					elseif (!empty($_SESSION['pmxi_import']['is_loaded_template']))
					{
						$template = new PMXI_Template_Record();
						$template->getById($_SESSION['pmxi_import']['is_loaded_template'])->set(array(
																								'options' => $_SESSION['pmxi_import']['options'],
																								'scheduled' => $_SESSION['pmxi_import']['scheduled']))->update();
					}

					if ( ! $this->input->post('save_only')) { 						
						wp_redirect(add_query_arg('action', 'process', $this->baseUrl)); die();
					} else {
						$import = $this->data['update_previous'];
						$is_update = ! $import->isEmpty();
						$import->set(
							$_SESSION['pmxi_import']['source']
							+ array(
								'xpath' => $_SESSION['pmxi_import']['xpath'],
								'template' => $_SESSION['pmxi_import']['template'],
								'options' => $_SESSION['pmxi_import']['options'],
								'scheduled' => $_SESSION['pmxi_import']['scheduled'],
							)
						)->save();
						
						$history_file = new PMXI_File_Record();
						$history_file->set(array(
							'name' => $import->name,
							'import_id' => $import->id,
							'path' => $import->path,
							'contents' => $_SESSION['pmxi_import']['xml'],
							'registered_on' => date('Y-m-d H:i:s'),
						))->save();	
						unset($_SESSION['pmxi_import']); // clear session data
						wp_redirect(add_query_arg(array('page' => 'pmxi-admin-manage', 'pmlc_nt' => urlencode($is_update ? __('Import updated', 'pmxi_plugin') : __('Import created', 'pmxi_plugin'))), admin_url('admin.php'))); die();
					}
				} else {
					$this->data['import']->set('options', $post)->set('scheduled', $scheduled['is_scheduled'] ? $scheduled['scheduled_period'] : '')->save();
					$template = new PMXI_Template_Record();

					if (!$template->getByName($this->data['import']->template['name'])->isEmpty()){
						$template->set(array(
											'options' => $post,
											'scheduled' => ($scheduled['is_scheduled'] ? $scheduled['scheduled_period'] : '')))->update();
					}
					wp_redirect(add_query_arg(array('page' => 'pmxi-admin-manage', 'pmlc_nt' => urlencode(__('Options updated', 'pmxi_plugin'))) + array_intersect_key($_GET, array_flip($this->baseUrlParamNames)), admin_url('admin.php'))); die();
				} 
			}
		}
		
		! empty($post['custom_name']) or $post['custom_name'] = array('') and $post['custom_value'] = array('');
		
		$this->render();
	}

	/**
	 * Import processing step (status console)
	 */
	public function process()
	{
		$this->render();
		wp_ob_end_flush_all(); flush();

		set_time_limit(0);				

		// store import info in database
		$import = $this->data['update_previous'];
		$import->set(
			$_SESSION['pmxi_import']['source']
			+ array(
				'xpath' => $_SESSION['pmxi_import']['xpath'],
				'template' => $_SESSION['pmxi_import']['template'],
				'options' => $_SESSION['pmxi_import']['options'],
				'scheduled' => $_SESSION['pmxi_import']['scheduled']
			)
		)->save();					
		
		$logger = create_function('$m', 'echo "<div class=\\"progress-msg\\">$m</div>\\n"; flush();');

		if (in_array($import->type, array('ftp', 'file'))) { // process files by patten
			$import->execute($logger);
		} else { // directly process XML
			$import->process($_SESSION['pmxi_import']['xml'], $logger);
		}
		
		unset($_SESSION['pmxi_import']); // clear session data (prevent from reimporting the same data on page refresh)

			// [indicate in header process is complete]
$msg = addcslashes(__('Complete', 'pmxi_plugin'), "'\n\r");
echo <<<COMPLETE
<script type="text/javascript">
//<![CDATA[
(function($){
	$('#status').html('$msg');
	window.onbeforeunload = false;
})(jQuery);
//]]>
</script>
COMPLETE;
		// [/indicate in header process is complete]	
	}

	/**
	 * Remove xml document nodes by xpath expression
	*/
	function removeNode($xml, $path)
    {
        $result = $xml->xpath($path);

        if (empty($result)) return false;
        $errlevel = error_reporting(E_ALL & ~E_WARNING);
        foreach ($result as $r) unset ($r[0]);
        error_reporting($errlevel);  

        return true;
    }  

    /*
	 * 
	 * Get SimpleXML object by xpath extension
	 *
	*/
    function get_chank(& $xml, $path){
    	
    	$result = $xml->xpath($path);

    	$array = array();
    	foreach ($result as $el) {        		
    		array_push($array, $this->simplexml2array($el));
    	}

    	if (empty($array)) return false;
    	
		return new SimpleXMLElement(ArrayToXML::toXml($array));
    }
	
	/*
	 * 
	 * Convert SimpleXML object to array 
	 *
	*/
    function simplexml2array($xml) {
		if (@get_class($xml) == 'SimpleXMLElement') {
			$attributes = $xml->attributes();
			foreach($attributes as $k=>$v) {
				if ($v) $a[$k] = (string) $v;
			}
			$x = $xml;
			$xml = get_object_vars($xml);
		}
		if (is_array($xml)) {
			if (count($xml) == 0) return (string) $x; // for CDATA
			foreach($xml as $key=>$value) {
				$r[$key] = $this->simplexml2array($value);
			}
			if (isset($a)) $r['@attributes'] = $a;    // Attributes
			return $r;
		}
		return (string) $xml;
	}

	protected $_sibling_limit = 20;
	protected function get_xml_path(DOMElement $el, DOMXPath $xpath)
	{
		for($p = '', $doc = $el; $doc and ! $doc instanceof DOMDocument; $doc = $doc->parentNode) {
			if (($ind = $xpath->query('preceding-sibling::' . $doc->nodeName, $doc)->length)) {
				$p = '[' . ++$ind . ']' . $p;
			} elseif ( ! $doc->parentNode instanceof DOMDocument) {
				$p = '[' . ($ind = 1) . ']' . $p;
			}
			$p = '/' . $doc->nodeName . $p;
		}
		return $p;
	}
	
	protected function shrink_xml_element(DOMElement $el)
	{
		$prev = null; $sub_ind = null;
		for ($i = 0; $i < $el->childNodes->length; $i++) {
			$child = $el->childNodes->item($i);
			if ($child instanceof DOMText) {
				if ('' == trim($child->wholeText)) {
					$el->removeChild($child);
					$i--;
					continue;
				}
			}
			if ($child instanceof DOMComment) {
				continue;
			}
			if ($prev instanceof $child and $prev->nodeName == $child->nodeName) {
				$sub_ind++;
			} else {
				if ($sub_ind > $this->_sibling_limit) {
					$el->insertBefore(new DOMComment('[pmxi_more:' . ($sub_ind - $this->_sibling_limit) . ']'), $child);
					$i++;
				}
				$sub_ind = 1;
				$prev = null;
			}
			if ($child instanceof DOMElement) {
				$prev = $child;
				if ($sub_ind <= $this->_sibling_limit) {
					$this->shrink_xml_element($child); 
				} else {
					$el->removeChild($child);
					$i--;
				}
			}
		}
		if ($sub_ind > $this->_sibling_limit) {
			$el->appendChild(new DOMComment('[pmxi_more:' . ($sub_ind - $this->_sibling_limit) . ']'));
		}
		return $el;
	}
	protected function render_xml_element(DOMElement $el, $shorten = false, $path = '/', $ind = 1, $lvl = 0)
	{
		$path .= $el->nodeName;
		if ( ! $el->parentNode instanceof DOMDocument and $ind > 0) {
			$path .= "[$ind]";
		}
		
		echo '<div class="xml-element lvl-' . $lvl . ' lvl-mod4-' . ($lvl % 4) . '" title="' . $path . '">';
		if ($el->hasChildNodes()) {
			$is_render_collapsed = $ind > 1;
			if ($el->childNodes->length > 1 or ! $el->childNodes->item(0) instanceof DOMText or strlen(trim($el->childNodes->item(0)->wholeText)) > 40) {
				echo '<div class="xml-expander">' . ($is_render_collapsed ? '+' : '-') . '</div>';
			}
			echo '<div class="xml-tag opening">&lt;<span class="xml-tag-name">' . $el->nodeName . '</span>'; $this->render_xml_attributes($el, $path . '/'); echo '&gt;</div>';
			if (1 == $el->childNodes->length and $el->childNodes->item(0) instanceof DOMText) {
				$this->render_xml_text(trim($el->childNodes->item(0)->wholeText), $shorten, $is_render_collapsed);
			} else {
				echo '<div class="xml-content' . ($is_render_collapsed ? ' collapsed' : '') . '">';
				$indexes = array();
				foreach ($el->childNodes as $child) {
					if ($child instanceof DOMElement) {
						empty($indexes[$child->nodeName]) and $indexes[$child->nodeName] = 0; $indexes[$child->nodeName]++;
						$this->render_xml_element($child, $shorten, $path . '/', $indexes[$child->nodeName], $lvl + 1); 
					} elseif ($child instanceof DOMText) {
						$this->render_xml_text(trim($child->wholeText), $shorten); 
					} elseif ($child instanceof DOMComment) {
						if (preg_match('%\[pmxi_more:(\d+)\]%', $child->nodeValue, $mtch)) {
							$no = intval($mtch[1]);
							echo '<div class="xml-more">[ &dArr; ' . sprintf(__('<strong>%s</strong> %s more', 'pmxi_plugin'), $no, _n('element', 'elements', $no, 'pmxi_plugin')) . ' &dArr; ]</div>';
						}
					}
				}
				echo '</div>';
			}
			echo '<div class="xml-tag closing">&lt;/<span class="xml-tag-name">' . $el->nodeName . '</span>&gt;</div>';
		} else {
			echo '<div class="xml-tag opening empty">&lt;<span class="xml-tag-name">' . $el->nodeName . '</span>'; $this->render_xml_attributes($el); echo '/&gt;</div>';
		}
		echo '</div>';
	}
	protected function render_xml_text($text, $shorten = false, $is_render_collapsed = false)
	{
		if (empty($text)) {
			return; // do not display empty text nodes
		}
		if (preg_match('%\[more:(\d+)\]%', $text, $mtch)) {
			$no = intval($mtch[1]);
			echo '<div class="xml-more">[ &dArr; ' . sprintf(__('<strong>%s</strong> %s more', 'pmxi_plugin'), $no, _n('element', 'elements', $no, 'pmxi_plugin')) . ' &dArr; ]</div>';
			return;
		}
		$more = '';
		if ($shorten and preg_match('%^(.*?\s+){20}(?=\S)%', $text, $mtch)) {
			$text = $mtch[0];
			$more = '<span class="xml-more">[' . __('more', 'pmxi_plugin') . ']</span>';
		}
		$is_short = strlen($text) <= 40;
		$text = esc_html($text); 
		$text = preg_replace('%(?<!\s)\b(?!\s|\W[\w\s])|\w{20}%', '$0&#8203;', $text); // put explicit breaks for xml content to wrap
		echo '<div class="xml-content textonly' . ($is_short ? ' short' : '') . ($is_render_collapsed ? ' collapsed' : '') . '">' . $text . $more . '</div>';
	}
	protected function render_xml_attributes(DOMElement $el, $path = '/')
	{
		foreach ($el->attributes as $attr) {
			echo ' <span class="xml-attr" title="' . $path . '@' . $attr->nodeName . '"><span class="xml-attr-name">' . $attr->nodeName . '</span>=<span class="xml-attr-value">"' . esc_attr($attr->value) . '"</span></span>';
		}
	}
	
	protected function render_xml_element_table(DOMElement $el, $shorten = false, $path = '/', $ind = 0, $lvl = 0)
	{
		$path .= $el->nodeName;
		if ($ind > 0) {
			$path .= "[$ind]";
		}
		
		$is_render_collapsed = $ind > 1;
		echo '<tr class="xml-element lvl-' . $lvl . ($is_render_collapsed ? ' collapsed' : '') . '" title="' . $path . '">';
			echo '<td style="padding-left:' . ($lvl + 1) * 15 . 'px">';
				$is_inline = true;
				if ( ! (0 == $el->attributes->length and 1 == $el->childNodes->length and $el->childNodes->item(0) instanceof DOMText and strlen($el->childNodes->item(0)->wholeText) <= 40)) {
					$is_inline = false;
					echo '<div class="xml-expander">' . ($is_render_collapsed ? '+' : '-') . '</div>';
				}
				echo '<div class="xml-tag opening"><span class="xml-tag-name">' . $el->nodeName . '</span></div>';
			echo '</td>';
			echo '<td>';
				$is_inline and $this->render_xml_text_table(trim($el->childNodes->item(0)->wholeText), $shorten, NULL, NULL, $is_inline = true);
			echo '</td>';
		echo '</tr>';
		if ( ! $is_inline) {
			echo '<tr class="xml-content' . ($is_render_collapsed ? ' collapsed' : '') . '">';
				echo '<td colspan="2">';
					echo '<table>';
						$this->render_xml_attributes_table($el, $path . '/', $lvl + 1);
						$indexes = array();
						foreach ($el->childNodes as $child) {
							if ($child instanceof DOMElement) {
								empty($indexes[$child->nodeName]) and $indexes[$child->nodeName] = 1;
								$this->render_xml_element_table($child, $shorten, $path . '/', $indexes[$child->nodeName]++, $lvl + 1);
							} elseif ($child instanceof DOMText) {
								$this->render_xml_text_table(trim($child->wholeText), $shorten, $path . '/', $lvl + 1);
							}
						}
					echo '</table>';
				echo '</td>';
			echo '</tr>';
		}
	}
	protected function render_xml_text_table($text, $shorten = false, $path = '/', $lvl = 0, $is_inline = false)
	{
		if (empty($text)) {
			return; // do not display empty text nodes
		}
		$more = '';
		if ($shorten and preg_match('%^(.*?\s+){20}(?=\S)%', $text, $mtch)) {
			$text = $mtch[0];
			$more = '<span class="xml-more">[' . __('more', 'pmxi_plugin') . ']</span>';
		}
		$is_short = strlen($text) <= 40;
		$text = esc_html($text); 
		$text = preg_replace('%(?<!\s)\b(?!\s|\W[\w\s])|\w{20}%', '$0&#8203;', $text); // put explicit breaks for xml content to wrap
		if ($is_inline) {
			echo $text . $more;
		} else {
			echo '<tr class="xml-content-tr textonly lvl-' . $lvl . ($is_short ? ' short' : '') . '" title="' . $path . 'text()">';
				echo '<td style="padding-left:' . ($lvl + 1) * 15 . 'px"><span class="xml-attr-name">text</span></td>';
				echo '<td>' . $text . $more . '</td>';
			echo '</tr>';
		}
	}
	protected function render_xml_attributes_table(DOMElement $el, $path = '/', $lvl = 0)
	{
		foreach ($el->attributes as $attr) {
			echo '<tr class="xml-attr lvl-' . $lvl . '" title="' . $path . '@' . $attr->nodeName . '">';
				echo '<td style="padding-left:' . ($lvl + 1) * 15 . 'px"><span class="xml-attr-name">@' . $attr->nodeName . '</span></td>';
				echo '<td><span class="xml-attr-value">' . esc_attr($attr->value) . '</span></td>';
			echo '</tr>';
		}
	}
	
	protected function xml_find_repeating(DOMElement $el, $path = '/')
	{
		$path .= $el->nodeName;
		if ( ! $el->parentNode instanceof DOMDocument) {
			$path .= '[1]';
		}
		$children = array();
		foreach ($el->childNodes as $child) {
			if ($child instanceof DOMElement) {
				if ( ! empty($children[$child->nodeName])) {
					return $path . '/' . $child->nodeName;
				} else {
					$children[$child->nodeName] = true;
				}
			}
		}
		// reaching this point means we didn't find anything among current element children, so recursively ask children to find something in them
		foreach ($el->childNodes as $child) {
			if ($child instanceof DOMElement) {
				$result = $this->xml_find_repeating($child, $path . '/');
				if ($result) {
					return $result;
				}
			}
		}
		// reaching this point means we didn't find anything, so return element itself if the function was called for it
		if ('/' . $el->nodeName == $path) {
			return $path;
		}
		
		return NULL;		
	}	
	
}
