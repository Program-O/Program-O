<?php

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.1.2
  * FILE: buildSelect.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 02-21-2013
  * DETAILS: Builds HTML  selectbox options from an array
  ***************************************/
  function buildSelect($optionList, $selList = array(), $selectText = '', $useSelDefault = false, $useOnlyValues = false, $space = 2)
  {
    $usd = ($useSelDefault) ? 1 : 0;
    $uov = ($useOnlyValues) ? 1 : 0;
    $out = <<<endDebug
<!-- DEBUG:
selList = $selList
SelectText = $selectText
useSelDefault = $usd
useOnlyValues = $uov
space = $space
-->

endDebug;
      $out = '';
    $padding = str_pad(" ", $space);
    if ($selectText != "")
    {
      $selDefault = (!$useSelDefault) ? '' : ' selected="selected"';
      $out .= "$padding<option $selDefault>$selectText</option>\n";
    }
    foreach ($optionList as $key => $value)
    {
      $sel = (in_array($key, $selList)) ? ' selected="selected"' : '';
      $key = ($useOnlyValues) ? $value : $key;
      $out .= "$padding<option$sel value='$key'>$value</option>\n";
    }
    return rtrim($out);
  }
?>
