<?php

// patch.php

/*
ALTER TABLE `aiml_userdefined` CHANGE `user_id` `convo_id` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
*/
require_once('config/global_config.php');
require_once('library/PDO_functions.php');
$dbConn = db_open();
$sql = 'describe `aiml_userdefined`';
$data = db_fetchAll($sql, null, __FILE__, __FUNCTION__, __LINE__);
$intFlag = false;
foreach ($data as $row) {
  if ($row['Field'] !== 'user_id') continue;
  if ($row['Type'] === 'int(11)') $intFlag = true;
}
if ($intFlag) {
  $sql = 'truncate aiml_userdefined;'; // empty the table
  $numRows = db_write($sql, null, __FILE__, __FUNCTION__, __LINE__);
  $sql = 'ALTER TABLE `aiml_userdefined` CHANGE `user_id` `user_id` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;'; // alter the table's structure
  $numRows = db_write($sql, null, __FILE__, __FUNCTION__, __LINE__);
}
unlink('patch.php');
header('Location: ./');
exit();
