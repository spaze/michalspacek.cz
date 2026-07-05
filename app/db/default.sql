# Tables

CREATE TABLE `article_sources` (
  `id_article_source` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `href` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id_article_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `articles` (
  `id_article` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `date` datetime NOT NULL,
  `key_article_source` int unsigned NOT NULL,
  `excerpt` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `key_replaced_with_blog_post` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id_article`),
  KEY `fk_article_source` (`key_article_source`),
  KEY `key_replaced_with_blog_post` (`key_replaced_with_blog_post`),
  CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`key_replaced_with_blog_post`) REFERENCES `blog_posts` (`id_blog_post`),
  CONSTRAINT `fk_article_source` FOREIGN KEY (`key_article_source`) REFERENCES `article_sources` (`id_article_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `auth_token_types` (
  `id_auth_token_type` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id_auth_token_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Same values as the UserAuthTokenType enum';

CREATE TABLE `auth_tokens` (
  `id_auth_token` int unsigned NOT NULL AUTO_INCREMENT,
  `key_user` int unsigned NOT NULL,
  `selector` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `token` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `created` datetime NOT NULL,
  `type` int NOT NULL,
  PRIMARY KEY (`id_auth_token`),
  UNIQUE KEY `selector` (`selector`),
  KEY `key_user` (`key_user`),
  KEY `type` (`type`),
  CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`key_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `auth_tokens_ibfk_2` FOREIGN KEY (`type`) REFERENCES `auth_token_types` (`id_auth_token_type`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `blog_post_edits` (
  `id_blog_post_edit` int unsigned NOT NULL AUTO_INCREMENT,
  `key_blog_post` int unsigned NOT NULL,
  `edited_at` datetime NOT NULL,
  `edited_at_timezone` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `summary` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  PRIMARY KEY (`id_blog_post_edit`),
  KEY `key_blog_post` (`key_blog_post`),
  CONSTRAINT `blog_post_edits_ibfk_1` FOREIGN KEY (`key_blog_post`) REFERENCES `blog_posts` (`id_blog_post`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `blog_posts` (
  `id_blog_post` int unsigned NOT NULL AUTO_INCREMENT,
  `key_locale` int unsigned NOT NULL,
  `key_twitter_card_type` int unsigned DEFAULT NULL,
  `key_translation_group` int unsigned DEFAULT NULL COMMENT 'No foreign key, yet',
  `slug` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `published` datetime DEFAULT NULL,
  `published_timezone` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `preview_key` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lead` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `originally` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `og_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `slug_tags` json DEFAULT NULL,
  `recommended` json DEFAULT NULL,
  `csp_snippets` json DEFAULT NULL,
  `allowed_tags` json DEFAULT NULL,
  `omit_exports` bit(1) DEFAULT NULL,
  PRIMARY KEY (`id_blog_post`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `key_locale_key_translation_group` (`key_locale`,`key_translation_group`),
  KEY `published` (`published`),
  KEY `key_twitter_card_type` (`key_twitter_card_type`),
  CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`key_twitter_card_type`) REFERENCES `twitter_card_types` (`id_twitter_card_type`) ON UPDATE RESTRICT,
  CONSTRAINT `blog_posts_ibfk_2` FOREIGN KEY (`key_locale`) REFERENCES `locales` (`id_locale`) ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `certificate_requests` (
  `id_certificate_request` int unsigned NOT NULL AUTO_INCREMENT,
  `certificate_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `certificate_name_ext` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cn` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `san` json DEFAULT NULL,
  `time` datetime NOT NULL,
  `time_timezone` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `success` bit(1) NOT NULL,
  PRIMARY KEY (`id_certificate_request`),
  KEY `cn_ext` (`certificate_name`,`certificate_name_ext`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `certificates` (
  `id_certificate` int unsigned NOT NULL AUTO_INCREMENT,
  `key_certificate_request` int unsigned NOT NULL,
  `not_before` datetime NOT NULL,
  `not_before_timezone` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `not_after` datetime NOT NULL,
  `not_after_timezone` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `hidden` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`id_certificate`),
  KEY `key_certificate_request` (`key_certificate_request`),
  CONSTRAINT `certificates_ibfk_3` FOREIGN KEY (`key_certificate_request`) REFERENCES `certificate_requests` (`id_certificate_request`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `files` (
  `id_file` int unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `added` datetime NOT NULL,
  `added_timezone` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id_file`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `forbidden` (
  `id_forbidden` int unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id_forbidden`),
  UNIQUE KEY `ip_UNIQUE` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `gc_log` (
  `gc_type` int NOT NULL,
  `gc_time` datetime NOT NULL,
  `gc_time_timezone` varchar(200) NOT NULL,
  `deleted` int DEFAULT NULL,
  `return_code` int NOT NULL,
  `message` text,
  PRIMARY KEY (`gc_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `interviews` (
  `id_interview` int unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci,
  `date` datetime NOT NULL,
  `href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `audio_href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `audio_embed` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `video_href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `video_thumbnail` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `video_thumbnail_alternative` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `video_embed` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `source_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `source_href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  PRIMARY KEY (`id_interview`),
  UNIQUE KEY `action_UNIQUE` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `languages` (
  `id_language` int unsigned NOT NULL AUTO_INCREMENT,
  `language` varchar(5) NOT NULL,
  PRIMARY KEY (`id_language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `locales` (
  `id_locale` int unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(5) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  PRIMARY KEY (`id_locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `passkeys` (
  `id_passkey` binary(16) NOT NULL,
  `key_user` int unsigned NOT NULL,
  `credential_id` varbinary(1023) NOT NULL,
  `credential_record` json NOT NULL,
  `name` varchar(200) NOT NULL,
  `created` datetime NOT NULL,
  `created_timezone` varchar(200) NOT NULL,
  `last_used` datetime DEFAULT NULL,
  `last_used_timezone` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id_passkey`),
  UNIQUE KEY `credential_id` (`credential_id`),
  KEY `key_user` (`key_user`),
  CONSTRAINT `fk_passkey_key_user` FOREIGN KEY (`key_user`) REFERENCES `users` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `redirections` (
  `id_redirection` int unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(200) NOT NULL,
  `destination` varchar(200) NOT NULL,
  `description` varchar(2000) NOT NULL,
  PRIMARY KEY (`id_redirection`),
  UNIQUE KEY `source_UNIQUE` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `security_events` (
  `id_security_event` int unsigned NOT NULL AUTO_INCREMENT,
  `key_user` int unsigned DEFAULT NULL,
  `action` varchar(200) NOT NULL,
  `created` datetime NOT NULL,
  `created_timezone` varchar(200) NOT NULL,
  `ip` varchar(200) DEFAULT NULL,
  `user_agent` text,
  `details` text,
  PRIMARY KEY (`id_security_event`),
  KEY `key_user` (`key_user`,`created`),
  CONSTRAINT `security_events_ibfk_1` FOREIGN KEY (`key_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `sessions` (
  `id` binary(32) NOT NULL,
  `timestamp` bigint unsigned NOT NULL,
  `data` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `key_user` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_user` (`key_user`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`key_user`) REFERENCES `users` (`id_user`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `talk_slides` (
  `id_slide` int unsigned NOT NULL AUTO_INCREMENT,
  `key_talk` int unsigned NOT NULL,
  `alias` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `number` int unsigned DEFAULT NULL,
  `filename` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `filename_alternative` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `width` smallint unsigned DEFAULT NULL,
  `height` smallint unsigned DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `speaker_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  PRIMARY KEY (`id_slide`),
  UNIQUE KEY `key_talk_number` (`key_talk`,`number`),
  KEY `fk_talk` (`key_talk`),
  CONSTRAINT `talk_slides_ibfk_1` FOREIGN KEY (`key_talk`) REFERENCES `talks` (`id_talk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `talks` (
  `id_talk` int unsigned NOT NULL AUTO_INCREMENT,
  `key_locale` int unsigned NOT NULL,
  `key_translation_group` int unsigned DEFAULT NULL COMMENT 'No foreign key, yet',
  `action` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci,
  `date` datetime NOT NULL,
  `duration` int unsigned DEFAULT NULL,
  `href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `key_talk_slides` int unsigned DEFAULT NULL COMMENT 'The slides from this talk will be symlinked to current talk including transcript and og:image',
  `key_talk_filenames` int unsigned DEFAULT NULL COMMENT 'The image filenames from this talk will be used for slides',
  `slides_href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `slides_embed` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `slides_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci,
  `video_href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `video_thumbnail` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `video_thumbnail_alternative` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `video_embed` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `event` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `event_href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `og_image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `transcript` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci,
  `favorite` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci,
  `key_superseded_by` int unsigned DEFAULT NULL,
  `publish_slides` bit(1) NOT NULL,
  `tags` json DEFAULT NULL,
  `slug_tags` json DEFAULT NULL,
  PRIMARY KEY (`id_talk`),
  UNIQUE KEY `action_UNIQUE` (`action`),
  UNIQUE KEY `key_locale_key_translation_group` (`key_locale`,`key_translation_group`),
  KEY `key_talk_slides` (`key_talk_slides`),
  KEY `key_superseded_by` (`key_superseded_by`),
  CONSTRAINT `talks_ibfk_1` FOREIGN KEY (`key_talk_slides`) REFERENCES `talks` (`id_talk`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `talks_ibfk_2` FOREIGN KEY (`key_superseded_by`) REFERENCES `talks` (`id_talk`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `talks_ibfk_3` FOREIGN KEY (`key_locale`) REFERENCES `locales` (`id_locale`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `training_application_sources` (
  `id_source` int unsigned NOT NULL AUTO_INCREMENT,
  `alias` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `training_application_status` (
  `id_status` int unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `description` varchar(2000) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id_status`),
  UNIQUE KEY `status_UNIQUE` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `training_application_status_flow` (
  `id_status_flow` int unsigned NOT NULL AUTO_INCREMENT,
  `key_status_from` int unsigned DEFAULT NULL,
  `key_status_to` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id_status_flow`),
  UNIQUE KEY `from_to_UNIQUE` (`key_status_from`,`key_status_to`),
  KEY `fk_training_application_status_flow_from_idx` (`key_status_from`),
  KEY `fk_training_application_status_flow_to_idx` (`key_status_to`),
  CONSTRAINT `fk_training_application_status_flow_from` FOREIGN KEY (`key_status_from`) REFERENCES `training_application_status` (`id_status`),
  CONSTRAINT `fk_training_application_status_flow_to` FOREIGN KEY (`key_status_to`) REFERENCES `training_application_status` (`id_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `training_application_status_history` (
  `id_status_log` int unsigned NOT NULL AUTO_INCREMENT,
  `key_application` int unsigned NOT NULL,
  `key_status` int unsigned NOT NULL,
  `status_time` datetime NOT NULL,
  `status_time_timezone` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id_status_log`),
  KEY `fk_status_history_application` (`key_application`),
  KEY `fk_status_history_status` (`key_status`),
  CONSTRAINT `fk_status_history_application` FOREIGN KEY (`key_application`) REFERENCES `training_applications` (`id_application`),
  CONSTRAINT `fk_status_history_status` FOREIGN KEY (`key_status`) REFERENCES `training_application_status` (`id_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `training_applications` (
  `id_application` int unsigned NOT NULL AUTO_INCREMENT,
  `key_date` int unsigned DEFAULT NULL,
  `key_training` int unsigned DEFAULT NULL,
  `name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `email` text CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci,
  `familiar` tinyint(1) NOT NULL DEFAULT '0',
  `company` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `street` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `city` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `zip` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `country` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `company_id` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `company_tax_id` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `note` varchar(2000) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `price` decimal(10,2) unsigned DEFAULT NULL,
  `vat_rate` decimal(4,3) unsigned DEFAULT NULL,
  `price_vat` decimal(10,2) unsigned DEFAULT NULL,
  `discount` int unsigned DEFAULT NULL,
  `invoice_id` int unsigned DEFAULT NULL,
  `paid` datetime DEFAULT NULL,
  `paid_timezone` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `access_token` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `key_status` int unsigned NOT NULL,
  `status_time` datetime NOT NULL,
  `status_time_timezone` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `key_source` int unsigned NOT NULL,
  PRIMARY KEY (`id_application`),
  UNIQUE KEY `access_code_UNIQUE` (`access_token`),
  KEY `fk_training_date` (`key_date`),
  KEY `fk_training_application_status` (`key_status`),
  KEY `fk_training_source` (`key_source`),
  KEY `key_training` (`key_training`),
  CONSTRAINT `fk_training_application_status` FOREIGN KEY (`key_status`) REFERENCES `training_application_status` (`id_status`),
  CONSTRAINT `fk_training_date` FOREIGN KEY (`key_date`) REFERENCES `training_dates` (`id_date`),
  CONSTRAINT `fk_training_source` FOREIGN KEY (`key_source`) REFERENCES `training_application_sources` (`id_source`),
  CONSTRAINT `training_applications_ibfk_1` FOREIGN KEY (`key_training`) REFERENCES `trainings` (`id_training`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `training_cooperations` (
  `id_cooperation` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `description` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id_cooperation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `training_date_status` (
  `id_status` int unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `description` varchar(2000) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Same values as the TrainingDateStatus enum';

CREATE TABLE `training_dates` (
  `id_date` int unsigned NOT NULL AUTO_INCREMENT,
  `key_training` int unsigned NOT NULL,
  `key_venue` int unsigned DEFAULT NULL,
  `remote` bit(1) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `label` json DEFAULT NULL,
  `key_status` int unsigned NOT NULL,
  `public` bit(1) NOT NULL DEFAULT b'1',
  `key_cooperation` int unsigned DEFAULT NULL,
  `note` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `price` int unsigned DEFAULT NULL,
  `student_discount` int unsigned DEFAULT NULL,
  `remote_url` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `remote_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci,
  `video_href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `feedback_href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id_date`),
  KEY `fk_training` (`key_training`),
  KEY `fk_venue` (`key_venue`),
  KEY `fk_training_date_status` (`key_status`),
  KEY `fk_training_cooperation_idx` (`key_cooperation`),
  CONSTRAINT `fk_training` FOREIGN KEY (`key_training`) REFERENCES `trainings` (`id_training`),
  CONSTRAINT `fk_training_cooperation` FOREIGN KEY (`key_cooperation`) REFERENCES `training_cooperations` (`id_cooperation`),
  CONSTRAINT `fk_training_date_status` FOREIGN KEY (`key_status`) REFERENCES `training_date_status` (`id_status`),
  CONSTRAINT `fk_venue` FOREIGN KEY (`key_venue`) REFERENCES `training_venues` (`id_venue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `training_materials` (
  `id_idtraining_material` int unsigned NOT NULL AUTO_INCREMENT,
  `key_file` int unsigned NOT NULL,
  `key_application` int unsigned NOT NULL,
  PRIMARY KEY (`id_idtraining_material`),
  KEY `fk_training_material_file_idx` (`key_file`),
  KEY `fk_training_material_application_idx` (`key_application`),
  CONSTRAINT `fk_training_material_application` FOREIGN KEY (`key_application`) REFERENCES `training_applications` (`id_application`),
  CONSTRAINT `fk_training_material_file` FOREIGN KEY (`key_file`) REFERENCES `files` (`id_file`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `training_reviews` (
  `id_review` int unsigned NOT NULL AUTO_INCREMENT,
  `key_date` int unsigned NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `company` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `job_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `review` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `href` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  `added` datetime NOT NULL,
  `added_timezone` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '1',
  `ranking` int unsigned DEFAULT NULL,
  `note` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci DEFAULT NULL,
  PRIMARY KEY (`id_review`),
  KEY `key_date` (`key_date`),
  CONSTRAINT `training_reviews_ibfk_1` FOREIGN KEY (`key_date`) REFERENCES `training_dates` (`id_date`) ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `training_url_actions` (
  `id_training_url_action` int unsigned NOT NULL AUTO_INCREMENT,
  `key_training` int unsigned NOT NULL,
  `key_url_action` int unsigned NOT NULL,
  PRIMARY KEY (`id_training_url_action`),
  KEY `key_training` (`key_training`),
  KEY `key_url_action` (`key_url_action`),
  CONSTRAINT `training_url_actions_ibfk_1` FOREIGN KEY (`key_training`) REFERENCES `trainings` (`id_training`) ON UPDATE RESTRICT,
  CONSTRAINT `training_url_actions_ibfk_2` FOREIGN KEY (`key_url_action`) REFERENCES `url_actions` (`id_url_action`) ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `training_venues` (
  `id_venue` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `name_extended` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `href` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `address` varchar(2000) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `city` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `description` varchar(2000) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `action` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `entrance` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `entrance_navigation` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `streetview` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `parking` text CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci,
  `public_transport` text CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci,
  `order` tinyint unsigned DEFAULT NULL,
  PRIMARY KEY (`id_venue`),
  UNIQUE KEY `action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `trainings` (
  `id_training` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `description` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `upsell` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `content` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `prerequisites` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `audience` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `capacity` int unsigned DEFAULT NULL,
  `price` int unsigned DEFAULT NULL,
  `student_discount` int unsigned DEFAULT NULL,
  `materials` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `custom` bit(1) NOT NULL DEFAULT b'0',
  `key_successor` int unsigned DEFAULT NULL,
  `key_discontinued` int unsigned DEFAULT NULL,
  `order` tinyint unsigned DEFAULT NULL,
  PRIMARY KEY (`id_training`),
  KEY `key_successor` (`key_successor`),
  KEY `order` (`order`),
  KEY `key_discontinued` (`key_discontinued`),
  CONSTRAINT `trainings_ibfk_1` FOREIGN KEY (`key_successor`) REFERENCES `trainings` (`id_training`),
  CONSTRAINT `trainings_ibfk_2` FOREIGN KEY (`key_discontinued`) REFERENCES `trainings_discontinued` (`id_trainings_discontinued`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `trainings_company` (
  `id_training_company` int unsigned NOT NULL AUTO_INCREMENT,
  `key_training` int unsigned NOT NULL,
  `description` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `upsell` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `price` int unsigned NOT NULL,
  `alternative_duration_price` int unsigned NOT NULL,
  `duration` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `alternative_duration` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  `alternative_duration_price_text` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_czech_ci NOT NULL,
  PRIMARY KEY (`id_training_company`),
  KEY `fk_training` (`key_training`),
  CONSTRAINT `fk_company_training` FOREIGN KEY (`key_training`) REFERENCES `trainings` (`id_training`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE TABLE `trainings_discontinued` (
  `id_trainings_discontinued` int unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `href` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id_trainings_discontinued`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

CREATE TABLE `twitter_card_types` (
  `id_twitter_card_type` int unsigned NOT NULL AUTO_INCREMENT,
  `card` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `title` varchar(200) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  PRIMARY KEY (`id_twitter_card_type`),
  UNIQUE KEY `card` (`card`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `url_actions` (
  `id_url_action` int unsigned NOT NULL AUTO_INCREMENT,
  `key_language` int unsigned NOT NULL,
  `action` varchar(200) NOT NULL,
  PRIMARY KEY (`id_url_action`),
  UNIQUE KEY `key_language_action` (`key_language`,`action`),
  CONSTRAINT `url_actions_ibfk_1` FOREIGN KEY (`key_language`) REFERENCES `languages` (`id_language`) ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE `users` (
  `id_user` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `passkey_user_handle` varbinary(64) NOT NULL,
  `notification_email` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username_UNIQUE` (`username`),
  UNIQUE KEY `passkey_user_handle` (`passkey_user_handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

# Data

INSERT INTO `auth_token_types` VALUES (1,'Permanent login token');
INSERT INTO `auth_token_types` VALUES (3,'Passkey bootstrap token');
INSERT INTO `auth_token_types` VALUES (4,'Passkey add token');

INSERT INTO `languages` VALUES (1,'cs_CZ');
INSERT INTO `languages` VALUES (2,'en_US');

INSERT INTO `locales` VALUES (1,'cs_CZ');
INSERT INTO `locales` VALUES (2,'en_US');

INSERT INTO `training_application_status` VALUES (1,'CREATED','The application was just created. The flow always starts here.');
INSERT INTO `training_application_status` VALUES (2,'TENTATIVE','The application is just tentative because the date of the training is not yet known. Skipped for known dates.');
INSERT INTO `training_application_status` VALUES (3,'INVITED','An invitation was sent as the date for training is known. Skipped for known dates.');
INSERT INTO `training_application_status` VALUES (4,'SIGNED_UP','The attendee is signed up.');
INSERT INTO `training_application_status` VALUES (5,'INVOICE_SENT','The invoice was sent.');
INSERT INTO `training_application_status` VALUES (6,'NOTIFIED','The attendee was notified about an upcoming training or partner was notified that the attendee has signed up.');
INSERT INTO `training_application_status` VALUES (7,'ATTENDED','The attendee has showed up at the training.');
INSERT INTO `training_application_status` VALUES (8,'MATERIALS_SENT','The materials from the training were sent. Could be just a link to download them too.');
INSERT INTO `training_application_status` VALUES (9,'ACCESS_TOKEN_USED','Access token was used to access the website or download the materials.');
INSERT INTO `training_application_status` VALUES (10,'CANCELED','Canceled by the attendee for whatever reason.');
INSERT INTO `training_application_status` VALUES (11,'REFUNDED','Money sent back to attendee for whatever reason eg. training canceled.');
INSERT INTO `training_application_status` VALUES (12,'CREDIT','Payment will be used for application for the next date of the same training, which will have the same invoice id.');
INSERT INTO `training_application_status` VALUES (13,'IMPORTED','The record was imported from a partner website where the attendee signed up at.');
INSERT INTO `training_application_status` VALUES (14,'NON_PUBLIC_TRAINING','The guy have attended a non-public/in-house training.');
INSERT INTO `training_application_status` VALUES (15,'REMINDED','Reminder has been sent.');
INSERT INTO `training_application_status` VALUES (16,'PAID_AFTER','Send the invoice after the training.');
INSERT INTO `training_application_status` VALUES (17,'INVOICE_SENT_AFTER','The invoice was sent after the training.');
INSERT INTO `training_application_status` VALUES (18,'PRO_FORMA_INVOICE_SENT','The pro forma invoice was sent.');
INSERT INTO `training_application_status` VALUES (19,'SPAM','Someone tried to spam or pentest the app.');

INSERT INTO `training_application_status_flow` VALUES (1,1,2);
INSERT INTO `training_application_status_flow` VALUES (20,1,4);
INSERT INTO `training_application_status_flow` VALUES (15,1,13);
INSERT INTO `training_application_status_flow` VALUES (17,1,14);
INSERT INTO `training_application_status_flow` VALUES (2,2,3);
INSERT INTO `training_application_status_flow` VALUES (33,2,10);
INSERT INTO `training_application_status_flow` VALUES (35,2,19);
INSERT INTO `training_application_status_flow` VALUES (3,3,4);
INSERT INTO `training_application_status_flow` VALUES (7,3,10);
INSERT INTO `training_application_status_flow` VALUES (8,4,5);
INSERT INTO `training_application_status_flow` VALUES (4,4,10);
INSERT INTO `training_application_status_flow` VALUES (25,4,16);
INSERT INTO `training_application_status_flow` VALUES (32,4,18);
INSERT INTO `training_application_status_flow` VALUES (36,4,19);
INSERT INTO `training_application_status_flow` VALUES (13,5,10);
INSERT INTO `training_application_status_flow` VALUES (9,5,15);
INSERT INTO `training_application_status_flow` VALUES (14,6,10);
INSERT INTO `training_application_status_flow` VALUES (10,6,15);
INSERT INTO `training_application_status_flow` VALUES (11,7,8);
INSERT INTO `training_application_status_flow` VALUES (27,7,17);
INSERT INTO `training_application_status_flow` VALUES (12,8,9);
INSERT INTO `training_application_status_flow` VALUES (5,10,11);
INSERT INTO `training_application_status_flow` VALUES (6,10,12);
INSERT INTO `training_application_status_flow` VALUES (21,13,10);
INSERT INTO `training_application_status_flow` VALUES (16,13,15);
INSERT INTO `training_application_status_flow` VALUES (24,14,7);
INSERT INTO `training_application_status_flow` VALUES (19,14,10);
INSERT INTO `training_application_status_flow` VALUES (18,14,15);
INSERT INTO `training_application_status_flow` VALUES (22,15,7);
INSERT INTO `training_application_status_flow` VALUES (23,15,10);
INSERT INTO `training_application_status_flow` VALUES (29,16,10);
INSERT INTO `training_application_status_flow` VALUES (26,16,15);
INSERT INTO `training_application_status_flow` VALUES (28,17,8);
INSERT INTO `training_application_status_flow` VALUES (34,18,5);
INSERT INTO `training_application_status_flow` VALUES (30,18,10);
INSERT INTO `training_application_status_flow` VALUES (31,18,15);

INSERT INTO `training_date_status` VALUES (1,'CREATED','Displayed in admin only');
INSERT INTO `training_date_status` VALUES (2,'TENTATIVE','Displayed on the site as month, tentative signup');
INSERT INTO `training_date_status` VALUES (3,'CONFIRMED','Displayed on the site with full date, regular signup');
INSERT INTO `training_date_status` VALUES (4,'CANCELED','Displayed only in admin');

INSERT INTO `twitter_card_types` VALUES (1,'summary','Summary Card');
INSERT INTO `twitter_card_types` VALUES (2,'summary_large_image','Summary Card with Large Image');
