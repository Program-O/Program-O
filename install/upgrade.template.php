<?php
/***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.3.0
  * FILE: upgrade.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 06-16-2013
  * DETAILS: Upgrades the database from 2.2 to 2.3
  ***************************************/

  require_once('../config/global_config.php');
  require_once(_LIB_PATH_ . 'db_functions.php');
  require_once(_LIB_PATH_ . 'error_functions.php');
  require_once(_LIB_PATH_ . 'misc_functions.php');
  //open db connection
  $con = db_open();
  $sql = "SELECT `unknown_user` FROM `$dbn`.`bots`;";
  if (!mysql_query($sql))
  {
    if (alter_db($con))
    {
      save_file(_LOG_PATH_ . 'upgrade.txt', 'Success!');
      if(file_exists(_INSTALL_PATH_ . 'upgrade.php')) unlink(_INSTALL_PATH_ . 'upgrade.php');
    }
  }

  if(isset($_GET['returnTo'])) header('Location: ../gui/' . $_GET['returnTo']);

  function alter_db($con)
  {
    global $dbn;
    $sql_array = array(
      "ALTER TABLE `$dbn`.`aiml` DROP `php_code`;",
      "ALTER TABLE `$dbn`.`bots` DROP `update_aiml_code`;",
      "ALTER TABLE `$dbn`.`bots` ADD unknown_user VARCHAR( 255 ) NOT NULL DEFAULT 'Seeker';",
      "ALTER TABLE `$dbn`.`bots` DROP `use_aiml_code`;",
      "ALTER TABLE `$dbn`.`undefined_defaults` DROP `user_id`;",
      "ALTER TABLE `$dbn`.`client_properties` CHANGE `id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT;"
    );
    foreach ($sql_array as $sql)
    {
      $result1 = db_query($sql, $con) or trigger_error('SQL error: ' . mysql_error() . "<br>\nSQL = $sql");
    }
    $sql = "select `bot_id` from `$dbn`.`unknown_inputs`;";
    $result2 = mysql_query($sql, $con) or mysql_query("ALTER TABLE `$dbn`.`unknown_inputs` ADD `bot_id` INT( 11 ) NOT NULL;", $con);

    return ($result1 and $result2);
  }



?>