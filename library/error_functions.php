<?php
/***************************************
* http://www.program-o.com
* PROGRAM O 
* Version: 2.0.1
* FILE: library/error_functions.php
* AUTHOR: ELIZABETH PERREAU
* DATE: MAY 4TH 2011
* DETAILS: common library of debugging functions
***************************************/


/**
 * function myErrorHandler()
 * Process PHP errors
 * @param string $errno - the severity of the error 
 * @param  string $errstr - the file the error came from
 * @param  string $errfile - the file the error came from
 * @param  string $errline - the line of code
**/
function myErrorHandler($errno, $errstr, $errfile, $errline) {
    switch ($errno) {
        case E_NOTICE:
        case E_USER_NOTICE:
            $errors = "Notice";
            break;
        case E_WARNING:
        case E_USER_WARNING:
            $errors = "Warning";
            break;
        case E_ERROR:
        case E_USER_ERROR:
            $errors = "Fatal Error";
            break;
        default:
            $errors = "Unknown";
            break;
        }

    $info = "PHP ERROR [$errors] -$errstr in $errfile on Line $errline";
        
    runDebug($errfile, '', $errline, $info, 1);    

}


/**
 * function sqlErrorHandler()
 * Process sql errors
 * @param  string $fileName - the file the error came from
 * @param  string $functionName - the function that triggered the error
 * @param  string $line - the line of code
 * @param  string $sql - the sql query
 * @param  string $error - the mysql_error
 * @param  string $erno - the mysql_error
**/
function sqlErrorHandler( $sql, $error, $erno, $file, $function, $line){
    $info = "MYSQL ERROR $erno - $error when excuting\n $sql";
	runDebug($file, $function, $line, $info, 1);
}


/**
 * function runDebug()
 * Building to a global debug array
 * @param  string $fileName - the file the error came from
 * @param  string $functionName - the function that triggered the error
 * @param  string $line - the line of code
 * @param  string $info - the message to display
**/
function runDebug($fileName, $functionName, $line, $info, $level=3) {
  
	global $debugArr,$srai_iterations,$debuglevel,$quickdebug,$writetotemp;
	if(empty($functionName)) $functionName = "Called outside of function";
	//only log the debug info if the info level is equal to or less than the chosen level
	if($level<=$debuglevel)
    {
		if($quickdebug==1)
		{
			outputDebug($fileName, $functionName, $line, $info);
		}

		list($usec, $sec) = explode(" ",microtime());

		//build timestamp index for the debug array
		$index = date("d-m-Y H:i:s").ltrim($usec, '0');
		//add to array
		$debugArr[$index]['fileName']=basename($fileName);
		$debugArr[$index]['functionName']=$functionName;
		$debugArr[$index]['line']=$line;
		$debugArr[$index]['info']=$info;
		
		if($srai_iterations<1)
        {
			$sr_it = 0;}
		else {
			$sr_it = $srai_iterations;}
		
		$debugArr[$index]['srai_iteration']=$sr_it;
		
		//if we are logging to file then build a log file. This will be overwriten if the program completes
		if($writetotemp==1)
		{	
			writefile_debug($debugArr);
		}
	}
	
	//return $debugArr;
}





/**
 * function handleDebug()
 * Handle the debug array at the end of the process
 * @param  array $convoArr - conversation arrau
 * @return array $convoArr;
 * TODO THIS MUST BE IMPLMENTED
**/
function handleDebug($convoArr){
	
	global $debugArr;
	$convoArr['debug']=$debugArr;
	$log ="";
	
	
	foreach($debugArr as $time => $subArray){
		$log .= "[NEWLINE]".$time.": ";
		foreach($subArray as $index=>$value){
			$log .= $index."=".$value.",\t";
		}
	}
	
	//echo ">>>>".$convoArr['conversation']['debugmode'];
	
	switch($convoArr['conversation']['debugshow']){
		case 0: //show in source code
			$log = str_replace("[NEWLINE]","\r\n",$log);
			display_on_page(0,$log);
			break;
		case 1: //write to log file
			$log = str_replace("[NEWLINE]","\r\n",$log);
			writefile_debug($convoArr);
			break;
		case 2: //show in webpage
			$log = str_replace("[NEWLINE]","<br/>",$log);
			display_on_page(1,$log);
			break;
		case 3: //email to user
			$log = str_replace("[NEWLINE]","\r\n",$log);
			email_debug($convoArr['conversation']['debugemail'],$log);
			break;
	}
	

	return $convoArr;
}

