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
	</td>
</tr>
<tr>
	<td colspan="3">
		<h3 style="text-align:center; color:#999;"><?php _e('Drag elements from the XML tree on the right to any textbox below.','pmxi_plugin');?></h3>
	</td>
</tr>