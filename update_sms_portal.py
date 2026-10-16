import sys

with open('manager/sms_portal.php', 'r', encoding='utf-8') as f:
    content = f.read()

php_old = '''// Fetch current settings
$settings = [
    'gateway_url' => '',
    'api_key' => '',
    'sender_id' => ''
];
$res = $conn->query("SELECT * FROM sms_settings_tbl LIMIT 1");
if ($res && $res->num_rows > 0) {
    $settings = $res->fetch_assoc();
}'''
php_new = '''// Fetch current settings
$settings = [
    'is_active' => 1,
    'gateway_url' => '',
    'http_method' => 'GET',
    'recipient_param' => 'msisdn',
    'message_param' => 'sms',
    'http_headers' => '{}',
    'additional_params' => '{}'
];
$res = $conn->query("SELECT * FROM sms_settings_tbl LIMIT 1");
if ($res && $res->num_rows > 0) {
    $settings = array_merge($settings, $res->fetch_assoc());
}'''
content = content.replace(php_old, php_new)

form_old = '''<form id="smsConfigForm">
                    <div class="mb-3">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size: 0.9rem;">Gateway URL</label>
                        <input type="url" name="gateway_url" class="form-control shadow-none border-slate-200" placeholder="https://api.sms-provider.com/send" value="<?php echo htmlspecialchars($settings['gateway_url']); ?>" required>
                        <div class="form-text text-slate-400" style="font-size: 0.75rem;">The base URL for sending SMS.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size: 0.9rem;">API Key</label>
                        <input type="text" name="api_key" class="form-control shadow-none border-slate-200" placeholder="Enter your API Key" value="<?php echo htmlspecialchars($settings['api_key']); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-slate-700 fw-semibold" style="font-size: 0.9rem;">Sender ID</label>
                        <input type="text" name="sender_id" class="form-control shadow-none border-slate-200" placeholder="e.g. 88018471..." value="<?php echo htmlspecialchars($settings['sender_id']); ?>">
                        <div class="form-text text-slate-400" style="font-size: 0.75rem;">Your registered Sender ID (if applicable).</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold shadow-sm d-flex justify-content-center align-items-center">
                        <i class="bi bi-cloud-arrow-up me-2"></i> Save Configuration
                    </button>
                </form>'''

form_new = '''<form id="smsConfigForm">
                    <div class="d-flex align-items-center mb-4">
                        <div class="form-check form-switch ms-0 ps-0 d-flex align-items-center">
                            <input class="form-check-input mt-0 me-2 shadow-none cursor-pointer" type="checkbox" role="switch" name="is_active" id="is_active" value="1" <?php echo ($settings['is_active'] == 1) ? 'checked' : ''; ?> style="width: 2.5rem; height: 1.25rem; margin-left:0;">
                            <label class="form-check-label fw-bold text-slate-700 cursor-pointer" for="is_active">Enable SMS Notifications & Alerts</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-slate-500 fw-semibold text-uppercase" style="font-size: 0.75rem;">SMS API GATEWAY URL</label>
                        <input type="url" name="gateway_url" class="form-control shadow-none border-slate-200" placeholder="https://isms.gennet.com.bd/api/v3/send-sms" value="<?php echo htmlspecialchars($settings['gateway_url']); ?>" required>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-slate-500 fw-semibold text-uppercase" style="font-size: 0.75rem;">HTTP METHOD</label>
                            <select name="http_method" class="form-select shadow-none border-slate-200">
                                <option value="GET" <?php echo ($settings['http_method'] == 'GET') ? 'selected' : ''; ?>>GET</option>
                                <option value="POST" <?php echo ($settings['http_method'] == 'POST') ? 'selected' : ''; ?>>POST</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-slate-500 fw-semibold text-uppercase" style="font-size: 0.75rem;">RECIPIENT PARAMETER KEY</label>
                            <input type="text" name="recipient_param" class="form-control shadow-none border-slate-200" placeholder="msisdn" value="<?php echo htmlspecialchars($settings['recipient_param']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-slate-500 fw-semibold text-uppercase" style="font-size: 0.75rem;">MESSAGE PARAMETER KEY</label>
                            <input type="text" name="message_param" class="form-control shadow-none border-slate-200" placeholder="sms" value="<?php echo htmlspecialchars($settings['message_param']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-slate-500 fw-semibold text-uppercase" style="font-size: 0.75rem;">HTTP HEADERS (JSON FORMAT)</label>
                        <textarea name="http_headers" class="form-control shadow-none border-slate-200 font-monospace text-slate-600" rows="3"><?php echo htmlspecialchars($settings['http_headers']); ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-slate-500 fw-semibold text-uppercase" style="font-size: 0.75rem;">ADDITIONAL QUERY/BODY PARAMETERS (JSON FORMAT)</label>
                        <textarea name="additional_params" class="form-control shadow-none border-slate-200 font-monospace text-slate-600" rows="4"><?php echo htmlspecialchars($settings['additional_params']); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold shadow-sm d-flex justify-content-center align-items-center">
                        <i class="bi bi-cloud-arrow-up me-2"></i> Save Configuration
                    </button>
                </form>'''

content = content.replace(form_old, form_new)

header_old = '''<h5 class="fw-bold text-slate-800 mb-0">API Gateway Setup</h5>
                        <small class="text-slate-500">Configure your SMS provider details</small>'''
header_new = '''<h5 class="fw-bold text-slate-600 text-uppercase mb-0" style="font-size: 0.9rem; letter-spacing: 0.5px;">Gateway Configuration Settings</h5>'''

content = content.replace(header_old, header_new)

with open('manager/sms_portal.php', 'w', encoding='utf-8') as f:
    f.write(content)

print('Updated manager/sms_portal.php successfully')
