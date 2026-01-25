SET NAMES 'utf8mb4';

-- Issues code reported by mam-analyzer
INSERT INTO issue_type(`code`, `penalty`) VALUES ('LandingHardFpm', 20);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('TaxiOverspeed', 5);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('AppHighVsBelow1000AGL', 10);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('AppHighVsBelow2000AGL', 10);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('Refueling', 50);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('LandingAllEnginesStopped', NULL);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('LandingSomeEngineStopped', NULL);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('AirborneEngineStopped', NULL);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('AirborneAllEnginesStopped', NULL);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('ZfwModified', 30);

-- Issues reported by mam
INSERT INTO issue_type(`code`, `penalty`) VALUES ('LandingAirportNotPlanned', 30);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('LandingOutOfAirport', 30);
INSERT INTO issue_type(`code`, `penalty`) VALUES ('LandingAirportAlternative', NULL);

-- Descriptions with translations
INSERT INTO issue_type_lang(issue_type_id, language, description) VALUES
((SELECT id FROM issue_type WHERE code='LandingHardFpm'), 'en', 'Hard landing (<-450 fpm)'),
((SELECT id FROM issue_type WHERE code='LandingHardFpm'), 'es', 'Hard landing (<-450 fpm)'),

((SELECT id FROM issue_type WHERE code='TaxiOverspeed'), 'en', 'Taxi overspeed (>25 knots)'),
((SELECT id FROM issue_type WHERE code='TaxiOverspeed'), 'es', 'Taxi overspeed (>25 nudos)'),

((SELECT id FROM issue_type WHERE code='AppHighVsBelow1000AGL'), 'en', 'High descent rate (<-1000 fpm) below 1000 AGL'),
((SELECT id FROM issue_type WHERE code='AppHighVsBelow1000AGL'), 'es', 'Alta tasa de descenso (<-1000 fpm) por debajo de 1000 AGL'),

((SELECT id FROM issue_type WHERE code='AppHighVsBelow2000AGL'), 'en', 'High descent rate (<-2000 fpm) below 2000 AGL'),
((SELECT id FROM issue_type WHERE code='AppHighVsBelow2000AGL'), 'es', 'Alta tasa de descenso (<-2000 fpm) por debajo de 2000 AGL'),

((SELECT id FROM issue_type WHERE code='Refueling'), 'en', 'Refueling during flight'),
((SELECT id FROM issue_type WHERE code='Refueling'), 'es', 'Refueling durante el vuelo'),

((SELECT id FROM issue_type WHERE code='LandingAllEnginesStopped'), 'en', 'Landing with all engines off'),
((SELECT id FROM issue_type WHERE code='LandingAllEnginesStopped'), 'es', 'Aterrizaje con todos los motores apagados'),

((SELECT id FROM issue_type WHERE code='LandingSomeEngineStopped'), 'en', 'Landing with one engine off'),
((SELECT id FROM issue_type WHERE code='LandingSomeEngineStopped'), 'es', 'Aterrizaje con un motor apagado'),

((SELECT id FROM issue_type WHERE code='AirborneEngineStopped'), 'en', 'Engine shutdown in flight'),
((SELECT id FROM issue_type WHERE code='AirborneEngineStopped'), 'es', 'Apagado de motor en vuelo'),

((SELECT id FROM issue_type WHERE code='AirborneAllEnginesStopped'), 'en', 'All engines shut down in flight'),
((SELECT id FROM issue_type WHERE code='AirborneAllEnginesStopped'), 'es', 'Apagado de todos los motores en vuelo'),

((SELECT id FROM issue_type WHERE code='ZfwModified'), 'en', 'ZFW modified after startup'),
((SELECT id FROM issue_type WHERE code='ZfwModified'), 'es', 'ZFW modificado tras arrancar motores'),

((SELECT id FROM issue_type WHERE code='LandingAirportNotPlanned'), 'en', 'Landed at an airport not in flight plan'),
((SELECT id FROM issue_type WHERE code='LandingAirportNotPlanned'), 'es', 'Aterrizaje en un aeropuerto no incluido en el plan de vuelo'),

((SELECT id FROM issue_type WHERE code='LandingOutOfAirport'), 'en', 'Landing outside an airport'),
((SELECT id FROM issue_type WHERE code='LandingOutOfAirport'), 'es', 'Aterrizaje fuera de un aeropuerto'),

((SELECT id FROM issue_type WHERE code='LandingAirportAlternative'), 'en', 'Landed at alternative airport'),
((SELECT id FROM issue_type WHERE code='LandingAirportAlternative'), 'es', 'Aterrizaje en aeropuerto alternativo');