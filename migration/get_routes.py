import mysql.connector
import argparse
import math

parser = argparse.ArgumentParser()
parser.add_argument("host", help="Host of the server")
parser.add_argument("user", help="User of the database")
#TODO: Enhance to protect password in shell
parser.add_argument("password", help="Password of the database")
parser.add_argument("fromdb", help="Vam database")
parser.add_argument("destinationdb", help="Mam database")

args = parser.parse_args()

# From Php https://gist.github.com/teachmeter/3014803
def distance_in_nm(lat1, lon1, lat2, lon2):
	theta = lon1 - lon2
	dist = math.sin(math.radians(lat1)) * math.sin(math.radians(lat2)) + math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) * math.cos(math.radians(theta))
	dist = math.acos(dist)
	dist = math.degrees(dist)
	miles = dist * 60 * 1.1515
	return miles * 0.8684

cnxvam = mysql.connector.connect(user=args.user, password=args.password, host=args.host, database=args.fromdb)
cnxmam = mysql.connector.connect(user=args.user, password=args.password, host=args.host, database=args.destinationdb)

cursorvam = cnxvam.cursor()
cursormam = cnxmam.cursor()
query = ("select trim(flight), departure, arrival, d.latitude_deg as dep_latitude, d.longitude_deg as dep_longitude, a.latitude_deg as arr_latitude, a.longitude_deg as arr_longitude from routes r left join airports d ON d.ident=r.departure left join airports a ON a.ident=r.arrival group by departure,arrival")

cursorvam.execute(query)

imported_routes = 0
	
for route in cursorvam:
	code = route[0]
	departure = route[1]
	arrival = route[2]
	print("Generating distance for %s - %s" % (departure, arrival))
	if departure != arrival:
		distance_nm = distance_in_nm(route[3], route[4], route[5], route[6])
	else:
		distance_nm = 0
	print("Inserting %s - %s = %s NM" % (departure, arrival, distance_nm) )
	cursormam.execute("INSERT INTO route(code, departure, arrival, distance_nm) VALUES (%s, %s, %s, %s)", (code, departure, arrival, distance_nm))
	imported_routes  = imported_routes + 1

print("%d routes imported from %s into %s" % (imported_routes, args.fromdb, args.destinationdb))
cnxmam.commit()
cursorvam.close()
cursormam.close()
cnxvam.close()
cnxmam.close()
