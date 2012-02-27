<?PHP
//-----------------------------------------------------------------------------------------------
//My Program-O Version 2.0.1
//Program-O  chatbot admin area
//Written by Elizabeth Perreau and Dave Morton
//Aug 2011
//for more information and support please visit www.program-o.com
//-----------------------------------------------------------------------------------------------
// bots.php

# set template section defaults

# Build page sections
# ordered here in the order that the page is constructed
  $bot_name = (isset($_SESSION['poadmin']['bot_name'])) ? $_SESSION['poadmin']['bot_name'] : 'unknown';
  $func = (isset($_POST['func'])) ? $_POST['func'] : 'getBot';
  #die ("func = $func");
  $topNav        = $template->getSection('TopNav');
  $leftNav       = $template->getSection('LeftNav');
  $main          = $template->getSection('Main');
  $topNavLinks   = makeLinks('top', $topLinks, 12);
  $navHeader     = $template->getSection('NavHeader');
  $leftNavLinks  = makeLinks('left', $leftLinks, 12);
  $FooterInfo    = getFooter();
  $errMsgClass   = (!empty($msg)) ? "ShowError" : "HideError";
  $errMsgStyle   = $template->getSection($errMsgClass);
  $noLeftNav     = '';
  $noTopNav      = '';
  $noRightNav    = $template->getSection('NoRightNav');
  $headerTitle   = 'Actions:';
  $pageTitle     = 'My-Program O - Bot Personality';
  #$mainContent   = ($func != 'updateBot') ? $func() : '';
  $mainContent   = "main content";
  #$msg           = "function = $func";
  switch ($func) {
    case 'updateBot':
    $msg = $func();
    $mainContent = getBot();
    break;
    default:
    $mainContent = $func();
    #$msg = "function = $func";
  }
/*
*/
  #$mainContent   = "test... func = $func";
  $mainTitle     = 'Bot Personality Settings for '.$bot_name;
  if ($func == 'updateBot' or $func == 'addBotPersonaity') {
    $msg = updateBot();
    include('main.php');
  }
function getBot() {
  #die('entered function.');
  global $dbn;
  $dbconn = db_open();
  $formCell  = '                <td><label for="[row_label]"><span class="label">[row_label]:</span></label> <span class="formw"><input name="[row_label]" id="[row_label]" value="[row_value]" /></span></td>
';
  $blankCell ='                <td style="text-align: center"><label for="newEntryName[cid]"><span class="label">New Entry Name: <input name="newEntryName[cid]" id="newEntryName[cid]" style="width: 98%" /></label></span>&nbsp;<span class="formw"><label for="newEntryValue[cid]" style="float: left; padding-left: 3px;">New Entry Value: </label><input name="newEntryValue[cid]" id="newEntryValue[cid]" /></span></td>
';
  $startDiv = '      <td>' . "\n        ";
  $endDiv = "\n      </td>\n      <br />\n";
  $inputs="";
  $row_class = 'row fm-opt';
  $bot_name = $_SESSION['poadmin']['bot_name'];
  $bot_id = (isset($_SESSION['poadmin']['bot_id'])) ? $_SESSION['poadmin']['bot_id'] : 0;
  //get the current bot's personality table from the db
  $sql = "SELECT * FROM `botpersonality` where  bot = $bot_id";
  #die ("SQL = $sql<br />db name = $dbn\n");
  $result = mysql_query($sql,$dbconn)or $msg .= SQL_Error(mysql_errno());
  if ($result) {
  $rowCount = mysql_num_rows($result);
  if ($rowCount > 0) {
    $left = true;
    $colCount = 0;
    while($row = mysql_fetch_assoc($result)) {
      $rid = $row['id'];
      $label = $row['name'];
      $value = stripslashes_deep($row['value']);
      $tmpRow = str_replace('[row_class]', $row_class, $formCell);
      $tmpRow = str_replace('[row_id]', $rid, $tmpRow);
      $tmpRow = str_replace('[row_label]', $label, $tmpRow);
      $tmpRow = str_replace('[row_value]', $value, $tmpRow);
      $inputs .= $tmpRow;
      $colCount++;
      if ($colCount >=3) {
        $inputs .= '              </tr>
              <tr>';
        $colCount = 0;
      }
    }
    $inputs .= "<!-- colCount = $colCount -->\n";
    if (($colCount > 0) and ($colCount < 3)) {
      for ($n = 0; $n < (3 - $colCount); $n++) {
        $addCell = str_replace('[cid]',"[$n]", $blankCell);
        $inputs .= $addCell;
      }
    }
    mysql_close($dbconn);
    $action = 'Update Data';
    $func   = 'updateBot';
  }
  else {
    $inputs = newForm();
    $action = 'Add New Data';
    $func   = 'addBotPersonality';
  }
  }
  if (empty($func)) $func = 'getBot';
  $form = <<<endForm2
          <form name="botpersonality" action="./?page=botpersonality" method="post">
            <table class="botForm">
              <tr>
$inputs
              </tr>
              <tr>
                <td colspan="3">
                  <input type="hidden" id="bot_id" name="bot_id" value="$bot_id">
                  <input type="hidden" id="func" name="func" value="$func">
                  <input type="submit" name="action" id="action" value="$action">
                </td>
              </tr>
            </table>
          </form>
  <!-- fieldset>
  </fieldset -->
endForm2;
  return $form;
}

