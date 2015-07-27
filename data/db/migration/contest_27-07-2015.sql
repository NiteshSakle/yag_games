ALTER TABLE  `contest_winner` CHANGE  `contest_id`  `contest_media_id` INT( 11 ) NOT NULL ;
ALTER TABLE `contest_winner`  DROP `media_id`;