<?php
/***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.4.4
  * FILE: misc_functions.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 05-22-2013
  * DETAILS: Miscelaneous functions for Program O
  ***************************************/

  /**
   * function get_cURL
   * Uses PHP's cURL functions to obtain data from "outside locations"
   *
   * @param (string) $url - The URL or IP address to access
   * @param array $options
   * @param array $params
   * @return mixed|string (string) $out - The returned value from the curl_exec() call.
   */

  function get_cURL($url, $options = array(), $params = array())
  {
    $failed = 'Cannot process CURL call.'; // This will need to be changed, at some point.
    if (function_exists('curl_init')) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      if (is_array($options) and count($options) > 0)
      {
        foreach ($options as $key => $value)
        {
          curl_setopt($ch, $key, $value);
        }
      }
      if (is_array($params) and count($params) > 0)
      {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
      }
      $data = curl_exec($ch);
      curl_close($ch);
      return $data;
    }
    else return $failed;
  }

  /**
   * function normalize_text
   * Transforms text to uppercase, removes all punctuation, and strips extra whitespace
   *
   * @param (string) $text - The text to perform the transformations on
   * @return mixed|string (string) $normalized_text - The completely transformed text
   */
    function normalize_text($text)
    {
      runDebug(__FILE__, __FUNCTION__, __LINE__,"Begin normalization - text = '$text'", 4);
      $normalized_text = preg_replace('/[[:punct:]]/uis', ' ', $text);
      $normalized_text = preg_replace('/\s\s+/', ' ', $normalized_text);
      $normalized_text = (IS_MB_ENABLED) ? mb_strtoupper($normalized_text) : strtoupper($normalized_text);
      $normalized_text = trim($normalized_text);
      runDebug(__FILE__, __FUNCTION__, __LINE__,"Normalization complete. Text = '$normalized_text'", 4);
      return $normalized_text;
    }


?>