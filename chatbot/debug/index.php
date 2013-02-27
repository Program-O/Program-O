<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.1.3
  * FILE: index.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 02-15-2013
  * DETAILS: Program O Debug File Reader
  ***************************************/

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
    ini_set('error_log', _LOG_PATH_ . 'debug.reader.error.log');
  }

  $postVars = filter_input_array(INPUT_POST);

  $iframeURL = (!empty($postVars['file'])) ? $postVars['file'] : _LIB_URL_ . 'accessdenied.htm';
  #$iframeURL = ($postVars['name'] == $adm_$adm_dbu and $postVars['pass'] == $adm_dbp) ? $iframeURL : 'about:blank'; //

  $optionTemplate = '        <option value="[file]">[file]</option>' . "\n";
  $fileList = glob('*.txt');
  $options = '';
  foreach ($fileList as $file) {
    $row = str_replace('[file]', trim($file), $optionTemplate);
    $options .= $row;
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
    <form name="fileChoice" action="./" method="POST">
      Select a Debug File to view: <select name="file" id="file" size="1" onchange="document.forms[0].submit();">
        <option value="about:blank">Empty Selection</option>
<?php echo rtrim($options); ?>
      </select> &nbsp; &nbsp;
      Admin Name: <input name="name" />
      Password: <input name="pass" type="password" />
      <br />
      <div id="viewer">
        <iframe  width="99%" height="99%" src="<?php echo $iframeURL ?>"><h1>Access Denied!</h1></iframe>
      </div>
    </form>
  </body>
</html>
