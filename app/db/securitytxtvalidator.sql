# Tables

CREATE TABLE `library_versions` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `reference` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `first_seen` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `version` (`version`,`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `responses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ascii_host_port` varchar(259) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `fetch_time` datetime NOT NULL,
  `check_host_result` mediumtext NOT NULL COMMENT 'Not json: MySQL sorts JSON object keys, reordering the redirect chains',
  `key_parser_library_version` smallint unsigned NOT NULL,
  `key_fetcher_library_version` smallint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ascii_host_port` (`ascii_host_port`,`fetch_time`),
  KEY `key_parser_library_version` (`key_parser_library_version`),
  KEY `key_fetcher_library_version` (`key_fetcher_library_version`),
  CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`key_parser_library_version`) REFERENCES `library_versions` (`id`),
  CONSTRAINT `responses_ibfk_2` FOREIGN KEY (`key_fetcher_library_version`) REFERENCES `library_versions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `version_check` (
  `id` int NOT NULL AUTO_INCREMENT,
  `last_check` datetime NOT NULL,
  `key_library_version` smallint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key_library_version` (`key_library_version`),
  CONSTRAINT `version_check_ibfk_1` FOREIGN KEY (`key_library_version`) REFERENCES `library_versions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

# No data
