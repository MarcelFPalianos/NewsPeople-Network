<?php
include 'db.php'; // Include your database connection

session_start(); // Start the session to access user details

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

// Get the data from the POST request
$comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
$vote = isset($_POST['vote']) ? $_POST['vote'] : '';

// Validate the vote type
if (!in_array($vote, ['true', 'false'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid vote type']);
    exit();
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Check if the user has already voted for this comment
$query = $conn->prepare("SELECT * FROM votes WHERE comment_id = ? AND user_id = ?");
$query->bind_param("ii", $comment_id, $user_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'You have already voted for this comment']);
    exit();
}

// Insert the vote into the votes table
$query = $conn->prepare("INSERT INTO votes (comment_id, user_id, vote) VALUES (?, ?, ?)");
$query->bind_param("iis", $comment_id, $user_id, $vote);

if ($query->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Vote recorded successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to record vote']);
}

// Close the database connection
$conn->close();
?>
