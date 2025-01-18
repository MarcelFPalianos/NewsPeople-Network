<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
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
        <!-- Example row -->
        <tr>
            <td>London</td>
            <td>This is an example comment.</td>
            <td>2025-01-18</td>
            <td>
                <button onclick="voteComment(1, 'true')">Vote True</button>
                <button onclick="voteComment(1, 'false')">Vote False</button>
            </td>
        </tr>
        <!-- Comments will be inserted here dynamically -->
    </tbody>
</table>


    <!-- Google Maps API and custom JavaScript -->
    <script>
        let map;
        let clickedLatLng;

        // Initialize the map
        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 51.5072, lng: 0.1276 }, // Default location (London)
                zoom: 19,
            });

            fetch('get_comments.php')
                .then(response => response.json())
                .then(data => {
                    const commentsTable = document.getElementById("commentsTable"); // Your table element
                    commentsTable.innerHTML = ''; // Clear existing rows
                    
                    data.forEach(commentData => {
                        const lat = parseFloat(commentData.lat);
                        const lng = parseFloat(commentData.lng);
                        getAreaName(lat, lng).then(areaName => {
                            // Create a new row for the table
                            const row = commentsTable.insertRow();
                            row.insertCell(0).textContent = areaName; // General area name
                            row.insertCell(1).textContent = commentData.comment; // Comment text
                            row.insertCell(2).textContent = commentData.user; // Display the username
                            row.insertCell(3).textContent = new Date(commentData.created_at).toLocaleString(); // Date
                        });
                    });
                })
                .catch(error => console.error('Error loading comments:', error));

            // Add click event listener to the map
            map.addListener("click", (event) => {
                clickedLatLng = event.latLng;
                openModal();
            });
        }

        // Function to fetch area name using reverse geocoding
        function getAreaName(lat, lng) {
            return new Promise((resolve, reject) => {
                const geocoder = new google.maps.Geocoder();
                const latlng = { lat: lat, lng: lng };
                
                geocoder.geocode({ location: latlng }, (results, status) => {
                    if (status === "OK") {
                        if (results[0]) {
                            resolve(results[0].formatted_address); // Ensure this returns a valid area name
                        } else {
                            reject("No results found");
                        }
                    } else {
                        reject("Geocoder failed due to: " + status);
                    }
                });
            });
        }

        // Open the modal for adding comments
        function openModal() {
            document.getElementById("overlay").style.display = "block";
            document.getElementById("commentModal").style.display = "block";
        }

        // Close the modal
        function closeModal() {
            document.getElementById("overlay").style.display = "none";
            document.getElementById("commentModal").style.display = "none";
        }

        // Submit the comment
        function submitComment() {
            const comment = document.getElementById("commentText").value;

            if (comment) {
                // Get area name first
                getAreaName(clickedLatLng.lat(), clickedLatLng.lng()).then(areaName => {
                    // Send the comment data to add_comment.php using fetch
                    fetch('add_comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `lat=${clickedLatLng.lat()}&lng=${clickedLatLng.lng()}&comment=${encodeURIComponent(comment)}`,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Add the marker to the map with the new comment
                            const marker = new google.maps.Marker({
                                position: clickedLatLng,
                                map: map,
                            });

                            const infoWindow = new google.maps.InfoWindow({
                                content: comment,
                            });

                            marker.addListener("click", () => {
                                infoWindow.open(map, marker);
                            });

                            // Close the modal and clear the input field
                            closeModal();
                            document.getElementById("commentText").value = "";

                            // Add the new comment to the table with the area name and username
                            addCommentToTable(clickedLatLng.lat(), clickedLatLng.lng(), comment, new Date().toLocaleString(), areaName, '<?php echo $_SESSION["user"]; ?>');
                        } else {
                            alert("Error saving comment.");
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }).catch(err => {
                    console.error("Error getting area name:", err);
                });
            } else {
                alert("Please enter a comment.");
            }
        }

        // Function to add a comment to the table
        function addCommentToTable(lat, lng, comment, createdAt, areaName, user) {
            const tableRow = document.createElement("tr");
            tableRow.innerHTML = `
                <td>${areaName}</td>  <!-- Area Name -->
                <td>${comment}</td>
                <td>${user}</td>  <!-- Display Username -->
                <td>${createdAt}</td> <!-- Display creation time -->
            `;
            document.querySelector("#commentsTable tbody").appendChild(tableRow);
        }

    </script>

    <!-- Load the Google Maps API -->
    <script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAejIG8ajQJk01Nzl8cabksIJ4gnsE_DXQ&callback=initMap"></script>
</body>
</html>
