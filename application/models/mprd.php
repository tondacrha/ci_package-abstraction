<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class mprd extends MY_Model
{

    public function __construct()
    {
        //load package configuration
        $sConfigFile = PACKAGE_CONFIG_PATH . 'mprd' . EXT;
        parent::__construct( $sConfigFile );
    }

    public function getAllPokus()
    {
        $this->mpokus->insertPokus();
    }

}
