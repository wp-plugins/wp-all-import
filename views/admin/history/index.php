<h2>
	<?php if ($import->path): ?>
		<?php if ( in_array($import->type, array('upload'))): ?>
			<?php
			$path = $import->path;
			$path_parts = pathinfo($import->path);
			if ( ! empty($path_parts['dirname'])){
				$path_all_parts = explode('/', $path_parts['dirname']);
				$dirname = array_pop($path_all_parts);
				if ( pmxi_isValidMd5($dirname)){								
					$path = str_replace($dirname . '/', '', str_replace('temp/','',$import->path));
				}
			}
			?>
			<em><?php printf(__('%s - Import History', 'pmxi_plugin'), str_replace("\\", '/', preg_replace('%^(\w+://[^:]+:)[^@]+@%', '$1*****@', $path))); ?></em>
		<?php else:?>
		<em><?php printf(__('%s - Import History', 'pmxi_plugin'), str_replace("\\", '/', preg_replace('%^(\w+://[^:]+:)[^@]+@%', '$1*****@', $import->path))); ?></em>
		<?php endif; ?>
	<?php endif ?>	
</h2>

<?php if ($this->errors->get_error_codes()): ?>
	<?php $this->error() ?>
<?php endif ?>

<?php
// define the columns to display, the syntax is 'internal name' => 'display name'
$columns = array(
	'id'			=> __('ID', 'pmxi_plugin'),
	'date'			=> __('Date', 'pmxi_plugin'),
	'time_run'		=> __('Run Time', 'pmxi_plugin'),	
	'type'			=> __('Type', 'pmxi_plugin'),
	'summary'		=> __('Summary', 'pmxi_plugin'),	
	'download'		=> '',
);
?>

<?php if ( $import->triggered ): ?>
	<p> <strong><?php _e('Scheduling Status', 'pmxi_plugin'); ?>:</strong> <?php _e('triggered'); ?> <?php if ($import->processing) _e('and processing', 'pmxi_plugin'); ?>...</p>
<?php endif; ?>

