<?php
/***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.3.1
  * FILE: index.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 07-23-2013
  * DETAILS: This is the interface for the Program O JSON API
  ***************************************/
  $cookie_name = 'Program_O_JSON_GUI';
  $convo_id = (isset($_COOKIE[$cookie_name])) ? $_COOKIE[$cookie_name] : get_convo_id();
  $bot_id = (isset($_COOKIE['bot_id'])) ? $_COOKIE['bot_id'] : 1;
  setcookie('bot_id', $bot_id);
  $display = 'Make sure that you edit this file to change the value of $url below to reflect the correct address, and to remove this message.' . PHP_EOL;
  $url = 'http://www.example.com/programo/chatbot/conversation_start.php';

  function get_convo_id()
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
  <head>
    <link rel="stylesheet" type="text/css" href="main.css" media="all" />
    <link rel="icon" href="./favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Program O AIML PHP Chatbot</title>
    <meta name="Description" content="A Free Open Source AIML PHP MySQL Chatbot called Program-O. Version2" />
    <meta name="keywords" content="Open Source, AIML, PHP, MySQL, Chatbot, Program-O, Version2" />
    <meta name="keywords" content="Open Source, AIML, PHP, MySQL, Chatbot, Program-O, Version2" />
    <style type="text/css">
      h3 {
        text-align: center;
      }
      hr {
        width: 80%;
        color: green;
        margin-left: 0;
      }

      .user_name {
        color: rgb(16, 45, 178);
      }
      .bot_name {
        color: rgb(204, 0, 0);
      }
      #shameless_plug {
        position: absolute;
        right: 10px;
        bottom: 10px;
        border: 1px solid red;
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-shadow: 2px 2px 2px 0 #808080;
        padding: 5px;
        border-radius: 5px;
      }
    </style>
  </head>
  <body>
    <h3>Program O XML GUI</h3>
    <p>
      This is a simple example of how to access the Program O chatbot using the JSON API. Feel free to change the HTML
      code for this page to suit your specific needs. For more advanced uses, please visit the <a href="http://www.forum.program-o.com/">
      Program O Forums</a> to ask for assistance.
    </p>
    <div class="centerthis">
      <div class="manspeech"><div  class="triangle-border bottom blue"><div class="botsay">Hey!</div></div></div>
      <div class="dogspeech"><div  class="triangle-border bottom orange"><div class="usersay">&nbsp;</div></div></div>
      <div class="man"></div>
      <div class="dog"></div>
    </div>
    <div class="clearthis"></div>
    <div class="centerthis">
      <form method="post" name="talkform" id="talkform" action="index.php">
        <p>
          <label>Say:</label>
          <input type="text" name="say" id="say"/>
          <input type="submit" name="submit" id="submit" class="submit"  value="say" />
          <input type="hidden" name="convo_id" id="convo_id" value="<?php echo $convo_id;?>" />
          <input type="hidden" name="bot_id" id="bot_id" value="<?php echo $bot_id;?>" />
          <input type="hidden" name="format" id="format" value="json" />
        </p>
      </form>
    </div>
    <div id="shameless_plug">
      To get your very own chatbot, visit <a href="http://www.program-o.com">program-o.com</a>!
    </div>
    <?php echo $display ?>
    <script type="text/javascript" src="jquery-1.3.min.js"></script>
    <script type="text/javascript" >
     $(document).ready(function() {
      // put all your jQuery goodness in here.
        $('#talkform').submit(function() {
          user = $('#say').val();
          $('.usersay').text(user);
          formdata = $("#talkform").serialize();
          $('#say').val('')
          $('#say').focus();
          $.post('<?php echo $url ?>', formdata, function(data){
            var b = data.botsay;
            $('.botsay').html(b);
          }, 'json');
          return false;
        });
      });
    </script>
  </body>
</html>
