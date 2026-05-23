# Tables

CREATE TABLE `keys` (
  `id_key` int unsigned NOT NULL AUTO_INCREMENT,
  `key_ssid` int unsigned NOT NULL,
  `prefix_id` tinyint unsigned NOT NULL,
  `serial` int unsigned NOT NULL,
  `key` bit(40) NOT NULL,
  `type` int unsigned NOT NULL,
  PRIMARY KEY (`id_key`),
  KEY `key_ssid` (`key_ssid`),
  CONSTRAINT `keys_ibfk_1` FOREIGN KEY (`key_ssid`) REFERENCES `ssids` (`id_ssid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `keys_ubee` (
  `mac` mediumint unsigned NOT NULL,
  `ssid` mediumint unsigned NOT NULL,
  `key` bit(40) NOT NULL,
  PRIMARY KEY (`mac`),
  KEY `ssid` (`ssid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ssids` (
  `id_ssid` int unsigned NOT NULL AUTO_INCREMENT,
  `ssid` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `added` datetime NOT NULL,
  `added_timezone` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_ssid`),
  UNIQUE KEY `ssid` (`ssid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

# No data
