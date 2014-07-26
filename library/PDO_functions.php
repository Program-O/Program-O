<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.4.3
  * FILE: library/PDO_functions.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: MAY 17TH 2014
  * DETAILS: common library of db functions
  ***************************************/
  /**
   * function db_open()
   * Connect to the database
   *
   * @link     http://blog.program-o.com/?p=1340
   * @internal param string $host -  db host
   * @internal param string $user - db user
   * @internal param string $password - db password
   * @internal param string $database_name - db name
   * @return resource $dbConn - the database connection resource
   */
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
      exit('Program O has encountered a problem with connecting to the database. With any luck, the following message will help: ' . $e->getMessage);
    }
    return $dbConn;
  }

  /**
   * function db_close()
   * Close the connection to the database
   *
   * @link     http://blog.program-o.com/?p=1343
   * @internal param resource $dbConn - the open connection
   *
   * @return null
   */
  function db_close()
  {
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'This DB is now closed. You don\'t have to go home, but you can\'t stay here.', 2);
    return null;
  }

