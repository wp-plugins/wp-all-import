<?php 
/**
 * Admin License page
 * 
 * @author Max Tsiplyakov <makstsiplyakov@gmail.com>
 */
class PMXI_Admin_License extends PMXI_Controller_Admin {
	
	public function index() {			

		$this->data['post'] = $post = $this->input->post(PMXI_Plugin::getInstance()->getOption());		

		if ($this->input->post('is_licenses_submitted')) { // save settings form

			check_admin_referer('edit-licenses', '_wpnonce_edit-licenses');													

			if ( ! $this->errors->get_error_codes()) { // no validation errors detected				

				PMXI_Plugin::getInstance()->updateOption($post);				
				
				$this->activate_licenses();

				$this->deactivate_licenses();

				wp_redirect(add_query_arg('pmxi_nt', urlencode(__('Licenses saved', 'pmxi_plugin')), $this->baseUrl)); die();
			}

		}						

		$addons = new PMXI_Admin_Addons();

		$this->data['addons'] = $addons->get_premium_addons();			

		$this->data['addons']['PMXI_Plugin'] = array(
			'title' => __('WP All Import', 'pmxi_plugin'),
			'active' => (class_exists('PMXI_Plugin') and defined('PMXI_EDITION') and PMXI_EDITION == 'paid')
		);

		$this->data['addons'] = array_reverse($this->data['addons']);

		$this->render();
	}	

	/*
	*
	* Activate licenses for main plugin and all premium addons
	*
	*/
	protected function activate_licenses() {

		// listen for our activate button to be clicked
		if( isset( $_POST['pmxi_license_activate'] ) ) {			

			// retrieve the license from the database
			$options = PMXI_Plugin::getInstance()->getOption();
			
			foreach ($_POST['pmxi_license_activate'] as $class => $val) {							

				if (!empty($options['licenses'][$class])){

					$product_name = (method_exists($class, 'getEddName')) ? call_user_func(array($class, 'getEddName')) : false;

					if ( $product_name !== false ){
						// data to send in our API request
						$api_params = array( 
							'edd_action'=> 'activate_license', 
							'license' 	=> $options['licenses'][$class], 
							'item_name' => urlencode( $product_name ) // the name of our product in EDD
						);
						
						// Call the custom API.
						$response = wp_remote_get( add_query_arg( $api_params, $options['info_api_url'] ), array( 'timeout' => 15, 'sslverify' => false ) );

						// make sure the response came back okay
						if ( is_wp_error( $response ) )
							continue;

						// decode the license data
						$license_data = json_decode( wp_remote_retrieve_body( $response ) );
						
						// $license_data->license will be either "active" or "inactive"

						$options['statuses'][$class] = $license_data->license;
						
						PMXI_Plugin::getInstance()->updateOption($options);	
					}
				}

			}				

		}
	}

	/*
	*
	* Deactivate licenses for main plugin and all premium addons
	*
	*/
	protected function deactivate_licenses(){

		// listen for our activate button to be clicked
		if( isset( $_POST['pmxi_license_deactivate'] ) ) {	

			// retrieve the license from the database
			$options = PMXI_Plugin::getInstance()->getOption();	

			foreach ($_POST['pmxi_license_deactivate'] as $class => $val) {		

				if (!empty($options['licenses'][$class])){

					$product_name = (method_exists($class, 'getEddName')) ? call_user_func(array($class, 'getEddName')) : false;

					if ( $product_name !== false ){

						// data to send in our API request
						$api_params = array( 
							'edd_action'=> 'deactivate_license', 
							'license' 	=> $options['licenses'][$class], 
							'item_name' => urlencode( $product_name ) // the name of our product in EDD
						);
						
						// Call the custom API.
						$response = wp_remote_get( add_query_arg( $api_params, $options['info_api_url'] ), array( 'timeout' => 15, 'sslverify' => false ) );

						// make sure the response came back okay
						if ( is_wp_error( $response ) )
							continue;

						// decode the license data
						$license_data = json_decode( wp_remote_retrieve_body( $response ) );
						
						// $license_data->license will be either "deactivated" or "failed"
						if( $license_data->license == 'deactivated' ){

							$options['statuses'][$class] = 'deactivated';

							PMXI_Plugin::getInstance()->updateOption($options);	

						}							
					}
				}
			}			
		}
	}

	/*
	*
	* Check plugin's license
	*
	*/
	public static function check_license($class) {

		global $wp_version;

		$options = PMXI_Plugin::getInstance()->getOption();	

		if (!empty($options['licenses'][$class])){

			$product_name = (method_exists($class, 'getEddName')) ? call_user_func(array($class, 'getEddName')) : false;

			if ( $product_name !== false ){

				$api_params = array( 
					'edd_action' => 'check_license', 
					'license' => $license, 
					'item_name' => urlencode( $product_name ) 
				);

				// Call the custom API.
				$response = wp_remote_get( add_query_arg( $api_params, $options['info_api_url'] ), array( 'timeout' => 15, 'sslverify' => false ) );

				if ( is_wp_error( $response ) )
					return false;

				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				if( $license_data->license == 'valid' ) {
					return true;
					// this license is still valid
				} else {
					return false;
					// this license is no longer valid
				}
			}
		}

		return false;

	}
}