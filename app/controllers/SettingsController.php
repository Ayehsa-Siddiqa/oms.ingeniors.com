<?php

class SettingsController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requirePermission('settings');
        $db = Database::connection();
        
        $rawSettings = $db->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);

        $defaults = [
            'company_name' => 'INGENIORS',
            'company_email' => 'info@ingeniors.com',
            'company_website' => 'https://ingeniors.com',
            'company_phone' => '+92 (0) 42 1234567',
            'company_address' => 'Engineering Operations Office, Lahore, Pakistan',
            'theme_color' => '#4f8cff',
            'default_theme' => 'dark',
            'sidebar_default' => 'expanded',
            'max_upload_size_mb' => '50',
        ];

        $settings = array_merge($defaults, $rawSettings);

        $this->view('settings/index', [
            'title' => 'Settings',
            'settings' => $settings,
        ]);
    }

    public function update(): void
    {
        verify_csrf();
        AuthMiddleware::requirePermission('settings');
        $db = Database::connection();

        $allowedKeys = [
            'company_name',
            'company_email',
            'company_website',
            'company_phone',
            'company_address',
            'theme_color',
            'default_theme',
            'sidebar_default',
            'max_upload_size_mb',
        ];

        $stmt = $db->prepare('
            INSERT INTO settings (setting_key, setting_value)
            VALUES (:key, :value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ');

        foreach ($allowedKeys as $key) {
            $val = trim((string)($_POST[$key] ?? ''));
            $stmt->execute(['key' => $key, 'value' => $val]);
        }

        ActivityLogger::log(Auth::user()['id'] ?? null, 'Updated Settings', 'Settings');
        flash('success', 'Settings updated successfully.');
        $this->redirect('settings');
    }
}
