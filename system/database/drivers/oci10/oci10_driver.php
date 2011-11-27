<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package	 CodeIgniter
 * @author	  Rick Ellis
 * @copyright   Copyright (c) 2006, EllisLab, Inc.
 * @license	 http://www.codeignitor.com/user_guide/license.html
 * @link		http://www.codeigniter.com
 * @since	   Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * oci10 Database Adapter Class
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the active record
 * class is being used or not.
 *
 * @package	 CodeIgniter
 * @subpackage  Drivers
 * @category	Database
 * @author	  Rick Ellis
 * @link		http://www.codeigniter.com/user_guide/database/
 */

/**
 * oci10 Database Adapter Class
 *
 * This is a modification of the DB_driver class to
 * permit access to oracle databases
 *
 * NOTE: this uses the PHP 4 oci methods
 *
 * @author	  Eric TINOCO
 * @since 2011-11-19 Antonin Crha <a.crha@pixvalley.com> Getters for errors
 *
 */
class CI_DB_oci10_driver extends CI_DB {

	// Set "auto commit" by default
	private $_commit = OCI_DEFAULT ;

	// need to track statement id and cursor id
	var $stmt_id;
	
	var $_bIsOracle = true ;
	
	var $_bPrefetch = true ;
	
	var $_iPrefetch = 1000 ;
	
	var $_sQuery = '' ;
	
	var $aOutputBinds = array() ;
	
	var $aInputBinds  = array() ;
	
	var $aSubstitution  = array() ;
	
	var $bColumnName   = true ;
	
	var $_sBindPrefixe  = ':' ; 
	
	var $_bOutputCursors = false ;
	
	var $_iCursorType = false ;
	
	var $_iCollectionType = false ;
	
	var $_iDefaultType = false ;

	function CI_DB_oci10_driver ($params) {
		
		$this->clean() ;
		if( defined('SQLT_RSET') ) {
			$this->_iCursorType = SQLT_RSET ;
		} else {
			$this->_iCursorType = OCI_B_CURSOR ;
		}
		
		if( defined('SQLT_NTY') ) {
			$this->_iCollectionType = SQLT_NTY ;
		} else {
			$this->_iCollectionType = OCI_B_NTY ;
		}
		
		if ( defined('SQLT_CHR') ) {
			$this->_iDefaultType = SQLT_CHR ;
		} else {
			$this->_iDefaultType = 1 ;
		}
		
		parent::CI_DB_driver($params);
	}
	
	/**
	 * Non-persistent database connection
	 *
	 * @access  private called by the base class
	 * @return  resource
	 */
	function db_connect()
	{
		return @ocilogon($this->username, $this->password, $this->hostname);
	}

	// --------------------------------------------------------------------

	/**
	 * Persistent database connection
	 *
	 * @access  private called by the base class
	 * @return  resource
	 */
	function db_pconnect()
	{
		return @ociplogon($this->username, $this->password, $this->hostname);
	}

	// --------------------------------------------------------------------

