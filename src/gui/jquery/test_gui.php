<?php
/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.11
 * FILE: index.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: FEB 01 2016
 * DETAILS: This is the interface for the Program O JSON API
 ***************************************/

$cookie_name = 'Program_O_JSON_GUI';
$botId = filter_input(INPUT_GET, 'bot_id');
$convo_id = (isset($_COOKIE[$cookie_name])) ? $_COOKIE[$cookie_name] : jq_get_convo_id();
$bot_id = (isset($_COOKIE['bot_id'])) ? $_COOKIE['bot_id'] : ($botId !== false && $botId !== null) ? $botId : 1;

if (is_nan($bot_id) || empty($bot_id))
{
    $bot_id = 1;
}

setcookie('bot_id', $bot_id);

// Experimental code
$HXFP  = (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) ? $_SERVER['HTTP_X_FORWARDED_PORT'] : '';
$HSP   = (isset($_SERVER['SERVER_PORT'])) ? $_SERVER['SERVER_PORT'] : '';
$HTTPS = (isset($_SERVER['HTTPS'])) ? $_SERVER['HTTPS'] : '';
$HHOST = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
$protocol = ((!empty($HTTPS) && $HTTPS != 'off') || $HSP == 443 || $HXFP == 443) ? "https://" : "http://";
$base_URL = $protocol . $HHOST;                                   // set domain name for the script
$this_path = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__)));  // The current location of this file, normalized to use forward slashes
$this_path = str_replace($_SERVER['DOCUMENT_ROOT'], $base_URL, $this_path);       // transform it from a file path to a URL
$url = str_replace('gui/jquery', 'chatbot/conversation_start.php', $this_path);   // and set it to the correct script location
/*
  Example URL's for use with the chatbot API
  $url = 'http://api.program-o.com/v2.3.1/chatbot/';
  $url = 'http://localhost/Program-O/Program-O/chatbot/conversation_start.php';
  $url = 'chat.php';
*/

$display = "The URL for the API is currently set as:<br />\n$url.<br />\n";
$display .= 'Test this to make sure it is correct by <a href="' . $url . '?say=hello">clicking here</a>. Then remove this message from gui/jquery/index.php' . PHP_EOL;
$display .= 'And don\'t forget to upload your AIML files in the admin area otherwise you will not get a response!' . PHP_EOL;
#$display = '';

/**
 * Function jq_get_convo_id
 *
 *
 * @return string
 */
function jq_get_convo_id()
{
    global $cookie_name;

    session_name($cookie_name);
    session_start();
    $convo_id = session_id();
    session_destroy();
    setcookie($cookie_name, $convo_id);

    return $convo_id;
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8"/>
        <meta name="Description" content="A Free Open Source AIML PHP MySQL Chatbot called Program-O. Version2"/>
        <meta name="keywords" content="Open Source, AIML, PHP, MySQL, Chatbot, Program-O, Version2"/>
        <title>Program O jQuery GUI Examples</title>
        <link rel="stylesheet" type="text/css" href="main.css" media="all"/>
        <link rel="icon" href="./favicon.ico" type="image/x-icon"/>
        <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon"/>
        <script type="text/javascript">
            var URL = '<?php echo $url ?>';
        </script>
    </head>
    <body>
        <h3>Program O JSON GUI</h3>
        <p class="center">
            This is a simple example of how to access the Program O chatbot using the JSON API. Feel free to change the HTML
            code for this page to suit your specific needs. For more advanced uses, please visit us at <a
            href="http://www.program-o.com/">Program O</a>.
        </p>
        <hr class="center">
        <p class="center">
            Please check out the Multi-bot example GUI <a href="multibot_gui_with_chatlog.php">here</a>.
        </p>
        <!-- The DIV below is for debugging purposes, and can be safely removed, if desired. -->
        <div id="convo_id">Conversion ID: <?php echo $convo_id; ?></div>
        <div class="centerthis">
            <form method="post" name="talkform" id="talkform" action="index.php">
                <div id="chatdiv">
                    <label for="submit">Say:</label>
                    <input type="text" name="say" id="say" size="60"/>
                    <input type="submit" name="submit" id="submit" class="submit" value="say"/>
                    <input type="hidden" name="convo_id" id="convo_id" value="<?php echo $convo_id; ?>"/>
                    <input type="hidden" name="bot_id" id="bot_id" value="<?php echo $bot_id; ?>"/>
                    <input type="hidden" name="format" id="format" value="json"/>
                </div>
            </form>
            <hr class="w80 thick">
            <strong>Conversation Log:</strong><br>
            <div id="conversation">&nbsp;</div>
        </div>
        <div id="urlwarning">Placeholder text</div>
        <script type="text/javascript" src="jquery-1.9.1.min.js"></script>
        <script type="text/javascript">
            var endSpan = '<span id="end"></span><br>';
            $(document).ready(function () {
                var user_name, bot_name, input, response;
                var botSay = $('.botsay');
                // put all your jQuery goodness in here.
                $('#talkform').submit(function (e) {
                    e.preventDefault();
                    $('#urlwarning').empty().hide();
                    //$('.botsay').html('(Thinking...)');
                    var user = $('#say').val();
                    $('.usersay').text(user);
                    var formdata = $("#talkform").serialize();
                    var say = $('#say').val();
                    $('#say').val('');
                    $('#say').focus();
                    $.get(URL, formdata, function (data) {
                        console.info('Data =', data);
                        var b = data.botsay;
                        if (b.indexOf('[img]') >= 0) {
                            b = showImg(b);
                        }
                        if (b.indexOf('[link') >= 0) {
                            b = makeLink(b);
                        }
                        bot_name = data.data.bot_name;
                        user_name = data.data.user_name;
                        input = '<span class="input">' + user_name + ': </span>' + say + '<br>';
                        response = '<span class="response">' + bot_name + ': </span>' + b + '<br>';
                        var conversation = $('#conversation').html();
                        conversation = conversation.replace(endSpan, '');
                        $('#conversation').html(conversation + input + response + endSpan);
                        $('#conversation').animate({scrollTop: $('#end').offset().top});
                        console.info('conversation =', conversation);
                        var usersay = data.usersay;
                        $('#urlwarning').hide();
                    }, 'json').fail(function (xhr, textStatus, errorThrown) {
                        console.error('XHR =', xhr.responseText);
                        $('#urlwarning').html("Something went wrong! Error = " + errorThrown).show();
                    });
                    return false;
                });
            });
            function showImg(input) {
                var regEx = /\[img\](.*?)\[\/img\]/;
                var repl = '<br><a href="$1" target="_blank"><img src="$1" alt="$1" width="150" /></a>';
                var out = input.replace(regEx, repl);
                console.log('out = ' + out);
                return out
            }
            function makeLink(input) {
                var regEx = /\[link=(.*?)\](.*?)\[\/link\]/;
                var repl = '<a href="$1" target="_blank">$2</a>';
                var out = input.replace(regEx, repl);
                console.log('out = ' + out);
                return out;
            }
        </script>
    </body>
</html>