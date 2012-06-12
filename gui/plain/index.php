<?php 
  $docRoot = $_SERVER['DOCUMENT_ROOT'];
  $docRoot = str_replace('/', DIRECTORY_SEPARATOR, $docRoot);
  $thisFolder = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR;
  $baseFolder = str_ireplace('gui'.DIRECTORY_SEPARATOR.'plain'.DIRECTORY_SEPARATOR, '', $thisFolder);
  $relPath = str_ireplace(array($docRoot, DIRECTORY_SEPARATOR), array('', '/'), $baseFolder);
  $configFile = $baseFolder . 'config' . DIRECTORY_SEPARATOR . 'global_config.php';
  $headerURL = 'http://' . $_SERVER["HTTP_HOST"] . $relPath . 'install/install_programo.php';

  $debugString = "
Document Root = $docRoot<br />
This folder = $thisFolder<br />
Relative path = $relPath<br />
Base folder = $baseFolder<br />
Config file = $configFile<br />
Header URL = $headerURL";
  #die($debugString);

  if (!file_exists($configFile)) header("location: $headerURL"); // Gives the full URL to the install script
  require_once($configFile);

	session_start();
	$display = "";

if(isset($_REQUEST['bot_id'])){
	$bot_id = $_REQUEST['bot_id'];
}else{
	$bot_id = 1;
}

if(isset($_REQUEST['convo_id'])){
	$convo_id = $_REQUEST['convo_id'];
}else{
	$convo_id = session_id();
}

if(isset($_REQUEST['format'])){
	$format = $_REQUEST['format'];
}else{
	$format = "html";
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
	<head>
		<link rel="icon" href="./favicon.ico" type="image/x-icon" />
		<link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
		
		  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Program O AIML PHP Chatbot</title>
		<meta name="Description" content="A Free Open Source AIML PHP MySQL Chatbot called Program-O. Version2" />
		<meta name="keywords" content="Open Source, AIML, PHP, MySQL, Chatbot, Program-O, Version2" />
	</head>
	<body>
	<?php echo $display;?>
		<form method="get" action="index.php">
			<p>
				<label>Say:</label>	
				<input type="text" name="say" id="say" />
				<input type="submit" name="submit" id="say" value="say" />
				<input type="hidden" name="convo_id" id="convo_id" value="<?php echo $convo_id;?>" />
				<input type="hidden" name="bot_id" id="bot_id" value="<?php echo $bot_id;?>" />
				<input type="hidden" name="format" id="format" value="<?php echo $format;?>" />
			</p>
		</form>
	</body>
</html>
