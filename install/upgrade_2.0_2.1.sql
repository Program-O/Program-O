--  SQL refactor - Program O version 2.1

ALTER TABLE  `aiml_userdefined` CHANGE  `userid`  `user_id` INT( 11 ) NOT NULL,
CHANGE  `botid`  `bot_id` INT( 11 ) NOT NULL;
ALTER TABLE  `botpersonality` CHANGE  `bot`  `bot_id` INT( 11 ) NOT NULL;
ALTER TABLE  `conversation_log` CHANGE  `userid`  `user_id` INT( 11 ) NOT NULL,
ADD  `convo_id` TEXT NOT NULL AFTER  `user_id`;
ALTER TABLE  `myprogramo` CHANGE  `uname`  `user_name` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE  `pword`  `password` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE  `lastip`  `last_ip` VARCHAR( 25 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE  `lastlogin`  `last_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE  `undefined_defaults` CHANGE  `bot`  `bot_id` INT( 11 ) NOT NULL,
CHANGE  `pattern`  `pattern` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE  `replacement`  `template` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
ADD  `user_id` INT( 11 ) NOT NULL AFTER  `bot_id`,
ADD  `topic` TEXT NOT NULL AFTER  `user_id`;
ALTER TABLE  `unknown_inputs` CHANGE  `userid`  `user_id` INT( 11 ) NOT NULL;
ALTER TABLE  `unknown_inputs` ADD `bot_id` INT NOT NULL AFTER `id`;
ALTER TABLE  `users` CHANGE  `name`  `user_name` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
