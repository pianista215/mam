# Changelog

## [1.12.0] - upcoming

### Added
- Aircraft configuration: new `crew` field (minimum crew count, integer ≥ 1), `mtow` field (Maximum Takeoff Weight in kg, integer ≥ 1) and `oew` field (Basic Empty Weight in kg, integer ≥ 1) on every configuration
- Fuel regression: nightly job (`fuel-regression/calculate`) computes per-configuration linear regression (fuel = a + b·distance) from completed historical flights, with hard-floor and 2σ outlier filtering. `FuelEstimator` helper (`basic/helpers/FuelEstimator.php`) provides fuel breakdowns (trip, alternate, contingency, reserve) for flight generation, returning `null` when insufficient data (< 5 valid flights) so the generator falls back to a static safe payload allocation. Regression fields in `aircraft_configuration` are write-protected against mass-assignment via `SCENARIO_ADMIN_FORM`
- Passenger and cargo load generation on FPL submission: `PayloadEstimator` helper computes a randomised but realistic load (adults, children, checked bags, paid cargo) using MTOW/OEW/fuel estimate and configurable weight constants; occupancy varies by day of week (higher on Mon/Fri/Sat/Sun). Results are stored on `submitted_flight_plan` and copied to `flight` when ACARS submits
- Aviation-style load sheet displayed in the FPL view and flight view: sectioned table (Crew / Passengers / Hold+Cargo) with unit weights, subtotals per section, and a highlighted Payload row showing total weight and POB; hidden on flights created before this feature (legacy flights without payload data show no load sheet)
- "People on board" field now shows the actual count (pax + crew) instead of 'X' in both the FPL and flight views
- Payload regenerated automatically when the alternate airport is changed during FPL update
- Admin settings → new "Pesos de Carga" section: configurable adult weight (default 84 Kg), child weight (default 35 Kg), and checked baggage weight (default 13 Kg)

### Changed
- Test suite performance: replaced dynamic `generatePasswordHash()` calls in fixture and unit test files with pre-computed static bcrypt hashes, and removed redundant `clearDatabase()` calls in unit tests (Codeception's transaction rollback already handles cleanup). Functional tests went from ~1 hour to ~56 seconds.
- Test: `FlightReportSubmissionCest` — `testValidWithRequest` now asserts that `pax_adults`, `pax_children`, `cargo_bags` and `cargo_paid_kg` are copied from the submitted FPL to the created flight; fixture entry id=1 updated with representative load data to enable the assertion. New test `testValidFlightReportSubmissionTourFlight` covers tour-type submissions, verifying `flight_type='T'`, `tour_stage_id` and pax/cargo propagation
- Test: `SubmittedFlightPlanUpdateCest.updateAlternateToCloserDoesNotRegeneratePayload` — setup now seeds `cargo_bags` and `cargo_paid_kg` alongside pax to reflect the real invariant (all four fields are populated together); assertions extended to include cargo fields
- Test: `PilotIndexViewCest` — expected flight count for pilot 5 updated to 7 (flight id=108 was added to the fixture for statistics testing)

### Fixed
- Console `flight-report/assemble-pending-acars` and `flight-report/import-pending-reports-analysis` — new unit test suite (`FlightReportControllerTest`, 15 tests) covering: gzip assembly and decompression, context.json generation, full happy-path import (phases, metrics, issues, events, pilot/aircraft hours, flight status), transaction rollback on unknown phase/issue/metric, null-issue timestamp, empty-array and null metric skipping, pipe-separated issue values, and comma-to-dot coordinate conversion
- Aircraft configuration index: aircraft type column is now sortable; default order is aircraft type ascending then name ascending

## [1.11.1] - 2026-05-12

### Fixed
- Aircraft index search by name no longer throws an integrity constraint violation when aircraft type and configuration tables are joined (ambiguous `name` column now qualified as `aircraft.name`)
- Renewing a license no longer sets an expiry date on descendant ratings that had none (null expiry is now preserved in cascade)
- Renewing a credential with the "Does not expire" checkbox now correctly removes the expiry date instead of rejecting the form with a validation error

## [1.11.0] - 2026-05-04

### Added
- Aircraft type resource management: upload, download and delete documentation/configuration files (PDF, images, compressed archives) per aircraft type
- New role `aircraftTypeResourceManager` with permissions to upload and delete aircraft type resources
- File access gated by RBAC rule: only pilots holding the credentials to fly the aircraft type can view and download its resources
- Admin settings: configurable storage path and maximum total size for aircraft type resources

## [1.10.0] - 2026-04-27

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
- FPL aircraft selection filtered by pilot credentials: aircraft types requiring credentials are hidden from the select-aircraft step; aircraft restricted to specific airports by certification are also hidden when the pilot lacks the required credential
- Credential bypass prevention: all three FPL preparation endpoints (route, tour, charter) validate server-side that the pilot holds the required credentials for the chosen aircraft type and destination airport, returning 403 if not

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
