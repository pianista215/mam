# Changelog

## [1.3.0] - Upcoming

### Changed
- Pilot list: rank column now shows the rank image with the rank name as tooltip instead of plain text

### Added
- Generate `context.json` with flight context (departure/destination/alternatives/landing airports and runway data) for mam-analyzer
- New phase metrics: `TakeoffRunway` and `TakeoffRunwayRemainingPct` for the takeoff phase, `LandingRunway` and `LandingRunwayTouchdownPct` for the final landing phase

## [1.2.0] - 2026-02-06

### Added
- Runway and runway end support for airports (database tables, models, migration)
- Airport view: display runway ends table with designator and heading when runways exist
- Airport view: draw runway polygons on OpenLayers map (main surface, displaced thresholds, stopways, threshold markers)
- Flight view: draw departure and landing airport runways on the flight map
- Flight view: startup and shutdown phases now show a colored circle marker at their first position on the map
- Aircraft type ICAO code now accepts 2 to 4 characters instead of exactly 4
- Flight list: `creation_date` filter now supports partial searches (e.g., "2025-02" for all February flights)
- Flight list: Removed filter input for `status` column (sorting still available)
- Tour list: `start` and `end` filters now support partial searches
- Statistics: Rankings by flight count now use total flight hours as tiebreaker

### Fixed
- Tour view: Tables now scroll horizontally on mobile instead of breaking layout

## [1.1.0] - 2026-02-04

### Added
- New event attribute `VSLast3Avg` (Sampled VS) for stabilized vertical speed tracking
- New issue type `AppHighVsAvgBelow1000AGL` for high sampled descent rate (<-1150 fpm) below 1000 AGL

### Changed
- Updated `AppHighVsBelow1000AGL` threshold from -1000 fpm to -1500 fpm

## [1.0.0] - 2026-01-31

- Initial version
