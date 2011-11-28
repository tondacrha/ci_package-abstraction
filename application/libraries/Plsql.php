<?php

/**
 * Main purpouse of this class is to make codeigniter version 2.x users more
 * happy using plsql procedures stored in Oracle database packages.
 * 
 * -------------------------------------------------------
 * Main benefits:
 * 1) Easy configuration. One file per package, nothing more.
 * 2) Configure once, use many times
 * 3) Freedom of use. Can be mixed with Codeigniter database class
 * 4) No magic done. Database request flow is clean and easy to follow.
 * 5) Easy error handling
 * 6) MULTIPLE output parameters
 * 7) MULTIPLE or NO cursors - depends on user needs
 * 8) Prefetch setting. Significant request speedup when inteligent configured 
 * -------------------------------------------------------
 * 
 * This class needs oci10 or sqlrelay database driver to work.
 * oci10 with fixed error handling. stmt_id insted of conn_id in error handling
 * 
 * Configuration of this class and its childs is stored in object variables.
 * This way it can fully profit from the inharitance concept.
 * 
 * -------------------------------------------------------
 * ATTENTION:
 * Output params are mapped to php variables names.
 * Carefull naming of output params in configuration file is needed.
 * If params for output or input bind are not valid php variable name,
 * an ORA-01036 error is raised. Eg.: PCUR$out will result in Oracle error.
 * 
 * For future use (variable name validation regexp): 
 * [a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]* 
 * -------------------------------------------------------
 * 
 * @author Antonin Crha <a.crha@pixvalley.com>
 */
class Plsql
{
    /**
     * Configuration file location. Contains configuration for single package
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @var string 
     */
    private $_sConfigFile = '';
    
    /**
     * Stores complete configuration for single model.
     * All plsql procedures names and their parameters
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @var array
     */
    private $_aConfiguration = array ( );
    
    /**
     * Stores list of configured procedures. Filled with values from config file
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @var array
     */
    private $_aFunctions = array ( );
    
    /**
     * For storing loaded error configuratiuon messages from config file
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @var array
     */
    private $_aErrors = array ( );
    
    /**
     * Stores last error message
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @var string
     */
    private $_sLastErrorMessage = '';
    
    /**
     * Stores last error number
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @var integer
     */
    private $_iLastErrorNumber  = 0;
    
    /**
     * Contains codeigniter error class. Used for displaying errors.
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @var object 
     */
    private $_oError;

    public function __construct ( )
    {

    }
    
    public function initialize( $sConfigFile )
    {
        $this->CI = & get_instance();
        
        $this->CI->load->database();
        $this->_oError = & load_class( 'Exceptions', 'core' );
        $this->CI->load->config( PACKAGE_CONFIG_PATH . 'errors' . EXT );
        $this->_aErrors = $this->CI->config->item( ABST_ERROR_PREFIX );

        // codeigniter will instantiate this class anyway. So the hack with
        // optional parameter is needed to prevent codeigniter from mess things
        if ( $sConfigFile !== null )
        {
            $this->_loadConfiguration( $sConfigFile );
        }
    }

    /**
     * Loads all necessery configuration files for the abstraction to work
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param string $sConfigFile Path to configuration file
     */
    private function _loadConfiguration ( $sConfigFile )
    {
        $this->CI->load->config( $sConfigFile );

        $this->_sConfigFile = $sConfigFile;
        $this->_aConfiguration = $this->CI->config->item( PKG_CONF_PREFIX );
        $this->_aFunctions = array_keys( $this->_aConfiguration );

    }

    /**
     * In model extending this MY_Model class user will call non-existing
     * functions which are basically declared in config file for each model.
     * 
     * This callback for non-existing function checks if user defined the 
     * function in config file.
     * 
     * a) There is no configuration for called function => raise error
     * b) There is configuration for called function => load it and do request
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param strign $sName
     * @param array $aArguments Contains all parametr passed to function
     */
    public function __call ( $sName, $aArguments )
    {
        $_error = & load_class( 'Exceptions', 'core' );

        if ( !in_array( $sName, $this->_aFunctions ) )
        {
            printf( $this->_oError->show_error( $this->_aErrors[ 0 ], $this->_aErrors[ 1 ] ), $sName, APPPATH, $this->_sConfigFile, $this->getErrorCaller(1) );
            exit;
        }

        if ( count( $aArguments ) > 1 )
        {
            printf( $this->_oError->show_error( $this->_aErrors[ 0 ], $this->_aErrors[ 2 ] ), $this->getErrorCaller(1) );
            exit;
        }

        return $this->_request( $sName, $aArguments );
    }

