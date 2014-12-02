<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.4.4
  * FILE: index.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 02-15-2013
  * DETAILS: Program O Debug File Reader
  ***************************************/

  ini_set('display_errors', 1);
  if (!file_exists('../../config/global_config.php'))
  {
    header('location: ../../install/install_programo.php');
  }
  else
  {
    $thisFile = __FILE__;
    require_once('../../config/global_config.php');
    if (!defined('SCRIPT_INSTALLED')) header('location: ' . _INSTALL_PATH_ . 'install_programo.php');
    require_once(_LIB_PATH_ . 'PDO_functions.php');
    include_once (_LIB_PATH_ . "error_functions.php");
    ini_set('error_log', _LOG_PATH_ . 'debug.reader.error.log');
  }
  $now_playing = '';
    $session_name = 'PGO_DEBUG';
    session_name($session_name);
    session_start();
    $iframeURL = 'about:blank';

  $postVars = filter_input_array(INPUT_POST);
  if (isset($postVars['logout']))
  {
    $_SESSION['isLoggedIn'] = false;
    header('Location: ' . _DEBUG_URL_);
  }

  if (isset($postVars['name']))
  {
    $name = $postVars['name'];
    $pass = md5($postVars['pass']);
    $dbConn = db_open();
    $sql = "select `password` from `myprogramo` where `user_name` = '$name' limit 1;";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $row = $sth->fetch();
    if ($row !== false)
    {
      $verify = $row['password'];
      if ($pass == $verify)
      {
        $_SESSION['isLoggedIn'] = true;
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
      <input type="submit" value="Log In" />';
  }
  else {
    $sel_msg = 'Empty Selection';
    $login_form = '
      <input type="submit" name="logout" value="Log Out" onclick="document.forms[0].submit();" />
';
    $iframeURL = (!empty($postVars['file'])) ? $postVars['file'] : 'about:blank';
    $now_playing = ($iframeURL == 'about:blank') ? 'Viewer is empty' : "<strong>Viewing Debug File: $iframeURL</strong>";
    $optionTemplate = '        <option[fileSelected] value="[file]">[file]</option>' . "\n";
    $fileList = glob(_DEBUG_PATH_ . '*.txt');
    usort(
      $fileList,
      create_function('$b,$a', 'return filemtime($a) - filemtime($b);')
    );
    $options = '';
    $postedFile = $postVars['file'];
    foreach ($fileList as $file) {
      $file = str_replace(_DEBUG_PATH_, '', $file);
      $file = trim($file);
      $fileSelected = ($file == $postedFile) ? ' selected="selected"' : '';
      $row = str_replace('[file]', trim($file), $optionTemplate);
      $row = str_replace('[fileSelected]', $fileSelected, $row);
      $options .= $row;
    }
  }



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Debug File Reader</title>
    <style type="text/css">
      body, html {
        min-width: 800px;
      }
      #viewer {
        position: absolute;
        left: 5px;
        top: 75px;
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
<?php echo $login_form ?> &nbsp; &nbsp;
    <a href="<?php echo _DEBUG_URL_ ?>">Reload the Page</a>
    </form>
    <br />
    <div id="now_playing"><?php echo $now_playing ?></div>
    <div id="viewer">
      <iframe  width="99%" height="99%" src="<?php echo $iframeURL ?>"><h1>Access Denied!</h1></iframe>
    </div>
  </body>
</html>
