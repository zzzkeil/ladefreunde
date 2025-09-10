<?php
/* Made by zzzkeil - https://github.com/zzzkeil  
/* Free to use, modify, share.
/* MIT License .....
/* Thanks go also to Gemini, ChatGPT, and some local LLM */

// --- DATABASE CONFIGURATION ---
$db_host = '127.0.0.1'; //oder localhost, oder...
$db_name = 'der_db_name';
$db_user = 'der_username';
$db_pass = 'das_sichere_passwort';

header('Content-Type: application/json');

if (!isset($_GET['term']) || empty($_GET['term'])) {
    echo json_encode([]);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$term = $_GET['term'];
$suggestions = [];

$query = "
    SELECT name, streetname, housenumber, postalcode, city, latitude, longitude
    FROM pois
    WHERE name LIKE ?
       OR streetname LIKE ?
       OR postalcode LIKE ?
       OR city LIKE ?
    LIMIT 40
";

if ($stmt = $conn->prepare($query)) {
    $searchTerm = '%' . $term . '%';
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $display_address_parts = array_filter([
            htmlspecialchars($row['name']),
            htmlspecialchars($row['streetname']),
            htmlspecialchars($row['housenumber']),
            htmlspecialchars($row['postalcode']),
            htmlspecialchars($row['city'])
        ]);

        $display_html = implode(', ', $display_address_parts);
        $display_html = preg_replace('/(' . preg_quote($term, '/') . ')/i', '<b>$1</b>', $display_html);

        $suggestions[] = [
            'name'         => htmlspecialchars($row['name']),
            'street'       => htmlspecialchars($row['streetname']),
            'housenumber'  => htmlspecialchars($row['housenumber']),
            'postalcode'   => htmlspecialchars($row['postalcode']),
            'city'         => htmlspecialchars($row['city']),
            'display_html' => $display_html,
            'latitude'     => $row['latitude'],
            'longitude'    => $row['longitude']
        ];
    }
    
    $stmt->close();
}

$conn->close();

echo json_encode($suggestions);
