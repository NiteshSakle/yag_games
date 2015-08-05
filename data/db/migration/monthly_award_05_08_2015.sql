INSERT INTO `yag`.`ps4_awards` (`id`, `title`, `description`, `active`, `image`) VALUES (5, 'Contest Winner', 'Artist awards that have won the contest', '1', 'uploads/awards/_1404807606.png');
ALTER TABLE  `ps4_monthly_award` ADD  `contest_id` INT NOT NULL AFTER  `member_id` ;
ALTER TABLE  `contest_winner` ADD UNIQUE (
`contest_media_id` ,
`rank`
);