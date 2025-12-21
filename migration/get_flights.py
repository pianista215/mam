import mysql.connector
from mysql.connector import errors
import argparse
from datetime import datetime
import math
import sys

REPORT_TOOL_IMPORTER = "MamImporter 1.0"

def parse_airport(raw):
	if not raw:
		return None
	value = raw.strip().upper()

	if len(value) != 4:
		return None

	return value

def parse_cruise_speed(raw):
    if not raw:
        return None, None

    s = str(raw).strip().upper()
    if not s:
        return None, None

    unit = s[0]
    if unit not in ("N", "M", "K"):
        return None, None

    value = s[1:].replace(".", "")

    if not value.isdigit() or len(value) > 4:
        return None, None

    return unit, int(value)

def parse_flight_level(raw):
    if not raw:
        return None, None

    s = str(raw).strip().upper().replace("FL", "F")
    if not s:
        return None, None

    # Special case VFR
    if "VFR" in s:
        return "VFR", ""

    unit = s[0]
    if unit not in ("F", "A", "S", "M"):
        return None, None

    value = s[1:]
    if not value.isdigit() or len(value) > 4:
        return None, None

    return unit, int(value)

def parse_network(raw):
    if not raw:
        return "UNKOWN"

    s = str(raw).strip().upper()

    if "IVAO" in s:
        return "IVAO"
    if "VATSIM" in s:
        return "VATSIM"

    return "UNKOWN"   

def parse_flight_type(raw):
    if not raw:
        return None

    s = str(raw).strip().upper()

    if "IFR" in s:
        return "I"
    if "VFR" in s:
        return "V"

    return None

def parse_charter(raw):
    if raw == 1:
        return 'C'
    if raw == 0:
        return 'R'
    return None


     

parser = argparse.ArgumentParser(description="Migrate old VAM flights into MAM schema")
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
	SELECT g.callsign as pilot_callsign, v.callsign as flight_callsign, 
	departure, arrival, alt1, alt2, 
	cruise_speed, flight_level, route, 
	eet, remarks, endurance, flight_date, network, flight_type,
	validator_comments, charter, aircraft_registry
	FROM vampireps v LEFT JOIN gvausers g ON v.gvauser_id=g.gvauser_id""")
flights = cursorvam.fetchall()

imported_flights = 0
omitted_flights = 0

for flight in flights:
	print(f"Importing flight {flight['pilot_callsign']} - {flight['departure']} - {flight['arrival']}")
	pilot = flight['pilot_callsign']	
	code = flight['flight_callsign']
	departure = parse_airport(flight.get('departure'))
	arrival = parse_airport(flight.get('arrival'))
	alt1 = parse_airport(flight.get('alt1'))
	alt2 = parse_airport(flight.get('alt2'))
	cruise_speed_unit, cruise_speed_value = parse_cruise_speed(flight.get("cruise_speed"))
	flight_level_unit, flight_level_value = parse_flight_level(flight.get("flight_level"))
	route = flight['route']
	eet = flight['eet']
	remarks = flight['remarks']
	endurance = flight['endurance']
	flight_date = flight['flight_date']
	network = parse_network(flight.get("network"))
	flight_rules = parse_flight_type(flight.get("flight_type"))
	validator_comments = flight['validator_comments']
	flight_type = parse_charter(flight.get('charter'))
	aircraft_registry = flight['aircraft_registry']

	if pilot is None:
		omitted_flights += 1		
		print(f"Omitting flight {flight['pilot_callsign']} - {flight['departure']} - {flight['arrival']} pilot is None")
	if departure is None or arrival is None or alt1 is None:
		omitted_flights += 1		
		print(f"Omitting flight {flight['pilot_callsign']} - {flight['departure']} - {flight['arrival']} invalid airports")		
	elif cruise_speed_unit is None:
		omitted_flights += 1		
		print(f"Omitting flight {flight['pilot_callsign']} - {flight['departure']} - {flight['arrival']} due bad speed")
	elif flight_level_unit is None:
		omitted_flights += 1				
		print(f"Omitting flight {flight['pilot_callsign']} - {flight['departure']} - {flight['arrival']} due bad flight level")
	elif flight_rules is None:
		omitted_flights += 1				
		print(f"Omitting flight {flight['pilot_callsign']} - {flight['departure']} - {flight['arrival']} due bad flight rules")		
	elif flight_type is None:
		omitted_flights += 1				
		print(f"Omitting flight {flight['pilot_callsign']} - {flight['departure']} - {flight['arrival']} due bad flight_type")				
	else:
		try:
			cursormam.execute(
				"""
				INSERT INTO flight (
					pilot_id, aircraft_id, 
					code, departure, arrival, alternative1_icao, alternative2_icao, 
					cruise_speed_value,	cruise_speed_unit, flight_level_value, flight_level_unit,
					route, estimated_time, other_information, endurance_time,
					report_tool, status, creation_date, network, flight_rules,
					validator_comments, flight_type
				)
				SELECT
				    p.id AS pilot_id, a.id AS aircraft_id,
				    %s, %s, %s, %s, %s,
				    %s, %s, 
				    %s, %s, 
				    %s, %s, %s, %s, 
				    %s, %s, %s, %s, %s,
				    %s, %s
				FROM pilot p
				JOIN aircraft a
				    ON a.registration = %s
				WHERE p.license = %s
				""",
				(
					code, departure, arrival, alt1, alt2, 
					cruise_speed_value, cruise_speed_unit,
					flight_level_value, flight_level_unit,
					route, eet, remarks, endurance,
					REPORT_TOOL_IMPORTER, "F", flight_date, network, flight_rules,
					validator_comments, flight_type,
					aircraft_registry, pilot
				)
			)
		except errors.IntegrityError as e:
			if e.errno == 1452:
				print(f"⚠️ Flight {flight['pilot_callsign']} - {flight['departure']} -> {flight['arrival']} omitted (FK missing)")
				omitted_flights += 1
			else:
				raise
		else:
			if cursormam.rowcount == 0:
				print(f"Flight {flight['pilot_callsign']} - {flight['departure']} - {flight['arrival']} was not inserted")							
				omitted_flights += 1
			else:
				new_flight_id = cursormam.lastrowid
				# TODO: REPORT
				imported_flights += 1

print("\n✅ Migration completed:")
print(f"- {imported_flights} flights imported")
print(f"- {omitted_flights} flights omitted")

cnxmam.commit()
cursorvam.close()
cursormam.close()
cnxvam.close()
cnxmam.close()
