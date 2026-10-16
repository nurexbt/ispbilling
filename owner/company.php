<?php
require_once '../includes/db.php';
$base_path = '../';
$is_owner = true;
$is_manager = true;
$active_menu = 'company';
$page_title = 'Company Profile';
$page_subtitle = 'Manage your company settings';
include '../includes/header.php';


include 'includes/sidebar.php';

// Fetch existing company info
$company = [
    'company_name' => '',
    'phone' => '',
    'email' => '',
    'address' => '',
    'website_url' => '',
    'logo_path' => ''
];

$res = $conn->query("SELECT * FROM company_tbl LIMIT 1");
if ($res && $res->num_rows > 0) {
    $company = $res->fetch_assoc();
}
?>

<div class="container-fluid pt-2">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="dashboard-card mt-3">
                <form id="companyForm" enctype="multipart/form-data">
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-3 text-center">
                            <div class="position-relative d-inline-block">
                                <img id="logoPreview" src="<?php echo !empty($company['logo_path']) ? $base_path . $company['logo_path'] : $base_path . 'assets/images/default-logo.png'; ?>" alt="Company Logo" class="rounded-circle border border-slate-200 shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                                <?php if (has_permission('company_edit')): ?>
                                <label for="logoUpload" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; cursor: pointer; border: 2px solid white; transform: translate(25%, 25%);">
                                    <i class="bi bi-camera" style="font-size: 14px;"></i>
                                </label>
                                <input type="file" id="logoUpload" name="logo" class="d-none" accept="image/*">
                                <?php endif; ?>
                            </div>
                            <?php if (has_permission('company_edit')): ?>
                            <div class="mt-2 text-slate-500" style="font-size: 11px;">Upload Logo</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <h5 class="text-slate-800 fw-bold mb-1">Company Details</h5>
                            <p class="text-slate-500 mb-0" style="font-size: 12px;">Update your company information. This will be displayed on invoices and reports.</p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-slate-700" style="font-size: 13px; font-weight: 500;">Company Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-slate-500 border-slate-200"><i class="bi bi-building"></i></span>
                                <input type="text" name="company_name" class="form-control form-control-sm shadow-none border-slate-200" value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-slate-700" style="font-size: 13px; font-weight: 500;">Phone Number</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-slate-500 border-slate-200"><i class="bi bi-telephone"></i></span>
                                <input type="text" name="phone" class="form-control form-control-sm shadow-none border-slate-200" value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-slate-700" style="font-size: 13px; font-weight: 500;">Email Address</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-slate-500 border-slate-200"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control form-control-sm shadow-none border-slate-200" value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-slate-700" style="font-size: 13px; font-weight: 500;">Website URL</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-slate-500 border-slate-200"><i class="bi bi-globe"></i></span>
                                <input type="url" name="website_url" class="form-control form-control-sm shadow-none border-slate-200" placeholder="https://www.example.com" value="<?php echo htmlspecialchars($company['website_url'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-slate-700" style="font-size: 13px; font-weight: 500;">Physical Address</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-slate-500 border-slate-200"><i class="bi bi-geo-alt"></i></span>
                                <textarea name="address" class="form-control shadow-none border-slate-200" rows="3" style="font-size: 13px;"><?php echo htmlspecialchars($company['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top border-slate-100 text-end">
                        <?php if (has_permission('company_edit')): ?>
                        <button type="submit" class="btn btn-primary shadow-sm px-4" style="font-size: 13px; font-weight: 500;"><i class="bi bi-check-circle me-2"></i>Save Changes</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Logo Preview
    $('#logoUpload').change(function(e) {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#logoPreview').attr('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Form Submission
    $('#companyForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var $btn = $(this).find('button[type="submit"]');
        var originalBtnText = $btn.html();
        
        $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...').prop('disabled', true);

        $.ajax({
            url: '../ajax/save_company.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $btn.html(originalBtnText).prop('disabled', false);
                if (response.status === 'success') {
                    showToast('success', response.message);
                    
                    // Update sidebar dynamically
                    var newName = $('input[name="company_name"]').val();
                    $('#sidebarCompanyName').text(newName).attr('title', newName);
                    
                    var newFontSize = '0.95rem';
                    if (newName.length > 25) {
                        newFontSize = '0.70rem';
                    } else if (newName.length > 15) {
                        newFontSize = '0.80rem';
                    }
                    $('#sidebarCompanyName').css('font-size', newFontSize);
                    
                    var newLogoSrc = $('#logoPreview').attr('src');
                    // Check if logo is not the default
                    if (newLogoSrc.indexOf('default-logo.png') === -1) {
                        if ($('#sidebarLogoImg').length) {
                            $('#sidebarLogoImg').attr('src', newLogoSrc);
                        } else {
                            $('#sidebarLogoText').replaceWith('<div class="sidebar-logo-box me-3 shadow-sm bg-white p-1" style="overflow: hidden;"><img id="sidebarLogoImg" src="' + newLogoSrc + '" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;"></div>');
                        }
                    } else {
                        // If it's default, we might want to revert to initials, but for simplicity, we leave it.
                    }
                } else {
                    showToast('error', response.message);
                }
            },
            error: function() {
                $btn.html(originalBtnText).prop('disabled', false);
                showToast('error', 'An error occurred while saving.');
            }
        });
    });
});
</script>
</body>
</html>
