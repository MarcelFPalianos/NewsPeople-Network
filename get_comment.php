<?php
// get_comments.php: Script for retrieving all comments

// Include the database connection
include 'db.php';

// Prepare SQL statement to get all comments
$sql = "SELECT * FROM comments";
$result = $conn->query($sql);

$comments = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'lat' => $row['lat'],
            'lng' => $row['lng'],
            'comment' => $row['comment'],
            'created_at' => $row['created_at']
        ];
    }
}

// Return comments as JSON
echo json_encode($comments);

$conn->close();
?>
