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
			if ( ! session_id()) session_start(); // prevent session initialization throw a notification in inline mode of delegated plugin 
		}
	}
	
	/**
	 * Previous Imports list
	 */
	public function index() {
		
		$get = $this->input->get(array(
			's' => '',
			'order_by' => 'ID',
			'order' => 'DESC',
			'pagenum' => 1,
			'perPage' => 10,
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
		
		$this->render();
	}
	
	/**
	 * Reimport
	 */
	public function update() {
		$id = $this->input->get('id');
		$this->data['item'] = $item = new PMXI_Import_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}
		
		if ($this->input->post('is_confirmed')) {
			check_admin_referer('update-import', '_wpnonce_update-import');
			
			if (in_array($item->type, array('ftp', 'file'))) {
				$xml = '';
			} else {

				$contents = get_headers($item->path,1 );
				
				if (preg_match('%\W(zip)$%i', trim($item->path))){

					$uploads = wp_upload_dir();
				
					$newfile = $uploads['path']."/".md5(time()).'.zip';

					if (!copy($item->path, $newfile)) {
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

				} elseif (preg_match('%\W(csv)$%i', trim($item->path)) or PMXI_Plugin::detect_csv($contents['Content-Type'])) {
					$uploads = wp_upload_dir();
					$fdata = file_get_contents($item->path);										
					$tmpname = md5(time()).'.csv';				
					file_put_contents($uploads['path']  .'/'. $tmpname, $fdata);					
					$xml = PMXI_Plugin::csv_to_xml($uploads['path']  .'/'. $tmpname);					
				}
				else
					$xml = @file_get_contents($item->path);				
			}
			if (in_array($item->type, array('ftp', 'file')) or PMXI_Import_Record::validateXml($xml, $this->errors)) { // xml is valid				
				// compose data to look like result of wizard steps				
				$_SESSION['pmxi_import'] = array(
					'xml' => $xml,
					'source' => array(
						'name' => $item->name,
						'type' => $item->type,
						'path' => $item->path,
					),
					'update_previous' => $item->id,
					'xpath' => $item->xpath,
					'template' => $item->template,
					'options' => $item->options,
				);
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
}