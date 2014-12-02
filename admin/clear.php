<?PHP
//-----------------------------------------------------------------------------------------------
//My Program-O Version: 2.4.4
//Program-O  chatbot admin area
//Written by Elizabeth Perreau and Dave Morton
//DATE: MAY 17TH 2014
//for more information and support please visit www.program-o.com
//-----------------------------------------------------------------------------------------------
// select_bots.php

$content ="";

  $upperScripts = <<<endScript

    <script type="text/javascript">
<!--
      function showMe()
{


        var sh = document.getElementById('showHelp');
        var tf = document.getElementById('clearForm');
        sh.style.display = 'block';
        tf.style.display = 'none';
      }
      function hideMe()
{


        var sh = document.getElementById('showHelp');
        var tf = document.getElementById('clearForm');
        sh.style.display = 'none';
        tf.style.display = 'block';
      }
      function showHide()
{


        var display = document.getElementById('showHelp').style.display;
        switch (display)
{


          case '':
          case 'none':
            return showMe();
            break;
          case 'block':
            return hideMe();
            break;
          default:
            alert('display = ' + display);
        }
      }
//-->
    </script>
endScript;
$post_vars = filter_input_array(INPUT_POST);


if((isset($post_vars['action']))&&($post_vars['action']=="clear"))
{


  $content .= clearAIML();
}
elseif((isset($post_vars['clearFile']))&&($post_vars['clearFile'] != "null"))
{


  $content .= clearAIMLByFileName($post_vars['clearFile']);
}
else {
}
    $content .= buildMain();

    $topNav        = $template->getSection('TopNav');
    $leftNav       = $template->getSection('LeftNav');
    $main          = $template->getSection('Main');
    
    $navHeader     = $template->getSection('NavHeader');
    
    $FooterInfo    = getFooter();
    $errMsgClass   = (!empty($msg)) ? "ShowError" : "HideError";
    $errMsgStyle   = $template->getSection($errMsgClass);
    $noLeftNav     = '';
    $noTopNav      = '';
    $noRightNav    = $template->getSection('NoRightNav');
    $headerTitle   = 'Actions:';
    $pageTitle     = "My-Program O - Clear AIML Categories";
    $mainContent   = $content;
    $mainTitle     = "Clear AIML Categories for the bot named $bot_name [helpLink]";
    $showHelp = $template->getSection('ClearShowHelp');

    $mainTitle     = str_replace('[helpLink]', $template->getSection('HelpLink'), $mainTitle);
    $mainContent   = str_replace('[showHelp]', $showHelp, $mainContent);
    $mainContent     = str_replace('[upperScripts]', $upperScripts, $mainContent);

  /**
   * Function clearAIML
   *
   *
   * @return string
   */
  function clearAIML()
{


    global $dbn, $bot_id, $bot_name, $dbConn;

    $sql = "DELETE FROM `aiml` WHERE `bot_id` = $bot_id;";
    #return "SQL = $sql";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $affectedRows = $sth->rowCount();
    $msg = "<strong>All AIML categories cleared for $bot_name!</strong><br />";
    return $msg;
  }

  /**
   * Function clearAIMLByFileName
   *
   * * @param $filename
   * @return string
   */
  function clearAIMLByFileName($filename)
{


    global $dbn, $bot_id, $dbConn;
    $sql = "delete from `aiml` where `filename` like '$filename' and `bot_id` = $bot_id;";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $affectedRows = $sth->rowCount();
    $msg = "<br/><strong>AIML categories cleared for file $filename!</strong><br />";
    return $msg;
  }

  /**
   * Function buildSelOpts
   *
   *
   * @return string
   */
  function buildSelOpts()
  {
    global $dbn, $bot_id, $msg, $dbConn;
    $out = "                  <!-- Start Selectbox Options -->\n";
    $optionTemplate = "                  <option value=\"[val]\">[val]</option>\n";
    $sql = "SELECT DISTINCT filename FROM `aiml` where `bot_id` = $bot_id order by `filename`;";
    $result = db_fetchAll($sql, null, __FILE__, __FUNCTION__, __LINE__);
/*
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $result = $sth->fet chAll();
*/
    $numRows = count($result);
    if ($numRows == 0) $msg = "This bot has no AIML categories to clear.";
    foreach ($result as $row)
    {
      if (empty($row['filename']))
      {
        $curOption = "                  <option value=\"\">{No Filename entry}</option>\n";
      }
      else $curOption = str_replace('[val]', $row['filename'], $optionTemplate);
      $out .= $curOption;
    }
    $out .= "                  <!-- End Selectbox Options -->\n";
    return $out;
  }

  /**
   * Function buildMain
   *
   *
   * @return string
   */
  function buildMain()
{


    $selectOptions = buildSelOpts();
    $content = <<<endForm
          Deleting AIML categories from the database is <strong>permanent</strong>!
          This action <strong>CANNOT</strong> be undone!<br />
          <div id="clearForm">
          <form name="clearForm" action="index.php?page=clear" method="POST" onsubmit="return verify()">
          <table class="formTable">
            <tr>
              <td>
                <input type="radio" name="action" value="void" id="actionClearFile" checked="checked">
                <label for="actionClearFile" style="width: 210px; text-align: left">Clear categories from this AIML file: </label><br />
              </td>
              <td>
                <input type="radio" name="action" id="actionClearAll" value="clear">
                <label for="actionClearAll" style="width: 250px">Clear <strong>ALL</strong> AIML categories (Purge database)</label>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <select name="clearFile" id="clearFile" size="1" style="margin: 14px;" onclick="document.getElementById('actionClearFile').checked = true" onchange="document.getElementById('actionClearFile').checked = true">
                  <option value="null" selected="selected">Choose a file</option>
$selectOptions
                </select>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <input type="submit" name="" value="Submit">
              </td>
            </tr>
          </table>
          </form>
          </div>
[showHelp]
          <script type="text/javascript">
            function verify()
{


              var fn = document.getElementById('clearFile').value;
              var clearAll = document.getElementById('actionClearAll').checked;
              if (fn == 'null' && clearAll === false) return false;
              if (clearAll) fn = 'repository for all files';
              return confirm('This will delete all categories from the AIML file ' + fn + '! This cannot be undone! Are you sure you want to do this?');
            }
          </script>
endForm;

    return $content;
  }
?>