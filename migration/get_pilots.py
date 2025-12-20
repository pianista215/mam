import mysql.connector
import argparse
from datetime import datetime
import math
import sys

parser = argparse.ArgumentParser(description="Migrate old VAM pilots into MAM schema")
parser.add_argument("host", help="Host of the server")
parser.add_argument("user", help="User of the database")
#TODO: Enhance to protect password in shell
parser.add_argument("password", help="Password of the database")
parser.add_argument("fromdb", help="Source (Vam) database")
parser.add_argument("destinationdb", help="Destination (MAM) database")

args = parser.parse_args()

cnxvam = mysql.connector.connect(user=args.user, password=args.password, host=args.host, database=args.fromdb)
cnxmam = mysql.connector.connect(user=args.user, password=args.password, host=args.host, database=args.destinationdb)

cursorvam = cnxvam.cursor(dictionary=True)
cursormam = cnxmam.cursor()

#Country, location, password and rank are not migrated
cursorvam.execute("""
	SELECT callsign, name, surname, email, register_date, city, birth_date, vatsimid, ivaovid,
	COALESCE(v.gva_hours, 0) AS gva_hours,
	transfered_hours
	FROM gvausers g
	LEFT JOIN (
    SELECT
        pilot,
        SUM(time) AS gva_hours
    FROM v_pilot_roster_rejected
    GROUP BY pilot
) v ON v.pilot = g.gvauser_id ORDER BY callsign
""")
pilots = cursorvam.fetchall()

imported_pilots = 0
DUMMY_PASSWORD = "$2b$12$k3Jp0Z7xE6dQ9ZpL2xN4WON4mC3Nw5Zb0hF3X9V6c5H8pYyWvU8a"

for pilot in pilots:
	print(f"Importing pilot {pilot['callsign']}")
	callsign = pilot['callsign']	
	name = pilot['name']
	surname = pilot['surname']
	email = pilot['email']
	register_date = pilot['register_date']
	city = pilot['city']
	birth_date = datetime.strptime(pilot['birth_date'], "%d/%m/%Y").date()
	vatsimid = pilot['vatsimid']
	ivaovid = pilot['ivaovid']
	hours = pilot['transfered_hours'] + pilot['gva_hours']

	cursormam.execute(
		"INSERT INTO pilot(country_id, location, password, license, name, surname, email, registration_date, city, date_of_birth, vatsim_id, ivao_id, hours_flown) VALUES (1, 'LEVD', %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		(DUMMY_PASSWORD, callsign, name, surname, email, register_date, city, birth_date, vatsimid, ivaovid, hours)
	)
	new_pilot_id = cursormam.lastrowid
	imported_pilots += 1

print("\n✅ Migration completed:")
print(f"- {imported_pilots} pilots imported")

cnxmam.commit()
cursorvam.close()
cursormam.close()
cnxvam.close()
cnxmam.close()
