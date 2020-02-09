<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.11
 * FILE: spellcheck.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 12-09-2014
 * DETAILS: Displays the admin page for the spellcheck plugin and provides access to various features
 ***************************************/
require_once(_LIB_PATH_ . 'misc_functions.php');
$msg = '';

$options = $allowed_pages['spellcheck'];
$form_vars = clean_inputs($options);
$group = (isset($form_vars['group'])) ? $form_vars['group'] : 1;
$content = $template->getSection('SearchSpellForm');
$sc_action = isset($form_vars['action']) ? strtolower($form_vars['action']) : '';
$sc_id = isset($form_vars['id']) ? $form_vars['id'] : -1;

if (!empty($sc_action))
{
    switch ($sc_action)
    {
        case 'search':
            $content .= runSpellSearch();
            $content .= spellCheckForm();
            break;
        case 'update':
            updateSpell();
            $content .= spellCheckForm();
            break;
        case 'delete':
            $content .= ($sc_id >= 0) ? delSpell($sc_id) . spellCheckForm() : spellCheckForm();
            break;
        case 'edit':
            $content .= ($sc_id >= 0) ? editSpellForm($sc_id) : spellCheckForm();
            break;
        case 'add':
            $x = insertSpell();
            $content .= spellCheckForm();
            break;
        default:
            $content .= spellCheckForm();
    }
}
else {
    $content .= spellCheckForm();
}

$content = str_replace('[group]', $group, $content);
$sc_enabled = (USE_SPELL_CHECKER) ? 'enabled' : 'disabled';

$topNav         = $template->getSection('TopNav');
$leftNav        = $template->getSection('LeftNav');
$main           = $template->getSection('Main');

$navHeader      = $template->getSection('NavHeader');
$rightNav       = $template->getSection('RightNav');

$rightNavLinks  = getMisspelledWords();
$FooterInfo     = getFooter();
$errMsgClass    = (!empty($msg)) ? "ShowError" : "HideError";
$errMsgStyle    = $template->getSection($errMsgClass);

$noLeftNav      = '';
$noTopNav       = '';
$noRightNav     = '';

$headerTitle    = 'Actions:';
$pageTitle      = 'My-Program O - Spellcheck Editor';
$mainContent    = $content;
$mainTitle      = 'Spellcheck Editor';

$mainContent = str_replace('[spellCheckForm]', spellCheckForm(), $mainContent);
$mainContent = str_replace('[sc_enabled]', $sc_enabled, $mainContent);
$rightNav    = str_replace('[rightNavLinks]', $rightNavLinks, $rightNav);
$rightNav    = str_replace('[navHeader]', $navHeader, $rightNav);
$rightNav    = str_replace('[headerTitle]', scPaginate(), $rightNav);

/**
 * Function scPaginate
 *
 * @return string
 */
function scPaginate()
{
    global $group;

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT COUNT(*) FROM `spellcheck`";
    $row = db_fetch($sql,null, __FILE__, __FUNCTION__, __LINE__);

    $rowCount = $row['COUNT(*)'];
    $lastPage = intval($rowCount / 50);
    $remainder = ($rowCount / 50) - $lastPage;

    if ($remainder > 0)
    {
        $lastPage++;
    }

    $out = "Missspelled Words<br />\n50 words per page:<br />\n";
    $link = " - <a class=\"paginate\" href=\"index.php?page=spellcheck&amp;group=[group]\">[label]</a>";
    $curStart = $group;
    $firstPage = 1;

    $prev = ($curStart > ($firstPage + 1)) ? $curStart - 1 : -1;
    $next = ($lastPage > ($curStart + 1)) ? $curStart + 1 : -1;

    $firstLink = ($firstPage != $curStart) ? str_replace('[group]', '1', $link) : '';
    $prevLink = ($prev > 0) ? str_replace('[group]', $prev, $link) : '';
    $curLink = "- $curStart ";

    if (empty($firstLink) && empty($prevLink))
    {
        $curLink = $curStart;
    }

    $nextLink = ($next > 0) ? str_replace('[group]', $next, $link) : '';
    $lastLink = ($lastPage > $curStart) ? str_replace('[group]', $lastPage, $link) : '';

    $firstLink = str_replace('[label]', 'first', $firstLink);
    $prevLink = str_replace('[label]', '&lt;&lt;', $prevLink);
    $nextLink = str_replace('[label]', '&gt;&gt;', $nextLink);
    $lastLink = str_replace('[label]', 'last', $lastLink);

    $out .= ltrim("$firstLink\n$prevLink\n$curLink\n$nextLink\n$lastLink", " - ");

    return $out;
}

