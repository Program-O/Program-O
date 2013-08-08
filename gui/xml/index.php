<?php
/***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 2.3.1
  * FILE: index.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 07-23-2013
  * DETAILS: This is the XML GUI interface for Program O
  ***************************************/

  $display = 'Make sure that you edit this file to change the value of $url below to reflect the correct address, and to remove this message.';
  $url = 'http://www.example.com/programo/chatbot/conversation_start.php';
  $display_template = <<<end_display
      <span class="user_name">[user_name]: </span><span class="user_say">[input]</span><br>
      <span class="bot_name">[bot_name]: </span><span class="bot_say">[response]</span><br>

end_display;

  $post_vars = (!empty($_POST)) ? filter_input_array(INPUT_POST) : array();
  $get_vars = (!empty($_GET)) ? filter_input_array(INPUT_GET) : array();
  $request_vars = array_merge($get_vars, $post_vars);
  $convo_id = (isset ($request_vars['convo_id'])) ? $request_vars['convo_id'] : get_convo_id();
  $bot_id = (isset ($request_vars['bot_id'])) ? $request_vars['bot_id'] : 1;
  if (!empty ($request_vars))
  {
    $options = array(
      CURLOPT_USERAGENT => 'Program_O_XML_API',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      //CURLOPT_CONNECTTIMEOUT => 3,
    );
    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_vars);
    $data = curl_exec($ch);
    curl_close($ch);
    $xml = new SimpleXMLElement($data);
    $display = '';
    $success = $xml->status->success;
    if (isset($xml->status->message))
    {
      $message = (string) $xml->status->message;
      $display = 'There was an error in the script. Message = ' . $message;
    }
    else
    {
      $user_name = (string) $xml->user_name;
      $bot_name = (string) $xml->bot_name;
      $chat = $xml->chat;
      $lines = $chat->xpath('line');
      foreach ($lines as $line)
      {
        $input = (string) $line->input;
        $response = (string) $line->response;
        $tmp_row = str_replace('[user_name]', $user_name, $display_template);
        $tmp_row = str_replace('[bot_name]', $bot_name, $tmp_row);
        $tmp_row = str_replace('[input]', $input, $tmp_row);
        $tmp_row = str_replace('[response]', $response, $tmp_row);
        $display .= $tmp_row;
      }
    }

  }

  function get_convo_id()
  {
    if (isset($_COOKIE['Program_O_XML_API'])) $convo_id = $_COOKIE['Program_O_XML_API'];
    else
    {
      session_name('Program O XML GUI');
      session_start();
      $convo_id = session_id();
      session_destroy();
    }
    return $convo_id;
  }

?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <link rel="icon" href="./favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
    <title>Program O AIML Chatbot</title>
    <meta name="Description" content="A Free Open Source AIML PHP MySQL Chatbot called Program-O. Version2" />
    <meta name="keywords" content="Open Source, AIML, PHP, MySQL, Chatbot, Program-O, Version2" />
    <style type="text/css">
      h3 {
        text-align: center;
      }
      .user_name {
        color: rgb(16, 45, 178);
      }
      .bot_name {
        color: rgb(204, 0, 0);
      }
    </style>
  </head>
  <body>
    <h3>Program O XML GUI</h3>
    <form accept-charset="utf-8" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
      <p>
        <input type="text" name="say" id="say" />
        <input id="bot_id" type="hidden" name="bot_id" value="<?php echo $bot_id ?>">
        <input id="convo_id" type="hidden" name="convo_id" value="<?php echo $convo_id ?>">
        <input id="format" type="hidden" name="format" value="xml">
        <input type="submit" value="Chat" />
      </p>
    </form>
    <div id="response">
<?php echo $display ?>
    </div>
  </body>
</html>
