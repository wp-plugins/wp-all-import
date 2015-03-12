<?php

class PMXI_Handler extends PMXI_Session {

	/** cookie name */
	private $_cookie;

	/** session due to expire timestamp */
	private $_session_expiring;

	/** session expiration timestamp */
	private $_session_expiration;

	/** Bool based on whether a cookie exists **/
	private $_has_cookie = false;

	/**
	 * Constructor for the session class.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		
		$this->set_session_expiration();

		$this->_import_id = $this->generate_import_id();		

		$this->_data = $this->get_session_data();		

    	//add_action( 'shutdown', array( $this, 'save_data' ), 20 );

    }      

    /**
     * Return true if the current user has an active session, i.e. a cookie to retrieve values
     * @return boolean
     */
    public function has_session() {
    	return isset( $_COOKIE[ $this->_cookie ] ) || $this->_has_cookie || is_user_logged_in();
    }

    /**
     * set_session_expiration function.
     *
     * @access public
     * @return void
     */
    public function set_session_expiration() {
	    $this->_session_expiring    = time() + intval( apply_filters( 'wpallimport_session_expiring', 60 * 60 * 47 ) ); // 47 Hours
		$this->_session_expiration  = time() + intval( apply_filters( 'wpallimport_session_expiration', 60 * 60 * 48 ) ); // 48 Hours
    }
	
	public function generate_import_id() {

		$input = new PMXI_Input();
		$import_id = $input->get('id', 'new');
		
		return $import_id;

	}

	/**
	 * get_session_data function.
	 *
	 * @access public
	 * @return array
	 */
	public function get_session_data() {
		return (array) get_option( '_wpallimport_session_' . $this->_import_id . '_', array() );
	}

    /**
     * save_data function.
     *
     * @access public
     * @return void
     */
    public function save_data() {
    	// Dirty if something changed - prevents saving nothing new
    	if ( $this->_dirty && $this->has_session() ) {

			$session_option        = '_wpallimport_session_' . $this->_import_id . '_';
			$session_expiry_option = '_wpallimport_session_expires_' . $this->_import_id . '_';

	    	if ( false === get_option( $session_option ) ) {
	    		add_option( $session_option, $this->_data, '', 'no' );
		    	add_option( $session_expiry_option, $this->_session_expiration, '', 'no' );
	    	} else {
		    	update_option( $session_option, $this->_data );
	    	}	    	
	    }	    
    }

    public function convertData( $import_id ){

    	$this->_import_id = 'new';

    	$this->_data = $this->get_session_data();

    	$this->set_session_expiration();    	
    	
    	$this->_import_id = $import_id;

    	$this->clean_session();        	

		$this->_dirty = true;

		$this->save_data();
    }

    public function clean_session( $import_id = 'new' ){

    	global $wpdb;
		
		$now                = time();
		$expired_sessions   = array();
		$wpallimport_session_expires = $wpdb->get_results( $wpdb->prepare("SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE %s", "_wpallimport_session_expires_" . $import_id . "_%") );			
		
		foreach ( $wpallimport_session_expires as $wpallimport_session_expire ) {
			//if ( $now > intval( $wpallimport_session_expire->option_value ) ) {
				$session_id         = substr( $wpallimport_session_expire->option_name, 29 );
				$expired_sessions[] = $wpallimport_session_expire->option_name;  // Expires key
				$expired_sessions[] = "_wpallimport_session_$session_id"; // Session key
			//}
		}

		if ( ! empty( $expired_sessions ) ) {
			$expired_sessions_chunked = array_chunk( $expired_sessions, 100 );

			foreach ( $expired_sessions_chunked as $chunk ) {
				$option_names = implode( "','", $chunk );
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name IN ('$option_names')" );
			}
		}

    }
}