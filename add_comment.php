<?php
include 'db.php';

$lat = $_POST['lat'];
$lng = $_POST['lng'];
$comment = $_POST['comment'];

$sql = "INSERT INTO comments (lat, lng, comment) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("dds", $lat, $lng, $comment);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Comment added successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add comment']);
}

$stmt->close();
$conn->close();
?>
