<?php

/* * *************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.5
* FILE: editAiml.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: 05-26-2014
* DETAILS: Search the AIML table of the DB for desired categories
* ************************************* */

$mainContent = $template->getSection('EditAimlPage');
$topNav = $template->getSection('TopNav');
$leftNav = $template->getSection('LeftNav');
$rightNav = $template->getSection('RightNav');
$main = $template->getSection('Main');
$navHeader = $template->getSection('NavHeader');
$FooterInfo = getFooter();
$errMsgClass = (!empty ($msg)) ? "ShowError" : "HideError";
$errMsgStyle = $template->getSection($errMsgClass);
$noLeftNav = '';
$noTopNav = '';
$noRightNav = $template->getSection('NoRightNav');
$headerTitle = 'Actions:';
$pageTitle = 'My-Program O - Search/Edit AIML';
$mainTitle = 'Search/Edit AIML' . $template->getSection('HelpLink');
$showHelp = $template->getSection('editAIMLShowHelp');
$mainContent = str_replace('[showHelp]', $showHelp, $mainContent);

