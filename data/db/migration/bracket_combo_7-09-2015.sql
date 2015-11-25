ALTER TABLE  `contest_bracket_round` ADD  `current_round` INT NULL AFTER  `round6` ;

ALTER TABLE  `contest_media_rating` ADD  `bracket_combo_id` INT( 11 ) NULL AFTER  `rating` ;