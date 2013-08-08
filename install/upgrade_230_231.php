<?php
/***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.3.1
  * FILE: upgrade_230_231.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 08-08-2013
  * DETAILS: Updates the table aiml_userdefined to the proper structure
  ***************************************/

  $thisFile = __FILE__;
  require_once ('../config/global_config.php');
  include_once (_LIB_PATH_ . "db_functions.php");
  include_once (_LIB_PATH_ . "error_functions.php");
  //ini_set('display_errors', true);

  $con = db_open();
  $sql = "alter table `$dbn`.`aiml_userdefined` ADD `thatpattern` TEXT NOT NULL AFTER `pattern`;";
  $result = db_query($sql, $con) or exit('<pre>There was a problem upgrading to version 2.3.1 - please submit a bug report and include the following information:<br><br><b>'. mysql_error(). 'in '. __FILE__. ', line '. __LINE__. '</b>');
  echo  'Upgrade successful!';


?>