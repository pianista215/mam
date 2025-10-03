-- Issues code reported by mam-analyzer
INSERT INTO issue_type(`code`, `penalty`) VALUES ('LandingHardFpm', '20');
INSERT INTO issue_type(`code`, `penalty`) VALUES ('TaxiOverspeed', '5');
INSERT INTO issue_type(`code`, `penalty`) VALUES ('AppHighVsBelow1000AGL', '10');
INSERT INTO issue_type(`code`, `penalty`) VALUES ('AppHighVsBelow2000AGL', '10');
INSERT INTO issue_type(`code`, `penalty`) VALUES ('Refueling', '50');
INSERT INTO issue_type(`code`, `penalty`) VALUES ('LandingAllEnginesStopped', NULL);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('LandingSomeEngineStopped', NULL);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('AirborneEngineStopped', NULL);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('AirborneAllEnginesStopped', NULL);

-- Issues reported by mam
INSERT INTO issue_type(`code`, `penalty`) VALUES ('LandingAirportNotPlanned', '30');
INSERT INTO issue_type(`code`, `penalty`) VALUES ('LandingOutOfAirport', '30');
