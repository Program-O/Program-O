<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.11
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
 * @return PDO $dbConn - the database connection resource
 */
function db_open()
{
    global $dbh, $dbu, $dbp, $dbn, $dbPort;

    try
    {
        $dbConn = new PDO("mysql:host=$dbh;port=$dbPort;dbname=$dbn;charset=utf8", $dbu, $dbp);
        $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbConn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $dbConn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }
    catch (Exception $e) {
        //exit('Program O has encountered a problem with connecting to the database. With any luck, the following information will help: ' . $e->getMessage());
        $errMsg = <<<endMsg
Program O has encountered a problem with connecting to the database. With any luck, the following information will help:<br>
Error message: {$e->getMessage()}<br>
Host: {$dbh}<br>
Port: {$dbPort}<br>
User: {$dbu}<br>
Pass: {$dbp}<br>
endMsg;
        exit($errMsg);
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
function db_close($inPGO = true)
{
    if ($inPGO) runDebug(__FILE__, __FUNCTION__, __LINE__, 'This DB is now closed. You don\'t have to go home, but you can\'t stay here.', 2);
    return null;
}

/**
 * function db_fetch
 * Fetches a single row of data from the database
 *
 * @param string $sql - The SQL query to execute
 * @param mixed $params - either an array of placeholder/value pairs, or null, for no parameters
 * @param string $file - the path/filename of the file that the function call originated in
 * @param string $function - the name of the function that the function call originated in
 * @param string $line - the line number of the originating function call
 *
 * @return mixed $out - Either the row of data from the DB query, or false, if the query fails
 */
function db_fetch($sql, $params = null, $file = 'unknown', $function = 'unknown', $line = 'unknown', $inPGO = true)
{
    $fn = basename($file);
    global $dbConn;
    if (!isset($dbConn)) $dbConn = db_open();
    try
    {
        $sth = $dbConn->prepare($sql);
        if ($params === null) $sth->execute();
        else
        {
            foreach ($params as $label => $value)
            {
                switch (gettype($value))
                {
                    case 'boolean':
                        $paramType = PDO::PARAM_BOOL;
                        break;
                    case 'integer':
                        $paramType = PDO::PARAM_INT;
                        break;
                    case 'NULL':
                        $paramType = PDO::PARAM_NULL;
                        break;
                    default: $paramType = PDO::PARAM_STR;
                }
                $sth->bindValue($label, $value, $paramType);
            }
            $sth->execute();
        }
        $out = $sth->fetch();
    }
    catch (Exception $e)
    {
        //error_log("bad SQL encountered in file $file, line #$line. SQL:\n$sql\n", 3, _LOG_PATH_ . 'badSQL.txt');
        $pdoError = print_r($dbConn->errorInfo(), true);

        /** @noinspection PhpUndefinedVariableInspection */
        $psError = print_r($sth->errorInfo(), true);
        if ($inPGO) {
            $errSQL = db_parseSQL($sql, $params);
            $errParams = print_r($params, true);
            $eMessage = $e->getMessage();
            $rdMsg = <<<endMsg
An error was generated while extracting a row of data from the database. Relevant info:
File: $file
Function: $function
Line #: $line
Error Message: $eMessage
SQL: $errSQL
Parameters: $errParams
PDO error: $pdoError
PDOStatement error: $psError

endMsg;
            runDebug(__FILE__, __FUNCTION__, __LINE__, $rdMsg, 4);
            $fn = basename($file);
            $errLogPath = "{$fn}.{$function}.error.log";
            error_log($rdMsg, 3, _LOG_PATH_ . $errLogPath);
        }
        $out = false;
    }
    return $out;
}

/**
 * function db_fetchAll
 * Fetches rows of data from the database
 *
 * @param string $sql - The SQL query to execute
 * @param mixed $params - either an array of placeholder/value pairs, or null, for no parameters
 * @param string $file - the path/filename of the file that the function call originated in
 * @param string $function - the name of the function that the function call originated in
 * @param string $line - the line number of the originating function call
 *
 * @return mixed $out - Either an array of data from the DB query, or false, if the query fails
 */
function db_fetchAll($sql, $params = null, $file = 'unknown', $function = 'unknown', $line = 'unknown', $inPGO = true)
{
    global $dbConn;
    if (!isset($dbConn)) $dbConn = db_open();

    try {
        $sth = $dbConn->prepare($sql);
        if ($params === null) $sth->execute();
        else
        {
            foreach ($params as $label => $value)
            {
                switch (gettype($value))
                {
                    case 'boolean':
                        $paramType = PDO::PARAM_BOOL;
                        break;
                    case 'integer':
                        $paramType = PDO::PARAM_INT;
                        break;
                    case 'NULL':
                        $paramType = PDO::PARAM_NULL;
                        break;
                    default: $paramType = PDO::PARAM_STR;
                }
                $sth->bindValue($label, $value, $paramType);
            }
            $sth->execute();
        }
        $out = $sth->fetchAll();
    }
    catch (Exception $e)
    {
        //error_log("bad SQL encountered in file $file, line #$line. SQL:\n$sql\n", 3, _LOG_PATH_ . 'badSQL.txt');
        $pdoError = print_r($dbConn->errorInfo(), true);

        /** @noinspection PhpUndefinedVariableInspection */
        $psError = print_r($sth->errorInfo(), true);
        $errSql = db_parseSQL($sql, $params);
        $dParams = (!is_null($params)) ? print_r($params, true) : 'null';
        $errMsg = <<<endMsg
An error was generated while extracting multiple rows of data from the database in file $file at line $line, in the function $function.
SQL: $errSql
Params: $dParams
PDO error: $pdoError
PDO_statement error: $psError

endMsg;
        if ($inPGO) runDebug(__FILE__, __FUNCTION__, __LINE__, $errMsg, 0);
        $out = false;
    }
    return $out;
}

/**
 * function db_write
 * write to the database
 *
 * @param string $sql - The SQL query to execute
 * @param mixed $params - either an array of placeholder/value pairs, or null, for no parameters
 * @param bool $multi - TODO add missing argument description
 * @param string $file - the path/filename of the file that the function call originated in
 * @param string $function - the name of the function that the function call originated in
 * @param string $line - the line number of the originating function call
 *
 * @return mixed $out - Either the number of rows affected by the DB query
 */
function db_write($sql, $params = null, $multi = false, $file = 'unknown', $function = 'unknown', $line = 'unknown', $inPGO = true)
{
    global $dbConn;
    if (!isset($dbConn)) $dbConn = db_open();
    $newLine = PHP_EOL;
    try
    {
        $sth = $dbConn->prepare($sql);

        switch (true)
        {
            case ($params === null):
                $sth->execute();
                break;

            case ($multi === true):
                foreach ($params as $row) {
                    $sth->execute($row);
                }
                break;

            default:
                $sth->execute($params);
        }

        return (!$multi) ?  $sth->rowCount() : count($params);
    }
    catch (Exception $e)
    {
        $errParams = ($multi) ? $row : $params;
        $paramsText = print_r($errParams, true);
        $pdoError = print_r($dbConn->errorInfo(), true);

        /** @noinspection PhpUndefinedVariableInspection */
        $psError = print_r($sth->errorInfo(), true);
        $eMessage = $e->getMessage();
        $errSQL = db_parseSQL($sql, $errParams);
        $errorMessage = <<<endMessage
Bad SQL encountered in file $file, line #$line. SQL:
$errSQL
PDO Error:
$pdoError
PDOStatement Error:
$psError
Exception Message:
$eMessage;
Parameters:
$paramsText
endMessage;

        $fn = basename($file);
        $errLogPath = "{$fn}.{$function}.error.log";
        error_log($errorMessage, 3, _LOG_PATH_ . $errLogPath);

        $rdMessage = <<<endMessage
An error was generated while writing to the database in file $file at line $line, in the function $function.
SQL: $errSQL
PDO error: $pdoError
PDOStatement error: $psError
endMessage;

        if ($inPGO) runDebug(__FILE__, __FUNCTION__, __LINE__, $rdMessage, 0);
        return false;
    }
}


/**
 * function db_parseSQL
 *
 * Converts a prepared statment query into a human readable version
 *
 * @param string $sql
 * @param array $params
 *
 * @return string $out
 */

 function db_parseSQL ($sql, $params = null)
{
    $out = $sql;
    if (is_array($params))
    {
        foreach ($params as $key => $value)
        {
            if (is_numeric($key)) // deal with unnamed placeholders (?)
            {
                $limit = 1;
                $search = '/\?/';
                $value = (is_numeric($value)) ? $value : "'$value'"; // if $value is a string, encase it in quotes
                $out = preg_replace($search, $value, $out, $limit);
            }
            else // handle named parameters
            {
                $value = (is_numeric($value)) ? $value : "'$value'"; // if $value is a string, encase it in quotes
                $out = str_replace($key, $value, $out);
            }
        }
    }
    return $out;
}

function db_quote($in)
{
    global $dbConn;
    if (!isset($dbConn)) $dbConn = db_open();
    return $dbConn->quote($in);
}

function db_lastInsertId($name = null)
{
    global $dbConn;
    if (!isset($dbConn)) $dbConn = db_open();
    return $dbConn->lastInsertId($name);
}

/* A custom function that automatically constructs a multi insert statement.
 *
 * @param string $tableName Name of the table we are inserting into.
 * @param array $data An "array of arrays" containing our row data.
 * @param PDO $pdoObject Our PDO object.
 * @return boolean TRUE on success. FALSE on failure.
 */
function db_multi_insert($tableName, $data,  $file = 'unknown', $function = 'unknown', $line = 'unknown')
{
    global $dbConn;
    if (!isset($dbConn)) $dbConn = db_open();
    //Will contain SQL snippets.
    $rowsSQL = array();

    //Will contain the values that we need to bind.
    $toBind = array();

    //Get a list of column names to use in the SQL statement.
    $columnNames = array_keys($data[0]);

    //Loop through our $data array.
    foreach($data as $arrayIndex => $row){
        $params = array();
        foreach($row as $columnName => $columnValue){
            $param = ":" . $columnName . $arrayIndex;
            $params[] = $param;
            $toBind[$param] = $columnValue;
        }
        $rowsSQL[] = "(" . implode(", ", $params) . ")";
    }
    //save_file(_LOG_PATH_ . 'pdo_functions.db_multi_insert.params.txt', print_r($params, true));
    //return false;

    //Construct our SQL statement
    $sql = "INSERT INTO `$tableName` (" . implode(", ", $columnNames) . ") VALUES " . implode(", ", $rowsSQL);

    try
    {
    //Prepare our PDO statement.
    $sth = $dbConn->prepare($sql);

    //Bind our values.
    foreach($toBind as $param => $val){
        $sth->bindValue($param, $val);
    }
        //Execute our statement (i.e. insert the data).
        $out = ($sth->execute()) ? $sth->rowCount() : false;
    }
    catch (Exception $e)
    {
        //error_log("bad SQL encountered in file $file, line #$line. SQL:\n$sql\n", 3, _LOG_PATH_ . 'badSQL.txt');
        $pdoError = print_r($dbConn->errorInfo(), true);

        /** @noinspection PhpUndefinedVariableInspection */
        $psError = print_r($sth->errorInfo(), true);
        $errSql = db_parseSQL($sql, $params);
        $dParams = (!is_null($params)) ? print_r($params, true) : 'null';
        $errMsg = <<<endMsg
An error was generated while extracting multiple rows of data from the database in file $file at line $line, in the function $function.
SQL: $errSql
Params: $dParams
PDO error: $pdoError
PDO_statement error: $psError

endMsg;
        //if ($inPGO) runDebug(__FILE__, __FUNCTION__, __LINE__, $errMsg, 0);
        $out = false;
    }
    return $out;
}
