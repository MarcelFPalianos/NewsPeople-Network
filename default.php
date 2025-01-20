<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Include the database connection
include 'db.php';

// Handle POST requests for votes and comments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['comment_id'], $_POST['vote'])) {
            // Handle vote request
            $comment_id = intval($_POST['comment_id']);
            $vote = $_POST['vote'];
            $user_id = $_SESSION['user_id'];

            if (!in_array($vote, ['true', 'false'])) {
                throw new Exception('Invalid vote type');
            }

            $stmt = $conn->prepare("SELECT * FROM votes WHERE comment_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $comment_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'You have already voted for this comment']);
                exit();
            }

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
        } elseif (isset($_POST['lat'], $_POST['lng'], $_POST['comment'])) {
            // Handle adding a comment
            $lat = floatval($_POST['lat']);
            $lng = floatval($_POST['lng']);
            $comment = htmlspecialchars($_POST['comment']);
            $user = $_SESSION['user'];

            $stmt = $conn->prepare("INSERT INTO comments (user, lat, lng, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            $stmt->bind_param("sdds", $user, $lat, $lng, $comment);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute statement: ' . $stmt->error);
            }

            echo json_encode(['status' => 'success', 'message' => 'Comment added successfully']);
            exit();
        } else {
            throw new Exception('Invalid request');
        }
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
                <th>User</th>
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
        let map;
        let clickedLatLng;

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 51.5072, lng: 0.1276 }, // Default location (London)
                zoom: 19,
            });

            fetch('get_comments.php')
                .then(response => response.json())
                .then(data => {
                    const commentsTable = document.querySelector("#commentsTable tbody");
                    commentsTable.innerHTML = ''; // Clear existing rows
                    data.forEach(comment => {
                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td>${comment.area || 'Unknown'}</td>
                            <td>${comment.comment}</td>
                            <td>${comment.user}</td>
                            <td>${new Date(comment.created_at).toLocaleString()}</td>
                            <td>
                                <button onclick="voteComment(${comment.id}, 'true')">Vote True</button>
                                <button onclick="voteComment(${comment.id}, 'false')">Vote False</button>
                            </td>
                        `;
                        commentsTable.appendChild(row);
                    });
                });
        }

        function submitComment() {
            const commentText = document.getElementById("commentText").value;
            if (!clickedLatLng || !commentText) {
                alert("Please fill in all fields");
                return;
            }
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `lat=${clickedLatLng.lat()}&lng=${clickedLatLng.lng()}&comment=${encodeURIComponent(commentText)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert("Comment added successfully");
                    closeModal();
                    initMap(); // Refresh the map and comments
                } else {
                    alert(`Error: ${data.message}`);
                }
            })
            .catch(error => console.error("Error:", error));
        }

        function voteComment(commentId, voteType) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `comment_id=${commentId}&vote=${voteType}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert("Vote recorded successfully");
                } else {
                    alert(`Error: ${data.message}`);
                }
            })
            .catch(error => console.error("Error:", error));
        }

        function openModal() {
            document.getElementById("overlay").style.display = "block";
            document.getElementById("commentModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("overlay").style.display = "none";
            document.getElementById("commentModal").style.display = "none";
        }
    </script>
    <script async src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap"></script>
</body>
</html>
