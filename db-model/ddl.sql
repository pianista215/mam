CREATE DATABASE `mam` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;

CREATE TABLE `aircraft_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `icao_type_code` char(4) NOT NULL,
  `name` varchar(60) NOT NULL,
  `max_nm_range` int(10) unsigned NOT NULL,
  `pax_capacity` smallint(5) unsigned NOT NULL,
  `cargo_capacity` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aircraft_types_unique` (`icao_type_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `aircraft` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aircraft_type_id` int(10) unsigned NOT NULL,
  `registration` varchar(10) NOT NULL,
  `name` varchar(20) NOT NULL,
  `location` char(4) NOT NULL,
  `hours_flown` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aircrafts_registration_unique` (`registration`),
  UNIQUE KEY `aircrafts_name_unique` (`name`),
  KEY `aircrafts_aircraft_types_FK` (`aircraft_type_id`),
  KEY `aircrafts_airports_FK` (`location`),
  CONSTRAINT `aircrafts_aircraft_types_FK` FOREIGN KEY (`aircraft_type_id`) REFERENCES `aircraft_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `aircrafts_airports_FK` FOREIGN KEY (`location`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `airport` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `iata_code` char(3) NOT NULL,
  `icao_code` char(4) NOT NULL,
  `name` varchar(100) NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `city` varchar(80) NOT NULL,
  `country_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `airports_unique_icao` (`icao_code`),
  UNIQUE KEY `airports_unique_iata` (`iata_code`),
  KEY `airports_countries_FK` (`country_id`),
  CONSTRAINT `airports_countries_FK` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `country` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `iso2_code` char(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_unique` (`iso2_code`)
) ENGINE=InnoDB AUTO_INCREMENT=261 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight_report` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aircraft_name` varchar(100) NOT NULL,
  `pilot_id` int(10) unsigned NOT NULL,
  `departure_icao` char(4) NOT NULL,
  `arrival_icao` char(4) NOT NULL,
  `alt1_icao` char(4) NOT NULL,
  `alt2_icao` char(4) DEFAULT NULL,
  `distance_nm` smallint(5) unsigned NOT NULL,
  `duration_min` char(4) NOT NULL,
  `flight_rules` char(1) NOT NULL,
  `flight_type` char(1) NOT NULL,
  `cruise_speed` varchar(5) NOT NULL,
  `flight_level` varchar(5) NOT NULL,
  `route` varchar(400) NOT NULL,
  `estimated_time` char(4) NOT NULL,
  `other_information` varchar(400) NOT NULL,
  `endurance_time` char(4) NOT NULL,
  `aircraft_type_icao` char(4) NOT NULL,
  `flight_date` datetime NOT NULL,
  `aircraft_registration` varchar(10) NOT NULL,
  `pilot_comments` varchar(400) DEFAULT NULL,
  `validator_comments` varchar(400) DEFAULT NULL,
  `zfw` int(10) unsigned NOT NULL,
  `block_fuel` int(10) unsigned NOT NULL,
  `initial_fuel` int(10) unsigned NOT NULL,
  `consumed_fuel` int(10) unsigned NOT NULL,
  `network` varchar(15) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `flight_report_pilots_FK` (`pilot_id`),
  CONSTRAINT `flight_report_pilots_FK` FOREIGN KEY (`pilot_id`) REFERENCES `pilot` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pilot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `license` varchar(8) NOT NULL,
  `name` varchar(20) NOT NULL,
  `surname` varchar(40) NOT NULL,
  `email` varchar(80) NOT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `registration_date` date NOT NULL,
  `city` varchar(40) NOT NULL,
  `country_id` int(10) unsigned NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_validator` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pilots_unique_license` (`license`),
  KEY `pilots_countries_FK` (`country_id`),
  CONSTRAINT `pilots_countries_FK` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `route` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `departure` char(4) NOT NULL,
  `arrival` char(4) NOT NULL,
  `distance_nm` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `routes_unique` (`code`),
  UNIQUE KEY `routes_unique_dep_arr` (`departure`,`arrival`),
  KEY `routes_airports_arrival_FK` (`arrival`),
  CONSTRAINT `routes_airports_arrival_FK` FOREIGN KEY (`arrival`) REFERENCES `airport` (`icao_code`),
  CONSTRAINT `routes_airports_departure_FK` FOREIGN KEY (`departure`) REFERENCES `airport` (`icao_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `submitted_flightplan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aircraft_id` int(10) unsigned NOT NULL,
  `flight_rules` char(1) NOT NULL,
  `flight_type` char(1) NOT NULL,
  `alternative1_icao` char(4) NOT NULL,
  `alternative2_icao` char(4) NOT NULL,
  `cruise_speed` varchar(5) NOT NULL,
  `flight_level` varchar(5) NOT NULL,
  `route` varchar(400) NOT NULL,
  `estimated_time` char(4) NOT NULL,
  `other_information` varchar(400) NOT NULL,
  `endurance_time` char(4) NOT NULL,
  `route_id` bigint(20) unsigned NOT NULL,
  `pilot_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `submited_flightplans_unique_pilot_id` (`pilot_id`),
  KEY `submitted_flightplans_routes_FK` (`route_id`),
  KEY `submitted_flightplans_aircraft_reserved_FK` (`aircraft_id`),
  CONSTRAINT `submitted_flightplans_aircraft_reserved_FK` FOREIGN KEY (`aircraft_id`) REFERENCES `aircraft` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `submitted_flightplans_pilots_FK` FOREIGN KEY (`pilot_id`) REFERENCES `pilot` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `submitted_flightplans_routes_FK` FOREIGN KEY (`route_id`) REFERENCES `route` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;