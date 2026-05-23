# Tables

CREATE TABLE `companies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `trade_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alias` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `added` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_algos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `algo` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `alias` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `salted` bit(1) NOT NULL,
  `stretched` bit(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_disclosure_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `alias` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_disclosures` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key_password_disclosure_types` int unsigned NOT NULL,
  `url` varchar(2000) COLLATE utf8mb4_general_ci NOT NULL,
  `archive` varchar(2000) COLLATE utf8mb4_general_ci NOT NULL,
  `note` varchar(2000) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `published` datetime DEFAULT NULL,
  `added` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_password_disclosure_types` (`key_password_disclosure_types`),
  CONSTRAINT `password_disclosures_ibfk_1` FOREIGN KEY (`key_password_disclosure_types`) REFERENCES `password_disclosure_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_disclosures_password_storages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key_password_disclosures` int unsigned NOT NULL,
  `key_password_storages` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_password_disclosures_key_password_storages` (`key_password_disclosures`,`key_password_storages`),
  KEY `key_password_storages` (`key_password_storages`),
  CONSTRAINT `password_disclosures_password_storages_ibfk_1` FOREIGN KEY (`key_password_disclosures`) REFERENCES `password_disclosures` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `password_disclosures_password_storages_ibfk_2` FOREIGN KEY (`key_password_storages`) REFERENCES `password_storages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_storages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key_companies` int unsigned DEFAULT NULL,
  `key_password_algos` int unsigned NOT NULL,
  `key_sites` int unsigned DEFAULT NULL,
  `from` datetime DEFAULT NULL,
  `from_confirmed` bit(1) NOT NULL,
  `attributes` json DEFAULT NULL,
  `note` varchar(2000) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_password_algos` (`key_password_algos`),
  KEY `key_sites` (`key_sites`),
  KEY `key_companies` (`key_companies`),
  CONSTRAINT `password_storages_ibfk_1` FOREIGN KEY (`key_companies`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `password_storages_ibfk_2` FOREIGN KEY (`key_password_algos`) REFERENCES `password_algos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `password_storages_ibfk_4` FOREIGN KEY (`key_sites`) REFERENCES `sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `sites` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `key_companies` int unsigned NOT NULL,
  `url` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `alias` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `shared_with` json DEFAULT NULL,
  `added` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alias` (`alias`),
  KEY `key_companies` (`key_companies`),
  CONSTRAINT `sites_ibfk_1` FOREIGN KEY (`key_companies`) REFERENCES `companies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# No data
