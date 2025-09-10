<?php
/* Made by zzzkeil - https://github.com/zzzkeil  
/* Free to use, modify, share.
/* MIT License .....
/* Thanks go also to Gemini, ChatGPT, and some local LLM */

require_once 'config.php';

if (!isset($_SESSION['discord_user'])) {

    $authUrl = 'https://discord.com/api/oauth2/authorize' . '?' . http_build_query([
        'client_id' => DISCORD_CLIENT_ID,
        'redirect_uri' => DISCORD_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'identify'
    ]);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Anmelden mit Discord</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="login-body">
    <div class="login-container">
        <h1>Ladefreunde Event Map</h1>
        <p>Zugang zur Planung mitc Discord Account.<br>Bitte über Discord einloggen.</p>
        <a href="<?php echo $authUrl; ?>" class="discord-login-button">Login mit Discord</a>
    </div>
</body>
</html>

<?php
    exit(); 
}


$user = $_SESSION['discord_user'];

?>
<!DOCTYPE html>
<html lang="de">
<head>
<!--
/* Made by zzzkeil - https://github.com/zzzkeil  
/* Free to use, modify, share.
/* MIT License .....
/* Thanks goes also to Gemini, ChatGPT, and some local LLM */
-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ladefreunde Event Map</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="main-container">
        <div class="search-panel">
            <div class="user-info">
                <img src="https://cdn.discordapp.com/avatars/<?php echo htmlspecialchars($user->id . '/' . $user->avatar); ?>.png" alt="Avatar">
                <span>Hallo, <?php echo htmlspecialchars($user->username); ?>!</span>
                <a href="logout.php" class="logout-link">Logout</a>
            </div>

            <h1>Laaaadeerleeebnis Event</h1>
            <h2>Hier kannst du ein kleines<br>Community treffen planen<br>an Ladestationen > 150kW</h2>
            <h3>Daten sind aus der Gerd Bremer Map</h3>
            <h3>Unser Dank geht an Gerd</h3>
            <div class="search-box-wrapper">
                <input type="text" id="search-box" placeholder="Suche nach Name, PLZ, oder Ort" autocomplete="off">
                <div id="suggestion-box"></div>
            </div>
        </div>

        <div class="map-panel">
            <div id="map"></div>
            <div id="info-display">
                <p>Suche deine Ladestation oben im Suchfeld, dann stehen hier die Daten,<br>und der Knopf zum Eventeintragen auf Discord.</p>
            </div>
        </div>
    </div>

    <script>
        const searchBox = document.getElementById('search-box');
        const suggestionBox = document.getElementById('suggestion-box');
        const infoDisplay = document.getElementById('info-display');

        let selectedPoiData = null;

        const map = L.map('map').setView([51.1657, 10.4515], 6); 
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        let currentMarker = null;

        searchBox.addEventListener('keyup', function() {
            const searchTerm = this.value;

            if (searchTerm.length < 2) {
                suggestionBox.style.display = 'none';
                return;
            }

            fetch(`search.php?term=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        let suggestionsHTML = '<ul>';
                        data.forEach(item => {
                            suggestionsHTML += `<li 
                                data-name="${item.name}"
                                data-street="${item.street}"
                                data-housenumber="${item.housenumber}"
                                data-postalcode="${item.postalcode}"
                                data-city="${item.city}"
                                data-lat="${item.latitude}" 
                                data-lon="${item.longitude}"
                            >${item.display_html}</li>`;
                        });
                        suggestionsHTML += '</ul>';
                        
                        suggestionBox.innerHTML = suggestionsHTML;
                        suggestionBox.style.display = 'block';
                    } else {
                        suggestionBox.innerHTML = '<p>No results found.</p>';
                        suggestionBox.style.display = 'block';
                    }
                })
                .catch(error => console.error('Error fetching search results:', error));
        });

        suggestionBox.addEventListener('click', function(e) {
            if (e.target.tagName === 'LI') {
                const selectedLi = e.target;
                const { name, street, housenumber, postalcode, city, lat, lon } = selectedLi.dataset;
                
                selectedPoiData = { name, street, housenumber, postalcode, city, latitude: lat, longitude: lon };

                searchBox.value = name;
                suggestionBox.style.display = 'none';
                
                if (lat && lon) {
                    const coords = [parseFloat(lat), parseFloat(lon)];
                    if (currentMarker) map.removeLayer(currentMarker);
                    map.setView(coords, 14); 
                    currentMarker = L.marker(coords).addTo(map).bindPopup(`<b>${name}</b>`).openPopup();
                }
                
                const streetAddress = [street, housenumber].filter(Boolean).join(' ');
                const cityAddress = [postalcode, city].filter(Boolean).join(' ');
                const now = new Date();
                const defaultDate = now.toISOString().split('T')[0]; 
                
                infoDisplay.innerHTML = `
                    <h3>${name || 'Details'}</h3>
                    <div class="info-grid">
                        <span>Adresse:</span> <span>${streetAddress || 'N/A'}</span>
                        <span>Stadt:</span> <span>${cityAddress || 'N/A'}</span>
                    </div>
                    <h4>Plane das Laaaadeerleeebnis Event</h4>
                    <div class="schedule-grid">
                        <label for="event-date">Date:</label>
                        <input type="date" id="event-date" value="${defaultDate}" min="${defaultDate}">
                        <label for="event-time">Start Time:</label>
                        <input type="time" id="event-time" value="14:00">
                    </div>
                    <button id="discord-event-btn" class="discord-button">In Discord Events eintragen</button>
                    <div id="discord-status"></div>
                `;
            }
        });

        infoDisplay.addEventListener('click', function(e) {
            if (e.target.id === 'discord-event-btn') {
                const eventDate = document.getElementById('event-date').value;
                const eventTime = document.getElementById('event-time').value;

                if (!eventDate || !eventTime) {
                    alert('Bitte schau mal nach ob das Datum oder die Zeit passt.');
                    return;
                }
                const selectedDateTime = new Date(`${eventDate}T${eventTime}`);
                if (selectedDateTime < new Date()) {
                    alert('Events in der Vergangenheit? \n Das wird nix. \n Muss in der Zukunft sein.');
                    return;
                }

                if (!selectedPoiData) {
                    alert('No location selected.');
                    return;
                }
                
                const statusDiv = document.getElementById('discord-status');
                statusDiv.textContent = 'Erstelle Event in Discord...';
                e.target.disabled = true;

                const eventData = { ...selectedPoiData, date: eventDate, time: eventTime };

                fetch('create_discord_event.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(eventData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        statusDiv.textContent = '✅ ' + data.message;
                        statusDiv.style.color = '#2ecc71';
                    } else {
                        statusDiv.textContent = `❌ Error: ${data.message}`;
                        statusDiv.style.color = '#e74c3c';
                        console.error('Discord API Error:', data.discord_response);
                    }
                })
                .catch(error => {
                    statusDiv.textContent = '❌ Netzwerkfehler ......';
                    statusDiv.style.color = '#e74c3c';
                })
                .finally(() => {
                    e.target.disabled = false;
                });
            }
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-box-wrapper')) {
                suggestionBox.style.display = 'none';
            }
        });
    </script>

</body>
</html>
