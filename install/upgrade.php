<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.1.3
  * FILE: upgrade.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 02-13-2013
  * DETAILS: Upgrades Program O from 2.0.x to 2.1
  ***************************************/

  $thisFile = __FILE__;
  if (!file_exists('../config/global_config.php')) header('location: ../install/install_programo.php');
  require_once('../config/global_config.php');
  error_reporting(E_ALL);
  ini_set('display_errors',1);
  ini_set('log_errors',1);
  ini_set('error_log', _LOG_PATH_ . 'upgrade.error.log');
  $test =$_SERVER['HTTP_REFERER'];
	//load shared files
	include_once(_LIB_PATH_."db_functions.php");
	include_once(_LIB_PATH_."error_functions.php");
	
	runDebug( __FILE__, __FUNCTION__, __LINE__, "Starting upgrade");
    $gui = filter_input(INPUT_GET,'returnTo');
    $gui = (!empty($gui)) ? $gui : 'plain';

  # process upgrade here
  $con = db_open();
  $upgrade_not_needed = 'Please remove the file upgrade.php from the install folder. It\'s not needed.';
  $needs_upgrade = check_for_upgrade();

  $msg = ($needs_upgrade == 1) ? $upgrade_not_needed : '';
  if (empty($msg))
  {
    $success = upgrade();
    if (!$success) exit(failure());
    $msg = success();
  }

  $msg .= ' <a href="' . _BASE_URL_ .  "gui/$gui" . '">Click here</a> to procede to the chatbot,
or <a href="' . _ADMIN_URL_ . '">here</a> to log into the admin page.
';

  echo($msg);


  function check_for_upgrade()
  {
    global $con, $dbn;
    $out = array();
    $sql = "show columns from `aiml_userdefined` like 'bot_id';";
    $result = db_query($sql, $con);
    if (!$result) exit(failure());
    else $out = mysql_num_rows($result);
    return $out; //
  }

  function upgrade()
  {
    global $dbn, $con;
    $queries = file_get_contents(_INSTALL_PATH_ . 'upgrade_2.0_2.1.sql');
    $sqlList = explode(';', $queries);
    $status = true;
    foreach ($sqlList as $sql)
    {
      if (trim($sql) == '') continue;
      $result = mysql_query($sql, $con);
      if (false === $result) {
        $status = false;
        trigger_error('There was a problem processing the SQL query! Error = ' . mysql_error() . "\nSQL:\n$sql");
      }
      $numRows = mysql_affected_rows($con);
    }
    return $status;
  }

  function success()
  {
    unlink(__FILE__);
    return 'DB upgrade successful!';
  }

  function failure()
  {
    return 'Upgrade failed! Please see the error logs for details.';
  }

?>