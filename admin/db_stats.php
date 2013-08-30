<?php
//-----------------------------------------------------------------------------------------------
//My Program-O Version: 2.3.1
//Program-O  chatbot admin area
//Written by Elizabeth Perreau and Dave Morton
//Aug 2011
//for more information and support please visit www.program-o.com
//-----------------------------------------------------------------------------------------------
// demochat.php
  $upperScripts  = '';
  $topNav        = $template->getSection('TopNav');
  $leftNav       = $template->getSection('LeftNav');
  $main          = $template->getSection('Main');
  $topNavLinks   = makeLinks('top', $topLinks, 12);
  $navHeader     = $template->getSection('NavHeader');
  $leftNavLinks  = makeLinks('left', $leftLinks, 12);
  $FooterInfo    = getFooter();
  $errMsgClass   = (!empty($msg)) ? "ShowError" : "HideError";
  $errMsgStyle   = $template->getSection($errMsgClass);
  $noLeftNav     = '';
  $noTopNav      = '';
  $noRightNav    = $template->getSection('NoRightNav');
  $headerTitle   = 'Actions:';
  $pageTitle     = 'My-Program O - Database Statistics';
  $mainContent   = "Database stats for $dbn";
  $mainContent   = showStats();
  $mainTitle     = 'DB Stats';

  function showStats()
  {
    global $template, $dbn;
    $dbConn = db_open();
    $stats = mysql_stat($dbConn);
    list($ut, $th, $q, $sq, $o, $ft, $ot, $qps) = explode('  ', $stats);
    $out = $template->getSection('DbStats');
    list($nul, $utd) = explode(': ', $ut);
    list($nul, $thd) = explode(': ', $th);
    list($nul, $qd) = explode(': ', $q);
    list($nul, $sqd) = explode(': ', $sq);
    list($nul, $ftd) = explode(': ', $ft);
    list($nul, $otd) = explode(': ', $ot);
    list($nul, $qpsd) = explode(': ', $qps);

    $out = str_replace('[stats_uptime]', seconds2YDHMS($utd), $out);
    $out = str_replace('[stats_threads]', $thd, $out);
    $out = str_replace('[stats_query_count]', number_format($qd), $out);
    $out = str_replace('[stats_slow_queries]', $sqd, $out);
    $out = str_replace('[stats_flush_count]', $ftd, $out);
    $out = str_replace('[stats_tables_open]', $otd, $out);
    $out = str_replace('[stats_qps]', 0+$qpsd, $out);
    $out = str_replace('[dbn]', $dbn, $out);
    $out .= <<<endComment

<!-- Raw Stats: $stats -->
endComment;
    return $out;
  }

  function seconds2YDHMS($seconds)
  {
    $secPerMin = 60;
    $secPerHour = 3600;
    $secPerDay = 86400;
    $secPerYear = 31557600; //Aproxomate - we're not splitting atoms, here. :)
    $years = (int)floor($seconds / $secPerYear);
    $seconds -= ($years * $secPerYear);
    $days = floor($seconds / $secPerDay);
    $seconds -= ($days * $secPerDay);
    $hours = floor($seconds / $secPerHour);
    $seconds -= ($hours * $secPerHour);
    $minutes = floor($seconds / $secPerMin);
    $seconds -= ($minutes * $secPerMin);
    $out = "$years years, $days days, $hours hours, $minutes minutes, $seconds seconds";
    return $out;
  }

?>
