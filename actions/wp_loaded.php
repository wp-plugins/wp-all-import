<?php

function pmxi_wp_loaded() {			
	
	wp_enqueue_script('pmxi-script', PMXI_ROOT_URL . '/static/js/pmxi.js', array('jquery'));	
		
}