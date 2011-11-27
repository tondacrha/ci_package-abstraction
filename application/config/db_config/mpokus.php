<?php
/**
 * There is not so much configuration needed.
 * Just always put first all input params and then all output params.
 */

$sPackage = SCHEMA . 'PKG_POKUS_1001';

$config[PKG_CONF_PREFIX]['insert'] = array(
    'PACKAGE' => $sPackage,
    'PROCEDURE' => 'INSERT_POKUS',
    
    'PARAMS_IN' => array(
        'LABEL',
        'CFILE',
    ),
    'PARAMS_OUT' => array(
        'PCUR_out' => 'CURSOR',
        'PN_number' => 'CURSOR',
        'PV_varchar' => 'VARCHAR',
    ),
);

$config[PKG_CONF_PREFIX]['getSingle'] = array(
    'PACKAGE' => $sPackage,
    'PROCEDURE' => 'GET_POKUS',

    'PARAMS_IN' => array(
        'POKUS_ID',
    ),
    'PARAMS_OUT' => array(
        'PCUR$out' => 'CURSOR',
    ),
);
