<tr>
	<td colspan="3">
		<h3>
			<?php _e('Download & Import Images To The Post Media Gallery', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('Specify URLs or XPath Template Tags to download and import images to the post media gallery. The first image will be set as the Featured Image. Example: <pre>{image[1]},{image[2]},{image[3]}</pre> Separate your URLs or XPath Template Tags with the comma. <i>Separated by</i> character will use if xPath value contains multiple URLs, for example: <br> \'http://test.com/first.jpg | http://test.com/second.png\'.', 'pmxi_plugin') ?>">?</a>
			<span class="separated_by">Separated by</span>
		</h3>
		<div>
			<input type="text" name="featured_image" style="width:92%;" value="<?php echo esc_attr($post['featured_image']) ?>" disabled="disabled"/>
			<input type="text" class="small" name="featured_delim" maxlength="1" value="<?php echo esc_attr($post['featured_delim']) ?>" style="width:5%; text-align:center;" disabled="disabled"/>
		</div>
		<div class="input">
			<input type="hidden" name="create_draft" value="no" />
			<input type="checkbox" id="create_draft_<?php echo $entry; ?>" name="create_draft" value="yes" <?php echo 'yes' == $post['create_draft'] ? 'checked="checked"' : '' ?> disabled="disabled"/>
			<label for="create_draft_<?php echo $entry; ?>"><?php _e('<small>If no images are downloaded successfully, create entry as Draft.</small>', 'pmxi_plugin') ?></label>
		</div>
		<a href="http://www.wpallimport.com/upgrade-to-pro?from=upi" target="_blank" class="upgrade_link">Upgrade to the paid edition of WP All Import to use this feature.</a>
		<h3>
			<?php _e('Download & Import Attachments', 'pmxi_plugin') ?>
			<span class="separated_by">Separated by</span>
		</h3>
		<div>
			<input type="text" name="attachments" style="width:92%;" value="<?php echo esc_attr($post['attachments']) ?>" />
			<input type="text" class="small" name="atch_delim" maxlength="1" value="<?php echo esc_attr($post['atch_delim']) ?>" style="width:5%; text-align:center;"/>
		</div>
		<br>
	</td>
</tr>