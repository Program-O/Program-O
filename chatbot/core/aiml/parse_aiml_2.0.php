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

    /**
     * Parses the AIML 2.0 <interval> tag (experimental)
     *
     * @param array $convoArr
     * @param SimpleXMLElement $element
     * @param string $parentName
     * @param int $level
     * @return string
     */
    function parse_interval_tag($convoArr, $element, $parentName, $level)
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing an INTERVAL tag.', 2);

        $from = null;
        $to = null;
        $format = isset($element->attributes()->format) ? $element->attributes()->format : '';
        $style = '';

        /** @var SimpleXMLElement $child */
        foreach ($element->children() as $child)
        {
            $response = parseTemplateRecursive($convoArr, $child, $level + 1);
            $response = implode_recursive('', $response, __FILE__, __FUNCTION__, __LINE__);

            switch ($child->getName())
            {
                case 'format':
                    $format = $response;
                    break;
                case 'style':
                    $style = $response;
                    break;
                case 'from':
                    $from = new DateTime($response);
                    break;
                case 'to':
                    $to = new DateTime($response);
                    break;
            }
        }

        return $from->diff($to)->format($format);
    }

    /**
     * Parses the AIML 2.0 <size/> tag
     *
     * @param array $convoArr
     * @param SimpleXMLElement $element
     * @param string $parentName
     * @param int $level
     * @return string|array
     */
    function parse_size_tag($convoArr, $element, $parentName, $level)
    {
        global $dbn;

        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a SIZE tag.', 2);

        $bot_id = $convoArr['conversation']['bot_id'];

        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlDialectInspection */
        $sql = "SELECT COUNT(*) as `total` FROM `$dbn`.`aiml` WHERE `bot_id` = $bot_id;";

        $row = db_fetch($sql, null, __FILE__, __FUNCTION__, __LINE__);

        return $row['total'];
    }

    /**
     * Parses the AIML 2.0 <explode> tag
     *
     * @param array $convoArr
     * @param SimpleXMLElement $element
     * @param string $parentName
     * @param int $level
     * @return string|array
     */
    function parse_explode_tag($convoArr, $element, $parentName, $level)
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing an EXPLODE tag.', 2);

        $response = tag_to_string($convoArr, $element, $parentName, $level, 'element');
        $response = preg_split('//u', $response, -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', $response);
    }

    /**
     * Parses the AIML 2.0 <first> tag
     *
     * @param array $convoArr
     * @param SimpleXMLElement $element
     * @param string $parentName
     * @param int $level
     * @return string|array
     */
    function parse_first_tag($convoArr, $element, $parentName, $level)
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a FIRST tag.', 2);

        $response = tag_to_string($convoArr, $element, $parentName, $level, 'element');
        $response = explode(' ', $response);

        return $response[0];
    }

    /**
     * Parses the AIML 2.0 <rest> tag
     *
     * @param array $convoArr
     * @param SimpleXMLElement $element
     * @param string $parentName
     * @param int $level
     * @return string|array
     */
    function parse_rest_tag($convoArr, $element, $parentName, $level)
    {
        runDebug(__FILE__, __FUNCTION__, __LINE__, 'Parsing a REST tag.', 2);

        $response = tag_to_string($convoArr, $element, $parentName, $level, 'element');
        $response = explode(' ', $response);

        return array_slice($response, 1);
    }
/*
 */