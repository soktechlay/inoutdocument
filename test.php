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
<button class="btn p-0" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-html="true" title="កែប្រែ">
    <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#update<?php echo htmlentities($result->id ?? ''); ?>" data-id="<?php echo htmlentities($result->id ?? ''); ?>">
        <i class="bx bx-edit-alt"></i>
    </a>
</button>
<!-- Update Modal -->
<div class="modal fade" id="update<?php echo htmlentities($result->id ?? ''); ?>" tabindex="-1" aria-labelledby="updateModalLabel<?php echo htmlentities($result->id ?? ''); ?>" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-simple modal-edit-user">
        <div class="modal-content p-3 p-md-5">
            <div class="modal-body">

                <div class="text-center mb-4">
                    <h3 class="mef2">កែប្រែគណនី</h3>
                </div>
                <form id="formAuthentication" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="login_type" value="updateuser">

                    <!-- User Avatar and Upload -->
                    <div class="d-flex flex-column align-items-center ">
                        <img src="<?php echo htmlentities($result->Profile ?? ''); ?>" alt="user image" id="profileImgPreview" class="img-fluid rounded-4 profile-image" style="cursor: pointer; object-fit: contain; width: 250px; height: 250px">
                        <div class="button-wrapper">
                            <label for="upload" class="btn btn-primary  " tabindex="0">
                                <span class="d-none d-sm-block"><i class="bx bx-photo-album"></i> ប្តូររូបភាព</span>
                                <i class="bx bx-upload d-block d-sm-none"></i>
                                <input type="file" id="upload" name="profile" class="account-file-input" hidden accept="image/png, image/jpeg" />
                            </label>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="honorific1" class="form-label">គោរមងារ<span class="text-danger fw-bolder">*</span></label>
                            <select id="honorific1" class="select2 form-select" name="honorific">
                                <option value="<?php echo htmlentities($result->Honorific ?? ''); ?>"><?php echo htmlentities($result->Honorific ?? ''); ?></option>
                                <option value="ឯកឧត្តម">ឯកឧត្តម</option>
                                <option value="លោកជំទាវ">លោកជំទាវ</option>
                                <option value="លោក">លោក</option>
                                <option value="លោកស្រី">លោកស្រី</option>
                                <option value="អ្នកនាង">អ្នកនាង</option>
                                <option value="កញ្ញា">កញ្ញា</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="firstname1">គោត្តនាម<span class="text-danger fw-bolder">*</span></label>
                            <input type="text" id="firstname1" name="firstname" class="form-control" value="<?php echo htmlentities($result->FirstName ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="lastname1">នាម<span class="text-danger fw-bolder">*</span></label>
                            <input type="text" id="lastname1" name="lastname" class="form-control" value="<?php echo htmlentities($result->LastName ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">ភេទ<span class="text-danger fw-bolder">*</span></label>
                            <div class="d-flex">
                                <div class="btn-group w-100" role="group" aria-label="Basic radio toggle button group">
                                    <?php
                                    $gender = htmlentities($result->Grender ?? ''); // Retrieve and sanitize the gender value
                                    ?>
                                    <input type="radio" value="ស្រី" class="btn-check" name="gender" id="gender3" <?php echo ($gender == 'ស្រី') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="gender1">ស្រី</label>

                                    <input type="radio" value="ប្រុស" class="btn-check" name="gender" id="gender4" <?php echo ($gender == 'ប្រុស') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="gender2">ប្រុស</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="contact1">លេខទូរស័ព្ទ<span class="text-danger fw-bolder">*</span></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text">+855</span>
                                <input type="text" id="contact1" name="contact" class="form-control phone-number-mask" value="<?php echo htmlentities($result->Contact ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Login Information -->
                    <div class="row mb-3">
                        <!-- <div class="col-md-6">
                            <label class="form-label" for="username">ឈ្មោះមន្ត្រី<span class="text-danger fw-bolder">*</span></label>
                            <div class="alert alert-warning alert-dismissible" role="alert">
                                <h6 class="text-warning fw-bolder">ចំណាំៈ</h6>
                                <p class="mb-0">ឈ្មោះមន្ត្រីប្រើសម្រាប់ធ្វើការ Login ចូលប្រើប្រាស់ប្រព័ន្ធ។</p>
                            </div>
                            <input type="text" id="username" name="username" class="form-control" placeholder="ឈ្មោះមន្ត្រី" >
                        </div> -->
                        <div class="col-md-6">
                            <label class="form-label" for="email1">Email<span class="text-danger fw-bolder">*</span></label>
                            <input type="email" id="email1" name="email" class="form-control" placeholder="example@gmail.com">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="password1">ពាក្យសម្ងាត់<span class="text-danger fw-bolder">*</span></label>
                            <input type="password" id="password1" name="password" class="form-control" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="status1">ស្ថានភាពគណនី<span class="text-danger fw-bolder">*</span></label>
                            <select id="status" name="status" class="select2 form-select">
                                <option value="">ជ្រើសរើស</option>
                                <option value="1">សកម្ម</option>
                                <option value="0">អសកម្ម</option>
                            </select>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="dob1">ថ្ងៃខែឆ្នាំកំណើត<span class="text-danger fw-bolder">*</span></label>
                            <input type="text" id="dob1" name="dob" class="form-control" placeholder="ថ្ងៃខែឆ្នាំកំណើត">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="department1">នាយកដ្ឋាន<span class="text-danger fw-bolder">*</span></label>
                            <select id="department1" name="department" class="select2 form-select">
                                <option value="">ជ្រើសរើស</option>
                                <?php
                                $sql = "SELECT * FROM tbldepartments";
                                $query = $dbh->prepare($sql);
                                $query->execute();
                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                if ($query->rowCount() > 0) {
                                    foreach ($results as $result) { ?>
                                        <option value="<?php echo htmlentities($result->id) ?>">
                                            <?php echo htmlentities($result->DepartmentName) ?>
                                        </option>
                                <?php }
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="office1">ការិយាល័យ<span class="text-danger fw-bolder">*</span></label>
                            <select id="office1" name="office" class="select2 form-select">
                                <option value="">ជ្រើសរើស</option>
                                <?php
                                $sql = "SELECT * FROM tbloffices";
                                $query = $dbh->prepare($sql);
                                $query->execute();
                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                if ($query->rowCount() > 0) {
                                    foreach ($results as $result) { ?>
                                        <option value="<?php echo htmlentities($result->id) ?>">
                                            <?php echo htmlentities($result->OfficeName) ?>
                                        </option>
                                <?php }
                                } ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="role1">តួនាទី</label>
                            <select id="role1" name="role" class="select2 form-select">
                                <option value="">ជ្រើសរើស</option>
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="updateuser" class="btn btn-primary">យល់ព្រម</button>
                        <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">បិទ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</html>