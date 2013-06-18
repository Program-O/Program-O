<?php
/***************************************
* www.program-o.com
* PROGRAM O 
* Version: 2.3.0
* FILE: chatbot/core/conversation/make_conversation.php
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: MAY 4TH 2011
* DETAILS: this file contains the functions control the creation of the conversation 
***************************************/

/**
 * function make_conversation()
 * A controller function to run the instructions to make the conversation
 * @param  array $convoArr - the current state of the conversation array
 * @return $convoArr (updated)
**/	
function make_conversation($convoArr){
	
	runDebug( __FILE__, __FUNCTION__, __LINE__, "Making conversation",4);
	//get the user input and clean it
	$convoArr['aiml']['lookingfor'] =  normalize_text($convoArr['user_say'][1]);
	//find an aiml match in the db
	$convoArr = get_aiml_to_parse($convoArr);
	$convoArr = parse_matched_aiml($convoArr,'normal');
		
	//parse the aiml to build a response
	//store the conversation
	$convoArr = push_on_front_convoArr('parsed_template',$convoArr['aiml']['parsed_template'],$convoArr);
	$convoArr = push_on_front_convoArr('template',$convoArr['aiml']['template'],$convoArr);
	//display conversation vars to user.
	$convoArr['conversation']['totallines']++;
	return $convoArr;
}


/**
 * function eval_aiml_to_php_code()
 * @param  array $convoArr - the current state of the conversation array
 * @param  string $evalthis - string to make safe
 * @return string $botsay
**/	
function eval_aiml_to_php_code($convoArr,$evalthis){
	
	runDebug( __FILE__, __FUNCTION__, __LINE__, "",4);
	$botsay = @run_aiml_to_php($convoArr,$evalthis);
	//if run correctly $botsay should be re valued
	return $botsay;
}



/**
 * function run_aiml_to_php()
 * @param  array $convoArr - the current state of the conversation array
 * @param  string $evalthis - string to make safe
 * @return string $result (-botsay)
**/	
function run_aiml_to_php($convoArr,$evalthis){
	
	runDebug( __FILE__, __FUNCTION__, __LINE__, "Evaluating Stored PHP Code from the Database",4);
	global $botsay, $error_response;

	//this must be NULL if it is FALSE then its failed but  if its NULL its a success
	$error_flag = eval($evalthis);
	if($error_flag===NULL){ //success
		runDebug( __FILE__, __FUNCTION__, __LINE__, "EVALUATED: $evalthis ",4);
		$result = $botsay;
	} else { //error
		runDebug( __FILE__, __FUNCTION__, __LINE__, "ERROR TRYING TO EVAL: $evalthis ",1);
		runDebug( __FILE__, __FUNCTION__, __LINE__, "ERROR TRYING TO EVAL: ".print_r($convoArr['aiml'],true),1);
		$result = $error_response;}
	
	return $result;
}

/**
 * function buildNounList()
 * @param array $convoArr
 * @param int $person
 * @param string $in
 * @return the tranformed string
**/

  function buildNounList($convoArr)
  {
    $fileName = _CONF_PATH_ . 'nounList.dat';
    $nounList = file($fileName,FILE_IGNORE_NEW_LINES);
    $convoArr['nounList'] = $nounList;
    return $convoArr;
  }


?>