/**
 * Function getMisspelledWords
 *
 * @return string
 */
function getMisspelledWords()
{
    global $template, $group, $form_vars;

    # pagination variables
    $_SESSION['poadmin']['group'] = $group;
    $startEntry = abs(($group - 1) * 50);
    $end = $group + 50;
    $_SESSION['poadmin']['page_start'] = $group;

    $curID = (isset($form_vars['id'])) ? $form_vars['id'] : -1;

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT `id`,`missspelling` FROM `spellcheck` limit $startEntry, 50;";

    $baseLink = $template->getSection('NavLink');
    $links = '      <div class="userlist">' . "\n";
    $result = db_fetchAll($sql,null, __FILE__, __FUNCTION__, __LINE__);
    $count = 0;

    foreach ($result as $row)
    {
        $linkId = $row['id'];
        $linkClass = ($linkId == $curID) ? 'selected' : 'noClass';

        $missspelling = $row['missspelling'];
        $tmpLink = str_replace('[linkClass]', " class=\"$linkClass\"", $baseLink);
        $linkHref = " href=\"index.php?page=spellcheck&amp;action=edit&amp;id=$linkId&amp;group=$group#$linkId\" name=\"$linkId\"";

        $tmpLink = str_replace('[linkHref]', $linkHref, $tmpLink);
        $tmpLink = str_replace('[linkOnclick]', '', $tmpLink);
        $tmpLink = str_replace('[linkTitle]', " title=\"Edit spelling correction for the word '$missspelling'\"", $tmpLink);
        $tmpLink = str_replace('[linkLabel]', $missspelling, $tmpLink);

        $links .= "$tmpLink\n";
        $count++;
    }

    $page_count = intval($count / 50);
    $_SESSION['poadmin']['page_count'] = $page_count + (($count / 50) > $page_count) ? 1 : 0;
    $links .= "\n      </div>\n";

    return $links;
}

/**
 * Function spellCheckForm
 *
 * @return mixed|string
 */
function spellCheckForm()
{
    global $template, $group;

    $out = $template->getSection('SpellcheckForm');
    $out = str_replace('[group]', $group, $out);

    return $out;
}

/**
 * Function insertSpell
 *
 * @return string
 */
function insertSpell()
{
    global $template, $msg, $form_vars;
    $correction = trim($form_vars['correction']);
    $missspell = trim($form_vars['missspell']);

    if (($correction == "") || ($missspell == ""))
    {
        $msg = '        <div id="errMsg">You must enter a spelling mistake and the correction.</div>' . "\n";
    }
    else {
        /** @noinspection SqlDialectInspection */
        $sql = "INSERT INTO `spellcheck` VALUES (:missspell, :correction)";
        $params = array(
            ':missspell' => $missspell,
            ':correction' => $correction
        );
        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);

        if ($affectedRows > 0)
        {
            $msg = '<div id="successMsg">Correction added.</div>';
        }
        else {
            $msg = '<div id="errMsg">There was a problem editing the correction - no changes made.</div>';
        }
    }

    return $msg;
}

/**
 * Function delSpell
 *
 * * @param $id
 * @return void
 */
function delSpell($id)
{
    global $template, $msg;

    if ($id == "")
    {
        $msg = '<div id="errMsg">There was a problem editing the correction - no changes made.</div>';
    }
    else
    {
        /** @noinspection SqlDialectInspection */
        $sql = "DELETE FROM spellcheck WHERE id = :id";
        $params = array(':id' => $id);
        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);

        if ($affectedRows > 0)
        {
            $msg = '<div id="successMsg">Correction deleted.</div>';
        }
        else {
            $msg = '<div id="errMsg">There was a problem editing the correction - no changes made.</div>';
        }
    }
}


