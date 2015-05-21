<?php
/**
 * API
 * 
 * Generates html code for fields
 * 
 * 
 * @package default
 * @author Max Tsiplyakov
 */
class PMXI_API 
{
	/**
	 * Function for generating html code for fields
	 * @param string $field_type simple, enum or textarea
	 * @param string $label field label
	 * @param array $params contains field params like tooltip, enum_values, mapping, field_name, field_value	 
	 */
	public static function add_field( $field_type = 'simple', $label = '', $params = array()){
		
		$params += array(
			'tooltip' => '',
			'enum_values' => array(),
			'mapping' => false,
			'field_key' => '',
			'mapping_rules' => array(),
			'xpath' => '',
			'field_name' => '',
			'field_value' => '',
			'addon_prefix' => ''
		);

		ob_start();
		if ($label != ""){
			?>
			<label for="<?php echo sanitize_title($params['field_name']); ?>"><?php echo $label;?></label>			
			<?php
		}
		if ( ! empty($params['tooltip'])){
			?>
			<a href="#help" class="wpallimport-help" title="<?php echo $params['tooltip']; ?>" style="position: relative; top: -2px;">?</a>
			<?php
		}
		?>
		<div class="input">
		<?php
		switch ($field_type){
			case 'simple':
				?>
				<input type="text" name="<?php echo $params['field_name']; ?>" id="<?php echo sanitize_title($params['field_name']); ?>" value="<?php echo $params['field_value']; ?>" style="width:100%;"/>
				<?php
				break;
			case 'enum':
				?>
				
				<?php foreach ($params['enum_values'] as $key => $value):?>
				<div class="form-field wpallimport-radio-field">
					<input type="radio" id="<?php echo sanitize_title($params['field_name']); ?>_<?php echo $key; ?>" class="switcher" name="<?php echo $params['field_name']; ?>" value="<?php echo $key; ?>" <?php echo $key == $params['field_value'] ? 'checked="checked"': '' ?>/>
					<label for="<?php echo sanitize_title($params['field_name']); ?>_<?php echo $key; ?>"><?php echo $value; ?></label>
				</div>
				<?php endforeach;?>							
				<div class="form-field wpallimport-radio-field">
					<input type="radio" id="<?php echo sanitize_title($params['field_name']); ?>_xpath" class="switcher" name="<?php echo $params['field_name']; ?>" value="xpath" <?php echo 'xpath' == $params['field_value'] ? 'checked="checked"': '' ?>/>
					<label for="<?php echo sanitize_title($params['field_name']); ?>_xpath"><?php _e('Set with XPath', 'wp_all_import_plugin' )?></label>
					<span class="wpallimport-clear"></span>
					<div class="switcher-target-<?php echo sanitize_title($params['field_name']); ?>_xpath set_with_xpath">
						<span class="wpallimport-slide-content" style="padding-left:0px;">
							<table class="form-table custom-params" style="max-width:none; border:none;">
								<tr class="form-field">
									<td class="wpallimport-enum-input-wrapper">
										<input type="text" class="smaller-text" name="<?php echo $params['addon_prefix'];?>[xpaths][<?php echo $params['field_key']; ?>]" value="<?php echo esc_attr($params['xpath']) ?>"/>	
									</td>
									<td class="action">
										<?php if ($params['mapping']): ?>

										<?php $custom_mapping_rules = (!empty($params['mapping_rules'])) ? json_decode($params['mapping_rules'], true) : false; ?>
										
										<div class="input wpallimport-custom-fields-actions">
											<a href="javascript:void(0);" class="wpallimport-cf-options"><?php _e('Field Options...', 'wp_all_import_plugin'); ?></a>
											<ul id="wpallimport-cf-menu-<?php echo sanitize_title($params['field_name']);?>" class="wpallimport-cf-menu">						
												<li class="<?php echo ( ! empty($custom_mapping_rules) ) ? 'active' : ''; ?>">
													<a href="javascript:void(0);" class="set_mapping pmxi_cf_mapping" rel="cf_mapping_<?php echo sanitize_title($params['field_name']); ?>"><?php _e('Mapping', 'wp_all_import_plugin'); ?></a>
												</li>
											</ul>														
										</div>
										<div id="cf_mapping_<?php echo sanitize_title($params['field_name']); ?>" class="custom_type" rel="mapping" style="display:none;">
											<fieldset>
												<table cellpadding="0" cellspacing="5" class="cf-form-table" rel="cf_mapping_<?php echo sanitize_title($params['field_name']); ?>">
													<thead>
														<tr>
															<td><?php _e('In Your File', 'wp_all_import_plugin') ?></td>
															<td><?php _e('Translated To', 'wp_all_import_plugin') ?></td>
															<td>&nbsp;</td>						
														</tr>
													</thead>
													<tbody>	
														<?php																																	
															if ( ! empty($custom_mapping_rules) and is_array($custom_mapping_rules)){
																
																foreach ($custom_mapping_rules as $key => $value) {

																	$k = $key;

																	if (is_array($value)){
																		$keys = array_keys($value);
																		$k = $keys[0];
																	}

																	?>
																	<tr class="form-field">
																		<td>
																			<input type="text" class="mapping_from widefat" value="<?php echo esc_textarea($k); ?>">
																		</td>
																		<td>
																			<input type="text" class="mapping_to widefat" value="<?php echo esc_textarea((is_array($value)) ? $value[$k] : $value); ?>">
																		</td>
																		<td class="action remove">
																			<a href="#remove" style="right:-10px;"></a>
																		</td>
																	</tr>
																	<?php
																}
															}
															else{
																if ( ! empty($params['enum_values']) and is_array($params['enum_values'])){
																	foreach ($params['enum_values'] as $key => $value){
																	?>
																	<tr class="form-field">
																		<td>
																			<input type="text" class="mapping_from widefat">
																		</td>
																		<td>
																			<input type="text" class="mapping_to widefat" value="<?php echo $key; ?>">
																		</td>
																		<td class="action remove">
																			<a href="#remove" style="right:-10px;"></a>
																		</td>
																	</tr>
																	<?php
																	}
																} else {
																	?>
																	<tr class="form-field">
																		<td>
																			<input type="text" class="mapping_from widefat">
																		</td>
																		<td>
																			<input type="text" class="mapping_to widefat">
																		</td>
																		<td class="action remove">
																			<a href="#remove" style="right:-10px;"></a>
																		</td>
																	</tr>
																	<?php
																}
															}
														?>												
														<tr class="form-field template">
															<td>
																<input type="text" class="mapping_from widefat">
															</td>
															<td>
																<input type="text" class="mapping_to widefat">
															</td>
															<td class="action remove">
																<a href="#remove" style="right:-10px;"></a>
															</td>
														</tr>
														<tr>
															<td colspan="3">
																<a href="javascript:void(0);" title="<?php _e('Add Another', 'wp_all_import_plugin')?>" class="action add-new-key add-new-entry"><?php _e('Add Another', 'wp_all_import_plugin') ?></a>
															</td>
														</tr>
														<tr>																										
															<td colspan="3">
																<div class="wrap" style="position:relative;">
																	<a class="save_popup save_mr" href="javascript:void(0);"><?php _e('Save Rules', 'wp_all_import_plugin'); ?></a>
																</div>
															</td>
														</tr>
													</tbody>
												</table>
												<input type="hidden" class="pmre_mapping_rules" name="<?php echo $params['addon_prefix'];?>[mapping][<?php echo $params['field_key']; ?>]" value="<?php if (!empty($params['mapping_rules'])) echo esc_html($params['mapping_rules']); ?>"/>
											</fieldset>
										</div>
										<?php endif; ?>
									</td>
								</tr>
							</table>								
						</span>
					</div>
				</div>																
				<?php
				break;
			case 'textarea':
				?>
				<textarea name="<?php echo $params['field_name']; ?>" id="<?php echo sanitize_title($params['field_name']); ?>" class="rad4 newline" style="height: 70px;margin: 5px 0;padding-top: 5px;width: 70%;"><?php echo $params['field_value']; ?></textarea>
				<?php
				break;
			case 'image':
				?>
				<div class="input">
					<div class="input" style="margin: 0px;">
						<input type="radio" name="<?php echo $params['addon_prefix'];?>[download_image][<?php echo $params['field_key'];?>]" value="yes" id="<?php echo sanitize_title($params['field_name']); ?>_yes" <?php echo ("yes" == $params['download_image']) ? 'checked="checked"' : '';?>/>
						<label for="<?php echo sanitize_title($params['field_name']); ?>_yes"><?php _e('Download image hosted elsewhere'); ?></label>
						<a href="#help" class="wpallimport-help" title="<?php _e('http:// or https://', 'wp_all_import_plugin') ?>" style="position: relative; top: -2px;">?</a>
					</div>
					<div class="input" style="margin: 0px;">
						<?php $wp_uploads = wp_upload_dir(); ?>																					
						<input type="radio" name="<?php echo $params['addon_prefix'];?>[download_image][<?php echo $params['field_key'];?>]" value="no" id="<?php echo sanitize_title($params['field_name']); ?>_no" <?php echo ("yes" != $params['download_image']) ? 'checked="checked"' : '';?>/>
						<label for="<?php echo sanitize_title($params['field_name']); ?>_no"><?php printf(__('Use image(s) currently uploaded in %s', 'wp_all_import_plugin'), $wp_uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::FILES_DIRECTORY . DIRECTORY_SEPARATOR); ?></label>
					</div>						
					<div class="input">						
						<input type="text" name="<?php echo $params['field_name']; ?>" style="width:100%;" placeholder="" value="<?php echo esc_attr($params['field_value']); ?>"/>
					</div>										
				</div>
				<?php
				break;
		}
		?>
		</div>
		<?php
		echo ob_get_clean();
	}