    /**
     * This main private function is doing all the 
     * configuration and calls needed.
     * 
     * Does not need to know the package name because it 
     * is obvios from the object context.
     * 
     * All process inside this function needs 
     * only the [0] index of second parameter.
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param string $sProcedure called oracle database object name
     * @param array $aArguments field of arguments. Only index [0] taken
     */
    private function _request ( $sProcedure, $aArguments )
    {
        $this->CI->db->clearAllBinds(); // Performance tested. No issue here
        $aProcedureDetails = $this->_aConfiguration[ $sProcedure ];
        $this->_setPrefetch( $aProcedureDetails['PREFETCH'] );
        
        $this->_checkInputParams( $aProcedureDetails, $aArguments[0] );        
        $sSqlBinds  = '';
        $sSqlBinds .= $this->_bindInputParams( $aArguments[0] );
        $sSqlBinds .= trim( $this->_bindOutputParams( $aProcedureDetails['PARAMS_OUT'] ), ',' );
        
        $sPackage = $aProcedureDetails['PACKAGE'].'.'.$aProcedureDetails['PROCEDURE'];
        $sQuery = 'BEGIN ' . $sPackage . '(' . $sSqlBinds . '); END;';
        
        // doing the actual query into database
        if( false === $this->CI->db->query( $sQuery ) )
        {
            $this->_sLastErrorMessage = $this->CI->db->getErrorMessage();
            $this->_iLastErrorNumber = $this->CI->db->getErrorNumber();
            return false;
        }
        else
        {
                $this->_fillOutputVariables( $aProcedureDetails['PARAMS_OUT'] );
                return true; // request successfull
        }
    }
    
    /**
     * Based on setting in config file sets the prefetch functionality of Oracle
     * oci8 driver will prefetch as many rows (for each cursor), as configured
     * Also working for sqlrelay.
     * 
     * Using a good value in this setting can significantly speed things up.
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param integer $iNum 
     */
    private function _setPrefetch( $iNum = 1 )
    {
        if( is_int($iNum) && $iNum > 0 ){
            $this->CI->db->setPrefetch( $iNum );
        }
        else
        {
            printf( $this->_oError->show_error( $this->_aErrors[ 0 ], $this->_aErrors[ 4 ] ), $this->getErrorCaller(3) );
            exit();
        }
    }

    /**
     * Creates object public variables filled with return from plsql call.
     * All output variables from config file will be created.
     * 
     * In the model where this class is extended, user can get result of call
     * just accessign the proper variable:
     * $this-><in config file declared variable name>
     * 
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param array $aParamsOut Array of output parameters configured in config 
     */
    private function _fillOutputVariables( $aParamsOut )
    {
        try{
            foreach( $aParamsOut as $sLabel => $sType )
            {
                $this->{$sLabel} = $this->CI->db->getOutputBinds( $sType, $sLabel );
            }
        }
        catch( Exception $e )
        {
            printf( $this->_oError->show_error( $this->_aErrors[ 0 ], $this->_aErrors[ 2 ] ), $this->getErrorCaller(1) );
        }
    }
    
    /**
     * Binds all parameters configured for called package and prepares
     * strign with substitution variables
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param array $aParamsOut of output parameters
     * @return string 
     */
    private function _bindOutputParams( $aParamsOut )
    {
        $sSql = '';
        foreach ( $aParamsOut as $sName => $sType )
        {
            $this->CI->db->addOutputBind( $sType, $sName );
            $sSql .= ' :' . $sName . ',';
        }
        return $sSql;
    }
    
    /**
     * Binds all input parameters configured for package and prepares string
     * wih substitution variables
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param array $aArguments of input parameters
     * @return string 
     */
    private function _bindInputParams( &$aArguments )
    {
        $sSql = '';
        foreach ( $aArguments as $sName => $mValue )
        {
            $this->CI->db->addInputBind( $sName, $mValue );
            $sSql .= ' :' . $sName . ',';
        }
        return $sSql;
    }
    
    /**
     * Checks the input parameter array for correct length and naming of params
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param array $aArguments Contains passed input parameters
     * @return boolean 
     */
    private function _checkInputParams( &$aProcedureDetails, &$aArguments )
    {
        foreach ( $aProcedureDetails[ 'PARAMS_IN' ] as $nParamPos => $sParamName )
        {
            if ( ! array_key_exists( $sParamName, $aArguments ) )
            {
                printf( $this->_oError->show_error( $this->_aErrors[ 0 ], $this->_aErrors[ 3 ]), APPPATH, $this->_sConfigFile, $this->getErrorCaller( 3 ) );
                exit;
            }
        }
        return false;
    }

    /**
     * Search the callstack for requested position and returns its content
     * Usefull for getting the error origin in user code
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param intger $nStackStep Position in the callstack wanted.
     * @return string
     */
    private function getErrorCaller ( $nStackStep = 1 )
    {
        $sFrom = debug_backtrace();
        return $sFrom[ $nStackStep ][ 'file' ] . ' Line: ' . $sFrom[ $nStackStep ][ 'line' ];
    }
    
        /**
     * Returns the last Oracle database error number
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @return integer
     */
    public function getErrorNumber()
    {
        return $this->_iLastErrorNumber;
    }
    
    /**
     * Returns the last Oracle database error message
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @return string Oracle error number
     */
    public function getErrorMessage()
    {
        return $this->_sLastErrorMessage;
    }

}