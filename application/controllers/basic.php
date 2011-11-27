<?php

/**
 * Proof of concept
 * 
 * @author Antonin Crha <a.crha@pixvalley.com>
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
        $this->mpokus->getAllPokus(10);
        // doing two different calls
        // in between the binds are unset and bind again.
        // performance tests shows no significant difference when keeping binds
        echo '<br><br>';
        $this->mpokus->getAllPokus(5);
        $iEnd = microtime(true);
        echo "<br>";echo $iEnd - $iStart;
        echo '<br>';echo memory_get_peak_usage(true);
        
    }

}