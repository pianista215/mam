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
query = ("select plane_icao, plane_description, maximum_range, pax, cargo_capacity  from fleettypes where length(plane_icao)=4 AND maximum_range REGEXP '^[0-9]+$'")

cursorvam.execute(query)

imported_aircraft_types = 0
	
for aircraft_type in cursorvam:
	cursormam.execute("INSERT INTO aircraft_type(icao_type_code, name, max_nm_range, pax_capacity, cargo_capacity) VALUES (%s,%s,%s,%s,%s) ", (aircraft_type[0], aircraft_type[1], aircraft_type[2], aircraft_type[3], aircraft_type[4]))
	imported_aircraft_types  = imported_aircraft_types + 1

print("%d aircraft types imported from %s into %s" % (imported_aircraft_types, args.fromdb, args.destinationdb))
cnxmam.commit()
cursorvam.close()
cursormam.close()
cnxvam.close()
cnxmam.close()
