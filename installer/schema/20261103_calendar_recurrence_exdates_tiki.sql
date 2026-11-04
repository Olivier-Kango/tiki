CREATE TABLE IF NOT EXISTS `tiki_calendar_recurrence_exdates` (
  `recurrenceId` INT NOT NULL,
  `recurrenceStart` INT UNSIGNED NOT NULL COMMENT 'Original start of an occurrence deleted from the series',
  PRIMARY KEY (`recurrenceId`, `recurrenceStart`)
) ENGINE=MyISAM;
