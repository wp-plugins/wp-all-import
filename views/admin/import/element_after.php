<form class="choose-elements no-enter-submit" method="post">
<table class="layout">
	<tr>
		<td class="left">
			<h2><?php _e('Import XML/CSV - Step 2: Element Selector', 'pmxi_plugin') ?><br/><span class="taglines"><?php _e('select which elements you wish to import', 'pmxi_plugin') ?></span></h2>
			<hr />


			<p>
				<?php _e('<b>Choose elements for import by double clicking on corresponding opening tag. Recurring sibling elements, if present, are selected automatically.</b>', 'pmxi_plugin') ?>
			</p>

			<div class="ajax-console">
				<?php if ($this->errors->get_error_codes()): ?>
					<?php $this->error() ?>
				<?php endif ?>
			</div>

			<div class="xml">
				<?php $this->render_xml_element($dom->documentElement) ?>
			</div>
		</td>
		<td class="right">
			<p>
				<?php _e('Advaned users: use <a href="http://www.w3schools.com/xpath/default.asp" target="_blank">XPath syntax.</a> If you are unable to figure out what XPath you need to match certain elements you are trying to import, contact <a href="http://www.wpallimport.com/support" target="_blank">support</a> and include your XML file. We will get back to you with the XPath.', 'pmxi_plugin') ?>
			</p>
			<p>
				<?php _e('Tip: change [1], [2], [3], etc. to [*] to match more elements.', 'pmxi_plugin') ?>
			</p>
			<div><input type="text" name="xpath" value="<?php echo esc_attr($post['xpath']) ?>" /></div>
			<p class="submit-buttons">
				<a href="<?php echo $this->baseUrl ?>" class="button back">Back</a>
				&nbsp;
				<input type="hidden" name="is_submitted" value="1" />
				<?php wp_nonce_field('choose-elements', '_wpnonce_choose-elements') ?>
				<input type="submit" class="button-primary" value="<?php _e('Continue', 'pmxi_plugin') ?> &gt;&gt;" />
			</p>
		</td>
	</tr>
</table>
</form>
