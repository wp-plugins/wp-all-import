<?php

class PMXI_File_Record extends PMXI_Model_Record {
	/**
	 * Initialize model instance
	 * @param array[optional] $data Array of record data to initialize object with
	 */
	public function __construct($data = array()) {
		parent::__construct($data);
		$this->setTable(PMXI_Plugin::getInstance()->getTablePrefix() . 'files');
	}
	
	/**
	 * @see PMXI_Model_Record::insert()
	 */
	public function insert() {
		$file_contents = NULL;
		if ($this->offsetExists('contents')) {
			$file_contents = $this['contents'];
			unset($this->contents);
		}
				
		parent::insert();

		if (isset($this->id) and ! is_null($file_contents)) {
			file_put_contents(PMXI_Plugin::ROOT_DIR . '/history/' . $this->id, $file_contents);
		}
		
		$list = new PMXI_File_List();
		$list->sweepHistory();
		return $this;
	}
	
	/**
	 * @see PMXI_Model_Record::update()
	 */
	public function update() {
		$file_contents = NULL;
		if ($this->offsetExists('contents')) {
			$file_contents = $this['contents'];
			unset($this->contents);
		}
				
		parent::update();

		if (isset($this->id) and ! is_null($file_contents)) {
			file_put_contents(PMXI_Plugin::ROOT_DIR . '/history/' . $this->id, $file_contents);
		}
		
		return $this;
	}
	
	public function __isset($field) {
		if ('contents' == $field and ! $this->offsetExists($field)) {
			return isset($this->id) and file_exists(PMXI_Plugin::ROOT_DIR . '/history/' . $this->id);
		}
		return parent::__isset($field);
	}
	
	public function __get($field) {
		if ('contents' == $field and ! $this->offsetExists('contents')) {
			if (isset($this->contents)) {
				$this['contents'] = file_get_contents(PMXI_Plugin::ROOT_DIR . '/history/' . $this->id);
			} else {
				$this->contents = NULL;
			}
		}
		return parent::__get($field);
	}
	
	public function delete() {
		if ($this->id) { // delete history file first
			$file_name = PMXI_Plugin::ROOT_DIR . '/history/' . $this->id;
			is_file($file_name) and unlink($file_name);
		}
		return parent::delete();
	}
}