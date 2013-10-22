<p class="submit-buttons">
	<?php wp_nonce_field('options', '_wpnonce_options') ?>
	<input type="hidden" name="is_submitted" value="1" />

	<?php if ($this->isWizard): ?>

		<a href="<?php echo add_query_arg('action', 'template', $this->baseUrl) ?>" class="back"><?php _e('Back', 'pmxi_plugin') ?></a>

		<?php if (in_array($source_type, array('url', 'ftp', 'file'))): ?>
			<input type="hidden" class="save_only" value="0" name="save_only"/>
			<input type="submit" name="btn_save_only" class="button button-primary button-hero large_button" value="<?php _e('Save Only', 'pmxi_plugin') ?>" />
		<?php endif ?>

		<input type="submit" class="button button-primary button-hero large_button" value="<?php _e('Finish', 'pmxi_plugin') ?>" />

	<?php else: ?>
		<a href="<?php echo remove_query_arg('id', remove_query_arg('action', $this->baseUrl)); ?>" class="back"><?php _e('Back', 'pmxi_plugin') ?></a>
		<input type="submit" class="button button-primary button-hero large_button" value="<?php _e('Save', 'pmxi_plugin') ?>" />
	<?php endif ?>
</p>