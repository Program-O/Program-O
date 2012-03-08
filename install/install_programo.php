<?PHP
//-----------------------------------------------------------------------------------------------
//My Program-O Version 2.0.1
//Program-O  chatbot admin area
//Written by Elizabeth Perreau and Dave Morton
//May 2011
//for more information and support please visit www.program-o.com
//-----------------------------------------------------------------------------------------------

# Test for PHP version 5+
$myPHP_Version = (float)phpversion();
If ($myPHP_Version < 5) die ("I'm sorry, but Program O requires PHP version 5.0 or greater to function. Please ask your hosting provider to upgrade.");
session_name('PGO_install');
session_start();
$_SESSION['errorMessage'] = (!empty($_SESSION['errorMessage'])) ? $_SESSION['errorMessage'] : '';
require_once('../library/buildSelect.php');
require_once('../config/global_config.php');
define ('SECTION_START', '<!-- Section [section] Start -->'); # search params for start and end of sections
define ('SECTION_END', '<!-- Section [section] End -->'); # search params for start and end of sections
define ('PHP_SELF', $_SERVER['PHP_SELF']); # search params for start and end of sections
ini_set("display_errors", 1);
ini_set("log_errors",true);
#ini_set("error_log","../logs/error.log");
ini_set("error_log","error.log");

$currentDir = getcwd();
$defaultBaseDir = rtrim(str_ireplace('admin', '', $currentDir), '\\/');
$sep = (substr($defaultBaseDir,0, 1) == '/') ? '/' : '\\';

$localhost = $remotehost = $local_dbh = $remote_dbh = $local_dbn = $remote_dbn = $local_dbu = $remote_dbu = $local_dbp = $remote_dbp = 'Required';
$myHost = $_SERVER['SERVER_NAME'];
$domain_name = $myHost;
switch ($myHost) {
  case 'localhost':
  case $alternate_local_server_name:
  $localhost = $myHost;
  $local_dbh = $dbh;
  $local_dbn = $dbn;
  $local_dbu = $dbu;
  $local_dbp = $dbp;
   break;
  default:
  $remotehost = $myHost;
  $remote_dbh = $dbh;
  $remote_dbn = $dbn;
  $remote_dbu = $dbu;
  $remote_dbp = $dbp;
}
$PHP_SELF = $_SERVER['PHP_SELF']; # search params for start and end of sections

