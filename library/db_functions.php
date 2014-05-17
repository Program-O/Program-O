<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.4.0
  * FILE: library/db_functions.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: MAY 17TH 2014
  * DETAILS: common library of db functions
  ***************************************/
  /**
  * function db_open()
  * Connect to the database
   * @link http://blog.program-o.com/?p=1340
  * @param  string $host -  db host
  * @param  string $user - db user
  * @param  string $password - db password
  * @param  string $database_name - db name
  * @return resource $dbConn - the database connection resource
  **/
  function db_open()
  {
    global $dbh, $dbu, $dbp, $dbn, $dbPort;
    $host = (!empty ($dbPort) and $dbPort != 3306) ? "$dbh:$dbPort" : $dbh;
    // add port selection if not the standard port number
    $con = @mysql_connect($host, $dbu, $dbp) or trigger_error('Couldn\'t connect to the DB. Error = ' . mysql_error());
    mysql_set_charset('utf8');
    db_query('SET NAMES UTF8', $con);
    $x = mysql_select_db($dbn) or trigger_error('Couldn\'t select the DB. Error = ' . mysql_error());
    return $con;
  }

  /**
  * function db_close()
  * Close the connection to the database
  * @link http://blog.program-o.com/?p=1343
  * @param resource $dbConn - the open connection
  **/
  function db_close($dbConn)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'This DB is now closed. You don\'t have to go home, but you can\'t stay here.', 2);
    $dbConn = mysql_close($dbConn) or trigger_error('Couldn\'t close the DB. Error = ' . mysql_error());
    return null;
  }

  /**
  * function db_query()
  * Run a query on the db
  * @link http://blog.program-o.com/?p=1345
  * @param resource $dbConn - the open connection
  * @param string $sql - the sql query to run
  * @return resource $result - the result resource
  **/
  function db_query($sql, $dbConn = null)
  {
    if ($dbConn === null) global $dbConn;
    //runDebug(__FILE__, __FUNCTION__, __LINE__, 'Querying the DB, for some reason.', 2);
    //run query
    $result = mysql_query($sql, $dbConn) or trigger_error('There was a problem with the following SQL query. Error = ' . mysql_error() . "\nSQL = $sql");
    //if no results output message
    if (!$result)
    {
      exit('Something is amiss!');
    }
    //return result resource
    return $result;
  }

  /*
   * function db_fetch_assoc
   * Fetches an associative array from a result pointer
   * @param $result
   * @return (array) $out
   */

  function db_fetch_assoc($result)
  {
    $out = mysql_fetch_assoc($result);
    return $out;
  }

  /*
     * function db_num_rows
     * Get number of rows in result
     * @param $result
     * @return (int) $out
     */

    function db_num_rows($result)
    {
      $out = mysql_num_rows($result);
      return $out;
    }

  /*
     * function db_affected_rows
     * Get number of affected rows in previous insert, delete or update operation
     * @param $result
     * @return (int) $out
     */

    function db_affected_rows($result = null)
    {
      $out = (is_null($result)) ? mysql_affected_rows() :  mysql_affected_rows($result);
      return $out;
    }

    /*
       * function db_escape_string
       * Escapes special characters in a string for use in an SQL statement
       * @param $Value
       * @return (string) $out
       */

      function db_escape_string($value)
      {
        // Code goes here...
        $out = mysql_real_escape_string($value);
        return $out;
      }

      /*
         * function db_insert_id
         * Get the ID generated in the last query
         * @param $dbConn
         * @return (int) $out
         */

        function db_insert_id($dbConn)
        {
          // Code goes here...
          $out = mysql_insert_id($dbConn);
          return (int) $out;
        }