<form method="post" id="import-list" action="<?php echo remove_query_arg('pmxi_nt') ?>">
	<input type="hidden" name="action" value="bulk" />
	<?php wp_nonce_field('bulk-imports', '_wpnonce_bulk-imports') ?>

	<div class="tablenav">
		<div class="alignleft actions">
			<select name="bulk-action">
				<option value="" selected="selected"><?php _e('Bulk Actions', 'pmxi_plugin') ?></option>
				<option value="delete"><?php _e('Delete', 'pmxi_plugin') ?></option>
			</select>
			<input type="submit" value="<?php esc_attr_e('Apply', 'pmxi_plugin') ?>" name="doaction" id="doaction" class="button-secondary action" />
		</div>

		<?php if ($page_links): ?>
			<div class="tablenav-pages">
				<?php echo $page_links_html = sprintf(
					'<span class="displaying-num">' . __('Displaying %s&#8211;%s of %s', 'pmxi_plugin') . '</span>%s',
					number_format_i18n(($pagenum - 1) * $perPage + 1),
					number_format_i18n(min($pagenum * $perPage, $list->total())),
					number_format_i18n($list->total()),
					$page_links
				) ?>
			</div>
		<?php endif ?>
	</div>
	<div class="clear"></div>

	<table class="widefat pmxi-admin-imports">
		<thead>
		<tr>
			<th class="manage-column column-cb check-column" scope="col" style="padding: 8px 10px;">
				<input type="checkbox" style="margin-top:1px;"/>
			</th>
			<?php
			$col_html = '';
			foreach ($columns as $column_id => $column_display_name) {
				if ( ! in_array($column_id, array('download'))){
					$column_link = "<a href='";
					$order2 = 'ASC';
					if ($order_by == $column_id)
						$order2 = ($order == 'DESC') ? 'ASC' : 'DESC';

					$column_link .= esc_url(add_query_arg(array('id' => $id, 'order' => $order2, 'order_by' => $column_id), $this->baseUrl));
					$column_link .= "'>{$column_display_name}</a>";
					$col_html .= '<th scope="col" class="column-' . $column_id . ' ' . ($order_by == $column_id ? $order : '') . '">' . $column_link . '</th>';
				}
				else $col_html .= '<th scope="col" class="column-' . $column_id . '">' . $column_display_name . '</th>';
			}
			echo $col_html;
			?>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<th class="manage-column column-cb check-column" scope="col" style="padding: 8px 10px;">
				<input type="checkbox" />
			</th>
			<?php echo $col_html; ?>
		</tr>
		</tfoot>
		<tbody id="the-pmxi-admin-import-list" class="list:pmxi-admin-imports">
		<?php if ($list->isEmpty()): ?>
			<tr>
				<td colspan="<?php echo count($columns) + 1 ?>"><?php _e('No previous history found.', 'pmxi_plugin') ?></td>
			</tr>
		<?php else: ?>
			<?php
			$class = '';
			?>
			<?php foreach ($list as $item): ?>
				<?php $class = ('alternate' == $class) ? '' : 'alternate'; ?>
				<tr class="<?php echo $class; ?>" valign="middle">					
					<th scope="row" class="check-column" style="vertical-align: middle; padding: 8px 10px;">
						<input type="checkbox" id="item_<?php echo $item['id'] ?>" name="items[]" value="<?php echo esc_attr($item['id']) ?>" />
					</th>
					<?php foreach ($columns as $column_id => $column_display_name): ?>
						<?php
						switch ($column_id):
							case 'id':
								?>
								<th valign="top" scope="row" style="vertical-align: middle;">
									<?php echo $item['id'] ?>
								</th>
								<?php
								break;
							case 'date':
								?>
								<td style="vertical-align: middle;">
									<?php if ('0000-00-00 00:00:00' == $item['date']): ?>
										<em>never</em>
									<?php else: ?>
										<?php echo mysql2date(__('Y/m/d g:i a', 'pmxi_plugin'), $item['date']) ?>
									<?php endif ?>
								</td>
								<?php
								break;
							case 'time_run':
								?>
								<td style="vertical-align: middle;">
									<?php echo ($item['time_run'] and is_numeric($item['time_run'])) ? gmdate("H:i:s", $item['time_run']) : '-'; ?>
								</td>
								<?php
								break;							
							case 'summary':
								?>
								<td style="vertical-align: middle;">
									<?php echo $item['summary'];?>
								</td>
								<?php
								break;
							case 'type':
								?>
								<td style="vertical-align: middle;">
									<?php
									switch ($item['type']) {
										case 'manual':
											_e('manual run', 'pmxi_plugin');
											break;
										case 'continue':
											_e('continue run', 'pmxi_plugin');
											break;
										case 'processing':
											_e('cron processing', 'pmxi_plugin');
											break;
										case 'trigger':
											_e('triggered by cron', 'pmxi_plugin');
											break;
										default:
											# code...
											break;
									}
									?>
								</td>
								<?php
								break;
							case 'download':
								?>
								<td style="vertical-align: middle;">
									<?php 
									if ( ! in_array($item['type'], array('trigger'))){
										$wp_uploads = wp_upload_dir();
										$log_file = pmxi_secure_file( $wp_uploads['basedir'] . "/wpallimport/logs", 'logs', $item['id'] ) . '/' . $item['id'] . '.html';
										
										if (file_exists($log_file)){
											?>
											<a href="<?php echo add_query_arg(array('id' => $import->id, 'action' => 'log', 'history_id' => $item['id']), $this->baseUrl); ?>"><?php _e('Download Log', 'pmxi_plugin'); ?></a>
											<?php
										} 
										else { 
											_e('Log Unavailable', 'pmxi_plugin'); 
										}										
									} 
									else { 
										?>									
										&nbsp;
										<?php 
									}; 
									?>
								</td>
								<?php
								break;							
							default:
								?>
								<td>
									<?php echo $item[$column_id]; ?>
								</td>
								<?php
								break;
						endswitch;
						?>
					<?php endforeach; ?>
				</tr>								
			<?php endforeach; ?>
		<?php endif ?>
		</tbody>
	</table>

	<div class="tablenav">
		<?php if ($page_links): ?><div class="tablenav-pages"><?php echo $page_links_html ?></div><?php endif ?>

		<div class="alignleft actions">
			<select name="bulk-action2">
				<option value="" selected="selected"><?php _e('Bulk Actions', 'pmxi_plugin') ?></option>
				<?php if ( empty($type) or 'trash' != $type): ?>
					<option value="delete"><?php _e('Delete', 'pmxi_plugin') ?></option>
				<?php else: ?>
					<option value="restore"><?php _e('Restore', 'pmxi_plugin')?></option>
					<option value="delete"><?php _e('Delete Permanently', 'pmxi_plugin')?></option>
				<?php endif ?>
			</select>
			<input type="submit" value="<?php esc_attr_e('Apply', 'pmxi_plugin') ?>" name="doaction2" id="doaction2" class="button-secondary action" />
		</div>
	</div>
	<div class="clear"></div>
	<a href="http://soflyy.com/" target="_blank" class="wpallimport-created-by"><?php _e('Created by', 'pmxi_plugin'); ?> <span></span></a>
</form>