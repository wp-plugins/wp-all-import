<form class="choose-elements no-enter-submit" method="post">
<h2><?php _e('Import XML/CSV - Step 2: Select Elements', 'pmxi_plugin') ?></h2>

<h3><?php _e('<b>Double-click on an element below to select it and its siblings.</b>', 'pmxi_plugin') ?></h3>

<div class="ajax-console">
	<?php if ($this->errors->get_error_codes()): ?>
		<?php $this->error() ?>
	<?php endif ?>
</div>
<table class="layout">
	<tr>
		<td class="left">
			<fieldset class="widefat">
				<legend><?php _e('Current XML tree', 'pmxi_plugin');?></legend>
				<div class="action_buttons">
					<a href="javascript:void(0);" id="prev_element" class="button button-primary button-hero large_button go_to">&laquo;</a>
					<a href="javascript:void(0);" id="next_element" class="button button-primary button-hero large_button go_to" style="margin-right:15px;">&raquo;</a>
					<div style="float:left;">
						<span style="font-size:20px; padding-top:17px; float:left; margin-right:10px;"><?php _e('Go to:','pmxi_plugin');?> </span><input type="text" id="goto_element" value="1"/>
					</div>					
					<?php
					if ($is_csv !== false){
						?>										
						<ul class="set_csv_delimiter">
							<li>Set delimiter for CSV fields: </li>
							<!--li> <a href="javascript:void(0);" rel="," <?php if ($is_csv == ','):?>class="delimiter_selected"<?php endif;?>><?php _e('comma', 'pmxi_plugin');?></a> </li>
							<li> <a href="javascript:void(0);" rel=";" <?php if ($is_csv == ';'):?>class="delimiter_selected"<?php endif;?>><?php _e('semicolon', 'pmxi_plugin');?></a> </li>
							<li> <a href="javascript:void(0);" rel="|" <?php if ($is_csv == '|'):?>class="delimiter_selected"<?php endif;?>><?php _e('pipe', 'pmxi_plugin');?></a> </li>
							<li> <a href="javascript:void(0);" rel="\t" <?php if ($is_csv == '\t'):?>class="delimiter_selected"<?php endif;?>><?php _e('tabulation', 'pmxi_plugin');?></a> </li-->
							<li> <input type="text" value="<?php echo $is_csv;?>" name="delimiter"/> <input type="button" name="apply_delimiter" value="Apply"/></li>
						</ul>
						<?php
					} 
					else{
						?>
						<input type="hidden" value="" name="delimiter"/>
						<?php
					}
					?>
				</div>
				<div class="xml" style="min-height:400px;">
					<?php //$this->render_xml_element($dom->documentElement) ?>
				</div>
			</fieldset>
		</td>
		<td class="right">
			<fieldset class="widefat">
				<legend><?php _e('Advanced','pmxi_plugin');?></legend>
				<p><?php _e('Current XPath:','pmxi_plugin');?></p>
				<div>
					<input type="text" name="xpath" value="<?php echo esc_attr($post['xpath']) ?>" style="max-width:none;" />					
					<input type="hidden" id="root_element" name="root_element" value="<?php echo PMXI_Plugin::$session->data['pmxi_import']['source']['root_element']; ?>"/>
					<?php
					if (!empty($elements_cloud)){
						?>
						&nbsp; <br/><label><?php _e('What element are you looking for?','pmxi_plugin');?></label>&nbsp; <br/>
						<?php
						$root_elements = array();
						foreach ($elements_cloud as $tag => $count) 						
							$root_elements[] = '<a href="javascript:void(0);" rel="'. $tag .'" class="change_root_element">' . $tag . '</a>';						
						echo implode(', ', $root_elements);
					}
					?>
					&nbsp; <br/><br/>or <a href="javascript:void(0);" rel="<?php echo esc_attr($post['xpath']) ?>" root="<?php echo PMXI_Plugin::$session->data['pmxi_import']['source']['root_element']; ?>" id="get_default_xpath"><?php _e('get default xPath','pmxi_plugin');?></a>
				</div> <br><br>				
				<a href="http://www.w3schools.com/xpath/default.asp" target='_blank'><?php _e('XPath Tutorial','pmxi_plugin');?></a> - <?php _e('For further help','pmxi_plugin');?>, <a href="http://www.wpallimport.com/support" target='_blank'><?php _e('contact us','pmxi_plugin');?></a>.
			</fieldset>
			<p class="submit-buttons" style="text-align:right;">
				<a href="<?php echo $this->baseUrl ?>" class="back"><?php _e('Back','pmxi_plugin');?></a>
				&nbsp;
				<input type="hidden" name="is_submitted" value="1" />
				<?php wp_nonce_field('choose-elements', '_wpnonce_choose-elements') ?>
				<input type="submit" class="button button-primary button-hero large_button" value="<?php _e('Next', 'pmxi_plugin') ?>" />
			</p>
		</td>
	</tr>
</table>
</form>
