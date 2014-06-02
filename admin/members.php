<?php
//-----------------------------------------------------------------------------------------------
//My Program-O Version: 2.4.2
//Program-O  chatbot admin area
//Written by Elizabeth Perreau and Dave Morton
//DATE: MAY 17TH 2014
//for more information and support please visit www.program-o.com
//-----------------------------------------------------------------------------------------------
// members.php
  ini_set('memory_limit','128M');
  ini_set('max_execution_time','0');
  $post_vars = filter_input_array(INPUT_POST);

  $user_name = '';
  $action = (isset($post_vars['action'])) ? ucfirst(strtolower($post_vars['action'])) : 'Add';
  if (!empty($post_vars)) {
    $msg = save($action);
    #$action = ($action == 'editfromlist') ? 'Edit' : $action;
  }

  $id = (isset($post_vars['id']) and $action != 'Add') ? $post_vars['id'] : getNextID();
  $id = ($id <= 0) ? getNextID() : $id;
  if (isset($post_vars['memberSelect'])) {
    $id = $post_vars['memberSelect'];
    getMemberData($post_vars['memberSelect']);
  }
  $upperScripts = <<<endScript

    <script type="text/javascript">
<!--
      function showMe() {
        var sh = document.getElementById('showHelp');
        var tf = document.getElementById('membersForm');
        sh.style.display = 'block';
        tf.style.display = 'none';
      }
      function hideMe() {
        var sh = document.getElementById('showHelp');
        var tf = document.getElementById('membersForm');
        sh.style.display = 'none';
        tf.style.display = 'block';
      }
      function showHide() {
        var display = document.getElementById('showHelp').style.display;
        switch (display) {
          case '':
          case 'none':
            return showMe();
            break;
          case 'block':
            return hideMe();
            break;
          default:
            alert('display = ' + display);
        }
      }
//-->
    </script>
endScript;

  $XmlEntities = array(
    '&amp;'  => '&',
    '&lt;'   => '<',
    '&gt;'   => '>',
    '&apos;' => '\'',
    '&quot;' => '"',
  );

  $AdminsOpts   = getAdminsOpts();

  $membersForm       = $template->getSection('MembersForm');
  $members_list_form = $template->getSection('MembersListForm');
  $showHelp          = $template->getSection('MembersShowHelp');

  $topNav            = $template->getSection('TopNav');
  $leftNav           = $template->getSection('LeftNav');
  $main              = $template->getSection('Main');
  $topNavLinks       = makeLinks('top', $topLinks, 12);
  $navHeader         = $template->getSection('NavHeader');
  $leftNavLinks      = makeLinks('left', $leftLinks, 12);
  $FooterInfo        = getFooter();
  $errMsgClass       = (!empty($msg)) ? "ShowError" : "HideError";
  $errMsgStyle       = $template->getSection($errMsgClass);
  $noLeftNav         = '';
  $noTopNav          = '';
  $noRightNav        = $template->getSection('NoRightNav');
  $headerTitle       = 'Actions:';
  $pageTitle         = 'My-Program O - Admin Accounts';
  $mainContent       = $template->getSection('MembersMain');
  $mainTitle         = "Modify Admin Account Data [helpLink]";

  $members_list_form = str_replace('[adminList]', $AdminsOpts, $members_list_form);
  $mainContent       = str_replace('[members_content]', $membersForm, $mainContent);
  $mainContent       = str_replace('[showHelp]', $showHelp, $mainContent);
  $mainContent       = str_replace('[members_list_form]', $members_list_form, $mainContent);
  $mainContent       = str_replace('[user_name]', $user_name, $mainContent);
  $mainContent       = str_replace('[action]', $action, $mainContent);
  $mainContent       = str_replace('[id]', $id, $mainContent);
  $mainTitle         = str_replace('[helpLink]', $template->getSection('HelpLink'), $mainTitle);


  function save($action) {
    global $dbConn, $dbn, $action, $post_vars;
    #return 'action = ' . $action;
    if (isset($post_vars['memberSelect'])) {
      $id = $post_vars['memberSelect'];
    }
    else {
      if (!isset($post_vars['user_name']) or !isset($post_vars['password']) or !isset($post_vars['passwordConfirm'])) return 'You left something out!';
      $id = $post_vars['id'];
      $user_name = $post_vars['user_name'];
      $password1 = $post_vars['password'];
      $password2 = $post_vars['passwordConfirm'];
      $password = md5($password1);
      if ($action != 'Delete' and ($password1 != $password2)) return 'The passwords don\'t match!';
    }
    switch ($action) {
      case 'Add':
      $ip = $_SERVER['REMOTE_ADDR'];
      $sql = "insert into myprogramo (id, user_name, password, last_ip, last_login) values (null, '$user_name', '$password','$ip', CURRENT_TIMESTAMP);";
      $out = "Account for $user_name successfully added!";
      break;
      case 'Delete':
      $action = 'Add';
      $sql = "DELETE FROM `$dbn`.`myprogramo` WHERE `myprogramo`.`id` = $id LIMIT 1";
      $out = "Account for $user_name successfully deleted!";
      break;
      case 'Edit':
      $action = 'Add';
      $sql = "update myprogramo set user_name = '$user_name', password = '$password' where id = $id;";
      $out = "Account for $user_name successfully updated!";
      break;
      default:
      $action = 'Edit';
      $sql = '';
      $out = '';
    }
    //$x = (!empty($sql)) ? updateDB($sql) : '';
    if (!empty($sql))
    {
      save_file(_LOG_PATH_ . 'memberSQL.txt', $sql);
      $sth = $dbConn->prepare($sql);
      $sth->execute();
      $affectedRows = $sth->rowCount();

      //
    }
    #return "action = $action<br />\n SQL = $sql";
    return $out;
  }



    function getAdminsOpts() {
    global $dbn, $dbConn;
    $out = "                  <!-- Start List of Current Admin Accounts -->\n";
    $optionTemplate = "                  <option value=\"[val]\">[key]</option>\n";
    $sql = 'SELECT id, user_name FROM myprogramo order by user_name;';
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $result = $sth->fetchAll();
    foreach ($result as $row) {
      $user_name = $row['user_name'];
      $id = $row['id'];
      $curOption = str_replace('[key]', $row['user_name'], $optionTemplate);
      $curOption = str_replace('[val]', $row['id'], $curOption);
      $out .= $curOption;
    }
    
    $out .= "                  <!-- End List of Current Admin Accounts -->\n";
    return $out;
  }

  function getMemberData($id) {
    if ($id <= 0) return false;
    global $dbn, $user_name, $id, $dbConn;
    $sql = "select id, user_name from myprogramo where id = $id limit 1;";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $row = $sth->fetch();
    $user_name = $row['user_name'];
    $id = $row['id'];
    
  }

  function getNextID() {
    global $dbn, $user_name, $dbConn;
    $sql = "select id from myprogramo order by id desc limit 1;";
    $sth = $dbConn->prepare($sql);
    $sth->execute();
    $row = $sth->fetch();
    $id = $row['id'];
    
    return $id + 1;
  }

?>