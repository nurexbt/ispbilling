<?php
include 'includes/db.php';
$page_title = 'KHAN ERP - Package Management';

// Auto-create table if not exists (workaround for CLI issue)
$checkTable = $conn->query("SHOW TABLES LIKE 'packages'");
if ($checkTable->num_rows == 0) {
    $sql = "CREATE TABLE packages (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        router_id INT(6) UNSIGNED,
        original_name VARCHAR(255) NOT NULL,
        given_name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) DEFAULT 0.00,
        status VARCHAR(50) DEFAULT 'enabled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (router_id) REFERENCES routers(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
}

include 'includes/header.php';
?>

<div class="card mb-4" style="background-color: var(--color-lightinfo); border: none; border-radius: 12px;">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold text-dark" style="font-size: 1.1rem;">Package Service</h5>
                <div class="small text-muted" style="font-size: 0.8rem;">
                    <a href="index.php" class="text-decoration-none text-muted">Dashboard</a> 
                    <span class="mx-1">•</span> 
                    Package Services
                </div>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#syncPackageModal" style="border-radius: 6px; font-weight: 500;">
                <i class="fas fa-sync me-1"></i> Sync Package
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
                        <th>Given Name <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>Original Profile Name <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>Mikrotik Router <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>Price <i class="fas fa-sort text-muted small ms-1"></i></th>
                        <th>Status <i class="fas fa-sort text-muted small ms-1"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT p.*, r.name as router_name FROM packages p LEFT JOIN routers r ON p.router_id = r.id ORDER BY p.id DESC";
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
                                            <li><a class='dropdown-item edit-package-btn' href='#' data-id='" . $row['id'] . "'><i class='fas fa-edit text-primary me-2'></i> Edit</a></li>
                                            <li><a class='dropdown-item delete-package-btn text-danger' href='#' data-id='" . $row['id'] . "'><i class='fas fa-trash-alt me-2'></i> Delete</a></li>
                                        </ul>
                                    </div>
                                  </td>";
                            echo "<td>" . $row['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['given_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['original_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['router_name'] ?? 'Unknown') . "</td>";
                            echo "<td>" . number_format($row['price'], 2) . "</td>";
                            echo "<td><span class='badge bg-success'>Enabled</span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center py-4 text-muted'>No packages found</td></tr>";
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

<!-- Sync Package Modal -->
<div class="modal fade" id="syncPackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Sync Packages from Mikrotik</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <label for="syncRouterSelect" class="form-label">Select Mikrotik Router</label>
                    <div class="input-group">
                        <select class="form-select" id="syncRouterSelect">
                            <option value="">Select a router...</option>
                            <?php
                            $routers = $conn->query("SELECT id, name, ip_address FROM routers");
                            while($r = $routers->fetch_assoc()) {
                                echo "<option value='" . $r['id'] . "'>" . $r['name'] . " (" . $r['ip_address'] . ")</option>";
                            }
                            ?>
                        </select>
                        <button class="btn btn-primary" type="button" id="btnFetchProfiles">
                            <i class="fas fa-sync me-1"></i> Sync Profiles
                        </button>
                    </div>
                </div>
                
                <div id="profilesListArea" class="d-none">
                    <h6 class="fw-bold mb-3">Available PPP Profiles</h6>
                    <form id="syncProfilesForm">
                        <input type="hidden" name="router_id" id="selectedRouterId">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="40" class="text-center"><input type="checkbox" id="selectAllProfiles"></th>
                                        <th>Original Profile Name</th>
                                        <th>Given Name (Editable)</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody id="profilesTableBody">
                                    <!-- Profiles will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success disabled" id="btnSavePackages">Save Selected Packages</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Package Modal -->
<div class="modal fade" id="editPackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editPackageForm">
                    <input type="hidden" id="editPackageId" name="id">
                    <div class="mb-3">
                        <label class="form-label">Original Profile Name</label>
                        <input type="text" class="form-control" id="editOriginalName" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label for="editGivenName" class="form-label">Given Name</label>
                        <input type="text" class="form-control" id="editGivenName" name="given_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPrice" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="editPrice" name="price" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updatePackageBtn">Update</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Package Modal -->
<div class="modal fade" id="deletePackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger">Delete Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this package? This action cannot be undone.</p>
                <input type="hidden" id="deletePackageId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeletePackageBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Edit Package
    $(document).on('click', '.edit-package-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        
        $.ajax({
            url: 'api/get_package.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editPackageId').val(response.data.id);
                    $('#editOriginalName').val(response.data.original_name);
                    $('#editGivenName').val(response.data.given_name);
                    $('#editPrice').val(response.data.price);
                    $('#editPackageModal').modal('show');
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    $('#updatePackageBtn').click(function() {
        $.ajax({
            url: 'api/update_package.php',
            type: 'POST',
            data: $('#editPackageForm').serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    toastr.success(response.message);
                    $('#editPackageModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Delete Package
    $(document).on('click', '.delete-package-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        $('#deletePackageId').val(id);
        $('#deletePackageModal').modal('show');
    });

    $('#confirmDeletePackageBtn').click(function() {
        const id = $('#deletePackageId').val();
        $.ajax({
            url: 'api/delete_package.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    toastr.success(response.message);
                    $('#deletePackageModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Fetch Profiles
    $('#btnFetchProfiles').click(function() {
        const routerId = $('#syncRouterSelect').val();
        if(!routerId) {
            toastr.warning('Please select a router first');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Syncing...');
        
        $.ajax({
            url: 'api/sync_profiles.php',
            type: 'GET',
            data: { router_id: routerId },
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fas fa-sync me-1"></i> Sync Profiles');
                
                if(response.success) {
                    $('#selectedRouterId').val(routerId);
                    $('#profilesTableBody').empty();
                    
                    if(response.data.length > 0) {
                        response.data.forEach(function(profile, index) {
                            const row = `
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input profile-check" name="profiles[${index}][selected]" value="1">
                                        <input type="hidden" name="profiles[${index}][original_name]" value="${profile.name}">
                                    </td>
                                    <td>${profile.name}</td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="profiles[${index}][given_name]" value="${profile.name}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control form-control-sm" name="profiles[${index}][price]" value="0.00">
                                    </td>
                                </tr>
                            `;
                            $('#profilesTableBody').append(row);
                        });
                        $('#profilesListArea').removeClass('d-none');
                        $('#btnSavePackages').removeClass('disabled');
                    } else {
                        toastr.info('No profiles found on this router');
                        $('#profilesListArea').addClass('d-none');
                    }
                } else {
                    toastr.error(response.message, 'Sync Failed');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync me-1"></i> Sync Profiles');
                toastr.error('Failed to connect to API', 'Error');
            }
        });
    });

    // Select All Checkbox
    $('#selectAllProfiles').change(function() {
        $('.profile-check').prop('checked', $(this).prop('checked'));
    });

    // Save Packages
    $('#btnSavePackages').click(function() {
        if($('.profile-check:checked').length === 0) {
            toastr.warning('Please select at least one profile to save');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        $.ajax({
            url: 'api/save_packages.php',
            type: 'POST',
            data: $('#syncProfilesForm').serialize(),
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html('Save Selected Packages');
                if(response.success) {
                    toastr.success(response.message);
                    $('#syncPackageModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html('Save Selected Packages');
                toastr.error('System Error');
            }
        });
    });
});
</script>
