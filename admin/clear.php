<?PHP

/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.11
 * FILE: clear.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: 12-12-2014
 * DETAILS: Clears out AIML categories from the DB for the currently selected chatbot
 ***************************************/
$content = "";
$upperScripts = $template->getSection('UpperScripts');
$post_vars = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

if ((isset ($post_vars['action'])) && ($post_vars['action'] == "clear"))
{
    $msg = clearAIML();
}
elseif ((isset ($post_vars['clearFile'])) && ($post_vars['clearFile'] != "null"))
{
    $msg = clearAIMLByFileName($post_vars['clearFile']);
}
else { }

$content .= buildMain();
$topNav = $template->getSection('TopNav');
$leftNav = $template->getSection('LeftNav');
$main = $template->getSection('Main');
$navHeader = $template->getSection('NavHeader');
$FooterInfo = getFooter();
$errMsgClass = (!empty ($msg)) ? "ShowError" : "HideError";
$errMsgStyle = $template->getSection($errMsgClass);
$noLeftNav = '';
$noTopNav = '';
$noRightNav = $template->getSection('NoRightNav');
$headerTitle = 'Actions:';
$pageTitle = "My-Program O - Clear AIML Categories";
$mainContent = $content;
$mainTitle = "Clear AIML Categories for the bot named $bot_name [helpLink]";
$showHelp = $template->getSection('ClearShowHelp');
$mainTitle = str_replace('[helpLink]', $template->getSection('HelpLink'), $mainTitle);
$mainContent = str_replace('[showHelp]', $showHelp, $mainContent);
$mainContent = str_replace('[upperScripts]', $upperScripts, $mainContent);
$mainContent = str_replace('[bot_name]', $bot_name, $mainContent);

/**
 * Function clearAIML
 *
 *
 * @return string
 */
function clearAIML()
{
    global $bot_id, $bot_name;
    /** @noinspection SqlDialectInspection */
    $sql = "DELETE FROM `aiml` WHERE `bot_id` = :bot_id;";
    $params = array(':bot_id' => $bot_id);
    $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);
    $msg = "<strong>All AIML categories cleared for $bot_name!</strong><br />";

    return $msg;
}

/**
 * Function clearAIMLByFileName
 *
 * * @param $filename
 * @return string
 */
function clearAIMLByFileName($filename)
{
    global $bot_id;
    /** @noinspection SqlDialectInspection */
    $sql = "DELETE FROM `aiml` WHERE `filename` LIKE :filename AND `bot_id` = :bot_id;";
    $params = array(
    ':bot_id' => $bot_id,
    ':filename' => $filename,
    );
    $affectedRows = db_write($sql, $params, false, __FILE__, __FUNCTION__, __LINE__);
    $msg = "<br/><strong>AIML categories cleared for file $filename!</strong><br />";

    return $msg;
}

/**
 * Function buildSelOpts
 *
 *
 * @return string
 */
function buildSelOpts()
{
    global $bot_id, $bot_name, $msg;
    /** @noinspection SqlDialectInspection */
    $sql = "SELECT DISTINCT filename FROM `aiml` WHERE `bot_id` = :bot_id ORDER BY `filename`;";
    $params = array(':bot_id' => $bot_id);
    $result = db_fetchAll($sql, $params, __FILE__, __FUNCTION__, __LINE__);

    if (count($result) == 0)
    {
        $msg = "The chatbot '$bot_name' has no AIML categories to clear.";
        return false;
    }

    $out = "                  <!-- Start Selectbox Options -->\n";
    $optionTemplate = "                  <option value=\"[val]\">[val]</option>\n";

    foreach ($result as $row)
    {
        if (empty ($row['filename']))
        {
            $curOption = "                  <option value=\"\">{No Filename entry}</option>\n";
        }
        else {
            $curOption = str_replace('[val]', $row['filename'], $optionTemplate);
        }
        $out .= $curOption;
    }
    $out .= "                  <!-- End Selectbox Options -->\n";

    return $out;
}

/**
 * Function buildMain
 *
 *
 * @return string
 */
function buildMain()
{
    global $msg, $template;
    $selectOptions = buildSelOpts();

    if ($selectOptions === false)
    {
        return "<div class=\"bold red center\">$msg</div><br>\n";
    }

    $content = $template->getSection('ClearAIML');
    $content = str_replace('[selectOptions]', $selectOptions, $content);
    $content = str_replace('[blank]', '', $content);

    return $content;
}