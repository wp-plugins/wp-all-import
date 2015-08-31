<?php

function wp_all_import_sanitize_filename($filename) {
	$filename_parts = explode('.',$filename);
	if ( ! empty($filename_parts) and count($filename_parts) > 1){
		$ext = end($filename_parts);
		// Replace all weird characters
		$sanitized = sanitize_file_name(substr($filename, 0, -(strlen($ext)+1)));
		// Replace dots inside filename
		$sanitized = str_replace('.','-', $sanitized);
		return strtolower($sanitized . '.' . $ext);
	}
	return $filename;
}