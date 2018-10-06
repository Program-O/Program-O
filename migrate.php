<?php
define('CLI', PHP_SAPI === 'cli');

//check we are only running from command line and po is installed
run_validation_checks();

//make sure that this script is only run in command line
require_once('config/global_config.php');
//load shared files
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'PDO_functions.php');
/** @noinspection PhpIncludeInspection */
include_once(_LIB_PATH_ . "error_functions.php");
/** @noinspection PhpIncludeInspection */
include_once(_LIB_PATH_ . 'misc_functions.php');

ini_set('default_charset', $charset);
$file_path='install/database/migrations/';

$last_initial_sql ='20181006-113000-initial-data.sql';
$migration_table_rollback_sql ='20181006-114000-add-migrations-table-rollback.sql';
$migration_table_rollforward_sql ='20181006-114000-add-migrations-table.sql';

//------------------------------------------------------------------------
// Error Handler
//------------------------------------------------------------------------
// set to the user defined error handler
set_error_handler("myErrorHandler");

//check if the migration table exists... if not the version pre 2.8.0
//get a list of files in the database/migrations folder.
//when a migration is performed
//get the latest record in the migrations table
//import the records from the directory starting at the latest file from the table...
//each record in this migration should have a migration_group_id as a rollback point
//this will be the version.txt

//get the script action (rollback/rollforward)
$action = get_action();
message("About to run ".$action." script");


//does the migrations table exist?
$has_migrations_table = has_migrations_table();
//is this the initial install?
$is_initial_install = is_initial_install();

//no table .... then this is either the initial or an 2.8.0 upgrade...
//there are different actions for each scenario
if($is_initial_install){ //initial install

    message("Initial Import $action");
    //start from beginning
    $last_known_migrated_file = '';
    $migration_group_number = 0;

}elseif(!$has_migrations_table  ){ //the 2.8.0 upgrade which added the migrations table

    message("2.8.0 upgrade $action");
    //this file marks the break between >2.8.0 and 2.8.0
    $last_known_migrated_file = $last_initial_sql;
    $migration_group_number = 0;

}else{ //just an upgrade

    message("Upgrading $action");
    $migration = get_last_known_migration();
    //start from last known file
    $last_known_migrated_file = $migration['filename'];
    $migration_group_number = (int)$migration['migration_group_number'];

}

if($action=='rollforward'){

    //get a list of migration files in the migrations directory
    $database_files = get_rollforward_files($last_known_migrated_file,array('..', '.'));

    if(empty($database_files)){
        message("No sql to migrate",'warning');
    }else{
        run_migrations_rollforward($database_files,$migration_group_number);
    }
}elseif($action=='rollback'){


    run_migrations_rollback($migration_group_number);

}



/*
 * perform the rollforward of the sql migration files
 */
