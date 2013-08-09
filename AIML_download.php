<?PHP

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.3.1
  * FILE: AIMLdownload.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 07-30-2013
  * DETAILS: extracts all content from a Program O DB's aiml table, converts it into AIML files, adds the files to a zip zrchive and sends it out for download.
  ***************************************/

  $e_all = defined('E_DEPRECATED') ? E_ALL & ~E_DEPRECATED : E_ALL;
  error_reporting($e_all);
  ini_set('display_errors', 1);
  ini_set('log_errors', 1);
  session_start();
  $this_path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
  $this_path = str_replace(DIRECTORY_SEPARATOR, '/', $this_path);
  chdir($this_path);
  define('ROOT_PATH', $this_path);
  switch (true)
  {
  // Version 1
  case (file_exists(ROOT_PATH . 'bot/config.php')):
    $msg = 'Version 1 detected. Loading DB info from config file.<br>' . PHP_EOL;
    require_once (ROOT_PATH . 'bot/config.php');
    break;
    // Version 2
  case (file_exists(ROOT_PATH . 'config/_global_config.php')):
    $thisFile = __FILE__; // Just in case this variable is needed (some versions)
    require_once (ROOT_PATH . 'config/global_config.php');
    $msg = 'Version 2 detected. Loading DB info from config file.<br>' . PHP_EOL;
    break;
    // Version 3
  case (file_exists(ROOT_PATH . 'config/db.config.php')):
    $msg = 'Version 3 detected. Loading DB info from config file.<br>' . PHP_EOL;
    require_once (ROOT_PATH . 'config/db.config.php');
    $dbh = $db_config['db_host'];
    $dbn = $db_config['db_name'];
    $dbu = $db_config['db_user'];
    $dbp = $db_config['db_pass'];
    break;
    default :
      $msg = 'No version detected. Please enter information in the form above.';
      if (empty ($_POST))
      {
        print_form();
        foreach ($_SESSION as $key)
        {
          $_SESSION[$key] = '';
          unset ($_SESSION[$key]);
        }
        $_SESSION = array();
        session_write_close();
        exit ();
      }
      else
      {
        $post_vars = filter_input_array(INPUT_POST);
        $error = 'All fields are required. Please fill out the missing information.';
        $url = $_SERVER['PHP_SELF'];
        foreach ($post_vars as $key => $value)
        {
          if (empty ($value))
            continue;
          $_SESSION[$key] = $value;
        }
        if (empty ($dbh) or empty ($dbn) or empty ($dbu) or empty ($dbp))
        {
          $_SESSION['error'] = $error;
          header("Location: $url");
          #die("Location: $url");
        }
      }
  }
  // define whether mb_string functions are available (just in case it sin't already defined)
  if (!defined('IS_MB_ENABLED'))
    define('IS_MB_ENABLED', (function_exists('mb_internal_encoding')) ? true : false);
  ini_set('display_errors', 1);
  echo ($msg);
  $dbh = mysql_connect($dbh, $dbu, $dbp) or die('Cannot open the database. Error: ' . mysql_error());
  mysql_select_db($dbn, $dbh) or die('Cannot open the database. Error: ' . mysql_error());
  $fileList = get_file_list();
  $zip = new ZipArchive();
  $result = $zip->open(ROOT_PATH . 'AIML_files.zip', ZipArchive :: CREATE);
  if ($result === TRUE)
  {
    foreach ($fileList as $file)
    {
    //echo "processing AIML file $file<br>\n";
      $aimlFile = getAIMLByFileName($file);
      if (!$aimlFile = pretty_print($aimlFile, $file)) continue;
      //die('<pre>' . htmlentities($aimlFile));
      $zip->addFromString($file, $aimlFile) or die('Couldn\'t add file!');
    }
    $zip->close();
    echo 'Process complete!';
  }
  else
  {
    echo 'failed';
  }
  exit ();
  serveFile();

  function serveFile()
  {
    header('Content-Description: File Transfer');
    header("Content-type: application/zip");
    header("Content-Disposition: attachment; filename=AIML_files.zip");
    header("Pragma: no-cache");
    header("Expires: 0");
    readfile(ROOT_PATH . 'AIML_files.zip');
    exit;
    return $msg;
  }

  function get_file_list()
  {
    global $dbh;
    $filenames = array();
    $no_file = 'no_file';
    $sql = 'select distinct filename from aiml;';
    $result = mysql_query($sql, $dbh) or die('oops? ' . mysql_error());
    while ($row = mysql_fetch_assoc($result))
    {
      $filenames[] = $row['filename'];
    }
    mysql_free_result($result);
    return $filenames;
  }

  function getAIMLByFileName($filename)
  {
    global $dbh;
    $categoryTemplate = '<category><pattern>[pattern]</pattern>[that]<template>[template]</template></category>';
    $fileNameSearch = '[fileName]';
    $cfnLen = strlen($filename);
    $fileNameSearch = str_pad($fileNameSearch, $cfnLen);
    # Get all topics within the file
    $topicArray = array();
    $curPath = dirname(__FILE__);
    chdir($curPath);
    $fileContent = '<?xml version="1.0" encoding="utf-8"?>
<aiml>';
    $sql = "select distinct topic from aiml where filename like '$filename';";
    #return "SQL = $sql";
    $result = mysql_query($sql, $dbh) or trigger_error('Cannot load the list of topics from the DB. Error = ' . mysql_error());
    while ($row = mysql_fetch_assoc($result))
    {
      $topicArray[] = $row['topic'];
    }
    foreach ($topicArray as $topic)
    {
      if (!empty ($topic))
        $fileContent .= "<topic name=\"$topic\">\n";
      $sql = "select pattern, thatpattern, template from aiml where topic like '$topic' and filename like '$filename';";
      $result = mysql_query($sql, $dbh) or trigger_error('Cannot obtain the AIML categories from the DB. Error = ' . mysql_error());
      while ($row = mysql_fetch_assoc($result))
      {
        $pattern = (IS_MB_ENABLED) ? mb_strtoupper($row['pattern']) : strtoupper($row['pattern']);
        $template = str_replace("\r\n", '', $row['template']);
        $template = str_replace("\n", '', $row['template']);
        $newLine = str_replace('[pattern]', $pattern, $categoryTemplate);
        $newLine = str_replace('[template]', $template, $newLine);
        $that = (!empty ($row['thatpattern'])) ? '<that>' . $row['thatpattern'] . '</that>' : '';
        $newLine = str_replace('[that]', $that, $newLine);
        $fileContent .= "$newLine\n";
      }
      if (!empty ($topic))
        $fileContent .= "</topic>\n";
    }
    $fileContent .= "\r\n</aiml>\r\n";
    $outFile = ltrim($fileContent, "\n\r\n");
    $outFile = mb_convert_encoding($outFile, 'UTF-8');
    return $outFile;
  }

  function pretty_print($content, $filename)
  {
    libxml_use_internal_errors(true);
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->preserveWhiteSpace = false;
    $xml->formatOutput = true;
    if (!$xml->loadXML($content))
    {
      echo "There was a problem entering $filename. Please see the error(s) encountered, below:<br>\n";
      foreach (libxml_get_errors() as $error)
      {
        echo 'Error #' . $error->code . ': ' . $error->message . "<br>\n";
      }
      libxml_clear_errors();
      return false;
    }
    return $xml->saveXML();
  }

  /*
  * function get_new_filename
  * Checks the array of used filenames before assigning a new filename to AIML categories that had no filenames in the DB
  * @param (array) $filenames - contains a list of currently used filenames
  * @param (string) $cur_name - The current name to check/assign
  * @return (string) $out - The filename to return
  * Note that this is a recursive function, so that it makes sure that
  */
  function get_new_filename($filenames, $cur_name)
  {
    global $no_file;
    if (!in_array("$cur_name.aiml", $filenames))
      return "$cur_name.aiml";
    $index = str_replace($no_file, '', $cur_name);
    $index = ($index = '') ? 0 : $index + 1;
    $index = str_pad($index, 3, '0', STR_PAD_LEFT);
    $out = get_new_filename($filenames, "$cur_name$index");
    return $out;
  }

  function print_form()
  {
    global $msg;
    $error = $dbh = $dbn = $dbu = $dbp = '';
    extract($_SESSION);

  ?>
<!DOCTYPE HTML>
<html>
  <head>
    <title>Program O AIML Extractor</title>
    <style type="text/css">
      p.error {
        color: red;
      }
      p.message {
        width: 100%;
        text-align: center;
      }
    </style>
  </head>
  <body>
    <h2>Program O Utilities - DB 2 AIML</h2>
    <p>
      This script will take the DB login information below and use it to connect to your Program O chatbot's
      database, where it will extract all of the AIML categories from the DB, sort them into their various
      file names, arrange them by topic, create the various associated AIML files, zip them up for convenience,
      and offer them for download as a single Zip archive. All you need to do is provide connection information
      for your chatbot's database.
    </p>
    <form id="db_info" name="db_info" action="db2aiml.php" method="POST">
    <table>
      <tr>
        <td>
          <label for="dbh">DB Host: </label>
        </td>
        <td>
          <input type="text" name="dbh" id="dbh" value="<?php echo $dbh ?>" />
        </td>
        <td>
          <label for="dbn">DB Name: </label>
        </td>
        <td>
          <input type="text" name="dbn" id="dbn" value="<?php echo $dbn ?>" />
        </td>
      </tr>
      <tr>
        <td>
          <label for="dbu">DB Username: </label>
        </td>
        <td>
          <input type="text" name="dbu" id="dbu" value="<?php echo $dbu ?>" />
        </td>
        <td>
          <label for="dbp">DB Password: </label>
        </td>
        <td>
          <input type="text" name="dbp" id="dbp" value="<?php echo $dbp ?>" />
        </td>
      </tr>
      <tr>
        <td colspan="4">
          <input id="submit" name="submit" type="submit" value="Log In">
        </td>
      </tr>
    </table>
    </form>
    <p class="message"><?php echo $msg ?></p>
    <p class="error"><?php echo $error ?></p>
  </body>
</html>
  <?php

  }

?>
