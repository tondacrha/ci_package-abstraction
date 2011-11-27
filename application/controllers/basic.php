<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class basic extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->model('mpokus');
        
        $iStart = microtime(true);
        $this->mpokus->getAllPokus(1);
        
        echo '<br><br>';
        $this->mpokus->getAllPokus(0);
        $iEnd = microtime(true);
        echo "<br>";echo $iEnd - $iStart;
        echo '<br>';echo memory_get_peak_usage(true);
        
    }

}