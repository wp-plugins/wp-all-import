<?php

if ( ! function_exists('wp_all_import_ctx_mapping')){
	function wp_all_import_ctx_mapping( $ctx, $mapping_rules, $tx_name ){
		if ( ! empty( $mapping_rules) and $ctx['is_mapping']){			
			foreach ($mapping_rules as $rule) {
				if ( ! empty($rule[trim($ctx['name'])])){ 
					$ctx['name'] = trim($rule[trim($ctx['name'])]);											
					break;
				}
			}			
		}				
		return apply_filters('pmxi_single_category', $ctx, $tx_name);
	}
}