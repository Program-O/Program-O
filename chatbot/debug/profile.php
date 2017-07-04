<?php
  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.6.7
  * FILE: profile.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 07-22-2016
  * DETAILS: Reads the contents of a selected
  * debug file and helps analyze performance bottlenecks
  ***************************************/
  $e_all = defined('E_DEPRECATED') ? E_ALL & ~ E_DEPRECATED : E_ALL;
  error_reporting($e_all);
  ini_set('log_errors', true);
  ini_set('error_log', 'debug.profile.error.log');
  ini_set('html_errors', false);
  ini_set('display_errors', true);
  
  $curFile = filter_input(INPUT_GET,'file');

  header('content-type: text/plain');
  if(!file_exists($curFile)) exit('No matching file for request!');
  $fileContents = file_get_contents($curFile);
  $debugEntries = explode("-----------------------", $fileContents);
  array_walk($debugEntries, function (& $val, & $idx) {$val = trim($val);});
  $profileArray = array();
  foreach ($debugEntries as $index => $entry) {
    // parse timings and collect variables
    @list($timings, $functions, $discard) = explode(PHP_EOL, $entry);
    $timingsSepRegEx = '/\[\d\]\[\d\] - Elapsed time: /';
    $newtimings = preg_replace($timingsSepRegEx, '~', $timings);
    @list($dateTime, $et) = explode('~', $newtimings);
    @list ($date, $time) = explode(' ', $dateTime);

    // parse functions and collect variables
    $functions = trim($functions, '[]'); // remove square brackets around the line
    @list($file, $function, $line) = explode('][', $functions);
    if (empty($et)) continue;
    $profileArray[] = array(
      'Elapsed time' => $et,
      'Entry #' => $index,
      'file name' => $file,
      'function name' => $function,
      'line #' => $line,
      'Entry' => $discard,
    );
  }
  usort($profileArray,'sort_et');
  echo
    "This profiler script gathers all of the information from the debug file you've selected and sorts it by the amount of elapsed
time that each entry has taken in decending order. It then displays the information as an array, listing things like filename,
the name of the function that was executed, the line number of the debug call, and what data was being presented. This will aid
in troubleshooting performance bottlenecks by showing which actions are taking the longest.

Here is the array of data:
";
  print_r($profileArray);


  function sort_et($b, $a)
  {
      $y = floatval(str_replace(',','',$b['Elapsed time'])) * 1000000;
      $x = floatval(str_replace(',','',$a['Elapsed time'])) * 1000000;
      return $x - $y;
  }








