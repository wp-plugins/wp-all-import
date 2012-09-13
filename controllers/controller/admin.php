<?php
/**
 * Introduce special type for controllers which render pages inside admin area
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
abstract class PMXI_Controller_Admin extends PMXI_Controller {
	/**
	 * Admin page base url (request url without all get parameters but `page`)
	 * @var string
	 */
	public $baseUrl;
	/**
	 * Parameters which is left when baseUrl is detected
	 * @var array
	 */
	public $baseUrlParamNames = array('page', 'pagenum', 'order', 'order_by', 'type', 's', 'f');
	/**
	 * Whether controller is rendered inside wordpress page
	 * @var bool
	 */
	public $isInline = false;
	/**
	 * Constructor
	 */
	public function __construct() {
		$remove = array_diff(array_keys($_GET), $this->baseUrlParamNames);
		if ($remove) {
			$this->baseUrl = remove_query_arg($remove);
		} else {
			$this->baseUrl = $_SERVER['REQUEST_URI'];
		}
		parent::__construct();
		
		// add special filter for url fields
		$this->input->addFilter(create_function('$str', 'return "http://" == $str || "ftp://" == $str ? "" : $str;'));
		
		// enqueue required sripts and styles
		global $wp_styles;
		if ( ! is_a($wp_styles, 'WP_Styles'))
			$wp_styles = new WP_Styles();
		
		wp_enqueue_style('jquery-ui', PMXI_Plugin::getInstance()->getRelativePath() . '/static/js/jquery/css/smoothness/jquery-ui.css');
		wp_enqueue_style('jquery-tipsy', PMXI_Plugin::getInstance()->getRelativePath() . '/static/js/jquery/css/smoothness/jquery.tipsy.css');
		wp_enqueue_style('pmxi-admin-style', PMXI_Plugin::getInstance()->getRelativePath() . '/static/css/admin.css');
		wp_enqueue_style('pmxi-admin-style-ie', PMXI_Plugin::getInstance()->getRelativePath() . '/static/css/admin-ie.css');
		$wp_styles->add_data('pmxi-admin-style-ie', 'conditional', 'lte IE 7');
		
		$scheme_color = get_user_option('admin_color') and is_file(PMXI_Plugin::ROOT_DIR . '/static/css/admin-colors-' . $scheme_color . '.css') or $scheme_color = 'fresh';
		if (is_file(PMXI_Plugin::ROOT_DIR . '/static/css/admin-colors-' . $scheme_color . '.css')) {
			wp_enqueue_style('pmxi-admin-style-color', PMXI_Plugin::getInstance()->getRelativePath() . '/static/css/admin-colors-' . $scheme_color . '.css');
		}
		
		wp_enqueue_script('jquery-ui-datepicker', PMXI_Plugin::getInstance()->getRelativePath() . '/static/js/jquery/ui.datepicker.js', 'jquery-ui-core');
		wp_enqueue_script('jquery-ui-autocomplete', PMXI_Plugin::getInstance()->getRelativePath() . '/static/js/jquery/ui.autocomplete.js', array('jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-position'));
		wp_enqueue_script('jquery-tipsy', PMXI_Plugin::getInstance()->getRelativePath() . '/static/js/jquery/jquery.tipsy.js', 'jquery');
		wp_enqueue_script('jquery-nestable', PMXI_Plugin::getInstance()->getRelativePath() . '/static/js/jquery/jquery.mjs.nestedSortable.js', array('jquery', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable'));
		
		wp_enqueue_script('pmxi-admin-script', PMXI_Plugin::getInstance()->getRelativePath() . '/static/js/admin.js', array('jquery', 'jquery-ui-dialog', 'jquery-ui-datepicker', 'jquery-ui-draggable', 'jquery-ui-droppable'));
		
	}
	
	/**
	 * @see Controller::render()
	 */
	protected function render($viewPath = NULL)
	{
		// assume template file name depending on calling function
		if (is_null($viewPath)) {
			$trace = debug_backtrace();
			$viewPath = str_replace('_', '/', preg_replace('%^' . preg_quote(PMXI_Plugin::PREFIX, '%') . '%', '', strtolower($trace[1]['class']))) . '/' . $trace[1]['function'];
		}
		
		// render contextual help automatically
		$viewHelpPath = $viewPath;
		// append file extension if not specified
		if ( ! preg_match('%\.php$%', $viewHelpPath)) {
			$viewHelpPath .= '.php';
		}
		$viewHelpPath = preg_replace('%\.php$%', '-help.php', $viewHelpPath);
		$fileHelpPath = PMXI_Plugin::ROOT_DIR . '/views/' . $viewHelpPath;
				
		if (is_file($fileHelpPath)) { // there is help file defined
			ob_start();
			include $fileHelpPath;
			add_contextual_help(PMXI_Plugin::getInstance()->getAdminCurrentScreen()->id, ob_get_clean());
		}
		
		parent::render($viewPath);
	}
	
}