<?php

/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.11
 * FILE: main.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: FEB 01 2016
 * DETAILS: Displays the "Home"  section of the admin page
 ***************************************/

require_once(_LIB_PATH_ . 'class.Parsedown.php');
$pd = new Parsedown();
$readMeRaw = file_get_contents(_BASE_PATH_ . 'README.md');
$blobMaster = 'https://github.com/Program-O/Program-O/blob/master/';
// fix or remove links
$nul = preg_match_all('~(\[.*?\]\([^\n]*?\))~', $readMeRaw,$rmMatches);
//save_file(_LOG_PATH_ . 'matches.txt', print_r($rmMatches, true));
foreach ($rmMatches[0] as $match){
    if (strstr($match, 'http://')) continue;
    $replace = str_replace('](', "]({$blobMaster}", $match);
    $readMeRaw = str_replace($match, $replace, $readMeRaw);
}

$readMe = $pd->text($readMeRaw);

$noRightNav     = $template->getSection('NoRightNav');
$logo           = $template->getSection('Logo');
$topNav         = $template->getSection('TopNav');
$leftNav        = $template->getSection('LeftNav');
$main           = $template->getSection('Main');

$rightNav       = '';
$footer         = trim($template->getSection('Footer'));
#$lowerScripts  = '';
#$pageTitleInfo = '';
$divDecoration  = $template->getSection('DivDecoration');

$navHeader      = $template->getSection('NavHeader');

$mainTitle      = 'Home';
$rightNavLinks  = '';
$FooterInfo     = getFooter();
$titleSpan      = $template->getSection('TitleSpan');
$errMsgStyle    = (!empty($msg)) ? "ShowError" : "HideError";
$errMsgStyle    = $template->getSection($errMsgStyle);
$mediaType      = ' media="screen"';
$upperScripts   = '';
$noLeftNav      = '';
$noTopNav       = '';
$pageTitle      = 'My-Program O - Main Page';
$headerTitle    = 'Actions:';
$mainContent    = <<<endMain
        <p>
          Welcome to 'My Program-O', the Program-O chatbot admin area. Please
          use the links above or to the left to perform administrative tasks,
          as needed.
        </p>
        <div id="readMe">[readMe]</div>
endMain;

$mainContent = str_replace('[readMe]', $readMe, $mainContent);