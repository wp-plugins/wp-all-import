<form class="template <?php echo ! $this->isWizard ? 'edit' : '' ?>" method="post">
	<table class="layout">
	<tr>
		<td class="left">
			<h2>
				<?php $templates = new PMXI_Template_List() ?>
				<span class="load-template">
					<select name="load_template">
						<option value=""><?php _e('Load Template...', 'pmxi_plugin') ?></option>
						<?php foreach ($templates->getBy()->convertRecords() as $t): ?>
							<option value="<?php echo $t->id ?>"><?php echo $t->name ?></option>
						<?php endforeach ?>
					</select><a href="#help" class="help" title="<?php _e('Select a <b>Template</b> from the dropdown and it will be preloaded', 'pmxi_plugin') ?>">?</a>
				</span>
				<?php  if ($this->isWizard): ?>
					<?php _e('Import XML/CSV - Step 3: Template Designer', 'pmxi_plugin') ?><br/><span class="taglines"><?php _e('arrange your data and design your posts', 'pmxi_plugin') ?></span>
				<?php else: ?>
					<?php _e('Edit Import Template', 'pmxi_plugin') ?>
				<?php endif ?>
			</h2>
			<hr/>

			<?php if ($this->errors->get_error_codes()): ?>
				<?php $this->error() ?>
			<?php endif ?>

			<h3>Post Title</h3>
			<div style="width:100%">
				<input id="title" class="widefat" type="text" name="title" value="<?php echo esc_attr($post['title']) ?>" />
			</div>

			<h3>
				<span class="header-option">
					<input type="hidden" name="is_keep_linebreaks" value="0" />
					<input type="checkbox" id="is_keep_linebreaks" name="is_keep_linebreaks" value="1" <?php echo $post['is_keep_linebreaks'] ? 'checked="checked"' : '' ?> />
					<label for="is_keep_linebreaks"><?php _e('Keep line breaks from XML', 'pmxi_plugin') ?></label>
				</span>
				Post Content
			</h3>
			<div id="poststuff">
				<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">

					<?php the_editor($post['content']) ?>
					<table id="post-status-info" cellspacing="0">
						<tbody>
						<tr>
							<td id="wp-word-count"></td>
							<td class="autosave-info">
								<span id="autosave">&nbsp;</span>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
			</div>
			<p>
				<?php wp_nonce_field('template', '_wpnonce_template') ?>
				<input type="hidden" name="is_submitted" value="1" />
				<?php if ($this->isWizard): ?>
					<a href="<?php echo add_query_arg('action', 'element', $this->baseUrl) ?>" class="button back"><?php _e('Back', 'pmxi_plugin') ?></a>
					&nbsp;
					<input type="submit" class="button-primary" value="<?php _e('Continue', 'pmxi_plugin') ?> &gt;&gt;" />
					<input type="text" name="name" title="<?php _e('Save Template As...', 'pmxi_plugin') ?>" style="vertical-align:middle" value="<?php echo esc_attr($post['name']) ?>" />
					&nbsp;
					<a href="#preview" class="button preview" title="<?php _e('Preview Post', 'pmxi_plugin') ?>"><?php _e('Preview', 'pmxi_plugin') ?></a>
				<?php else: ?>
					<input type="submit" class="button-primary" value="<?php _e('Edit', 'pmxi_plugin') ?>" />
					<input type="text" name="name" title="<?php _e('Save Template As...', 'pmxi_plugin') ?>" style="vertical-align:middle" value="<?php echo esc_attr($post['name']) ?>" />
				<?php endif ?>
			</p>
		</td>
		<?php if ($this->isWizard or $this->isTemplateEdit): ?>
			<td class="right">
					<p><?php _e('Drag &amp; Drop opening tag of an element for inserting corresponding XPath into template or title.', 'pmxi_plugin') ?></p>
					<p><?php _e('<a href="http://www.wpallimport.com/template-syntax/" target="_blank">Template Syntax Documentation</a>', 'pmxi_plugin') ?> - includes information on looping and shortcodes.</p>
					<?php $this->tag() ?>
			</td>
		<?php endif ?>
	</tr>
	</table>
</form>
