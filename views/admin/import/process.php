<h2 class="wpallimport-wp-notices"></h2>

<div class="inner-content wpallimport-step-6 wpallimport-wrapper">
	
	<div class="wpallimport-header">
		<div class="wpallimport-logo"></div>
		<div class="wpallimport-title">
			<p><?php _e('WP All Import', 'wp_all_import_plugin'); ?></p>
			<h2><?php _e('Import XML / CSV', 'wp_all_import_plugin'); ?></h2>					
		</div>
		<div class="wpallimport-links">
			<a href="http://www.wpallimport.com/support/" target="_blank"><?php _e('Support', 'wp_all_import_plugin'); ?></a> | <a href="http://www.wpallimport.com/documentation/" target="_blank"><?php _e('Documentation', 'wp_all_import_plugin'); ?></a>
		</div>

		<div class="clear"></div>	
		<div class="processing_step_1">

			<div class="clear"></div>

			<div class="step_description">
				<h2><?php _e('Import <span id="status">in Progress</span>', 'wp_all_import_plugin') ?></h2>
				<h3 id="process_notice"><?php _e('Importing may take some time. Please do not close your browser or refresh the page until the process is complete.', 'wp_all_import_plugin'); ?></h3>		
				
			</div>		
			<div id="processbar" class="rad14">
				<div class="rad14"></div>			
			</div>			
			<div id="import_progress">
				<span id="left_progress"><?php _e('Time Elapsed', 'wp_all_import_plugin');?> <span id="then">00:00:00</span></span>
				<span id="center_progress"><span id="percents_count">0</span>%</span>
				<span id="right_progress"><?php _e('Created','wp_all_import_plugin');?> <span id="created_count"><?php echo $update_previous->created; ?></span> / <?php _e('Updated','wp_all_import_plugin');?> <span id="updated_count"><?php echo $update_previous->updated; ?></span> <?php _e('of', 'wp_all_import_plugin');?> <span id="of"><?php echo $update_previous->count; ?></span> <?php _e('records', 'wp_all_import_plugin'); ?></span>				
			</div>			
		</div>

		<div id="import_finished">
			<h1><?php _e('Import Complete!', 'wp_all_import_plugin'); ?></h1>
			<h3><?php printf(__('WP All Import successfully imported your file <span>%s</span> into your WordPress installation!','wp_all_import_plugin'), (PMXI_Plugin::$session->source['type'] != 'url') ? basename(PMXI_Plugin::$session->source['path']) : PMXI_Plugin::$session->source['path'])?></h3>			
			<?php if ($ajax_processing): ?>
			<p class="wpallimport-log-details"><?php printf(__('There were <span class="wpallimport-errors-count">%s</span> errors and <span class="wpallimport-warnings-count">%s</span> warnings in this import. You can see these in the import log.', 'wp_all_import_plugin'), 0, 0); ?></p>
			<?php elseif ((int) PMXI_Plugin::$session->errors or (int) PMXI_Plugin::$session->warnings): ?>
			<p class="wpallimport-log-details" style="display:block;"><?php printf(__('There were <span class="wpallimport-errors-count">%s</span> errors and <span class="wpallimport-warnings-count">%s</span> warnings in this import. You can see these in the import log.', 'wp_all_import_plugin'), PMXI_Plugin::$session->errors, PMXI_Plugin::$session->warnings); ?></p>
			<?php endif; ?>
			<hr>
			<a href="<?php echo add_query_arg(array('id' => $update_previous->id, 'page' => 'pmxi-admin-history'), $this->baseUrl); ?>" id="download_log"><?php _e('View Logs','wp_all_import_plugin');?></a>			
			<a href="<?php echo add_query_arg(array('page' => 'pmxi-admin-manage'), remove_query_arg(array('id','page'), $this->baseUrl)); ?>" id="manage_imports"><?php _e('Manage Imports', 'wp_all_import_plugin') ?></a>
		</div>

	</div>

	<div class="wpallimport-modal-message rad4"><?php printf(__('WP All Import tried to process <span id="wpallimport-records-per-iteration">%s</span> records in one iteration, but your server terminated the process before it could finish. <a href="javascript:void(0);" id="wpallimport-try-again">Click here to try again</a>, but with only <span id="wpallimport-new-records-per-iteration">%s</span> records per iteration.', 'wp_all_import_plugin'), $update_previous->options['records_per_request'], ((ceil($update_previous->options['records_per_request']/2)) ? ceil($update_previous->options['records_per_request']/2) : 1)); ?></div>
	
	<fieldset id="logwrapper">
		<legend><?php _e('Log','wp_all_import_plugin');?></legend>
		<div id="loglist"></div>		
	</fieldset>	

	<input type="hidden" class="count_failures" value="0"/>
	<input type="hidden" class="records_per_request" value="<?php echo $update_previous->options['records_per_request']; ?>"/>
	<span id="wpallimport-error-terminated" style="display:none;"><?php printf(__('Unfortunately, your server terminated the import process. Click here for our <a href="%s" target="_blank">troubleshooting guide</a>, or ask your web host to look in your error_log file for an error that takes place at the same time you are trying to run your import, and fix whatever setting is causing the import to fail.', 'wp_all_import_plugin'), 'http://www.wpallimport.com/documentation/advanced/troubleshooting/'); ?></span>

	<a href="http://soflyy.com/" target="_blank" class="wpallimport-created-by"><?php _e('Created by', 'wp_all_import_plugin'); ?> <span></span></a>
	
