<?php
function pmxi_recursion_taxes($parent, $tx_name, $txes, $key){

	if ( is_array($parent) ){
		
		if ( empty($parent['parent']) ){

			$term = term_exists($parent['name'], $tx_name, 0);						

			if ( empty($term) and !is_wp_error($term) ){

				$term = term_exists(htmlspecialchars($parent['name']), $tx_name, 0);		
				if ( empty($term) and !is_wp_error($term) ){		
					$term = wp_insert_term(
						$parent['name'], // the term 
					  	$tx_name // the taxonomy			  	
					);
				}
			}

			return ( ! empty($term) and ! is_wp_error($term)) ? $term['term_id'] : 0;

		}
		else{
			
			$parent_id = pmxi_recursion_taxes($parent['parent'], $tx_name, $txes, $key);
			
			$term = term_exists($parent['name'], $tx_name, (int)$parent_id);				

			if ( empty($term) and  !is_wp_error($term) ){

				$term = term_exists(htmlspecialchars($parent['name']), $tx_name, (int)$parent_id);		
				if ( empty($term) and !is_wp_error($term) ){		
					$term = wp_insert_term(
						$parent['name'], // the term 
					  	$tx_name, // the taxonomy			  	
					  	array('parent'=> (!empty($parent_id)) ? (int)$parent_id : 0)
					);
				}
			}
			return ( ! empty($term) and ! is_wp_error($term)) ? $term['term_id'] : 0;

		}			
	}
	else{			

		if ( !empty($txes[$key - 1]) and !empty($txes[$key - 1]['parent']) and $parent != $txes[$key - 1]['parent']) {	

			$parent_id = pmxi_recursion_taxes($txes[$key - 1]['parent'], $tx_name, $txes, $key - 1);
			
			$term = term_exists($parent, $tx_name, (int)$parent_id);
			
			if ( empty($term) and ! is_wp_error($term) ){				
				$term = term_exists(htmlspecialchars($parent), $tx_name, (int)$parent_id);		
				if ( empty($term) and !is_wp_error($term) ){
					$term = wp_insert_term(
						$parent, // the term 
					  	$tx_name, // the taxonomy			  	
					  	array('parent'=> (!empty($parent_id)) ? (int)$parent_id : 0)
					);
				}
			}
			
			return ( ! empty($term) and ! is_wp_error($term) ) ? $term['term_id'] : 0;

		}
		else{
			
			$term = term_exists($parent, $tx_name);
			if ( empty($term) and !is_wp_error($term) ){					
				$term = term_exists(htmlspecialchars($parent), $tx_name);		
				if ( empty($term) and !is_wp_error($term) ){
					$term = wp_insert_term(
						$parent, // the term 
					  	$tx_name // the taxonomy			  	
					);
				}
			}				
			return ( ! empty($term) and ! is_wp_error($term)) ? $term['term_id'] : 0;
			
		}
	}

}
