<?php

  header('Content-type: text/plain');
  error_reporting(E_ALL);
  ini_set('display_errors', true);
  $timeStart = microtime(true);
  echo "This script takes a while to execute. Please be patient.\n";
  $thisFile = __FILE__;
  include ('config/global_config.php');
  include ('library/PDO_functions.php');
  ini_set('log_errors', true);
  ini_set('error_log', _LOG_PATH_ . 'sl.error.log');
  $dbConn = db_open();
  $searchSQL = "select id, template from aiml where template like '%<srai>%';";
  $es = microtime(true);
  $sth = $dbConn->prepare($searchSQL);
  $sth->execute();
  $result = $sth->fetchAll();
  $rowCount = count($result);
  $ct = microtime(true);
  $et = $ct - $es;
  $guess = round($rowCount * $et) + 1;
  $maxTime = intval($rowCount / 300);
  $totalRows = number_format($rowCount);
  echo "Initial query took $et seconds to run.\n";
  echo "Setting the max execution time to $maxTime seconds. We'll also look at setting it to $guess seconds.\n\n";
  //exit();
  set_time_limit($maxTime);
  echo("Found $totalRows rows that contain SRAI calls.\n");
  $insertCount = 0;
  $count = 0;
  $patternSQL = "select id from aiml where pattern = :srai limit 1;";
  $pth = $dbConn->prepare($patternSQL);
  $ltsql = "select id from srai_lookup where pattern = :pattern;";
  $ltsth = $dbConn->prepare($ltsql);
  $insertSQL = "insert into srai_lookup (id, pattern, template_id) values (null, :pattern, :template_id);";
  $isth = $dbConn->prepare($insertSQL);
  foreach ($result as $row)
  {
    $tid = $row['id'];
    $template = trim($row['template']);
    echo("\n SRAI call found. Category id = $tid\n");
    $found = array();
    while ($start = stripos($template, '<srai>', 0) !== false)
    {
      $end = stripos($template, '</srai>', $start);
      $len = $end - $start + 1;
      $srai = substr($template, $start-1, $len);
      $srai = strtoupper($srai);
      $srai = trim(str_ireplace('<srai>', '', $srai));
      echo "srai = '$srai'\n";
      if (strstr($srai,'<') == false)
      {
        echo "Trying to insert '$srai' into the lookup table. Looking for a match, first.\n";
        $pth->bindValue(':srai', $srai);
        $pth->execute();
        $result = $pth->fetch();
        if (!empty($result))
        {
          $id = $result['id'];
          echo "Match found. template id = $id. Let's look to see if it already exists.\n";
          $ltsth->bindValue(':pattern', $srai);
          $ltsth->execute();
          $result = $ltsth->fetch();
          if (empty($result))
          {
            echo "Entry does not already exist. Adding it now.\n";
            $isth->bindValue(':pattern', $srai);
            $isth->bindValue(':template_id', $id);
            $isth->execute();
            $affectedRows = $isth->rowCount();
            if ($affectedRows == 0)
            {
              echo "Something went wrong here.\n";
            }
            else{
              $insertCount++;
              echo number_format($insertCount), " rows inserted so far.\n";
            }
          }
          else echo "There's already an entry for this SRAI pattern. Moving on.\n";
        }
        else echo "No match found. Moving on.\n";
      }
      else echo "srai '$srai' is not a valid candidate. Moving on.\n";
      $template = substr($template, $end);
    }
    $count++;
  }
  $insertCount = number_format($insertCount);
  echo "\nInserted $insertCount new entries into the SRAI lookup table!\n";
  $timeEnd = microtime(true);
  $elapsed = $timeEnd - $timeStart;
  //$elapsed = number_format($elapsed);
  echo "Elapsed time: $elapsed seconds.";