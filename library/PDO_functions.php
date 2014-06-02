<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.4.2
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
      $dbConn = new PDO("mysql:host=$dbh;dbname=$dbn;charset=utf8", $dbu, $dbp);
      $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $dbConn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      $dbConn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
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

