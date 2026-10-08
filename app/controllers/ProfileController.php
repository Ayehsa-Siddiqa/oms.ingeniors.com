<?php

class ProfileController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $this->view('profile/index', ['title' => 'Profile', 'user' => Auth::user()]);
    }

    public function updatePassword(): void
    {
        verify_csrf();
        AuthMiddleware::requireAuth();
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 8 || $password !== ($_POST['password_confirmation'] ?? '')) {
            flash('error', 'Password must be at least 8 characters and confirmed.');
            $this->redirect('profile');
        }
        $stmt = Database::connection()->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute(['password' => password_hash($password, PASSWORD_DEFAULT), 'id' => Auth::user()['id']]);
        ActivityLogger::log(Auth::user()['id'], 'Password Change', 'Profile');
        flash('success', 'Password changed successfully.');
        $this->redirect('profile');
    }
}
