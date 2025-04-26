<?php
// get_photo.php

// Database credentials
$servername = "localhost:3306";
$username = "rc-attendance-system";
$password = "rc-attendance-system";
$dbname = "rc-attendance-system";

// Get the record ID from the request
$recordId = isset($_GET['id']) ? $_GET['id'] : null;

if ($recordId) {
  try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the photo for the given ID
    $stmt = $conn->prepare("SELECT photo FROM data WHERE ID = :id");
    $stmt->execute([':id' => $recordId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $conn = null;

    // Check if photo exists and return it in JSON format
    if ($result && $result['photo']) {
      echo json_encode(['photo' => $result['photo']]);
    } else {
      echo json_encode(['photo' => null]);
    }

  } catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
  }
}
?>
