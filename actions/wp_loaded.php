<?php

function pmxi_wp_loaded() {				
	
	@ini_set("max_input_time", PMXI_Plugin::getInstance()->getOption('max_input_time'));
	@ini_set("max_execution_time", PMXI_Plugin::getInstance()->getOption('max_execution_time'));

	$table = PMXI_Plugin::getInstance()->getTablePrefix() . 'imports';
	global $wpdb;
	$imports = $wpdb->get_results("SELECT `id`, `name`, `path` FROM $table WHERE `path` IS NULL", ARRAY_A);

	if ( ! empty($imports) ){

		$importRecord = new PMXI_Import_Record();
		$importRecord->clear();
		foreach ($imports as $imp) {
			$importRecord->getById($imp['id']);
			if ( ! $importRecord->isEmpty()){
				$importRecord->delete( true );
			}
			$importRecord->clear();
		}

	}
	
	/* Check if cron is manualy, then execute import */
	$cron_job_key = PMXI_Plugin::getInstance()->getOption('cron_job_key');
	
	if (!empty($cron_job_key) and !empty($_GET['import_id']) and !empty($_GET['import_key']) and $_GET['import_key'] == $cron_job_key and !empty($_GET['action']) and in_array($_GET['action'], array('processing','trigger','pipe'))) {		
		
		$logger = create_function('$m', 'echo "<p>$m</p>\\n";');								

		$import = new PMXI_Import_Record();
		
		$ids = explode(',', $_GET['import_id']);

		if (!empty($ids) and is_array($ids)){			

			foreach ($ids as $id) { if (empty($id)) continue;

				$import->getById($id);	

				if ( ! $import->isEmpty() ){

					if ( ! in_array($import->type, array('url', 'ftp', 'file')) ) {
						$logger and call_user_func($logger, sprintf(__('Scheduling update is not working with "upload" import type. Import #%s.', 'pmxi_plugin'), $id));
					}

					switch ($_GET['action']) {

						case 'trigger':							

							if ( (int) $import->executing ){
								$logger and call_user_func($logger, sprintf(__('Import #%s is currently in manually process. Request skipped.', 'pmxi_plugin'), $id));	
							}
							elseif ( ! $import->processing and ! $import->triggered ){
								
								$import->set(array(
									'triggered' => 1,						
									'imported' => 0,
									'created' => 0,
									'updated' => 0,
									'skipped' => 0,
									'deleted' => 0,																		
									'queue_chunk_number' => 0,
									'last_activity' => date('Y-m-d H:i:s')									
								))->update();
									
								$history_log = new PMXI_History_Record();						
								$history_log->set(array(
									'import_id' => $import->id,
									'date' => date('Y-m-d H:i:s'),
									'type' => 'trigger',
									'summary' => __("triggered by cron", "pmxi_plugin")
								))->save();	

								$logger and call_user_func($logger, sprintf(__('#%s Cron job triggered.', 'pmxi_plugin'), $id));
							
							}
							elseif( $import->processing and ! $import->triggered) {
								$logger and call_user_func($logger, sprintf(__('Import #%s currently in process. Request skipped.', 'pmxi_plugin'), $id));	
							}													
							elseif( ! $import->processing and $import->triggered){
								$logger and call_user_func($logger, sprintf(__('Import #%s already triggered. Request skipped.', 'pmxi_plugin'), $id));	
							}

							break;

						case 'processing':

							if ( $import->processing == 1 and (time() - strtotime($import->registered_on)) > ((PMXI_Plugin::getInstance()->getOption('cron_processing_time_limit')) ? PMXI_Plugin::getInstance()->getOption('cron_processing_time_limit') : 120)){ // it means processor crashed, so it will reset processing to false, and terminate. Then next run it will work normally.
								$import->set(array(
									'processing' => 0									
								))->update();
							}
							
							// start execution imports that is in the cron process												
							if ( ! (int) $import->triggered ){
								$logger and call_user_func($logger, sprintf(__('Import #%s is not triggered. Request skipped.', 'pmxi_plugin'), $id));	
							}
							elseif ( (int) $import->executing ){
								$logger and call_user_func($logger, sprintf(__('Import #%s is currently in manually process. Request skipped.', 'pmxi_plugin'), $id));	
							}
							elseif ( (int) $import->triggered and ! (int) $import->processing ){								
								
								$log_storage = (int) PMXI_Plugin::getInstance()->getOption('log_storage');

								// unlink previous logs
								$by = array();
								$by[] = array(array('import_id' => $id, 'type NOT LIKE' => 'trigger'), 'AND');
								$historyLogs = new PMXI_History_List();
								$historyLogs->setColumns('id', 'import_id', 'type', 'date')->getBy($by, 'id ASC');
								if ($historyLogs->count() and $historyLogs->count() >= $log_storage ){
									$logsToRemove = $historyLogs->count() - $log_storage;
									foreach ($historyLogs as $i => $file){																					
										$historyRecord = new PMXI_History_Record();
										$historyRecord->getBy('id', $file['id']);
										if ( ! $historyRecord->isEmpty()) $historyRecord->delete( false ); // unlink history file only
										if ($i == $logsToRemove)
											break;
									}
								}	

								$history_log = new PMXI_History_Record();						
								$history_log->set(array(
									'import_id' => $import->id,
									'date' => date('Y-m-d H:i:s'),
									'type' => 'processing',
									'summary' => __("cron processing", "pmxi_plugin")
								))->save();	

								if ($log_storage){
									$wp_uploads = wp_upload_dir();	
									$log_file = pmxi_secure_file( $wp_uploads['basedir'] . "/wpallimport/logs", 'logs', $history_log->id ) . '/' . $history_log->id . '.html';
									if ( @file_exists($log_file) ) pmxi_remove_source($log_file, false);	
								}

								ob_start();

								$import->set(array('canceled' => 0, 'failed' => 0))->execute($logger, true, $history_log->id);

								$log_data = ob_get_clean();
								
								if ($log_storage){									
									$log = @fopen($log_file, 'a+');				
									@fwrite($log, $log_data);
									@fclose($log);
								}

								if ( ! (int) $import->queue_chunk_number ){

									$logger and call_user_func($logger, sprintf(__('Import #%s complete', 'pmxi_plugin'), $import->id));	

								}
								else{

									$logger and call_user_func($logger, sprintf(__('Records Count %s', 'pmxi_plugin'), (int) $import->count));
									$logger and call_user_func($logger, sprintf(__('Records Processed %s', 'pmxi_plugin'), (int) $import->queue_chunk_number));

								}

							}
							else {
								$logger and call_user_func($logger, sprintf(__('Import #%s already processing. Request skipped.', 'pmxi_plugin'), $id));								
							}

							break;					
						case 'pipe':					

							$import->execute($logger);

							break;
					}								
				}					
			}
		}		
	}	
		
}