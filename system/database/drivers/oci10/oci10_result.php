<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package	 CodeIgniter
 * @author	  Rick Ellis
 * @copyright   Copyright (c) 2006, EllisLab, Inc.
 * @license	 http://www.codeignitor.com/user_guide/license.html
 * @link		http://www.codeigniter.com
 * @since	   Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * oci10 Result Class
 *
 * This class extends the parent result class: CI_DB_result
 *
 * @category	Database
 * @author	  Rick Ellis
 * @link		http://www.codeigniter.com/user_guide/database/
 */
class CI_DB_oci10_result extends CI_DB_result {

	var $stmt_id;
	var $curs_id;
	var $limit_used;
	var $_bGetColumnName = true ;
	var $current_row = 0 ;
	var $num_rows = false ;
	
	var $result_row = array() ;
	var $result_array = array() ;
	var $result_object = array() ;
	var $db_debug = false ;
	
	/**
	 * Constructor
	 *
	 * @param  bool $bDebug
	 * @return CI_DB_oci10_result
	 */
	function CI_DB_oci10_result($bDebug = false) {

	    $this->result_array    = array();
	    $this->result_row      = array();
	    $this->result_object   = array();
	    $this->db_debug        = $bDebug;
	}

	/**
	 * @desc   Define cursor
	 * 
	 * @author  Eric TINOCO <e.tinoco@fotovista.com>
	 * @date    2009/04/25
	 *
	 * @param resource $rCursor
	 */
	function setCursorId(&$rCursor) {

	    $this->curs_id = $rCursor;
	}

	/**
	 * Number of rows in the result set.
	 *
	 * Oracle doesn't have a graceful way to retun the number of rows
	 * so we have to use what amounts to a hack.
	 * 
	 *
	 * @access  public
	 * @return  integer
	 */
	function num_rows()
	{
		if( false !== is_null($this->num_rows) ) {
			return $this->num_rows ;
		} else {
			if( false === ($aRes = $this->result_array()) ) {
				$this->num_rows = 0 ;
			} else {
				$this->num_rows = count( $aRes ) ;				
			}
			return $this->num_rows ;			
		}
        
	}

	function num_total_rows( ){
		return 0 ;
	}
     
