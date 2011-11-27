<?php

/**
 * Main purpouse of this class is to make codeigniter version 2.x users more
 * happy using plsql procedures stored in Oracle database.
 * 
 * Codeigniter currently do not support bindig of variables and calling 
 * stored procedures directly so users need to write they own helpers, ...
 * 
 * This class needs oci10 or sqlrelay database driver to work.
 * 
 * This class stores configuration in variables to be able to fully profit from
 * the inharitance concept.
 * 
 * OUTPUT PARAMS are mapped to php variable names - so be carfull with naming
 * If params for output or input bind are not valid variable name,
 * an ORA-01036 error is raised. For future use: 
 * regexp for variable name validation [a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]* 
 * 
 * For now usable only for procedures!!!
 * I will add posibility to call functions, in the future.
 * 
 * @author Antonin Crha <a.crha@pixvalley.com>
 */
class MY_Model extends CI_Model
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

    public function __construct ( $sConfigFile = null )
    {
        parent::__construct();
        $this->load->database();
        $this->_oError = & load_class( 'Exceptions', 'core' );
        $this->load->config( PACKAGE_CONFIG_PATH . 'errors' . EXT );
        $this->_aErrors = $this->config->item( ABST_ERROR_PREFIX );

        // codeigniter will instantiate this class anyway. So the hack with
        // optional parameter is needed to prevent codeigniter from mess things
        if ( $sConfigFile !== null )
        {
            $this->_loadConfiguration( $sConfigFile );
        }
    }
    
    public function __destruct ()
    {
        //@TODO
        // close result set, connection, etc
    }


    /**
     * Loads all necessery configuration files for the abstraction to work
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param string $sConfigFile Path to configuration file
     */
    private function _loadConfiguration ( $sConfigFile )
    {
        $this->load->config( $sConfigFile );

        $this->_sConfigFile = $sConfigFile;
        $this->_aConfiguration = $this->config->item( PKG_CONF_PREFIX );
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
        $this->db->clearAllBinds(); // Performance tested. No issue here
        $aProcedureDetails = $this->_aConfiguration[ $sProcedure ];
        
        if( isset( $aProcedureDetails['PREFETCH'] ) && $aProcedureDetails['PREFETCH'] != '' )
        {
            $this->_setPrefetch( $aProcedureDetails['PREFETCH'] );
        }
        
        $this->_checkInputParams( $aProcedureDetails, $aArguments[0] );        
        $sSqlBinds  = '';
        $sSqlBinds .= $this->_bindInputParams( $aArguments[0] );
        $sSqlBinds .= trim( $this->_bindOutputParams( $aProcedureDetails['PARAMS_OUT'] ), ',' );
        
        $sPackage = $aProcedureDetails['PACKAGE'].'.'.$aProcedureDetails['PROCEDURE'];
        $sQuery = 'BEGIN ' . $sPackage . '(' . $sSqlBinds . '); END;';
        
        // doing the actual query into database
        if( false === $this->db->query( $sQuery ) )
        {
            $this->_sLastErrorMessage = $this->db->getErrorMessage();
            $this->_iLastErrorNumber = $this->db->getErrorNumber();
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
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param integer $iNum 
     */
    private function _setPrefetch( $iNum )
    {
        $this->db->setPrefetch( $iNum );
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
                $this->{$sLabel} = $this->db->getOutputBinds( $sType, $sLabel );
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
            $this->db->addOutputBind( $sType, $sName );
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
            $this->db->addInputBind( $sName, $mValue );
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