<?php
/* Made by zzzkeil - https://github.com/zzzkeil  
/* Free to use, modify, share.
/* MIT License .....
/* Thanks go also to Gemini, ChatGPT, and some local LLM */

require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['discord_user'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Du musst mit Discord eingelogt sein, sonst gehts nicht.']);
    exit;
}

if (empty($botToken) || empty($guildId)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Da stimmt was nicht mit dem Bot Token oder Guild ID.']);
    exit;
}

$postData = json_decode(file_get_contents('php://input'), true);

if (empty($postData) || !isset($postData['name']) || !isset($postData['date']) || !isset($postData['time'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Da stimmt was nicht mit den Event Daten.']);
    exit;
}

if (empty($postData) || !isset($postData['poi_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Incomplete event data received. Missing POI ID.']);
    exit;
}

try {
    $timezone = new DateTimeZone('Europe/Berlin');
    $dateTimeStr = $postData['date'] . ' ' . $postData['time'];
    $startDateTime = new DateTime($dateTimeStr, $timezone);
    $endDateTime = (clone $startDateTime)->add(new DateInterval('PT1H'));
    $scheduledStartTime = $startDateTime->format(DateTime::ATOM);
    $scheduledEndTime = $endDateTime->format(DateTime::ATOM);
    $creationDateTime = new DateTime('now', $timezone);
    $creationTimestamp = $creationDateTime->format('d.m.Y / H:i');
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
$eventDescription = "". ":round_pushpin: Adresse      :  " . $fullLocation . "\n"
                    . ":pencil: Ersteller     :  " . $creatorUsername . "\n"
                    . ":point_right: Mehr Details hier klicken :point_left: \n\n"
                    . ":date: Erstellt am     :   " . $creationTimestamp
                    . "\n\n[ref:poi_{$poi_id}]";

$payload = json_encode([
    'name' => $name,
    'privacy_level' => 2,
    'scheduled_start_time' => $scheduledStartTime,
    'scheduled_end_time' => $scheduledEndTime,
    'description' => $eventDescription,
    'entity_type' => 3,
    'entity_metadata' => [
        //location' => $fullLocation,
        'location' => $cityAddress,
    ],
]);

$apiUrl = "https://discord.com/api/v10/guilds/{$guildId}/scheduled-events";
$headers = [
    'Authorization: Bot ' . $botToken,
    'Content-Type: application/json',
];

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
    echo json_encode(['status' => 'success', 'message' => 'Laaaadeerleeebnis Event in Discord erstellt.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "OHHH Nein da ging was schieffff. Fehlercode: $httpcode", 'discord_response' => json_decode($response)]);
}
