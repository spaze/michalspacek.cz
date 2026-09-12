# Tables

CREATE TABLE `policy_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scheme` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ascii_host` varchar(253) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `port` smallint unsigned NOT NULL,
  `last_check_time` datetime NOT NULL,
  `check_host_result` mediumtext NOT NULL COMMENT 'Not json: MySQL sorts JSON object keys, reordering the redirect chains',
  PRIMARY KEY (`id`),
  UNIQUE KEY `origin` (`scheme`,`ascii_host`,`port`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `version_check` (
  `id` int NOT NULL AUTO_INCREMENT,
  `last_check` datetime NOT NULL,
  `lambda_version` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `lambda_reference` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

# No data
