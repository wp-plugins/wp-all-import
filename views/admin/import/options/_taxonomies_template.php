<?php	
	$exclude_taxonomies = (class_exists('PMWI_Plugin')) ? array('category', 'post_tag', 'post_format', 'product_type') : array('category', 'post_format', 'post_tag');	
	$post_taxonomies = array_diff_key(get_taxonomies_by_object_type(array($post_type), 'object'), array_flip($exclude_taxonomies));
	if ( ! empty($post_taxonomies)): ?>
	<tr>
		<td colspan="3" style="padding-bottom:20px;">
			<fieldset class="optionsset">
				<legend><?php _e('Custom Taxonomies','pmxi_plugin');?></legend>
				<?php foreach ($post_taxonomies as $ctx): if ("" == $ctx->labels->name or (class_exists('PMWI_Plugin') and strpos($ctx->name, "pa_") === 0 and $post_type == "product")) continue;?>
				<table style="width:100%;">
					<tr>
						<td>
							<div class="post_taxonomy">
								<div class="col2" style="width:35%;">
									<nobr><?php echo $ctx->labels->name ?></nobr>
								</div>
								<div class="col2" style="width:65%;">
									<ol class="sortable no-margin">
										<?php
										if (!empty($post['post_taxonomies'][$ctx->name])):

											$taxonomies_hierarchy = json_decode($post['post_taxonomies'][$ctx->name]);
											
											if (!empty($taxonomies_hierarchy) and is_array($taxonomies_hierarchy)): $i = 0; 

												foreach ($taxonomies_hierarchy as $cat) { $i++;
													if (is_null($cat->parent_id) or empty($cat->parent_id))
													{
														?>
														<li id="item_<?php echo $i; ?>" class="dragging">
															<div class="drag-element">
																<input type="checkbox" class="assign_post" <?php if ($cat->assign): ?>checked="checked"<?php endif; ?> title="<?php _e('Assign post to the taxonomy.','pmxi_plugin');?>"/>																
																<input type="text" class="widefat xpath_field" value="<?php echo esc_attr($cat->xpath); ?>"/>
																
																<?php do_action('pmxi_category_view', $cat, $i, $ctx->name, $entry); ?>

															</div>
															<?php if ($i>1):?><a href="javascript:void(0);" class="icon-item remove-ico"></a><?php endif;?>
															<?php echo reverse_taxonomies_html($taxonomies_hierarchy, $cat->item_id, $i, $ctx->name, $entry); ?>
														</li>
														<?php
													}
												}

											endif;

										endif;?>

										<li id="item" class="template">
									    	<div class="drag-element">
									    		<input type="checkbox" class="assign_post" checked="checked" title="<?php _e('Assign post to the taxonomy.','pmxi_plugin');?>"/>									    		
									    		<input type="text" class="widefat xpath_field" value=""/>
									    		<?php do_action('pmxi_category_view', false, false, $ctx->name, $entry); ?>
									    	</div>
									    	<a href="javascript:void(0);" class="icon-item remove-ico"></a>
									    </li>

									</ol>
									<a href="javascript:void(0);" class="icon-item add-new-ico"><?php _e('Add more','pmxi_plugin');?></a>
									<input type="hidden" class="hierarhy-output" name="post_taxonomies[<?php echo $ctx->name ?>]" value="<?php echo esc_attr($post['post_taxonomies'][$ctx->name]) ?>"/>									
									<?php do_action('pmxi_category_options_view', ((!empty($post['post_taxonomies'][$ctx->name])) ? $post['post_taxonomies'][$ctx->name] : false), $ctx->name, $entry, $ctx->labels->name); ?>
									<div class="delim">
										<label><?php _e('Separated by', 'pmxi_plugin'); ?></label>										
										<input type="text" class="small tax_delim" value="<?php echo (!empty($taxonomies_hierarchy) and $taxonomies_hierarchy[0]->delim) ? str_replace("&amp;","&", htmlentities(htmlentities($taxonomies_hierarchy[0]->delim))) : ',' ?>" />
										<label for="nested_<?php echo $ctx->name;?>"><?php _e('Enable Auto Nest', 'pmxi_plugin');?></label>
										<input id="nested_<?php echo $ctx->name;?>" type="checkbox" class="taxonomy_auto_nested" <?php if (!empty($taxonomies_hierarchy) and $taxonomies_hierarchy[0]->auto_nested):?>checked="checked"<?php endif; ?>/>
										<a href="#help" class="help" style="position:relative; top:-1px; left: -5px;" title="<?php _e('If this box is checked, a category hierarchy will be created. For example, if your <code>{category}</code> value is <code>Mens > Shoes > Diesel</code>, enter <code>&gt;</code> as the separator and enable <code>Auto Nest</code> to create <code>Diesel</code> as a child category of <code>Shoes</code> and <code>Shoes</code> as a child category of <code>Mens.</code>', 'pmxi_plugin') ?>">?</a>										
										<?php do_action('pmxi_category_options', ((!empty($post['post_taxonomies'][$ctx->name])) ? $post['post_taxonomies'][$ctx->name] : false), $ctx->name, $entry); ?>
									</div>
								</div>
							</div>
						</td>
					</tr>
				</table>
				<?php endforeach; ?>					
			</fieldset>
		</td>
	</tr>
<?php endif; ?>