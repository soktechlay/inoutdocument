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