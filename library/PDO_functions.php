<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.4.0
  * FILE: library/PDO_functions.php
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
    try {
      $dbConn = new PDO("mysql:host=$dbh;dbname=$dbn", $dbu, $dbp);
      $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (Exception $e)
    {
      exit('Looks like it\'s just not your day. ' . $e->getMessage);
    }
    return $dbConn;
  }

  /**
  * function db_close()
  * Close the connection to the database
  * @link http://blog.program-o.com/?p=1343
  * @param resource $dbConn - the open connection
  **/
  function db_close()
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'This DB is now closed. You don\'t have to go home, but you can\'t stay here.', 2);
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
  function db_query($sql, $dbConn)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Querying the DB, for some reason.', 2);
    //run query
    $result =  $dbConn->query($sql) or trigger_error("There was a problem with the following SQL query: \nSQL = $sql");
    //if no results output message
    if (!$result)
    {
    }
    //return result resource
    return $result;
  }

  /*
   * function db_fetch_assoc
   * Returns an associative array of rows from a database table
   * @param $result
   * @return (array) $out
   */

  function db_fetch_assoc($result)
  {
    $out = $result->fetchAll(PDO::FETCH_ASSOC);
    return $out;
  }

    /*
     * function db_escape_string
     * Returns an escaped version of the input string
     * @param $value
     * @return $out
     */

    function db_escape_string($value)
    {
      global $dbConn;
      $out = $dbConn->quote($value);
      return $out;
    }

      /*
       * function db_affected_rows
       * Returns the number of rows affected by an insertion, update or deletion
       * @param $dbConn
       * @return (int) $out
       */

      function db_affected_rows($result)
      {
        $out = $result->rowCount();
        return $out;
      }

        /*
         * function db_num_rows
         * returns the number of rows returned by a select statement
         * @param $result
         * @return (int) $out
         */

        function db_num_rows($result)
        {
          $rows = $result->fetchAll(PDO::FETCH_ASSOC);
          $out = count($rows);
          return $out;
        }

          /*
           * function db_insert_id
           * Returns the ID of the last inserted row or sequence value
           * @param $dbConn
           * @return (int) $out
           */

          function db_insert_id($dbConn)
          {
            // Code goes here...
            $out = $dbConn->lastInsertId();
            return $out;
          }


