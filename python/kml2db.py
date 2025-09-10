# Made by zzzkeil - https://github.com/zzzkeil
# Free to use, modify, share.
# MIT License .....
# Thanks go also to Gemini, ChatGPT, and some local LLM

from lxml import etree
import re
import pymysql
from datetime import datetime

# --- 1. Config ---
kml_file = "doc.kml"

DB_CONFIG = {
    "host": "localhost",
    "user": "der_username",
    "password": "das_sichere_passwort",
    "database": "der_db_name",
    "charset": "utf8mb4",
    "cursorclass": pymysql.cursors.DictCursor
}

ns = {"kml": "http://www.opengis.net/kml/2.2"}

# --- 2. Parse KML ---
tree = etree.parse(kml_file)
root = tree.getroot()

# --- 2a. Remove Placemark if kW < 150 ---
kw_pattern = re.compile(r"(\d+(?:\.\d+)?)\s*kW", re.IGNORECASE)

for pm in root.findall(".//kml:Placemark", namespaces=ns):
    name_el = pm.find("kml:name", namespaces=ns)
    if name_el is not None and name_el.text:
        match = kw_pattern.search(name_el.text)
        if match:
            value = float(match.group(1))
            if value < 150:
                parent = pm.getparent()
                parent.remove(pm)

# --- 2b. Clean names ---
remove_chars = r"[()\*\[\]≥≳,]"
remove_kw_num = r"\b\d+(\.\d+)?\s*kW\b"
remove_ct_num = r"\b\d+(?:/\d+)?(\.\d+)?\s*ct/kWh\b"
remove_rp_num = r"\b\d+(?:/\d+)?(\.\d+)?\s*rp/kWh\b"
remove_kw_unit = r"\bkW\b"
remove_ct_unit = r"\bct/kWh\b"
remove_rp_unit = r"\brp/kWh\b"

for name_el in root.findall(".//kml:name", namespaces=ns):
    if name_el.text:
        text = name_el.text
        text = re.sub(remove_chars, "", text)
        text = re.sub(remove_kw_num, "", text, flags=re.IGNORECASE)
        text = re.sub(remove_ct_num, "", text, flags=re.IGNORECASE)
        text = re.sub(remove_rp_num, "", text, flags=re.IGNORECASE)
        text = re.sub(remove_kw_unit, "", text, flags=re.IGNORECASE)
        text = re.sub(remove_ct_unit, "", text, flags=re.IGNORECASE)
        text = re.sub(remove_rp_unit, "", text, flags=re.IGNORECASE)
        text = re.sub(r"\s+", " ", text)  # collapse spaces
        name_el.text = text.strip()

placemarks = root.findall(".//kml:Placemark", namespaces=ns)

# --- 3. Connect to MariaDB ---
conn = pymysql.connect(**DB_CONFIG)
cursor = conn.cursor()

cursor.execute("""
CREATE TABLE IF NOT EXISTS pois (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    longitude DECIMAL(10,8) NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    streetname VARCHAR(255),
    housenumber VARCHAR(20),
    postalcode VARCHAR(20),
    city VARCHAR(255),
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_coords (longitude, latitude)
)
""")

# --- 4. Insert data ---
for pm in placemarks:
    name = pm.find("kml:name", ns)
    name = name.text if name is not None else None

    coords = pm.find(".//kml:coordinates", ns)
    coords = coords.text if coords is not None else None

    if coords:
        try:
            lon, lat, *_ = coords.strip().split(",")
            lon = float(lon)
            lat = float(lat)

            cursor.execute("""
                INSERT INTO pois (name, longitude, latitude, imported_at)
                VALUES (%s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    imported_at = VALUES(imported_at)
            """, (name, lon, lat, datetime.now()))
        except Exception as e:
            print(f"Skipping invalid coords: {coords} ({e})")

conn.commit()
cursor.close()
conn.close()
print("KML data cleaned and inserted into MariaDB successfully.")
