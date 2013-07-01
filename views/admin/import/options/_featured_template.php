<tr>
	<td colspan="3">
		<h3>
			<?php _e('Download & Import Images To The Post Media Gallery', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('Specify URLs or XPath Template Tags to download and import images to the post media gallery. The first image will be set as the Featured Image. Example: <pre>{image[1]},{image[2]},{image[3]}</pre> Separate your URLs or XPath Template Tags with the comma. <i>Separated by</i> character will use if xPath value contains multiple URLs, for example: <br> \'http://test.com/first.jpg | http://test.com/second.png\'.', 'pmxi_plugin') ?>">?</a>
			<span class="separated_by"><?php _e('Separated by','pmxi_plugin');?></span>
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
		<div class="input">
			<input type="radio" id="use_filename_<?php echo $entry; ?>" name="images_name" value="filename" <?php echo ($post['images_name'] == "filename") ? 'checked="checked"' : '' ?> <?php if ($post_type != "product"):?>disabled="disabled"<?php endif; ?>/>
			<label for="use_filename_<?php echo $entry; ?>"><?php _e('<small>The image filenames should be generated based on URLs.</small>', 'pmxi_plugin') ?></label> <br>

			<input type="radio" id="use_autoname_<?php echo $entry; ?>" name="images_name" value="auto" <?php echo ($post['images_name'] == "auto") ? 'checked="checked"' : '' ?> <?php if ($post_type != "product"):?>disabled="disabled"<?php endif; ?>/>
			<label for="use_autoname_<?php echo $entry; ?>"><?php _e('<small>The image filenames should be generated based on timestamp.</small>', 'pmxi_plugin') ?></label> <br>
		</div>
		<div class="input">
			<input type="hidden" name="auto_rename_images" value="0" />
			<input type="checkbox" id="auto_rename_images_<?php echo $entry; ?>" name="auto_rename_images" value="1" <?php echo $post['auto_rename_images'] ? 'checked="checked"' : '' ?> class="switcher" <?php if ($post_type != "product"):?>disabled="disabled"<?php endif; ?>/>
			<label for="auto_rename_images_<?php echo $entry; ?>"><?php _e('<small>The image filenames should be generated based on provided suffix.</small>', 'pmxi_plugin') ?></label>
			<a href="#help" class="help" title="<?php _e('Example <product_title>Acme Product</product>. Image filenames: acme_product-1.(ext), acme_product-2.(ext), acme_product-3.(ext), etc.', 'pmxi_plugin') ?>">?</a>
		</div>		
		<div class="switcher-target-auto_rename_images_<?php echo $entry; ?>" style="padding-left:17px;">
			<div class="input">
				<?php _e('<small>Images suffix</small>', 'pmxi_plugin') ?>
				<input type="text" name="auto_rename_images_suffix" value="<?php echo esc_attr($post['auto_rename_images_suffix']) ?>" />
			</div>
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