	/**
	 * Select the database
	 *
	 * @access  private called by the base class
	 * @return  resource
	 */
	function db_select()
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Version number query string
	 *
	 * @access  public
	 * @return  string
	 */
	function _version()
	{
		return @ociserverversion($this->conn_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Execute the query
	 *
	 * @access  private called by the base class
	 * @param   string  an SQL query
	 * @return  resource
	 */
	function _execute($sql)
	{		
		// oracle must parse the query before it is run. All of the actions with
		// the query are based on the statement id returned by ociparse
		$this->_set_stmt_id($sql);
		$this->_checkQuery($sql) ;
		if( false === $this->_prep_query($sql) ) {
			return false ;
		}
		
		$this->_sQuery = $sql ;
		
		$mReturn = $this->_executeQuery() ;
		
		return $mReturn ;
		
	}
	
	/**
	 * Prepare the query 
	 * Only if Oracle Type
	 *
	 * @access protected
	 * @return string
	 * @see    self::execute()
	 */	
    function _prep_query($sQuery) {
        
    	if ( true === $this->_bPrefetch  ) {
    		@ocisetprefetch($this->stmt_id, $this->_iPrefetch) ;
    	}
		
		if ( (count($this->aInputBinds) + count($this->aOutputBinds)) > 0 ){
		
			if(is_resource($this->stmt_id)) {		
				// Append Data to collection
				reset($this->aInputBinds) ;
				for($iBind=0,$iMaxi = count($this->aInputBinds);$iBind<$iMaxi;$iBind++){
	
					if( false === $this->_setInputBind(current($this->aInputBinds)) ) {
						return false ;						
					} else {
						next($this->aInputBinds) ;						
					}
				}
				
				// Append Data to collection
				reset($this->aOutputBinds) ;
				for($iBind=0,$iMaxi = count($this->aOutputBinds);$iBind<$iMaxi;$iBind++){
	
					$aBind = current($this->aOutputBinds) ;
					if( false === $this->_setOutputBind( $aBind ) ) {
						return false ;						
					} else {
						$this->aOutputBinds[key($this->aOutputBinds)] = $aBind ;
						next($this->aOutputBinds) ;						
					}
				}
			}
		}
    }
	
	/**
	 * Generate a statement ID
	 *
	 * @access  private
	 * @param   string  an SQL query
	 * @return  none
	 */
	function _set_stmt_id($sql)
	{
		if ( isset($this->stmt_id) === false 
		||   false === is_resource($this->stmt_id) 
		||   ( true == is_resource($this->stmt_id) && $sql != $this->_sQuery )
		) {
			$this->stmt_id = @ociparse($this->conn_id, $sql );
		}
	}

	/**
	 * getCursor.  Returns a cursor from the datbase
	 *
	 * @access  public
	 * @return  cursor id
	 */
	function getCursor()
	{
		return @ocinewcursor( $this->conn_id ) ;
	}

	// --------------------------------------------------------------------

	/**
	 * Begin Transaction
	 *
	 * @access	public
	 * @return	bool		
	 */	
	function trans_begin($test_mode = FALSE)
	{
		if ( ! $this->trans_enabled)
		{
			return TRUE;
		}
		
		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0)
		{
			return TRUE;
		}
		
		// Reset the transaction failure flag.
		// If the $test_mode flag is set to TRUE transactions will be rolled back
		// even if the queries produce a successful result.
		$this->_trans_failure = ($test_mode === TRUE) ? TRUE : FALSE;
		
		$this->_commit = OCI_DEFAULT;
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Commit Transaction
	 *
	 * @access	public
	 * @return	bool		
	 */	
	function trans_commit()
	{
		if ( ! $this->trans_enabled)
		{
			return TRUE;
		}

		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0)
		{
			return TRUE;
		}

		$ret = OCIcommit($this->conn_id);
		$this->_commit = OCI_COMMIT_ON_SUCCESS;
		return $ret;
	}

	// --------------------------------------------------------------------

	/**
	 * Rollback Transaction
	 *
	 * @access	public
	 * @return	bool		
	 */	
	function trans_rollback()
	{
		if ( ! $this->trans_enabled)
		{
			return TRUE;
		}

		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0)
		{
			return TRUE;
		}

		$ret = OCIrollback($this->conn_id);
		$this->_commit = OCI_COMMIT_ON_SUCCESS;
		return $ret;
	}

	// --------------------------------------------------------------------

	/**
	 * Escape String
	 *
	 * @access  public
	 * @param   string
	 * @return  string
	 */
	function escape_str($str)
	{
		return $str;
	}

	/**
	 * Returns the number of rows that were updated, inserted or deleted by the query. 
	 * Not all databases support this call. 
	 * Don't use it for applications which are designed to be portable across databases. 
	 * -1 is returned by databases which don't support this option.
	 *
	 * @access public
	 * @return integer
	 */
	function affected_rows() {
	    
	    return @oci_num_rows($this->stmt_id);
	}

	
	/**
	 * The error message string
	 *
	 * @access  private
	 * @return  string
	 */
	function _error_message()
	{
		$error = ocierror($this->stmt_id);
		return $error['message'];
	}

    /**
     * Public accessable error message getter.
     *
     * @since 2011-11-19 Created
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @access public
     * @return string
     */
    function getErrorMessage()
    {
        return $this->_error_message();
    }

	// --------------------------------------------------------------------

