<?php

/* * *************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.7
* FILE: editUDAiml.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: 05-26-2014
* DETAILS: Search the aiml_userdefined table of the DB for desired categories
* ************************************* */

$mainContent = $template->getSection('EditUDAimlPage');
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
$pageTitle = 'My-Program O - Search/Edit User Defined AIML';
$mainTitle = 'Search/Edit AIML' . $template->getSection('HelpLink');
$showHelp = $template->getSection('editAIMLShowHelp');
$mainContent = str_replace('[showHelp]', $showHelp, $mainContent);

