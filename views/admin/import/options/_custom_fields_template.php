<tr>
	<td colspan="3" style="padding-top:20px;">
		<fieldset class="optionsset" style="text-align:center;">
			<legend><?php _e('Custom Fields','pmxi_plugin');?></legend>

			<center>

				<h3>Please upgrade to the professional edition of WP All Import to import data to Custom Fields.</h3>

				<p style='font-size: 1.3em; font-weight: bold;'><a href="http://www.wpallimport.com/upgrade-to-pro?utm_source=wordpress.org&utm_medium=custom-fields&utm_campaign=free+plugin" target="_blank" class="upgrade_link">Upgrade Now</a></p>

				<hr />

			</center>

			<table class="form-table custom-params" style="max-width:none; border:none;">
				<thead>
					<tr>
						<td><?php _e('Name', 'pmxi_plugin') ?></td>
						<td><?php _e('Value', 'pmxi_plugin') ?></td>					
					</tr>
				</thead>
				<tbody>				
					<?php if (!empty($post['custom_name'])):?>
						<?php foreach ($post['custom_name'] as $i => $name): ?>
							<tr class="form-field">
								<td>
									<input type="text" name="custom_name[]"  value="<?php echo esc_attr($name) ?>" class="widefat" disabled="disabled"/>
									<div class="input to_the_left">									
										<input id="custom_format_<?php echo $i; ?>" type="checkbox" name="custom_format[]" <?php if ( ! empty($post['custom_format'][$i]) ): ?>checked="checked"<?php endif; ?> style="width:12px;" value="1"/>
										<label for="custom_format_<?php echo $i; ?>"><?php _e('Serialized format', 'pmxi_plugin') ?></label>
									</div>
								</td>
								<td class="action">
									<div class="custom_type" rel="default" <?php if ( ! empty($post['custom_format'][$i]) ): ?>style="display:none;"<?php endif; ?>>
										<textarea name="custom_value[]" class="widefat" disabled="disabled"><?php echo esc_html($post['custom_value'][$i]) ?></textarea>
									</div>
									<div class="custom_type" rel="serialized" <?php if ( empty($post['custom_format'][$i]) ): ?>style="display:none;"<?php endif; ?>>
										<table cellpadding="0" cellspacing="5">
											<thead>
												<tr>
													<td><?php _e('Key', 'pmxi_plugin') ?></td>
													<td><?php _e('Value', 'pmxi_plugin') ?></td>
													<td>&nbsp;</td>						
												</tr>
											</thead>
											<tbody>	
												<?php
													$serialized_values = (!empty($post['serialized_values'][$i])) ? json_decode($post['serialized_values'][$i], true) : false;
													
													if (!empty($serialized_values) and is_array($serialized_values)){
														foreach ($serialized_values as $key => $value) {

															$k = $key;

															if (is_array($value)){
																$keys = array_keys($value);
																$k = $keys[0];
															}

															?>
															<tr class="form-field">
																<td>
																	<input type="text" class="serialized_key widefat" value="<?php echo $k; ?>" disabled="disabled">
																</td>
																<td>
																	<input type="text" class="serialized_value widefat" value="<?php echo (is_array($value)) ? $value[$k] : $value; ?>" disabled="disabled">
																</td>
																<td class="action remove">
																	<a href="#remove" style="right:-10px;"></a>
																</td>
															</tr>
															<?php
														}
													}
													else{
														?>
														<tr class="form-field">
															<td>
																<input type="text" class="serialized_key widefat" disabled="disabled">
															</td>
															<td>
																<input type="text" class="serialized_value widefat" disabled="disabled">
															</td>
															<td class="action remove">
																<a href="#remove" style="right:-10px;"></a>
															</td>
														</tr>
														<?php
													}
												?>												
												<tr class="form-field template">
													<td>
														<input type="text" class="serialized_key widefat" disabled="disabled">
													</td>
													<td>
														<input type="text" class="serialized_value widefat" disabled="disabled">
													</td>
													<td class="action remove">
														<a href="#remove" style="right:-10px;"></a>
													</td>
												</tr>
												<tr>
													<td colspan="3">
														<a href="javascript:void(0);" title="<?php _e('add', 'pmxi_plugin')?>" class="action add-new-key"><?php _e('Add more', 'pmxi_plugin') ?></a>
													</td>
												</tr>
											</tbody>
										</table>
										<input type="hidden" name="serialized_values[]" value="<?php if (!empty($post['serialized_values'][$i])) echo esc_html($post['serialized_values'][$i]); ?>"/>
									</div>
									<span class="action remove">
										<a href="#remove"></a>
									</span>
								</td>														
							</tr>
						<?php endforeach ?>
					<?php else: ?>
						<tr class="form-field">
							<td>
								<input type="text" name="custom_name[]"  value="" class="widefat" disabled="disabled"/>
								<div class="input to_the_left">																		
									<input id="custom_format_0" type="checkbox" name="custom_format[]" style="width:12px;" value="1"/>
									<label for="custom_format_0"><?php _e('Serialized format', 'pmxi_plugin') ?></label>
								</div>
							</td>
							<td class="action">
								<div class="custom_type" rel="default">
									<textarea name="custom_value[]" class="widefat" disabled="disabled"></textarea>
								</div>
								<div class="custom_type" rel="serialized" style="display:none;">
									<table cellpadding="0" cellspacing="5">
										<thead>
											<tr>
												<td><?php _e('Key', 'pmxi_plugin') ?></td>
												<td><?php _e('Value', 'pmxi_plugin') ?></td>
												<td>&nbsp;</td>						
											</tr>
										</thead>
										<tbody>	
											<tr class="form-field">
												<td>
													<input type="text" class="serialized_key widefat" disabled="disabled">
												</td>
												<td>
													<input type="text" class="serialized_value widefat" disabled="disabled">
												</td>
												<td class="action remove">
													<a href="#remove" style="right:-10px;"></a>
												</td>
											</tr>
											<tr class="form-field template">
												<td>
													<input type="text" class="serialized_key widefat" disabled="disabled">
												</td>
												<td>
													<input type="text" class="serialized_value widefat" disabled="disabled">
												</td>
												<td class="action remove">
													<a href="#remove" style="right:-10px;"></a>
												</td>
											</tr>
											<tr>
												<td colspan="3">
													<a href="javascript:void(0);" title="<?php _e('add', 'pmxi_plugin')?>" class="action add-new-key"><?php _e('Add more', 'pmxi_plugin') ?></a>
												</td>
											</tr>
										</tbody>
									</table>
									<input type="hidden" name="serialized_values[]" value=""/>
								</div>
								<span class="action remove">
									<a href="#remove"></a>
								</span>
							</td>													
						</tr>
					<?php endif;?>
					<tr class="form-field template">
						<td>
							<input type="text" name="custom_name[]" value="" class="widefat" disabled="disabled"/>
							<div class="input to_the_left">
								<input type="checkbox" id="custom_format" name="custom_format[]" style="width:12px;" value="1"/>
								<label for="custom_format"><?php _e('Serialized format', 'pmxi_plugin') ?></label>
							</div>
						</td>
						<td class="action">
							<div class="custom_type" rel="default">
								<textarea name="custom_value[]" class="widefat" disabled="disabled"></textarea>
							</div>
							<div class="custom_type" rel="serialized" style="display:none;">
								<table cellpadding="0" cellspacing="5">
									<thead>
										<tr>
											<td><?php _e('Key', 'pmxi_plugin') ?></td>
											<td><?php _e('Value', 'pmxi_plugin') ?></td>	
											<td>&nbsp;</td>				
										</tr>
									</thead>
									<tbody>
										<tr class="form-field">
											<td>
												<input type="text" class="serialized_key widefat" disabled="disabled">
											</td>
											<td>
												<input type="text" class="serialized_value widefat" disabled="disabled">
											</td>
											<td class="action remove">
												<a href="#remove" style="right:-10px;"></a>
											</td>
										</tr>
										<tr class="form-field template">
											<td>
												<input type="text" class="serialized_key widefat" disabled="disabled">
											</td>
											<td>
												<input type="text" class="serialized_value widefat" disabled="disabled">
											</td>
											<td class="action remove">
												<a href="#remove" style="right:-10px;"></a>
											</td>
										</tr>
										<tr>
											<td colspan="3">
												<a href="javascript:void(0);" title="<?php _e('add', 'pmxi_plugin')?>" class="action add-new-key"><?php _e('Add more', 'pmxi_plugin') ?></a>
											</td>
										</tr>
									</tbody>
								</table>
								<input type="hidden" name="serialized_values[]" value=""/>
							</div>
							<span class="action remove">
								<a href="#remove"></a>
							</span>
						</td>
					</tr>
					<tr>
						<td colspan="2"><a href="javascript:void(0);" title="<?php _e('add', 'pmxi_plugin')?>" class="action add-new-custom"><?php _e('Add more', 'pmxi_plugin') ?></a></td>
					</tr>
				</tbody>
			</table>
			<select class="existing_meta_keys">
				<option value=""><?php _e('Existing Custom Fields...','pmxi_plugin');?></option>
				<?php				
				$hide_fields = array('_wp_page_template', '_edit_lock', '_edit_last', '_wp_trash_meta_status', '_wp_trash_meta_time');
				if (!empty($meta_keys) and $meta_keys->count()):
					foreach ($meta_keys as $meta_key) { if (in_array($meta_key['meta_key'], $hide_fields) or strpos($meta_key['meta_key'], '_wp') === 0) continue;
						?>
						<option value="<?php echo $meta_key['meta_key'];?>"><?php echo $meta_key['meta_key'];?></option>
						<?php
					}
				endif;
				?>
			</select>
			<br/>			
		</fieldset>		
	</td>
</tr>