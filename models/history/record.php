<?php

class PMXI_History_Record extends PMXI_Model_Record {	
	
	/**
	 * Initialize model instance
	 * @param array[optional] $data Array of record data to initialize object with
	 */
	public function __construct($data = array()) {
		parent::__construct($data);
		$this->setTable(PMXI_Plugin::getInstance()->getTablePrefix() . 'history');
	}

	public function delete( $db = true ) {
		if ($this->id) { // delete history file first
			$uploads = wp_upload_dir();
			$file_name = $uploads['basedir']  . DIRECTORY_SEPARATOR . PMXI_Plugin::LOGS_DIRECTORY . DIRECTORY_SEPARATOR . $this->id . '.html';
			@file_exists($file_name) and @is_file($file_name) and wp_all_import_remove_source($file_name, true);
			$file_name = wp_all_import_secure_file( $uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::LOGS_DIRECTORY, $this->id ) . DIRECTORY_SEPARATOR . $this->id . '.html';
			@file_exists($file_name) and @is_file($file_name) and wp_all_import_remove_source($file_name, true);
		}
		return ($db) ? parent::delete() : true;
	}
	
}