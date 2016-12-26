<?php
/***************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.6.3
* FILE: index.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: 02-13-2013
* DETAILS: Program O's starting point
***************************************/

if (!file_exists('config/global_config.php'))
{
    # No config exists we will run install
    header('Location: install/install_programo.php');
}
elseif (file_exists('patch.php'))
{
    require_once ('patch.php');
}
else
{
    $get_vars = filter_input_array(INPUT_GET);
    $qs = '';

    if (!empty($get_vars))
    {
        $qs = '?';

        foreach ($get_vars as $key => $value)
        {
            $qs .= "$key=$value&";
        }
        $qs = rtrim($qs, '&');
    }
    # Config exists we will goto the bot
    $thisFile = __FILE__;

    /** @noinspection PhpIncludeInspection */
    require_once('config/global_config.php');

    /** @noinspection PhpUndefinedVariableInspection */
    $format = (isset($get_vars['format'])) ? $get_vars['format'] : $format;
    $format = strtoupper($format);

    switch ($format)
    {
        case 'JSON':
            $gui = 'jquery';
            break;
        case 'XML':
            $gui= 'xml';
            break;
        default:
            $gui = 'plain';
    }

    if (!defined('SCRIPT_INSTALLED'))
    {
        header('Location: ' . _INSTALL_URL_ . 'install_programo.php');
    }
    else
    {
        header("Location: gui/$gui/$qs");
    }
}