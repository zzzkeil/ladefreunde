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
        'scope' => 'identify guilds'
    ]);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Anmelden mit Discord</title>
    <link rel="stylesheet" href="ext/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="login-body">
    <div class="login-container">
        <h1>Ladefreunde Event Map</h1>
        <p>Zugang zur Planung mit Discord Account.<br>Bitte über Discord einloggen.</p>
        <a href="<?php echo $authUrl; ?>" class="discord-login-button">Login mit Discord</a>
    </div>

    <div class="gdpr-notice">
         <p><strong>DSGVO Info</strong></p>
         <p>Mit klick auf Login, laden diese externe Dienste:</p>
         <p>Login und Events Eintragungen: <b>discord.com</b></p>
         <p>Anzeige der Karte: <b>openstreetmap.org</b></p>
         <p><strong>Wenn du das nicht möchtest, nicht Login klicken ;)</strong></p>
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
    
    <link rel="stylesheet" href="ext/leaflet.css"/>
    <script src="ext/leaflet.js"></script>
    
    <link rel="stylesheet" href="ext/style.css">
</head>
<body>
<div class="main-container">
  <div class="search-panel">
    <div class="search-content">
      <div class="user-info">
        <img src="https://cdn.discordapp.com/avatars/<?php echo htmlspecialchars($user->id . '/' . $user->avatar); ?>.png" alt="Avatar">
        <span>Hallo, <?php echo htmlspecialchars($user->username); ?>!</span>
        <a href="logout.php" class="logout-link">Logout</a>
      </div>

      <h1>Laaaadeerleeebnis planen</h1>
      <p>Ladestationen über 150kW & unter 50ct<br>
         Daten sind aus der Gerd Bremer Map<br>
         Vielen dank an Gerd
      </p>

      <div class="legend-item">
        <img src="ext/pin-gr-m.png" alt="Events Pin">
        <p>Klicken für Infos zu geplanten Events</p>
      </div>

      <div class="search-box-wrapper">
        <input type="text" id="search-box" placeholder="Suche nach Name, PLZ, oder Ort" autocomplete="off">
        <div id="suggestion-box"></div>
      </div>
    </div>

    <!-- New left info display area for desktop -->
    <div class="left-info-display" id="left-info-display">
      <p>Suche deine Ladestation oben im Suchfeld, dann stehen hier die Daten,<br>und der Knopf zum Eventeintragen auf Discord.</p>
    </div>
  </div>

  <div class="map-panel">
    <div id="map"></div>
    <!-- Original info display for mobile -->
    <div id="info-display">
      <p>Suche deine Ladestation oben im Suchfeld, dann stehen hier die Daten,<br>und der Knopf zum Eventeintragen auf Discord.</p>
    </div>
  </div>
</div>

    <script>
      const searchBox = document.getElementById('search-box');
  const suggestionBox = document.getElementById('suggestion-box');
  const infoDisplay = document.getElementById('info-display');
  const leftInfoDisplay = document.getElementById('left-info-display');

  let selectedPoiData = null;
  const eventLayer = L.layerGroup();

  const map = L.map('map').setView([51.1657, 10.4515], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);
  let currentMarker = null;

  // Function to get the active info display based on screen size
  function getActiveInfoDisplay() {
      const isDesktop = window.innerWidth > 1024;
      return isDesktop ? leftInfoDisplay : infoDisplay;
  }

  // Function to update info content in the correct display
  function updateInfoDisplay(content) {
      const activeDisplay = getActiveInfoDisplay();
      activeDisplay.innerHTML = content;
  }

  function loadUpcomingEvents() {
      fetch('fetch_events.php')
      .then(response => response.json())
      .then(data => {
          eventLayer.clearLayers();

          const eventIcon = L.icon({
              iconUrl: 'ext/pin-gr-m.png',
              shadowUrl: 'ext/shadow.png',
              iconSize: [25, 41],
              iconAnchor: [12, 41],
              popupAnchor: [1, -34],
              shadowSize: [41, 41]
          });

          data.forEach(event => {
              const startTime = new Date(event.start_time);
              const popupContent = `
              <b>${event.name}</b><br>
              Zeit: ${startTime.toLocaleString('de-DE')}<br>
              `;

              const marker = L.marker([event.latitude, event.longitude], { icon: eventIcon })
              .bindPopup(popupContent);

              eventLayer.addLayer(marker);
          });

          eventLayer.addTo(map);
      })
      .catch(error => console.error('Error fetching upcoming events:', error));
  }

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
                  data-id="${item.id}"
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
          const { id, name, street, housenumber, postalcode, city, lat, lon } = selectedLi.dataset;

          selectedPoiData = { poi_id: id, name, street, housenumber, postalcode, city, latitude: lat, longitude: lon };

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

          const infoContent = `
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
          
          updateInfoDisplay(infoContent);
      }
  });

  // Event delegation for both info displays
  document.addEventListener('click', function(e) {
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
              alert('Kein Punkt gewählt.');
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
                  loadUpcomingEvents();
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

  // Handle window resize to ensure correct info display is used
  window.addEventListener('resize', function() {
      // Trigger a re-render of info if there's selected data
      if (selectedPoiData) {
          const currentContent = getActiveInfoDisplay().innerHTML;
          if (currentContent.includes('<h3>') && !currentContent.includes('Suche deine Ladestation')) {
              // Re-trigger the display update
              const event = new Event('click');
              const mockLi = document.createElement('li');
              mockLi.dataset = {
                  id: selectedPoiData.poi_id,
                  name: selectedPoiData.name,
                  street: selectedPoiData.street,
                  housenumber: selectedPoiData.housenumber,
                  postalcode: selectedPoiData.postalcode,
                  city: selectedPoiData.city,
                  lat: selectedPoiData.latitude,
                  lon: selectedPoiData.longitude
              };
              
              // Manually trigger the content update without changing the map
              const streetAddress = [selectedPoiData.street, selectedPoiData.housenumber].filter(Boolean).join(' ');
              const cityAddress = [selectedPoiData.postalcode, selectedPoiData.city].filter(Boolean).join(' ');
              const now = new Date();
              const defaultDate = now.toISOString().split('T')[0];

              const infoContent = `
              <h3>${selectedPoiData.name || 'Details'}</h3>
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
              
              updateInfoDisplay(infoContent);
          }
      }
  });

  document.addEventListener('DOMContentLoaded', function() {
      loadUpcomingEvents();
  });
    </script>

</body>
</html>
