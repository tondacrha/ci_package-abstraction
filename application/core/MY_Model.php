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
 * @author Antonin Crha <a.crha@pixvalley.com>
 */
class MY_Model extends CI_Model
{
    private   $sConfigFile = '';
    protected $aConfiguration = array ( );
    protected $aFunctions = array ( );
    protected $aErrors = array ( );
    private   $oError;

    public function __construct ( $sConfigFile = FALSE )
    {
        parent::__construct();
        $this->load->database();
        $this->oError = & load_class( 'Exceptions', 'core' );

        if ( $sConfigFile )
        {
            $this->_loadConfiguration( $sConfigFile );
        }
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

        if ( !in_array( $sName, $this->aFunctions ) )
        {
            printf( $this->oError->show_error( $this->aErrors[ 0 ], $this->aErrors[ 1 ] ), $sName, APPPATH, $this->sConfigFile, $this->getErrorCaller() );
            exit;
        }

        if ( count( $aArguments ) > 1 )
        {
            printf( $this->oError->show_error( $this->aErrors[ 0 ], $this->aErrors[ 2 ] ), $this->getErrorCaller() );
            exit;
        }

        $this->_callProcedure( $sName, $aArguments );
    }

    /**
     * This main private function is doing all the 
     * configuration and calls needed.
     * 
     * Does not need to know the package name because it 
     * is obvios from the object context.
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param type $sProcedure
     * @param type $aArguments 
     */
    private function _callProcedure ( $sProcedure, $aArguments )
    {
        $this->db->clearAllBinds();
        $aProcedureDetails = $this->aConfiguration[ $sProcedure ];
        $aArguments = $aArguments[0]; // Always need just first index.   
        $this->_checkInputParams( $aProcedureDetails, $aArguments );
        
        $sSqlBinds = '';
        $sSqlBinds .= $this->_bindInputParams( $aArguments );
        $sSqlBinds .= trim( $this->_bindOutputParams( $aProcedureDetails['PARAMS_OUT'] ), ',' );
        
        $sPackage = $aProcedureDetails['PACKAGE'].'.'.$aProcedureDetails['PROCEDURE'];
        $this->db->query( 'BEGIN ' . $sPackage . '(' . $sSqlBinds . '); END;' );
        
        //@TODO
        //OUTPUT params handling
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
                printf( $this->oError->show_error( $this->aErrors[ 0 ], $this->aErrors[ 3 ]), APPPATH, $this->sConfigFile, $this->getErrorCaller( 3 ) );
                exit;
            }
        }
        return false;
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
        $this->load->config( PACKAGE_CONFIG_PATH . 'errors' . EXT );

        $this->sConfigFile = $sConfigFile;
        $this->aConfiguration = $this->config->item( PKG_CONF_PREFIX );
        $this->aFunctions = array_keys( $this->aConfiguration );
        $this->aErrors = $this->config->item( ABST_ERROR_PREFIX );
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

}