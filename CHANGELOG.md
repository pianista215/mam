# Changelog

## [1.10.0] - 2026-04-21

### Added
- Credential type catalogue: create, view, update and delete credential types (licenses, ratings, certifications) with a prerequisite DAG and aircraft-type/airport-restriction mappings
- Pilot credentials: issue, activate (student → active), renew, and revoke credentials per pilot, with full access-control enforcement (ISSUE_CREDENTIAL permission)
- Credential cascade on renew: renewing a license auto-renews all active descendant rating credentials to the same expiry date
- Credential cascade on revoke: revoking a license deletes all descendant credentials the pilot holds; revoking a license restores the revoked credential's expiry date to ancestor licenses
- Ancestor expiry clearing: issuing or activating a higher-level license sets the expiry date of all lower ancestor licenses to null, so only the highest license carries an active expiry
- Student-only issue enforcement: if a prerequisite is held only as Student, the new credential can only be issued as Student; the form disables the Active option dynamically and the server validates the rule
- Renew blocked by higher license: a license can only be renewed if the pilot holds no higher (descendant) license; the Renew button is hidden and the endpoint returns 403 otherwise
- Credential status badges (Active, Expired, Student) in pilot view and credential-type view
- "Does not expire" checkbox in the issue/renew/activate forms with correct default state
- RBAC roles CREDENTIAL_MANAGER and CREDENTIAL_AUTHORITY with corresponding permissions

## [1.9.0] - 2026-04-06

### Added
- Issues `AppHighVsBelow500AGL` and `AppHighVsAvgBelow500AGL`: detect high descent rate below 500 AGL, mirroring the existing 1000 AGL variants with dynamic `{limit}` placeholder support

## [1.8.0] - 2026-03-24

### Changed
- Issues `AppHighVsBelow1000AGL`, `AppHighVsAvgBelow1000AGL` and `AppHighVsBelow2000AGL`: descriptions now use a dynamic limit placeholder. When the flight analysis sends a third parameter in the issue value (e.g. `-1900|1500|-1800`), that value replaces the default threshold in the displayed message

### Fixed
- Issues `AppHighVsBelow1000AGL` and `AppHighVsAvgBelow1000AGL`: the `{limit}` placeholder was applied to the database but no migration existed for it, causing other environments to display the placeholder literally instead of the threshold value

## [1.7.0] - 2026-03-21

### Added
- Runway end: new optional `glideslope_deg` field to store the glideslope angle (in degrees) for each runway end

## [1.6.0] - 2026-03-20

### Added
- Navdata support: new database tables `nav_point`, `navaid`, and `airway_segment` with migrations and ActiveRecord models (`NavPoint`, `Navaid`, `AirwaySegment`)
- Flight map: nav points (VOR, NDB, DME, ILS-LOC, LOC, FIX) near the flight route are displayed with ICAO-inspired SVG symbols, identifier labels, and frequencies
- Flight map: airway segments between nav points within 10 km of the flight route are drawn as thin lines with airway identifiers
- Flight map: checkboxes to toggle visibility of each nav point type (VOR, NDB, DME, ILS-LOC/LOC, FIX) and airways independently
- Flight map: VFR/IFR base map toggle — switches between OpenStreetMap and ESRI Light Gray Canvas for better navaid readability
- Airport view: navaid table listing type, identifier, frequency, and associated runway for each navaid at the airport
- Airport view: nav points for the airport are displayed on the map with the same SVG symbols
- Shared `NavaidMapAsset` (Yii2 AssetBundle) with `navaid-map.js` containing reusable SVG symbols and `makeNavStyle()` to avoid code duplication between flight and airport maps
- Nav point proximity filtering: two-stage filter (SQL bounding box pre-filter + PHP point-to-segment distance ≤ 10 km) to avoid loading irrelevant navaids for long or curved routes

## [1.5.1] - 2026-03-18

### Fixed
- Pilot profile: tour badges now display ordered by completion date

## [1.5.0] - 2026-03-18

### Added
- Tour badges: each tour now has a 150×150px badge (medal image) visible on the tour page and on pilot profile pages to showcase completed tours
- Navigation: logged-in pilots now have a user dropdown in the header (showing their license) with quick access to their profile and logout

## [1.4.0] - 2026-03-17

### Changed
- TaxiOverspeed issue description updated from >25 knots to >30 knots

## [1.3.1] - 2026-03-02

### Fixed
- Statistics: Daily cron on the 1st of the month now recalculates all open periods before closing the previous month, preventing stale statistics when flights are finalised after the last daily run

## [1.3.0] - 2026-02-20

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
