<div class="inner-content">
	<h2><?php _e('Import XML - <span id="status">Processing...</span>', 'pmxi_plugin') ?></h2>
	<hr />	
	<p id="process_notice"><?php _e('Importing may take some time. Please do not close your browser or refresh the page until the process is complete.', 'pmxi_plugin') ?></p>
	<?php _e('<div id="processbar"><div></div><span id="import_progress"><span id="left_progress">Time Elapsed <span id="then">00:00:00</span></span><span id="center_progress"> Created 0 / Updated 0 </span><span id="right_progress">0%</span></span></div>', 'pmxi_plugin'); ?>	
	<div id="logbar">
		<a href="javascript:void(0);" id="view_log"><?php _e('View Log','pmxi_plugin');?></a><span id="download_log_separator"> | </span> <a href="javascript:void(0);" id="download_log"><?php _e('Download Log','pmxi_plugin');?></a>
		<p><?php _e('Warnings','pmxi_plugin');?> (<span id="warnings">0</span>), <?php _e('Errors','pmxi_plugin');?> (<span id="errors">0</span>)</p>
	</div>
	<fieldset id="logwrapper" <?php if (PMXI_Plugin::$session->data['pmxi_import']['large_file']): ?> style="display:none;" <?php endif; ?>>
		<legend><?php _e('Log','pmxi_plugin');?></legend>
		<div id="loglist">
			
		</div>
	</fieldset>
	<a href="<?php echo add_query_arg(array('page' => 'pmxi-admin-manage'), remove_query_arg(array('id','page'), $this->baseUrl)); ?>" style="float:right; display:none;" id="import_finished"><?php _e('Manage Imports', 'pmxi_plugin') ?></a>									
</div>

<script type="text/javascript">
//<![CDATA[
(function($){
	$('#status').each(function () {

		var then = $('#then');
		start_date = moment().sod();		
		update = function(){
			var duration = moment.duration({'seconds' : 1});
			start_date.add(duration); 

			if ( ! $('#download_pmxi_log').length) then.html(start_date.format('HH:mm:ss'));
		};
		update();
		setInterval(update, 1000);

		var $this = $(this);
		if ($this.html().match(/\.{3}$/)) {
			var dots = 0;
			var status = $this.html().replace(/\.{3}$/, '');
			var interval ;			
			var odd = false;
			interval = setInterval(function () {
				var percents = $('.import_percent:last').html();
				if ($this.html().match(new RegExp(status + '\\.{1,3}$', ''))) {					
					if (percents != null && percents != ''){
						$this.html(status + '...'.substr(0, dots++ % 3 + 1));					
						var msg = $('.import_process_bar:last').html();
						if (msg != undefined) $('#center_progress').html(msg);
						$('#warnings').html($('.warnings_count:last').html());
						$('#errors').html($('.errors_count:last').html());
						$('#right_progress').html(((parseInt(percents) > 100 || percents == undefined) ? 100 : percents) + '%');
					    $('#processbar div').css({'width': ((parseInt(percents) > 100 || percents == undefined) ? 100 : percents) + '%'});
					}
				} else {					
					var msg = $('.import_process_bar:last').html();
					if (msg != undefined) $('#center_progress').html(msg);
					$('#right_progress').html(((parseInt(percents) > 100 || percents == undefined) ? 100 : percents) + '%');
					$('#warnings').html($('.warnings_count:last').html());
					$('#errors').html($('.errors_count:last').html());
					$('#processbar div').css({'width': ((parseInt(percents) > 100 || percents == undefined) ? 100 : percents) + '%'});
					$('#process_notice').hide();
					$('#import_finished').show();
					clearInterval(update);
					clearInterval(interval);					
				}				
				if ($('#download_pmxi_log').length){
					$('#download_log').attr('href', $('#download_pmxi_log').attr('href')).show();
					$('#download_log_separator').show();
				}				
				// fill log bar									
				$('.progress-msg').each(function(i){ 
					if ( ! $(this).find('.import_process_bar').length){
						<?php if (PMXI_Plugin::$session->data['pmxi_import']['large_file']): ?>
							if ($('#loglist').find('p').length > 100) $('#loglist').html('');
						<?php endif; ?>
						$('#loglist').append('<p ' + ((odd) ? 'class="odd"' : 'class="even"') + '>' + $(this).html() + '</p>');						
						$('#loglist').animate({ scrollTop: $('#loglist').get(0).scrollHeight }, 0);
						$(this).remove();
						odd = !odd;
					}
				});		

			}, 1000);
			$('#processbar').css({'visibility':'visible'});
		}
	});
	window.onbeforeunload = function () {
		return 'WARNING:\nImport process in under way, leaving the page will interrupt\nthe operation and most likely to cause leftovers in posts.';
	};		
})(jQuery);

//]]>
</script>

<?php if (PMXI_Plugin::$session->data['pmxi_import']['large_file']): ?>
	
	<script type="text/javascript">
		//<![CDATA[
		(function($){
			function parse_element(){
				$.post('admin.php?page=pmxi-admin-import&action=process', {}, function (data) {
					$('#loglist').append(data);
					if ( ! $('#download_pmxi_log').length) parse_element();
				}, 'html').fail(function() { 					
					if ( ! $('#download_pmxi_log').length) parse_element(); 
				});
			}
			parse_element();
		})(jQuery);
		//]]>
	</script>

<?php endif; ?>