<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Include the database connection
include 'db.php';

// Check if this is a vote request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id'], $_POST['vote'])) {
    // Handle the vote request
    try {
        // Get data from POST
        $comment_id = intval($_POST['comment_id']);
        $vote = $_POST['vote'];
        $user_id = $_SESSION['user_id'];

        // Validate the input
        if (!in_array($vote, ['true', 'false'])) {
            throw new Exception('Invalid vote type');
        }

        // Check if the user has already voted for this comment
        $stmt = $conn->prepare("SELECT * FROM votes WHERE comment_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $comment_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'You have already voted for this comment']);
            exit();
        }

        // Insert the vote
        $stmt = $conn->prepare("INSERT INTO votes (comment_id, user_id, vote) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }

        $stmt->bind_param("iis", $comment_id, $user_id, $vote);
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute statement: ' . $stmt->error);
        }

        echo json_encode(['status' => 'success', 'message' => 'Vote recorded successfully']);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcel Palianos Project</title>
    <link rel="stylesheet" href="style.css"> <!-- Linking the external CSS file -->
</head>
<body>

    <!-- Displaying the logged-in user's name -->
    <h2>Welcome to the Default Page</h2>
    <p>Hello, <?php echo htmlspecialchars($_SESSION['user']); ?>! You are logged in.</p>
    <a href="logout.php">Logout</a>
    <a href="register.php">Register a New Account</a>

    <!-- Map container -->
    <div id="map"></div>

    <!-- Comment modal -->
    <div id="overlay"></div>
    <div id="commentModal">
        <h3>Leave a Comment</h3>
        <textarea id="commentText" rows="4"></textarea>
        <br>
        <button onclick="submitComment()">Submit</button>
        <button onclick="closeModal()">Cancel</button>
    </div>

    <!-- Comments table -->
    <table id="commentsTable">
        <thead>
            <tr>
                <th>Area</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Vote</th> <!-- New column for voting buttons -->
            </tr>
        </thead>
        <tbody>
            <!-- Comments will be inserted dynamically -->
        </tbody>
    </table>

    <!-- Google Maps API and custom JavaScript -->
    <script>
        function voteComment(commentId, voteType) {
            console.log(`Sending vote: comment_id=${commentId}, vote=${voteType}`); // Debug log
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `comment_id=${commentId}&vote=${voteType}`
            })
            .then(response => response.json())
            .then(data => {
                console.log(data); // Log backend response
                if (data.status === 'success') {
                    alert('Vote recorded successfully!');
                } else {
                    alert(`Error: ${data.message}`);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
    <script async src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap"></script>
</body>
</html>
