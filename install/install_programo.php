<?PHP

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.3.0
  * FILE: install_programo.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 02-13-2013
  * DETAILS: Program O's Automatic install script
  ***************************************/
  $thisFile = __FILE__;
  # Test for PHP version 5+
  $myPHP_Version = (float) phpversion();
  If ($myPHP_Version < 5)
    die("I'm sorry, but Program O requires PHP version 5.0 or greater to function. Please ask your hosting provider to upgrade.");
  session_name('PGO_install');
  session_start();
  $no_unicode_message = '';
  #$no_unicode_message = "<p class=\"red\">Warning! Unicode Support is not available on this server. Non-English languages will not display properly. Please ask your hosting provider to enable the PHP mbstring extension to correct this.</p>\n";
  $errorMessage = (!empty ($_SESSION['errorMessage'])) ? $_SESSION['errorMessage'] : '';
  $errorMessage .= $no_unicode_message;
  require_once ('install_config.php');
  define('SECTION_START', '<!-- Section [section] Start -->'); # search params for start and end of sections
  define('SECTION_END', '<!-- Section [section] End -->'); # search params for start and end of sections
  define('PHP_SELF', $_SERVER['SCRIPT_NAME']); # This is more secure than $_SERVER['PHP_SELF'], and returns more or less the same thing
  ini_set("display_errors", 0);
  ini_set("log_errors", true);
  ini_set("error_log", _LOG_PATH_ . "install.error.log");
  $myHost = $_SERVER['SERVER_NAME'];
  chdir(dirname(realpath(__FILE__)));
  $page_template = file_get_contents('install.tpl.htm');
  $page = (isset ($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
  $action = (isset ($_REQUEST['action'])) ? $_REQUEST['action'] : '';
  if (!empty ($action))
    $message = $action($page);
  $pageTemplate = 'Container';
  $pageNotes = ucwords("Page $page Notes");
  $content = getSection('Header', $page_template, false);
  $content .= getSection($pageTemplate, $page_template);
  $content .= getSection('Footer', $page_template);
  $content .= getSection("jQuery$page", $page_template);
  $notes = getSection($pageNotes, $page_template);
  $submitButton = getSection('SubmitButton', $page_template);
  $main = ($page == 1) ? getSection('InstallForm', $page_template) : $message;
  $tmpSearchArray = array();
  $content = str_replace('[mainPanel]', $main, $content);
  $content = str_replace('[http_host]', $myHost, $content);
  $content = str_replace('[error_response]', $error_response, $content);
  $content = str_replace('[notes]', $notes, $content);
  $content = str_replace('[PHP_SELF]', PHP_SELF, $content);
  $content = str_replace('[errorMessage]', $errorMessage, $content);
  $content = str_replace('[cr6]', "\n ", $content);
  $content = str_replace('[cr4]', "\n ", $content);
  $content = str_replace("\r\n", "\n", $content);
  $content = str_replace("\n\n", "\n", $content);
  $content = str_replace('[admin_url]', _ADMIN_URL_, $content);
  $content .= <<<endPage

</body>
</html>
endPage;

  exit ($content);

  function getSection($sectionName, $page_template, $notFoundReturn = true)
  {
    $sectionStart = str_replace('[section]', $sectionName, SECTION_START);
    $sectionStartLen = strlen($sectionStart);
    $sectionEnd = str_replace('[section]', $sectionName, SECTION_END);
    $startPos = strpos($page_template, $sectionStart, 0);
    if ($startPos === false)
    {
      if ($notFoundReturn)
      {
        return '';
      }
      else
        $startPos = 0;
    }
    else
      $startPos += $sectionStartLen;
    $endPos = strpos($page_template, $sectionEnd, $startPos) - 1;
    $sectionLen = $endPos - $startPos;
    $out = substr($page_template, $startPos, $sectionLen);
    return trim($out);
  }

  function Save()
  {
    global $page_template, $error_response;
    $pattern = "RANDOM PICKUP LINE";
    #$error_response = "No AIML category found. This is a Default Response.";
    $conversation_lines = '1';
    $remember_up_to = '10';
    $_SESSION['errorMessage'] = '';
    // First off, write the config file
    $myPostVars = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    ksort($myPostVars);
    $configContents = file_get_contents(_INSTALL_PATH_ . 'config.template.php');
    foreach ($myPostVars as $key => $value)
    {
      $tagSearch[] = "[$key]";
      $varReplace[] = $value;
    }
    $configContents = str_replace($tagSearch, $varReplace, $configContents);
    $saveFile = file_put_contents(_CONF_PATH_ . 'global_config.php', $configContents);
    // Now, update the data to the database, starting with making sure the tables are installed
    $sql = "show tables;";
    $conn = mysql_connect($myPostVars['dbh'], $myPostVars['dbu'], $myPostVars['dbp']) or install_error('Could not connect to the database!', mysql_error(), $sql);
    $dbn = $myPostVars['dbn'];
    $db = mysql_select_db($dbn, $conn) or install_error("Can't select the database $dbn!", mysql_error(), "use $dbn");
    $result = mysql_query($sql, $conn) or install_error('Unknown database error!', mysql_error(), $sql);
    $row = mysql_fetch_assoc($result);
    if (empty ($row))
    {
      $sql = file_get_contents('new.sql');
      $queries = preg_split("/;/", $sql);
      foreach ($queries as $query)
      {
        if (strlen(trim($query)) > 0)
        {
          $result = mysql_query($query, $conn) or install_error('Error creating new tables for DB!', mysql_error(), $query);
          $success = mysql_affected_rows();
        }
      }
    }
    $sql = 'select `error_response` from `bots` where 1 limit 1';
    $result = mysql_query($sql, $conn) or upgrade($conn);
    $sql = 'select `php_code` from `aiml` where 1 limit 1';
    $result = mysql_query($sql, $conn) or upgrade($conn);
    $sql_template = "
INSERT IGNORE INTO `bots` (`bot_id`, `bot_name`, `bot_desc`, `bot_active`, `bot_parent_id`, `format`, `save_state`, `conversation_lines`, `remember_up_to`, `debugemail`, `debugshow`, `debugmode`, `error_response`, `default_aiml_pattern`)
VALUES ([default_bot_id], '[bot_name]', '[bot_desc]', '[bot_active]', '[bot_parent_id]', '[format]', '[save_state]',
'$conversation_lines', '$remember_up_to', '[debugemail]', '[debugshow]', '[debugmode]', '$error_response', '$pattern');";
    require_once (_LIB_PATH_ . 'error_functions.php');
    require_once (_LIB_PATH_ . 'db_functions.php');
    $bot_id = 1;
    $sql = str_replace('[default_bot_id]', $bot_id, $sql_template);
    $sql = str_replace('[bot_name]', $myPostVars['bot_name'], $sql);
    $sql = str_replace('[bot_desc]', $myPostVars['bot_desc'], $sql);
    $sql = str_replace('[bot_active]', $myPostVars['bot_active'], $sql);
    $sql = str_replace('[bot_parent_id]', 1, $sql);
    $sql = str_replace('[format]', $myPostVars['format'], $sql);
    // "Use PHP from DB setting
    // "Update PHP in DB setting
    $sql = str_replace('[save_state]', $myPostVars['save_state'], $sql);
    $sql = str_replace('[conversation_lines]', $conversation_lines, $sql);
    $sql = str_replace('[remember_up_to]', $remember_up_to, $sql);
    $sql = str_replace('[debugemail]', $myPostVars['debugemail'], $sql);
    $sql = str_replace('[debugshow]', $myPostVars['debug_level'], $sql);
    $sql = str_replace('[debugmode]', $myPostVars['debug_mode'], $sql);
    $sql = str_replace('[error_response]', $error_response, $sql);
    $sql = str_replace('[aiml_pattern]', $pattern, $sql);
    #$save = file_put_contents(_CONF_PATH_ . 'sql.txt', $sql); // For debugging purposes only
    $x = db_query($sql, $conn) or install_error('Could not enter bot info for bot #' . $bot_id . '!', mysql_error(), $sql);
    $encrypted_adm_dbp = md5($myPostVars['adm_dbp']);
    $adm_dbu = $myPostVars['adm_dbu'];
    $cur_ip = $_SERVER['REMOTE_ADDR'];
    $adminSQL = "insert ignore into `myprogramo` (`id`, `user_name`, `password`, `last_ip`) values(null, '$adm_dbu', '$encrypted_adm_dbp', '$cur_ip');";
    $result = db_query($adminSQL, $conn) or install_error('Could not add admin credentials! Check line #' . __LINE__, mysql_error(), $adminSQL);
    mysql_close($conn);
    if ($result and empty ($_SESSION['errorMessage']))
    {
      $out = getSection('InstallComplete', $page_template);
      if (file_exists(_INSTALL_PATH_ . 'upgrade.php'))
        unlink(_INSTALL_PATH_ . 'upgrade.php');
    }
    else
      $out = getSection('InstallError', $page_template);
    return $out;
  }

  function install_error($msg, $err, $sql)
  {
    $errorTemplate = <<<endError
<pre>There was a problem while working with the database.
Error message: $msg
MySQL error: $err
SQL query:
$sql
</pre>
endError;
      $_SESSION['errorMessage'] .= $errorTemplate;
  }

  function upgrade($conn)
  {
    $upgradeSQL = file_get_contents('upgrade_2.0_2.1.sql');
    $queries = explode(';', $upgradeSQL);
    foreach ($queries as $line => $sql)
    {
      $sql = trim($sql);
      if (!empty ($sql))
      {
        $result = mysql_query($sql, $conn) or install_error('Error upgrading the database! check line #' . $line + 1 . ' of the SQL file. Error:', mysql_error() . "\nSQL: $sql\n", $sql);
        $success = mysql_affected_rows();
      }
    }
  }

?>
