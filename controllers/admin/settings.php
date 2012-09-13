<?php 
/**
 * Admin Statistics page
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class PMXI_Admin_Settings extends PMXI_Controller_Admin {
	
	public function index() {
		$this->data['post'] = $post = $this->input->post(PMXI_Plugin::getInstance()->getOption());
		
		if ($this->input->post('is_settings_submitted')) { // save settings form
			check_admin_referer('edit-settings', '_wpnonce_edit-settings');
			
			if ( ! preg_match('%^\d+$%', $post['history_file_count'])) {
				$this->errors->add('form-validation', __('History File Count must be a non-negative integer', 'pmxi_plugin'));
			}
			if ( ! preg_match('%^\d+$%', $post['history_file_age'])) {
				$this->errors->add('form-validation', __('History Age must be a non-negative integer', 'pmxi_plugin'));
			}
			if (empty($post['html_entities'])) $post['html_entities'] = 0;
			if (empty($post['utf8_decode'])) $post['utf8_decode'] = 0;
			
			if ( ! $this->errors->get_error_codes()) { // no validation errors detected

				PMXI_Plugin::getInstance()->updateOption($post);
				$files = new PMXI_File_List(); $files->sweepHistory(); // adjust file history to new settings specified
				
				wp_redirect(add_query_arg('pmxi_nt', urlencode(__('Settings saved', 'pmxi_plugin')), $this->baseUrl)); die();
			}
		}
		
		if ($this->input->post('is_templates_submitted')) { // delete templates form
			$templates_ids = $this->input->post('templates', array());
			if (empty($templates_ids)) {
				$this->errors->add('form-validation', __('Templates must be selected', 'pmxi_plugin'));
			}
			if ( ! $this->errors->get_error_codes()) { // no validation errors detected
				$template = new PMXI_Template_Record();
				foreach ($templates_ids as $template_id) {
					$template->clear()->set('id', $template_id)->delete();
				}
				wp_redirect(add_query_arg('pmxi_nt', urlencode(sprintf(_n('%d template deleted', '%d templates deleted', count($templates_ids), 'pmxi_plugin'), count($templates_ids))), $this->baseUrl)); die();
			}
		}
		
		$this->render();
	}

	public function dismiss(){

		PMXI_Plugin::getInstance()->updateOption("dismiss", 1);

		exit('OK');
	}
	
}