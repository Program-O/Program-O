<?php

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.1.2
  * FILE: index.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 02-13-2013
  * DETAILS: Program O's starting point
  ***************************************/

  if (!file_exists('config/global_config.php'))
  {
  # No config exists we will run install
    header('location: install/install_programo.php');
  }
  else
  {
    # Config exists we will goto the bot
    $thisFile = __FILE__;
    require_once('config/global_config.php');
    if (!defined('SCRIPT_INSTALLED')) header('location: ' . _INSTALL_PATH_ . 'install_programo.php');
    if (file_exists(_INSTALL_PATH_ . 'upgrade.php')) require_once(_INSTALL_PATH_ . 'upgrade.php');
    $default_format = strtoupper($default_format);
    switch ($default_format)
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
    header("location: gui/$gui");
  }

?>
