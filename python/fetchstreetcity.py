# Made by zzzkeil - https://github.com/zzzkeil
# Free to use, modify, share.
# MIT License .....
# Thanks go also to Gemini, ChatGPT, and some local LLM

import requests
import pymysql
import time

DB_CONFIG = {
    "host": "localhost",
    "user": "der_username",
    "password": "das_sichere_passwort",
    "database": "der_db_name",
    "charset": "utf8mb4",
    "cursorclass": pymysql.cursors.DictCursor
}

conn = pymysql.connect(**DB_CONFIG)
cursor = conn.cursor()

# Fetch only POIs where at least one of the required fields is missing
cursor.execute("""
    SELECT id, latitude, longitude, streetname, housenumber, postalcode, city
    FROM pois
    WHERE streetname IS NULL
       OR postalcode IS NULL
       OR city IS NULL
""")
pois = cursor.fetchall()
print(f"Found {len(pois)} POIs with missing fields.")

for idx, poi in enumerate(pois, start=1):
    poi_id = poi["id"]

    # ✅ Skip if required fields already exist (housenumber optional!)
    if all([poi["streetname"], poi["postalcode"], poi["city"]]):
        print(f"⏩ POI {poi_id} already complete (housenumber optional), skipping.")
        continue

    lat, lon = poi["latitude"], poi["longitude"]
    url = f"https://nominatim.openstreetmap.org/reverse?format=json&lat={lat}&lon={lon}&addressdetails=1"

    try:
        response = requests.get(
            url,
            headers={"User-Agent": "poi-updater/1.0 (your_email@example.com)"},
            timeout=10
        )
        data = response.json()

        address = data.get("address", {})
        street = address.get("road")
        house = address.get("house_number")  # optional
        postal = address.get("postcode")
        city   = address.get("city") or address.get("town") or address.get("village") or address.get("hamlet")

        if not any([street, postal, city]):
            print(f"⚠️ No usable address found for POI {poi_id}, skipping...")
            continue

        # Fill only missing fields (don’t overwrite)
        street = poi["streetname"] or street
        house  = poi["housenumber"] or house  # can stay NULL
        postal = poi["postalcode"] or postal
        city   = poi["city"] or city

        cursor.execute("""
            UPDATE pois
            SET streetname=%s, housenumber=%s, postalcode=%s, city=%s
            WHERE id=%s
        """, (street, house, postal, city, poi_id))

        if idx % 50 == 0:  # commit every 50 updates
            conn.commit()

        print(f"✅ Updated POI {poi_id}: {street or ''} {house or ''}, {postal or ''} {city or ''}")
        time.sleep(1)  # respect Nominatim usage policy

    except Exception as e:
        print(f"❌ Error updating POI {poi_id}: {e}")

conn.commit()
cursor.close()
conn.close()
print("All updates done.")
