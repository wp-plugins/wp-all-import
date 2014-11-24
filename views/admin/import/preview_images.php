<div id="post-preview" class="wpallimport-preview_images">

	<div class="title">
		<div class="navigation">			
			<?php if ($tagno > 1): ?><a href="#prev" class="previous_element">&nbsp;</a><?php else: ?><span class="previous_element">&nbsp;</span><?php endif ?>
			<?php printf(__('<strong><input type="text" value="%s" name="tagno" class="tagno"/></strong><span class="out_of"> of <strong class="pmxi_count">%s</strong></span>', 'pmxi_plugin'), $tagno, PMXI_Plugin::$session->count); ?>
			<?php if ($tagno < PMXI_Plugin::$session->count): ?><a href="#next" class="next_element">&nbsp;</a><?php else: ?><span class="next_element">&nbsp;</span><?php endif ?>			
		</div>
	</div>

	<div class="wpallimport-preview-content">

		<?php if ($this->errors->get_error_codes()): ?>
			<?php $this->error() ?>
		<?php endif ?>

		<h3><?php _e('Test Image Import', 'pmxi_plugin'); ?></h3>	

		<?php 

		if ( ! empty($featured_images) ){		

			?>
			<p><?php _e('Click to test button to have WP All Import ensure it can import your images.', 'pmxi_plugin'); ?></p>

			<a class="test_images" href="javascript:void(0);" style="margin-left:0;" rel="<?php echo $post['download_images']; ?>"><?php _e('Test', 'pmxi_plugin'); ?></a>
					
			<?php

			$featured_delim = ( "yes" == $post['download_images'] ) ? $post['download_featured_delim'] : $post['featured_delim'];
			$imgs = array();

			$line_imgs = explode("\n", $featured_images);
			if ( ! empty($line_imgs) )
				foreach ($line_imgs as $line_img)
					$imgs = array_merge($imgs, ( ! empty($featured_delim) ) ? str_getcsv($line_img, $featured_delim) : array($line_img) );					

			if ( "yes" == $post['download_images']):				
						
				?>
				<div class="test_progress">
					<div class="img_preloader"><?php _e('Download in progress...'); ?></div>
					<div class="img_success"></div>
					<div class="img_failed"></div>
				</div>
				<h4><?php _e('WP All Import will attempt to import images from the following URLs:'); ?></h4>
				<p><?php _e('Please check the URLs to ensure they point to valid images'); ?></p>
				<ul class="images_list">
					<?php foreach ($imgs as $img):?>
						
						<li rel="<?php echo trim($img); ?>"><a href="<?php echo trim($img); ?>" target="_blank"><?php echo trim($img); ?></a></li>
					
					<?php endforeach; ?>					
				</ul>
				<h4><?php _e('Here are the above URLs, in &lt;img&gt; tags. '); ?></h4>
				<?php foreach ($imgs as $img) : ?>
					
					<img src="<?php echo trim($img);?>" style="width:64px; margin:5px; vertical-align:top;"/>
				
				<?php endforeach; ?>
					
			<?php

			else:				
				
				$wp_uploads = wp_upload_dir();

				?>
				<div class="test_progress">
					<div class="img_preloader"><?php _e('Retrieving images...'); ?></div>
					<div class="img_success"></div>
					<div class="img_failed"></div>
				</div>
				<h4><?php _e('WP All Import will import images from the following file paths:', 'pmxi_plugin'); ?></h4>
				<p><?php _e('Please ensure the images exists at these file paths', 'pmxi_plugin'); ?></p>
				<ul class="images_list">
					<?php foreach ($imgs as $img) :?>
						
						<li rel="<?php echo trim($img);?>"><?php echo trim(preg_replace('%.*/wp-content%', '/wp-content', $wp_uploads['basedir']) . '/wpallimport/files/' . trim($img)); ?></li>
					
					<?php endforeach; ?> 					
				</ul>
				<h4><?php _e('Here are the above URLs, in &lt;img&gt; tags. '); ?></h4>
				
				<?php 
				foreach ($imgs as $img) {
					$img_url = home_url() . preg_replace('%.*/wp-content%', '/wp-content', $wp_uploads['basedir']) . '/wpallimport/files/' . trim($img);
					?>
					<img src="<?php echo trim($img_url);?>" style="width:64px; margin:5px; vertical-align:top;"/>
					<?php
				}				

			endif;
			
		}
		else{
			?>
			<p><?php _e('Images not found for current record.', 'pmxi_plugin'); ?></p>
			<?php
		}
		?>
	</div>
</div>