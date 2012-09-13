<table class="layout">
	<tr>
		<td class="left">
			<h2><?php _e('Import XML/CSV - Step 1: Choose Your File', 'pmxi_plugin') ?><br/><span class="taglines"><?php _e('choose which CSV or XML file you want to import', 'pmxi_plugin') ?></span></h2>
			<hr />

			<?php if ($this->errors->get_error_codes()): ?>
				<?php $this->error() ?>
			<?php endif ?>

			<form method="post" class="choose-file no-enter-submit" enctype="multipart/form-data" autocomplete="off">
				<input type="hidden" name="is_submitted" value="1" />
				<?php wp_nonce_field('upload-xml', '_wpnonce_upload-xml') ?>

				<div class="file-type-container">
					<h3>
						<input type="radio" id="type_upload" name="type" value="upload" <?php echo 'upload' == $post['type'] ? 'checked="checked"' : '' ?> />
						<label for="type_upload"><?php _e('Upload XML/CSV File From Your Computer', 'pmxi_plugin') ?></label>
					</h3>
					<div class="file-type-options">
						<input type="file" class="regular-text" name="upload" />
						<div class="note"><strong><?php _e('Warning', 'pmxi_plugin') ?></strong>: <?php printf(__('Your host allows a maximum filesize of <strong>%sB</strong> for uploads', 'pmxi_plugin'), ini_get('upload_max_filesize')) ?></div>
					</div>
				</div>
				<div class="file-type-container">
					<h3>
						<input type="radio" id="type_url" name="type" value="url" <?php echo 'url' == $post['type'] ? 'checked="checked"' : '' ?> />
						<label for="type_url"><?php _e('Get XML/CSV File From URL', 'pmxi_plugin') ?></label>
					</h3>
					<div class="file-type-options">
						<input type="text" class="regular-text" name="url" value="<?php echo esc_attr($post['url']) ?>" />
					</div>
				</div>
				<div class="file-type-container">
					<h3>
						<input type="radio" id="type_ftp" name="type" value="ftp" <?php echo 'ftp' == $post['type'] ? 'checked="checked"' : '' ?> />
						<label for="type_ftp"><?php _e('Get XML/CSV File Via FTP', 'pmxi_plugin') ?>*</label>
					</h3>
					<div class="file-type-options">
						<input type="text" class="regular-text" name="ftp[url]" value="<?php echo esc_attr($post['ftp']['url']) ?>" /><br />
						<input type="text" name="ftp[user]" title="username" /><strong>:</strong><input type="password" name="ftp[pass]" title="passowrd" />
						<div class="note"><b>*</b>&nbsp;<?php _e('These options support shell wildcard patterns<a href="#help" class="help" title="A shell wildcard pattern is a string used by *nix systems for referencing several files at once. The most common case is using asterisk symbol in the place of any set of characters, e.g. `*.xml` would correspond to any file with `xml` extension.">?</a> which enables linking several XML files to the same import. The option is useful when the exact source path is not known upfront or is going to change, e.g. some content providers submit XML files each time with a new name.', 'pmxi_plugin') ?></div>
					</div>
				</div>
				<div class="file-type-container">
					<h3>
						<input type="radio" id="type_file" name="type" value="file" <?php echo 'file' == $post['type'] ? 'checked="checked"' : '' ?> />
						<label for="type_file"><?php _e('Get XML/CSV File On This Server', 'pmxi_plugin') ?>*</label>
					</h3>
					<div class="file-type-options">
						<input type="text" id="__FILE_SOURCE" class="regular-text autocomplete" name="file" value="<?php echo esc_attr($post['file']) ?>" />
						<?php
						$local_files = array_merge(
							PMXI_Helper::safe_glob(PMXI_Plugin::ROOT_DIR . '/upload/*.xml', PMXI_Helper::GLOB_RECURSE),
							PMXI_Helper::safe_glob(PMXI_Plugin::ROOT_DIR . '/upload/*.gz', PMXI_Helper::GLOB_RECURSE),
							PMXI_Helper::safe_glob(PMXI_Plugin::ROOT_DIR . '/upload/*.csv', PMXI_Helper::GLOB_RECURSE)
						);
						sort($local_files);
						?>
						<script type="text/javascript">
							__FILE_SOURCE = <?php echo json_encode($local_files) ?>;
						</script>
						<div class="note"><?php printf(__('Upload files to <strong>%s</strong> and they will appear in this list', 'pmxi_plugin'), PMXI_Plugin::ROOT_DIR . '/upload/') ?></div>
					</div>
				</div>
				<?php if ($history->count()): ?>
					<div class="file-type-container">
						<h3>
							<input type="radio" id="type_reimport" name="type" value="reimport" <?php echo 'reimport' == $post['type'] ? 'checked="checked"' : '' ?> />
							<label for="type_reimport"><?php _e('Get Previously Imported XML  or CSV', 'pmxi_plugin') ?></label>
						</h3>
						<div class="file-type-options">
							<input type="text" id="__REIMPORT_SOURCE" class="regular-text autocomplete" name="reimport" value="<?php echo esc_attr($post['reimport']) ?>" readonly="readonly" />
							<?php
							$reimports = array();
							foreach ($history as $file) $reimports[] = '#' . $file['id'] . ': ' . $file['name'] . __(' on ', 'pmxi_plugin') . mysql2date('Y/m/d', $file['registered_on']) . ' - ' . preg_replace('%^(\w+://[^:]+:)[^@]+@%', '$1*****@', $file['path']);
							?>
							<script type="text/javascript">
								__REIMPORT_SOURCE = <?php echo json_encode($reimports) ?>;
							</script>
						</div>
					</div>
				<?php endif ?>

				<div class="file-type-container">
					<?php if ($imports->count()): ?>
						<div class="input">
							<input type="checkbox" id="is_update_previous" name="is_update_previous" class="switcher" disabled="disabled"/>
							<label for="is_update_previous"><?php _e('Update Previous Import', 'pmxi_plugin') ?></label>							
						</div>
						<a href="http://www.wpallimport.com/upgrade-to-pro?from=upi" target="_blank">Upgrade to pro to update previous imports.</a>
					<?php endif ?>
					<p class="submit-buttons">
						<input type="hidden" name="is_submitted" value="1" />
						<?php wp_nonce_field('choose-file', '_wpnonce_choose-file') ?>
						<input type="submit" class="button-primary" value="<?php _e('Continue', 'pmxi_plugin') ?> &gt;&gt;" />
					</p>
				</div>
				<br />
				<table><tr><td class="note"></td></tr></table>
			</form>
		</td>
		<td class="right">
			&nbsp;
		</td>
	</tr>
</table>
