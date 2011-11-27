<?php
/**
 * There is not so much configuration needed.
 * Just always put first all input params and then all output params.
 */

$sPackage = SCHEMA . 'PKG_POKUS_1001';

$config[PKG_CONF_PREFIX]['insert'] = array(
    'PACKAGE' => $sPackage,
    'PROCEDURE' => 'INSERT_POKUS',
    'PREFETCH'  => 50,
    
    'PARAMS_IN' => array(
        'LABEL',
        'CFILE',
        'PN$rownum'
    ),
    'PARAMS_OUT' => array(
        'PCUR_out' => 'CURSOR',
        'PN_number' => 'NUMBER',
        'PV_varchar' => 'VARCHAR',
    ),
);

$config[PKG_CONF_PREFIX]['getSingle'] = array(
    'PACKAGE' => $sPackage,
    'PROCEDURE' => 'GET_POKUS',
    'PREFETCH'  => 50,

    'PARAMS_IN' => array(
        'POKUS_ID',
    ),
    'PARAMS_OUT' => array(
        'PCUR$out' => 'CURSOR',
    ),
);
