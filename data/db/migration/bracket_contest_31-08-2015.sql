ALTER TABLE  `contest_bracket_round` ADD  `round6` DATE NOT NULL AFTER  `round5` ;
ALTER TABLE  `contest_bracket_round` CHANGE  `round1`  `round1` DATE NOT NULL ,
CHANGE  `round2`  `round2` DATE NOT NULL ,
CHANGE  `round3`  `round3` DATE NOT NULL ,
CHANGE  `round4`  `round4` DATE NOT NULL ,
CHANGE  `round5`  `round5` DATE NOT NULL ;
