--
-- Table structure for table `contest_rankings_modify`
--

CREATE TABLE IF NOT EXISTS `contest_rankings_modify` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `admin_id` int(4) NOT NULL,
  `contest_media_id` int(10) unsigned NOT NULL,
  `intended_rank` int(10) unsigned NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contest_media_id` (`contest_media_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


--
-- Table structure for table `contest_rankings_processed`
--

CREATE TABLE IF NOT EXISTS `contest_rankings_processed` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contest_rankings_modify_id` bigint(20) unsigned NOT NULL,
  `before_rank` int(10) unsigned NOT NULL,
  `after_rank` int(10) unsigned NOT NULL,
  `processed` tinyint(4) NOT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;