<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification with Clickable Icon Dropdown</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-xxx" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>


    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .notification {
            position: relative;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px;
            cursor: pointer;
            transition: box-shadow 0.3s, transform 0.3s;
            width: 300px;
            text-align: center;
        }

        .notification:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(-3px);
        }

        .notification .icon {
            margin-right: 10px;
            font-size: 20px;
            color: #555;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            width: calc(100% - 32px);
            /* 32px accounts for padding and border width */
            margin-top: 10px;
            /* Adjust as needed */
            left: 0;
        }

        .dropdown-content a {
            color: #333;
            padding: 10px 15px;
            display: block;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .dropdown-content a:hover {
            background-color: #f0f0f0;
        }

        .notification.active .dropdown-content {
            display: block;
        }

        .dropdown-content .dropdown-menu-footer button {
            width: 100%;
        }
    </style>
</head>

<body>

    <div class="notification" onclick="toggleDropdown(this)">
        <i class='bx bx-bell'></i>
        Notification
        <div class="dropdown-content" id="notificationDropdown">
            <ul class="list-group list-group-flush" id="notification-list">
                <!-- Notifications will be dynamically populated here by JavaScript -->
            </ul>
            <div class="dropdown-menu-footer border-top p-3">
                <button class="btn btn-primary text-uppercase">View All Notifications</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js" integrity="sha512-xxx" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
    // Function to toggle the dropdown visibility
    function toggleDropdown(notification) {
        notification.classList.toggle('active');
        if (notification.classList.contains('active')) {
            fetchNotifications(); // Fetch notifications when dropdown is activated
        }
    }

    // Function to fetch notifications from the server
    async function fetchNotifications() {
        try {
            const response = await fetch('get_notifications.php'); // Adjust URL as needed
            if (!response.ok) {
                throw new Error('Failed to fetch notifications');
            }
            const notifications = await response.json(); // Parse JSON response
            populateNotifications(notifications); // Populate notifications in the UI
        } catch (error) {
            console.error('Error fetching notifications:', error.message);
        }
    }

    // Function to populate notifications in the UI
    function populateNotifications(notifications) {
        const notificationList = document.getElementById('notification-list');
        notificationList.innerHTML = ''; // Clear existing notifications

        // Iterate over each notification and create list items
        notifications.forEach(notification => {
            const listItem = document.createElement('li');
            listItem.classList.add('list-group-item', 'list-group-item-action', 'dropdown-notifications-item');
            listItem.innerHTML = `
                <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                        <i class="bx bx-bell"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">ID: ${notification.ID}</h5>
                        <p class="mb-1">Document: <a href="${notification.document}" target="_blank">View PDF</a></p>
                        <p class="mb-0">Name Of Give: ${notification.NameOfgive}</p>
                    </div>
                </div>
            `;
            notificationList.appendChild(listItem); // Append each notification to the list
        });
    }
</script>



    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>

</body>

</html>