</div>

<script type="text/javascript">
//<![CDATA[
(function($){

	window.onbeforeunload = function () {
		return 'WARNING:\nImport process in under way, leaving the page will interrupt\nthe operation and most likely to cause leftovers in posts.';
	};

	var odd = false;
	var interval;

	function write_log(){			
			
		$('.progress-msg').each(function(i){ 
												
			if ($('#loglist').find('p').length > 350) $('#loglist').html('');										

			<?php if ( ! $ajax_processing ): ?>
				if ($(this).find('.processing_info').length) {
					$('#created_count').html($(this).find('.created_count').html());
					$('#updated_count').html($(this).find('.updated_count').html());
					$('#percents_count').html($(this).find('.percents_count').html());					
				}
			<?php endif; ?>

			if ( ! $(this).find('.processing_info').length ){ 
				$('#loglist').append('<p ' + ((odd) ? 'class="odd"' : 'class="even"') + '>' + $(this).html() + '</p>');
				odd = !odd;
			}
			//$('#loglist').animate({ scrollTop: $('#loglist').get(0).scrollHeight }, 0);			
			$(this).remove();			
		});	
	}

	$('#status').each(function () {

		var then = $('#then');
		start_date = moment().sod();		
		update = function(){
			var duration = moment.duration({'seconds' : 1});
			start_date.add(duration); 
			
			if ($('#process_notice').is(':visible') && ! $('.wpallimport-modal-message').is(':visible')) then.html(start_date.format('HH:mm:ss'));
		};
		update();
		setInterval(update, 1000);

		var $this = $(this);
												
		interval = setInterval(function () {															
			
			write_log();	

			var percents = $('#percents_count').html();
			$('#processbar div').css({'width': ((parseInt(percents) > 100 || percents == undefined) ? 100 : percents) + '%'});		
			

		}, 1000);
		
		$('#processbar').css({'visibility':'visible'});		

	
	
	<?php if ( $ajax_processing ): ?>

		var import_id = '<?php echo $update_previous->id; ?>';

		var records_per_request = $('.records_per_request').val();

		function parse_element(failures){			

			$.post('admin.php?page=pmxi-admin-import&action=process&id=' + import_id + '&failures=' + failures + '&_wpnonce=' + wp_all_import_security, {}, function (data) {								

				// responce with error
				if (data != null && typeof data.created != "undefined"){

					$('.wpallimport-modal-message').hide();
					$('#created_count').html(data.created);	
					$('#updated_count').html(data.updated);
					$('#warnings').html(data.warnings);
					$('#errors').html(data.errors);
					$('#percents_count').html(data.percentage);
				    $('#processbar div').css({'width': data.percentage + '%'});

				    records_per_request = data.records_per_request;

					if ( data.done ){
						clearInterval(update);		
						clearInterval(interval);	

						setTimeout(function() {
							
							$('#loglist').append(data.log);
							$('#process_notice').hide();
							$('.processing_step_1').hide();	
							$('#import_finished').fadeIn();								
							
							if ( parseInt(data.errors) || parseInt(data.warnings)){			
								$('.wpallimport-log-details').find('.wpallimport-errors-count').html(data.errors);
								$('.wpallimport-log-details').find('.wpallimport-warnings-count').html(data.warnings);
								$('.wpallimport-log-details').show();
							}
							
						}, 1000);						
					} 
					else{ 
						$('#loglist').append(data.log);
						parse_element(0);
					}

					write_log();

				} else {
					var count_failures = parseInt($('.count_failures').val());
					count_failures++;
					$('.count_failures').val(count_failures);

					if (count_failures > 4 || records_per_request < 2){
						$('#process_notice').hide();
						$('.wpallimport-modal-message').html($('#wpallimport-error-terminated').html()).show();

						if (data != null && typeof data != 'undefined'){
							$('#status').html('Error ' + '<span class="pmxi_error_msg">' + data.responseText + '</span>');
						}
						else{
							$('#status').html('Error');
						}
						clearInterval(update);					
						window.onbeforeunload = false;

						var request = {
							action:'import_failed',			
							id: '<?php echo $update_previous->id; ?>',
							security: wp_all_import_security
					    };	

					    $.ajax({
							type: 'POST',
							url: ajaxurl,
							data: request,
							success: function(response) {
								
							},
							error: function(request) {							
								
							},			
							dataType: "json"
						});

					}
					else{
						$('#wpallimport-records-per-iteration').html(records_per_request);
						$('#wpallimport-new-records-per-iteration').html(Math.ceil(parseInt(records_per_request)/2));
						records_per_request = Math.ceil(parseInt(records_per_request)/2);
						$('.wpallimport-modal-message').show();							
						//parse_element(1);
					}				
					return;
				}								

			}, 'json').fail(function(data) { 													

				var count_failures = parseInt($('.count_failures').val());
				count_failures++;
				$('.count_failures').val(count_failures);

				if (count_failures > 4 || records_per_request < 2 ){					
					$('#process_notice').hide();
					$('.wpallimport-modal-message').html($('#wpallimport-error-terminated').html()).show();
					
					if (data != null && typeof data != 'undefined'){
						$('#status').html('Error ' + '<span class="pmxi_error_msg">' + data.responseText + '</span>');
					}
					else{
						$('#status').html('Error');
					}
					clearInterval(update);					
					window.onbeforeunload = false;

					var request = {
						action:'import_failed',			
						id: '<?php echo $update_previous->id; ?>'						
				    };	

				    $.ajax({
						type: 'POST',
						url: ajaxurl,
						data: request,
						success: function(response) {
							
						},
						error: function(request) {							
							
						},			
						dataType: "json"
					});
				}
				else{
					$('#wpallimport-records-per-iteration').html(records_per_request);
					$('#wpallimport-new-records-per-iteration').html(Math.ceil(parseInt(records_per_request)/2));
					records_per_request = Math.ceil(parseInt(records_per_request)/2);
					$('.wpallimport-modal-message').show();					
					//parse_element(1);
				}
												
			});			
		}		
		
		$('#wpallimport-try-again').click(function(){
			parse_element(1);
			$('.wpallimport-modal-message').hide();			
		});

		$('#processbar').css({'visibility':'visible'});

		parse_element(0);

	<?php else: ?>

		complete = function(){
			if ($('#status').html() == 'Complete'){
				setTimeout(function() {
					$('#process_notice').hide();
					$('.processing_step_1').hide();	
					$('#import_finished').fadeIn();								
				}, 1000);
				clearInterval(update);
				clearInterval(complete);				
			}
		};			
		setInterval(complete, 1000);
		complete();		
		
	<?php endif; ?>

	});

})(jQuery);

//]]>
</script>