    /**
     * Answers the question is cursor empty?
     * 
     * Performance note: it does not increase the amount of consumed memory.
     * The result set needs to be loaded into memory anyway.
     * 
     * @author Antonin Crha <a.crha@pixvalley.com>
     * @return integer
     */
    function isEmpty()
    {
        if( false === self::result() )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
	
	// --------------------------------------------------------------------

	/**
	 * Number of fields in the result set
	 *
	 * @access  public
	 * @return  integer
	 */
	function num_fields()
	{
		$count = @ocinumcols($this->stmt_id);

		// if we used a limit we subtract it
		if ($this->limit_used)
		{
			$count = $count - 1;
		}

		return $count;
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch Field Names
	 *
	 * Generates an array of column names
	 *
	 * @access	public
	 * @return	array
	 */
	function list_fields()
	{
		$field_names = array();
		$fieldCount = $this->num_fields();
		for ($c = 1; $c <= $fieldCount; $c++)
		{
			$field_names[] = ocicolumnname($this->stmt_id, $c);
		}
		return $field_names;
	}

	// Deprecated
	function field_names()
	{
		return $this->list_fields();
	}

	// --------------------------------------------------------------------

	/**
	 * Field data
	 *
	 * Generates an array of objects containing field meta-data
	 *
	 * @access  public
	 * @return  array
	 */
	function field_data()
	{
		$retval = array();
		$fieldCount = $this->num_fields();
		for ($c = 1; $c <= $fieldCount; $c++)
		{
			$F			  = new stdClass();
			$F->name		= ocicolumnname($this->stmt_id, $c);
			$F->type		= ocicolumntype($this->stmt_id, $c);
			$F->max_length  = ocicolumnsize($this->stmt_id, $c);
			$F->length	      = $this->getColumnLength($c);
			$F->longest	      = $this->getColumnLongest($c);
			$F->primary_key   = $this->isColumnPrimaryKey($c);
			$F->unique        = $this->isColumnUnique($c);
			$F->part_of_key   = $this->isColumnPartOfKey($c);
			$F->autoIncrement = $this->isColumnAutoIncrement($c);

			$retval[] = $F;
		}

		return $retval;
	}

	// --------------------------------------------------------------------

	/**
	 * Free the result
	 *
	 * @return	null
	 */		
	function free_result()
	{
		if (is_resource($this->result_id))
		{
			ocifreestatement($this->result_id);			
			$this->result_id = FALSE;
		}
		$this->current_row = 0 ;
	}

	/**
	 * Result - associative array
	 *
	 * Returns the result set as an array
	 *
	 * @access	protected
	 * @return	array
	 */
	function _fetch_array() {

		return $this->_fetch_row() ;
	}

	/**
	 * Result - object
	 *
	 * Returns the result set as an object
	 *
	 * @access  private
	 * @return  object
	 */
	function _fetch_object()
	{	
		$result = array();

		// If PHP 5 is being used we can fetch an result object
		if (function_exists('oci_fetch_object'))
		{
			$id = ($this->curs_id) ? $this->curs_id : $this->stmt_id;
			
			return @oci_fetch_object($id);
		}
		
		// If PHP 4 is being used we have to build our own result
		foreach ($this->result_array() as $key => $val)
		{
			$obj = new stdClass();
			if (is_array($val))
			{
				foreach ($val as $k => $v)
				{
					$obj->$k = $v;
				}
			}
			else
			{
				$obj->$key = $val;
			}
			
			$result[] = $obj;
		}

		return $result;
	}

	/**
	 * Returns the result set as an index key array
	 *
	 * @access public
	 * @param  integer $iRow
	 * @return array
	 */
	function _fetch_row( $iRow = 0 ) {

		if ( true === $this->_bGetColumnName ) {

			$sFunction = 'oci_fetch_array' ;
			$iMode     = OCI_ASSOC + OCI_RETURN_NULLS + OCI_RETURN_LOBS ;
		} else {

			$sFunction = 'oci_fetch_array' ; 
			$sMode     = OCI_NUM + OCI_RETURN_NULLS + OCI_RETURN_LOBS ;
		}
		
		$id = ($this->curs_id) ? $this->curs_id : $this->stmt_id;
		
		if( $iRow != 0 ){
			
			if( isset( $this->result_row ) && $this->result_row !== array() ) {

				if( isset($this->result_row[$iRow]) ) {

					return $this->result_row[$iRow] ;
				} else if( $iRow <= $this->current_row ) {

					return false ;
				} else {

					$iRowTmp = $this->current_row ;
				}
			} else {

				$iRowTmp = 0 ;
			}
			
			while( $iRowTmp < $iRow && $sFunction($id, $iMode ) ) {

				$this->current_row++;
				$iRowTmp++ ;
			}
			//fetch row or get result > nb results
			if ( $iRowTmp == $iRow-1 ) {

				$this->current_row++;
				return $sFunction($id, $iMode ) ;
			} else {

				return false ;
			}
		} else {

			$this->current_row++ ;
			return $sFunction($id, $iMode ) ;	
		}
	}
	
	/**
     * Query result
     *
     * @access  public
     * @return  array
     */
	function result($sType = 'row', $iNbRow = 0) {

	    if ( $sType != 'row' && $sType != 'array' && $sType != 'object' ) {

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_oci10_result '."\t".'Method '.__METHOD__.' '.PHP_EOL."\t\t".'Wrong parameter '.$sType.'.'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port);
                return $this->display_error('db_wrong_parameter', $sType);
            }
            return false;
	    }
	    $sFunction = "_fetch_$sType";
	    $sArray    = "result_$sType";
	    $sReturn   = 'result_'.$sType;

        if (count($this->$sReturn) > 0) {

            return $this->$sReturn;
        }

        $iNbRowTemp        = 0;
        
        while ( ($aRow = $this->$sFunction()) && $iNbRowTemp <= $iNbRow ) {

            array_push( $this->$sArray, $aRow );
            if ( $iNbRow > 0 ) {

                $iNbRowTemp++;
            }
        }
        if (count($this->$sReturn) == 0 ) {

            return false;
        }
        return $this->$sReturn;
	}


    /**
     * Query result.  "array" version.
     *
     * @access  public
     * @return  array
     */
	function result_array() {
	    return $this->result('array');
	}


    /**
     * Query result.  "row" version.
     *
     * @access  public
     * @return  array
     */
	function result_row() {

	    return $this->result();
	}


    /**
     * Query result.  "object" version.
     *
     * @access  public
     * @return  aray
     */
	function result_object() {

	    return $this->result('object');
	}


	/**
	 * Returns a string with value of the specified row and column
	 *
	 * @access public
	 * @param  integer $iRow
	 * @param  integer $iCol
	 * @return string
	 */
	function getSpecificField($iRow, $iCol) {

		if( function_exists('oci_field_name') ) {
	    	return oci_field_name( $this->stmt_id, $iCol);
		} else {
			return '' ;
		}
	}


	/**
	 * Returns an array with the datas of the row given in argument
	 *
	 * @access public
	 * @param  integer $iRow
	 * @param  integer $iCol
	 * @return string
	 */
	function getSpecificRow($iRow, $sType = 'row') {

	    $sFunction         = "_fetch_$sType";
	    return $this->$sFunction($iRow);
	    
	}


	/**
	 * Returns the array of the column names of the current return set
	 *
	 * @access public
	 * @return array
	 */
	function showColumns() {

		$aCol = array() ;
		
		$id = ($this->curs_id) ? $this->curs_id : $this->stmt_id ;
		$iNbFieldsMax = oci_num_fields($id) ;
		$iCol = 1 ;
		while( ($aCol[] = oci_field_name($id, $iCol )) 
		&&     $iCol < $iNbFieldsMax ){
			 $iCol++ ;
		}
		
	    return $aCol ;
	}


	/**
	 * Returns the name of the specified column
	 *
	 * @access public
	 * @param  integer $iColumn
	 * @return string
	 */
	function showColumn($iColumn = 0) {

		$id = ($this->curs_id) ? $this->curs_id : $this->stmt_id ;
		
		if( $iColumn <  oci_num_fields($id) ) {
			return oci_field_name($id, $iColumn+1 ) ;
		} else {
			return false ;
		}
	}

	/**
	 * Returns the type of the specified column
	 *
	 * @access public
	 * @param  integer $Column
	 * @return string
	 */
	function getColumnType($iColumn) {

		
	    if ( (is_int($iColumn) OR is_string($iColumn))
	    &&   $iColumn <  oci_num_fields($id) ) {
			$id = ($this->curs_id) ? $this->curs_id : $this->stmt_id ;
	    	if ( is_int($iColumn) ) {
				$iColumn++ ;
				return oci_field_type($id , $iColumn);
	    	} else {
	    		return false ;
	    	}
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_oci10_result '."\t".'Method '.__METHOD__.' '.PHP_EOL."\t\t".'Wrong parameter '.$iColumn.'. Nb Max columns : ' . oci_num_fields($id) 
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port);
                return $this->display_error('db_wrong_parameter', $iColumn);
            }
            return false;
	    }
	}


	/**
	 * Returns the length of the specified column
	 *
	 * @access public
	 * @param  integer $Column
	 * @return integer
	 */
	function getColumnLength($iColumn) {

		if ( (is_int($iColumn) OR is_string($iColumn)) 
		&&   $iColumn <  oci_num_fields($id) ) {
			$id = ($this->curs_id) ? $this->curs_id : $this->stmt_id ;
	    	if ( is_int($iColumn) ) {
				$iColumn++ ;
				return oci_field_size($id , $iColumn);
	    	} else {
	    		return false ; 
	    	}
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_oci10_result '."\t".'Method '.__METHOD__.' '.PHP_EOL."\t\t".'Wrong parameter '.$iColumn.'. Nb Max COlumns : ' . oci_num_fields($id) 
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
                return $this->display_error('db_wrong_parameter', $iColumn);
            }
            return false;
	    }
	}


	/**
	 * Returns the precision of the specified column
	 *
	 * @access public
	 * @param  integer $Column
	 * @return integer
	 */
	function getColumnPrecision($iColumn) {

		if ( is_int($iColumn) OR is_string($iColumn) ) {
			$id = ($this->curs_id) ? $this->curs_id : $this->stmt_id ;
	    	if ( is_int($iColumn) &&  oci_num_fields($id) ) {
				$iColumn++ ;
				return oci_field_precision($id , $iColumn);
	    	} else {
	    		return false ;
	    	}
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_oci10_result '."\t".'Method '.__METHOD__.' '.PHP_EOL."\t\t".'Wrong parameter '.$iColumn.'. Nb Max Columns : ' . oci_num_fields($id) 
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port);
                return $this->display_error('db_wrong_parameter', $iColumn);
            }
            return false;
	    }
	}


	/**
	 * Returns the scale of the specified column
	 *
	 * @access public
	 * @param  integer $Column
	 * @return integer
	 */
	function getColumnScale($iColumn) {

		if ( (is_int($iColumn) OR is_string($iColumn))
		&&   $iColumn <  oci_num_fields($id) ) {
			$id = ($this->curs_id) ? $this->curs_id : $this->stmt_id ;
	    	if ( is_int($iColumn) ) {
				$iColumn++ ;
				return oci_field_scale($id , $iColumn);
	    	}
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_oci10_result '."\t".'Method '.__METHOD__.' '.PHP_EOL."\t\t".'Wrong parameter '.$iColumn.'. Nb Max Columns : ' . oci_num_fields($id) 
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port);
                return $this->display_error('db_wrong_parameter', $iColumn);
            }
            return false;
	    }
	}


	/**
	 * Returns the length of the longest field in the specified column
	 *
	 * @access public
	 * @param  integer $Column
	 * @return integer
	 */
	function getColumnLongest($iColumn) {
        return false;
	}

	/**
	 * Return the result set id
	 *
	 * @access public
	 * @return integer
	 */
	function getResultId() {
		
		return $this->current_row ; 
	}

	/**
	 * Suspend the result set open for another connection
	 *
	 */
	function suspendResult() {
	}

	/**
	 * Resume a previous record set suspended
	 *
	 * @access public
	 * @param  integer $curRef
	 * @return bool
	 */
	function resumeResult($curRef) {
	    return true ;
	}


	/**
	 * Display an error message
	 *
	 * @access	public
	 * @param	string	the error message
	 * @param	string	any "swap" values
	 * @param	boolean	whether to localize the message
	 * @return	string	sends the application/errror_db.php template
	 */
    function display_error($error = '', $swap = '', $native = false)  {

		$LANG = new CI_Language();
		$LANG->load('db');

		$heading = 'oci10 Error';

		if ($native == true) {

			$message = $error;
		}else {

			$message = ( ! is_array($error)) ? array(str_replace('%s', $swap, $LANG->line($error))) : $error;
		}

		if ( ! class_exists('CI_Exceptions')) {

			include_once(BASEPATH.'libraries/Exceptions.php');
		}

		$error = new CI_Exceptions();
		echo $error->show_error('An Error Was Encountered', $message, 'error_db');
		exit;

    }
	
}

?>
