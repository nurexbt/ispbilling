<?php
include 'includes/db.php';
$page_title = 'KHAN ERP - Host Management';
include 'includes/header.php';
?>

<div class="card mb-4" style="background-color: var(--color-lightinfo); border: none; border-radius: 12px;">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold text-dark" style="font-size: 1.1rem;">Host Service</h5>
                <div class="small text-muted" style="font-size: 0.8rem;">
                    <a href="index.php" class="text-decoration-none text-muted">Dashboard</a> 
                    <span class="mx-1">•</span> 
                    Host Services
                </div>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHostModal" style="border-radius: 6px; font-weight: 500;">
                <i class="fas fa-plus me-1"></i> Add Host Service
            </button>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <input type="text" class="form-control" placeholder="Search..." style="width: 250px;">
            <div class="d-flex align-items-center">
                <span class="me-2 text-muted small">Show:</span>
                <select class="form-select form-select-sm me-2" style="width: 70px;">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option selected>100</option>
                    <option>All</option>
                </select>
                <span class="me-3 text-muted small">entries</span>
                <button class="btn btn-outline-secondary btn-sm">Columns <i class="fas fa-chevron-down ms-1"></i></button>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 50px;">Actions</th>
                        <th>ID <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>Name <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>Real Name <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>Host IP <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>API Port <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>Online Status <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>Enabled</th>
                    </tr>
                </thead>
                <tbody id="routerTableBody">
                    <?php
                    // Check if table exists first to avoid error if install.php wasn't run
                    $checkTable = $conn->query("SHOW TABLES LIKE 'routers'");
                    if ($checkTable && $checkTable->num_rows > 0) {
                        $sql = "SELECT * FROM routers ORDER BY id DESC";
                        $result = $conn->query($sql);

                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>
                                        <div class='dropdown' style='position: static;'>
                                            <button class='btn btn-sm btn-link text-muted' type='button' data-bs-toggle='dropdown' aria-expanded='false' data-bs-boundary='viewport'>
                                                <i class='fas fa-ellipsis-h'></i>
                                            </button>
                                            <ul class='dropdown-menu dropdown-menu-end shadow border-0' style='border-radius: 8px;'>
                                                <li><h6 class='dropdown-header fw-bold text-dark'>Actions</h6></li>
                                                <li><a class='dropdown-item edit-router-btn' href='#' data-id='" . $row['id'] . "'><i class='fas fa-edit text-primary me-2'></i> Edit</a></li>
                                                <li><a class='dropdown-item delete-router-btn text-danger' href='#' data-id='" . $row['id'] . "'><i class='fas fa-trash-alt me-2'></i> Delete</a></li>
                                            </ul>
                                        </div>
                                      </td>";
                                echo "<td>" . $row['id'] . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>"; 
                                echo "<td>" . htmlspecialchars($row['ip_address']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['api_port']) . "</td>";
                                echo "<td><span class='badge badge-status bg-secondary router-status' data-id='" . $row['id'] . "'>Checking...</span></td>";
                                $checked = $row['status'] == 'enabled' ? 'checked' : '';
                                echo "<td>
                                        <div class='form-check form-switch'>
                                            <input class='form-check-input' type='checkbox' $checked>
                                        </div>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center py-4 text-muted'>No hosts found</td></tr>";
                        }
                    } else {
                         echo "<tr><td colspan='8' class='text-center py-4 text-danger'>Table 'routers' not found. Please run install.php</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body p-3 d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            Showing 1 to <?php echo isset($result) ? $result->num_rows : 0; ?> of <?php echo isset($result) ? $result->num_rows : 0; ?> entries
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item disabled"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- Add Host Modal -->
<div class="modal fade" id="addHostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add New Host</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addHostForm">
                    <div class="mb-3">
                        <label for="deviceName" class="form-label">Device Name / System Identity</label>
                        <input type="text" class="form-control" id="deviceName" name="deviceName" required>
                        <div class="form-text">Must match the Mikrotik System Identity exactly.</div>
                    </div>
                    <div class="mb-3">
                        <label for="hostIp" class="form-label">Host IP Address</label>
                        <input type="text" class="form-control" id="hostIp" name="hostIp" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="apiPort" class="form-label">API Port</label>
                            <input type="number" class="form-control" id="apiPort" name="apiPort" value="8728" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apiUser" class="form-label">API Username</label>
                            <input type="text" class="form-control" id="apiUser" name="apiUser" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="apiPass" class="form-label">API Password</label>
                        <input type="password" class="form-control" id="apiPass" name="apiPass">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveHostBtn">
                    <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                    Connect & Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Host Modal -->
<div class="modal fade" id="editHostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Host</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editHostForm">
                    <input type="hidden" id="editRouterId" name="id">
                    <div class="mb-3">
                        <label for="editDeviceName" class="form-label">Device Name / System Identity</label>
                        <input type="text" class="form-control" id="editDeviceName" name="deviceName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editHostIp" class="form-label">Host IP Address</label>
                        <input type="text" class="form-control" id="editHostIp" name="hostIp" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editApiPort" class="form-label">API Port</label>
                            <input type="number" class="form-control" id="editApiPort" name="apiPort" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editApiUser" class="form-label">API Username</label>
                            <input type="text" class="form-control" id="editApiUser" name="apiUser" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editApiPass" class="form-label">API Password</label>
                        <input type="password" class="form-control" id="editApiPass" name="apiPass" placeholder="Leave blank to keep current">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateHostBtn">
                    <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                    Update & Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteHostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger">Delete Host</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this host? This action cannot be undone.</p>
                <input type="hidden" id="deleteRouterId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Check Status for each router
    $('.router-status').each(function() {
        const span = $(this);
        const id = span.data('id');
        
        $.ajax({
            url: 'api/check_status.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'online') {
                    span.removeClass('bg-secondary').addClass('bg-success').text('Online');
                } else {
                    span.removeClass('bg-secondary').addClass('bg-danger').text('Offline');
                }
            },
            error: function() {
                span.removeClass('bg-secondary').addClass('bg-danger').text('Error');
            }
        });
    });

    $('#saveHostBtn').click(function() {
        const btn = $(this);
        const spinner = btn.find('.spinner-border');
        const form = $('#addHostForm');
        
        // Basic validation
        if(!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }

        const data = {
            name: $('#deviceName').val(),
            ip: $('#hostIp').val(),
            port: $('#apiPort').val(),
            user: $('#apiUser').val(),
            pass: $('#apiPass').val()
        };

        // UI Loading State
        btn.prop('disabled', true);
        spinner.removeClass('d-none');
        toastr.info('Connecting to Router...', 'Please Wait');

        $.ajax({
            url: 'api/add_router.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');

                if (response.success) {
                    toastr.success(response.message, 'Success');
                    $('#addHostModal').modal('hide');
                    form[0].reset();
                    // Reload page to show new entry
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                let msg = 'System error occurred.';
                if (xhr.responseText) {
                    try {
                        let json = JSON.parse(xhr.responseText);
                        if(json.message) msg = json.message;
                    } catch(e) {}
                }
                toastr.error(msg, 'Error');
                console.error(error);
            }
        });
    });

    // Handle Edit Button Click
    $(document).on('click', '.edit-router-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        
        $.ajax({
            url: 'api/get_router.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    const data = response.data;
                    $('#editRouterId').val(data.id);
                    $('#editDeviceName').val(data.name);
                    $('#editHostIp').val(data.ip_address);
                    $('#editApiPort').val(data.api_port);
                    $('#editApiUser').val(data.username);
                    $('#editApiPass').val(''); // Clear password field
                    $('#editHostModal').modal('show');
                } else {
                    toastr.error(response.message, 'Error fetching data');
                }
            },
            error: function() {
                toastr.error('Failed to fetch router details', 'Error');
            }
        });
    });

    // Handle Update Button Click
    $('#updateHostBtn').click(function() {
        const btn = $(this);
        const spinner = btn.find('.spinner-border');
        const form = $('#editHostForm');

        if(!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }

        const data = {
            id: $('#editRouterId').val(),
            name: $('#editDeviceName').val(),
            ip: $('#editHostIp').val(),
            port: $('#editApiPort').val(),
            user: $('#editApiUser').val(),
            pass: $('#editApiPass').val()
        };

        btn.prop('disabled', true);
        spinner.removeClass('d-none');
        toastr.info('Updating and Verifying...', 'Please Wait');

        $.ajax({
            url: 'api/update_router.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');

                if (response.success) {
                    toastr.success(response.message, 'Success');
                    $('#editHostModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                let msg = 'System error occurred.';
                try {
                    let json = JSON.parse(xhr.responseText);
                    if(json.message) msg = json.message;
                } catch(e) {}
                toastr.error(msg, 'Error');
            }
        });
    });

    // Handle Delete Button Click
    $(document).on('click', '.delete-router-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        $('#deleteRouterId').val(id);
        $('#deleteHostModal').modal('show');
    });

    // Confirm Delete
    $('#confirmDeleteBtn').click(function() {
        const id = $('#deleteRouterId').val();
        const btn = $(this);
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: 'api/delete_router.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Deleted');
                    $('#deleteHostModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message, 'Error');
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                toastr.error('Failed to delete', 'Error');
                btn.prop('disabled', false);
            }
        });
    });

    // Fix for Bootstrap 5 dropdowns in responsive tables
    $('.table-responsive').on('show.bs.dropdown', function () {
        $(this).css("overflow", "inherit");
    });

    $('.table-responsive').on('hide.bs.dropdown', function () {
        $(this).css("overflow", "auto");
    });
});
</script>