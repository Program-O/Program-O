<?PHP
//-----------------------------------------------------------------------------------------------
//My Program-O Version 2.0.1
//Program-O  chatbot admin area
//Written by Elizabeth Perreau and Dave Morton
//Aug 2011
//for more information and support please visit www.program-o.com
//-----------------------------------------------------------------------------------------------
// select_bots.php

$content ="";

/*
if((isset($_POST['action']))&&($_POST['action']=="clear"))
{
  $content = clearAIML();
}
elseif((isset($_POST['clearFile']))&&($_POST['clearFile'] != "null")){
  $content = clearAIMLByFileName($_POST['clearFile']);
}
else {}
*/

if((isset($_POST['action']))&&($_POST['action']=="clear")) {
  $content .= clearAIML();
}
elseif((isset($_POST['clearFile']))&&($_POST['clearFile'] != "null")) {
  $content .= clearAIMLByFileName($_POST['clearFile']);
}
else {
}
    $content .= renderMain();

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
    $pageTitle     = 'My-Program O - Clear AIML Categories';
    $mainContent   = $content;
    $mainTitle     = 'Clear AIML Categories';

  function replaceTags(&$content) {
    return $content;
  }

  function clearAIML() {
    global $dbn;
    $dbconn = db_open();

    $sql = 'truncate table aiml;';
    #return "SQL = $sql";
    $result = mysql_query($sql,$dbconn) or die(mysql_error());
    mysql_close($dbconn);
    $msg = "<strong>All AIML categories cleared!</strong><br />";
    return $msg;
  }

  function clearAIMLByFileName($filename) {
    global $dbn;
    $dbconn = db_open();
    $cleanedFilename = mysql_real_escape_string($filename, $dbconn);
    $sql = "delete from aiml where filename like '$cleanedFilename';";
    #return "SQL = $sql";
    $result = mysql_query($sql,$dbconn) or die(mysql_error());
    mysql_close($dbconn);
    $msg = "<br/><strong>AIML categories cleared for file $filename!</strong><br />";
    return $msg;
  }

  function getSelOpts() {
    global $dbn;
    $out = "                  <!-- Start Selectbox Options -->\n";
    $dbconn = db_open();
    $optionTemplate = "                  <option value=\"[val]\">[val]</option>\n";
    $sql = 'SELECT DISTINCT filename FROM aiml order by filename;';
    #return "SQL = $sql";
    $result = mysql_query($sql,$dbconn) or die(mysql_error());
    while ($row = mysql_fetch_assoc($result)) {
      if (empty($row['filename'])) {
        $curOption = "                  <option value=\"\">{No Filename entry}</option>\n";
      }
      else $curOption = str_replace('[val]', $row['filename'], $optionTemplate);
      $out .= $curOption;
    }
    mysql_close($dbconn);
    $out .= "                  <!-- End Selectbox Options -->\n";
    return $out;
  }

  function renderMain() {
    $selectOptions = getSelOpts();
    $content = <<<endForm
          Deleting AIML categories from the database is <strong>permanent</strong>!<br />
          This action <strong>CANNOT</strong> be undone!<br />
          <form name="clearForm" action="./?page=clear" method="POST">
          <table style="border: none;margin:10px;padding: 5px;">
            <tr>
              <td style="border:  none;padding: 12px;">
                <input type="radio" name="action" id="actionClearAll" value="clear">
                <label for="actionClearAll" style="width: 250px">Clear <strong>ALL</strong> AIML categories (Purge database)</label>
              </td>
            </tr>
            <tr>
              <td style="border:  none;padding: 12px;">
                <input type="radio" name="action" value="void" id="actionClearFile" checked="checked">
                <label for="actionClearFile" style="width: 210px; text-align: left">Clear categories from this AIML file: </label><br />
                <select name="clearFile" id="clearFile" size="1" style="margin: 14px;" onclick="document.getElementById('actionClearFile').checked = true" onchange="document.getElementById('actionClearFile').checked = true">
                  <option value="null" selected="selected">Choose a file</option>
$selectOptions
                </select>
              </td>
            </tr>
            <tr>
              <td style="border:  none;text-align: center;padding: 12px">
                <input type="submit" name="" value="Submit">
              </td>
            </tr>
          </table>
          </form>
endForm;

    return $content;
  }
?>