$replTagsArray = file('../install/config_template_tags.dat', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$replVarsArray = array();
foreach ($replTagsArray as $value) {
  $value = trim($value);
  $tmpVal = str_replace('[', '', $value);
  $tmpVal = str_replace(']', '', $tmpVal);
  $replVarsArray[$value] = $tmpVal;
}
chdir(dirname( realpath( __FILE__ )));
$rsTzVal = (function_exists('date_default_timezone_get')) ? date_default_timezone_get() : '';
$validPages = array(1,2,3);
$page_template = file_get_contents('config.tpl.htm');
if (isset($_POST)) {
  foreach ($_POST as $key => $value) {
  $_SESSION[$key] = rtrim($value);
 }
}
$page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
if (!in_array($page, $validPages)) $page = 1;
$func = (isset($_REQUEST['func'])) ? $_REQUEST['func'] : '';
if (!empty($func)) $nextPage = $func($page);
$pageTemplate = 'container';
$pageNotes = ucwords("Page $page Notes");
$content    = getSection('Header', $page_template, false);
$content .= "<!-- myHost = $myHost, localhost = $localhost  -->\n";
$content    .= getSection($pageTemplate, $page_template);
$content    .= getSection('Footer', $page_template);
$content    .= getSection("jQuery$page", $page_template);
$notes     = getSection($pageNotes, $page_template);
$submitButton = getSection('SubmitButton', $page_template);
$main = getMain($page, $page_template);
$tmpSearchArray = array();
$content = str_replace('[title]', "Step $page", $content);
$content = str_replace('[mainPanel]', $main, $content);
$content = str_replace('[localhost]', $localhost, $content);
$content = str_replace('[notes]', $notes, $content);
$content = str_replace('[SubmitButton]', $submitButton, $content);
$content = str_replace('[rsTZ]', $rsTzVal, $content);
$content = str_replace('[local_dbPort]', $dbPort, $content);
$content = str_replace('[remote_dbPort]', $dbPort, $content);
$content = str_replace('[PHP_SELF]', PHP_SELF, $content);
$content = str_replace('[errorMessage]', $_SESSION['errorMessage'], $content);
foreach ($replVarsArray as $search => $replace) {
  $replace = trim($replace);
  if (!isset($$replace)) $$replace = 'crap';
  $repl1 = $$replace;
  if (isset($$replace) and $$replace != 'crap') {
    $content = str_replace($search, $$replace, $content);
  }
}
if ($page == 2) $content = str_replace('[BotsTableForm]', page2form(), $content);
$content = str_replace('[cr6]', "\n    ", $content);
$content = str_replace('[cr4]', "\n   ", $content);
$content = str_replace('[nextPage]', $page + 1, $content);
$content = str_replace("\r\n", "\n", $content);
$content = str_replace("\n\n", "\n", $content);
foreach ($replVarsArray as $key => $value) {
  if (isset($$value)) {
    $content = str_replace($key, $$value, $content);
  }
}
$content .= <<<endPage

 </body>
</html>
endPage;

exit($content);

function getSection($sectionName, $page_template, $notFoundReturn = true) {
  $sectionStart = str_replace('[section]', $sectionName, SECTION_START);
  $sectionStartLen = strlen($sectionStart);
  $sectionEnd   = str_replace('[section]', $sectionName, SECTION_END);
  $startPos = strpos($page_template, $sectionStart, 0);
   if ($startPos === false) {
     if ($notFoundReturn) {
       return '';
     }
     else $startPos = 0;
  }
  else $startPos += $sectionStartLen;
  $endPos = strpos($page_template, $sectionEnd, $startPos) - 1;
  $sectionLen = $endPos - $startPos;
  $out = substr($page_template, $startPos, $sectionLen);
  return trim($out);
}

function save($page) {
  global $replVarsArray, $quickdebug, $writetotemp;
  $curSession = print_r($_SESSION, true);
  #if (!checkDBContents($dbn)) fillDB();
  checkDBContents($_SESSION['local_dbn']);
  switch ($page) {
    case 1:
      $out = 2;
      break;
    case 2:
      $out = 3;
      break;
    case 3:
      $newConfigFile = file_get_contents('../config/global_config.tpl');
      $addScriptInfo = 0;
      if (isset($_SESSION['botNames'])) {
        $botNameList = $_SESSION['botNames'];
        $nameArray = explode("\n", $botNameList);
        $flippedNameArray = array_flip($nameArray);
        $defaultBotID = $_SESSION['default_bot_id'];
      }
      $botArrayTemplate = '$botNames = array(';
      foreach ($replVarsArray as $key => $value) {
        if ($key == 'botNames') {
          $nameList = explode("\n", $value);
          $listCount = 1;
          foreach ($nameList as $botName) {
            $botName = trim($botName);
            $botArrayTemplate .= "$listCount => '$botName', ";
            $listCount++;
          }
          $botArrayTemplate = rtrim($botArrayTemplate, ',') . ');';
          $value = $botArrayTemplate . "\r\n";
        }
        $repl = (isset($_SESSION[$value])) ? $_SESSION[$value] : '';
        $newConfigFile = str_replace($key, $repl, $newConfigFile);
        $newConfigFile = str_replace("\r\n", "\n", $newConfigFile);
      }
      $newConfigFile = str_replace('[addScriptInfo]', $addScriptInfo, $newConfigFile);
      $x = file_put_contents('../config/global_config.php', $newConfigFile);
      $out = "";
      break;
    default:
  }
  return $out;
}

function getMain($page, $page_template) {
  global $dbh, $dbu, $dbp, $dbn, $dbPort;
  switch ($page) {
    case 1:
      return getSection('GeneralConfig',$page_template);
      break;
    case 2:
      return getSection('AIMLConfig', $page_template);
      break;
  }
  $numBots = $_SESSION['bot_count'];
  $sql_template = <<<endSQL
INSERT IGNORE INTO `bots` (`bot_id`, `bot_name`, `bot_desc`, `bot_active`, `bot_parent_id`, `format`, `use_aiml_code`, `update_aiml_code`, `save_state` , `conversation_lines` , `remember_up_to` , `debugemail`, `debugshow`, `debugmode`, `default_aiml_pattern`) VALUES ([cur_bot_ID], '[cur_bot_name]', '[cur_bot_desc]', [cur_bot_active], [cur_bot_parent_id], '[cur_format]', '[cur_save_state]', [cur_conversation_lines], [cur_remember_up_to], '[cur_debugemail]',  [cur_debugshow], [cur_debugmode], '[cur_default_aiml_pattern]', [cur_use_aiml_code], [cur_update_aiml_code]);
endSQL;
  require_once ('../library/error_functions.php');
  require_once ('../library/db_functions.php');
  $conn = db_open();
  for ($loop = 1; $loop <= $_SESSION['bot_count']; $loop++) {
    $sql = str_replace('[cur_bot_ID]', $loop, $sql_template);
    $sql = @str_replace('[cur_bot_name]',$_SESSION["bot_name_$loop"], $sql);
    $sql = @str_replace('[cur_bot_desc]',$_SESSION["bot_desc_$loop"], $sql);
    $sql = @str_replace('[cur_bot_active]',$_SESSION["bot_active_$loop"], $sql);
    $sql = @str_replace('[cur_bot_parent_id]',$_SESSION['default_bot_id'], $sql);
    $sql = @str_replace('[cur_format]',$_SESSION["format_$loop"], $sql);
    if (!isset($_SESSION["use_aiml_code_$loop"])) $_SESSION["use_aiml_code_$loop"] = 0;
    $cur_use_aiml_code_checked = ($_SESSION["use_aiml_code_$loop"] == 1) ? ' checked="checked"' : '';
    $sql = str_replace('[cur_use_aiml_code]',$_SESSION["use_aiml_code_$loop"], $sql);
    $sql = str_replace('[cur_use_aiml_code_checked]',$cur_use_aiml_code_checked, $sql);
    if (!isset($_SESSION["update_aiml_code_$loop"])) $_SESSION["update_aiml_code_$loop"] = 0;
    $cur_update_aiml_code_checked = ($_SESSION["update_aiml_code_$loop"] == 1) ? ' checked="checked"' : '';
    $sql = str_replace('[cur_update_aiml_code]',$_SESSION["update_aiml_code_$loop"], $sql);
    $sql = str_replace('[cur_update_aiml_code_checked]',$cur_update_aiml_code_checked, $sql);
    $sql = @str_replace('[cur_save_state]',$_SESSION["save_state_$loop"], $sql);
    $sql = @str_replace('[cur_conversation_lines]',$_SESSION["conversation_lines_$loop"], $sql);
    $sql = @str_replace('[cur_remember_up_to]',$_SESSION["remember_up_to_$loop"], $sql);
    $sql = str_replace('[cur_debugemail]',$_SESSION["default_debugemail"], $sql);
    $sql = str_replace('[cur_debugshow]',$_SESSION["default_debugshow"], $sql);
    $sql = str_replace('[cur_debugmode]',$_SESSION["default_debugmode"], $sql);
    $sql = str_replace('[cur_default_aiml_pattern]',$_SESSION["default_pattern"], $sql);
    $x = db_query($sql, $conn) or $_SESSION['errorMessage'] = 'Could not enter bot info for bot #' . $loop . '! Error = ' . mysql_error();
  }
  global $adm_dbu, $adm_dbp;
  $encrypted_adm_dbp = md5($adm_dbp);
  $cur_ip = $_SERVER['REMOTE_ADDR'];
  $adminSQL = "insert ignore into `myprogramo` (`id`, `uname`, `pword`, `lastip`) values(null, '$adm_dbu', '$encrypted_adm_dbp', '$cur_ip');";
  $result = db_query($adminSQL, $conn) or $_SESSION['errorMessage'] = 'Could not add admin account! Error = ' . mysql_error();
  return ($result) ? getSection('InstallComplete', $page_template) : getSection('InstallError', $page_template);
}

function page2form() {
  global $page_template;
  $out = '';
  $namesArray = explode("\n", $_SESSION['botNames']);
  for ($loop = 1; $loop <= $_SESSION['bot_count']; $loop++) {
    $curConvoLines = (!empty($_SESSION["conversation_lines_$loop"])) ? $_SESSION["conversation_lines_$loop"] : $_SESSION['default_conversation_lines'];
    $tmpSection = getSection('BotsTableForm', $page_template);
    $tmpSection = str_replace('[bot_ID]', $loop, $tmpSection);
    $tmpSection = str_replace('[current_bot_name]', rtrim($namesArray[$loop-1]), $tmpSection);
    $tmpSection = str_replace('[cl_value]', $curConvoLines, $tmpSection);
    $out .= $tmpSection;
  }
  return $out;
}

function checkDBContents($dbn) {
  $local_dbh    = $_SESSION['local_dbh'];
  $local_dbn    = $_SESSION['local_dbn'];
  $local_dbu    = $_SESSION['local_dbu'];
  $local_dbp    = $_SESSION['local_dbp'];
  $local_dbPort = $_SESSION['local_dbPort'];
  $death = <<<endDeath

<pre>
Variables:
local_dbh: $local_dbh
local_dbu: $local_dbu
local_dbp: $local_dbp
local_dbn: $local_dbn
local_dbPort: $local_dbPort


endDeath;
  $host = ($local_dbh == 'Required') ? 'localhost' : $local_dbh;
  $conn = mysql_connect($host, $local_dbu, $local_dbp) or die( "mysql_connect error:". mysql_errno() . ', ' . mysql_error() . $death);
  mysql_select_db($local_dbn,$conn);
  $sql = "show tables;";
  $result = mysql_query($sql,$conn) or die ("Houston, we have a problem! " . mysql_error() . ", sql = $sql");
  $out = mysql_fetch_assoc($result);
  if (empty($out)) {
    $sql = file_get_contents('new.sql');
    $queries = preg_split("/;/", $sql);
    foreach ($queries as $query){
      $death .= "sql:\n$query\n\n";
#die("This is where I died: " . __FILE__ . ', line ' . __LINE__ . "\nDeath = $death");
      if (strlen(trim($query)) > 0) {
        $result = mysql_query($query,$conn) or die ("Houston, we have a problem! " . mysql_error() . ", sql:\n<pre>$wuery\n</pre>\n");
        $success = mysql_affected_rows($result);
      }
    }
  }
  mysql_close($conn);
}

?>
