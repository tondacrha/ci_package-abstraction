<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Code Igniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		Rick Ellis
 * @copyright	Copyright (c) 2006, pMachine, Inc.
 * @license		http://www.codeignitor.com/user_guide/license.html 
 * @link		http://www.codeigniter.com
 * @since		Version 1.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * SQL Relay Database Adapter Class
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the active record
 * class is being used or not.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		Sylvain Gourvil <s.gourvil@fotovista.com>, j.justine <j.justine@fotovista.com>
 * @todo        Set as PHP5 code when server is ready
 * @todo        Destructor
 * @todo        Remplace direct display by template fetching
 * @todo        Transaction management
 * @version     0.1
 */


class CI_DB_sqlrelay_driver extends CI_DB {
    
    /**
     * Array of input binds to set
     *
     * @var array
     */
    var $aInputBinds;
    
    /**
     * Array of output binds to set
     *
     * @var array
     */
    var $aOutputBinds;
    
    /**
     * Array of output binds Cursor to set
     *
     * @var array
     */
    var $aOutputBindCursor;
    
    /**
     * Array of Substitution to set
     *
     * @var array
     */
    var $aSubstitution;
    
    /**
     * Query to execute
     *
     * @var string
     */
    var $_sQuery;
    
    /**
     * Has to prepare binds or not
     *
     * @var bool
     */
    var $_bUseBinds;
    
    /**
     * Has OutputBinds
     * 
     * @var bool
     */
    var $_bOutputBind;
    
    /**
     * Prefetch active or not
     *
     * @var bool
     */
    var $_bPrefetch;
    
    /**
     * Defined prefetch for the next request
     *
     * @var integer
     */
    var $_iPrefetch;
    
    /**
     * Define if it is an orcale database used by sqlRelay
     *
     * @var bool
     */
    var $_bIsOracle;
    
    /**
     * Is debug on ?
     *
     * @access protected
     * @var unknown_type
     */
    var $_bDebugExplain;
    
    /**
     * Explain log file id
     *
     * @var string
     */
    var $_sFileExplainId;
    
    /**
     * Array of elapsed time
     *
     * @var array
     */
    var $aElapseTime;
    
    /**
     * @desc	Is column name are needed
     * @author  Jody JUSTINE <j.justine@fotovista.com>
     * @since 	0.21
     * @var		boolean
     * @access	Private
     */
    private $_bColumnName = false;
    
    /**
     * @desc	Allows to decide if we use null values as null or as empty string
     * @author  Eric TINOCO <e.tinoco@fotovista.com>
     * @var		boolean
     * @access	Private
     */
    private $_bgetNullsAsNulls = false;

    /**
     * Constructor
     *
     */
    function CI_DB_sqlrelay_driver($params) {
//    function __construct() {

        $this->clean();
        $this->setIsOracle(false);

        $this->_iPrefetch = 1000;
        
        if ( isset( $params['getNullsAsNulls'] ) && true === $params['getNullsAsNulls'] ) {

          $this->_bgetNullsAsNulls = true;
        }
        
        parent::CI_DB_driver($params);
        //$this->setAutoCommitOn();
    }
    
    
    /**
     * Initialize object for binds, ect.
     *
     * @access public
     * @return void
     */
    function clean() {
        
        $this->_bOutputBind     = false ;
        $this->_bUseBinds       = false;
        $this->aInputBinds      = array();
        $this->aOutputBinds     = array();
        $this->aSubstitution    = array();
        //$this->aElapseTime    = array();
        $this->_bDebugExplainOn = false;
        $this->_sFileExplainId  = '';
        $this->setPrefetchState(true);
    }
    
    
    /**
     * Destructor
     *
     */
    function __destruct() {
        /**
         * unset des variables et fermeture session, curseur et connexion
         * et clear des binds et autres merdouilles...
         */
        unset($this->_bDebugExplain);
        unset($this->_bIsOracle);
        unset($this->_bOutputBind);
        unset($this->_bPrefetch);
        unset($this->_bUseBinds);
        unset($this->_iPrefetch);
        unset($this->_sFileExplainId);
        unset($this->_sQuery);
        unset($this->aInputBinds);
        unset($this->aOutputBindCursor);
        unset($this->aOutputBinds);
        unset($this->aSubstitution);
        unset( $this->_bColumnName );
        unset( $this->aElapseTime );
        
    }
	
