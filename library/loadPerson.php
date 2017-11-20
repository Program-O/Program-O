<?php
// loadPerson.php

require_once('../config/global_config.php');
// set up error logging and display
ini_set('log_errors', true);
ini_set('error_log', _LOG_PATH_ . 'loadPerson.error.log');
ini_set('html_errors', false);
ini_set('display_errors', true);

//load shared files
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'PDO_functions.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'error_functions.php');
/** @noinspection PhpIncludeInspection */
require_once(_LIB_PATH_ . 'misc_functions.php');
/** @noinspection PhpIncludeInspection */

$dbConn = db_open();
$truncate = 'truncate table `person`;';
$cleared = db_write($truncate);
echo 'Table cleared. Writing new transforms.<br>';

# contains a list of 1,800+ verbs, for use in determining whether "I" or "me" should be used when transforming a phrase from second person into first person.
# It's also used for verb modification when changing from first person to second or third.
# lines prefixed with # are ignored
# lines prefixed with "~ " indicate a verb that needs no modification.
# lines prefixed with "$ " indicate that first person and second person verbs are the same.
# lines with no prefix are lists of three comma separated words, one for for each "person".
#
# other rules:
# 1.) when first person verbs are the same as second person, then gender-neutral "they" is the same, as well.
# 2.) most past-tense verbs cover all "person" modes, and don't need to be modified.
#
# empty lines are ignored
# line formats:
# 1.) first, second, third
# 2.) $ first/second, third
# 3.) ~ first/second/third

$firstPersonVerbs = array();
$secondPersonVerbs = array();
$thirdPersonVerbs = array();
$sVerbsFN = _CONF_PATH_ . "verbList.dat";
$iVerbsFN = _CONF_PATH_ . "irregularVerbs.dat";
$stdVerbs = file($sVerbsFN, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$irregularVerbList = file($iVerbsFN, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
//$verbList = array_merge($stdVerbs, $irregularVerbList);
//$verbs = array_unique($verbList);

    //or exit("<br>Unable to open tr");
    sort($stdVerbs);

    //  fill the arrays
    foreach ($stdVerbs as $line)
    {
        $line = rtrim($line, "\r\n");
        #print "line = |$line|<br />\n";

        if (empty ($line)) {
            continue;
        }

        $firstChar = substr($line, 0, 1);

        if ($firstChar === '#') {
            continue;
        }

        if ($firstChar === '$')
        {
            $words = str_replace('$ ', '', $line);
            list($first, $third) = explode(', ', $words);
            $second = $first;
        }
        elseif ($firstChar === '~')
        {
            $words = str_replace('~ ', '', $line);
            $first = $words;
            $second = $first;
            $third = $first;
        }
        else {
            list($first, $second, $third) = explode(', ', $line);
        }
        $firstPersonVerbs[] = $first;
        $secondPersonVerbs[] = $second;
        $thirdPersonVerbs[] = $third;
    }
    // 1st to second, 2nd to first
    $transforms = array();
    foreach ($firstPersonVerbs as $verb)
    {
        $frstPhraseI = "I {$verb}";
        if(array_key_exists($frstPhraseI, $transforms)) continue;
        $transforms[$frstPhraseI] = "you {$verb}";

        $frstPhraseMy = "my {$verb}";
        if(array_key_exists($frstPhraseMy, $transforms)) continue;
        $transforms[$frstPhraseMy] = "your {$verb}";

        $scndPhraseYou = "you {$verb}";
        if(array_key_exists($scndPhraseYou, $transforms)) continue;
        $transforms[$scndPhraseYou] = "I {$verb}";

        $scndPhraseYour = "your {$verb}";
        if(array_key_exists($scndPhraseYour, $transforms)) continue;
        $transforms[$scndPhraseYour] = "my {$verb}";
    }

    foreach ($secondPersonVerbs as $verb)
    {
        $frstPhraseI = "I {$verb}";
        if(array_key_exists($frstPhraseI, $transforms)) continue;
        $transforms[$frstPhraseI] = "you {$verb}";

        $frstPhraseMy = "my {$verb}";
        if(array_key_exists($frstPhraseMy, $transforms)) continue;
        $transforms[$frstPhraseMy] = "your {$verb}";

        $scndPhraseYou = "you {$verb}";
        if(array_key_exists($scndPhraseYou, $transforms)) continue;
        $transforms[$scndPhraseYou] = "I {$verb}";

        $scndPhraseYour = "your {$verb}";
        if(array_key_exists($scndPhraseYour, $transforms)) continue;
        $transforms[$scndPhraseYour] = "my {$verb}";
    }

    foreach ($irregularVerbList as $verb)
    {
        $frstPhraseI = "I {$verb}";
        if(array_key_exists($frstPhraseI, $transforms)) continue;
        $transforms[$frstPhraseI] = "you {$verb}";

        $frstPhraseMy = "my {$verb}";
        if(array_key_exists($frstPhraseMy, $transforms)) continue;
        $transforms[$frstPhraseMy] = "your {$verb}";

        $scndPhraseYou = "you {$verb}";
        if(array_key_exists($scndPhraseYou, $transforms)) continue;
        $transforms[$scndPhraseYou] = "I {$verb}";

        $scndPhraseYour = "your {$verb}";
        if(array_key_exists($scndPhraseYour, $transforms)) continue;
        $transforms[$scndPhraseYour] = "my {$verb}";
    }

    $sql = 'insert into `person` (`id`, `srch`, `repl`) values(null, :srch, :repl);';
    $params = array();
    foreach ($transforms as $srch => $repl)
    {
        $params[] = array(':srch' => $srch, ':repl' => $repl);
    }
    $result = db_write($sql, $params, true, __FILE__, __FUNCTION__, __LINE__);

    echo 'Finished! ' . count($transforms) . ' entries added.<br>';