function stripslashes_deep($value) {
  $newValue = stripslashes($value);
  while ($newValue != $value) {
    $value = $newValue;
    $newValue = stripslashes($value);
  }
  return $newValue;
}


function updateBot() {
  global $bot_id, $bot_name;
  $botId = (isset($_POST['bot_id'])) ? $_POST['bot_id'] : $bot_id;
  $dbconn = db_open();
  $msg = "";
  if (!empty($_POST['newEntryName'])) {
    $newEntryNames  = $_POST['newEntryName'];
    $newEntryValues = $_POST['newEntryValue'];
    $addSQL = "Insert into `botpersonality` (`id`, `bot`, `name`, `value`) values\n";
    $addSQLTemplate = "(null, $bot_id, '[key]', '[value]'),\n";
    foreach ($newEntryNames as $index => $key) {
      $value = $newEntryValues[$index];
      if (empty($value)) continue;
      $tmpSQL = str_replace('[key]', $key, $addSQLTemplate);
      $tmpSQL = str_replace('[value]', $value, $tmpSQL);
      $addSQL .= $tmpSQL;
    }
    $addSQL = rtrim($addSQL,",\n");
    $result = mysql_query($addSQL,$dbconn) or die('You have a SQL error on line '. __LINE__ . ' of ' . __FILE__ . '. Error message is: ' . mysql_error() . ".<br />SQL:<br /><pre>\n$addSQL\n<br />\n</pre>\n");
    if(!$result) {
      $msg = 'Error updating bot personality.';
    }
    elseif($msg == "") {
      $msg = 'Bot personality added.';
    }
  }

  $updateSQL = "UPDATE `botpersonality` SET `value` = CASE `name` \n";
  $sql = "SELECT * FROM `botpersonality` where bot = $botId;";
  $changes = array();
  $additions = array();
  $result = mysql_query($sql, $dbconn) or $msg .= SQL_Error(mysql_errno());
  if ($result) {
  while ($row = mysql_fetch_assoc($result)) {
    $id = $row['id'];
    $name = $row['name'];
    $value = $row['value'];
    $postVal = (isset($_POST[$name])) ? $_POST[$name] : '';
    if (!empty($postVal)) {
       if ($postVal != $value){
        $changes[$id] = mysql_escape_string(stripslashes_deep($postVal));
        $additions[$id] = $name;
       }
    }
  }
  }
  if (!empty($additions)) {
    $changesText = implode(',', array_keys($changes));
    foreach ($changes as $id => $value) {
      $name = $additions[$id];
      $updateSQL .= sprintf("WHEN '%s' THEN '%s' \n", $name, $value);
    }
    $updateSQL .= "END WHERE `id` IN ($changesText);";
    $saveSQL = str_replace("\n", "\r\n", $updateSQL);
    $result = mysql_query($updateSQL, $dbconn) or die('You have a SQL error on line '. __LINE__ . ' of ' . __FILE__ . '. Error message is: ' . mysql_error() . ".<br />SQL:<br /><pre>\n$updateSQL\n<br />\n</pre>\n");
    if (!$result) $msg = 'Error updating bot.';
    $msg = (empty($msg)) ? 'Bot personality updated.' : $msg;
  }
  else $msg = 'Something';
  mysql_close($dbconn);
  return $msg;
}

