<?php

/**
 * Proof of concept.
 * 
 * @author Antonin Crha <a.crha@pixvalley.com>
 */
class mpokus extends MY_Model
{

    public function __construct()
    {
        //load package configuration
        $sConfigFile = PACKAGE_CONFIG_PATH . 'mpokus' . EXT;
        parent::__construct( $sConfigFile );

    }

    /**
     * Example:
     * How easy is to use this Oracle package/procedures abstraction:
     * --------------------------------------------------------------
     * Step 0) Prepare configuration file (just once) :-)
     * Step 1) Prepare input parameters array
     * Step 2) Pass it to function "declared" in config file $sConfigFile
     * Step 3) Read output params from variables declared for current object
     * Step 4) [optinal] Check if cursor contains data with <cursor>->isEmpty
     * Step 5) [optinal] Fetch data from cursor just like you are used in codeigniter
     * Step 6) Not done yet? Doing another request to another procedure? 
     *         Please free the memory with <cursor>->free_result()
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @param integer $iNum Number of rows to fetch
     */
    public function getAllPokus( $iNum )
    {
        $aIn  = array( 'LABEL' => 'ahoj', 'CFILE' => 'aaa', 'PN$rownum' => $iNum );
        
        if( false === $this->insert( $aIn ) )
        {
            echo $this->getErrorMessage();
        }
        else
        {
            if( $this->PCUR_out->isEmpty() )
            {
                echo 'No data';
            }
            else
            {
                // lets fetch the cursor
                foreach ($this->PCUR_out->result() as $row)
                {
                    var_dump($row); echo '<br>';
                }
            }
        }
        // usefull for keepin the memory consumption on acceptable level
        $this->PCUR_out->free_result(); 
        $this->db->trans_commit();
    }

}
