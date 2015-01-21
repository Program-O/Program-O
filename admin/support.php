<?php
  /***************************************
    * http://www.program-o.com
    * PROGRAM O
    * Version: 2.4.7
    * FILE: support.php
    * AUTHOR: Elizabeth Perreau and Dave Morton
    * DATE: 12-12-2014
    * DETAILS: Displays support information, including the Program O forum RSS feed
    ***************************************/
    $noRightNav    = $template->getSection('NoRightNav');
    $logo          = $template->getSection('Logo');
    $topNav        = $template->getSection('TopNav');
    $leftNav       = $template->getSection('LeftNav');
    $main          = $template->getSection('Main');
    $rightNav      = '';
    $footer        = trim($template->getSection('Footer'));
    $divDecoration = $template->getSection('DivDecoration');
    $navHeader     = $template->getSection('NavHeader');
    $mainTitle     = 'Program O Support';
    $rightNavLinks = '';
    $FooterInfo    = getFooter();
    $titleSpan     = $template->getSection('TitleSpan');
    $errMsgStyle   = (!empty($msg)) ? "ShowError" : "HideError";
    $errMsgStyle   = $template->getSection($errMsgStyle);
    $mediaType     = ' media="screen"';
    $upperScripts  = '';
    $noLeftNav     = '';
    $noTopNav      = '';
    $pageTitle     = 'My-Program O - Support Page';
    $headerTitle   = 'Actions:';
    $forumURL = FORUM_URL;
    $mainContent   = <<<endMain
        <div id="rssContainer">
         <div id="rssTitle">
           For specific support questions, please use the <a href="$forumURL">Program O Forums</a> and post your question.<br />
           Below are the most recent forum posts:
         </div>
         <div id="rssOutput">
           [rssOutput]
         </div>
        </div>
endMain;
  $mainContent = str_replace('[rssOutput]', getRSS('support'), $mainContent);

?>