	public static function upload_image($pid, $img_url, $download_images, $logger, $create_image = false, $image_name = ""){

		$url = str_replace(" ", "%20", trim($img_url));
		$bn  = preg_replace('/[\\?|&].*/', '', basename($url));

		if ($image_name == ""){
			$img_ext = pmxi_getExtensionFromStr($url);
			$default_extension = pmxi_getExtension($bn);
			if ($img_ext == "") $img_ext = pmxi_get_remote_image_ext($url);
			$image_name = urldecode(sanitize_file_name(($img_ext) ? str_replace("." . $default_extension, "", $bn) : $bn)) . (("" != $img_ext) ? '.' . $img_ext : '');
		}

		$uploads   = wp_upload_dir();
		$targetDir = $uploads['path'];
		$targetUrl = $uploads['url'];
		$download_image = true;
		$result = false;

		global $wpdb;

		$attch = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $wpdb->posts . " WHERE (post_title = %s OR post_title = %s) AND post_type = %s;", $image_name, preg_replace('/\\.[^.\\s]{3,4}$/', '', $image_name), "attachment" ) );

		if ( $attch != null ){			

			return $attch->ID;

		}	

		$image_filename = wp_unique_filename($targetDir, $image_name);
		$image_filepath = $targetDir . '/' . $image_filename;		

		// do not download images
		if ( "yes" != $download_images ){					

			$image_filename = $image_name;
			$image_filepath = $targetDir . '/' . $image_filename;									
																																																																
			$wpai_uploads = $uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::FILES_DIRECTORY . DIRECTORY_SEPARATOR;
			$wpai_image_path = $wpai_uploads . str_replace('%20', ' ', $url);

			$logger and call_user_func($logger, sprintf(__('- Searching for existing image `%s` in `%s` folder', 'wp_all_import_plugin'), $wpai_image_path, $wpai_uploads));

			if ( @file_exists($wpai_image_path) and @copy( $wpai_image_path, $image_filepath )){
				$download_image = false;																				
				if( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
					$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'wp_all_import_plugin'), $image_filepath));					
					@unlink($image_filepath);
				} else {
					$logger and call_user_func($logger, sprintf(__('- Image `%s` has been successfully founded', 'wp_all_import_plugin'), $wpai_image_path));
					$result = true;
				}
			}													
		}	

		if ($download_image){
			
			$logger and call_user_func($logger, sprintf(__('- Downloading image from `%s`', 'wp_all_import_plugin'), $url));

			$request = get_file_curl($url, $image_filepath);

			if ( (is_wp_error($request) or $request === false) and ! @file_put_contents($image_filepath, @file_get_contents($url))) {
				@unlink($image_filepath); // delete file since failed upload may result in empty file created
			} elseif( ($image_info = @getimagesize($image_filepath)) and in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
				$logger and call_user_func($logger, sprintf(__('- Image `%s` has been successfully downloaded', 'wp_all_import_plugin'), $url));									
				$result = true;
			}																	

			if (!$result){
				$url = str_replace(" ", "%20", trim(pmxi_convert_encoding($img_url)));
				
				$request = get_file_curl($url, $image_filepath);

				if ( (is_wp_error($request) or $request === false) and ! @file_put_contents($image_filepath, @file_get_contents($url))) {
					$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: File %s cannot be saved locally as %s', 'wp_all_import_plugin'), $url, $image_filepath));				
					@unlink($image_filepath); // delete file since failed upload may result in empty file created										
				} elseif( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
					$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'wp_all_import_plugin'), $url));		
					@unlink($image_filepath);
				} else {				
					$logger and call_user_func($logger, sprintf(__('- Image `%s` has been successfully downloaded', 'wp_all_import_plugin'), $url));											
					$result = true;
				}
			}			
		}

		if ($create_image and $result){

			$logger and call_user_func($logger, sprintf(__('- Creating an attachment for image `%s`', 'wp_all_import_plugin'), $targetUrl . '/' . $image_filename));	

			$attachment = array(
				'post_mime_type' => image_type_to_mime_type($image_info[2]),
				'guid' => $targetUrl . '/' . $image_filename,
				'post_title' => $image_filename,
				'post_content' => '',				
			);
			if (($image_meta = wp_read_image_metadata($image_filepath))) {
				if (trim($image_meta['title']) && ! is_numeric(sanitize_title($image_meta['title'])))
					$attachment['post_title'] = $image_meta['title'];
				if (trim($image_meta['caption']))
					$attachment['post_content'] = $image_meta['caption'];
			}

			$attid = wp_insert_attachment($attachment, $image_filepath, $pid);

			if (is_wp_error($attid)) {
				$logger and call_user_func($logger, __('- <b>WARNING</b>', 'wp_all_import_plugin') . ': ' . $attid->get_error_message());			
				return false;
			} else {
				// you must first include the image.php file
				// for the function wp_generate_attachment_metadata() to work
				require_once(ABSPATH . 'wp-admin/includes/image.php');
				wp_update_attachment_metadata($attid, wp_generate_attachment_metadata($attid, $image_filepath));																
				$logger and call_user_func($logger, sprintf(__('- Attachment has been successfully created for image `%s`', 'wp_all_import_plugin'), $targetUrl . '/' . $image_filename));
				return $attid;											
			}

		}
		else return $result;		
	}	
}
