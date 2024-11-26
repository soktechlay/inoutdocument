<div class="row mb-3">
                        <div class="mb-3 col-md-6">
                          <label for="permission" class="form-label">Permission</label>
                          <div class="input-group input-group-merge">
                            <span id="basic-icon-default-company2" class="input-group-text"><i
                                class='bx bx-user'></i></span>
                            <select name="permission" id="permission" class="select2 form-select select2-hidden-accessible">
                              <option value="<?php echo htmlentities($result->PermissionId ?? ''); ?>">
                                <?php echo htmlentities($result->PermissionId ?? ''); ?>
                              </option>
                              <?php
                              $sql = "SELECT * FROM permissions";
                              $query = $dbh->prepare($sql);
                              $query->execute();
                              $permissions = $query->fetchAll(PDO::FETCH_OBJ);
                              if ($query->rowCount() > 0) {
                                foreach ($permissions as $permission) {
                                  ?>
                                  <option value="<?php echo htmlentities($permission->id); ?>">
                                    <?php echo htmlentities($permission->name); ?>
                                  </option>
                                <?php }
                              } ?>
                            </select>
                          </div>
                        </div>

                      </div>

                      <!-- <div class="row mb-3">
                        <label for="permission" class="form-label">Permission</label>
                        <select name="permission" id="" class="form-control select2 form-select" multiple>
                        <?php
                          // Parse PermissionId into an array
                          $selectedPermissions = isset($result->PermissionId)
                            ? explode(',', $result->PermissionId)
                            : []; // Assuming it's a comma-separated string
                      
                          // Fetch all permissions
                          $sql = "SELECT * FROM permissions";
                          $query = $dbh->prepare($sql);
                          $query->execute();
                          $permissions = $query->fetchAll(PDO::FETCH_OBJ);

                          if ($query->rowCount() > 0) {
                            foreach ($permissions as $permission) {
                              // Check if the current permission is in the selectedPermissions array
                              $isSelected = in_array($permission->id, $selectedPermissions) ? 'selected' : '';
                              ?>
                              <option name="permission" value="<?php echo htmlentities($permission->id); ?>" <?php echo $isSelected; ?>>
                                <?php echo htmlentities($permission->name); ?>
                              </option>
                            <?php }
                          } ?>
                        </select>
                      </div> -->
                      <?php
                      $sql = "SELECT 
            i.*, 
            u.FirstName AS firstname, 
            u.LastName AS lastname, 
            i.NameRecipient AS username, 
            COALESCE(d.DepartmentName, i.DepartmentReceive) AS department_display_name, -- Use text if no match
            i.DepartmentReceive,
            d.id AS department_id
        FROM indocument i
        JOIN tbluser u ON i.user_id = u.ID
        LEFT JOIN tbldepartments d ON i.DepartmentReceive = d.id
        WHERE i.isdelete = 0
          AND i.Department = 1
          AND i.user_id = :userId"
          . $searchCondition
          . $dateCondition
          . " ORDER BY i.Date DESC"; ?>

<form method="POST" enctype="multipart/form-data">
        <div class="card-body mb-3">
            <input type="hidden" name="userid" value="<?php echo htmlspecialchars($_SESSION['userid']); ?>">
            <div class="row mt-2">
                <div class="col mb-3">
                    <label for="burden" class="form-label">បញ្ជូនទៅមន្រ្តីទទួលបន្ទុកបន្ត</label>
                    <select name="burden[]" id="burden" class="form-select select2 form-control" multiple required>
                        <option value="">ជ្រើសរើស...</option>
                        <?php
                        // SQL query to fetch user IDs and names based on specific criteria
                        $sql = "SELECT ID, CONCAT(FirstName, ' ', LastName) AS FullName FROM tbluser";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                        if ($query->rowCount() > 0) {
                            foreach ($results as $result) { ?>
                                <option value="<?php echo htmlspecialchars($result->ID); ?>">
                                    <?php echo htmlspecialchars($result->FullName); ?>
                                </option>
                            <?php }
                        } else { ?>
                            <option value="" disabled>User not found</option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col mb-3">
                    <label class="form-label">នាយកដ្ឋានទទួលបន្ទុក</label>
                    <select class="custom-select form-control select2 form-select" name="department[]" multiple
                        required>
                        <option value="">ជ្រើសរើស...</option>
                        <?php
                        $sql = "SELECT id, DepartmentName FROM tbldepartments"; // Include the 'id' column
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                        if ($query->rowCount() > 0) {
                            foreach ($results as $result) { ?>
                                <option value="<?php echo htmlspecialchars($result->id); ?>"> <!-- Use 'id' as the value -->
                                    <?php echo htmlspecialchars($result->DepartmentName); ?> <!-- Display DepartmentName -->
                                </option>
                            <?php }
                        } ?>
                    </select>

                </div>
            </div>

            <div class="form-group mt-2">
                <div class="input-group input-file">
                    <input type="file" name="file2" class="form-control rounded-2" placeholder="Choose document..." />
                </div>
                <?php if (isset($error2)) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error2); ?>
                    </div>
                <?php } elseif (isset($success2)) { ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo htmlspecialchars($success2); ?>
                    </div>
                <?php } ?>
            </div>

            <?php if (!empty($documents)): ?>
                <div class="h6 mt-4">ឯកសារចំណារ ថ្មីៗ</div>
                <?php foreach ($documents as $document): ?>
                    <?php if (!empty($document['document'])): ?>
                        <div class="d-flex align-items-center justify-content-between bg-label-success p-2 rounded-3">
                            <a href="../../uploads/file/note-doc/<?php echo htmlspecialchars($document['document']); ?>"
                                target="_blank" class="btn-sm btn-link h6 mb-0">
                                <?php echo htmlspecialchars($document['document']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No documents found.</p>
            <?php endif; ?>

        </div>
        <div class="card-footer text-end">
            <button type="submit" name="submit" class="btn btn-primary ms-auto pull-right">បញ្ជូនឯកសារ</button>
        </div>
    </form>