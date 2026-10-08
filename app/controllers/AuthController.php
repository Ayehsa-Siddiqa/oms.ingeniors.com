<?php

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->view('auth/login', ['title' => 'Login'], 'auth');
    }

    public function login(): void
    {
        verify_csrf();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (Auth::login($email, $password, !empty($_POST['remember']))) {
            flash('show_announcement_toast', '1');
            $this->redirect('dashboard');
        }

        flash('error', 'Invalid email or password.');
        $this->redirect('login');
    }

    public function forgotForm(): void
    {
        $this->view('auth/forgot', ['title' => 'Forgot Password'], 'auth');
    }

    public function forgot(): void
    {
        verify_csrf();
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid email address.');
            $this->redirect('forgot-password');
        }

        $db = Database::connection();
        $userStmt = $db->prepare('SELECT id, name, status FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
        $userStmt->execute(['email' => $email]);
        $user = $userStmt->fetch();

        if (!$user) {
            flash('error', 'No active account found with that email address.');
            $this->redirect('forgot-password');
            return;
        }

        if (($user['status'] ?? 'Active') !== 'Active') {
            flash('error', 'This account is currently inactive. Please contact your administrator.');
            $this->redirect('forgot-password');
            return;
        }

        $token = bin2hex(random_bytes(32));
        $stmt = $db->prepare(
            'INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())'
        );
        $stmt->execute(['email' => $email, 'token' => hash('sha256', $token)]);

        $resetLink = absolute_url('reset-password?token=' . $token . '&email=' . urlencode($email));
        $logoUrl = absolute_asset('images/ingeniors-light-logo.jpeg');
        $userName = !empty($user['name']) ? htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') : 'User';

        $subject = 'Password Reset Request - INGENIORS OMS';
        $messageHtml = '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Reset</title>
</head>
<body style="margin: 0; padding: 30px 15px; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 560px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid #e2e8f0; overflow: hidden;">
                    <!-- Centered Logo Header -->
                    <tr>
                        <td align="center" style="padding: 36px 30px 24px 30px; background-color: #ffffff; border-bottom: 1px solid #f1f5f9;">
                            <img src="' . $logoUrl . '" alt="INGENIORS" style="max-width: 180px; height: auto; display: block; margin: 0 auto; border-radius: 8px;">
                            <p style="margin: 10px 0 0 0; font-size: 11.5px; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 600;">Engineering Operations</p>
                        </td>
                    </tr>
                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 36px;">
                            <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #0f172a;">Password Reset Request</h2>
                            <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">Hello <strong>' . $userName . '</strong>,</p>
                            <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #334155;">We received a request to reset your password for your <strong>INGENIORS OMS</strong> account. Click the button below to set a new password:</p>
                            
                            <!-- Action Button -->
                            <div style="text-align: center; margin: 28px 0;">
                                <a href="' . $resetLink . '" target="_blank" style="background-color: #2563eb; color: #ffffff; padding: 13px 32px; font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 8px; display: inline-block; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.28);">Reset Password</a>
                            </div>

                            <p style="margin: 24px 0 8px 0; font-size: 13px; line-height: 1.5; color: #64748b;">If the button above does not work, copy and paste the full link below into your web browser:</p>
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; word-break: break-all; margin-bottom: 24px;">
                                <a href="' . $resetLink . '" style="color: #2563eb; font-size: 12.5px; font-family: monospace; text-decoration: underline; line-height: 1.4;">' . $resetLink . '</a>
                            </div>

                            <p style="margin: 0; font-size: 12.5px; line-height: 1.5; color: #94a3b8;">This password reset link will expire in <strong>1 hour</strong>. If you did not request this, please ignore this email.</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 20px 30px; background-color: #f8fafc; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8;">
                            &copy; ' . date('Y') . ' INGENIORS. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: INGENIORS OMS <no-reply@ingeniors.com>\r\n";
        $headers .= "Reply-To: no-reply@ingeniors.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        @mail($email, $subject, $messageHtml, $headers);

        flash('success', 'A password reset link has been sent to your email.');
        $this->redirect('forgot-password');
    }

    public function resetForm(): void
    {
        $email = trim($_GET['email'] ?? '');
        $token = trim($_GET['token'] ?? '');

        if (!$this->validResetToken($email, $token)) {
            flash('error', 'Password reset link is invalid or expired.');
            $this->redirect('forgot-password');
        }

        $this->view('auth/reset', [
            'title' => 'Reset Password',
            'email' => $email,
            'token' => $token,
        ], 'auth');
    }

    public function reset(): void
    {
        verify_csrf();
        $email = trim($_POST['email'] ?? '');
        $token = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';

        if (!$this->validResetToken($email, $token)) {
            flash('error', 'Password reset link is invalid or expired.');
            $this->redirect('forgot-password');
        }

        if (strlen($password) < 8 || $password !== $confirmation) {
            flash('error', 'Password must be at least 8 characters and match confirmation.');
            $this->redirect('reset-password?token=' . urlencode($token) . '&email=' . urlencode($email));
        }

        $db = Database::connection();
        $stmt = $db->prepare('UPDATE users SET password = :password, remember_token = NULL, remember_expires_at = NULL WHERE email = :email');
        $stmt->execute([
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email' => $email,
        ]);

        $stmt = $db->prepare('DELETE FROM password_resets WHERE email = :email');
        $stmt->execute(['email' => $email]);

        ActivityLogger::log(null, 'Password Reset', 'Authentication');
        flash('success', 'Password updated successfully. You can now login.');
        $this->redirect('login');
    }

    private function validResetToken(string $email, string $token): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $token === '') {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'SELECT token FROM password_resets WHERE email = :email AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row && hash_equals($row['token'], hash('sha256', $token));
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('login');
    }
}
