ALTER TABLE `tiki_calendar_recurrence` ADD COLUMN `monthlyType` enum('date','weekday') NULL default NULL AFTER `dayOfMonth`;
ALTER TABLE `tiki_calendar_recurrence` ADD COLUMN `monthlyWeekdayValue` varchar(4) NULL default NULL COMMENT 'Format => (-) + 1digit + 2 letters for weekday (1MO for every 1st Monday or -1TH for last Thursday of each month )' AFTER `monthlyType`;
