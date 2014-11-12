<?php

function pmxi_wp_ajax_nested_merge(){

	extract($_POST);

	PMXI_Plugin::$session = PMXI_Session::get_instance();	

	/*$nested_file = array(
		'file' => $filePath,
		'source' => $realPath,
		'xpath' => $customXpath,
		'root_element' => $root_element,
		'main_xml_field' => $main_xml_field,
		'child_xml_field' => $child_xml_field
	);		*/	

	$nested_files = (empty(PMXI_Plugin::$session->options['nested_files'])) ? array() : json_decode(PMXI_Plugin::$session->options['nested_files'], true);

	$nested_files[] = $filePath;	

	$options = PMXI_Plugin::$session->options;
	$options['nested_files'] = json_encode($nested_files);

	PMXI_Plugin::$session->set('options', $options);

	PMXI_Plugin::$session->save_data();

	exit( json_encode(array(
		'success' => true, 
		//'source' => $realPath,
		'nested_files' => $nested_files
	)));

	die;
}