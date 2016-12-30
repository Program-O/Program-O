<?php
// patch.php

/** @noinspection PhpIncludeInspection */
require_once('config/global_config.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'PDO_functions.php');
chdir(__DIR__);

// First, alter the aiml_userdefined table
$dbConn = db_open();
$sql = 'describe `aiml_userdefined`';
$data = db_fetchAll($sql, null, __FILE__, __FUNCTION__, __LINE__);
$intFlag = false;

foreach ($data as $row)
{
    if ($row['Field'] !== 'user_id') {
        continue;
    }

    if ($row['Type'] === 'int(11)') {
        $intFlag = true;
    }
}

if ($intFlag)
{
    $sql = 'truncate aiml_userdefined;'; // empty the table
    $numRows = db_write($sql, null, __FILE__, __FUNCTION__, __LINE__);

    /** @noinspection SqlDialectInspection */
    /** @noinspection SqlNoDataSourceInspection */
    $sql = 'ALTER TABLE `aiml_userdefined` CHANGE `user_id` `user_id` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;'; // alter the table's structure
    $numRows = db_write($sql, null, __FILE__, __FUNCTION__, __LINE__);
}

// Next, update the config file to set the proper upload path
$configFile = file_get_contents(_CONF_PATH_ . 'global_config.php');
$ocf = $configFile;
$configFile = str_replace("'_UPLOAD_PATH_', _CONF_PATH_", "'_UPLOAD_PATH_', _ADMIN_PATH_", $configFile);

if ($ocf !== $configFile)
{
    // no sense in changing the file if it's already changed
    file_put_contents(_CONF_PATH_ . 'global_config.php', $configFile);
}

unlink(_BASE_PATH_ . 'patch.php');

header('Location: ./');
exit();