function run_migrations_rollforward($file_array,$migration_number){

    global $file_path,$dbn,$migration_table_rollforward_sql;
    //increment
    $migration_number++;
    db_beginTransaction(__FILE__, __FUNCTION__, __LINE__, true);
    $count=0;


    foreach ($file_array as $file) {

        $full_file = $file_path.$file;

        message("Migrating: $file",'info');

        $sql_array = file($full_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        //if this file is the create a migration table
        if($file!==$migration_table_rollforward_sql){
            record_migration($file,$migration_number,$count);
        }

        foreach ($sql_array as $sql) {

            if(trim($sql)==''){
                continue;
            }elseif($sql[0]=='#'){
                $message_parts = extract_message($sql);
                message($message_parts['message'],$message_parts['type']);
                continue;
            }

            $count++;

            try {
                $sql = make_replacements($sql);
                $insertSuccess = db_write($sql, null, false, __FILE__, __FUNCTION__, __LINE__, false);
                if (false === $insertSuccess) {
                    throw new Exception('SQL operation failed!');
                }

                message("SQL#$count $sql",'success');
            }catch (Exception $e){

                message("SQL#$count $sql",'warning');
                message("DB migration failed: [".__LINE__."] ".$e->getMessage()." All transaction have been rolled backed",'warning');
                db_rollBack(__FILE__, __FUNCTION__, __LINE__, true);
                die();

            }
        }

        //if this file is the create a migration table
        if($file==$migration_table_rollforward_sql){
            record_migration($file,$migration_number,$count);
        }



    }

    db_commit(__FILE__, __FUNCTION__, __LINE__, true);

}


function record_migration($file,$migration_number,$count){

    global $dbn;

    try {
        $sql = "INSERT INTO `$dbn`.`migrations` (`id`, `filename`, `migration_group_number`) VALUES (NULL, :filename, :migration_group_number) ON DUPLICATE KEY UPDATE filename=:filename, migration_group_number=:migration_group_number,created_on=CURRENT_TIMESTAMP ";
        $params = array(
            ':filename' => $file,
            ':migration_group_number' => $migration_number
        );
        $insertSuccess = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__, false);
        if (false === $insertSuccess) {
            throw new Exception('SQL operation failed!');
        }
    } catch (Exception $e) {

        db_rollBack();
        message("SQL#$count $sql",'warning');
        message("DB migration failed: [".__LINE__."] ".$e->getMessage() . " All transaction have been rolled backed", 'warning');
        die();

    }

}

/*
 * perform the rollback of the sql migration files
 */
function run_migrations_rollback($migration_group_number){


    global $file_path,$dbn,$migration_table_rollback_sql;
    $count=0;





    $rollback_array = get_last_applied_files($migration_group_number);


    db_beginTransaction();


    foreach ($rollback_array as $row) {

        $file = $row['filename'];

        $file = str_replace(".sql","-rollback.sql",$file);

        $full_file = $file_path.$file;

        message("Migrating: $file",'info');

        $sqlArray = file($full_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($sqlArray as $sql) {

            if(trim($sql)==''){
                continue;
            }elseif($sql[0]=='#'){
                $message_parts = extract_message($sql);
                message($message_parts['message'],$message_parts['type']);
                continue;
            }

            $count++;
            try {
                $sql = make_replacements($sql);
                $insertSuccess = db_write($sql, null, false, __FILE__, __FUNCTION__, __LINE__, false);
                if (false === $insertSuccess) {
                    throw new Exception('SQL operation failed!');
                }
                message("SQL#$count SUCCESS $sql",'success');
            }catch (Exception $e){

                message("SQL#$count ERROR $sql",'warning');
                db_rollBack();
                message("DB migration failed: [".__LINE__."] ".$e->getMessage()." All transaction have been rolled backed",'warning');
                die();

            }

        }

        try {

            if($count>0 && $file !== $migration_table_rollback_sql) {

                $sql = "DELETE FROM `$dbn`.`migrations` WHERE migration_group_number=:migration_group_number ";
                $params = array(
                    ':migration_group_number' => $migration_group_number
                );
                $insertSuccess = db_write($sql, $params, $params, __FILE__, __FUNCTION__, __LINE__, false);
                if (false === $insertSuccess) {
                    throw new Exception('SQL operation failed!');
                }
                message("SQL SUCCESS $sql",'success');
            }
        }catch (Exception $e){

            message("SQL ERROR $sql",'warning');

            db_rollBack();
            message("DB migration failed: [".__LINE__."] ".$e->getMessage()." All transaction have been rolled backed",'warning');
            die();

        }

    }

    db_commit();

}

function make_replacements($sql){

    global $dbn;
    $sql = str_replace("{dbn}",$dbn,$sql);
    return $sql;

}
/*
 * make sure that we are only accessing this script from command line
 */
function run_validation_checks(){

    if(PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])){
        http_response_code(403);
        exit;
    }

    if (!file_exists('config/global_config.php'))
    {
        message('Program O exists, but is not installed. see:https://github.com/Program-O/Program-O/wiki','warning');
        die();
    }

}

/*
 * set the script actions.. it will either be rollback or rollforward
 */
