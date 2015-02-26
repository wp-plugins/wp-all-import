<?php
function pmxi_admin_head(){
	?>	
	<style type="text/css">
		#toplevel_page_pmxi-admin-home ul li:last-child{
			display: none;
		}
	</style>
	<?php	
	$input = new PMXI_Input();
	$import_id = $input->get('id', false);
	$import_action = $input->get('action', false);	
	if ($import_id){
		?>
		<script type="text/javascript">
			var import_id = '<?php echo $import_id; ?>';			
		</script>
		<?php
	}

	$wp_all_import_ajax_nonce = wp_create_nonce( "wp_all_import_secure" );

	?>
		<script type="text/javascript">
			var import_action = '<?php echo $import_action; ?>';
			var wp_all_import_security = '<?php echo $wp_all_import_ajax_nonce; ?>';
		</script>
	<?php
}