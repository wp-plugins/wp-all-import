<?php
function pmxi_wp_ajax_unmerge_file(){

	extract($_POST);	

	PMXI_Plugin::$session = PMXI_Session::get_instance();		
	
	if ( ! empty(PMXI_Plugin::$session->options['nested_files'])){

		$nested_files = json_decode(PMXI_Plugin::$session->options['nested_files'], true);

		unset($nested_files[$source]);

		$options = PMXI_Plugin::$session->options;
		$options['nested_files'] = json_encode($nested_files);

		PMXI_Plugin::$session->set('options', $options);

		PMXI_Plugin::$session->save_data();

		exit( json_encode(array(
			'success' => true,
			'nested_files' => $nested_files
		))); 
		die;
	}	

	exit( json_encode(array('success' => false)) ); die;
}