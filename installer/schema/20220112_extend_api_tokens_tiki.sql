ALTER TABLE `tiki_api_tokens`
  ADD `label` VARCHAR(191) NULL DEFAULT NULL AFTER `token`,
  ADD `parameters` TEXT NULL DEFAULT NULL AFTER `label`,
  ADD `type` VARCHAR(100) NOT NULL DEFAULT 'manual' AFTER `tokenId`;