/**
 * Function runSpellSearch
 *
 * @return string
 */
function runSpellSearch()
{
    global $template, $form_vars;

    $i = 0;
    $search = trim($form_vars['search']);

    /** @noinspection SqlDialectInspection */
    $sql = "SELECT * FROM `spellcheck` WHERE `missspelling` LIKE :search1 OR `correction` LIKE :search2 LIMIT 50";
    $params = array(
        ':search1' => "%{$search}%",
        ':search2' => "%{$search}%",
    );
    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    $htmltbl = '<table>
                  <thead>
                    <tr>
                      <th class="sortable">missspelling</th>
                      <th class="sortable">Correction</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                <tbody>';

    foreach ($result as $row)
    {
        $i++;
        $misspell = _strtoupper($row['missspelling']);
        $correction = _strtoupper($row['correction']);
        $id = $row['id'];
        $group = round(($id / 50));
        $action = "<a href=\"index.php?page=spellcheck&amp;action=edit&amp;id=$id&amp;group=$group#$id\"><img src=\"images/edit.png\" border=0 width=\"15\" height=\"15\" alt=\"Edit this entry\" title=\"Edit this entry\" /></a>
               <a href=\"index.php?page=spellcheck&amp;action=delete&amp;id=$id&amp;group=$group#$id\" onclick=\"return confirm('Do you really want to delete this missspelling? You will not be able to undo this!')\";><img src=\"images/del.png\" border=0 width=\"15\" height=\"15\" alt=\"Delete this entry\" title=\"Delete this entry\" /></a>";
        $htmltbl .= "<tr valign=top>
                            <td>$misspell</td>
                            <td>$correction</td>
                            <td align=center>$action</td>
                        </tr>";
    }

    $htmltbl .= "</tbody></table>";

    if ($i >= 50) {
        $msg = "Found more than 50 results for '<b>$search</b>', please refine your search further";
    }
    elseif ($i == 0) {
        $msg = "Found 0 results for '<b>$search</b>'. You can use the form below to add that entry.";
        $htmltbl = "";
    }
    else {
        $msg = "Found $i results for '<b>$search</b>'";
    }

    $htmlresults = "<div id=\"pTitle\">$msg</div>" . $htmltbl;
    return $htmlresults;
}

/**
 * Function editSpellForm
 *
 * @param $id
 * @return mixed|string
 */
function editSpellForm($id)
{
    global $template, $group;
    $form   = $template->getSection('EditSpellForm');

    /** @noinspection SqlDialectInspection */
    $sql    = "SELECT * FROM `spellcheck` WHERE `id` = :id";
    $params = array(':id' => $id);
    $row = db_fetch($sql, $params, __FILE__, __FUNCTION__, __LINE__);
    $uc_missspelling = _strtoupper($row['missspelling']);
    $uc_correction = _strtoupper($row['correction']);

    $form   = str_replace('[id]', $row['id'], $form);
    $form   = str_replace('[missspelling]', $uc_missspelling, $form);
    $form   = str_replace('[correction]', $uc_correction, $form);
    $form   = str_replace('[group]', $group, $form);

    return $form;
}

function updateSpell()
{
    global $template, $msg, $form_vars;

    $missspelling = trim($form_vars['missspelling']);
    $correction = trim($form_vars['correction']);
    $id = trim($form_vars['id']);

    if (($id == "") || ($missspelling == "") || ($correction == ""))
    {
        $msg = '<div id="errMsg">There was a problem editing the correction - no changes made.</div>';
    }
    else
    {
        /** @noinspection SqlDialectInspection */
        $sql = "UPDATE `spellcheck` SET `missspelling` = :missspelling,`correction` = :correction WHERE `id` = :id";
        $params = array(
            ':missspelling' => $missspelling,
            ':correction' => $correction,
            ':id' => $id
        );
        $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);

        if ($affectedRows > 0)
        {
            $msg = '<div id="successMsg">Correction edited.</div>';
        }
        else {
            $msg = '<div id="errMsg">There was a problem editing the correction - no changes made.</div>';
        }
    }
}
