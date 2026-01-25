# MAM

Modern Airlines Manager - A web application for virtual airline management with flight tracking, pilot management, tours, and CMS features. Integrates with [MAM ACARS](https://github.com/pianista215/mam-acars) for flight recording and [MAM Analyzer](https://github.com/pianista215/mam-analyzer) for automatic flight analysis.

## Related Projects

- [MAM ACARS](https://github.com/pianista215/mam-acars) - Flight recorder that captures black box data
- [MAM Analyzer](https://github.com/pianista215/mam-analyzer) - Analyzes flight data and generates reports

## What it does

- Manage virtual airline pilots, aircraft, routes, and tours
- Track flights submitted via MAM ACARS
- Process and visualize flight analysis reports (phases, metrics, issues)
- Role-based access control for different user levels
- CMS for pages and content management
- Internationalization support (English/Spanish)

## Requirements

- PHP 8.1.2+
- PHP extensions: curl, xml, gd, mysql, intl
- MariaDB/MySQL database
- Composer

## Installation

### Clone and install dependencies

```bash
git clone https://github.com/pianista215/mam.git
cd mam/basic
composer install --prefer-dist --no-progress
```

### Database setup

1. Create the database:

```sql
CREATE DATABASE mam DEFAULT CHARACTER SET utf8mb4;
```

2. Load the schema and seed data (in order):

```bash
mysql -u <user> -p mam < db-model/ddl.sql
mysql -u <user> -p mam < db-model/analysis.sql
mysql -u <user> -p mam < db-model/config.sql
mysql -u <user> -p mam < db-model/countries.sql
mysql -u <user> -p mam < db-model/issues.sql
mysql -u <user> -p mam < db-model/pages.sql
```

3. Configure the database connection in `basic/config/db.php`:

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=mam',
    'username' => 'your_user',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
];
```

4. Run migrations:

```bash
cd basic
php yii migrate/up --interactive=0
php yii migrate-rbac --interactive=0
php yii migrate-custom-rbac --interactive=0
```

### Storage directories

Create the required storage directories and set proper permissions for the web server user (e.g., `www-data`):

```bash
# Create directories
mkdir -p /opt/mam/chunks
mkdir -p /opt/mam/images
mkdir -p /opt/mam/acars-releases

# Set ownership for web server
chown -R www-data:www-data /opt/mam
chmod -R 755 /opt/mam
```

| Directory | Purpose |
|-----------|---------|
| `/opt/mam/chunks` | Temporary storage for ACARS flight data uploads |
| `/opt/mam/images` | Uploaded images (ranks, aircraft, pilots, etc.) |
| `/opt/mam/acars-releases` | MAM ACARS installer files for auto-update |

**ACARS releases:** Copy the Velopack output generated when building [MAM ACARS](https://github.com/pianista215/mam-acars) into the `acars-releases` directory. This enables the auto-update feature in the ACARS client.

The paths can be customized via the admin panel in Site Settings.

### First admin user

If you are not migrating from VAM, you need to create the first user and assign admin privileges manually.

**Option 1: Register via web and promote to admin**

1. Register a new pilot through the web interface
2. Assign a license via SQL (required to log in):

```sql
-- Get the pilot ID and assign license (replace with your email)
UPDATE pilot SET license = 'ADM001' WHERE email = 'your@email.com';
```

3. Assign admin role and `assignAdmin` permission via SQL:

```sql
-- Get the pilot ID
SELECT id FROM pilot WHERE email = 'your@email.com';

-- Assign admin role (replace <pilot_id> with the actual ID)
INSERT INTO auth_assignment (item_name, user_id, created_at)
VALUES ('admin', '<pilot_id>', UNIX_TIMESTAMP());

-- Grant permission to assign admin role to others
INSERT INTO auth_assignment (item_name, user_id, created_at)
VALUES ('assignAdmin', '<pilot_id>', UNIX_TIMESTAMP());
```

**Option 2: Create user directly in database**

1. Insert the pilot with a placeholder password and license:

```sql
INSERT INTO pilot (license, name, surname, email, city, country_id, password, date_of_birth, location)
VALUES ('ADM001', 'Admin', 'User', 'admin@yourairline.com', 'City', 1, 'placeholder', '1990-01-01', 'ICAO');
```

Note: Replace `country_id` with a valid ID from the `country` table, and `location` with a valid ICAO code from the `airport` table.

2. Use the "Forgot password" feature on the login page to set a real password.

3. Assign admin role and `assignAdmin` permission (see SQL above).

**For existing users (migrated from VAM):**

Run the same `INSERT INTO auth_assignment` statements to promote any pilot to admin.

### Web server configuration

Configure your web server to point to `basic/web` as the document root. For development:

```bash
cd basic
php yii serve
```

The application will be available at `http://localhost:8080`.

## Configuration

### Debug mode

Debug mode is **disabled by default** (production mode). To enable it for development, uncomment the following lines in `basic/web/index.php`:

```php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
```

When debug mode is enabled, you get:
- Detailed error pages with stack traces
- Debug toolbar at the bottom of the page
- Gii code generator at `/gii`

**Important:** Never enable debug mode in production.

### Email configuration (SMTP)

By default, emails are saved to files instead of being sent (development mode). To enable real email delivery in production, configure SMTP in `basic/config/web.php`, replacing the mailer component:

```php
'mailer' => [
    'class' => \yii\symfonymailer\Mailer::class,
    'viewPath' => '@app/mail',
    'useFileTransport' => false,
    'transport' => [
        'scheme' => 'smtps',
        'host' => 'smtp.example.com',
        'username' => 'your_username',
        'password' => 'your_password',
        'port' => 465,
    ],
],
```

The sender email addresses (no-reply, support) are configured via the admin panel (see Site Settings below).

### Site Settings (Admin panel)

Administrators can configure the site at `/admin/site-settings`. Available settings:

| Setting | Description |
|---------|-------------|
| **Airline name** | Name displayed throughout the site |
| **No-reply email** | Sender address for automated emails |
| **Support email** | Contact email for support |
| **Registration dates** | Start/end dates for pilot registration |
| **Registration start airport** | Default location for new pilots |
| **Social media URLs** | Links for X, Instagram, Facebook |
| **Storage paths** | Paths for chunks, images, and ACARS releases |
| **Token lifetime** | API token validity in hours |
| **Charter ratio** | Ratio for charter flight calculations |

These settings are stored in the database `config` table and can be modified without code changes.

## Migration from VAM

If you are migrating from Virtual Airlines Manager (VAM), use the Python scripts in the `migration/` directory. These scripts require the `mysql-connector-python` package.

```bash
pip install mysql-connector-python
```

Run the scripts in the following order:

```bash
# 1. Import airports
python migration/get_airports.py <host> <user> <password> <vam_db> <mam_db>

# 2. Import routes
python migration/get_routes.py <host> <user> <password> <vam_db> <mam_db>

# 3. Import aircraft types and configurations
python migration/get_aircraft_types.py <host> <user> <password> <vam_db> <mam_db>

# 4. Import aircraft fleet
python migration/get_aircrafts.py <host> <user> <password> <vam_db> <mam_db>

# 5. Import pilots
python migration/get_pilots.py <host> <user> <password> <vam_db> <mam_db>

# 6. Import tours (optional: add --include-completions for pilot tour completions)
python migration/get_tours.py <host> <user> <password> <vam_db> <mam_db> [--include-completions]
```

**Note:** Migrated pilots will have a dummy password and will need to use the password reset feature.

## Flight Processing (Cron)

MAM processes flights submitted via ACARS using a two-step pipeline:

1. **Assemble ACARS data** - Combines uploaded chunks into a report file
2. **Analyze with MAM Analyzer** - Processes the report to detect phases and issues
3. **Import analysis** - Updates the flight record with analysis results

### Setup

1. Install [MAM Analyzer](https://github.com/pianista215/mam-analyzer) following the instructions in the [project README](https://github.com/pianista215/mam-analyzer/blob/main/README.md).

2. Configure the cron script `cron/report_analysis.sh`. Edit the paths if needed:

```bash
YII_BIN="/path/to/mam/basic/yii"
MAM_ANALYZER_HOME="/path/to/mam-analyzer"
```

3. Add a cron job (e.g., every 5 minutes):

```bash
crontab -e
```

```cron
*/5 * * * * /path/to/mam/cron/report_analysis.sh >> /var/log/mam-report-analysis.log 2>&1
```

The script performs these steps:
- Calls `php yii flight-report/assemble-pending-acars` to prepare report files
- Runs MAM Analyzer on each report to generate `analysis.json`
- Calls `php yii flight-report/import-pending-reports-analysis` to import results

## Running tests

```bash
cd basic

# Run all tests
vendor/bin/codecept run

# Run specific test suite
vendor/bin/codecept run unit
vendor/bin/codecept run functional

# Run single test file
vendor/bin/codecept run unit path/to/TestCest.php

# Run with coverage
XDEBUG_MODE=coverage vendor/bin/codecept run --coverage --coverage-xml --coverage-html
```

## Directory structure

```
basic/                    # Main Yii2 application
├── controllers/          # Web controllers
├── models/               # ActiveRecord models
├── views/                # PHP view templates
├── rbac/                 # Role-Based Access Control
│   ├── constants/        # Roles.php, Permissions.php
│   ├── rules/            # Authorization rules
│   └── migrations/       # RBAC-specific migrations
├── tests/                # Codeception tests (unit/, functional/, api/)
├── config/               # web.php, console.php, db.php
├── messages/             # i18n translations (en/, es/)
└── commands/             # Console commands

cron/                     # Cron scripts for flight processing
db-model/                 # Database schema SQL files
migration/                # VAM to MAM migration scripts
```

## License

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)**.

This means:
- You can use, modify, and distribute this software
- Any derivative work must also be licensed under AGPL-3.0
- If you run a modified version as a network service, you must make the source code available to users
- See [LICENSE](LICENSE) for the full text

Copyright (c) 2026 Unai Sarasola Álvarez