function get_action(){

    global $argv;

    if(isset($argv[1])){

        if($argv[1]=='rollback'){
            $action='rollback';
        }else{
            message('Unknown argument '.$argv[1].' see: https://github.com/Program-O/Program-O/wiki/Upgrade-Guide','warning');
            die();
        }

    }else{
        $action='rollforward';
    }
    return $action;
}

/*
 * get an array of files from the migrations directory
 */
function get_rollforward_files($last_migration_file=false, $exclude_array=array('..','.')){

    $rollback_array = array();
    $directory = 'install/database/migrations';
    $temp_array = array_diff(scandir($directory), $exclude_array);

    //if this is an upgrade then we will have the last migration script
    if($last_migration_file){

        foreach($temp_array as $index => $file){

            if($file==$last_migration_file){

                $split_temp_array = array_chunk($temp_array,$index);

                $temp_array=$split_temp_array[1];

                break;
            }
        }



    }

    if(!empty($temp_array)){
        foreach($temp_array as $file){
            if(strpos($file,'rollback.sql')===false){
                $rollback_array[]=$file;
            }
        }
    }
    return $rollback_array;


}




/*
 * does migrations table exist?
 */
function get_last_applied_files($migration_group_number){

    global $dbn;
    /** @noinspection SqlDialectInspection */

    $sql = "SELECT filename FROM `$dbn`.`migrations` where migration_group_number = :migration_group_number ORDER BY id desc";
    $params = array(':migration_group_number'=> $migration_group_number);
    $results = db_fetchAll($sql, $params,  __FILE__, __FUNCTION__, __LINE__,true);
    if(empty($results)){
        message('There are no files to rollback!','warning');
        die();
    }
    return $results;
}

/*
 * does migrations table exist?
 */
function has_migrations_table(){

        global $dbn;
        /** @noinspection SqlDialectInspection */

        $sql = "SELECT 1 FROM `$dbn`.`migrations` LIMIT 1";

        $row_count = db_write($sql, array(), false, __FILE__, __FUNCTION__, __LINE__);
        if($row_count<1){
            return false;
        }else{
            return true;
        }
}


/*
 * does the aiml table exist? If not then its an initial install
 */
function is_initial_install(){

    global $dbn;
    /** @noinspection SqlDialectInspection */

    $sql = "SELECT 1 FROM `$dbn`.`aiml` LIMIT 1";

    $row_count = db_write($sql, array(), false, __FILE__, __FUNCTION__, __LINE__);
    if($row_count==1){
        return false;
    }else{
        return true;
    }
}

/*
 * does the aiml table exist? If not then its an initial install
 */
function get_last_known_migration(){

    global $dbn;
    /** @noinspection SqlDialectInspection */

    $sql = "SELECT filename, migration_group_number FROM `$dbn`.`migrations` LIMIT 1";

    $row = db_fetch($sql, array(), __FILE__, __FUNCTION__, __LINE__, true);

    if(empty($row)){
        message("ERROR Unable to complete - can not identify the last known migration point",'warning');
        die();
    }


    return $row;
}
function extract_message($str){

    $tmp = explode("#",$str);
    $message_parts['message']=strtolower($tmp[2]);
    $message_parts['type']=strtolower($tmp[1]);

    return $message_parts;
}


function message($message,$type='info'){


    switch($type){
        case "success":
            echo "\n\033[32m $message";
            break;
        case "info":
            echo "\n\033[29m $message";
            break;
        case "warning":
            echo "\n\033[31m $message";
            break;
        case "question":
            echo "\n\033[31m $message";

            $handle = fopen ("php://stdin","r");
            $line = fgets($handle);
            if(trim($line) == 'yes'){
                echo "\n\033[32m Thank you, continuing...";
            }elseif(trim($line) == 'no'){
                echo "\n\033[31m Exiting";
                exit;
            }else{
                echo "\n\033[31m Unknown option - Exiting";
                exit;
            }
            fclose($handle);


            break;
    }

}