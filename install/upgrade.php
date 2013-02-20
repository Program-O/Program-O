<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.1.1
  * FILE: upgrade.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 02-13-2013
  * DETAILS: Upgrades Program O from 2.0.x to 2.1
  ***************************************/

  $thisFile = __FILE__;
  if (!file_exists('../config/global_config.php')) header('location: ../install/install_programo.php');
  require_once('../config/global_config.php');


	//load shared files
	include_once(_LIB_PATH_."db_functions.php");
	include_once(_LIB_PATH_."error_functions.php");
	
	runDebug( __FILE__, __FUNCTION__, __LINE__, "Starting upgrade");

  # process upgrade here

?>