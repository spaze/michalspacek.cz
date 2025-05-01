# Tables

CREATE TABLE `policy_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ascii_host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `last_check_time` datetime NOT NULL,
  `check_host_result` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ascii_host` (`ascii_host`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `version_check` (
  `id` int NOT NULL AUTO_INCREMENT,
  `last_check` datetime NOT NULL,
  `lambda_version` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `lambda_reference` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

# No data
