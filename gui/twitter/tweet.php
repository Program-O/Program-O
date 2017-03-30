<?php

/***************************************
 * http://www.program-o.com
 * PROGRAM O
 * Version: 2.6.5
 * FILE: gui/plain/index.php
 * AUTHOR: Elizabeth Perreau and Dave Morton
 * DATE: MAY 17TH 2014
 * DETAILS: repling to @mentions on twitter
 ***************************************/
require_once("twitteroauth/OAuth.php");
require_once("twitteroauth/twitteroauth.php");

//--------------------------------------------------------------------------------------------------------------------
// INSTALLATION INSTRUCTIONS
//--------------------------------------------------------------------------------------------------------------------
//For full instructions on how to set this script up please go to:
//https://github.com/Program-O/Program-O/wiki/Twitterbot-Set-Up
//--------------------------------------------------------------------------------------------------------------------

//--------------------------------------------------------------------------------------------------------------------
// EDIT YOUR CHAT BOT PARAMS
//--------------------------------------------------------------------------------------------------------------------
//Locate the path of conversation_start.php on your server - this is your chatbot_endpoint.
//see: https://github.com/Program-O/Program-O/wiki/Twitterbot-Set-Up for full details
$chatbot_endpoint = "http://<yoursite.com>/chatbot/conversation_start.php";
//the bot_id of your chatbot in table 'bots'
$bot_id = 1;

//--------------------------------------------------------------------------------------------------------------------
// EDIT THE AGE LIMIT OF TWEETS TO REPLY TO (minutes)
//--------------------------------------------------------------------------------------------------------------------
//If you change the cron occurrence entry in the cron table you will have alter the cron_every_x_minutes value below, so that the minutes are the same.
//see: https://github.com/Program-O/Program-O/wiki/Twitterbot-Set-Up for full details
$age_of_tweets_in_minutes = 5;

//--------------------------------------------------------------------------------------------------------------------
// EDIT TWITTER OAUTH PARAMS
//--------------------------------------------------------------------------------------------------------------------
//Create a twitter account for your chatbot at http://twitter.com
//Then create a app for your twitter account at https://app.twitter.com/
//see: https://github.com/Program-O/Program-O/wiki/Twitterbot-Set-Up for full details
$consumerkey = "";
$consumersecret = "";
$accesstoken = "";
$accesstokensecret = "";

//--------------------------------------------------------------------------------------------------------------------
// CONNECT
//--------------------------------------------------------------------------------------------------------------------
$connection = new TwitterOAuth($consumerkey, $consumersecret, $accesstoken, $accesstokensecret);
$connection->host = "https://api.twitter.com/1.1/";
$content = $connection->get('account/verify_credentials');
//if you want to check that you have been authenticated with your creditionals uncomment the next line
//var_dump($content);
$twitterid = $content->id;

//--------------------------------------------------------------------------------------------------------------------
// RUN
//--------------------------------------------------------------------------------------------------------------------
//search for all mentions that occured since the last time we rand the script
$myMentionsArr = get_my_mentions($connection);
//formulate responses
$myReplies = makeReplies($myMentionsArr);
//tweet responses
tweetthis($connection, $myReplies);

//--------------------------------------------------------------------------------------------------------------------
// FUNCTIONS
//--------------------------------------------------------------------------------------------------------------------

/**
 * function tweetthis
 *
 * Sends out tweets to the Twitter API, based on the chatbot's response
 *
 * @param object $connection
 * @param array $myReplies
 * @return void
 */
function tweetthis($connection, $myReplies)
{
    if (!isset($myReplies[0]))
    {
        echo "<br/>No new tweets";
    }
    else
    {
        foreach ($myReplies as $i => $replies)
        {
            $message = $replies['message'];

            if (empty($message))
            {
                echo '<br/>Could not tweet. Bot returned an empty response!';
                continue;
            }

            $tweetStatus = $connection->post('statuses/update', array('status' => $replies['message'], 'in_reply_to_status_id' => $replies['in_reply_to_status_id']));

            if (isset($tweetStatus->errors[0]->code))
            {
                echo "<br/>Could not tweet " . $replies['message'] . ": " . $tweetStatus->errors[0]->message;
            }
        }
    }
}

/**
 * function getReply
 *
 * Collects the chatbot's response to the incoming tweet
 *
 * @param string $convo_id
 * @param string $usersay
 * @return string $botsay
 */
function getReply($convo_id, $usersay)
{
    global $chatbot_endpoint, $bot_id;

    $botsay = '';
    $request_url = $chatbot_endpoint . "?say=" . urlencode($usersay) . "&convo_id=$convo_id&bot_id=$bot_id&format=xml";
    $conversation = @simplexml_load_file($request_url, "SimpleXmlElement", LIBXML_NOERROR + LIBXML_ERR_FATAL + LIBXML_ERR_NONE);

    if ((@$conversation) && (@$conversation->count()) > 0)
    {
        $botsay = (string)$conversation->chat->line[0]->response;
        $botsay = str_replace("undefined", "...", $botsay);
    }

    return $botsay;
}

/* get all the mentions tweeted to me since the last time the cron ran */
function get_my_mentions($connection)
{
    global $age_of_tweets_in_minutes;

    $tArr = array();
    $tweets_to_me = $connection->get('statuses/mentions_timeline', array('count' => 1));

    foreach ($tweets_to_me as $i => $tweet)
    {
        if (strtotime($tweet->created_at) > strtotime("-$age_of_tweets_in_minutes minutes"))
        {
            $tArr[$i]['created'] = $tweet->created_at;
            $tArr[$i]['tweetidstr'] = $tweet->id_str;
            $tArr[$i]['tweet'] = cleanTweet($tweet->text);
            $tArr[$i]['userid'] = $tweet->user->id;
            $tArr[$i]['useridstr'] = $tweet->user->id_str;
            $tArr[$i]['userscreenname'] = $tweet->user->screen_name;
        }
    }

    return $tArr;
}

/* clean the tweet text before looking for a match */
function cleanTweet($tweet)
{
    $pattern = '/@([a-zA-Z0-9_]+)/';
    str_replace("#", "", $tweet);
    $tweet = preg_replace($pattern, "", $tweet);

    return trim($tweet);
}

/* package together the details to send the actual response */
function makeReplies($myMentionsArr)
{
    $rArr = array();

    foreach ($myMentionsArr as $i => $mention)
    {
        $rArr[$i]['in_reply_to_status_id'] = $mention['tweetidstr'];
        //build the reply to tweet
        $botresponse = getReply($mention['useridstr'], $mention['tweet']);

        if ($botresponse != '')
        {
            $message = safeToTweet("@" . $mention['userscreenname'] . " " . $botresponse);
            $rArr[$i]['message'] = $message;
        }
    }

    return $rArr;
}

/* check its with the 140 char limit */
function safeToTweet($text)
{
    if (subStr($text, 0, 140) != $text) {
        $text = subStr($text, 0, 137) . "...";
    }
    return trim($text);
}
