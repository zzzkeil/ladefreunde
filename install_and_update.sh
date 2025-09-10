#!/bin/bash
# Made by zzzkeil - https://github.com/zzzkeil
# Free to use, modify, share.
# MIT License .....
# Thanks go also to Gemini, ChatGPT, and some local LLM

echo "#####################################################################"
echo "# Danke an Gerd Bremer für deine arbeit an deiner Ad Hoc Map"
echo "#####################################################################"
echo ""
echo ""

DL_GERDSWERK="https://www.google.com/maps/d/kml?mid=1L-gatZq7W4lZzdrfLLAK3AVUoc8lKNo&femb=1&ll=50.36612061088382%2C10.627823200000002&z=6"
MAP_DIR="/opt/gerds_daten"
KMZ_FILE="gerds.kmz"
DL_KML2DB="https://raw.githubusercontent.com/zzzkeil/ladefreunde/refs/heads/main/python/kml2db.py"
DL_FETCH="https://raw.githubusercontent.com/zzzkeil/ladefreunde/refs/heads/main/python/fetchstreetcity.py"

# kleine vorab checks
. /etc/os-release
if [[ "$ID" = 'debian' ]]; then
   systemos=debian
fi
if [[ "$systemos" = '' ]]; then
   echo "Stop! Das Script ist erstmal nur für Debian."
   exit 1
fi

if [[ "$EUID" -ne 0 ]]; then
	echo "Sorry, mach das bitte mal als root"
	exit 1
fi

apt-get install -y -q unzip jq python3 python3-pymysql python3-lxml python3-requests

mkdir -p "$MAP_DIR"

wget -q -O "$MAP_DIR/$KMZ_FILE" "$DL_GERDSWERK"
if [ $? -ne 0 ]; then
    echo "Fehler beim Download"
    exit 1
fi

if [ -f "$MAP_DIR/kml2db.py" ]; then
    echo "$MAP_DIR/kml2db.py schon da, nicht überschrieben."
else
    wget -q -O "$MAP_DIR/kml2db.py" "$DL_KML2DB"
fi

if [ -f "$MAP_DIR/fetchstreetcity.py" ]; then
    echo "$MAP_DIR/fetchstreetcity.py schon da, nicht überschrieben."
else
    wget -q -O "$MAP_DIR/fetchstreetcity.py" "$DL_FETCH"
fi

unzip -o -j "$MAP_DIR/$KMZ_FILE" '*.kml' -d "$MAP_DIR"
if [ $? -ne 0 ]; then
    echo "Datei fehler. Ist die Datei da oder fehlerhaft?"
    rm -f "$KMZ_FILE"
    exit 1
fi
rm -f "$MAP_DIR/$KMZ_FILE"

# checken op die DB Daten angepasst wurden
target_content="\"password\": \"das_sichere_passwort\""
error=0
if grep -q "$target_content" "$MAP_DIR/kml2db.py"; then
    echo "$MAP_DIR/kml2db.py enthält noch DB Login Platzhalter"
    error=1
fi
if grep -q "$target_content" "$MAP_DIR/fetchstreetcity.py"; then
    echo "$MAP_DIR/fetchstreetcity.py enthält noch DB Login Platzhalter"
    error=1
fi
if [ $error -eq 1 ]; then
    echo ""
    echo "Stop! Du musst erst deine Datenbank-Daten eintragen."
    exit 1
fi

set -e   #halten bei fehler
cd $MAP_DIR
python3 kml2db.py
echo " Fertig - Rohdaten in DB. Jetzt Straße, Hausnummer, und Stadt beschafen"
python3 fetchstreetcity.py
echo " Fertig - Wenn Straße, Hausnummer, Stadt gefunden, wurden sie hinzugefügt"
exit 0
