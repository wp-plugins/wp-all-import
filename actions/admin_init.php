<?php
	
function pmxi_admin_init(){
	wp_enqueue_script('pmxi-script', PMXI_ROOT_URL . '/static/js/pmxi.js', array('jquery'), PMXI_VERSION);	

    @ini_set('mysql.connect_timeout', 300);
    @ini_set('default_socket_timeout', 300);    
	
}