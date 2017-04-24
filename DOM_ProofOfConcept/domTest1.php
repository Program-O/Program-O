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
    ),
    'bot_properties' => array(
        'var1' => 'foo',
        'var2' => 'bar',
        'var3' => 'bat',
        'var4' => 'baz',
    )
);


$dom = new DOMDocument('1.0', 'utf8');
$dom->preserveWhiteSpace = false;
$dom->load('testTemplate2.xml');
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
    if (!isset($convoArr['response'])) $convoArr['response'] = array();
    if ($element->nodeType === XML_TEXT_NODE)
    {
        $out .= trim($element->nodeValue, ' ') . ' '; // trim any trailing spaces, but add one back.
    }
    else
    {
        $elementName = $element->nodeName;
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
                        $childName = $childNode->nodeName;
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

function parse_date_tag(&$convoArr, DOMElement $element)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a DATE tag.', 0);

    $format = getAttribute($convoArr, 'format', $element);

    if (!$format)
    {
        return date('Y/m/d h:i:s'); // default response (<date/>)
    }

    return strftime($format);
}

function parse_think_tag(&$convoArr, DOMElement $element)
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a THINK tag.', 0);
    $out = '';
    foreach ($element->childNodes as $childNode)
    {
        if ($childNode->nodeType === XML_TEXT_NODE)
        {
            //$out .= trim($childNode->nodeValue, ' ') . ' ';
        }
        else
        {
            $childName = $childNode->nodeName;
            $func = "parse_{$childName}_tag";
            if (function_exists($func)) $out .= $func($convoArr, $childNode) . ' ';
            else $out .= "[{$childName}], ";
        }
    }
    return '';
}

function parse_get_tag(&$convoArr, DOMElement $element)
{
    global $getArray;
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a GET tag.', 0);

    $name = getAttribute($convoArr, 'name', $element);

    if (!$name) {
        return false;
    }

    $value = (isset($convoArr['client_properties'][$name])) ? $convoArr['client_properties'][$name] : 'undefined';
    return trim($value, ' ') . ' ';
}

function parse_set_tag(&$convoArr, DOMElement $element)
{
    global $getArray;
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a SET tag.', 0);
    $out = '';
    $name = getAttribute($convoArr, 'name', $element);

    if (!$name) {
        $name = 'unknown';
    }

    foreach ($element->childNodes as $childNode)
    {
        if ($childNode->nodeType === XML_TEXT_NODE)
        {
            $out .= trim($childNode->nodeValue, ' ') . ' ';
        }
        else
        {
            $childName = $childNode->nodeName;
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

function parse_random_tag(&$convoArr, DOMElement $element)
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
    $out = '';
    if ($pickedTag->nodeType === XML_TEXT_NODE)
    {
        $out .= trim($pickedTag->nodeValue, ' ') . ' ';
    }
    elseif ($pickedTag->nodeType === XML_ELEMENT_NODE)
    {
        $out .= trim($pickedTag->nodeValue, ' ');
        return trim($out, ' ') . ' ';
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
                $childName = $childNode->nodeName;
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

/**
 * Implodes a nested array into a single string recursively
 *
 * @param string $glue
 * @param array|string $input
 * @param string $file
 * @param string $function
 * @param string $line
 * @return string
 */
function implode_recursive($glue, $input, $file = 'unknown', $function = 'unknown', $line = 'unknown')
{
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Imploding an array into a string. (recursively, if necessary)', 2);
    if (empty($input)) {
        return $glue;
    }
    if (!is_array($input) && !is_string($input))
    {
        $varType = gettype($input);
        trigger_error("Input not array or string! Input is of type $varType. Error originated in $file, function $function, line $line. Input = " . print_r($input, true));
        return $input;
    }
    elseif (is_string($input))
    {
        return $input;
    }
    runDebug(__FILE__, __FUNCTION__, __LINE__, 'The variable $input is of type ' . gettype($input), 4);
    foreach ($input as $index => $element)
    {
        if (empty ($element)) {
            continue;
        }
        if (is_array($element)) {
            $input[$index] = implode_recursive($glue, $element, __FILE__, __FUNCTION__, __LINE__);
        }
    }
    switch (gettype($input))
    {
        case 'array':
            $out = implode($glue, $input);
            break;
        case 'string':
            $out = $input;
            break;
        default:
            runDebug(__FILE__, __FUNCTION__, __LINE__, 'input type: ' . gettype($input), 4);
            $out = (string)$input;
    }
    $out = str_replace('  ', ' ', $out);

    if ($function != 'implode_recursive')
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Imploding complete. Returning '$out'", 4);
    }
    return ltrim($out);
}

/**
 * Returns the value of an attribute or subtag
 *
 * @param $convoArr
 * @param string $attribute the name of an attribute or subtag
 * @param DOMElement $element
 * @return bool|string
 */
function getAttribute(&$convoArr, $attribute, DOMElement &$element)
{
    if ($element->hasAttribute($attribute))
    {
        return $element->getAttribute($attribute);
    }

    /** @var DOMElement $childNode */
    foreach ($element->childNodes as $childNode)
    {
        if ($childNode->nodeName == $attribute)
        {
            $element->removeChild($childNode);
            $out = parseTemplateRecursive($convoArr, $childNode, $element->nodeName);

            return trim($out, ' ') . ' ';
        }
    }

    return false;
}

function runDebug($file = 'unknown', $function = 'unknown', $line = 'unknown', $message = '', $debug_level = 4)
{
    $file = str_replace('P:\HTTP\tmp\DOMIterator\\', '', $file);
    $outMessage = "[$file][$function][$line]: $message\n";
    error_log($outMessage, 3, 'debug.txt');
}







