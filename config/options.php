<?php
/**
 * List of plugin optins, contains only default values, actual values are stored in database
 * and can be changed by corresponding wordpress function calls
 */
$config = array(
	"info_api_url" => "http://www.wpallimport.com/adminpanel/update/info.php",
	"history_file_count" => 10000,
	"history_file_age" => 365,
	"highlight_limit" => 10000,
	"upload_max_filesize" => 2048,
	"post_max_size" => 2048,
	"max_input_time" => -1,
	"max_execution_time" => -1,
	"dismiss" => 0,
	"html_entities" => 0
);
