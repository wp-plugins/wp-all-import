<?php
	if (!function_exists('reverse_taxonomies_html')) {
		function reverse_taxonomies_html($post_taxonomies, $item_id, $i){
			$childs = array();
			foreach ($post_taxonomies as $j => $cat) if ($cat->parent_id == $item_id) { $childs[] = $cat; } 
			
			if (!empty($childs)){
				?>
				<ol>
				<?php
				foreach ($childs as $child_cat){
					$i++;
					?>													
		            <li id="item_<?php echo $i; ?>">
		            	<div class="drag-element"><input class="widefat" type="text" value="<?php echo $child_cat->xpath; ?>"/></div><a href="javascript:void(0);" class="icon-item remove-ico"></a>
		            	<?php echo reverse_taxonomies_html($post_taxonomies, $child_cat->item_id, $i); ?>
		            </li>											            
					<?php
				}
				?>
				</ol>
				<?php
			}
		}
	}
?>
<form class="options <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
	<table class="layout">
	<tr>
		<td class="left">
			<h2>
				<?php if ($this->isWizard): ?>
					<?php if ($is_loaded_template && !$load_options): ?>
					<span class="load-options">						
						Load Options...&nbsp;<input type="checkbox" name="load_options" /><a class="help" href="#help" original-title="Load options from selected template.">?</a>							
					</span>
					<?php elseif ($is_loaded_template): ?>
					<span class="load-options">						
						Reset Options...&nbsp;<input type="checkbox" name="reset_options" /><a class="help" href="#help" original-title="Reset options.">?</a>							
					</span>
					<?php endif; ?>
					<?php _e('Import XML/CSV - Step 4: Post Options', 'pmxi_plugin') ?><br/><span class="taglines"><?php _e('options for the created posts', 'pmxi_plugin') ?></span>					
				<?php else: ?>
					<?php _e('Edit Import Options', 'pmxi_plugin') ?>
				<?php endif ?>
			</h2>
			<hr />
			
			<?php if ($this->errors->get_error_codes()): ?>
				<?php $this->error() ?>
			<?php endif ?>
			
			<div class="post-type-container">
				<h3>
					<input type="radio" id="type_post" name="type" value="post" <?php echo 'post' == $post['type'] ? 'checked="checked"' : '' ?> />
					<label for="type_post"><?php _e('Create Posts', 'pmxi_plugin') ?></label>
				</h3>
				<div class="post-type-options">
					<table class="form-table">
							<tr>
							<th><?php _e('Categories', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('Enter Category IDs or Names.', 'pmxi_plugin') ?>">?</a></th>
							<td>
								<ol class="sortable no-margin">
									<?php if (!empty($post['categories'])):?>
										<?php
											$categories = json_decode($post['categories']);
											if (!empty($categories) and is_array($categories)): foreach ($categories as $i => $cat) {
												if (is_null($cat->parent_id) or empty($cat->parent_id))
												{
													?>
													<li id="item_<?php echo ($i+1); ?>">
														<div class="drag-element"><input type="text" class="widefat" value="<?php echo $cat->xpath; ?>"/></div>
														<?php echo reverse_taxonomies_html($categories, $cat->item_id, ($i+1)); ?>
													</li>								    
													<?php
												}
											}; else: ?>
											<li id="item_1"><div class="drag-element"><input type="text" class="widefat" value=""/></div></li>								    
											<?php endif;?>
									<?php else: ?>
								    <li id="item_1"><div class="drag-element"><input type="text" class="widefat" value=""/></div></li>								    
									<?php endif; ?>
								</ol>								
								<input type="hidden" class="hierarhy-output" name="categories" value="<?php echo esc_attr($post['categories']) ?>"/>
								<div class="hidden" id="dialog-confirm-category-removing" title="Delete categories?">Remove only current category or current category with subcategories?</div>								
							</td>
							<td class="delim">																
								<a href="javascript:void(0);" class="icon-item add-new-ico"></a>
								<a href="#help" class="help" title="<?php _e('Drag&Drop inputs to create categories hierarchy', 'pmxi_plugin') ?>">?</a>
							</td>
						</tr>
						<tr>
							<th><?php _e('Tags', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('Enter tags separated by commas.', 'pmxi_plugin') ?>">?</a></th>
							<td><input type="text" name="tags" class="widefat" value="<?php echo esc_attr($post['tags']) ?>" /></td>
							<td class="delim">
								<input type="text" class="small" name="tags_delim" maxlength="1" value="<?php echo esc_attr($post['tags_delim']) ?>" />
								<a href="#help" class="help" title="<?php _e('Delimiter used for tag list', 'pmxi_plugin') ?>">?</a>
							</td>
						</tr>
						<tr>
							<td colspan="3"> <a href="http://www.wpallimport.com/upgrade-to-pro?from=cpt" target="_blank">To import to Custom Post Types, upgrade to pro.</a> </td>
						</tr>
						<?php $post_taxonomies = array_diff_key(get_taxonomies_by_object_type(array('post'), 'object'), array_flip(array('category', 'post_tag', 'post_format'))) ?>
						<?php foreach ($post_taxonomies as $ctx): ?>
							<tr class="post_taxonomy" data-type="<?php echo implode(' ', $ctx->object_type) ?>">
								<th><nobr><?php echo $ctx->labels->name ?> <a href="#help" class="help" title="<?php _e('Enter taxonomy <b>'.$ctx->labels->name.'</b> terms IDs or Names.', 'pmxi_plugin') ?>">?</a></nobr></th>
								<td>
									<ol class="sortable no-margin">
										<?php if (!empty($post['post_taxonomies'][$ctx->name])):												
												$taxonomies_hierarchy = json_decode($post['post_taxonomies'][$ctx->name]);												
												if (!empty($taxonomies_hierarchy) and is_array($taxonomies_hierarchy)): foreach ($taxonomies_hierarchy as $i => $cat) {
													if (is_null($cat->parent_id) or empty($cat->parent_id))
													{
														?>
														<li id="item_<?php echo ($i+1); ?>">
															<div class="drag-element"><input type="text" class="widefat" value="<?php echo $cat->xpath; ?>"/></div>
															<?php echo reverse_taxonomies_html($taxonomies_hierarchy, $cat->item_id, ($i+1)); ?>
														</li>								    
														<?php
													}
												}; else:?>
												<li id="item_1"><div class="drag-element"><input type="text" class="widefat" value=""/></div></li>								    
												<?php endif;
											  else: ?>
									    <li id="item_1"><div class="drag-element"><input type="text" class="widefat" value=""/></div></li>								    
										<?php endif; ?>
									</ol>								
									<input type="hidden" class="hierarhy-output" name="post_taxonomies[<?php echo $ctx->name ?>]" value="<?php echo esc_attr($post['post_taxonomies'][$ctx->name]) ?>"/>									
								</td>
								<td class="delim">									
									<a href="javascript:void(0);" class="icon-item add-new-ico"></a>
									<a href="#help" class="help" title="<?php _e('Drag&Drop inputs to create taxonomies hierarchy', 'pmxi_plugin') ?>">?</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
			</div>
			<div class="post-type-container">
				<h3>
					<input type="radio" id="type_page" name="type" value="page" <?php echo 'page' == $post['type'] ? 'checked="checked"' : '' ?> />
					<label for="type_page"><?php _e('Create Pages', 'pmxi_plugin') ?></label>
				</h3>
				<div class="post-type-options">
					<table class="form-table">
						<tr>
							<th><?php _e('Page Template', 'pmxi_plugin') ?></th>
							<td>
								<select name="page_template" id="page_template">
									<option value='default'><?php _e('Default', 'pmxi_plugin') ?></option>
									<?php page_template_dropdown($template); ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><?php _e('Parent Page', 'pmxi_plugin') ?></th>
							<td>
								<?php wp_dropdown_pages(array('post_type' => 'page', 'selected' => $post['parent'], 'name' => 'parent', 'show_option_none' => __('(no parent)', 'pmxi_plugin'), 'sort_column'=> 'menu_order, post_title',)) ?>
							</td>
						</tr>
						<tr>
							<th><?php _e('Order', 'pmxi_plugin') ?></th>
							<td><input type="text" class="small-text" name="order" value="<?php echo esc_attr($post['order']) ?>" /></td>
						</tr>
						<?php $page_taxonomies = get_taxonomies_by_object_type('page', 'object') ?>
						<?php foreach ($page_taxonomies as $ctx): ?>
							<tr class="page_taxonomy" data-type="<?php echo implode(' ', $ctx->object_type) ?>">
								<th><nobr><?php echo $ctx->labels->name ?> <a href="#help" class="help" title="<?php _e('Enter taxonomy <b>'.$ctx->labels->name.'</b> terms IDs or Names.', 'pmxi_plugin') ?>">?</a></nobr></th>
								<td>
									<ol class="sortable no-margin">
										<?php if (!empty($post['page_taxonomies'][$ctx->name])):
												$taxonomies_hierarchy = json_decode($post['page_taxonomies'][$ctx->name]);
												foreach ($taxonomies_hierarchy as $i => $cat) {
													if (is_null($cat->parent_id) or empty($cat->parent_id))
													{
														?>
														<li id="item_<?php echo ($i+1); ?>">
															<div class="drag-element"><input type="text" class="widefat" value="<?php echo $cat->xpath; ?>"/></div>
															<?php echo reverse_taxonomies_html($taxonomies_hierarchy, $cat->item_id, ($i+1)); ?>
														</li>								    
														<?php
													}
												}
											  else: ?>
									    <li id="item_1"><div class="drag-element"><input type="text" class="widefat" value=""/></div></li>								    
										<?php endif; ?>
									</ol>								
									<input type="hidden" class="hierarhy-output" name="page_taxonomies[<?php echo $ctx->name ?>]" value="<?php echo esc_attr($post['page_taxonomies'][$ctx->name]) ?>"/>
									<!--input type="text" name="page_taxonomies[<?php echo $ctx->name ?>]" class="widefat" value="<?php echo esc_attr(isset($post['page_taxonomies'][$ctx->name]) ? $post['page_taxonomies'][$ctx->name] : '') ?>" /-->
								</td>
								<td class="delim">
									<a href="javascript:void(0);" class="icon-item add-new-ico"></a>
									<a href="#help" class="help" title="<?php _e('Drag&Drop inputs to create taxonomies hierarchy', 'pmxi_plugin') ?>">?</a>
								</td>
							</tr>
						<?php endforeach ?>
					</table>
				</div>
			</div>
			
			<h2><?php _e('Generic Options', 'pmxi_plugin') ?></h2>
			<hr />
			
			<div class="input">
				<input type="hidden" name="is_import_specified" value="0" />
				<input type="checkbox" id="is_import_specified" class="switcher" name="is_import_specified" value="1" <?php echo $post['is_import_specified'] ? 'checked="checked"': '' ?>/>
				<label for="is_import_specified"><?php _e('Import only specified records', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('Enter records or record ranges separated by commas, e.g. <b>1,5,7-10</b> would import the first, the fifth, and the seventh to tenth.', 'pmxi_plugin') ?>">?</a></label>
				<span class="switcher-target-is_import_specified" style="vertical-align:middle">
					<input type="text" name="import_specified" value="<?php echo esc_attr($post['import_specified']) ?>" />
				</span>
			</div>
			<div>
				<input type="hidden" name="is_duplicates" value="0" />
				<input type="checkbox" id="is_duplicates" class="switcher" name="is_duplicates" value="1" disabled="disabled"/>
				<label for="is_duplicates"><?php _e('Check for duplicates', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('This option allows you to specify action for articles being imported which have duplicates in WordPress database.<br /><br /><b>Important</b>: This option applies only to pages or posts not associated with current import. To manage overwrite rules for records previously created by import operation currently being updated please see `Reimport / Update Options` section below.', 'pmxi_plugin') ?>">?</a></label>
				<a href="http://www.wpallimport.com/upgrade-to-pro?from=cfd" target="_blank">Upgrade to pro for automatic duplicate detection.</a>
			</div>
			<?php if (in_array($source_type, array('ftp', 'file'))): ?>
				<div class="input">
					<input type="hidden" name="is_delete_source" value="0" />
					<input type="checkbox" id="is_delete_source" name="is_delete_source" value="1" <?php echo $post['is_delete_source'] ? 'checked="checked"': '' ?>/>
					<label for="is_delete_source"><?php _e('Delete source XML file after importing', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('This setting takes effect only when script has access rights to perform the action, e.g. file is not deleted when pulled via HTTP or delete permission is not granted to the user that script is executed under.', 'pmxi_plugin') ?>">?</a></label>
				</div>
			<?php endif ?>
			<?php if (class_exists('PMLC_Plugin')): // option is only valid when `WP Wizard Cloak` pluign is enabled ?>
				<div class="input">
					<input type="hidden" name="is_cloak" value="0" />
					<input type="checkbox" id="is_cloak" name="is_cloak" value="1" <?php echo $post['is_cloak'] ? 'checked="checked"': '' ?>/>
					<label for="is_cloak"><?php _e('Auto-Cloak Links', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php printf(__('Automatically process all links present in body of created post or page with <b>%s</b> plugin', 'pmxi_plugin'), PMLC_Plugin::getInstance()->getName()) ?>">?</a></label>
				</div>
			<?php endif ?>
			<?php if (in_array($source_type, array('url', 'ftp', 'file'))): ?>
				<div class="input">
					<input type="hidden" name="is_scheduled" value="0" />
					<input type="checkbox" id="is_scheduled" class="switcher" name="is_scheduled" value="1" disabled="disabled"/>
					<label for="is_scheduled"><?php _e('Recurring import', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('Consider this option if you want this import task to be run automatically on regular basis.', 'pmxi_plugin') ?>">?</a></label>
					<a href="http://www.wpallimport.com/upgrade-to-pro?from=ri" target="_blank">Upgrade to pro for recurring/scheduled imports.</a>
				</div>
			<?php endif ?>
			<h3><?php _e('Post Status', 'pmxi_plugin') ?></h3>
			<div>
				<input type="radio" id="status_publish" name="status" value="publish" <?php echo 'publish' == $post['status'] ? 'checked="checked"' : '' ?> />
				<label for="status_publish"><?php _e('Published', 'pmxi_plugin') ?></label>
				&nbsp;
				<input type="radio" id="status_draft" name="status" value="draft" <?php echo 'draft' == $post['status'] ? 'checked="checked"' : '' ?> />
				<label for="status_draft"><?php _e('Draft', 'pmxi_plugin') ?></label>
			</div>
			
			<h3><?php _e('Post Dates', 'pmxi_plugin') ?> <a href="#help" class="help" title="<?php _e('Use any format supported by <b>strtotime</b>', 'pmxi_plugin') ?>">?</a></h3>
			<div class="input">
				<input type="radio" id="date_type_specific" class="switcher" name="date_type" value="specific" checked="checked"/>
				<label for="date_type_specific">
					<?php _e('As specified', 'pmxi_plugin') ?>
				</label>
				<span class="switcher-target-date_type_specific" style="vertical-align:middle">
					<input type="text" class="datepicker" name="date" value="<?php echo esc_attr($post['date']) ?>" />
				</span>
			</div>
			<div class="input">
				<input type="radio" id="date_type_random" class="switcher" value="random" disabled="disabled" />
				<label for="date_type_random">
					<?php _e('Random dates', 'pmxi_plugin') ?>
				</label>
				<span class="" style="vertical-align:middle">
					<?php _e('between', 'pmxi_plugin') ?>
					<input type="text" class="datepicker" name="date_start" value="<?php echo esc_attr($post['date_start']) ?>" />
					<?php _e('and', 'pmxi_plugin') ?>
					<input type="text" class="datepicker" name="date_end" value="<?php echo esc_attr($post['date_end']) ?>" />
				</span>
				<a href="http://www.wpallimport.com/upgrade-to-pro?from=rd" target="_blank">To create posts with random dates, upgrade to pro.</a>
			</div>
			
			<h3><?php _e('Custom Fields', 'pmxi_plugin') ?></h3>
			<table class="form-table custom-params">
			<thead>
				<tr>
					<td><?php _e('Name', 'pmxi_plugin') ?></td>
					<td><?php _e('Value', 'pmxi_plugin') ?></td>
					<td></td>
				</tr>
			</thead>
			<tbody>
				<tr class="form-field">
					<td><input type="text" name="custom_name[]" value="" disabled="disabled"/></td>
					<td><textarea name="custom_value[]" disabled="disabled"></textarea></td>
					<td class="action remove"></td>
				</tr>
				<tr>
					<td colspan="3"> <a href="http://www.wpallimport.com/upgrade-to-pro?from=cf" target="_blank">To import data to Custom Fields (including fields in Custom Post Types), upgrade to pro.</a> </td>					
				</tr>
			</tbody>
			</table>
			<br />
			<div>
				<input type="hidden" name="comment_status" value="closed" />
				<input type="checkbox" id="comment_status" name="comment_status" value="open" <?php echo 'open' == $post['comment_status'] ? 'checked="checked"' : '' ?> />
				<label for="comment_status"><?php _e('Allow Comments', 'pmxi_plugin') ?></label>
			</div>
			<div>
				<input type="hidden" name="ping_status" value="closed" />
				<input type="checkbox" id="ping_status" name="ping_status" value="open" <?php echo 'open' == $post['ping_status'] ? 'checked="checked"' : '' ?> />
				<label for="ping_status"><?php _e('Allow Trackbacks and Pingbacks', 'pmxi_plugin') ?></label>
			</div>
			
			<h3><?php _e('Post Author', 'pmxi_plugin') ?></h3>
			<div>
				<?php wp_dropdown_users(array('name' => 'author', 'selected' => $post['author'])); ?>
			</div>
			
			<h3><?php _e('Featured Image', 'pmxi_plugin') ?></h3>
			<div>
				<input type="text" name="featured_image" disabled="disabled" style="width:300px;" value="<?php echo esc_attr($post['featured_image']) ?>" /> <span>Separate multiple image URLs with commas</span> <br/>
				<a href="http://www.wpallimport.com/upgade-to-pro?from=fi" target="_blank">To import images to the post image gallery and set the Featured Image, upgrade to pro.</a>
			</div>
			
			<h2><?php _e('Reimport / Update Options', 'pmxi_plugin') ?></h2>
			<hr />
			
			<h3>
				<?php _e('Post Unique Key', 'pmxi_plugin') ?>
				<a href="#help" class="help" title="<?php _e('XPath expression which is used to detect correspondence between previously imported records and new ones. An expression used for the title is suitable in the most cases, but using recurring tag unique attribute, e.g. ID, if present, is good alternative as well.', 'pmxi_plugin') ?>">?</a>
			</h3>
			<div class="input">
				<input type="text" class="smaller-text" name="unique_key" value="<?php echo esc_attr($post['unique_key']) ?>" <?php echo  ! ($this->isWizard && $update_previous->isEmpty()) ? 'disabled="disabled"' : '' ?>/>
			</div>
			<br />
			<div>
				<input type="hidden" name="is_delete_missing" value="0" />
				<input type="checkbox" id="is_delete_missing" name="is_delete_missing" value="1" <?php echo $post['is_delete_missing'] ? 'checked="checked"': '' ?> />
				<label for="is_delete_missing"><?php _e('Delete missing records', 'pmxi_plugin') ?></label>
				<a href="#help" class="help" title="<?php _e('Check this option if you want to delete posts from previous import operation which are not found among newly impoprted set.', 'pmxi_plugin') ?>">?</a>
			</div>
			<div>
				<input type="hidden" name="is_keep_former_posts" value="0" />
				<input type="checkbox" id="is_keep_former_posts" name="is_keep_former_posts" value="1" <?php echo $post['is_keep_former_posts'] ? 'checked="checked"': '' ?> class="switcher switcher-reversed" />
				<label for="is_keep_former_posts"><?php _e('Do not update already existing records', 'pmxi_plugin') ?></label>
				<a href="#help" class="help" title="<?php _e('Check this option if you do not want to update already esisting records. The option is useful if you plan to manually edit imported records and do not want them to be overwritten.<br /><br /><b>Important</b>: The option applies to the posts or pages associated with the import being updated. To handle potential conflicts with post or pages which are not associated with this import please use `Check for duplicates` option in `General Options` secion above.', 'pmxi_plugin') ?>">?</a>
			</div>
			<div class="switcher-target-is_keep_former_posts">
				<div>
					<input type="hidden" name="is_keep_status" value="0" />
					<input type="checkbox" id="is_keep_status" name="is_keep_status" value="1" <?php echo $post['is_keep_status'] ? 'checked="checked"': '' ?> />
					<label for="is_keep_status"><?php _e('Keep status', 'pmxi_plugin') ?></label>
					<a href="#help" class="help" title="<?php _e('Check this option if you do not want previously imported posts to change their publish status or being restored from Trash.', 'pmxi_plugin') ?>">?</a>
				</div>
				<div>
					<input type="hidden" name="is_keep_content" value="0" />
					<input type="checkbox" id="is_keep_content" name="is_keep_content" value="1" <?php echo $post['is_keep_content'] ? 'checked="checked"': '' ?> />
					<label for="is_keep_content"><?php _e('Keep content', 'pmxi_plugin') ?></label>
					<a href="#help" class="help" title="<?php _e('Re-run an importer to pull in one more custom field... without nuking their edits in the process..', 'pmxi_plugin') ?>">?</a>
				</div>
				<div>
					<input type="hidden" name="is_keep_categories" value="0" />
					<input type="checkbox" id="is_keep_categories" name="is_keep_categories" value="1" <?php echo $post['is_keep_categories'] ? 'checked="checked"': '' ?> />
					<label for="is_keep_categories"><?php _e('Keep categories, tags and taxonomies', 'pmxi_plugin') ?></label>
					<a href="#help" class="help" title="<?php _e('Check this option if you do not want previously imported posts to change their category, tag and custom taxonomies associations upon reimport.', 'pmxi_plugin') ?>">?</a>
				</div>
			</div>
			<div>
				<input type="hidden" name="is_keep_attachments" value="0" />
				<input type="checkbox" id="is_keep_attachments" name="is_keep_attachments" value="1" <?php echo $post['is_keep_attachments'] ? 'checked="checked"': '' ?> />
				<label for="is_keep_attachments"><?php _e('Keep attachments when records removed', 'pmxi_plugin') ?></label>
				<a href="#help" class="help" title="<?php _e('Check this option if you want attachments like featured image to be kept in media library after parent post or page is removed or replaced during reimport operation.', 'pmxi_plugin') ?>">?</a>
			</div>
			
			<br />
			<p>
				<?php wp_nonce_field('options', '_wpnonce_options') ?>
				<input type="hidden" name="is_submitted" value="1" />
				
				<?php if ($this->isWizard): ?>
					<a href="<?php echo add_query_arg('action', 'template', $this->baseUrl) ?>" class="button back"><?php _e('Back', 'pmxi_plugin') ?></a>
					&nbsp;
					<input type="submit" class="button-primary ajax-import" value="<?php _e('Save &amp; Create Posts', 'pmxi_plugin') ?>" />
					
					<?php if (in_array($source_type, array('url', 'ftp', 'file'))): ?>
						or
						<input type="submit" name="save_only" class="button" value="<?php _e('Save Only', 'pmxi_plugin') ?>" />
					<?php endif ?>
					
				<?php else: ?>
					<input type="submit" class="button-primary" value="<?php _e('Edit', 'pmxi_plugin') ?>" />
				<?php endif ?>
			</p>
			
		</td>
		<?php if ($this->isWizard or $this->isTemplateEdit): ?>
			<td class="right">
				<p><?php _e('Drag &amp; Drop opening tag of an element for inserting corresponding XPath into a form element.', 'pmxi_plugin') ?></p>
				<?php $this->tag() ?>
			</td>
		<?php endif ?>
	</tr>
	</table>
</form>
<script type="text/javascript">
(function($){$(function(){
	$('select[name="custom_type"]').change(function () {
		var type = $(this).val(); if ('' == type) type = 'post';
		$('tr.post_taxonomy').each(function () {
			$(this)[$.inArray(type, $(this).data('type').split(' ')) < 0 ? 'hide' : 'fadeIn']();
		});
	}).change();
});})(jQuery);
</script>
