<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$sPackage = SCHEMA . 'PKG_PRD_1001';

$config[PKG_CONF_PREFIX]['prdInsert'] = array(
    'PACKAGE' => $sPackage,
    'PROCEDURE' => 'INSERT_PRD',

    'PARAMS_IN' => array(
        'LABEL',
        'CFILE',
    ),
    'PARAMS_OUT' => array(
        'PCUR$out' => 'CURSOR',
    ),
);

$config[PKG_CONF_PREFIX]['prdGetSingle'] = array(
    'PACKAGE' => $sPackage,
    'PROCEDURE' => 'GET_PRD',

    'PARAMS_IN' => array(
        'POKUS_ID',
    ),
    'PARAMS_OUT' => array(
        'PCUR$out' => 'CURSOR',
    ),
);
