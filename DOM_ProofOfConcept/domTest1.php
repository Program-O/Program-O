<?php
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 1);
ini_set('error_log', 'error.log');
require_once('class.DOMIterator.php');
header('content-type: text/plain');
$convoArr = array(
    'client_properties' => array(
        'foo' => 'bar',
        'bar' => 'bat',
        'bat' => 'baz',
        'baz' => 'foo',
        'test' => 'passed'
    )
);


$dom = new DOMDocument('1.0', 'utf8');
$dom->load('testTemplate.xml');
$templates = $dom->getElementsByTagName('template');

$content = '';
foreach ($templates as $template){
    if (!is_null($template)) $content .= parseTemplateRecursive($convoArr, $template);
}
$content .= "\n\nGet array = " . print_r($getArray, true);

exit($content);

function parseTemplateRecursive(&$convoArr, DOMNode $element, $parentName = 'unknown', $out = '', $level = 0)
{
    $out = '';
    if ($element->nodeType === XML_TEXT_NODE)
    {
        $out .= trim($element->nodeValue, ' ');
    }
    else
    {
        $elementName = $element->tagName;
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Parsing the tag '$elementName', a child of $parentName.", 0);
        if ($element->hasChildNodes())
        {
            foreach ($element->childNodes as $childNode)
            {
                $type = $childNode->nodeType;
                switch(true)
                {
                    case ($type === XML_TEXT_NODE):
                        $out .= trim($childNode->nodeValue, ' ') . ' ';
                        break;
                    case ($type === XML_ELEMENT_NODE):
                        $childName = $childNode->tagName;
                        $func = "parse_{$childName}_tag";
                        if (function_exists($func))
                        {
                            $out .= $func($convoArr, $childNode);
                        }
                        else
                        {
                            //$out .= parseTemplateRecursive($convoArr, $childNode, $childName, $out, $level + 1);
                            echo 'We should never get here.';
                        }
                        break;
                    default:
                        $out .= 'This is NOT where we need to be!';
                }
            }
        }
        else
        {
            $func = "parse_{$elementName}_tag";
            if (function_exists($func)) $out .= $func($convoArr, $element) . ' ';
            else $out .= "[{$elementName}], ";
        }
    }
    return trim($out, ' ') . ' ';
}

function parse_date_tag(&$convoArr, $element)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a DATE tag.', 0);
    if ($element->hasAttributes())
    {
        $format = $element->getAttribute('format');
        return trim(date($format), ' ') ;
    }
}

function parse_think_tag(&$convoArr, $element)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a THINK tag.', 0);
    $out = '';
    foreach ($element->childNodes as $childNode)
    {
        if ($childNode->nodeValue != null)
        {
            $out .= trim($childNode->nodeValue, ' ') . ' ';
        }
        else
        {
            $childName = $childNode->tagName;
            $func = "parse_{$childName}_tag";
            if (function_exists($func)) $out .= $func($convoArr, $childNode) . ' ';
            else $out .= "[{$elementName}], ";
        }
    }
    return '';
}

function parse_get_tag(&$convoArr, $element)
{
    global $getArray;
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a GET tag.', 0);
    $out = '';
    if (!$element->hasAttributes()) return false;
    $name = $element->getAttribute('name');
    $value = (isset($getArray[$name])) ? $getArray[$name] : 'undefined';
    return trim($value, ' ') . ' ';

}

function parse_set_tag(&$convoArr, $element)
{
    global $getArray;
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a SET tag.', 0);
    $out = '';
    if ($element->hasAttributes())
    {
        $name = $element->getAttribute('name');
    }
    else $name='unknown';
    foreach ($element->childNodes as $childNode)
    {
        if ($childNode->nodeType === XML_TEXT_NODE)
        {
            $out .= trim($childNode->nodeValue, ' ') . ' ';
        }
        else
        {
            $childName = $childNode->tagName;
            $func = "parse_{$childName}_tag";
            if (function_exists($func)) $out .= $func($convoArr, $childNode) . ' ';
            else $out .= "[{$childName}], ";
        }
    }
    $getArray[$name] = trim($out);
    //file_put_contents('set.txt', "The value of $name has been set to $out.\n");
    runDebug(__FILE__, __FUNCTION__, __LINE__, "The value of $name has been set to $out.", 0);
    return trim($out, ' ') . ' ';
}

function runDebug($file = 'unknown', $function = 'unknown', $line = 'unknown', $message = '', $debug_level = 4)
{
    $file = str_replace('P:\HTTP\tmp\DOMIterator\\', '', $file);
    $outMessage = "[$file][$function][$line]: $message\n";
    //echo $outMessage;
    error_log($outMessage, 3, 'debug.txt');
}

function parse_random_tag(&$convoArr, $element)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a RANDOM tag.', 0);
    $liTags = $element->getElementsByTagName('li');
    print_r($liTags, true);
    $liCount = $liTags->length;
    $picked = array();
    foreach ($liTags as $item)
    {
        $picked[] = $item;
    }
    $idx = array_rand($picked);
    $pickedTag = $picked[$idx];
    $newdoc = new DOMDocument();
    $cloned = $pickedTag->cloneNode(TRUE);
    $newdoc->appendChild($newdoc->importNode($cloned,TRUE));
    $test = $newdoc->saveXML();
    //exit(print_r($pickedTag, true));
    $out = '';
    if ($pickedTag->nodeType === XML_TEXT_NODE)
    {
        $out .= trim($pickedTag->nodeValue, ' ') . ' ';
    }
    elseif ($pickedTag->nodeType === XML_ELEMENT_NODE)
    {
        //$out .= trim($pickedTag->nodeValue, ' ');
        return trim($pickedTag->nodeValue, ' ') . ' ';
    }
    else
    {
        foreach ($pickedTag as $childNode)
        {
            if ($childNode->nodeType === XML_TEXT_NODE)
            {
                $out .= trim($childNode->nodeValue, ' ') . ' ';
            }
            else
            {
                $childName = $childNode->tagName;
                $func = "parse_{$childName}_tag";
                if (function_exists($func)) $out .= $func($convoArr, $childNode) . ' ';
                else $out .= "[{$childName}], ";
            }
        }
    }
    //exit("Random selection: out = $out");
    runDebug(__FILE__, __FUNCTION__, __LINE__, "Random selection: out = $out", 0);
    return trim($out, ' ') . ' ';
}







