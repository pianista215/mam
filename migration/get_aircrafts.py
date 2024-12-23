import mysql.connector
import argparse

parser = argparse.ArgumentParser()
parser.add_argument("host", help="Host of the server")
parser.add_argument("user", help="User of the database")
#TODO: Enhance to protect password in shell
parser.add_argument("password", help="Password of the database")
parser.add_argument("fromdb", help="Vam database")
parser.add_argument("destinationdb", help="Mam database")

args = parser.parse_args()

cnxvam = mysql.connector.connect(user=args.user, password=args.password, host=args.host, database=args.fromdb)
cnxmam = mysql.connector.connect(user=args.user, password=args.password, host=args.host, database=args.destinationdb)

cursorvam = cnxvam.cursor()
cursormam = cnxmam.cursor()

# Retrieve the current aircraft types from the MAM, and get the registered aircrafts in the VAM to import in the database
query = ("select icao_type_code from aircraft_type ORDER BY icao_type_code")
cursormam.execute(query)

existing_types = []
for aircraft_type in cursormam:
	existing_types.append(aircraft_type[0])

for aircraft_type in existing_types:
	imported_aircrafts = 0
	# TODO: Add location when the airport data is more consistent (there are a lot of airports not available if we ensure that the icao_code exists and is 4 letter)
	cursorvam.execute("select registry, f.name, hours from fleets f LEFT JOIN fleettypes ft ON ft.fleettype_id=f.fleettype_id WHERE ft.plane_icao = %s ORDER BY registry", (aircraft_type, ))

	for aircraft in cursorvam:
		cursormam.execute("INSERT INTO aircraft(aircraft_configuration_id, registration, name, location, hours_flown)  select ac.id,%s,%s,'LEVD',%s FROM aircraft_configuration ac LEFT JOIN aircraft_type at ON at.id=ac.aircraft_type_id WHERE at.icao_type_code=%s AND ac.name = 'Standard'", (aircraft[0], aircraft[1], aircraft[2], aircraft_type))
		imported_aircrafts = imported_aircrafts + 1

	print("%d standard aircrafts of type %s imported from %s into %s" % (imported_aircrafts, aircraft_type, args.fromdb, args.destinationdb))
	imported_aircrafts = 0

	# Check for cargo aircrafts (same icao ending on F)
	cursorvam.execute("select registry, f.name, hours from fleets f LEFT JOIN fleettypes ft ON ft.fleettype_id=f.fleettype_id WHERE ft.plane_icao = CONCAT(%s,'F') ORDER BY registry", (aircraft_type, ))

	for aircraft in cursorvam:
		cursormam.execute("INSERT INTO aircraft(aircraft_configuration_id, registration, name, location, hours_flown)  select ac.id,%s,%s,'LEVD',%s FROM aircraft_configuration ac LEFT JOIN aircraft_type at ON at.id=ac.aircraft_type_id WHERE at.icao_type_code=%s AND ac.name = 'Cargo'", (aircraft[0], aircraft[1], aircraft[2], aircraft_type))
		imported_aircrafts = imported_aircrafts + 1

	print("%d cargo aircrafts of type %s imported from %s into %s" % (imported_aircrafts, aircraft_type, args.fromdb, args.destinationdb))


cnxmam.commit()
cursorvam.close()
cursormam.close()
cnxvam.close()
cnxmam.close()
