SET NAMES 'utf8mb4';

-- Issues code reported by mam-analyzer
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('LandingHardFpm', '20', 'Hard landing (<-700 fpm)');
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('TaxiOverspeed', '5', 'Taxi overspeed (>25 knots)');
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('AppHighVsBelow1000AGL', '10', 'High descent rate (<-1000 fpm) below 1000 AGL');
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('AppHighVsBelow2000AGL', '10', 'High descent rate (<-2000 fpm) below 2000 AGL');
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('Refueling', '50', 'Refueling during flight');
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('LandingAllEnginesStopped', NULL, 'Landing with all engines off');
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('LandingSomeEngineStopped', NULL, 'Landing with one engine off');
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('AirborneEngineStopped', NULL, 'Engine shutdown in flight');
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('AirborneAllEnginesStopped', NULL, 'All engines shut down in flight');

-- Issues reported by mam
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('LandingAirportNotPlanned', '30', 'Landed at an airport not in flight plan');
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('LandingOutOfAirport', '30', 'Landing outside an airport');
INSERT INTO issue_type(`code`, `penalty`, `description`) VALUES ('LandingAirportAlternative', NULL, 'Landed at alternative airport');
