<?php
// db.php: Database connection file
$servername = "localhost";
$username = "u450382363_admin"; 
$password = "Marce7f70rin";
$dbname = "u450382363_users";
//test
//teast 2
//test 3
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>