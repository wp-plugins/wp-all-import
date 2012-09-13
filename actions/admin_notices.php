<?php 

function pmxi_admin_notices() {
	// notify user if history folder is not writable
	if ( ! is_dir(PMXI_Plugin::ROOT_DIR . '/history') or ! is_writable(PMXI_Plugin::ROOT_DIR . '/history')) {
		?>
		<div class="error"><p>
			<?php printf(
					__('<b>%s Plugin</b>: History folder %s must be writable for the plugin to function properly. Please deactivate the plugin, set proper permissions to the folder and activate the plugin again.', 'pmxi_plugin'),
					PMXI_Plugin::getInstance()->getName(),
					PMXI_Plugin::ROOT_DIR . '/history'
			) ?>
		</p></div>
		<?php
	}

	$input = new PMXI_Input();
	$messages = $input->get('pmxi_nt', array());
	if ($messages) {
		is_array($messages) or $messages = array($messages);
		foreach ($messages as $type => $m) {
			in_array((string)$type, array('updated', 'error')) or $type = 'updated';
			?>
			<div class="<?php echo $type ?>"><p><?php echo $m ?></p></div>
			<?php 
		}
	}
}