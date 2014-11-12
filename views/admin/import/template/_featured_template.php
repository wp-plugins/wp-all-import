<div class="wpallimport-collapsed closed wpallimport-section wpallimport-featured-images">
	<div class="wpallimport-content-section" style="padding-bottom: 0;">
		<div class="wpallimport-collapsed-header" style="margin-bottom: 15px;">
			<h3><?php _e('Images','pmxi_plugin');?></h3>	
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<?php if ($post_type != "product" or ! class_exists('PMWI_Plugin')):?>
					
					<div class="wpallimport-free-edition-notice" style="text-align:center; margin-top:-15px; margin-bottom: 40px;">
						<a href="http://www.wpallimport.com/upgrade-to-pro/?utm_source=free-plugin&utm_medium=in-plugin&utm_campaign=images" target="_blank" class="upgrade_link"><?php _e('Upgrade to the professional edition of WP All Import to import images.', 'pmxi_plugin');?></a>
					</div>
					
				<?php endif; ?>

				<table class="form-table" style="max-width:none;">
					<tr>
						<td colspan="3">
							<div class="input">
								<div class="input">							
									<input type="radio" name="download_images" value="yes" class="switcher" id="download_images_yes" <?php echo ("yes" == $post['download_images']) ? 'checked="checked"' : '';?>/>
									<label for="download_images_yes"><?php _e('Download image(s) hosted elsewhere'); ?></label>
									<a href="#help" class="wpallimport-help" title="<?php _e('http:// or https://', 'pmxi_plugin') ?>" style="position: relative; top: -2px;">?</a>
								</div>						
								<div class="switcher-target-download_images_yes" style="padding-left:27px;">
									<label for="download_featured_delim"><?php _e('Enter image URL one per line, or separate them with a ', 'pmxi_plugin');?></label>
									<input type="text" class="small" id="download_featured_delim" name="download_featured_delim" value="<?php echo esc_attr($post['download_featured_delim']) ?>" style="width:5%; text-align:center;" />
									<textarea name="download_featured_image" class="newline rad4" style="clear: both; display:block;" placeholder=""><?php echo esc_attr($post['download_featured_image']) ?></textarea>			
								</div>
								<div class="input">
									<?php $wp_uploads = wp_upload_dir(); ?>																					
									<input type="radio" name="download_images" value="no" class="switcher" id="download_images_no" <?php echo ("yes" != $post['download_images']) ? 'checked="checked"' : '';?>/>
									<label for="download_images_no"><?php printf(__('Use image(s) currently uploaded in %s/wpallimport/files/', 'pmxi_plugin'), $wp_uploads['basedir']); ?></label>
								</div>
								<div class="switcher-target-download_images_no" style="padding-left:27px;">
									<label for="featured_delim"><?php _e('Enter image filenames one per line, or separate them with a ', 'pmxi_plugin');?></label>
									<input type="text" class="small" id="featured_delim" name="featured_delim" value="<?php echo esc_attr($post['featured_delim']) ?>" style="width:5%; text-align:center;"/>
									<textarea name="featured_image" class="newline rad4" style="clear: both; display:block; "><?php echo esc_attr($post['featured_image']) ?></textarea>			
								</div>
								<a class="preview_images" href="javascript:void(0);" rel="preview_images"><?php _e('Preview & Test', 'pmxi_plugin'); ?></a>
							</div>
							<h4><?php _e('Featured Image', 'pmxi_plugin'); ?></h4>
							<div class="input" style="margin:3px 0px;">
								<input type="hidden" name="is_featured" value="0" />
								<input type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo $post['is_featured'] ? 'checked="checked"' : '' ?> class="fix_checkbox" />
								<label for="is_featured"><?php _e('Set the first image to the Featured Image (_thumbnail_id)','pmxi_plugin');?> </label>						
							</div>
							<h4><?php _e('Other', 'pmxi_plugin'); ?></h4>
							<div class="input">
								<input type="hidden" name="create_draft" value="no" />
								<input type="checkbox" id="create_draft" name="create_draft" value="yes" <?php echo 'yes' == $post['create_draft'] ? 'checked="checked"' : '' ?> class="fix_checkbox"/>
								<label for="create_draft"><?php _e('If no images are downloaded successfully, create entry as Draft.', 'pmxi_plugin') ?></label>
							</div>																						
						</td>
					</tr>
				</table>
			</div>

			<div class="wpallimport-collapsed closed wpallimport-section">
				<div class="wpallimport-content-section rad0" style="margin:0; border-top:1px solid #ddd; border-bottom: none; border-right: none; border-left: none; background: #f1f2f2;">
					<div class="wpallimport-collapsed-header">
						<h3 style="color:#40acad;"><?php _e('SEO & Advanced Options','pmxi_plugin');?></h3>	
					</div>
					<div class="wpallimport-collapsed-content" style="padding: 0;">
						<div class="wpallimport-collapsed-content-inner">
							<hr>						
							<table class="form-table" style="max-width:none;">
								<tr>
									<td colspan="3">
										<h4><?php _e('Meta Data', 'pmxi_plugin'); ?></h4>
										<div class="input">
											<input type="hidden" name="set_image_meta_title" value="0" />
											<input type="checkbox" id="set_image_meta_title" name="set_image_meta_title" value="1" <?php echo $post['set_image_meta_title'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
											<label for="set_image_meta_title"><?php _e('Set Title(s)','pmxi_plugin');?></label>
											<div class="switcher-target-set_image_meta_title" style="padding-left:23px;">							
												<label for="image_meta_title_delim"><?php _e('Enter one per line, or separate them with a ', 'pmxi_plugin');?></label>
												<input type="text" class="small" id="image_meta_title_delim" name="image_meta_title_delim" value="<?php echo esc_attr($post['image_meta_title_delim']) ?>" style="width:5%; text-align:center;" />
												<p style="margin-bottom:5px;"><?php _e('The first title will be linked to the first image, the second title will be linked to the second image, ...', 'pmxi_plugin');?></p>
												<textarea name="image_meta_title" class="newline rad4"><?php echo esc_attr($post['image_meta_title']) ?></textarea>																				
											</div>
										</div>
										<div class="input">
											<input type="hidden" name="set_image_meta_caption" value="0" />
											<input type="checkbox" id="set_image_meta_caption" name="set_image_meta_caption" value="1" <?php echo $post['set_image_meta_caption'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
											<label for="set_image_meta_caption"><?php _e('Set Caption(s)','pmxi_plugin');?></label>
											<div class="switcher-target-set_image_meta_caption" style="padding-left:23px;">							
												<label for="image_meta_caption_delim"><?php _e('Enter one per line, or separate them with a ', 'pmxi_plugin');?></label>
												<input type="text" class="small" id="image_meta_caption_delim" name="image_meta_caption_delim" value="<?php echo esc_attr($post['image_meta_caption_delim']) ?>" style="width:5%; text-align:center;"/>
												<p style="margin-bottom:5px;"><?php _e('The first caption will be linked to the first image, the second caption will be linked to the second image, ...', 'pmxi_plugin');?></p>
												<textarea name="image_meta_caption" class="newline rad4"><?php echo esc_attr($post['image_meta_caption']) ?></textarea>																				
											</div>
										</div>
										<div class="input">
											<input type="hidden" name="set_image_meta_alt" value="0" />
											<input type="checkbox" id="set_image_meta_alt" name="set_image_meta_alt" value="1" <?php echo $post['set_image_meta_alt'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
											<label for="set_image_meta_alt"><?php _e('Set Alt Text(s)','pmxi_plugin');?></label>
											<div class="switcher-target-set_image_meta_alt" style="padding-left:23px;">							
												<label for="image_meta_alt_delim"><?php _e('Enter one per line, or separate them with a ', 'pmxi_plugin');?></label>
												<input type="text" class="small" id="image_meta_alt_delim" name="image_meta_alt_delim" value="<?php echo esc_attr($post['image_meta_alt_delim']) ?>" style="width:5%; text-align:center;"/>
												<p style="margin-bottom:5px;"><?php _e('The first alt text will be linked to the first image, the second alt text will be linked to the second image, ...', 'pmxi_plugin');?></p>
												<textarea name="image_meta_alt" class="newline rad4"><?php echo esc_attr($post['image_meta_alt']) ?></textarea>																				
											</div>
										</div>
										<div class="input">
											<input type="hidden" name="set_image_meta_description" value="0" />
											<input type="checkbox" id="set_image_meta_description" name="set_image_meta_description" value="1" <?php echo $post['set_image_meta_description'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
											<label for="set_image_meta_description"><?php _e('Set Description(s)','pmxi_plugin');?></label>
											<div class="switcher-target-set_image_meta_description" style="padding-left:23px;">							
												<label for="image_meta_description_delim"><?php _e('Enter one per line, or separate them with a ', 'pmxi_plugin');?></label>
												<input type="text" class="small" id="image_meta_description_delim" name="image_meta_description_delim" value="<?php echo esc_attr($post['image_meta_description_delim']) ?>" style="width:5%; text-align:center;"/>
												<p style="margin-bottom:5px;"><?php _e('The first description will be linked to the first image, the second description will be linked to the second image, ...', 'pmxi_plugin');?></p>
												<textarea name="image_meta_description" class="newline rad4"><?php echo esc_attr($post['image_meta_description']) ?></textarea>																				
											</div>
										</div>

										<h4><?php _e('Files', 'pmxi_plugin'); ?></h4>
										<div id="advanced_options_files">
											<p style="font-style:italic; display:none;"><?php _e('These options only available if Download image(s) hosted elsewhere is selected above.', 'pmxi_plugin'); ?></p>
											<div class="input" style="margin:3px 0px;">
												<input type="hidden" name="auto_rename_images" value="0" />
												<input type="checkbox" id="auto_rename_images" name="auto_rename_images" value="1" <?php echo $post['auto_rename_images'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
												<label for="auto_rename_images"><?php _e('Change image file names to','pmxi_plugin');?> </label>
												<div class="input switcher-target-auto_rename_images" style="padding-left:23px;">
													<input type="text" id="auto_rename_images_suffix" name="auto_rename_images_suffix" value="<?php echo esc_attr($post['auto_rename_images_suffix']) ?>" style="width:480px;"/> 
													<p class="note"><?php _e('Multiple image will have numbers appended, i.e. image-name-1.jpg, image-name-2.jpg '); ?></p>
												</div>
											</div>
											<div class="input" style="margin:3px 0px;">
												<input type="hidden" name="auto_set_extension" value="0" />
												<input type="checkbox" id="auto_set_extension" name="auto_set_extension" value="1" <?php echo $post['auto_set_extension'] ? 'checked="checked"' : '' ?> class="switcher fix_checkbox"/>
												<label for="auto_set_extension"><?php _e('Change image file extensions','pmxi_plugin');?> </label>
												<div class="input switcher-target-auto_set_extension" style="padding-left:23px;">
													<input type="text" id="new_extension" name="new_extension" value="<?php echo esc_attr($post['new_extension']) ?>" placeholder="jpg" style="width:480px;"/>
												</div>
											</div>
										</div>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</div>	
		</div>
	</div>
</div>
<div id="images_hints" style="display:none;">	
	<ul>
		<li><?php _e('WP All Import will automatically ignore elements with blank image URLs/filenames.', 'pmxi_plugin'); ?></li>
		<li><?php _e('WP All Import must download the images to your server. You can\'t have images in a Gallery that are referenced by external URL. That\'s just how WordPress works.', 'pmxi_plugin'); ?></li>
		<li><?php printf(__('Importing a variable number of images can be done using a <a href="%s" target="_blank">FOREACH LOOP</a>', 'pmxi_plugin'), 'http://www.wpallimport.com/documentation/step-3/template-syntax/'); ?></li>
		<li><?php printf(__('For more information check out our <a href="%s" target="_blank">comprehensive documentation</a>', 'pmxi_plugin'), 'http://www.wpallimport.com/documentation/'); ?></li>
	</ul>
</div>