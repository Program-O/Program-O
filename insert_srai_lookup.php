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
  $sql = "select id, template from aiml where template like '%<srai>%';";
  $sth = $dbConn->prepare($sql);
  $sth->execute();
  $result = $sth->fetchAll();
  $totalRows = count($result);
  $maxTime = intval($totalRows / 500);
  $totalRows = number_format($totalRows);
  echo "Setting the max execution time to $maxTime seconds.\n\n";
  set_time_limit($maxTime);
  echo("Found $totalRows rows that contain SRAI calls.\n");
  $insertCount = 0;
  $count = 0;
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
        $sql = "select id from aiml where pattern = :srai limit 1;";
        $sth = $dbConn->prepare($sql);
        $sth->bindValue(':srai', $srai);
        $sth->execute();
        $result = $sth->fetch();
        if (!empty($result))
        {
          $id = $result['id'];
          echo "Match found. template id = $id. Let's look to see if it already exists.\n";
          $sql = "select id from srai_lookup where pattern = '$srai';";
          $sth = $dbConn->prepare($sql);
          $sth->execute();
          $result = $sth->fetch();
          if (empty($result))
          {
            echo "Entry does not already exist. Adding it now.\n";
            $sql = "insert into srai_lookup (id, pattern, template_id) values (null, :pattern, :template_id);";
            $sth = $dbConn->prepare($sql);
            $sth->bindValue(':pattern', $srai);
            $sth->bindValue(':template_id', $id);
            $sth->execute();
            $affectedRows = $sth->rowCount();
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