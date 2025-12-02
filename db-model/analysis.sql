SET NAMES 'utf8mb4';

-- Mam-analyzer parameters and phases
-- Phase types
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (1, 'startup');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (2, 'taxi');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (3, 'takeoff');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (4, 'cruise');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (5, 'touch_go');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (6, 'approach');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (7, 'final_landing');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (8, 'shutdown');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (9, 'unknown');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (10, 'backtrack');

-- Phase types translations
INSERT INTO flight_phase_type_lang(`flight_phase_type_id`, `language`, `name`) VALUES
(1, 'en', 'Startup'),
(2, 'en', 'Taxi'),
(3, 'en', 'Takeoff'),
(4, 'en', 'Cruise'),
(5, 'en', 'Touch & Go'),
(6, 'en', 'Approach'),
(7, 'en', 'Landing'),
(8, 'en', 'Shutdown'),
(9, 'en', 'Unknown'),
(10, 'en', 'Backtrack'),

(1, 'es', 'Encendido'),
(2, 'es', 'Taxi'),
(3, 'es', 'Despegue'),
(4, 'es', 'Crucero'),
(5, 'es', 'Touch & Go'),
(6, 'es', 'Aproximación'),
(7, 'es', 'Aterrizaje'),
(8, 'es', 'Apagado'),
(9, 'es', 'Desconocida'),
(10, 'es', 'Backtrack');

-- Metric types
-- Takeoff
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'TakeoffBounces' FROM flight_phase_type fp WHERE fp.code='takeoff';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Takeoff Bounces' FROM flight_phase_metric_type fpm WHERE fpm.code='TakeoffBounces';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Rebotes en Despegue' FROM flight_phase_metric_type fpm WHERE fpm.code='TakeoffBounces';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'TakeoffGroundDistance' FROM flight_phase_type fp WHERE fp.code='takeoff';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Takeoff Ground Distance' FROM flight_phase_metric_type fpm WHERE fpm.code='TakeoffGroundDistance';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Distancia de Despegue' FROM flight_phase_metric_type fpm WHERE fpm.code='TakeoffGroundDistance';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'TakeoffSpeed' FROM flight_phase_type fp WHERE fp.code='takeoff';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Takeoff Speed' FROM flight_phase_metric_type fpm WHERE fpm.code='TakeoffSpeed';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Velocidad en Despegue' FROM flight_phase_metric_type fpm WHERE fpm.code='TakeoffSpeed';

-- Cruise
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'Fuel' FROM flight_phase_type fp WHERE fp.code='cruise';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Fuel' FROM flight_phase_metric_type fpm WHERE fpm.code='Fuel';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Fuel' FROM flight_phase_metric_type fpm WHERE fpm.code='Fuel';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'CommonAlt' FROM flight_phase_type fp WHERE fp.code='cruise';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Most Common Altitude' FROM flight_phase_metric_type fpm WHERE fpm.code='CommonAlt';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Altitud más común' FROM flight_phase_metric_type fpm WHERE fpm.code='CommonAlt';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'HighAlt' FROM flight_phase_type fp WHERE fp.code='cruise';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Highest Altitude' FROM flight_phase_metric_type fpm WHERE fpm.code='HighAlt';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Altitud máxima' FROM flight_phase_metric_type fpm WHERE fpm.code='HighAlt';

