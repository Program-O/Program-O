<?php
/***************************************
* www.program-o.com
* PROGRAM O 
* Version: 2.2.0
* FILE: custom_tags.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: MAY 4TH 2011
* DETAILS: this file contains the addon library to process the custom <code> tag
***************************************/
include('code_tag/code_tag.php');


function custom_parse_aiml_as_XML($convoArr)
{
  runDebug(__FILE__, __FUNCTION__, __LINE__, 'Checking for custom AIML tags to parse.', 2);
  return $convoArr;
}

/*
 * function parse_php_tag
 * Parses the custom <php> tag
 * @param (array) $convoArr
 * @param (SimpleXMLelement) $element
 * @param (string) $parentName
 * @param (int) $level
 * @return (string) $response_string
 */

function parse_php_tag($convoArr, $element, $parentName, $level)
{
  runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing custom PHP tag.', 2);
  $response = array();
  $children = $element->children();
  if (!empty ($children))
  {
    $response = parseTemplateRecursive($convoArr, $children, $level + 1);
  }
  else
  {
    $response[] = (string) $element;
  }
  $response_string = implode_recursive(' ', $response);
  // do something here
  return $response_string;
}