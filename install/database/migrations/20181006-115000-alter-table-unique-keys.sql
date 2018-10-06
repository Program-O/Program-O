#VERSION#2.8.0
#WARNING#This change will remove data from your tables see: https://github.com/Program-O/Program-O/issues/461
#WARNING#Do not proceed until you are happy (ignore this message if this is an initial install)
#QUESTION#Do you want to run this migrate script? (yes/no)


DELETE t1 FROM botpersonality t1 INNER JOIN botpersonality t2 WHERE t1.id < t2.id AND t1.bot_id = t2.bot_id AND t1.name = t2.name;
DELETE t1 FROM bots t1 INNER JOIN bots t2 WHERE t1.bot_id < t2.bot_id AND t1.bot_name = t2.bot_name;
DELETE t1 FROM client_properties t1 INNER JOIN client_properties t2 WHERE t1.id < t2.id AND t1.user_id = t2.user_id AND t1.bot_id = t2.bot_id AND t1.name = t2.name;
DELETE t1 FROM spellcheck t1 INNER JOIN spellcheck t2 WHERE t1.id < t2.id AND t1.missspelling = t2.missspelling;
DELETE t1 FROM srai_lookup t1 INNER JOIN srai_lookup t2 WHERE t1.id < t2.id AND t1.bot_id = t2.bot_id AND t1.pattern = t2.pattern AND t1.template_id = t2.template_id;
DELETE t1 FROM undefined_defaults t1 INNER JOIN undefined_defaults t2 WHERE t1.id < t2.id AND t1.bot_id = t2.bot_id AND t1.user_id = t2.user_id AND t1.pattern = t2.pattern;
DELETE t1 FROM unknown_inputs t1 INNER JOIN unknown_inputs t2 WHERE t1.id < t2.id AND t1.bot_id = t2.bot_id AND t1.user_id = t2.user_id AND t1.input = t2.input;
DELETE t1 FROM users t1 INNER JOIN users t2 WHERE t1.id < t2.id AND t1.session_id = t2.session_id AND t1.bot_id = t2.bot_id;
DELETE t1 FROM wordcensor t1 INNER JOIN wordcensor t2 WHERE t1.censor_id < t2.censor_id AND t1.word_to_censor = t2.word_to_censor;

ALTER TABLE `client_properties` CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `aiml_userdefined` CHANGE `user_id` `user_id` INT NOT NULL;

ALTER TABLE `botpersonality` ADD UNIQUE `unique_botid_name` (`bot_id`, `name`);
ALTER TABLE `bots` ADD UNIQUE `unique_botname` (`bot_name`);
ALTER TABLE `client_properties` ADD UNIQUE `unique_userid_botid_name` (`user_id`, `bot_id`, `name`);
ALTER TABLE `spellcheck` ADD UNIQUE `unique_missspelling` (`missspelling`);
ALTER TABLE `srai_lookup` ADD UNIQUE `unique_botid_pattern_templateid` (`bot_id`, `pattern`(255), `template_id`);
ALTER TABLE `undefined_defaults` ADD UNIQUE `unqiue_botid_userid_pattern` (`bot_id`, `user_id`, `pattern`(255));
ALTER TABLE `unknown_inputs` ADD UNIQUE `unique_botid_input_userid` (`bot_id`, `input`(255), `user_id`);
ALTER TABLE `users` ADD UNIQUE `unique_sessionid_botid` (`session_id`, `bot_id`);
ALTER TABLE `wordcensor` ADD UNIQUE `unique_wordtocensor` (`word_to_censor`);