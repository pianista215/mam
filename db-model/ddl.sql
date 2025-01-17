-- CREATE DATABASE `mam` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;

CREATE TABLE `config` (
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `country` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `iso2_code` char(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_unique` (`iso2_code`)
) ENGINE=InnoDB AUTO_INCREMENT=261 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `airport` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `icao_code` char(4) NOT NULL,
  `name` varchar(100) NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `city` varchar(80) NOT NULL,
  `country_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `airports_unique_icao` (`icao_code`),
  KEY `airports_countries_FK` (`country_id`),
  CONSTRAINT `airports_countries_FK` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `aircraft_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `icao_type_code` char(4) NOT NULL,
  `name` varchar(60) NOT NULL,
  `max_nm_range` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aircraft_types_unique` (`icao_type_code`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `aircraft_configuration` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aircraft_type_id` int(10) unsigned NOT NULL,
  `name` varchar(20) NOT NULL,
  `pax_capacity` smallint(5) unsigned NOT NULL,
  `cargo_capacity` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aircraft_configuration_unique` (`aircraft_type_id`,`name`),
  CONSTRAINT `aircraft_configuration_aircraft_type_FK` FOREIGN KEY (`aircraft_type_id`) REFERENCES `aircraft_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `aircraft` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `aircraft_configuration_id` int(10) unsigned NOT NULL,
  `registration` varchar(10) NOT NULL,
  `name` varchar(20) NOT NULL,
  `location` char(4) NOT NULL,
  `hours_flown` double unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aircrafts_registration_unique` (`registration`),
  UNIQUE KEY `aircrafts_name_unique` (`name`),
  KEY `aircrafts_aircraft_types_FK` (`aircraft_configuration_id`),
  KEY `aircrafts_airports_FK` (`location`),
  CONSTRAINT `aircraft_aircraft_configuration_FK` FOREIGN KEY (`aircraft_configuration_id`) REFERENCES `aircraft_configuration` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `aircrafts_airports_FK` FOREIGN KEY (`location`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pilot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `license` varchar(8) DEFAULT NULL,
  `name` varchar(20) NOT NULL,
  `surname` varchar(40) NOT NULL,
  `email` varchar(80) NOT NULL,
  `registration_date` date NOT NULL DEFAULT current_timestamp(),
  `city` varchar(40) NOT NULL,
  `country_id` int(10) unsigned NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_of_birth` date NOT NULL,
  `vatsim_id` bigint(20) unsigned DEFAULT NULL,
  `ivao_id` bigint(20) unsigned DEFAULT NULL,
  `auth_key` char(32) DEFAULT NULL,
  `access_token` char(32) DEFAULT NULL,
  `hours_flown` double unsigned DEFAULT 0,
  `location` char(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pilot_unique` (`email`),
  UNIQUE KEY `pilots_unique_license` (`license`),
  KEY `pilots_countries_FK` (`country_id`),
  KEY `pilot_airport_FK` (`location`),
  CONSTRAINT `pilot_airport_FK` FOREIGN KEY (`location`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `pilots_countries_FK` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE `submitted_flight_plan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aircraft_id` int(10) unsigned NOT NULL,
  `flight_rules` char(1) NOT NULL,
  `alternative1_icao` char(4) NOT NULL,
  `alternative2_icao` char(4) DEFAULT NULL,
  `cruise_speed_value` varchar(4) NOT NULL,
  `flight_level_value` varchar(4) NOT NULL,
  `route` varchar(400) NOT NULL,
  `estimated_time` char(4) NOT NULL,
  `other_information` varchar(400) NOT NULL,
  `endurance_time` char(4) NOT NULL,
  `route_id` bigint(20) unsigned NOT NULL,
  `pilot_id` int(10) unsigned NOT NULL,
  `cruise_speed_unit` char(1) NOT NULL,
  `flight_level_unit` varchar(3) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `submited_flightplans_unique_pilot_id` (`pilot_id`),
  UNIQUE KEY `submitted_flightplan_unique_aircraft_id` (`aircraft_id`),
  KEY `submitted_flightplans_routes_FK` (`route_id`),
  KEY `submitted_flightplan_airport_alt1_FK` (`alternative1_icao`),
  KEY `submitted_flightplan_airport_alt2_FK` (`alternative2_icao`),
  CONSTRAINT `submitted_flightplan_airport_alt1_FK` FOREIGN KEY (`alternative1_icao`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `submitted_flightplan_airport_alt2_FK` FOREIGN KEY (`alternative2_icao`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `submitted_flightplans_aircraft_reserved_FK` FOREIGN KEY (`aircraft_id`) REFERENCES `aircraft` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `submitted_flightplans_pilots_FK` FOREIGN KEY (`pilot_id`) REFERENCES `pilot` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `submitted_flightplans_routes_FK` FOREIGN KEY (`route_id`) REFERENCES `route` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pilot_id` int(10) unsigned NOT NULL,
  `aircraft_id` int(10) unsigned NOT NULL,
  `code` varchar(10) NOT NULL,
  `departure` char(4) NOT NULL,
  `arrival` char(4) NOT NULL,
  `alternative1_icao` char(4) NOT NULL,
  `alternative2_icao` char(4) DEFAULT NULL,
  `cruise_speed_value` varchar(4) NOT NULL,
  `cruise_speed_unit` char(1) NOT NULL,
  `flight_level_value` varchar(4) NOT NULL,
  `flight_level_unit` varchar(3) NOT NULL,
  `route` varchar(400) NOT NULL,
  `estimated_time` char(4) NOT NULL,
  `other_information` varchar(400) NOT NULL,
  `endurance_time` char(4) NOT NULL,
  `report_tool` varchar(20) NOT NULL,
  `status` char(1) NOT NULL DEFAULT 'C',
  `creation_date` datetime NOT NULL DEFAULT current_timestamp(),
  `network` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flight_pilot_FK` (`pilot_id`),
  KEY `flight_aircraft_FK` (`aircraft_id`),
  KEY `flight_departure_FK` (`departure`),
  KEY `flight_arrival_FK` (`arrival`),
  KEY `flight_alt1_FK` (`alternative1_icao`),
  KEY `flight_alt2_FK` (`alternative2_icao`),
  CONSTRAINT `flight_aircraft_FK` FOREIGN KEY (`aircraft_id`) REFERENCES `aircraft` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `flight_alt1_FK` FOREIGN KEY (`alternative1_icao`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `flight_alt2_FK` FOREIGN KEY (`alternative2_icao`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `flight_arrival_FK` FOREIGN KEY (`arrival`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `flight_departure_FK` FOREIGN KEY (`departure`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `flight_pilot_FK` FOREIGN KEY (`pilot_id`) REFERENCES `pilot` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight_report` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `flight_id` bigint(20) unsigned NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `flight_time_minutes` smallint(5) unsigned DEFAULT NULL,
  `block_time_minutes` smallint(5) unsigned DEFAULT NULL,
  `total_fuel_burn_kg` mediumint(8) unsigned DEFAULT NULL,
  `distance_nm` mediumint(8) unsigned DEFAULT NULL,
  `pilot_comments` varchar(400) DEFAULT NULL,
  `validator_comments` varchar(400) DEFAULT NULL,
  `initial_fuel_on_board` mediumint(8) unsigned DEFAULT NULL,
  `zero_fuel_weight` int(10) unsigned DEFAULT NULL,
  `crash` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flight_report_unique` (`flight_id`),
  CONSTRAINT `flight_report_flight_FK` FOREIGN KEY (`flight_id`) REFERENCES `flight` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `acars_file` (
  `chunk_id` tinyint(3) unsigned NOT NULL,
  `flight_report_id` bigint(20) unsigned NOT NULL,
  `sha256sum` binary(32) NOT NULL,
  `upload_date` datetime DEFAULT NULL,
  PRIMARY KEY (`flight_report_id`,`chunk_id`),
  CONSTRAINT `acars_file_flight_report_FK` FOREIGN KEY (`flight_report_id`) REFERENCES `flight_report` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

