<?php

  //-----------------------------------------------------------------------------------------------
  //My Program-O Version: 2.3.1
  //Program-O  chatbot admin area
  //Written by Elizabeth Perreau and Dave Morton
  //Aug 2011
  //for more information and support please visit www.program-o.com
  //-----------------------------------------------------------------------------------------------
  // upload.php
  ini_set('memory_limit', '128M');
  ini_set('max_execution_time', '0');
  ini_set('display_errors', false);
  ini_set('log_errors', true);
  libxml_use_internal_errors(true);
  $msg = (array_key_exists('aimlfile', $_FILES)) ? processUpload() : '';
  $upperScripts = <<<endScript

    <script type="text/javascript">
<!--
      function showMe() {
        var sh = document.getElementById('showHelp');
        var tf = document.getElementById('uploadForm');
        sh.style.display = 'block';
        tf.style.display = 'none';
      }
      function hideMe() {
        var sh = document.getElementById('showHelp');
        var tf = document.getElementById('uploadForm');
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
      function checkSize(){
        var file_upload = document.getElementById('aimlfile');
        if (!file_upload.files) return false;
        var fileSize = file_upload.files[0].size;//.fileSize;
        var fileName = file_upload.files[0].name;//.fileSize;
        if (fileSize > 2000000){
          showError("The file " + fileName + " exceeds the file size limit of 2MB. Please choose a different file.")
          file_upload.value = null;
        }
        var fileType = file_upload.files[0].type;//.fileSize;
        //showError('The file type is ' + file_upload.files[0].type);
        if (fileType != 'text/aiml' && fileType != 'application/x-zip-compressed') {
          //showError("The file " + fileName + " is neither an AIML file, nor a zip archive. Please select another file.")
          //file_upload.value = null;
        }
      }
      function showError(msg){
        var errorDiv = document.getElementById("errMsg");
        var closeButton = '<div class="closeButton" id="closeButton" onclick="closeStatus(\'errMsg\')" title="Click to hide">&nbsp;</div>';
          errorDiv.innerHTML = closeButton + msg;
          errorDiv.style.display = 'block';

      }
//-->
    </script>
endScript;
  $post_vars = filter_input_array(INPUT_POST);

  $XmlEntities = array('&amp;' => '&', '&lt;' => '<', '&gt;' => '>', '&apos;' => '\'', '&quot;' => '"',);
  $g_tagName = null;
  $aiml_sql = "";
  $pattern_sql = "";
  $that_sql = "";
  $template_sql = "";
  $insert_sql = "";
  $file = "";
  $full_path = "";
  $cat_counter = 0;
  $AIML_List = getAIML_List();
  $all_bots = getBotList();
  $uploadContent = $template->getSection('UploadAIMLForm');
  $showHelp = $template->getSection('UploadShowHelp');
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
  $pageTitle = 'My-Program O - Upload AIML';
  $mainContent = $template->getSection('UploadMain');
  $mainTitle = "Upload AIML to use for the bot named $bot_name [helpLink]";
  #$msg = (empty($msg)) ? 'Test' : $msg;
  $mainContent = str_replace('[bot_name]', $bot_name, $mainContent);
  $mainContent = str_replace('[mainTitle]', $mainTitle, $mainContent);
  $mainContent = str_replace('[upload_content]', $uploadContent, $mainContent);
  $mainContent = str_replace('[showHelp]', $showHelp, $mainContent);
  $mainContent = str_replace('[AIML_List]', $AIML_List, $mainContent);
  $mainContent = str_replace('[all_bots]', $all_bots, $mainContent);
  $mainTitle = str_replace('[helpLink]', $template->getSection('HelpLink'), $mainTitle);
  $mainTitle = str_replace('[errMsg]', $msg, $mainTitle);

  function parseAIML($fn,$aimlContent, $from_zip = false)
  {
    global $post_vars;
    if (empty ($aimlContent)) return "File $fn was empty!";
    global $debugmode, $bot_id, $charset;
    $fileName = basename($fn);
    $success = false;
    $dbConn = db_open();
    #Clear the database of the old entries
    $sql = "DELETE FROM `aiml`  WHERE `filename` = '$fileName' AND bot_id = '$bot_id'";
    if (isset ($post_vars['clearDB']))
    {
      $x = updateDB($sql);
    }
    $myBot_id = (isset ($post_vars['bot_id'])) ? $post_vars['bot_id'] : $bot_id;
    # Read new file into the XML parser
    $sql_start = "insert into `aiml` (`id`, `bot_id`, `aiml`, `pattern`, `thatpattern`, `template`, `topic`, `filename`) values\n";
    $sql = $sql_start;
    $sql_template = "(NULL, $myBot_id, '[aiml_add]', '[pattern]', '[that]', '[template]', '[topic]', '$fileName'),\n";
    # Validate the incoming document

         /*******************************************************/
        /*       Set up for validation from a common DTD       */
       /*       This will involve removing the XML and        */
      /*       AIML tags from the beginning of the file      */
     /*       and replacing them with our own tags          */
    /*******************************************************/
    $validAIMLHeader = '<?xml version="1.0" encoding="[charset]"?>
<!DOCTYPE aiml PUBLIC "-//W3C//DTD Specification Version 1.0//EN" "http://www.program-o.com/xml/aiml.dtd">
<aiml version="1.0.1" xmlns="http://alicebot.org/2001/AIML-1.0.1">';
    $validAIMLHeader = str_replace('[charset]', $charset, $validAIMLHeader);
    $aimlTagStart = stripos($aimlContent, '<aiml', 0);
    $aimlTagEnd = strpos($aimlContent, '>', $aimlTagStart) + 1;
    $aimlFile = $validAIMLHeader . substr($aimlContent, $aimlTagEnd);
    try
    {
      libxml_use_internal_errors(true);
      $xml = new DOMDocument();
      $xml->loadXML($aimlFile);
      $aiml = new SimpleXMLElement($xml->saveXML());
      $rowCount = 0;
      $_SESSION['failCount'] = 0;
      if (!empty ($aiml->topic))
      {
        foreach ($aiml->topic as $topicXML)
        {
        # handle any topic tag(s) in the file
          $topicAttributes = $topicXML->attributes();
          $topic = $topicAttributes['name'];
          foreach ($topicXML->category as $category)
          {
            $fullCategory = $category->asXML();
            $pattern = $category->pattern;
            $pattern = str_replace("'", ' ', $pattern);
            $that = $category->that;
            $template = $category->template->asXML();
            $template = str_replace('<template>', '', $template);
            $template = str_replace('</template>', '', $template);
            $aiml_add = str_replace("\r\n", '', $fullCategory);
            # Strip CRLF from category (windows)
            $aiml_add = str_replace("\n", '', $aiml_add);
            # Strip LF from category (mac/*nix)
            $sql_add = str_replace('[aiml_add]', mysql_real_escape_string($aiml_add), $sql_template);
            $sql_add = str_replace('[pattern]', $pattern, $sql_add);
            $sql_add = str_replace('[that]', $that, $sql_add);
            $sql_add = str_replace('[template]', mysql_real_escape_string($template), $sql_add);
            $sql_add = str_replace('[topic]', $topic, $sql_add);
            $sql .= "$sql_add";
            $rowCount++;
            if ($rowCount >= 100)
            {
              $rowCount = 0;
              $sql = rtrim($sql, ",\n") . ';';
              $success = (updateDB($sql) >= 0) ? true : false;
              $sql = $sql_start;
            }
          }
        }
      }
      if (!empty ($aiml->category))
      {
        foreach ($aiml->category as $category)
        {
          $fullCategory = $category->asXML();
          $pattern = $category->pattern;
          $pattern = str_replace("'", ' ', $pattern);
          $that = $category->that;
          $template = $category->template->asXML();
          $template = substr($template,10);
          $tLen = strlen($template);
          $template = substr($template,0, $tLen - 11);
          # Strip CRLF and LF from category (Windows/mac/*nix)
          $aiml_add = str_replace(array("\r\n", "\n"), '', $fullCategory);
          $sql_add = str_replace('[aiml_add]', mysql_real_escape_string($aiml_add), $sql_template);
          $sql_add = str_replace('[pattern]', $pattern, $sql_add);
          $sql_add = str_replace('[that]', $that, $sql_add);
          $sql_add = str_replace('[template]', mysql_real_escape_string($template), $sql_add);
          $sql_add = str_replace('[topic]', '', $sql_add);
          $sql .= "$sql_add";
          $rowCount++;
          if ($rowCount >= 100)
          {
            $rowCount = 0;
            $sql = rtrim($sql, ",\n") . ';';
            $success = (updateDB($sql) >= 0) ? true : false;
            $sql = $sql_start;
          }
        }
      }
      if ($sql != $sql_start)
      {
        $sql = rtrim($sql, ",\n") . ';';
        $success = (updateDB($sql) >= 0) ? true : false;
      }
      $msg = ($from_zip === true) ? '' : "Successfully added $fileName to the database.<br />\n";
    }
    catch (Exception $e)
    {
      $trace = print_r($e->getTrace(), true);
      //file_put_contents(_LOG_PATH_ . 'error.trace.log', $trace . "\nEnd Trace\n\n", FILE_APPEND);
      $success = false;
      $_SESSION['failCount']++;
      $msg = "There was a problem adding file $fileName to the database. Please refer to the message below to correct the problem and try again.<br>\n" . $e->getMessage();
      $msg = libxml_display_errors($msg);
    }
    return $msg;
  }

  function updateDB($sql)
  {
    $dbConn = db_open();
    if (($result = mysql_query($sql, $dbConn)) === false) throw new Exception('You have a SQL error on line ' . __LINE__ . ' of ' . __FILE__ . '. Error message is: ' . mysql_error() . ".<br />\nSQL = <pre>" . htmlentities($sql) . "</pre><br />\n");
    $commit = mysql_affected_rows($dbConn);
    return $commit;
  }

  function processUpload()
  {
    global $msg;
    // Validate the uploaded file
    if ($_FILES['aimlfile']['size'] === 0 or empty ($_FILES['aimlfile']['tmp_name']))
    {
      $msg = 'No file was selected.';
    }
    elseif ($_FILES['aimlfile']['size'] > 2000000)
    {
      $msg = 'The file was too large.';
    }
    else
      if ($_FILES['aimlfile']['error'] !== UPLOAD_ERR_OK)
      {
      // There was a PHP error
        $msg = 'There was an error uploading.';
      }
      else
      {
      // Create uploads directory if necessary
        if (!file_exists('uploads'))
          mkdir('uploads');
        // Move the file
        $file = './uploads/' . $_FILES['aimlfile']['name'];
        if (move_uploaded_file($_FILES['aimlfile']['tmp_name'], $file))
        {
          #file_put_contents(_LOG_PATH_ . 'upload.type.txt', 'Type = ' . $_FILES['aimlfile']['type']);
          if ($_FILES['aimlfile']['type'] == 'application/zip' or $_FILES['aimlfile']['type'] == 'application/x-zip-compressed') return processZip($file);
          else return parseAIML($file,file_get_contents($file));
        }
        else
        {
          $msg = 'There was an error moving the file.';
        }
    }
    $_SESSION['errorMessage'] = $msg;
    return $msg;
  }

  function getAIML_List()
  {
    global $dbn, $bot_id;
    $out = "                  <!-- Start List of Currently Stored AIML files -->\n";
    $dbConn = db_open();
    $sql = "SELECT DISTINCT filename FROM `aiml` where `bot_id` = $bot_id order by `filename`;";
    if (($result = mysql_query($sql, $dbConn)) === false) throw new Exception(mysql_error());
    while ($row = mysql_fetch_assoc($result))
    {
      if (empty ($row['filename']))
      {
        $curOption = "                No Filename entry<br />\n";
      }
      else
        $out .= $row['filename'] . "<br />\n";
    }
    mysql_free_result($result);
    mysql_close($dbConn);
    $out .= "                  <!-- End List of Currently Stored AIML files -->\n";
    return $out;
  }

  function getBotList()
  {
    global $dbn, $bot_id;
    $botOptions = '';
    $dbConn = db_open();
    $sql = 'SELECT `bot_name`, `bot_id` FROM `bots` order by `bot_id`;';
    if (($result = mysql_query($sql, $dbConn)) === false) throw new Exception(mysql_error());
    while ($row = mysql_fetch_assoc($result))
    {
      $bn = $row['bot_name'];
      $bi = $row['bot_id'];
      $sel = ($bot_id == $bi) ? ' selected="selected"' : '';
      $botOptions .= "                    <option$sel value=\"$bi\">$bn</option>\n";
    }
    mysql_free_result($result);
    mysql_close($dbConn);
    return $botOptions;
  }

  function libxml_display_errors($msg)
  {
    $errors = libxml_get_errors();
    foreach ($errors as $error)
    {
      $msg .= libxml_display_error($error) . "<br />\n";
    }
    libxml_clear_errors();
    return $msg;
  }

  function libxml_display_error($error)
  {
    $out = "<br/>\n";
    switch ($error->level)
    {
      case LIBXML_ERR_WARNING :
        $out .= "<b>Warning $error->code</b>: ";
        break;
      case LIBXML_ERR_ERROR :
        $out .= "<b>Error $error->code</b>: ";
        break;
      case LIBXML_ERR_FATAL :
        $out .= "<b>Fatal Error $error->code</b>: ";
        break;
    }
    $out .= trim($error->message);
    if ($error->file)
    {
      $out .= " in <b>$error->file</b>";
    }
    $out .= " on line <b>$error->line</b>\n";
    return $out;
  }

  function processZip($fileName)
  {
    $out = '';
    $_SESSION['failCount'] = 0;
    $zipName = basename($fileName);
    $zip = new ZipArchive;
    $res = $zip->open($fileName);
    if ($res === TRUE) {
      $numFiles = $zip->numFiles;
      for ($loop = 0; $loop < $numFiles; $loop++)
      {
        $curName = $zip->getNameIndex($loop);
        if (strstr($curName, '/') !== false)
        {
          $endPos = strrpos($curName, '/') + 1;
          $curName = substr($curName, $endPos);
        }
        if (empty($curName)) continue;
        $fp = $zip->getStream($zip->getNameIndex($loop));
        if(!$fp)
        {
          $out .= "Processing for $curName failed.<br />\n";
          $bad_aiml_files = (!isset($bad_aiml_files)) ? array() : $bad_aiml_files;
          $bad_aiml_files[] = $curName;
          $_SESSION['bad_aiml_files'] = $curName;
        }
        else
        {
          $curText = '';
          while (!feof($fp))
          {
            $curText .= fread($fp, 8192);
          }
          fclose($fp);
          if (!stristr($curName, '.aiml')) continue;
          $out .= parseAIML($curName, $curText, true);
        }
      }
      $zip->close();
      $failCount = $_SESSION['failCount'];
      $out .= "<br />\nUpload complete. $numFiles files were processed, and $failCount files encountered errors.<br />\n";
      if (isset($_SESSION['bad_aiml_files']))
      {
        $out .= "<br />\nThe following AIML files encountered errors:<br />\n";
        foreach ($_SESSION['bad_aiml_files'] as $fn)
        {
          $out .= "$fn, ";
        }
        $out = rtrim($out, ', ') . "<br .>\nPlease test each of these files independently, to locate the errors within.";
        unset($_SESSION['bad_aiml_files']);
      }
    }
    else
    {
      $out = "Upload failed. $fileName was either corrupted, or not a zip file." ;
    }
    return $out;
  }

?>