<?php

if(!file_exists("config/global_config.php"))
{
	# No config exists we will run install
	header("location: install");
}
else
{
	# Config exists we will goto the bot
	header("location: gui/plain");
}

?>
