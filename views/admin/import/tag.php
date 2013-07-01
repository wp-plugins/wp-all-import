<?php if (!empty($elements->length)):?>
<div class="tag">	
	<input type="hidden" name="tagno" value="<?php echo $tagno ?>" />
	<div class="title">
		<?php printf(__('Record #<strong>%s</strong> out of <strong>%s</strong>', 'pmxi_plugin'), $tagno, ( ! PMXI_Plugin::$session->data['pmxi_import']['large_file']) ? $elements->length : PMXI_Plugin::$session->data['pmxi_import']['count']); ?>
		<div class="navigation">
			<?php if ($tagno > 1): ?><a href="#prev">&lang;&lang;</a><?php else: ?><span>&lang;&lang;</span><?php endif ?>
			<?php if ($tagno < $elements->length or (PMXI_Plugin::$session->data['pmxi_import']['large_file'] and $tagno < PMXI_Plugin::$session->data['pmxi_import']['count'])): ?><a href="#next">&rang;&rang;</a><?php else: ?><span>&rang;&rang;</span><?php endif ?>
		</div>
	</div>
	<div class="clear"></div>
	<div class="xml resetable"> <?php if (!empty($elements->length)) $this->render_xml_element(( ! PMXI_Plugin::$session->data['pmxi_import']['large_file']) ? $elements->item($tagno - 1) : $elements->item(0), true);  ?></div>
	<p class="xpath_help">
		<?php _e('Operate on elements using your own PHP functions, use FOREACH loops, and more.<br />Read the <a href="http://www.wpallimport.com/portal/" target="_blank">documentation</a> to learn how.', 'pmxi_plugin') ?>
	</p>	
</div>
<?php else: ?>
	<div class="error inline below-h2" style="padding:10px; margin-top:45px;">
		<?php printf(__('History file not found.', 'pmxi_plugin')); ?>			
	</div>
<?php endif; ?>