	/**
	 * The error message number
	 *
	 * @access  private
	 * @return  integer
	 */
	function _error_number()
	{
		$error = ocierror($this->stmt_id);
		return $error['code'];
	}

    /**
     * Public accessable error code getter
     *
     * @since 2011-11-19 Created
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @access public
     * @return string
     */
    function getErrorNumber()
    {
        return $this->_error_number();
    }

    /**
	 * Insert statement
	 *
	 * Generates a platform-specific insert string from the supplied data
	 *
	 * @access  public
	 * @param   string  the table name
	 * @param   array   the insert keys
	 * @param   array   the insert values
	 * @return  string
	 */
	function _insert($table, $keys, $values)
	{
		return false ;
	}

	/**
	 * Close DB Connection
	 *
	 * @access  public
	 * @param   resource
	 * @return  void
	 */
	function _close($conn_id)
	{
		@ocilogoff($conn_id);
	}
	

	/**
	 * Clear all binds for the current instance
	 * @author 	Aurélien Chevron <a.chevron@fotovista.com>
	 * @access	public
	 * @return 	void
	 */
	function clearAllBinds() {
		$this->_closeOutputBindCursors() ;
		unset($this->aInputBinds,$this->aOutputBinds) ;
		$this->aInputBinds = array() ;
		$this->aOutputBinds = array() ;
	}
	
	/**
	 * Log explain of each query in log files after truncating plan_table
	 * Only if debugExplain wanted and Oracle type database used
	 *
	 * @access protected
	 * @param  query $sql
	 * @see    self::execute()
	 * @link   http://www.toutenligne.com/index.php?contenu=sql_explain&menu=sql
	 */
	function _logExplain($sql,$bUseLogFile=false) {
		
		return '' ;
			
	}
	
	/**
     * @desc    Initialize object for binds, ect.
     * 
     * @author  Eric TINOCO <e.tinoco@fotovista.com>
     *
     * @date    2009/04/21
     * 
     * @version 1.0
     * 
     * @access  public
     * @return  void
     * 
     */
	function clean() {
		$this->clearAllBinds();
		
	}
	
	/**
	 * @desc    Always driver as an oracle driver
	 *
	 * @author  Eric TINOCO <e.tinoco@fotovista.com>
     *
     * @date    2009/04/21
     * 
     * @version 1.0
     * 
	 * @access  public
	 * @param   bool $bIsOracle
	 */
	function setIsOracle( $bIsOracle = true ) {
	    
	    $this->_bIsOracle = true ;
	}
	
	/**
	 * @desc     Returns if it is using a Oracle database type (for prefetch, session, etc)
	 *
	 * @author   Eric TINOCO <e.tinoco@fotovista.com>
     *
     * @date     2009/04/21
     * 
     * @version  1.0
	 * 
	 * @access   public
	 * 
	 * @return   bool
	 */
	function isOracle()	{
	    
	    return $this->_bIsOracle;
	}
	
