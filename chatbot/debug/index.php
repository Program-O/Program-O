<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.1.4
  * FILE: index.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 02-15-2013
  * DETAILS: Program O Debug File Reader
  ***************************************/

  ini_set('display_errors', 1);
  if (!file_exists('../../config/global_config.php'))
  {
  # No config exists we will run install
    header('location: ../../install/install_programo.php');
  }
  else
  {

    # Config exists we will goto the bot
    $thisFile = __FILE__;
    require_once('../../config/global_config.php');
    if (!defined('SCRIPT_INSTALLED')) header('location: ' . _INSTALL_PATH_ . 'install_programo.php');
    include_once (_LIB_PATH_ . "db_functions.php");
    include_once (_LIB_PATH_ . "error_functions.php");
    ini_set('error_log', _LOG_PATH_ . 'debug.reader.error.log');
    ini_set('display_errors', 1);
  }
    $session_name = 'PGO_DEBUG';
    session_name($session_name);
    session_start();
    $iframeURL = 'about:blank';

  $postVars = filter_input_array(INPUT_POST);
/*
  print_r($postVars);
  echo "<br />\n";
  print_r($_SESSION);
*/

  if (isset($postVars['logout']))
  {
    $_SESSION['isLoggedIn'] = false;
    header('Location: ./index.php');
    #file_put_contents(_LOG_PATH_ . 'logout.txt', print_r($postVars, true));
  }

  if (isset($postVars['name']))
  {
    //echo 'Post[name] exists!<br />';
    $name = $postVars['name'];
    $pass = md5($postVars['pass']);
    $con = db_open();
    $sql = "select `password` from `myprogramo` where `user_name` = '$name' limit 1;";
    //echo "SQL = $sql<br />\n";
    $result = mysql_query($sql, $con) or die ('SQL error! Error:' . mysql_error());
    $numRows = mysql_num_rows($result);
    //echo "Number of rows: $numRows.";
    if ($numRows > 0)
    {
      $row = mysql_fetch_assoc($result);
      $verify = $row['password'];
      echo "Pass = $pass, verify = $verify.<br />\n";
      if ($pass == $verify)
      {
        $_SESSION['isLoggedIn'] = true;
        //echo 'Success!';
        header('Location: ' . _DEBUG_URL_);
      }
      else $iframeURL = _LIB_URL_ . 'accessdenied.htm';
    }
    else echo 'No results found!';
  }
  if (!isset($_SESSION['isLoggedIn']) or $_SESSION['isLoggedIn'] === false)
  {
    $sel_msg = 'Log in to continue';
    $options = '';
    $login_form = '
      Admin Name: <input name="name" />
      Password: <input name="pass" type="password" />
      <input type="submit" name="" value="Log In" />';
  }
  else {
    $sel_msg = 'Empty Selection';
    $login_form = '
      <input type="submit" name="logout" value="Log Out" onclick="document.forms[0].submit();" />';
    $iframeURL = (!empty($postVars['file'])) ? $postVars['file'] : 'about:blank';
    $optionTemplate = '        <option value="[file]">[file]</option>' . "\n";
    $fileList = glob(_DEBUG_PATH_ . '*.txt');
    $options = '';
    foreach ($fileList as $file) {
      $file = str_replace(_DEBUG_PATH_, '', $file);
      $row = str_replace('[file]', trim($file), $optionTemplate);
      $options .= $row;
    }
  }



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Debug File Reader</title>
    <style type="text/css">
      #viewer {
        position: absolute;
        left: 5px;
        top: 65px;
        right: 5px;
        bottom: 5px;
      }
    </style>
  </head>
  <body>
    <form name="fileChoice" action="<?php echo _DEBUG_URL_ ?>" method="POST">
      Select a Debug File to view: <select name="file" id="file" size="1" onchange="document.forms[0].submit();">
        <option value="about:blank"><?php echo $sel_msg ?></option>
<?php echo rtrim($options) . PHP_EOL; ?>
      </select> &nbsp; &nbsp;
<?php echo $login_form ?>
    </form>
    <br />
    <div id="viewer">
      <iframe  width="99%" height="99%" src="<?php echo $iframeURL ?>"><h1>Access Denied!</h1></iframe>
    </div>
  </body>
</html>
