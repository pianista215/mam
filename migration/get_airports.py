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
query = ("select trim(ident), trim(name), latitude_deg, longitude_deg, trim(municipality), trim(iso_country) from airports where length(trim(ident))=4 AND length(trim(municipality))>0")

cursorvam.execute(query)

imported_airports = 0
	
for airport in cursorvam:
	cursormam.execute("INSERT INTO airport(icao_code, name, latitude, longitude, city, country_id) SELECT %s,%s,%s,%s,%s, id from country where iso2_code=%s", (airport[0], airport[1], airport[2], airport[3], airport[4], airport[5]))
	imported_airports  = imported_airports + 1

print("%d airports imported from %s into %s" % (imported_airports, args.fromdb, args.destinationdb))
cnxmam.commit()
cursorvam.close()
cursormam.close()
cnxvam.close()
cnxmam.close()
