<?php
header('Content-Type: application/json');
if (!isset($_GET['q']) || strlen(trim($_GET['q'])) < 3) {
    echo json_encode([]);
    exit;
}
$q = urlencode(trim($_GET['q']));
$url = "https://nominatim.openstreetmap.org/search?format=json&q={$q}&limit=1";

$opts = [
    "http" => [
        "header" => "User-Agent: Inventra-Tracker/1.0 (your@email.com)"
    ]
];
$context = stream_context_create($opts);
$response = @file_get_contents($url, false, $context);
if ($response === FALSE) {
    echo json_encode([]);
    exit;
}
echo $response; 