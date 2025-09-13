<?php
/* Made by zzzkeil - https://github.com/zzzkeil  
/* Free to use, modify, share.
/* MIT License .....
/* Thanks go also to Gemini, ChatGPT, and some local LLM */

require_once 'config.php';

function postAnnouncementMessage($botToken, $channelId, $guildId, $eventData, $newEventId, $creatorUsername) {
    $apiUrl = "https://discord.com/api/v10/channels/{$channelId}/messages";
    
    $eventUrl = "https://discord.com/events/{$guildId}/{$newEventId}";
    
    $content = NOTIFICATION_ROLE_ID ? "Ein neues Event wurde geplant! <@&" . NOTIFICATION_ROLE_ID . ">" : "Ein neues Event wurde geplant!";

    $embed = [
        'title' => "🗓️ " . $eventData['name'],
        'url' => $eventUrl,
        'description' => "Klicke auf den Titel oben, um alle Details zu sehen und dich anzumelden.",
        'color' => hexdec('5865F2'),
        'fields' => [
            [
                'name' => 'Ort',
                'value' => trim($eventData['street'] . ' ' . $eventData['housenumber']) . ', ' . trim($eventData['postalcode'] . ' ' . $eventData['city']),
            ],
            [
                'name' => 'Beginnt am',
                'value' => '<t:' . (new DateTime($eventData['date'] . ' ' . $eventData['time'], new DateTimeZone('Europe/Berlin')))->getTimestamp() . ':F>',
                'inline' => true,
            ],
            [
                'name' => 'Erstellt von',
                'value' => $creatorUsername,
                'inline' => true,
            ],
        ],
    ];

    $payload = json_encode([
        'content' => $content,
        'embeds' => [$embed],
        'allowed_mentions' => ['parse' => ['roles']],
    ]);

    $headers = ['Authorization: Bot ' . $botToken, 'Content-Type: application/json'];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_exec($ch);
    curl_close($ch);
}


header('Content-Type: application/json');

if (!isset($_SESSION['discord_user'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Du musst mit Discord eingelogt sein, sonst gehts nicht.']);
    exit;
}

if (empty($botToken) || empty($guildId)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Da stimmt was mit dem Bot Token oder Guild ID.']);
    exit;
}

$postData = json_decode(file_get_contents('php://input'), true);

if (empty($postData) || !isset($postData['poi_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Incomplete event data received. Missing POI ID.']);
    exit;
}

try {
    $timezone = new DateTimeZone('Europe/Berlin');
    $dateTimeStr = $postData['date'] . ' ' . $postData['time'];
    $startDateTime = new DateTime($dateTimeStr, timezone: $timezone);
    $endDateTime = (clone $startDateTime)->add(new DateInterval('PT1H'));
    $scheduledStartTime = $startDateTime->format(DateTime::ATOM);
    $scheduledEndTime = $endDateTime->format(DateTime::ATOM);
    $creationDateTime = new DateTime('now', $timezone);
    $creationTimestamp = $creationDateTime->format('d.m.Y - H:i');
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datum oder Zeit Format falsch.']);
    exit;
}


$name = $postData['name'];
$streetAddress = trim($postData['street'] . ' ' . $postData['housenumber']);
$cityAddress = trim($postData['postalcode'] . ' ' . $postData['city']);
$fullLocation = trim("$streetAddress, $cityAddress");
$creatorUsername = $_SESSION['discord_user']->username;
$poi_id = $postData['poi_id'];
$eventDescription = ":round_pushpin: Adresse      :  " . $fullLocation . "\n"
                    . ":pencil: Ersteller     :  " . $creatorUsername . " am " . $creationTimestamp . "\n"
                    . "\n\n[ref:poi_{$poi_id}]";

$payload = json_encode([
    'name' => $name,
    'privacy_level' => 2,
    'scheduled_start_time' => $scheduledStartTime,
    'scheduled_end_time' => $scheduledEndTime,
    'description' => $eventDescription,
    'entity_type' => 3,
    'entity_metadata' => ['location' => $cityAddress],
]);


$apiUrl = "https://discord.com/api/v10/guilds/{$guildId}/scheduled-events";
$headers = ['Authorization: Bot ' . $botToken, 'Content-Type: application/json'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode >= 200 && $httpcode < 300) {
    $newEventData = json_decode($response, true);
    
    if (defined('ANNOUNCEMENT_CHANNEL_ID') && !empty(ANNOUNCEMENT_CHANNEL_ID)) {
        postAnnouncementMessage($botToken, ANNOUNCEMENT_CHANNEL_ID, $guildId, $postData, $newEventData['id'], $creatorUsername);
    }

    echo json_encode(['status' => 'success', 'message' => 'Event in Discord erstellt']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "OHHH Nein da ging was schieffff. Fehlercode: $httpcode", 'discord_response' => json_decode($response)]);
}