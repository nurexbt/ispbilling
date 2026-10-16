        </main>
    </div> <!-- end .main-container -->
</div> <!-- end .wrapper -->

<!-- jQuery (Required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>

<!-- Flatpickr (Date Picker) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- SweetAlert2 for Toast Notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
            popup: 'compact-toast',
            title: 'compact-toast-title',
            icon: 'compact-toast-icon'
        },
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    function showToast(type, message) {
        Toast.fire({
            icon: type, // 'success', 'error', 'warning', 'info'
            title: message
        });
    }
</script>

<?php
$employee_full_name = "System Admin";
if(isset($conn) && isset($_SESSION['employee_id']) && !empty($_SESSION['employee_id'])) {
    $emp_id = intval($_SESSION['employee_id']);
    $emp_res = $conn->query("SELECT full_name FROM employee_tbl WHERE id = $emp_id");
    if($emp_res && $emp_res->num_rows > 0) {
        $employee_full_name = $emp_res->fetch_assoc()['full_name'];
    }
}
?>
<!-- User Profile Modal -->
<div class="modal fade" id="userProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 850px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <button type="button" class="btn-close shadow-none position-absolute top-0 end-0 m-3 z-3 bg-white rounded-circle p-2" data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.8;"></button>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- Profile Info Column (Glassmorphism & Gradient) -->
                    <div class="col-md-5 text-white p-5 position-relative overflow-hidden d-flex flex-column" style="background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%); min-height: 500px;">
                        <!-- Decorative background elements -->
                        <div class="position-absolute" style="width: 250px; height: 250px; background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%); border-radius: 50%; top: -50px; right: -50px;"></div>
                        <div class="position-absolute" style="width: 150px; height: 150px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%); border-radius: 50%; bottom: 30px; left: -30px;"></div>
                        
                        <div class="text-center mb-5 mt-3 position-relative z-1">
                            <!-- Avatar -->
                            <div class="bg-white text-primary rounded-circle d-inline-flex justify-content-center align-items-center mb-3 shadow-lg" style="width: 100px; height: 100px; font-size: 40px; font-weight: 800; text-transform: uppercase; border: 4px solid rgba(255,255,255,0.3); transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                <?php echo substr($_SESSION['username'] ?? 'U', 0, 1); ?>
                            </div>
                            <h4 class="fw-bold mb-1" style="letter-spacing: 0.5px;"><?php echo htmlspecialchars($employee_full_name); ?></h4>
                            <div class="mt-2">
                                <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-semibold shadow-sm" style="font-size: 11px; letter-spacing: 0.5px;"><?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Role'); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex flex-column gap-3 mb-4 text-start position-relative z-1 p-3 rounded-4" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                            <div class="d-flex align-items-center px-2">
                                <div class="bg-white bg-opacity-25 rounded p-2 me-3 d-flex justify-content-center align-items-center" style="width: 36px; height: 36px;">
                                    <i class="bi bi-person-badge fs-5"></i>
                                </div>
                                <div>
                                    <small class="text-white-50 fw-bold d-block mb-0" style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px;">Username</small>
                                    <div class="text-white fw-semibold" style="font-size: 14px;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                            <hr class="border-white opacity-25 my-1">
                            <div class="d-flex align-items-center px-2">
                                <div class="bg-white bg-opacity-25 rounded p-2 me-3 d-flex justify-content-center align-items-center" style="width: 36px; height: 36px;">
                                    <i class="bi bi-hash fs-5"></i>
                                </div>
                                <div>
                                    <small class="text-white-50 fw-bold d-block mb-0" style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px;">User ID</small>
                                    <div class="text-white fw-semibold font-monospace" style="font-size: 14px;"><?php echo htmlspecialchars($_SESSION['user_id'] ?? '0000'); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <a href="<?php echo isset($base_path) ? $base_path : ''; ?>logout.php" class="btn btn-light w-100 fw-bold shadow-sm position-relative z-1 mt-auto text-danger" style="border-radius: 12px; font-size: 14px; padding: 14px 0; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 .125rem .25rem rgba(0,0,0,.075)'">
                            <i class="bi bi-power me-2"></i> Secure Sign Out
                        </a>
                    </div>
                    
                    <!-- Password Change Column -->
                    <div class="col-md-7 p-5 bg-white d-flex flex-column justify-content-center">
                        <div class="mb-5 text-center">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-shield-lock fs-3"></i>
                            </div>
                            <h4 class="fw-bold text-slate-800 mb-1">Account Security</h4>
                            <p class="text-slate-500 mb-0" style="font-size: 14px;">Update your portal access password.</p>
                        </div>
                        
                        <form id="changePasswordForm" class="px-md-4">
                            <div class="mb-4 form-floating">
                                <input type="password" name="current_password" class="form-control shadow-none bg-slate-50 border-slate-200" id="currentPass" required placeholder="Current Password" style="border-radius: 12px; height: 58px;">
                                <label for="currentPass" class="text-slate-500"><i class="bi bi-unlock me-2"></i>Current Password</label>
                            </div>
                            
                            <div class="mb-4 form-floating">
                                <input type="password" name="new_password" class="form-control shadow-none bg-slate-50 border-slate-200" id="newPass" required placeholder="New Password" style="border-radius: 12px; height: 58px;">
                                <label for="newPass" class="text-slate-500"><i class="bi bi-key me-2"></i>New Password</label>
                            </div>
                            
                            <div class="mb-5 form-floating">
                                <input type="password" name="confirm_password" class="form-control shadow-none bg-slate-50 border-slate-200" id="confirmPass" required placeholder="Confirm New Password" style="border-radius: 12px; height: 58px;">
                                <label for="confirmPass" class="text-slate-500"><i class="bi bi-key-fill me-2"></i>Confirm New Password</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm" id="btnUpdatePassword" style="border-radius: 12px; font-size: 16px; padding: 14px 0; background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%); border: none; transition: transform 0.2s; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3) !important;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                <i class="bi bi-check2-circle me-2 fs-5"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JS -->
<script src="<?php echo isset($base_path) ? $base_path : ''; ?>assets/js/main.js"></script>
<script>
$(document).ready(function() {
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnUpdatePassword');
        var originalText = btn.html();
        
        var newPass = $('input[name="new_password"]').val();
        var confPass = $('input[name="confirm_password"]').val();
        
        if (newPass !== confPass) {
            if (typeof showToast === 'function') showToast('error', 'New passwords do not match');
            else alert('New passwords do not match');
            return;
        }
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Updating...');
        
        $.ajax({
            url: '<?php echo isset($base_path) ? $base_path : ""; ?>ajax/change_password.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    if (typeof showToast === 'function') showToast('success', res.message);
                    else alert(res.message);
                    
                    $('#changePasswordForm')[0].reset();
                    $('#userProfileModal').modal('hide');
                } else {
                    if (typeof showToast === 'function') showToast('error', res.message);
                    else alert(res.message);
                }
                btn.prop('disabled', false).html(originalText);
            },
            error: function() {
                if (typeof showToast === 'function') showToast('error', 'An unexpected error occurred.');
                else alert('An unexpected error occurred.');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
</body>
</html>
