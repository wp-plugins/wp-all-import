<?php

$l10n = array(
	'queue_limit_exceeded' => 'You have attempted to queue too many files.',
	'file_exceeds_size_limit' => 'This file exceeds the maximum upload size for this site.',
	'zero_byte_file' => 'This file is empty. Please try another.',
	'invalid_filetype' => 'This file type is not allowed. Please try another.',
	'default_error' => 'An error occurred in the upload. Please try again later.',
	'missing_upload_url' => 'There was a configuration error. Please contact the server administrator.',
	'upload_limit_exceeded' => 'You may only upload 1 file.',
	'http_error' => 'HTTP Error: Click here for our <a href="http://www.wpallimport.com/documentation/advanced/troubleshooting/" target="_blank">troubleshooting guide</a>, or ask your web host to look in your error_log file for an error that takes place at the same time you are trying to upload a file.',
	'upload_failed' => 'Upload failed.',
	'io_error' => 'IO error.',
	'security_error' => 'Security error.',
	'file_cancelled' => 'File canceled.',
	'upload_stopped' => 'Upload stopped.',
	'dismiss' => 'Dismiss',
	'crunching' => 'Crunching&hellip;',
	'deleted' => 'moved to the trash.',
	'error_uploading' => 'has failed to upload due to an error',
	'cancel_upload' => 'Cancel upload',
	'dismiss' => 'Dismiss'
);

?>
<script type="text/javascript">
	var plugin_url = '<?php echo WP_ALL_IMPORT_ROOT_URL; ?>';
	var swfuploadL10n = <?php echo json_encode($l10n); ?>;
</script>

<div class="change_file">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header" style="padding-left:30px;">
			<h3><?php _e('Import File','wp_all_import_plugin');?></h3>	
		</div>
		<div class="wpallimport-collapsed-content" style="padding-bottom: 40px;">
			<hr>
			<table class="form-table" style="max-width:none;">
				<tr>
					<td colspan="3">

						<div class="wpallimport-import-types">
							<h3><?php _e('Specify the location of the file to use for future runs of this import.', 'wp_all_import_plugin'); ?></h3>
							<a class="wpallimport-import-from wpallimport-upload-type <?php echo 'upload' == $import->type ? 'selected' : '' ?>" rel="upload_type" href="javascript:void(0);">
								<span class="wpallimport-icon"></span>
								<span class="wpallimport-icon-label"><?php _e('Upload a file', 'wp_all_import_plugin'); ?></span>
							</a>
							<a class="wpallimport-import-from wpallimport-url-type <?php echo 'url' == $import->type ? 'selected' : '' ?>" rel="url_type" href="javascript:void(0);">
								<span class="wpallimport-icon"></span>
								<span class="wpallimport-icon-label"><?php _e('Download from URL', 'wp_all_import_plugin'); ?></span>
							</a>
							<a class="wpallimport-import-from wpallimport-file-type <?php echo 'file' == $import->type ? 'selected' : '' ?>" rel="file_type" href="javascript:void(0);">
								<span class="wpallimport-icon"></span>
								<span class="wpallimport-icon-label"><?php _e('Use existing file', 'wp_all_import_plugin'); ?></span>
							</a>
						</div>						
						
						<input type="hidden" value="upload" name="new_type"/>

						<div class="wpallimport-upload-type-container" rel="upload_type">							
							<div id="plupload-ui" class="wpallimport-file-type-options">
					            <div>				                
					                <input type="hidden" name="filepath" value="<?php if ('upload' == $import->type) echo $import->path; ?>" id="filepath"/>
					                <a id="select-files" href="javascript:void(0);"/><?php _e('Click here to select file from your computer...', 'wp_all_import_plugin'); ?></a>
					                <div id="progressbar" class="wpallimport-progressbar">
					                	<?php if ('upload' == $import->type) _e( '<span>Upload Complete</span> - '.basename($import->path).' 100%', 'wp_all_import_plugin'); ?>
					                </div>
					                <div id="progress" class="wpallimport-progress" <?php if ('upload' == $import->type):?>style="display: block;"<?php endif;?>>
					                	<div id="upload_process" class="wpallimport-upload-process"></div>				                	
					                </div>
					            </div>
					        </div>
						</div>
						<div class="wpallimport-upload-type-container" rel="url_type">							
							<div class="wpallimport-file-type-options">
								<span class="wpallimport-url-icon"></span>
								<input type="text" class="regular-text" name="url" value="<?php echo ('url' == $import->type) ? esc_attr($import->path) : 'Enter a web address to download the file from...'; ?>"/> 								
								<div class="wpallimport-free-edition-notice">									
									<a href="http://www.wpallimport.com/upgrade-to-pro/?utm_source=free-plugin&utm_medium=in-plugin&utm_campaign=download-from-url" target="_blank" class="upgrade_link"><?php _e('Upgrade to the professional edition of WP All Import to use this feature.', 'wp_all_import_plugin');?></a>
								</div>
							</div>
							<input type="hidden" name="downloaded"/>
						</div>
						<div class="wpallimport-upload-type-container" rel="file_type">		
							<?php $upload_dir = wp_upload_dir(); ?>					
							<div class="wpallimport-file-type-options">								
								
								<div id="file_selector" class="dd-container" style="width: 600px;">
									<div class="dd-select" style="width: 600px; background: none repeat scroll 0% 0% rgb(238, 238, 238);">
										<input type="hidden" class="dd-selected-value" value="">
										<a class="dd-selected" style="color: rgb(207, 206, 202);">
											<label class="dd-selected-text "><?php _e('Select a previously uploaded file', 'wp_all_import_plugin'); ?></label>
										</a>
										<span class="dd-pointer dd-pointer-down"></span>
									</div>									
								</div>								
								
								<input type="hidden" name="file" value="<?php if ('file' == $import->type) echo esc_attr($import->path); ?>"/>	
								
								<div class="wpallimport-note" style="width:60%; margin: 0 auto; ">
									<?php printf(__('Upload files to <strong>%s</strong> and they will appear in this list', 'wp_all_import_plugin'), $upload_dir['basedir'] . '/wpallimport/files'); ?>									
								</div>
								<div class="wpallimport-free-edition-notice">									
									<a href="http://www.wpallimport.com/upgrade-to-pro/?utm_source=free-plugin&utm_medium=in-plugin&utm_campaign=use-existing-file" target="_blank" class="upgrade_link"><?php _e('Upgrade to the professional edition of WP All Import to use this feature.', 'wp_all_import_plugin');?></a>
								</div>
							</div>
						</div>						
					</td>
				</tr>
			</table>
		</div>		
	</div>
</div>