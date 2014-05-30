
<?PHP
  $content = '';
  $status = '';
  $upperScripts = <<<endScript

    <script type="text/javascript">
<!--
      function showMe() {
        var sh = document.getElementById('showHelp');
        var tf = document.getElementById('downloadForm');
        sh.style.display = 'block';
        tf.style.display = 'none';
      }
      function hideMe() {
        var sh = document.getElementById('showHelp');
        var tf = document.getElementById('downloadForm');
        sh.style.display = 'none';
        tf.style.display = 'block';
      }
      function showHide() {
        var display = document.getElementById('showHelp').style.display;
        switch (display) {
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
  $get_vars = filter_input_array(INPUT_GET);
  $msg = (isset ($post_vars['msg'])) ? $post_vars['msg'] : '';
  if ((isset ($post_vars['action'])) && ($post_vars['action'] == "AIML"))
  {
    $msg .= getAIMLByFileName($post_vars['getFile']);
  }
  elseif ((isset ($post_vars['action'])) && ($post_vars['action'] == "SQL"))
  {
    $msg .= getSQLByFileName($post_vars['getFile']);
  }
  elseif (isset ($get_vars['file']))
  {
    $msg .= serveFile($get_vars['file'], $msg);
  }
  else
  {
  }
  $content .= renderMain();
  $showHelp = $template->getSection('DownloadShowHelp');
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
  $pageTitle = "My-Program O - Download AIML files";
  $mainContent = $content;
  $mainTitle = "Download AIML files for the bot named  $bot_name [helpLink]";
  $mainContent = str_replace('[showHelp]', $showHelp, $mainContent);
  $mainContent = str_replace('[status]', $status, $mainContent);
  $mainTitle = str_replace('[helpLink]', $template->getSection('HelpLink'), $mainTitle);

  function replaceTags(& $content)
  {
    return $content;
  }

  function getAIMLByFileName($filename)
  {
    if ($filename == 'null') return "You need to select a file to download.";
    global $dbn, $botmaster_name, $charset, $dbConn;
    $bmnLen = strlen($botmaster_name) - 2;
    $bmnSearch = str_pad('[bm_name]', $bmnLen);
    $categoryTemplate =
    '<category><pattern>[pattern]</pattern>[that]<template>[template]</template></category>';
    $cleanedFilename = $filename;
    $fileNameSearch = '[fileName]';
    $cfnLen = strlen($cleanedFilename);
    $fileNameSearch = str_pad($fileNameSearch, $cfnLen);
    $topicArray = array();
    $curPath = dirname(__FILE__);
    chdir($curPath);
    $fileContent = file_get_contents('./AIML_Header.dat');
    $fileContent = str_replace('[year]', date('Y'), $fileContent);
    $fileContent = str_replace('[charset]', $charset, $fileContent);
    $fileContent = str_replace($bmnSearch, $botmaster_name, $fileContent);
    $curDate = date('m-d-Y', time());
    $cdLen = strlen($curDate);
    $curDateSearch = str_pad('[curDate]', $cdLen);
    $fileContent = str_replace($curDateSearch, $curDate, $fileContent);
    $fileContent = str_replace($fileNameSearch, $cleanedFilename, $fileContent);
    $sql = "select distinct topic from aiml where filename like '$cleanedFilename';";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $result = $sth->fetchAll();
    foreach ($result as $row)
    {
      $topicArray[] = $row['topic'];
    }
    foreach ($topicArray as $topic)
    {
      if (!empty ($topic))
        $fileContent .= "<topic name=\"$topic\">\n";
      $sql =
      "select pattern, thatpattern, template from aiml where topic like '$topic' and filename like '$cleanedFilename';";
      $sth = $dbConn->prepare($sql);
      $sth->execute();
      $result = $sth->fetchAll();
      foreach ($result as $row)
      {
        $pattern = (IS_MB_ENABLED) ? mb_strtoupper($row['pattern']) : strtoupper($row[
                   'pattern']);
        $template = str_replace("\r\n", '', $row['template']);
        $template = str_replace("\n", '', $row['template']);
        $newLine = str_replace('[pattern]', $pattern, $categoryTemplate);
        $newLine = str_replace('[template]', $template, $newLine);
        $that = (!empty ($row['thatpattern'])) ? '<that>' . $row['thatpattern'] .
                '</that>' : '';
        $newLine = str_replace('[that]', $that, $newLine);
        $fileContent .= "$newLine\n";
      }
      if (!empty ($topic))
        $fileContent .= "</topic>\n";
    }
    $fileContent .= "\r\n</aiml>\r\n";
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML(trim($fileContent));
    $fileContent = $dom->saveXML();
    $outFile = ltrim($fileContent, "\n\r\n");
    $outFile = mb_convert_encoding($outFile, 'UTF-8');
    $x = file_put_contents("./downloads/$cleanedFilename", trim($outFile));
    $msg =
    "Your file, <strong>$filename</strong>, is being prepaired. If it doesn't start, please <a href=\"file.php?file=$filename&send_file=yes\">Click Here</a>.<br />\n";
    return serveFile($filename, $msg);
  }

  function getSQLByFileName($filename)
  {
    global $dbn, $botmaster_name, $dbh, $dbConn;
    $curPath = dirname(__FILE__);
    chdir($curPath);
    $dbFilename = $filename;
    $filename = str_ireplace('.aiml', '.sql', $filename);
    $categoryTemplate =
    "    ([id],[bot_id],'[aiml]','[pattern]','[thatpattern]','[template]','[topic]','[filename]'),";
    $phpVer = phpversion();
    $cleanedFilename = $dbFilename;
    $topicArray = array();
    $sql = "select * from aiml where filename like '$cleanedFilename' order by id asc;";
    $fileContent = file_get_contents('SQL_Header.dat');
    $fileContent = str_replace('[botmaster_name]', $botmaster_name, $fileContent);
    $fileContent = str_replace('[host]', $dbh, $fileContent);
    $fileContent = str_replace('[dbn]', $dbn, $fileContent);
    $fileContent = str_replace('[sql]', $sql, $fileContent);
    $fileContent = str_replace('[phpVer]', $phpVer, $fileContent);
    $curDate = date('m-d-Y h:j:s A', time());
    $fileContent = str_replace('[curDate]', $curDate, $fileContent);
    $fileContent = str_replace('[fileName]', $cleanedFilename, $fileContent);
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $result = $sth->fetchAll();
    foreach ($result as $row)
    {
      $aiml = str_replace("\r\n", '', $row['aiml']);
      $aiml = str_replace("\n", '', $aiml);
      $template = str_replace("\r\n", '', $row['template']);
      $template = str_replace("\n", '', $template);
      $newLine = str_replace('[id]', $row['id'], $categoryTemplate);
      $newLine = str_replace('[bot_id]', $row['bot_id'], $newLine);
      $newLine = str_replace('[aiml]', $aiml, $newLine);
      $newLine = str_replace('[pattern]', $row['pattern'], $newLine);
      $newLine = str_replace('[thatpattern]', $row['thatpattern'], $newLine);
      $newLine = str_replace('[template]', $template, $newLine);
      $newLine = str_replace('[topic]', $row['topic'], $newLine);
      $newLine = str_replace('[filename]', $row['filename'], $newLine);
      $fileContent .= "$newLine\r\n";
    }
    $fileContent = trim($fileContent, ",\r\n");
    $fileContent .= "\n";
    $x = file_put_contents("./downloads/$filename", trim($fileContent));
    $msg =
    "Your file, <strong>$filename</strong>, is being prepaired. If it doesn't start, please <a href=\"file.php?file=$filename&send_file=yes\">Click Here</a>.<br />\n";
    return serveFile($filename, $msg);
  }

  function getSelOpts()
  {
    global $dbn, $bot_id, $msg, $dbConn;
    $out = "                  <!-- Start Selectbox Options -->\n";
    $optionTemplate = "                  <option value=\"[val]\">[val]</option>\n";
    $sql =
    "SELECT DISTINCT filename FROM `aiml` where `bot_id` = $bot_id order by `filename`;";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $result = $sth->fetchAll();
    if (count($result) == 0) $msg = "This bot has no AIML categories. Please select another bot.";
    foreach ($result as $row)
    {
      if (empty ($row['filename']))
      {
        $curOption = "                  <option value=\"\">{No Filename entry}</option>\n";
      }
      else
        $curOption = str_replace('[val]', $row['filename'], $optionTemplate);
      $out .= $curOption;
    }
    $out .= "                  <!-- End Selectbox Options -->\n";
    return $out;
  }

  function renderMain()
  {
    $selectOptions = getSelOpts();
    $content = <<<endForm
          <div id="downloadForm" class="fullWidth noBorder">
          Please select the AIML file you wish to download from the list below.<br />
          <form name="getFileForm" action="index.php?page=download" method="POST">
          <table class="formTable">
            <tr>
              <td>
                <select name="getFile" id="getFile" size="1" style="margin: 14px;">
                  <option value="null" selected="selected">Choose a file</option>
$selectOptions
                </select>
              </td>
              <td>
                <input type="submit" name="" value="Submit">
              </td>
            </tr>
            <tr>
              <td>
                <input type="radio" name="action" id="actionGetFileAIML" checked="checked" value="AIML">
                <label for="actionGetFileAIML" style="width: 250px">Download file as AIML</label>
              </td>
              <td>
                <input type="radio" name="action" id="actionGetFileSQL" value="SQL">
                <label for="actionGetFileSQL" style="width: 250px">Download file as SQL</label>
              </td>
            </tr>
          </table>
          </form>
          <div id="status">[status]</div>
          </div>
[showHelp]
endForm;

        return $content;
  }

  function serveFile($req_file, & $msg = '')
  {
    global $get_vars, $charset;
    $fileserver_path = dirname(__FILE__) . '/downloads';
    $whoami = basename(__FILE__);
    $myMsg = urlencode($msg);
    if (!preg_match("/^[a-zA-Z0-9._-]+$/", $req_file, $matches))
    {
      return "I don't know what you were trying to do, nor do I care. Just stop it.";
    }
    if ($req_file == $whoami)
    {
      return "I don't know what you were trying to do, nor do I care. Just stop it.";
    }
    if (!file_exists("$fileserver_path/$req_file"))
    {
      return "File <strong>$req_file</strong> doesn't exist.";
    }
    if (empty ($get_vars['send_file']))
    {
      header("Refresh: 5; url=$whoami?file=$req_file&send_file=yes&msg=$myMsg");
    }
    else
    {
      header('Content-Description: File Transfer');
      header('Content-Type: application/force-download; charset="' . $charset . '"');
      header('Content-Length: ' . filesize("$fileserver_path/$req_file"));
      header('Content-Disposition: attachment; filename=' . $req_file);
      print file_get_contents("$fileserver_path/$req_file");
      exit;
    }
    return $msg;
  }

?>
