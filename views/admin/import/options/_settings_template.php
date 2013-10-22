<tr>
	<td colspan="3" style="padding-top:20px;">
		<fieldset class="optionsset">
			<legend><?php _e('Import Settings','pmxi_plugin');?></legend>
			<p>
				<div class="input">
					<label for="save_import_as"><?php _e('Friendly Name','pmxi_plugin');?></label> <input type="text" name="friendly_name" title="<?php _e('Save friendly name...', 'pmxi_plugin') ?>" style="vertical-align:middle; font-size:11px; background:#fff !important;" value="<?php echo esc_attr($post['friendly_name']) ?>" />
				</div>
			</p>
			<?php if ( ! empty(PMXI_Plugin::$session->data['pmxi_import']['large_file']) or (!empty($import) and $import->large_import == 'Yes')):?>
				<p>
					<div class="input">
						<label for="records_per_request"><?php _e('Records Per Iteration', 'pmxi_plugin');?></label> <input type="text" name="records_per_request" style="vertical-align:middle; font-size:11px; background:#fff !important; width: 40px;" value="<?php echo esc_attr($post['records_per_request']) ?>" />
						<a href="#help" class="help" title="<?php _e('Your feed was detected as a &quot;large&quot; file. The import process will be executed via AJAX requests. To make import process faster you can increase the number of records imported per iteration. Higher numbers put more strain on your server but make the import process take less time. 10 is a very safe number. To speed up the process, try 100 or more, especially if your import settings are simple and you are not downloading images.', 'pmxi_plugin') ?>">?</a>
					</div>
				</p>
				<!--p>
					<div class="input">
						<input type="hidden" name="create_chunks" value="0" />
						<input type="checkbox" id="create_chunks_<?php echo $entry; ?>" name="create_chunks" value="1" class="fix_checkbox" <?php echo $post['create_chunks'] ? 'checked="checked"': '' ?>/>
						<label for="create_chunks_<?php echo $entry; ?>"><?php _e('create chunks', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('Check this to split up the file into pieces before import. Will speed up the import of large files.', 'pmxi_plugin') ?>">?</a></label>
					</div>
				</p-->
			<?php endif; ?>
			<div class="input">
				<input type="hidden" name="is_import_specified" value="0" />
				<input type="checkbox" id="is_import_specified_<?php echo $entry; ?>" class="switcher fix_checkbox" name="is_import_specified" value="1" <?php echo $post['is_import_specified'] ? 'checked="checked"': '' ?>/>
				<label for="is_import_specified_<?php echo $entry; ?>"><?php _e('Import only specified records', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('Enter records or record ranges separated by commas, e.g. <b>1,5,7-10</b> would import the first, the fifth, and the seventh to tenth.', 'pmxi_plugin') ?>">?</a></label>
				<span class="switcher-target-is_import_specified_<?php echo $entry; ?>" style="vertical-align:middle">
					<div class="input" style="display:inline;">
						<input type="text" name="import_specified" value="<?php echo esc_attr($post['import_specified']) ?>" style="width:50%;"/>
					</div>
				</span>
			</div>
			<p>
				<div class="input">
					<input type="checkbox" id="save_template_as_<?php echo $entry; ?>" name="save_template_as" class="fix_checkbox" value="1" <?php echo ( ! empty($post['save_template_as'])) ? 'checked="checked"' : '' ?>/> <label for="save_template_as_<?php echo $entry; ?>"><?php _e('Save template as:','pmxi_plugin');?></label> &nbsp;<input type="text" name="name" title="<?php _e('Save Template As...', 'pmxi_plugin') ?>" style="vertical-align:middle; font-size:13px;" value="<?php echo (!empty($post['name'])) ? esc_attr($post['name']) : ''; ?>" />
				</div>
			</p>
			<?php if (in_array($source_type, array('ftp', 'file'))): ?>
				<p>
					<div class="input">
						<input type="hidden" name="is_delete_source" value="0" />
						<input type="checkbox" id="is_delete_source_<?php echo $entry; ?>" class="fix_checkbox" name="is_delete_source" value="1" <?php echo $post['is_delete_source'] ? 'checked="checked"': '' ?>/>
						<label for="is_delete_source_<?php echo $entry; ?>"><?php _e('Delete source XML file after importing', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('This setting takes effect only when script has access rights to perform the action, e.g. file is not deleted when pulled via HTTP or delete permission is not granted to the user that script is executed under.', 'pmxi_plugin') ?>">?</a></label>
					</div>
				</p>
			<?php endif; ?>
			<?php if (class_exists('PMLC_Plugin')): // option is only valid when `WP Wizard Cloak` pluign is enabled ?>
				<p>
					<div class="input">
						<input type="hidden" name="is_cloak" value="0" />
						<input type="checkbox" id="is_cloak_<?php echo $entry; ?>" class="fix_checkbox" name="is_cloak" value="1" <?php echo $post['is_cloak'] ? 'checked="checked"': '' ?>/>
						<label for="is_cloak_<?php echo $entry; ?>"><?php _e('Auto-Cloak Links', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php printf(__('Automatically process all links present in body of created post or page with <b>%s</b> plugin', 'pmxi_plugin'), PMLC_Plugin::getInstance()->getName()) ?>">?</a></label>
					</div> 
				</p>
			<?php endif; ?>				
		</fieldset>
	</td>
</tr>