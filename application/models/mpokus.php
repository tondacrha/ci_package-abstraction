<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class mpokus extends MY_Model
{

    public function __construct()
    {
        //load package configuration
        $sConfigFile = PACKAGE_CONFIG_PATH . 'mpokus' . EXT;
        parent::__construct( $sConfigFile );

    }

    public function getAllPokus()
    {
        $aIn  = array( 'LABEL' => 'ahoj', 'CFILE' => 'aaa' );
        if( false === $this->insert( $aIn ) )
        {
            echo $this->getErrorMessage();
        }
        else
        {

            foreach ($this->PCUR_out->result() as $row)
            {
                var_dump($row); echo '<br>';
            }
        }
        $this->db->trans_commit();
    }

}
