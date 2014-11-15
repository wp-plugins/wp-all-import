<?php $custom_type = get_post_type_object( $post_type ); ?>
<div class="wpallimport-collapsed closed wpallimport-section ">
	<div class="wpallimport-content-section ">
		<div class="wpallimport-collapsed-header">
			<h3><?php printf(__('Other %s Options','pmxi_plugin'), $custom_type->labels->singular_name);?></h3>	
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<table class="form-table" style="max-width:none;">
					<tr>
						<td>					
							<input type="hidden" name="encoding" value="<?php echo ($isWizard) ? PMXI_Plugin::$session->encoding : $post['encoding']; ?>"/>
							<input type="hidden" name="delimiter" value="<?php echo ($isWizard) ? PMXI_Plugin::$session->is_csv : $post['delimiter']; ?>"/>

							<?php $is_support_post_format = ( current_theme_supports( 'post-formats' ) && post_type_supports( $post_type, 'post-formats' ) ) ? true : false; ?>
							
							<h4><?php _e('Post Status', 'pmxi_plugin') ?></h4>									
							<div class="input">
								<input type="radio" id="status_publish" name="status" value="publish" <?php echo 'publish' == $post['status'] ? 'checked="checked"' : '' ?> class="switcher"/>
								<label for="status_publish"><?php _e('Published', 'pmxi_plugin') ?></label>
							</div>
							<div class="input">
								<input type="radio" id="status_draft" name="status" value="draft" <?php echo 'draft' == $post['status'] ? 'checked="checked"' : '' ?> class="switcher"/>
								<label for="status_draft"><?php _e('Draft', 'pmxi_plugin') ?></label>
							</div>
							<div class="input fleft" style="position:relative;width:220px; margin-bottom:15px;">
								<input type="radio" id="status_xpath" class="switcher" name="status" value="xpath" <?php echo 'xpath' == $post['status'] ? 'checked="checked"': '' ?>/>
								<label for="status_xpath"><?php _e('Set with XPath', 'pmxi_plugin' )?></label> <br>
								<div class="switcher-target-status_xpath">
									<div class="input">
										&nbsp;<input type="text" class="smaller-text" name="status_xpath" style="width:190px;" value="<?php echo esc_attr($post['status_xpath']) ?>"/>
										<a href="#help" class="wpallimport-help" title="<?php _e('The value of presented XPath should be one of the following: (\'publish\', \'draft\', \'trash\').', 'pmxi_plugin') ?>" style="position:relative; top:13px; float: right;">?</a>
									</div>
								</div>
							</div>								
							<div class="clear"></div>													
						</td>
					</tr>			
					<tr>
						<td>					
							<h4><?php _e('Post Dates', 'pmxi_plugin') ?><a href="#help" class="wpallimport-help" style="position:relative; top: 1px;" title="<?php _e('Use any format supported by the PHP <b>strtotime</b> function. That means pretty much any human-readable date will work.', 'pmxi_plugin') ?>">?</a></h4>
							<div class="input">
								<input type="radio" id="date_type_specific" class="switcher" name="date_type" value="specific" <?php echo 'random' != $post['date_type'] ? 'checked="checked"' : '' ?> />
								<label for="date_type_specific">
									<?php _e('As specified', 'pmxi_plugin') ?>
								</label>
								<div class="switcher-target-date_type_specific" style="vertical-align:middle; margin-top: 5px; margin-bottom: 10px;">
									<input type="text" class="datepicker" name="date" value="<?php echo esc_attr($post['date']) ?>"/>
								</div>
							</div>
							<div class="input">
								<input type="radio" id="date_type_random" class="switcher" name="date_type" value="random" <?php echo 'random' == $post['date_type'] ? 'checked="checked"' : '' ?> />
								<label for="date_type_random">
									<?php _e('Random dates', 'pmxi_plugin') ?><a href="#help" class="wpallimport-help" style="position:relative; top:0;" title="<?php _e('Posts will be randomly assigned dates in this range. WordPress ensures posts with dates in the future will not appear until their date has been reached.', 'pmxi_plugin') ?>">?</a>
								</label>
								<div class="switcher-target-date_type_random" style="vertical-align:middle; margin-top:5px;">
									<input type="text" class="datepicker" name="date_start" value="<?php echo esc_attr($post['date_start']) ?>" />
									<?php _e('and', 'pmxi_plugin') ?>
									<input type="text" class="datepicker" name="date_end" value="<?php echo esc_attr($post['date_end']) ?>" />
								</div>
							</div>											
						</td>
					</tr>
					<tr>
						<td>
							<h4><?php _e('Discussion', 'pmxi_plugin'); ?></h4>
							<div class="input">
								<input type="hidden" name="comment_status" value="closed" />
								<input type="checkbox" id="comment_status" name="comment_status" value="open" <?php echo 'open' == $post['comment_status'] ? 'checked="checked"' : '' ?> />
								<label for="comment_status"><?php _e('Allow Comments', 'pmxi_plugin') ?></label>
							</div>
							<div class="input">
								<input type="hidden" name="ping_status" value="closed" />
								<input type="checkbox" id="ping_status" name="ping_status" value="open" <?php echo 'open' == $post['ping_status'] ? 'checked="checked"' : '' ?> />
								<label for="ping_status"><?php _e('Allow Trackbacks and Pingbacks', 'pmxi_plugin') ?></label>
							</div>
						</td>
					</tr>
					<tr>
						<td>	
							<h4><?php _e('Post Slug', 'pmxi_plugin') ?></h4>
							<div>
								<input type="text" name="post_slug" style="width:100%;" value="<?php echo esc_attr($post['post_slug']); ?>" />
							</div> 
						</td>
					</tr>
					<tr>
						<td>
							<h4><?php _e('Post Author', 'pmxi_plugin') ?></h4>
							<div>
								<input type="text" name="author" value="<?php echo esc_attr($post['author']) ?>"/> <a href="#help" class="wpallimport-help" style="position: relative; top: -2px;" title="<?php _e('Assign the post to an existing user account by specifying the user ID, username, or e-mail address.', 'pmxi_plugin') ?>">?</a>			
							</div>																	
						</td>								
					</tr>	
					<tr>
						<td>
							<h4 style="float:left;"><?php _e('Download & Import Attachments', 'pmxi_plugin') ?></h4>
							<span class="separated_by" style="position:relative; top:15px; margin-right:0px;"><?php _e('Separated by','pmxi_plugin');?></span>
							<div>
								<input type="text" name="attachments" style="width:93%;" value="<?php echo esc_attr($post['attachments']) ?>" />
								<input type="text" class="small" name="atch_delim" value="<?php echo esc_attr($post['atch_delim']) ?>" style="width:5%; text-align:center; float:right;"/>
							</div>																	
						</td>								
					</tr>	
					<?php if ($is_support_post_format):?>
					<tr>
						<td>													
							<h4><?php _e('Post Format', 'pmxi_plugin') ?></h4>
							<div>
								<?php $post_formats = get_theme_support( 'post-formats' ); ?>

								<div class="input">
									<input type="radio" id="post_format_<?php echo "standart_" . $post_type; ?>" name="post_format" value="0" <?php echo (empty($post['post_format']) or ( empty($post_formats) )) ? 'checked="checked"' : '' ?> />
									<label for="post_format_<?php echo "standart_" . $post_type; ?>"><?php _e( "Standard", 'pmxi_plugin') ?></label>
								</div>

								<?php								
									if ( ! empty($post_formats[0]) ){
										foreach ($post_formats[0] as $post_format) {
											?>
											<div class="input">
												<input type="radio" id="post_format_<?php echo $post_format . "_" . $entry; ?>" name="post_format" value="<?php echo $post_format; ?>" <?php echo $post_format == $post['post_format'] ? 'checked="checked"' : '' ?> />
												<label for="post_format_<?php echo $post_format . "_" . $entry; ?>"><?php _e( ucfirst($post_format), 'pmxi_plugin') ?></label>
											</div>
											<?php
										}
									}			
								?>
							</div>									
						</td>
					</tr>
					<?php endif; ?>		

					<?php if ( 'page' == $post_type ):?>							
					<tr>
						<td>
							<h4><?php _e('Page Template', 'pmxi_plugin') ?></h4>
							<div class="input">
								<select name="page_template" id="page_template">
									<option value='default'><?php _e('Default', 'pmxi_plugin') ?></option>
									<?php page_template_dropdown($post['page_template']); ?>
								</select>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<h4><?php _e('Page Parent', 'pmxi_plugin') ?></h4>
							<div class="input">
							<?php wp_dropdown_pages(array('post_type' => 'page', 'selected' => $post['parent'], 'name' => 'parent', 'show_option_none' => __('(no parent)', 'pmxi_plugin'), 'sort_column'=> 'menu_order, post_title',)) ?>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<h4><?php _e('Page Order', 'pmxi_plugin') ?></h4>
							<div class="input">
								<input type="text" class="" name="order" value="<?php echo esc_attr($post['order']) ?>" />
							</div>
						</td>
					</tr>					
					<?php endif; ?>			
				</table>
			</div>
		</div>
	</div>
</div>