<tr>
	<td colspan="3">
		<h3><?php _e('Post Author', 'pmxi_plugin') ?></h3>
		<div>
			<input type="text" name="author" value="<?php echo esc_attr($post['author']) ?>" /> <a href="#help" class="help" title="<?php _e('Value that contains user ID, login, slug or email.', 'pmxi_plugin') ?>">?</a>			
		</div>																	
	</td>								
</tr>		
<tr>
	<td colspan="3">
		<h3><?php _e('Post Excerpt', 'pmxi_plugin') ?></h3>
		<div>
			<input type="text" name="post_excerpt" style="width:100%;" value="<?php echo esc_attr($post['post_excerpt']) ?>" />
		</div>
		<h3><?php _e('Post Slug', 'pmxi_plugin') ?></h3>
		<div>
			<input type="text" name="post_slug" style="width:100%;" value="<?php echo esc_attr($post['post_slug']) ?>" />
		</div> <br><br>
	</td>
</tr>