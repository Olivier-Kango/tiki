ALTER TABLE `tiki_machine_learning_models` ADD `labelField` varchar(191) AFTER `trackerFields`;
ALTER TABLE `tiki_machine_learning_models` ADD `ignoreEmpty` tinyint(1) AFTER `labelField`;
