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
        $this->load->model('mprd');
        $this->mpokus->getAllPokus();
        echo "ahoj";
/*
        $this->db->addOutputBind('INTEGER', 'id');
        $this->db->query('BEGIN PKG_POKUS_1001.GET_COUNTRY_NUMBER(:id); END;');
        $mOutput = $this->db->getOutputBinds('INTEGER', 'id' );
        var_dump($mOutput); echo "<br>";

        $this->db->clearAllBinds();
        $this->db->addOutputBind('CURSOR', 'id');
        $this->db->query('BEGIN PKG_POKUS_1001.GET_COUNTRY_CURSOR(:id); END;');
        $mOutput = $this->db->getOutputBinds('CURSOR', 'id' );
        var_dump($mOutput->row()); echo "<br>";

        $this->db->clearAllBinds();
        $this->db->addOutputBind('VARCHAR2', 'id');
        $this->db->query('BEGIN PKG_POKUS_1001.GET_COUNTRY_VARCHAR(:id); END;');
        $mOutput = $this->db->getOutputBinds('VARCHAR2', 'id' );
        var_dump($mOutput); echo "<br><br>";
*/
/*
        $this->db->clearAllBinds();
        $ahoj = 'ahojaaa';
        $cFile = 'sufe  hsudfksdhfoisdhfoishf;sidgfiurew fea;ourf;iur galduf geaufg a';
        $this->db->addInputBind('labe', $ahoj);
        $this->db->addInputBind('cfile', $cFile);
        $this->db->query('BEGIN PKG_POKUS_1001.INSERT_POKUS(:labe, :cfile); END;');

        echo"<br><br>";
        //var_dump($this->db->getErrorMessage()); echo "<br>";
        //var_dump($this->db->getErrorNumber());
        $this->db->commit();
        
        //$mOutput = $this->db->getOutputBinds('VARCHAR2', 'id' );
        //var_dump($mOutput);
*/
        
    }

}