<?php
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 1);
ini_set('error_log', 'error.log');
require_once('class.DOMIterator.php');

define ('IS_WIN', (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') ? true : false); // Detect Windows OS
define ('BASE_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR); // Set current path
//exit('Base path = ' . BASE_PATH);

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
$dom->preserveWhiteSpace = false;
$dom->load('testTemplate2.xml');
$templates = $dom->getElementsByTagName('template');

$content = '';
foreach ($templates as $template){
    if (!is_null($template)) $content .= parseTemplateRecursive($convoArr, $template);
}
$content .= "\n\nClient properties (get) = array:\n";
array_walk($convoArr['client_properties'], function($value, $key, &$content){
    global $content;
    $content .= "'$key' => '$value'\n";
}, $content);

exit($content);

function parseTemplateRecursive(&$convoArr, DOMNode $element)
{
    $out = '';
    if (!isset($convoArr['response'])) $convoArr['response'] = array();
    if ($element->nodeType === XML_TEXT_NODE)
    {
        $out .= trim($element->nodeValue, ' ');
    }
    else
    {
        $elementName = $element->tagName;
        debugTrace(__FILE__, __FUNCTION__, __LINE__, "Parsing the tag '$elementName'.", 0);
        if ($element->hasChildNodes())
        {
            foreach ($element->childNodes as $childNode)
            {
                $type = $childNode->nodeType;
                switch(true)
                {
                    case ($type === XML_TEXT_NODE):
                        $out .= trim($childNode->nodeValue, ' ');
                        break;
                    case ($type === XML_ELEMENT_NODE):
                        $childName = $childNode->tagName;
                        $func = "parse_{$childName}_tag";
                        if (function_exists($func))
                        {
                            $portion = trim($func($convoArr, $childNode), ' ');
                            debugTrace(__FILE__, __FUNCTION__, __LINE__, "A portion of the output has been parsed. that portion is '$portion'.");
                            $out .= "$portion";
                        }
                        else
                        {
                            //$out .= parseTemplateRecursive($convoArr, $childNode, $childName, $out, $level + 1);
                            //echo "We should never get here.\nNode name = $childName, type = $type.\n";
                            $out .= trim(parseTemplateRecursive($convoArr, $childNode), ' ');
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
            if (function_exists($func)) $out .= $func($convoArr, $element);
            //else $out .= "[{$elementName}], ";
            else $out .= parseTemplateRecursive($convoArr, $element);
        }
    }
    return trim($out, ' ');
}

function parse_date_tag(&$convoArr, $element)
{
    debugTrace(__FILE__, __FUNCTION__, __LINE__, 'Parsing a DATE tag.', 0);
    $now = time();
    if ($element->hasAttributes())
    {
        $locale = getAttribute($convoArr, 'locale', $element);
        $tz     = getAttribute($convoArr, 'timezone', $element);
        $format = getAttribute($convoArr, 'format', $element);
        // Handle timezone first
        if (!empty($tz))
        {
            if (is_numeric($tz) && ($tz >= -12 && $tz <= 12))
            {
                if (!isset($convoArr['timezones']))
                {
                    require_once('tz_list.php');
                    $convoArr['timezones'] = $tz_list;
                }
                if (isset($convoArr['timezones'][$tz]))
                {
                    $cur_tz = date_default_timezone_get();
                    $new_tz = $convoArr['timezones'][$tz];
                    date_default_timezone_set($new_tz);
                }
            }
        }
        // Now work on locale
        if (!empty($locale))
        {
            //LC_TIME
            $cur_locale = setlocale(LC_TIME, '');
        }
        // Finally, deal with format
        if (IS_WIN)
        {
            // windows doesn't support %l, so some "trickery" is required.
            $format = str_replace('%l', date('g', $now), $format);
        }
        // %b %-d%O, %Y %-l:%M %p = 3 char Month 1-2 digit day with ordinal suffix, 4 digit year 12 hour hour:2 digit minute AM/PM
        // %m/%d/%Y %-l:%M %p = 2 digit month/2 digit day/4 digit year 12 hour hour:2 digit minute AM/PM
        // In order to use ordinal suffixes, which strftime does not support, some "trickery" is needed.
        // Since strftime doesn't use %O (percent sigh, capital O) for any formatting options, it can be 'hijacked', and used to make
        // ordinal suffixes possible.
        $format = str_replace('%O', date('S'), $format);
        $out = strftime($format);
    }
    else $out = strftime('%m/%d/%Y %H:%M %p');
    debugTrace(__FILE__, __FUNCTION__, __LINE__, "The date is $out.", 4);
    return trim($out, ' ');
}

function parse_think_tag(&$convoArr, $element)
{
    debugTrace(__FILE__, __FUNCTION__, __LINE__, 'Parsing a THINK tag.', 0);
    $out = '';
    foreach ($element->childNodes as $childNode)
    {
        if ($childNode->nodeType === XML_TEXT_NODE)
        {
            //$out .= trim($childNode->nodeValue, ' ', ' ');
        }
        else
        {
            $childName = $childNode->tagName;
            $func = "parse_{$childName}_tag";
            if (function_exists($func)) $out .= $func($convoArr, $childNode);
            else $out .= "[{$elementName}],";
        }
    }
    return '';
}

function parse_get_tag(&$convoArr, $element)
{
    global $getArray;
    debugTrace(__FILE__, __FUNCTION__, __LINE__, 'Parsing a GET tag.', 0);
    $name = getAttribute($convoArr, 'name', $element);
    debugTrace(__FILE__, __FUNCTION__, __LINE__, "name attribute = $name.", 0);
    $value = (isset($convoArr['client_properties'][$name])) ? $convoArr['client_properties'][$name] : 'undefined';
    debugTrace(__FILE__, __FUNCTION__, __LINE__, "Value for $name found. Returning $value.", 0);
    return trim($value, ' ');

}

function parse_set_tag(&$convoArr, $element)
{
    debugTrace(__FILE__, __FUNCTION__, __LINE__, 'Parsing a SET tag.', 0);
    $asXML = $element->ownerDocument->saveXML($element);
    debugTrace(__FILE__, __FUNCTION__, __LINE__, "set tag contents = '$asXML'.", 0);
    $out = '';
    $name = getAttribute($convoArr, 'name', $element);
    debugTrace(__FILE__, __FUNCTION__, __LINE__, "The name attribute is called $name.", 0);
    if ($element->nodeType === XML_TEXT_NODE)
    {
        $value = trim($element->nodeValue, ' ');
    }
    else
    {
        $value = '';
        foreach ($element->childNodes as $childNode)
        {
            if ($childNode->nodeType === XML_TEXT_NODE)
            {
                $value .= trim($childNode->nodeValue, ' ');
            }
            else
            {
                $childName = $childNode->tagName;
                $func = "parse_{$childName}_tag";
                if (function_exists($func))
                {
                    $piece = trim($func($convoArr, $childNode), ' ');
                    debugTrace(__FILE__, __FUNCTION__, __LINE__, "A piece of the answer is in the $childName tag. it's value is $piece.", 0);
                    $value .= $piece . ' ';
                }
                else
                {
                    $part = parseTemplateRecursive($convoArr, $childNode);
                    debugTrace(__FILE__, __FUNCTION__, __LINE__, "A part of the answer is in the $childName tag. it's value is $part.", 0);
                    $value .= $part . ' ';
                }
            }
        }
    }
    debugTrace(__FILE__, __FUNCTION__, __LINE__, "value for $name is $value.", 0);
    $convoArr['client_properties'][$name] = trim($value, ' ');
    $out = trim($value, ' ');
    //file_put_contents('set.txt', "The value of $name has been set to $out.\n");
    debugTrace(__FILE__, __FUNCTION__, __LINE__, "The value of $name has been set to $out.", 0);
    return trim($out, ' ');
}

function parse_random_tag(&$convoArr, $element)
{
    debugTrace(__FILE__, __FUNCTION__, __LINE__, 'Parsing a RANDOM tag.', 0);
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
        $out .= trim($pickedTag->nodeValue, ' ');
    }
    elseif ($pickedTag->nodeType === XML_ELEMENT_NODE)
    {
        $out .= trim($pickedTag->nodeValue, ' ');
        return trim($out, ' ');
    }
    else
    {
        foreach ($pickedTag as $childNode)
        {
            if ($childNode->nodeType === XML_TEXT_NODE)
            {
                $out .= trim($childNode->nodeValue, ' ');
            }
            else
            {
                $childName = $childNode->tagName;
                $func = "parse_{$childName}_tag";
                if (function_exists($func)) $out .= $func($convoArr, $childNode);
                else $out .= "[{$childName}],";
            }
        }
    }
    //exit("Random selection: out = $out");
    debugTrace(__FILE__, __FUNCTION__, __LINE__, "Random selection: out = $out", 0);
    return trim($out, ' ');
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
    debugTrace(__FILE__, __FUNCTION__, __LINE__, 'Imploding an array into a string. (recursively, if necessary)', 2);
    #debugTrace(__FILE__, __FUNCTION__, __LINE__, "This function was called from $file, function $function at line $line.", 4);
    if (empty($input)) {
        return $glue;
    }

    if (!is_array($input) && !is_string($input))
    {
        $varType = gettype($input);
        trigger_error("Input not array! Input is of type $varType. Error originated in $file, function $function, line $line. Input = " . print_r($input, true));

        return $input;
    }
    elseif (is_string($input))
    {
        return $input;
    }

    debugTrace(__FILE__, __FUNCTION__, __LINE__, 'The variable $input is of type ' . gettype($input), 4);

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
            debugTrace(__FILE__, __FUNCTION__, __LINE__, 'input type: ' . gettype($input), 4);
            $out = (string)$input;
    }

    $out = str_replace('  ', ' ', $out);

    if ($function != 'implode_recursive')
    {
        debugTrace(__FILE__, __FUNCTION__, __LINE__, "Imploding complete. Returning '$out'", 4);
    }

    return ltrim($out, ' ');
}

/**
 * function debugTrace
 * Creates a log entry to a debug file
 *
 * @param  string $file
 * @param  string $function
 * @param  string $line
 * @param  string $message
 * @param  int    $debug_level
 * @return void
 */
function debugTrace($file = 'unknown', $function = 'unknown', $line = 'unknown', $message = '', $debug_level = 4)
{
    if (empty($message)) return false;
    $file = str_replace(BASE_PATH, '', $file);
    $outMessage = "[$file][$function][$line]: $message\n";
    //echo $outMessage;
    error_log($outMessage, 3, 'debug.txt');
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
/*
            while ($childNode->childNodes->length)
            {
                $childNode->removeChild($childNode->firstchild);
            }
*/
            $element->removeChild($childNode);
            $out = parseTemplateRecursive($convoArr, $childNode);

            return trim($out) ;
        }
    }

    return false;
}




