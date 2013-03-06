<tr>
	<td colspan="3" style="border-bottom:1px solid #ccc;">
		<div class="col2" style="margin-bottom:20px;">
			<h3><?php _e('Post Status', 'pmxi_plugin') ?></h3>
			<div>
				<div class="input">
					<input type="radio" id="status_publish_<?php echo $entry; ?>" name="status" value="publish" <?php echo 'publish' == $post['status'] ? 'checked="checked"' : '' ?> />
					<label for="status_publish_<?php echo $entry; ?>"><?php _e('Published', 'pmxi_plugin') ?></label>
				</div>
				<div class="input">
					<input type="radio" id="status_draft_<?php echo $entry; ?>" name="status" value="draft" <?php echo 'draft' == $post['status'] ? 'checked="checked"' : '' ?> />
					<label for="status_draft_<?php echo $entry; ?>"><?php _e('Draft', 'pmxi_plugin') ?></label>
				</div>
				<br>
				<div class="input">
					<input type="hidden" name="comment_status" value="closed" />
					<input type="checkbox" id="comment_status_<?php echo $entry; ?>" name="comment_status" value="open" <?php echo 'open' == $post['comment_status'] ? 'checked="checked"' : '' ?> />
					<label for="comment_status_<?php echo $entry; ?>"><?php _e('Allow Comments', 'pmxi_plugin') ?></label>
				</div>
				<div class="input">
					<input type="hidden" name="ping_status" value="closed" />
					<input type="checkbox" id="ping_status_<?php echo $entry; ?>" name="ping_status" value="open" <?php echo 'open' == $post['ping_status'] ? 'checked="checked"' : '' ?> />
					<label for="ping_status_<?php echo $entry; ?>"><?php _e('Allow Trackbacks and Pingbacks', 'pmxi_plugin') ?></label>
				</div>
			</div>
		</div>
		<div class="col2">
			<h3><?php _e('Post Dates', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('Use any format supported by the PHP <b>strtotime</b> function. That means pretty much any human-readable date will work.', 'pmxi_plugin') ?>">?</a></h3>
			<div class="input">
				<input type="radio" id="date_type_specific_<?php echo $entry; ?>" class="switcher" name="date_type" value="specific" <?php echo 'random' != $post['date_type'] ? 'checked="checked"' : '' ?> />
				<label for="date_type_specific_<?php echo $entry; ?>">
					<?php _e('As specified', 'pmxi_plugin') ?>
				</label>
				<span class="switcher-target-date_type_specific_<?php echo $entry; ?>" style="vertical-align:middle">
					<input type="text" class="datepicker" name="date" value="<?php echo esc_attr($post['date']) ?>" style="width:40%;"/>
				</span>
			</div>
			<div class="input">
				<input type="radio" id="date_type_random_<?php echo $entry; ?>" class="switcher" name="date_type" value="random" <?php echo 'random' == $post['date_type'] ? 'checked="checked"' : '' ?> />
				<label for="date_type_random_<?php echo $entry; ?>">
					<?php _e('Random dates', 'pmxi_plugin') ?><a href="#help" class="help" title="<?php _e('Posts will be randomly assigned dates in this range. WordPress ensures posts with dates in the future will not appear until their date has been reached.', 'pmxi_plugin') ?>">?</a>
				</label> <br>
				<span class="switcher-target-date_type_random_<?php echo $entry; ?>" style="vertical-align:middle">
					<input type="text" class="datepicker" name="date_start" value="<?php echo esc_attr($post['date_start']) ?>" />
					<?php _e('and', 'pmxi_plugin') ?>
					<input type="text" class="datepicker" name="date_end" value="<?php echo esc_attr($post['date_end']) ?>" />
				</span>
			</div>
		</div>
		<!--div class="col3 last">
			<div class="input">
				<input type="hidden" name="is_duplicates" value="0" />
				<input type="checkbox" id="is_duplicates_<?php echo $entry; ?>" class="switcher" name="is_duplicates" value="1" <?php echo $post['is_duplicates'] ? 'checked="checked"': '' ?>/>
				<label for="is_duplicates_<?php echo $entry; ?>"><?php _e('Check for duplicates', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('This option allows you to specify action for articles being imported which have duplicates in WordPress database.<br /><br /><b>Important</b>: This option applies only to pages or posts not associated with current import. To manage overwrite rules for records previously created by import operation currently being updated please see `Reimport / Update Options` section below.', 'pmxi_plugin') ?>">?</a></label>
				<div class="switcher-target-is_duplicates_<?php echo $entry; ?>">
					<div class="input">
						<span style="vertical-align:middle"><?php _e('Determine duplicates by', 'pmxi_plugin') ?></span><br>
						<input type="radio" id="duplicate_indicator_title_<?php echo $entry; ?>" class="switcher" name="duplicate_indicator" value="title" <?php echo 'title' == $post['duplicate_indicator'] ? 'checked="checked"': '' ?>/>
						<label for="duplicate_indicator_title_<?php echo $entry; ?>"><?php _e('title', 'pmxi_plugin' )?></label><br>
						<input type="radio" id="duplicate_indicator_content_<?php echo $entry; ?>" class="switcher" name="duplicate_indicator" value="content" <?php echo 'content' == $post['duplicate_indicator'] ? 'checked="checked"': '' ?>/>
						<label for="duplicate_indicator_content_<?php echo $entry; ?>"><?php _e('content', 'pmxi_plugin' )?></label><br>
						<input type="radio" id="duplicate_indicator_custom_field_<?php echo $entry; ?>" class="switcher" name="duplicate_indicator" value="custom field" <?php echo 'custom field' == $post['duplicate_indicator'] ? 'checked="checked"': '' ?>/>
						<label for="duplicate_indicator_custom_field_<?php echo $entry; ?>"><?php _e('custom field', 'pmxi_plugin' )?></label><br>
						<span class="switcher-target-duplicate_indicator_custom_field_<?php echo $entry; ?>" style="vertical-align:middle">
							<?php _e('Name', 'pmxi_plugin') ?>
							<input type="text" name="custom_duplicate_name" value="<?php echo esc_attr($post['custom_duplicate_name']) ?>" /><br>
							<?php _e('Value', 'pmxi_plugin') ?>
							<input type="text" name="custom_duplicate_value" value="<?php echo esc_attr($post['custom_duplicate_value']) ?>" />
						</span>
					</div>
					<div class="input">
						<span style="vertical-align:middle"><?php _e('When found:', 'pmxi_plugin') ?></span> <br>
						<input type="radio" id="duplicate_action_keep_<?php echo $entry; ?>" name="duplicate_action" value="keep" <?php echo 'keep' == $post['duplicate_action'] ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="duplicate_action_keep_<?php echo $entry; ?>"><?php _e('keep existing and skip new', 'pmxi_plugin' )?></label> <br>
						<input type="radio" id="duplicate_action_rewrite_<?php echo $entry; ?>" name="duplicate_action" value="rewrite" <?php echo 'rewrite' == $post['duplicate_action'] ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="duplicate_action_rewrite_<?php echo $entry; ?>"><?php _e('remove existing and add new', 'pmxi_plugin' )?></label> <br>
						<input type="radio" id="duplicate_action_update_<?php echo $entry; ?>" name="duplicate_action" value="update" <?php echo 'update' == $post['duplicate_action'] ? 'checked="checked"': '' ?> class="switcher"/>
						<label for="duplicate_action_update_<?php echo $entry; ?>"><?php _e('update existing', 'pmxi_plugin' )?></label>
						<span class="switcher-target-duplicate_action_update_<?php echo $entry; ?>" style="vertical-align:middle">
							<div class="input" style="padding-left:20px;">
								<input type="hidden" name="not_create_records" value="0" />
								<input type="checkbox" id="not_create_records_<?php echo $entry; ?>" name="not_create_records" value="1" <?php echo $post['not_create_records'] ? 'checked="checked"' : '' ?> />
								<label for="not_create_records_<?php echo $entry; ?>"><?php _e('NOT create new records', 'pmxi_plugin') ?></label>
							</div>
						</span>
					</div>
				</div>
			</div>
		</div-->
	</td>
</tr>
<tr>
	<td colspan="3">
		<h3 style="text-align:center; color:#999;">Drag elements from the XML tree on the right to any textbox below.</h3>
	</td>
</tr>