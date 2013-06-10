<?php

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version 2.2.2
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
    if (!defined('SCRIPT_INSTALLED')) header('location: ' . _INSTALL_URL_ . 'install_programo.php');
    elseif (file_exists(_INSTALL_PATH_ . 'upgrade.php')) header('Location: ' . _INSTALL_URL_ . 'upgrade.php?returnTo=' . $gui);
    else header("location: gui/$gui");
  }

?>
