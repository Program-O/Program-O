<?php
//-----------------------------------------------------------------------------------------------
//My Program-O Version: 2.4.0
//Program-O  chatbot admin area
//Written by Elizabeth Perreau and Dave Morton
//DATE: MAY 17TH 2014
//for more information and support please visit www.program-o.com
//-----------------------------------------------------------------------------------------------
// search.php
  $post_vars = filter_input_array(INPUT_POST);
  $get_vars = filter_input_array(INPUT_GET);


  if((isset($post_vars['action']))&&($post_vars['action']=="search")) {
    $mainContent = $template->getSection('SearchAIMLForm');
    $mainContent .= runSearch();
  }
  elseif((isset($post_vars['action']))&&($post_vars['action']=="update")) {
    $mainContent = $template->getSection('SearchAIMLForm');
    $mainContent .= updateAIML();
  }
  elseif((isset($get_vars['action']))&&($get_vars['action']=="del")&&(isset($get_vars['id']))&&($get_vars['id']!="")) {
    $mainContent = $template->getSection('SearchAIMLForm');
    $mainContent .= delAIML($get_vars['id']);
  }
  elseif((isset($get_vars['action']))&&($get_vars['action']=="edit")&&(isset($get_vars['id']))&&($get_vars['id']!="")) {
    $mainContent = $template->getSection('SearchAIMLForm');
    $mainContent .= editAIMLForm($get_vars['id']);
  }
  else {
    $mainContent = $template->getSection('SearchAIMLForm');
  }

  $upperScripts = '<script type="text/javascript" src="scripts/tablesorter.min.js"></script>'."\n";
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
  $pageTitle     = 'My-Program O - Search/Edit AIML';
  $mainTitle     = 'Search/Edit AIML';

  function delAIML($id) {
    global $dbConn;
    if($id!="") {
      $sql = "DELETE FROM `aiml` WHERE `id` = '$id' LIMIT 1";
      $result = db_query($sql, $dbConn);
      if(!$result) {
        $msg = 'Error AIML couldn\'t be deleted - no changes made.</div>';
      }
      else {
        $msg = 'AIML has been deleted.';
      }
    }
    else {
      $msg = 'Error AIML couldn\'t be deleted - no changes made.';
    }
    db_close($dbConn);
    return $msg;
  }


  function runSearch() {
    global $bot_id, $bot_name, $post_vars, $dbConn;
    $i=0;
    $searchTermsTemplate = " like '[value]' or\n  ";
    $searchTerms = '';
    $search_topic    = db_escape_string(trim($post_vars['search_topic']));
    $search_filename = db_escape_string(trim($post_vars['search_filename']));
    $search_pattern  = db_escape_string(trim($post_vars['search_pattern']));
    $search_template = db_escape_string(trim($post_vars['search_template']));
    $search_that     = db_escape_string(trim($post_vars['search_that']));
    if(!empty($search_topic) or !empty($search_filename) or !empty($search_pattern) or !empty($search_template) or !empty($search_that)) {
      $sql = "SELECT * FROM `aiml` WHERE `bot_id` = '$bot_id'  AND (\n  [searchTerms]\n) LIMIT 50;";
      $searchTerms .= (!empty($search_topic)) ? '`topic`' . str_replace('[value]', $search_topic, $searchTermsTemplate) : '';
      $searchTerms .= (!empty($search_filename)) ? '`filename`' . str_replace('[value]', $search_filename, $searchTermsTemplate) : '';
      $searchTerms .= (!empty($search_pattern)) ? '`pattern`' . str_replace('[value]', $search_pattern, $searchTermsTemplate) : '';
      $searchTerms .= (!empty($search_template)) ? '`template`' . str_replace('[value]', $search_template, $searchTermsTemplate) : '';
      $searchTerms .= (!empty($search_that)) ? '`thatpattern`' . str_replace('[value]', $search_that, $searchTermsTemplate) : '';
      $searchTerms = rtrim($searchTerms, " or\n ");
      $sql = str_replace('[searchTerms]', $searchTerms, $sql);
      $result = db_query($sql, $dbConn);
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
      while($row=db_fetch_assoc($result)) {
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
      db_close($dbConn);
      $htmltbl .= "          </tbody>\n        </table>";
      if($i == 50) {
        $msg = "Found more than 50 results for your specified search terms. please refine your search further";
      }
      elseif($i == 0) {
        $msg = "Found 0 results for your specified search terms. please try again";
        $htmltbl="";
      }
      else {
        $msg = "Found $i results for your specified search terms.";
      }
      $htmlresults = "<div id=\"pTitle\">$msg</div>".$htmltbl;
    }
    else {
      $htmlresults =  'Please enter a search term in any one of the available search boxes.';
    }
    return $htmlresults;
  }


  function editAIMLForm($id) {
    //db globals
    global $template, $dbConn;
    $sql = "SELECT * FROM `aiml` WHERE `id` = '$id' LIMIT 1";
    $result = db_query($sql, $dbConn);
    $row=db_fetch_assoc($result);
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
    db_close($dbConn);
    return $form;
  }

  function updateAIML() {
    global $post_vars, $dbConn;
    $template = db_escape_string(trim($post_vars['template']));
    $filename = db_escape_string(trim($post_vars['filename']));
    $pattern = (IS_MB_ENABLED) ? mb_strtoupper(db_escape_string(trim($post_vars['pattern']))) : strtoupper(db_escape_string(trim($post_vars['pattern'])));
    $thatpattern = (IS_MB_ENABLED) ? mb_strtoupper(db_escape_string(trim($post_vars['thatpattern']))) : strtoupper(db_escape_string(trim($post_vars['thatpattern'])));
    $topic = (IS_MB_ENABLED) ? mb_strtoupper(db_escape_string(trim($post_vars['topic']))) : strtoupper(db_escape_string(trim($post_vars['topic'])));
    $id = trim($post_vars['id']);
    if(($template == "")||($pattern== "")||($id=="")) {
      $msg =  'Please make sure you have entered a user input and bot response ';
    }
    else {
      $sql = "UPDATE `aiml` SET `pattern` = '$pattern',`thatpattern`='$thatpattern',`template`='$template',`topic`='$topic',`filename`='$filename' WHERE `id`='$id' LIMIT 1";
      $result = db_query($sql, $dbConn);
      if($result) {
        $msg =  'AIML Updated.';
      }
      else {
        $msg =  'There was an error updating the AIML - no changes made.';
      }
    }
    db_close($dbConn);
    return $msg;
  }

?>
