<?php
//-----------------------------------------------------------------------------------------------
//My Program-O Version 2.0.1
//Program-O  chatbot admin area
//Written by Elizabeth Perreau and Dave Morton
//Aug 2011
//for more information and support please visit www.program-o.com
//-----------------------------------------------------------------------------------------------
// main.php
    $noRightNav    = $template->getSection('NoRightNav');
    $logo          = $template->getSection('Logo');
    $topNav        = $template->getSection('TopNav');
    $leftNav       = $template->getSection('LeftNav');
    $main          = $template->getSection('Main');
    $rightNav      = '';
    $footer        = trim($template->getSection('Footer'));
    #$lowerScripts  = '';
    #$pageTitleInfo = '';
    $divDecoration = $template->getSection('DivDecoration');
    $topNavLinks   = makeLinks('top', $topLinks, 12);
    $navHeader     = $template->getSection('NavHeader');
    $leftNavLinks  = makeLinks('left', $leftLinks, 12);
    $mainTitle     = 'Home';
    $rightNavLinks = '';
    $FooterInfo    = getFooter();
    $titleSpan     = $template->getSection('TitleSpan');
    $errMsgStyle   = (!empty($msg)) ? "ShowError" : "HideError";
    $errMsgStyle   = $template->getSection($errMsgStyle);
    $mediaType     = ' media="screen"';
    $upperScripts  = '';
    $noLeftNav     = '';
    $noTopNav      = '';
    $pageTitle     = 'My-Program O - Main Page';
    $headerTitle   = 'Actions:';
    $mainContent   = <<<endMain
        <p>
          Welcome to 'My Program-O', the Program-O chatbot admin area. Please
          use the links above or to the left to perform administrative tasks,
          as needed.
        </p>
        <div id="rssContainer">
         <div id="rssTitle">Latest News from Program-O.com</div>
         <div id="rssOutput">[rssOutput]</div>
        </div>
endMain;

  $mainContent = str_replace('[rssOutput]', getRSS(), $mainContent);

  function getRSS() {
    global $template;
    $out = '';
    $itemTemplate = $template->getSection('RSSItemTemplate');
    $xml = @simplexml_load_file(RSS_URL); //loading the document
    if ($xml) {
      $title = $xml->channel->title; //gets the title of the document.
      $rss = simplexml_load_file(RSS_URL);
      if($rss) {
        $items = $rss->channel->item;
        foreach($items as $item) {
          $title = $item->title;
          $link = $item->link;
          $published_on = $item->pubDate;
          $description = $item->description;
          $out .= "<h3><a href=\"$link\">$title</a></h3>\n";
          $out .= "<p>$description</p>";
        }
      }
    }
    else $out = 'RSS Feed not available';
    return $out;
  }

  function getXML_section($file, $tagName, $count = 1) {
    $startTagSearch = "<$tagName>";
    $endTagSearch   = "</$tagName>";
    $atomicTagSearch = "<$tagName />";
    $alternateAtomicTagSearch = "<$tagName/>";
    $foundPositions = array(-1);
    $pos = 0;
    $n = 0;
    while ($pos !== false) {
      switch (true) {
        case ($newPos = stripos($file, $startTagSearch, $pos)):
        case ($newPos = stripos($file, $atomicTagSearch, $pos)):
        case ($newPos = stripos($file, $alternateAtomicTagSearch, $pos)):
          $foundPositions[] = $newPos;
          break;
        default:
          $pos = $newPos;
      }
      $n++;
      if ($n > 50) break;
    }
    $startPos = $foundPositions[$count];
    $endPos = stripos($file, $endTagSearch, $startPos);
    $len = $endPos - $startPos;
    return substr($file, $startPos, $len);
  }

?>