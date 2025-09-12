<?php
/* Made by zzzkeil - https://github.com/zzzkeil
/ * Free to use, modify, share.    *
/* MIT License .....
/* Thanks go also to Gemini, ChatG*PT, and some local LLM */

require_once 'config.php';

header('Content-Type: application/json');

$apiUrl = "https://discord.com/api/v10/guilds/{$guildId}/scheduled-events";
$headers = ['Authorization: Bot ' . $botToken];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$discordEvents = json_decode($response, true);

if (!$discordEvents || isset($discordEvents['message'])) {
    echo json_encode([]); // Return empty array on error
    exit;
}

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}


$eventsWithCoords = [];
$sql = "SELECT latitude, longitude FROM pois WHERE id = ?";

foreach ($discordEvents as $event) {
    if (isset($event['description']) && preg_match('/\[ref:poi_(\d+)\]/', $event['description'], $matches)) {
        $poi_id = (int)$matches[1];
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $poi_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($coords = $result->fetch_assoc()) {
                $eventsWithCoords[] = [
                    'name' => $event['name'],
                    'start_time' => $event['scheduled_start_time'],
                    'description' => $event['description'],
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                ];
            }
            $stmt->close();
        }
    }
}

$conn->close();
echo json_encode($eventsWithCoords);
