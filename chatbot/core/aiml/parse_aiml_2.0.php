<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.5
 * FILE: parse_aiml_2.0.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: FEB 01 2016
 * DETAILS: Handles the parsing of AIML 2.0 tags
 ***************************************/

    runDebug(__FILE__, __FUNCTION__, __LINE__, 'Loading AIML 2.0 functions.', 0);

    function parse_oob_tag ($convoArr, SimpleXMLElement $element, $parentName, $level)
    {
        //save_file(_LOG_PATH_ . 'element_val.txt', print_r($element->asXML(), true), true);
        $eName = $element->getName();
        runDebug(__FILE__, __FUNCTION__, __LINE__, "Parsing OOB element '$eName'.", 4);
        $openingTag = "&lt;{$eName}>";
        $closingTag = "&lt;/{$eName}>";
        $prefix = array($openingTag);
        $suffix = array($closingTag);
        foreach ($element->children() as $child)
        {
            $curName = $child->getName();
            runDebug(__FILE__, __FUNCTION__, __LINE__, "Parsing child element '$curName'.", 4);
            $func = "parse_{$curName}_tag";
            if (function_exists($func))
            {
                runDebug(__FILE__, __FUNCTION__, __LINE__, "Passing element $curName to the $func function.", 4);
                if (count($child->children() > 0)) $result = parseTemplateRecursive($convoArr, $child, $eName, $level + 1);
                else $result = $func($convoArr, $child, $eName, $level + 1);
                runDebug(__FILE__, __FUNCTION__, __LINE__, "Result returned from $func: $result.", 4);
                $prefix[] = $result;
                array_unshift($suffix, '');
            }
            else
            {
                $openingTag = "&lt;{$curName}>";
                $closingTag = "&lt;/{$curName}>";
                runDebug(__FILE__, __FUNCTION__, __LINE__, "Adding '$openingTag' to the response array.", 4);
                $prefix[] = $openingTag;
                array_unshift($suffix, $closingTag);
            }
        }
        $out = implode_recursive('', $prefix) . implode_recursive('', $suffix);
        save_file(_LOG_PATH_ . 'parse_oob_output.txt', $out . PHP_EOL, true);
        return $out;
    }
/*
 */