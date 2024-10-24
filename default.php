<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcel Palianos Project</title>
    <style>

        #map {
            height: 500px;
            width: 100%;
        }


        #commentModal {
            display: 
            position:
            top: 20%;
            left: 50%;
            transform: translate(-50%, -20%);
            padding: 15px;
            background-color:
            border: 1px #ccc;
            z-index: 1000;
            width: 300px;
        }

        #commentModal textarea {
            width: 100%;
        }

        #overlay {
            position: 
            display:
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        
        #commentsTable {
            width: 100%;
            border-collapse: 
            margin-top: 20px;
        }
        #commentsTable, #commentsTable th, #commentsTable td {
            border: 1px #ccc;
        }
        #commentsTable th, #commentsTable td {
            padding: 8px;
            text-align: left;
        }
        #commentsTable th {
            background-color: #f2f2f2;
        }
    </style>
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

    </tbody>
</table>



    <script>
        let map;
        let clickedLatLng;

     function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 51.5072, lng: 0.1276 }, 
                zoom: 8,
            });

fetch('get_comments.php')
    .then(response => response.json())
    .then(data => {
        const commentsTable = document.getElementById("commentsTable"); 
        commentsTable.innerHTML = ''; 
        
        data.forEach(commentData => {
            const lat = parseFloat(commentData.lat);
            const lng = parseFloat(commentData.lng);
            getAreaName(lat, lng).then(areaName => {
                
                const row = commentsTable.insertRow();
                row.insertCell(0).textContent = areaName; 
                row.insertCell(1).textContent = commentData.comment; 
                row.insertCell(2).textContent = new Date(commentData.created_at).toLocaleString(); 
            });
        });
    })
    .catch(error => console.error('Error loading comments:', error));


            map.addListener("click", (event) => {
                clickedLatLng = event.latLng;
                openModal();
            });
        }


function getAreaName(lat, lng) {
    return new Promise((resolve, reject) => {
        const geocoder = new google.maps.Geocoder();
        const latlng = { lat: lat, lng: lng };
        
        geocoder.geocode({ location: latlng }, (results, status) => {
            if (status === "OK") {
                if (results[0]) {
                    console.log("Geocoding results:", results); 
                    let areaName = '';


                    for (let component of results[0].address_components) {

                        if (component.types.includes("locality") || component.types.includes("sublocality")) {
                            areaName = component.long_name; 
                            break; 
                        }
                    }
                    

                    if (!areaName) {
                        areaName = results[0].formatted_address;
                    }
                    
                    resolve(areaName);
                } else {
                    reject("No results found");
                }
            } else {
                reject("Geocoder failed due to: " + status);
            }
        });
    });
}



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
