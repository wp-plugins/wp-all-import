<?php 
/**
 * Manage Import's History
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class PMXI_Admin_History extends PMXI_Controller_Admin {
	
	public function init() {
		parent::init();
				
	}
	
	/**
	 * Import's History list
	 */
	public function index() {
		
		$get = $this->input->get(array(
			's' => '',
			'order_by' => 'date',
			'order' => 'DESC',
			'pagenum' => 1,
			'perPage' => 25,
			'id' => ''
		));
		$get['pagenum'] = absint($get['pagenum']);
		extract($get);
		if (empty($id)){ 
			wp_redirect(add_query_arg(array('page' => 'pmxi-admin-manage', 'pmxi_nt' => urlencode(__('Import is not specified.', 'pmxi_plugin'))), $this->baseUrl)); die();
		}
		$this->data += $get;
		$by = array('import_id' => $id);

		$this->data['import'] = new PMXI_Import_Record();
		$this->data['import']->getById($id);

		$list = new PMXI_History_List();
		
		$this->data['list'] = $list->setColumns(
				$list->getTable() . '.*'
			)->getBy($by, "$order_by $order", $pagenum, $perPage, $list->getTable() . '.id');
			
		$this->data['page_links'] = paginate_links(array(
			'base' => add_query_arg(array('id' => $id, 'pagenum' => '%#%'), $this->baseUrl),
			'format' => '',
			'prev_text' => __('&laquo;', 'pmxi_plugin'),
			'next_text' => __('&raquo;', 'pmxi_plugin'),
			'total' => ceil($list->total() / $perPage),
			'current' => $pagenum,
		));			

		$this->render();
	}	

	/*
	 * Download import log file
	 *
	 */
	public function log(){

		$id = $this->input->get('history_id');
		
		$import_id = $this->input->get('id');

		$wp_uploads = wp_upload_dir();
		
		$log_file = pmxi_secure_file( $wp_uploads['basedir'] . "/wpallimport/logs", 'logs', $id ) . '/' . $id . '.html';

		if (file_exists($log_file)) 
		{
			PMXI_download::xml($log_file);
		}
		else
		{			

			wp_redirect(add_query_arg(array('id' => $import_id, 'pmxi_nt' => urlencode(__('Log file does not exists.', 'pmxi_plugin'))), $this->baseUrl)); die();
		}
	}

	/**
	 * Delete an import
	 */
	public function delete() {
		$id = $this->input->get('id');
		$this->data['item'] = $item = new PMXI_History_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}
		$item->delete();
		wp_redirect(add_query_arg('pmxi_nt', urlencode(__('History deleted', 'pmxi_plugin')), $this->baseUrl)); die();				
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
		$this->data['items'] = $items = new PMXI_History_List();
		if (empty($action) or ! in_array($action, array('delete')) or empty($ids) or $items->getBy('id', $ids)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}		
		
		foreach($items->convertRecords() as $item) {
			$item->delete();
		}
		
		$id = $this->input->get('id');

		wp_redirect(add_query_arg(array('id' => $id, 'pmxi_nt' => urlencode(sprintf(__('<strong>%d</strong> %s deleted', 'pmxi_plugin'), $items->count(), _n('history', 'histories', $items->count(), 'pmxi_plugin')))), $this->baseUrl)); die();		
				
	}
}