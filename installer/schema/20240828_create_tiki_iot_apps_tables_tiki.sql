
CREATE TABLE IF NOT EXISTS `tiki_iot_apps` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `app_uuid` VARCHAR(50) NOT NULL,
  `trackerId` INT(11) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `active` VARCHAR(2) NOT NULL DEFAULT 'y',
  `scenario_config` LONGTEXT NULL,
  `dashboard_config` LONGTEXT NULL,
  `state_object` LONGTEXT NULL,
  `iot_bridge_access_token` TEXT NULL,
  `iot_bridge_access_token_expire_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX (`app_uuid`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `tiki_iot_apps_actions_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `app_uuid` VARCHAR(50) NOT NULL,
  `action_message` LONGTEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;
