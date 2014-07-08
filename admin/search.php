<?php
  /***************************************
    * http://www.program-o.com
    * PROGRAM O
    * Version: 2.4.3
    * FILE: search.php
    * AUTHOR: Elizabeth Perreau and Dave Morton
    * DATE: 05-26-2014
    * DETAILS: Search the AIML table of the DB for desired categories
    ***************************************/

  $post_vars = filter_input_array(INPUT_POST);
  $get_vars = filter_input_array(INPUT_GET);
  $form_vars = array_merge((array)$post_vars, (array)$get_vars);

  $group = (isset($get_vars['group'])) ? $get_vars['group'] : 1;

  if((isset($post_vars['action']))&&($post_vars['action']=="search")) {
    $mainContent = $template->getSection('SearchAIMLForm');
    $mainContent .= runSearch();
    $mainContent = str_replace('[group]', $group, $mainContent);
  }
  elseif((isset($get_vars['group']))) {
    $mainContent = $template->getSection('SearchAIMLForm');
    $mainContent .= runSearch();
    $mainContent = str_replace('[group]', $group, $mainContent);
  }
  elseif((isset($post_vars['action']))&&($post_vars['action']=="update")) {
    $mainContent = $template->getSection('SearchAIMLForm');
    $mainContent .= updateAIML();
    $mainContent = str_replace('[group]', $group, $mainContent);
  }
  elseif((isset($get_vars['action']))&&($get_vars['action']=="del")&&(isset($get_vars['id']))&&($get_vars['id']!="")) {
    $mainContent = $template->getSection('SearchAIMLForm');
    $mainContent .= delAIML($get_vars['id']);
  $mainContent = str_replace('[group]', $group, $mainContent);
  }
  elseif((isset($get_vars['action']))&&($get_vars['action']=="edit")&&(isset($get_vars['id']))&&($get_vars['id']!="")) {
    $mainContent = $template->getSection('SearchAIMLForm');
    $mainContent .= editAIMLForm($get_vars['id']);
  $mainContent = str_replace('[group]', $group, $mainContent);
  }
  else {
    $mainContent = $template->getSection('SearchAIMLForm');
  $mainContent = str_replace('[group]', $group, $mainContent);
  }
  $mainContent = str_replace('[group]', $group, $mainContent);

  $upperScripts = '<script type="text/javascript" src="scripts/tablesorter.min.js"></script>'."\n";
  $topNav        = $template->getSection('TopNav');
  $leftNav       = $template->getSection('LeftNav');
  $rightNav      = $template->getSection('RightNav');
  $main          = $template->getSection('Main');
  
  $navHeader     = $template->getSection('NavHeader');
  
  $FooterInfo    = getFooter();
  $errMsgClass   = (!empty($msg)) ? "ShowError" : "HideError";
  $errMsgStyle   = $template->getSection($errMsgClass);
  $noLeftNav     = '';
  $noTopNav      = '';
  $noRightNav    = $template->getSection('NoRightNav');
  $headerTitle   = 'Actions:';
  $pageTitle     = 'My-Program O - Search/Edit AIML';
  $mainTitle     = 'Search/Edit AIML';

  /**
   * Function delAIML
   *
   * * @param $id
   * @return string
   */
  function delAIML($id) {
    global $dbConn;
    if($id!="") {
      $sql = "DELETE FROM `aiml` WHERE `id` = '$id' LIMIT 1";
      $sth = $dbConn->prepare($sql);
      $sth->execute();
      $affectedRows = $sth->rowCount();

      if($affectedRows == 0) {
        $msg = 'Error AIML couldn\'t be deleted - no changes made.</div>';
      }
      else {
        $msg = 'AIML has been deleted.';
      }
    }
    else {
      $msg = 'Error AIML couldn\'t be deleted - no changes made.';
    }
    return $msg;
  }


  /**
   * Function runSearch
   *
   *
   * @return mixed|string
   */
  function runSearch()
  {
    global $bot_id, $bot_name, $form_vars, $dbConn, $group;
    //exit("group = $group");
    $i=0;
    $searchTermsTemplate = " like '[value]' or\n  ";
    $searchTerms = '';
    $search_fields = array('search_topic', 'search_filename','search_pattern','search_template','search_that');
    $qs = '';
    foreach ($search_fields as $index)
    {
      $$index = trim($form_vars[$index]);
      if (!empty($form_vars[$index]))
      {
        $ue = urlencode($form_vars[$index]);
        $qs .= "&amp;$index=$ue";
      }
    }
    if(!empty($search_topic) or !empty($search_filename) or !empty($search_pattern) or !empty($search_template) or !empty($search_that))
    {
      $limit = ($group - 1) * 50;
      $limit = ($limit < 0) ? 0 : $limit;
      $sql = "SELECT * FROM `aiml` WHERE `bot_id` = '$bot_id'  AND (\n  [searchTerms]\n) order by id limit $limit, 50;";
      $searchTerms .= (!empty($search_topic)) ? '`topic`' . str_replace('[value]', $search_topic, $searchTermsTemplate) : '';
      $searchTerms .= (!empty($search_filename)) ? '`filename`' . str_replace('[value]', $search_filename, $searchTermsTemplate) : '';
      $searchTerms .= (!empty($search_pattern)) ? '`pattern`' . str_replace('[value]', $search_pattern, $searchTermsTemplate) : '';
      $searchTerms .= (!empty($search_template)) ? '`template`' . str_replace('[value]', $search_template, $searchTermsTemplate) : '';
      $searchTerms .= (!empty($search_that)) ? '`thatpattern`' . str_replace('[value]', $search_that, $searchTermsTemplate) : '';
      $searchTerms = rtrim($searchTerms, " or\n ");
      $countSQL = "SELECT count(id) FROM `aiml` WHERE `bot_id` = '$bot_id'  AND (\n  [searchTerms]\n);";
      $countSQL = str_replace('[searchTerms]', $searchTerms, $countSQL);
      $sth = $dbConn->prepare($countSQL);
      $sth->execute();
      $row = $sth->fetch();
      $resCount = searchPaginate($row['count(id)'], $group, $qs);
      $sql = str_replace('[searchTerms]', $searchTerms, $sql);
      //trigger_error("SQL = $sql");
      $sth = $dbConn->prepare($sql);
      $sth->execute();
      $result = $sth->fetchAll();
      $rowCount = count($result);
      $htmltbl = <<<endtHead
          <table width="99%" border="1" cellpadding="1" cellspacing="1">
            <thead>
              <tr>
                <th class="sortable">Topic</th>
                <th class="sortable">Previous Bot Response</th>
                <th class="sortable">User Input</th>
                <th class="sortable">Bot Response</th>
                <th class="sortable">Filename</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
endtHead;
      foreach ($result as $row)
      {
        $i++;
        $topic = $row['topic'];
        $pattern = $row['pattern'];
        $thatpattern = $row['thatpattern'];
        $template = htmlentities($row['template'],ENT_COMPAT,'UTF-8');
        $filename = $row['filename'];
        $id = $row['id'];
        $action = <<<endLink
          <a href="index.php?page=search&amp;action=edit&amp;id=$id">
            <img src="images/edit.png" border=0 width="15" height="15" alt="Edit this entry" title="Edit this entry" />
          </a>
          <a href="index.php?page=search&amp;action=del&amp;id=$id" onclick="return confirm('Do you really want to delete this AIML record? You will not be able to undo this!')";>
            <img src="images/del.png" border=0 width="15" height="15" alt="Delete this entry" title="Delete this entry" />
          </a>
endLink;

      $htmltbl .= <<<endRow
            <tr valign=top>
              <td>$topic</td>
              <td>$thatpattern</td>
              <td>$pattern</td>
              <td>$template</td>
              <td>$filename</td>
              <td align=center>$action</td>
            </tr>
endRow;
    }
        $htmltbl .= "          </tbody>\n        </table>";
      if($i > 0)
      {
        //$msg = "Found more than 50 results for your specified search terms. please refine your search further";
        $msg = $resCount;
      }
      else
      {
        $msg = "Found 0 results for your specified search terms. please try again";
        $htmltbl="";
      }
      $htmlresults = "<div id=\"pTitle\">$msg</div>".$htmltbl;
    }
    else {
      $htmlresults =  'Please enter a search term in any one of the available search boxes.';
    }
    $htmlresults = str_replace('[group]', $group, $htmlresults);
    return $htmlresults;
  }


  /**
   * Function editAIMLForm
   *
   * * @param $id
   * @return mixed|string
   */
  function editAIMLForm($id) {
    //db globals
    global $template, $dbConn, $group;
    $sql = "SELECT * FROM `aiml` WHERE `id` = '$id' LIMIT 1";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $row = $sth->fetch();
    $topic = $row['topic'];
    $pattern = $row['pattern'];
    $thatpattern = $row['thatpattern'];
    $row_template = htmlentities($row['template'],ENT_COMPAT,'UTF-8');
    $filename = $row['filename'];
    $id = $row['id'];
    $form = $template->getSection('EditAIMLForm');
    $form = str_replace('[id]', $id, $form);
    $form = str_replace('[topic]', $topic, $form);
    $form = str_replace('[pattern]', $pattern, $form);
    $form = str_replace('[thatpattern]', $thatpattern, $form);
    $form = str_replace('[template]', $row_template, $form);
    $form = str_replace('[filename]', $filename, $form);
    $form = str_replace('[group]', $group, $form);
    return $form;
  }

  /**
   * Function updateAIML
   *
   *
   * @return string
   */
  function updateAIML() {
    global $post_vars, $dbConn;
    $template = trim($post_vars['template']);
    $filename = trim($post_vars['filename']);
    $pattern = (IS_MB_ENABLED) ? mb_strtoupper(trim($post_vars['pattern'])) : strtoupper(trim($post_vars['pattern']));
    $thatpattern = (IS_MB_ENABLED) ? mb_strtoupper(trim($post_vars['thatpattern'])) : strtoupper(trim($post_vars['thatpattern']));
    $topic = (IS_MB_ENABLED) ? mb_strtoupper(trim($post_vars['topic'])) : strtoupper(trim($post_vars['topic']));
    $id = trim($post_vars['id']);
    if(($template == "")||($pattern== "")||($id=="")) {
      $msg =  'Please make sure you have entered a user input and bot response ';
    }
    else {
      $sql = "UPDATE `aiml` SET `pattern` = '$pattern',`thatpattern`='$thatpattern',`template`='$template',`topic`='$topic',`filename`='$filename' WHERE `id`='$id' LIMIT 1";
      $sth = $dbConn->prepare($sql);
      $sth->execute();
      $affectedRows = $sth->rowCount();
      if($affectedRows > 0) {
        $msg =  'AIML Updated.';
      }
      else {
        $msg =  'There was an error updating the AIML - no changes made.';
      }
    }
    return $msg;
  }

  /**
   * Function searchPaginate
   *
   * * @param $rowCount
   * @param $group
   * @param $qs
   * @return string
   */
  function searchPaginate($rowCount, $group, $qs) {
    $row_count = number_format($rowCount);
    $firstGroup = 1;
    $prevGroup2 = $group - 2;
    $prevGroup1 = $group - 1;
    $curGroup   = $group;
    $nextGroup1 = $group + 1;
    $nextGroup2 = $group + 2;
    $lastGroup = intval($rowCount / 50);
    $remainder = ($rowCount / 50) - $lastGroup;
    if ($remainder > 0) $lastGroup++;
    $out = "Found $row_count Results. Displaying 50 rows per page: ";
    $link = ' - <a class="paginate" href="index.php?page=search&amp;group=[group][qs]">[label]</a>';
    $firstLink = ($group > 1) ? str_replace('[label]', 'First', $link) : 'First';
    $firstLink = str_replace('[group]', 1, $firstLink);

    $prevLink2 = ($prevGroup2 > 0) ? str_replace('[label]', $prevGroup2, $link) : '- ..';
    $prevLink2 = str_replace('[group]', $prevGroup2, $prevLink2);

    $prevLink1 = ($prevGroup1 > 0) ? str_replace('[label]', $prevGroup1, $link) : '- ..';
    $prevLink1 = str_replace('[group]', $prevGroup1, $prevLink1);

    $curLink   = str_replace('[label]', "<b>$curGroup</b>", $link);
    $curLink   = str_replace('[group]', $curGroup, $curLink);

    $nextLink1 = ($nextGroup1 < $lastGroup) ? str_replace('[label]', $nextGroup1, $link) : '- ..';
    $nextLink1 = str_replace('[group]', $nextGroup1, $nextLink1);

    $nextLink2 = ($nextGroup2 < $lastGroup) ? str_replace('[label]', $nextGroup2, $link) : '- ..';
    $nextLink2 = str_replace('[group]', $nextGroup2, $nextLink2);

    $lastLink  = ($group < $lastGroup) ? $link : 'Last';
    $lastLink = str_replace('[label]', 'Last', $lastLink);
    $lastLink  = str_replace('[group]', $lastGroup, $lastLink);

    $out .= "$firstLink\n$prevLink2\n$prevLink1\n$curLink\n$nextLink1\n$nextLink2\n$lastLink\n";
    $out = str_replace('[qs]', $qs, $out);
    return "$out <!-- group = $group -->";
  }


