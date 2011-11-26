<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Code Igniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package	 CodeIgniter
 * @author	  Rick Ellis
 * @copyright   Copyright (c) 2006, pMachine, Inc.
 * @license	 http://www.codeignitor.com/user_guide/license.html
 * @link		http://www.codeigniter.com
 * @since	   Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * oci8 Result Class
 *
 * This class extends the parent result class: CI_DB_result
 *
 * @category	Database
 * @author	    Sylvain Gourvil
 */
class CI_DB_sqlrelay_result extends CI_DB_result {

    /**
     * Statement ID
     *
     * @var integer
     */
    var $stmt_id;

    /**
     * Cursor Id
     *
     * @var integer
     */
    var $curs_id;

    /**
     * Is Limit Use
     *
     * @var bool
     */
    var $limit_used;

    /**
     * All Results by row
     *
     * @var array
     */
    var $result_row;

    /**
     * All Results by array
     *
     * @var array
     */
    var $result_array;

    /**
     * All Results by object
     *
     * @var array
     */
    var $result_object;

    /**
     * Is debug on ?
     *
     * @var bool
     */
	var $db_debug;

	/**
	 * Constructor
	 *
	 * @param  bool $bDebug
	 * @return CI_DB_sqlrelay_result
	 */
	function CI_DB_sqlrelay_result($bDebug = false) {

	    $this->result_array    = array();
	    $this->result_row      = array();
	    $this->result_object   = array();
	    $this->db_debug        = $bDebug;
	}

	function setCursorId(&$rCursor) {

	    $this->curs_id = $rCursor;
	}


	/**
	 * Number of rows in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	function num_rows() {

		return sqlrcur_rowCount($this->curs_id);
	}

	function num_total_rows() {

        return sqlrcur_totalRows($this->curs_id);
    }


	/**
	 * Number of fields in the result set
	 *
	 * @access	public
	 * @return	integer
	 */
	function num_fields() {

		$count = sqlrcur_colCount($this->curs_id);
		if ( $this->limit_used ) {

		    $count = $count - 1 ;
		}
		return $count ;
	}


