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
-- Cruise
-- Approach
-- Landing
