-- Related with registration
INSERT INTO config(`key`, `value`) VALUES ('registration_start', '2024-12-10');
INSERT INTO config(`key`, `value`) VALUES ('registration_end', '2025-12-10');
INSERT INTO config(`key`, `value`) VALUES ('registration_start_location', 'LEVD');

-- Settings
INSERT INTO config(`key`, `value`) VALUES ('chunks_storage_path', '/opt/mam/chunks');
INSERT INTO config(`key`, `value`) VALUES ('images_storage_path', '/opt/mam/images');
INSERT INTO config(`key`, `value`) VALUES ('acars_releases_path', '/opt/mam/acars-releases');
INSERT INTO config(`key`, `value`) VALUES ('acars_installer_name', 'Setup.exe');
INSERT INTO config(`key`, `value`) VALUES ('token_life_h', '24');
INSERT INTO config(`key`, `value`) VALUES ('charter_ratio', '0.1');

-- Global config
INSERT INTO config(`key`, `value`) VALUES ('airline_name', 'MamAirlines');
INSERT INTO config(`key`, `value`) VALUES ('no_reply_mail', 'no-reply@mamairlines.com');
INSERT INTO config(`key`, `value`) VALUES ('support_mail', 'support@mamairlines.com');

-- Footer
INSERT INTO config(`key`, `value`) VALUES ('x_url', 'https://x.com/');
INSERT INTO config(`key`, `value`) VALUES ('instagram_url', 'https://instagram.com/');
INSERT INTO config(`key`, `value`) VALUES ('facebook_url', 'https://facebook.com/');