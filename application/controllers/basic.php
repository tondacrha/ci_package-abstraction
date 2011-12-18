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
        
        $this->load->model('msecond');
        echo '<h4>Using library:</h4>';
        $iStart = microtime(true);
        $this->msecond->getAllPokus(10);
        $iEnd = microtime(true);
        unset($this->msecond);
        echo "<br>Time: ";echo $iEnd - $iStart; 
        echo '<br>Memory usage in bytes: ';echo memory_get_usage();
        
    }

	public function index2()
	{
		$this->load->model( 'backoffice/bocr/mpokus' );

        echo '<h4>Using ORM:</h4>';
        $iStart = microtime(true);
        $this->mpokus->getPokus();
        $iEnd = microtime(true);
        unset($this->mpokus);
        echo "<br>Time: ";echo $iEnd - $iStart; 
        echo '<br>Memory usage in bytes: ';echo memory_get_usage();
	}

}
