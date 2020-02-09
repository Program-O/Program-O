<?php
  /***************************************
   * http://www.program-o.com
   * Program-O
   * Version: 2.4.2
   * Build: 1402028162
   * FILE: reader.php
   * AUTHOR: Elizabeth Perreau and Dave Morton
   * DATE: 2/13/2018 - 11:19 PM
   * DETAILS: Reads debug files to pass along to the HTML GUI,
   * but with additional no-cache headers
   ***************************************/
  $fn = filter_input(INPUT_GET, 'file');
  if (!empty($fn) && file_exists($fn))
  {
    header('content-type: text/plain');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    exit(file_get_contents($fn));
  }