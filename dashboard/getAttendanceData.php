<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow all domains to access

// Database connection
$host = 'localhost:3306';
$dbname = 'rc-attendance-system';
$username = 'rc-attendance-system'; // Replace with your DB username
$password = 'rc-attendance-system'; // Replace with your DB password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $query = "SELECT * FROM data ORDER BY timestamp DESC";
    $stmt = $conn->query($query);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert timestamp to ISO format if needed
    foreach ($data as &$row) {
        if (!strpos($row['timestamp'], 'T')) {
            $row['timestamp'] = str_replace(' ', 'T', $row['timestamp']) . 'Z';
        }
    }
    
    echo json_encode($data);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>