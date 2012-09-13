<div class="tag">
	<input type="hidden" name="tagno" value="<?php echo $tagno ?>" />
	<div class="title">
		<?php printf(__('Record #<strong>%s</strong> out of <strong>%s</strong>', 'pmxi_plugin'), $tagno, $elements->length) ?>
		<div class="navigation">
			<?php if ($tagno > 1): ?><a href="#prev">&lang;&lang;</a><?php else: ?><span>&lang;&lang;</span><?php endif ?>
			<?php if ($tagno < $elements->length): ?><a href="#next">&rang;&rang;</a><?php else: ?><span>&rang;&rang;</span><?php endif ?>
		</div>
	</div>
	<div class="clear"></div>	
	<div class="xml resetable"> <?php if (!empty($elements)) $this->render_xml_element($elements->item($tagno - 1), true);  ?></div>		 
</div>
