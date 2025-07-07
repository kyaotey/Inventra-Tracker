<?php
// Step 1: Connect to MySQL
$host = 'localhost';
$user = 'root';
$password = ''; // Update if you have a DB password
$database = 'missing_items_db';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 2: Fetch reports with coordinates
$sql = "SELECT id, title, latitude, longitude FROM reports WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
$result = $conn->query($sql);

$locations = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Missing Items Map</title>

    <!-- Leaflet CSS -->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    />

    <!-- Leaflet JS -->
    <script
      src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
    </script>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        #map {
            height: 100vh;
            width: 100%;
        }
    </style>
</head>
<body>

    <div id="map"></div>

    <script>
        // Step 3: Initialize Map
        var map = L.map('map').setView([5.5600, -0.2050], 12); // Default: Accra

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Step 4: Use PHP data in JavaScript
        var locations = <?php echo json_encode($locations); ?>;

        locations.forEach(function(loc) {
            var marker = L.marker([loc.latitude, loc.longitude]).addTo(map);
            marker.bindPopup("<b>" + loc.title + "</b>");
        });
    </script>

</body>
</html>