	/**
	 * @desc     Set he prefetch integer
	 *
	 * @author   Eric TINOCO <e.tinoco@fotovista.com>
     *
     * @date     2009/04/21
     * 
     * @version  1.0
	 * 
	 * @param    integer $iPrefetch
	 * @return   bool
	 */
	function setPrefetch($iPrefetch) {
	    
	    if (is_int($iPrefetch)) {
	        
	        $this->setPrefetchState(true);
            $this->_iPrefetch = $iPrefetch;
            return true;
	    }else{ 
            if ($this->db_debug) {
                
                log_message('error', 'Driver Db_oci10 '."\t".'Method setPrefetch '.PHP_EOL."\t\t".'Invalid prefetch: "'.$iPrefetch.'"'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
                return $this->display_error('db_invalid_prefetch');
            }
            return false;
	    }
	}
    
    
    /**
     * @desc     Swith prefetch wether to use it or not
     *
     * @author   Eric TINOCO <e.tinoco@fotovista.com>
     *
     * @date     2009/04/21
     * 
     * @version  1.0
     * 
     * @access   public
     * @param    bool $bSwitch
     * @return   void
     */
    function setPrefetchState($bSwitch = false) {
        
            $this->_bPrefetch = $bSwitch ;
    }
	
	/**
	 * @desc    Check if query is "executable" (no select *)
	 *
	 * @author   Eric TINOCO <e.tinoco@fotovista.com>
     *
     * @date     2009/04/21
     * 
     * @version  1.0
	 * 
	 * @access  protected
	 * @param   string $sql
	 * @return  bool
	 * @see     self::execute()
	 */
	function _checkQuery($sql) {
	    
	    if ( stristr('update', $sql) || stristr('delete', $sql) ) {
	        
	        $this->setAutoCommitOff();
	    }
	}
	
	/**
     * Execute the query
     *
     * @access protected
     * @return integer  Result Id
	 * @see    self::execute()
     */
    function _executeQuery() {

    	$iResult =  @ociexecute($this->stmt_id, $this->_commit) ;    
        if( true === $this->_bOutputCursors ) {
        	
        	reset($this->aOutputBinds) ;
			for($iBind=0,$iMaxi = count($this->aOutputBinds);$iBind<$iMaxi;$iBind++){
				
				if( $this->aOutputBinds[key($this->aOutputBinds)]['TYPE'] === $this->_iCursorType ) {
					@ociexecute( $this->aOutputBinds[key($this->aOutputBinds)]['VALUE'] );
				}
				next($this->aOutputBinds) ;
        	}
        }
        
        if ($iResult == 0 ) {
            
            if ($this->db_debug) {
                
                log_message('error', 'Driver Db_oci10 '."\t".'Method _executeQuery '.PHP_EOL."\t\t".'Invalid query: "'.$this->_sQuery.'"'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
                return $this->display_error( 
                	array(
						'Error Number: '.$this->_sQuery,
						$this->_error_message()
					)
				);
            }
            return false;
        }
        
        return $iResult;
    }
	
	/**
	 * @desc    Free the cursor
	 *
	 * @access  protected
	 * @see     self::_close();
	 */
	function _freeCursor() {
	    
        if (is_resource ( $this->stmt_id ) ) {

            @ocifreestatement( $this->stmt_id );
        }
        unset($this->stmt_id);
	}
	
	/**
	 * Parse explain log of this connexion
	 * And print it to screen
	 *
	 * @access protected
	 * @param  query $sql
	 * @see    self::_close()
	 */
	function _parseExplain() {
	    echo '' ;
	}
	
	/**
	 * @desc 	Set the debug on for developers
	 * @since	2007/05/03 => Split debug and explain
	 * @access 	public
	 * @return 	void
	 */
	function setDebugOn($bExplain = false) {
	    
	    $this->db_debug = true;
	    @oci_internal_debug(1);
	}
	
	/**
	 * @desc 	Set the debug on for developers
	 * @since	2007/05/03 => Split debug and explain
	 * @access 	public
	 * @return 	void
	 */
	function setDebugOff() {
	    
	    $this->db_debug = false;
	    @oci_internal_debug(0);
	}
	
	/**
	 * Enable Explain logging and display
	 *
	 * @access public
	 * @see    self:setDebugOn
	 */
	function setDebugExplainOn() {
	    
	}

	/**
	 * Escape Table Name
	 *
	 * This function adds backticks if the table name has a period
	 * in it. Some DBs will get cranky unless periods are escaped
	 *
	 * @access	public
	 * @param	string	the table name
	 * @return	string
	 */
	function escapeTable($table) {
	    
		return $table;
	}
	
	/**
	 * Add a new Input bind to the object
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @param  string $sBind
	 * @param  long, double, string $value
	 * @param  short $shPrecision
	 * @param  short $shScale
	 * @return void
	 */
	function addInputBind( $sBind, $value, $shPrecision = null, $shScale = null, $sType = null) {
	    
		$sLocalBindType = $this->_setOracleBindType(strtoupper($sType)) ;
		
		//echo '<br /><br /><br /><br /> Input ' . $sLocalBindType . '-' . $sType ;
		
	    $this->aInputBinds[$sBind] = array(  'VARIABLE'      => $sBind,
	                                   'VALUE'         => $value,
	                                   'PRECISION'     => $shPrecision,
	                                   'SCALE'         => $shScale,
									   'TYPE'		   => $sLocalBindType );
	}
	
	
	/**
	 * Add a new Output bind to the object
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @param  string $sBind
	 * @param  long, double, string $value
	 * @param  short $shPrecision
	 * @param  short $shScale
	 * @return void
	 */
	function addOutputBind( $sType, $sBind, $value = null, $shPrecision = null, $shScale = null) {
	    
		$iLocalBindType = $this->_setOracleBindType(strtoupper($sType)) ;
		
	    $this->aOutputBinds[$sBind] = array( 'TYPE'          => $iLocalBindType,
	                                   'VARIABLE'      => $sBind,
	                                   'VALUE'         => $value,
	                                   'PRECISION'     => $shPrecision,
	                                   'SCALE'         => $shScale  ) ;
	}
	
	/**
	 * Add a substituion to the object
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @param  string $sBind
	 * @param  long, double, string $value
	 * @param  short $shPrecision
	 * @param  short $shScale
	 * @return void
	 */
	function addSubstitution( $sBind, $value, $shPrecision = null, $shScale = null) {
	    
	}
	
	/**
	 * Count all bind variables
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @return integer
	 */
	function countBinds() {
	    
	    return ( count($this->aOutputBinds) + count($this->aInputBinds) ) ;
	}
	
	/**
	 * Set an input bind
	 * Warning, not all databases support those functionalities.
	 *
	 * @access protected
	 * @param  array $aBind
	 * @return bool
	 * @see    this::_prepQuery()
	 */
	function _setInputBind($aBind) {
		
		return $this->_setBind($aBind,'INPUT') ;
	}
	
	/**
	 * Set an output bind
	 * Warning, not all databases support those functionalities.
	 *
	 * @access protected
	 * @param  array $aBind
	 * @return bool
	 * @see    this::_prepQuery()
	 */
	function _setOutputBind(&$aBind) {
	    
		return $this->_setBind($aBind,'OUTPUT') ;
	}
	
	/**
	 * @desc    Set bind according parameters and type (INPUT / OUTPUT)
	 *
	 * @author  Eric TINOCO <e.tinoco@fotovista.com>
	 * 
	 * @date    2009/04/23
	 * @version 1.0
	 * 
	 * @param   array $aBind
	 * @param   string $sType
	 * 
	 * @return  mixed
	 * 
	 */
	function _setBind( &$aBind, $sType = 'INPUT' ) {
		
		if(isset($aBind['VARIABLE'])  && !empty($aBind['VARIABLE']) && (isset($aBind['VALUE']) || is_null($aBind['VALUE']))){
			
			if( isset($aBind['TYPE'])  && ( $aBind['TYPE'] === $this->_iCollectionType )&& is_array($aBind['VALUES']) ){
				
				// Create an OCI-Collection object
				if( false !== isset($aBind['ORA_TYPE']) )
					$oCollection = @oci_new_collection($this->conn_id, $aBind['ORA_TYPE']);
				else
					return false ;
				
				// Append some category IDs to the collection;
				$iMaxj = count($aBind['VALUE']);
				for($j=0;$j<$iMaxj;$j++){
					
					$oCollection->append($aBind['VALUE'][$j]);
					
				}
				unset($j, $iMaxj);
				
				// Bind the collection to the parameter
				$bBind = @oci_bind_by_name($this->stmt_id, $aBind['VARIABLE'], $oCollection, -1, $aBind['TYPE']);
				unset($oCollection);
				
			}elseif ( isset($aBind['TYPE'])  && ($aBind['TYPE'] === $this->_iCursorType ) ){
								
				if( $sType == 'OUTPUT' ) {
					$aBind['VALUE'] = $this->getCursor() ;
					if( $aBind['VALUE'] === false ) {
						$bBind = false ;
					}
					$this->_bOutputCursors = true ;
				}
				
				// Bind the cursor resource to the Oracle argument
				$bBind = @oci_bind_by_name( $this->stmt_id, trim($aBind['VARIABLE']), $aBind['VALUE'] , -1, $aBind['TYPE'] );
				
			}elseif ( isset($aBind['TYPE']) ){
				
				if ( $aBind['TYPE'] == $this->_iDefaultType ) {
					if( $sType == 'OUTPUT' ) {
						$iLength = 4000 ;
					} else {
						$iLength = mb_strlen( $aBind['VALUE'] ) ;
					}
				} else {
					$iLength = -1 ;
				}
				$bBind = @oci_bind_by_name($this->stmt_id, trim($aBind['VARIABLE']), $aBind['VALUE'], $iLength, $aBind['TYPE']);
			}else{
				
				$bBind = @oci_bind_by_name($this->stmt_id, trim($aBind['VARIABLE']), $aBind['VALUE'], -1 );
			}
			return $bBind ;
			
		}else{

			if( !isset($aBind['VARIABLE'])  || empty($aBind['VARIABLE']) ){
			
				return false ;
			
			}else{
				
				return false ;
				
			}
			
		}
	}
	
	
	/**
	 * Set output binds
	 * Warning, not all databases support those functionalities.
	 *
	 * @access protected
	 * @param  array $aBind
	 * @return integer, double, string or bool
	 * @see    this::_prepQuery()
	 */
	function getOutputBinds( $sType = false, $sBind = false) {
	    
	    if ( false === $sBind ) {
	        
	        //foreach ($this->aOutputBinds as &$aBind ) {
	        for( $iInd=0, $iMax=count($this->aOutputBinds); $iInd<$iMax;$iInd++) {
	            $aBind = current($this->aOutputBinds) ;
	            $aBinds[$aBind['VARIABLE']] = $this->getOutputBind($aBind['VARIABLE'], $aBind['TYPE']);
	            next($this->aOutputBinds) ;
	        }
	        return $aBinds;
	    }else {
	    	
	        return $this->getOutputBind($sBind, $sType);
	    }
	}
	
	
	/**
	 * Set an output bind
	 * Warning, not all databases support those functionalities.
	 *
	 * @access protected
	 * @param  string $sBind
	 * @param  string $sType
	 * @return bool
	 * @see    this::getOutputBinds()
	 */
	function getOutputBind ( $sBind, $sType )
    {

        if ( isset( $this->aOutputBinds[ $sBind ] ) )
        {

            if ( $this->aOutputBinds[ $sBind ][ 'TYPE' ] === $this->_iCursorType )
            {

                if ( ! class_exists( 'CI_DB_oci10_result' ) )
                {
                    throw new Exception('Request error. Result class not present.', 20000);
                }
                
                if( is_resource( $this->aOutputBinds[ $sBind ][ 'VALUE' ] ) )
                {
                    $oResult = new CI_DB_oci10_result();
                    $oResult->stmt_id = $this->stmt_id;
                    $oResult->curs_id = $this->aOutputBinds[ $sBind ][ 'VALUE' ];
                    return $oResult;
                }
                else
                {
                    return false; // result is not valid resource. Something is wrong
                }
            }
            else
            {
                return $this->aOutputBinds[ $sBind ][ 'VALUE' ];
            }
        }
        else
        {
            return false;
        }
    }
	
	
	/**
	 * Set a substitution
	 * Warning, not all databases support those functionalities.
	 *
	 * @access protected
	 * @param  array $aBind
	 * @return bool
	 * @see    this::_prepQuery()
	 */
	function _setSubstitution($aSubstitution) {
	    
		return false;
	}
	
	
	/**
	 * Set autoCommit On
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @return bool
	 */
	function setAutoCommitOn() {
	    
	    $this->_commit = OCI_COMMIT_ON_SUCCESS ;
	}
	
	
	/**
	 * Set autoCommit Off
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @return bool
	 */
	function setAutoCommitOff() {
	    
	    $this->_commit = OCI_DEFAULT ;
	}
	
	
	/**
	 * Commit the previous queries
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @return bool
	 */
	function commit() {
	    
	    return @oci_commit($this->conn_id) ;
	}
	
	
	/**
	 * Rollback the previous queries
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @return bool
	 */
	function rollback() {
	    
	    return @oci_rollback($this->conn_id) ;
	}
	
	/**
	 * Terminates the session
	 * Warning, not all databases support those functionalities.
	 *
	 * @access protected
	 * @return void
	 */
	function endSession() {
	    
	}
	
	/**
	 * Suspend SQL Relay Session and Result Set in order to be resume later.
	 * 
	 * @access public
	 * @return array
	 */
	function suspendResult() {
	    
        return array() ;
	}
	
	/**
	 * Resume a previous Session and ResultSet
	 *
	 * @access public
	 * @param  array $aParams
	 * @return mixed   object or bool
	 */
	function resumeResultSet($aParams) {
		
		return true;
	}
	
	/**
	 * Tells the server to send or not to send any column info (names, types, sizes). 
	 *
	 * @access public
	 * @param  bool $bGet
	 * @return void
	 */
	function getColumnNames($bGet = false) {
	    
	}
	
	    
    /**
	 * Tells the server to send any column info (names, types, sizes). 
	 *
	 * @access public
	 * @return bool
	 */
	function setColumnNameOn() {
	       
		$this->bColumnName = true;
		return true;
	}
	
	
	/**
	 * Tells the server not to send any column info (names, types, sizes). 
	 *
	 * @access public
	 * @return bool
	 */
	function setColumnNameOff() {
	    
		$this->bColumnName = false;
		return true;
	}
	
	/**
	 * @desc    Close all output binds cursor that remain opened
	 *
	 * @author  Eric TINOCO <e.tinoco@fotovista.com>
	 * 
	 * @date    2009/04/23
	 * @version 1.0
	 * 
	 * @return void
	 * 
	 */
	function _closeOutputBindCursors() {
		if( count($this->aOutputBinds) > 0 ) {
        	foreach($this->aOutputBinds as &$aBind ) {
        		if( $aBind['TYPE'] === $this->_iCursorType  && is_resource($aBind['VALUE']) ) {
        			@ocifreestatement($aBind['VALUE']);
        		}
        	}
        }
	}
	
	/**
	 * @desc    redefine local bind type (allow being compliant with RELAY types)
	 *
	 * @author  Eric TINOCO <e.tinoco@fotovista.com>
	 * 
	 * @date    2009/04/23
	 * @version 1.0
	 * 
	 * @param   string $sType
	 */
	function _setOracleBindType($sType) {
		
		switch ( $sType ) {
			case 'CLOB' :
				$sOracleIntType = 'SQLT_CLOB' ;
				$sOciEquivalent = 'OCI_B_CLOB' ;
				break;
			case 'BLOB' :
				$sOracleIntType = 'SQLT_BLOB' ;
				$sOciEquivalent = 'OCI_B_BLOB' ;
				break;
			case 'FILE' :
				$sOracleIntType = 'SQLT_FILE' ;
				$sOciEquivalent = 'OCI_B_FILE' ;
				break;
			case 'CFILE' :
				$sOracleIntType = 'SQLT_CFILE' ;
				$sOciEquivalent = 'OCI_C_BFILE' ;
				break;
			case 'BFILE' :
				$sOracleIntType = 'SQLT_BFILE' ;
				$sOciEquivalent = 'OCI_B_BFILE' ;
				break;
			case 'ROWID' :
				$sOracleIntType = 'SQLT_RDD' ;
				$sOciEquivalent = 'OCI_B_ROWID' ;
				break;
			case 'COLLECTION' :
				return $this->_iCollectionType ;
				break;
			case 'INTEGER' :
				$sOracleIntType = 'SQLT_INT' ;
				$sOciEquivalent = 'OCI_B_INT' ;
				break;
			case 'RAW' :
				$sOracleIntType = 'SQLT_BIN' ;
				$sOciEquivalent = 'OCI_B_BIN' ;
				break;
			case 'LONG' :
				$sOracleIntType = 'SQLT_LNG' ;
				$sOciEquivalent = 8 ;
				break;
			case 'LRAW' :
				$sOracleIntType = 'SQLT_LBI' ;
				$sOciEquivalent = 24 ;
				break;
			case 'CURSOR' :
				return $this->_iCursorType ;
				break;
			case 'STRING' :
			default:
				return $this->_iDefaultType ;
		}
		
		// allow being compliant with PHP4&5
		if( defined($sOracleIntType) ) {
			return constant($sOracleIntType) ;
		} else {
			if( is_int($sOciEquivalent) ) {
				return $sOciEquivalent ;
			} else if( defined( $sOciEquivalent ) ) {
				return constant( $sOciEquivalent ) ;
			} else {
				return false ;
			}
		}
	}

}
