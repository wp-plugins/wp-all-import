<?php
if ( ! function_exists('wp_all_import_secure_file') ){

	function wp_all_import_secure_file( $targetDir, $importID = false){

		$is_secure_import = PMXI_Plugin::getInstance()->getOption('secure');

		if ( $is_secure_import ){			

			$dir = $targetDir . DIRECTORY_SEPARATOR . ( ( $importID ) ? md5( $importID . NONCE_SALT ) : md5( time() . NONCE_SALT ) );							

			@mkdir($dir, 0755);

			if (@is_writable($dir) and @is_dir($dir)){
				$targetDir = $dir;	
				@touch( $dir . DIRECTORY_SEPARATOR . 'index.php' );
			}
			
		}

		return $targetDir;
	}
}	