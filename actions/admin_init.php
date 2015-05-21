<?php
	
function pmxi_admin_init(){
    
	wp_enqueue_script('pmxi-script', WP_ALL_IMPORT_ROOT_URL . '/static/js/wp-all-import.js', array('jquery'), PMXI_VERSION);	

    @ini_set('mysql.connect_timeout', 300);
    @ini_set('default_socket_timeout', 300);
    
}