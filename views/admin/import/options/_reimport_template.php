<tr>
	<td colspan="3" style="padding-top:20px;">
		<fieldset class="optionsset">
			<legend><?php _e('Record Matching','pmxi_plugin');?></legend>		
			<div class="input" style="margin-bottom:15px;">
				<input type="radio" id="auto_matching_<?php echo $entry; ?>" class="switcher" name="duplicate_matching" value="auto" <?php echo 'manual' != $post['duplicate_matching'] ? 'checked="checked"': '' ?>/>
				<label for="auto_matching_<?php echo $entry; ?>"><?php _e('Automatic Record Matching', 'pmxi_plugin' )?></label><br>
				<div class="switcher-target-auto_matching_<?php echo $entry; ?>"  style="padding-left:17px;">
					<div class="input">
						<label><?php _e("Unique key"); ?></label>
						<input type="text" class="smaller-text" name="unique_key" style="width:300px;" value="<?php echo esc_attr($post['unique_key']) ?>" <?php echo  ! ($this->isWizard && $update_previous->isEmpty()) ? 'disabled="disabled"' : '' ?>/>
						<a href="#help" class="help" title="<?php _e('Specify something that is unique for all records. If posts are being updated and not just created during a brand new import, the problem is that the value of the unique key is not unique.', 'pmxi_plugin') ?>">?</a>
					</div>
				</div>
				<input type="radio" id="manual_matching_<?php echo $entry; ?>" class="switcher" name="duplicate_matching" value="manual" <?php echo 'manual' == $post['duplicate_matching'] ? 'checked="checked"': '' ?>/>
				<label for="manual_matching_<?php echo $entry; ?>"><?php _e('Manual Record Matching', 'pmxi_plugin' )?></label>
				<a href="#help" class="help" title="<?php _e('This allows you to match records by something other than Unique Key.', 'pmxi_plugin') ?>">?</a>
				<div class="switcher-target-manual_matching_<?php echo $entry; ?>" style="padding-left:17px;">
					<div class="input">
						<span style="vertical-align:middle"><?php _e('Match records based on...', 'pmxi_plugin') ?></span><br>
						<input type="radio" id="duplicate_indicator_title_<?php echo $entry; ?>" class="switcher" name="duplicate_indicator" value="title" <?php echo 'title' == $post['duplicate_indicator'] ? 'checked="checked"': '' ?>/>
						<label for="duplicate_indicator_title_<?php echo $entry; ?>"><?php _e('title', 'pmxi_plugin' )?></label><br>
						<input type="radio" id="duplicate_indicator_content_<?php echo $entry; ?>" class="switcher" name="duplicate_indicator" value="content" <?php echo 'content' == $post['duplicate_indicator'] ? 'checked="checked"': '' ?>/>
						<label for="duplicate_indicator_content_<?php echo $entry; ?>"><?php _e('content', 'pmxi_plugin' )?></label><br>
						<input type="radio" id="duplicate_indicator_custom_field_<?php echo $entry; ?>" class="switcher" name="duplicate_indicator" value="custom field" <?php echo 'custom field' == $post['duplicate_indicator'] ? 'checked="checked"': '' ?>/>
						<label for="duplicate_indicator_custom_field_<?php echo $entry; ?>"><?php _e('custom field', 'pmxi_plugin' )?></label><br>
						<span class="switcher-target-duplicate_indicator_custom_field_<?php echo $entry; ?>" style="vertical-align:middle; padding-left:17px;">
							<?php _e('Name', 'pmxi_plugin') ?>
							<input type="text" name="custom_duplicate_name" value="<?php echo esc_attr($post['custom_duplicate_name']) ?>" />
							<?php _e('Value', 'pmxi_plugin') ?>
							<input type="text" name="custom_duplicate_value" value="<?php echo esc_attr($post['custom_duplicate_value']) ?>" />
						</span>
					</div>
				</div>
			</div>
			<hr>
			<div class="input">
				<input type="hidden" name="not_create_records" value="0" />
				<input type="checkbox" id="not_create_records_<?php echo $entry; ?>" name="not_create_records" value="1" <?php echo $post['not_create_records'] ? 'checked="checked"' : '' ?> />
				<label for="not_create_records_<?php echo $entry; ?>"><?php _e('Do Not Add New Records', 'pmxi_plugin') ?></label>
			</div>
			<div class="input">
				<input type="hidden" name="is_delete_missing" value="0" />
				<input type="checkbox" id="is_delete_missing_<?php echo $entry; ?>" name="is_delete_missing" value="1" <?php echo $post['is_delete_missing'] ? 'checked="checked"': '' ?> class="switcher"/>
				<label for="is_delete_missing_<?php echo $entry; ?>"><?php _e('Delete missing records', 'pmxi_plugin') ?></label>
				<a href="#help" class="help" title="<?php _e('Check this option if you want to delete posts from the previous import operation which are not found among newly imported set.', 'pmxi_plugin') ?>">?</a>
			</div>
			<div class="switcher-target-is_delete_missing_<?php echo $entry; ?>" style="padding-left:17px;">
				<div class="input">
					<input type="hidden" name="is_keep_attachments" value="0" />
					<input type="checkbox" id="is_keep_attachments_<?php echo $entry; ?>" name="is_keep_attachments" value="1" <?php echo $post['is_keep_attachments'] ? 'checked="checked"': '' ?> />
					<label for="is_keep_attachments_<?php echo $entry; ?>"><?php _e('Keep attachments when records removed', 'pmxi_plugin') ?></label>
					<a href="#help" class="help" title="<?php _e('Check this option if you want attachments like featured image to be kept in media library after parent post or page is removed or replaced during reimport operation.', 'pmxi_plugin') ?>">?</a>
				</div>
			</div>
			<div class="input">
				<input type="hidden" name="is_update_missing_cf" value="0" />
				<input type="checkbox" id="is_update_missing_cf_<?php echo $entry; ?>" name="is_update_missing_cf" value="1" <?php echo $post['is_update_missing_cf'] ? 'checked="checked"': '' ?> class="switcher"/>
				<label for="is_update_missing_cf_<?php echo $entry; ?>"><?php _e('Instead of Deleting Missing Records, Set Custom Field', 'pmxi_plugin') ?></label>
				<a href="#help" class="help" title="<?php _e('Check this option if you want to update posts custom fields from the previous import operation which are not found among newly imported set.', 'pmxi_plugin') ?>">?</a>
			</div>			
			<div class="switcher-target-is_update_missing_cf_<?php echo $entry; ?>" style="padding-left:17px;">
				<div class="input">
					<?php _e('Name', 'pmxi_plugin') ?>
					<input type="text" name="update_missing_cf_name" value="<?php echo esc_attr($post['update_missing_cf_name']) ?>" /><br>
					<?php _e('Value', 'pmxi_plugin') ?>
					<input type="text" name="update_missing_cf_value" value="<?php echo esc_attr($post['update_missing_cf_value']) ?>" />									
				</div>
			</div>
			<div class="input">
				<input type="radio" id="is_keep_former_posts_<?php echo $entry; ?>" name="is_keep_former_posts" value="yes" <?php echo "yes" == $post['is_keep_former_posts'] ? 'checked="checked"': '' ?> class="switcher" />
				<label for="is_keep_former_posts_<?php echo $entry; ?>"><?php _e('Do not update already existing records', 'pmxi_plugin') ?></label> <br>
				<input type="radio" id="is_not_keep_former_posts_<?php echo $entry; ?>" name="is_keep_former_posts" value="no" <?php echo "yes" != $post['is_keep_former_posts'] ? 'checked="checked"': '' ?> class="switcher" />
				<label for="is_not_keep_former_posts_<?php echo $entry; ?>"><?php _e('Update existing records', 'pmxi_plugin') ?></label>
				<div class="switcher-target-is_not_keep_former_posts_<?php echo $entry; ?>" style="padding-left:17px;">
					<div class="input">
						<input type="hidden" name="is_keep_status" value="0" />
						<input type="checkbox" id="is_keep_status_<?php echo $entry; ?>" name="is_keep_status" value="1" <?php echo $post['is_keep_status'] ? 'checked="checked"': '' ?> />
						<label for="is_keep_status_<?php echo $entry; ?>"><?php _e('Keep status', 'pmxi_plugin') ?></label>
						<a href="#help" class="help" title="<?php _e('Check this option if you do not want previously imported posts to change their publish status or being restored from Trash.', 'pmxi_plugin') ?>">?</a>
					</div>
					<div class="input">
						<input type="hidden" name="is_keep_title" value="0" />
						<input type="checkbox" id="is_keep_title_<?php echo $entry; ?>" name="is_keep_title" value="1" <?php echo $post['is_keep_title'] ? 'checked="checked"': '' ?> />
						<label for="is_keep_title_<?php echo $entry; ?>"><?php _e('Keep title', 'pmxi_plugin') ?></label>

					</div>
					<div class="input">
						<input type="hidden" name="is_keep_excerpt" value="0" />
						<input type="checkbox" id="is_keep_excerpt_<?php echo $entry; ?>" name="is_keep_excerpt" value="1" <?php echo $post['is_keep_excerpt'] ? 'checked="checked"': '' ?> />
						<label for="is_keep_excerpt_<?php echo $entry; ?>"><?php _e('Keep excerpt', 'pmxi_plugin') ?></label>
					</div>
					<div class="input">
						<input type="hidden" name="is_keep_dates" value="0" />
						<input type="checkbox" id="is_keep_dates_<?php echo $entry; ?>" name="is_keep_dates" value="1" <?php echo $post['is_keep_dates'] ? 'checked="checked"': '' ?> />
						<label for="is_keep_dates_<?php echo $entry; ?>"><?php _e('Keep dates', 'pmxi_plugin') ?></label>
					</div>
					<div class="input">
						<input type="hidden" name="is_keep_menu_order" value="0" />
						<input type="checkbox" id="is_keep_menu_order_<?php echo $entry; ?>" name="is_keep_menu_order" value="1" <?php echo $post['is_keep_menu_order'] ? 'checked="checked"': '' ?> />
						<label for="is_keep_menu_order_<?php echo $entry; ?>"><?php _e('Keep menu order', 'pmxi_plugin') ?></label>
					</div>
					<div class="input">
						<input type="hidden" name="is_keep_parent" value="0" />
						<input type="checkbox" id="is_keep_parent_<?php echo $entry; ?>" name="is_keep_parent" value="1" <?php echo $post['is_keep_parent'] ? 'checked="checked"': '' ?> />
						<label for="is_keep_parent_<?php echo $entry; ?>"><?php _e('Keep parent post', 'pmxi_plugin') ?></label>
					</div>
					<div class="input">
						<input type="hidden" name="is_keep_content" value="0" />
						<input type="checkbox" id="is_keep_content_<?php echo $entry; ?>" name="is_keep_content" value="1" <?php echo $post['is_keep_content'] ? 'checked="checked"': '' ?> />
						<label for="is_keep_content_<?php echo $entry; ?>"><?php _e('Keep content', 'pmxi_plugin') ?></label>
					</div>
					<div class="input">
						<input type="hidden" name="is_keep_categories" value="0" />
						<input type="checkbox" id="is_keep_categories_<?php echo $entry; ?>" name="is_keep_categories" value="1" class="switcher" <?php echo $post['is_keep_categories'] ? 'checked="checked"': '' ?> />
						<label for="is_keep_categories_<?php echo $entry; ?>"><?php _e('Keep categories, tags and taxonomies', 'pmxi_plugin') ?></label>
						<div class="switcher-target-is_keep_categories_<?php echo $entry; ?>" style="padding-left:17px;">
							<div class="input" style="margin-bottom:3px;">
								<input type="hidden" name="is_add_newest_categories" value="0" />
								<input type="checkbox" id="is_add_newest_categories_<?php echo $entry; ?>" name="is_add_newest_categories" value="1" <?php echo $post['is_add_newest_categories'] ? 'checked="checked"': '' ?> />
								<label for="is_add_newest_categories_<?php echo $entry; ?>" style="position:relative; top:1px;"><?php _e('Add new categories and taxonomies', 'pmxi_plugin') ?></label>
							</div>
						</div>
					</div>
					<div class="input">
						<input type="hidden" name="is_keep_attachments_on_update" value="0" />
						<input type="checkbox" id="is_keep_attachments_on_update_<?php echo $entry; ?>" name="is_keep_attachments_on_update" value="1" <?php echo $post['is_keep_attachments_on_update'] ? 'checked="checked"': '' ?> />
						<label for="is_keep_attachments_on_update_<?php echo $entry; ?>"><?php _e('Keep attachments', 'pmxi_plugin') ?></label>
					</div>
					<div class="input">
						<input type="hidden" name="is_keep_images" value="0" />
						<input type="checkbox" id="is_keep_images_<?php echo $entry; ?>" name="is_keep_images" value="1" <?php echo $post['is_keep_images'] ? 'checked="checked"': '' ?> />
						<label for="is_keep_images_<?php echo $entry; ?>"><?php _e('Do not import images', 'pmxi_plugin') ?></label>
						<a href="#help" class="help" title="<?php _e('This will keep the featured image if it exists, so you could modify the post image manually, and then do a reimport, and it would not overwrite the manually modified post image.', 'pmxi_plugin') ?>">?</a>
					</div>
					<div class="input">
						<input type="hidden" name="no_create_featured_image" value="0" />
						<input type="checkbox" id="no_create_featured_image_<?php echo $entry; ?>" name="no_create_featured_image" value="1" <?php echo $post['no_create_featured_image'] ? 'checked="checked"': '' ?> />
						<label for="no_create_featured_image_<?php echo $entry; ?>"><?php _e('Only keep images for posts that already have images.', 'pmxi_plugin') ?></label>
						<a href="#help" class="help" title="<?php _e('This option will keep images for posts that already have images, and import images for posts that do not already have images.', 'pmxi_plugin') ?>">?</a>
					</div>
					<div class="input">
						<input type="hidden" name="keep_custom_fields" value="0" />
						<input type="checkbox" id="keep_custom_fields_<?php echo $entry; ?>" name="keep_custom_fields" value="1" <?php echo $post['keep_custom_fields'] ? 'checked="checked"': '' ?>  class="switcher switcher-reversed"/>
						<label for="keep_custom_fields_<?php echo $entry; ?>"><?php _e('Keep custom fields', 'pmxi_plugin') ?></label>
						<a href="#help" class="help" title="<?php _e('If Keep Custom Fields box is checked, it will keep all Custom Fields, and add any new Custom Fields specified in Custom Fields section, as long as they do not overwrite existing fields. If \'Only keep this Custom Fields\' is specified, it will only keep the specified fields.', 'pmxi_plugin') ?>">?</a>
						<div class="keep_except" style="padding-left:17px; <?php if (!$post['keep_custom_fields'] ):?>display:none;<?php endif;?>" >
							<div class="input">
								<label for="keep_custom_fields_except"><?php _e('Keep all Custom Fields, except for the fields specified for update <small>(separate field names with commas)</small>', 'pmxi_plugin') ?></label>
								<input type="text" id="keep_custom_fields_except" name="keep_custom_fields_except" style="width:100%;" value="<?php echo esc_attr($post['keep_custom_fields_except']) ?>" />
							</div>
						</div>
					</div>
					<div class="switcher-target-keep_custom_fields_<?php echo $entry; ?>" style="padding-left:17px;">
						<div class="input">
							<label for="keep_custom_fields_specific"><?php _e('Only Keep These Custom Fields <small>(separate field names with commas)</small>', 'pmxi_plugin') ?></label>
							<input type="text" id="keep_custom_fields_specific" name="keep_custom_fields_specific" style="width:100%;" value="<?php echo esc_attr($post['keep_custom_fields_specific']) ?>" />
						</div>
					</div>
				</div>
			</div>				
		</fieldset>
	</td>
</tr>