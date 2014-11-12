<?php

function pmxi_wp_ajax_test_images(){

	extract($_POST);

	$result = array();
	
	$wp_uploads = wp_upload_dir();	
	$imgs_basedir = $wp_uploads['basedir'] . '/wpallimport/files/';
	$targetDir = $wp_uploads['path']; // . '/wpallimport/uploads';
	$success_images = 0;
	$success_msg = '';

	$failed_msgs = array();

	if ( ! @is_writable($targetDir) )
	{
		$failed_msgs[] = sprintf(__('Uploads folder `%s` is not writable.', 'pmxi_plugin'), $targetDir);	
	}
	else{

		if ( 'no' == $download ){		

			if ( ! empty($imgs) ){

				foreach ($imgs as $img) {			

					if ( preg_match('%^(http|https|ftp|ftps)%i', $img)){
						$failed_msgs[] = sprintf(__('Use image name instead of URL `%s`.', 'pmxi_plugin'), $img);		
						continue;								
					}

					if ( @file_exists($imgs_basedir . $img) ){
						if (@is_readable($imgs_basedir . $img)){
							$success_images++;
						} else{
							$failed_msgs[] = sprintf(__('File `%s` isn\'t readable'), preg_replace('%.*/wp-content%', '/wp-content', $imgs_basedir . $img));
						}
					} 					
					else{
						$failed_msgs[] = sprintf(__('File `%s` doesn\'t exist'), preg_replace('%.*/wp-content%', '/wp-content', $imgs_basedir . $img));
					}
				}			
			}
			if ((int)$success_images === 1)
				$success_msg = sprintf(__('%d image was successfully retrieved from `%s/wpallimport/files`', 'pmxi_plugin'), $success_images, preg_replace('%.*/wp-content%', '/wp-content', $wp_uploads['basedir']));		
			elseif ((int)$success_images > 1)
				$success_msg = sprintf(__('%d images were successfully retrieved from `%s/wpallimport/files`', 'pmxi_plugin'), $success_images, preg_replace('%.*/wp-content%', '/wp-content', $wp_uploads['basedir']));		
		}
		else {

			$start = time();
			if ( ! empty($imgs) ){

				foreach ($imgs as $img) {	

					if ( ! preg_match('%^(http|https|ftp|ftps)%i', $img)){
						$failed_msgs[] = sprintf(__('URL `%s` is not valid.', 'pmxi_plugin'), $img);		
						continue;								
					}
					
					$image_name = wp_unique_filename($targetDir, 'test');
					$image_filepath = $targetDir . '/' . $image_name;

					$request = get_file_curl($img, $image_filepath);

					if ( (is_wp_error($request) or $request === false) and ! @file_put_contents($image_filepath, @file_get_contents($img))) {
						$failed_msgs[] = (is_wp_error($request)) ? $request->get_error_message() : sprintf(__('File `%s` cannot be saved locally', 'pmxi_plugin'), $img);										
					} elseif( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
						$failed_msgs[] = sprintf(__('File `%s` is not a valid image.', 'pmxi_plugin'), $img);										
					} else {
						$success_images++;											
					}						
					@unlink($image_filepath);
				}
			}
			$time = time() - $start;

			if ((int)$success_images === 1)
				$success_msg = sprintf(__('%d image was successfully downloaded in %s seconds', 'pmxi_plugin'), $success_images, number_format($time, 2));		
			elseif ((int)$success_images > 1)
				$success_msg = sprintf(__('%d images were successfully downloaded in %s seconds', 'pmxi_plugin'), $success_images, number_format($time, 2));		
		}	
	}

	exit(json_encode(array(		
		'success_images' => $success_images,
		'success_msg' => $success_msg,
		'failed_msgs' => $failed_msgs
	))); die;

}