-- Approach
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'MinVSFpm' FROM flight_phase_type fp WHERE fp.code='approach';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Min Vertical Speed (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='MinVSFpm';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Min Vertical Speed (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='MinVSFpm';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'MaxVSFpm' FROM flight_phase_type fp WHERE fp.code='approach';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Max Vertical Speed (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='MaxVSFpm';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Max Vertical Speed (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='MaxVSFpm';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'AvgVSFpm' FROM flight_phase_type fp WHERE fp.code='approach';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Average Vertical Speed (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='AvgVSFpm';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Vertical Speed Media (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='AvgVSFpm';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'LastMinuteMinVSFpm' FROM flight_phase_type fp WHERE fp.code='approach';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Last minute Min Vertical Speed (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='LastMinuteMinVSFpm';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Último minuto Min Vertical Speed (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='LastMinuteMinVSFpm';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'LastMinuteMaxVSFpm' FROM flight_phase_type fp WHERE fp.code='approach';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Last minute Max Vertical Speed (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='LastMinuteMaxVSFpm';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Último minuto Max Vertical Speed (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='LastMinuteMaxVSFpm';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'LastMinuteAvgVSFpm' FROM flight_phase_type fp WHERE fp.code='approach';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Last minute Average Vertical Speed (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='LastMinuteAvgVSFpm';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Último minuto Vertical Speed Media (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='LastMinuteAvgVSFpm';

-- Landing
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'LandingVSFpm' FROM flight_phase_type fp WHERE fp.code='final_landing';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Touch Vertical Speed' FROM flight_phase_metric_type fpm WHERE fpm.code='LandingVSFpm';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Vertical Speed de la toma' FROM flight_phase_metric_type fpm WHERE fpm.code='LandingVSFpm';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'LandingBounces' FROM flight_phase_type fp WHERE fp.code='final_landing';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Landing bounces Fpm' FROM flight_phase_metric_type fpm WHERE fpm.code='LandingBounces';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Rebotes en Aterrizaje (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='LandingBounces';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'BrakeDistance' FROM flight_phase_type fp WHERE fp.code='final_landing';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Brake distance' FROM flight_phase_metric_type fpm WHERE fpm.code='BrakeDistance';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Distancia de frenado' FROM flight_phase_metric_type fpm WHERE fpm.code='BrakeDistance';
-- Touch & go
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'TouchGoVSFpm' FROM flight_phase_type fp WHERE fp.code='touch_go';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Touch Vertical Speed' FROM flight_phase_metric_type fpm WHERE fpm.code='TouchGoVSFpm';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Vertical Speed de la toma' FROM flight_phase_metric_type fpm WHERE fpm.code='TouchGoVSFpm';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'TouchGoBounces' FROM flight_phase_type fp WHERE fp.code='touch_go';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Touch & go bounces Fpm' FROM flight_phase_metric_type fpm WHERE fpm.code='TouchGoBounces';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Rebotes Touch & Go (Fpm)' FROM flight_phase_metric_type fpm WHERE fpm.code='TouchGoBounces';

INSERT INTO flight_phase_metric_type(flight_phase_type_id, code)
    SELECT fp.id, 'TouchGoGroundDistance' FROM flight_phase_type fp WHERE fp.code='touch_go';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'en', 'Ground distance until airborne' FROM flight_phase_metric_type fpm WHERE fpm.code='TouchGoGroundDistance';
INSERT INTO flight_phase_metric_type_lang(flight_phase_metric_type_id, language, name)
    SELECT fpm.id, 'es', 'Distancia al aire del Touch & Go' FROM flight_phase_metric_type fpm WHERE fpm.code='TouchGoGroundDistance';

-- Event attributes
INSERT INTO flight_event_attribute(code, name) VALUES ('Latitude', 'Latitude');
INSERT INTO flight_event_attribute(code, name) VALUES ('Longitude', 'Longitude');
INSERT INTO flight_event_attribute(code, name) VALUES ('onGround', 'onGround');
INSERT INTO flight_event_attribute(code, name) VALUES ('Altitude', 'Altitude');
INSERT INTO flight_event_attribute(code, name) VALUES ('AGLAltitude', 'Altitude AGL');
INSERT INTO flight_event_attribute(code, name) VALUES ('Altimeter', 'Altimeter');
INSERT INTO flight_event_attribute(code, name) VALUES ('VSFpm', 'Vertical Speed (fpm)');
INSERT INTO flight_event_attribute(code, name) VALUES ('LandingVSFpm', 'Landing Vertical Speed (Fpm)');
INSERT INTO flight_event_attribute(code, name) VALUES ('Heading', 'Heading');
INSERT INTO flight_event_attribute(code, name) VALUES ('GSKnots', 'Ground Speed Knots');
INSERT INTO flight_event_attribute(code, name) VALUES ('IASKnots', 'IAS Knots');
INSERT INTO flight_event_attribute(code, name) VALUES ('QNHSet', 'QNH Set');
INSERT INTO flight_event_attribute(code, name) VALUES ('Flaps', 'Flaps');
INSERT INTO flight_event_attribute(code, name) VALUES ('Gear', 'Gear');
INSERT INTO flight_event_attribute(code, name) VALUES ('FuelKg', 'Fuel Kg');
INSERT INTO flight_event_attribute(code, name) VALUES ('Squawk', 'Squawk');
INSERT INTO flight_event_attribute(code, name) VALUES ('AP', 'Autopilot');
INSERT INTO flight_event_attribute(code, name) VALUES ('Engine 1', 'Engine 1');
INSERT INTO flight_event_attribute(code, name) VALUES ('Engine 2', 'Engine 2');
INSERT INTO flight_event_attribute(code, name) VALUES ('Engine 3', 'Engine 3');
INSERT INTO flight_event_attribute(code, name) VALUES ('Engine 4', 'Engine 4');