function addBotPersonality() {
  $dbconn = db_open();
/*
  $postVars = print_r($_POST, true);
  die ("Post vars:<pre>\n$postVars\n</pre>");
*/
  $bot_id = $_POST['bot_id'];
  $sql = "Insert into `botpersonality` (`id`, `bot`, `name`, `value`) values\n";
  $sql2 = "(null, $bot_id, '[key]', '[value]'),\n";
  $msg = "";
  $newEntryNames = (isset($_POST['newEntryName'])) ? $_POST['newEntryName'] : '';
  $newEntryValues = (isset($_POST['newEntryValue'])) ? $_POST['newEntryValue'] : '';
  if (!empty($newEntryNames)) {
    foreach ($newEntryNames as $index => $key) {
      $value = $newEntryValues[$index];
      if (!empty($value)) {
        $tmpSQL = str_replace('[key]', $key, $sql2);
        $tmpSQL = str_replace('[value]', $value, $tmpSQL);
        $sql .= $tmpSQL;
      }
    }
  }
  $skipKeys = array('bot_id', 'action', 'func', 'newEntryName', 'newEntryValue');
  foreach($_POST as $key => $value) {
    if(!in_array($key, $skipKeys)) {
      if($value=="")  continue;
      if (is_array($value)) {
        foreach ($value as $index => $fieldValue) {
          $field = $key[$fieldValue];
          $fieldValue = mysql_escape_string(trim($fieldValue));
          $tmpSQL = str_replace('[key]', $field, $sql2);
          $tmpSQL = str_replace('[value]', $fieldValue, $tmpSQL);
          $sql .= $tmpSQL;
        }
        continue;
      }
      else {
        $value = mysql_escape_string(trim($value));
        $tmpSQL = str_replace('[key]', $key, $sql2);
        $tmpSQL = str_replace('[value]', $value, $tmpSQL);
        $sql .= $tmpSQL;
      }
    }
  }
  $sql = rtrim($sql,",\n");
  $result = mysql_query($sql,$dbconn) or die('You have a SQL error on line '. __LINE__ . ' of ' . __FILE__ . '. Error message is: ' . mysql_error() . ".<br />SQL:<br /><pre>\n$sql\n<br />\n</pre>\n");
  if(!$result) {
    $msg = 'Error updating bot personality.';
  }
  elseif($msg == "") {
    $msg = 'Bot personality added!';
  }
  mysql_close($dbconn);
  return $msg;
}

  function newForm() {
    return <<<endForm
  <table class="botForm">
    <tr>
      <td><label for="feelings"><span class="label">feelings:</span></label> <span class="formw"><input name="feelings" id="feelings" value="" /></span></td>
      <td><label for="emotions"><span class="label">emotions:</span></label> <span class="formw"><input name="emotions" id="emotions" value="" /></span></td>
      <td><label for="ethics"><span class="label">ethics:</span></label> <span class="formw"><input name="ethics" id="ethics" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="orientation"><span class="label">orientation:</span></label> <span class="formw"><input name="orientation" id="orientation" value="" /></span></td>
      <td><label for="etype"><span class="label">etype:</span></label> <span class="formw"><input name="etype" id="etype" value="" /></span></td>
      <td><label for="baseballteam"><span class="label">baseballteam:</span></label> <span class="formw"><input name="baseballteam" id="baseballteam" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="build"><span class="label">build:</span></label> <span class="formw"><input name="build" id="build" value="" /></span></td>
      <td><label for="footballteam"><span class="label">footballteam:</span></label> <span class="formw"><input name="footballteam" id="footballteam" value="" /></span></td>
      <td><label for="hockeyteam"><span class="label">hockeyteam:</span></label> <span class="formw"><input name="hockeyteam" id="hockeyteam" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="vocabulary"><span class="label">vocabulary:</span></label> <span class="formw"><input name="vocabulary" id="vocabulary" value="" /></span></td>
      <td><label for="age"><span class="label">age:</span></label> <span class="formw"><input name="age" id="age" value="" /></span></td>
      <td><label for="celebrities"><span class="label">celebrities:</span></label> <span class="formw"><input name="celebrities" id="celebrities" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="celebrity"><span class="label">celebrity:</span></label> <span class="formw"><input name="celebrity" id="celebrity" value="" /></span></td>
      <td><label for="favoriteactress"><span class="label">favoriteactress:</span></label> <span class="formw"><input name="favoriteactress" id="favoriteactress" value="" /></span></td>
      <td><label for="favoriteartist"><span class="label">favoriteartist:</span></label> <span class="formw"><input name="favoriteartist" id="favoriteartist" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="favoritesport"><span class="label">favoritesport:</span></label> <span class="formw"><input name="favoritesport" id="favoritesport" value="" /></span></td>
      <td><label for="favoriteauthor"><span class="label">favoriteauthor:</span></label> <span class="formw"><input name="favoriteauthor" id="favoriteauthor" value="" /></span></td>
      <td><label for="language"><span class="label">language:</span></label> <span class="formw"><input name="language" id="language" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="website"><span class="label">website:</span></label> <span class="formw"><input name="website" id="website" value="" /></span></td>
      <td><label for="friend"><span class="label">friend:</span></label> <span class="formw"><input name="friend" id="friend" value="" /></span></td>
      <td><label for="version"><span class="label">version:</span></label> <span class="formw"><input name="version" id="version" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="class"><span class="label">class:</span></label> <span class="formw"><input name="class" id="class" value="" /></span></td>
      <td><label for="favoritesong"><span class="label">favoritesong:</span></label> <span class="formw"><input name="favoritesong" id="favoritesong" value="" /></span></td>
      <td><label for="kingdom"><span class="label">kingdom:</span></label> <span class="formw"><input name="kingdom" id="kingdom" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="nationality"><span class="label">nationality:</span></label> <span class="formw"><input name="nationality" id="nationality" value="" /></span></td>
      <td><label for="favoriteactor"><span class="label">favoriteactor:</span></label> <span class="formw"><input name="favoriteactor" id="favoriteactor" value="" /></span></td>
      <td><label for="family"><span class="label">family:</span></label> <span class="formw"><input name="family" id="family" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="religion"><span class="label">religion:</span></label> <span class="formw"><input name="religion" id="religion" value="" /></span></td>
      <td><label for="president"><span class="label">president:</span></label> <span class="formw"><input name="president" id="president" value="" /></span></td>
      <td><label for="party"><span class="label">party:</span></label> <span class="formw"><input name="party" id="party" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="order"><span class="label">order:</span></label> <span class="formw"><input name="order" id="order" value="" /></span></td>
      <td><label for="size"><span class="label">size:</span></label> <span class="formw"><input name="size" id="size" value="" /></span></td>
      <td><label for="species"><span class="label">species:</span></label> <span class="formw"><input name="species" id="species" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="botmaster"><span class="label">botmaster:</span></label> <span class="formw"><input name="botmaster" id="botmaster" value="" /></span></td>
      <td><label for="phylum"><span class="label">phylum:</span></label> <span class="formw"><input name="phylum" id="phylum" value="" /></span></td>
      <td><label for="genus"><span class="label">genus:</span></label> <span class="formw"><input name="genus" id="genus" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="msagent"><span class="label">msagent:</span></label> <span class="formw"><input name="msagent" id="msagent" value="" /></span></td>
      <td><label for="email"><span class="label">email:</span></label> <span class="formw"><input name="email" id="email" value="" /></span></td>
      <td><label for="name"><span class="label">name:</span></label> <span class="formw"><input name="name" id="name" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="gender"><span class="label">gender:</span></label> <span class="formw"><input name="gender" id="gender" value="" /></span></td>
      <td><label for="master"><span class="label">master:</span></label> <span class="formw"><input name="master" id="master" value="" /></span></td>
      <td><label for="birthday"><span class="label">birthday:</span></label> <span class="formw"><input name="birthday" id="birthday" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="birthplace"><span class="label">birthplace:</span></label> <span class="formw"><input name="birthplace" id="birthplace" value="" /></span></td>
      <td><label for="boyfriend"><span class="label">boyfriend:</span></label> <span class="formw"><input name="boyfriend" id="boyfriend" value="" /></span></td>
      <td><label for="favoritebook"><span class="label">favoritebook:</span></label> <span class="formw"><input name="favoritebook" id="favoritebook" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="favoriteband"><span class="label">favoriteband:</span></label> <span class="formw"><input name="favoriteband" id="favoriteband" value="" /></span></td>
      <td><label for="favoritecolor"><span class="label">favoritecolor:</span></label> <span class="formw"><input name="favoritecolor" id="favoritecolor" value="" /></span></td>
      <td><label for="favoritefood"><span class="label">favoritefood:</span></label> <span class="formw"><input name="favoritefood" id="favoritefood" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="favoritemovie"><span class="label">favoritemovie:</span></label> <span class="formw"><input name="favoritemovie" id="favoritemovie" value="" /></span></td>
      <td><label for="forfun"><span class="label">forfun:</span></label> <span class="formw"><input name="forfun" id="forfun" value="" /></span></td>
      <td><label for="friends"><span class="label">friends:</span></label> <span class="formw"><input name="friends" id="friends" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="girlfriend"><span class="label">girlfriend:</span></label> <span class="formw"><input name="girlfriend" id="girlfriend" value="" /></span></td>
      <td><label for="kindmusic"><span class="label">kindmusic:</span></label> <span class="formw"><input name="kindmusic" id="kindmusic" value="" /></span></td>
      <td><label for="location"><span class="label">location:</span></label> <span class="formw"><input name="location" id="location" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="looklike"><span class="label">looklike:</span></label> <span class="formw"><input name="looklike" id="looklike" value="" /></span></td>
      <td><label for="question"><span class="label">question:</span></label> <span class="formw"><input name="question" id="question" value="" /></span></td>
      <td><label for="sign"><span class="label">sign:</span></label> <span class="formw"><input name="sign" id="sign" value="" /></span></td>
    </tr>
    <tr>
      <td><label for="talkabout"><span class="label">talkabout:</span></label> <span class="formw"><input name="talkabout" id="talkabout" value="" /></span></td>
      <td><label for="wear"><span class="label">wear:</span></label> <span class="formw"><input name="wear" id="wear" value="" /></span></td>
      <td><label for="loves"><span class="label">loves:</span></label> <span class="formw"><input name="loves" id="loves" value="" /></span></td>
    </tr>
    <tr>
      <td style="text-align: center"><label for="newEntryName[0]"><span class="label">New Entry Name: <input name="newEntryName[0]" id="newEntryName[0]" style="width: 98%" /></label></span>&nbsp;<span class="formw"><label for="newEntryValue[0]" style="float: left; padding-left: 3px;">New Entry Value: </label><input name="newEntryValue[0]" id="newEntryValue[0]" /></span></td>
      <td style="text-align: center"><label for="newEntryName[1]"><span class="label">New Entry Name: <input name="newEntryName[1]" id="newEntryName[1]" style="width: 98%" /></label></span>&nbsp;<span class="formw"><label for="newEntryValue[1]" style="float: left; padding-left: 3px;">New Entry Value: </label><input name="newEntryValue[1]" id="newEntryValue[1]" /></span></td>
      <td style="text-align: center"><label for="newEntryName[2]"><span class="label">New Entry Name: <input name="newEntryName[2]" id="newEntryName[2]" style="width: 98%" /></label></span>&nbsp;<span class="formw"><label for="newEntryValue[2]" style="float: left; padding-left: 3px;">New Entry Value: </label><input name="newEntryValue[2]" id="newEntryValue[2]" /></span></td>
    </tr>
  </table>
endForm;
  }
?>