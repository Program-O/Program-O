<?php
/***************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.5
* FILE: index.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: 02-15-2013
* DETAILS: Program O Log File Reader
***************************************/

if (!file_exists('../config/global_config.php'))
{
    header('location: ../install/install_programo.php');
}
else
{
    $thisFile = __FILE__;
    /** @noinspection PhpIncludeInspection */
    require_once('../config/global_config.php');

    if (!defined('SCRIPT_INSTALLED'))
    {
        header('location: ' . _INSTALL_PATH_ . 'install_programo.php');
    }

    /** @noinspection PhpIncludeInspection */
    require_once(_LIB_PATH_ . 'PDO_functions.php');
    /** @noinspection PhpIncludeInspection */
    include_once (_LIB_PATH_ . "error_functions.php");

    ini_set('error_log', _LOG_PATH_ . 'log.reader.error.log');
}

$session_lifetime = 86400; // 24 hours, expressed in seconds
$now_playing = '';
$server_name = filter_input(INPUT_SERVER,'SERVER_NAME', FILTER_SANITIZE_STRING);
$session_cookie_path = str_replace("http://$server_name", '', _LOG_URL_);
session_set_cookie_params($session_lifetime, $session_cookie_path);
$session_name = 'PGO_LOG';
session_name($session_name);
session_start();
$iframeURL = 'about:blank';

$options = array(
    'file'   => FILTER_SANITIZE_STRING,
    'name'   => FILTER_SANITIZE_STRING,
    'pass'   => FILTER_SANITIZE_STRING,
    'logout' => FILTER_SANITIZE_STRING,
);

$post_vars = filter_input_array(INPUT_POST, $options);

if (isset($post_vars['logout']))
{
    $_SESSION['isLoggedIn'] = false;
    header('Location: ' . _LOG_URL_);
}

if (isset($post_vars['name']))
{
    $name = $post_vars['name'];
    $pass = md5($post_vars['pass']);
    $dbConn = db_open();
    /** @noinspection SqlDialectInspection */
    $sql = "SELECT `password` FROM `myprogramo` WHERE `user_name` = '$name' limit 1;";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $row = $sth->fetch();

    if ($row !== false)
    {
        $verify = (string) $row['password']; // cast the result as a string, just to make sure the comparison is properly made, later.

        if ($pass === $verify)
        {
            $_SESSION['isLoggedIn'] = true;
            header('Location: ' . _LOG_URL_);
        }
        else {
            $iframeURL = _LIB_URL_ . 'accessdenied.htm';
        }
    }
    else {
        echo 'No results found!';
    }
}

if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] === false)
{
    $sel_msg = 'Log in to continue';
    $options = '';
    $login_form = '
      Admin Name: <input name="name" />
      Password: <input name="pass" type="password" />
      <input type="submit" value="Log In" />';
}
else
{
    $sel_msg = 'Empty Selection';
    $login_form = '<input type="submit" name="logout" value="Log Out" onclick="document.forms[0].submit();" />';

    $iframeURL = (!empty($post_vars['file'])) ? $post_vars['file'] : 'about:blank';
    $now_playing = ($iframeURL == 'about:blank') ? 'Viewer is empty' : "<strong>Viewing Log File: $iframeURL</strong>";
    $optionTemplate = '        <option[fileSelected] value="[file]">[file]</option>' . "\n";
    $fileList = glob(_LOG_PATH_ . "{*.log,*.txt}", GLOB_BRACE);
    if (empty($fileList)) $sel_msg = 'No Logs to View';

    usort(
      $fileList,
      create_function('$b,$a', 'return filemtime($a) - filemtime($b);')
    );

    $options = '';
    $postedFile = $post_vars['file'];

    foreach ($fileList as $file)
    {
        $file = str_replace(_LOG_PATH_, '', $file);
        $file = trim($file);
        $fileSelected = ($file == $postedFile) ? ' selected="selected"' : '';
        $row = str_replace('[file]', trim($file), $optionTemplate);
        $row = str_replace('[fileSelected]', $fileSelected, $row);
        $options .= $row;
    }
}

?>
<!doctype html>
<html>
  <head>
    <title>Log File Reader</title>
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
    <form name="fileChoice" action="<?php echo _LOG_URL_ ?>" method="POST">
      Select a Log File to view: <select name="file" id="file" size="1" onchange="document.forms[0].submit();">
        <option value="about:blank"><?php echo $sel_msg ?></option>
<?php echo rtrim($options) . PHP_EOL; ?>
      </select> &nbsp; &nbsp;
<?php echo $login_form ?> &nbsp; &nbsp;
    <a href="<?php echo _LOG_URL_ ?>">Reload the Page</a>
    </form>
    <br />
    <div id="now_playing"><?php echo $now_playing ?></div>
    <div id="viewer">
      <iframe  width="99%" height="99%" src="<?php echo $iframeURL ?>"><h1>Access Denied!</h1></iframe>
    </div>
  </body>
</html>
