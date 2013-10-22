<?php

	if (!function_exists('reverse_taxonomies_html')) {
		function reverse_taxonomies_html($post_taxonomies, $item_id, &$i){
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
		            	<div class="drag-element">
		            		<input type="checkbox" class="assign_post" <?php if ($child_cat->assign): ?>checked="checked"<?php endif; ?> title="<?php _e('Assign post to the taxonomy.','pmxi_plugin');?>"/>		            		
		            		<input class="widefat" type="text" value="<?php echo esc_attr($child_cat->xpath); ?>"/>
		            	</div>
		            	<a href="javascript:void(0);" class="icon-item remove-ico"></a>
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
<input type="hidden" id="selected_post_type" value="<?php echo (!empty($post['custom_type'])) ? $post['custom_type'] : '';?>">
<input type="hidden" id="selected_type" value="<?php echo (!empty($post['type'])) ? $post['type'] : '';?>">
<h2>
	<?php if ($this->isWizard): ?>
		<?php _e('Import XML/CSV - Step 4: Options', 'pmxi_plugin') ?>
	<?php else: ?>
		<?php _e('Edit Import Options', 'pmxi_plugin') ?>
	<?php endif ?>
</h2>
<h3><?php _e('Click the appropriate tab to choose the type of posts to create.', 'pmxi_plugin');?></h3>

<div class="ajax-console">
	<?php if ($this->errors->get_error_codes()): ?>
		<?php $this->error() ?>
	<?php endif ?>
</div>

<table class="layout">
<tr>
	<td class="left" style="width:100%;">
		<?php $templates = new PMXI_Template_List() ?>
		<form class="load_options options <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
			<div class="load-template">
				<span><?php _e('Load existing template:','pmxi_plugin');?> </span>
				<select name="load_template">
					<option value=""><?php _e('Load Template...', 'pmxi_plugin') ?></option>
					<?php foreach ($templates->getBy()->convertRecords() as $t): ?>
						<option value="<?php echo $t->id ?>"><?php echo $t->name ?></option>
					<?php endforeach ?>
					<option value="-1"><?php _e('Reset...', 'pmxi_plugin') ?></option>
				</select>
			</div>
		</form>
		<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
			<a class="nav-tab nav-tab-active" rel="posts" href="javascript:void(0);">Posts</a>
			<a class="nav-tab" rel="pages" href="javascript:void(0);">Pages</a>			
			<?php
				if (class_exists('PMWI_Plugin')):
				?>
					<a class="nav-tab" rel="product" href="javascript:void(0);">WooCommerce Products</a>										
				<?php
				endif;
			?>
		</h2>
		<div id="pmxi_tabs">			
			<div class="left">			   

			    <!-- Post Options -->

			    <div id="posts" class="pmxi_tab"> <!-- Basic -->
				    <form class="options <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
				    	<input type="hidden" name="type" value="post"/>
				    	<input type="hidden" name="custom_type" value=""/>
						<div class="post-type-options">
							<table class="form-table" style="max-width:none;">
								<?php
									$post_type = 'post';
									$entry = 'post';
									include( 'options/_main_options_template.php' );
									include( 'options/_taxonomies_template.php' );
									include( 'options/_categories_template.php' );
									include( 'options/_custom_fields_template.php' );
									include( 'options/_featured_template.php' );
									include( 'options/_author_template.php' );
									include( 'options/_reimport_template.php' );									
									include( 'options/_settings_template.php' );
								?>
							</table>
						</div>

						<?php include( 'options/_buttons_template.php' ); ?>

					</form>
				</div>

				<!-- Page Options -->

				<div id="pages" class="pmxi_tab">
					<form class="options <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
						<input type="hidden" name="type" value="page"/>
						<input type="hidden" name="custom_type" value=""/>
						<div class="post-type-options">
							<table class="form-table" style="max-width:none;">

								<?php include( 'options/_main_options_template.php' ); ?>

								<tr>
									<td align="center" width="33%">
										<label><?php _e('Page Template', 'pmxi_plugin') ?></label> <br>
										<select name="page_template" id="page_template">
											<option value='default'><?php _e('Default', 'pmxi_plugin') ?></option>
											<?php page_template_dropdown($post['page_template']); ?>
										</select>
									</td>
									<td align="center" width="33%">
										<label><?php _e('Parent Page', 'pmxi_plugin') ?></label> <br>
										<?php wp_dropdown_pages(array('post_type' => 'page', 'selected' => $post['parent'], 'name' => 'parent', 'show_option_none' => __('(no parent)', 'pmxi_plugin'), 'sort_column'=> 'menu_order, post_title',)) ?>
									</td>
									<td align="center" width="33%">
										<label><?php _e('Order', 'pmxi_plugin') ?></label> <br>
										<input type="text" class="" name="order" value="<?php echo esc_attr($post['order']) ?>" />
									</td>
								</tr>
								<?php
									$post_type = 'post';
									$entry = 'page';
									include( 'options/_custom_fields_template.php' );
									include( 'options/_taxonomies_template.php' );
									include( 'options/_featured_template.php' );
									include( 'options/_author_template.php' );
									include( 'options/_reimport_template.php' );									
									include( 'options/_settings_template.php' );
								?>
							</table>
						</div>

						<?php include( 'options/_buttons_template.php' ); ?>

					</form>
				</div>				

				<!-- WooCommerce Add-On -->
				<?php
					if (class_exists('PMWI_Plugin')):
					?>
						<div id="product" class="pmxi_tab">
							<form class="options <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
								<input type="hidden" name="custom_type" value="product"/>
								<input type="hidden" name="type" value="post"/>
								<div class="post-type-options">
									<table class="form-table" style="max-width:none;">
										<?php
																					
											$post_type = $entry = 'product';		

											include( 'options/_main_options_template.php' );
											
											$woo_controller = new PMWI_Admin_Import();										
											$woo_controller->index();
											
											include( 'options/_taxonomies_template.php' );										
											include( 'options/_custom_fields_template.php' );
											include( 'options/_featured_template.php' );
											include( 'options/_author_template.php' );
											include( 'options/_reimport_template.php' );											
											include( 'options/_settings_template.php' );

										?>
									</table>
								</div>

								<?php include( 'options/_buttons_template.php' ); ?>

							</form>
						</div>					
					<?php
					endif;
				?>
			</div>
			<?php if ($this->isWizard or $this->isTemplateEdit): ?>
			<div class="right options">
				<?php $this->tag() ?>
			</div>
			<?php endif ?>
		</div>
	</td>	
</tr>
</table>