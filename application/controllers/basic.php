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
        $this->mpokus->getAllPokus();  
    }

}