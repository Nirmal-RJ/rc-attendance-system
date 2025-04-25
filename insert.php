<?php
// Set up the MySQL connection
$servername = "localhost"; // Change if needed
$username = "rc-attendance-system"; // MySQL username
$password = "rc-attendance-system"; // MySQL password
$dbname = "rc-attendance-system"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the incoming JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Ensure the required fields exist in the incoming data
if (isset($data['name'], $data['timestamp'], $data['location'], $data['photo'], $data['action'])) {
    $name = $conn->real_escape_string($data['name']);
    $timestamp = $conn->real_escape_string($data['timestamp']);
    $location = $conn->real_escape_string($data['location']);
    $photo = $conn->real_escape_string($data['photo']);
    $action = $conn->real_escape_string($data['action']);

    // Prepare the SQL query to insert the data into the 'data' table
    $sql = "INSERT INTO data (name, timestamp, location, photo, action)
            VALUES ('$name', '$timestamp', '$location', '$photo', '$action')";

    // Execute the query
    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "Error: Missing required fields in the incoming data.";
}

// Close the connection
$conn->close();
?>
