<?php 

function pmxi_shutdown() {
	PMXI_Plugin::$session = PMXI_Session::get_instance();
	PMXI_Plugin::$session->write_data();	
	do_action( 'pmxi_session_commit' );
}