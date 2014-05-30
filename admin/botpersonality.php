<?PHP
  /***************************************
    * http://www.program-o.com
    * PROGRAM O
    * Version: 2.4.1
    * FILE: botpersonality.php
    * AUTHOR: Elizabeth Perreau and Dave Morton
    * DATE: 05-26-2014
    * DETAILS: Displays predicate values for the current chatbot
    ***************************************/
  # set template section defaults
  # Build page sections
  # ordered here in the order that the page is constructed
  $post_vars = filter_input_array(INPUT_POST);
  $bot_name = (isset ($_SESSION['poadmin']['bot_name'])) ? $_SESSION['poadmin']['bot_name'] : 'unknown';
  $func = (isset ($post_vars['func'])) ? $post_vars['func'] : 'getBot';
  $topNav = $template->getSection('TopNav');
  $leftNav = $template->getSection('LeftNav');
  $main = $template->getSection('Main');
  $topNavLinks = makeLinks('top', $topLinks, 12);
  $navHeader = $template->getSection('NavHeader');
  $leftNavLinks = makeLinks('left', $leftLinks, 12);
  $FooterInfo = getFooter();
  $errMsgClass = (!empty ($msg)) ? "ShowError" : "HideError";
  $errMsgStyle = $template->getSection($errMsgClass);
  $noLeftNav = '';
  $noTopNav = '';
  $noRightNav = $template->getSection('NoRightNav');
  $headerTitle = 'Actions:';
  $pageTitle = 'My-Program O - Bot Personality';
  $mainContent = "main content";
  switch ($func)
  {
    case 'updateBot' :
    case 'addBotPersonality' :
      $msg = $func();
      $mainContent = getBot();
      break;
    default :
      $mainContent = $func();
  }
  $mainTitle = 'Bot Personality Settings for ' . $bot_name;

  function getBot()
  {
    global $dbn, $dbConn;
    $formCell =
'                <td>
                   <label for="[row_label]">
                     <span class="label">[row_label]:</span>
                     <span class="formw">
                       <input name="[row_label]" id="[row_label]" value="[row_value]" />
                     </span>
                   </label>
                 </td>
';
    $blankCell =
'                <td style="text-align: center">
                   <label for="newEntryName[cid]">
                     <span class="label">
                       New Entry Name: <input name="newEntryName[cid]" id="newEntryName[cid]" style="width: 98%" />
                     </span>
                   </label>&nbsp;
                   <label for="newEntryValue[cid]" style="float: left; padding-left: 3px;">
                     <span class="formw">New Entry Value: </span>
                     <input name="newEntryValue[cid]" id="newEntryValue[cid]" />
                   </label>
                 </td>
';
    $startDiv = '      <td>' . "\n        ";
    $endDiv = "\n      </td>\n      <br />\n";
    $inputs = "";
    $row_class = 'row fm-opt';
    $bot_name = $_SESSION['poadmin']['bot_name'];
    $bot_id = (isset ($_SESSION['poadmin']['bot_id'])) ? $_SESSION['poadmin']['bot_id'] :
              0;
    //get the current bot's personality table from the db
    $sql = "SELECT * FROM `botpersonality` where  `bot_id` = $bot_id";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $rows = $sth->fetchAll(PDO :: FETCH_ASSOC);
      $rowCount = count($rows);
      if ($rowCount > 0)
      {
        $left = true;
        $colCount = 0;
        foreach ($rows as $row)
        {
          $rid = $row['id'];
          $label = $row['name'];
          $value = stripslashes_deep($row['value']);
          $tmpRow = str_replace('[row_class]', $row_class, $formCell);
          $tmpRow = str_replace('[row_id]', $rid, $tmpRow);
          $tmpRow = str_replace('[row_label]', $label, $tmpRow);
          $tmpRow = str_replace('[row_value]', $value, $tmpRow);
          $inputs .= $tmpRow;
          $colCount++;
          if ($colCount >= 3)
          {
            $inputs .= '              </tr>
              <tr>' . PHP_EOL;
            $colCount = 0;
          }
        }
        $inputs .= "<!-- colCount = $colCount -->\n";
        if (($colCount > 0) and ($colCount < 3))
        {
          for ($n = 0; $n < (3 - $colCount); $n++)
          {
            $addCell = str_replace('[cid]', "[$n]", $blankCell);
            $inputs .= $addCell;
          }
        }
        $action = 'Update Data';
        $func = 'updateBot';
      }
      else
      {
        $inputs = newForm();
        $action = 'Add New Data';
        $func = 'addBotPersonality';
      }
    if (empty ($func))
      $func = 'getBot';
    $form = <<<endForm2
          <form name="botpersonality" action="index.php?page=botpersonality" method="post">
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

  function stripslashes_deep($value)
  {
    $newValue = stripslashes($value);
    while ($newValue != $value)
    {
      $value = $newValue;
      $newValue = stripslashes($value);
    }
    return $newValue;
  }

  function updateBot()
  {
    global $bot_id, $bot_name, $post_vars, $dbConn;
    $msg = "";
    if (!empty ($post_vars['newEntryName']))
    {
      $newEntryNames = $post_vars['newEntryName'];
      $newEntryValues = $post_vars['newEntryValue'];
      $original_sql = "Insert into `botpersonality` (`id`, `bot_id`, `name`, `value`) values\n";
      $sql = $original_sql;
      $sqlTemplate = "(null, $bot_id, '[key]', '[value]'),\n";
      foreach ($newEntryNames as $index => $key)
      {
        $value = $newEntryValues[$index];
        if (empty ($value)) continue;
        $tmpSQL = str_replace('[key]', $key, $sqlTemplate);
        $tmpSQL = str_replace('[value]', $value, $tmpSQL);
        $sql .= $tmpSQL;
      }
      if ($sql != $original_sql)
      {
        $sql = rtrim($sql, ",\n");
        $sth = $dbConn->prepare($sql);
        $sth->execute();
        $rowsAffected = $sth->rowCount();
        if ($rowsAffected > 0)
        {
          $msg = (empty ($msg)) ? "Bot personality added. \n" : $msg;
        }
        else
        {
          $msg = 'Error updating bot personality.';
        }
      }
    }
    $sql = "SELECT * FROM `botpersonality` where `bot_id` = $bot_id;";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $result = $sth->fetchAll();
    $rows = array();
    foreach ($result as $row)
    {
      $name = $row['name'];
      $value = $row['value'];
      $rows[$name] = array('id' => $row['id'], 'value' => $value);
    }
    $sql = '';
    $exclude = array('bot_id', 'func', 'action', 'newEntryName', 'newEntryValue');
    $values = '';
    foreach ($post_vars as $key => $value)
    {
      if (in_array($key, $exclude)) continue;
      if (!isset($rows[$key]))
      {
        $sql .= "Insert into `botpersonality` (`id`, `bot_id`, `name`, `value`) values (null, $bot_id, '$key', '$value';\n";
      }
      else
      {
        $oldValue = $rows[$key]['value'];
        if ($value != $oldValue)
        {
          $curId = $rows[$key]['id'];
          $sql .= "update `botpersonality` set `value` = '$value' where `id` = $curId;\n";
        }
      }
    }
    if (empty($sql)) return 'No changes found.';
    //exit("<pre>SQL:\n$sql\n</pre>");
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $affectedRows = $sth->rowCount();
    if ($affectedRows > 0) $msg = 'Bot Personality Updated.';
    else $msg = 'Something went wrong!';
    return $msg;
  }

  function addBotPersonality()
  {
    global $post_vars, $dbConn;
    $bot_id = $post_vars['bot_id'];
    $sql = "Insert into `botpersonality` (`id`, `bot_id`, `name`, `value`) values\n";
    $sql2 = "(null, $bot_id, '[key]', '[value]'),\n";
    $msg = "";
    $newEntryNames = (isset ($post_vars['newEntryName'])) ? $post_vars['newEntryName'] :
                     '';
    $newEntryValues = (isset ($post_vars['newEntryValue'])) ? $post_vars['newEntryValue']
                      : '';
    if (!empty ($newEntryNames))
    {
      foreach ($newEntryNames as $index => $key)
      {
        $value = $newEntryValues[$index];
        if (!empty ($value))
        {
          $tmpSQL = str_replace('[key]', $key, $sql2);
          $tmpSQL = str_replace('[value]', $value, $tmpSQL);
          $sql .= $tmpSQL;
        }
      }
    }
    $skipKeys = array('bot_id', 'action', 'func', 'newEntryName', 'newEntryValue');
    foreach ($post_vars as $key => $value)
    {
      if (!in_array($key, $skipKeys))
      {
        if (is_array($value))
        {
          foreach ($value as $index => $fieldValue)
          {
            $field = $key[$fieldValue];
            $fieldValue = trim($fieldValue);
            $tmpSQL = str_replace('[key]', $field, $sql2);
            $tmpSQL = str_replace('[value]', $fieldValue, $tmpSQL);
            $sql .= $tmpSQL;
          }
          continue;
        }
        else
        {
          $value = trim($value);
          $tmpSQL = str_replace('[key]', $key, $sql2);
          $tmpSQL = str_replace('[value]', $value, $tmpSQL);
          $sql .= $tmpSQL;
        }
      }
    }
    $sql = rtrim($sql, ",\n");
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $rowsAffected = $sth->rowCount();
    if ($rowsAffected > 0)
    {
      $msg = (empty ($msg)) ? "Bot personality added. \n" : $msg;
    }
    else
    {
      $msg = 'Error updating bot personality.';
    }
    return $msg;
  }

  function newForm()
  {
    $out = '                <table class="botForm">
                  <tr>
';
    $rowTemplate =
    '                    <td><label for="[field]"><span class="label">[uc_field]:</span></label> <span class="formw"><input name="[field]" id="[field]" value="" /></span></td>
';
    $tr = '                  </tr>
                  <tr>
';
    $blankTD = '                    <td>&nbsp;</td>
';
    $lastBit =
    '                  </tr>
                  <tr>
                    <td style="text-align: center"><label for="newEntryName[0]"><span class="label">New Entry Name: <input name="newEntryName[0]" id="newEntryName[0]" style="width: 98%" /></label></span>&nbsp;<span class="formw"><label for="newEntryValue[0]" style="float: left; padding-left: 3px;">New Entry Value: </label><input name="newEntryValue[0]" id="newEntryValue[0]" /></span></td>
                    <td style="text-align: center"><label for="newEntryName[1]"><span class="label">New Entry Name: <input name="newEntryName[1]" id="newEntryName[1]" style="width: 98%" /></label></span>&nbsp;<span class="formw"><label for="newEntryValue[1]" style="float: left; padding-left: 3px;">New Entry Value: </label><input name="newEntryValue[1]" id="newEntryValue[1]" /></span></td>
                    <td style="text-align: center"><label for="newEntryName[2]"><span class="label">New Entry Name: <input name="newEntryName[2]" id="newEntryName[2]" style="width: 98%" /></label></span>&nbsp;<span class="formw"><label for="newEntryValue[2]" style="float: left; padding-left: 3px;">New Entry Value: </label><input name="newEntryValue[2]" id="newEntryValue[2]" /></span></td>
                  </tr>
                </table>
';
    $fields = file(_CONF_PATH_ . 'default_botpersonality_fields.dat');
    $count = 0;
    foreach ($fields as $field)
    {
      $count++;
      $field = trim($field);
      $tmpRow = str_replace('[field]', $field, $rowTemplate);
      $tmpRow = str_replace('[uc_field]', ucfirst($field), $tmpRow);
      $out .= $tmpRow;
      if ($count % 3 == 0)
        $out .= $tr;
    }
    switch ($count % 3)
    {
      case 1 :
        $out .= $blankTD;
        break;
      case 2 :
        $out .= $blankTD . $blankTD;
    }
    $out .= $lastBit;
    return $out;
  }


function make_bot_predicates($bot_id)
{
  global $dbConn, $bot_name, $msg;

  $sql = <<<endSQL
INSERT INTO `botpersonality` VALUES
  (NULL,  $bot_id, 'age', ''),
  (NULL,  $bot_id, 'baseballteam', ''),
  (NULL,  $bot_id, 'birthday', ''),
  (NULL,  $bot_id, 'birthplace', ''),
  (NULL,  $bot_id, 'botmaster', ''),
  (NULL,  $bot_id, 'boyfriend', ''),
  (NULL,  $bot_id, 'build', ''),
  (NULL,  $bot_id, 'celebrities', ''),
  (NULL,  $bot_id, 'celebrity', ''),
  (NULL,  $bot_id, 'class', ''),
  (NULL,  $bot_id, 'email', ''),
  (NULL,  $bot_id, 'emotions', ''),
  (NULL,  $bot_id, 'ethics', ''),
  (NULL,  $bot_id, 'etype', ''),
  (NULL,  $bot_id, 'family', ''),
  (NULL,  $bot_id, 'favoriteactor', ''),
  (NULL,  $bot_id, 'favoriteactress', ''),
  (NULL,  $bot_id, 'favoriteartist', ''),
  (NULL,  $bot_id, 'favoriteauthor', ''),
  (NULL,  $bot_id, 'favoriteband', ''),
  (NULL,  $bot_id, 'favoritebook', ''),
  (NULL,  $bot_id, 'favoritecolor', ''),
  (NULL,  $bot_id, 'favoritefood', ''),
  (NULL,  $bot_id, 'favoritemovie', ''),
  (NULL,  $bot_id, 'favoritesong', ''),
  (NULL,  $bot_id, 'favoritesport', ''),
  (NULL,  $bot_id, 'feelings', ''),
  (NULL,  $bot_id, 'footballteam', ''),
  (NULL,  $bot_id, 'forfun', ''),
  (NULL,  $bot_id, 'friend', ''),
  (NULL,  $bot_id, 'friends', ''),
  (NULL,  $bot_id, 'gender', ''),
  (NULL,  $bot_id, 'genus', ''),
  (NULL,  $bot_id, 'girlfriend', ''),
  (NULL,  $bot_id, 'hockeyteam', ''),
  (NULL,  $bot_id, 'kindmusic', ''),
  (NULL,  $bot_id, 'kingdom', ''),
  (NULL,  $bot_id, 'language', ''),
  (NULL,  $bot_id, 'location', ''),
  (NULL,  $bot_id, 'looklike', ''),
  (NULL,  $bot_id, 'master', ''),
  (NULL,  $bot_id, 'msagent', ''),
  (NULL,  $bot_id, 'name', '$bot_name'),
  (NULL,  $bot_id, 'nationality', ''),
  (NULL,  $bot_id, 'order', ''),
  (NULL,  $bot_id, 'orientation', ''),
  (NULL,  $bot_id, 'party', ''),
  (NULL,  $bot_id, 'phylum', ''),
  (NULL,  $bot_id, 'president', ''),
  (NULL,  $bot_id, 'question', ''),
  (NULL,  $bot_id, 'religion', ''),
  (NULL,  $bot_id, 'sign', ''),
  (NULL,  $bot_id, 'size', ''),
  (NULL,  $bot_id, 'species', ''),
  (NULL,  $bot_id, 'talkabout', ''),
  (NULL,  $bot_id, 'version', ''),
  (NULL,  $bot_id, 'vocabulary', ''),
  (NULL,  $bot_id, 'wear', ''),
  (NULL,  $bot_id, 'website', '');
endSQL;

  $sth = $dbConn->prepare($sql);
  $sth->execute();
  $affectedRows = $sth->rowCount();
  if($affectedRows > 0)
  {
    $msg .= 'Please create the bots personality.';
  }
  else {
    $msg .= 'Unable to create the bots personality.';
  }
  return $msg;
}

