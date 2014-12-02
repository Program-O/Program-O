<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.4.4
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

  function db_fetch($sql, $params = null, $file = 'unknown', $function = 'unknown', $line = 'unknown')
  {
    global $dbConn;
    try
    {
      $sth = $dbConn->prepare($sql);
      ($params === null) ? $sth->execute() : $sth->execute($params);
      return $sth->fetch();
    }
    catch (Exception $e)
    {
      error_log("bad SQL encountered. SQL:\n$sql\n", 3, _LOG_PATH_ . 'badSQL.txt');
      $pdoError = print_r($dbConn->errorInfo(), true);
      $psError  = print_r($sth->errorInfo(), true);
      runDebug(__FILE__, __FUNCTION__, __LINE__, "An error was generated while extracting a row of data from the database in file $file at line $line, in the function $function - SQL:\n$sql\nPDO error: $pdoError\nPDOStatement error: $psError", 0);
      return false;
    }
  }

  function db_fetchAll($sql, $params = null, $file = 'unknown', $function = 'unknown', $line = 'unknown')
  {
    global $dbConn;
    try
    {
      $sth = $dbConn->prepare($sql);
      ($params === null) ? $sth->execute() : $sth->execute($params);
      return $sth->fetchAll();
    }
    catch (Exception $e)
    {
      error_log("bad SQL encountered. SQL:\n$sql\n", 3, _LOG_PATH_ . 'badSQL.txt');
      $pdoError = print_r($dbConn->errorInfo(), true);
      $psError  = print_r($sth->errorInfo(), true);
      runDebug(__FILE__, __FUNCTION__, __LINE__, "An error was generated while extracting multiple rows of data from the database in file $file at line $line, in the function $function - SQL:\n$sql\nPDO error: $pdoError\nPDOStatement error: $psError", 0);
      return false;
    }
  }

