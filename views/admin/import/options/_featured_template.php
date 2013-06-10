<tr>
	<td colspan="3">
		<h3>
			<?php _e('Download & Import Images To The Post Media Gallery', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('Specify URLs or XPath Template Tags to download and import images to the post media gallery. The first image will be set as the Featured Image. Example: <pre>{image[1]},{image[2]},{image[3]}</pre> Separate your URLs or XPath Template Tags with the comma. <i>Separated by</i> character will use if xPath value contains multiple URLs, for example: <br> \'http://test.com/first.jpg | http://test.com/second.png\'.', 'pmxi_plugin') ?>">?</a>
			<span class="separated_by">Separated by</span>
		</h3>
		<div>
			<input type="text" name="featured_image" style="width:92%;" value="<?php echo ($post_type == "product") ? esc_attr($post['featured_image']) : ""; ?>"  <?php if ($post_type != "product"):?>disabled="disabled"<?php endif; ?>/>
			<input type="text" class="small" name="featured_delim" maxlength="1" value="<?php echo esc_attr($post['featured_delim']) ?>" style="width:5%; text-align:center;" <?php if ($post_type != "product"):?>disabled="disabled"<?php endif; ?>/>
		</div>
		<div class="input">
			<input type="hidden" name="create_draft" value="no" />
			<input type="checkbox" id="create_draft_<?php echo $entry; ?>" name="create_draft" value="yes" <?php echo ($post_type == "product" and "yes" == $post['create_draft']) ? 'checked="checked"' : '' ?> <?php if ($post_type != "product"):?>disabled="disabled"<?php endif; ?>/>
			<label for="create_draft_<?php echo $entry; ?>"><?php _e('<small>If no images are downloaded successfully, create entry as Draft.</small>', 'pmxi_plugin') ?></label>
		</div>
		<?php if ($post_type != "product"):?>
						<center>

							<hr />

							<b>Please upgrade to the professional edition of WP All Import to download and import images to the post media gallery.</b>

							<p style='font-size: 1.1em; font-weight: bold;'><a href="http://www.wpallimport.com/upgrade-to-pro?utm_source=wordpress.org&utm_medium=featured-images&utm_campaign=free+plugin" target="_blank" class="upgrade_link">Upgrade Now</a></p>

						</center>
		<?php endif; ?>

		<h3>
			<?php _e('Download & Import Attachments', 'pmxi_plugin') ?>
			<span class="separated_by">Separated by</span>
		</h3>
		<div>
			<input type="text" name="attachments" style="width:92%;" value="<?php echo esc_attr($post['attachments']) ?>" />
			<input type="text" class="small" name="atch_delim" maxlength="1" value="<?php echo esc_attr($post['atch_delim']) ?>" style="width:5%; text-align:center;" />
		</div>
		<br>
	</td>
</tr>