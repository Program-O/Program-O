<?php
/***************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.0.1
* FILE: config/install_config.php
* AUTHOR: ELIZABETH PERREAU AND DAVE MORTON
* DATE: MAY 4TH 2011
* DETAILS: this file is a stripped down, "install" version of the config file,
* and as such, only has the most minimal settings within it. Theinstall script
*  will create a full and complete config file during the install process
***************************************/
    //------------------------------------------------------------------------
    // Error reporting
    //------------------------------------------------------------------------
    if(!(ini_get('allow_call_time_pass_reference'))){
      ini_set('allow_call_time_pass_reference', 'true');
    }

    $e_all = defined('E_DEPRECATED') ? E_ALL ^ E_DEPRECATED : E_ALL;
    error_reporting($e_all);

    //------------------------------------------------------------------------
    // Paths - only set this manually if the below doesnt work
    //------------------------------------------------------------------------

    chdir( dirname ( __FILE__ ) );
    $thisConfigFolder = dirname( realpath( __FILE__ ) ) . DIRECTORY_SEPARATOR;
    $thisConfigParentFolder = preg_replace( '~[/\\\\][^/\\\\]*[/\\\\]$~' , DIRECTORY_SEPARATOR , $thisConfigFolder);

    define("_BASE_DIR_", $thisConfigParentFolder);
    $path_separator = DIRECTORY_SEPARATOR;

    //------------------------------------------------------------------------
    // Define paths for include files
    //------------------------------------------------------------------------

    define("_INC_PATH_",_BASE_DIR_.$path_separator);
    define("_ADMIN_PATH_",_BASE_DIR_."admin".$path_separator);
    define("_GLOBAL_PATH_",_BASE_DIR_."global".$path_separator);
    define("_BOTCORE_PATH_",_BASE_DIR_."chatbot".$path_separator."core".$path_separator);
    define("_AIMLPHP_PATH_",_BASE_DIR_."chatbot".$path_separator."aiml_to_php".$path_separator);
    define("_LIB_PATH_",_BASE_DIR_."library".$path_separator);
    define("_ADDONS_PATH_",_BASE_DIR_."chatbot".$path_separator."addons".$path_separator);
    define("_CONF_PATH_",_BASE_DIR_."config".$path_separator);
    define("_DEBUG_PATH_",_BASE_DIR_."chatbot".$path_separator."debug".$path_separator);
    define("_INSTALL_PATH_",_BASE_DIR_.$path_separator."install".$path_separator);

?>