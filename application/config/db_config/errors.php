<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$config[ ABST_ERROR_PREFIX ][ 0 ] = 'Database abstraction layer fatal error';

$config[ ABST_ERROR_PREFIX ][ 1 ] = 'Call to undefined function: <strong>%s</strong>.
                            <br /> %s class auto-declaration only
                            works for array keys present in file:
                            %sconfig/%s<br />Error origin: %s';

$config[ ABST_ERROR_PREFIX ][ 2 ] = 'Auto-declared functions only accept one parameter.
                            (Array with plsql procedure input parameters.)<br />
                            Error origin: <br />%s';

$config[ ABST_ERROR_PREFIX ][ 3 ] = 'The parameter count passed from model does 
                            not corespond to configuration in "%sconfig/%s".<br />
                            Error origin: <br />%s';

$config[ ABST_ERROR_PREFIX ][ 4 ] = 'The parameter names passes from model does 
                            not corespond to configuration in "%sconfig/%s".<br />
                            Error origin: <br />%s';

$config[ ABST_ERROR_PREFIX ][ 5 ] = 'First parameter for %s library must be valid configuration file path.<br />
                            Error origin: <br />%s';

$config[ ABST_ERROR_PREFIX ][ 6 ] = 'Output parameters binding failed.<br />
                            Error origin: <br />%s';

$config[ ABST_ERROR_PREFIX ][ 7 ] = 'The [\'PREFETCH\'] field in package configuration
                            must be set to integer value > 0.<br />
                            Error origin: <br />%s';