	/**
	 * Field data
	 *
	 * Generates an array of objects containing field meta-data
	 *
	 * @access	public
	 * @return	array
	 */
	function field_data() {

		$retval = array();
		for ($c = 1; $c <= $this->num_fields(); $c++) {

			$F				  = new stdClass();
			$F->name 		  = $this->getColumnLength();
			$F->type 		  = $this->getColumnType($c);
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

	/**
	 * Free the result
	 *
	 * @return	null
	 */		
	function free_result()
	{
		if (is_resource($this->result_id))
		{
			sqlrcur_free($this->result_id);
			$this->result_id = FALSE;
		}
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

        $aReturn = sqlrcur_getRowAssoc($this->curs_id, $this->current_row);
        $this->current_row++;

        return $aReturn;
	}


	/**
	 * Returns the result set as an object
	 *
	 * @access	protected
	 * @return	object
	 */
	function _fetch_object() {

        if ( is_int($this->current_row) ) {

	        $row = array();
            $res = $this->_fetch_array();
            if ($res != false) {

    			$obj = new stdClass();
    			foreach ($res as $key => $value) {

    				$obj->{$key} = $value;
    			}
    			$res = $obj;
    		}
            return $res;
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_sqlrelay_result '."\t".'Method _fetch_object '."\n\t\t".'Wrong parameter '.$iRow.'.'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
                return $this->display_error('db_wrong_parameter', $iRow);
            }
            return false;
	    }
	}


	/**
	 * Returns the result set as an index key array
	 *
	 * @access public
	 * @param  integer $iRow
	 * @return array
	 */
	function _fetch_row() {

	    if ( is_int($this->current_row) ) {

	        $aReturn = sqlrcur_getRow($this->curs_id, $this->current_row);
            $this->current_row++;
            return $aReturn;
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_sqlrelay_result '."\t".'Method get_row '."\n\t\t".'Wrong parameter '.$this->current_row.'.'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
                return $this->display_error('db_wrong_parameter', $this->current_row);
            }
            return false;
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

                log_message('error', 'Class CI_DB_sqlrelay_result '."\t".'Method result '."\n\t\t".'Wrong parameter '.$sType.'.'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
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

        $this->current_row = 0;
        $iNbRowTemp        = 0;
        $iNbFirstResult    = $this->num_rows();
        while ( $this->current_row < $iNbFirstResult && $iNbRowTemp <= $iNbRow ) {
            array_push($this->$sArray, $this->$sFunction());
            if ( $iNbRow > 0 ) {
                $iNbRowTemp++;
            }
        }
        while ( ! sqlrcur_endOfResultSet($this->curs_id) && $iNbRowTemp <= $iNbRow ) {
            array_push($this->$sArray, $this->$sFunction());
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

	    return sqlrcur_getField($this->curs_id, $iRow, $iCol);
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
	    $this->current_row = $iRow;
	    return $this->$sFunction();
	}


	/**
	 * Returns the array of the column names of the current return set
	 *
	 * @access public
	 * @return array
	 */
	function showColumns() {

	    return sqlrcur_getColumnNames($this->curs_id);
	}


	/**
	 * Returns the name of the specified column
	 *
	 * @access public
	 * @param  integer $iColumn
	 * @return string
	 */
	function showColumn($iColumn = 0) {

	    return sqlrcur_getColumnName($this->curs_id, $iColumn);
	}

	/**
	 * Returns the type of the specified column
	 *
	 * @access public
	 * @param  integer $Column
	 * @return string
	 */
	function getColumnType($Column) {

	    if ( is_int($Column) OR is_string($Column) ) {

	       return sqlrcur_getColumnType($this->curs_id, $Column);
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_sqlrelay_result '."\t".'Method getColumnType '."\n\t\t".'Wrong parameter '.$Column.'.'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
                return $this->display_error('db_wrong_parameter', $Column);
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
	function getColumnLength($Column) {

	    if ( is_int($Column) OR is_string($Column) ) {

	       return sqlrcur_getColumnLength($this->curs_id, $Column);
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_sqlrelay_result '."\t".'Method getColumnType '."\n\t\t".'Wrong parameter '.$Column.'.'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
                return $this->display_error('db_wrong_parameter', $Column);
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
	function getColumnPrecision($Column) {

	    if ( is_int($Column) OR is_string($Column) ) {

	       return sqlrcur_getColumnPrecision($this->curs_id, $Column);
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_sqlrelay_result '."\t".'Method getColumnType '."\n\t\t".'Wrong parameter '.$Column.'.'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
                return $this->display_error('db_wrong_parameter', $Column);
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
	function getColumnScale($Column) {

	    if ( is_int($Column) OR is_string($Column) ) {

	       return sqlrcur_getColumnScale($this->curs_id, $Column);
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_sqlrelay_result '."\t".'Method getColumnType '."\n\t\t".'Wrong parameter '.$Column.'.'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
                return $this->display_error('db_wrong_parameter', $Column);
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
	function getColumnLongest($Column) {

	    if ( is_int($Column) OR is_string($Column) ) {

	       return sqlrcur_getLongest($this->curs_id, $Column);
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Class CI_DB_sqlrelay_result '."\t".'Method getColumnType '."\n\t\t".'Wrong parameter '.$Column.'.'
                    . PHP_EOL . " Connexion data "
    				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
        		);
                return $this->display_error('db_wrong_parameter', $Column);
            }
            return false;
	    }
	}

	/**
	 * Return the result set id
	 *
	 * @access public
	 * @return integer
	 */
	function getResultId() {

	    return sqlrcur_getResultSetId($this->curs_id);
	}

	/**
	 * Suspend the result set open for another connection
	 *
	 */
	function suspendResult() {

	    sqlrcur_suspendSession($this->curs_id);
	}

	/**
	 * Resume a previous record set suspended
	 *
	 * @access public
	 * @param  integer $curRef
	 * @return bool
	 */
	function resumeResult($curRef) {

	    if ( sqlrcur_resumeResultSet($curRef) ) {

	        return true;
	    }else{

            if ($this->db_debug) {

                log_message('error', 'Driver Db_sqlrelay '."\t".'Method resumeResult '."\n\t\t".'Unable to resume result.'
                    . PHP_EOL . " Connexion data "
       				. PHP_EOL . " username : " . $this->username   
    				. PHP_EOL . " hostname : " . $this->hostname
    				. PHP_EOL . " database : " . $this->database
    				. PHP_EOL . " dbdriver : " . $this->dbdriver
    				. PHP_EOL . " dbprefix : " . $this->dbprefix
    				. PHP_EOL . " port     : " . $this->port
                );
                return $this->display_error('db_unable_to_resume_result', var_export($curRef, true));
            }
            return false;
	    }
	    return ;
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

		$heading = 'SQL Relau Error';

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
