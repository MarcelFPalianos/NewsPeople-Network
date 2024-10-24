
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcel Palianos Project</title>
    <link rel="stylesheet" href="style.css"> <!-- Linking the external CSS file -->
 
</head>
<body>

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
        </tr>
    </thead>
    <tbody>
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
                zoom: 8,
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
                row.insertCell(2).textContent = new Date(commentData.created_at).toLocaleString(); // Date
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
                    console.log("Geocoding results:", results); // Log results
                    let areaName = '';

                    // Loop through the results to find general area names
                    for (let component of results[0].address_components) {
                        // Check for administrative area (like city or neighborhood)
                        if (component.types.includes("locality") || component.types.includes("sublocality")) {
                            areaName = component.long_name;
                            break; // Stop after finding the first suitable name
                        }
                    }
                    
                    // If no locality was found, you could use the formatted_address as a fallback
                    if (!areaName) {
                        areaName = results[0].formatted_address; // Fallback to the full address
                    }
                    
                    resolve(areaName); // Resolve with the area name
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

                    // Add the new comment to the table with the area name
                    addCommentToTable(clickedLatLng.lat(), clickedLatLng.lng(), comment, new Date().toLocaleString(), areaName);
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

// Function to add a comment to the table
function addCommentToTable(lat, lng, comment, createdAt, areaName) {
    const tableRow = document.createElement("tr");
    tableRow.innerHTML = `
        <td>${areaName}</td>  <!-- Area Name -->
        <td>${comment}</td>
        <td>${createdAt}</td> <!-- Display creation time -->
    `;
    document.querySelector("#commentsTable tbody").appendChild(tableRow);
}

    </script>

    <!-- Load the Google Maps API -->
    <script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAejIG8ajQJk01Nzl8cabksIJ4gnsE_DXQ&callback=initMap"></script>
</body>
</html>
