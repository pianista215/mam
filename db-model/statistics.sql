-- Statistics Seed Data

INSERT INTO `statistic_period_type` (`code`) VALUES
('monthly'),
('yearly'),
('all_time');

INSERT INTO `statistic_aggregate_type` (`code`) VALUES
('total_flights'),
('total_flight_hours');

INSERT INTO `statistic_aggregate_type_lang` (`aggregate_type_id`, `language`, `name`, `description`) VALUES
((SELECT id FROM statistic_aggregate_type WHERE code = 'total_flights'), 'en', 'Total Flights', 'Total number of completed flights'),
((SELECT id FROM statistic_aggregate_type WHERE code = 'total_flights'), 'es', 'Vuelos Totales', 'Número total de vuelos completados'),
((SELECT id FROM statistic_aggregate_type WHERE code = 'total_flight_hours'), 'en', 'Total Flight Hours', 'Total hours flown'),
((SELECT id FROM statistic_aggregate_type WHERE code = 'total_flight_hours'), 'es', 'Horas de Vuelo Totales', 'Horas totales de vuelo');

INSERT INTO `statistic_ranking_type` (`code`, `entity_type`, `max_positions`, `sort_order`) VALUES
('top_pilots_by_hours', 'pilot', 5, 'DESC'),
('top_pilots_by_flights', 'pilot', 5, 'DESC'),
('top_aircraft_types_by_flights', 'aircraft_type', 3, 'DESC'),
('smoothest_landings', 'flight', 3, 'ASC');

INSERT INTO `statistic_ranking_type_lang` (`ranking_type_id`, `language`, `name`, `description`) VALUES
((SELECT id FROM statistic_ranking_type WHERE code = 'top_pilots_by_hours'), 'en', 'Top Pilots by Hours', 'Pilots with most flight hours'),
((SELECT id FROM statistic_ranking_type WHERE code = 'top_pilots_by_hours'), 'es', 'Top Pilotos por Horas', 'Pilotos con más horas de vuelo'),
((SELECT id FROM statistic_ranking_type WHERE code = 'top_pilots_by_flights'), 'en', 'Top Pilots by Flights', 'Pilots with most flights'),
((SELECT id FROM statistic_ranking_type WHERE code = 'top_pilots_by_flights'), 'es', 'Top Pilotos por Vuelos', 'Pilotos con más vuelos'),
((SELECT id FROM statistic_ranking_type WHERE code = 'top_aircraft_types_by_flights'), 'en', 'Top Aircraft Types by Flights', 'Most used aircraft types'),
((SELECT id FROM statistic_ranking_type WHERE code = 'top_aircraft_types_by_flights'), 'es', 'Top Tipos de Aeronave por Vuelos', 'Tipos de aeronave más utilizados'),
((SELECT id FROM statistic_ranking_type WHERE code = 'smoothest_landings'), 'en', 'Smoothest Landings', 'Flights with lowest landing rate'),
((SELECT id FROM statistic_ranking_type WHERE code = 'smoothest_landings'), 'es', 'Aterrizajes más Suaves', 'Vuelos con menor ratio de aterrizaje');

INSERT INTO `statistic_record_type` (`code`, `entity_type`, `comparison`, `unit`) VALUES
('longest_flight_time', 'flight', 'MAX', 'minutes'),
('longest_flight_distance', 'flight', 'MAX', 'nm');

INSERT INTO `statistic_record_type_lang` (`record_type_id`, `language`, `name`, `description`) VALUES
((SELECT id FROM statistic_record_type WHERE code = 'longest_flight_time'), 'en', 'Longest Flight Time', 'Flight with longest duration'),
((SELECT id FROM statistic_record_type WHERE code = 'longest_flight_time'), 'es', 'Vuelo más Largo', 'Vuelo con mayor duración'),
((SELECT id FROM statistic_record_type WHERE code = 'longest_flight_distance'), 'en', 'Longest Flight Distance', 'Flight with longest distance'),
((SELECT id FROM statistic_record_type WHERE code = 'longest_flight_distance'), 'es', 'Mayor Distancia de Vuelo', 'Vuelo con mayor distancia recorrida');
