import mysql.connector
import argparse
import math
import sys

parser = argparse.ArgumentParser(description="Migrate old VAM tours into MAM schema")
parser.add_argument("host", help="Host of the server")
parser.add_argument("user", help="User of the database")
#TODO: Enhance to protect password in shell
parser.add_argument("password", help="Password of the database")
parser.add_argument("fromdb", help="Source (Vam) database")
parser.add_argument("destinationdb", help="Destination (MAM) database")

args = parser.parse_args()

# From Php https://gist.github.com/teachmeter/3014803
def distance_in_nm(lat1, lon1, lat2, lon2):
	theta = lon1 - lon2
	dist = math.sin(math.radians(lat1)) * math.sin(math.radians(lat2)) + math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) * math.cos(math.radians(theta))
	dist = math.acos(min(1, max(-1, dist)))  # clamp to avoid rounding errors
	dist = math.degrees(dist)
	miles = dist * 60 * 1.1515
	return miles * 0.8684

cnxvam = mysql.connector.connect(user=args.user, password=args.password, host=args.host, database=args.fromdb)
cnxmam = mysql.connector.connect(user=args.user, password=args.password, host=args.host, database=args.destinationdb)

cursorvam = cnxvam.cursor(dictionary=True)
cursormam = cnxmam.cursor()

cursorvam.execute("SELECT * FROM tours ORDER BY tour_id")
tours = cursorvam.fetchall()

imported_tours = 0
imported_stages = 0
imported_completions = 0

for tour in tours:
	print(f"Importing tour {tour['tour_name']} (old ID: {tour['tour_id']})")

	name = tour['tour_name'] or f"Tour {tour['tour_id']}"
	description = (tour['tour_description'] or '')[:200]
	start = tour['start_date'] or None
	end = tour['end_date'] or None

	cursormam.execute(
		"INSERT INTO tour(name, description, start, end) VALUES (%s, %s, %s, %s)",
		(name, description, start, end)
	)
	new_tour_id = cursormam.lastrowid
	imported_tours += 1

	# --- Import stages ---
	cursorvam.execute("""
		SELECT l.*, 
		       d.latitude_deg AS dep_latitude, d.longitude_deg AS dep_longitude,
		       a.latitude_deg AS arr_latitude, a.longitude_deg AS arr_longitude
		FROM tour_legs l
		LEFT JOIN airports d ON d.ident = l.departure
		LEFT JOIN airports a ON a.ident = l.arrival
		WHERE l.tour_id = %s
		ORDER BY l.leg_number
	""", (tour['tour_id'],))

	legs = cursorvam.fetchall()

	for leg in legs:
		departure = leg['departure']
		arrival = leg['arrival']
		desc = (leg['comments'] or '')[:200]
		seq = leg['leg_number']

		# Ensure all coordinates exist
		if not all([leg['dep_latitude'], leg['dep_longitude'], leg['arr_latitude'], leg['arr_longitude']]):
			print(f"❌ Missing coordinates for leg {seq} ({departure} -> {arrival}) in tour {tour['tour_id']}")
			sys.exit(1)

		dist_nm = distance_in_nm(leg['dep_latitude'], leg['dep_longitude'], leg['arr_latitude'], leg['arr_longitude'])
		print(f"  Stage {seq}: {departure} → {arrival} = {int(dist_nm)} NM")

		try:
			cursormam.execute(
				"INSERT INTO tour_stage(tour_id, departure, arrival, distance_nm, description, sequence) VALUES (%s, %s, %s, %s, %s, %s)",
				(new_tour_id, departure, arrival, int(dist_nm), desc, seq)
			)
		except mysql.connector.IntegrityError as e:
			print(f"❌ Error inserting stage {seq} of tour {new_tour_id}: {e}")
			print(f"   → Departure: {departure}, Arrival: {arrival}")
			raise e
		imported_stages += 1

print("\n✅ Migration completed:")
print(f"- {imported_tours} tours imported")
print(f"- {imported_stages} stages imported")

cnxmam.commit()
cursorvam.close()
cursormam.close()
cnxvam.close()
cnxmam.close()
