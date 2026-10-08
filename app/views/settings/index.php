<div class="settings-page pb-4">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="mb-1 d-flex align-items-center gap-2 fw-bold">
                <i class="bi bi-sliders text-primary"></i> Company &amp; System Settings
            </h3>
            <p class="text-muted mb-0 small">
                Configure organizational profile, branding colors, default theme, sidebar behavior, and upload limits.
            </p>
        </div>
        <button class="btn btn-primary d-inline-flex align-items-center gap-1 shadow-sm px-4" type="button" onclick="document.getElementById('settingsForm').submit();">
            <i class="bi bi-check2-circle"></i>
            <span>Save Settings</span>
        </button>
    </div>

    <!-- Main Settings Form -->
    <form id="settingsForm" method="post" action="<?= url('settings') ?>">
        <?= csrf_field() ?>

        <div class="vstack gap-4">
            <!-- 1. Company Profile Section -->
            <div class="card shadow-sm border-0" style="border-radius: 14px; background: var(--oms-panel);">
                <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-2 bg-primary-subtle text-primary" style="width:34px;height:34px;">
                        <i class="bi bi-building fs-6"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">Company Profile</h5>
                        <small class="text-muted">General organizational details and official contact information</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                            <input class="form-control" name="company_name" value="<?= e($settings['company_name']) ?>" required>
                            <div class="form-text">Displayed on headers, public dashboard, and print reports.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Company Email <span class="text-danger">*</span></label>
                            <input class="form-control" name="company_email" type="email" value="<?= e($settings['company_email']) ?>" required>
                            <div class="form-text">Primary contact email address.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Website</label>
                            <input class="form-control" name="company_website" type="url" value="<?= e($settings['company_website'] ?? '') ?>" placeholder="https://ingeniors.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone / Helpline</label>
                            <input class="form-control" name="company_phone" value="<?= e($settings['company_phone'] ?? '') ?>" placeholder="+92 (0) 42 1234567">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Office Address</label>
                            <textarea class="form-control" name="company_address" rows="2" placeholder="Street, City, Country…"><?= e($settings['company_address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Branding & User Interface Section -->
            <div class="card shadow-sm border-0" style="border-radius: 14px; background: var(--oms-panel);">
                <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-2 bg-primary-subtle text-primary" style="width:34px;height:34px;">
                        <i class="bi bi-palette fs-6"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">Branding &amp; User Interface</h5>
                        <small class="text-muted">Brand accent colors, buttons, borders, default theme, and sidebar layout</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Brand Primary Theme Color</label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="color" class="form-control form-control-color border-0 p-1 rounded-3 shadow-sm" id="themeColorPicker" value="<?= e($settings['theme_color']) ?>" style="width: 54px; height: 42px; cursor: pointer;">
                                <input class="form-control font-monospace" name="theme_color" id="themeColorHex" value="<?= e($settings['theme_color']) ?>">
                            </div>
                            <div class="d-flex gap-2 mt-2 align-items-center flex-wrap">
                                <span class="small text-muted me-1">Presets:</span>
                                <button type="button" class="btn btn-sm p-2 rounded-circle border color-swatch" style="background: #4f8cff; width: 26px; height: 26px;" data-color="#4f8cff" title="Electric Blue"></button>
                                <button type="button" class="btn btn-sm p-2 rounded-circle border color-swatch" style="background: #0B3D91; width: 26px; height: 26px;" data-color="#0B3D91" title="Deep Navy"></button>
                                <button type="button" class="btn btn-sm p-2 rounded-circle border color-swatch" style="background: #10b981; width: 26px; height: 26px;" data-color="#10b981" title="Emerald Green"></button>
                                <button type="button" class="btn btn-sm p-2 rounded-circle border color-swatch" style="background: #6366f1; width: 26px; height: 26px;" data-color="#6366f1" title="Indigo"></button>
                                <button type="button" class="btn btn-sm p-2 rounded-circle border color-swatch" style="background: #8b5cf6; width: 26px; height: 26px;" data-color="#8b5cf6" title="Violet"></button>
                                <button type="button" class="btn btn-sm p-2 rounded-circle border color-swatch" style="background: #ef4444; width: 26px; height: 26px;" data-color="#ef4444" title="Crimson"></button>
                            </div>
                            <div class="form-text mt-2">Applies immediately to all buttons, borders, badges, active tabs, and primary accents across the OMS.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Default System Theme</label>
                            <select class="form-select" name="default_theme" id="defaultThemeSelect">
                                <option value="dark" <?= ($settings['default_theme'] === 'dark') ? 'selected' : '' ?>>🌙 Dark Theme (Recommended for Engineering)</option>
                                <option value="light" <?= ($settings['default_theme'] === 'light') ? 'selected' : '' ?>>☀️ Light Theme</option>
                            </select>
                            <div class="form-text">Sets the default appearance mode for all users across the system.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Default Sidebar Mode</label>
                            <select class="form-select" name="sidebar_default" id="sidebarDefaultSelect">
                                <option value="expanded" <?= ($settings['sidebar_default'] === 'expanded') ? 'selected' : '' ?>>Expanded (Full labels &amp; categories)</option>
                                <option value="collapsed" <?= ($settings['sidebar_default'] === 'collapsed') ? 'selected' : '' ?>>Collapsed (Compact icon-only rail)</option>
                            </select>
                            <div class="form-text">Controls default sidebar expansion state.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. File Uploads Section -->
            <div class="card shadow-sm border-0" style="border-radius: 14px; background: var(--oms-panel);">
                <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-2 bg-primary-subtle text-primary" style="width:34px;height:34px;">
                        <i class="bi bi-cloud-arrow-up fs-6"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">File Uploads &amp; Attachments</h5>
                        <small class="text-muted">Maximum file attachment size limits for documents and project files</small>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Upload File Size (MB)</label>
                            <select class="form-select" name="max_upload_size_mb">
                                <option value="10" <?= ($settings['max_upload_size_mb'] === '10') ? 'selected' : '' ?>>10 MB</option>
                                <option value="25" <?= ($settings['max_upload_size_mb'] === '25') ? 'selected' : '' ?>>25 MB</option>
                                <option value="50" <?= ($settings['max_upload_size_mb'] === '50') ? 'selected' : '' ?>>50 MB (Recommended for CAD/Docs)</option>
                                <option value="100" <?= ($settings['max_upload_size_mb'] === '100') ? 'selected' : '' ?>>100 MB</option>
                            </select>
                            <div class="form-text">Controls file upload limits for Word, Excel, PowerPoint, PDF, Images, Text, ZIP, and CAD files.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="d-flex justify-content-end gap-2 pt-2">
                <button class="btn btn-primary px-4 py-2 shadow-sm" type="submit">
                    <i class="bi bi-check2-circle me-1"></i> Save Settings
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var picker = document.getElementById('themeColorPicker');
    var hexInput = document.getElementById('themeColorHex');

    if (picker && hexInput) {
        picker.addEventListener('input', function() {
            hexInput.value = picker.value;
        });
        hexInput.addEventListener('input', function() {
            if (/^#[0-9A-F]{6}$/i.test(hexInput.value)) {
                picker.value = hexInput.value;
            }
        });
        document.querySelectorAll('.color-swatch').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var col = btn.getAttribute('data-color');
                if (col) {
                    picker.value = col;
                    hexInput.value = col;
                }
            });
        });
    }

    // On form submit, synchronize localStorage so theme and sidebar default take effect immediately
    var form = document.getElementById('settingsForm');
    if (form) {
        form.addEventListener('submit', function() {
            var themeSel = document.getElementById('defaultThemeSelect');
            var sideSel = document.getElementById('sidebarDefaultSelect');
            if (themeSel) {
                localStorage.setItem('oms_theme', themeSel.value);
            }
            if (sideSel) {
                localStorage.setItem('oms_sidebar_collapsed', sideSel.value === 'collapsed' ? '1' : '0');
            }
        });
    }
});
</script>
