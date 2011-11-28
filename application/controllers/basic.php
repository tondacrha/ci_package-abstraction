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
        echo '<h4>Using class inharitance:</h4>';
        $iStart = microtime(true);
        $this->mpokus->getAllPokus(3);
        $iEnd = microtime(true);
        echo "<br>Time: ";echo $iEnd - $iStart;
        echo '<br>Memory usage in bytes: ';echo memory_get_usage();
        unset($this->mpokus);
        gc_collect_cycles();
        
        
        $this->load->model('msecond');
        echo '<br><br>';
        echo '<h4>Using library:</h4>';
        $iStart = microtime(true);
        $this->msecond->getAllPokus(3);
        $iEnd = microtime(true);
        unset($this->msecond);
        echo "<br>Time: ";echo $iEnd - $iStart; 
        echo '<br>Memory usage in bytes: ';echo memory_get_usage();
        echo "<br>Better time because db request is already loaded on server. It does not mean, that second solution is faster.";
        
        $this->load->model('mthird');
        echo '<br><br>';
        echo '<h4>Using library without extending CI_Model:</h4>';
        $iStart = microtime(true);
        $this->mthird->getAllPokus(3);
        $iEnd = microtime(true);
        echo "<br>Time: ";echo $iEnd - $iStart; 
        echo '<br>Memory usage in bytes: ';echo memory_get_usage();
        echo "<br>Better time because db request is already loaded on server. It does not mean, that second solution is faster.";
        echo '<br>Memory consumption groves in the time. It does not mean that some solution is worse.';
        

        
    }

}