/**
 * function writefile_debug()
 * Handles the debug when written to a file
 * @param  string $filename - the name of the file which is also the convo id
 * @param  string $log - the data to write
**/
function writefile_debug($array)
{	
	$myFile = _DEBUG_PATH_.session_id().".txt";

	$mode = "w";
	$file = "";
	$tabs = "";
	
	
	$file = print_r($array,true);
    if (DIRECTORY_SEPARATOR == '\\') {
      $file = str_replace("\n", "\r\n", $file);
    }
	
	if(isset($array['conversation']))
	{
	
		$file .= "\r\n----------------------------------------\r\n";
		
		$tmp_array = $array;
		unset($tmp_array['debug']);	
		$file .= str_replace("'","\'",serialize($tmp_array));
	}
	file_put_contents($myFile, $file);
}



/**
 * function display_on_page()
 * Handles the debug when it is displayed on the webpage either in the source or on the page
 * @param  int $show_on_page - 0=show in source 1=output to user
 * @param  string $log - the data to show
**/
function display_on_page($show_on_page,$log)
{
	if($show_on_page==0){
		echo "<!--<pre>";
		print_r($log);
		echo "</pre>-->";
	}else{
		echo "<pre>";
		print_r($log);
		echo "</pre>";
	}
}


/**
 * function email_debug()
 * Handles the debug when it is emailed to the botmaster
 * @param  string $email - email address
 * @param  string $log - the data to send
**/
function email_debug($email,$log)
{
	$to      = $email;
	$subject = 'Debug Data';
	$message = $log;
	$headers = 'From: '.$email . "\r\n" .
	    'Reply-To: '.$email . "\r\n" .
	    'X-Mailer: PHP/' . phpversion();
	
	mail($to, $subject, $message, $headers);
}




/**
 * function outputDebug()
 * Used in the install/upgrade files will display it straightaway
 * @param  string $fileName - the file the error came from
 * @param  string $functionName - the function that triggered the error
 * @param  string $line - the line of code
 * @param  string $info - the message to display
**/
function outputDebug($fileName, $functionName, $line, $info) {
  
	global $srai_iterations;
	list($usec, $sec) = explode(" ",microtime());
	
	//build timestamp index for the debug array
	$string = ((float)$usec + (float)$sec);
	$string2 = explode(".", $string);
	$index = date("d-m-Y H:i:s", $string2[0]).":".$string2[1];	
	
		if($srai_iterations<1){
			$sr_it = 0;}
		else {
			$sr_it = $srai_iterations;}	
	
	//add to array
	print "<br/>----------------------------------------------------";
	print "<br/>".$index.": ".$fileName;
	print "<br/>".$index.": ".$functionName;
	print "<br/>".$index.": ".$line;
	print "<br/>".$index.": ".$info;
	print "<br/>".$index.": srai:".$sr_it;
	print "<br/>----------------------------------------------------";
}

  function SQL_Error($errNum, $file = 'unknown', $function = 'unknown', $line = 'unknown') {
    $msg = "There's a problem with your Program O installation. Please run the <a href=\"../install/\">install script</a> to correct the problem.<br>\n";
    switch ($errNum) {
      case '1146':
      $msg .= "The database and/or table used in the config file doesn't exist.<br>\n";
      break;
      default:
      $msg = "Error number $errNum!<br>\n";
    }
    return $msg;
  }

  function save_file($file, $content, $append = false) {
    if (function_exists('file_put_contents')) {
      $x = file_put_contents($file, $content, $append);
    }
    else {
      $fileMode = ($append === true) ? "a" : "w";
      $fh = fopen($file, $fileMode)or die("Can't open the file!");
      $cLen = strlen($content);
      fwrite($fh, $content, $cLen);
      fclose($fh);
    }
    return 1;
  }

?>