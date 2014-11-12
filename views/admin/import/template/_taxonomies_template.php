<?php 

$custom_type = get_post_type_object( $post_type ); 

$exclude_taxonomies = (class_exists('PMWI_Plugin')) ? array('post_format', 'product_type', 'product_shipping_class') : array('post_format');	
$post_taxonomies = array_diff_key(get_taxonomies_by_object_type(array($post_type), 'object'), array_flip($exclude_taxonomies));

if ( ! empty($post_taxonomies)): 
?>
	<div class="wpallimport-collapsed closed wpallimport-section">
		<div class="wpallimport-content-section">
			<div class="wpallimport-collapsed-header">
				<h3><?php _e('Taxonomies, Categories, Tags','pmxi_plugin');?></h3>	
			</div>
			<div class="wpallimport-collapsed-content" style="padding: 0;">
				<div class="wpallimport-collapsed-content-inner">
					<input type="button" rel="taxonomies_hints" value="<?php _e('Show Hints', 'pmxi_plugin');?>" class="show_hints">
					<table class="form-table" style="max-width:none;">
					
						<?php $private_ctx = 0; ?>	
						<tr>
							<td colspan="3" style="padding-bottom:20px;">								
								<?php foreach ($post_taxonomies as $ctx): if ("" == $ctx->labels->name or (class_exists('PMWI_Plugin') and strpos($ctx->name, "pa_") === 0 and $post_type == "product")) continue;?>					
								<?php if (! $ctx->show_ui ) $private_ctx++; ?>
								<table style="width:100%;">
									<tr class="<?php echo ( ! $ctx->show_ui) ? 'private_ctx' : ''; ?>">
										<td>
											<div class="post_taxonomy">
												<div class="input">
													<input type="hidden" name="tax_assing[<?php echo $ctx->name;?>]" value="0"/>
													<input type="checkbox" class="assign_post switcher" name="tax_assing[<?php echo $ctx->name;?>]" id="tax_assing_<?php echo $ctx->name;?>" <?php echo ( ! empty($post['tax_assing'][$ctx->name]) ) ? 'checked="checked"' : ''; ?> title="<?php _e('Assign post to the taxonomy.','pmxi_plugin');?>" value="1"/>
													<label for="tax_assing_<?php echo $ctx->name;?>"><?php echo $ctx->labels->name; ?></label>											
												</div>
												<div class="switcher-target-tax_assing_<?php echo $ctx->name;?>">
													<div class="input sub_input">
														<div class="input">
															<input type="radio" name="tax_logic[<?php echo $ctx->name;?>]" value="single" id="tax_logic_single_<?php echo $ctx->name;?>" class="switcher" <?php echo (empty($post['tax_logic'][$ctx->name]) or $post['tax_logic'][$ctx->name] == 'single') ? 'checked="checked"' : ''; ?>/>
															<label for="tax_logic_single_<?php echo $ctx->name;?>"><?php printf(__('Each %s has just one %s', 'pmxi_plugin'), $custom_type->labels->singular_name, $ctx->labels->singular_name); ?></label>
															<div class="switcher-target-tax_logic_single_<?php echo $ctx->name;?> sub_input">
																<input type="text" class="widefat single_xpath_field" name="tax_single_xpath[<?php echo $ctx->name; ?>]" value="<?php echo ( ! empty($post['tax_single_xpath'][$ctx->name])) ? esc_attr($post['tax_single_xpath'][$ctx->name]) : ''; ?>" style="width:50%;"/>
															</div>
														</div>
														<div class="input">
															<input type="radio" name="tax_logic[<?php echo $ctx->name;?>]" value="multiple" id="tax_logic_multiple_<?php echo $ctx->name;?>" class="switcher" <?php echo (!empty($post['tax_logic'][$ctx->name]) and $post['tax_logic'][$ctx->name] == 'multiple') ? 'checked="checked"' : ''; ?>/>
															<label for="tax_logic_multiple_<?php echo $ctx->name;?>"><?php printf(__('Each %s has multiple %s', 'pmxi_plugin'), $custom_type->labels->singular_name, $ctx->labels->name); ?></label>
															<div class="switcher-target-tax_logic_multiple_<?php echo $ctx->name;?> sub_input">
																<input type="text" class="widefat multiple_xpath_field" name="tax_multiple_xpath[<?php echo $ctx->name; ?>]" value="<?php echo ( ! empty($post['tax_multiple_xpath'][$ctx->name])) ? esc_attr($post['tax_multiple_xpath'][$ctx->name]) : ''; ?>" style="width:50%;"/>
																<label><?php _e('Separated by', 'pmxi_plugin'); ?></label>										
																<input type="text" class="small tax_delim" name="tax_multiple_delim[<?php echo $ctx->name; ?>]" value="<?php echo ( ! empty($post['tax_multiple_delim'][$ctx->name]) ) ? str_replace("&amp;","&", htmlentities(htmlentities($post['tax_multiple_delim'][$ctx->name]))) : ',' ?>" />
															</div>
														</div>
														<?php if ($ctx->hierarchical): ?>
														<div class="input">
															<input type="radio" name="tax_logic[<?php echo $ctx->name;?>]" value="hierarchical" id="tax_logic_hierarchical_<?php echo $ctx->name;?>" class="switcher" <?php echo (!empty($post['tax_logic'][$ctx->name]) and $post['tax_logic'][$ctx->name] == 'hierarchical') ? 'checked="checked"' : ''; ?>/>
															<label for="tax_logic_hierarchical_<?php echo $ctx->name;?>"><?php printf(__('%ss have hierarchical (parent/child) %s (i.e. Sports > Golf > Clubs > Putters)', 'pmxi_plugin'), $custom_type->labels->singular_name, $ctx->labels->name); ?></label>
															<div class="switcher-target-tax_logic_hierarchical_<?php echo $ctx->name;?> sub_input">
																<div class="input">
																	<input type="radio" name="tax_hierarchical_logic[<?php echo $ctx->name;?>]" value="entire" id="hierarchical_logic_entire_<?php echo $ctx->name;?>" class="switcher" <?php echo (empty($post['tax_hierarchical_logic'][$ctx->name]) or "entire" == $post['tax_hierarchical_logic'][$ctx->name]) ? 'checked="checked"' : ''; ?>/>
																	<label for="hierarchical_logic_entire_<?php echo $ctx->name;?>"><?php _e('An element in my file contains the entire hierarchy (i.e. you have an element with a value = Sports > Golf > Clubs > Putters)', 'pmxi_plugin'); ?></label>
																	<div class="switcher-target-hierarchical_logic_entire_<?php echo $ctx->name;?> sub_input">
																		<input type="text" class="widefat hierarchical_xpath_field" name="tax_hierarchical_xpath[<?php echo $ctx->name; ?>]" value="<?php echo ( ! empty($post['tax_hierarchical_xpath'][$ctx->name])) ? esc_attr($post['tax_hierarchical_xpath'][$ctx->name]) : ''; ?>" style="width:50%;"/>
																		<label><?php _e('Separated by', 'pmxi_plugin'); ?></label>										
																		<input type="text" class="small tax_delim" name="tax_hierarchical_delim[<?php echo $ctx->name; ?>]" value="<?php echo ( ! empty($post['tax_hierarchical_delim'][$ctx->name]) ) ? str_replace("&amp;","&", htmlentities(htmlentities($post['tax_hierarchical_delim'][$ctx->name]))) : '>' ?>" />
																		<a class="preview_taxonomies" href="javascript:void(0);" style="top:0px;" rel="preview_taxonomies"><?php _e('Preview', 'pmxi_plugin'); ?></a>
																	</div>
																</div>
																<div class="input">
																	<input type="radio" name="tax_hierarchical_logic[<?php echo $ctx->name;?>]" value="manual" id="hierarchical_logic_manual_<?php echo $ctx->name;?>" class="switcher" <?php echo (!empty($post['tax_hierarchical_logic'][$ctx->name]) and $post['tax_hierarchical_logic'][$ctx->name] == 'manual') ? 'checked="checked"' : ''; ?>/>
																	<label for="hierarchical_logic_manual_<?php echo $ctx->name;?>"><?php _e('Manually design the hierarchy with drag & drop', 'pmxi_plugin'); ?></label>
																	<div class="switcher-target-hierarchical_logic_manual_<?php echo $ctx->name;?> sub_input">
																		<p style="margin-bottom: 10px;"><?php printf(__('Drag the <img src="%s" class="wpallimport-drag-icon"/> to the right to create a child, drag up and down to re-order.'), PMXI_ROOT_URL . '/static/img/drag.png'); ?></p>
																		<ol class="sortable no-margin">
																			<?php
																			if ( ! empty($post['post_taxonomies'][$ctx->name]) ):

																				$taxonomies_hierarchy = json_decode($post['post_taxonomies'][$ctx->name]);
																				
																				if (!empty($taxonomies_hierarchy) and is_array($taxonomies_hierarchy)): $i = 0; 

																					foreach ($taxonomies_hierarchy as $cat) { $i++;
																						if (is_null($cat->parent_id) or empty($cat->parent_id))
																						{
																							?>
																							<li id="item_<?php echo $i; ?>" class="dragging">
																								<div class="drag-element">																	
																									<input type="text" class="widefat xpath_field" value="<?php echo esc_attr($cat->xpath); ?>"/>
																									
																									<?php do_action('pmxi_category_view', $cat, $i, $ctx->name, $post_type); ?>

																								</div>
																								<?php if ($i>1):?><a href="javascript:void(0);" class="icon-item remove-ico"></a><?php endif;?>
																								<?php echo reverse_taxonomies_html($taxonomies_hierarchy, $cat->item_id, $i, $ctx->name, $post_type); ?>
																							</li>
																							<?php
																						}
																					}

																				endif;

																			endif;?>

																			<li id="item" class="template">
																		    	<div class="drag-element">														    		
																		    		<input type="text" class="widefat xpath_field" value=""/>
																		    		<?php do_action('pmxi_category_view', false, false, $ctx->name, $post_type); ?>
																		    	</div>
																		    	<a href="javascript:void(0);" class="icon-item remove-ico"></a>
																		    </li>

																		</ol>
																		<a href="javascript:void(0);" class="icon-item add-new-ico"><?php _e('Add Another','pmxi_plugin');?></a>
																		<input type="hidden" class="hierarhy-output" name="post_taxonomies[<?php echo $ctx->name; ?>]" value="<?php echo esc_attr($post['post_taxonomies'][$ctx->name]) ?>"/>									
																		<?php do_action('pmxi_category_options_view', ((!empty($post['post_taxonomies'][$ctx->name])) ? $post['post_taxonomies'][$ctx->name] : false), $ctx->name, $post_type, $ctx->labels->name); ?>														
																	</div>
																</div>
															</div>
														</div>
														<?php endif; ?>
														<div class="input" style="margin: 4px;">													
															<?php
																$tax_mapping = ( ! empty($post['tax_mapping'][$ctx->name]) ) ? json_decode($post['tax_mapping'][$ctx->name], true) : false;
															?>
															<input type="hidden" name="tax_enable_mapping[<?php echo $ctx->name; ?>]" value="0"/>
															<input type="checkbox" id="tax_mapping_<?php echo $ctx->name; ?>" class="pmxi_tax_mapping switcher" <?php if ( ! empty($post['tax_enable_mapping'][$ctx->name]) ) echo "checked='checked'"; ?> name="tax_enable_mapping[<?php echo $ctx->name; ?>]" value="1"/>
															<label for="tax_mapping_<?php echo $ctx->name;?>"><?php printf(__('Enable Mapping for %s', 'pmxi_plugin'), $ctx->labels->name); ?></label>
															<div class="switcher-target-tax_mapping_<?php echo $ctx->name;?> sub_input custom_type" rel="tax_mapping">
																<fieldset style="padding: 0;">															
																	<table cellpadding="0" cellspacing="5" class="tax-form-table" rel="tax_mapping_<?php echo $ctx->name; ?>" style="width: 100%;">
																		<thead>
																			<tr>
																				<td><?php _e('In Your File', 'pmxi_plugin') ?></td>
																				<td><?php _e('Translated To', 'pmxi_plugin') ?></td>
																				<td>&nbsp;</td>						
																			</tr>
																		</thead>
																		<tbody>	
																			<?php																																				
																				if ( ! empty($tax_mapping) and is_array($tax_mapping) ){

																					foreach ($tax_mapping as $key => $value) {

																						$k = $key;

																						if (is_array($value)){
																							$keys = array_keys($value);
																							$k = $keys[0];
																						}

																						?>
																						<tr class="form-field">
																							<td>
																								<input type="text" class="mapping_from widefat" value="<?php echo $k; ?>">
																							</td>
																							<td>
																								<input type="text" class="mapping_to widefat" value="<?php echo (is_array($value)) ? $value[$k] : $value; ?>">
																							</td>
																							<td class="action remove">
																								<a href="#remove" style="right:-10px; top: 7px;"></a>
																							</td>
																						</tr>
																						<?php
																					}

																				}
																				else{
																					?>
																					<tr class="form-field">
																						<td>
																							<input type="text" class="mapping_from widefat">
																						</td>
																						<td>
																							<input type="text" class="mapping_to widefat">
																						</td>
																						<td class="action remove">
																							<a href="#remove" style="right:-10px; top: 7px;"></a>
																						</td>
																					</tr>
																					<?php
																				}
																			?>												
																			<tr class="form-field template">
																				<td>
																					<input type="text" class="mapping_from widefat">
																				</td>
																				<td>
																					<input type="text" class="mapping_to widefat">
																				</td>
																				<td class="action remove">
																					<a href="#remove" style="right:-10px; top: 7px;"></a>
																				</td>
																			</tr>
																			<tr>
																				<td colspan="3">
																					<a href="javascript:void(0);" title="<?php _e('Add Another', 'pmxi_plugin')?>" class="action add-new-key add-new-entry"><?php _e('Add Another', 'pmxi_plugin') ?></a>
																				</td>
																			</tr>																	
																		</tbody>
																	</table>															
																	<input type="hidden" name="tax_mapping[<?php echo $ctx->name; ?>]" value="<?php if (!empty($post['tax_mapping'][$ctx->name])) echo esc_html($post['tax_mapping'][$ctx->name]); ?>"/>
																</fieldset>
															</div>
														</div>
													</div>											
												</div>
											</div>
										</td>
									</tr>
								</table>					
								<?php endforeach; ?>	
								<?php if ($private_ctx): ?>						
								<hr/>			
								<div class="input">
									<input type="checkbox" id="show_hidden_ctx"/>
									<label for="show_hidden_ctx"><?php _e('Show "private" taxonomies', 'pmxi_plugin'); ?></label>					
								</div>
								<?php endif;?>
							</td>
						</tr>												
					</table>
				</div>
			</div>
		</div>
		<div id="taxonomies_hints" style="display:none;">	
			<ul>
				<li><?php _e('Taxonomies that don\'t already exist on your site will be created.', 'pmxi_plugin'); ?></li>
				<li><?php _e('To import to existing parent taxonomies, use the existing taxonomy name or slug.', 'pmxi_plugin'); ?></li>
				<li><?php _e('To import to existing hierarchical taxonomies, create the entire hierarchy using the taxonomy names or slugs.', 'pmxi_plugin'); ?></li>			
			</ul>
		</div>
	</div>
<?php endif; ?>		