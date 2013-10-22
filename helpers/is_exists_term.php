<?php
if (!function_exists('is_exists_term')){
	function is_exists_term($tx_name, $name, $parent_id = ''){

		$term = false;

		delete_option("{$tx_name}_children");

		$siblings = get_terms($tx_name, array('fields' => 'all', 'get' => 'all', 'parent' => (int)$parent_id) );

		$defaults = array( 'alias_of' => '', 'description' => '', 'parent' => 0, 'slug' => '');
        $args = wp_parse_args(array('name' => $name, 'taxonomy' => $tx_name), $defaults);									        
        $args = sanitize_term($args, $tx_name, 'db');
		
		if (!empty($siblings)) foreach ($siblings as $t) {

			if ($t->name == wp_unslash($args['name'])){
				$term = array('term_id' => $t->term_id);
				break;
			}
		}

		return $term;
	}
}
?>