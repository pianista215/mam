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

#Common versions (ICAO 4 chars)
query = ("select plane_icao, plane_description, maximum_range, pax, cargo_capacity  from fleettypes where length(plane_icao)=4 AND maximum_range REGEXP '^[0-9]+$' ORDER BY plane_icao")

cursorvam.execute(query)

imported_aircraft_types = 0
	
for aircraft_type in cursorvam:
	cursormam.execute("INSERT INTO aircraft_type(icao_type_code, name, max_nm_range) VALUES (%s,%s,%s)", (aircraft_type[0], aircraft_type[1], aircraft_type[2]))
	cursormam.execute("INSERT INTO aircraft_configuration(aircraft_type_id, name, pax_capacity, cargo_capacity) select id,'Standard',%s,%s FROM aircraft_type WHERE icao_type_code=%s", (aircraft_type[3], aircraft_type[4], aircraft_type[0]))
	imported_aircraft_types  = imported_aircraft_types + 1

#Migrate cargo versions (ICAO 5 chars ending in F)
query = ("select plane_icao, plane_description, maximum_range, pax, cargo_capacity  from fleettypes where length(plane_icao)=5 AND maximum_range REGEXP '^[0-9]+$' AND RIGHT(plane_icao,1)='F' ORDER BY plane_icao")

cursorvam.execute(query)

for aircraft_type in cursorvam:
	real_icao = aircraft_type[0][0:4]
	cursormam.execute("select count(*) from aircraft_type where icao_type_code=%s", (real_icao,))
	if cursormam.fetchone()[0] == 0:
		cursormam.execute("INSERT INTO aircraft_type(icao_type_code, name, max_nm_range) VALUES (%s,%s,%s)", (real_icao, aircraft_type[1], aircraft_type[2]))

	cursormam.execute("INSERT INTO aircraft_configuration(aircraft_type_id, name, pax_capacity, cargo_capacity) select id,'Cargo',%s,%s FROM aircraft_type WHERE icao_type_code=%s", (aircraft_type[3], aircraft_type[4], real_icao))
	imported_aircraft_types  = imported_aircraft_types + 1

print("%d aircraft types imported from %s into %s" % (imported_aircraft_types, args.fromdb, args.destinationdb))
cnxmam.commit()
cursorvam.close()
cursormam.close()
cnxvam.close()
cnxmam.close()
