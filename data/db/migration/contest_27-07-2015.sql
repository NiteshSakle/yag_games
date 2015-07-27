ALTER TABLE  `contest_winner` CHANGE  `contest_id`  `contest_media_id` INT( 11 ) NOT NULL ;
ALTER TABLE `contest_winner`  DROP `media_id`;
ALTER TABLE  `contest_winner` ADD  `rank` INT NOT NULL AFTER  `contest_media_id` ;