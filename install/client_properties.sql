/***************************************
* http://www.program-o.com
* PROGRAM O
* Version: 2.1.2
* FILE: client_properties.sql
* AUTHOR: Elizabeth Perreau and Dave Morton
* DATE: 02-13-2013
* DETAILS: Creates the client_properties table in the DB
***************************************/

CREATE TABLE `programo2dev`.`client_properties` (
`id` INT NOT NULL ,
`user_id` INT NOT NULL ,
`bot_id` INT NOT NULL ,
`name` TEXT NOT NULL ,
`value` TEXT NOT NULL ,
PRIMARY KEY ( `id` )
) ENGINE = MYISAM;