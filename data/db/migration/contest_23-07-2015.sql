ALTER TABLE  `contest` ADD  `voting_start_date` DATE NOT NULL AFTER  `winners_announce_date` ,
ADD  `max_no_of_photos` INT( 11 ) NOT NULL AFTER  `voting_start_date` ;

ALTER TABLE  `contest` ADD  `is_exclusive` INT( 1 ) NOT NULL AFTER  `voting_started` ;