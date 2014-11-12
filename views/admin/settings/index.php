<form class="settings" method="post" action="<?php echo $this->baseUrl ?>" enctype="multipart/form-data">

	<div class="wpallimport-header">
		<div class="wpallimport-logo"></div>
		<div class="wpallimport-title">
			<p><?php _e('WP All Import', 'pmxi_plugin'); ?></p>
			<h3><?php _e('Settings', 'pmxi_plugin'); ?></h3>			
		</div>	
	</div>

	<h2></h2>
	
	<div class="wpallimport-setting-wrapper">
		<?php if ($this->errors->get_error_codes()): ?>
			<?php $this->error() ?>
		<?php endif ?>
		
		<h3><?php _e('Import/Export Templates', 'pmxi_plugin') ?></h3>
		<?php $templates = new PMXI_Template_List(); $templates->getBy()->convertRecords() ?>
		<?php if ($templates->total()): ?>
			<table>
				<?php foreach ($templates as $t): ?>
					<tr>
						<td>
							<label class="selectit" for="template-<?php echo $t->id ?>"><input id="template-<?php echo $t->id ?>" type="checkbox" name="templates[]" value="<?php echo $t->id ?>" /> <?php echo $t->name ?></label>
						</td>				
					</tr>
				<?php endforeach ?>
			</table>
			<p class="submit-buttons">
				<?php wp_nonce_field('delete-templates', '_wpnonce_delete-templates') ?>		
				<input type="submit" class="button-primary" name="delete_templates" value="<?php _e('Delete Selected', 'pmxi_plugin') ?>" />
				<input type="submit" class="button-primary" name="export_templates" value="<?php _e('Export Selected', 'pmxi_plugin') ?>" />
			</p>	
		<?php else: ?>
			<em><?php _e('There are no templates saved', 'pmxi_plugin') ?></em>
		<?php endif ?>
		<p>
			<input type="hidden" name="is_templates_submitted" value="1" />
			<input type="file" name="template_file"/>
			<input type="submit" class="button-primary" name="import_templates" value="<?php _e('Import Templates', 'pmxi_plugin') ?>" />
		</p>
	</div>

</form>

<form name="settings" method="post" action="<?php echo $this->baseUrl ?>" class="settings">	

	<h3><?php _e('Files', 'pmxi_plugin') ?></h3>
	
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label><?php _e('Secure Mode', 'pmxi_plugin'); ?></label></th>
				<td>
					<fieldset style="padding:0;">
						<legend class="screen-reader-text"><span><?php _e('Secure Mode', 'pmxi_plugin'); ?></span></legend>
						<input type="hidden" name="secure" value="0"/>
						<label for="secure"><input type="checkbox" value="1" id="secure" name="secure" <?php echo (($post['secure']) ? 'checked="checked"' : ''); ?>><?php _e('Randomize folder names', 'pmxi_plugin'); ?></label>																				
					</fieldset>														
					<p class="description">
						<?php
							$wp_uploads = wp_upload_dir();
						?>
						<?php printf(__('Imported files, chunks, logs and temporary files will be placed in a folder with a randomized name inside of %s.', 'pmxi_plugin'), $wp_uploads['basedir'] . '/wpallimport' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php _e('Log Storage', 'pmxi_plugin'); ?></label></th>
				<td>
					<input type="text" class="regular-text" name="log_storage" value="<?php echo esc_attr($post['log_storage']); ?>"/>
					<p class="description"><?php _e('Number of logs to store for each import. Enter 0 to never store logs.', 'pmxi_plugin'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php _e('Clean Up Temp Files', 'pmxi_plugin'); ?></label></th>
				<td>
					<a class="button-primary wpallimport-clean-up-tmp-files" href="<?php echo add_query_arg(array('action' => 'cleanup'), $this->baseUrl); ?>"><?php _e('Clean Up', 'pmxi_plugin'); ?></a>
					<p class="description"><?php _e('Attempt to remove temp files left over by imports that were improperly terminated.', 'pmxi_plugin'); ?></p>
				</td>
			</tr>
		</tbody>
	</table>	

	<div class="clear"></div>

	<h3><?php _e('Advanced Settings', 'pmxi_plugin') ?></h3>
	
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label><?php _e('Chunk Size', 'pmxi_plugin'); ?></label></th>
				<td>
					<input type="text" class="regular-text" name="large_feed_limit" value="<?php echo esc_attr($post['large_feed_limit']); ?>"/>
					<p class="description"><?php _e('Split file into chunks containing the specified number of records.', 'pmxi_plugin'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php _e('WP_IMPORTING', 'pmxi_plugin'); ?></label></th>
				<td>
					<fieldset style="padding:0;">
						<legend class="screen-reader-text"><span>Membership</span></legend>
						<input type="hidden" name="pingbacks" value="0"/>
						<label for="pingbacks"><input type="checkbox" value="1" id="pingbacks" name="pingbacks" <?php echo (($post['pingbacks']) ? 'checked="checked"' : ''); ?>><?php _e('Enable WP_IMPORTING', 'pmxi_plugin'); ?></label>																				
					</fieldset>														
					<p class="description"><?php _e('Setting this constant avoids triggering pingback.', 'pmxi_plugin'); ?></p>
				</td>
			</tr>			
		</tbody>
	</table>			

	<div class="clear"></div>

	<p class="submit-buttons">
		<?php wp_nonce_field('edit-settings', '_wpnonce_edit-settings') ?>
		<input type="hidden" name="is_settings_submitted" value="1" />
		<input type="submit" class="button-primary" value="Save Settings" />
	</p>

	<a href="http://soflyy.com/" target="_blank" class="wpallimport-created-by"><?php _e('Created by', 'pmxi_plugin'); ?> <span></span></a>

</form>