<div id="post-preview">
	<?php if ($this->errors->get_error_codes()): ?>
		<?php $this->error() ?>
	<?php endif ?>
	
	<?php if (isset($title)): ?>
		<h2 class="title"><?php echo $title ?></h2>
	<?php endif ?>
	<?php if (isset($content)): ?>
		<div class="content"><?php echo apply_filters('the_content', $content) ?></div>
	<?php endif ?>
</div>