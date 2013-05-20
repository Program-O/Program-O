<?php

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.2.1
  * FILE: library/db_functions.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: MAY 4TH 2011
  * DETAILS: common library of db functions
  ***************************************/
  /**
  * function db_open()
  * Connect to the database
  * @param  string $host -  db host
  * @param  string $user - db user
  * @param  string $password - db password
  * @param  string $database_name - db name
  * @return resource $con - the database connection resource
  **/
  function db_open()
  {
    global $dbh, $dbu, $dbp, $dbn, $dbPort;
    $host = (!empty ($dbPort) and $dbPort != 3306) ? "$dbh:$dbPort" : $dbh;
    // add port selection if not the standard port number
    $con = mysql_connect($host, $dbu, $dbp) or trigger_error('Couldn\'t connect to the DB. Error = ' . mysql_error());
    mysql_set_charset('utf8');
    mysql_query('SET NAMES UTF8');
    $x = mysql_select_db($dbn) or trigger_error('Couldn\'t select the DB. Error = ' . mysql_error());
    return $con;
  }

  /**
  * function db_close()
  * Close the connection to the database
  * @param resource $con - the open connection
  **/
  function db_close($con)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'This DB is now closed. You don\'t have to go home, but you can\'t stay here.', 2);
    $discdb = mysql_close($con) or trigger_error('Couldn\'t close the DB. Error = ' . mysql_error());
  }

  /**
  * function db_query()
  * Run a query on the db
  * @param resource $con - the open connection
  * @param string $sql - the sql query to run
  * @return resource $result - the result resource
  **/
  function db_query($sql, $dbConn)
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Querying the DB, for some reason.', 2);
    //run query
    $result = mysql_query($sql, $dbConn) or trigger_error('There was a problem with the following SQL query. Error = ' . mysql_error() . "\nSQL = $sql");
    //if no results output message
    if (!$result)
    {
    }
    //return result resource
    return $result;
  }


  /**
  * function db_res_count()
  * Makes a str safe to insert in the db
  * @param resource $result - the result resource
  * @return int $res - the number of results
  **/
  function db_res_count($result)
  {
    return mysql_num_rows($result);
  }

?>