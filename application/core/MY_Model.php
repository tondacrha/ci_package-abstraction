<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class MY_Model extends CI_Model
{

    private $sConfigFile = '';
    protected $aConfiguration = array ( );
    protected $aFunctions = array ( );
    protected $aErrors = array ( );
    private $oError;

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
     *
     * 
     * 
     * 
     * 
     * 
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

    private function _loadConfiguration ( $sConfigFile )
    {
        $this->load->config( $sConfigFile );
        $this->load->config( PACKAGE_CONFIG_PATH . 'errors' . EXT );

        $this->sConfigFile = $sConfigFile;
        $this->aConfiguration = $this->config->item( PKG_CONF_PREFIX );
        $this->aFunctions = array_keys( $this->aConfiguration );
        $this->aErrors = $this->config->item( ABST_ERROR_PREFIX );
    }

    private function getErrorCaller ( $nStackStep = 1 )
    {
        $sFrom = debug_backtrace();
        return $sFrom[ $nStackStep ][ 'file' ] . ' Line: ' . $sFrom[ $nStackStep ][ 'line' ];
    }

}