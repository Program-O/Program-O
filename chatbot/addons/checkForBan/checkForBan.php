<?php
  /***************************************************/
  /*                 checkForBan.php                 */
  /*             Program O Addon Script              */
  /* Checks the user's IP address against a list of  */
  /* address that were banned, aborting the script   */
  /* if the address is found, and notifying the user */
  /* of the ban.                                     */
  /* ©2011 Geek Cave Creations - All rights Reserved */
  /*                  Coded by Dave                  */
  /***************************************************/

  ####################################################
  # I'm planning on integrating this addon into the  #
  # database, at some point, rather than making use  #
  # of a text file. This will increase security of   #
  # script, overall, but will require the addition   #
  # of a new table in the database, which means that #
  # an install script will also need to be created,  #
  # along with an uninstall script, so that if the   #
  # addon is ever removed, it won't leave anything   #
  # behind, such as a 'Banned Users' table.          #
  ####################################################

  function checkIP($convoArr) {
    global $default_debugemail;
    $IP = $_SERVER['REMOTE_ADDR'];
    if (!file_exists('banList.txt')) return $convoArr;
    $ipFile = file_get_contents('banList.txt');
    $ipList = explode("\n",$ipFile);
    if (in_array($IP,$ipList)) {
      $convoArr['send_to_user'] = <<<endMessage
You have been banned from using this chat interface.
If you feel that this is in error, please contact
<a href="mailto:$default_debugemail">$default_debugemail</a>.
endMessage;
      $convoArr['abort'] = true;
    }
    return $convoArr;
  }

?>