	/**
	 * Define if it is using a Oracle database type (for prefetch, session, etc)
	 *
	 * @access public
	 * @param bool $bIsOracle
	 */
	function setIsOracle($bIsOracle = true) {
	    
	    $this->_bIsOracle = $bIsOracle;
	}
	
	/**
	 * Returns if it is using a Oracle database type (for prefetch, session, etc)
	 *
	 * @access public
	 * @return bool
	 */
	function isOracle()	{
	    
	    return $this->_bIsOracle;
	}
	
	/**
	 * Set he prefetch integer
	 *
	 * @param  integer $iPrefetch
	 * @return bool
	 */
	function setPrefetch($iPrefetch) {
	    
	    if (is_int($iPrefetch)) {
	        
	        $this->setPrefetchState(true);
            $this->_iPrefetch = $iPrefetch;
            return true;
	    }else{ 
            if ($this->db_debug) {
                
                log_message('error', 'Driver Db_sqlrelay '."\t".'Method setPrefetch '.PHP_EOL."\t\t".'Invalid prefetch: "'.$iPrefetch.'"'
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
     * Swith prefetch wether to use it or not
     *
     * @access  public
     * @param   bool $bSwitch
     * @return  void
     */
    function setPrefetchState($bSwitch = false) {
        
            $this->_bPrefetch = $bSwitch ;
    }
	
    
	/**
	 * Non-persistent database connection
	 * 
	 * @access	protected
	 * @return	resource
     * @see     parent::initialize();
	 */	
	function db_connect() {
	    
	    if ( ! function_exists('sqlrcon_alloc') ) {
	        
            log_message('error', 'Driver Db_sqlrelay '."\t".'Method db_connect '.PHP_EOL."\t\t".'Module SQL Relay non chargé dans la configuration serveur"'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
            );
            return false;
	    }
	    $sConnect = sqlrcon_alloc($this->hostname, $this->port, '', $this->username, $this->password,1,3);
	    if (sqlrcon_ping($sConnect) !== 1){
    	    
            log_message('error', 'Driver Db_sqlrelay '."\t".'Method db_connect '.PHP_EOL."\t\t".'Pas de ping serveur"'
                . PHP_EOL . " Connexion data "
				. PHP_EOL . " username : " . $this->username   
				. PHP_EOL . " hostname : " . $this->hostname
				. PHP_EOL . " database : " . $this->database
				. PHP_EOL . " dbdriver : " . $this->dbdriver
				. PHP_EOL . " dbprefix : " . $this->dbprefix
				. PHP_EOL . " port     : " . $this->port
            );
            return false;
        }
        if ( sqlrcon_identify($sConnect) == 'oracle8' )
        {
            $this->setIsOracle(true);
            $this->setPrefetchState(true);
        }else{
            $this->setIsOracle(false);
        }
        
//         $curs_id = sqlrcur_alloc($sConnect);
//         sqlrcur_prepareQuery($curs_id, "ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '. ' ");
//         sqlrcur_sendquery($curs_id, "ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '. ' ");
//         sqlrcur_free($curs_id);
        
        
        return $sConnect;
	}
	

	/**
	 * Persistent database connection
	 *
	 * @access	protected
	 * @return	resource
     * @see     parent::initialize();
	 */	
	function db_pconnect() {
	    
		return false;
	}
	

	/**
	 * Select the database
	 *
	 * @access	protected
	 * @return	resource
     * @see     parent::initialize();
	 */	
    function db_select() {
        
        return true;
    }
	

	/**
	 * Execute the query
	 *
	 * @access	protected
	 * @param	string	an SQL query
	 * @return	integer
     * @see     parent::query();
	 */	
	function _execute($sql) {
	
		/**
		 * Modif e.vincent@fotovista.com, 25/02/2008
		 * The line below is from DB_driver::query
		 * we use too much memory when we parse a file of 200000 lines and execute 200000 requests
		 */
        if ( $this->query_count > 100 ) {

			$this->queries 		= array( $this->queries[ 0 ] );
			$this->aElapseTime  = array();
			//$this->aElapseTime 	= $this->aElapseTime[ 0 ];
			//$this->query_count++ is in the DB_driver::query after this function, so we set to 0
			$this->query_count 	= 0;

        } 

		$time_start = list($sm, $ss) = explode(' ', microtime());   
	    
	    $this->_checkQuery($sql);
	    
        $this->_sQuery = $sql;
        
        $this->getCursor();
        $this->_bgetNullsAsNulls === true ? sqlrcur_getNullsAsNulls($this->curs_id) : sqlrcur_getNullsAsEmptyStrings($this->curs_id);

		//$this->_logExplain($sql);
		
		$this->_prep_query();
                      
        //Tells the server to send or not to send column info.
	    if ( $this->_bColumnName === false ) {
	        
	        sqlrcur_dontGetColumnInfo($this->curs_id);
	        
	    }else{
	        
	        sqlrcur_getColumnInfo($this->curs_id);
	        
	    }
        	    
		$iResult = $this->_executeQuery();
		
		if ( $this->_bPrefetch === false && $this->_bOutputBind == false ) {
		    
		    $this->endSession();
		    
		    
		}

		/**
		 * Modif e.vincent@fotovista.com, 25/02/2008
		 * Comment the next lines because when we parse a file of 200000 lines
		 * we use too much memory
		 */
		$time_end = list($em, $es) = explode(' ', microtime());
		$sTime = ($em + $es) - ($sm + $ss);

	    $this->aElapseTime[ ] = $sTime;
	   
		unset( $time_start, $time_end, $sm, $ss, $em, $es, $sTime );
		
		return $iResult;
	}
	
	
	/**
	 * Check if query is "executable" (no select *)
	 *
	 * @access protected
	 * @param  string $sql
	 * @return bool
	 * @see    self::execute()
	 */
	function _checkQuery($sql) {
	    
	    /*if ( ($this->db_debug === false) &&(ereg('[*]', $sql) ) ) {
	        
            $this->display_error('db_invalid_query', $sql);
            exit();
	    }*/
	    
	    if ( stristr('update', $sql) || stristr('delete', $sql) ) {
	        
	        $this->setAutoCommitOff();
	    }
	}
	

	/**
	 * Prepare the query 
	 * Only if Oracle Type
	 *
	 * @access protected
	 * @return string
	 * @see    self::execute()
	 */	
    function _prep_query() {
        
        if ( true === $this->_bPrefetch  ) {
        
            sqlrcur_setResultSetBufferSize($this->curs_id, $this->_iPrefetch);
            
        }
        
        if ( $this->isOracle() ) {
        	
            if ( count($this->aInputBinds) > 0 || count($this->aSubstitution) > 0 || count($this->aOutputBinds) > 0 ) {
                
    	        $this->_bUseBinds = true;
    	        $bSucceed         = true;
    	        
    	        /*
    	         * Prepare the query before substitution and/or binds 
    	         */
    	        
    	        sqlrcur_prepareQuery($this->curs_id, $this->_sQuery);
    	        
    	        /*
    	         * Set the different substitution 
    	         */
    	        for ( $i=0; $i < count($this->aSubstitution); $i++ ) {
    	            
    	            $this->_setSubstitution($this->aSubstitution[$i]);
    	        }
    	        /*
    	         * Set the different input substitution
    	         */
    	        for ( $i=0; $i < count($this->aInputBinds); $i++ ) {
    	            
    	            $this->_setInputBind($this->aInputBinds[$i]);
    	        }
    	           
    	        /*
    	         * Set the different output binds
    	         */
    	        for ( $i=0; $i < count($this->aOutputBinds); $i++ ) {
    	            
    	            $this->_bOutputBind = true;
                    $this->_setOutputBind($this->aOutputBinds[$i]);
    	        }
    	    }
        }
    }
	
	
	/**
	 * Allocate a cursor
	 *
	 * @access protected
	 * @see    self::execute();
	 * @return cursor id
	 */
	function getCursor() {

	    return $this->curs_id = sqlrcur_alloc($this->conn_id);
	}
    
    
    /**
     * Execute the query
     *
     * @access protected
     * @return integer  Result Id
	 * @see    self::execute()
     */
    function _executeQuery() {
        
        if ( $this->_bUseBinds === true ) {
        
            $iResult =  sqlrcur_executeQuery($this->curs_id);            
        }else{
            
            $iResult =  sqlrcur_sendquery($this->curs_id, $this->_sQuery);
        }
        if ($iResult == 0 ) {
            
            if ($this->db_debug) {
                
                log_message('error', 'Driver Db_sqlrelay '."\t".'Method _executeQuery '.PHP_EOL."\t\t".'Invalid query: "'.$this->_sQuery.'"'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
//                return $this->display_error('db_invalid_query', $this->_sQuery);
                return $this->display_error(
										array(
												'Error Number: '.$this->_sQuery,
												$this->_error_message()
											));
            }
            
            return false;
        }
        
        $this->clean();
        return $iResult;
    }
	
	
	/**
	 * Free the cursor
	 *
	 * @access protected
	 * @see    self::_close();
	 */
	function _freeCursor() {
	    
        if (is_resource ($this->curs_id) ) {
            
            sqlrcur_free($this->curs_id);
        }else{
        
            unset($this->curs_id);
        }
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
	function _logExplain($sql) {
	    
	    if ( $this->_bDebugExplain == true  && $this->isOracle() ) {
	        
	        /** TRUNCATE IN ORDER TO CLEAN PLAN_TABLE
	            CAN BE REMOVED IF NO_WAIT ERRORS       **/
	        $sSQLExplain = "TRUNCATE TABLE PLAN_TABLE";
	        sqlrcur_sendQuery($this->curs_id, $sSQLExplain);
	        
	        /** In order to identify connexion file **/
	        if ( $this->_sFileExplainId == '' ) {
	           
	            $this->_sFileExplainId  = date('Y-m-d H:i:s').'-'.(microtime() + mt_rand(0, 999));
	        }
	        
	        /** In order identify request in explain **/
	        $iTime = microtime() + mt_rand(0, 999);
	        
	        /** Calculate execution time of the request on the server **/
		    list($usec, $sec) = explode(" ",microtime()); 
            $time_Start = ((float)$usec + (float)$sec); 
	        
            /** EXPLAIN QUERY **/
	        $query = "EXPLAIN PLAN 
		              SET STATEMENT_ID = '".$iTime."' 
		              FOR ".$sql;
	        sqlrcur_prepareQuery($this->curs_id, $query);
	        sqlrcur_executeQuery($this->curs_id);
	        
    		list($usec, $sec) = explode(" ",microtime()); 
            $time_end = ((float)$usec + (float)$sec); 
    		$_elapsedTime += $time_end - $time_Start;
	        
	        /** GETTING BACK INFORMATIONS **/
	        $sSQLExplain = "SELECT  ID, PARENT_ID, OPERATION, OPTIONS, OBJECT_NAME, OBJECT_TYPE, 
	                                COST, CARDINALITY, BYTES, OPTIMIZER
                            FROM    PLAN_TABLE
                            WHERE   STATEMENT_ID = '".$iTime."'
                            ORDER   BY id, parent_id";
	        sqlrcur_prepareQuery($this->curs_id, $sSQLExplain);
	        $iResult        = sqlrcur_executeQuery($this->curs_id);
            $iNbFirstResult = sqlrcur_rowCount($this->curs_id);
            $i              = 0;
            $aResult        = array();
            while ( $i < $iNbFirstResult ) {
                
                $aResult[] = sqlrcur_getRowAssoc($this->curs_id, $i);
                $i++;
            }
            
            /** Writing in log file **/
	        $filepath = BASEPATH.'/logs/SQL/Explain/'.$this->_sFileExplainId.'.log';
    		if ( ! $fp = @fopen($filepath, "a")) {
    		    
    			return FALSE;
    		}
            fwrite($fp, str_replace("\n", ' ', $sql).' ( '.round($_elapsedTime, 4)." sec )\n");
	        foreach ( $aResult as $aExplain ) {
    
        		$message = $aExplain['ID'].'|'.$aExplain['PARENT_ID'].'|'.
        		           $aExplain['OPERATION'].'|'.$aExplain['OPTIONS'].'|'.
        		           $aExplain['OBJECT_NAME'].'|'.$aExplain['OBJECT_TYPE'].'|'.
        		           $aExplain['COST'].'|'.$aExplain['CARDINALITY'].'|'.
        		           $aExplain['BYTES'].'|'.$aExplain['OPTIMIZER']."\n";
        		
        		flock($fp, LOCK_EX);	
        		fwrite($fp, $message);
        		flock($fp, LOCK_UN);
	        }
    		fclose($fp);
    		@chmod($filepath, 0666); 		
	    }
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
	    
	    if ( $this->_bDebugExplain == true && $this->isOracle() ) {
	        $sFilepath = '/var/www/CodeIgniter_1.4.1/system/logs/SQL/explain/'.$this->_sFileExplainId.'.log';
	        $rFile     = fopen ($sFilepath, "rb");
            $sContent  = fread ($rFile, filesize ($sFilepath));
            fclose ($rFile);
            $aTime = explode("\n", $sContent);
            $sEcho = '
    			<table align="center" style="width: 95%; border: 1px solid black; background-color: white; font-style: normal;">
        			<tr style="background-color: #6389D8;color:white">
        				<td align="center"><b>Id</b></td>
        				<td align="center"><b>Par.</b></td>
        				<td align="center"><b>Operation</b></td>
        				<td align="center"><b>Options</b></td>
        				<td align="center"><b>OBJ_Name</b></td>
        				<td align="center"><b>OBJ_Type</b></td>
        				<td align="center"><b>Cost</b></td>
        				<td align="center"><b>Card.</b></td>
        				<td align="center"><b>Bytes</b></td>
        				<td align="center"><b>OPTIM.</b></td>
        			</tr>';
            for ($i = 0, $j = count($aTime); $i<$j; $i++ ) {
                $aTemp = explode('|', $aTime[$i]);
                if ($aTime[$i] == '') {
                    
                    break;
                }
                if ( bcmod($i,2) == 0) {
                    
                    $sEcho .= '<tr style="background-color:#DDDDDD;">';
                }else{
                    $sEcho .= '<tr style="background-color:#EEEEEE;">';
                }
                for ($a = 0, $b = count($aTemp); $a<$b; $a++ ) {
                    if ($b == 1) {
                        $iNumQuery ++;
                        $sEcho .= '<td colspan=10 style="background-color:E5ECF9; color:#6389D8; font-weight:bold"> + '.$iNumQuery.' -- '.$aTemp[$a].'<td>';
                    }else{
                        $sEcho .= '<td valign=top" align="center">'.$aTemp[$a].'</td>'; 
                    }
                }
                $sEcho .= '</tr>';
            }
            $sEcho .= '</table>';
            $this->_bDebugExplain = false;
            echo $sEcho;
	    }
	}
	

	/**
	 * Close DB Connection
	 *
	 * @access	public
	 * @param	resource
	 * @return	void
	 */
	function _close() {
	    
        $this->endSession();
		if ($this->curs_id != NULL) {
		    
		   $this->_freeCursor($this->curs_id);
		}
		@sqlrcon_free($this->conn_id);
        $this->_parseExplain();
	}
		
	
	/**
	 * @desc 	Set the debug on for developers
	 * @since	2007/05/03 => Split debug and explain
	 * @access 	public
	 * @return 	void
	 */
	function setDebugOn($bExplain = false) {
	    
	    $this->db_debug = true;
	    sqlrcon_debugOn($this->conn_id);

//DEPRECATED
// Split Debug and explain
//	    if ( true === $bExplain ) {
//	        
//	        $this->setDebugExplainOn();
//	    }
//DEPRECATED

	}
	

	/**
	 * @desc 	Set the debug on for developers
	 * @since	2007/05/03 => Split debug and explain
	 * @access 	public
	 * @return 	void
	 */
	function setDebugOff() {
	    
	    $this->db_debug = false;
	    sqlrcon_debugOff($this->conn_id);

	}
	
	/**
	 * Enable Explain logging and display
	 *
	 * @access public
	 * @see    self:setDebugOn
	 */
	function setDebugExplainOn() {
	    
	    $this->_bDebugExplain = true;
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
	    
		if (stristr($table, '.')) {
		    
			$table = preg_replace("/\./", "`.`", $table);
		}
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
	    
	    $this->aInputBinds[] = array(  'VARIABLE'      => $sBind,
	                                   'VALUE'         => $value,
	                                   'PRECISION'     => $shPrecision,
	                                   'SCALE'         => $shScale,
									   'TYPE'		   => $sType );
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
	    
	    $this->aOutputBinds[] = array( 'TYPE'          => $sType,
	                                   'VARIABLE'      => $sBind,
	                                   'VALUE'         => $value,
	                                   'PRECISION'     => $shPrecision,
	                                   'SCALE'         => $shScale  );
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
	    
	    $this->aSubstitution[] = array(    'VARIABLE'      => $sBind,
	                                       'VALUE'         => $value,
	                                       'PRECISION'     => $shPrecision,
	                                       'SCALE'         => $shScale  );
	}
	
	
	/**
	 * Clear all bind variables
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 */
	function clearAllBinds() {
	    
	    if ( $this->isOracle() ) {
	        
	    	if( is_resource ($this->curs_id) ){
	    		
    	    	sqlrcur_clearBinds($this->curs_id);	
	    		
	    	}
    	    $this->aInputBinds = array();
    	    $this->aOutputBinds = array();
    	    $this->aOutputBindCursor = array();
    	    $this->aSubstitution = array();
    	    return true;
	    }else{
	        
	        return false;
	    }
	}
	
	
	/**
	 * Count all bind variables
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @return integer
	 */
	function countBinds() {
	    
	    if ( $this->isOracle() ) {
	        
    	    return sqlrcur_countBindVariables($this->curs_id);
	    }else{
	        
	        return false;
	    }
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
	    
	    if ( $this->isOracle() ) {
	     
	        if ($aBind['TYPE'] == 'Clob') {
	        
	           if( sqlrcur_inputBindClob( $this->curs_id, $aBind['VARIABLE'], $aBind['VALUE'] ,strlen($aBind['VALUE']) ) ) {
        	        echo '<hr>'.mb_strlen($aBind['VALUE']).'<hr>';
        	        return true;
        	    }else{
                    if ($this->db_debug) {
                        
                        log_message('error', 'Driver Db_sqlrelay '."\t".'Method _setInputBind '.PHP_EOL."\t\t".'Unable to bind: "'.var_export($aBind, true).'"'
                            . PHP_EOL . " Connexion data "
        				    . PHP_EOL . " username : " . $this->username   
        				    . PHP_EOL . " hostname : " . $this->hostname
        				    . PHP_EOL . " database : " . $this->database
        				    . PHP_EOL . " dbdriver : " . $this->dbdriver
        				    . PHP_EOL . " dbprefix : " . $this->dbprefix
        				    . PHP_EOL . " port     : " . $this->port
    				    );
                        return $this->display_error('db_invalid_bind');
                    }
                    return false;
        	    }
	        }else{
	     
        	    if( sqlrcur_inputBind($this->curs_id, $aBind['VARIABLE'], $aBind['VALUE'], $aBind['PRECISION'], $aBind['SCALE']) ) {
        	        
        	        return true;
        	    }else{
                    if ($this->db_debug) {
                        
                        log_message('error', 'Driver Db_sqlrelay '."\t".'Method _setInputBind '.PHP_EOL."\t\t".'Unable to bind: "'.var_export($aBind, true).'"'
                            . PHP_EOL . " Connexion data "
            				. PHP_EOL . " username : " . $this->username   
            				. PHP_EOL . " hostname : " . $this->hostname
            				. PHP_EOL . " database : " . $this->database
            				. PHP_EOL . " dbdriver : " . $this->dbdriver
            				. PHP_EOL . " dbprefix : " . $this->dbprefix
            				. PHP_EOL . " port     : " . $this->port
                        );
                        return $this->display_error('db_invalid_bind');
                    }
                    return false;
        	    }
    	    }
	    }else{
	        
	        return false;
	    }
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
	function _setOutputBind($aBind) {
	    
	    if ( $this->isOracle() ) {
	        
    	    $sType = $aBind['TYPE'];
    	    $sFunction = "sqlrcur_defineOutputBind$sType"; 
            if ( $aBind['TYPE'] == 'string' ) {
               	    
    	       $sFunction($this->curs_id, $aBind['VARIABLE'], 2000);
    	    }else{
            
                $sFunction($this->curs_id, $aBind['VARIABLE']);
            }
	    }else{
	        
	        return false;
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
	    
	    if ( false === $sBind && $this->isOracle() ) {
	        
	        foreach ($this->aOutputBinds as $aBind ) {
	            
	            $aBinds[$aBind['VARIABLE']] = $this->getOutputBind($aBind['VARIABLE'], $aBind['TYPE']);
	        }
	        return $aBinds;
	    }else if ( $this->isOracle() ) {
	        
	        return $this->getOutputBind($sBind, $sType);
	    }else{
	        
	        return false;
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
	function getOutputBind($sBind, $sType) {

	    if ( $this->isOracle() ) {
	        
    	    $sFunction = "sqlrcur_getOutputBind$sType";
    	    if ( $sType == 'cursor' ) {
    	        
				if ( class_exists('CI_DB_sqlrelay_result')) {
				
	        	    $oResult = new CI_DB_sqlrelay_result();
	        	    $oResult->curs_id = $sFunction($this->curs_id, $sBind);
	
	        	    $oResult->conn_id = $this->conn_id;
	                sqlrcur_fetchFromBindCursor($oResult->curs_id);
	                return $oResult;
	            }else{
				
					log_message('error', 'Driver Db_sqlrelay '."\t".'Object Result '.PHP_EOL."\t\t".'Cant Create it. Not exist'
					    . PHP_EOL . " Connexion data "
        				. PHP_EOL . " username : " . $this->username   
        				. PHP_EOL . " hostname : " . $this->hostname
        				. PHP_EOL . " database : " . $this->database
        				. PHP_EOL . " dbdriver : " . $this->dbdriver
        				. PHP_EOL . " dbprefix : " . $this->dbprefix
        				. PHP_EOL . " port     : " . $this->port
					);
					redirect(WEB.'index.php/error/work','location'); 
				}
    	    }else{
                    	        
        	    return $sFunction($this->curs_id, $sBind);
    	    }
	    }else{
	        
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
	    
	    if ( $this->isOracle() ) {
	        
    	    if (sqlrcur_substitution($this->curs_id, $aSubstitution['VARIABLE'], $aSubstitution['VALUE'], $aSubstitution['PRECISION'], $aSubstitution['SCALE']) ) {
    	        
    	        return true;
    	    }else{
    	        
                if ($this->db_debug) {
                    
                    log_message('error', 'Driver Db_sqlrelay '."\t".'Method _setBind '.PHP_EOL."\t\t".'Unable to set substitution: "'.var_export($aSubstitution, true).'"'
                        . PHP_EOL . " Connexion data "
        				. PHP_EOL . " username : " . $this->username   
        				. PHP_EOL . " hostname : " . $this->hostname
        				. PHP_EOL . " database : " . $this->database
        				. PHP_EOL . " dbdriver : " . $this->dbdriver
        				. PHP_EOL . " dbprefix : " . $this->dbprefix
        				. PHP_EOL . " port     : " . $this->port
                    );
                    return $this->display_error('db_invalid_substitution');
                }
                return false;
    	    }
	    }else{
	        
	        return false;
	    }
	}
	
	
	/**
	 * Set autoCommit On
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @return bool
	 */
	function setAutoCommitOn() {
	    
	    if ( $this->isOracle() ) {
	        
    	    if ( $this->_bIsOracle === true ) {
    	        
    	        return sqlrcon_autoCommitOn($this->conn_id);
    	    }
	        return false;
	    }
	    
	}
	
	
	/**
	 * Set autoCommit Off
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @return bool
	 */
	function setAutoCommitOff() {
	    
	    if ( $this->isOracle() ) {

	        return sqlrcon_autoCommitOff($this->conn_id);
	    }
        return false;
	    
	}
	
	
	/**
	 * Commit the previous queries
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @return bool
	 */
	function commit() {
	    
	    if ( $this->isOracle() ) {
	        
    	    if ( sqlrcon_commit($this->conn_id) == 1 ){
    	        
    	        return true;
    	    }else{
    	        
    	        if ($this->db_debug) {
    	            
                    log_message('error', 'Driver Db_sqlrelay '."\t".'Method commit '.PHP_EOL."\t\t".'Unable to commit ! '
                        . PHP_EOL . " Connexion data "
        				. PHP_EOL . " username : " . $this->username   
        				. PHP_EOL . " hostname : " . $this->hostname
        				. PHP_EOL . " database : " . $this->database
        				. PHP_EOL . " dbdriver : " . $this->dbdriver
        				. PHP_EOL . " dbprefix : " . $this->dbprefix
        				. PHP_EOL . " port     : " . $this->port
                    );
                    return $this->display_error('db_unable_to_commit');
                }
                return false;
    	    }
	    }else{
	        
	        return false;
	    }
	}
	
	
	/**
	 * Rollback the previous queries
	 * Warning, not all databases support those functionalities.
	 *
	 * @access public
	 * @return bool
	 */
	function rollback() {
	    
	    if ( $this->isOracle() ) {
	        
    	    if ( sqlrcon_rollback($this->conn_id) == 1 ) {
    	        
    	        return true;
    	    }else{
    	        
    	        if ($this->db_debug) {
    	            
                    log_message('error', 'Driver Db_sqlrelay '."\t".'Method commit '.PHP_EOL."\t\t".'Unable to rollback ! '
                        . PHP_EOL . " Connexion data "
        				. PHP_EOL . " username : " . $this->username   
        				. PHP_EOL . " hostname : " . $this->hostname
        				. PHP_EOL . " database : " . $this->database
        				. PHP_EOL . " dbdriver : " . $this->dbdriver
        				. PHP_EOL . " dbprefix : " . $this->dbprefix
        				. PHP_EOL . " port     : " . $this->port
                    );
                    return $this->display_error('db_unable_to_rollback');
                }
                return false;
    	    }
	    }else{
	        
	        return false;
	    }
	}

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

		$ret = sqlrcon_commit($this->conn_id);
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

		$ret = sqlrcon_rollback($this->conn_id);
		$this->_commit = OCI_COMMIT_ON_SUCCESS;
		return $ret;
	}

		
	/**
	 * Terminates the session
	 * Warning, not all databases support those functionalities.
	 *
	 * @access protected
	 * @return void
	 */
	function endSession() {
	    
	    if ( $this->isOracle() ) {
	        
    	    @sqlrcon_endSession($this->conn_id);
	    }
	}
	
	
    /**
     * Escape String
     *
     * @access  public
     * @param   string
     * @return  string
     */
    function escape_str($str) {
        
        //return $str;
        return str_replace("'", "''", $str);
    }
    
	
	/**
	 * Suspend SQL Relay Session and Result Set in order to be resume later.
	 * 
	 * @access public
	 * @return array
	 */
	function suspendResult() {
	    
	    sqlrcur_suspendResultSet($this->curs_id);
	    sqlrcon_suspendSession($this->conn_id);
	    $aReturn['iResultSetId'] = sqlrcur_getResultSetId($this->curs_id);
        $aReturn['iPortId']      = sqlrcon_getConnectionPort($this->conn_id);
        $aReturn['iSocketId']    = sqlrcon_getConnectionSocket($this->conn_id);
        return $aReturn;
	}
	
	
	/**
	 * Resume a previous Session and ResultSet
	 *
	 * @access public
	 * @param  array $aParams
	 * @return mixed   object or bool
	 */
	function resumeResultSet($aParams) {
	    
		if ( ! $this->conn_id ) {
		    
			$this->initialize();
		}
	    if ( $this->curs_id == '' ) {
	        
            $this->getCursor();
	    }
	    $oResult           = new CI_DB_sqlrelay_result();
	    $oResult->curs_id  = $this->curs_id;
	    $oResult->conn_id  = $this->conn_id;
	    sqlrcon_resumeSession($this->conn_id, $aParams['iPortId'], $aParams['iSocketId'] );
        if ( sqlrcur_resumeResultSet($this->curs_id, $aParams['iResultSetId']) ) {   
            
            return $oResult;
        }else{
            
            return false;
        }
	}
	

	/**
	 * Tells the server to send or not to send any column info (names, types, sizes). 
	 *
	 * @access public
	 * @param  bool $bGet
	 * @return void
	 */
	function getColumnNames($bGet = false) {
	    
	    if ( false === $bGet ) {
	        
	        sqlrcur_dontGetColumnInfo($this->curs_id);
	    }else{
	        
	        sqlrcur_getColumnInfo($this->curs_id);
	    }
	}
	
	    
    /**
	 * Tells the server to send any column info (names, types, sizes). 
	 *
	 * @access public
	 * @return bool
	 */
	function setColumnNameOn() {
	    
	    if ( $this->isOracle() ) {
	        
    	    if ( $this->_bIsOracle === true ) {
    	        
    	        $this->_bColumnName = true;
    	        return true;
    	        
    	    }
	        
	    }
		return false;
	    
	}
	
	
	/**
	 * Tells the server not to send any column info (names, types, sizes). 
	 *
	 * @access public
	 * @return bool
	 */
	function setColumnNameOff() {
	    
	    if ( $this->isOracle() ) {

	        $this->_bColumnName = false;
	        return true;
	        
	    }
        return false;
	    
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
	    
	    return sqlrcur_affectedRows($this->curs_id);
	}
	
	
	/**
	 * If a query failed and generated an error, the error message is available here. 
	 * If the query succeeded then this function returns false
	 *
	 * @access public
	 * @return string
	 */
	function _error_message() {
	    
	    return sqlrcur_errorMessage($this->curs_id);
	}
	

	function _insert($table, $keys, $values) {
	
	   return false;
	}
	

    function _db_set_charset( $sCharset, $sExtraData ) {

        return true ;
    }
}
?>
