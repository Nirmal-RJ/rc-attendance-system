<?php
// Database config
$host = "localhost";
$db = "rc-attendance-system";
$user = "root";
$pass = "";

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// Get POST data
$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM credentials WHERE username = '$username' AND password = '$password'";
$result = $conn->query($sql);

if ($result->num_rows === 1) 
{
    $_SESSION['logged_in'] = true;
    echo "Login successful. Welcome, " . htmlspecialchars($username) . "!";    
    header("Location: ./test.php");
    exit();
} else {
    echo "Invalid username or password.";
    header("Location: ../login.html");
}

$conn->close();
?>
