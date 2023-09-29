ALTER TABLE tiki_actionlog_conf
    DROP PRIMARY KEY,
    DROP INDEX `id`,
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `uk_action_obj` (`action`, `objectType`);

ALTER TABLE tiki_history
    DROP PRIMARY KEY,
    DROP INDEX `user`,
    DROP INDEX `historyId`,
    ADD PRIMARY KEY (`historyId`),
    ADD UNIQUE KEY `uk_version_pageName` (`pageName`,`version`),
    ADD KEY `k_user` (`user`(191));

ALTER TABLE tiki_translated_objects
    CHANGE `traId` `traId` int(14) NOT NULL DEFAULT 0,
    ADD `id` INT(14) NOT NULL AUTO_INCREMENT,
    DROP PRIMARY KEY,
    DROP INDEX `traId`,
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `uk_type_objId` (`type`, `objId`(141));

ALTER TABLE tiki_untranslated
    DROP PRIMARY KEY,
    DROP INDEX `id_2`,
    DROP INDEX `id`,
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `uk_source_lang` (`source`(255),`lang`);