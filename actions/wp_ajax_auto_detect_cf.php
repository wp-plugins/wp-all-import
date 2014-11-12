<?php
function pmxi_wp_ajax_auto_detect_cf(){
	
	$input = new PMXI_Input();
	$fields = $input->post('fields', array());
	$post_type = $input->post('post_type', 'post');
	global $wpdb;

	$ignoreFields = array('_thumbnail_id', '_product_image_gallery', '_default_attributes', '_product_attributes');

	$result = array();

	if ($fields) {
		is_array($fields) or $fields = array($fields);
		foreach ($fields as $field) {
			if ($post_type == 'import_users'){
				$values = $wpdb->get_results("
					SELECT DISTINCT usermeta.meta_value
					FROM ".$wpdb->usermeta." as usermeta
					WHERE usermeta.meta_key='".$field."'
				", ARRAY_A);	
			}
			else{
				$values = $wpdb->get_results("
					SELECT DISTINCT postmeta.meta_value
					FROM ".$wpdb->postmeta." as postmeta
					WHERE postmeta.meta_key='".$field."'
				", ARRAY_A);	
			}

			if ( ! empty($values) ){
				foreach ($values as $key => $value) {
					if ( ! empty($value['meta_value']) and !empty($field) and ! in_array($field, $ignoreFields)) {
						$result[] = array(
							'key' => $field,
							'val' => $value['meta_value'],
							'is_serialized' => is_serialized($value['meta_value'])
						);
						break;
					}					
				}
			}
		}
	}

	if (empty($result)){
		$custom_type = get_post_type_object( $post_type );		
		$msg = sprintf(__('No Custom Fields are present in your database for %s', 'pmxi_plugin'), $custom_type->labels->name);
	}
	elseif (count($result) === 1)
		$msg = sprintf(__('%s field was automatically detected.', 'pmxi_plugin'), count($result));
	else{
		$msg = sprintf(__('%s fields were automatically detected.', 'pmxi_plugin'), count($result));
	}

	exit( json_encode(array('result' => $result, 'msg' => $msg)) );
}