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
  `pwd_reset_token` varchar(255) DEFAULT NULL,
  `pwd_reset_token_created_at` datetime DEFAULT NULL,
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

CREATE TABLE `tour` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(200) NOT NULL,
  `start` date NOT NULL,
  `end` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tour_stage` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` mediumint(8) unsigned NOT NULL,
  `departure` char(4) NOT NULL,
  `arrival` char(4) NOT NULL,
  `distance_nm` int(10) unsigned NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `sequence` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tour_stage_unique` (`tour_id`,`sequence`),
  KEY `tour_stage_airport_departure_FK` (`departure`),
  KEY `tour_stage_airport_arrival_FK` (`arrival`),
  CONSTRAINT `tour_stage_airport_arrival_FK` FOREIGN KEY (`arrival`) REFERENCES `airport` (`icao_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tour_stage_airport_departure_FK` FOREIGN KEY (`departure`) REFERENCES `airport` (`icao_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tour_stage_tour_FK` FOREIGN KEY (`tour_id`) REFERENCES `tour` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pilot_tour_completion` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pilot_id` int(10) unsigned NOT NULL,
  `tour_id` mediumint(8) unsigned NOT NULL,
  `completed_at` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pilot_tour_completion_unique` (`pilot_id`,`tour_id`),
  KEY `pilot_tour_completion_tour_FK` (`tour_id`),
  CONSTRAINT `pilot_tour_completion_pilot_FK` FOREIGN KEY (`pilot_id`) REFERENCES `pilot` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pilot_tour_completion_tour_FK` FOREIGN KEY (`tour_id`) REFERENCES `tour` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
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
  `route_id` bigint(20) unsigned DEFAULT NULL,
  `pilot_id` int(10) unsigned NOT NULL,
  `cruise_speed_unit` char(1) NOT NULL,
  `flight_level_unit` varchar(3) NOT NULL,
  `tour_stage_id` mediumint(8) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `submited_flightplans_unique_pilot_id` (`pilot_id`),
  UNIQUE KEY `submitted_flightplan_unique_aircraft_id` (`aircraft_id`),
  KEY `submitted_flightplans_routes_FK` (`route_id`),
  KEY `submitted_flightplan_airport_alt1_FK` (`alternative1_icao`),
  KEY `submitted_flightplan_airport_alt2_FK` (`alternative2_icao`),
  KEY `submitted_flightplan_tour_stage_FK` (`tour_stage_id`),
  CONSTRAINT `submitted_flightplan_airport_alt1_FK` FOREIGN KEY (`alternative1_icao`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `submitted_flightplan_airport_alt2_FK` FOREIGN KEY (`alternative2_icao`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `submitted_flightplans_aircraft_reserved_FK` FOREIGN KEY (`aircraft_id`) REFERENCES `aircraft` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `submitted_flightplans_pilots_FK` FOREIGN KEY (`pilot_id`) REFERENCES `pilot` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `submitted_flightplans_routes_FK` FOREIGN KEY (`route_id`) REFERENCES `route` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `submitted_flightplans_tour_stage_FK` FOREIGN KEY (`tour_stage_id`) REFERENCES `tour_stage` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
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
  `flight_rules` char(1) NOT NULL,
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
  `validator_comments` varchar(400) DEFAULT NULL,
  `validator_id` int(10) unsigned DEFAULT NULL,
  `validation_date` datetime DEFAULT NULL,
  `tour_stage_id` mediumint(8) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flight_pilot_FK` (`pilot_id`),
  KEY `flight_aircraft_FK` (`aircraft_id`),
  KEY `flight_departure_FK` (`departure`),
  KEY `flight_arrival_FK` (`arrival`),
  KEY `flight_alt1_FK` (`alternative1_icao`),
  KEY `flight_alt2_FK` (`alternative2_icao`),
  KEY `flight_validator_FK` (`validator_id`),
  KEY `flight_tour_stage_FK` (`tour_stage_id`),
  CONSTRAINT `flight_aircraft_FK` FOREIGN KEY (`aircraft_id`) REFERENCES `aircraft` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `flight_alt1_FK` FOREIGN KEY (`alternative1_icao`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `flight_alt2_FK` FOREIGN KEY (`alternative2_icao`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `flight_arrival_FK` FOREIGN KEY (`arrival`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `flight_departure_FK` FOREIGN KEY (`departure`) REFERENCES `airport` (`icao_code`) ON UPDATE CASCADE,
  CONSTRAINT `flight_pilot_FK` FOREIGN KEY (`pilot_id`) REFERENCES `pilot` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `flight_tour_stage_FK` FOREIGN KEY (`tour_stage_id`) REFERENCES `tour_stage` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `flight_validator_FK` FOREIGN KEY (`validator_id`) REFERENCES `pilot` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight_report` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `flight_id` bigint(20) unsigned NOT NULL,
  `landing_airport` char(4) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `flight_time_minutes` smallint(5) unsigned DEFAULT NULL,
  `block_time_minutes` smallint(5) unsigned DEFAULT NULL,
  `total_fuel_burn_kg` mediumint(8) unsigned DEFAULT NULL,
  `distance_nm` mediumint(8) unsigned DEFAULT NULL,
  `pilot_comments` varchar(400) DEFAULT NULL,
  `initial_fuel_on_board` mediumint(8) unsigned DEFAULT NULL,
  `zero_fuel_weight` int(10) unsigned DEFAULT NULL,
  `crash` tinyint(1) DEFAULT NULL,
  `sim_aircraft_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flight_report_unique` (`flight_id`),
  KEY `flight_report_airport_FK` (`landing_airport`),
  CONSTRAINT `flight_report_airport_FK` FOREIGN KEY (`landing_airport`) REFERENCES `airport` (`icao_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `flight_report_flight_FK` FOREIGN KEY (`flight_id`) REFERENCES `flight` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `acars_file` (
  `chunk_id` tinyint(3) unsigned NOT NULL,
  `flight_report_id` bigint(20) unsigned NOT NULL,
  `sha256sum` char(44) NOT NULL,
  `upload_date` datetime DEFAULT NULL,
  PRIMARY KEY (`flight_report_id`,`chunk_id`),
  CONSTRAINT `acars_file_flight_report_FK` FOREIGN KEY (`flight_report_id`) REFERENCES `flight_report` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `flight_phase_type` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flight_phase_type_unique_key` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight_phase` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `flight_report_id` bigint(20) unsigned NOT NULL,
  `flight_phase_type_id` tinyint(3) unsigned NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flight_phase_unique` (`flight_report_id`,`flight_phase_type_id`,`start`,`end`),
  KEY `flight_phase_flight_report_FK` (`flight_report_id`),
  KEY `flight_phase_flight_phase_type_FK` (`flight_phase_type_id`),
  CONSTRAINT `flight_phase_flight_phase_type_FK` FOREIGN KEY (`flight_phase_type_id`) REFERENCES `flight_phase_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `flight_phase_flight_report_FK` FOREIGN KEY (`flight_report_id`) REFERENCES `flight_report` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight_phase_metric_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `flight_phase_type_id` tinyint(3) unsigned NOT NULL,
  `code` varchar(32) NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flight_phase_metric_type_unique` (`flight_phase_type_id`,`code`),
  CONSTRAINT `flight_phase_metric_type_flight_phase_type_FK` FOREIGN KEY (`flight_phase_type_id`) REFERENCES `flight_phase_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight_phase_metric` (
  `flight_phase_id` bigint(20) unsigned NOT NULL,
  `metric_type_id` int(10) unsigned NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`flight_phase_id`,`metric_type_id`),
  KEY `flight_phase_metric_flight_phase_metric_type_FK` (`metric_type_id`),
  CONSTRAINT `flight_phase_metric_flight_phase_FK` FOREIGN KEY (`flight_phase_id`) REFERENCES `flight_phase` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `flight_phase_metric_flight_phase_metric_type_FK` FOREIGN KEY (`metric_type_id`) REFERENCES `flight_phase_metric_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight_event_attribute` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flight_event_attribute_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight_event` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `phase_id` bigint(20) unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `flight_event_flight_phase_FK` (`phase_id`),
  CONSTRAINT `flight_event_flight_phase_FK` FOREIGN KEY (`phase_id`) REFERENCES `flight_phase` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight_event_data` (
  `event_id` bigint(20) unsigned NOT NULL,
  `attribute_id` int(10) unsigned NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`event_id`,`attribute_id`),
  KEY `flight_event_data_flight_event_attribute_FK` (`attribute_id`),
  CONSTRAINT `flight_event_data_flight_event_FK` FOREIGN KEY (`event_id`) REFERENCES `flight_event` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `flight_event_data_flight_event_attribute_FK` FOREIGN KEY (`attribute_id`) REFERENCES `flight_event_attribute` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `issue_type` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(80) NOT NULL,
  `description` varchar(200) NOT NULL,
  `penalty` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `issue_type_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `flight_phase_issue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `phase_id` bigint(20) unsigned NOT NULL,
  `issue_type_id` smallint(5) unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  `value` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flight_phase_issue_flight_phase_FK` (`phase_id`),
  KEY `flight_phase_issue_issue_type_FK` (`issue_type_id`),
  CONSTRAINT `flight_phase_issue_flight_phase_FK` FOREIGN KEY (`phase_id`) REFERENCES `flight_phase` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `flight_phase_issue_issue_type_FK` FOREIGN KEY (`issue_type_id`) REFERENCES `issue_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `page` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `page_content` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` mediumint(8) unsigned NOT NULL,
  `language` char(2) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content_md` TEXT NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `page_content_page_FK` (`page_id`),
  UNIQUE KEY `page_content_unique` (`page_id`,`language`),
  CONSTRAINT `page_content_page_FK` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;




