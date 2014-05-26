<?php
/***************************************
* www.program-o.com
* PROGRAM O 
* Version: 2.4.1
* FILE: custom_tags.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: MAY 17TH 2014
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

function parse_google_tag($convoArr, $element, $parentName, $level)
{
  runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing custom GOOGLE tag.', 2);
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
  $googleURL = 'http://www.google.com/search?q=' . urlencode($response_string);
  $search_response = "Google Search Results: <a target=\"_blank\" href=\"$googleURL\">$response_string</a>";
  $response_string = $search_response;
  return $response_string;
}

function parse_wiki_tag($convoArr, $element, $parentName, $level)
{
  runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing custom WIKI tag.', 2);
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

  $wikiURL = 'http://en.wikipedia.org/w/api.php?action=opensearch&format=xml&limit=1&search=' . urlencode($response_string);
  $options = array(
		CURLOPT_HTTPGET => TRUE,
		CURLOPT_POST => FALSE,
		CURLOPT_HEADER => false,
		CURLOPT_NOBODY => FALSE,
		CURLOPT_VERBOSE => FALSE,
		CURLOPT_REFERER => "",
		CURLOPT_SSL_VERIFYPEER => FALSE,
		CURLOPT_FOLLOWLOCATION => TRUE,
		CURLOPT_MAXREDIRS => 4
  );
  $wikiText = get_cURL($wikiURL, $options);
  #save_file(_DEBUG_PATH_ . 'wiki_return.txt', $wikiText);
  $xml = simplexml_load_string($wikiText, 'SimpleXMLElement', LIBXML_NOCDATA);
  if((string)$xml->Section->Item->Description)
  {
    $description = (string)$xml->Section->Item->Description;
    $image = (string)$xml->Section->Item->Image->asXML();
    $image = str_replace('<Image source', '<img src', $image);
    $linkHref = (string)$xml->Section->Item->Url;
    $linkText = (string)$xml->Section->Item->Text;
    $link = "<a href=\"$linkHref\">$linkText</a>";
    $output = "$link<br/>\n$image<br/>\n$description";
  }
  else $output = 'Wikipedia returned no results!';
  $output = '<![CDATA[' . $output . ']]>';
  return $output;
}

