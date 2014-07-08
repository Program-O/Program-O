<?php
//-----------------------------------------------------------------------------------------------
//My Program-O Version: 2.4.3
//Program-O  chatbot admin area
//Written by Elizabeth Perreau and Dave Morton
//DATE: MAY 17TH 2014
//for more information and support please visit www.program-o.com
//-----------------------------------------------------------------------------------------------
// demochat.php
  $bot_id = ($bot_id == 'new') ? 0 : $bot_id;
  $upperScripts  = '';
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
  $pageTitle     = 'My-Program O - Chat Demo';
  $mainContent   = 'This will eventually be the page for the chat demo.';
  $mainContent   = showChatFrame();
  $mainTitle     = 'Chat Demo';

  /**
   * Function showChatFrame
   *
   *
   * @return mixed|string
   */
  function showChatFrame() {
    global $template, $bot_name, $bot_id, $dbConn;
    $qs = '?bot_id=' . $bot_id;
    $sql = "select `format` from `bots` where `bot_id` = $bot_id limit 1;";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $row = $sth->fetch();
    $format = strtolower($row['format']);
    switch ($format) {
      case "html":
        $url = '../gui/plain/';
        break;
      case "json":
        $url = '../gui/jquery/';
        break;
      case "xml":
        $url = '../gui/xml/';
        break;
      default:
        $url = '../gui/plain/';
    }
    $url .= $qs;
    $out = $template->getSection('ChatDemo');
    $out = str_replace('[pageSource]', $url, $out);
    $out = str_replace('[format]', strtoupper($format), $out);
    return $out;
  }
?>
