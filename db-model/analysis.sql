-- Mam-analyzer parameters and phases
-- Phase types
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (1, 'startup', 'Startup');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (2, 'taxi', 'Taxi');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (3, 'takeoff', 'Takeoff');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (4, 'cruise', 'Cruise');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (5, 'touch_go', 'Touch & Go');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (6, 'approach', 'Approach');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (7, 'final_landing', 'Landing');
INSERT INTO flight_phase_type(`id`, `code`, `name`) VALUES (8, 'shutdown', 'Shutdown');

-- Metric types
-- Takeoff
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'TakeoffBounces', 'Takeoff Bounces' FROM flight_phase_type fp WHERE fp.code='takeoff';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'TakeoffGroundDistance', 'Takeoff Ground Distance' FROM flight_phase_type fp WHERE fp.code='takeoff';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'TakeoffSpeed', 'Takeoff Speed' FROM flight_phase_type fp WHERE fp.code='takeoff';
-- Cruise
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'Fuel', 'Fuel' FROM flight_phase_type fp WHERE fp.code='cruise';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'CommonAlt', 'Most Common Altitude' FROM flight_phase_type fp WHERE fp.code='cruise';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'HighAlt', 'Highest Altitude' FROM flight_phase_type fp WHERE fp.code='cruise';
-- Approach
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'MinVSFpm', 'Min Vertical Speed (Fpm)' FROM flight_phase_type fp WHERE fp.code='approach';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'MaxVSFpm', 'Max Vertical Speed (Fpm)' FROM flight_phase_type fp WHERE fp.code='approach';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'AvgVSFpm', 'Average Vertical Speed (Fpm)' FROM flight_phase_type fp WHERE fp.code='approach';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'LastMinuteMinVSFpm', 'Last minute Min Vertical Speed (Fpm)' FROM flight_phase_type fp WHERE fp.code='approach';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'LastMinuteMaxVSFpm', 'Last minute Max Vertical Speed (Fpm)' FROM flight_phase_type fp WHERE fp.code='approach';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'LastMinuteAvgVSFpm', 'Last minute Average Vertical Speed (Fpm)' FROM flight_phase_type fp WHERE fp.code='approach';
-- Landing
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'LandingVSFpm', 'Touch Vertical Speed' FROM flight_phase_type fp WHERE fp.code='final_landing';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'LandingBounces', 'Landing bounces Fpm' FROM flight_phase_type fp WHERE fp.code='final_landing';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'BrakeDistance', 'Brake distance' FROM flight_phase_type fp WHERE fp.code='final_landing';
-- Touch & go
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'TouchGoVSFpm', 'Touch Vertical Speed' FROM flight_phase_type fp WHERE fp.code='touch_go';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'TouchGoBounces', 'Touch & go bounces Fpm' FROM flight_phase_type fp WHERE fp.code='touch_go';
INSERT INTO flight_phase_metric_type(flight_phase_type_id, code, name) select fp.id,'TouchGoGroundDistance', 'Ground distance until airborne' FROM flight_phase_type fp WHERE fp.code='touch_go';